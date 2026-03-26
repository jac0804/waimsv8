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

class general_journal
{
  public $modulename = 'General Journal Report';
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
    $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'approved'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    if ($companyid == 8) { //maxipro
      array_push($fields, 'dclientname', 'project', 'subprojectname', 'radioposttype');
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'dclientname.lookupclass', 'wasupplier');
      data_set($col1, 'dclientname.label', 'Customer');
      data_set($col1, 'project.name', "projectname");
      data_set($col1, 'subprojectname.type', "lookup");
      data_set($col1, 'subprojectname.action', "lookupsubproject");
      data_set($col1, 'subprojectname.addedparams', ['projectid']);
      data_set($col1, 'subprojectname.lookupclass', 'default');
      data_set($col1, 'subprojectname.required', false);
      data_set($col1, 'project.required', false);

      data_set(
        $col1,
        'radioposttype.options',
        [
          ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
          ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]
      );
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

    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,
                        '' as userid,'' as username,'' as approved,'0' as reporttype,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as reportusers,'0' as subproject,'' as subprojectname,
                        '' as project,'' as projectcode,'0' as projectid,'' as projectname,'0' as posttype,
                        '' as client,'0' as clientid,'' as clientname";

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
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 8: //maxipro
        $posttype   = $config['params']['dataparams']['posttype'];

        switch ($posttype) {
          case '0': //posted
            switch ($reporttype) {
              case '0': // SUMMARIZED
                $query = $this->maxipro_QUERY_SUMMARIZED_POSTED($config);
                break;
              case '1': // DETAILED
                $query = $this->maxipro_QUERY_DETAILED_POSTED($config);
                break;
            }
            break;
          case '1': //unposted
            switch ($reporttype) {
              case '0': // SUMMARIZED
                $query = $this->maxipro_QUERY_SUMMARIZED_UNPOSTED($config);
                break;
              case '1': // DETAILED
                $query = $this->maxipro_QUERY_DETAILED_UNPOSTED($config);
                break;
            }
            break;
          case '2': //all
            switch ($reporttype) {
              case '0': // SUMMARIZED
                $query = $this->default_maxipro_QUERY_SUMMARIZED($config);
                break;
              case '1': // DETAILED
                $query = $this->default_maxipro_QUERY_DETAILED($config);
                break;
            }
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


    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];

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

    if ($config['params']['companyid'] == 8) { //maxipro
      $client     = $config['params']['dataparams']['client'];
      $clientid     = $config['params']['dataparams']['clientid'];

      if ($client != "") {
        $filter .= " and hclient.clientid = '$clientid' ";
      }
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,
                    hclient.clientname as hclientname,head.dateid,
                    date_format(detail.postdate,'%Y-%m-%d') as postdate,
                    detail.checkno,coa.acno,coa.acnoname,
                    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                    dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref 
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.createby,head.docno,hclient.client as hclient,
                    hclient.clientname as hclientname,head.dateid,
                    date_format(detail.postdate,'%Y-%m-%d') as postdate,
                    detail.checkno,coa.acno,coa.acnoname,
                    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                    dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref 
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
              order by docno, cr";
    return $query;
  }


  public function default_maxipro_QUERY_DETAILED($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];

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

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      $client     = $config['params']['dataparams']['client'];
      $clientid     = $config['params']['dataparams']['clientid'];

