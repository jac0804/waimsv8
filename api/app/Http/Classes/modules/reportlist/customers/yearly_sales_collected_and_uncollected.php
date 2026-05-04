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

class yearly_sales_collected_and_uncollected
{
    public $modulename = 'Yearly Sales Collected And Uncollected';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
        return $this->summary_per_service_layout($config);
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
        $filter_collect = '';

        if ($center != '') {
            $filter .= " and cnum.center = '$center'";
        }

        if ($pref != '') {
            $filter .= " and cnum.bref = '$pref'";
        }

        switch ($reporttype) {
            case 1:
                $query = "select 
                bref, 
                YEAR(dateid) as year, 
                sum(sales) as sales, 
                sum(collected) as collected, 
                sum(uncollected) as uncollected
            from (
                select 
                    'Posted' as type, 
                    cnum.bref, 
                    ar.dateid, 
                    sum(ar.db) as sales, 
                    sum(ar.db - ar.bal) as collected, 
                    sum(ar.bal) as uncollected
                from arledger as ar
                left join glhead as head on head.trno = ar.trno
                left join cntnum as cnum on cnum.trno = ar.trno
                where cnum.doc = 'SJ' and ar.db > 0 and date(head.dateid) between '$start' and '$end' $filter
                group by cnum.bref, ar.dateid
            ) as sales
            group by bref, YEAR(dateid)
            order by YEAR(dateid), bref";
                break;

            case 2:
                $query = "select
                    bref, 
                    YEAR(dateid) as year, 
                    sum(sales) as sales, 
                    sum(collected) as collected, 
                    sum(uncollected) as uncollected
                from (
                    select 
                        'Unposted' as type, 
                        cnum.bref, 
                        head.dateid, 
                        ifnull(sum(stock.ext),0) as sales, 
                        0.0 as collected, 
                        ifnull(sum(stock.ext),0) as uncollected
                    from lahead as head
                    left join glstock as stock on stock.trno = head.trno
                    left join cntnum as cnum on cnum.trno = head.trno
                    where cnum.doc = 'SJ' and date(head.dateid) between '$start' and '$end' $filter
                    group by cnum.bref, head.dateid
                ) as sales
                group by bref, YEAR(dateid)
                order by YEAR(dateid), bref";
                break;

            default:
                $query = "select
                bref, 
                year(dateid) as year,
                sum(sales) as sales, 
                sum(collected) as collected, 
                sum(uncollected) as uncollected
            from (
                select 
                    'Posted' as type, 
                    cnum.bref, 
                    ar.dateid,
                    sum(ar.db) as sales, 
                    sum(ar.db - ar.bal) as collected, 
                    sum(ar.bal) as uncollected
                from arledger as ar
                left join glhead as head on head.trno = ar.trno
                left join cntnum as cnum on cnum.trno = ar.trno
                where cnum.doc = 'SJ' and ar.db > 0 and date(head.dateid) between '$start' and '$end' $filter
                group by cnum.bref, ar.dateid

                union all

                select 
                    'Unposted' as type,
                    cnum.bref, 
                    head.dateid, 
                    ifnull(sum(stock.ext),0) as sales,
                    0.0 as collected, 
                    ifnull(sum(stock.ext),0) as uncollected
                from lahead as head
                left join glstock as stock on stock.trno = head.trno
                left join cntnum as cnum on cnum.trno = head.trno
                where cnum.doc = 'SJ'  and date(head.dateid) between '$start' and '$end' $filter
                group by cnum.bref, head.dateid
            ) as sales
            group by bref, year(dateid)
            order by year(dateid), bref";
                break;
        }

        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    public function report_default_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
        $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));
        $pref = $config['params']['dataparams']['pref'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        if ($reporttype == '0' || $reporttype == '') {
            $reporttypeText = 'ALL TRANSACTIONS';
        } elseif ($reporttype == '1') {
            $reporttypeText = 'POSTED';
        } elseif ($reporttype == '2') {
            $reporttypeText = 'UNPOSTED';
        } else {
            $reporttypeText = '';
        }

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "14";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $printDate = date('m/d/Y');



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('YEARLY SALES COLLECTED & UNCOLLECTED REPORT(BY SALES INVOICE TYPE)', null, null, false, '2px solid', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($pref != '') {
            $str .= $this->reporter->col('Document Prefix: <b>' . $pref . '</b>', '500', null, false, '3px solid', '', 'L', $font, $fontsize, '', '', '');
        } else {
            $prefQuery = "select distinct bref from cntnum where doc = 'SJ' and center = $center and bref != '' order by bref";
            $prefResult = json_decode(json_encode($this->coreFunctions->opentable($prefQuery)), true);
            $prefList = implode('/', array_column($prefResult, 'bref'));
            $str .= $this->reporter->col('Document Prefix: <b>' . $prefList . '</b>', null, null, false, '3px solid', '', 'L', $font, $fontsize, '', '', '');
        }
        if ($reporttype != '' && $reporttype != '0') {
            $str .= $this->reporter->col('Type of Report: <b>' . $reporttypeText . '</b>', '500', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Type of Report: ' . '<b>ALL TRANSACTIONS</b>', '500', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: <b>' . date('m/d/Y', strtotime($start)) . '</b> to <b>' . date('m/d/Y', strtotime($end)) . '</b>', '500', null, false, '3px solid', '', 'l', $font, $fontsize, '', '', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOC PREFIX', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL SALES', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL COLLECTED', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL UNCOLLECTED', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    public function summary_per_service_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "14";
        $this->reporter->linecounter = 0;

        $result = $this->default_qry($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $count = count($result);

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->report_default_header($config);


        $totalsales = 0;
        $totalCollected = 0;
        $totalUncollected = 0;

        foreach ($result as $data) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->bref, '250', null, false, '1px dotted', '',  'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->sales       != 0 ? number_format($data->sales,       2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->collected   != 0 ? number_format($data->collected,   2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->uncollected != 0 ? number_format($data->uncollected, 2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $totalsales += $data->sales;
            $totalCollected += $data->collected;
            $totalUncollected += $data->uncollected;
        }

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 15, false, '3px solid', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TOTAL', '250', null, false, '3px solid', 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalsales, 2), '250', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalCollected, 2), '250', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalUncollected, 2), '250', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class