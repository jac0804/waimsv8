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
use DateTime;

class earning_and_deduction_list_report
{
  public $modulename = 'Earning/Deduction List';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
    $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'dclientname', 'repearnded'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'repearnded.lookupclass', 'lookupearndedrpt');
    $fields = ['start', 'end'];

    if ($companyid == 58) {
      array_push($fields, 'batch', 'radioearningtype');
    }

    array_push($fields, 'print');

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

    if ($companyid == 58) {
      data_set($col2, 'batch.lookupclass', 'lookupbatchrepcdo');
    }

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'default' as print,
      'default' as radioearningtype,
      '' as dclientname,
      '' as divid,
      '' as divname,
      '' as divrep,
      '' as division,
      '' as deptid,
      '' as deptname,
      '' as deptrep,
      '' as sectrep,
      '' as sectname,
      '' as sectid,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '' as earndedid,
      '' as earnded,
      '' as batch,
      '0' as batchid,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end
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
    $layout = $config['params']['dataparams']['radioearningtype'];

    switch ($layout) {
      case 'peraccount':
        return $this->peraccount_layout($config);
        break;
      case 'default':
        return $this->reportDefaultLayout($config);
        break;
    }
  }


  public function reportDefault($config)
  {

    $divid = $config['params']['dataparams']['divid'];
    $deptid = $config['params']['dataparams']['deptid'];
    $sectid = $config['params']['dataparams']['sectid'];
    $earndedid = $config['params']['dataparams']['earndedid'];
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $empcode = $config['params']['dataparams']['client'];

    $filter = "";
    if ($divid != "") $filter .= " and e.divid='" . $divid . "' ";
    if ($deptid != "") $filter .= " and e.dept='" . $deptid . "' ";
    if ($sectid != "") $filter .= " and e.orgsection='" . $sectid . "' ";
    if ($earndedid != "") $filter .= " and ss.acnoid='" . $earndedid . "' ";
    if ($empcode != "") $filter .= " and emp.client='" . $empcode . "' ";

    $query = "select a.docno,date(a.dateid) as dateid,right(a.empcode,10) as empcode,a.empname,date(a.effdate) as effdate,a.codename,a.alias,sum(a.amt) as amt,sum(a.balance) as bal,sum(a.amortization) as amortization from(
      select ss.docno,ss.dateid,
      emp.client as empcode,
      concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,ss.effdate,pa.codename,pa.alias,ss.amt,ss.balance,ss.amortization
      from standardsetup as ss
      left join employee as e on ss.empid=e.empid
      left join client as emp on emp.clientid=e.empid
      left join paccount as pa on pa.line=ss.acnoid
      where date(ss.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      union all
      select ss.docno,ss.dateid,
      emp.client as empcode,
      concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,ss.effdate,pa.codename,pa.alias,ss.amt,ss.balance,ss.amortization
      from standardsetupadv as ss
      left join employee as e on ss.empid=e.empid
      left join client as emp on emp.clientid=e.empid
      left join paccount as pa on pa.line=ss.acnoid
      where date(ss.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      ) as a
      group by docno,dateid,empcode,empname,effdate,codename,alias
      order by empname
      ";

    // var_dump($query);
    return $this->coreFunctions->opentable($query);
  }


  public function reportDefault2($config)
  {

    $divid = $config['params']['dataparams']['divid'];
    $deptid = $config['params']['dataparams']['deptid'];
    $sectid = $config['params']['dataparams']['sectid'];
    $earndedid = $config['params']['dataparams']['earndedid'];
    // $start = $config['params']['dataparams']['start'];
    // $end = $config['params']['dataparams']['end'];
    $empcode = $config['params']['dataparams']['client'];
    $batch = $config['params']['dataparams']['batch'];
    $batchid = $config['params']['dataparams']['batchid'];
    // var_dump($batchid);

    $filter = "";
    if ($divid != "") $filter .= " and e.divid='" . $divid . "' ";
    if ($deptid != "") $filter .= " and e.dept='" . $deptid . "' ";
    if ($sectid != "") $filter .= " and e.orgsection='" . $sectid . "' ";
    if ($earndedid != "") $filter .= " and ss.acnoid='" . $earndedid . "' ";
    if ($empcode != "") $filter .= " and emp.client='" . $empcode . "' ";
    if ($batch != "") $filter .= " and str.batchid='" . $batchid . "' ";

    $query = "select a.docno,date(a.dateid) as dateid,
      a.empname,date(a.effdate) as effdate,a.amt,
      a.balance as bal,sum(a.amortization) as amortization, quincena,a.codename,deductiondate,payrolldate
      from(
      select ss.docno,ss.dateid,
      emp.client as empcode,
      concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
      ss.effdate,ss.amt,ss.balance,ss.amortization,date(b.startdate) as payrolldate,
      ss.amortization as quincena,pa.codename,date(b.enddate) as deductiondate
      from standardsetup as ss
      left join employee as e on ss.empid=e.empid
      left join client as emp on emp.clientid=e.empid
      left join paccount as pa on pa.line=ss.acnoid
      left join standardtrans as str on str.trno=ss.trno
      left join batch as b on b.line=str.batchid
      where  1 =1 " . $filter . "
      union all
      select ss.docno,ss.dateid,
      emp.client as empcode,
      concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,ss.effdate,
      ss.amt,ss.balance,ss.amortization,date(b.startdate) as payrolldate,
      ss.amortization as quincena,pa.codename,date(b.enddate) as deductiondate
      from standardsetupadv as ss
      left join employee as e on ss.empid=e.empid
      left join client as emp on emp.clientid=e.empid
      left join paccount as pa on pa.line=ss.acnoid
      left join standardtrans as str on str.trno=ss.trno
      left join batch as b on b.line=str.batchid
      where  1 =1 " . $filter . "
       ) as a
      group by docno,a.dateid,empcode,empname,a.effdate, quincena,a.amt,a.balance,a.codename,deductiondate,payrolldate
      order by empname;";

    // var_dump($query);
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

    $division = $config['params']['dataparams']['divname'];
    $dept = $config['params']['dataparams']['deptname'];
    $sect = $config['params']['dataparams']['sectname'];

    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $earnded = $config['params']['dataparams']['earnded'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EARNING/DEDUCTION LIST REPORT', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date From: <b>' . $start . '</b> To: <b>' . $end . '</b>', null, null, false, $border, '', 'C', $font, '13', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Division: <b>' . ($division == '' ? 'ALL COMPANY' : $division) . '</b>&nbsp;&nbsp;&nbsp;Department: <b>' . ($dept == '' ? 'ALL DEPARTMENTS' : $dept) . '</b>&nbsp;&nbsp;&nbsp;Section: <b>' . ($sect == '' ? 'ALL SECTIONS' : $sect) . '</b>', null, null, false, $border, '', 'C', $font, '13', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Account Name: ' . ($earnded == '' ? 'ALL ACCOUNTS' : $earnded) . '</b>', null, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Docno', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Date', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Code', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Employee Name', '120', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('Effectivity of Deduction', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Earning/Deduction', '120', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('Amount', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Balance', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Amortization', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

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

    $count = 75;
    $page = 75;
    $layoutsize = '1000';

    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    $totalamt = 0;
    $totalbal = 0;
    $totalamortization = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      // $amt = $data->amt + ($data->db-$data->cr);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->docno, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empcode, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->effdate, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->codename, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col(number_format($data->amt, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->bal, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->amortization, 2), '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
      $totalamt += $data->amt;
      $totalbal += $data->bal;
      $totalamortization += $data->amortization;
    }
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('TOTAL', '120', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col(number_format($totalamt, 2), '80', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbal, 2), '80', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamortization, 2), '80', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  private function displayHeader2($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    //0070C0
    $fontcolor = '#000000'; //black
    $bgcolors = '#0070C0'; //deep sky blue
    $bgcolors2 = '#00B0F0'; //sky blue
    $result = $this->reportDefault2($config);
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $res = json_decode(json_encode($result), true);

    $str = '';
    $layoutsize = '1000';


    $str .= '<br/><br/><br/><br/>';

    $datehere = $res[0]['payrolldate'];
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($cdate, null, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //  $str .= $this->reporter->col('EMPLOYEE NAME', '300', null,  $bgcolors, 
    $str .= $this->reporter->col(isset($res[0]['codename']) ? $res[0]['codename'] : '', null, null,  $bgcolors, $border, '', 'C', $font, '13', 'B', $fontcolor, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $ddate = $res[0]['deductiondate'];
    $dedauction = new DateTime($ddate);
    $deductdate = $dedauction->format('F d, Y');
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NAME', '250', null, $bgcolors2, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->col('START OF DEDUCTION', '160', null, $bgcolors2, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->col('TOTAL LOAN AMOUNT', '160', null, $bgcolors2, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->col(strtoupper($deductdate), '150', null, $bgcolors2, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('TOTAL DEDUCTION', '140', null, $bgcolors2, $border, 'TBLR', 'C', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('REMAINING BAL', '140', null, $bgcolors2, $border, 'TB', 'C', $font, $font_size, 'B',  $fontcolor, '8px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function peraccount_layout($config)
  {
    $result = $this->reportDefault2($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 75;
    $page = 75;
    $layoutsize = '1000';

    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader2($config);

    $totalamt = 0;
    $totalbal = 0;
    $totalperquincena = 0;
    $totaldeduction = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      // $amt = $data->amt + ($data->db-$data->cr);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->empname, '250', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->effdate, '160', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2), '160', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->quincena, 2), '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->amortization, 2), '140', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->bal, 2), '140', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader2($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
      $totalamt += $data->amt;
      $totalbal += $data->bal;
      $totalperquincena += $data->quincena;
      $totaldeduction += $data->amortization;
    }
    $str .= $this->reporter->addline();

    $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('TOTAL:', '160', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '160', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalperquincena, 2), '150', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldeduction, 2), '140', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalbal, 2), '140', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PREPARED BY:', '1000', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '750', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAYROLL OFFICER', '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '750', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }


  public function sbcscript($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 58) { // CDO
      return [

        'report' => '
        switch (state.reportdata.params.radioearningtype) {
        case "peraccount":
                state.reportobject.txtfield.col2.start.style="display:none"
                state.reportobject.txtfield.col2.end.style="display:none"
                state.reportobject.txtfield.col2.batch.style="display:block"
                state.reportobject.txtfield.col2.batch.required=true
                state.reportobject.txtfield.col1.repearnded.required=true
                break;
            default:
                state.reportobject.txtfield.col2.start.style="display:block"
                state.reportobject.txtfield.col2.end.style="display:block"
                state.reportobject.txtfield.col2.batch.style="display:none"
                state.reportobject.txtfield.col2.batch.required=false
                state.reportobject.txtfield.col1.repearnded.required=false
                break;
        }
        '
      ];
    } else {
      return true;
    }
  }
}//end class