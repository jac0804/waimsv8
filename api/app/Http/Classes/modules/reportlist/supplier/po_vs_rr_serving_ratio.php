<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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
use DateInterval;
use DatePeriod;

class po_vs_rr_serving_ratio
{
    public $modulename = 'PO vs RR Serving Ratio';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    private $monthfield = [];

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

    //for resize;
    public $margin = 130;

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
        $companyid = $config['params']['companyid'];
        $fields = ['start', 'end', 'dcentername'];
        //'dcentername'
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['radioreporttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set(
            $col2,
            'radioreporttype.options',
            [
                ['label' => 'MC UNIT', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Spare Parts', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );
        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $companyid = $config['params']['companyid'];
        $paramstr = "select 
    'default' as print,
    left(now(),10) as start,
    left(now(),10) as end,
    '' as centername,
    '' as center,
    '' as dcentername,
    '0' as reporttype";
        return $this->coreFunctions->opentable($paramstr);
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

    public function reportDefault($config)
    {
        $query = $this->reportQuery_start($config);
        return $this->coreFunctions->opentable($query);
    }

    public function reportQuery_start($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $center = $config['params']['dataparams']['center'];
        $filter = " and cat.name = 'MC UNIT' or grp.stockgrp_name = 'Spareparts'";
        if ($reporttype == 0) {
            $filter = " and cat.name = 'MC UNIT'";
        } elseif ($reporttype == 1) {
            $filter = " and grp.stockgrp_name = 'Spareparts'";
        }
        $arrayquery = [];
        $startmonth = strtotime($start);
        $endmonth = strtotime($end);

        while ($startmonth <= $endmonth) {
            $month = date("m", $startmonth);
            array_push($arrayquery, "sum((case when month(head.dateid) = $month then stock.qty else 0 end)) as acrrqty_$month");
            if (!in_array("acrrqty_$month", $this->monthfield)) {
                array_push($this->monthfield, "acrrqty_$month");
            }
            $startmonth = strtotime("+1 month", $startmonth);
        }

        $arrayquery = implode(", ", $arrayquery);
        $fields = implode(", ", $this->monthfield);

        $fmounth =  date("m", strtotime($start));

        $query = "
        select dateid,itemid,itemname, poqty, $fields   from (
        select ifnull(po.dateid,'') as dateid,item.itemid,item.itemname,
        ifnull(po.poqty,0) as poqty, $arrayquery
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join itemcategory as cat on cat.line = item.category
        left join cntnum on cntnum.trno = head.trno
        left join (select phead.dateid ,pstock.trno,pstock.line,
        sum((case when month(phead.dateid)= $fmounth then pstock.rrqty else 0 end)) as poqty
        from hpohead  as phead
        left join hpostock as pstock on pstock.trno = phead.trno
        where date(phead.dateid) between '" . $start . "' and '" . $end . "'
        group by pstock.rrqty,phead.dateid ,pstock.trno,pstock.line
        ) as po on  po.trno = stock.refx and po.line = stock.linex
        where head.doc = 'rr' and cntnum.center='" . $center . "' and po.poqty <> 0  $filter and date(head.dateid) between '" . $start . "' and '" . $end . "' 
        group by item.itemid,item.itemname,po.poqty,po.dateid

        union all 

        select ifnull(po.dateid,'') as dateid,item.itemid,item.itemname,
        ifnull(po.poqty,0) as poqty, $arrayquery
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join itemcategory as cat on cat.line = item.category
        left join cntnum on cntnum.trno = head.trno
        left join (select phead.dateid ,pstock.trno,pstock.line,
        sum((case when month(phead.dateid)= $fmounth then pstock.rrqty else 0 end)) as poqty
        from hpohead  as phead
        left join hpostock as pstock on pstock.trno = phead.trno
        where date(phead.dateid) between '" . $start . "' and '" . $end . "'
        group by pstock.rrqty,phead.dateid ,pstock.trno,pstock.line
        ) as po on  po.trno = stock.refx and po.line = stock.linex
        where head.doc = 'rr' and cntnum.center='" . $center . "' and po.poqty <> 0 $filter and date(head.dateid) between '" . $start . "' and '" . $end . "' 
        group by item.itemid,item.itemname,po.poqty,po.dateid) as porr order by itemname";

        return $query;
    }
    private function default_displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];


        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'L', $font, '16', 'B', '', '3px') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($reporttype == 0) {
            $rtype = "MC Unit";
        } elseif ($reporttype == 1) {
            $rtype = "Spare Parts";
        } else {
            $rtype = "ALL";
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), 800, null, false, $border, '', 'L', $font, $fontsize, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('Report Type : ' . $rtype, 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', 300, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->col('', 250, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function default_tableheader($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $str = '';
        $layoutsize = '422';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $interval = new DateInterval('P1M');
        $period = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));
        $mcount = iterator_count($period);
        $layoutsize *= $mcount;
        if ($mcount == 1 || $mcount == 2) {
            $layoutsize = $this->reportParams['layoutSize'];
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PURCHASE DATE', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('MC UNIT', '190', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('TOTAL PO QTY', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '12', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');

