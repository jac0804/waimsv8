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

class payroll_monthly_summary
{
  public $modulename = 'Payroll Monthly Summary';
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
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1150'];

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

    $fields = ['month', 'year'];
    $col2 = $this->fieldClass->create($fields);

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
    '' as month,
    '' as deptrep,
    month(now()) as month,
    left(now(),4) as year
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

    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);


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
    $filter3 = " and pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER')";
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "SELECT e.clientname,e.client,d.divname,dept.clientname as deptname,
p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
p.acnoid,pa.alias,p.db,p.cr,pa.codename,pa.uom,p.qty,pa.alias,emp.empid,p.qty2
FROM paytrancurrent as p LEFT JOIN employee AS emp ON emp.empid=p.empid
left join client as e on e.clientid = emp.empid
left join division as d on d.divid = emp.divid
left join batch on batch.line=p.batchid
left join client as dept on dept.clientid = emp.deptid
left join paccount as pa on pa.line=p.acnoid
where  month(p.dateid)= '" . $month . "' and year(p.dateid)= '" . $year . "' and emp.level in $emplvl $filter $filter1 $filter2 $filter3
order by e.clientname";



    return $this->coreFunctions->opentable($query);
  }

  private function getcount($empid, $year, $month)
  {
    return $this->coreFunctions->datareader("select count(p.batchid) as value from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid where pa.alias not in ('YIS','YIM','YIP','YSR','YER','YMR','YPR','MPF','MPFER') and year(dateid) = '" . $year . "' and month(dateid) = '" . $month . "'  and p.empid=? group by p.empid", [$empid]);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $divname    = $config['params']['dataparams']['divname'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);

    $str = '';
    $layoutsize = 1000;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('P A Y R O L L &nbsp M O N T H L Y &nbsp S U M M A R Y', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($divname == "") {
      $str .= $this->reporter->col('COMPANY : ALL COMPANY', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    } else {
      $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    }

    if ($deptname == "") {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), '150', null, false, $border, '', 'L', $font, '11', '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    $monthNum = $month;
    $monthname = date("F", mktime(0, 0, 0, $monthNum, 10));
    $str .= $this->reporter->col('For the Month of ' . strtoupper($monthname) . ' and Year ' . strtoupper($year), NULL, null, false, $border, '', 'L', $font, '11', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('EARNINGS', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DEDUCTIONS', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('No.', '20', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Employee Name', '120', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs of Work', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Basic Pay', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Absent', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Late / Undertime', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Leave', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Regular' . '<br/>' . 'OT', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Ndiff/' . '<br/>' . 'OT', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Restday', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Restday' . '<br/>' . 'OT', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Special', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Special' . '<br/>' . 'OT', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Legal', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Legal' . '<br/>' . 'OT', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Earnings', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('WHT', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Pagibig', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Loans', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Total Deduction', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('NET PAY', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Allowance', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Hrs', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Total Earnings', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('SSS', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Pagibig Loan', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Other Deduction', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Amt', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('PHIC', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('SSS Loan', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];

    $border = '.5px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $gtotnetpay = 0;
    $gtotearn = 0;
    $gtotded = 0;

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


    $i = 0;
    $c = 0;
    $b = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
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
      } elseif ($data->alias == 'LEG') {
        $legal = $legal + $data->db - $data->cr;
        $totalearn = $totalearn + $data->db - $data->cr;
        $qtylegal = $qtylegal + $data->qty;
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
      } else {
        if ($data->cr > 0) {
          $otherdeduction = $otherdeduction + $data->cr;
          $totalded = $totalded + $data->cr;
        } elseif ($data->db > 0) {
          $otherearnings = $otherearnings + $data->db;
          $totalearn = $totalearn + $data->db;
        }
      }
      $month = intval($config['params']['dataparams']['month']);
      $year = intval($config['params']['dataparams']['year']);

      if ($c == 0) {
        $c = $this->getcount($data->empid, $year, $month);
      }

      $i = $i + 1;
      if ($i == $c) {
        $b = $b + 1;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($b . '.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($clientname, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtybasicpay == 0 ? '-' : number_format($qtybasicpay, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($basicpay == 0 ? '-' : number_format($basicpay, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyabsent == 0 ? '-' : number_format($qtyabsent, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $qtylateundertime = $qtylate + $qtyundertime;
        $str .= $this->reporter->col($qtylateundertime == 0 ? '-' : number_format($qtylateundertime, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyleave == 0 ? '-' : number_format($qtyleave, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyrot == 0 ? '-' : number_format($qtyrot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyndiffot == 0 ? '-' : number_format($qtyndiffot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyrestday == 0 ? '-' : number_format($qtyrestday, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyrestdayot == 0 ? '-' : number_format($qtyrestdayot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyspecial == 0 ? '-' : number_format($qtyspecial, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtyspecialot == 0 ? '-' : number_format($qtyspecialot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtylegal == 0 ? '-' : number_format($qtylegal, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($qtylegalot == 0 ? '-' : number_format($qtylegalot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');

        $tototherearnings = $otherearnings + $bonus;
        $str .= $this->reporter->col($tototherearnings == 0 ? '-' : number_format($tototherearnings, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($wht == 0 ? '-' : number_format($wht, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($hdmf == 0 ? '-' : number_format($hdmf, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($loan == 0 ? '-' : number_format($loan, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalded == 0 ? '-' : number_format($totalded, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($netpay == 0 ? '-' : number_format($netpay, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($allowance == 0 ? '-' : number_format($allowance, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($absent == 0 ? '-' : number_format($absent, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $totlateundertime = $late + $undertime;
        $str .= $this->reporter->col($totlateundertime == 0 ? '-' : number_format($totlateundertime, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($leave == 0 ? '-' : number_format($leave, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($rot == 0 ? '-' : number_format($rot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($ndiffot == 0 ? '-' : number_format($ndiffot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($restday == 0 ? '-' : number_format($restday, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($restdayot == 0 ? '-' : number_format($restdayot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($special == 0 ? '-' : number_format($special, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($specialot == 0 ? '-' : number_format($specialot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($legal == 0 ? '-' : number_format($legal, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($legalot == 0 ? '-' : number_format($legalot, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($totalearn == 0 ? '-' : number_format($totalearn, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sss == 0 ? '-' : number_format($sss, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($hdmfloan == 0 ? '-' : number_format($hdmfloan, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($otherdeduction == 0 ? '-' : number_format($otherdeduction, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($phic == 0 ? '-' : number_format($phic, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($sssloan == 0 ? '-' : number_format($sssloan, 2), '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
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
        $allowance = 0;
        $netpay = 0;
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
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
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
}//end class