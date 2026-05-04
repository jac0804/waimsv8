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

class attendance_summary_report
{
    public $modulename = 'ATTENDANCE SUMMARY REPORT';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'P', 'format' => 'letter', 'layoutSize' => 1200];

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

        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'dclientname', 'start', 'end',];
        if ($companyid == 68) { // jda
            array_push($fields, 'tpaygroupname');
        }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');

        data_set($col1, 'tpaygroupname.lookupclass', 'batchsetuppaygroup');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as division,
    '' as deptid,
    '' as deptname,
    '' as sectid,
    '' as sectname,
    '' as sectrep,
    '' as tpaygroupname,
    '' as paycode,
    '0' as pgroup");
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        switch ($companyid) {
            case 68:
                $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => 1200];
                return $this->jda_Layout($config);
                break;
            default:
                return $this->reportDefaultLayout($config);
                break;
        }
    }

    public function reportDefault($config)
    {
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client   = $config['params']['dataparams']['client'];
        $divname   = $config['params']['dataparams']['divname'];
        $divid   = $config['params']['dataparams']['divid'];
        $sectname   = $config['params']['dataparams']['sectname'];
        $sectid   = $config['params']['dataparams']['sectid'];
        $divname   = $config['params']['dataparams']['divname'];
        $divid   = $config['params']['dataparams']['divid'];
        $filter   = "";

        if ($client != "") {
            $filter  .= " and client.client='" . $client . "'";
        }
        if ($divname != "") {
            $filter  .= " and emp.divid='" . $divid . "'";
        }
        if ($sectname != "") {
            $filter  .= " and emp.sectid='" . $sectid . "'";
        }
        if ($divname != "") {
            $filter  .= " and emp.divid='" . $divid . "'";
        }

        $query = " select client.clientid,client.client as code,client.clientname,
            sum(case when tm.actualin is not null and tm.daytype = 'WORKING' then 
            (tm.reghrs/8) 
            else 0 end) as workday,
            sum(case when otapproved = 1 then tm.othrs else 0 end) as othrs,
            sum(case when tm.daytype = 'WORKING' and emp.classrate = 'M' and tm.actualin is null then (tm.absdays / 8) ELSE 0 end) as absdays,
            sum(tm.latehrs) as latehrs,sum(tm.underhrs) as underhrs,
            sum(case when tm.daytype = 'RESTDAY' and tm.rdapprvd = 1 then (tm.reghrs/8) else 0 end) as resday,
            sum(case when tm.daytype = 'LEG' and tm.legapprvd = 1 then tm.reghrs/8 else 0 end) as legal,
            sum(case when tm.daytype = 'SP' and tm.spapprvd = 1 then tm.reghrs/8 else 0 end) as sp,
            sum(case when tm.daytype = 'RESTDAY' and tm.rdotapprvd = 1 then tm.othrs  else 0 end) as restdayot,
            sum(case when tm.daytype = 'LEG' and tm.legotapprvd = 1 then tm.othrs  else 0 end) as legalot,
            sum(case when tm.daytype = 'SP' and tm.spotapprvd = 1 then tm.othrs else 0 end) as spot
            from timecard as tm
            left join employee as emp on emp.empid = tm.empid
            left join client on client.clientid = emp.empid
            where tm.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
            group by client.client,client.clientname,client.clientid";
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = date("m-d-Y", strtotime($this->othersClass->getCurrentDate()));


        $str = '';
        $layoutsize = '1000';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From ' . date("F d, Y", strtotime($start)) . ' to ' . date("F d, Y", strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date: ' . $currentdate, 940, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 150, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 280, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 70, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 95, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 5, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 200, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 150, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 280, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 70, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 95, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 5, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 200, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 150, null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 280, null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 70, null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 50, null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Holiday', 95, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 5, null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Overtime', 200, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Code', 150, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', 280, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Days Worked', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Absent (Days)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Late (Hrs)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Undertime (Hrs)', 70, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Restday (Days)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Legal (Days)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Special (Days)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reg.OT (Hrs)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Restday OT (Hrs)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Legal OT (Hrs)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SP OT (Days)', 50, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();



        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $companyid = $config['params']['companyid'];

        $count = 38;
        $page = 64;

        $str = '';
        $layoutsize = '1000';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "9.5";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        $dateid = "";
        $i = 0;
        $total = 0;

        if (!empty($result)) {

            foreach ($result as $key => $data) {

                $class = $this->coreFunctions->datareader("select classrate as value from employee where empid = " . $data->clientid);
                if (!empty($class)) {
                    if ($class == 'M') {
                        $data->workday = 13;
                    }
                }

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->code, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '280', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->workday != 0 ? number_format($data->workday, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->absdays != 0 ? number_format($data->absdays, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->latehrs != 0 ? number_format($data->latehrs, 2) : '-', '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->underhrs != 0 ? number_format($data->underhrs, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->resday != 0 ? number_format($data->resday, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->legal != 0 ? number_format($data->legal, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sp != 0 ? number_format($data->sp, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->othrs != 0 ? number_format($data->othrs, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->restdayot != 0 ? number_format($data->restdayot, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->legalot != 0 ? number_format($data->legalot, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->spot != 0 ? number_format($data->spot, 2) : '-', '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();

                $i++;
                if ($page == $i) {
                    $i = 0;
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader($config);
                }
            }
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();

        return $str;
    }

    // jda query
    public function jda_qry($config)
    {
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client   = $config['params']['dataparams']['client'];
        $divname   = $config['params']['dataparams']['divname'];
        $divid   = $config['params']['dataparams']['divid'];
        $sectname   = $config['params']['dataparams']['sectname'];
        $sectid   = $config['params']['dataparams']['sectid'];
        $divname   = $config['params']['dataparams']['divname'];
        $divid   = $config['params']['dataparams']['divid'];
        $tpaygroupname   = $config['params']['dataparams']['tpaygroupname'];
        $pgroup   = $config['params']['dataparams']['pgroup'];
        $paycode   = $config['params']['dataparams']['paycode'];
        $filter   = "";

        if ($client != "") {
            $filter  .= " and client.client='" . $client . "'";
        }
        if ($divname != "") {
            $filter  .= " and emp.divid='" . $divid . "'";
        }
        if ($sectname != "") {
            $filter  .= " and emp.sectid='" . $sectid . "'";
        }
        if ($divname != "") {
            $filter  .= " and emp.divid='" . $divid . "'";
        }
        if ($tpaygroupname != "") {
            $filter  .= " and emp.paygroup='" . $pgroup . "'";
        }

        $query = "select client.clientid, client.client as code, client.clientname,
            -- DAY SHIFT - Regular
            sum(case when tm.actualin is not null and tm.daytype = 'WORKING' and TIME(tm.schedin) <= '17:59:00' then (tm.reghrs/8) else 0 end) as day_reg_days,
            sum(case when tm.actualin is not null and tm.daytype = 'WORKING' and TIME(tm.schedin) <= '17:59:00' then tm.reghrs else 0 end) as day_reg_hrs,
            sum(case when tm.otapproved = 1 and TIME(tm.schedin) <= '17:59:00' then tm.othrs else 0 end) as day_reg_ot,
            -- DAY SHIFT - Late/Und
            sum(case when TIME(tm.schedin) <= '17:59:00' then tm.latehrs else 0 end) as day_late,
            sum(case when TIME(tm.schedin) <= '17:59:00' then tm.underhrs else 0 end) as day_und,
            -- DAY SHIFT - Rest Day
            sum(case when tm.daytype = 'RESTDAY' and TIME(tm.schedin) <= '17:59:00' then (tm.reghrs/8) else 0 end) as day_rd_days,
            sum(case when tm.daytype = 'RESTDAY' and TIME(tm.schedin) <= '17:59:00' then tm.reghrs else 0 end) as day_rd_hrs,
            sum(case when tm.daytype = 'RESTDAY' and tm.rdotapprvd = 1 and TIME(tm.schedin) <= '17:59:00' then tm.othrs else 0 end) as day_rd_ot,
            -- DAY SHIFT - Special Holiday
            sum(case when tm.daytype = 'SP' and TIME(tm.schedin) <= '17:59:00' then (tm.reghrs/8) else 0 end) as day_sp_days,
            sum(case when tm.daytype = 'SP' and TIME(tm.schedin) <= '17:59:00' then tm.reghrs else 0 end) as day_sp_hrs,
            sum(case when tm.daytype = 'SP' and tm.spotapprvd = 1 and TIME(tm.schedin) <= '17:59:00' then tm.othrs else 0 end) as day_sp_ot,
            -- DAY SHIFT - Legal Holiday
            sum(case when tm.daytype = 'LEG' and TIME(tm.schedin) <= '17:59:00' then (tm.reghrs/8) else 0 end) as day_leg_days,
            sum(case when tm.daytype = 'LEG' and TIME(tm.schedin) <= '17:59:00' then tm.reghrs else 0 end) as day_leg_hrs,
            sum(case when tm.daytype = 'LEG' and tm.legotapprvd = 1 and TIME(tm.schedin) <= '17:59:00' then tm.othrs else 0 end) as day_leg_ot,
            -- NIGHT SHIFT - Regular
            sum(case when tm.actualin is not null and tm.daytype = 'WORKING' and TIME(tm.schedin) > '17:59:00' then (tm.reghrs/8) else 0 end) as night_reg_days,
            sum(case when tm.actualin is not null and tm.daytype = 'WORKING' and TIME(tm.schedin) > '17:59:00' then tm.reghrs else 0 end) as night_reg_hrs,
            sum(case when tm.otapproved = 1 and TIME(tm.schedin) > '17:59:00' then tm.othrs else 0 end) as night_reg_ot,
            -- NIGHT SHIFT - Late/Und
            sum(case when TIME(tm.schedin) > '17:59:00' then tm.latehrs else 0 end) as night_late,
            sum(case when TIME(tm.schedin) > '17:59:00' then tm.underhrs else 0 end) as night_und,
            -- NIGHT SHIFT - Rest Day
            sum(case when tm.daytype = 'RESTDAY' and TIME(tm.schedin) > '17:59:00' then (tm.reghrs/8) else 0 end) as night_rd_days,
            sum(case when tm.daytype = 'RESTDAY' and TIME(tm.schedin) > '17:59:00' then tm.reghrs else 0 end) as night_rd_hrs,
            sum(case when tm.daytype = 'RESTDAY' and tm.rdotapprvd = 1 and TIME(tm.schedin) > '17:59:00' then tm.othrs else 0 end) as night_rd_ot,
            -- NIGHT SHIFT - Special Holiday
            sum(case when tm.daytype = 'SP' and TIME(tm.schedin) > '17:59:00' then (tm.reghrs/8) else 0 end) as night_sp_days,
            sum(case when tm.daytype = 'SP' and TIME(tm.schedin) > '17:59:00' then tm.reghrs else 0 end) as night_sp_hrs,
            sum(case when tm.daytype = 'SP' and tm.spotapprvd = 1 and TIME(tm.schedin) > '17:59:00' then tm.othrs else 0 end) as night_sp_ot,
            -- NIGHT SHIFT - Legal Holiday
            sum(case when tm.daytype = 'LEG' and TIME(tm.schedin) > '17:59:00' then (tm.reghrs/8) else 0 end) as night_leg_days,
            sum(case when tm.daytype = 'LEG' and TIME(tm.schedin) > '17:59:00' then tm.reghrs else 0 end) as night_leg_hrs,
            sum(case when tm.daytype = 'LEG' and tm.legotapprvd = 1 and TIME(tm.schedin) > '17:59:00' then tm.othrs else 0 end) as night_leg_ot,

            -- TOTAL
            sum(case when (tm.actualin is not null and tm.daytype = 'WORKING') or (tm.daytype in ('RESTDAY','SP','LEG')) then (tm.reghrs/8) else 0 end) as total_days,
            sum(case when tm.actualin is not null and tm.daytype = 'WORKING' then tm.reghrs else 0 end) + 
            sum(case when tm.daytype = 'RESTDAY' then tm.reghrs else 0 end) +
            sum(case when tm.daytype = 'SP' then tm.reghrs else 0 end) +
            sum(case when tm.daytype = 'LEG' then tm.reghrs else 0 end) as total_hrs,
            sum(case when tm.otapproved = 1 or tm.rdotapprvd = 1 or tm.spotapprvd = 1 or tm.legotapprvd = 1 then tm.othrs else 0 end) as total_ot

            from timecard as tm
            left join employee as emp on emp.empid = tm.empid
            left join client on client.clientid = emp.empid
            where tm.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
            group by client.client, client.clientname, client.clientid";

        return $this->coreFunctions->opentable($query);
    }

    // jda
    private function jda_displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = date("m-d-Y", strtotime($this->othersClass->getCurrentDate()));
        $tpaygroupname   = $config['params']['dataparams']['tpaygroupname'];

        if(empty($tpaygroupname)){
            $tpaygroupname = 'All';
        }


        $str = '';
        $layoutsize = '1200';

        // $font = $this->companysetup->getrptfont($config['params']);
        $font = 'Tahoma';
        $fontsize = "6";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'L', $font, '12', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Paygroup : ' . '<b>' . $tpaygroupname . '</b>', '600', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . '<b>' . date("F d, Y", strtotime($start)) . ' to ' . date("F d, Y", strtotime($end)) . '</b>', '600', null, false, $border, '', 'R', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Print Date: ' . $currentdate, 940, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->pagenumber('Page');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('',            70, null, false, $border, 'LT', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            190, null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Days Shift',  100,  null, false, $border, 'T', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            50,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            90,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            90,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            90,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Night Shift', 100,  null, false, $border, 'LT', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            50,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            90,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            90,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            90,  null, false, $border, 'T', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            100,  null, false, $border, 'RT', 'LB', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Emp Code',    70, null, false, $border, 'L', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Name',        190, null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Regular',     100,  null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            50,  null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rest Day',    90,  null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sp Hol',      90,  null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Leg Hol',     90,  null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Regular',     100,  null, false, $border, 'L', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            50,  null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rest Day',    90,  null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Sp Hol',      90,  null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Leg Hol',     90,  null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total',       100,  null, false, $border, 'R', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('',            70, null, false, $border, 'LB', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('',            190, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        // start of dayshift
        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         40,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Late/Und',    50,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        // start of nightshift
        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'LB','CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         40,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Late/Und',    50,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');

        // total
        $str .= $this->reporter->col('Days',        30,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Hrs',         40,  null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT',          30,  null, false, $border, 'RB','CT', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function jda_Layout($config)
    {
        $result = $this->jda_qry($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $companyid = $config['params']['companyid'];

        $count = 38;
        $page = 40;

        $str = '';
        $layoutsize = '1200';

        // $font = $this->companysetup->getrptfont($config['params']);
        $font = 'Tahoma';
        $fontsize = "6";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->jda_displayHeader($config);
        $dateid = "";
        $i = 0;
        $total = 0;

        if (!empty($result)) {

            foreach ($result as $key => $data) {

                // DAY SHIFT totals
                $day_reg_hrs_calc = isset($data->day_reg_hrs) ? $data->day_reg_hrs : 0;
                $day_rd_hrs_calc = isset($data->day_rd_hrs) ? $data->day_rd_hrs : 0;
                $day_sp_hrs_calc = isset($data->day_sp_hrs) ? $data->day_sp_hrs : 0;
                $day_leg_hrs_calc = isset($data->day_leg_hrs) ? $data->day_leg_hrs : 0;
                // NIGHT SHIFT totals
                $night_reg_hrs_calc = isset($data->night_reg_hrs) ? $data->night_reg_hrs : 0;
                $night_rd_hrs_calc = isset($data->night_rd_hrs) ? $data->night_rd_hrs : 0;
                $night_sp_hrs_calc = isset($data->night_sp_hrs) ? $data->night_sp_hrs : 0;
                $night_leg_hrs_calc = isset($data->night_leg_hrs) ? $data->night_leg_hrs : 0;
                // Calculate total hours
                $calculated_total_hrs = $day_reg_hrs_calc + $day_rd_hrs_calc + $day_sp_hrs_calc + $day_leg_hrs_calc +
                $night_reg_hrs_calc + $night_rd_hrs_calc + $night_sp_hrs_calc + $night_leg_hrs_calc;
                // Calculate total days
                $calculated_total_days = 0;
                if ($day_reg_hrs_calc > 0) $calculated_total_days += ($day_reg_hrs_calc / 8);
                if ($day_rd_hrs_calc > 0) $calculated_total_days += ($day_rd_hrs_calc / 8);
                if ($day_sp_hrs_calc > 0) $calculated_total_days += ($day_sp_hrs_calc / 8);
                if ($day_leg_hrs_calc > 0) $calculated_total_days += ($day_leg_hrs_calc / 8);
                if ($night_reg_hrs_calc > 0) $calculated_total_days += ($night_reg_hrs_calc / 8);
                if ($night_rd_hrs_calc > 0) $calculated_total_days += ($night_rd_hrs_calc / 8);
                if ($night_sp_hrs_calc > 0) $calculated_total_days += ($night_sp_hrs_calc / 8);
                if ($night_leg_hrs_calc > 0) $calculated_total_days += ($night_leg_hrs_calc / 8);
                // Calculate total OT
                $calculated_total_ot = (isset($data->day_reg_ot) ? $data->day_reg_ot : 0) +
                    (isset($data->day_rd_ot) ? $data->day_rd_ot : 0) +
                    (isset($data->day_sp_ot) ? $data->day_sp_ot : 0) +
                    (isset($data->day_leg_ot) ? $data->day_leg_ot : 0) +
                    (isset($data->night_reg_ot) ? $data->night_reg_ot : 0) +
                    (isset($data->night_rd_ot) ? $data->night_rd_ot : 0) +
                    (isset($data->night_sp_ot) ? $data->night_sp_ot : 0) +
                    (isset($data->night_leg_ot) ? $data->night_leg_ot : 0);

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();

                // Client Info
                $str .= $this->reporter->col($data->code,       70, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, 190, null, false, $border, '',  'LT', $font, $fontsize, '', '', '');

                // DAY SHIFT - Regular
                $str .= $this->reporter->col($data->day_reg_days != 0 ? number_format($data->day_reg_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_reg_hrs  != 0 ? number_format($data->day_reg_hrs,  2) : '-', 40, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_reg_ot   != 0 ? number_format($data->day_reg_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // DAY SHIFT - Late/Und (combined)
                $late_und_day = ($data->day_late != 0 ? number_format($data->day_late, 2) : '-') . '/' . ($data->day_und != 0 ? number_format($data->day_und, 2) : '-');
                $str .= $this->reporter->col($late_und_day, 50, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // DAY SHIFT - Rest Day
                $str .= $this->reporter->col($data->day_rd_days != 0 ? number_format($data->day_rd_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_rd_hrs  != 0 ? number_format($data->day_rd_hrs,  2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_rd_ot   != 0 ? number_format($data->day_rd_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // DAY SHIFT - Special Holiday
                $str .= $this->reporter->col($data->day_sp_days != 0 ? number_format($data->day_sp_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_sp_hrs  != 0 ? number_format($data->day_sp_hrs,  2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_sp_ot   != 0 ? number_format($data->day_sp_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // DAY SHIFT - Legal Holiday
                $str .= $this->reporter->col($data->day_leg_days != 0 ? number_format($data->day_leg_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_leg_hrs  != 0 ? number_format($data->day_leg_hrs,  2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->day_leg_ot   != 0 ? number_format($data->day_leg_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // NIGHT SHIFT - Regular
                $str .= $this->reporter->col($data->night_reg_days != 0 ? number_format($data->night_reg_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_reg_hrs  != 0 ? number_format($data->night_reg_hrs,  2) : '-', 40, null, false, $border, '',  'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_reg_ot   != 0 ? number_format($data->night_reg_ot,   2) : '-', 30, null, false, $border, '',  'RT', $font, $fontsize, '', '', '');

                // NIGHT SHIFT - Late/Und (combined)
                $late_und_night = ($data->night_late != 0 ? number_format($data->night_late, 2) : '-') . '/' . ($data->night_und != 0 ? number_format($data->night_und, 2) : '-');
                $str .= $this->reporter->col($late_und_night, 50, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // NIGHT SHIFT - Rest Day
                $str .= $this->reporter->col($data->night_rd_days != 0 ? number_format($data->night_rd_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_rd_hrs  != 0 ? number_format($data->night_rd_hrs,  2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_rd_ot   != 0 ? number_format($data->night_rd_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // NIGHT SHIFT - Special Holiday
                $str .= $this->reporter->col($data->night_sp_days != 0 ? number_format($data->night_sp_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_sp_hrs  != 0 ? number_format($data->night_sp_hrs,  2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_sp_ot   != 0 ? number_format($data->night_sp_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // NIGHT SHIFT - Legal Holiday
                $str .= $this->reporter->col($data->night_leg_days != 0 ? number_format($data->night_leg_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_leg_hrs  != 0 ? number_format($data->night_leg_hrs,  2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->night_leg_ot   != 0 ? number_format($data->night_leg_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                // TOTAL
                $str .= $this->reporter->col($calculated_total_days != 0 ? number_format($calculated_total_days, 2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($calculated_total_hrs  != 0 ? number_format($calculated_total_hrs,  2) : '-', 40, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($calculated_total_ot   != 0 ? number_format($calculated_total_ot,   2) : '-', 30, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();

                $i++;
                if ($page == $i) {
                    $i = 0;
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->begintable();
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1200', null, false, $border, 'T',  'LT', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->jda_displayHeader($config);
                }
            }
            $str .= $this->reporter->begintable();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '1200', null, false, $border, 'T',  'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();

        return $str;
    }
}
