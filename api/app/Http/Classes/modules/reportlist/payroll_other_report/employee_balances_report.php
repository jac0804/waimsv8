<?php

namespace App\Http\Classes\modules\reportlist\payroll_other_report;

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

class employee_balances_report
{
  public $modulename = 'Employee Balances Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1000px;max-width:1000px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
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
    '' as deptrep
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

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];

    $filter   = "";
    $filter1   = "";
    $filter2   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divid != 0) {
      $filter2 .= " and emp.divid = $divid";
    }

    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select a.code,a.empname,
  sum(a.cashloan) as cashloan,
  sum(a.sssloan) as sssloan,
  sum(a.pagibigloan) as pagibigloan,
  sum(a.calamityloan) as calamityloan,
  sum(a.longtermloan) as longtermloan,
  sum(a.pagibigcalamityloan) as pagibigcalamityloan,
  sum(a.otherloan) as otherloan,
  sum(a.cashadv) as cashadv,
  sum(a.otheradv) as otheradv from(
    select e.client as code,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as empname,pa.codename,pa.alias,ss.amt,
  (case when pa.codename='CASH LOAN' then ss.balance else 0 end) as cashloan,
  (case when pa.alias like '%SSSLOAN%' then ss.balance else 0 end) as sssloan,
  (case when pa.alias like '%HDMFLOAN%' then ss.balance else 0 end) as pagibigloan,
  (case when pa.codename='SSS CALAMITY LOAN' then ss.balance else 0 end) as calamityloan,
  (case when pa.codename='LONG TERM LOAN' then ss.balance else 0 end) as longtermloan,
  (case when pa.codename='PAG CALAMITY LOAN' then ss.balance else 0 end) as pagibigcalamityloan,
  (case when pa.alias like '%LOAN%' and pa.codename not in ('SSS SALARY LOAN','SSS LOAN','PAGIBIG SALARY LOAN','CASH LOAN','PAGIBIG LOAN','SSS CALAMITY LOAN') then ss.balance else 0 end) as otherloan,
  (case when pa.codename='CASH ADVANCE' then ss.balance else 0 end) as cashadv,
  (case when pa.alias like '%DEDUCTION%' and pa.codename not in ('CASH ADVANCE') then ss.balance else 0 end) as otheradv
  from standardsetup as ss
  Left join paccount as pa on pa.line=ss.acnoid
  LEFT JOIN employee AS emp ON emp.empid=ss.empid
  left join client as e on e.clientid = emp.empid
  left join division as d on d.divid = emp.divid
  left join client as dept on dept.clientid = emp.deptid
  where ss.balance>0
  and emp.level in $emplvl $filter $filter1 $filter2
  union all
  select e.client as code,concat(emp.emplast,', ',emp.empfirst,' ',emp.empmiddle) as empname,pa.codename,pa.alias,ss.amt,
  (case when pa.codename='CASH LOAN' then ss.balance else 0 end) as cashloan,
  (case when pa.alias like '%SSSLOAN%' then ss.balance else 0 end) as sssloan,
  (case when pa.alias like '%HDMFLOAN%' then ss.balance else 0 end) as pagibigloan,
  (case when pa.codename='SSS CALAMITY LOAN' then ss.balance else 0 end) as calamityloan,
  (case when pa.codename='LONG TERM LOAN' then ss.balance else 0 end) as longtermloan,
  (case when pa.codename='PAG CALAMITY LOAN' then ss.balance else 0 end) as pagibigcalamityloan,
  (case when pa.alias like '%LOAN%' and pa.codename not in ('SSS SALARY LOAN','SSS LOAN','PAGIBIG SALARY LOAN','CASH LOAN','PAGIBIG LOAN','SSS CALAMITY LOAN') then ss.balance else 0 end) as otherloan,
  (case when pa.codename='CASH ADVANCE' then ss.balance else 0 end) as cashadv,
  (case when pa.alias like '%DEDUCTION%' and pa.codename not in ('CASH ADVANCE') then ss.balance else 0 end) as otheradv
  from standardsetupadv as ss
  Left join paccount as pa on pa.line=ss.acnoid
  LEFT JOIN employee AS emp ON emp.empid=ss.empid
  left join client as e on e.clientid = emp.empid
  left join division as d on d.divid = emp.divid
  left join client as dept on dept.clientid = emp.deptid
  where ss.balance>0
  and emp.level in $emplvl $filter $filter1 $filter2
  ) as a
  group by code,empname";
    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
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


    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE BALANCES REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    if ($divid == 0) {
      $str .= $this->reporter->col('COMPANY : ALL COMPANY', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->col('AS OF ' . date("Y-m-d"), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endtable();
    $font_size += 2;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '70', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE NAME', '230', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CASH LOAN', '70', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SSS LOAN', '70', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PAG LOAN', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CAL LOAN', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('LONG TERM', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PAG CAL', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('OTHER LOAN', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CASH ADV', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('OTHER ADV', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '70', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $data = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '9';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($config);
    }

    $m = 'margin-top:10px;';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', $m);
    $str .= $this->displayHeader($config);
    $employee = '';
    $total = 0;
    $checkemployee = '';
    $subcashloan = 0;
    $subsssloan = 0;
    $subpagibigloan = 0;

    $subcalamityloan = 0;
    $sublongtermloan = 0;
    $subpagibigcalamitiyloan = 0;

    $subotherloan = 0;
    $subcashadv = 0;
    $subotheradv = 0;
    $grandtotal = 0;
    $font_size += 2;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['code'], '70', null, false, $border, '', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['empname'], '230', null, false, $border, '', 'LT', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data[$i]['cashloan'] == 0 ? '-' : number_format($data[$i]['cashloan'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['sssloan'] == 0 ? '-' : number_format($data[$i]['sssloan'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['pagibigloan'] == 0 ? '-' : number_format($data[$i]['pagibigloan'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['calamityloan'] == 0 ? '-' : number_format($data[$i]['calamityloan'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['longtermloan'] == 0 ? '-' : number_format($data[$i]['longtermloan'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['pagibigcalamityloan'] == 0 ? '-' : number_format($data[$i]['pagibigcalamityloan'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['otherloan'] == 0 ? '-' : number_format($data[$i]['otherloan'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['cashadv'] == 0 ? '-' : number_format($data[$i]['cashadv'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['otheradv'] == 0 ? '-' : number_format($data[$i]['otheradv'], 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $total = $data[$i]['cashloan'] + $data[$i]['sssloan'] + $data[$i]['pagibigloan'] + $data[$i]['calamityloan'] + $data[$i]['longtermloan'] + $data[$i]['pagibigcalamityloan'] + $data[$i]['otherloan'] + $data[$i]['cashadv'] + $data[$i]['otheradv'];
      $str .= $this->reporter->col($total  == 0 ? '-' : number_format($total, 2), '70', null, false, $border, '', 'RT', $font, $font_size, '', '', '');
      $subcashloan += $data[$i]['cashloan'];
      $subsssloan += $data[$i]['sssloan'];
      $subpagibigloan += $data[$i]['pagibigloan'];

      $subcalamityloan += $data[$i]['calamityloan'];
      $sublongtermloan += $data[$i]['longtermloan'];
      $subpagibigcalamitiyloan += $data[$i]['pagibigcalamityloan'];

      $subotherloan += $data[$i]['otherloan'];
      $subcashadv += $data[$i]['cashadv'];
      $subotheradv += $data[$i]['otheradv'];

      $grandtotal += $total;


      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand ', '70', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Total', '230', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col($subcashloan == 0 ? '-' : number_format($subcashloan, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($subsssloan == 0 ? '-' : number_format($subsssloan, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($subpagibigloan == 0 ? '-' : number_format($subpagibigloan, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($subcalamityloan == 0 ? '-' : number_format($subcalamityloan, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($sublongtermloan == 0 ? '-' : number_format($sublongtermloan, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($subpagibigcalamitiyloan == 0 ? '-' : number_format($subpagibigcalamitiyloan, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($subotherloan == 0 ? '-' : number_format($subotherloan, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($subcashadv == 0 ? '-' : number_format($subcashadv, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($subotheradv == 0 ? '-' : number_format($subotheradv, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');

    $str .= $this->reporter->col($grandtotal  == 0 ? '-' : number_format($grandtotal, 2), '70', null, false, $border, 'T', 'RT', $font, $font_size, '', '', '');

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class