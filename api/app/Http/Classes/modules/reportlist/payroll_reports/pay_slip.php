<?php

namespace App\Http\Classes\modules\reportlist\payroll_reports;

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
use App\Http\Classes\modules\warehousing\forklift;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class pay_slip
{
  public $modulename = 'Pay Slip';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $batch;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '700'];

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

    if (isset($config['params']['todo']['type'])) {
      if ($config['params']['todo']['type'] == 'dashboardprinting') {

        $fields = ['radioprint', 'batchrep'];
        $col1 = $this->fieldClass->create($fields);
        if ($companyid == 58) { //cdo
          data_set($col1, 'batchrep.lookupclass', 'lookupbatchempcdo');
        } else {
          data_set($col1, 'batchrep.lookupclass', 'lookupbatchrep');
        }
        if ($companyid == 53) { //camera
          data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'default', 'color' => 'red']
          ]);
        }
        data_set($col1, 'batchrep.required', true);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
      }
    }

    switch ($companyid) {
      case 58: //cdo
        $fields = ['radioprint', 'dbranchname', 'divrep', 'deptrep', 'batchrep', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.lookupclass', 'lookupdivpayslip');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'batchrep.lookupclass', 'lookupbatchrepcdo');
        data_set($col1, 'batchrep.addedparams', ['branchid', 'divid']);
        data_set($col1, 'batchrep.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupempcdo');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'dclientname.addedparams', ['branchid', 'divid', 'line']);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        break;

      default:
        $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');
        if ($companyid == 53) { //camera
          data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'default', 'color' => 'red']
          ]);
        }

        switch ($companyid) {
          case 45: //pdpi payroll
            $fields = ['batchrep', 'radiooption'];
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'batchrep.lookupclass', 'lookupbatchrep');
            data_set($col2, 'batchrep.required', true);
            data_set($col2, 'radiooption.options', array(
              ['label' => 'With Time In', 'value' => 'timein', 'color' => 'orange'],
              ['label' => 'Manual Entry', 'value' => 'manual', 'color' => 'orange']
            ));
            break;
          case 28: //xcomp
          case 30: //RT
            $fields = ['batchrep', 'radiooption'];
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'batchrep.lookupclass', 'lookupbatchrep');
            data_set($col2, 'batchrep.required', true);
            data_set($col2, 'radiooption.options', array(
              ['label' => 'Default', 'value' => 'default', 'color' => 'orange'],
              ['label' => 'W/out Hrs', 'value' => 'nohrs', 'color' => 'orange']
            ));
            break;
          case 62: //onesky
            $fields = ['batchrep', 'radioreporttype'];
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'batchrep.lookupclass', 'lookupbatchrep');
            data_set($col2, 'batchrep.required', true);
            data_set($col2, 'radioreporttype.options', array(
              ['label' => 'One Sky Logo', 'value' => 'onesky', 'color' => 'red'],
              ['label' => 'NSON Logo', 'value' => 'nson', 'color' => 'red'],
              ['label' => 'W/out Logo', 'value' => 'none', 'color' => 'red']
            ));
            break;

          default:
            $fields = ['batchrep'];
            $col2 = $this->fieldClass->create($fields);
            data_set($col2, 'batchrep.lookupclass', 'lookupbatchrep');
            data_set($col2, 'batchrep.required', true);
            break;
        }
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

    $client = '';
    $printtype = '';

    $divid = '';

    if (isset($config['params']['todo']['type'])) {
      if ($config['params']['todo']['type'] == 'dashboardprinting') {
        $printtype = 'dashboardprinting';
        $client = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$config['params']['adminid']]);
        $divid = $this->coreFunctions->getfieldvalue("employee", "divid", "empid=?", [$config['params']['adminid']]);
      }
    }

    $qry = "select 
    'default' as print,
    '" . $client . "' as client,
    '' as clientid,
    '' as clientname,
    '' as dclientname,
    '" . $divid . "' as divid,
    '' as divname,
    '' as deptid,
    '' as deptname,
    '' as batchid,
    '' as line,
    '' as batch,
    '' as divrep,
    '' as deptrep,
    '' as batchrep,
    '" . $printtype . "' as printtype,
    '' as dbranchname,
    '' as branchcode,
    '0' as branchid
    ";
    switch ($companyid) {
      case 45: //pdpi payroll
        $qry .= ",'timein' as poption";
        break;
      case 28: //xcomp
      case 30: //RT
        $qry .= ",'default' as poption";
        break;
      case 62: //onesky
        $qry .= ",'onesky' as reporttype";
        break;
    }

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
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    switch ($companyid) {
      case 45: //pdpi
        $option = $config['params']['dataparams']['poption'];
        switch ($option) {
          case 'timein':
            return $this->TIMEIN_Layout($config);
            break;

          case 'manual':
            return $this->MANUAL_Layout($config);
            break;
        }
        break;
      case 28: //xcomp
      case 30: //RT
        $option = $config['params']['dataparams']['poption'];
        switch ($option) {
          case 'nohrs':
            return $this->NOHRS_Layout($config);
            break;

          default:
            return $this->WHRS_Layout($config);
            break;
        }
        break;
      case 44: //stonepro
        return $this->stonepro_Layout($config);
        break;
      case 51: //ulitc
        return $this->ulitc_Layout($config);
        break;
      case 53:
        // return $this->camera_Layout_old($config);
        return $this->camera_Layout($config);
        break;
      case 58:
        #Layout 1: Fixed -- wag ireremove
        // return $this->cdo_Layout($config);
        #Layout 2: Nakadisplay lang yung may value
        //return $this->cdo_Layout2($config);
        #Layout 3: Nakadisplay lang yung may value
        return $this->cdo_Layout3($config);
        break;
      case 62: //one sky
        return $this->onesky_Layout($config);
        break;
      default:
        return $this->DEFAULT_Layout($config);
        break;
    }
  }

  private function main_hrs_qry($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $batchid      = $config['params']['dataparams']['line'];
    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divid != 0) {
      $filter2 .= " and emp.divid = $divid";
    }

    if ($batchid != 0) {
      $filter2 .= " and p.batchid = " . $batchid . " ";
    }

    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select 
    e.clientname, concat(left(e.client,2),right(e.client,4)) as client,
    d.divname,dept.clientname as deptname,
    year(p.dateid) as yr,p.dateid,
    batch.batch,p.batchid,
    date(batch.startdate) as startdate,date(batch.enddate) as enddate,
    p.acnoid,pa.alias,
    p.db,p.cr,
    pa.codename,pa.uom,p.qty,
    emp.empid,pa.code,
    (select sum(rs.basicrate) as basicrate from ratesetup as rs where rs.empid=emp.empid and date(batch.enddate) between rs.dateeffect and rs.dateend) as rate,
    batch.enddate as week
    FROM paytrancurrent as p 
    LEFT JOIN employee AS emp ON emp.empid=p.empid
    left join client as e on e.clientid = emp.empid
    left join division as d on d.divid = emp.divid
    left join batch on batch.line=p.batchid
    left join client as dept on dept.clientid = emp.deptid
    left join paccount as pa on pa.line=p.acnoid
    where emp.level in $emplvl $filter $filter1 $filter2 $filter3
    union all
    select 
    e.clientname, concat(left(e.client,2),right(e.client,4)) as client,
    d.divname,dept.clientname as deptname,
    year(p.dateid) as yr,p.dateid,
    batch.batch,p.batchid,
    date(batch.startdate) as startdate,date(batch.enddate) as enddate,
    p.acnoid,pa.alias,
    p.db,p.cr,
    pa.codename,pa.uom,p.qty,
    emp.empid,pa.code,
    (select sum(rs.basicrate) as basicrate from ratesetup as rs where rs.empid=emp.empid and date(batch.enddate) between rs.dateeffect and rs.dateend) as rate,
    batch.enddate as week
    FROM paytranhistory as p 
    LEFT JOIN employee AS emp ON emp.empid=p.empid
    left join client as e on e.clientid = emp.empid
    left join division as d on d.divid = emp.divid
    left join batch on batch.line=p.batchid
    left join client as dept on dept.clientid = emp.deptid
    left join paccount as pa on pa.line=p.acnoid
    where emp.level in $emplvl $filter $filter1 $filter2 $filter3
    order by clientname";
    return $query;
  }

  private function get_distinct_client($mainqry)
  {
    $qry = "
    select distinct clientname from(
    $mainqry
    ) as x
    ";
    return $this->coreFunctions->opentable($qry);
  }


  public function HRS_qry($config)
  {
    return $this->coreFunctions->opentable($this->main_hrs_qry($config));
  }

  public function DEFAULT_qry($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $batchid      = $config['params']['dataparams']['line'];
    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptname != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divname != "") {
      $filter2 .= " and emp.divid = $divid";
    }

    if ($batchid != 0) {
      $filter2 .= " and p.batchid = " . $batchid . " ";
    }

    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";

    if ($config['params']['dataparams']['printtype'] != 'dashboardprinting') {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filter2 .= " and emp.level in $emplvl";
    }

    $query = "SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,date_format(batch.startdate,'%b %d, %y') as startd,
          date_format(batch.enddate,'%b %d, %y') as endd,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,pa.seq
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''='' $filter $filter1 $filter2 $filter3
          union all
          SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,date_format(batch.startdate,'%b %d, %y') as startd,
          date_format(batch.enddate,'%b %d, %y') as endd,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,pa.seq
          FROM paytranhistory as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''='' $filter $filter1 $filter2 $filter3
          order by clientname,seq";

    return $this->coreFunctions->opentable($query);
  }

  public function camera_query($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $batchid      = $config['params']['dataparams']['line'];
    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptname != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divname != "") {
      $filter2 .= " and emp.divid = $divid";
    }

    if ($batchid != 0) {
      $filter2 .= " and p.batchid = " . $batchid . " ";
    }




    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    if ($config['params']['dataparams']['printtype'] != 'dashboardprinting') {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filter2 .= " and emp.level in $emplvl";
    }

    $query = "SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,date_format(batch.startdate,'%b %d, %y') as startd,
          date_format(batch.enddate,'%b %d, %y') as endd,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,pa.seq
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''='' and batch.postdate is null $filter $filter1 $filter2 $filter3
          union all
          SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,date_format(batch.startdate,'%b %d, %y') as startd,
          date_format(batch.enddate,'%b %d, %y') as endd,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,pa.seq
          FROM paytranhistory as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''='' and batch.postdate is not null  $filter $filter1 $filter2 $filter3
          order by clientname,seq";

    return $this->coreFunctions->opentable($query);
  }

  public function cdo_qry($config)
  {
    // QUERY

    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $batchid      = $config['params']['dataparams']['line'];
    $branch     = $config['params']['dataparams']['dbranchname'];
    $branchid     = $config['params']['dataparams']['branchid'];

    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }

    if ($branch != "") {
      $filter2 .= " and emp.branchid = $branchid";
    }

    if ($deptid != 0 && $deptid != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divid != 0 && $divid != "") {
      $filter2 .= " and emp.divid = $divid";
    }

    if ($batchid != 0) {
      $filter2 .= " and p.batchid = " . $batchid . " ";
    }

    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";

    if ($config['params']['dataparams']['printtype'] != 'dashboardprinting') {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filter2 .= " and emp.level in $emplvl";

      $query = "select e.client,e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,
                       date_format(batch.startdate,'%b %d, %y') as startd,date_format(batch.enddate,'%b %d, %y') as endd,
                       DATE_FORMAT(DATE(batch.dateid), '%M %d, %Y') as dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,
                       date(batch.enddate) as enddate,p.acnoid,pa.alias,p.db,p.cr,pa.code,pa.codename,pa.uom,
                      (case when p.acnoid in (29,36) then (p.qty/8) else p.qty end) as qty,
                       pa.alias,emp.empid,p.qty2,pa.seq ,branch.clientname as branchname
                FROM paytrancurrent as p 
                LEFT JOIN employee AS emp ON emp.empid=p.empid
                left join client as e on e.clientid = emp.empid
                left join division as d on d.divid = emp.divid
                left join batch on batch.line=p.batchid
                left join client as dept on dept.clientid = emp.deptid
                left join paccount as pa on pa.line=p.acnoid
                left join client as branch on branch.clientid = emp.branchid
                where ''='' $filter $filter1 $filter2 $filter3
                union all
                SELECT e.client,e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,
                       date_format(batch.startdate,'%b %d, %y') as startd,date_format(batch.enddate,'%b %d, %y') as endd,
                       DATE_FORMAT(DATE(batch.dateid), '%M %d, %Y') as dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,
                       date(batch.enddate) as enddate,p.acnoid,pa.alias,p.db,p.cr,pa.code,pa.codename,pa.uom,
                      (case when p.acnoid in (29,36) then (p.qty/8) else p.qty end) as qty,
                       pa.alias,emp.empid,p.qty2,pa.seq ,branch.clientname as branchname
                FROM paytranhistory as p 
                LEFT JOIN employee AS emp ON emp.empid=p.empid
                left join client as e on e.clientid = emp.empid
                left join division as d on d.divid = emp.divid
                left join batch on batch.line=p.batchid
                left join client as dept on dept.clientid = emp.deptid
                left join paccount as pa on pa.line=p.acnoid
                left join client as branch on branch.clientid = emp.branchid
                where ''='' $filter $filter1 $filter2 $filter3
                order by clientname,seq";
    } else {

      $query = "SELECT e.client,e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,
                     date_format(batch.startdate,'%b %d, %y') as startd,date_format(batch.enddate,'%b %d, %y') as endd,
                     DATE_FORMAT(DATE(batch.dateid), '%M %d, %Y') as dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,
                     date(batch.enddate) as enddate,p.acnoid,pa.alias,p.db,p.cr,pa.code,pa.codename,pa.uom,
                    (case when p.acnoid in (29,36) then (p.qty/8) else p.qty end) as qty,
                     pa.alias,emp.empid,p.qty2,pa.seq,branch.clientname as branchname
              FROM paytranhistory as p 
              LEFT JOIN employee AS emp ON emp.empid=p.empid
              left join client as e on e.clientid = emp.empid
              left join division as d on d.divid = emp.divid
              left join batch on batch.line=p.batchid
              left join client as dept on dept.clientid = emp.deptid
              left join paccount as pa on pa.line=p.acnoid
              left join client as branch on branch.clientid = emp.branchid
              where ''='' $filter $filter1 $filter2 $filter3
              order by clientname,seq";
    }



    return $this->coreFunctions->opentable($query);
  }

  public function stonepro_qry($config)
  {
    // QUERY
    $clientid     = $config['params']['dataparams']['clientid'];
    $client     = $config['params']['dataparams']['client'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $divid     = $config['params']['dataparams']['divid'];
    $batchid      = $config['params']['dataparams']['line'];
    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($clientid != 0) {
      $filter .= " and emp.empid = $clientid";
    }
    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($batchid != 0) {
      $filter2 .= " and p.batchid = " . $batchid . " ";
    }
    if ($divid != '') {
      $filter1 .= " and emp.divid = " . $divid . " ";
    }

    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";

    if ($config['params']['dataparams']['printtype'] != 'dashboardprinting') {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filter2 .= " and emp.level in $emplvl";
    }

    $query = "SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.code,emp.empid,p.qty2,jt.jobtitle as position,
          emp.tin,emp.sss,emp.phic,emp.hdmf,emp.teu,emp.bankacct,emp.nodeps,pa.seq,
          (case when batch.paymode = 'S' then 'Semi-Monthly' 
                when batch.paymode = 'W' then 'Weekly' 
                when batch.paymode = 'M' then 'Monthly' 
                else '' end ) as paymode
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          left join jobthead as jt on jt.line = emp.jobid
          where ''='' $filter $filter1 $filter2 $filter3
          union all
          SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.code,emp.empid,p.qty2,jt.jobtitle as position,
          emp.tin,emp.sss,emp.phic,emp.hdmf,emp.teu,emp.bankacct,emp.nodeps,pa.seq,
          (case when batch.paymode = 'S' then 'Semi-Monthly' 
                when batch.paymode = 'W' then 'Weekly' 
                when batch.paymode = 'M' then 'Monthly' 
                else '' end ) as paymode
          FROM paytranhistory as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          left join jobthead as jt on jt.line = emp.jobid
          where ''='' $filter $filter1 $filter2 $filter3
          order by clientname,seq desc";

    return $this->coreFunctions->opentable($query);
  }
  public function ulitc_qry($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $divid     = $config['params']['dataparams']['divid'];
    $batchid      = $config['params']['dataparams']['line'];
    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($batchid != 0) {
      $filter2 .= " and p.batchid = " . $batchid . " ";
    }
    if ($divid != '') {
      $filter1 .= " and emp.divid = " . $divid . " ";
    }

    $filter3 = " and pa.alias not in ('LOAN','EARNING','DEDUCTION','YSE','YME','YPE','PPBLE','YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    if ($config['params']['dataparams']['printtype'] != 'dashboardprinting') {
      $emplvl = $this->othersClass->checksecuritylevel($config);
      $filter2 .= " and emp.level in $emplvl";
    }
    $query = "SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,
          p.dateid,batch.batch,p.batchid,date_format(batch.startdate, '%b %m-%d') as startdate,date_format(batch.enddate, '%b %m-%d') as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.code,emp.empid,p.qty2,jt.jobtitle as position,
          emp.tin,emp.sss,emp.phic,emp.hdmf,emp.teu,emp.bankacct,emp.nodeps,date(p.dateid) as dateid,
          (case when batch.paymode = 'S' then 'Semi-Monthly'
                when batch.paymode = 'W' then 'Weekly'
                when batch.paymode = 'M' then 'Monthly'
                else '' end ) as paymode
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          left join jobthead as jt on jt.line = emp.jobid
          where 1=1 $filter $filter1 $filter2 $filter3 
          union all
          SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,year(p.dateid) as yr,
          p.dateid,batch.batch,p.batchid,date_format(batch.startdate, '%b %m-%d') as startdate,date_format(batch.enddate, '%b %m-%d') as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.code,emp.empid,p.qty2,jt.jobtitle as position,
          emp.tin,emp.sss,emp.phic,emp.hdmf,emp.teu,emp.bankacct,emp.nodeps,date(p.dateid) as dateid,
          (case when batch.paymode = 'S' then 'Semi-Monthly' 
                when batch.paymode = 'W' then 'Weekly'
                when batch.paymode = 'M' then 'Monthly'
                else '' end ) as paymode
          FROM paytranhistory as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          left join jobthead as jt on jt.line = emp.jobid
          where 1=1 $filter $filter1 $filter2 $filter3
          order by clientname";
    // var_dump($query);

    return $this->coreFunctions->opentable($query);
  }
  private function getcount($config, $empid, $batch)
  {
    $companyid = $config['params']['companyid'];
    $filter = "('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    $filter3 = "";
    $cpostedate = "";
    $hpostedate = "";
    $left_join = "";
    if ($companyid == 51) { // ulitc 
      $filter3 = " and pa.alias not in ('LOAN','EARNING','DEDUCTION','YSE','YME','YPE','PPBLE','YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    }
    if ($companyid == 53) {
      $hpostedate = " and batch.postdate is not null ";
      $cpostedate = " and batch.postdate is null ";
      $left_join = " left join batch on batch.line=p.batchid ";
    }
    return $this->coreFunctions->datareader("select count(batchid) as value from (select p.batchid from paytrancurrent as p 
    left join paccount as pa on pa.line=p.acnoid
    $left_join 
    where pa.alias not in $filter $cpostedate $filter3 and batchid = " . $batch . "  and p.empid=?
    union all
    select p.batchid from paytranhistory as p 
    left join paccount as pa on pa.line=p.acnoid
    $left_join 
    where pa.alias not in $filter $hpostedate $filter3 and batchid = " . $batch . "  and p.empid=?) as x", [$empid, $empid]);
  }

  public function getleave($empid)
  {
    $query = "select leav.bal,pa.codename as leavtype 
           from paccount as pa
           left join leavesetup as leav on leav.acnoid = pa.line 
           where leav.empid = ? and year(leav.dateid) = year(now())";
    return $this->coreFunctions->opentable($query, [$empid]);
  }
  public function getloans($config, $empid, $batch)
  {
    $companyid = $config['params']['companyid'];
    $filter = " and acc.codename like '%LOAN%' and ss.halt <> 1";
    $groupby = "";
    $field1 = " ss.balance,ss.amt ";
    $outerfields = " balance,amt ";
    $outgroupby = "";
    switch ($companyid) {
      case '51': //ulitc 
        $filter = " and (acc.codename like  '%LOAN%' or  acc.alias like  '%LOAN%' )";
        $groupby = " group by st.empid,batch.batch,ss.acnoid,acc.code,acc.codename,st.cr,ss.amt,batch.enddate,st.trno,ss.balance";
        $outgroupby = " group by no_pay,empid,batch,acnoid,code,codename,cr,amtpaid,amt";
        break;
      case '44': //stonepro
        $filter = " and acc.alias like '%LOAN%'";
        $groupby = " group by st.empid,batch.batch,ss.acnoid,acc.code,acc.codename,st.cr,ss.amt,batch.enddate,st.trno,ss.balance";
        $outgroupby = " group by no_pay,empid,batch,acnoid,code,codename,cr,amtpaid,amt";
        break;
    }
    switch ($companyid) {
      case '51': // ulitc
      case '44': // stonepro
        $query = " select empid,batch,acnoid,code,codename,cr,amtpaid, amt, sum((addbal + bal)-balance) as balance,no_pay from (
          select st.empid,batch.batch,acc.code,ss.acnoid,acc.codename,st.cr,st.cr as amtpaid, 

          ifnull(( select count(trans.cr) as balance 
	        from standardtrans as trans 
	        left join batch as b on b.line=trans.batchid
	        where trans.trno = st.trno and trans.empid = st.empid and b.startdate <= batch.enddate),0) as no_pay,

          ifnull(( select sum(trans.cr) as balance 
	        from standardtrans as trans 
	        left join batch as b on b.line=trans.batchid
	        where trans.trno = st.trno and trans.empid = st.empid and 
          (case when trans.ismanual = 1 then trans.dateid <= batch.enddate else b.startdate <= batch.enddate end)),0) as balance,

          ifnull(( select sum(trans.cr) as addbal 
	        from standardtrans as trans 
	        left join batch as b on b.line=trans.batchid
	        where trans.trno = st.trno and trans.empid = st.empid),0) as addbal,

          ss.amt,ss.balance as bal 
			    from  standardsetup as ss 
          left join standardtrans as st on st.trno = ss.trno and ss.empid = st.empid
          left join paccount as acc on acc.line=ss.acnoid
          left join batch on batch.line=st.batchid
          where st.empid = $empid and batch.line = $batch  $filter
         $groupby
          ) as v  $outgroupby ";
        break;
      default:
        $query = "
          select empid,batch,acnoid,code,codename,cr,amtpaid, balance,amt from (
          select st.empid,batch.batch,acc.code,ss.acnoid,acc.codename,st.cr,st.cr as amtpaid, ss.balance,ss.amt
			    from  standardsetup as ss 
          left join standardtrans as st on st.trno = ss.trno and ss.empid = st.empid
          left join paccount as acc on acc.line=ss.acnoid
          left join batch on batch.line=st.batchid
          where st.empid = $empid and batch.line = $batch $filter 
           $groupby
          ) as v $outgroupby ";
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function getdeduction($config, $empid, $batch)
  {
    $companyid = $config['params']['companyid'];
    $filter = " and acc.alias in ('YSE','YME','YPE','YWT')";
    if ($companyid == 51) { // ulict 
      $filter = " and acc.alias in ('YSE','YME','YPE','YWT','ABSENT','LATE','UNDERTIME')";
    }

    switch ($companyid) {
      case 44: //stonepro
        $query = "select pay.empid,batch.batch,pay.acnoid,acc.code,acc.codename,pay.db,pay.cr,acc.alias ,pay.qty,acc.seq,
ifnull((
select sum(pcr.cr) from paytrancurrent as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT42','PT162') and year(b.enddate) = year(now())),0) as wtholdtax,
ifnull((
select sum(pcr.cr) from paytrancurrent as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT44','PT45') and year(b.enddate) = year(now())),0) as sss,
ifnull((
select sum(pcr.cr) from paytrancurrent as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT48','PT495') and year(b.enddate) = year(now())),0) as phil,
ifnull((
select sum(pcr.cr) from paytrancurrent as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT51','PT52') and year(b.enddate) = year(now())),0) as pagibig

from paytrancurrent as pay
left join paccount as acc on acc.line=pay.acnoid
left join batch on batch.line=pay.batchid
left join aaccount as ac on ac.line=acc.aaid
where pay.empid = ? and batch.line = ?  $filter 
union all
select pay.empid,batch.batch,pay.acnoid,acc.code,acc.codename,pay.db,pay.cr,acc.alias ,pay.qty,acc.seq,
ifnull((
select sum(pcr.cr) from paytranhistory as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT42','PT162') and year(b.enddate) = year(now())),0) as wtholdtax,
ifnull((
select sum(pcr.cr) from paytranhistory as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT44','PT45') and year(b.enddate) = year(now())),0) as sss,
ifnull((
select sum(pcr.cr) from paytranhistory as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT48','PT495') and year(b.enddate) = year(now())),0) as phil,
ifnull((
select sum(pcr.cr) from paytranhistory as pcr
left join paccount as pac on pac.line = pcr.acnoid
left join batch as b on b.line = pcr.batchid
where pac.code in ('PT51','PT52') and year(b.enddate) = year(now())),0) as pagibig

from paytranhistory as pay
left join paccount as acc on acc.line=pay.acnoid
left join batch on batch.line=pay.batchid
left join aaccount as ac on ac.line=acc.aaid
where pay.empid = ? and batch.line = ?  $filter ";
        break;

      default:
        $query = "select pay.empid,batch.batch,pay.acnoid,acc.code,acc.codename,pay.db,pay.cr,acc.alias ,pay.qty,acc.seq
from paytrancurrent as pay
left join paccount as acc on acc.line=pay.acnoid
left join batch on batch.line=pay.batchid
left join aaccount as ac on ac.line=acc.aaid
where pay.empid = ? and batch.line = ?  $filter 
union all
select pay.empid,batch.batch,pay.acnoid,acc.code,acc.codename,pay.db,pay.cr,acc.alias ,pay.qty,acc.seq
from paytranhistory as pay
left join paccount as acc on acc.line=pay.acnoid
left join batch on batch.line=pay.batchid
left join aaccount as ac on ac.line=acc.aaid
where pay.empid = ? and batch.line = ?  $filter ";
        break;
    }


    return $this->coreFunctions->opentable($query, [$empid, $batch, $empid, $batch]);
  }

  public function godedection($config, $empid, $batch)
  {
    $batchline = $config['params']['dataparams']['line'];
    $endate = $this->coreFunctions->getfieldvalue("batch", "enddate", "line=?", [$batchline]);
    $curdate = date("Y-m-d");
    $endate = $this->othersClass->sbcdateformat($endate);
    if ($batchline != 0) {
      if ($endate < $curdate) {
        $filter = "and date(b.startdate) <= date('" . $endate . "')";
        $year = " and year(b.startdate) = year('" . $endate . "') ";
      } else {
        $filter = "";
        $year = " and year('" . $endate . "') = year(curdate()) ";
      }
    }

    $emplvl = $this->othersClass->checksecuritylevel($config, true);
    $query = "
    SELECT SUM(phil) AS phil,SUM(pagibig) AS pagibig,SUM(sss) AS sss,SUM(wtholdtax) AS wtholdtax FROM (
    select sum(p.cr) as phil,0 AS pagibig,0 AS sss,0 as wtholdtax

    FROM paytranhistory AS p 
    left join batch as b on b.line = p.batchid
    left join paccount as acc on acc.line = p.acnoid
    left join employee as emp on emp.empid = p.empid
    WHERE p.empid = $empid $year AND acc.code IN ('PT48','PT49') $filter
    and emp.level in  $emplvl
    UNION ALL 

    SELECT 0 as phil, sum(p.cr)  as pagibi, 0 as sss,0 as wtholdtax
    
    FROM paytranhistory AS p 
    left join batch as b on b.line = p.batchid
    left join paccount as acc on acc.line = p.acnoid
    left join employee as emp on emp.empid = p.empid
    WHERE p.empid = $empid $year AND acc.code IN ('PT51','PT52') $filter
    and emp.level in  $emplvl

    UNION ALL 

    SELECT 0 as phil,0 AS pagibig, sum(p.cr) as sss,0 as wtholdtax
  
    FROM paytranhistory AS p 
    left join batch as b on b.line = p.batchid
    left join paccount as acc on acc.line = p.acnoid
    left join employee as emp on emp.empid = p.empid
    WHERE p.empid = $empid $year AND acc.code IN ('PT44','PT45') $filter
    and emp.level in  $emplvl

    UNION ALL 

    SELECT 0 as phil,0 AS pagibig, 0 as sss, sum(p.cr)  as wtholdtax

    FROM paytranhistory AS p 
    left join batch as b on b.line = p.batchid
    left join paccount as acc on acc.line = p.acnoid
    left join employee as emp on emp.empid = p.empid
    WHERE p.empid = $empid $year AND acc.code IN ('PT42','PT162') $filter
    and emp.level in  $emplvl
) AS deduction";
    return $this->coreFunctions->opentable($query);
  }
  public function getearningsbreakdown($config, $empid, $batch)
  {
    $query = "select paytran.batchid,paytran.empid,acc.seq, acc.codename, paytran.qty, paytran.db as earning
from paytrancurrent as paytran
left join employee as emp on emp.empid=paytran.empid
left JOIN paccount as acc on acc.line=paytran.acnoid
left join batch on batch.line=paytran.batchid
where paytran.empid = ? and batch.line = ? and paytran.db>0 and acc.code not in('PT56','PT57','PT55','PT54','PT53') 
and acc.codename not like '%leave%' and acc.codename not like '%OT%'

union all
select paytran.batchid,paytran.empid,acc.seq, acc.codename, paytran.qty, paytran.db as earning
from paytranhistory as paytran
left join employee as emp on emp.empid=paytran.empid
left JOIN paccount as acc on acc.line=paytran.acnoid
left join batch on batch.line=paytran.batchid
where paytran.empid = ? and batch.line = ? and paytran.db>0 and acc.code not in('PT56','PT57','PT55','PT54','PT53') 
and acc.codename not like '%leave%' and  acc.codename not like '%OT%'
order by seq desc";
    return $this->coreFunctions->opentable($query, [$empid, $batch, $empid, $batch]);
  }
  public function getearnings($empid, $batch)
  {
    $query = "select clientname,batchid,empid,code,codename,basic,hrs,uom,paymode,alias,basicrate,istax from (

select e.clientname,p.batchid,p.empid,pa.code,pa.codename,pa.istax,
(case when pa.code = 'PT57' then p.db
when pa.alias like '%ALLOWANCE%' and pa.istax =1 then p.db
when pa.alias like '%ALLOWANCE%' and pa.istax <> 1 then p.db
else p.db  end ) as basic,p.qty as hrs,pa.uom,
(select rs.basicrate from ratesetup as rs where rs.empid=p.empid and year(rs.dateend) = '9999') as basicrate,
      (case when emp.classrate = 'D' then 'DAILY'
                when emp.classrate = 'W' then 'WEEKLY'
                when emp.classrate = 'M' then 'MONTHLY'
                else p.db end ) as paymode,pa.alias
from paytrancurrent as p
left join employee AS emp ON emp.empid=p.empid
left join client as e on e.clientid = emp.empid
left join batch on batch.line=p.batchid
left join paccount as pa on pa.line=p.acnoid
where p.db > 0 and pa.code in ('PT1','PT8','PT9','PT57') or codename like '%OTHER EARNING%' or codename like '%ALLOWANCE%'

union all

select e.clientname,p.batchid,p.empid,pa.code,pa.codename,pa.istax,
(case when pa.code = 'PT57' then p.db
when pa.alias like '%ALLOWANCE%' and pa.istax =1 then p.db
when pa.alias like '%ALLOWANCE%' and pa.istax <> 1 then p.db
else p.db end ) as basic,p.qty as hrs,pa.uom,
(select rs.basicrate from ratesetup as rs where rs.empid=p.empid and year(rs.dateend) = '9999') as basicrate,
      (case when emp.classrate = 'D' then 'DAILY'
                when emp.classrate = 'W' then 'WEEKLY'
                when emp.classrate = 'M' then 'MONTHLY'
                else '' end ) as paymode,pa.alias
from paytranhistory as p
left join employee AS emp ON emp.empid=p.empid
left join client as e on e.clientid = emp.empid
left join batch on batch.line=p.batchid
left join paccount as pa on pa.line=p.acnoid
where p.db > 0 and pa.code in ('PT1','PT8','PT9','PT57') or codename like '%OTHER EARNING%' or codename like '%ALLOWANCE%'
order by clientname

) as es where es.empid = ? and  es.batchid = ?  order by clientname";
    //  var_dump($query, [$empid, $batch]);
    return $this->coreFunctions->opentable($query, [$empid, $batch]);
  }
  public function getovertimebreakdown($config, $empid, $batch)
  {
    $query = "select code,codename,otqty,otxqty,ndqty,ndxqty,empid,batchid,seq,alias,otb from (
select acc.code,acc.codename,paytran.qty as otqty, 0 as otxqty, 0 as ndqty , 0 as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.db as otb from paytrancurrent  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where paytran.db>0 and acc.code in ('PT15','PT16','PT18','PT64','PT82','PT83')
union all
select acc.code,acc.codename,0 as otqty, paytran.qty as otxqty, 0 as ndqty , 0 as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.db as otb from paytrancurrent  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where paytran.db>0 and acc.code in ('PT17','PT142','PT80','PT81','PT87','PT88')
union all
select acc.code,acc.codename,0 as otqty, 0 as otxqty, paytran.qty as ndqty , 0 as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.db as otb from paytrancurrent  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where acc.code in ('PT76','PT143','PT144','PT145','PT146','PT147')
union all
select acc.code,acc.codename,0 as otqty, 0 as otxqty, 0 as ndqty , paytran.qty as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.db as otb from paytrancurrent  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where paytran.db>0 and acc.code in ('PT148','PT149','PT150','PT151','PT152','PT153')

union all

select acc.code,acc.codename,paytran.qty as otqty, 0 as otxqty, 0 as ndqty , 0 as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.db as otb from paytranhistory  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where paytran.db>0 and acc.code in ('PT15','PT16','PT18','PT64','PT82','PT83')
union all
select acc.code,acc.codename,0 as otqty, paytran.qty as otxqty, 0 as ndqty , 0 as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.db as otb from paytranhistory  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where paytran.db>0 and acc.code in ('PT17','PT142','PT80','PT81','PT87','PT88')
union all
select acc.code,acc.codename,0 as otqty, 0 as otxqty, paytran.qty as ndqty , 0 as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.qty as otb from paytranhistory  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where acc.code in ('PT76','PT143','PT144','PT145','PT146','PT147')
union all
select acc.code,acc.codename,0 as otqty, 0 as otxqty, 0 as ndqty , paytran.qty as ndxqty,paytran.empid,paytran.batchid,acc.seq,acc.alias,paytran.qty as otb from paytranhistory  as paytran
left JOIN paccount as acc on acc.line=paytran.acnoid
left join employee as emp on emp.empid=paytran.empid
left join batch on batch.line=paytran.batchid
where paytran.db>0 and acc.code in ('PT148','PT149','PT150','PT151','PT152','PT153')
) as ovt
where empid = ? and batchid = ?";
    return $this->coreFunctions->opentable($query, [$empid, $batch]);
  }

  public function getdeductionbreakdown($config, $empid, $batch)
  {
    $companyid = $config['params']['companyid'];
    $filter = " and acc.alias in ('DEDUCTION')";
    $filteraccIn = "";
    $orderby = " desc";


    switch ($companyid) {
      case 53: //camera
      case 62: //onesky
        $filter = "";
        $filteraccIn = " and acc.alias in ('ABSENT','LATE','UNDERTIME','YWT','LOAN','DEDUCTION','HDMFLOAN','YPE','YME','YSE') ";
        break;

      case 58: //cdo
        $filter = "";
        $orderby = " ";
        $filteraccIn = " and acc.alias in ('ABSENT','LATE','UNDERTIME','YWT','YSE','YME','YPE','LOAN','SSSLOAN','HDMFLOAN','DEDUCTION') ";
        break;
    }

    $query = "select paytran.batchid,paytran.empid,acc.seq, acc.codename, paytran.qty, paytran.cr
              from paytrancurrent as paytran
              left join employee as emp on emp.empid=paytran.empid
              left JOIN paccount as acc on acc.line=paytran.acnoid
              left join batch on batch.line=paytran.batchid
              where paytran.empid = ? and batch.line = ? and paytran.cr <>0 and batch.postdate is null $filteraccIn $filter
              union all
              select paytran.batchid,paytran.empid,acc.seq, acc.codename, paytran.qty, paytran.cr
              from paytranhistory as paytran
              left join employee as emp on emp.empid=paytran.empid
              left JOIN paccount as acc on acc.line=paytran.acnoid
              left join batch on batch.line=paytran.batchid
              where paytran.empid = ? and batch.line = ? and paytran.cr <> 0 and batch.postdate is not null  $filteraccIn $filter order by seq $orderby";

    return $this->coreFunctions->opentable($query, [$empid, $batch, $empid, $batch]);
  }

  public function taxableincome($config, $empid, $batch)
  {
    $query = "select sum(taxable)  AS taxable 
    FROM (
select sum(paytran.db - paytran.cr) taxable
from paytranhistory as paytran
left join employee as emp on emp.empid=paytran.empid
left JOIN paccount as acc on acc.line=paytran.acnoid
left join batch on batch.line=paytran.batchid
where paytran.empid = ? and batch.line = ? and (acc.istax = 1 or acc.code IN ('PT44','PT48','PT51'))) AS A";

    return $this->coreFunctions->opentable($query, [$empid, $batch, $empid, $batch]);
  }
  public function dataearn($config, $empid, $batchid)
  {
    switch ($config['params']['companyid']) {
      case 44: // stonepro
        $query = "
          select batch,batchid,alias,codename,uom,code,empid,sum(cr) as cr,seq,
          case when code = 'PT57' then (sum(db) - minusbasic) else sum(db) end as db,
          case when code = 'PT57' then (sum(qty)) else sum(qty) end AS qty from (
          select batch.batch,p.batchid,
          ifnull((select sum(pay.db) FROM paytrancurrent as pay where pay.empid = p.empid and 
          pay.acnoid in (85,95,96,104,117,120) and pay.batchid = $batchid ),0) as minusbasic,

          p.acnoid,pa.alias,sum(p.db) as db,sum(p.cr) as cr,pa.codename,pa.uom,case when pa.`code` = 'PT57' then sum(p.qty) when pa.uom = 'PESO' then 0 ELSE sum(p.qty) end as qty,pa.code,emp.empid,pa.seq
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''=''  and emp.empid  = ?   and p.batchid = ?  and emp.level in (1,2,3,4,5,6,7,8,9,10) 
          and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER','YSE', 'YME', 'YPE', 'LOAN', 'HDMFLOAN', 'SSSLOAN')
          and pa.code not in ('PT86', 'PT131', 'PT128', 'PT130', 'PT107')
          GROUP BY batch.batch,p.batchid,
          p.acnoid,pa.alias,pa.codename,pa.uom,pa.code,emp.empid,p.empid,pa.seq
          union all
          select batch.batch,p.batchid,
          
          ifnull((select sum(pay.db) FROM paytranhistory as pay where pay.empid = p.empid and 
          pay.acnoid in (85,95,96,104,117,120)  and pay.batchid = $batchid),0) as minusbasic,

          p.acnoid,pa.alias,sum(p.db) as db,sum(p.cr) as cr,pa.codename,pa.uom,case when pa.`code` = 'PT57' then sum(p.qty) when pa.uom = 'PESO' then 0 ELSE sum(p.qty) end as qty,pa.code,emp.empid,pa.seq
          FROM paytranhistory as p LEFT JOIN employee as emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''=''  and emp.empid  = ?  and p.batchid = ?  and emp.level in (1,2,3,4,5,6,7,8,9,10) 
          and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER','YSE', 'YME', 'YPE', 'LOAN', 'HDMFLOAN', 'SSSLOAN')
          and pa.code not in ('PT86', 'PT131', 'PT128', 'PT130', 'PT107')
          GROUP BY batch.batch,p.batchid,
          p.acnoid,pa.alias,pa.codename,pa.uom,pa.code,emp.empid,p.empid,pa.seq
          order BY seq desc
) as v group by batch,batchid,alias,codename,uom,code,empid,minusbasic,seq
 order by seq desc";
        break;

      default:
        $query = "
          select batch.batch,p.batchid,
          p.acnoid,pa.alias,sum(p.db) as db,sum(p.cr) as cr,pa.codename,pa.uom,sum(p.qty) qty,pa.code,emp.empid,pa.seq
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''='' and batch.postdate is null and emp.empid  = ?    and p.batchid = ?  and emp.level in (1,2,3,4,5,6,7,8,9,10) 
          and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER','YSE', 'YME', 'YPE', 'LOAN', 'HDMFLOAN', 'SSSLOAN')
          GROUP BY batch.batch,p.batchid,
          p.acnoid,pa.alias,pa.codename,pa.uom,pa.code,emp.empid,pa.seq
          union all
          select batch.batch,p.batchid,
          p.acnoid,pa.alias,sum(p.db) as db,sum(p.cr) as cr,pa.codename,pa.uom,sum(p.qty) qty,pa.code,emp.empid,pa.seq
          FROM paytranhistory as p LEFT JOIN employee as emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where ''='' and batch.postdate is not null and emp.empid  = ?   and p.batchid = ?  and emp.level in (1,2,3,4,5,6,7,8,9,10) 
          and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER','YSE', 'YME', 'YPE', 'LOAN', 'HDMFLOAN', 'SSSLOAN')
          GROUP BY batch.batch,p.batchid,
          p.acnoid,pa.alias,pa.codename,pa.uom,pa.code,emp.empid,pa.seq
          order BY seq desc";
        break;
    }

    return $this->coreFunctions->opentable($query, [$empid, $batchid, $empid, $batchid]);
  }
  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '11';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $batch      = $config['params']['dataparams']['batchid'];

    $str = '';
    $layoutsize = '500';


    $str .= '<br/><br/>';


    return $str;
  }

  public function WHRS_Layout($config)
  {
    $result = $this->HRS_qry($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 14;
    $font_size_title = 20;
    $font_size_header = 17;
    $font_size_tablecol = 17;
    $font_size_collabel = 16;
    $font_size_colvalue = 15;
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1120';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }


    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '15px;margin-top:5px;');
    $str .= $this->displayHeader($config);
    $emp = "";

    $clientname = "";
    $client = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $cashadv = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;
    $totalbalances = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;
    $qtyallowance = 0;

    $i = 0;
    $c = 0;
    $break = 0;
    $instance = 0;


    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $clientname = $data->clientname;
      $client = $data->client;
      $divname = $data->divname;
      $deptname = $data->deptname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + ($data->cr - $data->db);

        $qtyabsent = $qtyabsent + $data->qty;
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'LATE') {
        $late = $late   + ($data->cr - $data->db);

        $qtylate = $qtylate + $data->qty;
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + ($data->cr - $data->db);

        $qtyundertime = $qtyundertime + $data->qty;
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyallowance = $qtyallowance + $data->qty;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + ($data->db - $data->cr);
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'YME') {
        $phic = $phic + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } else {
        if (abs($data->cr) > 0) {
          if ($data->code == 'PT69') {
            $cashadv = $cashadv + $data->cr;
          } else {
            $otherdeduction = $otherdeduction + $data->cr;
          }
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }

      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('P A Y &nbsp S L I P', null, null, false, $border, '', '', $font, $font_size_title, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $break += 1;



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE: ' . strtoupper($client) . ' - ' . strtoupper($clientname), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(strtoupper($divname), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payroll Period: ' . strtoupper($data->startdate) . ' to ' . strtoupper($data->enddate) . ' - ' . strtoupper($data->batch), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT: ' . strtoupper($deptname), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $loanbal = $this->coreFunctions->datareader("select sum(balance) as value from standardsetup where empid=? ", [$data->empid]);
        $leavebal = $this->coreFunctions->datareader("select sum(bal) as value from leavesetup left join paccount on paccount.line=leavesetup.acnoid where paccount.alias='SIL' and year(dateid)= '$data->yr' and empid=? ", [$data->empid]);
        $advancebal = $this->coreFunctions->datareader("SELECT ifnull(SUM(balance),0) AS value FROM standardsetupadv WHERE empid=? ", [$data->empid]);



        $hours = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(reghrs) as workedhrs,sum(absdays) as absent,sum(latehrs+underhrs) as lateunderhrs from timecard
          where empid=$data->empid 
          and date(dateid) between '$data->startdate' and '$data->enddate'"

        )), true);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Rate.: ' . number_format($data->rate, 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('Hrs Worked:' . number_format($hours[0]['workedhrs'], 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Absent:' . number_format($hours[0]['absent'], 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('Late/Und:' . number_format($hours[0]['lateunderhrs'], 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);

        // column heads
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('E A R N I N G S', '200', null, false, $border, 'TB', 'L', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('HOURS', '95', null, false, $border, 'TB', 'R', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '95', null, false, $border, 'TB', 'R', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('D E D U C T I O N S', '200', null, false, $border, 'TB', 'L', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '95', null, false, $border, 'TB', 'R', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '95', null, false, $border, 'TB', 'R', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->endrow();

        // basic pay, WHT
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Basic Pay :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtybasicpay == 0 ? '-' : number_format($qtybasicpay - $qtyabsent - $qtylate - $qtyundertime, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($basicpay == 0 ? '-' : number_format($basicpay - $absent  - $late - $undertime, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('WHT : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($wht == 0 ? '-' : number_format($wht, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();



        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Regular OT :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtyrot == 0 ? '-' : number_format($qtyrot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($rot == 0 ? '-' : number_format($rot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('SSS Premium : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($sss == 0 ? '-' : number_format($sss, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // ndiff ot, phil prem
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Ndiff OT :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtyndiffot == 0 ? '-' : number_format($qtyndiffot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($ndiffot == 0 ? '-' : number_format($ndiffot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('PHIP Premium : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($phic == 0 ? '-' : number_format($phic, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // restday, pagibig prem
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtyrestday == 0 ? '-' : number_format($qtyrestday, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($restday == 0 ? '-' : number_format($restday, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('Pag-Ibig Premium: ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($hdmf == 0 ? '-' : number_format($hdmf, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // restday ot, other loans

        $sssloanbal = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(balance) as value from standardsetup 
          left join paccount as p on p.line=standardsetup.acnoid
          where empid=$data->empid 
          and p.alias='SSSLOAN'
          and date(effdate) <= '$data->enddate'"
        )), true);


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday OT :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtyrestdayot == 0 ? '-' : number_format($qtyrestdayot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($restdayot == 0 ? '-' : number_format($restdayot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('SSS Loans : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($sssloanbal[0]['value'] == 0 ? '-' : number_format($sssloanbal[0]['value'], 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($sssloan == 0 ? '-' : number_format($sssloan, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // special holiday, sss

        $hdmfloanbal = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(balance) as value from standardsetup 
          left join paccount as p on p.line=standardsetup.acnoid
          where empid=$data->empid 
          and p.alias='HDMFLOAN'
          and date(effdate) <= '$data->enddate'"
        )), true);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special Holiday :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtyspecial == 0 ? '-' : number_format($qtyspecial, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col((($special + $specialun) == 0 ? '-' : number_format(($special + $specialun), 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('Pag Ibig Loan : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($hdmfloanbal[0]['value'] == 0 ? '-' : number_format($hdmfloanbal[0]['value'], 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // special ot, other deductions

        $otherloanbal = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(balance) as value from standardsetup 
          left join paccount as p on p.line=standardsetup.acnoid
          where empid=$data->empid 
          and p.alias='LOAN'
          and date(effdate) between '$data->startdate' and '$data->enddate'"
        )), true);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special OT :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtyspecialot == 0 ? '-' : number_format($qtyspecialot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($specialot == 0 ? '-' : number_format($specialot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Other Loans: ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($otherloanbal[0]['value'] == 0 ? '-' : number_format($otherloanbal[0]['value'], 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($loan == 0 ? '-' : number_format($loan, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();


        // legal holiday, sss loan
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal Holiday :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtylegal + $qtylegalun == 0 ? '-' : number_format($qtylegal + $qtylegalun, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col((($legal + $legalun) == 0 ? '-' : number_format(($legal + $legalun), 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Cash Advance : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($advancebal == 0 ? '-' : number_format($advancebal, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($cashadv == 0 ? '-' : number_format($cashadv, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');

        $str .= $this->reporter->endrow();

        $totalbalances += $sssloanbal[0]['value'] + $hdmfloanbal[0]['value'] + $otherloanbal[0]['value'] + $advancebal;

        // legal ot, philhealth
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal OT :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtylegalot == 0 ? '-' : number_format($qtylegalot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($legalot == 0 ? '-' : number_format($legalot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Other Deductions : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Allowance :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($qtyallowance == 0 ? '-' : number_format($qtyallowance, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($allowance == 0 ? '-' : number_format($allowance, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->endrow();


        // other earnings, pagibig loan
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Other Earnings :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($otherearnings == 0 ? '-' : number_format($otherearnings, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');



        $str .= $this->reporter->endrow();

        // 13th month,cash advance
        if ($bonus != 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('13th Month :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
          $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
          $str .= $this->reporter->col(($bonus == 0 ? '-' : number_format($bonus, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
          $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');


          $str .= $this->reporter->endrow();
        }

        // leave

        if ($leave != 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Leave :', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
          $str .= $this->reporter->col(($qtyleave == 0 ? '-' : number_format($qtyleave, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
          $str .= $this->reporter->col(($leave == 0 ? '-' : number_format($leave, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
          $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
          $str .= $this->reporter->col('', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

          $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
          $str .= $this->reporter->endrow();
        }

        // space tb
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();

        // total earnings, total deductions
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '200', null, false, $border, '', 'L', $font, $font_size_collabel, 'B', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($totalearn == 0 ? '-' : number_format($totalearn, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('TOTAL', '200', null, false, $border, '', 'L', $font, $font_size_collabel, 'B', '', '');
        $str .= $this->reporter->col($totalbalances == 0 ? '-' : number_format($totalbalances, 2), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // space tb
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();


        // net pay
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NET PAY', '200', null, false, $border, '', 'L', $font, $font_size_collabel, 'B', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), '95', null, false, $border, '', 'R', $font, $font_size_colvalue + 2, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Received By : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $cashadv = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $qtyallowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;
        $totalbalances = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
        $qtyallowance = 0;
      }


      if ($break == 1 && $instance == 0) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', null, 250, false, $border, '', '', $font, $font_size_collabel, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $instance = 1;
      }

      if ($break == 2) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();




        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $break = 0;
        $instance = 0;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }

  public function NOHRS_Layout($config)
  {
    $result = $this->HRS_qry($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '12';

    $font_size_title = 20;
    $font_size_header = 17;
    $font_size_tablecol = 17;
    $font_size_collabel = 16;
    $font_size_colvalue = 15;
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;

    $layoutsize = '1120';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }


    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '15px;margin-top:5px;');
    $str .= $this->displayHeader($config);
    $emp = "";

    $clientname = "";
    $client = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $cashadv = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;
    $totalbalances = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;

    $i = 0;
    $c = 0;
    $break = 0;
    $instance = 0;


    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $clientname = $data->clientname;
      $client = $data->client;
      $divname = $data->divname;
      $deptname = $data->deptname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + ($data->cr - $data->db);

        $qtyabsent = $qtyabsent + $data->qty;
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'LATE') {
        $late = $late   + ($data->cr - $data->db);

        $qtylate = $qtylate + $data->qty;
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + ($data->cr - $data->db);

        $qtyundertime = $qtyundertime + $data->qty;
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'SL') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + ($data->db - $data->cr);
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + ($data->db - $data->cr);
        $totalearn = $totalearn + ($data->db - $data->cr);
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + ($data->cr - $data->db);
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + ($data->cr - $data->db);
        $totalded = $totalded + ($data->cr - $data->db);
      } else {
        if (abs($data->cr) > 0) {
          if ($data->code == 'PT69') {
            $cashadv = $cashadv + $data->cr;
          } else {
            $otherdeduction = $otherdeduction + $data->cr;
          }
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }

      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('P A Y &nbsp S L I P', null, null, false, $border, '', '', $font, $font_size_title, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $break += 1;



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE: ' . strtoupper($client) . ' - ' . strtoupper($clientname), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col(strtoupper($divname), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payroll Period: ' . strtoupper($data->startdate) . ' to ' . strtoupper($data->enddate) . ' - ' . strtoupper($data->batch), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT: ' . strtoupper($deptname), '390', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $loanbal = $this->coreFunctions->datareader("select sum(balance) as value from standardsetup where empid=? ", [$data->empid]);
        $leavebal = $this->coreFunctions->datareader("select sum(bal) as value from leavesetup left join paccount on paccount.line=leavesetup.acnoid where paccount.alias='SIL' and year(dateid)= '$data->yr' and empid=? ", [$data->empid]);
        $advancebal = $this->coreFunctions->datareader("SELECT ifnull(SUM(balance),0) AS value FROM standardsetupadv WHERE empid=? ", [$data->empid]);



        $hours = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(reghrs) as workedhrs,sum(absdays) as absent,sum(latehrs+underhrs) as lateunderhrs from timecard
          where empid=$data->empid 
          and date(dateid) between '$data->startdate' and '$data->enddate'"

        )), true);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Rate.: ' . number_format($data->rate, 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('Hrs Worked:' . number_format($hours[0]['workedhrs'], 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Absent:' . number_format($hours[0]['absent'], 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->col('Late/Und:' . number_format($hours[0]['lateunderhrs'], 2), '195', null, false, $border, '', 'L', $font, $font_size_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();




        $str .= $this->reporter->begintable($layoutsize);

        // column heads
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('E A R N I N G S', '295', null, false, $border, 'TB', 'L', $font, $font_size_tablecol, 'B', '', '');

        $str .= $this->reporter->col('AMOUNT', '95', null, false, $border, 'TB', 'R', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('D E D U C T I O N S', '200', null, false, $border, 'TB', 'L', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('BALANCE', '95', null, false, $border, 'TB', 'R', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '95', null, false, $border, 'TB', 'R', $font, $font_size_tablecol, 'B', '', '');
        $str .= $this->reporter->endrow();

        // basic pay, WHT
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Basic Pay :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($basicpay == 0 ? '-' : number_format($basicpay - $absent  - $late - $undertime, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('WHT : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($wht == 0 ? '-' : number_format($wht, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();


        // regular ot, sss pre

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Regular OT :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($rot == 0 ? '-' : number_format($rot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('SSS Premium : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($sss == 0 ? '-' : number_format($sss, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // ndiff ot, phil prem
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Ndiff OT :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($ndiffot == 0 ? '-' : number_format($ndiffot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('PHIP Premium : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($phic == 0 ? '-' : number_format($phic, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // restday, pagibig prem
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($restday == 0 ? '-' : number_format($restday, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('Pag-Ibig Premium: ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($hdmf == 0 ? '-' : number_format($hdmf, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // restday ot, other loans
        $sssloanbal = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(balance) as value from standardsetup 
          left join paccount as p on p.line=standardsetup.acnoid
          where empid=$data->empid 
          and p.alias='SSSLOAN'
          and date(effdate) <= '$data->enddate'"
        )), true);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday OT :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($restdayot == 0 ? '-' : number_format($restdayot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('SSS Loans : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($sssloanbal[0]['value'] == 0 ? '-' : number_format($sssloanbal[0]['value'], 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($sssloan == 0 ? '-' : number_format($sssloan, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();


        $hdmfloanbal = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(balance) as value from standardsetup 
          left join paccount as p on p.line=standardsetup.acnoid
          where empid=$data->empid 
          and p.alias='HDMFLOAN'
          and date(effdate) <= '$data->enddate'"
        )), true);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special Holiday :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col((($special + $specialun) == 0 ? '-' : number_format(($special + $specialun), 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('Pag Ibig Loan : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($hdmfloanbal[0]['value'] == 0 ? '-' : number_format($hdmfloanbal[0]['value'], 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // special ot, other deductions

        $otherloanbal = json_decode(json_encode($this->coreFunctions->opentable(
          "select sum(balance) as value from standardsetup 
          left join paccount as p on p.line=standardsetup.acnoid
          where empid=$data->empid 
          and p.alias='LOAN'
          and date(effdate) between '$data->startdate' and '$data->enddate'"
        )), true);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special OT :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($specialot == 0 ? '-' : number_format($specialot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Other Loans: ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($otherloanbal[0]['value'] == 0 ? '-' : number_format($otherloanbal[0]['value'], 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($loan == 0 ? '-' : number_format($loan, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // legal holiday, sss loan
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal Holiday :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col((($legal + $legalun) == 0 ? '-' : number_format(($legal + $legalun), 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Cash Advance : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($advancebal == 0 ? '-' : number_format($advancebal, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col(($cashadv == 0 ? '-' : number_format($cashadv, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');

        $str .= $this->reporter->endrow();

        $totalbalances += $sssloanbal[0]['value'] + $hdmfloanbal[0]['value'] + $otherloanbal[0]['value'] + $advancebal;

        // legal ot, philhealth
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal OT :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($legalot == 0 ? '-' : number_format($legalot, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Other Deductions : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col(($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Allowance :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($allowance == 0 ? '-' : number_format($allowance, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->endrow();


        // other earnings, pagibig loan
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Other Earnings :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col(($otherearnings == 0 ? '-' : number_format($otherearnings, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');



        $str .= $this->reporter->endrow();

        // 13th month,cash advance
        if ($bonus != 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('13th Month :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

          $str .= $this->reporter->col(($bonus == 0 ? '-' : number_format($bonus, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
          $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');


          $str .= $this->reporter->endrow();
        }

        // leave

        if ($leave != 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Leave :', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

          $str .= $this->reporter->col(($leave == 0 ? '-' : number_format($leave, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
          $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
          $str .= $this->reporter->col('', '295', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

          $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
          $str .= $this->reporter->endrow();
        }

        // space tb
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '295', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();

        // total earnings, total deductions
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '295', null, false, $border, '', 'L', $font, $font_size_collabel, 'B', '', '');

        $str .= $this->reporter->col(($totalearn == 0 ? '-' : number_format($totalearn, 2)), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('TOTAL', '200', null, false, $border, '', 'L', $font, $font_size_collabel, 'B', '', '');
        $str .= $this->reporter->col($totalbalances == 0 ? '-' : number_format($totalbalances, 2), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, '', '', '');
        $str .= $this->reporter->endrow();

        // space tb
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '295', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();


        // net pay
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NET PAY', '295', null, false, $border, '', 'L', $font, $font_size_collabel, 'B', '', '');

        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), '95', null, false, $border, '', 'R', $font, $font_size_colvalue, 'B', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('Received By : ', '200', null, false, $border, '', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('', '95', null, false, $border, '', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '295', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');

        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'L', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'TB', 'R', $font, $font_size_collabel, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $cashadv = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $qtyallowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;
        $totalbalances = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
      }


      if ($break == 1 && $instance == 0) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', null, 250, false, $border, '', '', $font, $font_size_collabel, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $instance = 1;
      }

      if ($break == 2) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();




        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $break = 0;
        $instance = 0;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }

  private function cdo_Header($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '11';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $batch      = $config['params']['dataparams']['batchid'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    return $str;
  }


  public function cdo_Layout($config)
  {
    $result = $this->cdo_qry($config);

    $companyid = $config['params']['companyid'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->cdo_Header($config);
    $emp = "";

    $clientname = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;
    $deduction = 0;

    $i = 0;
    $c = 0;
    $k = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $clientname = $data->clientname;
      $divname = $data->divname;
      $deptname = $data->deptname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtylate = $qtylate + $data->qty;
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        //$totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'DEDUCTION') {
        $deduction = $deduction + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }

      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('P A Y &nbsp S L I P', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payroll Period : ' . strtoupper($data->startdate) . ' to ' . strtoupper($data->enddate) . ' - ' . strtoupper($data->batch), '540', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $loanbal = $this->coreFunctions->datareader("select sum(balance) as value from standardsetup where empid=? ", [$data->empid]);
        $leavebal = $this->coreFunctions->datareader("select sum(bal) as value from leavesetup left join paccount on paccount.line=leavesetup.acnoid where paccount.alias='SIL' and year(dateid)= '$data->yr' and empid=? ", [$data->empid]);

        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Current Loan Balance Amt : ' . number_format($loanbal, 2), '540', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col('', '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('E A R N I N G S', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='QTY'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='AMOUNT'>", '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('D E D U C T I O N S', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='QTY'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='AMOUNT'>", '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Basic Pay :", '150', '', false, $border, '', 'L', $font, '11', 'QTY', 'QTY', 'QTY');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtybasicpay == 0 ? '-' : number_format($qtybasicpay, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($basicpay == 0 ? '-' : number_format($basicpay, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Absent : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyabsent == 0 ? '-' : number_format($qtyabsent, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($absent == 0 ? '-' : number_format($absent, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Allowance : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($allowance == 0 ? '-' : number_format($allowance, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Late : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylate == 0 ? '-' : number_format($qtylate, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($late == 0 ? '-' : number_format($late, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Regular OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyrot == 0 ? '-' : number_format($qtyrot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($rot == 0 ? '-' : number_format($rot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Undertime : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyundertime == 0 ? '-' : number_format($qtyundertime, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($undertime == 0 ? '-' : number_format($undertime, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Ndiff OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyndiffot == 0 ? '-' : number_format($qtyndiffot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($ndiffot == 0 ? '-' : number_format($ndiffot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('WHT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($wht == 0 ? '-' : number_format($wht, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyrestday == 0 ? '-' : number_format($qtyrestday, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($restday == 0 ? '-' : number_format($restday, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Pag-Ibig : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($hdmf == 0 ? '-' : number_format($hdmf, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyrestdayot == 0 ? '-' : number_format($qtyrestdayot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($restdayot == 0 ? '-' : number_format($restdayot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Other Loans : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($loan == 0 ? '-' : number_format($loan, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special Hol unwork : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyspecialun == 0 ? '-' : number_format($qtyspecialun, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($specialun == 0 ? '-' : number_format($specialun, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('SSS : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($sss == 0 ? '-' : number_format($sss, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special Hol : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyspecial == 0 ? '-' : number_format($qtyspecial, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($special == 0 ? '-' : number_format($special, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Other Deduction : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyspecialot == 0 ? '-' : number_format($qtyspecialot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($specialot == 0 ? '-' : number_format($specialot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('SSS Loan : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($sssloan == 0 ? '-' : number_format($sssloan, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal Hol unwork : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylegalun == 0 ? '-' : number_format($qtylegalun, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($legalun == 0 ? '-' : number_format($legalun, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Philhealth : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($phic == 0 ? '-' : number_format($phic, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal Hol : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylegal == 0 ? '-' : number_format($qtylegal, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($legal == 0 ? '-' : number_format($legal, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Pagibig Loan : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylegalot == 0 ? '-' : number_format($qtylegalot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($legalot == 0 ? '-' : number_format($legalot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Other Earnings : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($otherearnings == 0 ? '-' : number_format($otherearnings, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('13th Month : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($bonus == 0 ? '-' : number_format($bonus, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Leave : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyleave == 0 ? '-' : number_format($qtyleave, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($leave == 0 ? '-' : number_format($leave, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');


        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EARNINGS', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($totalearn == 0 ? '-' : number_format($totalearn, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('TOTAL DEDUCTIONS', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        // here
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('NET PAY', '150', null, false, $border, '', 'L', $font, '14', '', '', '');
        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), '150', null, false, $border, '', 'R', $font, '14', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= "</br></br>";
        $k++;

        if ($k == 2) {
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_Header($config);
          $k = 0;
        }
        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $tripping = 0;
        $operator = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
      }
    }

    $str .= $this->reporter->endreport();


    return $str;
  }

  public function cdo_Layout2($config)
  {
    $result = $this->cdo_qry($config);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '60px; margin-top:10px');
    // $str .= $this->DEFAULT_Header($config);
    $clientname = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;
    $deduction = 0;

    $i = 0;
    $c = 0;
    $str .= '<div style="position: relative;margin-top:70px">';
    $str .= $this->reporter->begintable($layoutsize);
    $j = 0;
    foreach ($result as $key => $data) {
      $clientname = $data->clientname;
      $divname = $data->divname;
      $deptname = $data->deptname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtylate = $qtylate + $data->qty;
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'DEDUCTION') {
        $deduction = $deduction + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('P A Y &nbsp S L I P', 1000, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payroll Period : ' . strtoupper($data->startdate) . ' to ' . strtoupper($data->enddate) . ' - ' . strtoupper($data->batch), '540', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $loanbal = $this->coreFunctions->datareader("select sum(balance) as value from standardsetup where empid=? ", [$data->empid]);
        $leavebal = $this->coreFunctions->datareader("select sum(bal) as value from leavesetup left join paccount on paccount.line=leavesetup.acnoid where paccount.alias='SIL' and year(dateid)= '$data->yr' and empid=? ", [$data->empid]);

        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Current Loan Balance Amt : ' . number_format($loanbal, 2), '540', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col('', '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->reporter->begintable(500);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('E A R N I N G S', '300', null, false, $border, 'TBL', 'L', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $listdeduc = ['PPBLE', 'YWT', 'DEDUCTION', 'LATE', 'ABSENT'];
        $getearn = $this->dataearn($config, $data->empid, $data->batchid);
        $str .= $this->reporter->begintable(500);
        foreach ($getearn as $key => $val) {
          if ($val->db > 0) {
            if (!in_array($val->alias, $listdeduc)) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '5', '23', false, $border, '', 'C', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->col('' . $val->codename, '310', '23', false, $border, '', 'L', $font, $font_size, '', '', '1.5px');
              if ($val->codename == 'BASIC SALARIES') {
                $val->qty = $val->qty / 8;
              }
              $str .= $this->reporter->col('' . $val->qty == 0 ? '-' : number_format($val->qty, 2), '70', '23', false, $border, '', 'R', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->col('' . $val->db == 0 ? '-' : number_format($val->db, 2), '100', '23', false, $border, '', 'R', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->col('', '5', '23', false, $border, '', 'R', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->endrow();
            }
          }
        }
        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->addlineright_cdo();
        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 405px'>";
        $str .= $this->reporter->begintable(500);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('D E D U C T I O N S', '300', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TBR', 'R', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $deduction = $this->getdeductionbreakdown($config, $data->empid, $data->batchid);
        $str .= $this->reporter->begintable(500);
        foreach ($deduction as $key => $q) {
          if ($q->cr > 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '5', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $q->codename, '310', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            switch ($q->codename) {
              case 'ABSENT':
              case 'LATE':
              case 'UNDERTIME':
                if ($q->codename == 'ABSENT') {
                  $q->qty = $q->qty / 8;
                }
                $str .= $this->reporter->col($q->qty == 0 ? '-' : number_format($q->qty, 2), '70', '18', false, $border, '', 'R', $font, $font_size, '', '', '');
                break;
              default:
                $str .= $this->reporter->col('', '70', '18', false, $border, '', 'R', $font, $font_size, '', '', '');
                break;
            }
            $str .= $this->reporter->col('' . $q->cr == 0 ? '-' : number_format($q->cr, 2), '100', '18', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '5', '18', false, $border, 'R', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        }

        $str .= $this->reporter->endtable();

        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:-10px 0 0 405px'>";
        $str .= $this->addlineright2_cdo();
        $str .= "</div>";


        $str .= "<div style='position:absolute; margin:250px 0 0 -95px'>";
        $str .= $this->reporter->begintable(500);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EARNINGS:', '300', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalearn == 0 ? '-' : number_format($totalearn, 2), '200', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:250px 0 0 405px'>";
        $str .= $this->reporter->begintable(500);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL DEDUCTIONS: ', '300', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalded == 0 ? '-' : number_format($totalded, 2), '200', null, false, $border, 'TBR', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:286px 0 0 -95px'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '500', null, false, $border, 'B', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('NET PAY: ', '300', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('' . $netpay == 0 ? '-' : number_format($netpay, 2), '200', null, false, $border, 'BR', 'R', $font, $font_size, 'B', '', '8px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $j++;
        $str .= "</div>";
        // $str .= "</br></br></br></br></br>";
        // $str .= "</br></br>";
        $str .= "</div>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br>";
        if ($j == 3) {
          $str .= $this->reporter->page_break();
          // $str .= $this->DEFAULT_Header($config);
          $j = 0;
        }

        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $legalun = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;


        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
      }
    } //loop

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function cdo_Layout3($config)
  {
    $result = $this->cdo_qry($config);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '60px; margin-top:10px');
    // $str .= $this->DEFAULT_Header($config);
    $clientname = "";
    $client = "";
    $divname = "";
    $deptname = "";
    $dateid = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $ssscalamityloan = 0;
    $hdmfloan = 0;
    $hdmfcalamityloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $incentives = 0;
    $atu = 0;
    $retroactive = 0;
    $hmo = 0;
    $ridefund = 0;
    $mcunit = 0;
    $debitmemo = 0;
    $pcfloan = 0;
    $cashbond = 0;
    $cola = 0;

    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;
    $deduction = 0;

    $branchname = "";

    $i = 0;
    $c = 0;
    $str .= '<div style="position: relative;margin-top:70px">';
    $str .= $this->reporter->begintable($layoutsize);
    $k = 0;
    foreach ($result as $key => $data) {
      $clientname = $data->clientname;
      $client = $data->client;
      $divname = $data->divname;
      $deptname = $data->deptname;
      $dateid = $data->dateid;
      $branchname = $data->branchname;


      switch ($data->code) {
        case 'PT57': //basic pay
          $basicpay = $basicpay + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          $qtybasicpay = $qtybasicpay + $data->qty;
          break;
        case 'PT31': //Current Responsibility Allowance
        case 'PT4': //Gasoline Allowance
        case 'PT22': //Hazard Allowance
        case 'PT21': //Housing Allowance
        case 'PT24': //Load Allowance
        case 'PT67': //Meal Allowance
        case 'PT79': //COLA
        case 'PT23': //Key Keeper
          $allowance = $allowance + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          break;
        case 'PT304': //Incentives
          $incentives = $incentives + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          break;
        case 'PT15': //Regular OT
          $rot = $rot + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          $qtyrot = $qtyrot + $data->qty;
          break;
        case 'PT16': //Restday
          $restday = $restday + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          $qtyrestday = $qtyrestday + $data->qty;
          break;
        case 'PT8': //Sick Leave
        case 'PT9': //Vacation Leave
        case 'PT58': //Service Incentive Leave
        case 'PT85': //Emergency Leave
        case 'PT86': //Birthday Leave
        case 'PT103': //LEAVE BALANCE
        case 'PT111': //SOLO PARENT LEAVE
        case 'PT112': //PATERNITY LEAVE
        case 'PT113': //MATERNITY LEAVE
        case 'PT114': //BEREAVEMENT LEAVE
        case 'PT115': //UNUSED LEAVE
          $leave = $leave + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          $qtyleave = $qtyleave + $data->qty;
          break;
        case 'PT64': //Special Holiday
        case 'PT105': //Special Holiday 100%
          $special = $special + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          $qtyspecial = $qtyspecial + $data->qty;
          break;
        case 'PT18': //Legal Holiday
          $legal = $legal + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          $qtylegal = $qtylegal + $data->qty;
          break;
        case 'PT303': //Absences/Tardiness/Undertime
          $atu = $atu + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          break;
        case 'PT302': //Retroactive
          $retroactive = $retroactive + $data->db - $data->cr;
          $totalearn = $totalearn + $data->db - $data->cr;
          break;
        case 'PT5': //Absent
          $absent = $absent + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          $qtyabsent = $qtyabsent + $data->qty;
          break;
        case 'PT6': //Late
        case 'PT107': //late penalty
          $late = $late   + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          $qtylate = $qtylate + $data->qty;
          break;
        case 'PT7': //undertime
          $undertime = $undertime  + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          $qtyundertime = $qtyundertime + $data->qty;
          break;
        case 'PT42': //WHT
          $wht = $wht + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT51': //pagibig
          $hdmf = $hdmf + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT48': //philhealth
          $phic = $phic + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT44': //sss
          $sss = $sss + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT12': //sss loan
          $sssloan = $sssloan + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT11': //pagibig loan
          $hdmfloan = $hdmfloan + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT93': //pagibig calamity loan
          $hdmfcalamityloan = $hdmfcalamityloan + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT13': //sss calamity loan
          $ssscalamityloan = $ssscalamityloan + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT71': //HMO
          $hmo = $hmo + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT205': //Ridefund Salary Loan
          $ridefund = $ridefund + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT301': //MC UNIT
          $mcunit = $mcunit + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT116': //PCF Loan
          $pcfloan = $pcfloan + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT206': //Debit Memo
          $debitmemo = $debitmemo + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        case 'PT117': //cash bond
          $cashbond = $cashbond + $data->cr - $data->db;
          $totalded = $totalded + $data->cr - $data->db;
          break;
        default:
          switch ($data->alias) {
            case '13PAY':
            case 'ADJUSTMENT':
            case 'BACKPAY':
            case 'BON':
            case 'EARNINGS':
            case 'EARNINGS1':
            case 'NDIFF':
            case 'SPECIALOT':
            case 'OTHER EARNINGS':
              $otherearnings = $otherearnings + $data->db;
              $totalearn = $totalearn + $data->db;
              break;

            case 'COLA':
              $cola = $cola + $data->db;
              $totalearn = $totalearn + $data->db;
              break;

            case 'CA':
            case 'DEDUCTION':
            case 'LOAN':
            case 'OVERPAYMENT':
            case 'PENALTY':
            case 'NOLOGINPENALTY':
            case 'LATEPENALTY':
            case 'NOOUTPENALTY':
            case 'NOBREAKPENALTY':
            case 'NOUNDERPENALTY':
              $otherdeduction = $otherdeduction + $data->cr;
              $totalded = $totalded + $data->cr;
              break;
            case 'PPBLE':
              $netpay = $netpay + $data->db - $data->cr;
              break;
          }
          break;
      }



      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('P A Y &nbsp S L I P', 1000, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE CODE : ' . strtoupper($client), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $loanbal = $this->coreFunctions->datareader("select sum(balance) as value from standardsetup where empid=? ", [$data->empid]);
        $leavebal = $this->coreFunctions->datareader("select sum(bal) as value from leavesetup left join paccount on paccount.line=leavesetup.acnoid where paccount.alias='SIL' and year(dateid)= '$data->yr' and empid=? ", [$data->empid]);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAYROLL PERIOD : ' . strtoupper($data->startdate) . ' to ' . strtoupper($data->enddate) . ' - ' . strtoupper($data->batch), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('BRANCH :' . $branchname, '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAYROLL DATE : ' . strtoupper($dateid), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('CURRENT LOAN BALANCE : ' . number_format($loanbal, 2), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        ////////
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp E A R N I N G S', '175', null, false, $border, 'TBLR', 'L', $font, 11, 'B', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right;border:hidden;' value='QTY'>", '60', null, false, $border, 'TBR', 'R', $font, 11, 'B', '', '');
        $str .= $this->reporter->col(" <input readonly type='text' style='width: 100%; text-align:right;border:hidden;' value='AMOUNT'>", '90', null, false, $border, 'TBR', 'R', $font, 11, 'B', '', '');
        $str .= $this->reporter->col('&nbsp D E D U C T I O N S', '175', null, false, $border, 'TBLR', 'L', $font, 11, 'B', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right;border:hidden;' value='QTY'>", '60', null, false, $border, 'TBR', 'C', $font, 11, 'B', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right;border:hidden;' value='AMOUNT'>", '90', null, false, $border, 'TBR', 'R', $font, 11, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("&nbsp Basic Pay :", '175', '', false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($qtybasicpay == 0 ? '-' : number_format($qtybasicpay, 2)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($basicpay == 0 ? '-' : number_format($basicpay, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->col('&nbsp Absent : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');

        $blnOffsetLeave = false;

        $print_absentqty = $qtyabsent;
        $print_absent = $absent;
        if ($qtyleave != 0 && $qtyabsent >= $qtyleave) {
          $print_absentqty = $qtyabsent - $qtyleave;
          $print_absent = $absent - $leave;

          $totalded = $totalded - $leave;
          $blnOffsetLeave = true;
        }

        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($print_absentqty == 0 ? '-' : number_format($print_absentqty, 2)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($print_absent == 0 ? '-' : number_format($print_absent, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Allowance : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($allowance == 0 ? '- ' : number_format($allowance, 2) . '&nbsp', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp Late : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($qtylate == 0 ? '-' : number_format($qtylate, 2)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($late == 0 ? '-' : number_format($late, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Incentives : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($incentives == 0 ? '- ' : number_format($incentives, 2) . '&nbsp', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->col('&nbsp Undertime : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($qtyundertime == 0 ? '-' : number_format($qtyundertime, 2)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($undertime == 0 ? '-' : number_format($undertime, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Overtime : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($qtyrot == 0 ? '-' : number_format($qtyrot, 2)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($rot == 0 ? '-' : number_format($rot, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->col('&nbsp WHT : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($wht == 0 ? '-' : number_format($wht, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Leave : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');



        $print_leaveqty = $qtyleave;
        $print_leave = $leave;
        if ($blnOffsetLeave) {
          if ($qtyabsent != 0 && $qtyleave != 0) {
            if ($qtyabsent > $qtyleave) {
              $print_leaveqty = $qtyleave - $qtyleave;
              $print_leave = $leave - $leave;
              $totalearn = $totalearn - $leave;
            } else {
              $print_leaveqty = $qtyleave - $qtyabsent;
              $print_leave = $leave - $absent;
              $totalearn = $totalearn - $absent;
            }
          }
        }

        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($print_leaveqty == 0 ? '-' : number_format($print_leaveqty, 2)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($print_leave == 0 ? '-' : number_format($print_leave, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->col('&nbsp Pag-Ibig Contribution : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($hdmf == 0 ? '-' : number_format($hdmf, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Restday : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($qtyrestday == 0 ? '-' : number_format($qtyrestday, 2)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($restday == 0 ? '-' : number_format($restday, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->col('&nbsp Philhealth Contribution : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($phic == 0 ? '-' : number_format($phic, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Special Holiday : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($qtyspecial == 0 ? '-' : (float)($qtyspecial / 8)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($special == 0 ? '-' : number_format($special, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->col('&nbsp SSS Contribution : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($sss == 0 ? '-' : number_format($sss, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Legal Holiday : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($qtylegal == 0 ? '-' : (float)($qtylegal / 8)) . "'>", '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%; text-align:right; border: hidden;' value='" . ($legal == 0 ? '-' : number_format($legal, 2)) . "'>", '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp Other Loans : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp Adjustment : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp SSS Loan : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($sssloan == 0 ? '-' : number_format($sssloan, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Absences/Tardiness/Undertime : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($atu == 0 ? '-' : number_format($atu, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp SSS Calamity Loan : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($ssscalamityloan == 0 ? '-' : number_format($ssscalamityloan, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Retroactive : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($retroactive == 0 ? '-' : number_format($retroactive, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Pag-Ibig Loan : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Others : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($otherearnings == 0 ? '-' : number_format($otherearnings, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Pag-Ibig Calamity Loan : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($hdmfcalamityloan == 0 ? '-' : number_format($hdmfcalamityloan, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp COLA', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($cola == 0 ? '-' : number_format($cola, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp Other Deductions : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp HMO : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($hmo == 0 ? '-' : number_format($hmo, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Ridefund Salary Loan : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($ridefund == 0 ? '-' : number_format($ridefund, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp MC Unit : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($mcunit == 0 ? '-' : number_format($mcunit, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Debit Memo : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($debitmemo == 0 ? '-' : number_format($debitmemo, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp PCF Loan : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($pcfloan == 0 ? '-' : number_format($pcfloan, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Cash Bond : &nbsp', '175', null, false, $border, 'LR', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($cashbond == 0 ? '-' : number_format($cashbond, 2), '90', null, false, $border, 'R', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LRB', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'RB', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'RB', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp Others : &nbsp', '175', null, false, $border, 'LRB', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'RB', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2), '90', null, false, $border, 'RB', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp TOTAL EARNINGS', '175', null, false, $border, 'LB', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'RB', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($totalearn == 0 ? '-' : number_format($totalearn, 2), '90', null, false, $border, 'LRB', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp TOTAL DEDUCTIONS', '175', null, false, $border, 'LB', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), '90', null, false, $border, 'LRB', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        // // here
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '175', null, false, $border, 'LB', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('&nbsp NET PAY', '175', null, false, $border, 'LB', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'R', $font, '14', '', '', '');
        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), '90', null, false, $border, 'LRB', 'R', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .= "</br></br>";
        $k++;

        if ($k == 2) {
          $str .= $this->reporter->page_break();
          // $str .= $this->DEFAULT_Header($config);
          $k = 0;
        }



        ///////////////////////

        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $legalun = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $ssscalamityloan = 0;
        $hdmfloan = 0;
        $hdmfcalamityloan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $incentives = 0;
        $atu = 0;
        $retroactive = 0;
        $hmo = 0;
        $ridefund = 0;
        $mcunit = 0;
        $debitmemo = 0;
        $pcfloan = 0;
        $cashbond = 0;
        $cola = 0;

        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;


        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
      }
    } //loop

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_Header($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '11';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $batch      = $config['params']['dataparams']['batchid'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    return $str;
  }

  public function DEFAULT_Layout($config)
  {
    $result = $this->DEFAULT_qry($config);

    $companyid = $config['params']['companyid'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->DEFAULT_Header($config);
    $emp = "";

    $clientname = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;

    $i = 0;
    $c = 0;


    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $clientname = $data->clientname;
      $divname = $data->divname;
      $deptname = $data->deptname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        if ($companyid == 43) { //mighty
          $qtylate = $qtylate + $data->qty2;
        } else {
          $qtylate = $qtylate + $data->qty;
        }
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        //$totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }

      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('P A Y &nbsp S L I P', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payroll Period : ' . strtoupper($data->startdate) . ' to ' . strtoupper($data->enddate) . ' - ' . strtoupper($data->batch), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);

        $loanbal = $this->coreFunctions->datareader("select sum(balance) as value from standardsetup where empid=? ", [$data->empid]);
        $leavebal = $this->coreFunctions->datareader("select sum(bal) as value from leavesetup left join paccount on paccount.line=leavesetup.acnoid where paccount.alias='SIL' and year(dateid)= '$data->yr' and empid=? ", [$data->empid]);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Current Loan Balance Amt : ' . number_format($loanbal, 2), '540', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Current Leave Balance Hrs : ' . number_format($leavebal, 2), '460', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('E A R N I N G S', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='QTY'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='AMOUNT'>", '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('D E D U C T I O N S', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='QTY'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='AMOUNT'>", '150', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Basic Pay :", '150', '', false, $border, '', 'L', $font, '11', 'QTY', 'QTY', 'QTY');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtybasicpay == 0 ? '-' : number_format($qtybasicpay, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($basicpay == 0 ? '-' : number_format($basicpay, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Absent : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyabsent == 0 ? '-' : number_format($qtyabsent, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($absent == 0 ? '-' : number_format($absent, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Allowance : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($allowance == 0 ? '-' : number_format($allowance, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Late : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylate == 0 ? '-' : number_format($qtylate, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($late == 0 ? '-' : number_format($late, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Regular OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyrot == 0 ? '-' : number_format($qtyrot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($rot == 0 ? '-' : number_format($rot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Undertime : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyundertime == 0 ? '-' : number_format($qtyundertime, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($undertime == 0 ? '-' : number_format($undertime, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Ndiff OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyndiffot == 0 ? '-' : number_format($qtyndiffot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($ndiffot == 0 ? '-' : number_format($ndiffot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('WHT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($wht == 0 ? '-' : number_format($wht, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyrestday == 0 ? '-' : number_format($qtyrestday, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($restday == 0 ? '-' : number_format($restday, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Pag-Ibig : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($hdmf == 0 ? '-' : number_format($hdmf, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Restday OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyrestdayot == 0 ? '-' : number_format($qtyrestdayot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($restdayot == 0 ? '-' : number_format($restdayot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Other Loans : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($loan == 0 ? '-' : number_format($loan, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special Hol unwork : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyspecialun == 0 ? '-' : number_format($qtyspecialun, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($specialun == 0 ? '-' : number_format($specialun, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('SSS : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($sss == 0 ? '-' : number_format($sss, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special Hol : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyspecial == 0 ? '-' : number_format($qtyspecial, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($special == 0 ? '-' : number_format($special, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Other Deduction : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Special OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyspecialot == 0 ? '-' : number_format($qtyspecialot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($specialot == 0 ? '-' : number_format($specialot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('SSS Loan : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($sssloan == 0 ? '-' : number_format($sssloan, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal Hol unwork : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylegalun == 0 ? '-' : number_format($qtylegalun, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($legalun == 0 ? '-' : number_format($legalun, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Philhealth : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($phic == 0 ? '-' : number_format($phic, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal Hol : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylegal == 0 ? '-' : number_format($qtylegal, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($legal == 0 ? '-' : number_format($legal, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('Pagibig Loan : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Legal OT : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtylegalot == 0 ? '-' : number_format($qtylegalot, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($legalot == 0 ? '-' : number_format($legalot, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Other Earnings : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($otherearnings == 0 ? '-' : number_format($otherearnings, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('13th Month : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($bonus == 0 ? '-' : number_format($bonus, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Leave : &nbsp', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 20%; text-align:right; border: hidden;' value='" . ($qtyleave == 0 ? '-' : number_format($qtyleave, 2)) . "'> <input readonly type='text' style='width: 50%; text-align:right; border: hidden;' value='" . ($leave == 0 ? '-' : number_format($leave, 2)) . "'>", '150', null, false, $border, '', 'R', $font, '11', '', '', '');


        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EARNINGS', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($totalearn == 0 ? '-' : number_format($totalearn, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('TOTAL DEDUCTIONS', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        // here
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, '11', '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '11', '', '', '');
        $str .= $this->reporter->col('NET PAY', '150', null, false, $border, '', 'L', $font, '14', '', '', '');
        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), '150', null, false, $border, '', 'R', $font, '14', '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $tripping = 0;
        $operator = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->DEFAULT_Header($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }



  private function data_assembly($variable_data, $data)
  {
    $alias = $data->alias;
    $db = $data->db;
    $cr = $data->cr;
    $qty = $data->qty;
    $code = $data->code;

    $variable_data["clientname"] = $data->clientname;
    $variable_data["client"] = $data->client;
    $variable_data["divname"] = $data->divname;
    $variable_data["deptname"] = $data->deptname;

    $variable_data["rate"] = $data->rate;

    $variable_data["week_ending"] = $this->get_weekending($data->enddate);
    $variable_data["week"] = $data->week;
    $variable_data["start"] = $data->startdate;
    $variable_data["end"] = $data->enddate;
    $variable_data["ot_hours"] = $this->get_value_from_timesheet($data->empid, $data->batchid, '7', 'qty');
    $variable_data["work_hours"] = $this->get_value_from_timesheet($data->empid, $data->batchid, '13', 'qty');
    $variable_data["under_hours"] = $this->get_value_from_timesheet($data->empid, $data->batchid, '42', 'qty');
    $variable_data["late_hours"] = $this->get_value_from_timesheet($data->empid, $data->batchid, '38', 'qty');
    $variable_data["absent_hours"] = $this->get_value_from_timesheet($data->empid, $data->batchid, '29', 'qty');
    $variable_data["ndiff_hours"] = $this->get_value_from_timesheet($data->empid, $data->batchid, '69', 'qty');

    $variable_data["hourly_rate"] = $data->rate / 8;
    $variable_data["empid"] = $data->empid;

    switch ($alias) {
      case 'BSA':
        $variable_data["basicpay"] = $variable_data["basicpay"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtybasicpay"] = $variable_data["qtybasicpay"] + $qty;

        break;
      case 'ABSENT':
        $variable_data["absent"] = $variable_data["absent"] + ($cr - $db);

        $variable_data["qtyabsent"] = $variable_data["qtyabsent"] + $qty;
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);

        break;
      case 'LATE':
        $variable_data["late"] = $variable_data["late"] + ($cr - $db);

        $variable_data["qtylate"] = $variable_data["qtylate"] + $qty;
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);

        break;
      case 'UNDERTIME':
        $variable_data["undertime"] = $variable_data["undertime"] + ($cr - $db);

        $variable_data["qtyundertime"] = $variable_data["qtyundertime"] + $qty;
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);

        break;
      case 'OTREG':
        $variable_data["rot"] = $variable_data["rot"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyrot"] = $variable_data["qtyrot"] + $qty;

        break;
      case 'NDIFF':
        $variable_data["ndiffot"] = $variable_data["ndiffot"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyndiffot"] = $variable_data["qtyndiffot"] + $qty;

        break;
      case 'ALLOWANCE':
      case 'ALLOWANCE3':
      case 'COLA':
        $variable_data["allowance"] = $variable_data["allowance"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyallowance"] = $variable_data["qtyallowance"] + $qty;

        break;
      case 'SL':
        $variable_data["leave"] = $variable_data["leave"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyleave"] = $variable_data["qtyleave"] + $qty;

        break;
      case 'VL':
        $variable_data["leave"] = $variable_data["leave"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyleave"] = $variable_data["qtyleave"] + $qty;

        break;
      case 'SIL':
        $variable_data["leave"] = $variable_data["leave"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyleave"] = $variable_data["qtyleave"] + $qty;

        break;
      case 'ML':
        $variable_data["leave"] = $variable_data["leave"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyleave"] = $variable_data["qtyleave"] + $qty;

        break;
      case '13PAY':
        $variable_data["bonus"] = $variable_data["bonus"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);

        break;
      case 'PPBLE':
        $variable_data["netpay"] = $variable_data["netpay"] + ($db - $cr);

        break;
      case 'RESTDAY':
        $variable_data["restday"] = $variable_data["restday"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyrestday"] = $variable_data["qtyrestday"] + $qty;

        break;
      case 'RESTDAYOT':
        $variable_data["restdayot"] = $variable_data["restdayot"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyrestdayot"] = $variable_data["qtyrestdayot"] + $qty;

        break;
      case 'OTRES':
        $variable_data["restdayot"] = $variable_data["restdayot"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyrestdayot"] = $variable_data["qtyrestdayot"] + $qty;

        break;
      case 'SP':
        $variable_data["special"] = $variable_data["special"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyspecial"] = $variable_data["qtyspecial"] + $qty;

        break;
      case 'SPUN':
        $variable_data["specialun"] = $variable_data["specialun"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyspecialun"] = $variable_data["qtyspecialun"] + $qty;

        break;
      case 'SPECIALOT':
        $variable_data["specialot"] = $variable_data["specialot"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtyspecialot"] = $variable_data["qtyspecialot"] + $qty;

        break;
      case 'LEG':
        $variable_data["legal"] = $variable_data["legal"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtylegal"] = $variable_data["qtylegal"] + $qty;

        break;
      case 'LEGUN':
        $variable_data["legalun"] = $variable_data["legalun"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtylegalun"] = $variable_data["qtylegalun"] + $qty;

        break;
      case 'LEGALOT':
        $variable_data["legalot"] = $variable_data["legalot"] + ($db - $cr);
        $variable_data["totalearn"] = $variable_data["totalearn"] + ($db - $cr);
        $variable_data["qtylegalot"] = $variable_data["qtylegalot"] + $qty;

        break;
      case 'YWT':
        $variable_data["wht"] = $variable_data["wht"] + ($cr - $db);
        $variable_data["totalded"] = $variable_data["totalded"] + ($cr - $db);

        break;
      case 'YSE':
        $variable_data["sss"] = $variable_data["sss"] + ($cr - $db);
        $variable_data["totalded"] = $variable_data["totalded"] + ($cr - $db);

        break;
      case 'YME':
        $variable_data["phic"] = $variable_data["phic"] + ($cr - $db);
        $variable_data["totalded"] = $variable_data["totalded"] + ($cr - $db);

        break;
      case 'YPE':
        $variable_data["hdmf"] = $variable_data["hdmf"] + ($cr - $db);
        $variable_data["totalded"] = $variable_data["totalded"] + ($cr - $db);

        break;
      case 'LOAN':
        $variable_data["loan"] = $variable_data["loan"] + ($cr - $db);
        $variable_data["totalded"] = $variable_data["totalded"] + ($cr - $db);

        break;
      case 'SSSLOAN':
        $variable_data["sssloan"] = $variable_data["sssloan"] + ($cr - $db);
        $variable_data["totalded"] = $variable_data["totalded"] + ($cr - $db);

        break;
      case 'HDMFLOAN':
        $variable_data["hdmfloan"] = $variable_data["hdmfloan"] + ($cr - $db);
        $variable_data["totalded"] = $variable_data["totalded"] + ($cr - $db);
        break;
      default:
        if (abs($cr) > 0) {
          if ($code == 'PT69') {
            $variable_data["cashadv"] = $variable_data["cashadv"] + $cr;
          } else {
            $variable_data["otherdeduction"] = $variable_data["otherdeduction"] + $cr;
          }
          $variable_data["totalded"] = $variable_data["totalded"] + $cr;
        } elseif ($db > 0) {
          $variable_data["otherearnings"] = $variable_data["otherearnings"] + $db;
          $variable_data["totalearn"] = $variable_data["totalearn"] + $db;
        }
        break;
    }
    return $variable_data;
  }

  private function get_weekending($enddate)
  {
    $week_ending = date("Y-M-d H:i:s", strtotime($enddate));
    $week_ending = date("l", strtotime($week_ending));
    return $week_ending;
  }

  private function get_value_from_timesheet($empid, $batchid, $acno, $field)
  {

    return $this->coreFunctions->datareader("select $field as value from timesheet where empid=? and batchid=? and acnoid=?", [$empid, $batchid, $acno]);
  }

  private function get_value_from_employee($empid, $field)
  {
    return $this->coreFunctions->datareader("select $field as value from employee where empid=?", [$empid]);
  }

  private function get_value_from_jobthead($line, $field)
  {
    return $this->coreFunctions->datareader("select $field as value from jobthead where line=?", [$line]);
  }

  private function get_timecard($clientcount, $empid, $start, $end, $empid2, $start2, $end2)
  {
    if ($clientcount > 1 && $empid2 != "") {
      $timecard = json_decode(json_encode($this->coreFunctions->opentable(
        "
        select * from (
          select 
          dateid, 
          (case when actualin is null then schedin else actualin end) as timein,
          (case when actualout is null then schedout else actualout end) as timeout,
          reghrs as reghrs,
          othrs as othrs, absdays,latehrs,underhrs
          from timecard
          where empid=$empid and 
          dateid between '$start' and '$end'
          order by dateid
        ) as time1
          
        union all
          
        select * from (
          select 
          dateid, 
          (case when actualin is null then schedin else actualin end) as timein,
          (case when actualout is null then schedout else actualout end) as timeout,
          reghrs as reghrs,
          othrs as othrs, absdays,latehrs,underhrs
          from timecard
          where empid=$empid2 and 
          dateid between '$start2' and '$end2'
          order by dateid
        ) as time2"
      )), true);
    } else {
      $timecard = json_decode(json_encode($this->coreFunctions->opentable(
        "
        select * from (
          select 
          dateid, 
          (case when actualin is null then schedin else actualin end) as timein,
          (case when actualout is null then schedout else actualout end) as timeout,
          reghrs as reghrs,
          othrs as othrs, absdays,latehrs,underhrs
          from timecard
          where empid=$empid and 
          dateid between '$start' and '$end'
          order by dateid
        ) as time1"
      )), true);
    }
    return $timecard;
  }


  private function print_timein_layout_payslip_one_two($clientcount, $layoutsize, $border, $dotted, $font, $font_size, $first_data, $second_data)
  {
    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', '30', false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', '30', false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '560', '30', false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name:       ' . strtoupper(isset($first_data["clientname"]) ? $first_data["clientname"] : ''), '300', null, false, $border, 'TL', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->col('Week: ' . date('W', strtotime(isset($first_data["week"]) ? $first_data["week"] : '')), '230', null, false, $border, 'TR', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('Name:       ' . strtoupper(isset($second_data["clientname"]) ? $second_data["clientname"] : ''), '300', null, false, $border, 'TL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('Week: ' . date('W', strtotime(isset($second_data["week"]) ? $second_data["week"] : '')), '230', null, false, $border, 'TR', 'R', $font, $font_size, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $fstart = date("M j, Y", strtotime($first_data["start"]));
    $fend = date("M j, Y", strtotime($first_data["end"]));

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Covering the Period from ' . $fstart . ' to ' . $fend, '380', null, false, $border, 'L', 'L', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col('Week Ending ' . $first_data["week_ending"], '180', null, false, $border, 'R', 'L', $font, $font_size - 2, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {

      $sstart = date("M j, Y", strtotime($first_data["start"]));
      $send = date("M j, Y", strtotime($first_data["end"]));
      $str .= $this->reporter->col('Covering the Period from ' . $sstart . ' to ' . $send, '380', null, false, $border, 'L', 'L', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('Week Ending ' . $second_data["week_ending"], '180', null, false, $border, 'R', 'L', $font, $font_size - 2, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Daily Rate: ' . number_format($first_data["rate"], 2), '300', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '230', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('Daily Rate: ' . number_format($second_data["rate"], 2), '300', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '230', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Workhours', '130', null, false, $border, 'TL', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hourly Rate', '130', null, false, $border, 'TL', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Pay', '200', null, false, $border, 'TLR', 'C', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    if ($clientcount > 1 && $second_data["clientname"] != "") {

      $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Workhours', '130', null, false, $border, 'TL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Hourly Rate', '130', null, false, $border, 'TL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Pay', '200', null, false, $border, 'TLR', 'C', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $fwork_hours = $first_data["work_hours"] - ($first_data["absent_hours"] + $first_data["late_hours"] + $first_data["under_hours"]);
    $fwork_pay = $first_data["basicpay"] - ($first_data["absent"] + $first_data["late"] + $first_data["undertime"]);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Regular', '100', null, false, $border, 'TBL', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($fwork_hours == 0 ? '-' : $fwork_hours, '130', null, false, $border, 'TBL', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($first_data["hourly_rate"] == 0 ? '-' : '₱' . number_format($first_data["hourly_rate"], 2), '130', null, false, $border, 'TBL', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($fwork_pay == 0 ? '-' : '₱' . number_format($fwork_pay, 2), '200', null, false, $border, 'TBLR', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $swork_hours = $second_data["work_hours"] - ($second_data["absent_hours"] + $second_data["late_hours"] + $second_data["under_hours"]);
      $swork_pay = $second_data["basicpay"] - ($second_data["absent"] + $second_data["late"] + $second_data["undertime"]);

      $str .= $this->reporter->col('Regular', '100', null, false, $border, 'TBL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($swork_hours == 0 ? '-' : $swork_hours, '130', null, false, $border, 'TBL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($second_data["hourly_rate"] == 0 ? '-' : '₱' . number_format($second_data["hourly_rate"], 2), '130', null, false, $border, 'TBL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($swork_pay == 0 ? '-' : '₱' . number_format($swork_pay, 2), '200', null, false, $border, 'TBLR', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $ot_modifier = $this->coreFunctions->datareader("select qty as value from paccount where line=7");

    $str .= $this->reporter->begintable($layoutsize);
    //OT START
    $otrow = 0;
    if ($first_data["ot_hours"] != 0) {
      $otrow = 1;
      $fot_amount = $first_data["rot"];
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('OT', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($first_data["ot_hours"] == 0 ? '-' : $first_data["ot_hours"], '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($first_data["hourly_rate"] == 0 ? '-' : '₱' . number_format(($first_data["hourly_rate"]) * $ot_modifier, 2), '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($first_data["rot"] == 0 ? '-' : '₱' . number_format($first_data["rot"], 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $second_data["ot_hours"] != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1) {
      if ($second_data["ot_hours"] != 0) {
        $otrow = 1;
        $sot_amount = $second_data["rot"];
        $str .= $this->reporter->col('OT', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($second_data["ot_hours"] == 0 ? '-' : $second_data["ot_hours"], '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($second_data["hourly_rate"] == 0 ? '-' : '₱' . number_format(($second_data["hourly_rate"]) * $ot_modifier, 2), '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($second_data["rot"] == 0 ? '-' : '₱' . number_format($second_data["rot"], 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');
      } elseif ($second_data["ot_hours"] == 0 && $otrow == 1) {
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
      }
    } else {
      if ($otrow == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($otrow == 1) {
      $str .= $this->reporter->endrow();
    }
    //OT END
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    //ndiff START
    $ndiffrow = 0;
    if ($first_data["ndiffot"] != 0) {
      $ndiffrow = 1;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Ndiff', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($first_data["ndiff_hours"] == 0 ? '-' : $first_data["ndiff_hours"], '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($first_data["ndiffot"] == 0 ? '-' : '₱' . number_format($first_data["ndiffot"], 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $second_data["ndiffot"] != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1) {
      if ($second_data["ndiffot"] != 0) {
        $ndiffrow = 1;
        $str .= $this->reporter->col('Ndiff', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($second_data["ndiff_hours"] == 0 ? '-' : $second_data["ndiff_hours"], '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($second_data["ndiffot"] == 0 ? '-' : '₱' . number_format($second_data["ndiffot"], 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');
      } elseif ($second_data["ndiffot"] == 0 && $ndiffrow == 1) {
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
      }
    } else {
      if ($ndiffrow == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($ndiffrow == 1) {
      $str .= $this->reporter->endrow();
    }
    //ndiff END
    $str .= $this->reporter->endtable();

    $fholidays = $first_data["special"] + $first_data["specialun"] + $first_data["legal"] + $first_data["legalun"];
    $sholidays = $second_data["special"] + $second_data["specialun"] + $second_data["legal"] + $second_data["legalun"];

    $str .= $this->reporter->begintable($layoutsize);
    //holiday START
    $holidayrow = 0;
    if ($fholidays != 0) {
      $holidayrow = 1;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Holiday', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($fholidays == 0 ? '-' : '₱' . number_format($fholidays, 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $sholidays != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1) {

      if ($sholidays != 0) {
        $holidayrow = 1;
        $str .= $this->reporter->col('Holiday', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sholidays == 0 ? '-' : '₱' . number_format($sholidays, 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');
      } elseif ($sholidays == 0 && $holidayrow == 1) {
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
      }
    } else {
      if ($holidayrow == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($holidayrow == 1) {
      $str .= $this->reporter->endrow();
    }
    //holiday END
    $str .= $this->reporter->endtable();

    //sundayot start
    $str .= $this->reporter->begintable($layoutsize);
    $sundayotrow = 0;
    if ($first_data["restdayot"] != 0) {
      $sundayotrow = 1;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sunday OT', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($first_data["restdayot"] == 0 ? '-' : '₱' . number_format($first_data["restdayot"], 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $second_data["restdayot"] != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1) {
      if ($second_data["restdayot"] != 0) {
        $sundayotrow = 1;
        $str .= $this->reporter->col('Sunday OT', '100', null, false, $border, 'BL', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($second_data["restdayot"] == 0 ? '-' : '₱' . number_format($second_data["restdayot"], 2), '200', null, false, $border, 'BLR', 'R', $font, $font_size, '', '', '');
      } elseif ($second_data["restdayot"] == 0 && $sundayotrow == 1) {
        $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
      }
    } else {
      if ($sundayotrow == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($sundayotrow == 1) {
      $str .= $this->reporter->endrow();
    }
    //Sunday OT END
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');


    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');


    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $ftotalearn = $first_data['totalearn'] - ($fwork_pay + $first_data["rot"] + $first_data["ndiffot"] + $fholidays + $first_data["restdayot"]);
    $ftotalded = $first_data['totalded'];
    $fothers = $first_data["allowance"] + ($ftotalearn - $ftotalded);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Incentives/Allowances:', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($fothers == 0 ? '-' : '₱' . number_format($fothers, 2), '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $stotalearn = $second_data['totalearn'] - ($swork_pay + $second_data["rot"] + $second_data["ndiffot"] + $sholidays + $second_data["restdayot"]);
      $stotalded = $second_data['totalded'];
      $sothers = $second_data["allowance"] + ($stotalearn - $stotalded);

      $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Incentives/Allowances:', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($sothers == 0 ? '-' : '₱' . number_format($sothers, 2), '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }

    $str .= $this->reporter->endrow();
    $fgross = $first_data["totalearn"];
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Gross Pay:', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($fgross == 0 ? '-' : '₱' . number_format($fgross, 2), '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $sgross = $second_data["totalearn"];
      $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Gross Pay:', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($sgross == 0 ? '-' : '₱' . number_format($sgross, 2), '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    //deductions start
    $fdeductions = $first_data["totalded"];
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Deductions:', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($fdeductions == 0 ? '-' : '₱' . number_format($fdeductions, 2), '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $sdeductions = $second_data["totalded"];
      $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Deductions:', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($sdeductions == 0 ? '-' : '₱' . number_format($sdeductions, 2), '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    //deductions end


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($first_data["netpay"] == 0 ? '-' : '₱' . number_format($first_data["netpay"], 2), '200', null, false, $border, 'R', 'R', $font, $font_size + 2, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $snet = $sgross - $sdeductions;
      $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($second_data["netpay"] == 0 ? '-' : '₱' . number_format($second_data["netpay"], 2), '200', null, false, $border, 'R', 'R', $font, $font_size + 2, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '15', false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', '15', false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', '15', false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', '15', false, $border, 'R', 'R', $font, $font_size + 2, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $str .= $this->reporter->col('', '100', '15', false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', '15', false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '180', '15', false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', '15', false, $border, 'R', 'R', $font, $font_size + 2, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // //timecard
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Time In', '130', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Time Out', '130', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('OT', '100', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Work Hours', '100', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    if ($clientcount > 1 && $second_data["clientname"] != '') {

      $str .= $this->reporter->col('Date', '100', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Time In', '130', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Time Out', '130', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('OT', '100', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Work Hours', '100', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    $border = '4px solid';

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    $border = '1px solid';
    if ($clientcount > 1 && $second_data["clientname"]) {
      $timecard = $this->get_timecard(
        $clientcount,
        $first_data["empid"],
        $first_data["start"],
        $first_data["end"],
        $second_data["empid"],
        $second_data["start"],
        $second_data["end"]
      );
    } else {
      $timecard = $this->get_timecard(
        $clientcount,
        $first_data["empid"],
        $first_data["start"],
        $first_data["end"],
        '',
        '',
        ''
      );
    }

    if ($clientcount > 1 && $second_data["clientname"] != '') {
      for ($i = 0; $i < count($timecard) - 7; $i++) {

        $fworkhours = $timecard[$i]['reghrs'] - ($timecard[$i]['absdays'] + $timecard[$i]['latehrs'] + $timecard[$i]['underhrs']);
        $sworkhours = $timecard[$i + 7]['reghrs'] - ($timecard[$i + 7]['absdays'] + $timecard[$i + 7]['latehrs'] + $timecard[$i + 7]['underhrs']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($timecard[$i]['dateid'], '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(date('h:iA', strtotime($timecard[$i]['timein'])), '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(date('h:iA', strtotime($timecard[$i]['timeout'])), '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($timecard[$i]['othrs'] == 0 ? '-' : number_format($timecard[$i]['othrs']), '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($fworkhours == 0 ? '-' : number_format($fworkhours, 2), '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col($timecard[$i + 7]['dateid'], '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(date('h:iA', strtotime($timecard[$i + 7]['timein'])), '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(date('h:iA', strtotime($timecard[$i + 7]['timeout'])), '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($timecard[$i + 7]['othrs'] == 0 ? '-' : number_format($timecard[$i + 7]['othrs']), '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sworkhours == 0 ? '-' : number_format($sworkhours, 2), '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
      }
    } else {
      for ($i = 0; $i < count($timecard); $i++) {

        $fworkhours = $timecard[$i]['reghrs'] - ($timecard[$i]['absdays'] + $timecard[$i]['latehrs'] + $timecard[$i]['underhrs']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($timecard[$i]['dateid'], '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(date('h:iA', strtotime($timecard[$i]['timein'])), '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(date('h:iA', strtotime($timecard[$i]['timeout'])), '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($timecard[$i]['othrs'] == 0 ? '-' : number_format($timecard[$i]['othrs']), '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($fworkhours == 0 ? '-' : number_format($fworkhours, 2), '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable($layoutsize);

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '560', null, false, $dotted, 'T', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '560', null, false, $dotted, 'T', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', '20', false, $border, 'LR', 'C', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', '20', false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', '20', false, $border, 'LR', 'C', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', '20', false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('HOUSING Inadlaw', '560', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('HOUSING Inadlaw', '560', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', '50', false, $border, 'LR', 'L', $font, $font_size - 1, 'I', '', '');
    $str .= $this->reporter->col('', '30', '50', false, $border, '', '', $font, $font_size, '', '', '');
    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', '50', false, $border, 'LR', 'L', $font, $font_size - 1, 'I', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', 15, false, $border, 'LR', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->col('', '30', 15, false, $border, '', '', $font, $font_size, '', '', '');
    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', 15, false, $border, 'LR', 'L', $font, '11', '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Net Pay', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($first_data["netpay"] == 0 ? '-' : '₱' . number_format($first_data["netpay"], 2), '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Net Pay', '130', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($second_data["netpay"] == 0 ? '-' : '₱' . number_format($second_data["netpay"], 2), '200', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Received By:', '180', null, false, $border, 'L', 'L', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col('Covering the Period from ' . $first_data["start"] . ' to ' . $first_data["end"], '380', null, false, $border, 'R', 'R', $font, $font_size - 2, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('Received By:', '180', null, false, $border, 'L', 'L', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('Covering the Period from ' . $second_data["start"] . ' to ' . $first_data["end"], '380', null, false, $border, 'R', 'R', $font, $font_size - 2, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($first_data["clientname"]), '300', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->col('', '230', null, false, $border, 'R', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col(strtoupper($second_data["clientname"]), '300', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('', '230', null, false, $border, 'R', 'R', $font, $font_size, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('I acknowledge that I received the amount stated above as my weekly wage.', '560', null, false, $border, 'LR', 'L', $font, $font_size - 1, 'I', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('I acknowledge that I received the amount stated above as my weekly wage.', '560', null, false, $border, 'LR', 'L', $font, $font_size - 1, 'I', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', '50', false, $border, 'T', 'L', $font, $font_size - 1, 'I', '', '');

    $str .= $this->reporter->col('', '30', '50', false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', '50', false, $border, 'T', 'L', $font, $font_size - 1, 'I', '', '');
    } else {
      $str .= $this->reporter->col('', '560', '50', false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function print_manual_layout_payslip_one_two($clientcount, $layoutsize, $border, $dotted, $font, $font_size, $first_data, $second_data)
  {
    $str = '';

    $fpay = 0;
    $spay = 0;
    $fot_amount = 0;
    $sot_amount = 0;
    $fndiff_amount = 0;
    $sndiff_amount = 0;
    $fholidays = 0;
    $sholidays = 0;
    $fsunday_amount = 0;
    $ssunday_amount = 0;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', '30', false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', '30', false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '560', '30', false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($first_data["clientname"]), '300', null, false, $border, 'TL', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->col('Rate: ' . number_format($first_data["rate"], 2), '230', null, false, $border, 'TR', 'L', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col(strtoupper($second_data["clientname"]), '300', null, false, $border, 'TL', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, 'T', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col('Rate: ' . number_format($second_data["rate"], 2), '230', null, false, $border, 'TR', 'L', $font, $font_size, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', 2, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', 2, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $fstart = date("F j", strtotime($first_data["start"]));
    if (substr($first_data["end"], 5, 2) != substr($first_data["start"], 5, 2)) {
      $fend = date("F j, Y", strtotime($first_data["end"]));
    } else {
      $fend = date("j, Y", strtotime($first_data["end"]));
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Period Covered: ', '150', null, false, $border, 'LR', 'L', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col($fstart . ' - ' . $fend, '410', null, false, $border, 'R', 'C', $font, $font_size - 2, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $sstart = date("F j", strtotime($second_data["start"]));
      if (substr($second_data["end"], 5, 2) != substr($second_data["start"], 5, 2)) {
        $send = date("F j, Y", strtotime($second_data["end"]));
      } else {
        $send = date("j, Y", strtotime($second_data["end"]));
      }
      $str .= $this->reporter->col('Period Covered: ', '150', null, false, $border, 'LR', 'L', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($sstart . ' - ' . $send, '410', null, false, $border, 'R', 'C', $font, $font_size - 2, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $femp_job = $this->get_value_from_employee($first_data["empid"], 'jobid');
    $fjob = $this->get_value_from_jobthead($femp_job, 'jobtitle');


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Scope of Work: ', '150', null, false, $border, 'LTR', 'L', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col($fjob, '410', null, false, $border, 'TR', 'C', $font, $font_size - 2, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {

      $semp_job = $this->get_value_from_employee($second_data["empid"], 'jobid');
      $sjob = $this->get_value_from_jobthead($second_data, 'jobtitle');
      $str .= $this->reporter->col('Scope of Work: ', '150', null, false, $border, 'TLR', 'L', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($sjob, '410', null, false, $border, 'TR', 'C', $font, $font_size - 2, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', 2, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', 2, false, $border, 'TLRB', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'LR', 'C', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'LR', 'C', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col('Rate/Day', '75', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');
    $str .= $this->reporter->col('HRS', '75', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');
    $str .= $this->reporter->col('Total Rate/HR', '85', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');


    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {

      $str .= $this->reporter->col('', '150', null, false, $border, 'LR', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'LR', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('Rate/Day', '75', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');
      $str .= $this->reporter->col('HRS', '75', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');
      $str .= $this->reporter->col('Total Rate/HR', '85', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');
      $str .= $this->reporter->col('Amount', '100', null, false, $border, 'LR', 'C', $font, $font_size - 2, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $fworkday = $first_data["work_hours"] - ($first_data["absent_hours"]);
    $fpay = $first_data["basicpay"] - ($first_data["absent"]);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('No. of Days:', '150', null, false, $border, 'TLRB', 'C', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col($fworkday == 0 ? '-' : number_format($fworkday / 8, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col($first_data["rate"] == 0 ? '-' : $first_data["rate"], '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col($fworkday == 0 ? '-' : number_format($fworkday, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col($first_data["rate"] == 0 ? '-' : number_format($first_data["rate"] / 8, 2), '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
    $str .= $this->reporter->col($fpay == 0 ? '-' : number_format($fpay, 2), '100', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');


    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $sworkday = $second_data["work_hours"] - ($second_data["absent_hours"]);
      $spay = $second_data["basicpay"] - ($second_data["absent"]);
      $str .= $this->reporter->col('No. of Days:', '150', null, false, $border, 'TLRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($sworkday == 0 ? '-' : number_format($sworkday / 8, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($second_data["rate"] == 0 ? '-' : $second_data["rate"], '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($sworkday == 0 ? '-' : number_format($sworkday, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($second_data["rate"] == 0 ? '-' : number_format($second_data["rate"] / 8, 2), '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($spay == 0 ? '-' : number_format($spay, 2), '100', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();



    //OT START
    $ot_modifier = $this->coreFunctions->datareader("select qty as value from paccount where line=7");
    $otrow = 0;
    if ($first_data["ot_hours"] != 0) {
      $otrow = 1;
      $str .= $this->reporter->startrow();
      $fot_amount = (($first_data["rate"] / 8) * $ot_modifier) * $first_data["ot_hours"];
      $str .= $this->reporter->col('Overtime:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($first_data["ot_hours"] == 0 ? '-' : number_format($first_data["ot_hours"] / 8, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($first_data["ot_hours"] == 0 ? '-' : number_format($first_data["ot_hours"], 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($first_data["rate"] == 0 ? '-' : '<span style="color:red;">' . number_format(($first_data["rate"] / 8) * $ot_modifier, 2) . '</span>', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($fot_amount == 0 ? '-' : number_format($fot_amount, 2), '100', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');

      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $second_data["ot_hours"] != "") {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'R', $font, $font_size - 2, '', '', '');

        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      if ($second_data["ot_hours"] != 0) {
        $otrow = 1;
        $sot_amount = (($second_data["rate"] / 8) * $ot_modifier) * $second_data["ot_hours"];
        $str .= $this->reporter->col('Overtime:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($second_data["ot_hours"] == 0 ? '-' : number_format($second_data["ot_hours"] / 8, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($second_data["ot_hours"] == 0 ? '-' : number_format($second_data["ot_hours"], 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($second_data["rate"] == 0 ? '-' : number_format(($second_data["rate"] / 8) * $ot_modifier, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($sot_amount == 0 ? '-' : number_format($sot_amount, 2), '100', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      } elseif ($second_data["ot_hours"] == 0 && $otrow == 1) {
        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $font_size - 2, '', '', '');
      }
    } else {
      if ($otrow == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($otrow == 1) {
      $str .= $this->reporter->endrow();
    }
    //OT END


    //NDIFF START
    $ndiff_row = 0;
    if ($first_data["ndiffot"] != 0) {
      $ndiff_row = 1;
      $fndiff_amount = $first_data["ndiffot"];
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('NDiff:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($first_data["ndiff_hours"] == 0 ? '-' : number_format($first_data["ndiff_hours"] / 8, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($first_data["ndiff_hours"] == 0 ? '-' : number_format($first_data["ndiff_hours"], 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($fndiff_amount == 0 ? '-' : number_format($fndiff_amount, 2), '100', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $second_data["ndiffot"] != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      if ($second_data["ndiffot"] != 0) {
        $ndiff_row = 1;
        $sndiff_amount = $second_data["ndiffot"];
        // $sot_amount = (($second_data["rate"]/8)*$ot_modifier)*$second_data["ot_hours"];
        $str .= $this->reporter->col('NDiff:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($second_data["ndiff_hours"] == 0 ? '-' : number_format($second_data["ndiff_hours"] / 8, 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($second_data["ndiff_hours"] == 0 ? '-' : number_format($second_data["ndiff_hours"], 2), '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($sndiff_amount == 0 ? '-' : number_format($sndiff_amount, 2), '100', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      } elseif ($second_data["ndiffot"] == 0 && $ndiff_row == 1) {

        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $font_size - 2, '', '', '');
      }
    } else {
      if ($ndiff_row == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($ndiff_row == 1) {
      $str .= $this->reporter->endrow();
    }
    //NDIFF END

    //HOLIDAY START
    $holiday_row = 0;
    $fholidays = $first_data["special"] + $first_data["specialun"] + $first_data["legal"] + $first_data["legalun"];
    $sholidays = $second_data["special"] + $second_data["specialun"] + $second_data["legal"] + $second_data["legalun"];
    if ($fholidays != 0) {
      $holiday_row = 1;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Holiday:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($fholidays == 0 ? '-' : number_format($fholidays, 2), '100', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $sholidays != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1 && $second_data["clientname"] != "") {

      if ($sholidays != 0) {
        $holiday_row = 1;
        $str .= $this->reporter->col('Overtime:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($sholidays == 0 ? '-' : number_format($sholidays, 2), '100', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      } elseif ($sholidays == 0 && $holiday_row == 1) {

        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $font_size - 2, '', '', '');
      }
    } else {
      if ($holiday_row == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($holiday_row == 1) {
      $str .= $this->reporter->endrow();
    }
    //HOLIDAY END


    //SUNDAY START
    $sunday_row = 0;
    if ($first_data["restdayot"] != 0) {
      $sunday_row = 1;
      $fsunday_amount = $first_data["restdayot"];
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sunday OT:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col($first_data["restdayot"] == 0 ? '-' : number_format($first_data["restdayot"], 2), '100', null, false, $border, 'TRB', 'R', $font, $font_size - 2, '', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
    } else {
      if ($clientcount > 1 && $second_data["restdayot"] != 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');
      }
    }

    if ($clientcount > 1) {

      if ($second_data["restdayot"] != 0) {
        $sunday_row = 1;
        $ssunday_amount = $second_data["restdayot"];
        $str .= $this->reporter->col('Sunday OT:', '150', null, false, $border, 'TBLR', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col($second_data["restdayot"] == 0 ? '-' : number_format($second_data["restdayot"], 2), '100', null, false, $border, 'TRB', 'C', $font, $font_size - 2, '', '', '');
      } elseif ($second_data["restdayot"] == 0 && $sunday_row == 1) {

        $str .= $this->reporter->col('', '150', null, false, $border, 'L', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '85', null, false, $border, '', 'R', $font, $font_size - 2, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $font_size - 2, '', '', '');
      }
    } else {
      if ($sunday_row == 1) {
        $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      }
    }
    if ($sunday_row == 1) {
      $str .= $this->reporter->endrow();
    }
    //SUNDAY END
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Late: ', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($first_data["late"] == 0 ? '-' : number_format($first_data["late"], 2), '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('Late: ', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($second_data["late"] == 0 ? '-' : number_format($second_data["late"], 2), '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Undertime: ', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($first_data["undertime"] == 0 ? '-' : number_format($first_data["undertime"], 2), '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('Undertime: ', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($second_data["undertime"] == 0 ? '-' : number_format($second_data["undertime"], 2), '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    $fdeductions = $first_data["late"] + $first_data["undertime"];
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<span style="color:red;">' . 'Total Deductions: ' . '</span>', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($fdeductions == 0 ? '-' : '<span style="color:red;">' . '-' . number_format($fdeductions, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $sdeductions = $second_data["late"] + $second_data["undertime"];
      $str .= $this->reporter->col('<span style="color:red;">' . 'Total Deductions: ' . '</span>', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($sdeductions == 0 ? '-' : '<span style="color:red;">' . '-' . number_format($sdeductions, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $ftotalearn = $first_data["totalearn"] - ($fpay + $fot_amount + $fndiff_amount + $fholidays + $fsunday_amount);
    $ftotalded = $first_data["totalded"] - ($first_data['late'] + $first_data['undertime']);
    $fothers = $ftotalearn - $ftotalded;
    $f_orientation = '';
    if (($ftotalearn - $ftotalded) < 0) {
      $f_orientation = '-';
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Others: ', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($fothers == 0 ? '-' : $f_orientation . number_format($fothers, 2), '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $stotalearn = $second_data["totalearn"] - ($spay + $sot_amount + $sndiff_amount + $sholidays + $ssunday_amount);
      $stotalded = $second_data["totalded"] - ($second_data['late'] + $second_data['undertime']);
      $sothers = $stotalearn - $stotalded;
      $s_orientation = '';
      if (($stotalearn - $stotalded) < 0) {
        $s_orientation = '-';
      }
      $str .= $this->reporter->col('Others: ', '460', null, false, $border, 'L', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($sothers == 0 ? '-' : $s_orientation . number_format($sothers, 2), '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    $ftotaladjust = $first_data["allowance"] + $fothers;
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total Adjustment: ', '460', null, false, $border, 'L', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($ftotaladjust == 0 ? '-' : '<span style="color:red;">' . number_format($ftotaladjust, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $stotaladjust = $second_data["allowance"] + $sothers;
      $str .= $this->reporter->col('Total Adjustment: ', '460', null, false, $border, 'L', 'R', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($stotaladjust == 0 ? '-' : '<span style="color:red;">' . number_format($stotaladjust, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();

    $fgross = $first_data["totalearn"];
    $fnet = $first_data["netpay"];
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Net Pay: ', '460', null, false, $border, 'L', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($fnet == 0 ? '-' : '<span style="color:red;">' . number_format($fnet, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $sgross = $second_data["totalearn"] + ($spay);
      $snet = $second_data["netpay"];
      $str .= $this->reporter->col('Net Pay: ', '460', null, false, $border, 'L', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($snet == 0 ? '-' : '<span style="color:red;">' . number_format($snet, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', null, false, '3px dotted', 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', null, false, '3px dotted', 'T', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', 80, false, $border, 'LR', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', 80, false, $border, 'LR', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //2nd NETPAY
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Net Pay: ', '460', null, false, $border, 'L', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($fnet == 0 ? '-' : '<span style="color:red;">' . number_format($fnet, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('Net Pay: ', '460', null, false, $border, 'L', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($snet == 0 ? '-' : '<span style="color:red;">' . number_format($snet, 2) . '</span>', '100', null, false, $border, 'R', 'R', $font, $font_size, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Received by: ', '150', null, false, $border, 'L', 'L', $font, $font_size - 2, 'I', '', '');
    $str .= $this->reporter->col($fjob, '410', null, false, $border, 'R', 'L', $font, $font_size - 2, 'BI', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('Received by: ', '150', null, false, $border, 'L', 'L', $font, $font_size - 2, 'I', '', '');
      $str .= $this->reporter->col($sjob, '410', null, false, $border, 'R', 'L', $font, $font_size - 2, 'BI', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '560', 20, false, $border, 'LR', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col('', '560', 20, false, $border, 'LR', '', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($first_data["clientname"]), '200', null, false, $border, 'L', 'L', $font, $font_size, 'I', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->col($fstart . ' - ' . $fend, '330', null, false, $border, 'R', 'L', $font, $font_size, 'I', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {
      $str .= $this->reporter->col(strtoupper($second_data["clientname"]), '200', null, false, $border, 'L', 'L', $font, $font_size, 'I', '', '');
      $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, '11', '', '', '');
      $str .= $this->reporter->col($sstart . ' - ' . $send, '330', null, false, $border, 'R', 'L', $font, $font_size, 'I', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('I Acknowledge that I received the amount stated above as my weekly wage ', '560', null, false, $border, 'BLR', 'C', $font, $font_size - 2, 'I', '', '');

    $str .= $this->reporter->col('', '30', null, false, $border, '', '', $font, $font_size, '', '', '');

    if ($clientcount > 1 && $second_data["clientname"] != "") {

      $str .= $this->reporter->col('I Acknowledge that I received the amount stated above as my weekly wage ', '560', null, false, $border, 'BLR', 'C', $font, $font_size - 2, 'I', '', '');
    } else {
      $str .= $this->reporter->col('', '560', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }

  public function TIMEIN_Layout($config)
  {
    $result = $this->HRS_qry($config);

    $border = '1px solid';
    $dotted = '1px dotted';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 14;

    $pagecount = 4;
    $page = 4;
    $layoutsize = '1150';

    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '15px;margin-top:5px;');

    $i = 0;
    $c = 0;
    $is_to_side = 0;

    $clientcount = count($this->get_distinct_client($this->main_hrs_qry($config)));


    $original_data = [
      "clientname" => "",
      "client" => "",
      "divname" => "",
      "deptname" => "",

      "basicpay" => 0,
      "absent" => 0,
      "late" => 0,
      "undertime" => 0,
      "rot" => 0,
      "ndiffot" => 0,
      "leave" => 0,
      "restday" => 0,
      "restdayot" => 0,
      "special" => 0,
      "specialot" => 0,
      "specialun" => 0,
      "legal" => 0,
      "legalot" => 0,
      "legalun" => 0,
      "wht" => 0,
      "sss" => 0,
      "phic" => 0,
      "hdmf" => 0,
      "loan" => 0,
      "sssloan" => 0,
      "hdmfloan" => 0,
      "cashadv" => 0,
      "bonus" => 0,
      "otherearnings" => 0,
      "otherdeduction" => 0,
      "allowance" => 0,
      "netpay" => 0,
      "totalearn" => 0,
      "totalded" => 0,
      "totalbalances" => 0,

      "qtybasicpay" => 0,
      "qtyabsent" => 0,
      "qtylate" => 0,
      "qtyundertime" => 0,
      "qtyrot" => 0,
      "qtyndiffot" => 0,
      "qtyleave" => 0,
      "qtyrestday" => 0,
      "qtyrestdayot" => 0,
      "qtyspecial" => 0,
      "qtyspecialot" => 0,
      "qtylegal" => 0,
      "qtylegalot" => 0,
      "qtyspecialun" => 0,
      "qtylegalun" => 0,
      "qtyallowance" => 0,

      "rate" => 0,
      "week_ending" => "",
      "week" => "",
      "start" => "",
      "end" => "",
      "ot_hours" => "",
      "work_hours" => "",
      "under_hours" => "",
      "late_hours" => "",
      "absent_hours" => "",
      "ndiff_hours" => "",

      "hourly_rate" => "",
      "empid" => ""


    ];


    $first_data = $second_data = $original_data;

    $count = 0;
    foreach ($result as $key => $data) {


      //check if data set is only for one person
      if ($clientcount > 1) {
        //determine what array the data should fall on
        if ($is_to_side == 0) {
          $first_data = $this->data_assembly($first_data, $data);
        } else {
          $second_data = $this->data_assembly($second_data, $data);
        }
      } else {
        $first_data = $this->data_assembly($first_data, $data);
      }

      //get all acno entries of the employee
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      //while $c as count of all acno entries per employee is not equal to $i, no printing
      //when it triggers this if condition, only then would it print per two payslip

      if ($i == $c) {
        $i = 0;
        $c = 0;

        if ($clientcount > 1) {
          $count++;
          switch ($is_to_side) {
            case 0:
              if ($count == $clientcount) {
                $str .= $this->print_timein_layout_payslip_one_two($clientcount, $layoutsize, $border, $dotted, $font, $font_size, $first_data, $second_data);

                $str .= $this->reporter->addline();

                //reset data
                $first_data = $second_data = $original_data;
              }

              $is_to_side = 1;
              break;

            case 1:
              $is_to_side = 0;

              //print layout
              $str .= $this->print_timein_layout_payslip_one_two($clientcount, $layoutsize, $border, $dotted, $font, $font_size, $first_data, $second_data);


              $str .= $this->reporter->addline();

              $str .= $this->reporter->addline();



              //reset data
              $first_data = $second_data = $original_data;


              if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->page_break();
                $page = $page + $pagecount;
              }
              break;
          }
        } else {
          //print layout
          $str .= $this->print_timein_layout_payslip_one_two($clientcount, $layoutsize, $border, $dotted, $font, $font_size, $first_data, $second_data);

          $str .= $this->reporter->addline();


          //reset data
          $first_data = $second_data = $original_data;
        }
      }
    }

    $str .= $this->reporter->endreport();

    return $str;
  }


  public function MANUAL_Layout($config)
  {
    $result = $this->HRS_qry($config);

    $border = '1px solid';
    $dotted = '1px dotted';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 14;

    $pagecount = 4;
    $page = 4;
    $layoutsize = '1150';

    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '15px;margin-top:5px;');

    $i = 0;
    $c = 0;
    $is_to_side = 0;


    $clientcount = count($this->get_distinct_client($this->main_hrs_qry($config)));


    $original_data = [
      "clientname" => "",
      "client" => "",
      "divname" => "",
      "deptname" => "",

      "basicpay" => 0,
      "absent" => 0,
      "late" => 0,
      "undertime" => 0,
      "rot" => 0,
      "ndiffot" => 0,
      "leave" => 0,
      "restday" => 0,
      "restdayot" => 0,
      "special" => 0,
      "specialot" => 0,
      "specialun" => 0,
      "legal" => 0,
      "legalot" => 0,
      "legalun" => 0,
      "wht" => 0,
      "sss" => 0,
      "phic" => 0,
      "hdmf" => 0,
      "loan" => 0,
      "sssloan" => 0,
      "hdmfloan" => 0,
      "cashadv" => 0,
      "bonus" => 0,
      "otherearnings" => 0,
      "otherdeduction" => 0,
      "allowance" => 0,
      "netpay" => 0,
      "totalearn" => 0,
      "totalded" => 0,
      "totalbalances" => 0,

      "qtybasicpay" => 0,
      "qtyabsent" => 0,
      "qtylate" => 0,
      "qtyundertime" => 0,
      "qtyrot" => 0,
      "qtyndiffot" => 0,
      "qtyleave" => 0,
      "qtyrestday" => 0,
      "qtyrestdayot" => 0,
      "qtyspecial" => 0,
      "qtyspecialot" => 0,
      "qtylegal" => 0,
      "qtylegalot" => 0,
      "qtyspecialun" => 0,
      "qtylegalun" => 0,
      "qtyallowance" => 0,

      "rate" => 0,
      "week_ending" => "",
      "week" => "",
      "start" => "",
      "end" => "",
      "ot_hours" => "",
      "work_hours" => "",
      "under_hours" => "",
      "late_hours" => "",
      "absent_hours" => "",
      "ndiff_hours" => "",

      "hourly_rate" => "",
      "empid" => ""


    ];

    $first_data = $second_data = $original_data;

    $count = 0;

    foreach ($result as $key => $data) {

      //check if data set is only for one person
      if ($clientcount > 1) {
        //determine what array the data should fall on
        if ($is_to_side == 0) {
          $first_data = $this->data_assembly($first_data, $data);
        } else {
          $second_data = $this->data_assembly($second_data, $data);
        }
      } else {
        $first_data = $this->data_assembly($first_data, $data);
      }

      //get all acno entries of the employee
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      //while $c as count of all acno entries per employee is not equal to $i, no printing
      //when it triggers this if condition, only then would it print per two payslip
      if ($i == $c) {
        $i = 0;
        $c = 0;

        if ($clientcount > 1) {
          $count++;
          switch ($is_to_side) {
            case 0:
              if ($count == $clientcount) { //if count is for last client

                //print payslip for leftside(once), since its the last


                $str .= $this->print_manual_layout_payslip_one_two(
                  $clientcount,
                  $layoutsize,
                  $border,
                  $dotted,
                  $font,
                  $font_size,
                  $first_data,
                  $second_data
                );

                $str .= $this->reporter->addline();


                //reset data
                $first_data = $second_data = $original_data;
              }

              $is_to_side = 1;
              break;

            case 1:
              $is_to_side = 0;

              //print layout
              //print one and two payslip(left and right)


              $str .= $this->print_manual_layout_payslip_one_two($clientcount, $layoutsize, $border, $dotted, $font, $font_size, $first_data, $second_data);
              $str .= $this->reporter->addline();
              $str .= $this->reporter->addline();





              //reset data
              $first_data = $second_data = $original_data;


              if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->page_break();
                $page = $page + $pagecount;
              }
              break;
          }
        } else {
          //print once(left), since its only one


          $str .= $this->print_manual_layout_payslip_one_two($clientcount, $layoutsize, $border, $dotted, $font, $font_size, $first_data, $second_data);
          $str .= $this->reporter->addline();



          //reset data
          $first_data = $second_data = $original_data;
        }
      }
    }

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function stonepro_Header($config, $data, $division)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Calibri';
    $font_size = '11';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $batch      = $config['params']['dataparams']['batchid'];

    $str = '';
    $layoutsize = '1100';
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', $font, '13', '', '', '', 0, '', 0, 5);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'c', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px solid ', '', 'c', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('' . strtoupper($division), null, null, false, $border, '', '', $font, '20', 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<div style="position: relative;">';
    $str .= "<div style='position:absolute; margin:-70px 0 0 890px'>";
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Period Start: &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[0]->startdate, '600', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Period End: &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[0]->enddate, '600', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Pay Frequency: &nbsp&nbsp&nbsp&nbsp' . $data[0]->paymode, '600', null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= "</div>";
    $str .= "</div>";
    return $str;
  }
  public function stonepro_Layout($config)
  {
    $result = $this->stonepro_qry($config);
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Calibri';
    $font_size = '7.5';
    $padding = '';
    $margin = '';
    $bg = '#6793df';
    $color = '#ffffff';

    $count = 1;
    $page = 1;
    $layoutsize = '1100';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '60px; margin-top:10px');
    $emp = "";
    $clientname = "";
    $divname = "";
    $deptname = "";
    $position = "";
    $taxcode = "";
    $alias = "";
    $daysw = 0;
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;

    $i = 0;
    $c = 0;
    $b = 0;
    $str .= '<div style="position: relative;">';
    $str .= $this->reporter->begintable($layoutsize);
    $layout = ($layoutsize / 3) - 10;
    foreach ($result as $key => $data) {

      // $str .= $this->reporter->addline();
      $clientname = $data->clientname;
      $empcode = $data->client;
      $divname = $data->divname;
      $deptname = $data->deptname;
      $position = $data->position;

      $ssscode = $data->sss;
      $tincode = $data->tin;
      $phiccode = $data->phic;
      $hdmfcode = $data->hdmf;
      $taxcode = $data->teu;
      $nodeps = $data->nodeps;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtylate = $qtylate + $data->qty;
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        // var_dump($totalearn);
        $str .= $this->stonepro_Header($config, $result, $divname);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Name: ', '110', null, false, '1px solid', 'T', 'L', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . strtoupper($clientname), '100', null, false, '1px solid', 'T', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Department: ', '110', null, false, '1px solid', 'T', 'L', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . strtoupper($deptname), '100', null, false, '1px solid', 'T', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Code: ', '110', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . strtoupper($empcode), '100', null, false, '1px solid', 'B', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Position : ', '110', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . strtoupper($position), '100', null, false, '1px solid', 'B', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SSS', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TIN', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('HDMF', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('PHIC', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Tax Code', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . $ssscode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('' . $tincode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('' . $hdmfcode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('' . $phiccode, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('' . $taxcode . '' . ($nodeps == 0 ? '' : '-' . $nodeps), '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);

        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EARNINGS', '360', null, $bg, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Description', '90', null, false, $border, 'LRB', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Days Work', '90', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Hours', '90', null, false, $border, 'LRB', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Current', '90', null, false, $border, 'LRB', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layout);
        //CHECKING
        $dataearn = $this->dataearn($config, $data->empid, $data->batchid);
        $j = 1;
        $basicp = 0;
        $array = ['LATE', 'DEDUCTION', 'ABSENT', 'UNDERTIME', 'CA'];
        $accoun_list = ['PT56']; //payable
        $leave = ['PT58', 'PT8', 'PT9', 'PT85', 'PT103', 'PT107'];
        $lqty = 0;
        $lamt = 0;
        $td = 0;
        $ltqty = 0;

        $total = array_reduce($dataearn, function ($qty, $row) use ($leave) { //qty starting point to zero
          if (in_array($row->code, $leave)) {
            return $qty + $row->qty;
          }
          return $qty;
        }, 0);
        foreach ($dataearn as $key2 => $data2) {
          if ($data->empid == $data2->empid) {
            $alias = $data2->alias;
            $code = $data2->code;
            $codename = $data2->codename;
            // if (in_array($alias, $deduc) || in_array($code, $deduc)) {
            //   goto last;
            // }
            if (in_array($alias, $array)) {
              if ($data2->db > 0) {
                $basicp = $basicp + $data2->db;
              } else if ($data2->cr > 0) {
                $basicp = $basicp + $data2->cr;
                $td += $data2->cr;
              }
            } else {
              if ($data2->db > 0) {
                $basicp = $basicp + $data2->db;
              } else {

                if ($data2->db < 0) {
                  $basicp = $basicp + $data2->db;
                  $td += ($data2->db) * -1;
                } else {
                  if ($data2->cr > 0) {
                    $basicp = $basicp + $data2->cr;
                  }
                }
              }
            }
            if (in_array($code, $leave)) {
              $lqty += $data2->qty * 8;
              $lamt += $data2->db;
            }
            if (in_array($code, $accoun_list)) {
              goto last;
            }
            if ($data2->uom == 'PESO') {
              goto display;
            }
            if ($data2->qty != 0) {
              display:
              $dataqty = $data2->qty;
              if ($code == 'PT5') { // absent
                $data2->qty = $data2->qty - $lqty;
                $basicp = $basicp - $lamt;
              }
              $daysw = $data2->qty * 8;
              if ($data2->alias == 'BSA') {
                if ($total != 0) {
                  $data2->qty -= ($total * 8);
                }

                goto hrs;
              }
              if ($data2->uom == 'PESO') {
                if ($data2->qty == 0) {
                  $dataqty = 0;
                  $daysw = 0;
                }
              }

              if ($data2->uom == 'HRS') {
                hrs:
                $dataqty = $data2->qty / 8;
                $daysw = $data2->qty;
                $this->coreFunctions->LogConsole("-------");
                $this->coreFunctions->LogConsole("Your:" . $total . '-' . $dataqty . '----' . $daysw);
              }

              if ($j != count($dataearn)) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('' . $codename, '90', null, false, $border, 'L', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col($dataqty != 0 ? number_format($dataqty, 2) : '', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col($daysw != 0 ? number_format($daysw, 2) : '', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col($basicp != '' ? (in_array($alias, $array) || $basicp < 0 ? '(' . number_format(abs($basicp), 2) . ')' : number_format($basicp, 2)) : '', '90', null, false, $border, 'R', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->endrow();
              } else {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('' . $codename, '90', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col($dataqty != 0 ? number_format($dataqty, 2) : '', '90', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col($daysw != 0 ? number_format($daysw, 2) : '', '90', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col($basicp != '' ? (in_array($alias, $array) || $basicp < 0 ? '(' . number_format(abs($basicp), 2) . ')' : number_format($basicp, 2)) : '', '90', null, false, $border, 'RB', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->endrow();
              }
            } else {
              last:
              if (count($dataearn) == $j) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '90', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->col('', '90', null, false, $border, 'RB', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
                $str .= $this->reporter->endrow();
              }
            }
            $j++;
          }
          $codename = "";
          $code = "";
          $alias = "";
          $basicp = 0;
        }
        $totaldduc = $this->gettotalded($config, $data->empid, $data->batchid);
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 276px'>";
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CURRENT TOTALS', '360', null, $bg, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Earnings', '120', null, false, $border, 'LRB', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Deductions', '120', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Net Pay', '120', null, false, $border, 'LRB', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $totalearn = $totalearn - $td;
        $npay = $totalearn - $totaldduc;
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . number_format($totalearn, 2), '120', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . number_format($totaldduc, 2), '120', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . number_format($npay, 2), '120', null, false, $border, 'RB', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";



        $str .= "<div style='position:absolute; margin:-10px 0 0 648px'>";
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEDUCTIONS', '360', null, $bg, $border, 'TLR', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DESCRIPTION', '180', null, false, $border, 'TBL', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('CURRENT', '180', null, false, $border, 'TBLR', 'R', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layout);

        $deduction = $this->getdeduction($config, $data->empid, $data->batchid);
        $g = 0;
        foreach ($deduction as $key => $ded) {
          $g++;
          if ($ded->cr > 0) {
            $str .= $this->reporter->startrow();

            if (count($deduction) != $g) {
              $str .= $this->reporter->col('' . $ded->codename, '120', null, false, $border, 'L', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->col('' . number_format($ded->cr, 2), '120', null, false, $border, 'R', 'R', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
            } else {
              $str .= $this->reporter->col('' . $ded->codename, '120', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->col('' . number_format($ded->cr, 2), '120', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
            }
            $str .= $this->reporter->endrow();
          }
        }
        $str .= $this->reporter->endtable();
        $str .= "</div>";


        $str .= "<div style='position:absolute;margin:83px 0 0 276px'>";
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LEAVES', '360', null, $bg, $border, 'TLRB', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Leave Types', '180', null, false, $border, 'LB', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Balance', '180', null, false, $border, 'RB', 'R', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layout);
        $getleave = $this->getleave($data->empid);
        if (!empty($getleave)) {
          $s = 0;
          foreach ($getleave as $h => $leave) {
            if ($leave->bal != 0) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('' . $leave->leavtype, '180', null, false, $border, 'L', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->col('' . number_format($leave->bal, 2), '180', null, false, $border, 'R', 'R', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->endrow();
            }

            if (count($getleave) - 1 == $s) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '180', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->col('', '180', null, false, $border, 'RB', 'R', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->endrow();
            }
            $s++;
          }
        }
        $str .= $this->reporter->endtable();
        $str .= "</div>";


        $str .= "<div style='position:absolute; margin:120px 0 0 648px'>";
        $str .= $this->reporter->begintable($layout);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOANS', '360', null, $bg, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layout);


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Loan Type', '120', null, false, $border, 'LRB', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Loan Amount', '80', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Amount Paid', '80', null, false, $border, 'LRB', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Balance', '80', null, false, $border, 'LRB', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layout);
        $getloan = $this->getloans($config, $data->empid, $data->batchid);
        if (!empty($getloan)) {
          $k = 0;
          foreach ($getloan as $gt => $loan) {

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('' . $loan->codename, '120', null, false, $border, 'L', 'L', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
            $str .= $this->reporter->col('' . number_format($loan->amt, 2), '80', null, false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
            $str .= $this->reporter->col('' . number_format($loan->cr, 2), '80', null, false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
            $str .= $this->reporter->col('' . number_format($loan->balance, 2), '80', null, false, $border, 'R', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
            $str .= $this->reporter->endrow();
            if ((count($getloan) - 1) == $k) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '120', null, false, $border, 'BL', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->col('', '80', null, false, $border, 'BR', 'C', $font, $font_size, '', '', '', '', 0, '', 0, 0, $bg);
              $str .= $this->reporter->endrow();
            }
            $k++;
          }
        }
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:290px 0 0 -95px'>";

        // $layout2  = $layoutsize - 80;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DIRECT DEPOSIT', '360', null, $bg, $border, 'TLRB', 'C', $font, $font_size, 'B', '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Bank Account', '275', null, false, $border, 'BL', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Account Number', '275', null, false, $border, 'BL', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Account Type', '275', null, false, $border, 'BL', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('Amount', '275', null, false, $border, 'BLR', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Metrobank', '275', null, false, $border, 'BL', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . $data->bankacct, '275', null, false, $border, 'BL', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('SA', '275', null, false, $border, 'BL', 'L', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->col('' . number_format($npay, 2), '275', null, false, $border, 'BLR', 'C', $font, $font_size, 'B',  '', '', '', 0, '', 0, 0, $bg);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= "<br><br><br><br><br><br><br><br>";
        $str .= "</div>";
        $str .= "<br><br>";
        if (count($result) != $b + 1) {
          $str .= $this->reporter->page_break();
        }
        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $legalun = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $cashadv = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $qtyallowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;
        $totalbalances = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
        $qtyallowance = 0;
      }
      $b++;
    } //loop
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function ulitc_Header($config, $data, $division)
  {

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '11';
    $str = '';
    $layoutsize = '1100';
    $str .= "<br><br><br>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('' . strtoupper($division), null, null, false, $border, 'LBT', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CONFIDENTIAL', null, null, false, $border, 'BTR', 'R', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function ulitc_Layout($config)
  {
    $result = $this->ulitc_qry($config);
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '8.5';
    $count = 1;
    $page = 1;
    $pagecount = 0;
    $layoutsize = '1100';

    $str = '';
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= '<div style="position: relative;">';
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '60px; margin-top:10px');
    $clientname = "";
    $divname = "";
    $deptname = "";
    $datepaeriod = "";
    $paydate = "";
    $daysw = 0;
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;

    $i = 0;
    $c = 0;
    $b = 0;

    foreach ($result as $key => $data) {
      $clientname = strtoupper($data->clientname);
      $codename = $data->codename;
      $divname = $data->divname;
      $deptname = $data->deptname;
      $position = $data->position;
      $paymode = $data->paymode;
      $paydate = $data->dateid;
      $batch = $data->batch;
      $datepaeriod = $data->startdate . ' to ' . $data->enddate;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtylate = $qtylate + $data->qty;
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }
      $pagecount++;
      $i++;
      $totalgross = 0;
      $totaldeduction = 0;

      if ($i == $c) {

        $str .= $this->ulitc_Header($config, $data, $divname);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME : ', '100', null, false, $border, 'L', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('' . $clientname, '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('PAYROLL PERIOD : ', '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($datepaeriod . '-' . $batch, '250', null, false, $border, 'R', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEPARTMENT : ', '100', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('' . $deptname, '250', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('PAY DATE : ', '100', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('' . $paydate, '250', null, false, $border, 'BR', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EARNINGS', '230', null, false, $border, 'LBR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('DAYS/HR', '100', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('DEDUCTIONS', '130', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('DAYS/HR', '90', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('AMOUNT', '90', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('YTD', '90', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('E A R N I N G S &nbsp; B R E A K D O W N', '270', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= "<div style='position:absolute;margin: 0 0 0 0;'>";

        $str .= $this->reporter->begintable(430);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BASIC RATE', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BASIC', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ALLOWANCE TAX', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ALLOWANCE N-TAX', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OTH EARNINGS TAX', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OTH EARNINGS N-TAX', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('VL /  SL / OL', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REG NIGHT DIFF', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('UNPAID LEAVE', '230', '', false, $border, '', 'L', $font, '7.8', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $j = 0;
        $str .= "<div style='position:absolute;margin: 0 0 0 230px;'>";
        $str .= $this->reporter->begintable(200);
        $basichrs = 0;
        $basicamt = 0;

        $altaxamt = 0;
        $alntaxamt = 0;

        $othtaxamt = 0;
        $othntaxamt = 0;

        $vlhrs = 0;
        $slhrs = 0;
        $clhrs = 0;
        $clamt = 0;
        $ulamt = 0;
        $ulhrs = 0;
        $tbasicamt = 0;
        $tvlhrs = 0;
        $tvlamt = 0;

        $earnings = $this->getearnings($data->empid, $data->batchid);
        $f = false;
        foreach ($earnings as $key2 => $data2) {

          if (isset($data2->alias)) {
            if (strpos(trim($data2->alias), 'BSA') !== false) {
              $basichrs += $data2->hrs;
              $basicamt += $data2->basic;
              $tbasicamt += $basicamt;
            }

            if (strpos(trim($data2->alias), 'ALLOWANCE') !== false) {
              if ($data2->istax == 1) {
                $altaxamt += $data2->basic;
              } else {
                $alntaxamt += $data2->basic;
              }
            }

            if (strpos(trim($data2->alias), 'OTHER EARNING') !== false) {
              if ($data2->istax == 1) {
                $othtaxamt += $data2->basic;
              } else {
                $othntaxamt += $data2->basic;
              }
            }
            $otherLv = ['PT58', 'PT85', 'PT86', 'PT103', 'PT107', 'PT154', 'PT196', 'PT9', 'PT8'];
            if (strpos(trim($data2->alias), 'SL') !== false || strpos(trim($data2->alias), 'VL') !== false || in_array($data2->code, $otherLv)) {
              if ($data2->alias == 'SL') {
                $slhrs += $data2->hrs;
              }
              if ($data2->alias == 'VL') {
                $vlhrs += $data2->hrs;
              }
              if ($data2->code == 'PT58') {
                $clhrs += $data2->hrs;
              }
              if ($data2->code == 'PT85') {
                $clhrs += $data2->hrs;
              }
              if ($data2->code == 'PT86') {
                $clhrs += $data2->hrs;
              }
              if ($data2->code == 'PT103') {
                $clhrs += $data2->hrs;
              }
              if ($data2->code == 'PT107') {
                $clhrs += $data2->hrs;
              }
              if ($data2->code == 'PT154') {
                $clhrs += $data2->hrs;
              }
              if ($data2->code == 'PT196') {
                $clhrs += $data2->hrs;
              }

              $tvlhrs += ($vlhrs + $clhrs + $slhrs);
              $tvlamt += $data2->basic;
            }

            $ulleae = ['PT191', 'PT190', 'PT189'];
            #'PT191','PT190','PT189' EARNINGS
            if (strpos(trim($data2->code), 'PT191') !== false || strpos(trim($data2->code), 'PT190') !== false || in_array($data2->code, $ulleae)) {
              $ulamt += $data2->basic;
              $ulhrs += $data2->hrs;
            }
          }
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . $data2->paymode, '100', null, false, $border, '', 'C', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col('' . $data2->basicrate, '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col('' . number_format($tbasicamt, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col('' . number_format($altaxamt, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col('' . number_format($alntaxamt, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col('' . number_format($othtaxamt, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col('' . number_format($othntaxamt, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . number_format($vlhrs, 2) . '/' . number_format($slhrs, 2) . '/' . number_format($clhrs, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col('' . number_format($tvlamt, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('-', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col('-', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($ulhrs != 0 ? number_format($ulhrs, 2) : '', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($ulamt != 0 ? number_format($ulamt, 2) : '', '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= "</div>";

        $str .= "<div style='position:absolute;margin: 0 0 0 430px;'>";
        $getdeduction = $this->getdeduction($config, $data->empid, $data->batchid);
        $godedection = $this->godedection($config, $data->empid, $data->batchid);

        $ytdlist = ['PT42', 'PT44', 'PT48', 'PT51'];
        $ytd = 0;
        $str .= $this->reporter->begintable(400);
        $subdeduction = 0;
        $totald = 0;
        $dedgros = 0;

        $qqty = 0;
        $qqcr = 0;

        $qqcrl = 0;
        $qqtyl = 0;

        $qqtyun = 0;
        $qqcrun = 0;

        $ytdsssamt = 0;
        $ytdwhtamt = 0;
        $ytdpagibigamt = 0;
        $ytdphealthamt = 0;

        $dedgrossss = 0;
        $dedgrosphealth = 0;
        $dedgrospagibig = 0;
        $dedgroswht = 0;

        $abstotal = 0;
        foreach ($godedection as $dkey => $dvalue) {
          $ytdphealthamt = $dvalue->phil;
          $ytdpagibigamt = $dvalue->pagibig;
          $ytdsssamt = $dvalue->sss;
          $ytdwhtamt = $dvalue->wtholdtax;
        }

        foreach ($getdeduction as $ov => $valded) {
          $str .= $this->reporter->startrow();
          if (in_array($valded->code, $ytdlist)) {
            if ($valded->code == 'PT44') { // SSS EMP
              $dedgrossss = $valded->cr;
            }
            if ($valded->code == 'PT42') { // WTHOLDING TAX
              $dedgroswht = $valded->cr;
            }
            if ($valded->code == 'PT48') { // PHILHEALTH EMP
              $dedgrosphealth = $valded->cr;
            }
            if ($valded->code == 'PT51') { // PAGIBIG EMP
              $dedgrospagibig += $valded->cr;
              // if ($valded->cr >= 200) {
              //   $dedgrospagibig += 200;
              // }
            }
            $subdeduction += $valded->cr;
          } else {
            if ($valded->code == 'PT5') { // absent
              $qqty = $valded->qty != 0 ? ($valded->qty / 8) - $tvlhrs : 0; //- $tvlhrs
              $qqcr = $valded->cr - $tvlamt; //- $tvlamt
              $abstotal = $valded->cr;
            } else if ($valded->code == 'PT6') { // late
              $qqtyl = $valded->qty;
              $qqcrl = $valded->cr;
            } else {
              $qqtyun = $valded->qty; // undertime
              $qqcrun = $valded->cr;
            }
            $totald += $valded->cr;
          }

          $str .= $this->reporter->endrow();
        }



        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SSS', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($dedgrossss != 0 ? number_format($dedgrossss, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($ytdsssamt != 0 ? number_format($ytdsssamt, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PHILHEALTH', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($dedgrosphealth != 0 ? number_format($dedgrosphealth, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($ytdphealthamt != 0 ? number_format($ytdphealthamt, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAGIBIG', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($dedgrospagibig != 0 ? number_format($dedgrospagibig, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($ytdpagibigamt != 0 ? number_format($ytdpagibigamt, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WTAX(S)', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($dedgroswht != 0 ? number_format($dedgroswht, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($ytdwhtamt != 0 ? number_format($ytdwhtamt, 2) : '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ABSENT (day/s)', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->col($qqty != 0 ? number_format($qqty, 2) : '', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($qqcr != 0 ? number_format($qqcr, 2) :  '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LATES (hrs)', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->col($qqtyl != 0 ? number_format($qqtyl, 2) :  '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($qqcrl != 0 ? number_format($qqcrl, 2) :  '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('UNDERTIME (hrs)', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->col($qqtyun != 0 ? number_format($qqtyun, 2) :  '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '', '');
        $str .= $this->reporter->col($qqcrun != 0 ? number_format($qqcrun, 2) :  '-', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= "<div style='position:absolute;margin: 0 0 0 830px;'>";
        $earningbreakd = $this->getearningsbreakdown($config, $data->empid, $data->batchid);
        $earningtot = 0;
        $cco = 0;
        $str .= $this->reporter->begintable(270);
        if (!empty($earningbreakd)) {
          foreach ($earningbreakd as $ebk => $earbk) {
            $cco++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($earbk->codename, '170', null, false, $border, 'R', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
            $str .= $this->reporter->col(number_format($earbk->earning, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
            $str .= $this->reporter->endrow();
            $earningtot += $earbk->earning;
            if (count($earningbreakd) == $cco) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('TOTAL EARNINGS BREAKDOWN: ', '170', null, false, $border, 'T', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
              $str .= $this->reporter->col(number_format($earningtot, 2), '100', null, false, $border, 'T', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
              $str .= $this->reporter->endrow();
            }
          }
        }
        $str .= $this->reporter->endtable();
        $str .= "</div>";


        $str .= $this->reporter->begintable(1100.24);
        for ($l = 1; $l <= 9; $l++) {
          if ($l == 9) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '230', null, false, $border, 'LRB', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'RB', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'RB', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->col('LOANS', '130', null, false, $border, 'TB', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->col('# of PAY', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->col('AMOUNT', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->col('BALANCE(S)', '90', null, false, $border, 'TBR', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->col('D E D U C T I O N S &nbsp;&nbsp B R E A K D O W N', '270', null, false, $border, 'TBR', 'C', $font, $font_size, 'B',  '', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '230', null, false, $border, 'LR', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->col('', '130', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '8.5px', '');
            $str .= $this->reporter->endrow();
          }
        }
        $str .= $this->reporter->endtable();
        $str .= "<div style='position:absolute;margin: 42px 0 0 0;'>";


        $str .= $this->reporter->begintable(430.5);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REGULAR OT', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REGULAR HOLIDAY', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SPECIAL HOLIDAY', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAY OFF', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DO / REG HOLIDAY', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DO / SP HOLIDAY', '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= "<div style='position:absolute;margin: 42px 0 0 150px;'>";
        //overtime breakdown
        $getovertime = $this->getovertimebreakdown($config, $data->empid, $data->batchid);

        $ot = 0;
        $otx = 0;
        $nd = 0;
        $ndx = 0;
        $ott = 0;
        $otbgrandtotal = 0;

        // regot
        $rgot = 0;
        $rgotx = 0;
        $rgnd = 0;
        $rgndx = 0;
        $rgamt = 0;
        // special holiday ot
        $spgot = 0;
        $spgotx = 0;
        $spgnd = 0;
        $spgndx = 0;
        $spgamt = 0;
        // reg holiday ot
        $rghot = 0;
        $rghotx = 0;
        $rghnd = 0;
        $rghndx = 0;
        $rghamt = 0;
        // res ot
        $resot = 0;
        $resotx = 0;
        $resnd = 0;
        $resndx = 0;
        $resamt = 0;
        // dayoff reg-h ot
        $doreghot = 0;
        $doreghotx = 0;
        $doreghnd = 0;
        $doreghndx = 0;
        $doreghamt = 0;
        // dayoff spc-h ot
        $dospchot = 0;
        $dospchotx = 0;
        $dospchnd = 0;
        $dospchndx = 0;
        $dospchamt = 0;


        $str .= $this->reporter->begintable(430.5);
        foreach ($getovertime as $ov => $overtime) {
          $regot = ['PT15', 'PT142', 'PT76', 'PT148'];
          if (in_array($overtime->code, $regot)) { // regot
            if ($overtime->code == 'PT15') {
              $rgot += $overtime->otqty;
            }
            if ($overtime->code == 'PT142') {
              $rgotx += $overtime->otxqty;
            }
            if ($overtime->code == 'PT76') {
              $rgnd += $overtime->ndqty;
            }
            if ($overtime->code == 'PT148') {
              $rgndx += $overtime->ndxty;
            }
            $rgamt += $overtime->otb;
          }
          $spot = ['PT64', 'PT81', 'PT143', 'PT149'];
          if (in_array($overtime->code, $spot)) { // spc-h
            if ($overtime->code == 'PT64') {
              $spgot += $overtime->otqty;
            }
            if ($overtime->code == 'PT81') {
              $spgotx += $overtime->otxqty;
            }
            if ($overtime->code == 'PT143') {
              $spgnd += $overtime->ndqty;
            }
            if ($overtime->code == 'PT149') {
              $spgndx += $overtime->ndxqty;
            }
            $spgamt += $overtime->otb;
          }
          $regh = ['PT18', 'PT80', 'PT144', 'PT150'];
          if (in_array($overtime->code, $regh)) { // reg-h
            if ($overtime->code == 'PT18') {
              $rghot += $overtime->otqty;
            }
            if ($overtime->code == 'PT80') {
              $rghotx += $overtime->otxqty;
            }
            if ($overtime->code == 'PT144') {
              $rghnd += $overtime->ndqty;
            }
            if ($overtime->code == 'PT150') {
              $rghndx += $overtime->ndxqty;
            }
            $rghamt += $overtime->otb;
          }
          $res_ot = ['PT16', 'PT17', 'PT145', 'PT151'];
          if (in_array($overtime->code, $res_ot)) { // res ot
            if ($overtime->code == 'PT16') {
              $resot += $overtime->otqty;
            }
            if ($overtime->code == 'PT17') {
              $resotx += $overtime->otxqty;
            }
            if ($overtime->code == 'PT145') {
              $resnd += $overtime->ndqty;
            }
            if ($overtime->code == 'PT151') {
              $resndx += $overtime->ndxqty;
            }
            $resamt += $overtime->otb;
          }
          $doregh = ['PT82', 'PT87', 'PT146', 'PT152'];
          if (in_array($overtime->code, $doregh)) { // dayoff reg-h
            if ($overtime->code == 'PT82') {
              $doreghot += $overtime->otqty;
            }
            if ($overtime->code == 'PT87') {
              $doreghotx += $overtime->otxqty;
            }
            if ($overtime->code == 'PT146') {
              $doreghnd += $overtime->ndqty;
            }
            if ($overtime->code == 'PT152') {
              $doreghndx += $overtime->ndxqty;
            }
            $doreghamt += $overtime->otb;
          }
          $dosph = ['PT83', 'PT88', 'PT147', 'PT153'];
          if (in_array($overtime->code, $dosph)) { // dayoff spc-h
            if ($overtime->code == 'PT83') {
              $dospchot += $overtime->otqty;
            }
            if ($overtime->code == 'PT88') {
              $dospchotx += $overtime->otxqty;
            }
            if ($overtime->code == 'PT147') {
              $dospchnd += $overtime->ndqty;
            }
            if ($overtime->code == 'PT153') {
              $dospchndx += $overtime->ndxqty;
            }
            $dospchamt += $overtime->otb;
          }
          $ot = $rgot + $spgot + $rghot + $resot + $doreghot + $dospchot;
          $otx = $rgotx + $spgotx + $rghotx + $resotx + $doreghotx + $dospchotx;
          $nd = $rgnd + $spgnd + $rghnd + $resnd + $doreghnd + $dospchnd;
          $ndx = $rgndx + $spgndx + $rghndx + $resndx + $doreghndx + $dospchndx;
          $ott = $rgamt + $spgamt + $rghamt + $resamt + $doreghamt + $dospchamt;;
          $otbgrandtotal = $ott;
        }

        $str .= $this->reporter->begintable(280.5);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($rgot != 0 ? number_format($rgot, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rgotx != 0 ? number_format($rgotx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rgnd != 0 ? number_format($rgnd, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rgndx != 0 ? number_format($rgndx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rgamt != 0 ? number_format($rgamt, 2) : '', '60', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($rghot != 0 ? number_format($rghot, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rghotx != 0 ? number_format($rghotx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rghnd != 0 ? number_format($rghnd, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rghndx != 0 ? number_format($rghndx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($rghamt != 0 ? number_format($rghamt, 2) : '', '60', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($spgot != 0 ? number_format($spgot, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($spgotx != 0 ? number_format($spgotx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($spgnd != 0 ? number_format($spgnd, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($spgndx != 0 ? number_format($spgndx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($spgamt != 0 ? number_format($spgamt, 2) : '', '60', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($resot != 0 ? number_format($resot, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($resotx != 0 ? number_format($resotx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($resnd != 0 ? number_format($resnd, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($resndx != 0 ? number_format($resndx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($resamt != 0 ? number_format($resamt, 2) : '', '60', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($doreghot != 0 ? number_format($doreghot, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($doreghotx != 0 ? number_format($doreghotx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($doreghnd != 0 ? number_format($doreghnd, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($doreghndx != 0 ? number_format($doreghndx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($doreghamt != 0 ? number_format($doreghamt, 2) : '', '60', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($dospchot != 0 ? number_format($dospchot, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($dospchotx != 0 ? number_format($dospchotx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($dospchnd != 0 ? number_format($dospchnd, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($dospchndx != 0 ? number_format($dospchndx, 2) : '', '55', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->col($dospchamt != 0 ? number_format($dospchamt, 2) : '', '60', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute;'>";
        $str .= $this->reporter->begintable(430 - 1100.24);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('O V E R T I M E &nbsp;&nbsp B R E A K D O W N', '430', null, false, $border, 'LBR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable(430 - 1100.24);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OVERTIME', '150', null, false, $border, 'LBR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('OT', '55', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('OT>8', '55', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');

        $str .= $this->reporter->col('ND', '55', null, false, $border, 'LBR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('ND>8', '55', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('TOTAL', '60', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable(430 - 1100.24);
        for ($f = 0; $f <= 6; $f++) {

          if ($f == 6) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL OT: ', '150', null, false, $border, 'BTLR', 'L', $font, '7.5', '',  '', '', '');
            $str .= $this->reporter->col('' . $ot != 0 ? number_format($ot, 2) : '', '55', null, false, $border, 'TBR', 'R', $font, '7.5', '',  '', '', '');
            $str .= $this->reporter->col('' . $otx != 0 ? number_format($otx, 2) : '', '55', null, false, $border, 'TBR', 'R', $font, '7.5', '',  '', '', '');
            $str .= $this->reporter->col('' . $nd != 0 ? number_format($nd, 2) : '', '55', null, false, $border, 'TBR', 'R', $font, '7.5', '',  '', '', '');
            $str .= $this->reporter->col('' . $ndx != 0 ? number_format($ott, 2) : '', '55', null, false, $border, 'TBR', 'R', $font, '7.5', '',  '', '', '');
            $str .= $this->reporter->col('' . $otbgrandtotal != 0 ? number_format($otbgrandtotal, 2) : '', '60', null, false, $border, 'TBR', 'R', $font, '7.5', '',  '', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '150', null, false, $border, 'LR', 'L', $font, $font_size, '',  '', '10px', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'R', $font, $font_size, '',  '', '10px', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'R', $font, $font_size, '',  '', '10px', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'R', $font, $font_size, '',  '', '10px', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'R', $font, $font_size, '',  '', '10px', '');
            $str .= $this->reporter->col('', '60', null, false, $border, 'R', 'R', $font, $font_size, '',  '', '10px', '');
            $str .= $this->reporter->endrow();
          }
        }
        $str .= $this->reporter->endtable();

        $str .= "</div>";

        $str .= "<div style='position:absolute;margin: 0 0 0 429.5px;'>";
        $str .= $this->reporter->begintable(400.24);
        $j = 0;
        for ($p = 0; $p <= 8; $p++) {
          if ($p == 8) {

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '9.2px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '9.2px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '9.2px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '9.2px', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '130', null, false, $border, '', 'C', $font, $font_size, 'B',  '', '10px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, 'B',  '', '10px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, 'B',  '', '10px', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '10px', '');
            $str .= $this->reporter->endrow();
          }
        }
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= "<div style='position:absolute;margin: 2px 0 0 429.5px;'>";
        //loandata
        $str .= $this->reporter->begintable(400.24);

        $loandata = $this->getloans($config, $data->empid, $data->batchid);
        $loantotol = 0;
        $str .= $this->reporter->begintable(400.24);
        foreach ($loandata  as $key3 => $loanap) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('' . $loanap->codename, '130', null, false, $border, '', 'L', $font, '7.5', '',  '', '', '');
          $str .= $this->reporter->col('' . number_format($loanap->no_pay, 2), '90', null, false, $border, '', 'C', $font, '7.5', '',  '', '', '');
          $str .= $this->reporter->col('' . number_format($loanap->amtpaid, 2), '90', null, false, $border, '', 'C', $font, '7.5', '',  '', '', '');
          $str .= $this->reporter->col('' . number_format($loanap->balance, 2), '100', null, false, $border, '', 'C', $font, '7.5', '',  '', '', '');
          $str .= $this->reporter->endrow();
          $loantotol += $loanap->amtpaid;
        }
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute;margin: 0 0 0 830px;'>";
        $str .= $this->reporter->begintable(270.24);
        for ($p = 0; $p <= 8; $p++) {
          if ($p == 8) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '170', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '9.2px', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '9.2px', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '170', null, false, $border, '', 'C', $font, $font_size, 'B',  '', '10px', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '10px', '');
            $str .= $this->reporter->endrow();
          }
        }
        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute;margin: 2px 0 0 830px;'>";
        // deduction breakdown
        $deducbk = $this->getdeductionbreakdown($config, $data->empid, $data->batchid);
        $ttdeduc = 0;
        $str .= $this->reporter->begintable(270.24);
        foreach ($deducbk   as $key3 => $debk) {
          if ($debk->cr > 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('' . $debk->codename, '170', null, false, $border, '', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
            $str .= $this->reporter->col('' . number_format($debk->cr, 2), '100', null, false, $border, '', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
            $str .= $this->reporter->endrow();
          }
          $ttdeduc += $debk->cr;
        }
        if ($ttdeduc > 0) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('TOTAL DEDUCTION BREAKDOWN', '170', null, false, $border, 'T', 'L', $font, '7.5', '',  '', '0 0 0 5px', '');
          $str .= $this->reporter->col('' . number_format($ttdeduc, 2), '100', null, false, $border, 'T', 'R', $font, '7.5', '',  '', '0 5px 0 0', '');
          $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        $str .= "</div>";



        $str .= "<div style='position:absolute;margin: 179px 0 0 0;'>";
        $totaldeduction = $subdeduction + $loantotol + $ttdeduc;
        $totalgross = ($tbasicamt + $earningtot + $otbgrandtotal + $tvlamt) - $totald;

        $taxable = $this->taxableincome($config, $data->empid, $data->batchid);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL GROSS: ', '230', null, false, $border, 'L', 'L', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col(number_format($totalgross, 2), '100', null, false, $border, 'R', 'R', $font, $font_size, 'B',  '', '', '');

        $str .= $this->reporter->col('TOTAL DEDUCTION: ', '130', null, false, $border, '', 'L', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col(number_format($totaldeduction, 2), '90', null, false, $border, 'R', 'R', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $totolincome = $totalgross - $totaldeduction;
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TAXABLE INCOME', '230', null, false, $border, 'LB', 'L', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col(number_format($taxable[0]->taxable, 2), '100', null, false, $border, 'RB', 'R', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'BR', 'C', $font, $font_size, 'B',  '', '', '');
        $str .= $this->reporter->col('N E T P A Y :', '100', null, false, $border, 'B', 'L', $font, '12', 'B',  '', '', '');
        $str .= $this->reporter->col(number_format($totolincome, 2), '170', null, false, $border, 'BR', 'LC', $font, '12', 'B',  '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";
        $str .= '<br/><br/><br/>';
        $str .= '<br/><br/><br/>';
        $str .= '<br/><br/>';
        $b++;
        if ($b == 3) {
          if (count($result) != $pagecount) {
            $str .= $this->reporter->page_break();
          }
          $b = 0;
        }
        $totaldeduc = 0;
        $totalloan = 0;



        $otbgrandtotal = 0;
        $earningtot = 0;
        $totald = 0;
        $tbasicamt = 0;
        $totalgross = 0;
        $totaldeduction = 0;

        $totolincome = 0;
        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $cashadv = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $qtyallowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;
        $totalbalances = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
        $qtyallowance = 0;
      }
      // $b++;

    }

    $str .= $this->reporter->endreport();
    $str .= '</div>';
    return $str;
  }
  public function gettotalded($config, $empid, $batchid)
  {
    $totaldeduction = 0.0;
    $deduction = $this->getdeduction($config, $empid, $batchid);
    if (!empty($deduction)) {
      foreach ($deduction as $key => $value) {
        $totaldeduction += $value->cr;
      }
    }

    $getloan = $this->getloans($config, $empid, $batchid);
    if (!empty($getloan)) {
      foreach ($getloan as $key2 => $value2) {
        $totaldeduction += $value2->cr;
      }
    }
    return $totaldeduction;
  }
  public function camera_Layout_old($config)
  {
    $result = $this->DEFAULT_qry($config);
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '7';
    $padding = '';
    $margin = '';

    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '60px; margin-top:10px');
    $str .= $this->DEFAULT_Header($config);
    $clientname = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;

    $i = 0;
    $c = 0;
    $str .= '<div style="position: relative;">';
    $str .= $this->reporter->begintable($layoutsize);
    $j = 0;
    foreach ($result as $key => $data) {
      $clientname = $data->clientname;
      $divname = $data->divname;
      $deptname = $data->deptname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtylate = $qtylate + $data->qty;
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($divname), '400', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('P A Y S L I P', '270', null, false, $border, 'R', 'C', $font, 13, 'B', '', '');
        $str .= $this->reporter->col('', '330', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME: ' . strtoupper($clientname), '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '320', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEPARTMENT: ' . strtoupper($deptname), '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '320', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PERIOD COVERED : ' . strtoupper($data->startd) . ' to ' . strtoupper($data->endd) . ' - ' . strtoupper($data->batch), '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '330', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->reporter->begintable(340);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EARNINGS', '340', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $listdeduc = ['PPBLE', 'YWT', 'DEDUCTION', 'LATE', 'ABSENT'];
        $getearn = $this->dataearn($config, $data->empid, $data->batchid);

        $str .= $this->reporter->begintable(340);
        foreach ($getearn as $key => $val) {
          if ($val->db > 0) {
            if (!in_array($val->alias, $listdeduc)) {
              if (strpos($val->codename, 'ALLOWANCE') !== false) {
                $val->qty = 0;
              }
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '5', '23', false, $border, '', 'C', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->col('' . $val->codename, '160', '23', false, $border, '', 'L', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->col('' . $val->qty == 0 ? '-' : number_format($val->qty, 2), '70', '23', false, $border, '', 'R', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->col('' . $val->db == 0 ? '-' : number_format($val->db, 2), '100', '23', false, $border, '', 'R', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->col('', '5', '23', false, $border, '', 'R', $font, $font_size, '', '', '1.5px');
              $str .= $this->reporter->endrow();
            }
          }
        }
        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->addlinerigth();
        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 245px'>";
        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEDUCTIONS', '330', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $deduction = $this->getdeductionbreakdown($config, $data->empid, $data->batchid);
        $str .= $this->reporter->begintable(330);
        foreach ($deduction as $key => $q) {
          if ($q->cr > 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '5', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $q->codename, '150', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $q->qty == 0 ? '-' : number_format($q->qty, 2), '70', '18', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $q->cr == 0 ? '-' : number_format($q->cr, 2), '100', '18', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '5', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        }

        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 245px'>";
        $str .= $this->addline();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:-98px 0 0 585px'>";
        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '340', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Received From', '340', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($divname), '340', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br><br>";

        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('My salaries for the period of', '180', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($data->startd) . ' to ' . strtoupper($data->endd) . ' - ' . strtoupper($data->batch), '300', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "<br>";
        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Amounting to', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Php ' . number_format($netpay, 2), '230', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:394px 0 0 -95px'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EARNINGS:', '140', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalearn == 0 ? '-' : number_format($totalearn, 2), '200', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('TOTAL DEDUCTIONS: ', '180', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalded == 0 ? '-' : number_format($totalded, 2), '150', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Received by:', '310', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '140', null, false, $border, 'B', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('NET PAY: ', '180', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('' . $netpay == 0 ? '-' : number_format($netpay, 2), '150', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '8px');

        $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('' . strtoupper($clientname), '320', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $j++;
        $str .= "</div>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br></br>";
        $str .= "</div>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br>";
        if ($j == 2) {
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_Header($config);
          $j = 0;
        }

        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $legalun = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;


        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
      }
    } //loop

    $str .= $this->reporter->endreport();

    return $str;
  }
  public function addlinerigth()
  {
    $border = '1px solid black';
    $font = 'Century Gothic';
    $fontsize = '5';
    $str = '';
    $str .= $this->reporter->begintable(200);
    for ($i = 0; $i < 23; $i++) {
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('', '340', '25px', false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '5', '14.5', false, $border, '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '65', '14.5', false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '65', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '5', '14.5', false, $border, 'R', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function addlineright_cdo()
  {
    $border = '1px solid black';
    $font = 'Century Gothic';
    $fontsize = '10';
    $str = '';
    $str .= $this->reporter->begintable(500);
    for ($i = 0; $i < 16; $i++) {
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('', '340', '25px', false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '5', '20.8', false, $border, 'L', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '360', '20.8', false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '70', '20.8', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', '20.8', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '5', '20.8', false, $border, 'R', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function addlineright2_cdo()
  {
    $border = '1px solid black';
    $font = 'Century Gothic';
    $fontsize = '10';
    $str = '';
    $str .= $this->reporter->begintable(500);
    for ($i = 0; $i < 16; $i++) {
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('', '340', '25px', false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '5', '20.8', false, $border, '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '360', '20.8', false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '70', '20.8', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', '20.8', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '5', '20.8', false, $border, 'R', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function addline()
  {
    $border = '1px solid black';
    $font = 'Century Gothic';
    $fontsize = '5';
    $str = '';

    $str .= $this->reporter->begintable(200);
    for ($i = 0; $i < 23; $i++) {
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('', '330', '25px', false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '5', '14.5', false, $border, '', '', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '150', '14.5', false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '70', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '5', '14.5', false, $border, 'R', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  }
  private function camera_Header($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '7';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $batch      = $config['params']['dataparams']['batchid'];

    $str = '';
    $layoutsize = '600';

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->name, '500', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '500', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '', 0, '', 0, 5);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), '500', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '', 0, '', 0, 5);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    return $str;
  }
  public function camera_Layout($config)
  {
    $result = $this->camera_query($config);
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '5';
    $padding = '';
    $margin = '';

    $layoutsize = '600';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '60px; margin-top:10px');
    $str .= $this->camera_Header($config);
    $clientname = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;

    $i = 0;
    $c = 0;
    $str .= '<div style="position: relative;">';
    $str .= $this->reporter->begintable($layoutsize);
    $j = 0;
    foreach ($result as $key => $data) {
      $clientname = $data->clientname;
      $divname = $data->divname;
      $deptname = $data->deptname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtylate = $qtylate + $data->qty;
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($divname), '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('P A Y S L I P', '200', null, false, $border, 'R', 'C', $font, 13, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME: ' . strtoupper($clientname), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEPARTMENT: ' . strtoupper($deptname), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PERIOD COVERED : ' . strtoupper($data->startd) . ' to ' . strtoupper($data->endd) . ' - ' . strtoupper($data->batch), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->reporter->begintable(200);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EARNINGS', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $listdeduc = ['PPBLE', 'YWT', 'DEDUCTION', 'LATE', 'ABSENT'];
        $getearn = $this->dataearn($config, $data->empid, $data->batchid);

        $str .= $this->reporter->begintable(200);
        foreach ($getearn as $key => $val) {
          if ($val->db > 0) {
            if (!in_array($val->alias, $listdeduc)) {
              if (strpos($val->codename, 'ALLOWANCE') !== false) {
                $val->qty = 0;
              }
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '5', '14.5', false, $border, '', 'C', $font, $font_size, '', '', '1px');
              $str .= $this->reporter->col('' . $val->codename, '65', '14.5', false, $border, '', 'LT', $font, $font_size, '', '', '1px');
              $str .= $this->reporter->col('' . $val->qty == 0 ? '-' : number_format($val->qty, 2), '60', '14.5', false, $border, '', 'RT', $font, $font_size, '', '', '1px');
              $str .= $this->reporter->col('' . $val->db == 0 ? '-' : number_format($val->db, 2), '65', '14.5', false, $border, '', 'RT', $font, $font_size, '', '', '1px');
              $str .= $this->reporter->col('', '5', '14.5', false, $border, '', 'R', $font, $font_size, '', '', '1px');
              $str .= $this->reporter->endrow();
            }
          }
        }
        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->addlinerigth();
        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 105px'>";
        $str .= $this->reporter->begintable(200);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEDUCTIONS', '200', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $deduction = $this->getdeductionbreakdown($config, $data->empid, $data->batchid);
        $str .= $this->reporter->begintable(200);
        foreach ($deduction as $key => $q) {
          if ($q->cr > 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '5', '14.5', false, $border, '', 'L', $font, $font_size, '', '', '1px');
            $str .= $this->reporter->col('' . $q->codename, '65', '14.5', false, $border, '', 'L', $font, $font_size, '', '', '1px');
            $str .= $this->reporter->col('' . $q->qty == 0 ? '-' : number_format($q->qty, 2), '60', '14.5', false, $border, '', 'R', $font, $font_size, '', '', '1px');
            $str .= $this->reporter->col('' . $q->cr == 0 ? '-' : number_format($q->cr, 2), '65', '14.5', false, $border, '', 'R', $font, $font_size, '', '', '1px');
            $str .= $this->reporter->col('', '5', '14.5', false, $border, '', 'L', $font, $font_size, '', '', '1px');
            $str .= $this->reporter->endrow();
          }
        }

        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 105px'>";
        $str .= $this->addline();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:-80px 0 0 315px'>";
        $str .= $this->reporter->begintable(200);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Received From', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($divname), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br><br>";

        $str .= $this->reporter->begintable(200);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('My salaries for the period of', '180', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable(200);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($data->startd) . ' to ' . strtoupper($data->endd) . ' - ' . strtoupper($data->batch), '300', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "<br>";
        $str .= $this->reporter->begintable(200);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Amounting to', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Php ' . number_format($netpay, 2), '100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:295px 0 0 -95px'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EARNINGS:', '100', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalearn == 0 ? '-' : number_format($totalearn, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('TOTAL DEDUCTIONS: ', '100', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalded == 0 ? '-' : number_format($totalded, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Received by:', '180', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('NET PAY: ', '100', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('' . $netpay == 0 ? '-' : number_format($netpay, 2), '100', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '8px');

        $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('' . strtoupper($clientname), '190', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $j++;
        $str .= "</div>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br></br>";
        $str .= "</div>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br>";
        if ($j == 2) {
          $str .= $this->reporter->page_break();
          $str .= $this->DEFAULT_Header($config);
          $j = 0;
        }

        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $legalun = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;


        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
      }
    } //loop

    $str .= $this->reporter->endreport();

    return $str;
  }
  public function onesky_Layout($config)
  {
    $result = $this->DEFAULT_qry($config);
    $companyid = $config['params']['companyid'];
    $logotype = $config['params']['dataparams']['reporttype'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '7';
    $padding = '';
    $margin = '';

    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '60px; margin-top:10px');
    // $str .= $this->DEFAULT_Header($config);

    // $str .= $this->reporter->begintable(330);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('', '330', 10, false, $border, '', 'C', $font, $font_size, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    // $str .= "</br>";
    $clientname = "";
    $divname = "";
    $deptname = "";
    $basicpay = 0;
    $absent = 0;
    $late = 0;
    $undertime = 0;
    $rot = 0;
    $ndiffot = 0;
    $leave = 0;
    $restday = 0;
    $restdayot = 0;
    $special = 0;
    $specialot = 0;
    $specialun = 0;
    $legal = 0;
    $legalot = 0;
    $legalun = 0;
    $wht = 0;
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $loan = 0;
    $sssloan = 0;
    $hdmfloan = 0;
    $bonus = 0;
    $otherearnings = 0;
    $otherdeduction = 0;
    $allowance = 0;
    $tripping = 0;
    $operator = 0;
    $netpay = 0;
    $totalearn = 0;
    $totalded = 0;

    $qtybasicpay = 0;
    $qtyabsent = 0;
    $qtylate = 0;
    $qtyundertime = 0;
    $qtyrot = 0;
    $qtyndiffot = 0;
    $qtyleave = 0;
    $qtyrestday = 0;
    $qtyrestdayot = 0;
    $qtyspecial = 0;
    $qtyspecialot = 0;
    $qtylegal = 0;
    $qtylegalot = 0;
    $qtyspecialun = 0;
    $qtylegalun = 0;

    $i = 0;
    $c = 0;
    $page = 0;
    $str .= '<div style="position: relative;">';
    $str .= $this->reporter->begintable($layoutsize);
    $j = 0;
    foreach ($result as $key => $data) {
      $clientname = $data->clientname;
      $divname = $data->divname;
      $deptname = $data->deptname;
      $page = $page + 1;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtylate = $qtylate + $data->qty;
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == '13PAY') {
        $bonus = $bonus + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'PPBLE') {
        $netpay = $netpay + $data->db - $data->cr;
      } elseif ($data->alias == 'RESTDAY') {
        $restday = $restday + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestday = $qtyrestday + $data->qty;
      } elseif ($data->alias == 'RESTDAYOT') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'OTRES') {
        $restdayot = $restdayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrestdayot = $qtyrestdayot + $data->qty;
      } elseif ($data->alias == 'SP') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $specialun = $specialun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialun = $qtyspecialun + $data->qty;
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legalun = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalun = $qtylegalun + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'YWT') {
        $wht = $wht + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YSE') {
        $sss = $sss + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YME') {
        $phic = $phic + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'YPE') {
        $hdmf = $hdmf + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'LOAN') {
        $loan = $loan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'SSSLOAN') {
        $sssloan = $sssloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'HDMFLOAN') {
        $hdmfloan = $hdmfloan + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $legalun + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } else {

        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }
      if ($c == 0) {
        $c = $this->getcount($config, $data->empid, $config['params']['dataparams']['line']);
      }

      $i = $i + 1;
      if ($i == $c) {
        $str .= "</br>";
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $font_size, 'B', '', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($divname), '400', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('P A Y S L I P', '270', null, false, $border, 'R', 'C', $font, 13, 'B', '', '');
        $str .= $this->reporter->col('', '330', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME: ' . strtoupper($clientname), '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '320', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEPARTMENT: ' . strtoupper($deptname), '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '320', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PERIOD COVERED : ' . strtoupper($data->startd) . ' to ' . strtoupper($data->endd) . ' - ' . strtoupper($data->batch), '400', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '270', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '330', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->reporter->begintable(340);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EARNINGS', '340', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $listdeduc = ['PPBLE', 'YWT']; #'DEDUCTION', 'LATE', 'ABSENT'
        $getearn = $this->dataearn($config, $data->empid, $data->batchid);

        $str .= $this->reporter->begintable(340);
        foreach ($getearn as $key => $val) {
          if ($val->db > 0) {
            if (!in_array($val->alias, $listdeduc)) {
              if (strpos($val->codename, 'ALLOWANCE') !== false) {
                $val->qty = 0;
              }
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '5', '5', false, $border, '', 'C', $font, $font_size, '', '', '1');
              $str .= $this->reporter->col('' . $val->codename, '160', '5', false, $border, '', 'L', $font, $font_size, '', '', '1');
              $str .= $this->reporter->col('' . $val->qty == 0 ? '-' : number_format($val->qty, 2), '70', '5', false, $border, '', 'R', $font, $font_size, '', '', '1');
              $str .= $this->reporter->col('' . $val->db == 0 ? '-' : number_format($val->db, 2), '100', '5', false, $border, '', 'R', $font, $font_size, '', '', '1');
              $str .= $this->reporter->col('', '5', '5', false, $border, '', 'R', $font, $font_size, '', '', '1');
              $str .= $this->reporter->endrow();
            }
          }
        }
        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 -95px'>";
        $str .= $this->addlinerigth_onesky();
        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 245px'>";
        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DEDUCTIONS', '330', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $deduction = $this->getdeductionbreakdown($config, $data->empid, $data->batchid);
        $str .= $this->reporter->begintable(330);
        foreach ($deduction as $key => $q) {
          if ($q->cr > 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '5', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $q->codename, '150', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $q->qty == 0 ? '-' : number_format($q->qty, 2), '70', '18', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $q->cr == 0 ? '-' : number_format($q->cr, 2), '100', '18', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '5', '18', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
          }
        }

        $str .= $this->reporter->endtable();

        $str .= "</div>";
        $str .= "<div style='position:absolute; margin:-10px 0 0 245px'>";
        $str .= $this->addline_onesky();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:-98px 0 0 585px'>";

        switch ($logotype) {
          case 'onesky':
            $logo = URL::to('/images/onesky/onesky_logo.png');
            $dislogo = '<img src ="' . $logo . '" alt="mbc" width="330" height ="90px" >';
            break;
          case 'nson':
            $logo = URL::to('/images/onesky/nson_logo.png');
            $dislogo = '<img src ="' . $logo . '" alt="mbc" width="330" height ="90px" >';
            break;

          default:
            $dislogo = strtoupper($divname);
            break;
        }

        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '330', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Received From', '330', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($dislogo, '330', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "<br><br>";

        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('My salaries for the period of', '180', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . strtoupper($data->startd) . ' to ' . strtoupper($data->endd) . ' - ' . strtoupper($data->batch), '300', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "<br>";
        $str .= $this->reporter->begintable(330);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Amounting to', '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Php ' . number_format($netpay, 2), '230', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= "<div style='position:absolute; margin:319px 0 0 -95px'>";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL EARNINGS:', '140', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalearn == 0 ? '-' : number_format($totalearn, 2), '200', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('TOTAL DEDUCTIONS: ', '180', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('' . $totalded == 0 ? '-' : number_format($totalded, 2), '150', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Received by:', '310', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '140', null, false, $border, 'B', 'L', $font, $font_size, '', '', '8px');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'R', $font, $font_size, '', '', '8px');

        $str .= $this->reporter->col('NET PAY: ', '180', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '8px');
        $str .= $this->reporter->col('' . $netpay == 0 ? '-' : number_format($netpay, 2), '150', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '8px');

        $str .= $this->reporter->col('', '10', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('' . strtoupper($clientname), '320', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $j++;
        $str .= "</div>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br></br></br>";
        $str .= "</br></br></br></br>";
        $str .= "</div>";

        if ($j == 3) {
          if ((count($result)) != $page) {
            $this->coreFunctions->LogConsole($page . '-' . count($result));
            $str .= $this->reporter->page_break();
            $j = 0;
          }
        }

        $i = 0;
        $c = 0;
        $basicpay = 0;
        $absent = 0;
        $late = 0;
        $undertime = 0;
        $rot = 0;
        $ndiffot = 0;
        $leave = 0;
        $restday = 0;
        $restdayot = 0;
        $special = 0;
        $specialot = 0;
        $legal = 0;
        $legalot = 0;
        $legalun = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $sssloan = 0;
        $hdmfloan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $allowance = 0;
        $netpay = 0;
        $totalearn = 0;
        $totalded = 0;


        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyleave = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtyspecialun = 0;
        $qtylegalun = 0;
        $legalun = 0;
        $specialun = 0;
      }
    } //loop

    $str .= $this->reporter->endreport();

    return $str;
  }
  public function addlinerigth_onesky()
  {
    $border = '1px solid black';
    $font = 'Century Gothic';
    $fontsize = '5';
    $str = '';
    $str .= $this->reporter->begintable(340);
    for ($i = 0; $i < 27; $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '5', '14.5', false, $border, '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '65', '14.5', false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '65', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '5', '14.5', false, $border, 'R', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function addline_onesky()
  {
    $border = '1px solid black';
    $font = 'Century Gothic';
    $fontsize = '5';
    $str = '';

    $str .= $this->reporter->begintable(330);
    for ($i = 0; $i < 27; $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '5', '14.5', false, $border, '', '', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '150', '14.5', false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '70', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', '14.5', false, $border, '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '5', '14.5', false, $border, 'R', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    return $str;
  }
}//end class