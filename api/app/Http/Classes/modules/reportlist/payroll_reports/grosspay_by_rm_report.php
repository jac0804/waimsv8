<?php

namespace App\Http\Classes\modules\reportlist\payroll_reports;

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

class grosspay_by_rm_report

{
    public $modulename = 'Grosspay By RM Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;<max-width:15></max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'divrep', 'deptrep', 'sectrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
        data_set($col1, 'divrep.label', 'Company');
        data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
        data_set($col1, 'deptrep.label', 'Department');
        data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
        data_set($col1, 'sectrep.label', 'Section');
        $fields = ['batchrep', 'prepared', 'checked', 'approved'];
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
        $batchdate = $this->datesquery_md($config);
        $result = $this->report_md_Layout_ot($config, $batchdate);
        return $result;
    }
    public function reportDefault($config)
    {
        $divid = $config['params']['dataparams']['divid'];
        $divname = $config['params']['dataparams']['divname'];
        $deptid = $config['params']['dataparams']['deptid'];
        $deptname = $config['params']['dataparams']['deptname'];
        $sectid = $config['params']['dataparams']['sectid'];
        $sectname = $config['params']['dataparams']['sectname'];
        $batch = $config['params']['dataparams']['batch'];

        $batchfilter = "";
        $filter1 = "";
        $filter2 = "";
        $filter3 = "";


        if ($divname != "") {
            $filter1 .= " and division.divname = '$divname'";
        }

        if ( $deptname != "") {
            $filter2 .= " and dept.clientname = '$deptname'";
        }

        if ($sectname != "") {
            $filter3 .= " and sect.sectname = '$sectname'";
        }

        if ($batch != "") {
            $batchfilter .= "batch.batch = '$batch'";
        }

        $query = "
                select * from(select name.clientname, division.divname, dept.clientname as deptname, sect.sectname, sum(pay.db - pay.cr) as gross
                from paytrancurrent as pay
                left join employee as emp on emp.empid = pay.empid
                left join client as name on name.clientid = emp.empid
                left join division  on division.divid = emp.divid
                left join client as dept on dept.clientid = emp.deptid
                left join section as sect on sect.sectid = emp.sectid
                left join paccount as pa on pa.line = pay.acnoid
                left join batch on batch.line = pay.batchid
                where (pa.alias in ('Otother','OTREG','RESTDAY','OTREST') 
                or pa.code in ('PT57', 'PT4', 'PT34', 'PT67', 'PT30', 'PT6', 'PT5', 'PT7'))
                and $batchfilter $filter1 $filter2 $filter3
                group by name.clientname, division.divname, dept.clientname, sect.sectname 
            union all
                select name.clientname, division.divname, dept.clientname as deptname, sect.sectname, sum(pay.db - pay.cr) as gross
                from paytranhistory as pay
                left join employee as emp on emp.empid = pay.empid
                left join client as name on name.clientid = emp.empid
                left join division  on division.divid = emp.divid
                left join client as dept on dept.clientid = emp.deptid
                left join section as sect on sect.sectid = emp.sectid
                left join paccount as pa on pa.line = pay.acnoid
                left join batch on batch.line = pay.batchid
                where (pa.alias in ('Otother','OTREG','RESTDAY','OTREST') 
                or pa.code in ('PT57', 'PT4', 'PT34', 'PT67', 'PT30', 'PT6', 'PT5', 'PT7'))
                and  $batchfilter $filter1 $filter2 $filter3
                group by name.clientname, division.divname, dept.clientname, sect.sectname) as a
                order by divname, deptname, sectname, clientname 

                ;";
                // var_dump($query);
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
        $divname = $config['params']['dataparams']['divname'];
        $deptname = $config['params']['dataparams']['deptname'];
        $section = $config['params']['dataparams']['sectname'];
        $batch = $config['params']['dataparams']['batch'];
        $str = '';
        $font = 'TAHOMA';
        $fontsize = "12";
        $fontsize_header = "15";
        $fontsize_subheader = "9";
        $border = "1px solid ";
        $border2 = "3px solid ";
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
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = date('m/d/Y', strtotime($this->othersClass->getCurrentDate()));
        $start = isset($batchdate->start) ? $batchdate->start : '';
        $startformat = $start ? date('d-M-Y', strtotime($start)) : '';
        $end = isset($batchdate->end) ? $batchdate->end : '';
        $endformat = $end ? date('d-M-Y', strtotime($end)) : '';

        $str .= '</br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GROSS PAY BY RM REPORT', null, null, false, $border, '', 'C', $font, '20', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('</u> Division: <u>' . $divname . '</u>' . '       Department: <u>' . $deptname . '</u>' . '        Section: <u>' . $section, 100, null, false, $border, '', 'C', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Batch:' . $batch. ' Payroll Period: From'. $startformat. 'to' . $endformat, null, null, false, $border, '', 'C', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</br>';
        $pageNum = strip_tags($this->reporter->pagenumber());
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Print Date :' . $current_timestamp, null, null, false, $border, '', 'L', $font, $fontsize_subheader, '', '', '');
        $str .= $this->reporter->col('Page  ' . $pageNum, null, null, false, $border, '', 'R', $font, $fontsize_subheader, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border2, 'B', 'L', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', 300, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', 280, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', 40, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Gross Pay', 280, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
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
        $fontsize = '10';
        $count = 1;
        $maxRows = 30;
        $rowCount = 0;
        $subGross = 0;
        $grandGross = 0;
        $dept = null;
        $str = '';
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1150'];
        $layoutsize = $this->reportParams['layoutSize'];
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10px;margin-top:10px;margin-left:25px;');
        $str .= $this->header_md($config, $layoutsize, $batchdate);
        foreach ($result as $key => $data) {

            $deptChange = ($dept !== $data->deptname && $dept !== null);//if change, subtotal
            $newDept = ($dept !== $data->deptname);//if new, deptname
            if ($rowCount > 0 && $rowCount > $maxRows) {  //row counter limit
                $str .= $this->reporter->page_break();
                $str .= $this->header_md($config, $layoutsize, $batchdate);
                $str .= '</br>';
                $rowCount = 0;
            }
            if ($deptChange) { //for subtotal
                $rowCount += 1;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Sub Total', 600, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($subGross, 2), 300, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $subGross = 0;//reset
            }
            if ($newDept) { //for new dept
                $str .= '</br>';
                $rowCount += 1;
                $dept = $data->deptname;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', 300, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($dept, 300, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', 175, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();  
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', 300, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->clientname, 300, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data->gross, 2 ), 300, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', 'null', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $count++;
            $rowCount++;
            $subGross += $data->gross;
            $grandGross += $data->gross;
            $str .= $this->reporter->begintable($layoutsize);//small space
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', null, 15, false, $border, '', 'L', $font, 9, 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        // final subtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Sub Total', 600, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subGross, 2), 300, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        //Grand Total
        $rowCount += 3;
        if ($rowCount > 0 && $rowCount > $maxRows) {
            $str .= $this->reporter->page_break();
            $str .= $this->header_md($config, $layoutsize, $batchdate);
            $str .= '</br></br>';
        }
        $str .= '</br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', 600, null, false, $border, 'T', 'L', $font, 9, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandGross, 2), 300, null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', null, null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border2, 'B', 'L', $font, 9, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</br></br>';
        $str .= $this->reporter->endreport();
        return $str;
    }




}