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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class payroll_register
{
  public $modulename = 'Payroll Register';
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
  public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1150'];

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

    if ($companyid == 58) { //cdo
      $fields = ['radioprint', 'dbranchname', 'divrep', 'deptrep', 'batchrep', 'dclientname'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'divrep.lookupclass', 'lookupdivpayslip');
      data_set($col1, 'divrep.addedparams', ['branchid']);
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
    } else {
      $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
      data_set($col1, 'dclientname.label', 'Employee');
      data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
      data_set($col1, 'divrep.label', 'Company');
      data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptrep.label', 'Department');

      $fields = ['batchrep'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'batchrep.lookupclass', 'lookupbatchrep');
      data_set($col2, 'batchrep.required', true);
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    '' as batchid,
    '' as line,
    '' as batch,
    '' as deptrep,
    '' as batchrep,
    '' as dbranchname,
    '' as branchcode,
    '0' as branchid
    ");
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
      // case 62: //one sky
      //   return  $this->onesky_layout_old($config);
      //   break;
      case 62: //one sky
        // return  $this->onesky_layout_new($config);
        return $this->onesky_layout_org_new($config);
        break;

      default:
        return $this->reportDefaultLayout($config);
        break;
    }
  }

  public function reportDefault($config, $batch)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];

    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0 && $deptid != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divid != 0 && $divid != "") {
      $filter2 .= " and emp.divid = $divid";
    }
    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select e.clientname,e.client,d.divname,dept.clientname as deptname,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,pa.code,emp.classrate,rate.basicrate
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          left join ratesetup as rate on rate.empid = emp.empid
          where  p.batchid = " . $batch . " and emp.level in $emplvl $filter $filter1 $filter2 $filter3
          order by e.clientname";


    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_onesky($config, $batch)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];

    $filter   = "";
    $filter1   = "";
    $filter2   = "";
    $filter3   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0 && $deptid != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divid != 0 && $divid != "") {
      $filter2 .= " and emp.divid = $divid";
    }
    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select e.clientname,e.client,d.divname,dept.clientname as deptname,sect.sectname,jt.jobtitle,case when emp.atm = 1 then 'ATM' else 'CASH' end as atm,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,case when batch.paymode = 'S' then 'SEMI-MONTHLY' else 'MONTHLY' end as paymode,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,pa.code,emp.classrate,rate.basicrate,emp.bankacct
          FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          left join ratesetup as rate on rate.empid = emp.empid
          left join section as sect on sect.sectid = emp.sectid
          left join jobthead as jt on jt.line = emp.jobid
          where  p.batchid = " . $batch . " and emp.level in $emplvl $filter $filter1 $filter2 $filter3
          union all
          SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,sect.sectname,jt.jobtitle,case when emp.atm = 1 then 'ATM' else 'CASH' end as atm,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,case when batch.paymode = 'S' then 'SEMI-MONTHLY' else 'MONTHLY' end as paymode,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,pa.code,emp.classrate,rate.basicrate,emp.bankacct
          FROM paytranhistory as p LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          left join ratesetup as rate on rate.empid = emp.empid
          left join section as sect on sect.sectid = emp.sectid
          left join jobthead as jt on jt.line = emp.jobid
          where  p.batchid = " . $batch . " and emp.level in $emplvl $filter $filter1 $filter2 $filter3
          order by clientname";


    return $this->coreFunctions->opentable($query);
  }

  public function CDO_query($config, $batch)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $batchid      = $config['params']['dataparams']['line'];
    $branch     = $config['params']['dataparams']['dbranchname'];
    $branchid     = $config['params']['dataparams']['branchid'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }

    if ($branch != "") {
      $filter .= " and emp.branchid = $branchid";
    }

    if ($deptid != 0) {
      $filter .= " and emp.deptid = $deptid";
    }

    if ($divid != 0) {
      $filter .= " and emp.divid = $divid";
    }

    if ($batchid != '') {
      $filter .= " and p.batchid = " . $batchid . " ";
    }

    $filter .= " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select e.clientname,e.client,d.divname,dept.clientname as deptname,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2
          FROM paytrancurrent as p 
          LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where  p.batchid = " . $batch . " and emp.level in $emplvl $filter 
          union all 
          SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,
          p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
          p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2
          FROM paytranhistory as p 
          LEFT JOIN employee AS emp ON emp.empid=p.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join batch on batch.line=p.batchid
          left join client as dept on dept.clientid = emp.deptid
          left join paccount as pa on pa.line=p.acnoid
          where  p.batchid = " . $batch . " and emp.level in $emplvl $filter 
          order by clientname";
    return $this->coreFunctions->opentable($query);
  }

  private function getcountcdo($empid, $batch)
  {
    return $this->coreFunctions->datareader("select count(p.batchid) as value 
    from paytrancurrent as p 
    left join paccount as pa on pa.line=p.acnoid 
    where pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER') 
           and batchid = " . $batch . "  and p.empid=? 
    union all
    select count(p.batchid) as value 
    from paytranhistory as p 
    left join paccount as pa on pa.line=p.acnoid 
    where pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER') 
           and batchid = " . $batch . "  and p.empid=? 
    
    group by p.empid", [$empid, $empid]);
  }

  private function getcount($empid, $batch)
  {
    return $this->coreFunctions->datareader("select count(p.batchid) as value from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid where pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER') and batchid = " . $batch . "  and p.empid=? group by p.empid", [$empid]);
  }

  private function displayHeader($config)
  {
    $layoutsize = 1500;
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 9;
    $divname    = $config['params']['dataparams']['divname'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $company   = $config['params']['companyid'];
    $batch      = $config['params']['dataparams']['line'];
    $batchno    = $config['params']['dataparams']['batch'];

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('P A Y R O L L &nbsp R E G I S T E R', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($company == 58) { //cdo
      $str .= $this->reporter->startrow();
      $branch   = $config['params']['dataparams']['dbranchname'];
      if ($branch == "") {
        $str .= $this->reporter->col('BRANCH : ALL COMPANY', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
      } else {
        $str .= $this->reporter->col('BRANCH : ' . strtoupper($branch), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
      }
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    if ($company != 28) {
      if ($divname == "") {
        $str .= $this->reporter->col('COMPANY : ALL COMPANY', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
      } else {
        $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($deptname == "") {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $batchstart = $this->coreFunctions->datareader("select date(startdate) as value from batch where line=? ", [$batch]);
    $batchend = $this->coreFunctions->datareader("select date(enddate) as value from batch where line=? ", [$batch]);

    $str .= $this->reporter->col('Payroll Period : ' . strtoupper($batchstart) . ' to ' . strtoupper($batchend) . ' - ' . strtoupper($batchno), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 20, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 200, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('EARNINGS', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DEDUCTIONS', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 45, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('No.', 20, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Employee Name', 200, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs of Work', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Basic Pay', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Absent', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Late / Undertime', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Leave', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Regular' . '<br/>' . 'OT', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Ndiff/' . '<br/>' . 'OT', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Restday', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Restday' . '<br/>' . 'OT', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Special', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Special' . '<br/>' . 'OT', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Legal', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Legal' . '<br/>' . 'OT', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Earnings', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('WHT', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Pagibig', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Loans', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Total Deduction', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('NET PAY', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 45, null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($company == 43) {
      $str .= $this->reporter->col('', 20, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Tripping', 200, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Operator', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('', 20, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', 200, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->col('Allowance', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Total Earnings', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('SSS', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Pagibig Loan', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Deduction', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 45, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 20, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 200, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('PHIC', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('SSS Loan', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    if ($company != 28) {
      $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('CASH ADVANCE', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    }
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 45, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $batch = $config['params']['dataparams']['line'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 58: //cdo
        $result = $this->CDO_query($config, $batch);
        break;
      default:
        $result = $this->reportDefault($config, $batch);
        break;
    }

    $border = '.5px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 9;
    $count = 30;
    $page = 30;
    $layoutsize = 1500;

    $str = '';
    $gtotnetpay = 0;
    $gtotearn = 0;
    $gtotded = 0;

    //1st
    $gtqtybasicpay = 0;
    $gtbasicpay = 0;
    $gtqtyabsent = 0;
    $gtqtylateundertime = 0;
    $gtqtyleave = 0;
    $gtqtyrot = 0;
    $gtqtyndiffot = 0;
    $gtqtyrestday = 0;
    $gtqtyrestdayot = 0;
    $gtqtyspecial = 0;
    $gtqtyspecialot = 0;
    $gtqtylegal = 0;
    $gtqtylegalot = 0;
    $gttototherearnings = 0;
    $gtwht = 0;
    $gthdmf = 0;
    $gtloan = 0;


    //2st
    $gttripping = 0;
    $gtoperator = 0;
    $gtallowance = 0;

    $gtabsent = 0;
    $gtlateundertime = 0;
    $gtleave = 0;
    $gtrot = 0;
    $gtndiffot = 0;
    $gtrestday = 0;
    $gtrestdayot = 0;
    $gtspecial = 0;
    $gtspecialot = 0;
    $gtlegal = 0;
    $gtlegalot = 0;

    $gtsss = 0;
    $gthdmfloan = 0;
    $gtotherdeduction = 0;
    $gtphic = 0;
    $gtsssloan = 0;


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
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
    $cashadvance = 0;
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


    $i = 0;
    $c = 0;
    $b = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $clientname = $data->clientname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        if ($companyid == 43) { //mighty
          $qtylate = $qtylate + $data->qty2;
        } else {
          $qtylate = $qtylate + $data->qty;
        }
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE' || $data->alias == 'ALLOWANCE3') {
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
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
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
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
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
      } elseif ($data->alias == 'DEDUCTION' && $data->codename == 'CASH ADVANCE') {
        $cashadvance = $cashadvance + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $tripping + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $operator + $data->db - $data->cr;
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
        if ($companyid == 58) { //cdo
          $c = $this->getcountcdo($data->empid, $config['params']['dataparams']['line']);
        } else {
          $c = $this->getcount($data->empid, $config['params']['dataparams']['line']);
        }
      }

      $i = $i + 1;
      if ($i == $c) {

        $b = $b + 1;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($b . '.', 20, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($clientname, 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtybasicpay == 0 ? '-' : number_format($qtybasicpay, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($basicpay == 0 ? '-' : number_format($basicpay, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyabsent == 0 ? '-' : number_format($qtyabsent, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $qtylateundertime = $qtylate + $qtyundertime;
        $str .= $this->reporter->col($qtylateundertime == 0 ? '-' : number_format($qtylateundertime, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyleave == 0 ? '-' : number_format($qtyleave, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyrot == 0 ? '-' : number_format($qtyrot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyndiffot == 0 ? '-' : number_format($qtyndiffot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyrestday == 0 ? '-' : number_format($qtyrestday, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyrestdayot == 0 ? '-' : number_format($qtyrestdayot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyspecial == 0 ? '-' : number_format($qtyspecial, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyspecialot == 0 ? '-' : number_format($qtyspecialot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtylegal == 0 ? '-' : number_format($qtylegal, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtylegalot == 0 ? '-' : number_format($qtylegalot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $tototherearnings = $otherearnings + $bonus;
        $str .= $this->reporter->col($tototherearnings == 0 ? '-' : number_format($tototherearnings, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($wht == 0 ? '-' : number_format($wht, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($hdmf == 0 ? '-' : number_format($hdmf, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($loan == 0 ? '-' : number_format($loan, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 45, null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        if ($companyid == 43) { //mighty
          $str .= $this->reporter->col('', 20, null, false, $border, '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($tripping == 0 ? '-' : number_format($tripping, 2), 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($operator == 0 ? '-' : number_format($operator, 2), 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col('', 20, null, false, $border, '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        }

        $str .= $this->reporter->col($allowance == 0 ? '-' : number_format($allowance, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($absent == 0 ? '-' : number_format($absent, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $totlateundertime = $late + $undertime;
        $str .= $this->reporter->col($totlateundertime == 0 ? '-' : number_format($totlateundertime, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($leave == 0 ? '-' : number_format($leave, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($rot == 0 ? '-' : number_format($rot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($ndiffot == 0 ? '-' : number_format($ndiffot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($restday == 0 ? '-' : number_format($restday, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($restdayot == 0 ? '-' : number_format($restdayot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($special == 0 ? '-' : number_format($special, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($specialot == 0 ? '-' : number_format($specialot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($legal == 0 ? '-' : number_format($legal, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($legalot == 0 ? '-' : number_format($legalot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalearn == 0 ? '-' : number_format($totalearn, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sss == 0 ? '-' : number_format($sss, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 45, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $loanbal = $this->coreFunctions->datareader("select sum(balance) as value from standardsetup where empid=? and date(effdate) between '?' and '?'", [$data->empid, $data->startdate, $data->enddate]);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 20, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($phic == 0 ? '-' : number_format($phic, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sssloan == 0 ? '-' : number_format($sssloan, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        if ($companyid != 28) { //not xcomp
          $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        } else {
          $str .= $this->reporter->col($cashadvance == 0 ? '-' : number_format($cashadvance, 2), 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        }
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 45, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100px', null, false, $border, 'OB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $gtotnetpay = $gtotnetpay + $netpay;
        $gtotearn = $gtotearn + $totalearn;
        $gtotded = $gtotded + $totalded;

        //1st grandtotal
        $gttripping += $tripping;
        $gtoperator += $operator;
        $gtallowance += $allowance;

        $gtabsent += $absent;
        $gtlateundertime += $totlateundertime;
        $gtleave += $leave;
        $gtrot += $rot;
        $gtndiffot += $ndiffot;
        $gtrestday += $restday;
        $gtrestdayot += $restdayot;
        $gtspecial += $special;
        $gtspecialot += $specialot;
        $gtlegal += $legal;
        $gtlegalot += $legalot;

        $gtsss += $sss;
        $gthdmfloan += $hdmfloan;
        $gtotherdeduction += $otherdeduction;

        //2nd grandtotal

        $gtqtybasicpay += $qtybasicpay;
        $gtbasicpay += $basicpay;
        $gtqtyabsent += $qtyabsent;;
        $gtqtylateundertime += $qtylate;
        $gtqtyleave += $qtyundertime;
        $gtqtyrot += $qtyrot;
        $gtqtyndiffot += $qtyndiffot;
        $gtqtyrestday += $qtyrestday;
        $gtqtyrestdayot += $qtyrestdayot;
        $gtqtyspecial += $qtyspecial;
        $gtqtyspecialot += $qtyspecialot;
        $gtqtylegal += $qtylegal;
        $gtqtylegalot += $qtylegalot;
        $gttototherearnings += $tototherearnings;

        $gtwht += $wht;
        $gthdmf += $hdmf;
        $gtloan += $loan;

        // 3rd
        $gtphic += $phic;
        $gtsssloan += $sssloan;

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
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $cashadvance = 0;
        $allowance = 0;
        $netpay = 0;
        $tripping = 0;
        $operator = 0;
        $totalearn = 0;
        $totalded = 0;
        $sssloan = 0;
        $hdmfloan = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
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
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    if ($companyid == 43) { //mighty
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtybasicpay == 0 ? '-' : number_format($gtqtybasicpay, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtbasicpay == 0 ? '-' : number_format($gtbasicpay, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyabsent == 0 ? '-' : number_format($gtqtyabsent, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtylateundertime == 0 ? '-' : number_format($gtqtylateundertime, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyleave == 0 ? '-' : number_format($gtqtyleave, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyrot == 0 ? '-' : number_format($gtqtyrot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyndiffot == 0 ? '-' : number_format($gtqtyndiffot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyrestday == 0 ? '-' : number_format($gtqtyrestday, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyrestdayot == 0 ? '-' : number_format($gtqtyrestdayot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyspecial == 0 ? '-' : number_format($gtqtyspecial, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtyspecialot == 0 ? '-' : number_format($gtqtyspecialot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtylegal == 0 ? '-' : number_format($gtqtylegal), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtqtylegalot == 0 ? '-' : number_format($gtqtylegalot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gttototherearnings == 0 ? '-' : number_format($gttototherearnings, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtwht == 0 ? '-' : number_format($gtwht, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gthdmf == 0 ? '-' : number_format($gthdmf, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtloan == 0 ? '-' : number_format($gtloan, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtotded == 0 ? '-' : number_format($gtotded, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtotnetpay == 0 ? '-' : number_format($gtotnetpay, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      // $gttripping = 0;
      // $gtoperator = 0;
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gttripping == 0 ? '-' : number_format($gttripping, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtoperator == 0 ? '-' : number_format($gtoperator, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('-', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtabsent == 0 ? '-' : number_format($gtabsent, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtlateundertime == 0 ? '-' : number_format($gtlateundertime, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtleave == 0 ? '-' : number_format($gtleave, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtrot == 0 ? '-' : number_format($gtrot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtndiffot == 0 ? '-' : number_format($gtndiffot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtrestday == 0 ? '-' : number_format($gtrestday, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtrestdayot == 0 ? '-' : number_format($gtrestdayot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtspecial == 0 ? '-' : number_format($gtspecial, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtspecialot == 0 ? '-' : number_format($gtspecialot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtlegal == 0 ? '-' : number_format($gtlegal, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtlegalot == 0 ? '-' : number_format($gtlegalot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtotearn == 0 ? '-' : number_format($gtotearn, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtsss == 0 ? '-' : number_format($gtsss, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gthdmfloan == 0 ? '-' : number_format($gthdmfloan, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL', '20', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '120', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtphic == 0 ? '-' : number_format($gtphic, 2), '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($gtsssloan == 0 ? '-' : number_format($gtsssloan, 2), '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($gtotnetpay, 2), '60', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ______________________', '266', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Checked By : ______________________', '266', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Approved By : ______________________', '266', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
  private function displayheader_onesky_new($config)
  {
    $layoutsize = 1585;
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 8;
    $divname    = $config['params']['dataparams']['divname'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $company   = $config['params']['companyid'];
    $batch      = $config['params']['dataparams']['line'];
    $batchno    = $config['params']['dataparams']['batch'];

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('P A Y R O L L &nbsp R E G I S T E R', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->startrow();

    if ($divname == "") {
      $str .= $this->reporter->col('COMPANY : ALL COMPANY', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    } else {
      $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($deptname == "") {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $batchstart = $this->coreFunctions->datareader("select date(startdate) as value from batch where line=? ", [$batch]);
    $batchend = $this->coreFunctions->datareader("select date(enddate) as value from batch where line=? ", [$batch]);

    $str .= $this->reporter->col('Payroll Period : ' . strtoupper($batchstart) . ' to ' . strtoupper($batchend) . ' - ' . strtoupper($batchno), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('', 20, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 200, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('EARNINGS', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DEDUCTIONS', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 85, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('', 20, null, false, $border, '', 'L', $font, $font_size, '', '', ''); //No.
    $str .= $this->reporter->col('', 200, null, false, $border, '', 'L', $font, $font_size, '', '', ''); //Employee Name
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Days of Work
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Basic
    $str .= $this->reporter->col('Reg. OT', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Absent
    $str .= $this->reporter->col('NDiff OT', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Late / Undertime
    $str .= $this->reporter->col('Sunday & Special H', 65, null, false, $border, '', 'C', $font, $font_size, '', '', ''); //Leave
    $str .= $this->reporter->col('Legal H', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Regular' . '<br/>' . 'OT'
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Ndiff/' . '<br/>' . 'OT
    $str .= $this->reporter->col('Absent', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Late / Undertime OT', 65, null, false, $border, '', 'C', $font, $font_size, '', '', ''); //restday
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Legal
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Legal' . '<br/>' . 'OT
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Other Earnings
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //WHT
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Pagibig
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Meal Deduction
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Other Loans
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Total Deduction
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //NET PAY
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Total Deduction
    $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //NET PAY
    $str .= $this->reporter->col('', 85, null, false, $border, '', 'R', $font, $font_size, '', '', ''); //Signature of Payee
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    // $str .= $this->reporter->col('', 20, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 200, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');


    $str .= $this->reporter->col('Salary', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Days', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 85, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('No.', 20, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Employee Name', 200, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Days of Work', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Allowance', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Earnings', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('TOTAL Amount', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('WTH', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('SSS', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('PHIC', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Pagibig', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('SSS Loan', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Pagibig Loan', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Meal Deduction', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Deduction', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Total Deduction', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Net Amount Paid', 65, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Signature of Payee', 85, null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    return $str;
  }
  public function onesky_layout_new($config)
  {
    $batch = $config['params']['dataparams']['line'];
    $companyid = $config['params']['companyid'];


    $result = $this->reportDefault_onesky($config, $batch);

    $border = '.5px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 9;
    $count = 14;
    $page = 60;
    $layoutsize = 1585;

    $str = '';
    $gtotnetpay = 0;
    $gtotearn = 0;
    $gtotded = 0;

    //1st
    $gtqtybasicpay = 0;
    $gtbasicpay = 0;
    $gtqtyabsent = 0;
    $gtqtylateundertime = 0;
    $gtqtyleave = 0;
    $gtqtyrot = 0;
    $gtqtyndiffot = 0;
    $gtqtyrestday = 0;
    $gtqtyrestdayot = 0;
    $gtqtyspecial = 0;
    $gtqtyspecialot = 0;
    $gtqtylegal = 0;
    $gtqtylegalot = 0;
    $gttototherearnings = 0;
    $gtwht = 0;
    $gthdmf = 0;
    $gtloan = 0;


    //2st
    $gttripping = 0;
    $gtoperator = 0;
    $gtallowance = 0;

    $gtabsent = 0;
    $gtlateundertime = 0;
    $gtleave = 0;
    $gtrot = 0;
    $gtndiffot = 0;
    $gtrestday = 0;
    $gtrestdayot = 0;
    $gtspecial = 0;
    $gtspecialot = 0;
    $gtlegal = 0;
    $gtlegalot = 0;

    $gtsss = 0;
    $gthdmfloan = 0;
    $gtotherdeduction = 0;
    $gtphic = 0;
    $gtsssloan = 0;
    $gmealdeduction = 0;
    $countemp = 0;


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '10px;margin-top:5px;');
    $str .= $this->displayheader_onesky_new($config);
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
    $satot = 0;
    $sundayot = 0;
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
    $cashadvance = 0;
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
    $qtysatot = 0;
    $qtysundayot = 0;
    $mealdeduction = 0;
    $totalamount = 0;
    $gtotalamount = 0;


    $vl_sl = 0;
    $sil = 0;


    $i = 0;
    $c = 0;
    $b = 0;
    $clientname = '';
    $class_rate = '';
    $basic_rate = 0;

    $tempid = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $class_rate = $data->classrate;
      $basic_rate = $data->basicrate;
      $clientname = $data->clientname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        if ($companyid == 43) { //mighty
          $qtylate = $qtylate + $data->qty2;
        } else {
          $qtylate = $qtylate + $data->qty;
        }
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE' || $data->alias == 'COLA') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $vl_sl = $vl_sl + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $vl_sl = $vl_sl + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $vl_sl = $vl_sl + $data->db - $data->cr;
        $sil = $sil + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
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
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
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
      } elseif ($data->alias == 'DEDUCTION' && $data->codename == 'CASH ADVANCE') {
        $cashadvance = $cashadvance + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $tripping + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $operator + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->code == 'PT37' && $data->alias == 'DEDUCTION') {
        $mealdeduction = $mealdeduction + $data->cr;
        $totalded = $totalded + $data->cr;
      } elseif ($data->alias == 'OTSAT') {
        $satot = $satot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtysatot = $qtysatot + $data->qty;
      } elseif ($data->alias == 'RESTDAYSAT') {
        $sundayot = $sundayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtysundayot = $qtysundayot + $data->qty;
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
        if ($tempid == 0) {
          $tempid = $data->empid;
          $c = $this->getcount($tempid, $data->batchid);
        }
      }


      $i = $i + 1;
      if ($i == $c) {
        $countemp++;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $qtylateundertime = $qtylate + $qtyundertime;

        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 85, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();



        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($clientname, 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');

        if ($qtybasicpay != 0) {
          $qtybasicpay = ($qtybasicpay / 8);
        }
        if ($qtyabsent != 0) {
          $qtyabsent = ($qtyabsent / 8);
        }
        if ($class_rate == 'D') {
          $basicpay = ($qtybasicpay - $qtyabsent) * $basic_rate;
          $qtybasicpay = ($qtybasicpay - $qtyabsent);
          $qtyabsent = 0;
          $absent = 0;
        }
        // if ($class_rate == 'D') {
        //   $basicpay = ((($qtybasicpay / 8) - ($qtyabsent / 8)) * $basic_rate);
        //   $qtybasicpay = (($qtybasicpay / 8) - ($qtyabsent / 8));
        //   $qtyabsent = 0;
        //   $absent = 0;
        // } else {
        //   $qtybasicpay = ($qtybasicpay / 8);
        //   $qtyabsent = ($qtyabsent / 8);
        // }

        $qtylegal = $qtylegal + $qtylegalot;

        $str .= $this->reporter->col($basicpay == 0 ? '-' : number_format($basicpay, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyrot == 0 ? '-' : number_format($qtyrot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $totlateundertime = $late + $undertime;
        $str .= $this->reporter->col($qtyndiffot == 0 ? '-' : number_format($qtyndiffot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $rest_spqty = $qtyrestday + $qtyrestdayot + $qtyspecial + $qtyspecialot + $qtysatot + $qtysundayot;
        $str .= $this->reporter->col($rest_spqty == 0 ? '-' : number_format($rest_spqty, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtylegal == 0 ? '-' : number_format($qtylegal, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyabsent == 0 ? '-' : number_format($qtyabsent, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtylateundertime == 0 ? '-' : number_format($qtylateundertime, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 85, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $rest_spamt = $restday + $restdayot + $special + $specialot + $satot + $sundayot;
        $tototherearnings = $otherearnings + $bonus + $vl_sl;



        $legal = $legal + $legalot;
        $totalamount = $basicpay + $allowance + $rot + $tototherearnings + $rest_spamt + $legal + $ndiffot - ($totlateundertime + $absent);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 200, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtybasicpay == 0 ? '-' : number_format($qtybasicpay, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($allowance == 0 ? '' : number_format($allowance, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($rot == 0 ? '-' : number_format($rot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($ndiffot == 0 ? '-' : number_format($ndiffot, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($rest_spamt == 0 ? '-' : number_format($rest_spamt, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($legal == 0 ? '-' : number_format($legal, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($tototherearnings == 0 ? '-' : number_format($tototherearnings, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($absent == 0 ? '-' : number_format($absent, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totlateundertime == 0 ? '-' : number_format($totlateundertime, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalamount == 0 ? '-' : number_format($totalamount, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($wht == 0 ? '-' : number_format($wht, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sss == 0 ? '-' : number_format($sss, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($phic == 0 ? '-' : number_format($phic, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($hdmf == 0 ? '-' : number_format($hdmf, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sssloan == 0 ? '-' : number_format($sssloan, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($mealdeduction == 0 ? '-' : number_format($mealdeduction, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), 65, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 85, null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $border_dotted = '1px dotted';

        $str .= $this->reporter->col('', 200, null, false, $border_dotted, 'T', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');


        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 65, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', 85, null, false, $border_dotted, 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
        $gtotnetpay = $gtotnetpay + $netpay;
        $gtotearn = $gtotearn + $totalearn;
        $gtotded = $gtotded + $totalded;

        //1st grandtotal
        $gttripping += $tripping;
        $gtoperator += $operator;
        $gtallowance += $allowance;


        $gtabsent += $absent;
        $gtlateundertime += $totlateundertime;
        $gtleave += $leave;
        $gtrot += $rot;
        $gtndiffot += $ndiffot;
        $gtrestday += $rest_spamt;
        $gtrestdayot += $restdayot;
        $gtspecial += $special;
        $gtspecialot += $specialot;
        $gtlegal += $legal;
        $gtlegalot += $legalot;

        $gtsss += $sss;
        $gthdmfloan += $hdmfloan;
        $gtotherdeduction += $otherdeduction;

        $gmealdeduction += $mealdeduction;

        //2nd grandtotal

        $gtqtybasicpay += $qtybasicpay;
        $gtbasicpay += $basicpay;
        $gtqtyabsent += $qtyabsent;;
        $gtqtylateundertime += $qtylate;
        $gtqtyleave += $qtyundertime;
        $gtqtyrot += $qtyrot;
        $gtqtyndiffot += $qtyndiffot;
        $gtqtyrestday += $qtyrestday;
        $gtqtyrestdayot += $qtyrestdayot;
        $gtqtyspecial += $qtyspecial;
        $gtqtyspecialot += $qtyspecialot;
        $gtqtylegal += $qtylegal;
        $gtqtylegalot += $qtylegalot;
        $gttototherearnings += $tototherearnings;
        // $gtotalamount += $tototherearnings;
        $gtotalamount += $totalamount;

        $gtwht += $wht;
        $gthdmf += $hdmf;
        $gtloan += $loan;

        // 3rd
        $gtphic += $phic;
        $gtsssloan += $sssloan;

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
        $vl_sl = 0;
        $legal = 0;
        $legalot = 0;
        $satot = 0;
        $sundayot = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $cashadvance = 0;
        $allowance = 0;
        $netpay = 0;
        $tripping = 0;
        $operator = 0;
        $totalearn = 0;
        $totalded = 0;
        $sssloan = 0;
        $hdmfloan = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtysatot = 0;
        $qtysundayot = 0;
        $mealdeduction = 0;
        $tempid = 0;
      }

      if ($count == $countemp) {
        $countemp = 0;
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayheader_onesky_new($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('GRAND TOTAL', 200, null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($gtotalamount, 2), 65, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 65, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col(number_format($gtotded, 2), 65, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($gtotnetpay, 2), 65, null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 85, null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('I HEREBY CERTIFY that I have personally paid in cash to each employees whose name appears in the above payroll the amount set opposite his name.', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("The amount paid in this payroll is P" . number_format($gtotnetpay, 2) . " , including their overtime pay", null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ______________________', '266', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Checked By : ______________________', '266', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Approved By : ______________________', '266', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
  public function one_sky_header_org($config, $layoutsize, $data)
  {
    $batch = $config['params']['dataparams']['line'];
    $companyid = $config['params']['companyid'];
    $divname = $config['params']['dataparams']['divname'];
    $border = '1px solid';
    // $font = $this->companysetup->getrptfont($config['params']); 
    $font = 'Century Gothic';

    $font_size = 6;
    $count = 14;
    $page = 60;
    $fontcolor = '#FFFFFF'; //white #FFFFFF
    $bgcolor = '#000000'; //black #000000
    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Payroll Register', 120, '20px', false, $border, '', 'L', $font, '15px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('' . $divname, 120, '20px', false, $border, '', 'L', $font, '15px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]->paymode . ' PAYROLL', 100, '20px', false, $border, '', 'L', $font, '15px', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $batchstart = $this->coreFunctions->datareader("select date(startdate) as value from batch where line=? ", [$batch]);
    $batchend = $this->coreFunctions->datareader("select date(enddate) as value from batch where line=? ", [$batch]);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Payroll Period : ' . strtoupper($batchstart) . ' to ' . strtoupper($batchend), 300, '20px', false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 120, '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 80, '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 120, '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 100, '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 140, '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 140, '', false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 120, '', false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('RATE', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'RT', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EARNINGS', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'TR', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', 60, '', false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DEDUCTION', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 60, '', false, $border, 'TR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee Number', 120, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Section', 80, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Name', 120, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Job Title', 100, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Payroll Credit', 140, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('AUB Account Number', 140, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Basic Salary', 120, '', false, $border, 'LBT', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Daily', 60, '', false, $border, 'BTL', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Per Hour', 60, '', false, $border, 'BTL', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Per Mins', 60, '', false, $border, 'BRTL', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('No. of Days', 60, '', false, $border, 'LTB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Basic Salary', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('No. of Holiday', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Holiday Pay', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Holiday OT hours + 25%', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Holiday OT Pay', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('OT Hours + 25%', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Regular Overtime Pay', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Night Diff', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Night Diff Pay', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Rest Day + 30%', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Rest Day Pay', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('RDOT Hrs + 30%', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Rest Day OT', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('13 month', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SlL', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Alllowance', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Skill Alllowances', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Adjustment', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('GROSS AMOUNT', 60, '', false, $border, 'TLBR', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Late / Undertime', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Leave / Absences', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SSS', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Philhealth', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Pagibig', 60, '', false, $border, 'TLBR', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('SSS Loan', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Pagibig Loan', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Adjustment / Penalties / CA', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Meal Deduction (Canteen)', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Total Deduction', 60, '', false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('NET PAY', 60, '', false, $border, 'TLBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function onesky_layout_org_new($config)
  {
    $batch = $config['params']['dataparams']['line'];
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault_onesky($config, $batch);
    $border = '.5px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = 5;
    $count = 20;
    $page = 60;
    $layoutsize = 2890;
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '10px;margin-top:5px;');
    $str .= $this->one_sky_header_org($config, $layoutsize, $result);
    $gtotnetpay = 0;
    $gtotearn = 0;
    $gtotded = 0;

    //1st
    $gtqtybasicpay = 0;
    $gtbasicpay = 0;
    $gtqtyabsent = 0;
    $gtqtylateundertime = 0;
    $gtqtyleave = 0;
    $gtqtyrot = 0;
    $gtqtyndiffot = 0;
    $gtqtyrestday = 0;
    $gtqtyrestdayot = 0;
    $gtqtyspecial = 0;
    $gtqtyspecialot = 0;
    $gtqtylegal = 0;
    $gtqtylegalot = 0;
    $gttototherearnings = 0;
    $gtwht = 0;
    $gthdmf = 0;
    $gtloan = 0;


    //2st
    $gttripping = 0;
    $gtoperator = 0;
    $gtallowance = 0;

    $gtabsent = 0;
    $gtlateundertime = 0;
    $gtleave = 0;
    $gtrot = 0;
    $gtndiffot = 0;
    $gtrestday = 0;
    $gtrestdayot = 0;
    $gtspecial = 0;
    $gtspecialot = 0;
    $gtlegal = 0;
    $gtlegalot = 0;

    $gtsss = 0;
    $gthdmfloan = 0;
    $gtotherdeduction = 0;
    $gtphic = 0;
    $gtsssloan = 0;
    $gmealdeduction = 0;
    $countemp = 0;


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
    $satot = 0;
    $sundayot = 0;
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
    $cashadvance = 0;
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
    $qtysatot = 0;
    $qtysundayot = 0;
    $mealdeduction = 0;
    $totalamount = 0;
    $gtotalamount = 0;


    $vl_sl = 0;
    $sil = 0;
    $adj = 0;
    $adj1 = 0;
    $adj_cash_penalty = 0;
    $penalty = 0;
    $skillallowance = 0;


    $totlateundertime = 0;
    $tototherearnings = 0;
    $rest_spamt = 0;


    $i = 0;
    $c = 0;
    $b = 0;
    $clientname = '';
    $class_rate = '';
    $basic_rate = 0;
    $str .= $this->reporter->begintable($layoutsize);
    $tempid = 0;
    foreach ($result as $key => $data) {
      $class_rate = $data->classrate;
      $basic_rate = $data->basicrate;
      $clientname = $data->clientname;

      if ($data->alias == 'BSA') {
        $basicpay = $basicpay + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtybasicpay = $qtybasicpay + $data->qty;
      } elseif ($data->alias == 'ABSENT') {
        $absent = $absent + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyabsent = $qtyabsent + $data->qty;
      } elseif ($data->alias == 'LATE') {
        $late = $late   + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        if ($companyid == 43) { //mighty
          $qtylate = $qtylate + $data->qty2;
        } else {
          $qtylate = $qtylate + $data->qty;
        }
      } elseif ($data->alias == 'UNDERTIME') {
        $undertime = $undertime  + $data->cr - $data->db;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyundertime = $qtyundertime + $data->qty;
      } elseif ($data->alias == 'OTREG') {
        $rot = $rot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyrot = $qtyrot + $data->qty;
      } elseif ($data->alias == 'NDIFF') {
        $ndiffot = $ndiffot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyndiffot = $qtyndiffot + $data->qty;
      } elseif ($data->alias == 'ALLOWANCE' || $data->alias == 'COLA') {
        $allowance = $allowance + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'SL') {
        $leave = $leave + $data->db - $data->cr;
        $vl_sl = $vl_sl + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'VL') {
        $leave = $leave + $data->db - $data->cr;
        $vl_sl = $vl_sl + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyleave = $qtyleave + $data->qty;
      } elseif ($data->alias == 'SIL') {
        $leave = $leave + $data->db - $data->cr;
        $vl_sl = $vl_sl + $data->db - $data->cr;
        $sil = $sil + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'ML') {
        $leave = $leave + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
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
      } elseif ($data->alias == 'SPECIALOT') {
        $specialot = $specialot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecialot = $qtyspecialot + $data->qty;
      } elseif ($data->alias == 'SPUN') {
        $special = $special + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtyspecial = $qtyspecial + $data->qty;
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
      } elseif ($data->alias == 'LEGALOT') {
        $legalot = $legalot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegalot = $qtylegalot + $data->qty;
      } elseif ($data->alias == 'LEGUN') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
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
      } elseif ($data->alias == 'CA' && $data->codename == 'CASH ADVANCE') {
        $cashadvance = $cashadvance + $data->cr - $data->db;
        $totalded = $totalded + $data->cr - $data->db;
      } elseif ($data->alias == 'INCENTIVE1') {
        $tripping = $tripping + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->alias == 'INCENTIVE2') {
        $operator = $operator + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->code == 'PT37' && $data->alias == 'DEDUCTION') {
        $mealdeduction = $mealdeduction + $data->cr;
        $totalded = $totalded + $data->cr;
      } elseif ($data->alias == 'OTSAT') {
        $satot = $satot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtysatot = $qtysatot + $data->qty;
      } elseif ($data->alias == 'RESTDAYSAT') {
        $sundayot = $sundayot + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtysundayot = $qtysundayot + $data->qty;
      } elseif ($data->code == 'PT29') { // adjustment
        $adj1 = $adj1 + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
      } elseif ($data->code == 'PT09') { // pentalty
        $penalty = $penalty + $data->cr;
        $totalded = $totalded + $data->cr;
      } elseif ($data->code == 'PT70') { // adjustment deduction
        $adj = $adj + $data->cr;
        $totalded = $totalded + $data->cr;
      } elseif ($data->code == 'PT4') { //  skill allowance
        $skillallowance = $skillallowance + $data->cr;
        $totalded = $totalded + $data->cr;
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
        if ($tempid == 0) {
          $tempid = $data->empid;
          $c = $this->getcount($tempid, $data->batchid);
        }
      }
      $i = $i + 1;
      if ($i == $c) {
        $countemp++;

        if ($qtybasicpay != 0) {
          $qtybasicpay = ($qtybasicpay / 8);
        }
        if ($qtyabsent != 0) {
          $qtyabsent = ($qtyabsent / 8);
        }
        if ($class_rate == 'D') {
          $basicpay = ($qtybasicpay - $qtyabsent) * $basic_rate;
          $qtybasicpay = ($qtybasicpay - $qtyabsent);
          $qtyabsent = 0;
          $absent = 0;
        }


        $qtylateundertime = $qtylate + $qtyundertime;
        $totlateundertime = $late + $undertime;

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->client, 120, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($data->sectname, 80, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($clientname, 120, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($data->jobtitle, 100, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($data->atm, 140, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($data->bankacct, 140, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($basicpay, 2), 120, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($basic_rate, 2), 60, '', false, $border, 'BL', 'C', $font, $font_size, 'B', '', '');
        $perhrs = ($basic_rate / 8);
        $permins = ($perhrs / 60);
        $str .= $this->reporter->col(number_format($perhrs, 2), 60, '', false, $border, 'BL', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($permins, 2), 60, '', false, $border, 'BRL', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');
        // $qtylegalot
        $str .= $this->reporter->col($qtyrot == 0 ? '-' : number_format(($qtyrot / 8), 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($basicpay, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($qtylegal == 0 ? '-' : number_format($qtylegal, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($legal == 0 ? '-' : number_format($legal, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($qtylegalot == 0 ? '-' : number_format($qtylegalot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($legalot == 0 ? '-' : number_format($legalot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($qtyrot == 0 ? '-' : number_format($qtyrot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($rot == 0 ? '-' : number_format($rot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($qtyndiffot == 0 ? '-' : number_format($qtyndiffot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($ndiffot == 0 ? '-' : number_format($ndiffot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($qtyrestday == 0 ? '-' : number_format($qtyrestday, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($restday == 0 ? '-' : number_format($restday, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($qtyrestdayot == 0 ? '-' : number_format($qtyrestdayot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($restdayot == 0 ? '-' : number_format($restdayot, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($bonus == 0 ? '-' : number_format($bonus, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($sil == 0 ? '-' : number_format($sil, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($allowance == 0 ? '-' : number_format($allowance, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($skillallowance == 0 ? '-' : number_format($skillallowance, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($adj1 == 0 ? '-' : number_format($adj1), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');

        $tototherearnings = $otherearnings + $bonus + $vl_sl + $sil;
        $legal = $legal + $legalot;
        $totalamount = $basicpay + $allowance + $rot + $tototherearnings + $rest_spamt + $legal + $ndiffot + $adj1 +
          $restday + $restdayot - ($totlateundertime + $absent);
        $str .= $this->reporter->col(number_format($totalamount, 2), 60, '', false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($totlateundertime == 0 ? '-' : number_format($totlateundertime, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');

        $leave_absent = $leave - $absent;
        $str .= $this->reporter->col($leave_absent == 0 ? '-' : number_format($leave_absent, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($sss == 0 ? '-' : number_format($sss, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($phic == 0 ? '-' : number_format($phic, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($hdmf == 0 ? '-' : number_format($hdmf, 2), 60, '', false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($sssloan == 0 ? '-' : number_format($sssloan, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $adj_cash_penalty = $adj + $penalty + $cashadvance + $otherdeduction;
        $str .= $this->reporter->col($adj_cash_penalty == 0 ? '-' : number_format($adj_cash_penalty, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($mealdeduction == 0 ? '-' : number_format($mealdeduction, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), 60, '', false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), 60, '', false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', 10, '', false, $border, '', '', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->endrow();




        $gtotnetpay = $gtotnetpay + $netpay;
        $gtotearn = $gtotearn + $totalearn;
        $gtotded = $gtotded + $totalded;

        //1st grandtotal
        $gttripping += $tripping;
        $gtoperator += $operator;
        $gtallowance += $allowance;


        $gtabsent += $absent;
        $gtlateundertime += $totlateundertime;
        $gtleave += $leave;
        $gtrot += $rot;
        $gtndiffot += $ndiffot;
        $gtrestday += $rest_spamt;
        $gtrestdayot += $restdayot;
        $gtspecial += $special;
        $gtspecialot += $specialot;
        $gtlegal += $legal;
        $gtlegalot += $legalot;

        $gtsss += $sss;
        $gthdmfloan += $hdmfloan;
        $gtotherdeduction += $otherdeduction;

        $gmealdeduction += $mealdeduction;

        //2nd grandtotal

        $gtqtybasicpay += $qtybasicpay;
        $gtbasicpay += $basicpay;
        $gtqtyabsent += $qtyabsent;;
        $gtqtylateundertime += $qtylate;
        $gtqtyleave += $qtyundertime;
        $gtqtyrot += $qtyrot;
        $gtqtyndiffot += $qtyndiffot;
        $gtqtyrestday += $qtyrestday;
        $gtqtyrestdayot += $qtyrestdayot;
        $gtqtyspecial += $qtyspecial;
        $gtqtyspecialot += $qtyspecialot;
        $gtqtylegal += $qtylegal;
        $gtqtylegalot += $qtylegalot;
        $gttototherearnings += $tototherearnings;
        // $gtotalamount += $tototherearnings;
        $gtotalamount += $totalamount;

        $gtwht += $wht;
        $gthdmf += $hdmf;
        $gtloan += $loan;

        // 3rd
        $gtphic += $phic;
        $gtsssloan += $sssloan;

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
        $vl_sl = 0;
        $legal = 0;
        $legalot = 0;
        $satot = 0;
        $sundayot = 0;
        $wht = 0;
        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $loan = 0;
        $bonus = 0;
        $otherearnings = 0;
        $otherdeduction = 0;
        $cashadvance = 0;
        $allowance = 0;
        $netpay = 0;
        $tripping = 0;
        $operator = 0;
        $totalearn = 0;
        $totalded = 0;
        $sssloan = 0;
        $hdmfloan = 0;

        $sil = 0;
        $adj = 0;
        $adj1 = 0;
        $adj_cash_penalty = 0;
        $penalty = 0;
        $skillallowance = 0;

        $qtybasicpay = 0;
        $qtyabsent = 0;
        $qtylate = 0;
        $qtyundertime = 0;
        $qtyrot = 0;
        $qtyndiffot = 0;
        $qtyrestday = 0;
        $qtyrestdayot = 0;
        $qtyspecial = 0;
        $qtyspecialot = 0;
        $qtylegal = 0;
        $qtylegalot = 0;
        $qtysatot = 0;
        $qtysundayot = 0;
        $mealdeduction = 0;
        $tempid = 0;
      }
      if ($count == $countemp) {
        $countemp = 0;
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->one_sky_header_org($config, $layoutsize, $result);
        // $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->endtable();
      }
    }
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class