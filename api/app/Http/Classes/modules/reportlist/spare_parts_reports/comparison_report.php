<?php

namespace App\Http\Classes\modules\reportlist\spare_parts_reports;

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

class comparison_report
{
    public $modulename = 'Comparison Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1500px;max-width:1500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1480'];

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
        $fields = ['radioprint',  'radioreporttype', 'dwhname', 'radioposttype', 'year', 'year2', 'byear'];

        $col1 = $this->fieldClass->create($fields);
        data_set(
            $col1,
            'radioreporttype.options',
            [
                ['label' => 'Spareparts', 'value' => '0', 'color' => 'blue'],
                ['label' => 'MC Unit.', 'value' => '1', 'color' => 'magenta'],
                ['label' => 'All', 'value' => '2', 'color' => 'blue']
            ]
        );
        data_set(
            $col1,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'blue'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'magenta'],
                ['label' => 'All', 'value' => '2', 'color' => 'blue']
            ]
        );
        data_set($col1, 'year.label', 'Year 1');
        data_set($col1, 'year2.label', 'Year 2');
        data_set($col1, 'byear.label', 'Year 3');
        data_set($col1,  'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'red']]);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable(
            "select 
                'default' as print,
                year(now()) as year, year(now()) as year2,
                year(now())  as byear, 
                '0' as posttype,
                '0' as reporttype,
                0 as whid,
                '' as wh,
                '' as whname,
                '' as dwhname"
        );
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function reportplotting($config)
    {
        $posttype  = $config['params']['dataparams']['posttype'];
        $year1 = $config['params']['dataparams']['year'];
        $year2 = $config['params']['dataparams']['year2'];
        $year3 = $config['params']['dataparams']['byear'];
        $result1 =  $this->reportDefaultLayout_qry($config, $year1);
        $result2 =  $this->reportDefaultLayout_qry($config, $year2);
        $result3 =  $this->reportDefaultLayout_qry($config, $year3);

        switch ($posttype) {
            case 0: // posted
            case 1: //unposted
                $result = $this->posted_unposted_layout($config, $result1, $result2, $result3);
                break;
            case 2: //all
                $result = $this->reportDefaultLayout_All($config, $result1, $result2, $result3);
                break;
        }

        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $year1 = $config['params']['dataparams']['year'];
        $year2 = $config['params']['dataparams']['year2'];
        $year3 = $config['params']['dataparams']['byear'];
        $reporttype  = $config['params']['dataparams']['reporttype'];
        $posttype  = $config['params']['dataparams']['posttype'];
        $wh         = $config['params']['dataparams']['wh'];

        $filter = '';
        if ($wh != "") {
            $whid = $config['params']['dataparams']['whid'];
            $filter = $filter . " and wh.clientid=" . $whid;
        }

        $condition1 = "";
        $head = "";
        $head1 = "";
        $head2 = "";
        $whjoin = "";
        $whjoin_posted = "";
        $whjoin_unposted = "";

        switch ($reporttype) {
            case 0: //spareparts
                $condition1 = " and head.doc='CI'";
                break;
            case 1: //mc unit
                $condition1 = " and head.doc in('SJ','MJ')";
                break;
            case 2: //all
                $condition1 = "and head.doc in ('SJ','MJ', 'CI')";
                break;
        }
        switch ($posttype) {
            case 0: //posted
                $head = "glhead";
                $whjoin = "left join client as wh on wh.clientid = head.whid";
                break;
            case 1: //unposted
                $head = "lahead";
                $whjoin = "left join client as wh on wh.client = head.wh";
                break;
            case 2: //all
                $head1 = "lahead";
                $whjoin_unposted = "left join client as wh on wh.client = head.wh";

                $head2 = "glhead";
                $whjoin_posted = "left join client as wh on wh.clientid = head.whid";
                break;
        }

        switch ($posttype) {
            case 0: //posted
            case 1: //unposted
                $query = "select cntnum.center as branch,cn.name,cn.address
                            from $head as head
                            left join cntnum on cntnum.trno=head.trno
                            left join center as cn on cn.code=cntnum.center
                            $whjoin
                            where  year(head.dateid) in ('$year1','$year2','$year3') $condition1 $filter
                            group by cntnum.center,cn.name,cn.address";
                break;
            case 2: // all
                $query = "
                 select branch,name,address from (
                select cntnum.center as branch,cn.name,cn.address
                            from $head1 as head
                            left join cntnum on cntnum.trno=head.trno
                            left join center as cn on cn.code=cntnum.center
                            $whjoin_unposted
                            where  year(head.dateid) in ('$year1','$year2','$year3') $condition1 $filter
                            group by cntnum.center,cn.name,cn.address
                            
                            union all 

                            select cntnum.center as branch,cn.name,cn.address
                            from $head2 as head
                            left join cntnum on cntnum.trno=head.trno
                            left join center as cn on cn.code=cntnum.center
                            $whjoin_posted
                            where  year(head.dateid) in ('$year1','$year2','$year3') $condition1 $filter
                            group by cntnum.center,cn.name,cn.address ) as a
                            group by branch,name,address";
                break;
        }

        return $query;
    }

    public function reportDefaultLayout_qry($config, $year)
    {
        $this->reporter->linecounter = 0;
        $reporttype  = $config['params']['dataparams']['reporttype'];
        $posttype  = $config['params']['dataparams']['posttype'];
        $wh         = $config['params']['dataparams']['wh'];

        $filter = '';
        if ($wh != '') {
            $filter .= " and wh.client = '$wh'";
        }
        
        $condition = "";
        $condition1 = "";

        $head = "";
        $stock = "";

        switch ($posttype) {
            case 0: //posted
                $head = "glhead";
                $stock = "glstock";
                break;
            case 1: //unposted
                $head = "lahead";
                $stock = "lastock";
                break;
        }

        switch ($reporttype) {
            case 0: //spareparts
                $condition = "";
                $condition1 = " and head.doc='CI'";
                break;
            case 1: //mc unit
                $condition = " and cat.name='MC Unit'";
                $condition1 = " and head.doc in ('SJ','MJ')";
                break;
            case 2: //all
                $condition = "";
                $condition = " and (head.doc in ('SJ','MJ') and cat.name='MC Unit' or head.doc='CI')";
                $condition1 = "and head.doc in ('SJ','MJ', 'CI')";
                break;
        }
        switch ($posttype) {
            case 0: //posted
            case 1: //unposted 
                $query1 = " select year,m,sum(amt) as amt,branch
                                     from ( select  year(head.dateid) as year,month(head.dateid) as m,sum(stock.ext) as amt, cntnum.center as branch 
                                        from $head as head
                                        left join $stock as stock on stock.trno=head.trno
                                        left join cntnum on cntnum.trno=head.trno
                                        left join item as i on i.itemid=stock.itemid
                                        left join itemcategory as cat on cat.line = i.category
                                        left join center as cn on cn.code=cntnum.center
                                        left join client as wh on wh.clientid = stock.whid
                                        where year(head.dateid) = '" . $year . "' $condition1 $condition $filter
                                        group by year(head.dateid),month(head.dateid),cntnum.center ) as T group by year,m,branch
                                        ";
                break;


            case 2: //all
                $query1 = "select year,m,sum(amt) as amt,branch
                                     from (
                            select  year(head.dateid) as year,month(head.dateid) as m,sum(stock.ext) as amt, cntnum.center as branch
                                        from lahead as head
                                        left join lastock as stock on stock.trno=head.trno
                                        left join cntnum on cntnum.trno=head.trno
                                        left join item as i on i.itemid=stock.itemid
                                        left join itemcategory as cat on cat.line = i.category
                                        left join center as cn on cn.code=cntnum.center
                                        left join client as wh on wh.clientid = stock.whid
                                       where year(head.dateid) = '" . $year . "' $condition1 $condition $filter
                                        group by year(head.dateid),month(head.dateid),cntnum.center

                            union all

                            select  year(head.dateid) as year,month(head.dateid) as m,sum(stock.ext) as amt, cntnum.center as branch
                                        from glhead as head
                                        left join glstock as stock on stock.trno=head.trno
                                        left join cntnum on cntnum.trno=head.trno
                                        left join item as i on i.itemid=stock.itemid
                                        left join itemcategory as cat on cat.line = i.category
                                        left join center as cn on cn.code=cntnum.center
                                        left join client as wh on wh.clientid = stock.whid
                                        where year(head.dateid) = '" . $year . "' $condition1 $condition $filter 
                                        group by year(head.dateid),month(head.dateid),cntnum.center
                             ) as T group by year,m,branch";
                break;
        }


        $data1 = $this->coreFunctions->opentable($query1);

        return $data1;
    }

    public function reportDefaultLayout_All($config, $data3, $data4, $data5)
    {
        $result = $this->reportDefault($config);

        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1480';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);

        $branch = '';
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $branch = $data->branch;
                $monthlyAmounts = []; // Array to store monthly amounts for each year
                $year1 = $year2 = $year3 = null;
                // data3 -> first year input 
                foreach ($data3 as $key => $data3_entry) {
                    if ($data3_entry->branch == $branch) {
                        $year = $data3_entry->year;
                        $month = $data3_entry->m;
                        $amount = $data3_entry->amt;
                        // Store the amount for the corresponding year and month
                        $monthlyAmounts[$year][$month] = $amount;
                        if ($year1 === null) {
                            $year1 = $year; // Initialize year2
                        }
                    }
                }
                // data4 -> second year input 
                foreach ($data4 as $key => $data4_entry) {
                    if ($data4_entry->branch == $branch) {
                        $year = $data4_entry->year;
                        $month = $data4_entry->m;
                        $amount = $data4_entry->amt;
                        // Store the amount for the corresponding year and month
                        $monthlyAmounts[$year][$month] = $amount;
                        if ($year2 === null) {
                            $year2 = $year; // Initialize year2
                        }
                    }
                }
                // data5 -> third year input 
                foreach ($data5 as $key => $data5_entry) {
                    if ($data5_entry->branch == $branch) {
                        $year = $data5_entry->year;
                        $month = $data5_entry->m;
                        $amount = $data5_entry->amt;
                        // Store the amount for the corresponding year and month
                        $monthlyAmounts[$year][$month] = $amount;
                        if ($year3 === null) {
                            $year3 = $year; // Initialize year2
                        }
                    }
                }

                if (!empty($monthlyAmounts)) {
                    // Display branch header
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->name . ' -' . $data->address, '450', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', 'b', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= $this->tableheader($layoutsize, $config);

                    // Process each year
                    $averages = [];
                    foreach ($monthlyAmounts as $year => $months) {
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col($year, '100', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
                        $amounts = array_fill(1, 12, 0); // Initialize an array with 12 elements set to 0
                        foreach ($months as $month => $amount) {
                            $amounts[$month] = $amount;
                        }
                        $total = array_sum($months);
                        $average = $total / 12;
                        $averages[$year] = $average;
                        for ($i = 1; $i <= 12; $i++) {
                            $amount = $amounts[$i];
                            $str .= $this->reporter->col(number_format($amount, 2), '90', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        }
                        $str .= $this->reporter->col(number_format($total, 2), '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($average, 2), '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');

                        // Calculate and display variance
                        $variance = '';
                        if ($year == $year2 && isset($averages[$year1])) {
                            $variance = ($average - $averages[$year1]) / $average;
                        } elseif ($year == $year3 && isset($averages[$year2])) {
                            $variance = ($average - $averages[$year2]) / $average;
                        }

                        if ($variance !== '') {
                            $str .= $this->reporter->col(number_format($variance * 100, 2) . '%', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        } else {
                            $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        }

                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }

                    // Display LY for each month at the bottom of the branch
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Vs. LY', '100', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');

                    for ($i = 1; $i <= 12; $i++) {
                        $ly = 0;
                        if (isset($monthlyAmounts[$year3][$i]) && isset($monthlyAmounts[$year2][$i])) {
                            $ly = $monthlyAmounts[$year3][$i] / $monthlyAmounts[$year2][$i];
                        } elseif (isset($monthlyAmounts[$year2][$i]) && isset($monthlyAmounts[$year1][$i])) {
                            $ly = $monthlyAmounts[$year2][$i] / $monthlyAmounts[$year1][$i];
                        } elseif (isset($monthlyAmounts[$year3][$i]) && isset($monthlyAmounts[$year1][$i])) {
                            $ly = $monthlyAmounts[$year3][$i] / $monthlyAmounts[$year1][$i];
                        }

                        $str .= $this->reporter->col(number_format($ly * 100, 2) . '%', '90', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                    }

                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for total
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for average
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); //  for variance
                    $str .= $this->reporter->endrow();

                    // Display LM for each month at the bottom of the branch
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Vs. LM', '100', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');

                    for ($i = 1; $i <= 12; $i++) {
                        $lm = 0;
                        if (isset($monthlyAmounts[$year3][$i])) {
                            $last_month_value = $monthlyAmounts[$year3][$i];
                        } elseif (isset($monthlyAmounts[$year2][$i])) {
                            $last_month_value = $monthlyAmounts[$year2][$i];
                        } else {
                            $last_month_value = 0;
                        }

                        $last_year_month_value = 0;
                        if ($i == 1) {
                            if (isset($monthlyAmounts[$year3][12])) {
                                $last_year_month_value = $monthlyAmounts[$year3][12];
                            } elseif (isset($monthlyAmounts[$year2][12])) {
                                $last_year_month_value = $monthlyAmounts[$year2][12];
                            } elseif (isset($monthlyAmounts[$year1][12])) {
                                $last_year_month_value = $monthlyAmounts[$year1][12];
                            }
                        } else {
                            if (isset($monthlyAmounts[$year3][$i - 1])) {
                                $last_year_month_value = $monthlyAmounts[$year3][$i - 1];
                            } elseif (isset($monthlyAmounts[$year2][$i - 1])) {
                                $last_year_month_value = $monthlyAmounts[$year2][$i - 1];
                            } elseif (isset($monthlyAmounts[$year1][$i - 1])) {
                                $last_year_month_value = $monthlyAmounts[$year1][$i - 1];
                            }
                        }

                        if ($last_month_value > 0 && $last_year_month_value > 0) {
                            $lm = $last_month_value / $last_year_month_value;
                        }

                        $str .= $this->reporter->col(number_format($lm * 100, 2) . '%',  '90', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                    }

                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); //  for total
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for average
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for variance

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }


    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        // $year1        = date("Y", strtotime($config['params']['dataparams']['year']));

        $str = '';
        $layoutsize = '1480';
        $font = $this->companysetup->getrptfont($config['params']);
        // $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Comparison Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1480';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JANUARY', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('FEBRUARY', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MARCH', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('APRIL', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MAY', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JUNE', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('JULY', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AUGUST', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SEPTEMBER', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OCTOBER', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('NOVEMBER', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DECEMBER', '90', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL', '100', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AVERAGE', '100', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('VARIANCE [%age]', '100', '', '', $border, 'B', 'R', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function posted_unposted_layout($config, $data3, $data4, $data5)
    {

        $result = $this->reportDefault($config);

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);

        //$data3=0 -2022
        //$data4-1-2023
        // $data5 -2024 

        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $layoutsize = '1480';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);


        $branch = '';
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $branch = $data->branch;
                $monthlyAmounts = []; // Array to store monthly amounts for each year
                $year1 = $year2 = $year3 = null;
                // data3 -> first year input 
                foreach ($data3 as $key => $data3_entry) {
                    if ($data3_entry->branch == $branch) {
                        $year = $data3_entry->year;
                        $month = $data3_entry->m;
                        $amount = $data3_entry->amt;
                        // Store the amount for the corresponding year and month
                        $monthlyAmounts[$year][$month] = $amount;
                        if ($year1 === null) {
                            $year1 = $year; // Initialize year2
                        }
                    }
                }
                // data4 -> second year input 
                foreach ($data4 as $key => $data4_entry) {
                    if ($data4_entry->branch == $branch) {
                        $year = $data4_entry->year;
                        $month = $data4_entry->m;
                        $amount = $data4_entry->amt;
                        // Store the amount for the corresponding year and month
                        $monthlyAmounts[$year][$month] = $amount;
                        if ($year2 === null) {
                            $year2 = $year; // Initialize year2
                        }
                    }
                }
                // data5 -> third year input 
                foreach ($data5 as $key => $data5_entry) {
                    if ($data5_entry->branch == $branch) {
                        $year = $data5_entry->year;
                        $month = $data5_entry->m;
                        $amount = $data5_entry->amt;
                        // Store the amount for the corresponding year and month
                        $monthlyAmounts[$year][$month] = $amount;
                        if ($year3 === null) {
                            $year3 = $year; // Initialize year2
                        }
                    }
                }

                if (!empty($monthlyAmounts)) {
                    // Display branch header
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->name . ' -' . $data->address, '450', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= $this->tableheader($layoutsize, $config);

                    // Process each year
                    $averages = [];
                    foreach ($monthlyAmounts as $year => $months) {
                        $str .= $this->reporter->begintable($layoutsize);
                        $str .= $this->reporter->startrow();
                        $str .= $this->reporter->col($year, '100', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');
                        $amounts = array_fill(1, 12, 0); // Initialize an array with 12 elements set to 0
                        foreach ($months as $month => $amount) {
                            $amounts[$month] = $amount;
                        }
                        $total = array_sum($months);
                        $average = $total / 12;
                        $averages[$year] = $average;
                        for ($i = 1; $i <= 12; $i++) {
                            $amount = $amounts[$i];
                            $str .= $this->reporter->col(number_format($amount, 2), '90', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        }
                        $str .= $this->reporter->col(number_format($total, 2), '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        $str .= $this->reporter->col(number_format($average, 2), '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');

                        // Calculate and display variance
                        $variance = '';
                        if ($year == $year2 && isset($averages[$year1])) {
                            $variance = ($average - $averages[$year1]) / $average;
                        } elseif ($year == $year3 && isset($averages[$year2])) {
                            $variance = ($average - $averages[$year2]) / $average;
                        }

                        if ($variance !== '') {
                            $str .= $this->reporter->col(number_format($variance * 100, 2) . '%', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        } else {
                            $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                        }

                        $str .= $this->reporter->endrow();
                        $str .= $this->reporter->endtable();
                    }

                    // Display LY for each month at the bottom of the branch
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Vs. LY', '100', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');

                    for ($i = 1; $i <= 12; $i++) {
                        $ly = 0;
                        if (isset($monthlyAmounts[$year3][$i]) && isset($monthlyAmounts[$year2][$i])) {
                            $ly = $monthlyAmounts[$year3][$i] / $monthlyAmounts[$year2][$i];
                        } elseif (isset($monthlyAmounts[$year2][$i]) && isset($monthlyAmounts[$year1][$i])) {
                            $ly = $monthlyAmounts[$year2][$i] / $monthlyAmounts[$year1][$i];
                        } elseif (isset($monthlyAmounts[$year3][$i]) && isset($monthlyAmounts[$year1][$i])) {
                            $ly = $monthlyAmounts[$year3][$i] / $monthlyAmounts[$year1][$i];
                        }

                        $str .= $this->reporter->col(number_format($ly * 100, 2) . '%', '90', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                    }

                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for total
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for average
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); //  for variance
                    $str .= $this->reporter->endrow();

                    // Display LM for each month at the bottom of the branch
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Vs. LM', '100', '', '', $border, 'T', 'C', $font, $fontsize, 'B', '', '');

                    for ($i = 1; $i <= 12; $i++) {
                        $lm = 0;
                        if (isset($monthlyAmounts[$year3][$i])) {
                            $last_month_value = $monthlyAmounts[$year3][$i];
                        } elseif (isset($monthlyAmounts[$year2][$i])) {
                            $last_month_value = $monthlyAmounts[$year2][$i];
                        } else {
                            $last_month_value = 0;
                        }

                        $last_year_month_value = 0;
                        if ($i == 1) {
                            if (isset($monthlyAmounts[$year3][12])) {
                                $last_year_month_value = $monthlyAmounts[$year3][12];
                            } elseif (isset($monthlyAmounts[$year2][12])) {
                                $last_year_month_value = $monthlyAmounts[$year2][12];
                            } elseif (isset($monthlyAmounts[$year1][12])) {
                                $last_year_month_value = $monthlyAmounts[$year1][12];
                            }
                        } else {
                            if (isset($monthlyAmounts[$year3][$i - 1])) {
                                $last_year_month_value = $monthlyAmounts[$year3][$i - 1];
                            } elseif (isset($monthlyAmounts[$year2][$i - 1])) {
                                $last_year_month_value = $monthlyAmounts[$year2][$i - 1];
                            } elseif (isset($monthlyAmounts[$year1][$i - 1])) {
                                $last_year_month_value = $monthlyAmounts[$year1][$i - 1];
                            }
                        }

                        if ($last_month_value > 0 && $last_year_month_value > 0) {
                            $lm = $last_month_value / $last_year_month_value;
                        }

                        $str .= $this->reporter->col(number_format($lm * 100, 2) . '%',  '90', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', '');
                    }

                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); //  for total
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for average
                    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $fontsize, 'B', '', ''); // for variance

                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
            }
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class