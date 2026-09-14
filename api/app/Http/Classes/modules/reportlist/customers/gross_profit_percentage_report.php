<?php

namespace App\Http\Classes\modules\reportlist\customers;

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


class gross_profit_percentage_report
{
    public $modulename = 'Gross Profit Percentage Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1200'];

    // Category columns shown on the report. Left blank ('-') for now --
    // per-category breakdown queries can be wired in later.
    private $categories = ['EGC', 'EVC', 'PVC', 'FOIL', 'FILTER', 'MACHINE', 'RAW MATS', 'SPARE PARTS', 'PACKAGING'];

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
        $fields = ['radioprint', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.type', 'date');
        data_set($col1, 'end.type', 'date');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end
        ");
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportplotting($config)
    {
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "select sum(x.beginning) as beginning, sum(x.ending) as ending,
            sum(x.sales) as sales, sum(x.gsales) as gsales, sum(x.discount) as discount,
            0 as purchases, 0 as opex
            from
            (select sum(gdetail.db - gdetail.cr) as beginning, 0 as ending, 0 as sales, 0 as gsales, 0 as discount
            from glhead as head
            left join gldetail as gdetail on gdetail.trno = head.trno
            left join coa on coa.acnoid = gdetail.acnoid
            where left(coa.alias, 2) in ('AR')
            and date(head.dateid) < '$start'

            union all

            select 0 as beginning, sum(gdetail.db - gdetail.cr) as ending, 0 as sales, 0 as gsales, 0 as discount
            from glhead as head
            left join gldetail as gdetail on gdetail.trno = head.trno
            left join coa on coa.acnoid = gdetail.acnoid
            where left(coa.alias, 2) in ('AR')
            and date(head.dateid) <= '$end'

            union all

            select 0 as beginning, 0 as ending, sum(stock.ext) as sales, sum(stock.isamt*stock.isqty) as gsales,
            sum((stock.isamt*stock.isqty)-stock.ext) as discount
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            where head.doc in ('sj','mj','sd','se','sf')
            and date(head.dateid) between '$start' and '$end'

            union all

            select 0 as beginning, 0 as ending, sum(stock.ext) as sales, sum(stock.isamt*stock.isqty) as gsales,
            sum((stock.isamt*stock.isqty)-stock.ext) as discount
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            where head.doc in ('sj','mj','sd','se','sf')
            and date(head.dateid) between '$start' and '$end'
            ) as x";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1200';
        $font = 'Tahoma';
        $fontsize = "11";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From: ' . date("F d, Y", strtotime($start)) . ' to ' . date("F d, Y", strtotime($end)), '1200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        return $str;
    }

    // Column widths sum to 1200: 200 (label) + 9 x 100 (categories) + 100 (total) = 1200
    private function reportRow($label, $totalDisplay, $font, $fontsize, $bold = '', $border = '', $borderPos = '')
    {
        $labelWidth = '200';
        $catWidth   = '120';
        $totalWidth = '120';
        $layoutsize = '1200';

        $str = $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($label, $labelWidth, null, false, $border, $borderPos, 'L', $font, $fontsize, $bold);
        foreach ($this->categories as $cat) {
            $str .= $this->reporter->col('-', $catWidth, null, false, $border, $borderPos, 'R', $font, $fontsize, $bold);
        }
        $str .= $this->reporter->col($totalDisplay, $totalWidth, null, false, $border, $borderPos, 'R', $font, $fontsize, $bold);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function formatAmount($value)
    {
        if ($value === null) {
            return '-';
        }
        $value = (float) $value;
        if ($value == 0.0) {
            return '-';
        }
        $formatted = number_format(abs($value), 2);
        return $value < 0 ? "($formatted)" : $formatted;
    }

    private function formatPercent($value)
    {
        if ($value === null) {
            return '-';
        }
        return number_format($value, 2);
    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '1200';
        $font = 'Tahoma';
        $fontsize = "11";
        $border  = "1px dotted ";
        $border1 = "3px double ";
        $companyid = $config['params']['companyid'];

        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $row = $result[0];
        $beginning          = (float) $row->beginning;
        $ending             = (float) $row->ending;
        $netPurchases       = (float) $row->purchases;
        $netSales           = (float) $row->sales;
        $salesDiscount      = 0.0; // shown separately, per sample report
        $operatingExpenses  = (float) $row->opex;

        $totalGoodsAvailable = $beginning + $netPurchases;
        $costOfSales         = $totalGoodsAvailable - $ending;
        $grossProfit         = $netSales - $costOfSales;
        $netIncome           = $grossProfit - $operatingExpenses;

        $gpOverNs = $netSales != 0 ? $grossProfit / $netSales : null;
        $gpOverCs = $costOfSales != 0 ? $grossProfit / $costOfSales : null;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($result));

        // column header row -- widths sum to 1200: 200 + (9 x 100) + 100
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCOUNT NAME', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B');
        foreach ($this->categories as $cat) {
            $str .= $this->reporter->col($cat, '120', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        }
        $str .= $this->reporter->col('Total', '120', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        // section header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('COST OF SALES', '1200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reportRow('BEG. INVENTORY ' . date('n/j/Y', strtotime($start)), $this->formatAmount($beginning), $font, $fontsize);
        $str .= $this->reportRow('ADD: NET PURCHASES ' . strtoupper(date('F', strtotime($end))), $this->formatAmount($netPurchases), $font, $fontsize);
        $str .= $this->reportRow('TOTAL GOODS AVAILABLE FOR SALES', $this->formatAmount($totalGoodsAvailable), $font, $fontsize, '', $border, 'T');
        $str .= $this->reportRow('LESS: ENDING INVENTORY ' . date('n/j/Y', strtotime($end)), $this->formatAmount(-1 * $ending), $font, $fontsize);
        $str .= $this->reportRow('', $this->formatAmount($costOfSales), $font, $fontsize, 'B', $border1, '');

        $str .= '<br/><br/>';

        $str .= $this->reportRow('NET SALES', $this->formatAmount($netSales), $font, $fontsize);
        $str .= $this->reportRow('SALES DISCOUNT', $this->formatAmount($salesDiscount), $font, $fontsize);
        $str .= $this->reportRow('LESS: COST OF SALES', $this->formatAmount($costOfSales), $font, $fontsize, '', $border, 'T');
        $str .= $this->reportRow('GROSS PROFIT', $this->formatAmount($grossProfit), $font, $fontsize, 'B');
        $str .= $this->reportRow('LESS: OPERATING EXPENSES', $this->formatAmount(-1 * $operatingExpenses), $font, $fontsize);
        $str .= $this->reportRow('NET INCOME FR. OPERATION', $this->formatAmount($netIncome), $font, $fontsize, 'B', $border1, 'B');

        $str .= '<br/><br/>';

        $str .= $this->reportRow('PERCENTAGE GP/NS', $this->formatPercent($gpOverNs), $font, $fontsize);
        $str .= $this->reportRow('PERCENTAGE GP/CS', $this->formatPercent($gpOverCs), $font, $fontsize);

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class