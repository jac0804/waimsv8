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

class summary_of_absences_for_13th_report
{
    public $modulename = 'SUMMARY OF ABSENCES FOR 13TH COMPUTATION REPORT';
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
        $fields = ['radioprint', 'radiostatus', 'divrep', 'deptrep', 'sectrep', 'dclientname', 'atype', 'start', 'end',];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'atype.label', 'Employee Type');
        data_set($col1, 'atype.readonly', true);

        data_set($col1, 'radiostatus.label', 'Status');
        data_set($col1, 'radiostatus.options', [
            ['label' => "Active Employee's", 'value' => '1', 'color' => 'red'],
            ['label' => "Inactive Employee's", 'value' => '0', 'color' => 'red']
        ]);

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
    '1' as status,
    '' as type");
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
        $status   = $config['params']['dataparams']['status'];
        $type   = $config['params']['dataparams']['type'];
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
        if ($status != "") {
            $filter  .= " and emp.isactive='" . $status . "'";
        }
        if ($type != "") {
            $filter  .= " and emp.emptype='" . $type . "'";
        }
        $query = "
            select client.clientid,client.client as code,client.clientname,
	        date(emp.hired) as hired,date(emp.regular) as regular,date(emp.resigned) as resigned,
            sum(tm.reghrs/8) as workday,sum(tm.absdays / 8) as absdays
            from employee as emp
            join timecard as tm on tm.empid = emp.empid
            left join client on client.clientid = emp.empid
            where tm.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
				group by client.clientid,client.client,client.clientname,emp.hired,emp.regular,emp.resigned";

        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = date("l, F j, Y g:i:s A", strtotime($this->othersClass->getCurrentTimeStamp()));


        $str = '';
        $layoutsize = '1000';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "11";
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
        $str .= $this->reporter->col('', 940, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();




        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee#', 150, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', 250, null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Start Date', 100, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date Reg.', 100, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('End Date', 100, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Days Worked', 100, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Absences', 100, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cons. Lvs.', 100, null, false, $border, 'B', 'CT', $font, $fontsize, 'B', '', '');
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

        $count = 50;
        $page = 64;

        $str = '';
        $layoutsize = '1000';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);
        $i = 0;
        if (!empty($result)) {

            foreach ($result as $key => $data) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->code, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->hired, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->regular, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->resigned, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->workday != 0 ? number_format($data->workday, 2) : '-', '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->absdays != 0 ? number_format($data->absdays, 2) : '-', '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');


                $query = "select ifnull(sum(lev),0) as value from  (
                select (case when acc.uom = 'DAYS' then sum(lt.adays) else sum(lt.adays / 8) end) as lev 
				from leavetrans as lt
                left join leavesetup as ls on ls.trno = lt.trno
                left join paccount as acc on acc.line = ls.acnoid
				where lt.empid = '$data->clientid' and date(lt.effectivity) between '" . $start . "' and '" . $end . "' 
                group by acc.uom
                )  as v";
                $leaveday = $this->coreFunctions->datareader($query, [], '', false);

                $str .= $this->reporter->col($leaveday != 0 ? number_format($leaveday, 2) : '-', '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $i++;
                if ($i == $count) {
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
