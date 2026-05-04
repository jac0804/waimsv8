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

class employee_evaluation_record
{
    public $modulename = 'Employee Evaluation Record';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $fields = [];

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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'dclientname', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);
        data_set($col1, 'start.type', 'date');
        data_set($col1, 'end.type', 'date');
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        // data_set(
        //     $col1,
        //     'radioreporttype.options',
        //     [
        //         ['label' => 'Detailed', 'value' => '0', 'color' => 'green'],
        //     ]
        // );

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        left(now(),10) as end,
        '' as client,
        '' as clientname,
        '' as dclientname,
        '0' as 'reporttype',
        '' as divid,
        '' as divname,
        '' as divrep,
        '' as division
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
        $data          = $this->default_query($config);
        $noinout_all   = $this->noinout_query($config);
        $tardiness_all = $this->tardiness_query($config);
        return $this->report_default_detailed($config, $data, $noinout_all, $tardiness_all);
    }

    public function default_query($config)
    {
        $client = $config['params']['dataparams']['clientname'];
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter = "";

        if ($client != "") {
            $filter .= " and e.clientname = '$client'";
        }

        $outerfields = " ,client as code, concat(date_format(schedin,'%l %p'),'-',date_format(schedout,'%l %p')) as shift,
        schedin,schedout,actualin,actualout,
        dateid,daytype,empid,detpname,jobtitle,reason,divid,divcode,divname";

        $innerfields = " ,e.client ,timecard.dateid,
        time(timecard.schedin) as schedin,
        time(timecard.schedout) as schedout,time(timecard.actualin) as actualin,
        time(timecard.actualout) as actualout,timecard.daytype,emp.empid,dept.clientname as detpname,jt.jobtitle,
        `div`.divid as divid,`div`.divcode as divcode, `div`.divname as divname,
        ifnull((select group_concat(lt.remarks) from leavetrans as lt where lt.status='A' and lt.empid=emp.empid and date(lt.effectivity)=(timecard.dateid)),'') as reason";

        $hrs = " absdays ,";

        $leftjoin = "       
        left join client as dept on dept.clientid = emp.deptid
        left join jobthead as jt on jt.line = emp.jobid
        left join section as sect on sect.sectid = emp.sectid";

        $emplvl = $this->othersClass->checksecuritylevel($config);

        $query = "select $hrs clientname as empname $outerfields
        from (
        select timecard.absdays ,e.clientname $innerfields
        from timecard
        left join employee as emp on emp.empid=timecard.empid
        left join division as `div` on `div`.divid = emp.divid
        left join client as e on e.clientid = emp.empid
        $leftjoin
        where dateid between '" . $start . "' and '" . $end . "' and timecard.absdays <> 0 and emp.level in $emplvl $filter
        order by e.clientname,timecard.dateid) as t";

        return $this->coreFunctions->opentable($query);
    }

    public function tardiness_query($config)
    {
        $client = $config['params']['dataparams']['client'];
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter = "";

        if ($client != "") {
            $filter .= " and emp.empid = '$client'";
        }

        $emplvl = $this->othersClass->checksecuritylevel($config);

        $query = "select 
            emp.empid,
            e.clientname as empname,
            timecard.dateid,
            time(timecard.actualin)  as actualin,
            time(timecard.actualout) as actualout,
            (timecard.latehrs * 60)  as tardmins
            from timecard
            left join employee as emp on emp.empid = timecard.empid
            left join client as e on e.clientid = emp.empid
            where timecard.dateid between '" . $start . "' and '" . $end . "'
            and timecard.latehrs > 0
            and emp.level in $emplvl $filter
            order by timecard.dateid";

        return $this->coreFunctions->opentable($query);
    }

    public function noinout_query($config)
    {
        $client = $config['params']['dataparams']['clientname'];
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter = "";

        if ($client != "") {
            $filter .= " and e.clientname = '$client'";
        }

        $emplvl = $this->othersClass->checksecuritylevel($config);

        $query = "select e.clientname, e.client, timecard.dateid,time(timecard.schedin)  AS schedin,
            time(timecard.schedout) AS schedout,time(timecard.actualin)  AS actualin,time(timecard.actualout) AS actualout,
            timecard.reghrs,date_format(timecard.dateid, '%a') as acnoday,timecard.absdays,timecard.latehrs,timecard.latehrs2,
            timecard.underhrs,timecard.othrs,timecard.ndiffhrs,timecard.ndiffot,timecard.daytype,
            time(timecard.actualbrkin)  AS lunchin,time(timecard.actualbrkout) AS lunchout,tms.shftcode,
            time(timecard.abrk1stout) AS abrk1stout,time(timecard.abrk1stin)  AS abrk1stin,time(timecard.abrk2ndin)  AS abrk2ndin,
            time(timecard.abrk2ndout) AS abrk2ndout,timecard.isnologin,time(timecard.actualin) as timeisnologin,
            timecard.isnombrkout,time(timecard.brk1stout) as timeisnombrkout,timecard.isnombrkin,
            time(timecard.brk1stin) as timeisnombrkin,timecard.isnolunchout,time(timecard.actualbrkout) as timeisnolunchout,
            timecard.isnolunchin,time(timecard.actualbrkin) as timeisnolunchin,timecard.isnopbrkout,
            time(timecard.brk2ndout) as timeisnopbrkout,timecard.isnopbrkin,time(timecard.brk2ndin) as timeisnopbrkin,
            timecard.isnologout,time(timecard.actualout) as timeisnologout,timecard.isnologpin,timecard.isnologunder,
            r.isrestday,lr.status as leavestat,p.codename,timecard.lateoffset,ls.isnopay,

                          -- Penalty: each flag that is 1 counts as 50
            (ifnull(timecard.isnologin,   0) +
            ifnull(timecard.isnombrkout,  0) +
            ifnull(timecard.isnombrkin,   0) +
            ifnull(timecard.isnolunchout, 0) +
            ifnull(timecard.isnolunchin,  0) +
            ifnull(timecard.isnopbrkout,  0) +
            ifnull(timecard.isnopbrkin,   0) +
            ifnull(timecard.isnologout,   0) +
            ifnull(timecard.isnologpin,   0) +
            ifnull(timecard.isnologunder, 0)) * 50 as penalty

        from timecard
        left join employee as emp on emp.empid = timecard.empid
        left join client as e on e.clientid = emp.empid
        left join tmshifts as tms on tms.line  = timecard.shiftid
        left join changeshiftapp as r on r.empid  = timecard.empid and r.dateid = timecard.dateid
        left join leavetrans as lr on lr.empid = timecard.empid and lr.effectivity = timecard.dateid and lr.status = 'A'
        left join leavesetup as ls on ls.trno = lr.trno
        left join paccount as p on p.line = ls.acnoid
        where timecard.dateid between '" . $start . "' and '" . $end . "' and emp.level in $emplvl $filter

        and (ifnull(timecard.isnologin,0) = 1 or
        ifnull(timecard.isnombrkout,0) = 1 or
        ifnull(timecard.isnombrkin,0) = 1 or
        ifnull(timecard.isnolunchout,0) = 1 or
        ifnull(timecard.isnolunchin,0) = 1 or
        ifnull(timecard.isnopbrkout,0) = 1 or
        ifnull(timecard.isnopbrkin,0) = 1 or
        ifnull(timecard.isnologout,0) = 1 or
        ifnull(timecard.isnologpin,0) = 1 or
        ifnull(timecard.isnologunder,0) = 1 or
        timecard.lateoffset > 0)
        order by timecard.dateid";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $layoutsize)
    {
        $str       = "";
        $center    = $config['params']['center'];
        $username  = $config['params']['user'];
        $start     = $config['params']['dataparams']['start'];
        $end       = $config['params']['dataparams']['end'];
        $empname   = $config['params']['dataparams']['clientname'];
        $empid     = $config['params']['dataparams']['client'];
        $divcode = $config['params']['dataparams']['divcode'];
        $printdate = date("F d, Y");
        $layoutsize = 1000;
        $font = $this->companysetup->getrptfont($config['params']);

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";

        $client   = $config['params']['dataparams']['client'];
        $division = $this->coreFunctions->getfieldvalue("employee", "divid", "empid=?", [$client]);
        $divcode  = $this->coreFunctions->getfieldvalue("division", "divcode", "divid=?", [$division]);

        $img = '';

        $logo = '';

        switch ($divcode) {
            case '001':
                $companyPath = $this->companysetup->getlogopath($config['params']) . 'paflogo.png';
                $localPath = public_path('images/cdohris/paflogo.png');
                $img = 'paflogo.png';
                break;
            case '002':
                $companyPath = $this->companysetup->getlogopath($config['params']) . 'mbcpaflogo.png';
                $localPath = public_path('images/cdohris/mbcpaflogo.png');
               $img = 'mbcpaflogo.png';
                break;
            case '003':
                $companyPath = $this->companysetup->getlogopath($config['params']) . 'mbcpaflogo.png';
                $localPath = public_path('images/cdohris/ridefundpaf.png');
                $img = 'ridefundpaf.png';
                break;
            case '004':
                $companyPath = $this->companysetup->getlogopath($config['params']) . 'mbcpaflogo.png';
                $localPath = public_path('images/cdohris/samplelogo.png');
                $img = 'samplelogo.png';
                break;
        }

        $companyPath = $this->companysetup->getlogopath($config['params']) . $img;
        $localPath   = public_path('images/cdohris/' . $img);

        $path = str_replace('public/', '', $companyPath);
        $logopath = URL::to($path); 


        $imgPath = file_exists($companyPath) ? $companyPath : $localPath;

        // IMAGE SA HEADER 
        $str .= "<div style='text-align:left; margin-top:10px;'>";
        // $str .= "<img src='{$logopath}' width='400' height='150'>";
        // $str .= "</div>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col("<img src='{$logopath}' width='500' height='100'>", '550', null, false, '', '', 'L', $font, '14', '', '', '');
        $str .= $this->reporter->col("<span style='font-size:27px;'>HUMAN RESOURCES DEPARTMENT</span>"  . '<br/>' 
        . "<span style='color:#000000;'>" . $this->coreFunctions->opentable($qry)[0]->address, '450', null, false, '', '', 'R', $font, '14', '', '#029aff', '');
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('EMPLOYEE EVALUATION RECORD', null, null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee Name :  ' . $empname, null, null, false, '', '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employee ID :  ' . $empid, null, null, false, '', '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Period Cover :  ' . $start . ' - ' . $end, null, null, false, '', '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date :  ' . strtoupper($printdate), null, null, false, '', '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        return $str;
    }

    public function report_default_detailed($config, $data, $noinout_all = [], $tardiness_all = [])
    {
        $border     = '1px solid';
        $font       = $this->companysetup->getrptfont($config['params']);
        $font_size  = '10';
        $layoutsize = '1000';
        $str        = '';

        // Group absence data by empid
        $grouped = [];
        foreach ($data as $row) {
            $grouped[$row->empid][] = $row;
        }

        // Group noinout by empid
        $noinout_grouped = [];
        foreach ($noinout_all as $row) {
            $noinout_grouped[$row->client][] = $row;
        }

        // Group tardiness by empid
        $tardiness_grouped = [];
        foreach ($tardiness_all as $row) {
            $tardiness_grouped[$row->empid][] = $row;
        }

        foreach ($noinout_grouped as $empid => $nrows) {
            if (!isset($grouped[$empid])) {
                $grouped[$empid] = [];
            }
        }
        foreach ($tardiness_grouped as $empid => $trows) {
            if (!isset($grouped[$empid])) {
                $grouped[$empid] = [];
            }
        }

        if (empty($grouped)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);

        foreach ($grouped as $empid => $rows) {

            $savedStart = $config['params']['dataparams']['start'];
            $savedEnd   = $config['params']['dataparams']['end'];

            // Get empname from source ang may data
            if (!empty($rows)) {
                $empname = $rows[0]->empname;
                $divcode = $rows[0]->divcode;
            } elseif (isset($noinout_grouped[$empid])) {
                $empname = $noinout_grouped[$empid][0]->clientname;
                $divcode = '';
            } elseif (isset($tardiness_grouped[$empid])) {
                $empname = $tardiness_grouped[$empid][0]->empname;
                $divcode = '';
            } else {
                $empname = '';
                $divcode = '';
            }

            $config['params']['dataparams']['client']     = $empid;
            $config['params']['dataparams']['clientname'] = $empname;
            $config['params']['dataparams']['divcode']    = $divcode;
            $config['params']['dataparams']['start']      = $savedStart;
            $config['params']['dataparams']['end']        = $savedEnd;

            $str .= $this->displayHeader($config, $layoutsize);

            // SUMMARY OF ABSENCES
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('SUMMARY OF ABSENCES', null, null, false, $border . ';background-color:#DED1D1FF;', 'B', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DATE',    '150', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '','', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('DETAILS', '700', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->col('DAYS',    '150', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalabs = 0;
            foreach ($rows as $value) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(date("M d, Y", strtotime($value->dateid)), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($value->reason != '' ? $value->reason : 'NO RECORD OF DUTY', '700', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(($value->absdays / 8), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totalabs += $value->absdays;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL ABSENCES', '850', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col(number_format($totalabs / 8, 1), '150', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= '<br/>';

            // SUMMARY OF TARDINESS 
            $tardiness_data = isset($tardiness_grouped[$empid]) ? $tardiness_grouped[$empid] : [];

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('SUMMARY OF TARDINESS', null, null, false, $border . ';background-color:#DED1D1FF;', 'B', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DATE',      '150', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->col('DETAILS',   '700', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->col('MINUTES',   '150', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totaltardiness = 0;
            if (!empty($tardiness_data)) {
                foreach ($tardiness_data as $value) {
                    $shift = date("h:i A", strtotime($value->actualin)) . ' - ' . date("h:i A", strtotime($value->actualout));
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(date("M d, Y", strtotime($value->dateid)), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($shift, '700', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($value->tardmins, '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $totaltardiness += $value->tardmins;
                }
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL TARDINESS', '850', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col($totaltardiness, '150', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= '<br/>';

            // SUMMARY OF NO IN & OUT 
            $noinout_data = isset($noinout_grouped[$empid]) ? $noinout_grouped[$empid] : [];

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('SUMMARY OF NO IN & OUT', null, null, false, $border . ';background-color:#DED1D1FF;', 'B', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('DATE',    '150', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->col('DETAILS', '700', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->col('PENALTY', '150', null, false, '1px solid black;background-color:#000000;', 'B', 'C', $font, $font_size, 'B', '#ffffff', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalpenalty = 0;
            if (!empty($noinout_data)) {
                foreach ($noinout_data as $row) {
                    $timeisnologin    = $row->timeisnologin    == null ? '' : (new DateTime($row->timeisnologin))->format('h:i A');
                    $timeisnombrkout  = $row->timeisnombrkout  == null ? '' : (new DateTime($row->timeisnombrkout))->format('h:i A');
                    $timeisnombrkin   = $row->timeisnombrkin   == null ? '' : (new DateTime($row->timeisnombrkin))->format('h:i A');
                    $timeisnolunchout = $row->timeisnolunchout == null ? '' : (new DateTime($row->timeisnolunchout))->format('h:i A');
                    $timeisnolunchin  = $row->timeisnolunchin  == null ? '' : (new DateTime($row->timeisnolunchin))->format('h:i A');
                    $timeisnopbrkout  = $row->timeisnopbrkout  == null ? '' : (new DateTime($row->timeisnopbrkout))->format('h:i A');
                    $timeisnopbrkin   = $row->timeisnopbrkin   == null ? '' : (new DateTime($row->timeisnopbrkin))->format('h:i A');
                    $timeisnologout   = $row->timeisnologout   == null ? '' : (new DateTime($row->timeisnologout))->format('h:i A');

                    $isnologin    = $row->isnologin    == 1 ? 'NO MORNING IN '          . $timeisnologin    : '';
                    $isnombrkout  = $row->isnombrkout  == 1 ? 'NO MORNING BREAK OUT '   . $timeisnombrkout  : '';
                    $isnombrkin   = $row->isnombrkin   == 1 ? 'NO MORNING BREAK IN '    . $timeisnombrkin   : '';
                    $isnolunchout = $row->isnolunchout == 1 ? 'NO LUNCH BREAK OUT '     . $timeisnolunchout : '';
                    $isnolunchin  = $row->isnolunchin  == 1 ? 'NO LUNCH BREAK IN '      . $timeisnolunchin  : '';
                    $isnopbrkout  = $row->isnopbrkout  == 1 ? 'NO AFTERNOON BREAK OUT ' . $timeisnopbrkout  : '';
                    $isnopbrkin   = $row->isnopbrkin   == 1 ? 'NO AFTERNOON BREAK IN '  . $timeisnopbrkin   : '';
                    $isnologout   = $row->isnologout   == 1 ? 'NO AFTERNOON OUT '       . $timeisnologout   : '';
                    $isnologpin   = $row->isnologpin   == 1 ? 'NO AFTERNOON IN'                             : '';
                    $isnologunder = $row->isnologunder == 1 ? 'NO IN/OUT UNDERTIME'                         : '';

                    $parts  = array_filter([$isnologin, $isnombrkout, $isnombrkin, $isnolunchout,
                                            $isnolunchin, $isnopbrkout, $isnopbrkin, $isnologout,
                                            $isnologpin, $isnologunder]);
                    $detail = implode(' , ', $parts);

                    if ($detail == '' && $row->penalty == 0) continue;

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(date("M d, Y", strtotime($row->dateid)), '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col($detail,                                  '700', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col(number_format($row->penalty, 2),          '150', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $totalpenalty += $row->penalty;
                }
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('TOTAL PENALTY', '850', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col(number_format($totalpenalty, 2), '150', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= '<br/>';

            // FOOTER
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('<b>Important Notes.</b>', null, null, false, '', '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('This system generating data is intended for regularization and evaluation attachment only.', null, null, false, '', '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= '<br/>';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('PREPARED BY', null, null, false, '', '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($config['params']['user'], null, null, false, '', '', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Human Resource Staff', null, null, false, '', '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // Page break between employees
            $keys    = array_keys($grouped);
            $lastkey = end($keys);
            if ($empid !== $lastkey) {
                $str .= $this->reporter->page_break();
            }
        }

        $str .= $this->reporter->endreport();

        return $str;
    }

    // Count Helper
    public function getcountdata($config, $empid)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "select count(*) as cnt
        from timecard
        where empid = '$empid'
        and dateid between '" . $start . "' and '" . $end . "'
        and absdays <> 0";

        $result = $this->coreFunctions->opentable($query);
        return !empty($result) ? $result[0]->cnt : 0;
    }

} // end class