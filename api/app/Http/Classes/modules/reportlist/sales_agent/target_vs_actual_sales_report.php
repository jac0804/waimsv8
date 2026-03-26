<?php

namespace App\Http\Classes\modules\reportlist\sales_agent;

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

class target_vs_actual_sales_report
{
  public $modulename = 'Target vs Actual Sales Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
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
    $fields = ['radioprint', 'year', 'dagentname', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'year.type', 'lookup');
    data_set($col1, 'year.class', 'csyear sbccsreadonly');
    data_set($col1, 'year.lookupclass', 'lookupyear');
    data_set($col1, 'year.action', 'lookupyear');

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {

    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      year(now()) as year,
      '' as dagentname,
      '' as agentname,
      '' as agent,
      0  as agentid
      ");
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
    $result = $this->reportDefault($config);

    $result = $this->layout_DEFAULT($config, $result);
    return $result;
  }

  public function reportDefault($config)
  {


    $query = $this->default_QUERY($config);

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filter = "";

    $year = $config['params']['dataparams']['year'];
    $agentname = $config['params']['dataparams']['agentname'];
    $agentid = $config['params']['dataparams']['agentid'];

    if ($agentname != "") {
      $filter .= " and agent.clientid = '" . $agentid . "' ";
    }

    $query = "
      select  a.agent, a.agentname,
      sum(a.mjan) as mjan,
      sum(a.mfeb) as mfeb,
      sum(a.mmar) as mmar,
      sum(a.mapr) as mapr,
      sum(a.mmay) as mmay,
      sum(a.mjun) as mjun,
      sum(a.mjul) as mjul,
      sum(a.maug) as maug,
      sum(a.msep) as msep,
      sum(a.moct) as moct,
      sum(a.mnov) as mnov,
      sum(a.mdec) as mdec,
      q.janamt,
      q.febamt,
      q.maramt,
      q.apramt,
      q.mayamt,
      q.junamt,
      q.julamt,
      q.augamt,
      q.sepamt,
      q.octamt,
      q.novamt,
      q.decamt
          from (
          select 
          (case when month(head.dateid)=1 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mjan,
          (case when month(head.dateid)=2 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mfeb,
          (case when month(head.dateid)=3 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mmar,
          (case when month(head.dateid)=4 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mapr,
          (case when month(head.dateid)=5 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mmay,
          (case when month(head.dateid)=6 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mjun,
          (case when month(head.dateid)=7 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mjul,
          (case when month(head.dateid)=8 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as maug,
          (case when month(head.dateid)=9 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as msep,
          (case when month(head.dateid)=10 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as moct,
          (case when month(head.dateid)=11 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mnov,
          (case when month(head.dateid)=12 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mdec,
          agent.client as agent, agent.clientname as agentname, agent.clientid as agentid
          from lahead as head
          left join lastock as stock on head.trno = stock.trno
          left join client as agent on agent.client = head.agent
          where stock.ext is not null and agent.client is not null  $filter
          group by agent.quota, agent.client, agent.clientname, head.dateid, agent.clientid
          union all
          select 
          (case when month(head.dateid)=1 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mjan,
          (case when month(head.dateid)=2 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mfeb,
          (case when month(head.dateid)=3 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mmar,
          (case when month(head.dateid)=4 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mapr,
          (case when month(head.dateid)=5 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mmay,
          (case when month(head.dateid)=6 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mjun,
          (case when month(head.dateid)=7 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mjul,
          (case when month(head.dateid)=8 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as maug,
          (case when month(head.dateid)=9 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as msep,
          (case when month(head.dateid)=10 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as moct,
          (case when month(head.dateid)=11 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mnov,
          (case when month(head.dateid)=12 and year(head.dateid) = '$year' then sum(stock.ext) else 0 end) as mdec,
          agent.client as agent, agent.clientname as agentname, agent.clientid as agentid
          from glhead as head
          left join glstock as stock on head.trno = stock.trno
          left join client as agent on agent.clientid = head.agentid
          where stock.ext is not null  and agent.client is not null  $filter
          group by agent.quota, agent.client, agent.clientname, head.dateid, agent.clientid
          ) as a 
          left join agentquota as q on q.clientid = a.agentid
          and q.yr = '$year'
          group by a.agent, a.agentname,
          q.janamt,
          q.febamt,
          q.maramt,
          q.apramt,
          q.mayamt,
          q.junamt,
          q.julamt,
          q.augamt,
          q.sepamt,
          q.octamt,
          q.novamt,
          q.decamt";

    return $query;
  }


  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $year = $config['params']['dataparams']['year'];
    $agentname = $config['params']['dataparams']['agentname'];

    $str = '';
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>YEAR : </b>' . $year, '500', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('<b>SALESMAN : </b>' . ($agentname != "" ? $agentname : "ALL "), '500', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function layout_DEFAULT($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $year = $config['params']['dataparams']['year'];
    $agentname = $config['params']['dataparams']['agentname'];

    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $totjan = 0;
    $totfeb = 0;
    $totmar = 0;
    $totapr = 0;
    $totmay = 0;
    $totjun = 0;
    $totjul = 0;
    $totaug = 0;
    $totsep = 0;
    $totoct = 0;
    $totnov = 0;
    $totdec = 0;

    $tottjan = 0;
    $tottfeb = 0;
    $tottmar = 0;
    $tottapr = 0;
    $tottmay = 0;
    $tottjun = 0;
    $tottjul = 0;
    $tottaug = 0;
    $tottsep = 0;
    $tottoct = 0;
    $tottnov = 0;
    $tottdec = 0;


    $totalquota = 0;
    $totalsales = 0;

    $gtotalquota = 0;
    $gtotalsales = 0;
    $str = '';
    $layoutsize = '2750';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->header_DEFAULT($config);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JANUARY', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('FEBRUARY', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('MARCH', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('APRIL', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('MAY', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JUNE', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JULY', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('AUGUST', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('SEPTEMBER', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('OCTOBER', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('NOVEMBER', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('DECEMBER', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES MAN', '150', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    for ($i = 0; $i < 13; $i++) {
      $str .= $this->reporter->col('Quota', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('Sales', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $value) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->agentname, '150', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // january
      $str .= $this->reporter->col(number_format($value->janamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mjan, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totjan += $value->mjan;
      $tottjan += $value->janamt;


      // february
      $str .= $this->reporter->col(number_format($value->febamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mfeb, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totfeb += $value->mfeb;
      $tottfeb += $value->febamt;

      // march
      $str .= $this->reporter->col(number_format($value->maramt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mmar, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totmar += $value->mmar;
      $tottmar += $value->maramt;

      // april
      $str .= $this->reporter->col(number_format($value->apramt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mapr, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totapr += $value->mapr;
      $tottapr += $value->apramt;

      // may
      $str .= $this->reporter->col(number_format($value->mayamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mmay, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totmay += $value->mmay;
      $tottmay += $value->mayamt;

      // june
      $str .= $this->reporter->col(number_format($value->junamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mjun, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totjun += $value->mjun;
      $tottjun += $value->junamt;

      // jul
      $str .= $this->reporter->col(number_format($value->julamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mjul, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totjul += $value->mjul;
      $tottjul += $value->julamt;

      // august
      $str .= $this->reporter->col(number_format($value->augamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->maug, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totaug += $value->maug;
      $tottaug += $value->augamt;

      // september
      $str .= $this->reporter->col(number_format($value->sepamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->msep, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totsep += $value->msep;
      $tottsep += $value->sepamt;

      // october
      $str .= $this->reporter->col(number_format($value->octamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->moct, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totoct += $value->moct;
      $tottoct += $value->octamt;

      // november
      $str .= $this->reporter->col(number_format($value->novamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mnov, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totnov += $value->mnov;
      $tottnov += $value->novamt;

      // december
      $str .= $this->reporter->col(number_format($value->decamt, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->mdec, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $totdec += $value->mdec;
      $tottdec += $value->decamt;

      // total per itemgroup

      $gtotalsales = $value->mjan + $value->mfeb + $value->mmar + $value->mapr + $value->mmay + $value->mjun + $value->mjul + $value->maug + $value->msep + $value->moct + $value->mnov + $value->mdec;
      $gtotalquota = $value->janamt + $value->febamt + $value->maramt + $value->apramt + $value->mayamt + $value->junamt + $value->julamt + $value->augamt + $value->sepamt + $value->octamt + $value->novamt + $value->decamt;

      $str .= $this->reporter->col(number_format($gtotalquota, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($gtotalsales, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->endrow();
    }

    //totals monthly
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("TOTAL", '150', null, false, $border, 'LRTB', 'L', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottjan, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totjan, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottfeb, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totfeb, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottmar, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totmar, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottapr, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totapr, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottmay, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totmay, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottjun, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totjun, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottjul, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totjul, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottaug, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totaug, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottsep, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totsep, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottoct, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totoct, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottnov, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totnov, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($tottdec, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totdec, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $totalquota = $tottjan + $tottfeb + $tottmar + $tottapr + $tottmay + $tottjun + $tottjul + $tottaug + $tottsep + $tottoct + $tottnov + $tottdec;
    $totalsales = $totjan + $totfeb + $totmar + $totapr + $totmay + $totjun + $totjul + $totaug + $totsep + $totoct + $totnov + $totdec;

    $str .= $this->reporter->col(number_format($totalquota, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totalsales, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
