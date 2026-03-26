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
        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'dclientname', 'start', 'end',];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');

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
    '' as sectrep");
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
        return $this->reportDefaultLayout($config);
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
}
