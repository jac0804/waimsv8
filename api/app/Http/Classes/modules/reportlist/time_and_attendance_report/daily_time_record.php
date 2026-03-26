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
use Illuminate\Support\Facades\URL;
use DateTime;

class daily_time_record
{
  public $modulename = 'Daily Time Record';
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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint'];

    if ($companyid == 58) { //cdo
      array_push($fields, 'dbranchname');
    }

    array_push($fields, 'divrep', 'deptrep', 'dclientname');

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['start', 'end', 'radioreporttype', 'radioreportitemstatus'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

    data_set($col2, 'radioreportitemstatus.label', 'Employee Status');
    data_set($col2, 'radioreportitemstatus.options', array(
      ['label' => 'Active', 'value' => '(1)', 'color' => 'orange'],
      ['label' => 'Inactive', 'value' => '(0)', 'color' => 'orange']
    ));

    switch ($companyid) {
      case 44:
      case 58: //stonepro, cdo
        $colset = [
          ['label' => 'Default Format', 'value' => 'default', 'color' => 'teal']
        ];
        break;
      default:
        $colset = [
          ['label' => 'Default Format', 'value' => 'default', 'color' => 'teal'],
          ['label' => 'Government Format', 'value' => 'gov', 'color' => 'teal']
        ];
        break;
    }

    data_set($col2, 'radioreporttype.options', $colset);

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
    adddate(left(now(),10),-30) as start,
    left(now(),10) as end,
    '' as deptrep,
    '' as dbranchname,
    '' as branchcode,
    '0' as branchid,
    '(1)' as itemstatus,
    'default' as reporttype
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
    $reportlayout = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];
    if ($reportlayout == 'default') {
      switch ($companyid) {
        case 44: //stonepro
          return $this->Layout_stonepro($config);
          break;
        case 58: //cdo
          return $this->Layout_CDO($config);
          break;
        default:
          return $this->reportDefaultLayout($config);
          break;
      }
    } else {
      return $this->Government_Layout($config);
    }
  }

  public function reportDefault($config)
  {

    $client     = $config['params']['dataparams']['client'];
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $reportlayout = $config['params']['dataparams']['reporttype'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $itemstatus = $config['params']['dataparams']['itemstatus'];

    $filter   = "";
    $filter1   = "";
    $filter2   = "";

    if ($client != "") {
      $filter .= " and e.client = '$client'";
    }
    if ($deptid != 0 && $deptid != "") {
      $filter1 .= " and emp.deptid = $deptid";
    }
    if ($divid != 0 && $divid != "") {
      $filter2 .= " and emp.divid = $divid";
    }
    $emplvl = $this->othersClass->checksecuritylevel($config);

    if ($config['params']['companyid'] == 58) { //cdo
      $branch     = $config['params']['dataparams']['dbranchname'];
      $branchid     = $config['params']['dataparams']['branchid'];


      if ($branch != "") {
        $filter2 .= " and emp.branchid = $branchid";
      }
    }

    $filter .= " and emp.isactive in $itemstatus";



    if ($reportlayout == 'default') {
      if ($config['params']['companyid'] == 58) { //cdo hris
        $query = "select e.clientname,e.client, timecard.dateid,time(timecard.schedin) AS schedin,
                          time(timecard.schedout) AS schedout,time(timecard.actualin) AS actualin,
                          time(timecard.actualout) AS actualout, timecard.reghrs,date_format(timecard.dateid, '%a') as acnoday, 
                          timecard.absdays, timecard.latehrs, timecard.latehrs2,timecard.underhrs, timecard.othrs, timecard.ndiffhrs,
                          timecard.ndiffot, timecard.daytype,bio.terminal,d.clientname as deptname,timecard.ismactualin,
                          timecard.ismactualout,timecard.isobactualin,timecard.isobactualout,timecard.logactualin,
                          timecard.logactualout,round(timestampdiff(minute, actualin, actualout) / 60, 2) as computed,emp.division,
                          time(timecard.actualbrkin) AS lunchin,time(timecard.actualbrkout) AS lunchout,tms.shftcode,
                          time(timecard.abrk1stout) AS abrk1stout, time(timecard.abrk1stin) AS abrk1stin, time(timecard.abrk2ndin) AS abrk2ndin, time(timecard.abrk2ndout) AS abrk2ndout, 

                          timecard.isnologin,time(timecard.actualin) as timeisnologin,
                          timecard.isnombrkout,time(timecard.brk1stout) as timeisnombrkout,
                          timecard.isnombrkin, time(timecard.brk1stin) as timeisnombrkin,
                          timecard.isnolunchout, time(timecard.actualbrkout) as timeisnolunchout,
                          timecard.isnolunchin, time(timecard.actualbrkin) as timeisnolunchin,
                          timecard.isnopbrkout,time(timecard.brk2ndout) as timeisnopbrkout,
                          timecard.isnopbrkin,time(timecard.brk2ndin) as timeisnopbrkin,
                          timecard.isnologout,time(timecard.actualout) as timeisnologout,
                          timecard.isnologpin,timecard.isnologunder, r.isrestday,lr.status as leavestat, p.codename, timecard.lateoffset, ls.isnopay
                  FROM timecard 
                  LEFT JOIN employee AS emp ON emp.empid=timecard.empid
                  left join client as e on e.clientid = emp.empid
                  left join biometric as bio on bio.line = emp.biometricid
                  left join client as d on d.clientid = emp.deptid
                  left join tmshifts as tms on tms.line=timecard.shiftid
                  left join changeshiftapp as r on r.empid=timecard.empid and r.dateid = timecard.dateid
                  left join leavetrans as lr on lr.empid=timecard.empid and lr.effectivity=timecard.dateid and lr.status='A'
                  left join leavesetup as ls on ls.trno = lr.trno left join paccount as p on p.line=ls.acnoid
                  where timecard.dateid between '" . $start . "' and '" . $end . "' and emp.level in $emplvl $filter $filter1 $filter2 
                  order by d.clientname,e.clientname,timecard.dateid";
      } else {
        $query = "select e.clientname,e.client, timecard.dateid,time(timecard.schedin) AS schedin,
                          time(timecard.schedout) AS schedout,time(timecard.actualin) AS actualin,
                          time(timecard.actualout) AS actualout, timecard.reghrs,date_format(timecard.dateid, '%a') as acnoday, 
                          timecard.absdays, timecard.latehrs,timecard.underhrs, timecard.othrs, timecard.ndiffhrs,
                          timecard.ndiffot, timecard.daytype,bio.terminal,d.clientname as deptname,timecard.ismactualin,
                          timecard.ismactualout,timecard.isobactualin,timecard.isobactualout,timecard.logactualin,
                          timecard.logactualout,round(timestampdiff(minute, actualin, actualout) / 60, 2) as computed,emp.division,
                          time(timecard.actualbrkin) AS lunchin,time(timecard.actualbrkout) AS lunchout,tms.shftcode
                  FROM timecard 
                  LEFT JOIN employee AS emp ON emp.empid=timecard.empid
                  left join client as e on e.clientid = emp.empid
                  left join biometric as bio on bio.line = emp.biometricid
                  left join client as d on d.clientid = emp.deptid
                  left join tmshifts as tms on tms.line=timecard.shiftid
                  where dateid between '" . $start . "' and '" . $end . "' and emp.level in $emplvl $filter $filter1 $filter2 
                  order by d.clientname,e.clientname,timecard.dateid";
      }
    } else {
      $query = "select e.clientname,e.client, timecard.dateid,ts.shftcode,time_format(time(ts.tschedin),'%h:%i') as timein,
                        time_format(time(ts.tschedout),'%h:%i') timeout,year(timecard.dateid) as yearof,
                        monthname(timecard.dateid) as monthof,day(timecard.dateid) as daycount,
                        time(timecard.actualin) AS amin,time(timecard.actualbrkout) AS amout,
                        time(timecard.actualbrkin) AS pmin,time(timecard.actualout) AS pmout,
                        case when floor(timecard.underhrs) = 0 then '' else floor(timecard.underhrs) end as underhours,
                        case when 60*(timecard.underhrs%1) = 0 then '' else 60*(timecard.underhrs%1) end as underminute,
                        timecard.reghrs, timecard.absdays, timecard.latehrs,timecard.underhrs,
                        timecard.othrs, timecard.ndiffhrs,timecard.ndiffot, timecard.daytype,bio.terminal,
                        timecard.ismactualin,timecard.ismactualout,timecard.isobactualin,timecard.isobactualout,timecard.logactualin,
                        timecard.logactualout,round(timestampdiff(minute, actualin, actualout) / 60, 2) as computed
              FROM timecard
              LEFT JOIN employee AS emp ON emp.empid=timecard.empid
              left join client as e on e.clientid = emp.empid
              left join tmshifts as ts on ts.line = emp.shiftid 
              left join biometric as bio on bio.line = emp.biometricid
              where dateid between '" . $start . "' and '" . $end . "' and emp.level in $emplvl $filter $filter1 $filter2
              order by e.clientname,timecard.dateid";
    }
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

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DAILY TIME RECORD', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
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


    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');

    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE NAME', '250', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DAYTYPE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BIOMETRIC TERMINAL', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SCHEDULE IN', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SCHEDULE OUT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ACTUAL IN', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ACTUAL OUT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('WORK HRS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ABSENT HRS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('LATE HRS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('UNDERTIME HRS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('OT HRS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('NDIFF HRS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('NDIFF OT HRS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
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

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);
    $chkemp = "";

    $totworking = 0;
    $totabsent = 0;
    $totlate = 0;
    $totundertime = 0;
    $totot = 0;
    $totndiff = 0;
    $totndiffot = 0;


    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      if ($chkemp != $data->client) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();

        if ($chkemp != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('Total', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');

          $str .= $this->reporter->col($totworking == 0 ? '-' : number_format($totworking, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($totabsent == 0 ? '-' : number_format($totabsent, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($totlate == 0 ? '-' : number_format($totlate, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($totundertime == 0 ? '-' : number_format($totundertime, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($totot == 0 ? '-' : number_format($totot, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($totndiff == 0 ? '-' : number_format($totndiff, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($totndiffot == 0 ? '-' : number_format($totndiffot, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

          $totworking = 0;
          $totabsent = 0;
          $totlate = 0;
          $totundertime = 0;
          $totot = 0;
          $totndiff = 0;
          $str .= $this->reporter->endrow();
        }
      }

      $schedin = $data->schedin; //dapat 
      $date = new DateTime($schedin);
      $schedout = $data->schedout;
      // $endate = $newdate->format('Y-m-d');

      $str .= $this->reporter->col($data->client, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->terminal, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->daytype, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->schedin, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->schedout, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->actualin, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->actualout, '80', null, false, $border, '', '', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->reghrs == 0 ? '-' : $data->reghrs, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->absdays == 0 ? '-' : $data->absdays, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->latehrs == 0 ? '-' : $data->latehrs, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->underhrs == 0 ? '-' : $data->underhrs, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->othrs == 0 ? '-' : $data->othrs, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->ndiffhrs == 0 ? '-' : $data->ndiffhrs, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->ndiffot == 0 ? '-' : $data->ndiffot, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');



      $totworking = $totworking + $data->reghrs;
      $totabsent = $totabsent + $data->absdays;
      $totlate = $totlate + $data->latehrs;
      $totundertime = $totundertime + $data->underhrs;
      $totot = $totot + $data->othrs;
      $totndiff = $totndiff + $data->ndiffhrs;
      $totndiffot = $totndiffot + $data->ndiffot;


      $str .= $this->reporter->endrow();
      $chkemp = $data->client;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $page = $page + $count;
      }
    }



    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Total', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($totworking == 0 ? '-' : number_format($totworking, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($totabsent == 0 ? '-' : number_format($totabsent, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($totlate == 0 ? '-' : number_format($totlate, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($totundertime == 0 ? '-' : number_format($totundertime, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($totot == 0 ? '-' : number_format($totot, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($totndiff == 0 ? '-' : number_format($totndiff, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($totndiffot == 0 ? '-' : number_format($totndiffot, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, '', '', '');

    $totworking = 0;
    $totabsent = 0;
    $totlate = 0;
    $totundertime = 0;
    $totot = 0;
    $totndiff = 0;

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }


  private function Govt_displayHeader($config, $client, $month, $year, $shift, $in, $out)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize'] / 3);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 21, null, false, $border, '', 'L', $font, '14', '', '', '');
    $str .= $this->reporter->col('CIVIL SERVICE FORM No.48', 379, null, false, $border, '', 'L', $font, '14', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize'] / 3);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DAILY TIME RECORD', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize'] / 3);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($client, null, null, false, $border, 'B', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize'] / 3);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the month of: ', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->col($month, null, null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('Year', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->col($year, null, null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize'] / 3);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 25, null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('Official Hours for Arrival', 200, null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('(Regular Days: ' . $in . '-' . $out . ')', 150, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('', 25, null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize'] / 3);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 25, null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('and departure', 200, null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->col('(Saturdays: ' . $in . '-' . $out . ')', 150, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->col('', 25, null, false, $border, '', 'L', $font, '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }


  private function government_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Day', '40', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('A', '55', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('M', '55', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('P', '55', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('M', '55', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('UNDER', '55', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TIME', '55', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Arrival', '55', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Depart', '55', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Arrival', '55', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Depart', '55', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Hours', '55', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Mins', '55', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    return $str;
  }

  public function Government_Layout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '800';

    $str = '';
    $Tot = 0;
    $Grandtot = 0;


    $totworking = 0;
    $totabsent = 0;
    $totlate = 0;
    $totundertime = 0;
    $totot = 0;
    $totndiff = 0;
    $totndiffot = 0;



    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);

    $chkemp = "";

    $first = 0;
    $second = 0;
    $third = 0;
    $loops = 0;

    $h = -70;
    $w = -90;
    $str .= '<div style="position: relative;">';
    foreach ($result as $key => $data) {
      if ($chkemp == "" || $chkemp != $data->clientname) {


        //when a line has three employees
        if ($first == 1 && $second == 1 && $third == 1) {

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->page_break();



          $first = 0;
          $second = 0;
          $third = 0;
          $loops += 1;
          if ($loops == 1) {
            $h = 945;
            $w = -800;
          } else {
            $h = 945;
            $w = -800;
          }
        }
        //line has two employees
        if ($first == 1 && $second == 1 && $third == 0) {

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->endtable();
          $str .= "</div>";
          $w += 400;
          $str .= "<div style='position:absolute; top: " . $h . "px; left: " . $w . "px;'>";
          $str .= $this->Govt_displayHeader($config, $data->clientname, $data->monthof, $data->yearof, $data->shftcode, $data->timein, $data->timeout);
          $str .= $this->government_table_cols($this->reportParams['layoutSize'] / 3, $border, $font, $font_size, $config);
          $third = 1;
        }

        //line has first employee
        if ($first == 1 && $second == 0) {

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->endtable();
          $str .= "</div>";
          $w += 400;
          $str .= "<div style='position:absolute; top: " . $h . "px; left: " . $w . "px;'>";
          $str .= $this->Govt_displayHeader($config, $data->clientname, $data->monthof, $data->yearof, $data->shftcode, $data->timein, $data->timeout);
          $str .= $this->government_table_cols($this->reportParams['layoutSize'] / 3, $border, $font, $font_size, $config);
          $second = 1;
        }

        if ($first == 0) {
          $w += 0;
          $str .= "<div style='position:absolute; top: " . $h . "px; left: " . $w . "px;'>";
          $str .= $this->Govt_displayHeader($config, $data->clientname, $data->monthof, $data->yearof, $data->shftcode, $data->timein, $data->timeout);
          $str .= $this->government_table_cols($this->reportParams['layoutSize'] / 3, $border, $font, $font_size, $config);
          $first = 1;
        }
      }


      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('' . $data->daycount, '40', null, false, $border, 'TLR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->amin, '55', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->amout, '55', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->pmin, '55', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->pmout, '55', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->underhours, '55', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->underminute, '55', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $chkemp = $data->clientname;
    }
    if ($first == 1 && $second == 0) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endtable();
      // $str .= "</div>";
      // $w += 400;
      // $str .= "<div style='position:absolute; top: " . $h . "px; left: " . $w . "px;'>";
      // $str .= $this->Govt_displayHeader($config, $data->clientname, $data->monthof, $data->yearof, $data->shftcode, $data->timein, $data->timeout);
      // $str .= $this->government_table_cols($this->reportParams['layoutSize'] / 3, $border, $font, $font_size, $config);
      // $second = 1;
    }

    if ($first == 1 && $second == 1 && $third == 0) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endtable();
      // $str .= "</div>";
      // $w += 400;
      // $str .= "<div style='position:absolute; top: " . $h . "px; left: " . $w . "px;'>";
      // $str .= $this->Govt_displayHeader($config, $data->clientname, $data->monthof, $data->yearof, $data->shftcode, $data->timein, $data->timeout);
      // $str .= $this->government_table_cols($this->reportParams['layoutSize'] / 3, $border, $font, $font_size, $config);
      // $third = 1;
    }
    if ($first == 1 && $second == 1 && $third == 1) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '40', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endtable();

      // $str .= $this->reporter->page_break();



      // $first = 0;
      // $second = 0;
      // $third = 0;
      // $loops +=1;
      // if($loops==1){
      //   $h = 850;
      //   $w = -800;
      // }else{
      //   $h = 800;
      //   $w = -800;
      // }



    }
    $str .= "</div>";



    $str .= $this->reporter->endreport();


    return $str;
  }
  private function displayHeader_stonepro($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $layoutsize = 960;

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

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DAILY TIME RECORD', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Run Date: ' . date('Y-m-d'), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE CODE', '150', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('FORMAL NAME', '250', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DAYTYPE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TIME IN', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TIME OUT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SCHED HOURS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('COMPUTED HOURS', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }
  public function Layout_stonepro($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 38;
    $page = 50;
    $layoutsize = 960;

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_stonepro($config);
    $chkemp = "";
    $deptname = "";

    $totworking = 0;
    $totabsent = 0;
    $totlate = 0;
    $totundertime = 0;
    $totot = 0;
    $totndiff = 0;
    $totndiffot = 0;

    $blue = '#0200FE';
    $red = '#FE0000';
    $purple = '#83007E';
    $teal = '#037E86';
    $silver = '#C1C1C1';
    $pink = '#FE00FE';
    $green = '#008001';

    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($deptname != $data->deptname) {
        if ($chkemp != $data->client) {

          if ($chkemp != "" && $deptname != "") {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Total: ', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col($totworking == 0 ? '-' : number_format($totworking, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();

            $totworking = 0;
            $totabsent = 0;
            $totlate = 0;
            $totundertime = 0;
            $totot = 0;
            $totndiff = 0;
          }
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->deptname, null, null, false, $border, '', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= '<br/>';
      }

      $color = '';
      $color2 = '';

      if ($data->ismactualin != 0) {
        $color = $blue;
      } else {
        switch ($data->logactualin) {
          case 1:
          case 2:
            $color = $silver;
            break;
          case 3:
            $color = $teal;
            break;
          case 4:
            $color = $purple;
            break;
          case 10:
            $color = $red;
            break;
        }
      }
      if ($data->isobactualin != 0) {
        $color = $pink;
      } else {
        switch ($data->logactualin) {
          case 1:
          case 2:
            $color = $silver;
            break;
          case 3:
            $color = $teal;
            break;
          case 4:
            $color = $purple;
            break;
          case 10:
            $color = $red;
            break;
        }
      }

      if ($data->ismactualout) {
        $color2 = $blue;
      } else {
        switch ($data->logactualout) {
          case 1:
          case 2:
            $color2 = $silver;
            break;
          case 3:
            $color2 = $teal;
            break;
          case 4:
            $color2 = $purple;
            break;
          case 10:
            $color2 = $red;
            break;
        }
      }

      if ($data->isobactualout) {
        $color2 = $pink;
      } else {
        switch ($data->logactualout) {
          case 1:
          case 2:
            $color2 = $silver;
            break;
          case 3:
            $color2 = $teal;
            break;
          case 4:
            $color2 = $purple;
            break;
          case 10:
            $color2 = $red;
            break;
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->acnoday, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->actualin, '80', null, false, $border, '', 'C', $font, $font_size, '', $color, '');
      $str .= $this->reporter->col($data->actualout, '100', null, false, $border, '', 'C', $font, $font_size, '', $color2, '');
      $str .= $this->reporter->col($data->reghrs == 0 ? 0.00 : number_format($data->reghrs + $data->othrs, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->computed, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $totworking = $totworking + $data->reghrs + $data->othrs;
      $totabsent = $totabsent + $data->absdays;
      $totlate = $totlate + $data->latehrs;
      $totundertime = $totundertime + $data->underhrs;
      $totot = $totot + $data->othrs;
      $totndiff = $totndiff + $data->ndiffhrs;
      $totndiffot = $totndiffot + $data->ndiffot;

      $chkemp = $data->client;
      $deptname = $data->deptname;
      // if ($this->reporter->linecounter == $page) {

      //   $str .= $this->reporter->endtable();
      //   $str .= $this->reporter->page_break();
      //   $str .= $this->displayHeader_stonepro($config);
      //   $str .= $this->reporter->begintable($layoutsize);
      //   $page = $page + $count;
      // }
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Total', '100', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($totworking == 0 ? '-' : number_format($totworking, 2), '100', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->dtrcolorcoding($border, $font, $font_size);

    $totworking = 0;
    $totabsent = 0;
    $totlate = 0;
    $totundertime = 0;
    $totot = 0;
    $totndiff = 0;

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function dtrcolorcoding($border, $font, $font_size)
  {
    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LEGEND', '150', 20, null, $border, '', '', $font, $font_size, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', 20, '#0200FE', $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DTR From Manual Entry', '150', 20, null, $border, '', '', $font, $font_size, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', 20, '#FE00FE', $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DTR From OB', '150', 20, null, $border, '', '', $font, $font_size, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', 20, '#C1C1C1', $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DTR From Biometric ZK Teco', '250', 20, null, $border, '', '', $font, $font_size, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', 20, '#83007E', $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DTR From Wilcon', '150', 20, null, $border, '', '', $font, $font_size, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', 20, '#FE0000', $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DTR From Bundy Clock', '200', 20, null, $border, '', '', $font, $font_size, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', 20, '#037E86', $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('DTR From IT Excel', '150', 20, null, $border, '', '', $font, $font_size, 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', 5, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function displayHeader_CDO($config)
  {
    $border = '1px solid #C0C0C0 !important';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '9';
    $padding = '';
    $margin = '';
    $layoutsize = 960;

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

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    // $logo = URL::to('/images/cdohris/paflogo.png');

    // if ($logo != "") {
    //   // $str .= '<div style="position: relative;">';
    //   $str .= '<br/><br/><br/><br/>';
    //   $str .= "<div style='position:absolute; margin:-50px 0 -500px 0'>";
    //   $str .= $this->reporter->begintable($layoutsize);
    //   $str .= $this->reporter->startrow();
    //   $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mbc" width="1000px" height ="120px">', '250', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    //   $str .= $this->reporter->endrow();
    //   $str .= $this->reporter->endtable();
    //   $str .= "</div>";
    //   // $str .= "</div>";
    // }

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper(date('F j, Y')), null, null, false, $border, '', '', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUBJECT: DAILY TIME RECORD ATTENDANCE', null, null, false, $border, '', '', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function tablecols_CDO($config, $clientname)
  {
    $border = '1px solid #C0C0C0 !important';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 38;
    $page = 50;
    $layoutsize = 1000;
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($clientname . ', ' . $start . ' - ' . $end, '1000', null, false, $border, 'TLR', 'L', $font, '13', 'B', '', '15px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '90', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('IN', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('OUT (Break)', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('IN (Break)', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('OUT (Lunch)', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('IN (Lunch)', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('OUT (Break)', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('IN (Break)', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('OUT', '80', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('DETAILS', '180', null, false, $border, 'TBLR', 'R', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('HOURS', '90', null, false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->endrow();
    return $str;
  }


  public function Layout_CDO($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid #C0C0C0 !important';
    $bgcolors = '#F7F8F8';
    $bgcolors2 = '#FFFFFF';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '9';
    $padding = '';
    $margin = '';

    $count = 0;
    $page = 27;
    $layoutsize = 1000;

    $str = '';
    $Tot = 0;
    $Grandtot = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '20px;margin-top:10px;margin-left:100px');
    $str .= $this->displayHeader_CDO($config);
    $chkemp = "";
    $employee = "";

    $totworking = 0;
    $totabsent = 0;
    $totlate = 0;
    $totundertime = 0;
    $totot = 0;
    $totndiff = 0;
    $totndiffot = 0;


    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      $rowBg = ($key % 2 == 0) ? $bgcolors : $bgcolors2;
      $str .= $this->reporter->addline();
      if ($employee != $data->clientname) {
        if ($chkemp != $data->client) {
          if ($chkemp != "" && $employee != "") {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '90', null, false, $border, 'TBL', '', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('Total Hours:', '180', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '10px');
            $str .= $this->reporter->col($totworking == 0 ? '-' : number_format($totworking, 2), '90', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '10px');
            $str .= $this->reporter->endrow();


            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '90', null, false, $border, 'TBL', '', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('Total Days:', '180', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '10px');
            $str .= $this->reporter->col($totworking == 0 ? '-' : number_format(($totworking / 8), 2), '90', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '10px');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '90', null, false, $border, 'TBL', '', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '10px');
            $str .= $this->reporter->col('Total Late Hours:', '180', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '10px');
            $str .= $this->reporter->col($totlate == 0 ? '-' : number_format($totlate, 2), '90', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '10px');
            $str .= $this->reporter->endrow();


            $totworking = 0;
            $totabsent = 0;
            $totlate = 0;
            $totundertime = 0;
            $totot = 0;
            $totndiff = 0;
            // $this->reporter->linecounter + 3;

            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader_CDO($config);
            // $page = $page + $count;

            $count = 0;
          }
        }
        $str .= $this->tablecols_CDO($config, $data->clientname);
        $employee = $data->clientname;
      }
      $actualin = $data->actualin == null ? '' : (new DateTime($data->actualin))->format('h:i A');
      $lunchout = $data->lunchout == null ? '' : (new DateTime($data->lunchout))->format('h:i A');
      $lunchin = $data->lunchin == null ? '' : (new DateTime($data->lunchin))->format('h:i A');
      $actualout = $data->actualout == null ? '' : (new DateTime($data->actualout))->format('h:i A');

      $abrk1stout = $data->abrk1stout == null ? '' : (new DateTime($data->abrk1stout))->format('h:i A');
      $abrk1stin = $data->abrk1stin == null ? '' : (new DateTime($data->abrk1stin))->format('h:i A');
      $abrk2ndout = $data->abrk2ndout == null ? '' : (new DateTime($data->abrk2ndout))->format('h:i A');
      $abrk2ndin = $data->abrk2ndin == null ? '' : (new DateTime($data->abrk2ndin))->format('h:i A');

      $shiftco = preg_replace('/(\d+)AM-(\d+)PM(.*)/', '*$3($1-$2)', $data->shftcode);
      $datenow = (new DateTime($data->dateid))->format('M d'); // Aug 23 
      $dt = new DateTime($data->dateid);

      $lateh = '';
      if ($data->latehrs > 0) {
        if ($data->latehrs2 >= 60) {
          $lateh = 'Late: ' . (float) ($data->latehrs2 / 60) . ' hrs';
        } else {
          $lateh = 'Late: ' . (float) $data->latehrs2 . ' min(s)';
        }
      }

      // $lateh = $data->latehrs > 0 ? 'Late: ' . $data->latehrs . ($data->latehrs >= 1 ? ' hrs' : ' mins') : '';
      $absen = $data->absdays <> 0 ? 'Absent: ' . ($data->absdays / 8) . ' day/s' : '';
      $under = $data->underhrs > 0 ? 'Undertime: ' . $data->underhrs . ($data->underhrs >= 1 ? ' hrs' : 'min(s)') : '';

      $lateoffset = $data->lateoffset > 0 ? 'Late Offset: ' . (float) $data->lateoffset . ' min(s)' : '';

      //leavestat
      if ($data->leavestat == 'A') {
        $leave = $data->codename;
        if ($data->isnopay == 0) {
          $absen = '';
          $data->absdays = 0;
        }
      } else {
        $leave = '';
      }

      $timeisnologin = $data->timeisnologin == null ? '' : (new DateTime($data->timeisnologin))->format('h:i A');
      $timeisnombrkout = $data->timeisnombrkout == null ? '' : (new DateTime($data->timeisnombrkout))->format('h:i A');
      $timeisnombrkin = $data->timeisnombrkin == null ? '' : (new DateTime($data->timeisnombrkin))->format('h:i A');
      $timeisnolunchout = $data->timeisnolunchout == null ? '' : (new DateTime($data->timeisnolunchout))->format('h:i A');
      $timeisnolunchin = $data->timeisnolunchin == null ? '' : (new DateTime($data->timeisnolunchin))->format('h:i A');
      $timeisnopbrkout = $data->timeisnopbrkout == null ? '' : (new DateTime($data->timeisnopbrkout))->format('h:i A');
      $timeisnopbrkin = $data->timeisnopbrkin == null ? '' : (new DateTime($data->timeisnopbrkin))->format('h:i A');
      $timeisnologout = $data->timeisnologout == null ? '' : (new DateTime($data->timeisnologout))->format('h:i A');

      $blnPenalty = false;

      $isnologin = $data->isnologin == 1 ? 'NO MORNING IN: ' . $timeisnologin : '';
      $isnombrkout = $data->isnombrkout == 1 ? 'NO MORNING BREAK OUT: ' . $timeisnombrkout : '';
      $isnombrkin = $data->isnombrkin == 1 ? 'NO MORNING BREAK IN: ' . $timeisnombrkin : '';
      $isnolunchout = $data->isnolunchout == 1 ? 'NO LUNCH BREAK OUT: ' . $timeisnolunchout : '';
      $isnolunchin = $data->isnolunchin == 1 ? 'NO LUNCH BREAK IN: ' . $timeisnolunchin : '';
      $isnopbrkout = $data->isnopbrkout == 1 ? 'NO AFTERNOON BREAK OUT: ' . $timeisnopbrkout : '';
      $isnopbrkin = $data->isnopbrkin == 1 ? 'NO AFTERNOON BREAK IN: ' . $timeisnopbrkin : '';
      $isnologout = $data->isnologout == 1 ? 'NO AFTERNOON OUT: ' . $timeisnologout : '';
      $isnologpin = $data->isnologpin == 1 ? 'NO AFTERNOON IN: ' : '';
      $isnologunder = $data->isnologunder == 1 ? 'NO IN/OUT UNDERTIME: ' : '';

      if ($data->isnologin || $data->isnombrkout || $data->isnombrkin || $data->isnolunchout || $data->isnolunchin || $data->isnopbrkout || $data->isnopbrkin || $data->isnologout || $data->isnologpin || $data->isnologunder) {
        $blnPenalty = true;
      }

      // if ($dt->format('w') == 0) { //pag sunday
      //   $detail = 'Sunday';
      // } else {
      $parts = array_filter([
        $lateh,
        $lateoffset,
        $absen,
        $under,
        $isnologin,
        $isnombrkout,
        $isnombrkin,
        $isnolunchout,
        $isnolunchin,
        $isnopbrkout,
        $isnopbrkin,
        $isnologout,
        $isnologpin,
        $isnologunder,
        $leave
      ]); // alisin yung mga empty
      $detail = implode(' , ', $parts);
      // }

      if ($dt->format('w') == 0 && $data->daytype == 'RESTDAY') { //pag sunday
        $detail = 'Sunday';
      }

      if ($data->isrestday == 1) {
        $detail .= 'Restday';
      }

      switch ($data->daytype) {
        case 'SP':
          $detail .= 'Special Holiday';
          break;
        case 'LEG':
          $detail .= 'Legal Holiday';
          break;
      }

      $bold_nombrkin = ($data->isnombrkin) ? 'B' : '';
      $bold_nombrkout = ($data->isnombrkout) ? 'B' : '';
      $bold_nolunchout = ($data->isnolunchout) ? 'B' : '';
      $bold_nolunchin = ($data->isnolunchin) ? 'B' : '';
      $bold_nopbrkout = ($data->isnopbrkout) ?  'B' : '';
      $bold_nopbrkin = ($data->isnopbrkin) ? 'B' : '';
      $bold_nologin = ($data->isnologin) ? 'B' :    '';
      $bold_nologout = ($data->isnologout) ? 'B' :   '';

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($datenow . ' ' . $shiftco, '90', null, $rowBg, $border, 'TBLR', '', $font, $font_size, '', '', '10px');
      $str .= $this->reporter->col($actualin, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nologin, '', '10px');
      $str .= $this->reporter->col($abrk1stout, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nombrkout, '', '10px');
      $str .= $this->reporter->col($abrk1stin, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nombrkin, '', '10px');
      $str .= $this->reporter->col($lunchout, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nolunchout, '', '10px');

      $str .= $this->reporter->col($lunchin, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nolunchin, '', '10px');
      $str .= $this->reporter->col($abrk2ndout, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nopbrkout, '', '10px');
      $str .= $this->reporter->col($abrk2ndin, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nopbrkin, '', '10px');
      $str .= $this->reporter->col($actualout, '80', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, $bold_nologout, '', '10px');

      if ($blnPenalty) {
        $detail = '<span style="background-color:#FF0000;color:#FFFFFF;padding:2px;">' . $detail . '</span>';
      }
      $str .= $this->reporter->col($detail, '180', null, $rowBg, $border, 'TBLR', 'R', $font, $font_size, '', '', '10px');
      $str .= $this->reporter->col(($data->reghrs - $data->absdays), '90', null, $rowBg, $border, 'TBLR', 'C', $font, $font_size, '', '', '10px');
      $str .= $this->reporter->endrow();

      $totworking = $totworking + ($data->reghrs - $data->absdays);
      $totabsent = $totabsent + $data->absdays;
      $totlate = $totlate + $data->latehrs;
      $totundertime = $totundertime + $data->underhrs;
      $totot = $totot + $data->othrs;
      $totndiff = $totndiff + $data->ndiffhrs;
      $totndiffot = $totndiffot + $data->ndiffot;

      $chkemp = $data->client;
      $employee = $data->clientname;
      $count++;
      if ($count + 3  >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_CDO($config);
        $str .= $this->tablecols_CDO($config, $data->clientname);
        // $page = $page + $count;
        $count = 0;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', null, false, $border, 'TBL', '', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('Total Hours:', '180', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col($totworking == 0 ? '-' : number_format($totworking, 2), '90', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', null, false, $border, 'TBL', '', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('Total Days:', '180', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col($totworking == 0 ? '-' : number_format(($totworking / 8), 2), '90', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', null, false, $border, 'TBL', '', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $font_size, '', '', '10px');
    $str .= $this->reporter->col('Total Late Hours:', '180', null, false, $border, 'TB', '', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col($totlate == 0 ? '-' : number_format($totlate, 2), '90', null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->endrow();


    $totworking = 0;
    $totabsent = 0;
    $totlate = 0;
    $totundertime = 0;
    $totot = 0;
    $totndiff = 0;

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}
