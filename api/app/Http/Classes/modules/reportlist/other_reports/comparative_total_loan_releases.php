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

class comparative_total_loan_releases
{
    public $modulename = 'Comparative Total Loan Releases';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $month;
    public $year;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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


        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
            'default' as print,
            adddate(left(now(),10),-360) as start, left(now(),10) as end");
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



    // $query = "select ifnull(r.reqtype,'') as reqtype
    // from heahead  as head
    // left join reqcategory as r on r.line=head.planid
    // where date(head.dateid) between '$start' and '$end'
    // group by r.reqtype";

    // if (!empty($req) && is_array($req)) {
    //     foreach ($req as $row) {
    //         if (isset($row->reqtype)) {
    //             $loantype[] = $row->reqtype;
    //         }
    //     }
    // }
    public function loantype($config)
    {
        $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $query = "select distinct head.planid
        from heahead  as head
        where date(head.dateid) between '$start' and '$end'";
        $req = $this->coreFunctions->opentable($query);
        $loantype = [];

        if (!empty($req) && is_array($req)) {
            foreach ($req as $row) {
                if (isset($row->planid)) {
                    $loantype[] = (int) $row->planid;
                }
            }
        }
        return $loantype;
    }


    public function reportDefault($config)
    {
        // QUERY
        $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        // $query = "select sum(info.amount) as amount, date(head.dateid) as effective, head.planid, month(head.dateid) as months,
        //             ifnull(r.reqtype,'') as categoryname,count(cl.clientid) as empcount
        //             from heahead  as head
        //             left join transnum as num on num.trno = head.trno
        //             left join reqcategory as r on r.line=head.planid
        //             left join heainfo as info on info.trno=head.trno
        //             left join client as cl on cl.client=head.client
        //             where num.cvtrno<>0 and date(head.dateid) between '$start' and '$end'
        //             group by r.reqtype,head.dateid,head.planid";
        $query ="select sum(info.amount) as amount, date(head.dateid) as effective, ea.planid, month(head.dateid) as months,
                    ifnull(r.reqtype,'') as categoryname,count(cl.clientid) as empcount
                    from lahead as head
                    left join cntnum as num on num.trno = head.trno
                    left join transnum as tnum on tnum.cvtrno = head.trno
                    left join heahead as ea on ea.trno = tnum.trno
                    left join reqcategory as r on r.line=ea.planid
                    left join heainfo as info on info.trno=ea.trno
                    left join client as cl on cl.client=head.client
                    where num.dptrno<>0 and date(head.dateid) between '$start' and '$end'
                    group by r.reqtype,head.dateid,ea.planid
union all
select sum(info.amount) as amount, date(head.dateid) as effective, ea.planid, month(head.dateid) as months,
                    ifnull(r.reqtype,'') as categoryname,count(cl.clientid) as empcount
                    from glhead as head
                    left join cntnum as num on num.trno = head.trno
                    left join transnum as tnum on tnum.cvtrno = head.trno
                    left join heahead as ea on ea.trno = tnum.trno
                    left join reqcategory as r on r.line=ea.planid
                    left join heainfo as info on info.trno=ea.trno
                    left join client as cl on cl.clientid=head.clientid
                    where num.dptrno<>0 and date(head.dateid) between '$start' and '$end'
                    group by r.reqtype,head.dateid,ea.planid";
        return $this->coreFunctions->opentable($query);
    }


