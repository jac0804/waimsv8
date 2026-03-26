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

class cash_check_voucher
{
  public $modulename = 'Cash Check Voucher Report';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    if ($companyid == 8) { //maxipro
      array_push($fields, 'project', 'subprojectname', 'radioposttype');
      $col1 = $this->fieldClass->create($fields);
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
    data_set($col1, 'dclientname.lookupclass', 'wasupplier');

    $fields = ['radioreporttype', 'radiosorting'];
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
                        '' as userid,'' as username,'' as approved,'0' as reporttype,'ASC' as sorting,
                        '' as dclientname,'' as clientname,'' as client,'0' as clientid,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as reportusers,'0' as subproject,'' as subprojectname,'' as project,
                        '' as projectcode,'0' as projectid,'' as projectname,'0' as posttype";
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
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');
    $companyid = $config['params']['companyid'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        switch ($companyid) {
          case 8: //maxipro
            $result = $this->reportMaxiproLayout_SUMMARIZED($config);
            break;
          case 15: //NATHINA
          case 17: //UNIHOME
          case 39: //CBBSI
            $result = $this->reportDefaultLayout_others_SUMMARIZED($config);
            break;
          case 28: //xcomp
            $result = $this->reportDefaultLayout_xcomp_SUMMARIZED($config);
            break;
          case 55: //AFLI
            $result = $this->reportDefaultLayout_afli_SUMMARIZED($config);
            break;
          default:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
        }

        break;
      case '1': // DETAILED
        switch ($companyid) {
          case 16: //ati
            $result = $this->reportDefaultLayout_ATI_DETAILED($config);
            break;
          case 15: //nathina
          case 17: //unihome
          case 39: //cbbsi
            $result = $this->reportotherCVLayout_DETAILED($config);
            break;
          case 8: //maxipro
            $result = $this->reportDefaultLayout_maxipro_DETAILED($config);
            break;
          default:
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', -1);
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

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
                $query = $this->maxipro_QUERY_SUMMARIZED($config);
                break;
              case '1': // DETAILED
                $query = $this->maxipro_QUERY_DETAILED($config);
                break;
            }
            break;
        }

        break;
      case 15: //nathina
      case 17: //unihome
      case 39: //cbbsi
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->others_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->others_QUERY_DETAILED($config);
            break;
        }
        break;
      case 16: //ati
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->def_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->ati_QUERY_DETAILED($config);
            break;
        }

        break;

      case 28: //xcomp
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->xcomp_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->def_QUERY_DETAILED($config);
            break;
        }

        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->def_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->def_QUERY_DETAILED($config);
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  //default -dati
  public function default_QUERY_DETAILED($config)
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
    $sorting    = $config['params']['dataparams']['sorting'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $addfields = "";
    $leftjoin = "";
    $addfields_u = "";
    $addfields_p = "";

    switch ($config['params']['companyid']) {
      case 8: //maxipro
        $projectid = $config['params']['dataparams']['projectid'];
        $projectname = $config['params']['dataparams']['projectname'];
        $subprojectname = $config['params']['dataparams']['subprojectname'];
        $subprojectid = $config['params']['dataparams']['subproject'];
        if ($projectid != "") {
          $projectid = $config['params']['dataparams']['projectid'];
          $filter .= " and head.projectid = '" . $projectid . "' ";
        }

        if ($subprojectname != "") {
          $filter .= " and sp.line  = $subprojectid";
        }


        $addfields = ", (case when head.paymode = 'O' then head.hacno else '' end ) as hacno,
                        (case when head.paymode = 'O' then head.hacnoname else '' end ) as hacnoname,
                        proj.code as projcode, proj.name as projname";
        $leftjoin = "left join projectmasterfile as proj on proj.line=head.projectid
                     left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject";
        break;
      case 15: //nathina
      case 17: //unihome
      case 39: //CBBSI
        $addfields = " , head.yourref,proj.name as projname,proj.code as projcode, head.rem as headrem, head.vattype,head.ourref,head.ewtrate,dproj.name as dprojname ";
        $leftjoin = " left join projectmasterfile as proj on proj.line=head.projectid left join projectmasterfile as dproj on dproj.line=detail.projectid ";
        break;

      case 16: //ati
        $addfields_u = ", (select group_concat(distinct prh.docno separator ', ') from cvitems as cv 
        left join hpostock as pos on pos.trno=cv.refx and pos.line=cv.linex 
        left join hprhead as prh on prh.trno=pos.reqtrno where cv.trno=detail.trno and cv.line=detail.line) as prdocno,

        (select group_concat(distinct hpr.docno separator ', ') from ladetail as d 
        left join glstock as rrs on rrs.trno=d.refx 
        left join hprhead as hpr on hpr.trno=rrs.reqtrno 
        where d.trno=detail.trno and d.line=detail.line and d.refx<>0) as rrprdocno,

        (select group_concat(distinct poh.docno separator ', ') from cvitems as cv 
        left join hpohead as poh on poh.trno=cv.refx where cv.trno=detail.trno and cv.line=detail.line) as podocno";

        $addfields_p = ", (select group_concat(distinct prh.docno separator ', ') from hcvitems as cv 
        left join hpostock as pos on pos.trno=cv.refx and pos.line=cv.linex 
        left join hprhead as prh on prh.trno=pos.reqtrno where cv.trno=head.trno and cv.line=detail.line) as prdocno,

        (select group_concat(distinct hpr.docno separator ', ') from gldetail as d 
        left join glstock as rrs on rrs.trno=d.refx 
        left join hprhead as hpr on hpr.trno=rrs.reqtrno where d.trno=detail.trno and d.line=detail.line and d.refx<>0) as rrprdocno,

        (select group_concat(distinct poh.docno separator ', ') from hcvitems as cv 
        left join hpohead as poh on poh.trno=cv.refx where cv.trno=detail.trno and cv.line=detail.line) as podocno";
        break;
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref $addfields  $addfields_u
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno $leftjoin
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref $addfields  $addfields_p
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno $leftjoin
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              order by docno $sorting,cr ";


    return $query;
  }

  public function maxipro_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];

    $filter = "";
    $leftjoin = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $leftjoin .= "left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject";
      $filter .= " and sp.line  = $subprojectid";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, (case when head.paymode = 'O' then head.hacno else '' end ) as hacno,
                        (case when head.paymode = 'O' then head.hacnoname else '' end ) as hacnoname,
                        proj.code as projcode, proj.name as projname 
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              left join projectmasterfile as proj on proj.line=head.projectid
              $leftjoin
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, (case when head.paymode = 'O' then head.hacno else '' end ) as hacno,
                        (case when head.paymode = 'O' then head.hacnoname else '' end ) as hacnoname,
                        proj.code as projcode, proj.name as projname 
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              left join projectmasterfile as proj on proj.line=head.projectid
              $leftjoin
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              order by docno $sorting,cr ";
    return $query;
  }

  public function maxipro_QUERY_DETAILED_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];

    $filter = "";
    $leftjoin = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $leftjoin .= "left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject";
      $filter .= " and sp.line  = $subprojectid";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, (case when head.paymode = 'O' then head.hacno else '' end ) as hacno,
                        (case when head.paymode = 'O' then head.hacnoname else '' end ) as hacnoname,
                        proj.code as projcode, proj.name as projname 
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              left join projectmasterfile as proj on proj.line=head.projectid
              $leftjoin
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter  ";
    return $query;
  }

  public function maxipro_QUERY_DETAILED_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $subprojectid = $config['params']['dataparams']['subproject'];

    $filter = "";
    $leftjoin = "";
    if ($prefix != "") {
      $filter .= " and cntnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and cntnum.center = '$fcenter'";
    }
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $leftjoin .= "left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject";
      $filter .= " and sp.line  = $subprojectid";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, (case when head.paymode = 'O' then head.hacno else '' end ) as hacno,
                        (case when head.paymode = 'O' then head.hacnoname else '' end ) as hacnoname,
                        proj.code as projcode, proj.name as projname 
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              left join projectmasterfile as proj on proj.line=head.projectid
              $leftjoin
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              order by docno $sorting,cr ";
    return $query;
  }

  public function others_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, head.yourref,proj.name as projname,
                     proj.code as projcode, head.rem as headrem, 
                     head.vattype,head.ourref,head.ewtrate,dproj.name as dprojname  
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join projectmasterfile as proj on proj.line=head.projectid 
              left join projectmasterfile as dproj on dproj.line=detail.projectid 
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, head.yourref,proj.name as projname,
                     proj.code as projcode, head.rem as headrem,
                      head.vattype,head.ourref,head.ewtrate,dproj.name as dprojname  
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              left join projectmasterfile as proj on proj.line=head.projectid 
              left join projectmasterfile as dproj on dproj.line=detail.projectid 
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              order by docno $sorting,cr ";
    return $query;
  }


  public function ati_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, (select group_concat(distinct prh.docno separator ', ') from cvitems as cv 
                    left join hpostock as pos on pos.trno=cv.refx and pos.line=cv.linex 
                    left join hprhead as prh on prh.trno=pos.reqtrno where cv.trno=detail.trno and cv.line=detail.line) as prdocno,

                    (select group_concat(distinct hpr.docno separator ', ') from ladetail as d 
                    left join glstock as rrs on rrs.trno=d.refx 
                    left join hprhead as hpr on hpr.trno=rrs.reqtrno 
                    where d.trno=detail.trno and d.line=detail.line and d.refx<>0) as rrprdocno,

                    (select group_concat(distinct poh.docno separator ', ') from cvitems as cv 
                    left join hpohead as poh on poh.trno=cv.refx where cv.trno=detail.trno and cv.line=detail.line) as podocno
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref, (select group_concat(distinct prh.docno separator ', ') from hcvitems as cv 
                    left join hpostock as pos on pos.trno=cv.refx and pos.line=cv.linex 
                    left join hprhead as prh on prh.trno=pos.reqtrno where cv.trno=head.trno and cv.line=detail.line) as prdocno,

                    (select group_concat(distinct hpr.docno separator ', ') from gldetail as d 
                    left join glstock as rrs on rrs.trno=d.refx 
                    left join hprhead as hpr on hpr.trno=rrs.reqtrno where d.trno=detail.trno and d.line=detail.line and d.refx<>0) as rrprdocno,

                    (select group_concat(distinct poh.docno separator ', ') from hcvitems as cv 
                    left join hpohead as poh on poh.trno=cv.refx where cv.trno=detail.trno and cv.line=detail.line) as podocno
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              order by docno $sorting,cr ";
    return $query;
  }

  public function def_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref   
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.createby,head.docno,hclient.client as hclient,head.clientname as hclientname,
                     date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,
                     detail.checkno,coa.acno,coa.acnoname,concat(left(dclient.client,2),
                     right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                     detail.db,detail.cr,detail.rem,detail.ref   
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno 
              left join client as hclient on hclient.clientid=head.clientid
              left join client as dclient on dclient.clientid=detail.clientid 
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc='cv' and head.dateid between '$start' and '$end' $filter 
              order by docno $sorting,cr ";
    return $query;
  }

  //default -dati
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
    $sorting    = $config['params']['dataparams']['sorting'];
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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }
    $addgrpfields = "";
    $addfields = "";
    $leftjoin = "";
    $addgrp = "";

    if ($companyid == 8) { //maxipro
      $projectid = $config['params']['dataparams']['projectid'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];
      $subprojectid = $config['params']['dataparams']['subproject'];

      if ($projectid != "") {
        $filter .= " and head.projectid = '" . $projectid . "' ";
      }

      if ($subprojectname != "") {
        $filter .= " and sp.line  = $subprojectid";
      }

      $leftjoin = "left join projectmasterfile as proj on proj.line=head.projectid
                     left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject";
    }

    switch ($companyid) {
      case 15: //nathinna
      case 17: //unihome
      case 39: //CBBSI
        $addgrpfields = " , hclientname,yourref,ourref,projname,vattype,ewtrate ";
        $addfields = " , head.yourref,head.ourref,proj.name as projname,head.vattype,head.ewtrate ";
        $leftjoin = " left join projectmasterfile as proj on proj.line=head.projectid ";
        $addgrp = " ,hclientname,yourref,ourref,projname,vattype,ewtrate ";
        break;
      case 28: //xcomp
      case 8: //maxipro
        $addgrpfields = " ,t.hclient, t.hclientname ";
        $addgrp = " ,hclient,hclientname";
        break;
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, 
                     sum(cr) as credit, rem $addgrpfields
              from(select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                          head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                          concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                          detail.db,detail.cr,head.rem,detail.ref $addfields
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno $leftjoin 
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter 
                  union all
                  select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                  head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                  concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                  detail.db,detail.cr,head.rem,detail.ref $addfields
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.clientid=head.clientid
                  left join client as dclient on dclient.clientid=detail.clientid 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno $leftjoin 
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter ) as t 
              group by docno, createby, dateid, rem $addgrp
              order by docno $sorting";
    return $query;
  }


  public function maxipro_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, 
                     sum(cr) as credit, rem,t.hclient, t.hclientname
              from(select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                          head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                          concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                          detail.db,detail.cr,head.rem,detail.ref
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join projectmasterfile as proj on proj.line=head.projectid
                  left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject 
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter 
                  union all
                  select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                  head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                  concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                  detail.db,detail.cr,head.rem,detail.ref 
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.clientid=head.clientid
                  left join client as dclient on dclient.clientid=detail.clientid 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join projectmasterfile as proj on proj.line=head.projectid
                  left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject 
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter ) as t 
              group by docno, createby, dateid, rem,hclient,hclientname
              order by docno $sorting";
    return $query;
  }

  public function maxipro_QUERY_SUMMARIZED_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, 
                     sum(cr) as credit, rem,t.hclient, t.hclientname
              from(select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                          head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                          concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                          detail.db,detail.cr,head.rem,detail.ref
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join projectmasterfile as proj on proj.line=head.projectid
                  left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject 
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter ) as t 
              group by docno, createby, dateid, rem,hclient,hclientname
              order by docno $sorting";
    return $query;
  }

  public function maxipro_QUERY_SUMMARIZED_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }

    if ($subprojectname != "") {
      $filter .= " and sp.line  = $subprojectid";
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, 
                     sum(cr) as credit, rem,t.hclient, t.hclientname
              from(select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                  head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                  concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                  detail.db,detail.cr,head.rem,detail.ref 
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.clientid=head.clientid
                  left join client as dclient on dclient.clientid=detail.clientid 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join projectmasterfile as proj on proj.line=head.projectid
                  left join subproject as sp on sp.projectid = proj.line and sp.line=detail.subproject 
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter ) as t 
              group by docno, createby, dateid, rem,hclient,hclientname
              order by docno $sorting";
    return $query;
  }


  public function others_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, 
                     sum(cr) as credit, rem, hclientname,yourref,ourref,projname,vattype,ewtrate
              from(select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                          head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                          concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                          detail.db,detail.cr,head.rem,detail.ref, head.yourref,head.ourref,proj.name as projname,head.vattype,head.ewtrate 
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join projectmasterfile as proj on proj.line=head.projectid
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter 
                  union all
                  select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                  head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                  concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                  detail.db,detail.cr,head.rem,detail.ref, head.yourref,head.ourref,proj.name as projname,head.vattype,head.ewtrate 
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.clientid=head.clientid
                  left join client as dclient on dclient.clientid=detail.clientid 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno
                  left join projectmasterfile as proj on proj.line=head.projectid
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter ) as t 
              group by docno, createby, dateid, rem, hclientname,yourref,ourref,projname,vattype,ewtrate
              order by docno $sorting";
    return $query;
  }

  public function xcomp_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, 
                     sum(cr) as credit, rem,t.hclient, t.hclientname
              from(select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                          head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                          concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                          detail.db,detail.cr,head.rem,detail.ref 
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno  
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter 
                  union all
                  select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                  head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                  concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                  detail.db,detail.cr,head.rem,detail.ref 
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.clientid=head.clientid
                  left join client as dclient on dclient.clientid=detail.clientid 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno  
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter ) as t 
              group by docno, createby, dateid, rem,hclient,hclientname
              order by docno $sorting";
    return $query;
  }


  public function def_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = $config['params']['dataparams']['sorting'];
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
    if ($client != '') {
      $filter .= " and hclient.clientid = '$clientid' ";
    }

    $query = "select docno, createby, date(dateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, 
                     sum(cr) as credit, rem, hclientname, amt
              from(select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                          head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                          concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                          detail.db,detail.cr,head.rem,detail.ref, head.amount as amt
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno  
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter 
                  union all
                  select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
                  head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
                  concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
                  detail.db,detail.cr,head.rem,detail.ref, head.amount as amt
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno 
                  left join client as hclient on hclient.clientid=head.clientid
                  left join client as dclient on dclient.clientid=detail.clientid 
                  left join coa on coa.acnoid=detail.acnoid
                  left join cntnum on cntnum.trno=head.trno  
                  where head.doc='cv' and date(head.dateid) between '$start' and '$end' $filter ) as t 
              group by docno, createby, dateid, rem , hclientname, amt
              order by docno $sorting";
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
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername     = $config['params']['dataparams']['centername'];

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Cash Check Voucher Report Detailed', 1000, null, false, $border, '', 'L', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center: ' . $c, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';


    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];


    $count = 11;
    $page = 12;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 10;
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header_detailed($config);

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

          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '90', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 1000, null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= '<br/>';
        }


        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Supplier: ' . '</b>' . $data->hclientname, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();



          $str .= '</br>';

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();


          $str .= $this->reporter->col('Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Customer/Supplier', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->postdate, '80', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '120', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '80', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '200', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->hclientname, '140', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '90', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '90', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '90', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '110', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        // $str .= $this->reporter->endtable();

        if ($docno == $data->docno) {
          $debit += $data->db;
          $credit += $data->cr;
          $totaldb += $data->db;
          $totalcr += $data->cr;
        }
        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '90', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');



          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= '</br>';

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 1000, null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();

          $str .= $this->default_header_detailed($config);
          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total: ', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '90', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '90', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '110', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function otherCV_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername     = $config['params']['dataparams']['centername'];

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Cash Check Voucher Report Detailed', 1000, null, false, $border, '', 'L', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center: ' . $c, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }

  public function reportotherCVLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];


    $count = 11;
    $page = 12;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 10;
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->otherCV_header_detailed($config);

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
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2) . '&nbsp&nbsp', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 1100, null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Yourref: ' . '</b>' . $data->yourref, '200', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('<b>' . 'Project: ' . '</b>' . $data->projcode, '200', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('<b>' . 'Notes: ' . '</b>' . $data->headrem, '200', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Supplier: ' . '</b>' . $data->hclientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Vat Type: ' . '</b>' . $data->vattype, '200', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('<b>' . 'Ourref: ' . '</b>' . $data->ourref, '200', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('<b>' . 'EWT Rate: ' . '</b>' . $data->ewtrate, '200', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= '</br>';

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit' . '&nbsp&nbsp', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Project', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->postdate, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '100', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2) . '&nbsp&nbsp', '100', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dprojname, '200', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $debit += $data->db;
          $credit += $data->cr;
          $totaldb += $data->db;
          $totalcr += $data->cr;
        }

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2) . '&nbsp&nbsp', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= '</br>';

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 1000, null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->otherCV_header_detailed($config);
          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2) . '&nbsp&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function reportDefaultLayout_ATI_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 13;
    $page = 12;
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
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Supplier: ' . '</b>' . $data->hclientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Customer/Supplier', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('PR #', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->postdate, '80', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '80', null, false, '10px solid ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '80', null, false, '10px solid ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '100', null, false, '10px solid ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dclientname, '100', null, false, '10px solid ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '80', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '80', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, '10px solid ', '', 'L', $font, $fontsize, '', '', '');

        $ref = $data->ref;
        if ($data->podocno != '') {
          if ($ref != '') {
            $ref .= (", " . $data->podocno);
          } else {
            $ref = $data->podocno;
          }
        }

        $str .= $this->reporter->col($ref, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');

        $prref = '';
        if ($data->prdocno != '') {
          $prref = $data->prdocno;
        }
        if ($data->rrprdocno != '') {
          if ($prref != "") {
            $prref .= (", " . $data->rrprdocno);
          } else {
            $prref .= $data->rrprdocno;
          }
        }

        $str .= $this->reporter->col($prref, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
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
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
        if ($this->reporter->linecounter == $page) {

          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->default_header_detailed($config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 14;
    $page = 16;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1000;

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
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($checkno, '130', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '420', null, false, $border, '', 'LT', $font, $fontsize, 'R', '', '');

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

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '130', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '110', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '110', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '440', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_header_table($config, $layoutsize)
  {
    $str = "";
    $border = "1px solid";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '440', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
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
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername = $config['params']['dataparams']['centername'];

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

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Cash Check Voucher Report Summarized', 1000, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, 200, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, 300, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center: ' . $c, 1000, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '</br>';

    return $str;
  }

  public function reportDefaultLayout_others_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 14;
    $page = 16;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1400;

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
    $str .= $this->summarized_others_header_table($config, $layoutsize);


    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($data->hclientname, '160', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->yourref, '90', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->ourref, '80', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->projname, '130', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->vattype, '80', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->ewtrate, '70', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp' . $checkno, '80', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2) . '&nbsp&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '130', null, false, $border, '', 'L', $font, $fontsize, 'L', '', '');
        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
          $str .= $this->summarized_others_header_table($config, $layoutsize);
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
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '800', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Grand Total:', '870', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function summarized_others_header_table($config, $layoutsize)
  {
    $str = "";
    $border = "1px solid";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payee Name', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Yourref', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ourref', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Type', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('EWT Rate', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount' . '&nbsp&nbsp', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout_xcomp_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);

    $count = 14;
    $page = 16;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
    $str .= $this->summarized_xcomp_header_table($config, $layoutsize);


    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->hclient, '100', null, false, $border, '', 'LT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->hclientname, '100', null, false, $border, '', 'LT', $font, $fontsize, 'R', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($checkno, '100', null, false, $border, '', 'C', $font, $fontsize, 'RT', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'L', $font, $fontsize, 'RT', '', '');

        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
          $str .= $this->summarized_xcomp_header_table($config, $layoutsize);
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


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '130', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '110', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '110', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '440', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_xcomp_header_table($config, $layoutsize)
  {
    $str = "";
    $border = "1px solid";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payee Code', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Name', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  //FOR MAXIPRO
  public function reportMaxiproLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 36;
    $page = 35;
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
    $str .= $this->summarized_header_MAXIPRO($config, $layoutsize);
    $str .= $this->summarized_MAXIPRO_header_table($config, $layoutsize);

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
        $str .= $this->reporter->col($data->docno, '140', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->hclientname, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($checkno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2) . ' ', '130', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2) . ' ', '130', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
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
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '200', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '400', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total:', '350', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '130', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '130', null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_MAXIPRO_header_table($config, $layoutsize)
  {
    $str = "";
    $companyid = $config['params']['companyid'];
    $border = "1px solid";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payee`s Name', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '130', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '130', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '340', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }


  public function summarized_header_MAXIPRO($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername = $config['params']['dataparams']['centername'];

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
    $str .= $this->reporter->col('Cash Check Voucher Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->col('Center: ' . $c, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    return $str;
  }


  public function reportDefaultLayout_maxipro_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];


    $count = 11;
    $page = 12;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = 1000;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 10;
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header_detailed($config);

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

          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '90', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');


          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 1000, null, false, '1px dotted', 'B', 'R', $font, $fontsize, 'B', '', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= '<br/>';
        }

        // $str .= '</br></br>';

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('<b>' . 'Supplier: ' . '</b>' . $data->hclientname, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          if ($data->hacno <> '') {
            $str .= $this->reporter->col('<b>' . 'Account: ' . '</b>' . $data->hacno . ' ~ ' . $data->hacnoname, '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          }

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Project: ' . '</b>' . $data->projcode . ' ~ ' . $data->projname, '1000', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $str .= '</br>';

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();


          $str .= $this->reporter->col('Date', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Customer/Supplier', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->postdate, '80', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '120', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '80', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '200', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->hclientname, '140', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '90', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '90', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '90', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '110', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        // $str .= $this->reporter->endtable();

        if ($docno == $data->docno) {
          $debit += $data->db;
          $credit += $data->cr;
          $totaldb += $data->db;
          $totalcr += $data->cr;
        }
        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total: ', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '90', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');



          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= '</br>';

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 1000, null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;


        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();

          $str .= $this->default_header_detailed($config);
          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Grand Total: ', '140', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '90', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '90', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '110', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }




  public function summarized_AFLI_header_table($config, $layoutsize)
  {
    $str = "";
    $companyid = $config['params']['companyid'];
    $border = "1px solid";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payee', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }


  public function summarized_header_AFLI($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername = $config['params']['dataparams']['centername'];

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
    $str .= $this->reporter->col('Cash Check Voucher Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->col('Center: ' . $c, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    return $str;
  }
  public function reportDefaultLayout_afli_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 36;
    $page = 35;
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
    $str .= $this->summarized_header_AFLI($config, $layoutsize);
    $str .= $this->summarized_AFLI_header_table($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;
    $totalamt = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        $totalamt += $data->amt;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->hclientname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($checkno, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2) . ' ', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2) . ' ', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
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
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '200', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '400', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '150', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class