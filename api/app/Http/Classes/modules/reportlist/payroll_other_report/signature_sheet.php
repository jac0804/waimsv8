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

class signature_sheet
{
  public $modulename = 'Signature Sheet';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep', 'batchrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupearndedaccounts');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'batchrep.lookupclass', 'lookupbatchrep');
    data_set($col1, 'batchrep.required', true);


    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'default' as print,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '' as dclientname,
      '' as divid,
      '' as divname,
      '' as divrep,
      '' as division,
      '' as deptid,
      '' as deptname,
      '' as deptrep,
      '' as batchrep,
      '' as batchid,
      '' as line
      ");
  }

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
    $client     = $config['params']['dataparams']['client'];
    $divid = $config['params']['dataparams']['divid'];
    $deptid = $config['params']['dataparams']['deptid'];
    $batchid      = $config['params']['dataparams']['line'];


    $filter = "";



    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0) {
      $filter .= " and dept.clientid = $deptid";
    }
    if ($divid != 0) {
      $filter .= " and emp.divid = $divid";
    }

    if ($batchid != '') {
      $filter .= " and p.batchid = " . $batchid . " ";
    }



    $query = "select clientname,client,divname,deptname,sum(earnings) as earnings, sum(deductions) as deductions, sum(netpay) as netpay from ( 
              select e.clientname,e.client,d.divname,dept.clientname as deptname,
              (p.db) as earnings,(p.cr) as deductions,(p.db-p.cr) as netpay
              FROM paytrancurrent as p
              LEFT JOIN employee AS emp ON emp.empid=p.empid
              left join client as e on e.clientid = emp.empid
              left join division as d on d.divid = emp.divid
              left join client as dept on dept.clientid = emp.deptid
              left join paccount as pa on pa.line=p.acnoid
              where pa.alias not in ('PPBLE','YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER','LATE','UNDERTIME','ABSENT')
              $filter
              union all
              select e.clientname,e.client,d.divname,dept.clientname as deptname,
              (p.db-p.cr) as earnings, 0 as deductions,(p.db-p.cr) as netpay
              FROM paytrancurrent as p
              LEFT JOIN employee AS emp ON emp.empid=p.empid
              left join client as e on e.clientid = emp.empid
              left join division as d on d.divid = emp.divid
              left join client as dept on dept.clientid = emp.deptid
              left join paccount as pa on pa.line=p.acnoid
              where pa.alias in ('LATE','UNDERTIME','ABSENT')
              $filter
              ) as x group by clientname,client,divname,deptname order by clientname,client";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px dotted';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $division = $config['params']['dataparams']['divname'];


    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SIGNATURE SHEET', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }

  public function default_group_head($config)
  {


    $dept = $config['params']['dataparams']['deptname'];
    $batchid = $config['params']['dataparams']['line'];



    $query = "
    select date(startdate) as start,date(enddate) as end from batch where batch.line=$batchid
    ";



    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);


    $font = $this->companysetup->getrptfont($config['params']);
    $border = '1px dotted';

    $str = '';
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, '13', '', '', '');

    $s = isset($result[0]['start']) ? $result[0]['start'] : '';
    $e = isset($result[0]['end']) ? $result[0]['end'] : '';
    $str .= $this->reporter->col('Batch From: ' . $s . ' To ' . $e, 400, null, false, $border, '', 'R', $font, '13', '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department:' . ($dept == '' ? 'ALL DEPARTMENTS' : $dept), 400, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function default_table_col($config)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '14';
    $border = '1px dotted';

    $str = '';
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('CODE', '125', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE NAME', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TOTAL EARNINGS', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TOTAL DEDUCTIONS', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('NET PAY', '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SIGNATURE', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);


    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '14';
    $padding = '';
    $margin = '';

    $count = 60;
    $page = 59;
    $layoutsize = '1000';

    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);



    $total = 0;
    $division = '';
    $totalEarnings = 0;
    $totalDeductions = 0;
    $totalNetpay = 0;
    foreach ($result as $key => $data) {
      $border = '1px solid';
      if ($division == '' || $division != $data->divname) {
        $division = $data->divname;

        $str .= $this->default_group_head($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('COMPANY:' . ($division == '' ? 'ALL COMPANY' : $division), 1000, null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->default_table_col($config);
      }
      $str .= $this->reporter->begintable($layoutsize);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('<br>', '125', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '125', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->earnings, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->deductions, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->netpay, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1px dotted', 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $totalEarnings += $data->earnings;
      $totalDeductions += $data->deductions;
      $totalNetpay += $data->netpay;
      if ($this->reporter->linecounter == $page) {
        $border = '1px dotted';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp', '125', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '125', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        $str .= $this->displayHeader($config);
        $str .= $this->default_group_head($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('COMPANY:' . ($division == '' ? 'ALL COMPANY' : $division), 1000, null, false, $border, '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->default_table_col($config);
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '125', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totalEarnings, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totalDeductions, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totalNetpay, 2), '100', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class