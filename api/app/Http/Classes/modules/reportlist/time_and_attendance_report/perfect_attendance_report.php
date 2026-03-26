<?php

namespace App\Http\Classes\modules\reportlist\time_and_attendance_report;

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

class perfect_attendance_report
{
  public $modulename = 'Perfect Attendance Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => 1200];

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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep', 'sectrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['start', 'end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

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
    '' as sectrep,
    '' as sectname,
    '' as sectid,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
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
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {

    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $sectid     = $config['params']['dataparams']['sectid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter = '';

    if ($client != "") $filter .= " and cl.client = '$client'";
    if ($deptid != 0) $filter .= " and emp.deptid = $deptid";
    if ($divid != 0) $filter .= " and emp.divid = $divid";
    if ($sectid != 0) $filter .= " and emp.sectid = $sectid";

    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select client, employee 
      from (select cl.client, concat(upper(emp.emplast), ', ', emp.empfirst, ' ', left(emp.empmiddle, 1), '.') as employee, 
                   sum(t.absdays + t.latehrs + t.underhrs) as check_attendance
            from timecard as t 
            left join employee as emp on emp.empid = t.empid 
            left join department as dept on dept.deptid = emp.deptid 
            left join division as d on d.divid = emp.divid 
            left join section as sec on sec.sectid = emp.sectid
            left join client as cl on cl.clientid = emp.empid
            where dateid between '" . $start . "' and '" . $end . "' and emp.level in $emplvl $filter
            group by cl.client, employee,emp.emplast,emp.empfirst,emp.empmiddle
            having sum(t.absdays + t.latehrs + t.underhrs) = 0) as a
      order by employee";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $sectid     = $config['params']['dataparams']['sectid'];
    $sectname    = $config['params']['dataparams']['sectname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '1000';
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PERFECT ATTENDANCE', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('from ' . strtoupper($start) . ' to ' . strtoupper($end), null, null, false, $border, '', 'C', $font, '10', '', '', '', 0, '', 0, 8);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col('Division : ', '70', null, false, $border, '', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col($divid == 0 ? 'All Divisions' : strtoupper($divname), null, null, false, $border, 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Department : ', '80', null, false, $border, '', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col($deptid == 0 ? 'All Departments' : strtoupper($deptname), null, null, false, $border, 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Section : ', '62', null, false, $border, '', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->col($sectid == 0 ? 'All sections' : strtoupper($sectname), null, null, false, $border, 'B', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '750', null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '110', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE NAME', '200', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $count = 55;
    $page = 55;

    $str = '';
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '110', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->employee, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '160', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '160', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '160', null, false, $border, '', '', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '110', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->printline();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
