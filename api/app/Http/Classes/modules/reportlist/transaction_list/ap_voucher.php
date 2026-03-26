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

class ap_voucher
{
  public $modulename = 'AP Voucher Report';
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
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      case 19: //housegem
        array_push($fields, 'dclientname', 'radiosorting');
        $col1 = $this->fieldClass->create($fields);
        break;
      case 15: //nathina
        array_push($fields, 'apvfrom', 'apvto', 'radiogatherby');
        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as userid,
      '' as username,
      '' as approved,
      '0' as clientid,
      '' as dclientname,
      '' as client,
      '' as clientname,
      '0' as reporttype,
      '' as reportusers,
      
      '" . $defaultcenter[0]['center'] . "' as center,
      '" . $defaultcenter[0]['centername'] . "' as centername,
      '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
      '0' as deptid,
       '' as ddeptname, '' as dept, '' as deptname,
       '0' as gatherby, '' as apvfrom, '' as apvto,
       'ASC' as sorting ";
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
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 15: //nathina
      case 17: //unihome
      case 39: //cbbsi
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $result = $this->reportDefaultLayout_other_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $result = $this->reportDefaultLayout_other_DETAILED($config);
            break;
        }
        break;

      default:
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
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 10:
      case 12: //afti
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->default_afti_QUERY_SUMMARIZED($config);
            break;
          case '1': // DETAILED
            $query = $this->default_afti_QUERY_DETAILED($config);
            break;
        }
        break;
      case 15: //nathina
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $query = $this->default_nathina_QUERY_SUMMARIZED($config); 
            break;
          case '1': // DETAILED
            $query = $this->default_nathina_QUERY_DETAILED($config); 
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
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = isset($config['params']['dataparams']['sorting']) ? $config['params']['dataparams']['sorting'] : '';
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

 

    $query = "select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,
        detail.cr,detail.rem,detail.ref,head.yourref,
         head.ourref,project.name,head.ewtrate, hclient.tin
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno left join client as hclient on hclient.client=head.client
        left join client as dclient on dclient.client=detail.client
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
        where head.doc='pv'  and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        union all
        select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
        detail.db,detail.cr,detail.rem,detail.ref,head.yourref,
         head.ourref,project.name,head.ewtrate, hclient.tin
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno left join client as hclient on hclient.clientid=head.clientid
        left join client as dclient on dclient.clientid=detail.clientid left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
        where head.doc='pv'  and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        order by docno,cr";

    return $query;
  }

  public function default_afti_QUERY_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = isset($config['params']['dataparams']['sorting']) ? $config['params']['dataparams']['sorting'] : '';
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $deptid = $config['params']['dataparams']['ddeptname'];
    $dept = $config['params']['dataparams']['deptid'];

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

    if ($deptid != "") {
      $filter .= " and head.deptid = $dept";
    }


    $query = "select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,
        detail.cr,detail.rem,detail.ref,head.yourref,
         head.ourref,project.name,head.ewtrate, hclient.tin
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno 
        left join client as hclient on hclient.client=head.client
        left join client as dclient on dclient.client=detail.client
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
        where head.doc='pv' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        union all
        select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
        detail.db,detail.cr,detail.rem,detail.ref,head.yourref,
         head.ourref,project.name,head.ewtrate, hclient.tin
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno 
        left join client as hclient on hclient.clientid=head.clientid
        left join client as dclient on dclient.clientid=detail.clientid 
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
        where head.doc='pv' and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        order by docno,cr";

    return $query;
  }

  public function default_nathina_QUERY_DETAILED($config)
  {
 
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = isset($config['params']['dataparams']['sorting']) ? $config['params']['dataparams']['sorting'] : '';
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
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


    $gatherby = $config['params']['dataparams']['gatherby'];
    $apvfrom = $config['params']['dataparams']['apvfrom'];
    $apvto = $config['params']['dataparams']['apvto'];

    $apvfrom = $apvfrom == '' ? 0 : $apvfrom;
    $apvto = $apvto == '' ? 0 : $apvto;
    if ($gatherby == 0) {
      $filter2 = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    } else {
      $filter2 = " and cntnum.seq between '" . $apvfrom . "' and '" . $apvto . "' ";
    }
    

    $query = "select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,
        detail.cr,detail.rem,detail.ref,head.yourref,
         head.ourref,project.name,head.ewtrate, hclient.tin
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno 
        left join client as hclient on hclient.client=head.client
        left join client as dclient on dclient.client=detail.client
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
        where head.doc='pv' " . $filter . "  " . $filter2 . "
        union all
        select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
        detail.db,detail.cr,detail.rem,detail.ref,head.yourref,
         head.ourref,project.name,head.ewtrate, hclient.tin
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno 
        left join client as hclient on hclient.clientid=head.clientid
        left join client as dclient on dclient.clientid=detail.clientid 
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
        where head.doc='pv' " . $filter . "  " . $filter2 . "
        order by docno,cr";

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = isset($config['params']['dataparams']['sorting']) ? $config['params']['dataparams']['sorting'] : '';
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
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

    if ($companyid == 15) { //nathina
      $gatherby = $config['params']['dataparams']['gatherby'];
      $apvfrom = $config['params']['dataparams']['apvfrom'];
      $apvto = $config['params']['dataparams']['apvto'];
      $apvfrom = $apvfrom == '' ? 0 : $apvfrom;
      $apvto = $apvto == '' ? 0 : $apvto;
      if ($gatherby == 0) {
        $filter2 = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
      } else {
        $filter2 = " and cntnum.seq between '" . $apvfrom . "' and '" . $apvto . "' ";
      }
    } else {
      $filter2 = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    }

    $query = "select docno, createby, date(dateid) as dateid, postdate, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, 
      sum(db) as debit, sum(cr) as credit, rem,hclient,hclientname,yourref,ourref,name,ewtrate,tin
      from(
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
       head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
       concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
      detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref,project.name,head.ewtrate, hclient.tin
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
      where head.doc='pv' " . $filter . " " . $filter1 . " " . $filter2 . "
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
      head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
      detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref,project.name,head.ewtrate, hclient.tin
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.clientid=head.clientid
      left join client as dclient on dclient.clientid=detail.clientid 
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
      where head.doc='pv' " . $filter . " " . $filter1 . " " . $filter2 . ") as t 
      group by docno, createby, dateid, postdate, rem,hclient,hclientname,yourref,ourref,name,ewtrate,tin
      order by dateid,docno $sorting";

    return $query;
  }

  public function default_afti_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = isset($config['params']['dataparams']['sorting']) ? $config['params']['dataparams']['sorting'] : '';
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];
    $deptid = $config['params']['dataparams']['ddeptname'];
    $dept = $config['params']['dataparams']['deptid'];

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

    if ($deptid != "") {
      $filter .= " and head.deptid = $dept";
    }


    $query = "select docno, createby, date(dateid) as dateid, postdate, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, 
      sum(db) as debit, sum(cr) as credit, rem,hclient,hclientname,yourref,ourref,name,ewtrate,tin
      from(
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
       head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
       concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
      detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref,project.name,head.ewtrate, hclient.tin
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
      where head.doc='pv' and date(head.dateid) between '" . $start . "' and '" . $end . "'   " . $filter . " 
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
      head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
      detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref,project.name,head.ewtrate, hclient.tin
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.clientid=head.clientid
      left join client as dclient on dclient.clientid=detail.clientid 
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
      left join projectmasterfile as project on project.line = head.projectid
      where head.doc='pv' and date(head.dateid) between '" . $start . "' and '" . $end . "'  " . $filter . "  ) as t 
      group by docno, createby, dateid, postdate, rem,hclient,hclientname,yourref,ourref,name,ewtrate,tin
      order by dateid,docno $sorting";

    return $query;
  }


  public function default_nathina_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];
    $sorting    = isset($config['params']['dataparams']['sorting']) ? $config['params']['dataparams']['sorting'] : '';
    $client    = $config['params']['dataparams']['client'];
    $clientid    = $config['params']['dataparams']['clientid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
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

  
    $gatherby = $config['params']['dataparams']['gatherby'];
    $apvfrom = $config['params']['dataparams']['apvfrom'];
    $apvto = $config['params']['dataparams']['apvto'];
    $apvfrom = $apvfrom == '' ? 0 : $apvfrom;
    $apvto = $apvto == '' ? 0 : $apvto;

    if ($gatherby == 0) {
      $filter2 = " and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    } else {
      $filter2 = " and cntnum.seq between '" . $apvfrom . "' and '" . $apvto . "' ";
    }
   

    $query = "select docno, createby, date(dateid) as dateid, postdate, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, 
      sum(db) as debit, sum(cr) as credit, rem,hclient,hclientname,yourref,ourref,name,ewtrate,tin
      from(
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
       head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
       concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
      detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref,project.name,head.ewtrate, hclient.tin
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
      where head.doc='pv' " . $filter . "  " . $filter2 . "
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,
      head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,detail.checkno,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,
      detail.db,detail.cr,head.rem,detail.ref,head.yourref,head.ourref,project.name,head.ewtrate, hclient.tin
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.clientid=head.clientid
      left join client as dclient on dclient.clientid=detail.clientid 
      left join coa on coa.acnoid=detail.acnoid
      left join cntnum on cntnum.trno=head.trno
        left join projectmasterfile as project on project.line = head.projectid
      where head.doc='pv' " . $filter . "  " . $filter2 . ") as t 
      group by docno, createby, dateid, postdate, rem,hclient,hclientname,yourref,ourref,name,ewtrate,tin
      order by dateid,docno $sorting";

    return $query;
  }

  public function default_headers_detailed($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10:
      case 12: //afti
        return $this->default_afti_header_detailed($config);
        break;
      case 15: //nathina
        return $this->default_nathina_header_detailed($config);
        break;
      default:
        return $this->default_header_detailed($config);
        break;
    }
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
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
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

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AP Voucher Report Detailed', 1000, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . $c, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    return $str;
  }


  public function default_afti_header_detailed($config)
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
    $dept   = $config['params']['dataparams']['ddeptname'];

    if ($dept != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
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

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AP Voucher Report Detailed', 1000, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department: ' . $deptname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . $c, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function default_nathina_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername     = $config['params']['dataparams']['centername'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
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

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AP Voucher Report Detailed', 1000, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($config['params']['dataparams']['gatherby'] == 0) {
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('From APV No: ' . $config['params']['dataparams']['apvfrom'] . ' To ' . $config['params']['dataparams']['apvto'], '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . $c, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_headers_detailed($config);


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
          $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total:', '150', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          // }
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
       
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '500', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '500', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->endrow();
 
          $str .= $this->reporter->startrow();
   
          $str .= $this->reporter->col('<b>' . 'Supplier: ' . '</b>' . $data->hclientname, '500', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->endrow();
 
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Customer/Supplier', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

       
        $str .= $this->reporter->col($data->postdate, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '100', null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '120', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dclientname, '150', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '100', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '100', null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
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
          $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ', '150', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
       
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        if ($this->reporter->linecounter == $page) {

          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->default_headers_detailed($config);

          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

   
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Total:', '150', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    // }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

   
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '150', null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
   
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_other_DETAILED($config)
  {
    $result = $this->reportDefault($config);

    $companyid = $config['params']['companyid'];
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "11";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_headers_detailed($config);


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
     
          $str .= $this->reporter->col('', '100', null, false, '10px dotted', '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '150', null, false, '10px dotted', '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Total:', '200', null, false, '10px dotted', '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '150', null, false, '10px dotted', '', 'C', $font, $fontsize, 'B', '', '');
        
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
         
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '200', null, false, $border, '', '', $font, $fontsize, '', '', '2px'); //2px padding?
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '100', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>' . 'Ourref: ' . '</b>' . $data->ourref, '100', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>' . 'Ewtrate: ' . '</b>' . $data->ewtrate, '100', null, false, $border, '', '', $font, $fontsize, '', '', '2px');

        
          $str .= $this->reporter->startrow();
        
          $str .= $this->reporter->col('<b>' . 'Supplier: ' . '</b>' . $data->hclientname, '500', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>' . 'Yourref: ' . '</b>' . $data->yourref, '350', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>' . 'Project: ' . '</b>' . $data->name, '250', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
         
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('<b>' . 'Notes: ' . '</b>' . $data->rem, '100', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
        
          $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
         
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

       
        $str .= $this->reporter->col($data->postdate, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '150', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '200', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '200', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '200', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '150', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
      

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
          $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
         

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        if ($this->reporter->linecounter == $page) {

          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->default_headers_detailed($config);

          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

   
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Total: ', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($debit, 2), '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($credit, 2), '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
    
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

  
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
   
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_headers_DEFAULT($config, $layoutsize);
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
        $str .= $this->reporter->col($data->docno, '130', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($checkno, '100', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2) . '&nbsp&nbsp', '100', null, false, $border, '', 'RT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '270', null, false, $border, '', 'LT', $font, $fontsize, 'R', '', '');

        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_headers_DEFAULT($config, $layoutsize);
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


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '270', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_header_table($config)
  {
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $layoutsize = '800';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit' . '&nbsp&nbsp', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '270', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function summarized_headers_DEFAULT($config, $layoutsize)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10:
      case 12: //afti
        return $this->summarized_afti_header_DEFAULT($config, $layoutsize);
        break;
      case 15: //nathina
        return $this->summarized_nathina_header_DEFAULT($config, $layoutsize);
        break;
      default:
        return $this->summarized_header_DEFAULT($config, $layoutsize);
        break;
    }
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
    $filtercentername     = $config['params']['dataparams']['centername'];

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

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AP Voucher Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . $c, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }


  public function summarized_afti_header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername     = $config['params']['dataparams']['centername'];
    $dept   = $config['params']['dataparams']['ddeptname'];

    if ($dept != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
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

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AP Voucher Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department: ' . $deptname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . $c, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function summarized_nathina_header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $filtercenter     = $config['params']['dataparams']['center'];
    $filtercentername     = $config['params']['dataparams']['centername'];

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

    if ($filtercenter != "") {
      $c = $filtercenter . ' - ' . $filtercentername;
    } else {
      $c = "ALL";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AP Voucher Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($config['params']['dataparams']['gatherby'] == 0) {
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('From APV No: ' . $config['params']['dataparams']['apvfrom'] . ' To: ' . $config['params']['dataparams']['apvto'], '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center: ' . $c, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout_other_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;
    $layoutsize = '1400';

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_headers_DEFAULT($config, $layoutsize);
    $str .= $this->summarized_other_header_table($config, $layoutsize);
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', ''); //100
        $str .= $this->reporter->col($data->hclient, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->hclientname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', ''); //100
        $str .= $this->reporter->col($data->tin, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', ''); //100
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ewtrate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->name, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '250', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_headers_DEFAULT($config, $layoutsize);
          $str .= $this->summarized_other_header_table($config, $layoutsize);
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
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '250', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function summarized_other_header_table($config)
  {
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $layoutsize = '1400'; //1250
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vendor Code', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Name', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Tin', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ourref', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Wtax', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
}//end class