        foreach ($period as $date) {
            $str .= $this->reporter->col("ACTUAL REC'D WITHIN " . strtoupper($date->format('F Y')), '130', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
            $str .= $this->reporter->col("", '12', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '3px');
            $str .= $this->reporter->col('SERVING RATIO WITHIN ' . strtoupper($date->format('F Y')), '130', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '3px');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function reportDefaultLayout($config)
    {

        $result  = $this->reportDefault($config);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $reporttype = $config['params']['dataparams']['reporttype'];
        $count = 51;
        $page = 50;
        $this->reporter->linecounter = 0;

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
        $str .= $this->default_displayHeader($config);
        $str .= $this->default_tableheader($config);

        $pototalqty = 0;
        $actualreceived = 0;
        $podate = "";
        $totalallratio = 0;

        $total = [];
        foreach ($result as $key => $data) {
            $query = "select itemname from item where itemid = $data->itemid";
            $items = $this->coreFunctions->opentable($query);

            if ($podate == '' || $podate != date("F Y", strtotime($data->dateid))) {
                $podate = date("F Y", strtotime($data->dateid));
                $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
                $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');
                $str .= $this->reporter->col($podate, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $podate = date("F Y", strtotime($data->dateid));
            }

            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();

            foreach ($items as $key2 => $value) {
                $str .= $this->reporter->col('', '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($value->itemname, '190', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->poqty, 2), '132', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            }
            $arrayfield = [];
            $arrayqty = [];

            foreach ($this->monthfield as $rrkey) {
                array_push($arrayfield, $rrkey);
                array_push($arrayqty, $data->$rrkey);


                if (isset($total[$rrkey])) {
                    $total[$rrkey] += $arrayqty[array_search($rrkey, $arrayfield)];
                } else {
                    $total[$rrkey] = $arrayqty[array_search($rrkey, $arrayfield)];
                }
                $str .= $this->reporter->col('', '12', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->$rrkey, 2), '130', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $ratio = ($data->$rrkey / $data->poqty) * 100;
                $str .= $this->reporter->col('', '12', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($ratio, 2) . '%', '130', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '12', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            }

            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $pototalqty += $data->poqty;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);

                $page = $page + $count;
            }
        }
        $date = date("F Y", strtotime($start));
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL - ' . strtoupper($date), '110', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('', '190', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col(number_format($pototalqty, 2), '132', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $sumratio = 0;
        $ratioflag = false;
        foreach ($total as $key => $value) {
            $sumratio += $value;
            $ratio = ($sumratio / $pototalqty) * 100;
            if ($ratio == 100 && !$ratioflag) {
                $ratioflag = true;
            } elseif ($ratioflag) {
                $ratio = 0;
            }
            $str .= $this->reporter->col('', '12', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($value, 2), '130', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '12', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($ratio, 2) . '%', '130', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '12', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '5px');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }
}
