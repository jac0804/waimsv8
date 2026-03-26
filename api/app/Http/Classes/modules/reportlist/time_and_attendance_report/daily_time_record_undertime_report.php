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

class daily_time_record_undertime_report
{
    public $modulename = 'Daily Time Record - Undertime Reports';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => 1000];

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
        data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
        $fields = ['start', 'end', 'radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set(
            $col2,
            'radioreporttype.options',
            [
                ['label' => 'Summarized', 'value' => '0', 'color' => 'green'],
                ['label' => 'Detailed', 'value' => '1', 'color' => 'green']

            ]
        );
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
                '' as sectname,
                '' as sectid,
                '' as sectcode,
                '0' as 'reporttype'
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
        $data = $this->defualt_query($config);
        if ($reporttype != '0') {
            return $this->report_default_detailed($config, $data);
        } else {
            return $this->report_default_summary($config, $data);
        }
    }
    public function defualt_query($config)
    {
        $client     = $config['params']['dataparams']['client'];
        $divid     = $config['params']['dataparams']['divid'];
        $deptid     = $config['params']['dataparams']['deptid'];
        $sectid     = $config['params']['dataparams']['sectid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $filter = "";
        if ($client != "") {
            $filter .= " and e.client = '$client'";
        }
        if ($deptid != 0) {
            $filter .= " and emp.deptid = $deptid";
        }
        if ($divid != 0) {
            $filter .= " and emp.divid = $divid";
        }
        if ($sectid != 0) {
            $filter .= " and emp.sectid = $sectid";
        }

        // for summary
        $innerfields = "";
        $outerfields = "";
        $leftjoin = "";
        $hrs = " sum(underhrs) as underhrs,";
        $groupby = " group by t.clientname";

        if ($reporttype != '0') {
            //for detailed
            $outerfields = " ,client as code, concat(date_format(schedin,'%l %p'),'-',date_format(schedout,'%l %p')) as shift,
        schedin,schedout,actualin,actualout,
        dateid,daytype,empid,detpname,jobtitle";
            $innerfields = " ,e.client ,timecard.dateid,
        time(timecard.schedin) as schedin,
        time(timecard.schedout) as schedout,time(timecard.actualin) as actualin,
        time(timecard.actualout) as actualout,timecard.daytype,emp.empid,dept.clientname as detpname,jt.jobtitle";
            $hrs = " underhrs ,";
            $leftjoin = "       
        left join client as dept on dept.clientid = emp.deptid
        left join jobthead as jt on jt.line = emp.jobid
        left join section as sect on sect.sectid = emp.sectid";
            $groupby = "";
        }

        $emplvl = $this->othersClass->checksecuritylevel($config);
        // 01-10-2025 JF add summary
        // $query = "select client as code, clientname as empname,concat(date_format(schedin,'%l %p'),'-',date_format(schedout,'%l %p')) as shift,
        // schedin,schedout,actualin,actualout,absdays,latehrs,underhrs,
        // detpname,jobtitle,dateid,daytype,empid from (
        // select e.client ,e.clientname,dept.clientname as detpname,jt.jobtitle,timecard.dateid,
        // time(timecard.schedin) as schedin,
        // time(timecard.schedout) as schedout,time(timecard.actualin) as actualin,
        // time(timecard.actualout) as actualout,timecard.absdays,timecard.latehrs,timecard.underhrs,timecard.daytype,emp.empid from timecard
        // left join employee as emp on emp.empid=timecard.empid
        // left join client as e on e.clientid = emp.empid
        // left join client as dept on dept.clientid = emp.deptid
        // left join jobthead as jt on jt.line = emp.jobid
        // left join section as sect on sect.sectid = emp.sectid
        // where dateid between '" . $start . "' and '" . $end . "' and timecard.underhrs <> 0 and emp.level in $emplvl $filter
        // order by e.clientname,timecard.dateid) as t";
        // return $this->coreFunctions->opentable($query);

        $query = "select $hrs clientname as empname $outerfields
        from (
        select timecard.underhrs ,e.clientname $innerfields
        from timecard
        left join employee as emp on emp.empid=timecard.empid
        left join client as e on e.clientid = emp.empid
        $leftjoin
        where dateid between '" . $start . "' and '" . $end . "' and timecard.underhrs <> 0 and emp.level in $emplvl $filter
        order by e.clientname,timecard.dateid) as t 
        $groupby";
        return $this->coreFunctions->opentable($query);
    }
    public function getcountdata($config, $empid)
    {
        $emplvl = $this->othersClass->checksecuritylevel($config);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $query = "select count(emp.empid) as value from timecard 
        left join employee as emp on emp.empid=timecard.empid where dateid between '" . $start . "' and '" . $end . "' and emp.empid =? and timecard.underhrs <> 0 and emp.level in $emplvl  ";
        return $this->coreFunctions->datareader($query, [$empid]);
    }
    public function displayHeader($config, $layoutsize)
    {
        $str = "";
        $font_size = '10';
        $border = '1px solid';
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start   = $config['params']['dataparams']['start'];
        $end   = $config['params']['dataparams']['end'];
        $devname  = $config['params']['dataparams']['divname'];
        $deptname = $config['params']['dataparams']['deptname'];
        $sectname = $config['params']['dataparams']['sectname'];

        $font = $this->companysetup->getrptfont($config['params']);
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY TIME RECORD - UNDERTIME REPORTS', null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '</br>';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Division:  ' . $devname .   ' Department:  ' . $deptname . ' Section:  ' . $sectname, null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From: ' . $start . ' to ' . $end, null, null, false, $border, '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        return $str;
    }
    public function report_default_detailed($config, $data)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $page = 55;
        $layoutsize = '1000';
        $str = '';
        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $layoutsize);
        $str .= $this->reporter->begintable($layoutsize);
        $client = "";
        $totalundertime = 0;
        $nodays = 0;
        $clno = 0;
        $count = 0;
        foreach ($data as $key => $value) {

            if ($client == "" || $client != $data[$key]->empname) {
                $client = $data[$key]->empname;

                $str .= $this->reporter->begintable($layoutsize);
                if ($clno != 0) {
                    $str .= '<br/>';
                }
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Code : ' . $data[$key]->code, '500px', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Department : ' . $data[$key]->detpname, '500px', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Name : ' . $client, '500px', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Job Title : ' . $data[$key]->jobtitle, '500px', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Date', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Shift', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('SchedIn', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Schedout', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Actual In', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Actual Out', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Undertime', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Day Type', '100px', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();

                $totalundertime = 0;
                $nodays = 0;
                $clno++;
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$key]->dateid, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->shift, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->schedin, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->schedout, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->actualin, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->actualout, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->underhrs, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->daytype, '100px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            $totalundertime += $data[$key]->underhrs;
            $nodays++;
            $count = $this->getcountdata($config, $data[$key]->empid);
            if ($nodays == $count) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Number of days: ' . $nodays, '100px', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Total: ', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col(number_format($totalundertime, 2), '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->endrow();
                $count = 0;
            }
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
    public function report_default_summary($config, $data)
    {
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';
        $padding = '';
        $margin = '';

        $count = 0;
        $page = 55;
        $layoutsize = '1000';
        $str = '';
        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $layoutsize);

        $totalabs = 0;
        $str .= $this->reporter->begintable(1000);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Name', '600', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Total Undertime', '200', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        foreach ($data as $key => $value) {
            $str .= $this->reporter->begintable(1000);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$key]->empname, '600', null, false, '1px dotted', 'B', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data[$key]->underhrs, '200', null, false, '1px dotted', 'B', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endreport();

        return $str;
    }
}
