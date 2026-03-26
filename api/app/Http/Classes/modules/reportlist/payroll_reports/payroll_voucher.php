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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use DateTime;

class payroll_voucher
{
    public $modulename = 'Payroll Voucher';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '700'];

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
        $fields = ['radioprint', 'batchrep'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'batchrep.lookupclass', 'lookupbatchrep');
        data_set($col1, 'batchrep.required', true);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }
    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
    '' as batchid,
    '' as batcrep,
    0 as line,
    'default' as print
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
        return $this->reportDefaultLayout($config);
    }
    public function reportDefault($config)
    {
        $batchid = $config['params']['dataparams']['line'];
        $emplvl = $this->othersClass->checksecuritylevel($config);
        $filter = " ('PT44','PT45','PT46','PT48','PT49','PT51','PT52','PT29','PT57','PT42','PT11','PT12') ";
        $query = "select e.clientname,e.client, year(p.dateid) as year,
                p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
                pa.codename,p.acnoid,pa.alias,p.db,p.cr,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,
                        (case when batch.paymode = 'S' then 'Semi-Monthly' 
                                when batch.paymode = 'W' then 'Weekly' 
                                when batch.paymode = 'M' then 'Monthly' 
                                else '' end ) as paymode
                from paytrancurrent as p
                left join paccount as pa on pa.line = p.acnoid
                left join aaccount as acc on acc.line = pa.aaid
                left join employee as emp on emp.empid=p.empid
                left join client as e on e.clientid = emp.empid
                left join batch on batch.line=p.batchid
                where batch.line = $batchid and pa.code in $filter and emp.level in $emplvl
                union all
                select e.clientname,e.client, year(p.dateid) as year,
                p.dateid,batch.batch,p.batchid,date(batch.startdate) as startdate,date(batch.enddate) as enddate,
                pa.codename,p.acnoid,pa.alias,p.db,p.cr,pa.uom,p.qty,pa.alias,emp.empid,p.qty2,
                        (case when batch.paymode = 'S' then 'Semi-Monthly' 
                                when batch.paymode = 'W' then 'Weekly' 
                                when batch.paymode = 'M' then 'Monthly' 
                                else '' end ) as paymode
                from paytranhistory as p
                left join paccount as pa on pa.line = p.acnoid
                left join aaccount as acc on acc.line = pa.aaid
                left join employee as emp on emp.empid=p.empid
                left join client as e on e.clientid = emp.empid
                left join batch on batch.line=p.batchid
                where batch.line = $batchid and pa.code in $filter and emp.level in $emplvl";
        return $this->coreFunctions->opentable($query);
    }
    public function getaccount($config)
    {
        $filter = " ('PT44','PT45','PT46','PT48','PT49','PT51','PT52','PT29','PT57','PT42','PT11','PT12') ";
        $batchid = $config['params']['dataparams']['line'];
        $query = "select sum(pt.db) as db,sum(pt.cr) as cr,acc.codename as account from paytrancurrent as pt 
        left join paccount as pa on pa.line = pt.acnoid
        left join aaccount as acc on acc.line = pa.aaid
        left join batch on batch.line=pt.batchid
        where batch.line = $batchid and pa.code in $filter group by acc.codename 
        union all
        select sum(pt.db) as db,sum(pt.cr) as cr,acc.codename as account from paytranhistory as pt 
        left join paccount as pa on pa.line = pt.acnoid
        left join aaccount as acc on acc.line = pa.aaid
        left join batch on batch.line=pt.batchid
        where batch.line = $batchid and pa.code in $filter group by acc.codename";
        return $this->coreFunctions->opentable($query);
    }
    private function displayHeader($config, $data)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $batch  = $config['params']['dataparams']['batch'];

        $str = '';
        $layoutsize = '1200';
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAYROLL VOUCHER', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($data[0]->paymode), null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $startdate = new DateTime($data[0]->startdate);
        $enddate = new DateTime($data[0]->enddate);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '355', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('For the Period of ' . $startdate->format('j-F-y'), '150', null, false, $border, 'C', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('to ' . $enddate->format('j-F-y'), '80', null, false, $border, 'C', '', $font, $font_size, 'B', 'L', '');
        $str .= $this->reporter->col('Batch No. ' . $batch, '160', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '355', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('EARNINGS', '130', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, '', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('DEDUCTIONS', '870', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '495', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('SSS', '140', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('PHIC', '70', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('HDMF', '70', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '285', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Employee Name", '150', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("Gross Pay", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("Adj/Other", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("Advance", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("Other Deduction", '70', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("EE", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("ER", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("EC", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("EE", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("ER", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("EE", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("ER", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("TAX", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("PAGIBIG LOAN", '70', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("SSS LOAN", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("NET PAY", '70', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    private function getcount($empid, $batch)
    {
        $filter = " ('PT44','PT45','PT46','PT48','PT49','PT51','PT52','PT29','BSA','PT57','PT42','PT11','PT12') ";
        return $this->coreFunctions->datareader("select count(batchid) as value from (select p.batchid from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid where batchid = " . $batch . " and pa.code in $filter  and p.empid=?
    union all
    select p.batchid from paytranhistory as p left join paccount as pa on pa.line=p.acnoid where batchid = " . $batch . " and pa.code in $filter and p.empid=?) as x", [$empid, $empid]);
    }
    public function reportDefaultLayout($config)
    {

        $result =  $this->reportDefault($config);
        $border = '1px dotted';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $count = 55;
        $page = 55;
        $str = '';
        $layoutsize = '1200';
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, $result);

        $netpay = 0;
        $basicpay = 0;
        $sssee = 0;
        $ssser = 0;
        $sssloan = 0;
        $sssec = 0;
        $philee = 0;
        $philer = 0;
        $hdmfee = 0;
        $hdmfer = 0;
        $hdmfloan = 0;
        $wht = 0;
        $deduction = 0;
        $otherdeduction = 0;
        $adjustment = 0;


        $totalearn = 0;
        $totalded = 0;

        $totalbasicpay = 0;
        $totalsssee = 0;
        $totalssser = 0;
        $totalsssloan = 0;
        $totalsssec = 0;
        $totalphilee = 0;
        $totalphiler = 0;
        $totalhdmfee = 0;
        $totalhdmfer = 0;
        $totalhdmfloan = 0;
        $totalwht = 0;
        $totaldeduction = 0;
        $totalotherdeduction = 0;
        $totaladjustment = 0;
        $totalnetpay = 0;

        $i = 0;
        $c = 0;
        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            if ($data->alias == 'BSA') { // basic salary
                $basicpay = $basicpay + $data->db - $data->cr;
                $totalearn = $totalearn + $data->db - $data->cr;
            } else if ($data->alias == 'YSE') { // sssemploye
                $sssee = $sssee + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'YSR') { // sssemploye
                $ssser = $ssser + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'SSSLOAN') { //sssloan
                $sssloan = $sssloan + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'YER') { // ecemployer
                $sssec = $sssec + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'YME') { // philemploye
                $philee = $philee + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'YMR') { // philemployer
                $philer = $philer + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'YPE') { // hdmfemploye
                $hdmfee = $hdmfee + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'YPR') { // hdmfemployer
                $hdmfer = $hdmfer + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'HDMFLOAN') { // hdmfloan
                $hdmfloan = $hdmfloan + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'YWT') { // with hdtax
                $wht = $wht + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'DEDUCTION') { //advance
                $deduction = $deduction + $data->cr - $data->db;
                $totalded = $totalded + $data->cr - $data->db;
            } else if ($data->alias == 'DEDUCTION' && $data->code == 'PT35') { // other deduction
                $otherdeduction  = $otherdeduction + $data->cr;
                $totalded = $totalded + $data->cr;
            } else if ($data->alias == 'ADJUSTMENT') { //adjustment
                $adjustment = $adjustment + $data->db - $data->cr;
                $totalearn = $totalearn + $data->db - $data->cr;
            }

            $netpay = $totalearn - $totalded;
            if ($c == 0) {
                $c = $this->getcount($data->empid, $config['params']['dataparams']['line']);
            }
            $i = $i + 1;
            if ($i == $c) {
                $this->coreFunctions->LogConsole($hdmfee . 'ee--i' . $i . '---' . $c . 'c--er' . $hdmfer);
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("" . $data->clientname, '150', null, false, '', 'B', 'L', $font, $font_size, '', '20px', '', '');
                $str .= $this->reporter->col("" . number_format($basicpay, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($adjustment, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($deduction, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($otherdeduction, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($sssee, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($ssser, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($sssec, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($philee, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($philer, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($hdmfee, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($hdmfer, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($wht, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($hdmfloan, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($sssloan, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("" . number_format($netpay, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("", null, null, false, $border, 'B', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $c = 0;
                $i = 0;

                $totalbasicpay += $basicpay;
                $totaladjustment += $adjustment;
                $totaldeduction += $deduction; //advance
                $totalotherdeduction += $otherdeduction;
                $totalsssee += $sssee;
                $totalssser += $ssser;
                $totalsssec += $sssec;
                $totalphilee += $philee;
                $totalphiler += $philer;
                $totalhdmfee += $hdmfee;
                $totalhdmfer += $hdmfer;
                $totalhdmfloan += $hdmfloan;
                $totalsssloan += $sssloan;
                $totalwht += $wht;
                $totalnetpay += $netpay;

                $netpay = 0;
                $basicpay = 0;
                $sssee = 0;
                $ssser = 0;
                $sssloan = 0;
                $sssec = 0;
                $philee = 0;
                $philer = 0;
                $hdmfee = 0;
                $hdmfer = 0;
                $hdmfloan = 0;
                $wht = 0;
                $deduction = 0;
                $adjustment = 0;
                $totalearn = 0;
                $totalded = 0;
                $otherdeduction = 0;
            }

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $result);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $result);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("GRAND TOTAL", '150', null, false, '', 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("" . number_format($totalbasicpay, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totaladjustment, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totaldeduction, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalotherdeduction, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalsssee, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalssser, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalsssec, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalphilee, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalphiler, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalhdmfee, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalhdmfer, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalhdmfloan, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalsssloan, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalwht, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalnetpay, 2), '70', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= "</br></br></br>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("", '400', null, false, '', 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("ACCOUNTING ENTRY", '134', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("", '133', null, false, '1px solid', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '133', null, false, '1px solid', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '400', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("", '400', null, false, '', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("Accounts", '134', null, false, '1px solid', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("DEBIT", '133', null, false, '1px solid', '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("CREDIT", '133', null, false, '1px solid', '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("", '400', null, false, '', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $account = $this->getaccount($config);
        $totaldb = 0;
        $totalcr = 0;
        $str .= $this->reporter->begintable($layoutsize);
        foreach ($account as $key => $acc) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("", '400', null, false, '', 'B', 'L', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col("" . $acc->account, '134', null, false, '', 'T', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("" . $acc->db == 0 ? '' : number_format($acc->db, 2), '133', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("" . $acc->cr == 0 ? '' : number_format($acc->cr, 2), '133', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '400', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $totaldb += $acc->db;
            $totalcr += $acc->cr;
        }
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("", '400', null, false, '', 'B', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col("", '134', null, false, '', 'B', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totaldb, 2), '133', null, false, '1px solid', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("" . number_format($totalcr, 2), '133', null, false, '1px solid', 'T', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '400', null, false, '', 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->endreport();

        return $str;
    }
}
