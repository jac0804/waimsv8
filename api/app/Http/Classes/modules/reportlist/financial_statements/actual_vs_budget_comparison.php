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

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $costCenter = $config['params']['dataparams']['costcenterid'];
        $option  = $config['params']['dataparams']['reporttype'];
        $filter = "";

        if ($costCenter != '') {
            $filter .= " and head.projectid = " . $costCenter . "";
        }

        if ($option == 2) {
            $cat = "('R','E')";
        } else {
            $cat = "('A','L','C')";
        }

        $query = "select acno, acnoname, sum(total) as total, sum(budget) as budget, sum(budget - total) as diff from (
        select c.acno, c.acnoname,
        sum(
        case when 1 between month('$start') and month('$end') then b.amt1 else 0 end +
        case when 2 between month('$start') and month('$end') then b.amt2 else 0 end +
        case when 3 between month('$start') and month('$end') then b.amt3 else 0 end +
        case when 4 between month('$start') and month('$end') then b.amt4 else 0 end +
        case when 5 between month('$start') and month('$end') then b.amt5 else 0 end +
        case when 6 between month('$start') and month('$end') then b.amt6 else 0 end +
        case when 7 between month('$start') and month('$end') then b.amt7 else 0 end +
        case when 8 between month('$start') and month('$end') then b.amt8 else 0 end +
        case when 9 between month('$start') and month('$end') then b.amt9 else 0 end +
        case when 10 between month('$start') and month('$end') then b.amt10 else 0 end +
        case when 11 between month('$start') and month('$end') then b.amt11 else 0 end +
        case when 12 between month('$start') and month('$end') then b.amt12 else 0 end
        ) as budget,
        sum(if(detail.db>0, detail.db, detail.cr)) as total
        from budget as b
        left join coa as c on c.acnoid = b.acnoid
        left join ladetail as detail on detail.acnoid = b.acnoid and year(detail.postdate) = b.year
        left join lahead as head on head.trno = detail.trno
        where date(detail.postdate) between '$start' and '$end' and c.cat in " . $cat . "  $filter
        group by c.acno, c.acnoname

        union all 

         select c.acno, c.acnoname,
        sum(
        case when 1 between month('$start') and month('$end') then b.amt1 else 0 end +
        case when 2 between month('$start') and month('$end') then b.amt2 else 0 end +
        case when 3 between month('$start') and month('$end') then b.amt3 else 0 end +
        case when 4 between month('$start') and month('$end') then b.amt4 else 0 end +
        case when 5 between month('$start') and month('$end') then b.amt5 else 0 end +
        case when 6 between month('$start') and month('$end') then b.amt6 else 0 end +
        case when 7 between month('$start') and month('$end') then b.amt7 else 0 end +
        case when 8 between month('$start') and month('$end') then b.amt8 else 0 end +
        case when 9 between month('$start') and month('$end') then b.amt9 else 0 end +
        case when 10 between month('$start') and month('$end') then b.amt10 else 0 end +
        case when 11 between month('$start') and month('$end') then b.amt11 else 0 end +
        case when 12 between month('$start') and month('$end') then b.amt12 else 0 end
        ) as budget,
        sum(if(detail.db>0, detail.db, detail.cr)) as total
        from budget as b
        left join coa as c on c.acnoid = b.acnoid
        left join gldetail as detail on detail.acnoid = b.acnoid and year(detail.postdate) = b.year
        left join glhead as head on head.trno = detail.trno
        where date(detail.postdate) between '$start' and '$end' and c.cat in " . $cat . "  $filter
        group by c.acno, c.acnoname

        ) as x
        group by x.acno, x.acnoname
        order by acnoname
        ";
        // var_dump($query);
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
        $fontsize = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->beginreport();
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, null, null, 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LIST OF ACCOUNTS BEYOND BUDGET', null, null, false, '10px solid ', '', 'C', $font, '12', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('' . $start . ' - ' . $end, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, 20, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($type == 1) {
            $str .= $this->reporter->col('Type: Balance Sheet', null, null, false, '3px solid', '', 'L', $font, '12', 'B', '', '');
        } else {
            $str .= $this->reporter->col('Type: Profit & Loss', null, null, false, '3px solid', '', 'L', $font, '12', 'B', '', '');
        }
        if ($costCenter != '') {
            $str .= $this->reporter->col('Cost Center: ' . $costCenter, null, null, false, '3px solid', '', 'L', $font, '12', '', '', '');
        } else {
            $str .= $this->reporter->col('Cost Center: All', null, null, false, '3px solid', '', 'L', $font, '12', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Code', '200', null, false, '1px dashed', 'BT', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Account Name', '200', null, false, '1px dashed', 'BT', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('For The Period', '200', null, false, '1px dashed', 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Period Budget', '200', null, false, '1px dashed', 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Difference', '200', null, false, '1px dashed', 'TB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    public function reportDefault_Layout($config)
    {
        $result = $this->reportDefault_query($config);
        $str = $this->displayHeader($config);
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";
        foreach ($result as $row) {
            $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->acno, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->acnoname, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($row->total, 2), '200', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($row->budget, 2), '200', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($row->diff, 2), '200', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class