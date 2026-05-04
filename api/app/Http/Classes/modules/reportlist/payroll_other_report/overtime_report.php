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
use Illuminate\Support\Facades\URL;
use DateTime;

class overtime_report
{
    public $modulename = 'Overtime Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1500px;max-width:1500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];

    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config) // batchrep required works here
    {
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
        data_set($col1, 'dclientname.label', 'Employee');
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
        data_set($col1, 'sectrep.label', 'Section');

        $fields = ['batchrep'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'batchrep.lookupclass', 'lookupbatchrep');
        data_set($col2, 'batchrep.required', true);

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
    '' as dsectionname,
    '' as sectname,
    '' as orgsection,
    '' as sectid,
    '' as sectrep,
    '' as deptrep,
    '' as batchid,
    '' as line,
    '' as batch,
    '' as batchrep
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
        //case 67: md layout
        $batchdate = $this->datesquery_md($config);
        $result = $this->report_md_Layout_ot($config, $batchdate);
        return $result;
    }

    public function reportDefault($config)
    {
        $client = $config['params']['dataparams']['client'];
        $divid = $config['params']['dataparams']['divid'];
        $deptid = $config['params']['dataparams']['deptid'];
        $sectid = $config['params']['dataparams']['sectid'];
        $batch = $config['params']['dataparams']['batch'];

        $batchfilter = "";
        $filter = "";
        $filter1 = "";
        $filter2 = "";
        $filter3 = "";

        if ($client != "") {
            $filter .= " and emp.empid = '$client'";
        }
        if ($deptid != 0 && $deptid != "") {
            $filter1 .= " and emp.deptid = $deptid";
        }
        if ($divid != 0 && $divid != "") {
            $filter2 .= " and emp.divid = $divid";
        }

        if ($sectid != "") {
            $filter3 .= " and emp.sectid = $sectid";
        }

        if ($batch != "") {
            $batchfilter .= "batch.batch = '$batch'";
        }

        $query = "select distinct 1 as Num, division.divname, client.clientname as deptname, section.sectname, batch.startdate, batch.enddate,
            (select max(rs.basicrate) from ratesetup as rs where rs.empid = emp.empid and rs.dateeffect <= batch.startdate) as basicrate,
            emp.empid,cemp.clientname AS Employee,
            
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT15')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as OTREG,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT16')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as OTNS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT17')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as O8NS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT18')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as SUN,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT19')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as OTSUN,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT70')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as SUNS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT71')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as S8NS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT72')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as SHRD,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT73')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as OTSR,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT74')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as SRNS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT75')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as SR8NS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT76')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as LEGAL,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT77')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as OTLG,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT78')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as LGNS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT79')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as L8NS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT64')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as RDLG,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT65')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as LROT,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT68')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as RRNS,
            ifnull((select sum(p.db) from paytrancurrent as p left join paccount as a on a.line = p.acnoid where a.code = ('PT69')
            and p.batchid = pt.batchid and p.empid = pt.empid), 0) as RR8NS
            FROM ((( paytrancurrent as pt left join employee AS emp on emp.empid = pt.empid
            LEFT JOIN client ON client.clientid = emp.deptid) LEFT JOIN division ON division.divid = emp.divid)
            LEFT JOIN section ON section.sectid = emp.sectid)
            LEFT JOIN batch on batch.line = pt.batchid
            left join client as cemp on cemp.clientid = emp.empid
            where $batchfilter $filter $filter1 $filter2 $filter3
            group by emp.empid,pt.batchid, pt.empid, division.divname, client.clientname, section.sectname,cemp.clientname, batch.startdate, batch.enddate
            order by division.divname, client.clientname, section.sectname, cemp.clientname
            ;";
        // $this->othersClass->logConsole($query);
        return $this->coreFunctions->opentable($query);
    }

    public function datesquery_md($config)
    {
        $batch = $config['params']['dataparams']['batch'];
        $query = "select date(startdate) as start, date(enddate) as end from batch where batch = '$batch' limit 1";
        $result = $this->coreFunctions->opentable($query);
        return $result[0];
    }

    public function header_md($config, $layoutsize, $batchdate)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $divname = $config['params']['dataparams']['divname'];
        $deptname = $config['params']['dataparams']['deptname'];
        $section = $config['params']['dataparams']['sectname'];
        $batch = $config['params']['dataparams']['batch'];
        // $asof2 = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $str = '';
        $font = 'TAHOMA';
        $fontsize = "7";
        $fontsize_header = "8";
        $border = "1px solid ";
        $border2 = '3px solid';
        $str = '';

        if ($divname == '') {
            $divname = 'All Division';
        }
        if ($deptname == '') {
            $deptname = 'All Department';
        }
        if ($section == '') {
            $section = 'All Section';
        }

        $start = isset($batchdate->start) ? $batchdate->start : '';
        $startformat = $start ? date('d-M-Y', strtotime($start)) : '';
        $end = isset($batchdate->end) ? $batchdate->end : '';
        $endformat = $end ? date('d-M-Y', strtotime($end)) : '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Overtime Report', null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Division: <u>' . $divname . '</u>' . '       Department: <u>' . $deptname . '</u>' . '        Section: <u>' . $section, 100, null, false, $border, '', 'C', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $pageNum = strip_tags($this->reporter->pagenumber());
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Batch:' . $batch . '        Payroll Period: From: ' . $startformat . ' to ' . $endformat . '        Page  ' . $pageNum, null, null, false, $border, '', 'C', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable(1472.6);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 'null', null, false, $border2, 'B', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Code', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT-RG', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('08-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HR-SU', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT-SU', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SU-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('S8-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SH-RD', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT-SR', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SR-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SR8-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('HR-LG', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT-LG', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('LG-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('L8-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('LG-RD', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT-LR', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('RR-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('RR8-NS', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL OT & NS', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_md_Layout_ot($config, $batchdate)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid';
        $border2 = '3px solid';
        $font = 'TAHOMA';
        $fontsize = '7';
        $dept = null;
        $firstline = 0;

        $maxRows = 35;
        $rowCount = 0;
        $count = 0;
        $totalOTNS = 0;

        //subtotals
        $subOTREG = 0;
        $subOTNS = 0;
        $subO8NS = 0;
        $subSUN = 0;
        $subOTSUN = 0;
        $subSUNS = 0;
        $subS8NS = 0;
        $subSHRD = 0;
        $subOTSR = 0;
        $subSRNS = 0;
        $subSR8NS = 0;
        $subLEGAL = 0;
        $subOTLG = 0;
        $subLGNS = 0;
        $subL8NS = 0;
        $subRDLG = 0;
        $subLROT = 0;
        $subRRNS = 0;
        $subRR8NS = 0;
        $subTotalOTNS = 0;

        //grand total
        $grandOTREG = 0;
        $grandOTNS = 0;
        $grandO8NS = 0;
        $grandSUN = 0;
        $grandOTSUN = 0;
        $grandSUNS = 0;
        $grandS8NS = 0;
        $grandSHRD = 0;
        $grandOTSR = 0;
        $grandSRNS = 0;
        $grandSR8NS = 0;
        $grandLEGAL = 0;
        $grandOTLG = 0;
        $grandLGNS = 0;
        $grandL8NS = 0;
        $grandRDLG = 0;
        $grandLROT = 0;
        $grandRRNS = 0;
        $grandRR8NS = 0;
        $grandTotalOTNS = 0;

        $str = '';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1500'];
        $layoutsize = $this->reportParams['layoutSize'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10px;margin-top:10px;margin-left:25px;');
        $str .= $this->header_md($config, $layoutsize, $batchdate);

        foreach ($result as $key => $data) {
            $firstline++;
            $deptChange = ($dept !== $data->deptname && $dept !== null);//if change, subtotal
            $newDept = ($dept !== $data->deptname);//if new, deptname

            $neededRows = 1; //default
            if ($deptChange)
                $neededRows += 2; //for subtotal
            if ($newDept)
                $neededRows += 2; //space + dept header

            if ($rowCount > 0 && ($rowCount + $neededRows) > $maxRows) {
                $str .= $this->reporter->page_break();
                $str .= $this->header_md($config, $layoutsize, $batchdate);
                $rowCount = 0;
                $firstline = 1;
            }

            if ($deptChange) { //for subtotal
                $rowCount += 2;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('SUB TOTAL', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subOTNS == 0 ? '-' : number_format($subOTNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subSUN == 0 ? '-' : number_format($subSUN, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subSUNS == 0 ? '-' : number_format($subSUNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subSHRD == 0 ? '-' : number_format($subSHRD, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subSRNS == 0 ? '-' : number_format($subSRNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subLEGAL == 0 ? '-' : number_format($subLEGAL, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subLGNS == 0 ? '-' : number_format($subLGNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subRDLG == 0 ? '-' : number_format($subRDLG, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subRRNS == 0 ? '-' : number_format($subRRNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subTotalOTNS == 0 ? '-' : number_format($subTotalOTNS, 2), '130', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subOTREG == 0 ? '-' : number_format($subOTREG, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subO8NS == 0 ? '-' : number_format($subO8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subOTSUN == 0 ? '-' : number_format($subOTSUN, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subS8NS == 0 ? '-' : number_format($subS8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subOTSR == 0 ? '-' : number_format($subOTSR, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subSR8NS == 0 ? '-' : number_format($subSR8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subOTLG == 0 ? '-' : number_format($subOTLG, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subL8NS == 0 ? '-' : number_format($subL8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subLROT == 0 ? '-' : number_format($subLROT, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($subRR8NS == 0 ? '-' : number_format($subRR8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                //reset
                $subOTREG = 0;
                $subOTNS = 0;
                $subO8NS = 0;
                $subSUN = 0;
                $subOTSUN = 0;
                $subSUNS = 0;
                $subS8NS = 0;
                $subSHRD = 0;
                $subOTSR = 0;
                $subSRNS = 0;
                $subSR8NS = 0;
                $subLEGAL = 0;
                $subOTLG = 0;
                $subLGNS = 0;
                $subL8NS = 0;
                $subRDLG = 0;
                $subLROT = 0;
                $subRRNS = 0;
                $subRR8NS = 0;
                $subTotalOTNS = 0;
            }

            if ($newDept) { //for new dept
                $height = ($firstline == 1) ? null : 40;
                $rowCount += ($firstline == 1) ? 1 : 2; // spacer
                $str .= '</br>';

                $dept = $data->deptname;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($dept, null, null, false, $border, '', '', $font, '8', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->empid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->Employee, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->OTREG == 0 ? '-' : number_format($data->OTREG, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->OTNS == 0 ? '-' : number_format($data->OTNS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->O8NS == 0 ? '-' : number_format($data->O8NS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->SUN == 0 ? '-' : number_format($data->SUN, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->OTSUN == 0 ? '-' : number_format($data->OTSUN, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->SUNS == 0 ? '-' : number_format($data->SUNS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->S8NS == 0 ? '-' : number_format($data->S8NS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->SHRD == 0 ? '-' : number_format($data->SHRD, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->OTSR == 0 ? '-' : number_format($data->OTSR, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->SRNS == 0 ? '-' : number_format($data->SRNS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->SR8NS == 0 ? '-' : number_format($data->SR8NS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->LEGAL == 0 ? '-' : number_format($data->LEGAL, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->OTLG == 0 ? '-' : number_format($data->OTLG, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->LGNS == 0 ? '-' : number_format($data->LGNS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->L8NS == 0 ? '-' : number_format($data->L8NS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->RDLG == 0 ? '-' : number_format($data->RDLG, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->LROT == 0 ? '-' : number_format($data->LROT, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->RRNS == 0 ? '-' : number_format($data->RRNS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->RR8NS == 0 ? '-' : number_format($data->RR8NS, 2), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            
            $totalOTNS =
                $data->OTREG + $data->OTNS + $data->O8NS +
                $data->SUN + $data->OTSUN + $data->SUNS + $data->S8NS +
                $data->SHRD + $data->OTSR + $data->SRNS + $data->SR8NS + $data->LEGAL +
                $data->OTLG + $data->LGNS + $data->L8NS + $data->RDLG + $data->LROT +
                $data->RRNS + $data->RR8NS;

            $str .= $this->reporter->col($totalOTNS == 0 ? '-' :number_format($totalOTNS,2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //subtotals
            $subOTREG += $data->OTREG;
            $subOTNS += $data->OTNS;
            $subO8NS += $data->O8NS;
            $subSUN += $data->SUN;
            $subOTSUN += $data->OTSUN;
            $subSUNS += $data->SUNS;
            $subS8NS += $data->S8NS;
            $subSHRD += $data->SHRD;
            $subOTSR += $data->OTSR;
            $subSRNS += $data->SRNS;
            $subSR8NS += $data->SR8NS;
            $subLEGAL += $data->LEGAL;
            $subOTLG += $data->OTLG;
            $subLGNS += $data->LGNS;
            $subL8NS += $data->L8NS;
            $subRDLG += $data->RDLG;
            $subLROT += $data->LROT;
            $subRRNS += $data->RRNS;
            $subRR8NS += $data->RR8NS;
            $subTotalOTNS += $totalOTNS;

            //for grandtotal
            $grandOTREG += $data->OTREG;
            $grandOTNS += $data->OTNS;
            $grandO8NS += $data->O8NS;
            $grandSUN += $data->SUN;
            $grandOTSUN += $data->OTSUN;
            $grandSUNS += $data->SUNS;
            $grandS8NS += $data->S8NS;
            $grandSHRD += $data->SHRD;
            $grandOTSR += $data->OTSR;
            $grandSRNS += $data->SRNS;
            $grandSR8NS += $data->SR8NS;
            $grandLEGAL += $data->LEGAL;
            $grandOTLG += $data->OTLG;
            $grandLGNS += $data->LGNS;
            $grandL8NS += $data->L8NS;
            $grandRDLG += $data->RDLG;
            $grandLROT += $data->LROT;
            $grandRRNS += $data->RRNS;
            $grandRR8NS += $data->RR8NS;
            $grandTotalOTNS += $totalOTNS;

            $totalOTNS = 0; //reset
            $count++;
            $rowCount++;
        }
        //last subtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SUB TOTAL', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subOTNS == 0 ? '-' : number_format($subOTNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subSUN == 0 ? '-' : number_format($subSUN, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subSUNS == 0 ? '-' : number_format($subSUNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subSHRD == 0 ? '-' : number_format($subSHRD, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subSRNS == 0 ? '-' : number_format($subSRNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subLEGAL == 0 ? '-' : number_format($subLEGAL, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subLGNS == 0 ? '-' : number_format($subLGNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subRDLG == 0 ? '-' : number_format($subRDLG, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subRRNS == 0 ? '-' : number_format($subRRNS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subTotalOTNS == 0 ? '-' : number_format($subTotalOTNS, 2), '130', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subOTREG == 0 ? '-' : number_format($subOTREG, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subO8NS == 0 ? '-' : number_format($subO8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subOTSUN == 0 ? '-' : number_format($subOTSUN, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subS8NS == 0 ? '-' : number_format($subS8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subOTSR == 0 ? '-' : number_format($subOTSR, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subSR8NS == 0 ? '-' : number_format($subSR8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subOTLG == 0 ? '-' : number_format($subOTLG, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subL8NS == 0 ? '-' : number_format($subL8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subLROT == 0 ? '-' : number_format($subLROT, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($subRR8NS == 0 ? '-' : number_format($subRR8NS, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //GRAND TOTAL
        $str .= '</br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('No. of record printed: '.$count, null, null, false, $border, '', '', $font, '6', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandOTNS == 0 ? '-' : number_format($grandOTNS, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandSUN == 0 ? '-' : number_format($grandSUN, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandSUNS == 0 ? '-' : number_format($grandSUNS, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandSHRD == 0 ? '-' : number_format($grandSHRD, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandSRNS == 0 ? '-' : number_format($grandSRNS, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandLEGAL == 0 ? '-' : number_format($grandLEGAL, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandLGNS == 0 ? '-' : number_format($grandLGNS, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandRDLG == 0 ? '-' : number_format($grandRDLG, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandRRNS == 0 ? '-' : number_format($grandRRNS, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandTotalOTNS == 0 ? '-' : number_format($grandTotalOTNS, 2), '130', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandOTREG == 0 ? '-' : number_format($grandOTREG,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandO8NS == 0 ? '-' : number_format($grandO8NS,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandOTSUN == 0 ? '-' : number_format($grandOTSUN,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandS8NS == 0 ? '-' : number_format($grandS8NS,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandOTSR == 0 ? '-' : number_format($grandOTSR,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandSR8NS == 0 ? '-' : number_format($grandSR8NS,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandOTLG == 0 ? '-' : number_format($grandOTLG,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandL8NS == 0 ? '-' : number_format($grandL8NS,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandLROT == 0 ? '-' : number_format($grandLROT,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($grandRR8NS == 0 ? '-' : number_format($grandRR8NS,2), '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable(1472.6);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border2, 'T', '', $font, '8', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '</br></br>';

        $str .= $this->reporter->endreport();
        return $str;
    }


}