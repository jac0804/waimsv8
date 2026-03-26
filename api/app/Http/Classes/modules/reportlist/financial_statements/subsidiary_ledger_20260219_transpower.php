<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

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

class subsidiary_ledger
{
  public $modulename = 'Subsidiary Ledger';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  private $logger;

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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $fields = ['dateid', 'enddate', 'dacnoname', 'dclientname', 'prepared', 'approved'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dclientname.lookupclass', 'lookupgjclient');
        data_set($col2, 'dclientname.label', 'Customer/Supplier');
        data_set($col2, 'dateid.label', 'StartDate');
        data_set($col2, 'dateid.readonly', false);
        data_set($col2, 'dacnoname.action', 'lookupcoa');
        data_set($col2, 'dacnoname.lookupclass', 'detail');

        $fields = ['radioposttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'radioposttype.options', array(
          ['label' => 'Posted Transaction', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Unposted Transaction', 'value' => '1', 'color' => 'orange'],
          ['label' => 'All Transaction', 'value' => '2', 'color' => 'orange'],
        ));

        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);
        break;

      case 15: //nathina
        $fields = ['prepared', 'approved', 'dateid', 'enddate', 'dacnoname', 'dexpacnoname'];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'dateid.label', 'StartDate');
        data_set($col2, 'dateid.readonly', false);
        data_set($col2, 'dacnoname.action', 'lookupcoa');
        data_set($col2, 'dacnoname.lookupclass', 'detail');
        data_set($col2, 'dacnoname.label', 'From Account');
        data_set($col2, 'dacnoname.required', true);

        data_set($col2, 'dexpacnoname.action', 'lookupcoa');
        data_set($col2, 'dexpacnoname.lookupclass', 'detail2');
        data_set($col2, 'dexpacnoname.label', 'To Account');
        data_set($col2, 'dexpacnoname.labeldata', 'contra2~acnoname2');

        $fields = ['dprojectname', 'dprojectname2', 'dclientname', 'radioposttype', 'radioreporttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'dprojectname.lookupclass', 'projectcode');
        data_set($col3, 'dprojectname.label', 'From Project');
        data_set($col3, 'dprojectname2.lookupclass', 'projectcode2');
        data_set($col3, 'dprojectname2.label', 'To Project');

        data_set($col3, 'radioposttype.options', array(
          ['label' => 'Posted Transaction', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Unposted Transaction', 'value' => '1', 'color' => 'orange'],
          ['label' => 'All Transaction', 'value' => '2', 'color' => 'orange'],
        ));
        data_set($col3, 'dclientname.lookupclass', 'lookupgjclient');
        data_set($col3, 'dclientname.label', 'Customer/Supplier');
        data_set($col3, 'radioreporttype.options', array(
          ['label' => 'Detailed', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Summary', 'value' => '1', 'color' => 'orange']
        ));

        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);
        break;

      default:
        $fields = ['prepared', 'approved', 'dateid', 'enddate', 'dacnoname'];

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            array_push($fields, 'dclientname', 'dcentername', 'costcenter', 'ddeptname');
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'ddeptname.label', 'Department');
            data_set($col2, 'costcenter.label', 'Item Group');
            data_set($col2, 'dclientname.lookupclass', 'lookupgjclient');
            data_set($col2, 'dclientname.label', 'Customer/Supplier');
            break;
          case 24: //goodfound
            array_push($fields, 'dcentername');
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'dcentername.action', 'lookupcenter_reports');
            break;
          case 55: //afli
            array_push($fields, 'dexpacnoname', 'dacnoname4', 'dacnoname3');
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'dexpacnoname.action', 'lookupcoa');
            data_set($col2, 'dexpacnoname.lookupclass', 'detail2');
            data_set($col2, 'dexpacnoname.label', 'Account 1');
            data_set($col2, 'dexpacnoname.labeldata', 'contra2~acnoname2');
            break;
          default:
            if ($this->companysetup->getmultibranch($config['params'])) {
              array_push($fields, 'dcentername');
            }
            $col2 = $this->fieldClass->create($fields);
            break;
        }
        data_set($col2, 'dcentername.lookupclass', 'getmultibranch');
        data_set($col2, 'dateid.label', 'StartDate');
        data_set($col2, 'dateid.readonly', false);
        data_set($col2, 'dacnoname.action', 'lookupcoa');
        data_set($col2, 'dacnoname.lookupclass', 'detail');
        data_set($col2, 'dacnoname.label', 'Account Description');

        if ($companyid != 8) { //not maxipro
          data_set($col2, 'dacnoname.required', true);
        }

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            data_set($col2, 'dacnoname.required', false);
            $fields = ['radioposttype', 'radioreporttype'];
            $col3 = $this->fieldClass->create($fields);
            break;
          case 8: //maxipro
            $fields = ['radioposttype', 'dprojectname', 'subprojectname', 'dclientname', 'radioreporttype'];
            $col3 = $this->fieldClass->create($fields);
            data_set($col3, 'dprojectname.required', false);
            data_set($col3, 'subprojectname.required', false);
            data_set($col3, 'subprojectname.readonly', false);
            data_set($col3, 'dprojectname.lookupclass', 'projectcode');
            break;
          default:
            $fields = ['dprojectname', 'dclientname', 'radioposttype', 'radioreporttype'];
            $col3 = $this->fieldClass->create($fields);
            data_set($col3, 'dprojectname.lookupclass', 'projectcode');
            break;
        }

        data_set($col3, 'radioposttype.options', array(
          ['label' => 'Posted Transaction', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Unposted Transaction', 'value' => '1', 'color' => 'orange'],
          ['label' => 'All Transaction', 'value' => '2', 'color' => 'orange'],
        ));
        data_set($col3, 'dclientname.lookupclass', 'lookupgjclient');
        data_set($col3, 'dclientname.label', 'Customer/Supplier');

        if ($companyid == 32) { //3m
          data_set($col3, 'radioreporttype.options', array(
            ['label' => 'Detailed', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Summary', 'value' => '1', 'color' => 'orange'],
            ['label' => 'With Items', 'value' => '2', 'color' => 'orange']
          ));
        } else {
          data_set($col3, 'radioreporttype.options', array(
            ['label' => 'Detailed', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Summary', 'value' => '1', 'color' => 'orange']
          ));
        }
        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);
        break;
    }
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $prep = '';
    $preparedby = '';


    if ($companyid == 21) { //kinggeorge
      if (isset($config['params']['user'])) {
        $prep = $config['params']['user'];
        $preparedby = $this->coreFunctions->datareader("select name as value from useraccess where username=? limit 1", [$prep]);
      }
    }

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab

        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-30) as dateid,
        left(now(),10) as enddate,
        '' as prepared,
        '' as approved,
        '' as dacnoname,
        '' as contra,
        '' as acnoname,
        '' as dclientname,
        '' as client,
        '' as clientname,
        '' as clientid,
        '' as center,
        '' as code,
        '' as costcenter,
        0 as clientid,
        0 as acnoid,
        '' as cat,          
        '0' as posttype
        ");
        break;
      case 15: //nathina
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as dateid,
        left(now(),10) as enddate,
        '' as contra,
        '' as contra2,
        '' as acnoname,
        '' as acnoname2,
        '' as dacnoname,
        '' as dexpacnoname,

        '' as client,
        '' as clientname,
        '' as dclientname,
        0 as clientid,
        0 as acnoid,          
        '' as cat,

        '$preparedby' as prepared,
        '' as approved,

        '0' as posttype,
        '0' as reporttype,'' as dprojectname,'' as dprojectname2, '' as projectname, '' as projectcode, '' as projectname2, '' as projectcode2";

        return $this->coreFunctions->opentable($paramstr);

        break;

      default:
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as dateid,
        left(now(),10) as enddate,
        '' as contra,
        '' as acnoname,
        '' as dacnoname,

        '' as client,
        '' as clientname,
        '' as dclientname,
        0 as clientid,
        0 as acnoid,   
        '' as cat,  
        '0' as projectid,   
        
        '$preparedby' as prepared,
        '' as approved,

        '0' as posttype,
        '0' as reporttype";

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            $paramstr .= ",'' as center,'' as centername,'' as dcentername,'' as code,'' as name,'' as costcenter,'0' as costcenterid, '' as ddeptname, '' as dept, '' as deptname ";
            break;
          case 24: //goodfound
            $paramstr .= ",'' as dprojectname, '' as projectname, '' as projectcode,
            '" . $defaultcenter[0]['center'] . "' as center,
            '" . $defaultcenter[0]['centername'] . "' as centername,
            '" . $defaultcenter[0]['dcentername'] . "' as dcentername";
            break;
          case 8: //maxipro
            $paramstr .= ",'' as dprojectname, '' as projectname, '' as projectcode,'' as subprojectname";
            break;
          case 55: //afli
            $paramstr .= ", '' as contra2, '' as acnoname2, '' as dexpacnoname, '0' as acnoid2
                          , '' as contra3, '' as acnoname3, '' as dacnoname3, '0' as acnoid3  
                          , '' as contra4, '' as acnoname4, '' as dacnoname4, '0' as acnoid4                                                      ";
            break;
          default:
            $paramstr .= ",'' as dprojectname, '' as projectname, '' as projectcode, 
            '" . $defaultcenter[0]['center'] . "' as center,
            '" . $defaultcenter[0]['centername'] . "' as centername,
            '" . $defaultcenter[0]['dcentername'] . "' as dcentername";
            break;
        }

        return $this->coreFunctions->opentable($paramstr);

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
    if ($config['params']['companyid'] == 8) { //maxipro
      if ($config['params']['dataparams']['dacnoname'] == '') {
        if ($config['params']['dataparams']['dprojectname'] != '' || $config['params']['dataparams']['dclientname'] != '') {
          $str = $this->reportplotting($config);
        } else {
          $str = $this->notallowtoprint($config, "Select project/customer.");
        }
      } else {
        $str = $this->reportplotting($config);
      }
    } else {
      $str = $this->reportplotting($config);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    ini_set('max_execution_time', -1);
    switch ($config['params']['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $result = $this->vltdefault_query($config);
        $reportdata =  $this->DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_VTL($config, $result);
        break;
      case 32: //3M
        switch ($config['params']['dataparams']['reporttype']) {
          case '0':
            $result = $this->default_query($config);
            $reportdata =  $this->THREEMDEFAULT_SUBSIDIARY_LEDGER_LAYOUT($config, $result);
            break;
          case '1':
            $result = $this->default_query_summary($config);
            $reportdata =  $this->SUBSIDIARY_LEDGER_SUMM_LAYOUT($config, $result);
            break;
          default:
            $result = $this->default_query($config);
            $reportdata = $this->subsidiary_withitems_layout($config, $result);
            break;
        }
        break;
      case 19: //housegem
        if ($config['params']['dataparams']['reporttype'] == '0') {
          $result = $this->default_query_housegem($config);
          $reportdata =  $this->DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_HOUSEGEM($config, $result);
        } else {
          $result = $this->default_query_summary($config);
          $reportdata =  $this->SUBSIDIARY_LEDGER_SUMM_LAYOUT($config, $result);
        }
        break;
      case 21: //kinggeorge
        if ($config['params']['dataparams']['reporttype'] == '0') {
          $result = $this->default_query_kinggeorge($config);
          $reportdata =  $this->DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_KG($config, $result);
        } else {
          $result = $this->default_query_summary($config);
          $reportdata =  $this->SUBSIDIARY_LEDGER_SUMM_LAYOUT($config, $result);
        }
        break;
      case 40: //cdo
        if ($config['params']['dataparams']['reporttype'] == '0') {
          $result = $this->default_query_cdo($config);
          $reportdata =  $this->DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_CDO($config, $result);
        } else {
          $result = $this->default_query_summary($config);
          $reportdata =  $this->SUBSIDIARY_LEDGER_SUMM_LAYOUT($config, $result);
        }
        break;
      case 8: //maxipro
        if ($config['params']['dataparams']['reporttype'] == '0') {
          $result = $this->default_query_maxipro($config);
          $reportdata =  $this->DEFAULT_SUBSIDIARY_LEDGER_LAYOUT($config, $result);
        } else {
          $result = $this->default_query_summary($config);
          $reportdata =  $this->SUBSIDIARY_LEDGER_SUMM_LAYOUT($config, $result);
        }
        break;
      default:
        if ($config['params']['dataparams']['reporttype'] == '0') {
          $result = $this->default_query($config);
          $reportdata =  $this->DEFAULT_SUBSIDIARY_LEDGER_LAYOUT($config, $result);
        } else {
          $result = $this->default_query_summary($config);
          $reportdata =  $this->SUBSIDIARY_LEDGER_SUMM_LAYOUT($config, $result);
        }
        break;
    }

    return $reportdata;
  }

  public function default_query_kinggeorge($filters)
  {
    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));

    $acno = $filters['params']['dataparams']['contra'];
    $acnoid = $filters['params']['dataparams']['acnoid'];
    $client = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];
    $project = $filters['params']['dataparams']['dprojectname'];

    $filter = "";
    $filter = "";

    if ($project != "") {
      $projectid = $filters['params']['dataparams']['projectid'];
      $filter .= " and detail.projectid = '" . $projectid . "' ";
    }

    $clientp = "";
    $clientu = "";

    if ($client != "") {
      $clientp = " and detail.clientid=" . $clientid;
      $clientu = " and client.clientid=" . $clientid;
    }

    $fieldbegbal = '';
    $cat = $filters['params']['dataparams']['cat'];
    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $fieldbegbal = ' ifnull(sum(detail.cr-detail.db),0) ';
        break;
      default:
        $fieldbegbal = ' ifnull(sum(detail.db-detail.cr),0) ';
        break;
    }

    switch ($isposted) {
      case 0: // posted
        $query = "select 'Beginning Balance' as docno, null as dateid, '' as clientname, '' as rem, 0 as db, 0 as cr, sum(begbal) as begbal from (
                select $fieldbegbal as begbal                         
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(head.dateid) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientp) as b
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem, detail.db,detail.cr, 0 as begbal
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(head.dateid) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientp
                order by dateid,docno";
        break;

      case 1: // unposted
        $query = "select 'Beginning Balance' as docno, null as dateid, '' as clientname, '' as rem, 0 as db, 0 as cr, sum(begbal) as begbal from (
                select $fieldbegbal as begbal                         
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                left join client as dclient on dclient.client = detail.client
                where  date(head.dateid) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientu) as b
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem,detail.db as db,detail.cr as cr, 0 as begbal
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                where  date(head.dateid) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientu
                order by dateid,docno";
        break;

      case 2: // all

        $query = "select 'Beginning Balance' as docno, null as dateid, '' as clientname, '' as rem, 0 as db, 0 as cr, sum(begbal) as begbal from (
                select $fieldbegbal as begbal                         
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                left join client as dclient on dclient.client = detail.client
                where  date(head.dateid) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientu
                union all
                select $fieldbegbal as begbal                         
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(head.dateid) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientp) as b
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem, detail.db,detail.cr, 0 as begbal
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(head.dateid) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientp
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem,detail.db as db,detail.cr as cr, 0 as begbal
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                where  date(head.dateid) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientu
                order by dateid,docno";

        break;
    } // end switch

    $result = $this->coreFunctions->opentable($query);

    return $result;
  }

  public function default_query_cdo($filters)
  {
    $companyid = $filters['params']['companyid'];

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));
    //not done yet

    //use replace() for acct and project range
    $project = $filters['params']['dataparams']['dprojectname'];
    if ($project != "") {
      $projectid = $filters['params']['dataparams']['projectid'];
    }

    $acno = $filters['params']['dataparams']['contra'];
    $acnoid = $filters['params']['dataparams']['acnoid'];
    $client = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];

    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(detail.cr-detail.db,2)),0) ';
        break;
      default:
        $field = ' ifnull(sum(round(detail.db-detail.cr,2)),0) ';
        break;
    }

    $filter = "";

    if ($project != "") {
      $filter .= " and detail.projectid = '" . $projectid . "' ";
    }
    if ($this->companysetup->getmultibranch($filters['params'])) {
      $center = $filters['params']['dataparams']['center'];
      $filter .= " and cntnum.center='" . $center . "' ";
    }

    if ($client != "") {
      // $filter .= " and client.client='" . $client . "' ";
      $filter .= " and dclient.clientid=" . $clientid;
    }

    $datefilter = " date(head.dateid) between '" . $start . "' and  '" . $end . "'  ";

    switch ($isposted) {
      case 0: // posted
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem,
                    '\\" . $acno . "' as acno,'' as acnoname,0 as db,0 as cr,sum(b.begbal) as begbal,0 as detail,''as drem,'' as yourref,'' as si,'' as chsi
              from (select " . $field . " as begbal
              from ((((lahead as head 
              left join ladetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.client = head.client)))
              left join client as dclient on((dclient.client = detail.client))) 
              left join cntnum on cntnum.trno=head.trno
              where date(head.dateid) < '" . $start . "' and detail.acnoid =" . $acnoid . "
              " . $filter . "
              union all
              select " . $field . " as begbal
              from ((((glhead as head 
              left join gldetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.clientid = head.clientid)))
              left join client as dclient on((dclient.clientid = detail.clientid))) 
              left join cntnum on cntnum.trno=head.trno
              where date(head.dateid) < '" . $start . "' and detail.acnoid =" . $acnoid . "
              " . $filter . ") as b
              union all
              select head.dateid as dateid,head.docno as docno,
              client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
              coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,
              coa.detail, detail.rem as drem,head.yourref,
              head.ourref as si,head.chsino as chsi
              from ((((glhead as head 
              left join gldetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.clientid = head.clientid)))
              left join client as dclient on((dclient.clientid = detail.clientid))) 
              left join cntnum on cntnum.trno=head.trno
              where $datefilter and detail.acnoid =" . $acnoid . "
              " . $filter . "
              order by acno,dateid,docno";

        break;

      case 1: // unposted
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem,
        '\\" . $acno . "' as acno,'' as acnoname,0 as db,0 as cr,sum(b.begbal) as begbal,0 as detail,''as drem,'' as yourref,'' as si,'' as chsi from (select " . $field . " as begbal
        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' and detail.acnoid =" . $acnoid . "
        " . $filter . "
        union all
        select " . $field . " as begbal
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' and detail.acnoid =" . $acnoid . "
        " . $filter . ") as b
        union all
        select head.dateid as dateid,head.docno as docno,
        client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,
        coa.detail, detail.rem as drem,head.yourref,
        head.ourref as si,head.chsino as chsi
        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where $datefilter and detail.acnoid =" . $acnoid . "
        " . $filter . "
        order by acno,dateid,docno";
        break;

      case 2: // all
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem,
        '\\" . $acno . "' as acno,'' as acnoname,0 as db,0 as cr,sum(b.begbal) as begbal,0 as detail,''as drem,'' as yourref,'' as si,'' as chsi from (select " . $field . " as begbal
        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' and detail.acnoid =" . $acnoid . "
        " . $filter . "
        union all
        select " . $field . " as begbal
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' and detail.acnoid =" . $acnoid . "
        " . $filter . ") as b
        union all
        select head.dateid as dateid,head.docno as docno,
        client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,
        coa.detail, detail.rem as drem,head.yourref,
        head.ourref as si,head.chsino as chsi
        from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where $datefilter and detail.acnoid =" . $acnoid . "
        " . $filter . "
        union all
        select head.dateid as dateid,head.docno as docno,
        client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,
        coa.detail, detail.rem as drem,head.yourref,
        head.ourref as si,head.chsino as chsi
        from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where $datefilter and detail.acnoid =" . $acnoid . "
        " . $filter . "
        order by acno,dateid,docno";

        break;
    } // end switch

    //$this->coreFunctions->LogConsole($query);
    $result = $this->coreFunctions->opentable($query);
    return $result;
  }

  public function vltdefault_query($filters)
  {
    $companyid = $filters['params']['companyid'];

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));
    //not done yet

    //use replace() for acct and project range
    $center = $filters['params']['dataparams']['center'];
    $costcenter = isset($filters['params']['dataparams']['costcenter']) ? $filters['params']['dataparams']['costcenter'] : "";
    $costcenterid = isset($filters['params']['dataparams']['costcenterid']) ? $filters['params']['dataparams']['costcenterid'] : 0;

    $acno = $filters['params']['dataparams']['contra'];
    $acnoid = $filters['params']['dataparams']['acnoid'];
    $client = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];

    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(detail.cr-detail.db,2)),0) ';
        break;
      default:
        $field = ' ifnull(sum(round(detail.db-detail.cr,2)),0) ';
        break;
    }
    //myconstant

    $filter = "";

    if ($acno != "ALL") {
      $filter .= " and detail.acnoid=" . $acnoid;
    }

    if ($costcenter != "") {
      $filter .= " and head.projectid = '" . $costcenterid . "' ";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }

    if ($client != "") {
      $filter .= " and dclient.clientid=" . $clientid;
    }

    $datefilter = " date(head.dateid) between '" . $start . "' and  '" . $end . "'  ";


    switch ($isposted) {
      case 0: // posted
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref, '' as checkno,'' as rem,'' as acno,'' as acnoname,0 as db,0 as cr,sum(b.begbal) as begbal,0 as detail,'' as drem,'' as alias,null as postdate
        from (select " . $field . " as begbal from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' $filter
        union all
        select " . $field . " as begbal from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' $filter) as b
        union all
        select head.dateid as dateid,head.docno as docno,client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,coa.detail,detail.rem as drem,coa.alias,detail.postdate  from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where $datefilter $filter
        order by acno,dateid,docno";

        break;

      case 1: // unposted
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref, '' as checkno,'' as rem,'' as acno,'' as acnoname,0 as db,0 as cr,sum(b.begbal) as begbal,0 as detail,'' as drem,'' as alias,null as postdate
        from (select " . $field . " as begbal from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' $filter
        union all
        select " . $field . " as begbal from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' $filter) as b
        union all
        select head.dateid as dateid,head.docno as docno,client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,coa.detail,detail.rem as drem,coa.alias,detail.postdate  from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where $datefilter $filter
        order by acno,dateid,docno";

        break;

      case 2: // all
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref, '' as checkno,'' as rem,'' as acno,'' as acnoname,0 as db,0 as cr,sum(b.begbal) as begbal,0 as detail,'' as drem,'' as alias,null as postdate
        from (select " . $field . " as begbal from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' $filter
        union all
        select " . $field . " as begbal from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) < '" . $start . "' $filter) as b
        union all
        select head.dateid as dateid,head.docno as docno,client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,coa.detail,detail.rem as drem,coa.alias,detail.postdate  from ((((lahead as head 
        left join ladetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.client = head.client)))
        left join client as dclient on((dclient.client = detail.client))) 
        left join cntnum on cntnum.trno=head.trno
        where $datefilter $filter
        union all
        select head.dateid as dateid,head.docno as docno,client.client,head.clientname as clientname,detail.ref as ref,detail.checkno as checkno,head.rem as rem,
        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,0 as begbal,coa.detail,detail.rem as drem,coa.alias,detail.postdate  from ((((glhead as head 
        left join gldetail as detail on((head.trno = detail.trno))) 
        left join coa on((coa.acnoid = detail.acnoid))) 
        left join client on((client.clientid = head.clientid)))
        left join client as dclient on((dclient.clientid = detail.clientid))) 
        left join cntnum on cntnum.trno=head.trno
        where $datefilter $filter
        order by acno,dateid,docno";

        break;
    } // end switch
    $result = $this->coreFunctions->opentable($query);

    return $result;
  }

  public function default_query_maxipro($filters)
  {
    $companyid = $filters['params']['companyid'];

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));
    //not done yet

    //use replace() for acct and project range
    $project = $filters['params']['dataparams']['dprojectname'];
    $subprojectname = $filters['params']['dataparams']['subprojectname'];
    if ($project != "") {
      $projectid = $filters['params']['dataparams']['projectid'];
    }
    if ($subprojectname != "") {
      $subproject = $filters['params']['dataparams']['subproject'];
    }

    $acno = $filters['params']['dataparams']['contra'];
    $acnoid = $filters['params']['dataparams']['acnoid'];
    $client = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];

    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(detail.cr-detail.db,2)),0) ';
        break;
      default:
        $field = ' ifnull(sum(round(detail.db-detail.cr,2)),0) ';
        break;
    }
    //myconstant

    $filter = "";
    if ($acno != "ALL") {
      $filter .= " and detail.acnoid=" . $acnoid;
    }

    if ($project != "") {
      $filter .= " and detail.projectid = '" . $projectid . "' ";
    }
    if ($subprojectname != "") {
      $filter .= " and detail.subproject = '" . $subproject . "' ";
    }

    $fclientl = '';
    $fclientg = '';

    if ($client != "") {
      // $filter .= " and client.client='" . $client . "' ";
      // $filter .= " and detail.clientid=" . $clientid;
      $fclientl = " and dclient.clientid=" . $clientid;
      $fclientg = " and detail.clientid=" . $clientid;
    }

    $selectjc = '';
    $selecthjc = '';
    $select = '';
    $allselect = '';
    $grpselect = '';

    $select = ', proj.code as projcode';
    $allselect = ', projcode';
    $grpselect = ',proj.code';
    $selectjc = " union all
                  select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                        coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                        coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,
                        detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,
                        head.yourref,'' as si,'' as chsi,proj.code as projcode
                  from ((jchead as head
                  left join ladetail as detail on ((head.trno = detail.trno)))
                  left join coa on ((coa.acnoid = detail.acnoid)))
                  left join cntnum on cntnum.trno=head.trno
                  left join client on client.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join projectmasterfile as proj on proj.line=detail.projectid
                  where date(head.dateid) between '" . $start . "' and  '" . $end . "' $filter $fclientl
                  group by head.trno ,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem ,detail.line,
                                  coa.acno,coa.acnoname ,detail.db,detail.cr,coa.alias,detail.ref,detail.client,
                                  detail.rem,detail.checkno,coa.acnoid,head.yourref $grpselect ";
    $selecthjc = " union all
                    select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                            client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                            coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                            coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                            detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,
                            head.yourref,'' as si,'' as chsi,proj.code as projcode
                    from ((((hjchead as head
                    left join gldetail as detail on((head.trno = detail.trno)))
                    left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.client = head.client)))
                    left join client as dclient on((dclient.clientid = detail.clientid)))
                    left join cntnum on cntnum.trno=head.trno
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    where date(head.dateid) between '" . $start . "' and  '" . $end . "'  $filter $fclientg
                    group by  head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                              coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
                              detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr,head.yourref $grpselect ";

    $leftjoin = '';
    $datefilter = " date(head.dateid) between '" . $start . "' and  '" . $end . "'  ";
    $leftjoin = 'left join projectmasterfile as proj on proj.line = detail.projectid';

    switch ($isposted) {
      case 0: // posted
        $query = "select a.dateid,a.docno,a.client,client.clientname,a.ref,
                    a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                    coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail,a.drem,a.yourref,a.si,a.chsi $allselect
              from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                            client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                            coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                            coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                            detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,head.yourref,
                            head.ourref as si,head.chsino as chsi
                            $select
                    from ((((glhead as head 
                    left join gldetail as detail on((head.trno = detail.trno))) 
                    left join coa on((coa.acnoid = detail.acnoid))) 
                    left join client on((client.clientid = head.clientid)))
                    left join client as dclient on((dclient.clientid = detail.clientid))) 
                    left join cntnum on cntnum.trno=head.trno
                    $leftjoin
                    where $datefilter $filter $fclientg
                    group by  head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                              coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
                              detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr,head.yourref,
                              head.ourref,head.chsino
                              $grpselect
                   $selecthjc ) as a
                left join coa on a.acno=coa.acno left join client on client.client = a.client  
                order by acno,dateid,docno";

        break;

      case 1: // unposted
        $query = "select a.dateid,a.docno,a.client,client.clientname,a.ref,
                    a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                    coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail,a.drem,a.yourref,a.si,a.chsi $allselect
                   from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                           client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                           coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                           coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,
                           detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,head.yourref,
                           head.ourref as si,head.chsino as chsi
                            $select
                    from ((lahead as head 
                    left join ladetail as detail on ((head.trno = detail.trno)))
                    left join coa on ((coa.acnoid = detail.acnoid)))
                    left join cntnum on cntnum.trno=head.trno 
                    left join client on client.client=head.client
                    left join client as dclient on dclient.client=detail.client
                    $leftjoin
                    where $datefilter $filter $fclientl
                  group by head.trno ,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem ,detail.line,
                       coa.acno,coa.acnoname ,detail.db,detail.cr,coa.alias,detail.ref,detail.client,
                       detail.rem,detail.checkno,coa.acnoid,head.yourref,
                       head.ourref,head.chsino
                       $grpselect $selectjc
                 ) as a
                    left join coa on a.acno=coa.acno left join client on client.client = a.client 
                    order by acno,dateid,docno";
        break;

      case 2: // all

        $query = "select a.dateid,a.docno,a.client,a.clientname,a.ref, a.checkno,
                    case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                    coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail,a.drem,a.yourref,a.si,a.chsi  $allselect
              from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                            client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                            coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                            coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                            detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,head.yourref,
                            head.ourref as si,head.chsino as chsi
                            $select
                    from ((((glhead as head 
                    left join gldetail as detail on((head.trno = detail.trno))) 
                    left join coa on((coa.acnoid = detail.acnoid))) 
                    left join client on((client.clientid = head.clientid)))
                    left join client as dclient on((dclient.clientid = detail.clientid))) 
                    left join cntnum on cntnum.trno=head.trno
                    $leftjoin
                    where $datefilter $filter $fclientg
                    group by  head.trno,head.doc,head.docno,head.dateid,client.client,
                              head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
                              detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr,head.yourref,
                              head.ourref,head.chsino
                              $grpselect $selecthjc              
              union all 
              select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                          coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,detail.rem as drem,
                          detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,head.yourref,
                          head.ourref as si,head.chsino as chsi
                          $select
                    from ((lahead as head left join ladetail as detail on ((head.trno = detail.trno)))
                    left join coa on ((coa.acnoid = detail.acnoid)))left join cntnum on cntnum.trno=head.trno 
                    left join client on client.client=head.client
                    left join client as dclient on dclient.client=detail.client
                    $leftjoin
                    where $datefilter $filter $fclientl
                    group by head.trno ,head.doc,head.docno,head.dateid,
                          client.client,head.clientname,head.rem ,detail.line,
                          coa.acno,coa.acnoname ,detail.db,detail.cr,
                          coa.alias,detail.ref,detail.client,detail.rem,
                          detail.checkno,coa.acnoid,head.yourref,
                          head.ourref,head.chsino
                          $grpselect $selectjc) as a
              left join coa on a.acno=coa.acno 
              order by acno,dateid,docno";

        break;
    } // end switch
    $result = $this->coreFunctions->opentable($query);
    $bal = 0;
    foreach ($result as $key => $value) {
      if ($key == 0) {
        $bal = $value->begbal;
      } else {
        switch ($cat) {
          case 'L':
          case 'R':
          case 'C':
          case 'O':
            $bal = $bal + ($value->cr - $value->db);
            break;
          default:
            $bal = $bal + ($value->db - $value->cr);
            break;
        } // end switch
        $value->begbal = $bal;
      }
    } // end foreah

    return $result;
  }

  public function default_query($filters)
  {
    $companyid = $filters['params']['companyid'];

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));
    //not done yet

    //use replace() for acct and project range
    $acnoids = [];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $center = $filters['params']['dataparams']['center'];
        $costcenter = isset($filters['params']['dataparams']['costcenter']) ? $filters['params']['dataparams']['costcenter'] : "";
        $costcenterid = isset($filters['params']['dataparams']['costcenterid']) ? $filters['params']['dataparams']['costcenterid'] : 0;
        break;
      case 24: //goodfound
        $center = $filters['params']['dataparams']['center'];
        $project = $filters['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $filters['params']['dataparams']['projectid'];
        }
        break;
      case 15: //nathina
        $acno1 = $filters['params']['dataparams']['acnoname'];
        $contra1 = $filters['params']['dataparams']['contra'];

        $acno2 = $filters['params']['dataparams']['acnoname2'];
        $contra2 = $filters['params']['dataparams']['contra2'];

        $proj1 = $filters['params']['dataparams']['projectname'];
        $projid1 = isset($filters['params']['dataparams']['projectid']) ? $filters['params']['dataparams']['projectid'] : '';
        $projcode1 = $filters['params']['dataparams']['projectcode'];

        $proj2 = $filters['params']['dataparams']['projectname2'];
        $projid2 = isset($filters['params']['dataparams']['projectid2']) ? $filters['params']['dataparams']['projectid2'] : '';
        $projcode2 = $filters['params']['dataparams']['projectcode2'];
        break;
      case 55: //afli
        $project = $filters['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $filters['params']['dataparams']['projectid'];
        }
        $acnoid2 = $filters['params']['dataparams']['acnoid2'];
        $acnoid3 = $filters['params']['dataparams']['acnoid3'];
        $acnoid4 = $filters['params']['dataparams']['acnoid4'];

        if (!empty($acnoid2) && $acnoid2 != '0') $acnoids[] = $acnoid2;
        if (!empty($acnoid3) && $acnoid3 != '0') $acnoids[] = $acnoid3;
        if (!empty($acnoid4) && $acnoid4 != '0') $acnoids[] = $acnoid4;
        break;
      default:
        $project = $filters['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $filters['params']['dataparams']['projectid'];
        }
        break;
    }

    $acno = $filters['params']['dataparams']['contra'];
    $acnoid = $filters['params']['dataparams']['acnoid'];
    if (!empty($acnoid) || $acnoid != '0') $acnoids[] = $acnoid;
    $client = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];

    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(detail.cr-detail.db,2)),0) ';
        break;
      default:
        $field = ' ifnull(sum(round(detail.db-detail.cr,2)),0) ';
        break;
    }
    //myconstant

    $filter = "";
    if ($companyid != 15) { //not nathina
      if ($acno != "ALL") {
        // if ($companyid == 55) { //afli
        //   $filter .= " and detail.acnoid in ('$acnoid','$acnoid2', '$acnoid3', '$acnoid4')";
        // } else {
        //   $filter .= " and detail.acnoid=" . $acnoid;
        // }

        if ($companyid == 55) { // afli
          if (!empty($acnoids)) {
            $acnoidList = "'" . implode("','", $acnoids) . "'";
            $filter .= " and detail.acnoid in ($acnoidList)";
          }
        } else {
          if (!empty($acnoid) || $acnoid != '0') {
            $filter .= " and detail.acnoid = '$acnoid'";
          }
        }
      }
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        if ($costcenter != "") {
          $filter .= " and detail.projectid = '" . $costcenterid . "' ";
        }

        if ($center != "") {
          $filter .= " and cntnum.center='" . $center . "' ";
        }
        break;
      case 24: //goodfound
        if ($center != '' && $center != '0') {
          $filter .= " and cntnum.center='" . $center . "' ";
        }
        if ($project != "") {
          $filter .= " and detail.projectid = '" . $projectid . "' ";
        }
        break;
      case 15: //nathina
        if ($proj1 != "" && $proj2 != "") {
          $filter .= " and proj.code between '$projcode1' and '$projcode2'";
        }

        if ($acno1 != "" && $acno2 != "") {
          $filter .= " and (replace(ifnull(coa.acno,''),'\\\','') between replace('$contra1','\\\','') and replace('$contra2','\\\',''))";
        } else {
          // $filter .= " and coa.acno='\\" . $acno . "' ";
          $filter .= " and detail.acnoid=" . $acnoid;
        }
        break;
      default:
        if ($project != "") {
          $filter .= " and detail.projectid = '" . $projectid . "' ";
        }
        if ($this->companysetup->getmultibranch($filters['params'])) {
          $center = $filters['params']['dataparams']['center'];
          $filter .= " and cntnum.center='" . $center . "' ";
        }

        break;
    }

    if ($client != "") {
      $filter .= " and dclient.client='" . $client . "' ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $filters['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $filters['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter .= " and detail.deptid = $dept";
      }
    }

    $dateid = 'head.dateid';
    $leftjoin = '';
    $datefilter = " date(head.dateid) between '" . $start . "' and  '" . $end . "'  ";
    switch ($companyid) {
      case 15: //nathina
        $leftjoin = 'left join projectmasterfile as proj on proj.line = head.projectid';
        $datefilter = " date(head.dateid) <'" . $end . "'";
        break;
      case 19: //housegem
        $datefilter = " date(detail.postdate) between '" . $start . "' and  '" . $end . "'  ";
        break;
    }

    switch ($isposted) {
      case 0: // posted
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem,
        b.acno,b.acnoname,0 as db,0 as cr,sum(b.begbal) as begbal, 0 as detail,'' as drem,'' as yourref from (
              select coa.acno,coa.acnoname," . $field . " as begbal
              from ((((glhead as head 
              left join gldetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.clientid = head.clientid)))
              left join client as dclient on((dclient.clientid = detail.clientid))) 
              left join cntnum on cntnum.trno=head.trno
               $leftjoin
              where date(head.dateid) < '" . $start . "'
              " . $filter . "
                group by coa.acno,coa.acnoname
        ) as b group by acno,acnoname
         union all
        select a.dateid,a.docno,a.client,client.clientname,a.ref,
                    a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                    coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail,a.drem,a.yourref
              from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                    client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                    coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                    coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                    detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,head.yourref
                    from ((((glhead as head 
                    left join gldetail as detail on((head.trno = detail.trno))) 
                    left join coa on((coa.acnoid = detail.acnoid))) 
                    left join client on((client.clientid = head.clientid)))
                    left join client as dclient on((dclient.clientid = detail.clientid))) 
                    left join cntnum on cntnum.trno=head.trno
                    $leftjoin
                    where $datefilter $filter 
                    group by  head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                    coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
                    detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr,head.yourref
                    ) as a
                left join coa on a.acno=coa.acno left join client on client.client = a.client  
                order by acno,dateid,docno";


        break;

      case 1: // unposted
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem,
        b.acno,b.acnoname,0 as db,0 as cr,sum(b.begbal) as begbal, 0 as detail,'' as drem,'' as yourref from (
        select coa.acno,coa.acnoname," . $field . " as begbal
              from ((((lahead as head 
              left join ladetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.client = head.client)))
              left join client as dclient on((dclient.client = detail.client))) 
              left join cntnum on cntnum.trno=head.trno 
               $leftjoin
              where date(head.dateid) < '" . $start . "' 
              " . $filter . "
                group by coa.acno,coa.acnoname
        ) as b group by acno,acnoname
         union all
         select a.dateid,a.docno,a.client,client.clientname,a.ref,
                    a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                    coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail,a.drem,a.yourref
                   from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                           client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                           coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                           coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,
                           detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,head.yourref
                    from ((lahead as head 
                    left join ladetail as detail on ((head.trno = detail.trno)))
                    left join coa on ((coa.acnoid = detail.acnoid)))
                    left join cntnum on cntnum.trno=head.trno 
                    left join client on client.client=head.client
                    left join client as dclient on dclient.client=detail.client
                    $leftjoin
                    where $datefilter $filter
                  group by head.trno ,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem ,detail.line,
                       coa.acno,coa.acnoname ,detail.db,detail.cr,coa.alias,detail.ref,detail.client,
                       detail.rem,detail.checkno,coa.acnoid,head.yourref
                 ) as a
                    left join coa on a.acno=coa.acno left join client on client.client = a.client 
                    order by acno,dateid,docno";
        break;

      case 2: // all
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem,
        b.acno,b.acnoname,0 as db,0 as cr,sum(b.begbal) as begbal, 0 as detail,'' as drem,'' as yourref from (
        select coa.acno,coa.acnoname," . $field . " as begbal
              from ((((lahead as head 
              left join ladetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.client = head.client)))
              left join client as dclient on((dclient.client = detail.client))) 
              left join cntnum on cntnum.trno=head.trno 
               $leftjoin
              where date(head.dateid) < '" . $start . "' 
              " . $filter . "
                group by coa.acno,coa.acnoname
              union all
              select coa.acno,coa.acnoname," . $field . " as begbal
              from ((((glhead as head 
              left join gldetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.clientid = head.clientid)))
              left join client as dclient on((dclient.clientid = detail.clientid))) 
              left join cntnum on cntnum.trno=head.trno
               $leftjoin
              where date(head.dateid) < '" . $start . "'
              " . $filter . "
                group by coa.acno,coa.acnoname
        ) as b group by acno,acnoname
         union all
         select a.dateid,a.docno,a.client,a.clientname,a.ref, a.checkno,
        case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail,a.drem,a.yourref
        from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                      client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                      coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                      coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                      detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,head.yourref
              from ((((glhead as head 
              left join gldetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.clientid = head.clientid)))
              left join client as dclient on((dclient.clientid = detail.clientid))) 
              left join cntnum on cntnum.trno=head.trno
              $leftjoin
              where $datefilter $filter
              group by  head.trno,head.doc,head.docno,head.dateid,client.client,
                        head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
                        detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr,head.yourref
        union all 
        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                    client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                    coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                    coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,detail.rem as drem,
                    detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,head.yourref
              from ((lahead as head left join ladetail as detail on ((head.trno = detail.trno)))
              left join coa on ((coa.acnoid = detail.acnoid)))left join cntnum on cntnum.trno=head.trno 
              left join client on client.client=head.client
              left join client as dclient on dclient.client=detail.client
              $leftjoin
              where $datefilter $filter
              group by head.trno ,head.doc,head.docno,head.dateid,
                    client.client,head.clientname,head.rem ,detail.line,
                    coa.acno,coa.acnoname ,detail.db,detail.cr,
                    coa.alias,detail.ref,detail.client,detail.rem,
                    detail.checkno,coa.acnoid,head.yourref) as a
        left join coa on a.acno=coa.acno 
        order by acno,dateid,docno";

        break;
    } // end switch

    // var_dump($query);
    $result = $this->coreFunctions->opentable($query);
    $this->coreFunctions->LogConsole($query);

    return $result;
  }

  public function default_query_summary($filters)
  {
    $companyid = $filters['params']['companyid'];

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));
    $acnoids = [];
    switch ($companyid) {
      case 1: //vitaline
      case 10: //afti
      case 12: //afti usd
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $center = $filters['params']['dataparams']['center'];
        $costcenter = isset($filters['params']['dataparams']['costcenter']) ? $filters['params']['dataparams']['costcenter'] : "";
        $costcenterid = isset($filters['params']['dataparams']['costcenterid']) ? $filters['params']['dataparams']['costcenterid'] : 0;
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $project = $filters['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $filters['params']['dataparams']['projectid'];
        }
        break;
      case 8: //maxipro
        $project = $filters['params']['dataparams']['dprojectname'];
        $subprojectname = $filters['params']['dataparams']['subprojectname'];
        if ($project != "") {
          $projectid = $filters['params']['dataparams']['projectid'];
        }
        if ($subprojectname != "") {
          $subproject = $filters['params']['dataparams']['subproject'];
        }
        break;
      case 15: //nathina
        $acno1 = $filters['params']['dataparams']['acnoname'];
        $contra1 = $filters['params']['dataparams']['contra'];

        $acno2 = $filters['params']['dataparams']['acnoname2'];
        $contra2 = $filters['params']['dataparams']['contra2'];

        $proj1 = $filters['params']['dataparams']['projectname'];
        $projcode1 = $filters['params']['dataparams']['projectcode'];

        $proj2 = $filters['params']['dataparams']['projectname2'];
        $projcode2 = $filters['params']['dataparams']['projectcode2'];
        break;
      case 55: //afli
        $project = $filters['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $filters['params']['dataparams']['projectid'];
        }
        $acnoid2 = $filters['params']['dataparams']['acnoid2'];
        $acnoid3 = $filters['params']['dataparams']['acnoid3'];
        $acnoid4 = $filters['params']['dataparams']['acnoid4'];
        if (!empty($acnoid2) && $acnoid2 != '0') $acnoids[] = $acnoid2;
        if (!empty($acnoid3) && $acnoid3 != '0') $acnoids[] = $acnoid3;
        if (!empty($acnoid4) && $acnoid4 != '0') $acnoids[] = $acnoid4;
        break;

      default:
        $project = $filters['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $filters['params']['dataparams']['projectid'];
        }
        break;
    }

    $acno = $filters['params']['dataparams']['contra'];
    $client = $filters['params']['dataparams']['client'];
    $acnoid = $filters['params']['dataparams']['acnoid'];
    if (!empty($acnoid) || $acnoid != '0') $acnoids[] = $acnoid;


    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' sum(ifnull(a.cr,0)-ifnull(a.db,0)) ';
        break;
      default:
        $field = ' sum(ifnull(a.db,0)-ifnull(a.cr,0)) ';
        break;
    }
    //myconstant

    $filter = "";
    if ($acno != "ALL") {
      if ($companyid == 55) { // afli
        if (!empty($acnoids)) {
          $acnoidList = "'" . implode("','", $acnoids) . "'";
          $filter .= " and coa.acnoid in ($acnoidList)";
        }
      } else {
        if (!empty($acnoid) || $acnoid != '0') {
          $filter .= " and coa.acnoid = '$acnoid'";
        }
      }
    }


    switch ($companyid) {
      case 1: //vitaline
      case 10: //afti
      case 12: //afti usd
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        if ($costcenter != "") {
          $filter .= " and head.projectid = '" . $costcenterid . "' ";
        }

        if ($center != "") {
          $filter .= " and cntnum.center='" . $center . "' ";
        }
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        if ($project != "") {
          $filter .= " and detail.projectid = '" . $projectid . "' ";
        }
        break;
      case 8: //maxipro
        if ($project != "") {
          $filter .= " and detail.projectid = '" . $projectid . "' ";
        }
        if ($subprojectname != "") {
          $filter .= " and detail.subproject = '" . $subproject . "' ";
        }
        break;
      case 15: //nathina
        if ($proj1 != "" && $proj2 != "") {
          $filter .= " and proj.code between '$projcode1' and '$projcode2'";
        }

        if ($acno1 != "" && $acno2 != "") {
          $filter .= " and (replace(ifnull(coa.acno,''),'\\\','') between replace('$contra1','\\\','') and replace('$contra2','\\\',''))";
        }
        break;
      default:
        if ($project != "") {
          $filter .= " and detail.projectid = '" . $projectid . "' ";
        }
        if ($this->companysetup->getmultibranch($filters['params'])) {
          $center = $filters['params']['dataparams']['center'];
          if ($companyid != 24) $filter .= " and cntnum.center='" . $center . "' "; //not goodfound
        }
        break;
    }

    if ($client != "") {
      $filter .= " and client.client='" . $client . "' ";
    }


    $selectjc1 = '';
    $selectjc2 = '';
    $selecthjc1 = '';
    $selecthjc2 = '';

    if ($companyid == 8) { //maxipro
      $selectjc1 = " union all select 'U' As Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientname,
                          case detail.rem when '' then head.rem else detail.rem end as rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,proj.name as projname, 
                          detail.subproject, sub.subproject as subname
                    from jchead as head 
                    left join ladetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    where ifnull(coa.acno,'') <> '' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " ";

      $selectjc2 = " union all select 'U' as Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientName, head.rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,
                          proj.name as projname, detail.subproject, sub.subproject as subname
                    from jchead as head 
                    left join ladetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    where ifnull(coa.acno,'')<>'' and date(head.dateid)<'" . $start . "' " . $filter . " ";

      $selecthjc1 = " union all select 'P' As Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientname,
                          case detail.rem when '' then head.rem else detail.rem end as rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,proj.name as projname, 
                          detail.subproject, sub.subproject as subname
                    from hjchead as head 
                    left join gldetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    where ifnull(coa.acno,'') <> '' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " ";

      $selecthjc2 = " union all select 'P' as Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientName, head.rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,
                          proj.name as projname, detail.subproject, sub.subproject as subname
                    from hjchead as head 
                    left join gldetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    where ifnull(coa.acno,'')<>'' and date(head.dateid)<'" . $start . "' " . $filter . "";
    }


    $datefilter = "date(head.dateid)";
    if ($companyid == 19) { //housegem
      $datefilter = "date(detail.postdate)";
    }
    switch ($isposted) {
      case 0: // posted
        $query = "select acno, acnoname , sum(a.db) as db, sum(a.cr) as cr, 0 as begbal 
              from (select 'P' As Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientname,
                          case detail.rem when '' then head.rem else detail.rem end as rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,proj.name as projname, 
                          detail.subproject, sub.subproject as subname
                    from glhead as head 
                    left join gldetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.clientid=head.clientid
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'') <> '' and $datefilter between '" . $start . "' and  '" . $end . "' " . $filter . " $selecthjc1) as a
              group by acno,acnoname
              union all
              select acno,acnoname,db,cr,sum(begbal) as begbal from (
              select '' as acno, 'Beginning Balance' as acnoname, 0 as db, 0 as cr, 
                    " . $field . " as begbal
              from (select 'P' as Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientName, head.rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,
                          proj.name as projname, detail.subproject, sub.subproject as subname
                    from glhead as head 
                    left join gldetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.clientid=head.clientid
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'')<>'' and $datefilter <'" . $start . "' " . $filter . " $selecthjc2) as a
              group by acno,a.acnoname ) as k
              group by acnoname,acno,db,cr
              order by acnoname";

        break;

      case 1: // unposted
        $query = "select acno, acnoname , sum(a.db) as db, sum(a.cr) as cr, 0 as begbal 
              from (select 'U' As Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientname,
                          case detail.rem when '' then head.rem else detail.rem end as rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,proj.name as projname, 
                          detail.subproject, sub.subproject as subname
                    from lahead as head 
                    left join ladetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'') <> '' and $datefilter between '" . $start . "' and  '" . $end . "' " . $filter . " $selectjc1) as a
              group by acno,acnoname
              union all
              select acno, acnoname,db,cr,sum(begbal) as begbal from (
              select '' as acno, 'Beginning Balance' as acnoname, 0 as db, 0 as cr, 
              " . $field . " as begbal
              from (select 'U' as Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientName, head.rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,
                          proj.name as projname, detail.subproject, sub.subproject as subname
                    from lahead as head 
                    left join ladetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'')<>'' and $datefilter <'" . $start . "' " . $filter . " $selectjc2) as a
              group by a.acnoname) as k group by acnoname,acno,db,cr
              order by acnoname";
        break;

      case 2: // all
        $query = "select acno, acnoname , sum(db) as db, sum(cr) as cr, sum(begbal) as begbal from 
        (select acno, acnoname , sum(a.db) as db, sum(a.cr) as cr, 0 as begbal 
              from (select 'U' As Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientname,
                          case detail.rem when '' then head.rem else detail.rem end as rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,proj.name as projname, 
                          detail.subproject, sub.subproject as subname
                    from lahead as head 
                    left join ladetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'') <> '' and $datefilter between '" . $start . "' and  '" . $end . "' " . $filter . " $selectjc1) as a
              group by acno,acnoname
              union all
              
        select acno, acnoname , sum(a.db) as db, sum(a.cr) as cr, 0 as begbal 
              from (select 'P' As Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientname,
                          case detail.rem when '' then head.rem else detail.rem end as rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,proj.name as projname, 
                          detail.subproject, sub.subproject as subname
                    from glhead as head 
                    left join gldetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.clientid=head.clientid
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'') <> '' and $datefilter between '" . $start . "' and  '" . $end . "' " . $filter . " $selecthjc1) as a
              group by acno,acnoname
              union all

              select acno, acnoname, db, cr, sum(begbal) as begbal from (
              select '' as acno, 'Beginning Balance' as acnoname, 0 as db, 0 as cr, 
              " . $field . " as begbal
              from (select 'U' as Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientName, head.rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,
                          proj.name as projname, detail.subproject, sub.subproject as subname
                    from lahead as head 
                    left join ladetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.client=head.client
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'')<>'' and $datefilter <'" . $start . "' " . $filter . " $selectjc2 ) as a
              group by a.acnoname
              union all
              select '' as acno, 'Beginning Balance' as acnoname, 0 as db, 0 as cr, 
              " . $field . " as begbal
              from (select 'P' as Tr, head.trno, head.doc, head.docno, head.dateid, 
                          client.client, head.clientName, head.rem, detail.line,
                          coa.acno, coa.acnoname, detail.db, detail.cr, coa.alias, '' as ref,
                          detail.rem as drem, '' as checkno,detail.project,
                          proj.name as projname, detail.subproject, sub.subproject as subname
                    from glhead as head 
                    left join gldetail as detail on head.trno=detail.trno 
                    left join coa on coa.acnoid=detail.acnoid
                    left join client on client.clientid=head.clientid
                    left join projectmasterfile as proj on proj.line=detail.projectid
                    left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                    left join cntnum on cntnum.trno = head.trno
                    where ifnull(coa.acno,'')<>'' and $datefilter <'" . $start . "' " . $filter . " $selecthjc2) as a
              group by a.acnoname 
              
              
              ) as k group by acnoname,acno,db,cr
              
              
              ) as xm 
              group by acno, acnoname
              order by acnoname
             ";
        break;
    } // end switch


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $result;
  }

  public function default_query_housegem($filters)
  {

    $isposted = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['enddate']));

    $acno = $filters['params']['dataparams']['contra'];
    $acnoid = $filters['params']['dataparams']['acnoid'];
    $client = $filters['params']['dataparams']['client'];
    $clientid = $filters['params']['dataparams']['clientid'];
    $project = $filters['params']['dataparams']['dprojectname'];

    $filter = "";
    $filter = "";

    if ($project != "") {
      $projectid = $filters['params']['dataparams']['projectid'];
      $filter .= " and detail.projectid = '" . $projectid . "' ";
    }

    $clientp = "";
    $clientu = "";

    if ($client != "") {
      $clientp = " and detail.clientid=" . $clientid;
      $clientu = " and client.clientid=" . $clientid;
    }

    $fieldbegbal = '';
    $cat = $filters['params']['dataparams']['cat'];
    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $fieldbegbal = ' ifnull(sum(detail.cr-detail.db),0) ';
        break;
      default:
        $fieldbegbal = ' ifnull(sum(detail.db-detail.cr),0) ';
        break;
    }

    switch ($isposted) {
      case 0: // posted
        $query = "select 'Beginning Balance' as docno, null as dateid, '' as clientname, '' as rem, 0 as db, 0 as cr, sum(begbal) as begbal, '' as drem, null as postdate from (
                select $fieldbegbal as begbal                         
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(detail.postdate) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientp) as b
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem, detail.db,detail.cr, 0 as begbal, detail.rem as drem, date(detail.postdate) as postdate
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(detail.postdate) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientp
                order by dateid,docno";
        break;

      case 1: // unposted
        $query = "select 'Beginning Balance' as docno, null as dateid, '' as clientname, '' as rem, 0 as db, 0 as cr, sum(begbal) as begbal, '' as drem, null as postdate from (
                select $fieldbegbal as begbal                         
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                left join client as dclient on dclient.client = detail.client
                where  date(detail.postdate) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientu) as b
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem,detail.db as db,detail.cr as cr, 0 as begbal, detail.rem as drem, date(detail.postdate) as postdate
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                where  date(detail.postdate) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientu
                order by dateid,docno";
        break;

      case 2: // all

        $query = "select 'Beginning Balance' as docno, null as dateid, '' as clientname, '' as rem, 0 as db, 0 as cr, sum(begbal) as begbal, '' as drem, null as postdate from (
                select $fieldbegbal as begbal                         
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                left join client as dclient on dclient.client = detail.client
                where  date(detail.postdate) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientu
                union all
                select $fieldbegbal as begbal                         
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(detail.postdate) < '" . $start . "' and detail.acnoid=$acnoid $filter $clientp) as b
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem, detail.db,detail.cr, 0 as begbal, detail.rem as drem, date(detail.postdate) as postdate
                from glhead as head left join gldetail as detail on head.trno = detail.trno
                where  date(detail.postdate) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientp
                union all
                select head.docno,date(head.dateid) as dateid,head.clientname,if(detail.ref='',head.rem,concat(head.rem,' ',detail.ref)) as rem,detail.db as db,detail.cr as cr, 0 as begbal, detail.rem as drem, date(detail.postdate) as postdate
                from lahead as head left join ladetail as detail on head.trno = detail.trno
                where  date(detail.postdate) between '" . $start . "' and  '" . $end . "' and detail.acnoid=$acnoid $filter $clientu
                order by dateid,docno";

        break;
    } // end switch

    $result = $this->coreFunctions->opentable($query);

    return $result;
  }

  private function headerlabel($params, $pLayoutSize = 800)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $font = $this->companysetup->getrptfont($params['params']);
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    if ($companyid == 8) { //maxipro
      $pLayoutSize = 1000;
    }

    switch ($companyid) {
      case 1: //vitaline
      case 10: //afti
      case 12: //afti usd
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $center = $params['params']['dataparams']['center'];
        $costcenter = isset($params['params']['dataparams']['costcenter']) ? $params['params']['dataparams']['costcenter'] : "";
        $costcenterid = isset($params['params']['dataparams']['costcenterid']) ? $params['params']['dataparams']['costcenterid'] : 0;
        break;
      case 15: //nathina
        $acno1 = $params['params']['dataparams']['acnoname'];
        $contra1 = $params['params']['dataparams']['contra'];

        $acno2 = $params['params']['dataparams']['acnoname2'];
        $contra2 = $params['params']['dataparams']['contra2'];

        $proj1 = $params['params']['dataparams']['projectname'];
        $projid1 = isset($params['params']['dataparams']['projectid']) ? $params['params']['dataparams']['projectid'] : '';
        $projcode1 = $params['params']['dataparams']['projectcode'];

        $proj2 = $params['params']['dataparams']['projectname2'];
        $projid2 = isset($params['params']['dataparams']['projectid2']) ? $params['params']['dataparams']['projectid2'] : '';
        $projcode2 = $params['params']['dataparams']['projectcode2'];
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $project = isset($params['params']['dataparams']['projectname']) ? $params['params']['dataparams']['projectname'] : "";
        break;
      case 55: //homeworks
        $acno2 = $params['params']['dataparams']['contra2'];
        $acnoname2 = $params['params']['dataparams']['acnoname2'];
        $acno3 = $params['params']['dataparams']['contra3'];
        $acnoname3 = $params['params']['dataparams']['acnoname3'];
        $acno4 = $params['params']['dataparams']['contra4'];
        $acnoname4 = $params['params']['dataparams']['acnoname4'];
        break;
    }


    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];


    if ($companyid == 15) { //nathina
      $acnoname = $contra1 . ' - ' . $acno1 . ' to ' . $contra2 . ' - ' . $acno2;
      $project = $projcode1 . ' - ' . $proj1 . ' to ' . $projcode2 . ' - ' . $proj2;
    } else {
      if ($acnoname == "") {
        $acnoname = "ALL";
      } else {
        if ($companyid == 55) { //homeworks
          $accounts = [];
          if (!empty($acno)) $accounts[] = $acno . ' - ' . $acnoname;
          if (!empty($acno2)) $accounts[] = $acno2 . ' - ' . $acnoname2;
          if (!empty($acno3)) $accounts[] = $acno3 . ' - ' . $acnoname3;
          if (!empty($acno4)) $accounts[] = $acno4 . ' - ' . $acnoname4;
          $acnoname = implode(',', $accounts);
        } else {
          $acnoname = $acno . ' - ' . $acnoname;
        }
      }
    }


    switch ($isposted) {
      case 0:
        $isposted = 'posted';
        break;

      case 1:
        $isposted = 'unposted';
        break;

      case 2:
        $isposted = 'ALL';
        break;
    }

    switch ($companyid) {
      case 1: //vitaline
      case 10: //afti
      case 12: //afti usd
      case 23: //labsol cebu
      case 41: // labsol manila
      case 52: //technolab
        if ($center == "") {
          $center = "ALL";
        }
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        if ($project == "") {
          $project = "ALL";
        }
        break;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dept   = $params['params']['dataparams']['ddeptname'];
        if ($costcenter != "") {
          $costcenter = $params['params']['dataparams']['name'];
        } else {
          $costcenter = "ALL";
        }

        if ($dept != "") {
          $deptname = $params['params']['dataparams']['deptname'];
        } else {
          $deptname = "ALL";
        }
        break;
      case 1: //vitaline
        if ($costcenter != "") {
          $costcenter = $params['params']['dataparams']['name'];
        } else {
          $costcenter = "ALL";
        }
        break;
    }


    $str = '';

    $str .= $this->reporter->begintable($pLayoutSize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($pLayoutSize);

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SUBSIDIARY LEDGER', 300, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, '11', '', '', '');

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        break;
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('Center : ' . $center, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('Project : ' . $costcenter, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        break;
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($isposted), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->col('Accounts: ' . strtoupper($acnoname), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    switch ($companyid) {
      case 17: //unihome
      case 28: //xcomp
      case 15: //nathina
      case 39: //CBBSI
        $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('Project : ' . $project, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        break;
      case 8: //maxipro
        $project = $params['params']['dataparams']['projectname'];
        $subproject = $params['params']['dataparams']['subprojectname'];
        if ($project == '') {
          $project = 'ALL';
        }
        if ($subproject == '') {
          $subproject = 'ALL';
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Project : ' . $project, 1000, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sub-Project : ' . $subproject, 1000, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        break;
    }

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function default_detail_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $companyid = $params['params']['companyid'];
    $str = '';
    if ($companyid == 8) { //maxipro
      $layoutsize = 1000;
    } else {
      $layoutsize = 800;
      $str .= $this->reporter->printline();
    }




    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    switch ($companyid) {
      case 1: //vitaline
        $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Check Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Document#', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '180', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '170', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Notes', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        break;
      case 3:
        $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Document#', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '180', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '170', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Project', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        break;
      case 8: //maxipro
        $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'TB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Document#', '120', null, false, '1px solid', 'TB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Project Code', '140', null, false, '1px solid', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '160', null, false, '1px solid', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '230', null, false, '1px solid', 'TB', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'TB', 'R', $font, '10', 'B', '', '');
        break;
      case 15: //nathina
        $str .= $this->reporter->col('Your Ref.', '60', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Project', '70', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '180', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        break;
      case 19: //housegem
        $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Document#', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '180', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '170', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Notes', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Date Per Account', '100', null, false, '1px solid', 'B', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Check Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Document#', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '180', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '170', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        break;
      case 40: //cdo
        $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Document#', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('CSI No.', '90', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        break;

      default:
        $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Document#', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Supplier/Customer', '180', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Particular', '170', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
        break;
    }


    $str .= $this->reporter->endrow();
    return $str;
  }

  private function nathina_subtotal($params, $db, $cr, $bal)
  {
    $str = '';
    $fontsize = 11;

    $font = $this->companysetup->getrptfont($params['params']);

    $col3 = array();
    array_push($col3, array('100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
    array_push($col3, array('150', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
    array_push($col3, array('60', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
    array_push($col3, array('70', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));

    array_push($col3, array('100', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
    array_push($col3, array('100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''));
    array_push($col3, array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''));
    array_push($col3, array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''));

    array_push($col3, array('100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''));

    $value2 = array('', '', '', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
    $str .= $this->reporter->row($col3, $value2);

    return $str;
  } // end fn

  private function default_subtotal($params, $db, $cr, $bal, $companyid)
  {
    $str = '';
    $fontsize = 11;

    $font = $this->companysetup->getrptfont($params['params']);

    $col3 = array();
    array_push($col3, array('60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
    array_push($col3, array('160', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));

    switch ($companyid) {
      case 15: //nathina
        array_push($col3, array('60', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
        break;
      case 8: //maxipro
        array_push($col3, array('110', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
        break;
      case 40: //cdo  
        array_push($col3, array('90', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
        break;
    }

    array_push($col3, array('170', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''));
    array_push($col3, array('100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''));
    array_push($col3, array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''));
    array_push($col3, array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''));
    array_push($col3, array('100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''));

    switch ($companyid) {
      case 15: //nathina
        $value2 = array('', '', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
        break;
      case 8: //maxipro
        $value2 = array('', '', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
        break;
      case 40: //cdo
        $value2 = array('', '', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
        break;
      default:
        $value2 = array('', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
        break;
    }

    $str .= $this->reporter->row($col3, $value2);

    return $str;
  } // end fn

  private function default_housegemsubtotal($params, $db, $cr, $bal)
  {
    $str = '';
    $fontsize = 9;

    $font = $this->companysetup->getrptfont($params['params']);

    $col3 = array(
      array('60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),

      array('100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''),
      array('60', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('110', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
    );
    $value2 = array('', '', '', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
    $str .= $this->reporter->row($col3, $value2);

    return $str;
  } // end fn

  private function VITA_default_subtotal($params, $db, $cr, $bal)
  {
    $str = '';
    $fontsize = 10;

    $font = $this->companysetup->getrptfont($params['params']);

    $col3 = array(
      array('60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('150', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('180', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''),
      array('170', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
    );
    $value2 = array('', '', '', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
    $str .= $this->reporter->row($col3, $value2);

    return $str;
  } // end fn

  private function CONTI_default_subtotal($params, $db, $cr, $bal)
  {
    $str = '';
    $fontsize = 9;

    $font = $this->companysetup->getrptfont($params['params']);

    $col3 = array(
      array('60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
    );
    $value2 = array('', '', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
    $str .= $this->reporter->row($col3, $value2);

    return $str;
  } // end fn

  public function NATHINA_SUBSIDIARY_LEDGER_SUMM_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));
    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $client = $params['params']['dataparams']['client'];

    $count = 60;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';

    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summ_header($params);
    $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $params);

    $amt = 0;
    $totalamt = 0;
    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        switch ($cat) {
          case 'L':
          case 'R':
          case 'C':
            $amt = ($data[$i]['begbal'] + $data[$i]['cr']) - $data[$i]['db'];
            break;
          default:
            $amt = ($data[$i]['begbal'] + $data[$i]['db']) - $data[$i]['cr'];
            break;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acnoname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($amt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $totalamt += $amt;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();

          #header here
          $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
          if (!$allowfirstpage) {

            $str .= $this->summ_header($params);
          }
          $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $params);
          #header end


          $page = $page + $count;
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL: ', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_KG($params, $data)
  {
    $count = 32;
    $page = 32;
    $this->reporter->linecounter = 0;
    $str = '';

    $fontsize = 10;
    $font = $this->companysetup->getrptfont($params['params']);

    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params);

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction Date', '80', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Document#', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Supplier/Customer', '170', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Particular', '150', null, false, '1px solid', 'B', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;


    $result = json_decode(json_encode($data), true);
    for ($i = 0; $i < count($result); $i++) {

      if ($i == 0) {
        $bal = $result[$i]['begbal'];
      } else {
        $cat = $params['params']['dataparams']['cat'];
        switch ($cat) {
          case 'L':
          case 'R':
          case 'C':
          case 'O':
            $bal = $result[$i]['cr'] - $result[$i]['db'];
            break;
          default:
            $bal = $result[$i]['db'] - $result[$i]['cr'];
            break;
        }
      }

      $totalbal += $bal;

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($result[$i]['dateid'], '80', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['docno'], '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['clientname'], '170', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['rem'], '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['db'] == 0 ? '' : number_format($result[$i]['db'], 2), '75', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', 1);
      $str .= $this->reporter->col($result[$i]['cr'] == 0 ? '' : number_format($result[$i]['cr'], 2), '75', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', 1);
      $str .= $this->reporter->col($totalbal == 0 ? '' : number_format($totalbal, 2), '100', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', 1);
      $str .= $this->reporter->endrow();

      $totaldb += $result[$i]['db'];
      $totalcr += $result[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->headerlabel($params);
        }
        $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);
        $page = $page + $count;
      } // end if

    } // end foreach loop


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, '1px solid', '', 'LT', $font, $fontsize, 'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '170', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('TOTAL:', '150', null, false, '2px solid', 'T', 'LT', $font, $fontsize, 'B', '', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '2px solid', 'T', 'RT', $font, $fontsize, 'B', '', '', '', '', 1);
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '2px solid', 'T', 'RT', $font, $fontsize, 'B', '', '', '', '', 1);
    $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '2px solid', 'T', 'RT', $font, $fontsize, 'B', '', '', '', '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_HOUSEGEM($params, $data)
  {
    $count = 32;
    $page = 32;
    $this->reporter->linecounter = 0;
    $str = '';

    $hgc_layoutSize = 1000;

    $fontsize = 10;
    $font = $this->companysetup->getrptfont($params['params']);

    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params, $hgc_layoutSize);

    $str .= $this->reporter->begintable($hgc_layoutSize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction Date', '100', null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Document#', '120', null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier/Customer', '150', null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Particular', '150', null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '150', null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date Per Account', '100', null, false, '1px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;


    $result = json_decode(json_encode($data), true);
    for ($i = 0; $i < count($result); $i++) {

      if ($i == 0) {
        $bal = $result[$i]['begbal'];
      } else {
        $cat = $params['params']['dataparams']['cat'];
        switch ($cat) {
          case 'L':
          case 'R':
          case 'C':
          case 'O':
            $bal = $result[$i]['cr'] - $result[$i]['db'];
            break;
          default:
            $bal = $result[$i]['db'] - $result[$i]['cr'];
            break;
        }
      }

      $totalbal += $bal;

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($result[$i]['dateid'], '80', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['docno'], '120', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['clientname'], ' 150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['rem'], '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['drem'], '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['postdate'], '100', null, false, '1px solid', '', 'CT', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col($result[$i]['db'] == 0 ? '' : number_format($result[$i]['db'], 2), '75', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', 1);
      $str .= $this->reporter->col($result[$i]['cr'] == 0 ? '' : number_format($result[$i]['cr'], 2), '75', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', 1);
      $str .= $this->reporter->col($totalbal == 0 ? '' : number_format($totalbal, 2), '100', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', 1);
      $str .= $this->reporter->endrow();

      $totaldb += $result[$i]['db'];
      $totalcr += $result[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->page_break();

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->headerlabel($params);
        }
        $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);
        $page = $page + $count;
      } // end if

    } // end foreach loop


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, '1px solid', '', 'LT', $font, $fontsize, 'B', '', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('TOTAL:', '100', null, false, '2px solid', 'T', 'LT', $font, $fontsize, 'B', '', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '2px solid', 'T', 'RT', $font, $fontsize, 'B', '', '', '', '', 1);
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '2px solid', 'T', 'RT', $font, $fontsize, 'B', '', '', '', '', 1);
    $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '2px solid', 'T', 'RT', $font, $fontsize, 'B', '', '', '', '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($hgc_layoutSize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '2px solid', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable($hgc_layoutSize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_CDO($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    $project = $params['params']['dataparams']['dprojectname'];
    if ($project != "") {
      $projectid = $params['params']['dataparams']['projectid'];
    }

    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $client = $params['params']['dataparams']['client'];
    $cost = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    $filter = "";

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "'";
    }
    if ($this->companysetup->getmultibranch($params['params'])) {
      $center = $params['params']['dataparams']['center'];
      $filter .= " and cntnum.center='" . $center . "' ";
    }

    if ($client != "") {
      $filter .= " and client.client='" . $client . "' ";
    }

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';

    $fontsize = 10;
    $font = $this->companysetup->getrptfont($params['params']);

    $col = array(
      array('60', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );

    $col2 = array(
      array('60', null, false, '1px solid', '', 'C', $font, '10', 'B', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', 'B', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );

    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params);
    $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;
    $bal = 0;
    $acno = '';
    $acno2 = '';

    if (!empty($data)) {

      foreach ($data as $key => $data_) {
        if ($acno2 != $data_->acno) { // account groupings          
          if ($acno2 != '') { // subtotal for accounts

            $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);

            $db = 0;
            $cr = 0;
            $bal = 0;
          }

          $acnoname = $data_->acnoname;
          if ($data_->acnoname == '') {
            $acnoname = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acno=?", [$data_->acno]);
          }
          $value2 = array($data_->acno . '     -', $acnoname, '', '', '', '', '');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->row($col2, $value2);
          $str .= $this->reporter->endrow();

          $sort = "";

          // $query = $this->subsidiaryledger_query($field, $start, $end, $data_->acno, $isposted, $filter, $sort, $companyid);

          // $data1 = $this->coreFunctions->opentable($query);
          $result = json_decode(json_encode($data), true);
          $this->coreFunctions->LogConsole(count($result));


          // $chkqry = $this->begbal_chkqry($field, $start, $end, $data_->acno, $isposted, $filter, $companyid);
          // $bdat = $this->coreFunctions->opentable($chkqry);
          // $bdata = json_decode(json_encode($bdat), true);

          $bal = 0;
          for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['docno'] == 'Beginning Balance') {
              $bal = $result[$i]['begbal'];
            } else {
              switch ($cat) {
                case 'L':
                case 'R':
                case 'C':
                case 'O':
                  $bal += ($result[$i]['cr'] - $result[$i]['db']);
                  break;
                default:
                  $bal += ($result[$i]['db'] - $result[$i]['cr']);
                  break;
              } // end switch
              $result[$i]['begbal'] = $bal;
            }

            //table
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($result[$i]['dateid'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['docno'], '150', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['clientname'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['rem'], '150', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['si'], '90', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['db'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '', 1);
            $str .= $this->reporter->col(number_format($result[$i]['cr'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '', 1);
            $str .= $this->reporter->col(number_format($result[$i]['begbal'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '', 1);

            $totaldb += $result[$i]['db'];
            $totalcr += $result[$i]['cr'];

            $db += $result[$i]['db'];
            $cr += $result[$i]['cr'];
            $bal = $result[$i]['begbal'];

            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->printline();
              $str .= $this->reporter->page_break();

              $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
              if (!$allowfirstpage) {

                $str .= $this->headerlabel($params);
              }
              $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);
              $page = $page + $count;
            } // end if
          } // end for loop       

          $acno2 = $data_->acno;
        }
      }
    }

    $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_SUBSIDIARY_LEDGER_LAYOUT_VTL($params, $data) //vita,technolab,labsol
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));


    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $client = $params['params']['dataparams']['client'];
    $cost = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';

    $fontsize = 10;
    $font = $this->companysetup->getrptfont($params['params']);

    $col = array(
      array('60', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );

    $col2 = array(
      array('60', null, false, '1px solid', '', 'C', $font, '10', 'B', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', 'B', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );

    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params);
    $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;
    $bal = 0;
    // $acno = '';
    $acno2 = '';


    if (!empty($data)) {
      foreach ($data as $key => $data_) {
        if ($data_->acno == '') {
          $data_->acno = $acno;
          $data_->acnoname = $acnoname;
        }

        if ($acno2 != $data_->acno) { // account groupings
          if ($acno2 != '') { // subtotal for accounts

            switch ($companyid) {
              case 1: //vitaline
                $str .= $this->VITA_default_subtotal($params, $db, $cr, $bal);
                break;
              default:
                $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);
                break;
            }

            $db = 0;
            $cr = 0;
            $bal = 0;
          }

          $value2 = array($data_->acno . '     -', $data_->acnoname, '', '', '', '', '');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->row($col2, $value2);
          $str .= $this->reporter->endrow();

          $sort = "";

          $result = json_decode(json_encode($data), true);

          $bal = 0;
          for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['docno'] == 'Beginning Balance') {
              $bal = $result[$i]['begbal'];
            } else {
              switch ($cat) {
                case 'L':
                case 'R':
                case 'C':
                case 'O':
                  $bal += ($result[$i]['cr'] - $result[$i]['db']);
                  break;
                default:
                  $bal += ($result[$i]['db'] - $result[$i]['cr']);
                  break;
              } // end switch
              $result[$i]['begbal'] = $bal;
            }

            //table
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($result[$i]['dateid'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            if ($companyid == 1 || $companyid == 23 || $companyid == 41 || $companyid == 52) { // vitaline, labsol cebu, labsol manila, technolab
              if (substr($result[$i]['alias'], 0, 2) == 'CB' || substr($result[$i]['alias'], 0, 2) == 'CR') {
                $str .= $this->reporter->col($result[$i]['postdate'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
              } else {
                $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
              }
            }
            $str .= $this->reporter->col($result[$i]['docno'], '150', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['clientname'], '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['rem'], '170', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');

            if ($companyid == 3) { //conti
              $str .= $this->reporter->col($result[$i]['projname'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            }
            if ($companyid == 1) { //vitaline
              $str .= $this->reporter->col($result[$i]['drem'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            }
            $str .= $this->reporter->col(number_format($result[$i]['db'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['cr'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['begbal'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');

            $totaldb += $result[$i]['db'];
            $totalcr += $result[$i]['cr'];

            $db += $result[$i]['db'];
            $cr += $result[$i]['cr'];
            $bal = $result[$i]['begbal'];

            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->printline();
              $str .= $this->reporter->page_break();

              $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
              if (!$allowfirstpage) {

                $str .= $this->headerlabel($params);
              }
              $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);
              $page = $page + $count;
            } // end if
          } // end for loop
          $acno2 = $data_->acno;
        }
      }
    }
    //else {

    //   switch ($companyid) {
    //     case 1: //vitaline
    //     case 10: //afti
    //     case 12: //afti usd
    //     case 23: //labsol cebu
    //     case 41: //labsol manila
    //     case 52: //technolab
    //       $center = $params['params']['dataparams']['center'];
    //       $costcenter = isset($params['params']['dataparams']['costcenter']) ? $params['params']['dataparams']['costcenter'] : "";
    //       $costcenterid = isset($params['params']['dataparams']['costcenterid']) ? $params['params']['dataparams']['costcenterid'] : 0;
    //       break;

    //     case 15: //nathina
    //       $acno = $params['params']['dataparams']['contra'];
    //       $acnoname = $params['params']['dataparams']['acnoname'];
    //       $client = $params['params']['dataparams']['client'];

    //       $acno1 = $params['params']['dataparams']['acnoname'];
    //       $contra1 = $params['params']['dataparams']['contra'];

    //       $acno2 = $params['params']['dataparams']['acnoname2'];
    //       $contra2 = $params['params']['dataparams']['contra2'];

    //       $proj1 = $params['params']['dataparams']['projectname'];
    //       $projid1 = isset($params['params']['dataparams']['projectid']) ? $params['params']['dataparams']['projectid'] : '';
    //       $projcode1 = $params['params']['dataparams']['projectcode'];

    //       $proj2 = $params['params']['dataparams']['projectname2'];
    //       $projid2 = isset($params['params']['dataparams']['projectid2']) ? $params['params']['dataparams']['projectid2'] : '';
    //       $projcode2 = $params['params']['dataparams']['projectcode2'];
    //       break;
    //   }
    //   $acno = $params['params']['dataparams']['contra'];

    //   if ($acno == "") {
    //     $acno = "ALL";
    //   }

    //   $filter = "";

    //   switch ($companyid) {
    //     case 1: //vitaline
    //     case 10: //afti
    //     case 12: //afti usd
    //     case 23: //labsol cebu
    //     case 41: // labsol manila
    //     case 52: //technolab
    //       if ($costcenter != "") {
    //         $filter .= " and head.projectid = '" . $costcenterid . "'";
    //       }
    //       if ($center != "") {
    //         $filter .= " and cntnum.center='" . $center . "'";
    //       }
    //       break;
    //     case 15: //nathina
    //       if ($proj1 != "" && $proj2 != "") {
    //         $filter .= " and proj.code between '$projcode1' and '$projcode2'";
    //       }
    //       if ($acno1 != "" && $acno2 != "") {
    //         $filter .= " and (replace(ifnull(coa.acno,''),'\\\','') between replace('$contra1','\\\','') and replace('$contra2','\\\',''))";
    //       }
    //       break;
    //   }


    //   if ($client != "") {
    //     $filter .= " and client.client='" . $client . "' ";
    //   }

    //   if ($companyid != 15) { //not nathina
    //     if ($acno != "ALL") {
    //       $filter .= " and coa.acno='\\" . $acno . "'";
    //     }
    //   }

    //   $query = $this->subsidiary_query($field, $start, $end, $acno, $isposted, $filter, $companyid);

    //   $data1 = $this->coreFunctions->opentable($query);
    //   $result = json_decode(json_encode($data1), true);

    //   $chkqry = $this->begbal1_chkqry($field, $start, $end, $acno, $isposted, $filter, $companyid);
    //   $bdat = $this->coreFunctions->opentable($chkqry);
    //   $bdata = json_decode(json_encode($bdat), true);

    //   $bal = 0;

    //   for ($i = 0; $i < count($result); $i++) {

    //     if (isset($result[$i]['docno'])) {
    //       if ($result[$i]['docno'] == 'Beginning Balance') {
    //         $bal = $result[$i]['begbal'];
    //       } else {
    //         switch ($cat) {
    //           case 'L':
    //           case 'R':
    //           case 'C':
    //           case 'O':
    //             $bal += ($result[$i]['cr'] - $result[$i]['db']);
    //             break;
    //           default:
    //             $bal += ($result[$i]['db'] - $result[$i]['cr']);
    //             break;
    //         } // end switch
    //         $result[$i]['begbal'] = $bal;
    //       }
    //     }
    //   } // end for loop

    //   if (empty($bdata)) {
    //     $str .= $this->reporter->startrow();
    //     if ($companyid == 15) { //nathina
    //       $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('Beginning Balance', '150', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('', '70', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');

    //       $str .= $this->reporter->col('', '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('0.00', '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('0.00', '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');

    //       $str .= $this->reporter->col('0.00', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
    //     } else {
    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       if ($companyid == 8) { //maxipro
    //         $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'R', $font, $fontsize, '', '',  '', '');
    //       }
    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       if ($companyid == 3) { //conti
    //         $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
    //       }
    //       if ($companyid == 19) { //housegem
    //         $str .= $this->reporter->col('', '60', null, false, '1px solid', '', '', $font, $fontsize, '', '', '', '');
    //         $str .= $this->reporter->col('', '60', null, false, '1px solid', '', '', $font, $fontsize, '', '', '', '');
    //       }
    //       $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
    //       $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
    //     }
    //     $str .= $this->reporter->endrow();
    //   } else {
    //     $str .= $this->reporter->startrow();
    //     if ($companyid == 15) { //nathina
    //       $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('Beginning Balance', '150', null, false, '1px solid', '', 'L', $font, $fontsize, '',  '', '');
    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('', '70', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');

    //       $str .= $this->reporter->col('', '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('0.00', '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('0.00', '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '');

    //       $str .= $this->reporter->col(number_format($bdata[0]['begbal'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px', 1);
    //     } else {
    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       switch ($companyid) {
    //         case 15: //nathina
    //           $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
    //           break;
    //         case 8: //maxipro
    //           $str .= $this->reporter->col('', '150', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
    //           break;
    //       }

    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       if ($companyid == 3) { //conti
    //         $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '');
    //       }
    //       if ($companyid == 19) { //housegem
    //         $str .= $this->reporter->col('', '60', null, false, '1px solid', '', '', $font, $fontsize, '', '', '');
    //         $str .= $this->reporter->col('', '60', null, false, '1px solid', '', '', $font, $fontsize, '', '', '');
    //       }
    //       $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '');
    //       $str .= $this->reporter->col(number_format($bdata[0]['begbal'], 2), '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '');
    //     }
    //     $str .= $this->reporter->endrow();
    //     $bal = $bdata[0]['begbal'];
    //   }
    // }


    switch ($companyid) {
      case 1: //vitaline
        $str .= $this->VITA_default_subtotal($params, $db, $cr, $bal);
        break;
      case 3: //conti
        $str .= $this->CONTI_default_subtotal($params, $db, $cr, $bal);
        break;
      case 19: //housegem
        $str .= $this->default_housegemsubtotal($params, $db, $cr, $bal);
        break;
      case 15: //nathina
        $str .= $this->nathina_subtotal($params, $db, $cr, $bal);
        break;
      default:
        $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);
        break;
    }


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_SUBSIDIARY_LEDGER_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    $layoutsize = 800;

    if ($companyid == 8) { //maxipro
      $layoutsize = 1000;
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $center = $params['params']['dataparams']['center'];
        $costcenter = isset($params['params']['dataparams']['costcenter']) ? $params['params']['dataparams']['costcenter'] : "";
        $costcenterid = isset($params['params']['dataparams']['costcenterid']) ? $params['params']['dataparams']['costcenterid'] : 0;
        break;
      case 24: //goodfound
        $center = $params['params']['dataparams']['center'];
        $project = $params['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $params['params']['dataparams']['projectid'];
        }
        break;
      case 15: //nathina
        $acno = $params['params']['dataparams']['contra'];
        $acnoname = $params['params']['dataparams']['acnoname'];
        $client = $params['params']['dataparams']['client'];

        $acno1 = $params['params']['dataparams']['acnoname'];
        $contra1 = $params['params']['dataparams']['contra'];

        $acno2 = $params['params']['dataparams']['acnoname2'];
        $contra2 = $params['params']['dataparams']['contra2'];

        $proj1 = $params['params']['dataparams']['projectname'];
        $projid1 = isset($params['params']['dataparams']['projectid']) ? $params['params']['dataparams']['projectid'] : '';
        $projcode1 = $params['params']['dataparams']['projectcode'];

        $proj2 = $params['params']['dataparams']['projectname2'];
        $projid2 = isset($params['params']['dataparams']['projectid2']) ? $params['params']['dataparams']['projectid2'] : '';
        $projcode2 = $params['params']['dataparams']['projectcode2'];
        break;
      case 55: //afli
        $project = $params['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $params['params']['dataparams']['projectid'];
        }
        $acnoid2 = $params['params']['dataparams']['acnoid2'];
        $acnoid3 = $params['params']['dataparams']['acnoid3'];
        $acnoid4 = $params['params']['dataparams']['acnoid4'];

        break;
      default:
        $project = $params['params']['dataparams']['dprojectname'];
        if ($project != "") {
          $projectid = $params['params']['dataparams']['projectid'];
        }
        break;
    }

    $acno = $params['params']['dataparams']['contra'];
    $acnoid = $params['params']['dataparams']['acnoid'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $client = $params['params']['dataparams']['client'];
    $cost = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);
    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(cr-db,2)),0) ';
        break;

      default:
        $field = ' ifnull(sum(round(db-cr,2)),0) ';
        break;
    }

    $filter = "";
    if ($companyid != 15) { //not nathina
      if ($acno != "ALL") {
        if ($companyid == 55) { //afli
          $filter .= " and detail.acnoid in ('$acnoid','$acnoid2', '$acnoid3', '$acnoid4')";
        } else {
          $filter .= " and coa.acno='\\" . $acno . "'";
        }
      }
    }


    switch ($companyid) {
      case 12: //afti usd
        if ($costcenter != "") {
          $filter .= " and head.projectid = '" . $costcenterid . "'";
        }

        if ($center != "") {
          $filter .= " and cntnum.center='" . $center . "'";
        }
        break;
      case 10: //afti
        if ($costcenter != "") {
          $filter .= " and detail.projectid = '" . $costcenterid . "'";
        }

        if ($center != "") {
          $filter .= " and cntnum.center='" . $center . "'";
        }

        $deptid = $params['params']['dataparams']['ddeptname'];
        if ($deptid == "") {
          $dept = "";
        } else {
          $dept = $params['params']['dataparams']['deptid'];
        }
        if ($deptid != "") {
          $filter .= " and detail.deptid = $dept";
        }

        break;

      case 24: //goodfound
        if ($center != '' && $center != '0') {
          $filter .= " and cntnum.center='" . $center . "' ";
        }
        if ($project != "") {
          $filter .= " and detail.projectid = '" . $projectid . "' ";
        }
        break;
      case 15: //nathina
        if ($proj1 != "" && $proj2 != "") {
          $filter .= " and proj.code between '$projcode1' and '$projcode2'";
        }
        if ($acno1 != "" && $acno2 != "") {
          $filter .= " and (replace(ifnull(coa.acno,''),'\\\','') between replace('$contra1','\\\','') and replace('$contra2','\\\',''))";
        }
        break;
      default:
        if ($project != "") {
          $filter .= " and head.projectid = '" . $projectid . "'";
        }
        if ($this->companysetup->getmultibranch($params['params'])) {
          $center = $params['params']['dataparams']['center'];
          $filter .= " and cntnum.center='" . $center . "' ";
        }
        break;
    }

    if ($client != "") {
      $filter .= " and client.client='" . $client . "' ";
    }

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';

    $fontsize = 10;
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $font = "cambria";
        break;

      default:
        $font = $this->companysetup->getrptfont($params['params']);
        break;
    }

    $col = array(
      array('60', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );

    $col2 = array(
      array('60', null, false, '1px solid', '', 'C', $font, '10', 'B', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', 'B', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );

    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params);
    $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;
    $bal = 0;
    $acno = '';
    $acno2 = '';


    if (!empty($data)) {
      foreach ($data as $key => $data_) {
        if ($acno2 != $data_->acno) { // account groupings
          if ($acno2 != '') { // subtotal for accounts
            switch ($companyid) {
              case 3: //conti
                $str .= $this->CONTI_default_subtotal($params, $db, $cr, $bal);
                break;
              case 19: //housegem
                $str .= $this->default_housegemsubtotal($params, $db, $cr, $bal);
                break;
              case 15: //nathina
                $str .= $this->nathina_subtotal($params, $db, $cr, $bal);
                break;
              default:
                $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);
                break;
            }

            $db = 0;
            $cr = 0;
            $bal = 0;
          }


          $value2 = array($data_->acno . '     -', $data_->acnoname, '', '', '', '', '');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->row($col2, $value2);
          $str .= $this->reporter->endrow();
        }
        //$bal = 0;
        //for ($i = 0; $i < count($result); $i++) {
        if ($data_->docno == 'Beginning Balance') {
          $bal = $data_->begbal;
        } else {
          switch ($cat) {
            case 'L':
            case 'R':
            case 'C':
            case 'O':
              $bal += ($data_->cr - $data_->db);
              break;
            default:
              $bal += ($data_->db - $data_->cr);
              break;
          } // end switch
          $data_->begbal = $bal;
        }

        //table

        switch ($companyid) {
          case 8: //maxipro
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data_->dateid, '100', null, false, '1px solid', '', 'CT', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->docno, '120', null, false, '1px solid', '', 'CT', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->projcode, '140', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->clientname, '160', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->rem, '230', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($data_->db, 2), '75', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', '', 1);
            $str .= $this->reporter->col(number_format($data_->cr, 2), '75', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', '', 0);
            $str .= $this->reporter->col(number_format($data_->begbal, 2), '100', null, false, '1px solid', '', 'RT', $font, $fontsize, '', '', '', '', '', '', 0);

            $totaldb += $data_->db;
            $totalcr += $data_->cr;

            $db += $data_->db;
            $cr += $data_->cr;
            $bal = $data_->begbal;
            break;
          case 15: //nathina
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data_->dateid, '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->docno, '150', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');

            $str .= $this->reporter->col($data_->yourref, '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->projname, '70', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->clientname, '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->rem, '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($data_->db, 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($data_->cr, 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($data_->begbal, 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');

            $totaldb += $data_->db;
            $totalcr += $data_->cr;

            $db += $data_->db;
            $cr += $data_->cr;
            $bal = $data_->begbal;
            break;
          default:
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data_->dateid, '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');

            $str .= $this->reporter->col($data_->docno, '150', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');


            $str .= $this->reporter->col($data_->clientname, '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($data_->rem, '170', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');

            switch ($companyid) {
              case 19: //housegem
                $str .= $this->reporter->col($data_->drem, '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                $str .= $this->reporter->col($data_->postdate, '100', null, false, '1px solid', '', 'C', $font, $fontsize, '', '', '', '', '');
                $str .= $this->reporter->col(number_format($data_->db, 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
                $str .= $this->reporter->col(number_format($data_->cr, 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
                $str .= $this->reporter->col(number_format($data_->begbal, 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
                break;
              default:
                if ($companyid == 3) { //conti
                  $str .= $this->reporter->col($data_->projname, '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                }
                if ($companyid == 1) { //vitaline
                  $str .= $this->reporter->col($data_->drem, '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                }
                $str .= $this->reporter->col(number_format($data_->db, 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '', '', 1);
                $str .= $this->reporter->col(number_format($data_->cr, 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '', '', 0);
                $str .= $this->reporter->col(number_format($data_->begbal, 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '', '', 0);
                break;
            }

            $totaldb += $data_->db;
            $totalcr += $data_->cr;

            $db += $data_->db;
            $cr += $data_->cr;
            $bal = $data_->begbal;
            break;
        }

        switch ($companyid) {
          case 10: //afti
          case 12: //afti usd
            break;
          default:
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->printline();
              $str .= $this->reporter->page_break();

              $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
              if (!$allowfirstpage) {

                $str .= $this->headerlabel($params);
              }
              $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], '', $font, $fontsize + 1, $params);
              $page = $page + $count;
            } // end if
            break;
        }
        //} // end for loop
        $acno2 = $data_->acno;
      }
    }

    switch ($companyid) {
      case 1: //vitaline
        $str .= $this->VITA_default_subtotal($params, $db, $cr, $bal);
        break;
      case 3: //conti
        $str .= $this->CONTI_default_subtotal($params, $db, $cr, $bal);
        break;
      case 19: //housegem
        $str .= $this->default_housegemsubtotal($params, $db, $cr, $bal);
        break;
      case 15: //nathina
        $str .= $this->nathina_subtotal($params, $db, $cr, $bal);
        break;
      default:
        $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);
        break;
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', $layoutsize, null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function begbal_chkqry($field, $start, $end, $acno, $isposted, $filter, $companyid)
  {
    $selecthjc = '';
    $selectjc = '';
    if ($companyid == 8) { //maxipro
      $selecthjc = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                  from ((((glhead as head 
                  left join gldetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.clientid = head.clientid)))
                  left join client as dclient on((dclient.clientid = detail.clientid))) 
                  left join cntnum on cntnum.trno=head.trno 
                  where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                           client.client,head.clientname,head.rem,detail.line,
                           coa.acno,coa.acnoname,detail.db,detail.cr,
                           coa.alias,detail.ref,detail.postdate,dclient.client,
                           detail.rem,detail.checkno,coa.acnoid ";
      $selectjc = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                    client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                    coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                    coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                    detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr from
                    ((((lahead as head 
                    left join ladetail as detail on((head.trno = detail.trno))) 
                    left join coa on((coa.acnoid = detail.acnoid))) 
                    left join client on((client.client = head.client)))
                    left join client as dclient on((dclient.client = detail.client))) 
                    left join cntnum on cntnum.trno=head.trno 
                    where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                    group by head.trno,head.doc,head.docno,head.dateid,
                    client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                    coa.alias,detail.ref,detail.postdate,dclient.client,
                    detail.rem,detail.checkno,coa.acnoid ";
    }

    $leftjoin = "";
    $datefilter = "date(head.dateid)";
    $coafilter = "and coa.acno='\\" . $acno . "'";
    switch ($companyid) {
      case 15: //nathina
        $leftjoin = 'left join projectmasterfile as proj on proj.line = head.projectid';
        // $coafilter = "";
        break;
      case 19: //housegem
        $datefilter = "date(detail.postdate)";
        break;
    }

    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                  '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate

            from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                  from ((((glhead as head 
                  left join gldetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.clientid = head.clientid)))
                  left join client as dclient on((dclient.clientid = detail.clientid))) 
                  left join cntnum on cntnum.trno=head.trno 
                  $leftjoin
                  where $datefilter < '" . $start . "' $coafilter " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                           client.client,head.clientname,head.rem,detail.line,
                           coa.acno,coa.acnoname,detail.db,detail.cr,
                           coa.alias,detail.ref,detail.postdate,dclient.client,
                           detail.rem,detail.checkno,coa.acnoid $selecthjc) as a
              left join coa on a.acno=coa.acno 
              left join client on client.client = a.client
              where coa.acno is not null 
              group by coa.acno,coa.acnoname,coa.detail, coa.alias";

        break;

      case 1: // unposted 
        $query = "
            select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,'' as rem, '' as acno,
            '' as acnoname,0 as db,0 as cr,$field as begbal, '' as detail, null as alias, null as postdate
            from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
            client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
            coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
            coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
            detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr from
            ((((lahead as head 
            left join ladetail as detail on((head.trno = detail.trno))) 
            left join coa on((coa.acnoid = detail.acnoid))) 
            left join client on((client.client = head.client)))
            left join client as dclient on((dclient.client = detail.client))) 
            left join cntnum on cntnum.trno=head.trno 
            $leftjoin
            where $datefilter < '" . $start . "' $coafilter " . $filter . " 
            group by head.trno,head.doc,head.docno,head.dateid,
            client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
            coa.alias,detail.ref,detail.postdate,dclient.client,
            detail.rem,detail.checkno,coa.acnoid $selectjc
            ) as a
            left join coa on a.acno=coa.acno 
            left join client on client.client = a.client
            where coa.acno is not null 
            order by  acno,dateid,docno
            ";
        break;

      case 2: // all 
        $query = "
        select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                  '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate
            from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                  from ((((glhead as head 
                  left join gldetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.clientid = head.clientid)))
                  left join client as dclient on((dclient.clientid = detail.clientid))) 
                  left join cntnum on cntnum.trno=head.trno 
                  $leftjoin
                  where $datefilter < '" . $start . "' $coafilter " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                           client.client,head.clientname,head.rem,detail.line,
                           coa.acno,coa.acnoname,detail.db,detail.cr,
                           coa.alias,detail.ref,detail.postdate,dclient.client,
                           detail.rem,detail.checkno,coa.acnoid
                $selecthjc
              union all 
                select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr from
                ((((lahead as head 
                left join ladetail as detail on((head.trno = detail.trno))) 
                left join coa on((coa.acnoid = detail.acnoid))) 
                left join client on((client.client = head.client)))
                left join client as dclient on((dclient.client = detail.client))) 
                left join cntnum on cntnum.trno=head.trno 
                $leftjoin
                where $datefilter < '" . $start . "' $coafilter " . $filter . " 
                group by head.trno,head.doc,head.docno,head.dateid,
                client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                coa.alias,detail.ref,detail.postdate,dclient.client,
                detail.rem,detail.checkno,coa.acnoid  
                $selectjc   
              ) as a
              left join coa on a.acno=coa.acno 
              left join client on client.client = a.client
              where coa.acno is not null 
              group by coa.acno,coa.acnoname,coa.detail, coa.alias
      ";

        break;
    } // end switch
    return $query;
  }

  private function begbal1_chkqry($field, $start, $end, $acno, $isposted, $filter, $companyid)
  {
    $hjcselect = '';
    $jcselect = '';

    if ($companyid == 8) { //maxipro
      $hjcselect = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr
                    from ((((hjchead as head
                    left join gldetail as detail on((head.trno = detail.trno)))
                    left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.client = head.client)))
                    left join client as dclient on((dclient.clientid = detail.clientid)))
                    left join cntnum on cntnum.trno=head.trno
                    where date(head.dateid) < '" . $start . "' " . $filter . "
                    group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                            coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                            detail.rem,detail.checkno,coa.acnoid ";
      $jcselect = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                  from ((((jchead as head 
                  left join ladetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.client = head.client)))
                  left join client as dclient on((dclient.client = detail.client))) 
                  left join cntnum on cntnum.trno=head.trno 
                  where date(head.dateid) < '" . $start . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                          client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                          coa.alias,detail.ref,detail.postdate,dclient.client,
                          detail.rem,detail.checkno,coa.acnoid ";
    }

    switch ($companyid) {
      case 15: //nathina
        $leftjoin = 'left join projectmasterfile as proj on proj.line = head.projectid';
        $datefilter = "date(head.dateid)";
        break;
      case 19: //housegem
        $leftjoin = '';
        $datefilter = "date(detail.postdate)";
        break;
      default:
        $leftjoin = '';
        $datefilter = "date(head.dateid)";
        break;
    }
    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr, 
                      ifnull(sum(round(db-cr,2)),0)  as begbal
              from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr
                    from ((((glhead as head
                    left join gldetail as detail on((head.trno = detail.trno)))
                    left join coa on((coa.acnoid = detail.acnoid)))
                    left join client on((client.clientid = head.clientid)))
                    left join client as dclient on((dclient.clientid = detail.clientid)))
                    left join cntnum on cntnum.trno=head.trno
                    $leftjoin
                    where $datefilter < '" . $start . "' " . $filter . "
                    group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                            coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                            detail.rem,detail.checkno,coa.acnoid $hjcselect) as a
              left join coa on a.acno=coa.acno
              left join client on client.client = a.client
              where coa.acno is not null
              group by coa.acno,coa.acnoname,coa.detail, coa.alias";
        break;

      case 1: // unposted 
        $query = "select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr,$field as begbal
            from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                  from ((((lahead as head 
                  left join ladetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.client = head.client)))
                  left join client as dclient on((dclient.client = detail.client))) 
                  left join cntnum on cntnum.trno=head.trno 
                  $leftjoin
                  where $datefilter < '" . $start . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                          client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                          coa.alias,detail.ref,detail.postdate,dclient.client,
                          detail.rem,detail.checkno,coa.acnoid $jcselect) as a
            left join coa on a.acno=coa.acno 
            left join client on client.client = a.client
            where coa.acno is not null";
        break;

      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr, 
                    ifnull(sum(round(db-cr,2)),0)  as begbal
              from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr
                  from ((((glhead as head
                  left join gldetail as detail on((head.trno = detail.trno)))
                  left join coa on((coa.acnoid = detail.acnoid)))
                  left join client on((client.clientid = head.clientid)))
                  left join client as dclient on((dclient.clientid = detail.clientid)))
                  left join cntnum on cntnum.trno=head.trno
                  $leftjoin
                  where $datefilter < '" . $start . "' " . $filter . "
                  group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                          coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                          detail.rem,detail.checkno,coa.acnoid $hjcselect) as a
              left join coa on a.acno=coa.acno
              left join client on client.client = a.client
              where coa.acno is not null
              group by coa.acno,coa.acnoname,coa.detail, coa.alias
              union all
              select null as dateid,'Beginning Balance' as docno,0 as db,0 as cr,$field as begbal
              from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                            client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                            coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                            coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                            detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                    from ((((lahead as head 
                    left join ladetail as detail on((head.trno = detail.trno))) 
                    left join coa on((coa.acnoid = detail.acnoid))) 
                    left join client on((client.client = head.client)))
                    left join client as dclient on((dclient.client = detail.client))) 
                    left join cntnum on cntnum.trno=head.trno 
                    $leftjoin
                    where $datefilter < '" . $start . "' " . $filter . " 
                    group by head.trno,head.doc,head.docno,head.dateid,
                            client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                            coa.alias,detail.ref,detail.postdate,dclient.client,
                            detail.rem,detail.checkno,coa.acnoid $jcselect) as a
              left join coa on a.acno=coa.acno 
              left join client on client.client = a.client
              where coa.acno is not null";
        break;
    } // end switch
    return $query;
  }

  private function subsidiaryledger_query($field, $start, $end, $acno, $isposted, $filter, $sort = "", $companyid)
  {
    if ($sort == "") {
      $sort = " order by  acno,dateid,docno";
    }

    $hjcselect1 = '';
    $hjcselect2 = '';
    $jcselect1 = '';
    $jcselect2 = '';
    $select = '';
    $allselect = '';
    $grpselect = '';
    $allgrp = '';

    if ($companyid == 8) { //maxipro
      $select = ', proj.code as projcode';
      $allselect = ', projcode';
      $grpselect = ',proj.code';
      $allgrp = ', projcode';
      $hjcselect1 = " union all 
                      select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,head.clientname as clientname,head.rem as rem,
                      coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                      detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr, proj.name as projname,detail.line,
                      '' as yourref,'' as createby,'' as si,'' as chsi,proj.code as projcode
                      from ((((hjchead as head 
                      left join gldetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.client = head.client)))
                      left join client as dclient on((dclient.clientid = detail.clientid))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line = detail.projectid
                      where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,
                      head.rem,coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                      detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,proj.code ";
      $hjcselect2 = " union all 
                      select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,head.clientname as clientname,head.rem as rem,
                      coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                      detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,detail.line,
                      head.yourref,head.createby,'' as si,'' as chsi,proj.code as projcode
                      from ((((hjchead as head 
                      left join gldetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.client = head.client)))
                      left join client as dclient on((dclient.clientid = detail.clientid))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line= detail.projectid
                      where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
                      detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,head.yourref,head.createby,proj.code ";
      $jcselect1 =  " union all 
                      select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,head.clientname as clientname,head.rem as rem,
                      coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                      detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,detail.line,
                      '' as yourref,'' as createby,'' as si,'' as chsi,proj.code as projcode
                      from ((((jchead as head 
                      left join ladetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.client = head.client)))
                      left join client as dclient on((dclient.client = detail.client))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line = detail.projectid
                      where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                      detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,proj.code ";

      $jcselect2 =  " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                      client.client,head.clientname as clientname,head.rem as rem,
                      coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
                      coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,
                      detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname,detail.line,
                      head.yourref,head.createby,'' as si,'' as chsi,proj.code as projcode
                      from ((jchead as head 
                      left join ladetail as detail on ((head.trno = detail.trno)))
                      left join coa on ((coa.acnoid = detail.acnoid)))
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line =detail.projectid
                      left join client on client.client=head.client
                      where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,coa.acno,coa.acnoname,coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem ,
                      detail.checkno ,coa.acnoid,proj.name,detail.line,head.yourref,head.createby,proj.code ";
    }

    $coafilter = '';
    $ljoin = '';
    $datefilter = "date(head.dateid)";
    if ($companyid != 15) { //not nathina
      $coafilter .= "and coa.acno='\\" . $acno . "'";
    }
    if ($companyid == 19) { //housegem
      $datefilter = "date(detail.postdate)";
    }

    if ($companyid == 8) { //maxipro
      $ljoin = "left join projectmasterfile as proj on proj.line = detail.projectid";
    } else {
      $ljoin = "left join projectmasterfile as proj on proj.line = head.projectid";
    }

    switch ($isposted) {
      case 0: // posted
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                         '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate,
                         group_concat(distinct a.drem separator '/') as drem,'' as yourref,'' as createby,0 as trno,'' as projname,'' as si,'' as chsi $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                               client.client,dclient.clientname as clientname,head.rem as rem,
                               coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                               coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                               detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr, proj.name as projname,
                               detail.line,'' as yourref,'' as createby, head.ourref as si,head.chsino as chsi $select
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' $coafilter " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,dclient.clientname,head.rem,
                                coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,head.ourref,head.chsino $grpselect
                        $hjcselect1 ) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail,coa.alias $allgrp
                  union all    
                  select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                         coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,
                         group_concat(distinct a.drem separator '/') as drem,a.yourref,a.createby,a.trno,a.projname,a.si,a.chsi $allselect             
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client,dclient.clientname as clientname,head.rem as rem,
                                coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
                                coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                                detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,
                                detail.line,head.yourref,head.createby,head.ourref as si,head.chsino as chsi $select
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter between '" . $start . "' and '" . $end . "' $coafilter " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,
                        client.client,dclient.clientname,head.rem,coa.acno,coa.acnoname,
                        coa.alias,detail.ref,detail.postdate,dclient.client,
                        detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,head.yourref,head.createby,head.ourref,head.chsino $grpselect
                        $hjcselect2 ) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null
                  group by a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                           a.ref, a.rem,coa.acno,coa.acnoname, a.db, a.cr,coa.detail, coa.alias, 
                           date(postdate),line,a.yourref,a.createby,a.trno,a.projname,a.si,a.chsi $allgrp
                  $sort ";


        break;

      case 1: // unposted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,
                        '' as ref,'' as checkno,'' as rem, '' as acno,'' as acnoname,0 as db,0 as cr,
                        $field as begbal, '' as detail, null as alias, null as postdate,'' as drem,'' as yourref,
                        '' as createby,0 as trno,'' as projname,'' as si,'' as chsi  $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                              client.client,dclient.clientname as clientname,head.rem as rem,
                              coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                              coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                              detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,
                              detail.line,'' as yourref,'' as createby,head.ourref as si,head.chsino as chsi $select
                        from lahead as head 
                        left join ladetail as detail on head.trno = detail.trno
                        left join coa on coa.acnoid = detail.acnoid
                        left join client on client.client = head.client
                        left join client as dclient on dclient.client = detail.client
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' $coafilter " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid, client.client,dclient.clientname,head.rem,
                                 coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                 detail.rem,detail.checkno,coa.acnoid,detail.line,detail.rem,proj.name,head.ourref,head.chsino $grpselect
                        $jcselect1) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail, coa.alias $allgrp
                  union all
                  select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                          case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                          coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, a.alias, date(postdate) as postdate,a.drem,a.yourref,a.createby,
                          a.trno,a.projname,a.si,a.chsi  $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client,dclient.clientname as clientname,head.rem as rem,
                                coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
                                coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,
                                detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname,
                                detail.line,head.yourref,head.createby,head.ourref as si,head.chsino as chsi $select
                        from lahead as head 
                        left join ladetail as detail on head.trno = detail.trno
                        left join coa on coa.acnoid = detail.acnoid
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        left join client on client.client=head.client
                        left join client as dclient on dclient.client = detail.client
                        where $datefilter between '" . $start . "' and '" . $end . "' $coafilter " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,dclient.clientname,head.rem,coa.acno,coa.acnoname,
                                  coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem ,
                                  detail.checkno ,coa.acnoid,proj.name,detail.line,head.yourref,head.createby,head.ourref,head.chsino $grpselect
                        $jcselect2 ) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail, coa.alias,drem,line,a.dateid,a.docno,a.client,
                          a.clientname,a.ref,a.checkno,a.rem,a.db,a.cr,coa.detail, a.alias,a.drem,a.yourref,
                          a.createby,a.trno,a.postdate,a.projname,a.si,a.chsi $allgrp
            $sort";

        break;

      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                          '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate,
                          group_concat(distinct a.drem separator '/') as drem,'' as yourref,'' as createby,0 as trno,
                          '' as projname,'' as si,'' as chsi  $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client,dclient.clientname as clientname,head.rem as rem,coa.acno as acno,coa.acnoname as acnoname,
                                round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,
                                detail.postdate as postdate,dclient.client as dclient,detail.rem as drem,detail.checkno as checkno,
                                coa.acnoid as acnoid,'p' as tr, proj.name as projname,detail.line,'' as yourref,'' as createby,head.ourref as si,head.chsino as chsi $select
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' $coafilter " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,dclient.clientname,head.rem,
                                  coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                  detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,head.ourref,head.chsino $grpselect
                        $hjcselect1 
                        union all
                        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client,dclient.clientname as clientname,head.rem as rem,coa.acno as acno,coa.acnoname as acnoname,
                                round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,
                                detail.postdate as postdate,dclient.client as dclient,detail.rem as drem,detail.checkno as checkno,
                                coa.acnoid as acnoid,'p' as tr,proj.name as projname,detail.line,'' as yourref,'' as createby,head.ourref as si,head.chsino as chsi  $select
                        from ((((lahead as head 
                        left join ladetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.client = head.client)))
                        left join client as dclient on((dclient.client = detail.client))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' $coafilter " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,client.client,dclient.clientname,head.rem,coa.acno,coa.acnoname,
                            detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,detail.rem,detail.checkno,
                            coa.acnoid,proj.name,detail.line,head.ourref,head.chsino $grpselect
                  $jcselect1 ) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail,coa.alias $allgrp
                  union all    
                  select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                          coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,
                          group_concat(distinct a.drem separator '/') as drem,a.yourref,a.createby,a.trno,a.projname,a.si,a.chsi  $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client,dclient.clientname as clientname,head.rem as rem,coa.acno as acno,coa.acnoname as acnoname,
                                sum(detail.db) as db,sum(detail.cr) as cr,coa.alias as alias,detail.ref as ref,detail.postdate as postdate,
                                dclient.client as dclient,detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,
                                'p' as tr,proj.name as projname,detail.line, head.yourref,head.createby,head.ourref as si,head.chsino as chsi $select
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter between '" . $start . "' and '" . $end . "' $coafilter " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,dclient.clientname,head.rem,coa.acno,coa.acnoname,
                                  coa.alias,detail.ref,detail.postdate,dclient.client,detail.rem,detail.checkno,coa.acnoid,
                                  proj.name,detail.line,head.yourref,head.createby,head.ourref,head.chsino $grpselect
                        $hjcselect2 
                        union all
                        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client,dclient.clientname as clientname,head.rem as rem,coa.acno as acno,coa.acnoname as acnoname,
                                sum(detail.db) as db,sum(detail.cr) as cr,coa.alias as alias,detail.ref as ref,detail.postdate as postdate,
                                detail.client as dclient,detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,
                                proj.name as projname,detail.line,head.yourref,head.createby,head.ourref as si,head.chsino as chsi  $select
                        from ((lahead as head 
                        left join ladetail as detail on ((head.trno = detail.trno)))
                        left join coa on ((coa.acnoid = detail.acnoid)))
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        left join client on client.client=head.client
                        left join client as dclient on dclient.client = detail.client
                        where $datefilter between '" . $start . "' and '" . $end . "' $coafilter " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,dclient.clientname,head.rem,
                                  coa.acno,coa.acnoname,coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem,
                                  detail.checkno ,coa.acnoid,proj.name,detail.line,head.yourref,head.createby,head.ourref,head.chsino $grpselect
                        $jcselect2) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null
                  group by a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,a.ref, a.rem,coa.acno,coa.acnoname, a.db, 
                           a.cr,coa.detail, coa.alias, date(postdate),line,a.yourref, a.createby,a.trno,a.projname,a.si,a.chsi $allgrp $sort";
        break;
    } // end switch
    return $query;
  } // end fn

  private function subsidiaryledger_query3m($field, $start, $end, $acno, $isposted, $filter)
  {
    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
          '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate,
            group_concat(distinct a.drem separator '/') as drem,'' as yourref,'' as createby, a.encodeddate, a.doc, a.trno
             
          from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                    client.client,head.clientname as clientname,head.rem as rem,
                    coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                    coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                    detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr, proj.name as projname,detail.line,detail.encodeddate
              from ((((glhead as head 
              left join gldetail as detail on((head.trno = detail.trno))) 
              left join coa on((coa.acnoid = detail.acnoid))) 
              left join client on((client.clientid = head.clientid)))
              left join client as dclient on((dclient.clientid = detail.clientid))) 
              left join cntnum on cntnum.trno=head.trno 
              left join projectmasterfile as proj on proj.line = head.projectid
              where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
              group by head.trno,head.doc,head.docno,head.dateid,
                    client.client,head.clientname,head.rem,
                    coa.acno,coa.acnoname,detail.db,detail.cr,
                    coa.alias,detail.ref,detail.postdate,dclient.client,
                    detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,detail.encodeddate) as a
          left join coa on a.acno=coa.acno 
          where coa.acno is not null 
          group by coa.acno,coa.acnoname,coa.detail,coa.alias,a.encodeddate,a.doc,a.trno
          union all    
          select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,
                group_concat(distinct a.drem separator '/') as drem,a.yourref,a.createby,a.encodeddate,a.doc,a.trno
              
          from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                        client.client,head.clientname as clientname,head.rem as rem,
                        coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
                        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,detail.line,head.yourref,head.createby,detail.encodeddate
                from ((((glhead as head 
                left join gldetail as detail on((head.trno = detail.trno))) 
                left join coa on((coa.acnoid = detail.acnoid))) 
                left join client on((client.clientid = head.clientid)))
                left join client as dclient on((dclient.clientid = detail.clientid))) 
                left join cntnum on cntnum.trno=head.trno 
                left join projectmasterfile as proj on proj.line= head.projectid
                where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                group by head.trno,head.doc,head.docno,head.dateid,
                        client.client,head.clientname,head.rem,coa.acno,coa.acnoname,
                        coa.alias,detail.ref,detail.postdate,dclient.client,
                        detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line,head.yourref,head.createby,detail.encodeddate) as a
          left join coa on a.acno=coa.acno 
          where coa.acno is not null
          group by a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                 a.ref, a.rem,coa.acno,coa.acnoname, a.db, a.cr,coa.detail, coa.alias, date(postdate),line,a.yourref,a.createby,a.encodeddate,a.doc,a.trno
          order by  acno,encodeddate,docno";


        break;

      case 1: // unposted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,
                   '' as ref,'' as checkno,'' as rem, '' as acno,'' as acnoname,0 as db,0 as cr,
                   $field as begbal, '' as detail, null as alias, null as postdate,a.drem,'' as yourref,'' as createby, '' as encodeddate, a.doc, a.trno
            from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,detail.line, detail.encodeddate
                  from ((((lahead as head 
                  left join ladetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.client = head.client)))
                  left join client as dclient on((dclient.client = detail.client))) 
                  left join cntnum on cntnum.trno=head.trno 
                  left join projectmasterfile as proj on proj.line = head.projectid
                  where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                          client.client,head.clientname,head.rem,coa.acno,coa.acnoname,detail.db,detail.cr,
                          coa.alias,detail.ref,detail.postdate,dclient.client,
                          detail.rem,detail.checkno,coa.acnoid,proj.name,detail.line, detail.encodeddate) as a
            left join coa on a.acno=coa.acno 
            where coa.acno is not null 
            group by coa.acno,coa.acnoname,coa.detail, coa.alias,drem,line,encodeddate,doc,trno
            union all
            select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                    case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                    coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, a.alias, date(postdate) as postdate,a.drem,a.yourref,a.createby, a.encodeddate, a.doc, a.trno
            from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,
                          coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,
                          detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname,detail.line,head.yourref,head.createby, detail.encodeddate
                  from ((lahead as head 
                  left join ladetail as detail on ((head.trno = detail.trno)))
                  left join coa on ((coa.acnoid = detail.acnoid)))
                  left join cntnum on cntnum.trno=head.trno 
                  left join projectmasterfile as proj on proj.line =head.projectid
                  left join client on client.client=head.client
                  where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                            client.client,head.clientname,head.rem,
                            coa.acno,coa.acnoname,
                            coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem ,
                            detail.checkno ,coa.acnoid,proj.name,detail.line,head.yourref,head.createby,detail.encodeddate) as a
            left join coa on a.acno=coa.acno 
            where coa.acno is not null 
            order by  acno,encodeddate,docno";

        break;

      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                  '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate,
                  group_concat(distinct a.drem separator '/') as drem,'' as yourref,'' as createby, '' as encodeddate, a.doc, a.trno
                  from ( 
                    select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr, detail.encodeddate
                  from ((((glhead as head 
                  left join gldetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.clientid = head.clientid)))
                  left join client as dclient on((dclient.clientid = detail.clientid))) 
                  left join cntnum on cntnum.trno=head.trno 
                  where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                          coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                          detail.rem,detail.checkno,coa.acnoid, detail.encodeddate
                  union all
                  select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                        coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                        detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr, detail.encodeddate
                  from ((((lahead as head 
                  left join ladetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.client = head.client)))
                  left join client as dclient on((dclient.client = detail.client))) 
                  left join cntnum on cntnum.trno=head.trno 
                  where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,
                          head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                          coa.alias,detail.ref,detail.postdate,dclient.client,
                          detail.rem,detail.checkno,coa.acnoid, detail.encodeddate) as a
            left join coa on a.acno=coa.acno 
            where coa.acno is not null 
            group by coa.acno,coa.acnoname,coa.detail, coa.alias, a.encodeddate, a.doc, a.trno

            union all   

            select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                  case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                  coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,
                  group_concat(distinct a.drem separator '/') as drem,a.yourref,a.createby,a.encodeddate, a.doc, a.trno
            from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                          client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                          coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                          coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                          detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,head.yourref,head.createby,detail.encodeddate
                  from ((((glhead as head 
                  left join gldetail as detail on((head.trno = detail.trno))) 
                  left join coa on((coa.acnoid = detail.acnoid))) 
                  left join client on((client.clientid = head.clientid)))
                  left join client as dclient on((dclient.clientid = detail.clientid))) 
                  left join cntnum on cntnum.trno=head.trno 
                  where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                  group by head.trno,head.doc,head.docno,head.dateid,
                            client.client,head.clientname,head.rem,detail.line, coa.acno,coa.acnoname,detail.db,detail.cr,
                            coa.alias,detail.ref,detail.postdate,dclient.client,
                            detail.rem,detail.checkno,coa.acnoid,head.yourref,head.createby,detail.encodeddate) as a
            left join coa on a.acno=coa.acno 
            where coa.acno is not null 
            group by a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                 a.ref, a.rem,coa.acno,coa.acnoname, a.db, a.cr,coa.detail, coa.alias, date(postdate),line,a.yourref,a.createby,a.encodeddate,a.doc, a.trno

            union all


            select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
            case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
            coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,
            group_concat(distinct a.drem separator '/') as drem,a.yourref,a.createby,a.encodeddate,a.doc, a.trno
            from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                        client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                        coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                        coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,detail.rem as drem,
                        detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,head.yourref,head.createby,detail.encodeddate
                from ((lahead as head 
                left join ladetail as detail on ((head.trno = detail.trno)))
                left join coa on ((coa.acnoid = detail.acnoid)))
                left join cntnum on cntnum.trno=head.trno 
                left join client on client.client=head.client
                where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                group by head.trno,head.doc,head.docno,head.dateid,
                      client.client,head.clientname,head.rem,detail.line,
                      coa.acno,coa.acnoname,detail.db,detail.cr ,
                      coa.alias,detail.ref ,detail.client,detail.rem ,
                      detail.checkno ,coa.acnoid, detail.postdate,head.yourref,head.createby,detail.encodeddate) as a
            left join coa on a.acno=coa.acno 
            where coa.acno is not null 
            group by a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,
                 a.ref, a.rem,coa.acno,coa.acnoname, a.db, a.cr,coa.detail, coa.alias, date(postdate),line,a.yourref,a.createby,a.encodeddate,a.doc,a.trno
            order by  acno,encodeddate,docno";
        break;
    } // end switch
    return $query;
  }

  private function subsidiaryledger_query_n($field, $start, $end, $acno, $isposted, $filter)
  {

    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,
                        '' as ref,'' as checkno,'' as rem,coa.acno,coa.acnoname,0 as db,
                        0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate,projname
                from ( select head.trno as trno,head.doc as doc,head.docno as docno,
                              date(head.dateid) as dateid,client.client as client,
                              head.clientname as clientname,head.rem as rem,coa.acno as acno,
                              coa.acnoname as acnoname,round(detail.db,2) as db,
                              round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,
                              detail.postdate as postdate,dclient.client as dclient,
                              detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,
                              'p' as tr, proj.name as projname
                      from ((((glhead as head 
                      left join gldetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.clientid = head.clientid)))
                      left join client as dclient on((dclient.clientid = detail.clientid))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line = head.projectid
                      left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                      where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,client.client,
                               head.clientname,head.rem,coa.acno,coa.acnoname,detail.db,detail.cr,
                               coa.alias,detail.ref,detail.postdate,dclient.client,
                               detail.rem,detail.checkno,coa.acnoid,proj.name) as a
                left join coa on a.acno=coa.acno 
                left join client on client.client = a.client
                where coa.acno is not null 
                group by coa.acno,coa.acnoname,coa.detail, coa.alias, a.projname
                union all    
                select a.dateid,a.docno,a.client,client.clientname,a.ref,a.checkno,
                      case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                      coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, 
                      date(postdate) as postdate,a.projname
                from ( select head.trno as trno,head.doc as doc,head.docno as docno,
                              date(head.dateid) as dateid,client.client as client,
                              head.clientname as clientname,head.rem as rem,
                              coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,
                              sum(detail.cr) as cr,coa.alias as alias,detail.ref as ref,
                              detail.postdate as postdate,dclient.client as dclient,
                              detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,
                              'p' as tr,proj.name as projname
                      from ((((glhead as head 
                      left join gldetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.clientid = head.clientid)))
                      left join client as dclient on((dclient.clientid = detail.clientid))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line= head.projectid
                      left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                      where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,
                      client.client,head.clientname,head.rem,coa.acno,coa.acnoname,
                      coa.alias,detail.ref,detail.postdate,dclient.client,
                      detail.rem,detail.checkno,coa.acnoid,proj.name) as a
                left join coa on a.acno=coa.acno 
                left join client on client.client = a.client
                where coa.acno is not null
                order by  acno,dateid,docno";
        break;

      case 1: // unposted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,
                        '' as ref,'' as checkno,'' as rem, '' as acno,'' as acnoname,0 as db,0 as cr,
                        $field as begbal, '' as detail, null as alias, null as postdate,projname
                from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                              client.client as client,head.clientname as clientname,head.rem as rem,
                              coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                              coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                              detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname
                      from ((((lahead as head 
                      left join ladetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.client = head.client)))
                      left join client as dclient on((dclient.client = detail.client))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line = head.projectid
                      left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                      where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,
                              client.client,head.clientname,head.rem,coa.acno,coa.acnoname,detail.db,detail.cr,
                              coa.alias,detail.ref,detail.postdate,dclient.client,
                              detail.rem,detail.checkno,coa.acnoid,proj.name) as a
                left join coa on a.acno=coa.acno 
                left join client on client.client = a.client
                where coa.acno is not null 
                group by coa.acno,coa.acnoname,coa.detail, coa.alias, a.projname
                union all
                select a.dateid,a.docno,a.client,client.clientname,a.ref,a.checkno,
                      case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                      coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, a.alias, date(postdate) as postdate,a.projname
                from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                            head.client as client,head.clientname as clientname,head.rem as rem,
                            coa.acno as acno,coa.acnoname as acnoname,sum(detail.db) as db,sum(detail.cr) as cr,
                            coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,
                            detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname
                      from ((lahead as head 
                      left join ladetail as detail on ((head.trno = detail.trno)))
                      left join coa on ((coa.acnoid = detail.acnoid)))
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line =head.projectid
                      left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                      where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,
                                  head.client,head.clientname,head.rem,
                                  coa.acno,coa.acnoname,
                                  coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem ,
                                  detail.checkno ,coa.acnoid,proj.name) as a
                left join coa on a.acno=coa.acno 
                left join client on client.client = a.client
                where coa.acno is not null 
                order by  acno,dateid,docno";
        break;

      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                        '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, null as postdate
                  from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                                coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                                coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                                detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                        from ((((glhead as head left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        left join projectmasterfile as proj on proj.line = head.projectid
                        left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,
                                client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                                coa.alias,detail.ref,detail.postdate,dclient.client,detail.rem,detail.checkno,coa.acnoid
                        union all
                        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                                coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                                coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                                detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                        from ((((lahead as head 
                        left join ladetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.client = head.client)))
                        left join client as dclient on((dclient.client = detail.client))) 
                        left join cntnum on cntnum.trno=head.trno 
                        left join projectmasterfile as proj on proj.line = head.projectid
                        left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                        where date(head.dateid) < '" . $start . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,
                                client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                                coa.alias,detail.ref,detail.postdate,dclient.client,
                                detail.rem,detail.checkno,coa.acnoid) as a
                  left join coa on a.acno=coa.acno 
                  left join client on client.client = a.client
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail, coa.alias
                  union all         
                  select a.dateid,a.docno,a.client,client.clientname,a.ref,a.checkno,
                        case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate
                  from ( select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                                client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                                coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                                coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                                detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        left join projectmasterfile as proj on proj.line = head.projectid
                        left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                        where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " group by head.trno,head.doc,head.docno,head.dateid,
                        client.client,head.clientname,head.rem,detail.line, coa.acno,coa.acnoname,detail.db,detail.cr,
                        coa.alias,detail.ref,detail.postdate,dclient.client,
                        detail.rem,detail.checkno,coa.acnoid) as a
                  left join coa on a.acno=coa.acno 
                  left join client on client.client = a.client
                  where coa.acno is not null 
                  union all
                  select a.dateid,a.docno,a.client,client.clientname,a.ref,a.checkno,
                        case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                        coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                              head.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
                              coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                              coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,detail.rem as drem,
                              detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr 
                      from ((lahead as head 
                      left join ladetail as detail on ((head.trno = detail.trno)))
                      left join coa on ((coa.acnoid = detail.acnoid)))
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line = head.projectid
                      left join subproject as sub on sub.line=detail.subproject and sub.projectid=proj.line
                      where date(head.dateid) between '" . $start . "' and '" . $end . "' and coa.acno='\\" . $acno . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,
                              head.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr ,
                              coa.alias,detail.ref ,detail.client,detail.rem,detail.checkno ,coa.acnoid, detail.postdate) as a
                  left join coa on a.acno=coa.acno 
                  left join client on client.client = a.client
                  where coa.acno is not null 
                  order by  acno,dateid,docno";
        break;
    }


    return $query;
  } // end fn

  private function subsidiary_query($field, $start, $end, $acno, $isposted, $filter, $companyid)
  {

    $selecthjc1 = '';
    $selecthjc2 = '';
    $selectjc1 = '';
    $selectjc2 = '';
    $select = '';
    $allselect = '';
    $grpselect = '';
    $allgrp = '';

    if ($companyid == 8) { //maxipro
      $select = ', proj.code as projcode';
      $allselect = ',projcode';
      $grpselect = ',proj.code';
      $allgrp = ',a.projcode';

      $selecthjc1 = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                            client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                            coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                            coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                            detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname, proj.code as projcode
                      from ((((hjchead as head 
                      left join gldetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.client = head.client)))
                      left join client as dclient on((dclient.clientid = detail.clientid))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line = detail.projectid
                      where date(head.dateid) < '" . $start . "' " . $filter . " 
                      group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                               coa.alias,detail.ref,detail.postdate,dclient.client,detail.rem,detail.checkno,coa.acnoid,proj.name,proj.code ";

      $selecthjc2 = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                              coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                              coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                              detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,proj.code as projcode
                      from ((((hjchead as head 
                      left join gldetail as detail on((head.trno = detail.trno))) 
                      left join coa on((coa.acnoid = detail.acnoid))) 
                      left join client on((client.client = head.client)))
                      left join client as dclient on((dclient.clientid = detail.clientid))) 
                      left join cntnum on cntnum.trno=head.trno 
                      left join projectmasterfile as proj on proj.line = detail.projectid
                      where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " ";

      $selectjc1 = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                                coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                                detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname,proj.code as projcode
                        from ((((jchead as head 
                        left join ladetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.client = head.client)))
                        left join client as dclient on((dclient.client = detail.client))) 
                        left join cntnum on cntnum.trno=head.trno 
                        left join projectmasterfile as proj on proj.line = detail.projectid
                        where date(head.dateid) < '" . $start . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                                coa.alias,detail.ref,detail.postdate,dclient.client,detail.rem,detail.checkno,coa.acnoid,proj.name,proj.code ";

      $selectjc2 = " union all select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                               client.client,head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
                               coa.alias as alias,detail.ref as ref,detail.postdate as postdate,detail.client as dclient,detail.rem as drem,
                               detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname,proj.code as projcode
                        from ((((jchead as head 
                        left join ladetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.client = head.client)))
                        left join client as dclient on((dclient.client = detail.client))) 
                        left join cntnum on cntnum.trno=head.trno 
                        left join projectmasterfile as proj on proj.line= detail.projectid
                        where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,coa.acno,coa.acnoname,detail.db,detail.cr,
                                 coa.alias,detail.ref, detail.postdate ,detail.client,detail.rem,detail.checkno ,coa.acnoid,proj.name,proj.code ";
    }

    $datefilter = "date(head.dateid)";
    if ($companyid == 19) { //housegem
      $datefilter = "date(detail.postdate)";
    }

    if ($companyid == 8) { //maxipro
      $ljoin = "left join projectmasterfile as proj on proj.line = detail.projectid";
    } else {
      $ljoin = "left join projectmasterfile as proj on proj.line = head.projectid";
    }

    if ($companyid == 15) $grpselect = ',proj.code';
    switch ($isposted) {
      case 0: // posted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,
                          '' as checkno,'' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail,
                          coa.alias, null as postdate, '' as projname $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                              client.client,head.clientname as clientname,head.rem as rem,detail.line as line,
                              coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
                              coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                              detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr,proj.name as projname $select
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                                 coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                 detail.rem,detail.checkno,coa.acnoid,proj.name $grpselect 
                        $selecthjc1) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail, coa.alias $allgrp
                  union all    
                  select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                          coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,a.projname $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,
                                head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,coa.acnoname as acnoname,
                                round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,
                                detail.postdate as postdate,dclient.client as dclient,detail.rem as drem,detail.checkno as checkno,
                                coa.acnoid as acnoid,'p' as tr,proj.name as projname $select
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter between '" . $start . "' and '" . $end . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid, client.client,head.clientname,head.rem,detail.line, 
                                 coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                 detail.rem,detail.checkno,coa.acnoid,proj.name $grpselect 
                        $selecthjc2) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null
                  order by  acno,dateid,docno";
        break;

      case 1: // unposted 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                          '' as rem, '' as acno,'' as acnoname,0 as db,0 as cr, $field as begbal, '' as detail, null as alias, 
                          null as postdate,'' as projname $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,
                                head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,coa.acnoname as acnoname,
                                round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,
                                detail.postdate as postdate,dclient.client as dclient,detail.rem as drem,detail.checkno as checkno,
                                coa.acnoid as acnoid,'p' as tr,proj.name as projname $select
                        from ((((lahead as head 
                        left join ladetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.client = head.client)))
                        left join client as dclient on((dclient.client = detail.client))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                                  coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                  detail.rem,detail.checkno,coa.acnoid,proj.name $grpselect 
                        $selectjc1) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail, coa.alias $allgrp
                  union all
                  select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                          coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, a.alias, date(postdate) as postdate, a.projname $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,
                              client.client,head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,
                              coa.acnoname as acnoname,detail.db as db,detail.cr as cr,coa.alias as alias,detail.ref as ref,
                              detail.postdate as postdate,detail.client as dclient,detail.rem as drem,
                              detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr,proj.name as projname
                        from ((((lahead as head 
                        left join ladetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.client = head.client)))
                        left join client as dclient on((dclient.client = detail.client))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter between '" . $start . "' and '" . $end . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                                  coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref, detail.postdate ,detail.client,
                                  detail.rem,detail.checkno ,coa.acnoid,proj.name $grpselect 
                        $selectjc2 ) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  order by acno,dateid,docno";
        break;

      case 2: // all 
        $query = "select null as dateid,'Beginning Balance' as docno,'' as client,'' as clientname,'' as ref,'' as checkno,
                          '' as rem,coa.acno,coa.acnoname,0 as db,0 as cr,$field as begbal,coa.detail, coa.alias, 
                          null as postdate,'' as projname $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,
                                head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,
                                coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,
                                detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
                                detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr ,proj.name as projname $select
                        from ((((glhead as head left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                                  coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                  detail.rem,detail.checkno,coa.acnoid,proj.name $grpselect
                        union all
                        select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,
                                head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,coa.acnoname as acnoname,
                                round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,
                                detail.postdate as postdate,dclient.client as dclient,detail.rem as drem,detail.checkno as checkno,
                                coa.acnoid as acnoid,'p' as tr ,proj.name as projname $select
                        from ((((lahead as head 
                        left join ladetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.client = head.client)))
                        left join client as dclient on((dclient.client = detail.client))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter < '" . $start . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                                  coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                  detail.rem,detail.checkno,coa.acnoid,proj.name $grpselect 
                        $selecthjc1 
                        $selectjc1) as a
                  left join coa on a.acno=coa.acno 
                  left join client on client.client = a.client
                  where coa.acno is not null 
                  group by coa.acno,coa.acnoname,coa.detail, coa.alias $allgrp
                  union all         
                  select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                          coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,projname $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,
                                head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,coa.acnoname as acnoname,
                                round(detail.db,2) as db,round(detail.cr,2) as cr,coa.alias as alias,detail.ref as ref,
                                detail.postdate as postdate,dclient.client as dclient,detail.rem as drem,detail.checkno as checkno,
                                coa.acnoid as acnoid,'p' as tr ,proj.name as projname $select
                        from ((((glhead as head 
                        left join gldetail as detail on((head.trno = detail.trno))) 
                        left join coa on((coa.acnoid = detail.acnoid))) 
                        left join client on((client.clientid = head.clientid)))
                        left join client as dclient on((dclient.clientid = detail.clientid))) 
                        left join cntnum on cntnum.trno=head.trno 
                        $ljoin
                        where $datefilter between '" . $start . "' and '" . $end . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line, 
                                  coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref,detail.postdate,dclient.client,
                                  detail.rem,detail.checkno,coa.acnoid,proj.name $grpselect 
                        $selecthjc2) as a
                  left join coa on a.acno=coa.acno 
                  where coa.acno is not null 
                  union all
                  select a.dateid,a.docno,a.client,a.clientname,a.ref,a.checkno,case a.ref when '' then a.rem else concat(a.rem,' ',a.ref) end as rem,
                          coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail, coa.alias, date(postdate) as postdate,projname $allselect
                  from (select head.trno as trno,head.doc as doc,head.docno as docno,date(head.dateid) as dateid,client.client,
                                head.clientname as clientname,head.rem as rem,detail.line as line,coa.acno as acno,coa.acnoname as acnoname,
                                detail.db as db,detail.cr as cr,coa.alias as alias,detail.ref as ref,detail.postdate as postdate,
                                detail.client as dclient,detail.rem as drem,detail.checkno as checkno ,coa.acnoid as acnoid,
                                'u' as tr ,proj.name as projname $select
                        from ((lahead as head 
                        left join ladetail as detail on ((head.trno = detail.trno)))
                        left join coa on ((coa.acnoid = detail.acnoid)))
                        left join cntnum on cntnum.trno=head.trno 
                        left join client on client.client=head.client
                        $ljoin
                        where $datefilter between '" . $start . "' and '" . $end . "' " . $filter . " 
                        group by head.trno,head.doc,head.docno,head.dateid,client.client,head.clientname,head.rem,detail.line,
                                  coa.acno,coa.acnoname,detail.db,detail.cr,coa.alias,detail.ref ,detail.client,detail.rem,
                                  detail.checkno ,coa.acnoid,proj.name, detail.postdate $grpselect 
                        $selectjc2) as a
                  left join coa on a.acno=coa.acno 
                  left join client on client.client = a.client
                  where coa.acno is not null 
                  order by acno,dateid,docno";
        break;
    } // end switch

    return $query;
  } // end fn

  private function summ_header($params)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));
    $font = $this->companysetup->getrptfont($params['params']);
    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];

    $center1 = $params['params']['center'];
    $username = $params['params']['user'];


    if ($acnoname == "") {
      $acnoname = "ALL";
    } else {
      $acnoname = $acno . ' - ' . $acnoname;
    }

    switch ($isposted) {
      case 0:
        $isposted = 'posted';
        break;

      case 1:
        $isposted = 'unposted';
        break;

      case 2:
        $isposted = 'ALL';
        break;
    }

    switch ($companyid) {
      case 15: //nathina
        $acno1 = $params['params']['dataparams']['acnoname'];
        $contra1 = $params['params']['dataparams']['contra'];

        $acno2 = $params['params']['dataparams']['acnoname2'];
        $contra2 = $params['params']['dataparams']['contra2'];

        $proj1 = $params['params']['dataparams']['projectname'];
        $projcode1 = $params['params']['dataparams']['projectcode'];

        $proj2 = $params['params']['dataparams']['projectname2'];
        $projcode2 = $params['params']['dataparams']['projectcode2'];
        break;
      case 55: //homeworks
        $acno2 = $params['params']['dataparams']['contra2'];
        $acnoname2 = $params['params']['dataparams']['acnoname2'];
        $acno3 = $params['params']['dataparams']['contra3'];
        $acnoname3 = $params['params']['dataparams']['acnoname3'];
        $acno4 = $params['params']['dataparams']['contra4'];
        $acnoname4 = $params['params']['dataparams']['acnoname4'];
        break;
    }

    if ($companyid == 15) { //nathina
      $acnoname = $contra1 . ' - ' . $acno1 . ' to ' . $contra2 . ' - ' . $acno2;
      $project = $projcode1 . ' - ' . $proj1 . ' to ' . $projcode2 . ' - ' . $proj2;
    } else {
      if ($acnoname == "") {
        $acnoname = "ALL";
      } else {
        if ($companyid == 55) { //homeworks
          $accounts = [];
          if (!empty($acno)) $accounts[] = $acno . ' - ' . $acnoname;
          if (!empty($acno2)) $accounts[] = $acno2 . ' - ' . $acnoname2;
          if (!empty($acno3)) $accounts[] = $acno3 . ' - ' . $acnoname3;
          if (!empty($acno4)) $accounts[] = $acno4 . ' - ' . $acnoname4;
          $acnoname = implode(',', $accounts);
        } else {
          $acnoname = $acno . ' - ' . $acnoname;
        }
      }
    }

    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SUBSIDIARY LEDGER - SUMMARY', 300, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, '11', '', '', '');


    $str .= $this->reporter->col('Transaction: ' . strtoupper($isposted), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->col('Accounts: ' . strtoupper($acnoname), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    switch ($companyid) {
      case 8: //maxipro
        $project = $params['params']['dataparams']['projectname'];
        $subproject = $params['params']['dataparams']['subprojectname'];
        if ($project == '') {
          $project = 'ALL';
        }
        if ($subproject == '') {
          $subproject = 'ALL';
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Project : ' . $project, '800', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sub-Project : ' . $subproject, '800', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        break;
      case 15: //nathina
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Project : ' . $project, '800', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        break;
    }

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, '11', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    return $str;
  }

  private function default_summary_table_cols($layoutsize, $border, $font, $fontsize, $params)
  {
    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Account Description', '600', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Amount', '150', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function SUBSIDIARY_LEDGER_SUMM_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));
    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $client = $params['params']['dataparams']['client'];

    $count = 60;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';

    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summ_header($params);
    $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $params);

    $amt = 0;
    $totalamt = 0;
    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        switch ($cat) {
          case 'L':
          case 'R':
          case 'C':
            $amt = ($data[$i]['begbal'] + $data[$i]['cr']) - $data[$i]['db'];
            break;
          default:
            $amt = ($data[$i]['begbal'] + $data[$i]['db']) - $data[$i]['cr'];
            break;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data[$i]['acnoname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($amt, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $totalamt += $amt;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();

          #header here
          $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
          if (!$allowfirstpage) {

            $str .= $this->summ_header($params);
          }
          $str .= $this->default_summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $params);
          #header end
          $page = $page + $count;
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL: ', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalamt, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function threemheaderlabel($params)
  {
    $layoutsize = '1000';
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $font = $this->companysetup->getrptfont($params['params']);
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];

    $center1 = $params['params']['center'];
    $username = $params['params']['user'];


    if ($acnoname == "") {
      $acnoname = "ALL";
    } else {
      $acnoname = $acno . ' - ' . $acnoname;
    }

    switch ($isposted) {
      case 0:
        $isposted = 'posted';
        break;

      case 1:
        $isposted = 'unposted';
        break;

      case 2:
        $isposted = 'ALL';
        break;
    }

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUBSIDIARY LEDGER', $layoutsize, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), $layoutsize, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction: ' . strtoupper($isposted), $layoutsize, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Accounts: ' . strtoupper($acnoname), $layoutsize, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction Date', '75', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Document#', '100', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Supplier/Customer', '180', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Particular', '180', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Debit', '75', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Credit', '75', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Balance', '100', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('User', '100', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Encoded Date', '115', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();


    return $str;
  }

  private function THREEMDEFAULT_SUBSIDIARY_LEDGER_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    $project = $params['params']['dataparams']['dprojectname'];
    if ($project != "") {
      $projectid = $params['params']['dataparams']['projectid'];
    }

    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $client = $params['params']['dataparams']['client'];

    $cost = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);
    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(cr-db,2)),0) ';
        break;

      default:
        $field = ' ifnull(sum(round(db-cr,2)),0) ';
        break;
    }

    $filter = "";
    if ($acno != "ALL") {
      $filter .= " and coa.acno='\\" . $acno . "'";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "'";
    }

    if ($client != "") {
      $filter .= " and client.client='" . $client . "' ";
    }

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';

    $fontsize = 11;
    $font = $this->companysetup->getrptfont($params['params']);

    $col = array(
      array('60', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );

    $col2 = array(
      array('60', null, false, '1px solid', '', 'C', $font, '10', 'B', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', 'B', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', $font, '10', '', '', '1px', '', ''),
    );



    $str .= $this->reporter->beginreport();
    $str .= $this->threemheaderlabel($params);

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;
    $bal = 0;
    $acno = '';
    $acno2 = '';


    if (!empty($data)) {
      foreach ($data as $key => $data_) {

        if ($acno2 != $data_->acno) { // account groupings
          if ($acno2 != '') { // subtotal for accounts

            $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);

            $db = 0;
            $cr = 0;
            $bal = 0;
          }

          $value2 = array($data_->acno . '     -', $data_->acnoname, '', '', '', '', '');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->row($col2, $value2);
          $str .= $this->reporter->endrow();

          $query = $this->subsidiaryledger_query3m($field, $start, $end, $data_->acno, $isposted, $filter);

          $data1 = $this->coreFunctions->opentable($query);
          $result = json_decode(json_encode($data1), true);

          $chkqry = $this->begbal_chkqry($field, $start, $end, $data_->acno, $isposted, $filter, $companyid);
          $bdat = $this->coreFunctions->opentable($chkqry);
          $bdata = json_decode(json_encode($bdat), true);

          $bal = 0;
          for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['docno'] == 'Beginning Balance') {
              $bal = $result[$i]['begbal'];
            } else {
              switch ($cat) {
                case 'L':
                case 'R':
                case 'C':
                case 'O':
                  $bal += ($result[$i]['cr'] - $result[$i]['db']);
                  break;
                default:
                  $bal += ($result[$i]['db'] - $result[$i]['cr']);
                  break;
              } // end switch
              $result[$i]['begbal'] = $bal;
            }
          } // end for loop


          if (empty($bdata) || $bdat[0]->begbal == 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();
          }

          for ($i = 0; $i < count($result); $i++) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($result[$i]['dateid'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['docno'], '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['clientname'], '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['rem'], '170', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['db'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['cr'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['begbal'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['createby'], '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['encodeddate'], '125', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $totaldb += $result[$i]['db'];
            $totalcr += $result[$i]['cr'];
            $db += $result[$i]['db'];
            $cr += $result[$i]['cr'];
            $bal = $result[$i]['begbal'];
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->printline();
              $str .= $this->reporter->page_break();
              $str .= $this->threemheaderlabel($params);
              $page = $page + $count;
            } // end if
          } // end foreach loop

          $acno2 = $data_->acno;
        }
      }
    } else {

      $acno = $params['params']['dataparams']['contra'];

      if ($acno == "") {
        $acno = "ALL";
      }

      $filter = "";


      if ($client != "") {
        $filter .= " and client.client='" . $client . "' ";
      }

      if ($acno != "ALL") {
        $filter .= " and coa.acno='\\" . $acno . "'";
      }


      $query = $this->subsidiary_query($field, $start, $end, $acno, $isposted, $filter, $companyid);
      $data1 = $this->coreFunctions->opentable($query);
      $result = json_decode(json_encode($data1), true);

      $chkqry = $this->begbal1_chkqry($field, $start, $end, $acno, $isposted, $filter, $companyid);
      $bdat = $this->coreFunctions->opentable($chkqry);
      $bdata = json_decode(json_encode($bdat), true);

      $bal = 0;

      for ($i = 0; $i < count($result); $i++) {

        if ($result[$i]['docno'] == 'Beginning Balance') {
          $bal = $result[$i]['begbal'];
        } else {
          switch ($cat) {
            case 'L':
            case 'R':
            case 'C':
            case 'O':
              $bal += ($result[$i]['cr'] - $result[$i]['db']);
              break;
            default:
              $bal += ($result[$i]['db'] - $result[$i]['cr']);
              break;
          } // end switch
          $result[$i]['begbal'] = $bal;
        }
      } // end for loop


      if (empty($bdata)) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($bdata[0]['begbal'], 2), '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $bal = $bdata[0]['begbal'];
      }
    }
    $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function subsidiary_withitems_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $isposted = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['enddate']));

    $project = $params['params']['dataparams']['dprojectname'];
    if ($project != "") {
      $projectid = $params['params']['dataparams']['projectid'];
    }

    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $client = $params['params']['dataparams']['client'];

    $cost = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);
    switch ($cat) {
      case 'L':
      case 'R':
      case 'C':
      case 'O':
        $field = ' ifnull(sum(round(cr-db,2)),0) ';
        break;

      default:
        $field = ' ifnull(sum(round(db-cr,2)),0) ';
        break;
    }

    $filter = "";
    if ($acno != "ALL") {
      $filter .= " and coa.acno='\\" . $acno . "'";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "'";
    }

    if ($client != "") {
      $filter .= " and client.client='" . $client . "' ";
    }

    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';

    $fontsize = 11;
    $font = $this->companysetup->getrptfont($params['params']);

    $col2 = array(
      array('100', null, false, '1px solid', '', 'C', $font, '10', 'B', '', '1px', '', ''),
      array('180', null, false, '1px solid', '', 'L', $font, '10', 'B', '', '1px', '', ''),
      array('720', null, false, '1px solid', '', 'L', $font, '10', 'B', '', '1px', '', '')
    );

    $str .= $this->reporter->beginreport();
    $str .= $this->threemheaderlabel($params);

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;
    $bal = 0;
    $acno = '';
    $acno2 = '';

    $layoutsize = '1000';


    if (!empty($data)) {
      foreach ($data as $key => $data_) {
        if ($acno2 != $data_->acno) { // account groupings
          if ($acno2 != '') { // subtotal for accounts
            $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);
            $db = 0;
            $cr = 0;
            $bal = 0;
          }
          $value2 = array($data_->acno . '     -', $data_->acnoname, '', '', '', '', '');
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->row($col2, $value2);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $query = $this->subsidiaryledger_query3m($field, $start, $end, $data_->acno, $isposted, $filter);

          $data1 = $this->coreFunctions->opentable($query);
          $result = json_decode(json_encode($data1), true);

          $chkqry = $this->begbal_chkqry($field, $start, $end, $data_->acno, $isposted, $filter, $companyid);
          $bdat = $this->coreFunctions->opentable($chkqry);
          $bdata = json_decode(json_encode($bdat), true);

          $bal = 0;
          for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['docno'] == 'Beginning Balance') {
              $bal = $result[$i]['begbal'];
            } else {
              switch ($cat) {
                case 'L':
                case 'R':
                case 'C':
                case 'O':
                  $bal += ($result[$i]['cr'] - $result[$i]['db']);
                  break;
                default:
                  $bal += ($result[$i]['db'] - $result[$i]['cr']);
                  break;
              } // end switch
              $result[$i]['begbal'] = $bal;
            }
          } // end for loop


          if (empty($bdata) || $bdat[0]->begbal == 0) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col('Beginning Balance', '460', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col('0.00', '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col('0.00', '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col('0.00', '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col('', '215', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
          }

          for ($i = 0; $i < count($result); $i++) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($result[$i]['dateid'], '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['docno'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['clientname'], '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['rem'], '180', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['db'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['cr'], 2), '75', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col(number_format($result[$i]['begbal'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['createby'], '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
            $str .= $this->reporter->col($result[$i]['encodeddate'], '115', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
            $totaldb += $result[$i]['db'];
            $totalcr += $result[$i]['cr'];
            $db += $result[$i]['db'];
            $cr += $result[$i]['cr'];
            $bal = $result[$i]['begbal'];
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->printline();
              $str .= $this->reporter->page_break();
              $str .= $this->threemheaderlabel($params);
              $page = $page + $count;
            } // end if
            switch ($result[$i]['doc']) {
              case 'RR':
              case 'DM':
              case 'SJ':
              case 'CM':
                $data2 = $this->subsidiaryledger_itemsquery($result[$i]['trno']);
                if (!empty($data2)) {
                  $str .= $this->reporter->addline();
                  $str .= $this->reporter->endtable();
                  $str .= $this->reporter->begintable($layoutsize);
                  $str .= $this->reporter->startrow();
                  $str .= $this->reporter->col('', '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                  $str .= $this->reporter->col('Item Description', '275', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('Qty', '100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('UOM', '75', null, false, '1px solid', '', 'C', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('Amount', '100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('Discount', '90', null, false, '1px solid', '', 'C', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('Total', '100', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('', '10', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('Warehouse', '100', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '', '');
                  $str .= $this->reporter->col('', '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                  $str .= $this->reporter->endrow();

                  foreach ($data2 as $ikey => $items) {
                    $str .= $this->reporter->addline();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col($items['itemname'], '275', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col(number_format($items['isqty'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col($items['uom'], '75', null, false, '1px solid', '', 'C', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col(number_format($items['isamt'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col($items['disc'], '90', null, false, '1px solid', '', 'C', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col(number_format($items['ext'], 2), '100', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col('', '10', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col($items['warehouse'], '100', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->col('', '75', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    if ($this->reporter->linecounter == $page) {
                      $str .= $this->reporter->endtable();
                      $str .= $this->reporter->printline();
                      $str .= $this->reporter->page_break();
                      $str .= $this->threemheaderlabel($params);
                      $page = $page + $count;
                    } // end if
                  }
                  $str .= $this->reporter->endtable();
                }
                break;
            }
          } // end foreach loop

          $acno2 = $data_->acno;
        }
      }
    } else {

      $acno = $params['params']['dataparams']['contra'];

      if ($acno == "") {
        $acno = "ALL";
      }

      $filter = "";


      if ($client != "") {
        $filter .= " and client.client='" . $client . "' ";
      }

      if ($acno != "ALL") {
        $filter .= " and coa.acno='\\" . $acno . "'";
      }


      $query = $this->subsidiary_query($field, $start, $end, $acno, $isposted, $filter, $companyid);
      $data1 = $this->coreFunctions->opentable($query);
      $result = json_decode(json_encode($data1), true);

      $chkqry = $this->begbal1_chkqry($field, $start, $end, $acno, $isposted, $filter, $companyid);
      $bdat = $this->coreFunctions->opentable($chkqry);
      $bdata = json_decode(json_encode($bdat), true);

      $bal = 0;

      for ($i = 0; $i < count($result); $i++) {

        if ($result[$i]['docno'] == 'Beginning Balance') {
          $bal = $result[$i]['begbal'];
        } else {
          switch ($cat) {
            case 'L':
            case 'R':
            case 'C':
            case 'O':
              $bal += ($result[$i]['cr'] - $result[$i]['db']);
              break;
            default:
              $bal += ($result[$i]['db'] - $result[$i]['cr']);
              break;
          } // end switch
          $result[$i]['begbal'] = $bal;
        }
      } // end for loop


      if (empty($bdata)) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Beginning Balance', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('0.00', '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($bdata[0]['begbal'], 2), '60', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $bal = $bdata[0]['begbal'];
      }
    }
    $str .= $this->default_subtotal($params, $db, $cr, $bal, $companyid);


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('</br></br></br>', '800', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $prepared = $params['params']['dataparams']['prepared'];
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col($prepared, '100', null, false, '1px solid', 'B', 'C', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '580', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function subsidiaryledger_itemsquery($trno)
  {
    $qry = "select item.barcode, item.itemname, stock.isqty, stock.uom, stock.isamt, stock.disc, stock.ext, wh.clientname as warehouse from lastock as stock left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid where stock.trno=" . $trno . "
        union all
        select item.barcode, item.itemname, stock.isqty, stock.uom, stock.isamt, stock.disc, stock.ext, wh.clientname as warehouse from glstock as stock left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid where stock.trno=" . $trno;
    $data = $this->coreFunctions->opentable($qry);
    return json_decode(json_encode($data), true);
  }

  public function notallowtoprint($config, $msg)
  {
    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($msg, '100', null, false, '1px solid', '', 'C', 'Century Gothic', 17, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class