      if ($client != "") {
        $filter .= " and hclient.clientid = '$clientid' ";
      }
    }


    $query = "select head.docno,head.clientname as hclientname,head.dateid,
                    date_format(detail.postdate,'%Y-%m-%d') as postdate,
                    detail.checkno,coa.acno,coa.acnoname,
                    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                    detail.db,detail.cr,detail.rem,detail.ref 
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject 
              where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.docno,head.clientname as hclientname,head.dateid,
                    date_format(detail.postdate,'%Y-%m-%d') as postdate,
                    detail.checkno,coa.acno,coa.acnoname,
                    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                    detail.db,detail.cr,detail.rem,detail.ref 
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
               left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject 
              where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
              order by docno, cr";

    return $query;
  }

  public function maxipro_QUERY_DETAILED_UNPOSTED($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];

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

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      $client     = $config['params']['dataparams']['client'];
      $clientid     = $config['params']['dataparams']['clientid'];

      if ($client != "") {
        $filter .= " and hclient.clientid = '$clientid' ";
      }
    }


    $query = "select head.docno,head.clientname as hclientname,head.dateid,
                    date_format(detail.postdate,'%Y-%m-%d') as postdate,
                    detail.checkno,coa.acno,coa.acnoname,
                    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                    detail.db,detail.cr,detail.rem,detail.ref 
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject 
              where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
              order by docno, cr";

    return $query;
  }

  public function maxipro_QUERY_DETAILED_POSTED($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];

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

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      $client     = $config['params']['dataparams']['client'];
      $clientid     = $config['params']['dataparams']['clientid'];

      if ($client != "") {
        $filter .= " and head.clientid = '$clientid' ";
      }
    }

    $query = "select head.docno,head.clientname as hclientname,head.dateid,
                    date_format(detail.postdate,'%Y-%m-%d') as postdate,
                    detail.checkno,coa.acno,coa.acnoname,
                    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,
                    detail.db,detail.cr,detail.rem,detail.ref 
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject 
              where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
              order by docno, cr";

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $fcenter    = $config['params']['dataparams']['center'];

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

    if ($companyid == 8) { //maxipro
      $project = $config['params']['dataparams']['dprojectname'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
      }

      if ($project != "") {
        $filter .= " and head.projectid = '" . $projectid . "' ";
      }
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, sum(cr) as credit, rem
   from(
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref from lahead as head
    left join ladetail as detail on detail.trno=head.trno 
    left join client as hclient on hclient.client=head.client
    left join client as dclient on dclient.client=detail.client
    left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
    union all
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref from glhead as head
    left join gldetail as detail on detail.trno=head.trno 
    left join client as hclient on hclient.clientid=head.clientid
    left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
    union all
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
    concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref from hglhead as head
    left join hgldetail as detail on detail.trno=head.trno 
    left join client as hclient on hclient.clientid=head.clientid
    left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno=head.trno
    where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
    order by dateid,docno) as t 
    group by docno, createby, dateid, rem order by docno";

    return $query;
  }


  public function default_maxipro_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];

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

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    if ($client != "") {
      $filter .= " and hclient.clientid = '$clientid' ";
    }


    $query = "select docno, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, 
                    sum(db) as debit, sum(cr) as credit, rem
              from(select head.docno,head.dateid,detail.checkno,coa.acno,coa.acnoname,
                          detail.db,detail.cr,detail.rem
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject
                  left join client as hclient on hclient.client=head.client
                  where head.doc='gj' and head.dateid between '$start' and '$end' $filter 
                  union all
                  select head.docno,head.dateid,detail.checkno,coa.acno,coa.acnoname,
                        detail.db,detail.cr,detail.rem
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject
                  left join client as hclient on hclient.clientid=head.clientid
                  where head.doc='gj' and head.dateid between '$start' and '$end' $filter) as t 
                  group by docno, t.dateid, rem order by docno";
    return $query;
  }

  public function maxipro_QUERY_SUMMARIZED_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];

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

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    if ($client != "") {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select docno, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, 
                    sum(db) as debit, sum(cr) as credit, rem
              from(select head.docno,head.dateid,detail.checkno,coa.acno,coa.acnoname,
                          detail.db,detail.cr,detail.rem
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject
                  left join client as hclient on hclient.client=head.client
                  where head.doc='gj' and head.dateid between '$start' and '$end' $filter ) as t 
                  group by docno, t.dateid, rem order by docno";
    return $query;
  }

  public function maxipro_QUERY_SUMMARIZED_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];

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

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    if ($client != "") {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select docno, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, 
                    sum(db) as debit, sum(cr) as credit, rem
              from(select head.docno,head.dateid,detail.checkno,coa.acno,coa.acnoname,
                        detail.db,detail.cr,detail.rem
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join subproject as sp on sp.projectid = head.projectid and sp.line=detail.subproject
                  left join client as hclient on hclient.clientid=head.clientid
                  where head.doc='gj' and head.dateid between '$start' and '$end' $filter) as t 
                  group by docno, t.dateid, rem order by docno";
    return $query;
  }

  public function detailed_header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    if ($config['params']['companyid'] == 8) { //maxipro
      $projectname = $config['params']['dataparams']['projectname'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];

      if ($projectname == '') {
        $projectname = 'ALL';
      }
      if ($subprojectname == '') {
        $subprojectname = 'ALL';
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
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('General Journal Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    if ($config['params']['companyid'] == 8) { //maxipro
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project: ' . $projectname, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub Project: ' . $subprojectname, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->detailed_header_DEFAULT($config);

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '210', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('Total:', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->detailed_header_DEFAULT($config);
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '500', null, false, $border, '', '', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '500', null, false, $border, '', '', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Customer/Supplier: ' . '</b>' . $data->hclientname, '500', null, false, $border, '', '', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '210', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Customer/Supplier', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->postdate, '80', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '80', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '210', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dclient, '90', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->db <> 0 ? number_format($data->db, 2) : '-', '100', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->cr <> 0 ? number_format($data->cr, 2) : '-', '100', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '140', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();


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
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '210', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('Total: ', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->col('', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '',  '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col('', '210', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col('Grand Total: ', '90', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col('', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 33;
    $page = 33;
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
        $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($checkno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '340', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
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
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '150', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '400', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total:', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, 'B', '',  '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '340', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_header_table($config, $layoutsize)
  {
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '340', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function summarized_header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    if ($config['params']['companyid'] == 8) { //maxipro
      $projectname = $config['params']['dataparams']['projectname'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];
      $client = $config['params']['dataparams']['client'];
      $clientname = $config['params']['dataparams']['clientname'];


      if ($projectname == '') {
        $projectname = 'ALL';
      }
      if ($subprojectname == '') {
        $subprojectname = 'ALL';
      }
      if ($client == '') {
        $client = 'ALL';
      } else {
        $client = $client . ' ~ ' . $clientname;
      }
    }

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
    $str .= $this->reporter->col('General Journal Report Summarized', 1000, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border,  '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($config['params']['companyid'] == 8) { //maxipro


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project: ' . $projectname, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub Project: ' . $subprojectname, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Customer: ' . $client, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    return $str;
  }
}//end class