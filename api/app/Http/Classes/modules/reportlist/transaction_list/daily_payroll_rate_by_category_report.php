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

class daily_payroll_rate_by_category_report
{
    public $modulename = 'Daily Payroll Rate by Category Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1750px;max-width:1750px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1750'];

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

    public function paramsdata($config)
    {
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

    public function reportplotting($config)
    {
        return $this->reportDefaultLayout($config);
    }


    private function getAddDate($config)
    {
        $adddate = isset($config['params']['dataparams']['end'])
            ? trim($config['params']['dataparams']['end'])
            : '';

        if ($adddate == '' || $adddate == '0000-00-00' || $adddate == '0000-00-00 00:00:00') {
            return '';
        }

        $adddate = date("Y-m-d", strtotime($adddate));
        $start   = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end     = date("Y-m-d", strtotime($start . ' +6 days'));

        if ($adddate >= $start && $adddate <= $end) {
            return '';
        }
        return $adddate;
    }

    public function data_query($config)
    {
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($start . ' +6 days'));
        $adddate      = $this->getAddDate($config);
        $agencyname   = isset($config['params']['dataparams']['agencyname'])       ? $config['params']['dataparams']['agencyname']       : '';
        $itemcategory = isset($config['params']['dataparams']['itemcategoryname']) ? $config['params']['dataparams']['itemcategoryname'] : '';
        $catid        = isset($config['params']['dataparams']['catid'])            ? $config['params']['dataparams']['catid']            : '0';
        $center       = $config['params']['center'];
        $filter       = "";

        $d = [];
        for ($days = 0; $days < 7; $days++) {
            $d[$days + 1] = date("Y-m-d", strtotime($start . " +$days days"));
        }

        if ($agencyname != '') {
            $filter .= " and emp.agencyname = '" . $agencyname . "'";
        }

        if ($itemcategory != '') {
            $catids = implode(',', array_map('intval', explode('~', $catid)));
            $filter .= " and icat.line in ($catids)";
        }

        $datefilter = " date(head.dateid) between '$start' and '$end'";
        if ($adddate != '') {
            $datefilter = " (date(head.dateid) between '$start' and '$end' or date(head.dateid) = '$adddate')";
        }

        $add16 = "0";
        if ($adddate != '') {
            $add16 = "sum(case when dateid = '$adddate' then amt else 0 end)";
        }

        $query = "select empid, empname, category,
                sum(case when dateid = '{$d[1]}' then amt else 0 end) as d1,
                sum(case when dateid = '{$d[2]}' then amt else 0 end) as d2,
                sum(case when dateid = '{$d[3]}' then amt else 0 end) as d3,
                sum(case when dateid = '{$d[4]}' then amt else 0 end) as d4,
                sum(case when dateid = '{$d[5]}' then amt else 0 end) as d5,
                sum(case when dateid = '{$d[6]}' then amt else 0 end) as d6,
                sum(case when dateid = '{$d[7]}' then amt else 0 end) as d7,
                $add16 as d16,
                sum(amt) as gross
                from (
                    select date(head.dateid) as dateid,
                        cl.clientname as empname, stock.empid,
                        ifnull(icat.name, 'NO CATEGORY') as category,
                        round(ifnull(stock.amount, 0), 2) as amt
                    from piecetrans as stock
                    left join dahead as head on head.trno = stock.trno
                    left join hrisnum as num on num.trno = head.trno
                    left join client as cl on cl.clientid = stock.empid
                    left join employee as emp on emp.empid = cl.clientid
                    left join item as it on it.itemid = stock.itemid
                    left join itemcategory as icat on icat.line = it.category
                    where $datefilter and num.center = '$center' $filter

                    union all

                    select date(head.dateid) as dateid,
                        cl.clientname as empname, stock.empid,
                        ifnull(icat.name, 'NO CATEGORY') as category,
                        round(ifnull(stock.amount, 0), 2) as amt
                    from hpiecetrans as stock
                    left join hdahead as head on head.trno = stock.trno
                    left join hrisnum as num on num.trno = head.trno
                    left join client as cl on cl.clientid = stock.empid
                    left join employee as emp on emp.empid = cl.clientid
                    left join item as it on it.itemid = stock.itemid
                    left join itemcategory as icat on icat.line = it.category
                    where $datefilter and num.center = '$center' $filter
                ) as x
                group by empid, empname, category
                order by category, empname";

        return $this->coreFunctions->openTable($query);
    }

    public function DefaultHeader($config)
    {
        $center     = $config['params']['center'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($start . ' +6 days'));
        $adddate    = $this->getAddDate($config);
        $str        = '';
        $layoutsize = '1750';
        $tw         = 1700;
        $font       = $this->companysetup->getrptfont($config['params']);
        $fontsize   = "10";
        $border     = "1px solid ";
        $category   = isset($config['params']['dataparams']['itemcategoryname']) ? $config['params']['dataparams']['itemcategoryname'] : '';
        $agencyname = isset($config['params']['dataparams']['agencyname'])       ? $config['params']['dataparams']['agencyname']       : '';

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $category = str_replace('~', ', ', $category);

        if ($agencyname == '') {
            $agencyname = 'ALL';
        }
        if ($category == '') {
            $category = 'ALL';
        }

        // only show the "Added Date" label when there is an actual date
        $addedLabel = ($adddate != '') ? 'Added Date : ' . $adddate : '';

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '880', null, false, $border, '', '', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY PAYROLL RATE BY CATEGORY REPORT', null, null, false, $border, '', 'L', $font, '12', 'B', '#8B0000', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FROM : ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agency : ' . $agencyname, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '1250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($addedLabel, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category: [' . $category . ']', '1500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($tw);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NO.', '50', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('EMPLOYEE', '300', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        // added date column: always printed so the width stays, header text only when there is a date
        $addHeader = ($adddate != '') ? date('j', strtotime($adddate)) : '';
        $str .= $this->reporter->col($addHeader, '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('1', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('2', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('3', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('4', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('5', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('6', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('7', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Gross Pay', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $str = '';
        $layoutsize = '1750';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px dotted ";
        $result = $this->data_query($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $showAdd = ($this->getAddDate($config) != '');

        $tw = 1700;

        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '200px;margin-top:5px;');
        $str .= $this->DefaultHeader($config);

        $numCount     = 1;
        $lastCategory = '';

        $sub16 = $sub1 = $sub2 = $sub3 = $sub4 = $sub5 = $sub6 = $sub7 = 0;
        $subGross = 0;

        $total16 = $total1 = $total2 = $total3 = $total4 = $total5 = $total6 = $total7 = 0;
        $totalGross = 0;

        foreach ($result as $key => $data) {

            if ($lastCategory != $data->category) {

                if ($lastCategory != '') {
                    // print sub-total of previous category
                    $str .= $this->reporter->begintable($tw);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col(strtoupper($lastCategory) . '  Sub-Total :', '350', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $subAddVal = $showAdd ? ($sub16 == 0 ? '-' : number_format($sub16, 3)) : '';
                    $str .= $this->reporter->col($subAddVal, '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($sub1 == 0  ? '-' : number_format($sub1, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($sub2 == 0  ? '-' : number_format($sub2, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($sub3 == 0  ? '-' : number_format($sub3, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($sub4 == 0  ? '-' : number_format($sub4, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($sub5 == 0  ? '-' : number_format($sub5, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($sub6 == 0  ? '-' : number_format($sub6, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($sub7 == 0  ? '-' : number_format($sub7, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col($subGross == 0 ? '-' : number_format($subGross, 2), '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    // reset per-category subtotal
                    $sub16 = $sub1 = $sub2 = $sub3 = $sub4 = $sub5 = $sub6 = $sub7 = 0;
                    $subGross = 0;
                }

                // category header row
                $str .= $this->reporter->begintable($tw);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('<u>' . strtoupper($data->category) . '</u>', $tw, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $lastCategory = $data->category;
                $numCount = 1; 
            }

            // employee row
            $str .= $this->reporter->begintable($tw);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($numCount, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->empname, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
            $rowAddVal = $showAdd ? ($data->d16 == 0 ? '-' : number_format($data->d16, 3)) : '';
            $str .= $this->reporter->col($rowAddVal, '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d1  == 0 ? '-' : number_format($data->d1, 3),  '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d2  == 0 ? '-' : number_format($data->d2, 3),  '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d3  == 0 ? '-' : number_format($data->d3, 3),  '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d4  == 0 ? '-' : number_format($data->d4, 3),  '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d5  == 0 ? '-' : number_format($data->d5, 3),  '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d6  == 0 ? '-' : number_format($data->d6, 3),  '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->d7  == 0 ? '-' : number_format($data->d7, 3),  '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->gross == 0 ? '-' : number_format($data->gross, 2), '150', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // accumulate sub-total
            $sub16 += $data->d16;
            $sub1  += $data->d1;
            $sub2  += $data->d2;
            $sub3  += $data->d3;
            $sub4  += $data->d4;
            $sub5  += $data->d5;
            $sub6  += $data->d6;
            $sub7  += $data->d7;
            $subGross += $data->gross;

            // accumulate grand total
            $total16 += $data->d16;
            $total1  += $data->d1;
            $total2  += $data->d2;
            $total3  += $data->d3;
            $total4  += $data->d4;
            $total5  += $data->d5;
            $total6  += $data->d6;
            $total7  += $data->d7;
            $totalGross += $data->gross;

            $numCount++;
        }

        // print sub-total of the LAST category
        if ($lastCategory != '') {
            $str .= $this->reporter->begintable($tw);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($lastCategory) . '  Sub-Total :', '350', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $subAddVal = $showAdd ? ($sub16 == 0 ? '-' : number_format($sub16, 3)) : '';
            $str .= $this->reporter->col($subAddVal, '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($sub1 == 0  ? '-' : number_format($sub1, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($sub2 == 0  ? '-' : number_format($sub2, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($sub3 == 0  ? '-' : number_format($sub3, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($sub4 == 0  ? '-' : number_format($sub4, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($sub5 == 0  ? '-' : number_format($sub5, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($sub6 == 0  ? '-' : number_format($sub6, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($sub7 == 0  ? '-' : number_format($sub7, 3),  '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col($subGross == 0 ? '-' : number_format($subGross, 2), '150', null, false, '1px dotted', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= '</br>';
        }

        // GRAND TOTAL row
        $str .= $this->reporter->begintable($tw);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total :', '350', null, false, '1px solid', 'TB', 'L', $font, $fontsize, 'B', '', '', '');
        $totalAddVal = $showAdd ? ($total16 == 0 ? '-' : number_format($total16, 3)) : '';
        $str .= $this->reporter->col($totalAddVal, '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total1 == 0  ? '-' : number_format($total1, 3),  '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total2 == 0  ? '-' : number_format($total2, 3),  '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total3 == 0  ? '-' : number_format($total3, 3),  '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total4 == 0  ? '-' : number_format($total4, 3),  '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total5 == 0  ? '-' : number_format($total5, 3),  '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total6 == 0  ? '-' : number_format($total6, 3),  '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($total7 == 0  ? '-' : number_format($total7, 3),  '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($totalGross == 0 ? '-' : number_format($totalGross, 2), '150', null, false, '1px solid', 'TB', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}
