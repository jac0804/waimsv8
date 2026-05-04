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

class payroll_receiving_report
{
    public $modulename = 'Payroll Receiving Report';
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
        $deptid = $config['params']['dataparams']['deptid'];
        $sectid = $config['params']['dataparams']['sectid'];
        $batch = $config['params']['dataparams']['batch'];

        $batchfilter = "";
        $filter1 = "";
        $filter2 = "";
        $filter3 = "";

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

        $query = "
                select cemp.clientname as employee,'' as workdays,
                (select max(rs.basicrate) from ratesetup as rs where rs.empid = emp.empid and rs.dateeffect <= batch.startdate) as basicrate,
                sum(case when a.code = 'PT57' then pt.db else 0 end) as BasicPay,
                ifnull(max(case when a.code = 'PT57' then pt.qty end), 0) as Dwork,
                ifnull(sum(case when a.code in ('PT5','PT6','PT7') then pt.cr else 0 end), 0) as AbsLate,
                ifnull(sum(case when a.code in ('PT15','PT16','PT17','PT18','PT19','PT64','PT65','PT66') then pt.db else 0 end), 0) as OT,
                ifnull(sum(case when a.code in ('PT4','PT8','PT9','PT58','PT29','PT30','PT31','PT32','PT34','PT67') then pt.db else 0 end), 0) as OtherPay,
                ifnull(sum(case when a.code = 'PT31' then pt.db else 0 end), 0) as Allowance,
                ifnull(sum(case when a.code in ('PT10','PT11','PT12','PT13','PT14','PT33','PT35','PT36','PT37','PT44','PT51','PT48','69','70','71') then pt.cr else 0 end), 0) as Deduction,
                ifnull(sum(case when a.code = 'PT42' then pt.cr else 0 end), 0) as WHT,
                ifnull(sum(case when a.code = 'PT44' then pt.cr else 0 end), 0) as SSS,
                ifnull(sum(case when a.code = 'PT48' then pt.cr else 0 end), 0) as Phic,
                ifnull(sum(case when a.code = 'PT51' then pt.cr else 0 end), 0) as HDMF,
                ifnull(sum(case when a.code = 'PT14' then pt.cr else 0 end), 0) as Vale,
                (select count(tm.reghrs) from timecard as tm
                where tm.empid = emp.empid and tm.dateid between batch.startdate and batch.enddate
                and tm.reghrs <> 0  and tm.reghrs > 0) as days
                from paytrancurrent as pt
                left join paccount as a on a.line = pt.acnoid
                left join employee as emp on emp.empid = pt.empid
                left join client as cemp on cemp.clientid = emp.empid
                left join batch as batch on batch.line = pt.batchid
                where $batchfilter $filter1 $filter2 $filter3       
                group by emp.empid, cemp.clientname, batch.startdate, batch.enddate, pt.batchid, pt.empid
                union all
                select cemp.clientname as employee,'' as workdays,
                (select max(rs.basicrate) from ratesetup as rs where rs.empid = emp.empid and rs.dateeffect <= batch.startdate) as basicrate,
                sum(case when a.code = 'PT57' then pt.db else 0 end) as BasicPay,
                ifnull(max(case when a.code = 'PT57' then pt.qty end), 0) as Dwork,
                ifnull(sum(case when a.code in ('PT5','PT6','PT7') then pt.cr else 0 end), 0) as AbsLate,
                ifnull(sum(case when a.code in ('PT15','PT16','PT17','PT18','PT19','PT64','PT65','PT66') then pt.db else 0 end), 0) as OT,
                ifnull(sum(case when a.code in ('PT4','PT8','PT9','PT58','PT29','PT30','PT31','PT32','PT34','PT67') then pt.db else 0 end), 0) as OtherPay,
                ifnull(sum(case when a.code = 'PT31' then pt.db else 0 end), 0) as Allowance,
                ifnull(sum(case when a.code in ('PT10','PT11','PT12','PT13','PT14','PT33','PT35','PT36','PT37','PT44','PT51','PT48','69','70','71') then pt.cr else 0 end), 0) as Deduction,
                ifnull(sum(case when a.code = 'PT42' then pt.cr else 0 end), 0) as WHT,
                ifnull(sum(case when a.code = 'PT44' then pt.cr else 0 end), 0) as SSS,
                ifnull(sum(case when a.code = 'PT48' then pt.cr else 0 end), 0) as Phic,
                ifnull(sum(case when a.code = 'PT51' then pt.cr else 0 end), 0) as HDMF,
                ifnull(sum(case when a.code = 'PT14' then pt.cr else 0 end), 0) as Vale,
                (select count(tm.reghrs) from timecard as tm
                where tm.empid = emp.empid and tm.dateid between batch.startdate and batch.enddate
                and tm.reghrs <> 0  and tm.reghrs > 0) as days
                from paytranhistory as pt
                left join paccount as a on a.line = pt.acnoid
                left join employee as emp on emp.empid = pt.empid
                left join client as cemp on cemp.clientid = emp.empid
                left join batch as batch on batch.line = pt.batchid
                where $batchfilter $filter1 $filter2 $filter3
                group by emp.empid, cemp.clientname, batch.startdate, batch.enddate, pt.batchid, pt.empid
            ;";
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
        $fontsize = "8";
        $fontsize_header = "8";
        $border = "1px solid ";
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

