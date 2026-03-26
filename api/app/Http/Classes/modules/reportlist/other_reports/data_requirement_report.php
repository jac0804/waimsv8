<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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
use DateTime;

class data_requirement_report
{
    public $modulename = 'Data Requirement Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1800px;max-width:1800px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1800'];

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
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
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
        left(now(),10) as end");
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
        return $this->reportDefaultLayout($config);
    }

    public function reportDefaultqry($config)
    {

        $start   = $config['params']['dataparams']['start'];
        $end   = $config['params']['dataparams']['end'];

        $query = " select cp.docno as policyno,cp.clientname as planholdername,pt.name as plan,date(cp.dateid) as issuedate,
                '' as issueage, date(info.bday) as bday,

                    case when head.terms='Upfront 3%' then 'Spot cash' else '2 years' end as payingperiod,
                    case when head.terms='Upfront 3%' then 'N/A' else head.terms end as paymentterms,
                    info.dp as gross,
                    ifnull((case info.issenior when 1 then pt.amount/1.12 else pt.amount end),0) as planvalue,
                    head.yourref as modeofpayment,       
                                                   (select detail.db from gldetail as detail
                                                    left join coa on coa.acnoid=detail.acnoid
                                                    where coa.alias ='AR1' and detail.trno=cp.trno limit 1) as modalinstallment,

                            (select postd from (
                            select head.docno,head.yourref,date(detail.postdate) as postd from lahead as head
                            left join ladetail as detail on detail.trno=head.trno
                            left join coa on coa.acnoid=detail.acnoid
                            where head.doc='cr'
                            and left (coa.alias,2) not in ('AR')
                            group by head.docno,detail.postdate,head.yourref
                            union all
                            select head.docno,head.yourref,date(detail.postdate) as postd from glhead as head
                            left join gldetail as detail on detail.trno=head.trno
                            left join coa on coa.acnoid=detail.acnoid
                            where head.doc='cr'
                            and left (coa.alias,2) not in ('AR')
                            group by head.docno,detail.postdate,head.yourref
                            order by postd desc ) as a where a.yourref=cp.docno limit 1) as dateoflastpayment,


                    (select count(docno) as doc
                    from (
                    select head.yourref,head.docno from lahead as head where head.doc='cr'
                    union all
                    select head.yourref, head.docno  from glhead as head  where head.doc='cr' ) as a where a.yourref=cp.docno) as numberofmodalpayment,


                            (select sum(payment) as wholepayment from (
                            select sum(detail.db) as payment,head.yourref from lahead as head
                            left join ladetail as detail on detail.trno=head.trno
                            left join coa on coa.acnoid=detail.acnoid
                            where head.doc='cr' 
                            and left (coa.alias,2) not in ('AR')
                            group by head.yourref
                            union all
                            select sum(detail.db) as payment,head.yourref from glhead as head
                            left join gldetail as detail on detail.trno=head.trno
                            left join coa on coa.acnoid=detail.acnoid
                            where head.doc='cr' 
                            and left (coa.alias,2) not in ('AR')
                            group by head.yourref  ) as a where a.yourref=cp.docno ) as wholepayment,
                            ifnull(format(info.pf,2),0) as processingfee,head.terms,
                            year(cp.dateid) as yearhere,
                             MONTH(cp.dateid) AS month


                        from glhead as cp
                        left join heahead as head on cp.aftrno = head.trno
                        left join plantype as pt on pt.line = head.planid
                        left join heainfo as info on head.trno = info.trno
                        where cp.doc='CP'  and date(cp.dateid) between '$start' and '$end'
                        GROUP BY
                                cp.docno,
                                cp.clientname,
                                pt.name,
                                DATE(cp.dateid),
                                DATE(info.bday),
                                head.terms,
                                info.dp,
                                head.yourref,
                                info.pf,
                                YEAR(cp.dateid),
                                MONTH(cp.dateid),pt.amount,info.issenior,cp.trno,head.terms  order by YEAR(cp.dateid), MONTH(cp.dateid) ";

        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $border = '1px solid';
        $border_line = '';
        $alignment = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '9';
        $padding = '';
        $margin = '';

        $start   = $config['params']['dataparams']['start'];
        $end   = $config['params']['dataparams']['end'];

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $str = '';
        $layoutsize = '1800';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATA REQUIREMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('Start Date: ' . strtoupper($start), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('End Date: ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '40', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Policy Number', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Plan Holder Name', '160', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Plan / Plan Code', '140', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Effective or Issue Date ', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Issue Age ', '80', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Birthdate', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Paying Period', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Pay Terms', '90', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Gross Contract Price', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Plan Value', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Mode of Payment', '90', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Modal installment Amount', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Paid to Date or Date of Last Payment', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Number of Modal Payments Made', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Total Payments Made Excluding Handling Charges', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Trust Fund Contribution', '100', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '9';
        $count = 55;
        $page = 55;
        $layoutsize = '1800';


        $str = '';
        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $yr = '';
        $grossmonthTotals = 0;
        $grossyearTotals = 0;
        $monthplanvaluetotal = 0;
        $yearplanvaluetotal = 0;

        $monthtotalpayment = 0;
        $yeartotalpayments = 0;

        $modalinstallmentmonth = 0;
        $modalinstallmentyear = 0;


        $lastYear = null;
        foreach ($result as $key => $data) {
            $yr = $data['issuedate'];
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($yr, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $result2 = $this->reportDefaultqry($config);
            $str .= $this->reporter->begintable($layoutsize);

            if (!empty($result2)) {
                $lastMonth = null;
                foreach ($result2 as $key => $data2) {
                    if ($data2->yearhere == $yr) {
                        $policyhere = $data2->policyno;
                        $planholder = $data2->planholdername;
                        $plann = $data2->plan;
                        $issueddate = $data2->issuedate;
                        $date1 = new DateTime($data2->issuedate); // First date
                        $date2 = new DateTime($data2->bday); // Second date
                        $interval = $date1->diff($date2);
                        $issueage = $interval->y;
                        $birthdate = $data2->bday;
                        $payingperiod = $data2->payingperiod;
                        $payterms = $data2->paymentterms;
                        $grosscontract = $data2->gross;
                        $planvalue = $data2->planvalue;
                        $modeofpayment = $data2->modeofpayment;

                        $terms = $data2->terms;
                        if ($terms == 'Upfront 3%') {
                            $modalinstallamt = 'N/A';
                            $paiddate = $issueddate;
                        } else {
                            $modalinstallamt = $data2->modalinstallment;
                            $paiddate = $data2->dateoflastpayment;
                        }
                        $nomodalpayment = $data2->numberofmodalpayment;
                        $processingfee = $data2->processingfee;
                        $totalpayment = $data2->wholepayment;
                        if ($processingfee != 0) {
                            $tlpayment = $totalpayment - $processingfee;
                        } else {
                            $tlpayment = $data2->wholepayment;
                        }

                        $monthName = $date1->format('M');

                        if ($monthName != $lastMonth) {
                            if ($lastMonth !== null) {

                                $str .= $this->reporter->endtable();
                                $str .= $this->reporter->begintable($layoutsize);
                                $str .= $this->reporter->startrow();
                                $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('', '160', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('Total For ' . $lastMonth, '140', null, false, $border, '', 'R', $font, $font_size, 'B', '', ''); //kapantay ng planholder
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col(number_format($grossmonthTotals, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col(number_format($monthplanvaluetotal, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col(number_format($modalinstallmentmonth, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col(number_format($monthtotalpayment, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->endrow();
                                $str .= $this->reporter->endtable();
                            }

                            // Display the month
                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col($monthName, '40', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                            $lastMonth = $monthName;
                            $grossmonthTotals = 0;
                            $monthplanvaluetotal = 0;
                            $monthtotalpayment = 0;
                            $modalinstallmentmonth = 0;
                        } else {
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $font_size, '', '', ''); // Empty na column for month
                        }

                        $grossmonthTotals += $grosscontract;
                        $grossyearTotals += $grosscontract;

                        $monthplanvaluetotal += $planvalue;
                        $yearplanvaluetotal += $planvalue;

                        $monthtotalpayment += $tlpayment;
                        $yeartotalpayments += $tlpayment;

                        $modalinstallmentmonth += $modalinstallamt;

                        $modalinstallmentyear += $modalinstallamt;

                        $str .= $this->reporter->col($policyhere, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($planholder, '160', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($plann, '140', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($issueddate, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($issueage, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($birthdate, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($payingperiod, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($payterms, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col(number_format($grosscontract, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col(number_format($planvalue, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($modeofpayment, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col(($modalinstallamt == 'N/A' ? 'N/A' : number_format($modalinstallamt, 2)), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($paiddate, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col($nomodalpayment, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col(number_format($tlpayment, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                        $str .= $this->reporter->endrow();
                    }
                } //end foreach
            }

            if ($lastMonth !== null) {
                //   Total gross per month 
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '160', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Total For ' . $lastMonth, '140', null, false, $border, '', 'R', $font, $font_size, 'B', '', ''); //kapantay ng planholder
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($grossmonthTotals, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col(number_format($monthplanvaluetotal, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($modalinstallmentmonth, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($monthtotalpayment, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $lastYear = $yr;
            if ($lastYear !== null) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '160', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Total For ' . $yr, '140', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($grossyearTotals, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', ''); // Total gross per year
                $str .= $this->reporter->col(number_format($yearplanvaluetotal, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', ''); // Total planvalue per year
                $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($modalinstallmentyear, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', ''); // Total payments per month
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col(number_format($yeartotalpayments, 2), '100', null, false, $border, '', 'R', $font, $font_size, 'B', '', ''); // Total payment per year
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
                $page = $page + $count;
            }
        } //end ng foreach


        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endreport();

        return $str;
    }

    //main
    public function reportDefault($config)
    {
        $start   = $config['params']['dataparams']['start'];
        $end   = $config['params']['dataparams']['end'];
        $query = "select year(cp.dateid) as issuedate
                        from glhead as cp
                        where cp.doc='CP'  and date(cp.dateid) between '$start' and '$end'
                        group by issuedate";
        return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    }
}//end class