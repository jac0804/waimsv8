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

class budget_report
{
  public $modulename = 'Budget Report';
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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dateid', 'costcenter', 'ddeptname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'ddeptname.label', 'Department');
    data_set($col2, 'costcenter.label', 'Item Group');
    data_set($col2, 'dateid.label', 'As of Date');
    data_set($col2, 'dateid.readonly', false);

    $fields = ['forex', 'radiolayoutformat'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'forex.readonly', false);
    data_set($col3, 'forex.required', true);
    data_set($col3, 'forex.label', 'Forex SGD');

    data_set($col3, 'radiolayoutformat.label', 'Type of Report');
    data_set(
      $col3,
      'radiolayoutformat.options',
      [
        ['label' => 'Per Department', 'value' => 'perdept'],
        ['label' => 'Per Item Group', 'value' => 'peritemgroup']
      ]
    );

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $paramstr = "select 'default' as print,
      left(now(),10) as dateid,
      0 as costcenterid,
      '' as code,
      '' as name,
      '' as costcenter,
      0 as deptid,
      '' as dept, 
      '' as deptname,
      '' as ddeptname,
      '' as forex,
      'perdept' as layoutformat";

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
    $result =  $this->reportDefaultLayout($config);
    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->defaultQuery($config);
    return $this->coreFunctions->opentable($query);
  }

  public function defaultQuery($config)
  {
    $asof = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $projcode = $config['params']['dataparams']['code'];
    $deptname = $config['params']['dataparams']['dept'];
    $year = date("Y", strtotime($asof));

    $filter = '';
    if ($projcode != '') {
      $projid = $config['params']['dataparams']['costcenterid'];
      $filter .= " and b.projectid = " . $projid . "";
    }

    if ($deptname != '') {
      $deptid = $config['params']['dataparams']['deptid'];
      $filter .= " and b.deptid = " . $deptid . "";
    }

    $join = '';
    $layoutformat = '';
    $name = '';
    $grp='';
    if ($config['params']['dataparams']['layoutformat'] == 'perdept') {
      $layoutformat = " and b.deptid <> 0";
      $name = "ifnull(dept.clientname, '') as name,";
      $grp = " dept.clientname";
      $join = " and d.deptid = b.deptid ";
    } else {
      $layoutformat = " and b.projectid <> 0";
      $name = "ifnull(p.name, '') as name,";
      $grp = " p.name";
      $join = " and d.projectid = b.projectid";
    }
                
                $query ="select c.acnoname, $name case
                when month('".$asof."') = 1 then amt1
                when month('".$asof."') = 2 then amt1 + amt2
                when month('".$asof."') = 3 then amt1 + amt2 + amt3
                when month('".$asof."') = 4 then amt1 + amt2 + amt3 + amt4
                when month('".$asof."') = 5 then amt1 + amt2 + amt3 + amt4 + amt5
                when month('".$asof."') = 6 then amt1 + amt2 + amt3 + amt4 + amt5 + amt6
                when month('".$asof."') = 7 then amt1 + amt2 + amt3 + amt4 + amt5 + amt6 + amt7
                when month('".$asof."') = 8 then amt1 + amt2 + amt3 + amt4 + amt5 + amt6 + amt7 + amt8
                when month('".$asof."') = 9 then amt1 + amt2 + amt3 + amt4 + amt5 + amt6 + amt7 + amt8 + amt9
                when month('".$asof."') = 10 then amt1 + amt2 + amt3 + amt4 + amt5 + amt6 + amt7 + amt8 + amt9 + amt10
                when month('".$asof."') = 11 then amt1 + amt2 + amt3 + amt4 + amt5 + amt6 + amt7 + amt8 + amt9 + amt10 + amt11
                when month('".$asof."') = 12 then amt1 + amt2 + amt3 + amt4 + amt5 + amt6 + amt7 + amt8 + amt9 + amt10 + amt11 + amt12 end as budget, ifnull(sum(d.db-d.cr), 0) as actual
                from budget as b
                left join (
                select detail.db,detail.cr,detail.deptid,detail.projectid,detail.acnoid from ladetail as detail
                left join lahead as head on head.trno = detail.trno
                where  year(head.dateid) = '".$year."' and head.dateid <= '".$asof."'  
                union all
                select detail.db,detail.cr ,detail.deptid,detail.projectid,detail.acnoid from gldetail as detail
                left join glhead as head on head.trno = detail.trno
                where  year(head.dateid) = '".$year."' and head.dateid <= '".$asof."'
                ) as d on  d.acnoid = b.acnoid  $join
                left join coa as c on c.acnoid = b.acnoid
                left join projectmasterfile as p on p.line = b.projectid
                left join client as dept on dept.clientid = b.deptid
                where  b.year ='".$year."' and b.total <>0  " . $layoutformat . " " . $filter . "
                group by c.acnoname,  $grp, b.amt1 , b.amt2 , b.amt3, b.amt4 , b.amt5 , b.amt6 ,b.amt7 , b.amt8 , b.amt9, b.amt10, b.amt11,b.amt12 order by name";

              $this->coreFunctions->LogConsole($query);
              return $query;
  }

  private function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);
    $forexsgd = $config['params']['dataparams']['forex'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $border = '1px solid ';
    $fontsize = '8';
    $fontsize10 = '10';
    $font = $this->companysetup->getrptfont($config['params']);
    $this->reporter->linecounter = 0;
    $str .= $this->reporter->beginreport();
    $name = '';

    $totalbudget = 0;
    $totalactual = 0;
    $totalvariance = 0;
    $totalbudgetsgd = 0;
    $totalactualsgd = 0;
    $totalvariancesgd = 0;
    $totalpercentage = 0;

    foreach ($result as $key => $data) {
      $budgetsdg = $data->budget / $forexsgd;
      $actualsdg = $data->actual / $forexsgd;
      $phpvariance = $data->budget - $data->actual;
      $sgdvariance = $phpvariance / $forexsgd;
      if($data->budget !=0){
        $percentage = ($phpvariance / $data->budget) * 100;
      }else{
        $percentage = 0;
      }
      

      if ($name != $data->name) {
        if ($name != '') {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('TOTAL: ', '200', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($this->defaultVal($totalbudget, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($this->defaultVal($totalbudgetsgd, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($this->defaultVal($totalactual, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($this->defaultVal($totalactualsgd, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($this->defaultVal($totalvariance, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($this->defaultVal($totalvariancesgd, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($this->defaultVal($totalpercentage, 2) . '%', '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }

        $name = $data->name;

        $padding = ($key > 0) ? '40px 0px 0px 0px' : '';
        $str .= $this->defaultHeader($config, $name, $padding, $key, $fontsize10);
        $str .= $this->defaultTableCols($this->reportParams['layoutSize'], $border, $font, $fontsize10);

        $totalbudget = 0;
        $totalactual = 0;
        $totalvariance = 0;
        $totalbudgetsgd = 0;
        $totalactualsgd = 0;
        $totalvariancesgd = 0;
        $totalpercentage = 0;
      }

      $totalbudget += $data->budget;
      $totalactual += $data->actual;
      $totalvariance = $totalbudget - $totalactual;
      $totalbudgetsgd += $budgetsdg;
      $totalactualsgd += $actualsdg;
      $totalvariancesgd += $sgdvariance;
    
      if($totalbudget !=0){
        $totalpercentage = ($totalvariance / $totalbudget) * 100;
      }else{
        $totalpercentage = 0;
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->acnoname, '200', null, false, $border, 'TBLR', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($this->defaultVal($data->budget, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($this->defaultVal($budgetsdg, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($this->defaultVal($data->actual, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($this->defaultVal($actualsdg, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($this->defaultVal($phpvariance, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($this->defaultVal($sgdvariance, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($percentage != 0) ? $this->defaultVal($percentage, 2) . '%' : '-', '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    if ($name != '') {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('TOTAL: ', '200', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($this->defaultVal($totalbudget, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($this->defaultVal($totalbudgetsgd, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($this->defaultVal($totalactual, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($this->defaultVal($totalactualsgd, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($this->defaultVal($totalvariance, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($this->defaultVal($totalvariancesgd, $decimal_currency), '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($this->defaultVal($totalpercentage, 2) . '%', '100', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function defaultHeader($config, $name, $padding, $key, $fontsize)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $str = '';
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    if ($key <= 0) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('BUDGET VS ACTUAL EXPENSES REPORT', null, null, false, '1px solid ', '', 'L', $font, '20', 'B', '', $padding);
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("(" . $name . ")", null, null, false, '1px solid ', '', 'L', $font, $fontsize, 'B', '', '30px 0px 10px 0px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function defaultTableCols($layoutsize, $border, $font, $fontsize)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0);
    $str .= $this->reporter->col('BUDGET', '200', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 2);
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ACTUAL', '200', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 2);
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VARIANCE', '300', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 3);
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EXPENSES', '200', null, false, $border, 'TBLR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount (Php)', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount (SGD)', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount (Php)', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount (SGD)', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount (Php)', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount (SGD)', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PERCENTAGE', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function defaultVal($val, $curr)
  {
    return $val == 0 ? '-' : number_format($val, $curr);
  }
}//end class