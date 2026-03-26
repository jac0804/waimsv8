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

class earning_and_deduction_cash_advance_report
{
  public $modulename = 'Earning and Deduction Cash Advance Report';
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
    $fields = ['radioprint', 'dclientname', 'repearnded'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
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
      '' as dclientname,'' as client,'' as clientname,
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
    $earndedid = $config['params']['dataparams']['earndedid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $empname = $config['params']['dataparams']['dclientname'];
    $empid = $config['params']['dataparams']['clientid'];

    $filter = "";
    if ($earndedid != "") $filter .= " and ss.acnoid='" . $earndedid . "' ";
    if ($empname != "")  $filter .= " and e.empid='" . $empid . "' ";


    $query = "
    select 'BEGBAL' as transdoc, date('" . $start . "') as stdate, '' as remarks,
      '' as batch, 0 as db, 0 as cr, ifnull(sum(st.db-st.cr),0) as begbal
      from standardsetupadv as ss
      left join standardtransadv as st on ss.trno = st.trno
      left join employee as e on ss.empid = e.empid
      left join paccount as pa on pa.line=ss.acnoid
      left join client as c on c.clientid=e.empid
      where date(st.dateid) < '" . $start . "'  " . $filter . "
      union all
    select st.docno as transdoc, date(st.dateid) as stdate, ss.remarks,
      ifnull(batch.batch,'') as batch, ifnull(st.db,0), ifnull(st.cr,0), ifnull(st.db-st.cr,0) as begbal
      from standardsetupadv as ss
      left join standardtransadv as st on ss.trno = st.trno
      left join employee as e on ss.empid = e.empid
      left join paccount as pa on pa.line=ss.acnoid
      left join client as c on c.clientid=e.empid
      left join batch on batch.line=st.batchid
      where date(st.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
      ";

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

    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $earnded = $config['params']['dataparams']['earnded'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '800';


    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EARNING/DEDUCTION REPORT', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date From: <b>' . $start . '</b> To: <b>' . $end . '</b>', null, null, false, $border, '', 'C', $font, '13', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $empname = $config['params']['dataparams']['clientname'];

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Employee : ' . ($empname != '' ? $empname : 'ALL ') . '</b>', null, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>Account Name : ' . ($earnded != '' ? $earnded : 'ALL ') . '</b>', null, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Doc no', '100', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Payroll Batch', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Remarks', '120', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Debit', '120', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Credit', '120', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Balance', '120', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
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

    $count = 75;
    $page = 75;
    $layoutsize = '800';

    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $totalcr = 0;
    $totaldb = 0;
    $amt = 0;
    foreach ($result as $key => $data) {

      $amt += $data->begbal;
      $totalcr += $data->cr;
      $totaldb += $data->db;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->stdate, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->transdoc, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->batch, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->remarks, '120', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->db, '120', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cr, '120', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '120', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total&nbsp;&nbsp;&nbsp; : ', '440', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '120', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '120', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($amt, 2), '120', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class