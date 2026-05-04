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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Symfony\Component\VarDumper\VarDumper;

class monthly_sales_collected_and_uncollected_report
{
    public $modulename = 'Monthly Sales Collected and Uncollected Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1200'];

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

        $fields = ['start', 'end', 'dcentername', 'pref', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dcentername.readonly', false);
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'All Transaction', 'value' => '0', 'color' => 'orange'],
            ['label' => 'Posted', 'value' => '1', 'color' => 'orange'],
            ['label' => 'Unposted', 'value' => '2', 'color' => 'orange'],
        ]);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        date(now()) as end, 
        '" . $center . "' as center,
        '" . $dcenter[0]->dcentername . "' as dcentername,
        '" . $dcenter[0]->name . "' as centername,
        0 clientid, '' client, '' as clientname, '' as dclientname,
        '0' as reporttype, '' as pref, '' as prefix
        ";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        return $this->reportDefaultLayout($config);
    }

    // QUERY
    public function default_qry($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $center = $config['params']['dataparams']['center'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $pref = $config['params']['dataparams']['pref'];

        $filter = '';

        if ($center != '') {
            $filter .= " and cnum.center = '$center'";
        }

        if ($pref != '') {
            $filter .= " and cnum.bref = '$pref'";
        }

        switch ($reporttype) {
            case 1:
                $query = "select s.dateid, s.sales, ifnull(s.uncollected, 0) as uncollected,ifnull(s.collected, 0) as collected
                    from (select dateid,sum(sales) as sales,sum(collected) as collected,sum(uncollected) as uncollected
                    from (select 'Posted' as type, cnum.bref, date_format(ar.dateid,'%Y-%m') as dateid,
                    sum(ar.db) as sales,sum(ar.db - ar.bal) as collected,sum(ar.bal) as uncollected
                    from arledger as ar
                    left join glhead as head on head.trno = ar.trno
                    left join cntnum as cnum on cnum.trno = ar.trno
                    where cnum.doc = 'SJ'and date(ar.dateid) between '$start' and '$end' $filter
                    group by cnum.bref, date_format(ar.dateid,'%Y-%m')
                    ) as posted
                    group by dateid
                    ) as s
                    order by s.dateid";
                break;
            case 2:
                $query = "select s.dateid, s.sales, ifnull(s.uncollected, 0) as uncollected,ifnull(s.collected, 0) as collected
                    from (select dateid,sum(sales) as sales,sum(collected) as collected,sum(uncollected) as uncollected
                    from (select 'Unposted' as type, cnum.bref, date_format(head.dateid,'%Y-%m') as dateid,
                    ifnull(sum(stock.ext),0) as sales,0.0 as collected,ifnull(sum(stock.ext),0) as uncollected
                    from lahead as head
                    left join lastock as stock on stock.trno = head.trno
                    left join cntnum as cnum on cnum.trno = head.trno
                    where cnum.doc = 'SJ' and date(head.dateid) between '$start' and '$end' $filter
                    group by cnum.bref, date_format(head.dateid,'%Y-%m')
                    ) as unposted
                    group by dateid
                    ) as s
                    order by s.dateid";
                break;
            default:
                $query = "select bref, dateid, sum(sales) as sales, sum(collected) as collected, sum(uncollected) as uncollected
                    from (select 'Posted' as type, cnum.bref, date_format(ar.dateid,'%Y-%m') as dateid,
                    sum(ar.db) as sales,sum(ar.db - ar.bal) as collected,sum(ar.bal) as uncollected
                    from arledger as ar
                    left join glhead as head on head.trno = ar.trno
                    left join cntnum as cnum on cnum.trno = ar.trno
                    where cnum.doc = 'SJ' and date(ar.dateid) between '$start' and '$end' $filter
                    group by cnum.bref, date_format(ar.dateid,'%Y-%m')
                    union all
                    select 'Unposted' as type, cnum.bref, date_format(head.dateid,'%Y-%m') as dateid,
                    ifnull(sum(stock.ext),0) as sales, 0.0 as collected, ifnull(sum(stock.ext),0) as uncollected
                    from lahead as head
                    left join lastock as stock on stock.trno = head.trno
                    left join cntnum as cnum on cnum.trno = head.trno
                    where cnum.doc = 'SJ'
                    and date(head.dateid) between '$start' and '$end' $filter
                    group by cnum.bref, date_format(head.dateid,'%Y-%m')
                    ) as sales
                    group by bref, dateid
                    order by dateid, bref";
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function report_default_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
        $end   = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
        $pref = $config['params']['dataparams']['pref'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "14";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MONTHLY SALES COLLECTED & UNCOLLECTED REPORT (BY SALES INVOICE TYPE)', null, null, false, '2px solid', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '', '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($pref != '') {
            $str .= $this->reporter->col('Document Prefix: <b>' . $pref . '</b>', '400', null, false, '3px solid', '', 'L', $font,  $fontsize, '', '', '');
        } else {
            $prefQuery = "select distinct bref from cntnum where doc = 'SJ' and center = $center and bref != '' order by bref";
            $prefResult = json_decode(json_encode($this->coreFunctions->opentable($prefQuery)), true);
            $prefList = implode('/', array_column($prefResult, 'bref'));
            $str .= $this->reporter->col('Document Prefix: <b>' . $prefList . '</b>', '400', null, false, '3px solid', '', 'L', $font,  $fontsize, '', '', '');
        }
        if ($reporttype == '1') {
            $reporttype = 'POSTED TRANSACTIONS';
        } elseif ($reporttype == '2') {
            $reporttype = 'UNPOSTED TRANSACTIONS';
        } else {
            $reporttype = 'ALL TRANSACTIONS';
        }
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Type of Report: <b>' . $reporttype . '</b>', '400', null, false, '3px solid', '', 'R', $font,  $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : <b>' . $start . '</b> to <b>' . $end . '</b>', null, null, false, '', '', 'L', $font,  $fontsize, '');
        $str .= $this->reporter->pagenumber('Page', '230', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('MONTH-YEAR', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL SALES', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL COLLECTED', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL UNCOLLECTED', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "14";
        $this->reporter->linecounter = 0;

        $result = $this->default_qry($config);
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // index result by month
        $data_map = [];
        foreach ($result as $row) {

            if (!isset($data_map[$row->dateid])) {
                $data_map[$row->dateid] = (object)[
                    'dateid' => $row->dateid,
                    'sales' => 0,
                    'collected' => 0,
                    'uncollected' => 0
                ];
            }

            $data_map[$row->dateid]->sales       += $row->sales;
            $data_map[$row->dateid]->collected   += $row->collected;
            $data_map[$row->dateid]->uncollected += $row->uncollected;
        }

        // build months from start year-month to end year-month
        $months = [];
        $current = date("Y-m", strtotime($start));
        $last    = date("Y-m", strtotime($end));
        while ($current <= $last) {
            $months[] = $current;
            $current  = date("Y-m", strtotime($current . '-01 +1 month'));
        }

        $count = count($months);

        $grand_sales       = 0;
        $grand_collected   = 0;
        $grand_uncollected = 0;

        $str .= $this->reporter->beginreport($layoutsize);
        // $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '25px;margin-top:5px;');
        $str .= $this->report_default_header($config);

        for ($i = 0; $i < $count; $i++) {
            $month_key   = $months[$i];
            $data        = isset($data_map[$month_key]) ? $data_map[$month_key] : null;
            $sales       = $data ? $data->sales       : 0;
            $collected   = $data ? $data->collected   : 0;
            $uncollected = $data ? $data->uncollected : 0;

            $grand_sales       += $sales;
            $grand_collected   += $collected;
            $grand_uncollected += $uncollected;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(strtoupper(date("F Y", strtotime($month_key . '-01'))), '200', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($sales       != 0 ? number_format($sales,       2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($collected   != 0 ? number_format($collected,   2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($uncollected != 0 ? number_format($uncollected, 2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        // output per row
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '250', null, false, '2px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grand_sales,       2), '250', null, false, '2px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grand_collected,   2), '250', null, false, '2px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($grand_uncollected, 2), '250', null, false, '2px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class