        $str .='</br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAYROLL RECEIVING SUMMARY', null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WE HEREBY ACKNOWLEDGE to have received from '. strtoupper($headerdata[0]->name).' '. strtoupper($headerdata[0]->address). '  the sum specified opposite our respective names as full compensation for services rendered for the period '. $startformat . ' to ' . $endformat , null, null, false, $border, '', 'L', $font, '8', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Batch: <u>' . $batch . '</u> Division: <u>' . $divname . '</u>' . '       Department: <u>' . $deptname . '</u>' . '        Section: <u>' . $section, 100, null, false, $border, '', 'C', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</br>';
        $pageNum = strip_tags($this->reporter->pagenumber());
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col( 'Print Date :' . $current_timestamp , null, null, false, $border, '', 'L', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->col( 'Page  ' . $pageNum, null, null, false, $border, '', 'R', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' ,470, null, false, $border, '', 'L', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->col('Deduction', 315, null, false, $border, '', 'C', $font, $fontsize_header, 'B', '', '');
        $str .= $this->reporter->col('', 95, null, false, $border, '', 'L', $font, $fontsize_header, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('#', '20', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee Name', '180', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Days of Work', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rate', '40', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total Regular Wage', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OT', '40', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Other Income', '40', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Total Amt', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('W/Tax', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SSS', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Medic', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PagIbig', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Net Amount Paid', '70', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SIGNATURE OF PAYEE', '90', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function report_md_Layout_ot($config, $batchdate)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid';
        $borderLine = '1.5px solid';
        $border2 = '3px solid';
        $borderLine = '2px solid';
        $font = 'TAHOMA';
        $fontsize = '8';
        $count = 1;
        $maxRows = 31;
        $rowCount = 0;
        $totalNetPay = 0;
        $str = '';
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1150'];
        $layoutsize = $this->reportParams['layoutSize'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '10px;margin-top:10px;margin-left:25px;');
        $str .= $this->header_md($config, $layoutsize, $batchdate);
        foreach ($result as $key => $data) {

        if ($rowCount > 0 && $rowCount  > $maxRows) {
            $str .= $this->reporter->page_break();
            $str .= $this->header_md($config, $layoutsize, $batchdate);
            $rowCount = 0;
        }
        $totalAmt = $data->BasicPay;
        //+ $data->OT + $data->OtherPay
        $NetPay = $totalAmt - ($data->WHT + $data->SSS + $data->Phic + $data->HDMF);
        //$totalAmt - $data->Deduction 
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($count, '20', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->employee, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->days, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('***', '40', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->BasicPay, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('0.00', '40', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('0.00', '40', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($totalAmt, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->WHT, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->SSS, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->Phic, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->HDMF, 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($NetPay, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
            $totalNetPay += $NetPay;
            $count++;
            $rowCount++;
            $totalAmt = 0;
            $NetPay = 0;
        
        $str .= $this->reporter->begintable($layoutsize);//small space in-between
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 15, false, $border, '', 'L', $font, 9, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        }
        //Grand Total
        $rowCount += 12;
        if ($rowCount > 0 && $rowCount > $maxRows) {
            $str .= $this->reporter->page_break();
            $str .= $this->header_md($config, $layoutsize, $batchdate);
            $str .= '</br></br>';
        }
        $str .= '</br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(); 
        $str .= $this->reporter->col('GRAND TOTAL', 200, null, false, $border, 'T', 'L', $font, 9, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalNetPay, 2), 610, null, false, $border, 'T', 'R', $font, 9, 'B', '', '');
        $str .= $this->reporter->col(' ', 85, null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(); 
        $str .= $this->reporter->col('', null, null, false, $border2, 'T', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</br>';
        //Footer
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('I HEREBY CERTIFY that I have paid in cash to each employee whose name appears in the above payroll the amount opposite his name . The amount paid in this pay roll is  <b>'. number_format($totalNetPay, 2). '</b>  including their overtime pay.', null, null, false, $border2, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('APPROVED FOR PAYMENT', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Owner/Manager', 150, null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 20, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Date of Payment', 150, null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 20, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Paymaster', 150, null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', 300, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</br></br>';
        $str .= $this->reporter->endreport();
        return $str;
    }

}