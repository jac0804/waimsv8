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

class daily_sales_collected_and_uncollected
{
    public $modulename = 'Daily Sales Collected and Uncollected';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
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

    public function report_default_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date('m/d/Y', strtotime($config['params']['dataparams']['start']));
        $end   = date('m/d/Y', strtotime($config['params']['dataparams']['end']));
        $pref = $config['params']['dataparams']['pref'];
        $reporttype = $config['params']['dataparams']['reporttype'];

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "14";
        $fontsizehead = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $reportLabel = '';
        if ($reporttype != '') {
            switch ($reporttype) {
                case 1:
                    $reportLabel = 'POSTED';
                    break;
                case 2:
                    $reportLabel = 'UNPOSTED';
                    break;
            }
        }

        //main header
        // if ($config['params']['dataparams']['dcentername'] == '') {
        //     $dcentername = '-';
        // }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        // $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        // $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY SALES COLLECTED & UNCOLLECTED REPORT (BY SALES INVOICE TYPE)', null, null, false, '2px solid', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($pref != '') {
            $str .= $this->reporter->col('Document Prefix: <b>' . $pref . '</b>', '500', null, false, '3px solid', '', 'L', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Document Prefix: ' . '<b>ALL</b>', '500', null, false, '3px solid', '', 'L', $font, $fontsize, '', '', '');
        }
        if ($reportLabel != '') {
            $str .= $this->reporter->col('Type of Report: <b>' . $reportLabel . '</b>', '500', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '');
        } else {
            $str .= $this->reporter->col('Type of Report: ' . '<b>ALL TRANSACTIONS</b>', '500', null, false, '3px solid', '', 'R', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: <b>' . $start . '</b> to <b>' . $end . '</b>', '700', null, false, '3px solid', '', 'L', $font, $fontsize, $fontsize, '', '');
        $str .= $this->reporter->pagenumber('Page', '300', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '10', false, '3px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL SALES', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL COLLECTED', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL UNCOLLECTED', '250', null, false, '3px solid', 'TB', 'c', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    // QUERY
    public function default_qry($config)
    {
        // $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $center = $config['params']['dataparams']['center'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $pref = $config['params']['dataparams']['pref'];
        $filter = '';
        $filter2 = '';

        if ($pref != '') {
            $filter .= " and cnum.bref = '$pref'";
        }

        switch ($reporttype) {
            case 1:
                $query = "select ar.dateid, sum(ar.db) as sales, sum(ar.db - ar.bal) as collected, sum(ar.bal) as uncollected
                from arledger as ar
                left join glhead as head on head.trno = ar.trno
                left join cntnum as cnum on cnum.trno = ar.trno
                where cnum.doc = 'SJ' 
                and ar.db > 0 
                and date(head.dateid) between '$start' and '$end' 
                and cnum.center = $center $filter
                group by  ar.dateid
                order by ar.dateid";
                break;
            case 2:
                $query = "select head.dateid, ifnull(sum(stock.ext),0) as sales, 0.0 as collected, ifnull(sum(stock.ext),0) as uncollected
                from lahead as head
                left join glstock as stock on stock.trno = head.trno
                left join cntnum as cnum on cnum.trno = head.trno
                where cnum.doc = 'SJ' 
                and date(head.dateid) between '$start' and '$end' 
                and cnum.center = $center $filter
                group by head.dateid
                order by head.dateid";
                break;
            default:
                $query = "select  dateid, sum(sales) as sales, sum(collected) as collected, sum(uncollected) as uncollected
                from (select 'Posted' as type,  ar.dateid, sum(ar.db) as sales, sum(ar.db - ar.bal) as collected, sum(ar.bal) as uncollected
                from arledger as ar
                left join glhead as head on head.trno = ar.trno
                left join cntnum as cnum on cnum.trno = ar.trno
                where cnum.doc = 'SJ' and ar.db > 0 and date(head.dateid) between '$start' and '$end' and cnum.center = $center
                $filter
                group by ar.dateid
                union all
                select 'Unposted' as type, head.dateid, ifnull(sum(stock.ext),0) as sales, 0.0 as collected, ifnull(sum(stock.ext),0) as uncollected
                from lahead as head
                left join glstock as stock on stock.trno = head.trno
                left join cntnum as cnum on cnum.trno = head.trno
                where cnum.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and cnum.center = $center
                $filter
                group by head.dateid
                ) as sales
                group by dateid
                order by dateid";
                break;
        }
        // var_dump($query);
        // $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        // return $data;
        return $this->coreFunctions->opentable($query);
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

        // Pagination settings
        $linesPerPage = 30;
        $rowCount = 0;
        $count = count($result);

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->report_default_header($config);

        $salesTotal = 0;
        $collectedTotal = 0;
        $uncollectedTotal = 0;

        for ($i = 0; $i < $count; $i++) {
            $data = $result[$i];
            $rowCount++;

            // Page break
            if ($rowCount > $linesPerPage) {
                $str .= $this->reporter->endreport();
                $str .= $this->reporter->beginreport($layoutsize);
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', null, '10', false, '3px solid', '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->report_default_header($config);
                $rowCount = 1;
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, '1px dotted', '',  'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(date("F d, Y (D)", strtotime($data->dateid)), '200', null, false, '1px dotted', '',  'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->sales       != 0 ? number_format($data->sales,       2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->collected   != 0 ? number_format($data->collected,   2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->uncollected != 0 ? number_format($data->uncollected, 2) : '-', '250', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $salesTotal += $data->sales;
            $collectedTotal += $data->collected;
            $uncollectedTotal += $data->uncollected;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '10', false, '3px solid', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL: ', '250', null, false, '3px solid', 'TB',  'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($salesTotal != 0 ? number_format($salesTotal, 2) : '-', '250', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($collectedTotal != 0 ? number_format($collectedTotal, 2) : '-', '250', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($uncollectedTotal != 0 ? number_format($uncollectedTotal, 2) : '-', '250', null, false, '3px solid', 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class