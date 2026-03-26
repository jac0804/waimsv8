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

class employee_rate_report
{
  public $modulename = 'Employee Rate Report';
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
    $fields = ['radioprint'];

    if ($config['params']['companyid'] == 58) { //cdo
      array_push($fields, 'dbranchname');
    }

    array_push($fields, 'divrep', 'deptrep', 'dclientname');

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
    '' as deptrep,
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
    if ($deptid != 0 && $deptid != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divid != 0 && $divid != "") {
      $filter2 .= " and emp.divid = $divid";
    }
    $emplvl = $this->othersClass->checksecuritylevel($config);

    if ($config['params']['companyid'] == 58) { //cdo
      $branch     = $config['params']['dataparams']['dbranchname'];
      $branchid     = $config['params']['dataparams']['branchid'];


      if ($branch != "") {
        $filter2 .= " and emp.branchid = $branchid";
      }
    }

    $query = "select e.clientname,e.client,d.divname,dept.clientname as deptname,emp.empid,p.remarks,p.basicrate
              FROM ratesetup as p LEFT JOIN employee AS emp ON emp.empid=p.empid
              left join client as e on e.clientid = emp.empid
              left join division as d on d.divid = emp.divid
              left join client as dept on dept.clientid = emp.deptid
              where date(p.dateend)='9999-12-31' and emp.level in $emplvl $filter $filter1 $filter2
              order by e.clientname";
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

    if ($config['params']['companyid'] == 58) { //cdo
      $branch = $config['params']['dataparams']['dbranchname'];
    }


    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE RATE REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($config['params']['companyid'] == 58) { //cdo
      $str .= $this->reporter->startrow();
      if ($branch == '') {
        $str .= $this->reporter->col('BRANCH : ALL BRANCH', NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
      } else {
        $str .= $this->reporter->col('BRANCH : ' . strtoupper($branch), NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
      }

      $str .= $this->reporter->endrow();
    }
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

    $str .= $this->reporter->col('AS OF ' . date("Y-m-d"), NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '0px 0px 5px 0px', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '120', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '300', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('R A T E', '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('R E M A R K S', '250', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
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

    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->basicrate == 0 ? '-' : number_format($data->basicrate, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '30', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->remarks, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');


      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '300px', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200px', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');


    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class