    public function month($config)
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = strtoupper(date('M', mktime(0, 0, 0, $i, 1))) . '.';
        }
        return $months;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $loantyp = $this->loantype($config);
        $monthv = $this->month($config);
        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';
        $count = 35;
        $page = 35;
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }


        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();

        $monthsWithData = [];
        foreach ($result as $resmonth) {
            if (isset($resmonth->months)) {
                $monthsWithData[] = (int) $resmonth->months;
            }
        }

        $uniqueMonths = array_unique($monthsWithData);
        sort($uniqueMonths);
        $monthsCount = count($uniqueMonths);

        $monthss = ($monthsCount < 12) ? $monthsCount + 1 : $monthsCount;
        $layoutsizes = ($monthsCount < 12) ? 800 : 680;
        $width = round($layoutsizes / $monthss);
        $totalWidth = ($monthsCount == 12) ? 120 : $width;

        $indexedResult = [];
        foreach ($result as $row) {
            $planid = $row->planid;
            $month = (int) $row->months;
            if (!isset($indexedResult[$planid][$month])) {
                $indexedResult[$planid][$month] = 0;
            }
            $indexedResult[$planid][$month] += $row->amount;
        }

        $typeNames = [];
        foreach ($loantyp as $res) {
            if (!isset($typeNames[$res])) {
                $typeNames[$res] = $this->coreFunctions->getfieldvalue("reqcategory", "reqtype", "line=?", [$res]);
            }
        }
        $totalpermonth = [];
        $totals = 0;
        foreach ($loantyp as $res) {
            $type = $typeNames[$res];
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($type, '200', null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');

            $totalpertype = 0;
            foreach ($monthv as $index => $m) {
                $intmonth = $index + 1;
                if (in_array($intmonth, $uniqueMonths)) {
                    $amount = isset($indexedResult[$res][$intmonth]) ? $indexedResult[$res][$intmonth] : 0;
                    $totalpertype += $amount;

                    if (!isset($totalpermonth[$intmonth])) {
                        $totalpermonth[$intmonth] = 0;
                    }
                    $totalpermonth[$intmonth] += $amount;

                    $str .= $this->reporter->col(number_format($amount, 2), $width, null, false, $border, 'TLB', 'RT', $font, $font_size, '', '', '');
                }
            }

            $totals += $totalpertype;
            $str .= $this->reporter->col(number_format($totalpertype, 2), $totalWidth, null, false, $border, 'TLBR', 'RT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
        }


        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', '200', null, false, $border, 'LB', 'CT', $font, $font_size, 'B', '', '');

        foreach ($monthv as $index => $m) {
            $intmonth = $index + 1;
            if (in_array($intmonth, $uniqueMonths)) {
                $amt = isset($totalpermonth[$intmonth]) ? $totalpermonth[$intmonth] : 0;
                $str .= $this->reporter->col(number_format($amt, 2), $width, null, false, $border, 'LB', 'RT', $font, $font_size, 'B', '', '');
            }
        }
        $str .= $this->reporter->col(number_format($totals, 2), $totalWidth, null, false, $border, 'LBR', 'RT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter != $page) {
            $str .= '<br/><br/>';
            $str .= $this->reportDefaultLayout2($config);
        } else {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config);
            $page += $count;
        }

        $str .= $this->reporter->endreport();

        return $str;
    }



    private function displayHeader($config)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = '10';
        $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $fstart = date('F j, Y', strtotime($start));
        $fstarts = date('F j', strtotime($start));
        $fend = date('F j, Y', strtotime($end));

        $year1 = date('Y', strtotime($start));
        $year2 = date('Y', strtotime($end));

        $fontcolor = '#FFFFFF'; // text color
        $bgcolors = '#002060'; // background color 

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $str = '';

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name, null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Comparative Total Loan Releases (Amount)', null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        if ($year1 == $year2) {
            $str .= $this->reporter->col('For the period covered ' . $fstarts . ' - ' . $fend, null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        } else {
            $str .= $this->reporter->col('For the period covered ' . $fstart . ' - ' . $fend, null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Type of Loan', '200', null,  $bgcolors, $border, '', 'C', $font, $font_size, 'B',  $fontcolor, '2px');


        $monthsWithData = [];
        foreach ($result as $resmonth) {
            if (isset($resmonth->months)) {
                $monthsWithData[] = (int) $resmonth->months;
            }
        }

        $uniqueMonths = array_unique($monthsWithData);
        sort($uniqueMonths); // sort to maintain order
        $monthsCount = count($uniqueMonths);

        // $layoutsizes = ($monthsCount < 12) ? 840 : 720;
        if ($monthsCount < 12) {
            $monthss = $monthsCount + 1;
            $layoutsizes = 800;
        } else {
            $monthss = $monthsCount;
            $layoutsizes = 680;
        }
        $width = round($layoutsizes / $monthss);

        foreach ($uniqueMonths as $intmonth) {
            $monthName = strtoupper(date('M', mktime(0, 0, 0, $intmonth, 1))) . '.';
            $str .= $this->reporter->col($monthName, $width, null, $bgcolors, $border, '', 'C', $font, $font_size, 'B', $fontcolor, '2px');
        }

        // Total column
        $totalWidth = ($monthsCount == 12) ? 120 : $width;
        $str .= $this->reporter->col('YTD', $totalWidth, null,  $bgcolors, $border, '', 'C', $font, $font_size, 'B',  $fontcolor, '2px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    private function displayHeader2($config)
    {
        $result = $this->reportDefault($config);
        $border = '1px solid';
        $font = 'Century Gothic';
        $font_size = '10';
        $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $fstart = date('F j, Y', strtotime($start));
        $fstarts = date('F j', strtotime($start));
        $fend = date('F j, Y', strtotime($end));

        $year1 = date('Y', strtotime($start));
        $year2 = date('Y', strtotime($end));

        $fontcolor = '#FFFFFF'; // text color
        $bgcolors = '#002060'; // background color 

        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $str = '';
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name, null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Comparative Total Loan (No. of Clients)', null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        if ($year1 == $year2) {
            $str .= $this->reporter->col('For the period covered ' . $fstarts . ' - ' . $fend, null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        } else {
            $str .= $this->reporter->col('For the period covered ' . $fstart . ' - ' . $fend, null, null, false, $border, '', 'C', $font, '16', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Type of Loan', '200', null,  $bgcolors, $border, '', 'C', $font, $font_size, 'B',  $fontcolor, '2px');


        $monthsWithData = [];
        foreach ($result as $resmonth) {
            if (isset($resmonth->months)) {
                $monthsWithData[] = (int) $resmonth->months;
            }
        }

        $uniqueMonths = array_unique($monthsWithData);
        sort($uniqueMonths); // sort to maintain order
        $monthsCount = count($uniqueMonths);

        // $layoutsizes = ($monthsCount < 12) ? 840 : 720;
        if ($monthsCount < 12) {
            $monthss = $monthsCount + 1;
            $layoutsizes = 800;
        } else {
            $monthss = $monthsCount;
            $layoutsizes = 680;
        }
        $width = round($layoutsizes / $monthss);

        foreach ($uniqueMonths as $intmonth) {
            $monthName = strtoupper(date('M', mktime(0, 0, 0, $intmonth, 1))) . '.';
            $str .= $this->reporter->col($monthName, $width, null, $bgcolors, $border, '', 'C', $font, $font_size, 'B', $fontcolor, '2px');
        }

        // Total column
        $totalWidth = ($monthsCount == 12) ? 120 : $width;
        $str .= $this->reporter->col('YTD', $totalWidth, null,  $bgcolors, $border, '', 'C', $font, $font_size, 'B',  $fontcolor, '2px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout2($config)
    {
        $result = $this->reportDefault($config);
        $loantyp = $this->loantype($config);
        $monthv = $this->month($config);
        $border = '1px solid #C0C0C0 !important';
        $font = 'Century Gothic';
        $font_size = '10';
        $count = 20;
        $page = 20;
        $str = '';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->displayHeader2($config);
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();

        $monthsWithData = [];
        foreach ($result as $resmonth) {
            if (isset($resmonth->months)) {
                $monthsWithData[] = (int) $resmonth->months;
            }
        }

        $uniqueMonths = array_unique($monthsWithData);
        sort($uniqueMonths);
        $monthsCount = count($uniqueMonths);

        $monthss = ($monthsCount < 12) ? $monthsCount + 1 : $monthsCount;
        $layoutsizes = ($monthsCount < 12) ? 800 : 680;
        $width = round($layoutsizes / $monthss);
        $totalWidth = ($monthsCount == 12) ? 120 : $width;

        $indexedResult = [];
        foreach ($result as $row) {
            $planid = $row->planid;
            $month = (int) $row->months;
            if (!isset($indexedResult[$planid][$month])) {
                $indexedResult[$planid][$month] = 0;
            }
            $indexedResult[$planid][$month] += $row->empcount;
        }

        $typeNames = [];
        foreach ($loantyp as $res) {
            if (!isset($typeNames[$res])) {
                $typeNames[$res] = $this->coreFunctions->getfieldvalue("reqcategory", "reqtype", "line=?", [$res]);
            }
        }
        $totalpermonth = [];
        $totals = 0;
        foreach ($loantyp as $res) {
            $type = $typeNames[$res];
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($type, '200', null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');

            $totalpertype = 0;
            foreach ($monthv as $index => $m) {
                $intmonth = $index + 1;
                if (in_array($intmonth, $uniqueMonths)) {
                    $amount = isset($indexedResult[$res][$intmonth]) ? $indexedResult[$res][$intmonth] : 0;
                    $totalpertype += $amount;

                    if (!isset($totalpermonth[$intmonth])) {
                        $totalpermonth[$intmonth] = 0;
                    }
                    $totalpermonth[$intmonth] += $amount;
                    $str .= $this->reporter->col($amount, $width, null, false, $border, 'TLB', 'CT', $font, $font_size, '', '', '');
                }
            }

            $totals += $totalpertype;
            $str .= $this->reporter->col($totalpertype, $totalWidth, null, false, $border, 'TLBR', 'CT', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $page += $count;
            }
        }

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total', '200', null, false, $border, 'LB', 'CT', $font, $font_size, 'B', '', '');

        foreach ($monthv as $index => $m) {
            $intmonth = $index + 1;
            if (in_array($intmonth, $uniqueMonths)) {
                $amt = isset($totalpermonth[$intmonth]) ? $totalpermonth[$intmonth] : 0;
                $str .= $this->reporter->col($amt, $width, null, false, $border, 'LB', 'CT', $font, $font_size, 'B', '', '');
            }
        }
        $str .= $this->reporter->col($totals, $totalWidth, null, false, $border, 'LBR', 'CT', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class