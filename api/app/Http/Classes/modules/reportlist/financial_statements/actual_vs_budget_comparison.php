<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;


class actual_vs_budget_comparison
{
    public $modulename = 'Actual vs Budget Comparison';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
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
        $fields = ['radioprint', 'start', 'end', 'radioreporttype', 'costcenter'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'costcenter.label', 'Cost Center');
        data_set($col1, 'radioreporttype.label', 'Type');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Balance Sheet', 'value' => '1', 'color' => 'red'],
            ['label' => 'Profit & Loss', 'value' => '2', 'color' => 'green']
        ]);
        data_set($col1, 'costcenter.required', true);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        date(now()) as end, 
        '' as code, '' as name, 0 as costcenterid, '' as costcenter,
        '" . $center . "' as center,
        '1' as reporttype,
        '" . $dcenter[0]->dcentername . "' as dcentername,
        '" . $dcenter[0]->name . "' as centername
        ";
        return $this->coreFunctions->opentable($paramstr);
    }


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

        return $this->reportDefault_Layout($config);
    }


    public function reportDefault_query($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $costCenter = $config['params']['dataparams']['costcenterid'];
        $option     = $config['params']['dataparams']['reporttype'];

        $startYear  = (int) date("Y", strtotime($start));
        $endYear    = (int) date("Y", strtotime($end));
        $ytdStart   = $startYear . "-01-01";
        $ytdEnd     = $end;

        if ($costCenter != '') {
            $projectFilter = "and b.projectid = $costCenter";
        } else {
            $projectFilter = "";
        }

        if ($option == 2) {
            $cat = "('R','E')";
        } else {
            $cat = "('A','L','C')";
        }

        $query = "select acno, acnoname, acnocode,
        sum(amt1) as amt1, sum(amt2) as amt2, sum(amt3) as amt3, sum(amt4) as amt4,
        sum(amt5) as amt5, sum(amt6) as amt6, sum(amt7) as amt7, sum(amt8) as amt8,
        sum(amt9) as amt9, sum(amt10) as amt10, sum(amt11) as amt11, sum(amt12) as amt12,
        sum(period_total) as period_total,
        sum(ytd_total) as ytd_total
        from (select c.acno, c.acnoname, c.acno as acnocode,
        ifnull(b.amt1,0) as amt1, ifnull(b.amt2,0) as amt2, ifnull(b.amt3,0) as amt3, ifnull(b.amt4,0) as amt4,
        ifnull(b.amt5,0) as amt5, ifnull(b.amt6,0) as amt6, ifnull(b.amt7,0) as amt7, ifnull(b.amt8,0) as amt8,
        ifnull(b.amt9,0) as amt9, ifnull(b.amt10,0) as amt10, ifnull(b.amt11,0) as amt11, ifnull(b.amt12,0) as amt12,
        sum(case when date(detail.postdate) between '$start' and '$end'
        then if(detail.db > 0, detail.db, detail.cr) else 0 end) as period_total,
        sum(case when date(detail.postdate) between '$ytdStart' and '$ytdEnd'
        then if(detail.db > 0, detail.db, detail.cr) else 0 end) as ytd_total
        from coa as c
        left join budget as b on b.acnoid = c.acnoid
        and b.year between $startYear and $endYear
        $projectFilter
        left join ladetail as detail on detail.acnoid = c.acnoid
        and date(detail.postdate) between '$ytdStart' and '$ytdEnd'
        where c.cat in $cat
        and c.acno not in (select distinct parent from coa)
        group by c.acno, c.acnoname,
        b.amt1, b.amt2, b.amt3, b.amt4, b.amt5, b.amt6,
        b.amt7, b.amt8, b.amt9, b.amt10, b.amt11, b.amt12
        union all
        select c.acno, c.acnoname, c.acno as acnocode,
        ifnull(b.amt1,0) as amt1, ifnull(b.amt2,0) as amt2, ifnull(b.amt3,0) as amt3, ifnull(b.amt4,0) as amt4,
        ifnull(b.amt5,0) as amt5, ifnull(b.amt6,0) as amt6, ifnull(b.amt7,0) as amt7, ifnull(b.amt8,0) as amt8,
        ifnull(b.amt9,0) as amt9, ifnull(b.amt10,0) as amt10, ifnull(b.amt11,0) as amt11, ifnull(b.amt12,0) as amt12,
        sum(case when date(detail.postdate) between '$start' and '$end'
        then if(detail.db > 0, detail.db, detail.cr) else 0 end) as period_total,
        sum(case when date(detail.postdate) between '$ytdStart' and '$ytdEnd'
        then if(detail.db > 0, detail.db, detail.cr) else 0 end) as ytd_total
        from coa as c
        left join budget as b on b.acnoid = c.acnoid
        and b.year between $startYear and $endYear
        $projectFilter
        left join gldetail as detail on detail.acnoid = c.acnoid
        and date(detail.postdate) between '$ytdStart' and '$ytdEnd'
        where c.cat in $cat
        and c.acno not in (select distinct parent from coa)
        group by c.acno, c.acnoname,
        b.amt1, b.amt2, b.amt3, b.amt4, b.amt5, b.amt6,
        b.amt7, b.amt8, b.amt9, b.amt10, b.amt11, b.amt12
        ) as x
        group by acno, acnoname, acnocode
        order by acno";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
        $end = date("m/d/Y", strtotime($config['params']['dataparams']['end']));
        $costCenter = $config['params']['dataparams']['name'];
        $type = $config['params']['dataparams']['reporttype'];

        $result = $this->reportDefault_query($config);
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "8";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, null, null, 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LIST OF ACCOUNTS BEYOND BUDGET', null, null, false, '10px solid ', '', 'C', $font, '10', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('' . $start . ' - ' . $end, null, null, false, '3px solid', '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($costCenter != '') {
            $str .= $this->reporter->col('Cost Center: ' . $costCenter, null, null, false, '3px solid', '', 'L', $font, '10', '', '', '');
        } else {
            $str .= $this->reporter->col('Cost Center: All', null, null, false, '3px solid', '', 'L', $font, '10', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($type == 1) {
            $str .= $this->reporter->col('Type: Balance Sheet', null, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        } else {
            $str .= $this->reporter->col('Type: Profit & Loss', null, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Code', '100', null, false, '1px dashed', 'BT', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Account Name', '270', null, false, '1px dashed', 'BT', 'LT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('For The Period', '105', null, false, '1px dashed', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Period Budget', '105', null, false, '1px dashed', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Difference', '105', null, false, '1px dashed', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('For The Year', '105', null, false, '1px dashed', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Year Budget', '105', null, false, '1px dashed', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Difference', '105', null, false, '1px dashed', 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $str = '';
        $result = $this->reportDefault_query($config);
        $font     = "Tahoma";
        $fontsize = "8";
        $border   = "1px solid ";
        $layoutsize = '1000';
        $linecounter = 0;
        $page = 65;
        $count = 65;

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $startMonth = (int) date("m", strtotime($start));
        $endMonth   = (int) date("m", strtotime($end));

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $totalPeriodActual = 0;
        $totalPeriodBudget = 0;
        $totalPeriodDiff   = 0;
        $totalYtdActual    = 0;
        $totalYtdBudget    = 0;
        $totalYtdDiff      = 0;

        foreach ($result as $row) {

            if ($linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config);
                $page += $count;
            }

            // Period budget: isasama lang ang mga buwan from start to end date (Feb-Apr = amt2 + amt3 + amt4)
            $periodBudget = 0;
            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $periodBudget += $row->{"amt$m"};
            }

            // YTD budget: isasama lahat ng buwan mula January hanggang end date (Jan-Apr = amt1 + amt2 + amt3 + amt4)
            $ytdBudget = 0;
            for ($m = 1; $m <= $endMonth; $m++) {
                $ytdBudget += $row->{"amt$m"};
            }

            // difference: kung ilan nalang ang natitira sa budget
            $periodDiff = $periodBudget - $row->period_total;
            $ytdDiff    = $ytdBudget    - $row->ytd_total;

            $totalPeriodActual += $row->period_total;
            $totalPeriodBudget += $periodBudget;
            $totalPeriodDiff   += $periodDiff;
            $totalYtdActual    += $row->ytd_total;
            $totalYtdBudget    += $ytdBudget;
            $totalYtdDiff      += $ytdDiff;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->acnocode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->acnoname, '270', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->period_total != 0 ? number_format($row->period_total, 2) : '-', '105', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($periodBudget != 0 ? number_format($periodBudget, 2) : '-', '105', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($periodDiff != 0 ? number_format($periodDiff, 2) : '-', '105', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->ytd_total != 0 ? number_format($row->ytd_total, 2) : '-', '105', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($ytdBudget != 0 ? number_format($ytdBudget, 2) : '-', '105', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($ytdDiff != 0 ? number_format($ytdDiff, 2) : '-', '105', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $linecounter++;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '1px dashed', 'B', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', '370', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalPeriodActual != 0 ? number_format($totalPeriodActual, 2) : '-', '105', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalPeriodBudget != 0 ? number_format($totalPeriodBudget, 2) : '-', '105', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalPeriodDiff != 0 ? number_format($totalPeriodDiff, 2) : '-', '105', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalYtdActual != 0 ? number_format($totalYtdActual, 2) : '-', '105', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalYtdBudget != 0 ? number_format($totalYtdBudget, 2) : '-', '105', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($totalYtdDiff != 0 ? number_format($totalYtdDiff, 2) : '-', '105', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class