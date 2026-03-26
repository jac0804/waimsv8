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

class leave_balance_report
{
  public $modulename = 'Leave Balance Report';
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
    $fields = ['radioprint', 'print'];
    $col1 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("
    select 'default' as print");
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
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select cl.client empcode, concat(upper(emp.emplast), ', ', emp.empfirst, ' ', left(emp.empmiddle, 1), '.') as employee, l.docno, 
      acc.codename as accname, l.days as entitled, l.bal, l.prdstart, l.prdend 
      from leavesetup as l 
      left join employee as emp on emp.empid = l.empid
      left join paccount as acc on acc.line = l.acnoid
      left join department as dept on dept.deptid = emp.deptid
      left join division as d on d.divid = emp.divid
      left join section as sec on sec.sectid = emp.sectid
      left join client as cl on cl.clientid = emp.empid
      where l.bal > 0 and emp.level in $emplvl
      order by employee";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LEAVE BALANCE', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '4px');
    $str .= $this->reporter->col('', '950', null, false, $border, '', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->printline();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee Name', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Document #', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Account name', '220', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Entitled', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Balance', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Period Start', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Period End', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 55;
    $page = 55;
    $str = '';
    $layoutsize = '1000';
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->employee, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->accname, '220', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->entitled, '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->bal, '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(date("Y-m-d", strtotime($data->prdstart)), '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(date("Y-m-d", strtotime($data->prdend)), '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '220', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', '', $font, $font_size, '', '', '');
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
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
