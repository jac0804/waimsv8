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

class received_payment
{
  public $modulename = 'Received Payment Report';
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
      case 10: //afti
      case 12: // afti usd
        $fields = ['radioprint', 'start', 'end', 'dbranchname', 'collectorname', 'dclientname', 'dagentname', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'collectorname.label', 'Collection Officer');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'collectorname.action', 'lookupcollector');
        data_set($col1, 'dclientname.label', 'Customer');

        break;

      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'dclientname', 'dagentname', 'approved', 'radiooption'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'radiooption.name', 'sortby');
        data_set(
          $col1,
          'radiooption.options',
          [
            ['label' => 'Doc No.', 'value' => 'docno', 'color' => 'magenta'],
            ['label' => 'Customer', 'value' => 'customer', 'color' => 'blue']
          ]
        );

        break;
      case 21: //kinggeorge
        $fields = ['radioprint', 'start', 'end', 'terms', 'dcentername', 'reportusers', 'dclientname', 'dagentname', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'terms.lookupclass', 'ledgerterms');
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        break;

      default:
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'dclientname', 'dagentname', 'approved'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'approved.label', 'Prefix');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        break;
    }

    $fields = ['radioreporttype'];
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

    $qry = "select 
    'default' as print,
     adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as reporttype,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as reportusers,
    '' as dagentname,
    '' as agent, 
    '' as agentname,
    0 as agentid,
    '' as dclientname,
    '' as client,
    '' as clientname,
    '' as clientid,
         '' as branchcode,
          '' as branch,
          '' as branchid,
          '' as branchname,
          '' as dbranchname,
          '' as collectorid,
          '' as collectorcode,
          '' as collectorname,
          '' as collector,
          'docno' as sortby,
          '' as terms
    
    ";

    return $this->coreFunctions->opentable($qry);
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

    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 10: //afti
      case 12: // afti usd
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->afti_detailed_layout($config);
            break;
        }
        break;
      case 17: //unihome
      case 28: //xcomp
      case 32: //3m
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportUnihomeLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
      case 39: //CBBSI
        switch ($reporttype) {
          case '0':
            $result = $this->reportUnihomeLayout_SUMMARIZED($config);
            break;
          case '1':
            $result = $this->reportDefaultLayout_cbbsi_DETAILED($config);
            break;
        }
        break;
      case 34: //evergreen
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportEvergreenLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;

      case 29: //sbc default
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->report_SBC_tLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
      default: // default
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->default_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->afti_detailed_query($config);
            break;
        }
        break;
      case 17: //unihome
      case 28: //xcomp
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->unihome_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->unihome_QUERY_DETAILED($config);
            break;
        }
        break;
      case 39: //cbbsi
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->unihome_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->cbbsi_query_detailed($config);
            break;
        }
        break;

      case 34: //evergreen
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->evergreen_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->default_QUERY_DETAILED($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->default_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->default_QUERY_DETAILED($config);
            break;
        }
        break;
    }

    // return $query;
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customerid   = $config['params']['dataparams']['clientid'];
    $customer   = $config['params']['dataparams']['client'];


    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($agentname != "") {
      $filter .= " and agent.clientid = '$agentid'";
    }

    if ($customer != "") {
      $filter .= " and customer.clientid = '$customerid'";
    }


    $companyid = $config['params']['companyid'];
    if ($companyid == 21) { //kinggeorge
      $terms    = $config['params']['dataparams']['terms'];
      if ($terms != "") {
        $filter .= " and head.terms = '$terms'";
      }
    }

    $addfield = '';
    if ($companyid == 32) { //3m
      $addfield = ",hclient.brgy, hclient.area";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref,
    agent.client as agentcode, agent.clientname as agentname " . $addfield . "
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno left join client as hclient on hclient.client=head.client
    left join client as dclient on dclient.client=detail.client
    left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    left join client as agent on agent.client = head.agent
    left join client as customer on customer.client = head.client
    where head.doc='cr' and head.dateid between '$start' and '$end' $filter 
    union all
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref,
    agent.client as agentcode, agent.clientname as agentname " . $addfield . "
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno left join client as hclient on hclient.clientid=head.clientid
    left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    left join client as agent on agent.clientid = head.agentid
    left join client as customer on customer.clientid = head.clientid
    where head.doc='cr' and head.dateid between '$start' and '$end' $filter
    order by docno,cr";

    return $query;
  }

  public function evergreen_QUERY_SUMMARIZED($config)
  {
    $companyid  = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customerid   = $config['params']['dataparams']['clientid'];
    $customer   = $config['params']['dataparams']['client'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($agentname != "") {
      $filter .= " and agent.clientid = '$agentid'";
    }

    if ($customer != "") {
      $filter .= " and customer.clientid = '$customerid'";
    }


    $companyid = $config['params']['companyid'];


    if ($companyid == 21) { //kinggeorge
      $terms    = $config['params']['dataparams']['terms'];
      if ($terms != "") {
        $filter .= " and head.terms = '$terms'";
      }
    }

    $addfield = '';
    $addfield2 = '';
    if ($companyid == 32) { //3m
      $addfield = ',brgy, area';
      $addfield2 = ',hclient.brgy, hclient.area';
    }


    $query = "select planholder,docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, sum(cr) as credit, rem, hclientname,yourref,ourref " . $addfield . "
    from(
    select info.clientname as planholder,head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref " . $addfield2 . "
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join cntnuminfo as ci on ci.trno = head.trno
      left join glhead as cp on cp.trno=ci.cptrno
      left join cntnum as trn on trn.trno = cp.trno
      left join heahead as ea on ea.trno=cp.aftrno
      left join heainfo as info on info.trno=ea.trno
      left join client as agent on agent.client = head.agent
      left join client as customer on customer.client = head.client
      where head.doc='cr' and head.dateid between '$start' and '$end' $filter 
      union all
      select info.clientname as planholder,head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref " . $addfield2 . "
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.clientid=head.clientid
      left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join hcntnuminfo as ci on ci.trno = head.trno
      left join glhead as cp on cp.trno=ci.cptrno
      left join cntnum as trn on trn.trno = cp.trno
      left join heahead as ea on ea.trno=cp.aftrno
      left join heainfo as info on info.trno=ea.trno
      left join client as agent on agent.clientid = head.agentid
      left join client as customer on customer.clientid = head.clientid
      where head.doc='cr' and head.dateid between '$start' and '$end' $filter) as t 
      group by docno, createby, dateid, rem, hclientname,planholder,yourref,ourref" . $addfield;

    $this->coreFunctions->Logconsole($query);
    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $companyid  = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customerid   = $config['params']['dataparams']['clientid'];
    $customer   = $config['params']['dataparams']['client'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($agentname != "") {
      $filter .= " and agent.clientid = '$agentid'";
    }

    if ($customer != "") {
      $filter .= " and customer.clientid = '$customerid'";
    }


    $companyid = $config['params']['companyid'];


    if ($companyid == 21) { //kinggeorge
      $terms    = $config['params']['dataparams']['terms'];
      if ($terms != "") {
        $filter .= " and head.terms = '$terms'";
      }
    }

    $addfield = '';
    $addfield2 = '';
    if ($companyid == 32) { //3m
      $addfield = ',brgy, area';
      $addfield2 = ',hclient.brgy, hclient.area';
    }


    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, sum(cr) as credit, rem, hclientname,yourref,ourref " . $addfield . "
    from(
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref " . $addfield2 . "
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join client as agent on agent.client = head.agent
      left join client as customer on customer.client = head.client
      where head.doc='cr' and head.dateid between '$start' and '$end' $filter 
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref " . $addfield2 . "
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.clientid=head.clientid
      left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join client as agent on agent.clientid = head.agentid
      left join client as customer on customer.clientid = head.clientid
      where head.doc='cr' and head.dateid between '$start' and '$end' $filter) as t 
      group by docno, createby, dateid, rem, hclientname,yourref,ourref" . $addfield . " order by docno";

    return $query;
  }

  public function cbbsi_query_detailed($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customerid   = $config['params']['dataparams']['clientid'];
    $customer   = $config['params']['dataparams']['client'];
    $sortby    = $config['params']['dataparams']['sortby'];
    $sort = '';

    $filter = "";
    if ($prefix != "") $filter .= " and cntnum.bref = '$prefix' ";
    if ($filterusername != "") $filter .= " and head.createby = '$filterusername' ";
    if ($fcenter != "") $filter .= " and cntnum.center = '$fcenter'";
    if ($agentname != "") $filter .= " and agent.clientid = '$agentid'";

    if ($customer != "") $filter .= " and customer.clientid = '$customerid'";

    if ($sortby != 'docno') {
      $sort = 'hclientname';
    } else {
      $sort = 'docno';
    }

    $query = "select case(head2.doc) when 'AR' then detail2.ref else detail.ref end as ref, head.createby,head.docno,hclient.client as hclient,
    hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,head.ourref,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,
    agent.client as agentcode, agent.clientname as agentname 
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno left join client as hclient on hclient.client=head.client
    left join client as dclient on dclient.client=detail.client
    left join gldetail as detail2 on detail2.trno=detail.refx and detail2.line=detail.linex
    left join glhead as head2 on head2.trno=detail2.trno
    left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    left join client as agent on agent.client = head.agent
    left join client as customer on customer.client = head.client
    where head.doc='cr' and head.dateid between '$start' and '$end' $filter 
    union all
    select case(head2.doc) when 'AR' then detail2.ref else detail.ref end as ref, head.createby,head.docno,hclient.client as hclient,hclient.
    clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,head.ourref,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,
    agent.client as agentcode, agent.clientname as agentname 
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno left join client as hclient on hclient.clientid=head.clientid
    left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
    left join gldetail as detail2 on detail2.trno=detail.refx and detail2.line=detail.linex
    left join glhead as head2 on head2.trno=detail2.trno
    left join cntnum on cntnum.trno=head.trno
    left join client as agent on agent.clientid = head.agentid
    left join client as customer on customer.clientid = head.clientid
    where head.doc='cr' and head.dateid between '$start' and '$end' $filter
    order by dateid, $sort";
    return $query;
  }

  public function unihome_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customerid   = $config['params']['dataparams']['clientid'];
    $customer   = $config['params']['dataparams']['client'];
    $sortby    = $config['params']['dataparams']['sortby'];
    $sort = '';

    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($agentname != "") {
      $filter .= " and agent.clientid = '$agentid'";
    }

    if ($customer != "") {
      $filter .= " and customer.clientid = '$customerid'";
    }


    if ($sortby != 'docno') {
      $sort = 'hclientname';
    } else {
      $sort = 'docno';
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,head.ourref,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref,
    agent.client as agentcode, agent.clientname as agentname 
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno left join client as hclient on hclient.client=head.client
    left join client as dclient on dclient.client=detail.client
    left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    left join client as agent on agent.client = head.agent
    left join client as customer on customer.client = head.client
    where head.doc='cr' and head.dateid between '$start' and '$end' $filter 
    union all
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,head.ourref,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref,
    agent.client as agentcode, agent.clientname as agentname 
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno left join client as hclient on hclient.clientid=head.clientid
    left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    left join client as agent on agent.clientid = head.agentid
    left join client as customer on customer.clientid = head.clientid
    where head.doc='cr' and head.dateid between '$start' and '$end' $filter
    order by dateid, $sort";

    return $query;
  }

  public function unihome_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customerid   = $config['params']['dataparams']['clientid'];
    $customer   = $config['params']['dataparams']['client'];
    $sortby    = $config['params']['dataparams']['sortby'];
    $sort = '';
    $filter = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($agentname != "") {
      $filter .= " and agent.clientid = '$agentid'";
    }

    if ($customer != "") {
      $filter .= " and customer.clientid = '$customerid'";
    }

    if ($sortby != 'docno') {
      $sort = 'hclientname';
    } else {
      $sort = 'docno';
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, sum(cr) as credit, rem, hclientname,yourref,ourref
    from(
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,if(left(coa.alias,2)='CA',detail.db,0) as db,if(left(coa.alias,2)='CR',detail.db,0) as cr,head.rem,detail.ref,head.yourref,head.ourref
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join client as agent on agent.client = head.agent
      left join client as customer on customer.client = head.client
      where head.doc='cr' and head.dateid between '$start' and '$end' $filter 
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,if(left(coa.alias,2)='CA',detail.db,0) as db,if(left(coa.alias,2)='CR',detail.db,0) as cr,head.rem,detail.ref,head.yourref,head.ourref
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.clientid=head.clientid
      left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join client as agent on agent.clientid = head.agentid
      left join client as customer on customer.clientid = head.clientid
      where head.doc='cr' and head.dateid between '$start' and '$end' $filter) as t 
      group by docno, createby, dateid, rem, hclientname,yourref,ourref
      order by $sort";

    return $query;
  }

  public function default_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $str = '';
    $layoutsize = '1200';
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
    $str .= $this->reporter->col('Received Payment Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 15;
    $page = 14;
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
    $str .= $this->default_header_detailed($config);


    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total:', '120', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '600', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '200', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          if ($companyid == 32) { //3m
            $str .= $this->reporter->col('<b>' . 'Customer: ' . '</b>' . $data->hclientname . ' - ' . $data->brgy . ', ' . $data->area, '500', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          } else {
            $str .= $this->reporter->col('<b>' . 'Customer: ' . '</b>' . $data->hclientname, '600', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          }
          $str .= $this->reporter->col('<b>' . 'Agent: ' . '</b>' . $data->agentname, '200', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Customer/Supplier', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->postdate, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '200', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dclient, '120', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '150', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '150', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '150', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '130', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $debit += $data->db;
          $credit += $data->cr;
          $totaldb += $data->db;
          $totalcr += $data->cr;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ', '120', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '150', null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->default_header_detailed($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '120', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '150', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '150', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_cbbsi_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 15;
    $page = 14;
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
    $str .= $this->default_header_detailed($config);


    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total:', '170', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '166', null, false, $font, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '166', null, false, $font, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>Docno#: </b>' . $data->docno, '400', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>Date: </b>' . $data->dateid, '200', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>Ourref: </b>' . $data->ourref, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>Customer: </b>' . $data->hclient . ' - ' . $data->hclientname, '400', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>Agent: </b>' . $data->agentname, '200', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '166', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '166', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '170', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '166', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '166', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '166', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->postdate, '166', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '166', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '170', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '166', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '166', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '166', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $debit += $data->db;
          $credit += $data->cr;
          $totaldb += $data->db;
          $totalcr += $data->cr;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ', '170', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '166', null, false, $font, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '166', null, false, $font, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->default_header_detailed($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '170', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '166', null, false, '1px dotted', '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '166', null, false, '1px dotted', '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '166', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
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
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 46;
    $page = 45;
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
    $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
    $str .= $this->summarized_header_table($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($checkno, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2) . '&nbsp&nbsp', '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
          $str .= $this->summarized_header_table($config, $layoutsize);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '200', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '300', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '200', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '160', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '140', null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '280', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function summarized_header_sbc($config, $layoutsize)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str = "";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '25', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Client Name', '225', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '25', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '225', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function report_SBC_tLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 46;
    $page = 45;
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
    $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
    $str .= $this->summarized_header_sbc($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;

        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '25', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->hclientname, '225', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $checkno = str_replace(',', ' ', $data->checkno);
        $str .= $this->reporter->col($checkno, '100', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '25', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '225', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow($layoutsize);
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
          $str .= $this->summarized_header_sbc($config, $layoutsize);
          $page = $page + $count;
        } //end if
      }

      $str .= $this->grandtotal($config, $totaldb, $totalcr);
      $str .= $this->reporter->endreport();
    }

    return $str;
  }
  public function grandtotal($config, $totaldb, $totalcr)
  {

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '25', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '225', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '25', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '225', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '225', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '225', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function summarized_header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customername    = $config['params']['dataparams']['clientname'];
    $customercode    = $config['params']['dataparams']['client'];

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
    $str .= $this->reporter->col('Received Payment Report Summarized', 800, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer: ' . $customername, '110', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Agent: ' . $agentname, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function summarized_header_table($config, $layoutsize)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str = "";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '160', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit' . '&nbsp&nbsp', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '300', null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportEvergreenLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 46;
    $page = 45;
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
    $str .= $this->esummarized_header_DEFAULT($config, $layoutsize);
    $str .= $this->esummarized_header_table($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        // $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->planholder, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');

        $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
          $str .= $this->summarized_header_table($config, $layoutsize);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '100', null, false, $font, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $font, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $font, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function esummarized_header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customername    = $config['params']['dataparams']['clientname'];
    $customercode    = $config['params']['dataparams']['client'];

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
    $str .= $this->reporter->col('Received Payment Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer: ' . $customername, '110', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Agent: ' . $agentname, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function esummarized_header_table($config, $layoutsize)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str = "";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LA #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Planholder Name', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Mode of Payment', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportUnihomeLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 46;
    $page = 45;
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
    $str .= $this->summarized_header_UNIHOME($config, $layoutsize);
    $str .= $this->summarized_headertable_unihome($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($companyid == 32) { //3m
          $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->docno, '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->hclientname, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->brgy, '75', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->area, '75', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->yourref, '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->ourref, '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $checkno = str_replace(',', '<br>', $data->checkno);
          $str .= $this->reporter->col($checkno, '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        } else {
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->hclientname, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $checkno = str_replace(',', '<br>', $data->checkno);
          $str .= $this->reporter->col($checkno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        }
        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_header_UNIHOME($config, $layoutsize);
          $str .= $this->summarized_headertable_unihome($config, $layoutsize);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 32) { //3m
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '90', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '90', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '75', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '75', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '90', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '90', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '90', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    } else {
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    }
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    if ($companyid == 32) { //3m
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('Grand Total:', '90', null, false, $font, '', 'R', $font, $fontsize, 'B', '', '', '');
    } else {
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
      $str .= $this->reporter->col('Grand Total:', '100', null, false, $font, '', 'R', $font, $fontsize, 'B', '', '', '');
    }
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $font, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $font, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_header_UNIHOME($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $customername    = $config['params']['dataparams']['clientname'];
    $customercode    = $config['params']['dataparams']['client'];

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
    $str .= $this->reporter->col('Received Payment Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer: ' . $customername, '110', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Agent: ' . $agentname, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function summarized_headertable_unihome($config, $layoutsize)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $companyid = $config['params']['companyid'];
    $fontsize = "10";
    $border = "1px solid";
    $str = "";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 32) { //3m
      $str .= $this->reporter->col('Date', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Docno', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Customer', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Barangay', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Area', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Yourref', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Ourref', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Check#', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Cash', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Check', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Customer', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Ourref', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Cash', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Check', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }


  private function afti_detailed_query($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $branch    = $config['params']['dataparams']['branch'];
    $branchid    = $config['params']['dataparams']['branchid'];
    $agentname    = $config['params']['dataparams']['agentname'];
    $agentid    = $config['params']['dataparams']['agentid'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

    $filter = "";

    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }

    if ($branch != "") {
      $filter .= " and branch.clientid = '$branch'";
    }

    if ($agentname != "") {
      $filter .= " and agent.clientid = '$agentid'";
    }

    if ($client != "") {
      $filter .= " and cl.clientid = '$clientid'";
    }


    $qry = "select detail.refx,detail.linex, head.trno, detail.line, head.docno,head.crref, date(head.dateid) as dateid, detail.poref, date(detail.podate) as podate, 
          ifnull(agent.clientname,'') as agentname, ifnull(proj.name,'') as costcenter, 
          sum(detail.cr-detail.db) as total, detail.rem, cl.tax as vat
          from glhead as head
          left join gldetail as detail on detail.trno = head.trno
          left join client as cl on cl.clientid = head.clientid
          left join client as agent on agent.clientid = detail.clientid
          left join client as branch on branch.clientid = detail.branch
          left join projectmasterfile as proj on proj.line = detail.projectid
          where head.doc = 'cr' and poref <> '' and head.dateid between '$start' and '$end' $filter
          group by detail.refx,detail.linex, head.trno, head.docno,head.crref, date(head.dateid), detail.poref, date(detail.podate), 
          ifnull(agent.clientname,''), proj.name,  detail.isvat, detail.rem, cl.tax, detail.line
          union all
          select detail.refx,detail.linex, head.trno, detail.line, head.docno,head.crref, date(head.dateid) as dateid, detail.poref, date(detail.podate) as podate, 
          ifnull(agent.clientname,'') as agentname, ifnull(proj.name,'') as costcenter, 
          sum(detail.cr-detail.db) as total, detail.rem, cl.tax as vat
          from lahead as head
          left join ladetail as detail on detail.trno = head.trno
          left join client as cl on cl.client = head.client
          left join client as agent on agent.client = detail.client
          left join projectmasterfile as proj on proj.line = detail.projectid
          left join client as branch on branch.clientid = detail.branch
          where head.doc = 'cr' and poref <> '' and head.dateid between '$start' and '$end' $filter
          group by detail.refx,detail.linex, head.trno, head.docno,head.crref, date(head.dateid), detail.poref, date(detail.podate), 
          ifnull(agent.clientname,''), proj.name, detail.rem, cl.tax, detail.line
        ";

    return $qry;
  }

  public function afti_detailed_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $collectorname  = $config['params']['dataparams']['collectorname'];
    $prefix     = $config['params']['dataparams']['approved'];

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
    if ($collectorname != "") {
      $user = $collectorname;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Received Payment Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Journal Entry', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CR No.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payment Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer PO', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer PO Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Invoice', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Partner', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost Center', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Tax Type', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function afti_detailed_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 15;
    $page = 14;
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
    $str .= $this->afti_detailed_header($config);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;
    $vat = "";
    $tax = 0;
    $vatable = 0;
    $gtotal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        if ($data->vat == 12) {
          $vat = "VATABLE SALES 12%";
          $tax = 1.12;

          $total = number_format($data->total / $tax, 2);
          $vatable = number_format($data->total - ($data->total / $tax), 2);
          $gtotal = number_format($data->total, 2);
        } else {
          $vat = "ZERO RATED";
          $tax = 0;
          $vatable = "-";
          $total = number_format($data->total, 2);
          $gtotal = number_format($data->total, 2);
        }


        $siref = $this->coreFunctions->datareader("
      select value from (select group_concat(case left(ref,2) when 'DR' then concat('SI',right(s.ref,6))
      when 'AR' then s.rem
      else concat(left(s.ref,3),right(s.ref,5)) end separator '/ ') as value
      from gldetail as s left join coa on coa.acnoid = s.acnoid
      where s.trno = " . $data->trno . " and s.line = " . $data->line . " and s.refx<>0 and coa.alias <> 'AR5'
      union all
      select group_concat(case left(ref,2) when 'DR' then concat('SI',right(s.ref,6)) 
      when 'AR' then s.rem
      else concat(left(s.ref,3),right(s.ref,5)) end separator '/ ') as value
      from ladetail as s 
      left join coa as coa on coa.acnoid = s.acnoid
      where s.trno =" . $data->trno . " and s.line = " . $data->line . " and s.refx<>0 and coa.alias <> 'AR5') as a where value is not null
      ");


        $agentname = $this->coreFunctions->datareader("select ifnull(c.clientname,'') as value from arledger as head left join client as c on c.clientid = head.agentid where head.trno = " . $data->refx . " and head.line = " . $data->linex);
        $siref = $siref != '' ? $siref : '';

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->crref, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->poref, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->podate, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($siref, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($agentname, '100', null, false, '10px solid ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->costcenter, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($vat, '120', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($total, '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($vatable, '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($gtotal, '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->afti_detailed_header($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class