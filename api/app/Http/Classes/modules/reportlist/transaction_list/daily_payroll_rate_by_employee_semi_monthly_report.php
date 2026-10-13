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

class daily_payroll_rate_by_employee_semi_monthly_report
{
    public $modulename = 'Daily Payroll Rate by Employee Semi Monthly Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1850px;max-width:1850px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1700'];

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
        $fields = ['radioprint', 'start', 'end', 'radioreporttype', 'agencyname', 'dwhname', 'itemcategoryname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Day 1 - 15 Employee', 'value' => '0', 'color' => 'red'],
            ['label' => 'Day 16 - 31 Employee', 'value' => '1', 'color' => 'red']
        ]);
        data_set($col1, 'itemcategoryname.lookupclass', 'lookupcategoryitemcategory');
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $qry = "select 'default' as print,
        '" . $this->othersClass->getCurrentDate() . "' as start,
        '" . $this->othersClass->getCurrentDate() . "' +interval 30 day as end,
        '0' as reporttype, '' as agencyname, '' as dwhname,'' as wh,'' as whname,
        '' as categoryname, '' as category, 0 as catid,'' as itemcategoryname
        ";

        return $this->coreFunctions->opentable($qry);
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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportQuery($config)
    {
        $center   = $config['params']['center'];
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $option = $config['params']['dataparams']['reporttype'];
        $category = $config['params']['dataparams']['itemcategoryname'];
        $catid = $config['params']['dataparams']['catid'];
        $catIDs = "'" . implode("','", array_map('addslashes', explode('~', $catid))) . "'";
        $whname = $config['params']['dataparams']['whname'];
        $agency = $config['params']['dataparams']['agencyname'];
        $filter = '';
        $days = '';

        if ($category != '') {
            $filter .= " and it.category in ($catIDs)";
        }

        if ($whname != '') {
            $filter .= " and warehouse.clientname = '" . $whname . "'";
        }

        if ($agency != '') {
            $filter .= " and emp.agencyname = '" . $agency . "'";
        }

        if ($option == '0') {
            $days = '
            sum(case when day(a.dateid) = 1 then a.amount else 0 end) as day1,
            sum(case when day(a.dateid) = 2 then a.amount else 0 end) as day2,
            sum(case when day(a.dateid) = 3 then a.amount else 0 end) as day3,
            sum(case when day(a.dateid) = 4 then a.amount else 0 end) as day4,
            sum(case when day(a.dateid) = 5 then a.amount else 0 end) as day5,
            sum(case when day(a.dateid) = 6 then a.amount else 0 end) as day6,
            sum(case when day(a.dateid) = 7 then a.amount else 0 end) as day7,
            sum(case when day(a.dateid) = 8 then a.amount else 0 end) as day8,
            sum(case when day(a.dateid) = 9 then a.amount else 0 end) as day9,
            sum(case when day(a.dateid) = 10 then a.amount else 0 end) as day10,
            sum(case when day(a.dateid) = 11 then a.amount else 0 end) as day11,
            sum(case when day(a.dateid) = 12 then a.amount else 0 end) as day12,
            sum(case when day(a.dateid) = 13 then a.amount else 0 end) as day13,
            sum(case when day(a.dateid) = 14 then a.amount else 0 end) as day14,
            sum(case when day(a.dateid) = 15 then a.amount else 0 end) as day15
            ';
        } else if ($option == '1') {
            $days = '
            sum(case when day(a.dateid) = 16 then a.amount else 0 end) as day16,
            sum(case when day(a.dateid) = 17 then a.amount else 0 end) as day17,
            sum(case when day(a.dateid) = 18 then a.amount else 0 end) as day18,
            sum(case when day(a.dateid) = 19 then a.amount else 0 end) as day19,
            sum(case when day(a.dateid) = 20 then a.amount else 0 end) as day20,
            sum(case when day(a.dateid) = 21 then a.amount else 0 end) as day21,
            sum(case when day(a.dateid) = 22 then a.amount else 0 end) as day22,
            sum(case when day(a.dateid) = 23 then a.amount else 0 end) as day23,
            sum(case when day(a.dateid) = 24 then a.amount else 0 end) as day24,
            sum(case when day(a.dateid) = 25 then a.amount else 0 end) as day25,
            sum(case when day(a.dateid) = 26 then a.amount else 0 end) as day26,
            sum(case when day(a.dateid) = 27 then a.amount else 0 end) as day27,
            sum(case when day(a.dateid) = 28 then a.amount else 0 end) as day28,
            sum(case when day(a.dateid) = 29 then a.amount else 0 end) as day29,
            sum(case when day(a.dateid) = 30 then a.amount else 0 end) as day30,
            sum(case when day(a.dateid) = 31 then a.amount else 0 end) as day31
            ';
        }

        $query="
        select a.empname,
        $days, sum(a.restday) as restday, sum(a.night) as night, sum(a.holiday) as holiday, sum(a.adjustmnt) as adjustmnt, sum(a.thirteenmos) as thirteenmos, sum(a.admin) as admin, sum(a.canteen) as canteen, sum(a.cacanteen) as cacanteen, sum(a.cahahsy) as cahahsy, sum(a.charge) as charge
        from(
        select date(head.dateid) as dateid, num.center,head.trno, head.docno,head.type,stock.empname,
        head.whid, warehouse.clientname as whname,warehouse.client as wh,sum(stock.amount) as amount,
        sum(stock.restday) as restday, sum(stock.night) as night, sum(stock.holiday) as holiday, sum(stock.adjustmnt) as adjustmnt, sum(stock.thirteenmos) as thirteenmos,sum(stock.admin) as admin, sum(stock.canteen) as canteen, sum(stock.cacanteen) as cacanteen,sum(stock.cahahsy) as cahahsy, sum(stock.charge) as charge
        from dahead as head
        inner join piecetrans as stock on stock.trno = head.trno
        left join hrisnum as num on num.trno = head.trno
        left join item as it on it.itemid = stock.itemid
        left join client as warehouse on warehouse.clientid = head.whid
        left join employee as emp on emp.empid = stock.empid
        where  num.center = $center and date(head.dateid) between '$start' and '$end' $filter
        group by dateid, num.center, head.trno, head.docno, head.type, stock.empname,
        head.whid, warehouse.clientname, warehouse.client
        union all
        select  date(head.dateid) as dateid, num.center,head.trno, head.docno,head.type,stock.empname,
        head.whid, warehouse.clientname as whname,warehouse.client as wh,sum(stock.amount) as amount,
        sum(stock.restday) as restday, sum(stock.night) as night, sum(stock.holiday) as holiday, sum(stock.adjustmnt) as adjustmnt, sum(stock.thirteenmos) as thirteenmos,sum(stock.admin) as admin, sum(stock.canteen) as canteen, sum(stock.cacanteen) as cacanteen,sum(stock.cahahsy) as cahahsy, sum(stock.charge) as charge
        from hdahead as head
        inner join hpiecetrans as stock on stock.trno = head.trno
        left join hrisnum as num on num.trno = head.trno
        left join item as it on it.itemid = stock.itemid
        left join client as warehouse on warehouse.clientid = head.whid 
        left join employee as emp on emp.empid = stock.empid
        where  num.center =  $center and date(head.dateid) between '$start' and '$end' $filter
        group by  stock.empname, dateid, num.center, head.trno, head.docno, head.type,
        head.whid, warehouse.clientname, warehouse.client
        )as a group by a.empname
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    public function reportDefaultheader($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $agency = $config['params']['dataparams']['agencyname'];
        $wh         = $config['params']['dataparams']['wh'];
        $warehouse = $config['params']['dataparams']['whname'];
        $category = $config['params']['dataparams']['itemcategoryname'];
        $option = $config['params']['dataparams']['reporttype'];
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $qry = "select name,address,tel,code from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str = '';
        $layoutsize = '1700';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "8";
        $border = "3px solid";
        $str .= '<br><br>';
        ($agency != '') ? $agency  : $agency = 'All Agencies';
        ($warehouse != '') ? $warehouse  : $warehouse  = 'All Warehouses';
        ($category != '') ? $category  : $category = 'All Categories';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name, null, null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY PAYROLL RATE REPORT BY EMPLOYEE', null, null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date: <u>' . $start . '</u> To <u>' . $end . '</u>', 100, null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Agency: <u>' . $agency . '</u>', 100, null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('Warehouse: <u>' . $warehouse . '</u>', 400, null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Category: <u>'. $category. '</u>', null, null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('No.', 30, 30, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Employee', 100, 30, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');

        if ($option == '0') { //first half of the month
            for ($i = 1; $i <= 15; $i++) {
                $str .= $this->reporter->col(($i), 50, 30, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');
            }
        } else if ($option == '1') { //second half of the month
            for ($i = 16; $i <= 31; $i++) {
                $str .= $this->reporter->col(($i), 50, 30, false, $border, 'TB', 'c', $font, $fontsize, 'B', '', '', '8px');
            }
        }
        $str .= $this->reporter->col('Gross Pay', 70, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('+SDAY', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('+NIGHT', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('+HDAY', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('+ADJ', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('+13TH', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('+ADMI', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('-Cant', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('-CAC', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('-CAH', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('-Chrg', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Net Pay', 50, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Days', 40, 30, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('No.', 30, 30, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('', 20, 30, false, '', '', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $center   = $config['params']['center'];
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $option = $config['params']['dataparams']['reporttype'];
        $str = '';
        $layoutsize = '1700';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "8";
        $border = "1px dotted ";
        $count = 0;

        $page = 55;

        $tgross = 0;
        $tnet = 0;
        $trest = 0;
        $tnight = 0;
        $tholiday = 0;
        $tadjustmnt = 0;
        $tthirteenmos = 0;
        $tadmin = 0;
        $tcanteen = 0;
        $tcacanteen = 0;
        $tcahahsy = 0;
        $tcharge = 0;
        $totaldays = 0;
        $tdays = array();
        
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '170px;margin-top:5px;');
        $str .= $this->reportDefaultheader($config);
        $query = $this->reportQuery($config);
  
        if (empty($query)) {
            return $this->othersClass->emptydata($config);
        }

        for($i = 1; $i <= 31; $i++){  //for grand total init
            $tdays[$i] = 0;
        }

        foreach ($query as $data) {
            // row calculation
            $lines = $this->reporter->estimateRowLines([
                [$data->empname, 80, '6px']
            ], $fontsize  * 0.5);
            for ($l = 0; $l < $lines; $l++) {
                $this->reporter->addline();
            } // row calculation END

            $count++;
            $gross = 0;
            $net = 0;
            $daysworked = 0;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($count, 30, 30, false, $border, 'B', 'C', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col($data->empname, 100, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');

            if ($option == '0'){//first half 1 - 15
                for($i = 1; $i <= 15; $i++){     
                    $str .= $this->reporter->col(($data->{'day'.$i} != 0? number_format($data->{'day'.$i},2) : '-'), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
                    $gross += $data->{'day'.$i}; // gross
                    $tdays[$i] += $data->{'day'.$i}; //total gross per day accumulation
                    if ($data->{'day'.$i} != 0) { $daysworked++;} //days work ++
                }
            }else if ($option == '1'){//second half 16 - 31
                    for($i = 16; $i <= 31; $i++){
                    $str .= $this->reporter->col(($data->{'day'.$i} != 0? number_format($data->{'day'.$i},2) : '-'), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
                    $gross += $data->{'day'.$i};// gross
                    $tdays[$i] += $data->{'day'.$i};//total gross per day accumulation
                    if ($data->{'day'.$i} != 0) { $daysworked++;} //days work ++
                }
            }
    
            $str .= $this->reporter->col(number_format($gross,2), 70, 30, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->restday,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->night,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->holiday,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->adjustmnt,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->thirteenmos,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->admin,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->canteen,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->cacanteen,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->cahahsy,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col(number_format($data->charge,2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');

            $net = $gross + $data->restday + $data->night + $data->holiday + $data->adjustmnt + $data->thirteenmos + $data->admin - $data->canteen - $data->cacanteen - $data->cahahsy - $data->charge;
            $str .= $this->reporter->col(number_format($net, 2), 50, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col($daysworked, 40, 30, false, $border, 'B', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col($count, 30, 30, false, $border, 'B', 'c', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->col('', 20, 30, false, '', '', 'R', $font, $fontsize, '', '', '', '8px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //for grandtotal
            $tgross += $gross;
            $tnet += $net;
            $trest += $data->restday;
            $tnight += $data->night;
            $tholiday += $data->holiday;
            $tadjustmnt += $data->adjustmnt;
            $tthirteenmos += $data->thirteenmos;
            $tadmin += $data->admin;
            $tcanteen += $data->canteen;
            $tcacanteen += $data->cacanteen;
            $tcahahsy += $data->cahahsy;
            $tcharge += $data->charge;
            $totaldays += $daysworked;

            if ($this->reporter->linecounter >= $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->reportDefaultheader($config);
                $this->reporter->linecounter = 0;
            }
        }
        //GRAND TOTAL
        // $a determines if top or bottom grandtotal (UPPER = 0, LOWER = 1)
        for($a = 0; $a <= 1; $a++){ 
        $border = "1px solid ";
        $lineborder = $a == 0 ? 'T' : 'B';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($a == 0 ? 'GRAND TOTAL: ' : '', 130, 30, false, $border, $lineborder, 'C', $font, $fontsize, 'B', '', '', '8px');
           if ($option == '0'){//first half of the month
                for($i = 1; $i <= 15; $i++){//if multiple of 2 print; -1 determines it
                    $str .= $this->reporter->col((($i - 1) % 2 == $a) ? ($tdays[$i] != 0 ? number_format($tdays[$i], 2) : '-') : '', 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
                }
            }else if ($option == '1'){//second half of the month
                for($i = 16; $i <= 31; $i++){//if multiple of 2 print; -1 determines it
                    $str .= $this->reporter->col((($i - 16) % 2 == $a) ? ($tdays[$i] != 0 ? number_format($tdays[$i], 2) : '-') : '', 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
                }
            }
            //outer ternary - checks if it should printed on top or bottom grandtotal
            //inner ternary - checks if the value is not 0
            $str .= $this->reporter->col(($a == 0 ? ($tgross != 0? number_format($tgross,2):'-') : ''), 70, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 1 ? ($tnet !=0? number_format($tnet,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 0 ? ($tnight !=0? number_format($tnight,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 1 ? ($tholiday !=0? number_format($tholiday,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 0 ? ($tadjustmnt !=0? number_format($tadjustmnt,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 1 ? ($tthirteenmos !=0? number_format($tthirteenmos,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 0 ? ($tadmin !=0? number_format($tadmin,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 1 ? ($tcanteen !=0? number_format($tcanteen,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 0 ? ($tcacanteen !=0? number_format($tcacanteen,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 1 ? ($tcahahsy !=0? number_format($tcahahsy,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 0 ? ($tcharge !=0? number_format($tcharge,2):'-') : ''), 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col($a == 1 ? ($tnet !=0? number_format($tnet,2):'-') : '', 50, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col(($a == 0 ? ($totaldays !=0? $totaldays:'-') : ''), 40, 30, false, $border, $lineborder, 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col('', 30, 30, false, $border, $lineborder, 'C', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col('', 20, 30, false, '', '', 'R', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', $option == 0? 1500 : 1550, null, false, '2px solid', 'T', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', 20, 30, false, '', '', 'R', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}
