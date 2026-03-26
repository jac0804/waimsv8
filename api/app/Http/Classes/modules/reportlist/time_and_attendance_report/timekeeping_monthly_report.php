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

class timekeeping_monthly_report
{
    public $modulename = 'Timekeeping Monthly Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];


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
        $fields = ['radioprint', 'year', 'month',  'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);


        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);
        data_set($col1, 'year.required', true);
        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Timekeeping Monthly', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Employee W/out Morning In', 'value' => '1', 'color' => 'teal'],
                ['label' => 'Employee with Habitual Late', 'value' => '2', 'color' => 'teal']
            ]
        );

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            '0' as reporttype,
            left(now(),4) as year,
            '0' as bmonth,
            '' as month");
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
        $reptype = $config['params']['dataparams']['reporttype'];

        switch ($reptype) {
            case '0':
                return $this->reportDefaultLayout($config);
                break;
            case '1':
                return $this->reportWout_morning_in($config);
                break;
            case '2':
                return $this->reportWith_habitual_late($config);
                break;
        }
    }

    public function reportDefault($config)
    {

        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['bmonth'];

        $query = "
      select branch,vlday,slday,absdays,absent,restday,ontrip,under,underhr,noinout,tardiness,late 
      from (
	  select  branch.clientname as branch,case when acc.alias = 'VL' then count(acc.alias) else 0 end as vlday,
	  case when acc.alias = 'SL' then count(acc.alias) else 0 end as slday,'0' as absdays,'0' as absent,'0' as restday, '0' as ontrip ,
      '0' as under,'0' as underhr,'0' as noinout, '0' as tardiness,'0' as late
	  FROM employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join leavetrans AS l ON l.empid = emp.empid
	  left join leavesetup as ls ON ls.trno = l.trno
	  LEFT JOIN paccount AS acc ON acc.line = ls.acnoid
	  where year(l.dateid) = '$year' and month(l.dateid) = '$month' 
	  and l.status = 'A' and acc.alias in ('VL','SL')
	  group by branch.clientname,acc.alias

	  union all
	   
	  select  branch.clientname as branch ,'0' as vlday,'0' as slday,count(tc.absdays) as absdays,sum(tc.absdays) as absent,'0' as restday, '0' as ontrip,
	  '0' as under,'0' as underhr,'0' as noinout, '0' as tardiness,'0' as late
	  from employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join timecard AS tc on tc.empid = emp.empid
	  where year(tc.dateid) = '$year' and month(tc.dateid) = '$month' and tc.absdays <> 0
	  group by branch.clientname
	  
	  union all
	  
	  select  branch.clientname as branch ,'0' as vlday,'0' as slday,'0' as absdays,'0' as absent,count(cs.isrestday) AS restday, '0' as ontrip,
      '0' as under,'0' as underhr,'0' as noinout, '0' as tardiness,'0' as late
	  FROM employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join changeshiftapp AS cs on cs.empid = emp.empid
	  where year(date(cs.dateid)) = '$year' and month(date(dateid)) = '$month' 
	  and cs.status = 1 and cs.isrestday = 1
	  group by branch.clientname
	  
	  union all
	  
	  select  branch.clientname as branch ,'0' as vlday,'0' as slday,'0' as absdays,'0' as absent,'0' as restday,count(ob.ontrip) as ontrip,
      '0' as under,'0' as underhr,'0' as noinout, '0' as tardiness,'0' as late  from employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join obapplication as ob on ob.empid = emp.empid
	  where  year(date(ob.dateid)) = '$year' and month(ob.dateid) = '$month' 
	  and ob.status = 'E' and ob.ontrip = 'ON TRIP'
	  group by branch.clientname
	  
	  union all
	  
	  select  branch.clientname as branch ,'0' as vlday,'0' as slday,'0' as absdays,'0' as absent,'0' as restday, '0' as ontrip,
	  count(tc.underhrs) as under,sum(tc.underhrs) as underhr,'0' as noinout, '0' as tardiness,'0' as late
	  from employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join timecard AS tc on tc.empid = emp.empid
	  where year(tc.dateid) = '$year' and month(tc.dateid) = '$month' and tc.underhrs <> 0
	  group by branch.clientname
	  
	  union all 
	  
	  select  branch.clientname as branch ,'0' as vlday,'0' as slday,'0' as absdays,'0' as absent,'0' as restday, '0' as ontrip,
      '0' as under,'0' as underhr, count(tc.line) as noinout, '0' as tardiness,'0' as late
	  from employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join timecard AS tc on tc.empid = emp.empid
	  where year(tc.dateid) = '$year' and month(tc.dateid) = '$month' and (tc.isnologin <> 0 or tc.isnologout <> 0)
	  group by branch.clientname
	  
	  union all 
	  
	  select  branch.clientname as branch ,'0' as vlday,'0' as slday,'0' as absdays,'0' as absent,'0' as restday, '0' as ontrip,
      '0' as under,'0' as underhr,'0' as noinout,count(tc.latehrs) as tardiness,sum(tc.latehrs) as late
	  from employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join timecard AS tc on tc.empid = emp.empid
	  where year(tc.dateid) = '$year' and month(tc.dateid) = '$month' 
	  and tc.latehrs <> 0 
	  group by branch.clientname
	   
) as v group by branch,vlday,slday,absdays,absent,restday,ontrip,under,underhr,noinout,tardiness,late;";
        return $this->coreFunctions->opentable($query);
    }
    public function query_wout_morning_in($config)
    {
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['bmonth'];
        $query = "
      select  branch.clientname as branch,concat(emp.empfirst,emp.empmiddle,emp.emplast) as empname,count(emp.empid) as freq
	  from employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join timecard AS tc on tc.empid = emp.empid and year(tc.dateid) = '$year' and month(tc.dateid) = '$month'
      where tc.isnologin <> 0
	  group by branch.clientname,emp.empfirst,emp.empmiddle,emp.emplast";
        return $this->coreFunctions->opentable($query);
    }
    public function query_with_habitual_late($config)
    {
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['bmonth'];
        $query = "
      select  concat(emp.empfirst,emp.empmiddle,emp.emplast) as empname,branch.clientname as branch ,'0',dept.clientname as deptname,
      count(tc.latehrs) as freq,sum(tc.latehrs * 60) as late
	  from employee as emp 
	  left join client as branch on branch.clientid =  emp.branchid
	  left join client as dept on dept.clientid =  emp.deptid
	  left join timecard as tc on tc.empid = emp.empid and year(tc.dateid) = '$year' and month(tc.dateid) = '$month'
	  where tc.latehrs <> 0 
	  group by branch.clientname,dept.clientname,emp.empfirst,emp.empmiddle,emp.emplast";
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {

        $border = '1px solid';
        $font = 'Times new Roman';
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $reptype = $config['params']['dataparams']['reporttype'];
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['bmonth'];
        $layoutsize = "1000";

        switch ($reptype) {
            case '0':
                $rep_type = "TIMEKEEPING";
                break;
            case '1':
                $rep_type = "EMPLOYEE WITHOUT MORNING IN";
                break;

            default:
                $rep_type = "EMPLOYEE WITH HABITUAL LATE";
                break;
        }


        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $fontcolor = '#FFFFFF'; //white#FFFFFF
        $bgcolors = '#000000'; //black#000000
        $layoutsize = "1000";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . $rep_type . (''), '', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid #C0C0C0 !important';
        $font = 'Times new Roman';
        $font_size = '10';
        $count = 55;
        $page = 55;
        $str = '';
        $layoutsize = "1000";
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $fontcolor = '#0B76C3'; //white#FFFFFF
        $bgcolors = ''; //black#000000
        $fontcolorh = '#FFFFFF';
        $bgcolorsh = '#000000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('REGION', '250', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('VL', '70', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('SL', '70', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('PMD', '70', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('ABSENT', '90', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('RESTDAY', '90', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('ON TRIP', '90', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('UNDERTIME', '90', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('NO IN/OUT', '90', null, $bgcolorsh, $border, 'BTL', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->col('TARDINESS', '90', null, $bgcolorsh, $border, 'BTLR', 'C', $font, $font_size, 'B', $fontcolorh, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $vl = 0;
        $sl = 0;
        $abs = 0;
        $underhr = 0;
        foreach ($result as $key => $data) {
            // branch,vlday,slday,absdays,restday,ontrip,under,noinout,tardiness 
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('' . $data->branch, '250', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->vlday, '70', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->slday, '70', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('', '70', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('' . $data->absdays, '90', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');

            $str .= $this->reporter->col('' . $data->restday, '90', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->ontrip, '90', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');

            $str .= $this->reporter->col('' . $data->under, '90', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->noinout, '90', 25, '', $border, 'LR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->tardiness, '90', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');

            $str .= $this->reporter->endrow();

            $vl = ($data->vlday / 8);
            $sl = ($data->slday / 8);
            $underhr = ($data->underhr * 60);
            $abs = $data->absent != 0 ? (float) $data->absent : 0;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL # OF HOURS/DAYS', '250', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');
            $str .= $this->reporter->col('' . (is_float($vl) ? number_format($vl, 1) : $vl) . ' days', '70', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');
            $str .= $this->reporter->col('' . (is_float($sl) ? number_format($sl, 1) : $sl) . ' days', '70', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');
            $str .= $this->reporter->col('' . ' days', '70', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '');
            $str .= $this->reporter->col('' . (is_float($abs) ? number_format($abs / 8, 1) :  number_format($abs, 0)) . ' days', '90', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');

            $str .= $this->reporter->col('' . number_format($data->restday, 0) . ' days', '90', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');
            $str .= $this->reporter->col('' . number_format(($data->ontrip / 8), 0) . ' days', '90', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');

            $str .= $this->reporter->col('' . (is_float($underhr) ? number_format($underhr, 1) :  $underhr) . ' minutes', '90', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');
            $str .= $this->reporter->col('', '90', 25, '', $border, 'BLR', 'C', $font, $font_size, 'B', $fontcolor, '');
            $str .= $this->reporter->col('' . number_format(($data->late * 60), 0) . ' minutes', '90', 25, '', $border, 'LBR', 'C', $font, $font_size, 'B', $fontcolor, '25');

            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportWout_morning_in($config)
    {
        $result = $this->query_wout_morning_in($config);
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['month'];
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $border = '1px solid #C0C0C0 !important';
        $font = 'Times new Roman';
        $font_size = '10';
        $count = 55;
        $page = 55;
        $str = '';
        $layoutsize = "1000";
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $fontcolor = '#FFFFFF'; //white#FFFFFF
        $bgcolors = '#000000'; //black#000000
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(' ', '150', 30, false, $border, '', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->col(' EMPLOYEES WITHOUT MORNING IN ' . "( $month , $year )", '700', 30, $bgcolors, $border, 'BTLR', 'C', $font, 13, 'B', $fontcolor, '30');
        $str .= $this->reporter->col(' ', '150', 30, false, $border, '', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(' ', '150', 30, false, $border, '', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->col('NAME OF EMPLOYEE', '240', 30, $bgcolors, $border, 'BTLR', 'C', $font, 13, 'B', $fontcolor, '30');
        $str .= $this->reporter->col('BRANCH', '230', 30, $bgcolors, $border, 'BTLR', 'C', $font, 13, 'B', $fontcolor, '30');
        $str .= $this->reporter->col('FREQUENCY', '230', 30, $bgcolors, $border, 'BTLR', 'C', $font, 13, 'B', $fontcolor, '30');
        $str .= $this->reporter->col(' ', '150', 30, false, $border, '', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '150', 25, '', $border, '', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->empname, '240', 25, '', $border, 'LBR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->branch, '230', 25, '', $border, 'BR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('' . $data->freq, '230', 25, '', $border, 'BR', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->col('', '150', 25, '', $border, '', 'C', $font, $font_size, '', '', '25');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
    public function reportWith_habitual_late($config)
    {
        $result = $this->query_with_habitual_late($config);
        $year = $config['params']['dataparams']['year'];
        $month = $config['params']['dataparams']['month'];
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $border = '1px solid #C0C0C0 !important';
        $font = 'Times new Roman';
        $font_size = '10';
        $count = 55;
        $page = 55;
        $str = '';
        $layoutsize = "1000";
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $fontcolor = '#FFFFFF'; //white#FFFFFF
        $bgcolors = '#000000'; //black#000000
        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(' List of Employee with Habitail Late ' . "( $month , $year )", null, 30, $bgcolors, $border, 'BTLR', 'C', $font, 13, 'B', $fontcolor, '30');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('No', '40', 30, false, $border, 'BLRT', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->col('Name of Employee', '340', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->col('Branch', '160', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->col('Department', '200', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->col('Frequency', '100', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->col('Total #. Of Late', '160', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $i = 0;
        foreach ($result as $key => $data) {
            $i++;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('' . $i, '40', 30, false, $border, 'BLRT', 'C', $font, 13, 'B', '', '30');
            $str .= $this->reporter->col('' . $data->empname, '340', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
            $str .= $this->reporter->col('' . $data->branch, '160', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
            $str .= $this->reporter->col('' . $data->deptname, '200', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
            $str .= $this->reporter->col('' . $data->freq . ' times', '100', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
            $str .= $this->reporter->col('' . number_format($data->late, 0) . ' minutes', '160', 30, false, $border, 'BTLR', 'C', $font, 13, 'B', '', '30');
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class