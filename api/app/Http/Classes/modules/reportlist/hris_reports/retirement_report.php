<?php

namespace App\Http\Classes\modules\reportlist\hris_reports;

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

class retirement_report
{
  public $modulename = 'Retirement Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep','start', 'end','tpaygroup', 'radioreporttype'];

    $col1 = $this->fieldClass->create($fields);
    if ($companyid != 53) {
      data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
      data_set($col1, 'dclientname.label', 'Employee');
      data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
      data_set($col1, 'divrep.label', 'Company');
      data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptrep.label', 'Department');
      data_set($col2, 'start.required', true);
      data_set($col2, 'end.required', true);
    }
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
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '0' as reporttype,
    '' as deptrep,
    '0' as paygroupid,
    '' as tpaygroup
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
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
        case '0': // SUMMARY
        return $this->JDA_SUMMARY_Layout($config);
        break;

        case '1': // DETAIL
        return $this->JDA_DETAIL_Layout($config);
        break;
    }
  }


  public function JDA_QRY($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $dividname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname     = $config['params']['dataparams']['deptname'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $payid     = $config['params']['dataparams']['paygroupid'];
    $payname     = $config['params']['dataparams']['tpaygroup'];
    $filter   = "";
    $filter  = "";
    $filter  = "";

    if ($client != "") {
      $filter .= " and c.client = '$client'";
    }
    if ($deptname != "") {
      $filter .= " and emp.deptid = $deptid";
    }

    if ($dividname != "") {
      $filter .= " and emp.divid = $divid";
    }

    if ($payname != "") {
      $filter .= " and paygroup.line = $payid";
    }
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select  client, clientname,date(hired) as hired, date(resigned) as resigned, reghrs, abshrs, basicrate from (
    select c.client, c.clientname,emp.hired, emp.resigned, rate.basicrate,
    (select sum(reghrs) from timecard as t where t.empid = emp.empid) as reghrs,
    (select sum(absdays) from timecard as t where t.empid = emp.empid) as abshrs
    from employee as emp
    left join client as c on c.clientid = emp.empid and isemployee = 1
    left join paygroup on paygroup.line = emp.paygroup
    left join ratesetup as rate on rate.empid = emp.empid
      and rate.dateend = (select max(r2.dateend) from ratesetup as r2 where r2.empid = emp.empid)
    where date(emp.resigned) between '$start' and '$end' and c.client <>'' and emp.level in $emplvl $filter
    ) as a";
    return $this->coreFunctions->opentable($query);
  }

  private function display_JDA_Header($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype     = $config['params']['dataparams']['reporttype'];
    $payid     = $config['params']['dataparams']['paygroupid'];
    $payname     = $config['params']['dataparams']['tpaygroup'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    if ($reporttype == 0) {
      $type = '(SUMMARY)';
    } else {
      $type = '(DETAILED)';
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RETIREMENT REPORT' . $type, null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
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
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();

    
    $str .= $this->reporter->startrow();
    if ($payid == 0 || $payid == '') {
      $str .= $this->reporter->col('PAY GROUP : ALL GROUPS', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('PAY GROUP : ' . strtoupper($payname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->col('', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
  
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    switch ($reporttype) {
      case '0': // SUMAMRY
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '210', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('NO OF REG DAYS', '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('RETIREMENT', '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
      case '1': // DETAIL
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE NAME', '300', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('FROM', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('TO', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('NO OF <br> REG DAYS', '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('RETIREMENT', '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
    }
    return $str;
  }

  public function JDA_SUMMARY_Layout($config)
  {

    $result = $this->JDA_QRY($config);
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $count = 55;
    $page = 55;
    $layoutsize = '1000';
    $str = '';
    $regdays = 0;
    $retirement = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->display_JDA_Header($config);

    foreach ($result as $key => $data) {

      $regdays = ($data->reghrs - $data->abshrs)/8;
      $retirement = ((($data->basicrate * 22.5)/12)/30) * $regdays;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '210', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($regdays,2), '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col( number_format($retirement, 2), '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $Grandtot = $Grandtot + $retirement;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->display_JDA_Header($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '210', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($Grandtot, 2), '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }

  public function JDA_DETAIL_Layout($config)
  {
    $result = $this->JDA_QRY($config);
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $count = 45;
    $page = 45;
    $layoutsize = '1000';

    $str = '';
    $regdays = 0;
    $retirement = 0;
    $Grandtot = 0;


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->display_JDA_Header($config);
    foreach ($result as $key => $data) {

      $regdays = ($data->reghrs - $data->abshrs)/8;
      $retirement = ((($data->basicrate * 22.5)/12)/30) * $regdays;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client.' ' .$data->clientname, '300', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->hired, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->resigned, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($regdays,2), '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($retirement, 2), '60', null, false, $border, '', 'R', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      $Grandtot = $Grandtot + $retirement;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->display_JDA_Header($config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '80', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($Grandtot, 2), '60', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
}