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

class loan_deduction_report
{
  public $modulename = 'Monthly Deduction Report';
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
    $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'month', 'year'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['dloantype'];
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
    '' as code,
    '' as codename,
    '' as dloantype,
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
    '' as month,
    '' as year
    ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    $code = $config['params']['dataparams']['code'];
    $divid      = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $sectid     = $config['params']['dataparams']['sectid'];
    $emplvl = $this->othersClass->checksecuritylevel($config);
    $month = intval($config['params']['dataparams']['month']);
    $year = intval($config['params']['dataparams']['year']);

    $filter = '';
    if ($code != '') $filter = "and pa.code = '$code'";
    if ($deptid != 0) $filter .= " and emp.deptid = $deptid";
    if ($divid != 0) $filter .= " and emp.divid = $divid";
    if ($sectid != 0) $filter .= " and emp.sectid = $sectid";

    $query = "select pa.code, pa.codename, concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle) as empname, 
                    ss.docno, ifnull(sum(st.cr),0) as amt, ss.balance
              from standardsetup as ss
              left join standardtrans as st on ss.trno = st.trno
              left join employee as emp on ss.empid = emp.empid
              left join paccount as pa on pa.line = ss.acnoid
              where month(st.dateid)= '" . $month . "' and year(st.dateid)= '" . $year . "' and emp.level in $emplvl $filter
              group by pa.code, pa.codename, emp.emplast, emp.empfirst, emp.empmiddle, ss.docno, ss.balance
              having ifnull(sum(st.cr),0)<>0
              order by pa.code, ss.docno";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $border = '1px solid';
    $font = 'Tahoma';
    $font_size = '11';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LOAN DEDUCTION REPORT', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year:' . $config['params']['dataparams']['year'], '150', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Month:' . $config['params']['dataparams']['month'], '250', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Employee Name', '350', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Reference #', '350', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Amt', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Balance', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    // $font = $this->companysetup->getrptfont($config['params']);
    $font = "Tahoma";
    $fontsize = "10";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->displayHeader($config);



    $loanType = '';
    $isFirst = true;
    $subtotal = 0;
    $grandTotal = 0;

    foreach ($result as $data) {
      if ($data->codename != $loanType) {

        if (!$isFirst) {

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '', '10', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
          $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
          $str .= $this->reporter->col('Subtotal: ', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          // spacing
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '', '20', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $loanType = $data->codename;
        $subtotal = 0;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($loanType, '200', null, false, '1px dotted', 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '800', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $isFirst = false;
      }


      $subtotal += $data->amt;
      $grandTotal += $data->amt;

      // report details
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->code, '100', null, false, '1px dotted', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data->empname, '350', null, false, '1px dotted', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col($data->docno, '350', null, false, '1px dotted', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, '1px dotted', 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, '1px dotted', 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '', '10', false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Subtotal: ', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted', 'T', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col(' ', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // grand total
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col(number_format($grandTotal, 2), '100', null, false, $border, 'T', 'RT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}
