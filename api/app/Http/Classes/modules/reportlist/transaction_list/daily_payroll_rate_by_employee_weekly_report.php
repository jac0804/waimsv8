<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class daily_payroll_rate_by_employee_weekly_report
{
    public $modulename = 'Daily Payroll Rate by Employee Weekly Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1760px;max-width:1760px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1760'];

    public function __construct($params = null)
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass($params);
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['start', 'end', 'agencyname', 'itemcategoryname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.type', 'date');
        data_set($col1, 'end.type', 'date');
        data_set($col1, 'end.label', 'Add Date');
        data_set($col1, 'agencyname.lookupclass', 'lookupagency');
        data_set($col1, 'itemcategoryname.lookupclass', 'lookupcategoryitemcategory');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config) //data parameters; the default values of the input fields
    { // 'names' or 'alias'
        $center = $config['params']['center'];
        $currentDate = $this->othersClass->getCurrentDate();
        return $this->coreFunctions->opentable(
            "select 
            'default' as print,
            '" . $currentDate . "' as start,
            '' as end, 
            '' as agencyname,
            '' as itemcategoryname,
            '' as category,
            '0' as catid"
        );
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Msg works', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config) // Type of Report (radio option) case connection
    {
        return $this->reportDefaultLayout($config);
    }

    public function data_query($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($start . ' +6 days'));
        $adddate = $config['params']['dataparams']['end'];
        $agencyname = $config['params']['dataparams']['agencyname'];
        $itemcategory = $config['params']['dataparams']['itemcategoryname'];
        $catid = $config['params']['dataparams']['catid'];
        $center = $config['params']['center'];
        $filter = "";

        $d = [];
        for ($days = 0; $days < 7; $days++) {
            $d[$days + 1] = date("Y-m-d", strtotime($start . " +$days days"));
        }

        if ($adddate != '') {
            $adddate = date("Y-m-d", strtotime($adddate));
        }

        if ($agencyname != '') {
            $filter .= " and emp.agencyname = '" . $agencyname . "'";
        }

        if ($itemcategory != '') {
            $catids = implode(',', array_map('intval', explode('~', $catid)));
            $filter .= " and icat.line in ($catids)";
        }
        // if ($itemcategory!= ''){
        //     $filter .= " and icat.line = $catid";
        // }

        $datefilter = " date(head.dateid) between '$start' and '$end'";
        if ($adddate != '') {
            $datefilter = " (date(head.dateid) between '$start' and '$end' or date(head.dateid) = '$adddate')";
        }

        $add16 = "0";
        if ($adddate != '') {
            $add16 = "sum(case when dateid = '$adddate' then amt else 0 end)";
        }

        $query = "select empid, empname,
                sum(case when dateid = '{$d[1]}' then amt else 0 end) as d1,
                sum(case when dateid = '{$d[2]}' then amt else 0 end) as d2,
                sum(case when dateid = '{$d[3]}' then amt else 0 end) as d3,
                sum(case when dateid = '{$d[4]}' then amt else 0 end) as d4,
                sum(case when dateid = '{$d[5]}' then amt else 0 end) as d5,
                sum(case when dateid = '{$d[6]}' then amt else 0 end) as d6,
                sum(case when dateid = '{$d[7]}' then amt else 0 end) as d7,
                $add16 as d16, count(distinct case when amt > 0 then dateid end) as days,
                sum(grossrate) as grossrate, sum(totaladj) as totaladj,
                sum(lesscant + lesscacant + lesscahahsy + lesscharge) as totalless,
                sum(netpayroll) as netpayroll
                from (select
                stock.trno,
                stock.line, date(head.dateid) as dateid, cl.client as empcode, cl.clientname as empname, stock.empid,
                it.itemid, it.barcode, it.itemname, it.uom, wh.client as wh, wh.clientid as whid,
                round(ifnull(stock.amount,0),2) as amt,
                round(ifnull(stock.pcswork,0),2) as pcswork,
                round(if(it.payqty > 0 and stock.pcswork > 0, stock.pcswork / it.payqty, 0), 2) as totalbox,
                round(ifnull(stock.amount,0),2) as grossrate,
                round(
                    ifnull(stock.restday,0) + ifnull(stock.night,0) + ifnull(stock.holiday,0)
                    + ifnull(stock.adjustmnt,0) + ifnull(stock.thirteenmos,0) + ifnull(stock.admin,0)
                ,2) as totaladj,
                round(ifnull(stock.canteen,0),2) as lesscant, 
                round(ifnull(stock.cacanteen,0),2) as lesscacant, 
                round(ifnull(stock.cahahsy,0),2) as lesscahahsy, 
                round(ifnull(stock.charge,0),2) as lesscharge,
                round(
                    (ifnull(stock.restday,0) + ifnull(stock.night,0) + ifnull(stock.holiday,0)
                    + ifnull(stock.adjustmnt,0) + ifnull(stock.thirteenmos,0) + ifnull(stock.admin,0))
                    - (ifnull(stock.canteen,0) + ifnull(stock.cacanteen,0)
                    + ifnull(stock.cahahsy,0) + ifnull(stock.charge,0))
                ,2) as netpayroll,
                round(ifnull(it.payrate,0),4) as rate,round(ifnull(it.payqty,0),2) as boxcount,ifnull(icat.name,'') as category,
                it.defcost,
                '' as bgcolor
                from piecetrans as stock
                left join dahead as head on head.trno = stock.trno
                left join hrisnum as num on num.trno = head.trno
                left join client as cl on cl.clientid=stock.empid
                left join employee as emp on emp.empid=cl.clientid
                left join item as it on it.itemid=stock.itemid
                left join client as wh on wh.clientid=stock.whid
                left join itemcategory as icat on icat.line=it.category
                where $datefilter and num.center = '$center' $filter

                union all
                
                select 
                stock.trno,
                stock.line, date(head.dateid), cl.client as empcode, cl.clientname as empname, stock.empid,
                it.itemid, it.barcode, it.itemname, it.uom, wh.client as wh, wh.clientid as whid,
                round(ifnull(stock.amount,0),2) as amt,
                round(ifnull(stock.pcswork,0),2) as pcswork,
                round(if(it.payqty > 0 and stock.pcswork > 0, stock.pcswork / it.payqty, 0), 2) as totalbox,
                round(ifnull(stock.amount,0),2) as grossrate,
                round(
                    ifnull(stock.restday,0) + ifnull(stock.night,0) + ifnull(stock.holiday,0)
                    + ifnull(stock.adjustmnt,0) + ifnull(stock.thirteenmos,0) + ifnull(stock.admin,0)
                ,2) as totaladj,
                round(ifnull(stock.canteen,0),2) as lesscant,
                round(ifnull(stock.cacanteen,0),2) as lesscacant,
                round(ifnull(stock.cahahsy,0),2) as lesscahahsy,
                round(ifnull(stock.charge,0),2) as lesscharge,
                round(
                    (ifnull(stock.restday,0) + ifnull(stock.night,0) + ifnull(stock.holiday,0)
                    + ifnull(stock.adjustmnt,0) + ifnull(stock.thirteenmos,0) + ifnull(stock.admin,0))
                    - (ifnull(stock.canteen,0) + ifnull(stock.cacanteen,0)
                    + ifnull(stock.cahahsy,0) + ifnull(stock.charge,0))
                ,2) as netpayroll,
                round(ifnull(it.payrate,0),4) as rate,round(ifnull(it.payqty,0),2) as boxcount,ifnull(icat.name,'') as category,
                it.defcost,
                '' as bgcolor
                from hpiecetrans as stock
                left join hdahead as head on head.trno = stock.trno
                left join hrisnum as num on num.trno = head.trno
                left join client as cl on cl.clientid=stock.empid
                left join employee as emp on emp.empid=cl.clientid
                left join item as it on it.itemid=stock.itemid
                left join client as wh on wh.clientid=stock.whid
                left join itemcategory as icat on icat.line=it.category
                where $datefilter and num.center = '$center ' $filter) as x
                group by empid, empname
                order by empname";
        // var_dump($query);
        return $this->coreFunctions->openTable($query);
    }

    public function DefaultHeader($config)
    {
        $center     = $config['params']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($start . ' +6 days'));
        $adddate = $config['params']['dataparams']['end'];
        $printDate = date('m/d/Y g:i a');
        $str = ''; // required
        $layoutsize = '1760';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "2px solid ";
        $category = $config['params']['dataparams']['itemcategoryname'];
        $agencyname = $config['params']['dataparams']['agencyname'];

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $category = str_replace('~', ', ', $category);

        if ($adddate != '') {
            $adddate = date("Y-m-d", strtotime($adddate));
        }

        if ($agencyname == '') {
            $agencyname = 'ALL';
        }

        if ($category == '') {
            $category = 'ALL';
        }

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '880', null, false, $border, '', '', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Print Date : ' . $printDate, '880', null, false, $border, '', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY PAYROLL RATE BY EMPLOYEE WEEKLY REPORT', null, null, false, $border, '', 'L', $font, '12', 'B', '#8B0000', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FROM : ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agency : ' . $agencyname, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '1380', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Added Date : ' . $adddate, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category: [' . $category . ']', '1510', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1760', null, false, '3px solid', 'B', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NO.', '40', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Employee', '240', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('16', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('1', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('2', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('3', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('4', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('5', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('6', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('7', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Gross Pay', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('+ADJ', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('-Cant', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Net Pay', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Days', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('No.', '40', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $str = '';
        $layoutsize = '1760';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px dotted ";
        $result = $this->data_query($config);


        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = ''; // required
        // $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '205px;margin-top:5px;');
        $str .= $this->DefaultHeader($config);

        $numCount = 1;
        $total16 = $total1 = $total2 = $total3 = $total4 = $total5 = $total6 = $total7 = 0;
        $totalGross = 0;
        $gtotalAdj = 0;
        $totalLessCant = 0;
        $totalNetPay = 0;
        $totalDays = 0;

        foreach ($result as $key => $data)  // client loop
        {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($numCount, '40', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->empname, '240', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d16 == 0 ? '-' : number_format($data->d16, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d1 == 0 ? '-' : number_format($data->d1, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d2 == 0 ? '-' : number_format($data->d2, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d3 == 0 ? '-' : number_format($data->d3, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d4 == 0 ? '-' : number_format($data->d4, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d5 == 0 ? '-' : number_format($data->d5, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d6 == 0 ? '-' : number_format($data->d6, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d7 == 0 ? '-' : number_format($data->d7, 3), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->grossrate == 0 ? '-' : number_format($data->grossrate, 2), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->totaladj == 0 ? '-' : number_format($data->totaladj, 2), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->totalless == 0 ? '-' : number_format($data->totalless, 2), '110', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->netpayroll == 0 ? '-' : number_format($data->netpayroll, 3), '130', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->days == 0 ? '-' : $data->days, '100', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($numCount, '40', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $total16 += $data->d16;
            $total1 += $data->d1;
            $total2 += $data->d2;
            $total3 += $data->d3;
            $total4 += $data->d4;
            $total5 += $data->d5;
            $total6 += $data->d6;
            $total7 += $data->d7;
            $totalGross += $data->grossrate;
            $gtotalAdj += $data->totaladj;
            $totalLessCant += $data->totalless;
            $totalNetPay += $data->netpayroll;
            $totalDays += $data->days;

            $numCount++;
        }

        // $str .= '<br/><br/>';
        $border = "2px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1760', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('GRAND TOTAL', '240', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total1 == 0 ? '-' : number_format($total1, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total3 == 0 ? '-' : number_format($total3, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total5 == 0 ? '-' : number_format($total5, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total7 == 0 ? '-' : number_format($total7, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($gtotalAdj == 0 ? '-' : number_format($gtotalAdj, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($totalNetPay == 0 ? '-' : number_format($totalNetPay, 3), '130', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '240', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total16 == 0 ? '-' : number_format($total16, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total2 == 0 ? '-' : number_format($total2, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total4 == 0 ? '-' : number_format($total4, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total6 == 0 ? '-' : number_format($total6, 3), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($totalGross == 0 ? '-' : number_format($totalGross, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($totalLessCant == 0 ? '-' : number_format($totalLessCant, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '130', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($totalDays == 0 ? '-' : $totalDays, '100', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1760', null, false, '3px solid', 'B', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }
}
