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

class total_net_pay
{
  public $modulename = 'Total Net Pay';
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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep', 'sectrep', 'batchrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'batchrep.lookupclass', 'lookupbatchrep');
    data_set($col1, 'batchrep.required', true);

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
    '' as sectrep,
    '' as sectname,
    '' as sectid,
    '' as deptrep,
    '' as batchid,
    '' as batch,
    '' as batchrep,
    '' as line
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
    $batch     = $config['params']['dataparams']['line'];

    $filter = '';

    if ($client != "") $filter .= " and client.client = '$client'";
    if ($deptid != 0) $filter .= " and emp.deptid = $deptid";
    if ($divid != 0) $filter .= " and emp.divid = $divid";
    if ($sectid != 0) $filter .= " and emp.sectid = $sectid";

    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select client.client as empcode, concat(upper(emp.emplast), ', ', emp.empfirst, ' ', left(emp.empmiddle, 1), '.') as employee, sum(p.db) as netpay
    from paytranhistory as p
    inner join employee as emp on emp.empid=p.empid
    inner join paccount as accnt on accnt.line=p.acnoid
    left join department as dept on dept.deptid = emp.deptid
    left join division as d on d.divid = emp.divid
    left join section as sec on sec.sectid = emp.sectid
    left join batch on batch.line=p.batchid
    left join client on client.clientid = emp.empid
    where p.batchid = " . $batch . " and emp.level in $emplvl $filter
    group by empcode, employee,emp.emplast,emp.empfirst,emp.empmiddle
    union all
    select client.client as empcode, concat(upper(emp.emplast), ', ', emp.empfirst, ' ', left(emp.empmiddle, 1), '.') as employee, sum(p.db) as netpay
    from paytrancurrent as p
    inner join employee as emp on emp.empid=p.empid
    inner join paccount as accnt on accnt.line=p.acnoid
    left join department as dept on dept.deptid = emp.deptid
    left join division as d on d.divid = emp.divid
    left join section as sec on sec.sectid = emp.sectid
    left join batch on batch.line = p.batchid
    left join client on client.clientid = emp.empid
    where p.batchid = " . $batch . " and emp.level in $emplvl $filter
    group by empcode, employee,emp.emplast,emp.empfirst,emp.empmiddle
    order by employee";


    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $sectid     = $config['params']['dataparams']['sectid'];
    $sectname    = $config['params']['dataparams']['sectname'];
    $batchid     = $config['params']['dataparams']['batchid'];
    $batchname   = $config['params']['dataparams']['batch'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '11';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LEAVE DETAILS', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Batch : ' . ($batchid == '' ? 'All Batches' : strtoupper($batchname)), null, null, false, $border, '', 'C', $font, '11', '', '', '');
    $str .= $this->reporter->col('Division : ' . ($divid == 0 ? 'All Divisions' : strtoupper($divname)), null, null, false, $border, '', 'C', $font, '11', '', '', '');
    $str .= $this->reporter->col('Department : ' . ($deptid == 0 ? 'All Departments' : strtoupper($deptname)), null, null, false, $border, '', 'C', $font, '11', '', '', '');
    $str .= $this->reporter->col('Section : ' . ($sectid == 0 ? 'All sections' : strtoupper($sectname)), null, null, false, $border, '', 'C', $font, '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '750', null, false, $border, '', '', $font, '11', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->printline();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NO. ', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE CODE', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE NAME', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('NET PAY', '200', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
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

    $grandTotal = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($key + 1, '200', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empcode, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->employee, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->netpay, 2), '200', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }

      $grandTotal += $data->netpay;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total: ', '200', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($grandTotal, '200', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ______________________', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Checked By : ______________________', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Approved By : ______________________', null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
