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

class earning_and_deduction_report
{
  public $modulename = 'Earning and Deduction Report';
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
    $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'tpaygroup', 'repearnded'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupearndedaccounts');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'tpaygroup.label', 'Pay Group');
    data_set($col1, 'repearnded.lookupclass', 'lookupearndedrpt');
    $fields = ['start', 'end', 'print'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'default' as print,
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
      '' as tpaygroup,
      '' as paygroupid,
      '' as earndedid,
      '' as earnded,
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

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    $divid = $config['params']['dataparams']['divid'];
    $deptid = $config['params']['dataparams']['deptid'];
    $sectid = $config['params']['dataparams']['sectid'];
    $tpaygroup = $config['params']['dataparams']['tpaygroup'];
    $earndedid = $config['params']['dataparams']['earndedid'];
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];

    $filter = "";
    if ($divid != "") $filter .= " and e.divid='" . $divid . "' ";
    if ($deptid != "") $filter .= " and e.dept='" . $deptid . "' ";
    if ($sectid != "") $filter .= " and e.orgsection='" . $sectid . "' ";
    if ($tpaygroup != "") $filter .= " and e.paygroup='" . $tpaygroup . "' ";
    if ($earndedid != "") $filter .= " and ss.acnoid='" . $earndedid . "' ";

    $query = "select a.empname,
    a.idno,a.empid, 
    sum(a.amt) as amt
    from(
      select
          concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,ss.empid,
          ss.amt as amt, c.client as idno
          from standardsetup as ss
          left join employee as e on ss.empid = e.empid
          left join client as c on c.clientid=e.empid
          where date(ss.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      union all
      select
          concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,ss.empid,
          ss.amt as amt, c.client as idno
          from standardsetupadv as ss
          left join employee as e on ss.empid = e.empid
          left join client as c on c.clientid=e.empid
          where date(ss.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
          order by empname
    ) as a
     group by a.empname, idno, empid";
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
    $tpaygroup = $config['params']['dataparams']['tpaygroup'];
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
    $str .= $this->reporter->col('EARNING/DEDUCTION REPORT', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->col('Pay Group: ' . ($tpaygroup == '' ? 'All Paygroup' : $tpaygroup), null, null, false, $border, '', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Account Name: ' . ($earnded == '' ? 'ALL ACCOUNTS' : $earnded) . '</b>', null, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('I D  N O.', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '450', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('A M O U N T', '200', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
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

    $total = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      // $amt = $data->amt + ($data->db-$data->cr);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->idno, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, '450', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2), '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
      $total += $data->amt;
    }

    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Grand Total&nbsp;&nbsp;&nbsp;', '450px', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '200px', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class