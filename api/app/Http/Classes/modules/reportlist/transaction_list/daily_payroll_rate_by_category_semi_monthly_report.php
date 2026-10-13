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
use App\Http\Classes\sbcscript\sbcscript;

class daily_payroll_rate_by_category_semi_monthly_report
{
    public $modulename = 'Daily Payroll Rate by Category Semi Monthly Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    private $sbcscript;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1200'];

    public function __construct($params = null)
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass($params);
        $this->reporter = new SBCPDF;
        $this->sbcscript = new sbcscript;
    }

    public function createHeadField($config)
    {

        $fields = ['radioprint', 'start', 'end', 'dwhname', 'itemcategoryname', 'agencyname', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', false);
        data_set($col1, 'itemcategoryname.action', 'lookupcategory');
        data_set($col1, 'itemcategoryname.lookupclass', 'lookupcategoryitemcategory');
        data_set($col1, 'itemcategoryname.class', 'cscategory sbccsreadonly');
        data_set($col1, 'radioreporttype.label', 'Option');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Day 1-15 Category', 'value' => '1', 'color' => 'red'],
            ['label' => 'Day 16-31 Category', 'value' => '2', 'color' => 'green']
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $qry = "select 'default' as print,
        left(now(),10) as start,
        adddate(left(now(),10),+30) as end,
        '1' as reporttype,'' as itemcategoryname, '' as agencyname,
        '0' as whid,'' as wh,'' as whname, '' as dwhname,
         0 as catid";
        return $this->coreFunctions->opentable($qry);
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
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case '1': //Day 1-15 Category
                $result = $this->reportDefaultLayout_DPRS1($config);
                break;
            case '2': //'Day 16-31 Category'
                $result = $this->reportDefaultLayout_DPRS2($config);
                break;
        }

        return $result;
    }


    public function report_default_query($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $reporttype = $config['params']['dataparams']['reporttype'];
        $center   = $config['params']['center'];
        $catid    = $config['params']['dataparams']['catid'];
        $category = $config['params']['dataparams']['itemcategoryname'];
        $agencyname = $config['params']['dataparams']['agencyname'];
        $wh         = $config['params']['dataparams']['wh'];
        $whid        = $config['params']['dataparams']['whid'];

        $filter = "";
        if ($category != "") {
            // "4~2~3~1" magiging 4','2','3','1
            $catlist = str_replace("~", "','", $catid);

            // and it.category in ('4','2','3','1')
            $filter .= " and it.category in ('$catlist')";
        }
        if ($agencyname != "") {
            $filter .= " and em.agencyname = '$agencyname'";
        }

        if ($wh != "") {
            $filter .= " and wh.clientid = '$whid' ";
        }

        switch ($reporttype) {
            case '1': //Day 1-15 Category

                $qry = "
            select  catname, employee,
                    round(sum(if(day(rdate) = 1,  amount, 0)), 2) as day1,
                    round(sum(if(day(rdate) = 2,  amount, 0)), 2) as day2,
                    round(sum(if(day(rdate) = 3,  amount, 0)), 2) as day3,
                    round(sum(if(day(rdate) = 4,  amount, 0)), 2) as day4,
                    round(sum(if(day(rdate) = 5,  amount, 0)), 2) as day5,
                    round(sum(if(day(rdate) = 6,  amount, 0)), 2) as day6,
                    round(sum(if(day(rdate) = 7,  amount, 0)), 2) as day7,
                    round(sum(if(day(rdate) = 8,  amount, 0)), 2) as day8,
                    round(sum(if(day(rdate) = 9,  amount, 0)), 2) as day9,
                    round(sum(if(day(rdate) = 10, amount, 0)), 2) as day10,
                    round(sum(if(day(rdate) = 11, amount, 0)), 2) as day11,
                    round(sum(if(day(rdate) = 12, amount, 0)), 2) as day12,
                    round(sum(if(day(rdate) = 13, amount, 0)), 2) as day13,
                    round(sum(if(day(rdate) = 14, amount, 0)), 2) as day14,
                    round(sum(if(day(rdate) = 15, amount, 0)), 2) as day15,
                    round(sum(amount), 2) as grosspay
                    from (
              select  sum(stock.amount) as amount, cl.clientname as employee, date(head.dateid) as rdate, ifnull(cat.name,'') as catname
              from dahead as head
              inner join piecetrans as stock on stock.trno = head.trno
              left join hrisnum as num on num.trno = head.trno
              left join client as cl on cl.clientid = stock.empid
              left join employee as em on em.empid = cl.clientid
              left join item as it on it.itemid = stock.itemid
              left join itemcategory as cat on cat.line=it.category
              left join client as wh on wh.clientid = head.whid
              where date(head.dateid) between '$start' and '$end' and day(head.dateid) between 1 and 15  and num.center = '$center' $filter
              group by  cl.clientname, head.dateid,cat.name
              union all
              select sum(stock.amount) as amount, cl.clientname as employee, date(head.dateid) as rdate, ifnull(cat.name,'') as catname
              from hdahead as head
              inner join hpiecetrans as stock on stock.trno = head.trno
              left join hrisnum as num on num.trno = head.trno
              left join client as cl on cl.clientid = stock.empid
              left join item as it on it.itemid = stock.itemid
              left join employee as em on em.empid = cl.clientid
              left join itemcategory as cat on cat.line=it.category
              left join client as wh on wh.clientid = head.whid
              where date(head.dateid) between '$start' and '$end' and day(head.dateid) between 1 and 15   and num.center = '$center' $filter
              group by  cl.clientname, head.dateid,cat.name ) as rates
              group by catname, employee order by catname, employee";
                break;
            case '2': //'Day 16-31 Category'

                $qry = "
            select  catname, employee,
                    round(sum(if(day(rdate) = 16, amount, 0)), 2) as day16,
                    round(sum(if(day(rdate) = 17, amount, 0)), 2) as day17,
                    round(sum(if(day(rdate) = 18, amount, 0)), 2) as day18,
                    round(sum(if(day(rdate) = 19, amount, 0)), 2) as day19,
                    round(sum(if(day(rdate) = 20, amount, 0)), 2) as day20,
                    round(sum(if(day(rdate) = 21, amount, 0)), 2) as day21,
                    round(sum(if(day(rdate) = 22, amount, 0)), 2) as day22,
                    round(sum(if(day(rdate) = 23, amount, 0)), 2) as day23,
                    round(sum(if(day(rdate) = 24, amount, 0)), 2) as day24,
                    round(sum(if(day(rdate) = 25, amount, 0)), 2) as day25,
                    round(sum(if(day(rdate) = 26, amount, 0)), 2) as day26,
                    round(sum(if(day(rdate) = 27, amount, 0)), 2) as day27,
                    round(sum(if(day(rdate) = 28, amount, 0)), 2) as day28,
                    round(sum(if(day(rdate) = 29, amount, 0)), 2) as day29,
                    round(sum(if(day(rdate) = 30, amount, 0)), 2) as day30,
                    round(sum(if(day(rdate) = 31, amount, 0)), 2) as day31,
                    round(sum(amount), 2) as grosspay
                    from (
              select  sum(stock.amount) as amount, cl.clientname as employee, date(head.dateid) as rdate, ifnull(cat.name,'') as catname
              from dahead as head
              inner join piecetrans as stock on stock.trno = head.trno
              left join hrisnum as num on num.trno = head.trno
              left join client as cl on cl.clientid = stock.empid
              left join item as it on it.itemid = stock.itemid
              left join employee as em on em.empid = cl.clientid
              left join itemcategory as cat on cat.line=it.category
              left join client as wh on wh.clientid = head.whid
              where date(head.dateid) between '$start' and '$end' and day(head.dateid) between 16 and 31   and num.center = '$center' $filter
              group by  cl.clientname, head.dateid,cat.name
              union all
              select sum(stock.amount) as amount, cl.clientname as employee, date(head.dateid) as rdate, ifnull(cat.name,'') as catname
              from hdahead as head
              inner join hpiecetrans as stock on stock.trno = head.trno
              left join hrisnum as num on num.trno = head.trno
              left join client as cl on cl.clientid = stock.empid
              left join item as it on it.itemid = stock.itemid
              left join employee as em on em.empid = cl.clientid
              left join itemcategory as cat on cat.line=it.category
              left join client as wh on wh.clientid = head.whid
              where date(head.dateid) between '$start' and '$end' and day(head.dateid) between 16 and 31  and num.center = '$center' $filter
              group by  cl.clientname, head.dateid,cat.name ) as rates
              group by catname, employee order by catname, employee";
                break;
        }

        return $this->coreFunctions->opentable($qry);
    }

    public function tableheader_dprs1($layoutsize, $config)
    {
        $companyid = $config['params']['companyid'];

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('No.', '30', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee', '170', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('01', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('02', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('03', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('04', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('05', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('06', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('07', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('08', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('09', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('10', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('11', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('12', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('13', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('14', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('15', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Gross Pay', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    public function reportDefaultLayout_DPRS1($config)
    {

        $data = $this->report_default_query($config);

        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $this->reporter->linecounter = 0;
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:30px;margin-left:125px');
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader_dprs1($layoutsize, $config);

        $page = 30;    // ilang employee bawat page
        $count = 30;

        $i = 0;
        $catname = '';

        // sub-total ng bawat category
        $sub1 = 0;
        $sub2 = 0;
        $sub3 = 0;
        $sub4 = 0;
        $sub5 = 0;
        $sub6 = 0;
        $sub7 = 0;
        $sub8 = 0;
        $sub9 = 0;
        $sub10 = 0;
        $sub11 = 0;
        $sub12 = 0;
        $sub13 = 0;
        $sub14 = 0;
        $sub15 = 0;
        $subgross = 0;

        // grand total
        $grand1 = 0;
        $grand2 = 0;
        $grand3 = 0;
        $grand4 = 0;
        $grand5 = 0;
        $grand6 = 0;
        $grand7 = 0;
        $grand8 = 0;
        $grand9 = 0;
        $grand10 = 0;
        $grand11 = 0;
        $grand12 = 0;
        $grand13 = 0;
        $grand14 = 0;
        $grand15 = 0;
        $grandgross = 0;

        foreach ($data as $key => $value) {

            // kapag puno na ang page, lipat sa bagong page at ulitin ang header
            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->header_DEFAULT($config);
                $str .= $this->tableheader_dprs1($layoutsize, $config);
                $page = $page + $count;
            }

            // kapag nagpalit ng category, i-print muna ang sub-total ng naunang category
            if ($i > 0 && $catname != $data[$key]->catname) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(strtoupper($catname) . ' Sub-Total :', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub1 <= 0 ? '-' : number_format($sub1, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub2 <= 0 ? '-' : number_format($sub2, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub3 <= 0 ? '-' : number_format($sub3, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub4 <= 0 ? '-' : number_format($sub4, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub5 <= 0 ? '-' : number_format($sub5, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub6 <= 0 ? '-' : number_format($sub6, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub7 <= 0 ? '-' : number_format($sub7, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub8 <= 0 ? '-' : number_format($sub8, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub9 <= 0 ? '-' : number_format($sub9, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub10 <= 0 ? '-' : number_format($sub10, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub11 <= 0 ? '-' : number_format($sub11, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub12 <= 0 ? '-' : number_format($sub12, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub13 <= 0 ? '-' : number_format($sub13, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub14 <= 0 ? '-' : number_format($sub14, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub15 <= 0 ? '-' : number_format($sub15, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($subgross, 2, '.', ','), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // ibalik sa 0 ang sub-total para sa bagong category
                $sub1 = 0;
                $sub2 = 0;
                $sub3 = 0;
                $sub4 = 0;
                $sub5 = 0;
                $sub6 = 0;
                $sub7 = 0;
                $sub8 = 0;
                $sub9 = 0;
                $sub10 = 0;
                $sub11 = 0;
                $sub12 = 0;
                $sub13 = 0;
                $sub14 = 0;
                $sub15 = 0;
                $subgross = 0;
            }

            $catname = $data[$key]->catname;
            $i++;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($i, '30', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$key]->employee, '170', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day1 <= 0 ? '-' : number_format($data[$key]->day1, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day2 <= 0 ? '-' : number_format($data[$key]->day2, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day3 <= 0 ? '-' : number_format($data[$key]->day3, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day4 <= 0 ? '-' : number_format($data[$key]->day4, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day5 <= 0 ? '-' : number_format($data[$key]->day5, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day6 <= 0 ? '-' : number_format($data[$key]->day6, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day7 <= 0 ? '-' : number_format($data[$key]->day7, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day8 <= 0 ? '-' : number_format($data[$key]->day8, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day9 <= 0 ? '-' : number_format($data[$key]->day9, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day10 <= 0 ? '-' : number_format($data[$key]->day10, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day11 <= 0 ? '-' : number_format($data[$key]->day11, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day12 <= 0 ? '-' : number_format($data[$key]->day12, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day13 <= 0 ? '-' : number_format($data[$key]->day13, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day14 <= 0 ? '-' : number_format($data[$key]->day14, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day15 <= 0 ? '-' : number_format($data[$key]->day15, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$key]->grosspay, 2, '.', ','), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // idagdag sa sub-total
            $sub1 = $sub1 + $data[$key]->day1;
            $sub2 = $sub2 + $data[$key]->day2;
            $sub3 = $sub3 + $data[$key]->day3;
            $sub4 = $sub4 + $data[$key]->day4;
            $sub5 = $sub5 + $data[$key]->day5;
            $sub6 = $sub6 + $data[$key]->day6;
            $sub7 = $sub7 + $data[$key]->day7;
            $sub8 = $sub8 + $data[$key]->day8;
            $sub9 = $sub9 + $data[$key]->day9;
            $sub10 = $sub10 + $data[$key]->day10;
            $sub11 = $sub11 + $data[$key]->day11;
            $sub12 = $sub12 + $data[$key]->day12;
            $sub13 = $sub13 + $data[$key]->day13;
            $sub14 = $sub14 + $data[$key]->day14;
            $sub15 = $sub15 + $data[$key]->day15;
            $subgross = $subgross + $data[$key]->grosspay;

            // idagdag sa grand total
            $grand1 = $grand1 + $data[$key]->day1;
            $grand2 = $grand2 + $data[$key]->day2;
            $grand3 = $grand3 + $data[$key]->day3;
            $grand4 = $grand4 + $data[$key]->day4;
            $grand5 = $grand5 + $data[$key]->day5;
            $grand6 = $grand6 + $data[$key]->day6;
            $grand7 = $grand7 + $data[$key]->day7;
            $grand8 = $grand8 + $data[$key]->day8;
            $grand9 = $grand9 + $data[$key]->day9;
            $grand10 = $grand10 + $data[$key]->day10;
            $grand11 = $grand11 + $data[$key]->day11;
            $grand12 = $grand12 + $data[$key]->day12;
            $grand13 = $grand13 + $data[$key]->day13;
            $grand14 = $grand14 + $data[$key]->day14;
            $grand15 = $grand15 + $data[$key]->day15;
            $grandgross = $grandgross + $data[$key]->grosspay;
        }

        // sub-total ng huling category
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($catname) . ' Sub-Total :', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub1 <= 0 ? '-' : number_format($sub1, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub2 <= 0 ? '-' : number_format($sub2, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub3 <= 0 ? '-' : number_format($sub3, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub4 <= 0 ? '-' : number_format($sub4, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub5 <= 0 ? '-' : number_format($sub5, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub6 <= 0 ? '-' : number_format($sub6, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub7 <= 0 ? '-' : number_format($sub7, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub8 <= 0 ? '-' : number_format($sub8, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub9 <= 0 ? '-' : number_format($sub9, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub10 <= 0 ? '-' : number_format($sub10, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub11 <= 0 ? '-' : number_format($sub11, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub12 <= 0 ? '-' : number_format($sub12, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub13 <= 0 ? '-' : number_format($sub13, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub14 <= 0 ? '-' : number_format($sub14, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub15 <= 0 ? '-' : number_format($sub15, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subgross, 2, '.', ','), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // grand total
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total :', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand1 <= 0 ? '-' : number_format($grand1, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand2 <= 0 ? '-' : number_format($grand2, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand3 <= 0 ? '-' : number_format($grand3, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand4 <= 0 ? '-' : number_format($grand4, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand5 <= 0 ? '-' : number_format($grand5, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand6 <= 0 ? '-' : number_format($grand6, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand7 <= 0 ? '-' : number_format($grand7, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand8 <= 0 ? '-' : number_format($grand8, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand9 <= 0 ? '-' : number_format($grand9, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand10 <= 0 ? '-' : number_format($grand10, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand11 <= 0 ? '-' : number_format($grand11, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand12 <= 0 ? '-' : number_format($grand12, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand13 <= 0 ? '-' : number_format($grand13, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand14 <= 0 ? '-' : number_format($grand14, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand15 <= 0 ? '-' : number_format($grand15, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandgross, 2, '.', ','), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        $str .= '</div>';

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $companyid  = $config['params']['companyid'];
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $category = $config['params']['dataparams']['itemcategoryname'];
        $agencyname = $config['params']['dataparams']['agencyname'];
        $warehouse = $config['params']['dataparams']['wh'];
        $warehousename = $config['params']['dataparams']['whname'];


        if ($category == "") {
            $category = "ALL";
        } else {

            $catlist = str_replace("~", " , ", $category);
            $category = "$catlist";
        }


        if ($agencyname == "") {
            $agencyname = "ALL";
        }


        if ($warehouse == "") {
            $warehousename = "ALL";
        }




        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";


        $company = $this->coreFunctions->datareader("select name as value from center where code = '$center'");
        // $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($company), null, null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Daily Payroll Rate by Category Semi Monthly Report', null, null, false, $border, '', 'L', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Trans. Date : <u>' . $start . '</u>' . ' To ' . ' <u>' . $end . '</u>', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agency : <u>' . $agencyname . '</u>', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse : <u>' . $warehousename . '</u>', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Category : <u>" . $category . "</u>", null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        return $str;
    }



    public function tableheader_dprs2($layoutsize, $config)
    {
        $companyid = $config['params']['companyid'];

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('No.', '30', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Employee', '130', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('16', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('17', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('18', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('19', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('20', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('21', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('22', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('23', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('24', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('25', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('26', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('27', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('28', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('29', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('30', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('31', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Gross Pay', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }



    public function reportDefaultLayout_DPRS2($config)
    {

        $data = $this->report_default_query($config);

        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($data)) {
            return $this->othersClass->emptydata($config);
        }

        $this->reporter->linecounter = 0;
        $str .= '<div style="position: absolute;">';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:30px;margin-left:125px');
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader_dprs2($layoutsize, $config);

        $page = 30;    // ilang employee bawat page
        $count = 30;

        $i = 0;
        $catname = '';

        // sub-total ng bawat category
        $sub16 = 0;
        $sub17 = 0;
        $sub18 = 0;
        $sub19 = 0;
        $sub20 = 0;
        $sub21 = 0;
        $sub22 = 0;
        $sub23 = 0;
        $sub24 = 0;
        $sub25 = 0;
        $sub26 = 0;
        $sub27 = 0;
        $sub28 = 0;
        $sub29 = 0;
        $sub30 = 0;
        $sub31 = 0;
        $subgross = 0;

        // grand total
        $grand16 = 0;
        $grand17 = 0;
        $grand18 = 0;
        $grand19 = 0;
        $grand20 = 0;
        $grand21 = 0;
        $grand22 = 0;
        $grand23 = 0;
        $grand24 = 0;
        $grand25 = 0;
        $grand26 = 0;
        $grand27 = 0;
        $grand28 = 0;
        $grand29 = 0;
        $grand30 = 0;
        $grand31 = 0;
        $grandgross = 0;

        foreach ($data as $key => $value) {

            // kapag puno na ang page, lipat sa bagong page at ulitin ang header
            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->header_DEFAULT($config);
                $str .= $this->tableheader_dprs2($layoutsize, $config);
                $page = $page + $count;
            }

            // kapag nagpalit ng category, i-print muna ang sub-total ng naunang category
            if ($i > 0 && $catname != $data[$key]->catname) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col(strtoupper($catname) . ' Sub-Total :', '160', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub16 <= 0 ? '-' : number_format($sub16, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub17 <= 0 ? '-' : number_format($sub17, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub18 <= 0 ? '-' : number_format($sub18, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub19 <= 0 ? '-' : number_format($sub19, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub20 <= 0 ? '-' : number_format($sub20, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub21 <= 0 ? '-' : number_format($sub21, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub22 <= 0 ? '-' : number_format($sub22, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub23 <= 0 ? '-' : number_format($sub23, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub24 <= 0 ? '-' : number_format($sub24, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub25 <= 0 ? '-' : number_format($sub25, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub26 <= 0 ? '-' : number_format($sub26, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub27 <= 0 ? '-' : number_format($sub27, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub28 <= 0 ? '-' : number_format($sub28, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub29 <= 0 ? '-' : number_format($sub29, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub30 <= 0 ? '-' : number_format($sub30, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(($sub31 <= 0 ? '-' : number_format($sub31, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($subgross, 2, '.', ','), '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // ibalik sa 0 ang sub-total para sa bagong category
                $sub16 = 0;
                $sub17 = 0;
                $sub18 = 0;
                $sub19 = 0;
                $sub20 = 0;
                $sub21 = 0;
                $sub22 = 0;
                $sub23 = 0;
                $sub24 = 0;
                $sub25 = 0;
                $sub26 = 0;
                $sub27 = 0;
                $sub28 = 0;
                $sub29 = 0;
                $sub30 = 0;
                $sub31 = 0;
                $subgross = 0;
            }

            $catname = $data[$key]->catname;
            $i++;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($i, '30', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$key]->employee, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day16 <= 0 ? '-' : number_format($data[$key]->day16, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day17 <= 0 ? '-' : number_format($data[$key]->day17, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day18 <= 0 ? '-' : number_format($data[$key]->day18, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day19 <= 0 ? '-' : number_format($data[$key]->day19, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day20 <= 0 ? '-' : number_format($data[$key]->day20, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day21 <= 0 ? '-' : number_format($data[$key]->day21, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day22 <= 0 ? '-' : number_format($data[$key]->day22, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day23 <= 0 ? '-' : number_format($data[$key]->day23, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day24 <= 0 ? '-' : number_format($data[$key]->day24, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day25 <= 0 ? '-' : number_format($data[$key]->day25, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day26 <= 0 ? '-' : number_format($data[$key]->day26, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day27 <= 0 ? '-' : number_format($data[$key]->day27, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day28 <= 0 ? '-' : number_format($data[$key]->day28, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day29 <= 0 ? '-' : number_format($data[$key]->day29, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day30 <= 0 ? '-' : number_format($data[$key]->day30, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(($data[$key]->day31 <= 0 ? '-' : number_format($data[$key]->day31, 2, '.', ',')), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$key]->grosspay, 2, '.', ','), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // idagdag sa sub-total
            $sub16 = $sub16 + $data[$key]->day16;
            $sub17 = $sub17 + $data[$key]->day17;
            $sub18 = $sub18 + $data[$key]->day18;
            $sub19 = $sub19 + $data[$key]->day19;
            $sub20 = $sub20 + $data[$key]->day20;
            $sub21 = $sub21 + $data[$key]->day21;
            $sub22 = $sub22 + $data[$key]->day22;
            $sub23 = $sub23 + $data[$key]->day23;
            $sub24 = $sub24 + $data[$key]->day24;
            $sub25 = $sub25 + $data[$key]->day25;
            $sub26 = $sub26 + $data[$key]->day26;
            $sub27 = $sub27 + $data[$key]->day27;
            $sub28 = $sub28 + $data[$key]->day28;
            $sub29 = $sub29 + $data[$key]->day29;
            $sub30 = $sub30 + $data[$key]->day30;
            $sub31 = $sub31 + $data[$key]->day31;
            $subgross = $subgross + $data[$key]->grosspay;

            // idagdag sa grand total
            $grand16 = $grand16 + $data[$key]->day16;
            $grand17 = $grand17 + $data[$key]->day17;
            $grand18 = $grand18 + $data[$key]->day18;
            $grand19 = $grand19 + $data[$key]->day19;
            $grand20 = $grand20 + $data[$key]->day20;
            $grand21 = $grand21 + $data[$key]->day21;
            $grand22 = $grand22 + $data[$key]->day22;
            $grand23 = $grand23 + $data[$key]->day23;
            $grand24 = $grand24 + $data[$key]->day24;
            $grand25 = $grand25 + $data[$key]->day25;
            $grand26 = $grand26 + $data[$key]->day26;
            $grand27 = $grand27 + $data[$key]->day27;
            $grand28 = $grand28 + $data[$key]->day28;
            $grand29 = $grand29 + $data[$key]->day29;
            $grand30 = $grand30 + $data[$key]->day30;
            $grand31 = $grand31 + $data[$key]->day31;
            $grandgross = $grandgross + $data[$key]->grosspay;
        }

        // sub-total ng huling category
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($catname) . ' Sub-Total :', '160', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub16 <= 0 ? '-' : number_format($sub16, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub17 <= 0 ? '-' : number_format($sub17, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub18 <= 0 ? '-' : number_format($sub18, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub19 <= 0 ? '-' : number_format($sub19, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub20 <= 0 ? '-' : number_format($sub20, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub21 <= 0 ? '-' : number_format($sub21, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub22 <= 0 ? '-' : number_format($sub22, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub23 <= 0 ? '-' : number_format($sub23, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub24 <= 0 ? '-' : number_format($sub24, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub25 <= 0 ? '-' : number_format($sub25, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub26 <= 0 ? '-' : number_format($sub26, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub27 <= 0 ? '-' : number_format($sub27, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub28 <= 0 ? '-' : number_format($sub28, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub29 <= 0 ? '-' : number_format($sub29, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub30 <= 0 ? '-' : number_format($sub30, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($sub31 <= 0 ? '-' : number_format($sub31, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subgross, 2, '.', ','), '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // grand total
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total :', '160', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand16 <= 0 ? '-' : number_format($grand16, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand17 <= 0 ? '-' : number_format($grand17, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand18 <= 0 ? '-' : number_format($grand18, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand19 <= 0 ? '-' : number_format($grand19, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand20 <= 0 ? '-' : number_format($grand20, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand21 <= 0 ? '-' : number_format($grand21, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand22 <= 0 ? '-' : number_format($grand22, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand23 <= 0 ? '-' : number_format($grand23, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand24 <= 0 ? '-' : number_format($grand24, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand25 <= 0 ? '-' : number_format($grand25, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand26 <= 0 ? '-' : number_format($grand26, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand27 <= 0 ? '-' : number_format($grand27, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand28 <= 0 ? '-' : number_format($grand28, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand29 <= 0 ? '-' : number_format($grand29, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand30 <= 0 ? '-' : number_format($grand30, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(($grand31 <= 0 ? '-' : number_format($grand31, 2, '.', ',')), '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandgross, 2, '.', ','), '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        $str .= '</div>';

        return $str;
    }


    public function sbcscript($config)
    {
        if ($config['params']['companyid'] != 72) {
            return [];
        }
        return $this->sbcscript->dprbycat_semimonthly_report($config);
    }
}//end class