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

class employee_loan_balances
{
  public $modulename = 'Employee Loan Balances';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '900'];

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

    $query = "SELECT e.clientname,e.client,ss.docno,ss.remarks,date(ss.dateid) as dateid,pa.codename,ss.amt,ss.amortization,ss.balance from standardsetup as ss
          Left join paccount as pa on pa.line=ss.acnoid
          LEFT JOIN employee AS emp ON emp.empid=ss.empid
          left join client as e on e.clientid = emp.empid
          left join division as d on d.divid = emp.divid
          left join client as dept on dept.clientid = emp.deptid
          where ss.balance<>0 and emp.level in $emplvl $filter $filter1 $filter2
          order by e.clientname ";

    return $this->coreFunctions->opentable($query);
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
    $str .= $this->reporter->col('EMPLOYEE LOAN BALANCES REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
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
    // $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AS OF ' . date("Y-m-d"), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE &nbsp NAME', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ACCOUNT &nbsp NAME', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PRINCIPAL &nbsp AMT.', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('AMORTIZATION', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('REMARKS', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
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
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->codename, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->amt == 0 ? '-' : number_format($data->amt, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->amortization == 0 ? '-' : number_format($data->amortization, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->balance == 0 ? '-' : number_format($data->balance, 2), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '10', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->remarks, '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');


      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '300px', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class