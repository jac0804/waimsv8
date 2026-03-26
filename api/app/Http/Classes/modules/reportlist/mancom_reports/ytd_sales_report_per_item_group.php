<?php

namespace App\Http\Classes\modules\reportlist\mancom_reports;

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

class ytd_sales_report_per_item_group
{
  public $modulename = 'Sales Report Per Item Group (YTD)';
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
    $fields = ['radioprint', 'year', 'cur', 'industry', 'repitemgroup'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'cur.lookupclass', 'lookupcurrency');
    data_set($col1, 'industry.type', 'lookup');
    data_set($col1, 'industry.lookupclass', 'lookupindustry');
    data_set($col1, 'industry.action', 'lookupindustry');
    data_set($col1, 'year.type', 'lookup');
    data_set($col1, 'year.class', 'csyear sbccsreadonly');
    data_set($col1, 'year.lookupclass', 'lookupyear');
    data_set($col1, 'year.action', 'lookupyear');

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      year(now()) as year,
      '' as cur,
      '' as industry,
      '' as repitemgroup,
      0 as projectid
    ");
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
    $result = $this->reportDefault($config);

    $result = $this->layout_DEFAULT($config, $result);
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY

    $query = $this->default_QUERY($config);

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    $year  = $config['params']['dataparams']['year'];
    $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 1;
    $indus = $config['params']['dataparams']['industry'];
    $repitemgroup = $config['params']['dataparams']['repitemgroup'];

    $filter = "";
    if ($indus != "") {
      $filter .= " and client.industry = '$indus'";
    }
    if ($repitemgroup != "") {
      $projectid = $config['params']['dataparams']['projectid'];
      $filter .= " and stock.projectid=" . $projectid;
    }

    $query = "
      select ifnull(igroup,'no item group') as itemgroup,
      sum(mjan) as mjan,
      sum(mfeb) as mfeb,
      sum(mmar) as mmar,
      sum(mapr) as mapr,
      sum(mmay) as mmay,
      sum(mjun) as mjun,
      sum(mjul) as mjul,
      sum(maug) as maug,
      sum(msep) as msep,
      sum(moct) as moct,
      sum(mnov) as mnov,
      sum(mdec) as mdec,
      ifnull(a.projectid,0) as projectid, target
          from (
          select 
          (case when month(sohead.dateid)=1 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mjan,
          (case when month(sohead.dateid)=2 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mfeb,
          (case when month(sohead.dateid)=3 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mmar,
          (case when month(sohead.dateid)=4 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mapr,
          (case when month(sohead.dateid)=5 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mmay,
          (case when month(sohead.dateid)=6 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mjun,
          (case when month(sohead.dateid)=7 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mjul,
          (case when month(sohead.dateid)=8 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as maug,
          (case when month(sohead.dateid)=9 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as msep,
          (case when month(sohead.dateid)=10 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as moct,
          (case when month(sohead.dateid)=11 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mnov,
          (case when month(sohead.dateid)=12 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mdec,
          prj.code as igroup,prj.line as projectid, igq.amt as target
          from hsqhead as sohead
          left join hqshead as head on head.sotrno = sohead.trno
          left join hqsstock as stock on stock.trno = head.trno
          left join item as item on item.itemid = stock.itemid
          left join client as ag on ag.client = head.agent
          left join client on client.client=head.client
          left join projectmasterfile as prj on prj.line = stock.projectid
          left join itemgroupqouta igq on prj.line = igq.projectid and igq.yr = '$year'
          where stock.ext is not null and  year(sohead.dateid) = '$year' $filter
          group by prj.code,prj.line,sohead.dateid,target
          
          UNION ALL

          select
          (case when month(sohead.dateid)=1 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mjan,
          (case when month(sohead.dateid)=2 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mfeb,
          (case when month(sohead.dateid)=3 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mmar,
          (case when month(sohead.dateid)=4 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mapr,
          (case when month(sohead.dateid)=5 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mmay,
          (case when month(sohead.dateid)=6 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mjun,
          (case when month(sohead.dateid)=7 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mjul,
          (case when month(sohead.dateid)=8 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as maug,
          (case when month(sohead.dateid)=9 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as msep,
          (case when month(sohead.dateid)=10 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as moct,
          (case when month(sohead.dateid)=11 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mnov,
          (case when month(sohead.dateid)=12 then sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) else 0 end) as mdec,
          prj.code as igroup,prj.line as projectid, igq.amt as target
          from hqshead as sohead
          left join hqtstock as stock on sohead.trno = stock.trno
          left join item as item on item.itemid = stock.itemid
          left join client as ag on ag.client = sohead.agent
          left join client on client.client=sohead.client
          left join projectmasterfile as prj on prj.line = stock.projectid
          left join itemgroupqouta igq on prj.line = igq.projectid and igq.yr = '$year'
          where stock.ext is not null and  year(sohead.dateid) = '$year' $filter
          group by prj.code,prj.line,sohead.dateid,target
          ) as a 
          group by a.igroup,a.projectid,target";
          
    return $query;
  }


  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $indus   = $config['params']['dataparams']['industry'];
    $year   = $config['params']['dataparams']['year'];
    $indus   = $config['params']['dataparams']['industry'];
    $itemgroup   = $config['params']['dataparams']['repitemgroup'];

    if ($indus == "") {
      $indus = 'ALL';
    }
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
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Year : ' . $year, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Industry : ' . $indus, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Group : ' . ($itemgroup != '' ? $itemgroup : 'ALL'), '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function layout_DEFAULT($config, $result)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $year  = $config['params']['dataparams']['year'];
    // $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 1;
    // $indus = $config['params']['dataparams']['industry'];
    // $proj = $config['params']['dataparams']['projectid'];
    // $repitemgroup = $config['params']['dataparams']['repitemgroup'];

    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $str = '';
    $layoutsize = '5000';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->header_DEFAULT($config);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JANUARY', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('FEBRUARY', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('MARCH', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('APRIL', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('MAY', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JUNE', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JULY', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('AUGUST', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('SEPTEMBER', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('OCTOBER', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('NOVEMBER', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('DECEMBER', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM GROUP', '150', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    for ($i = 0; $i < 12; $i++) {
      $str .= $this->reporter->col('Actual', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('Target', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('%', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
      $str .= $this->reporter->col('Variance +/-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);


    foreach ($result as $key => $value) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->itemgroup, '150', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

      // january
      $str .= $this->reporter->col(number_format($value->mjan, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mjan != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mjan / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mjan, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // february
      $str .= $this->reporter->col(number_format($value->mfeb, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mfeb != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mfeb / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mfeb, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // march
      $str .= $this->reporter->col(number_format($value->mmar, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mmar != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mmar / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mmar, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // april
      $str .= $this->reporter->col(number_format($value->mapr, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mapr != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mapr / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mapr, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // may
      $str .= $this->reporter->col(number_format($value->mmay, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mmay != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mmay / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mmay, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // june
      $str .= $this->reporter->col(number_format($value->mjun, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mjun != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mjun / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mjun, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // jul
      $str .= $this->reporter->col(number_format($value->mjul, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mjul != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mjul / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mjul, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // august
      $str .= $this->reporter->col(number_format($value->maug, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->maug != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->maug / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->maug, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // september
      $str .= $this->reporter->col(number_format($value->msep, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->msep != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->msep / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->msep, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // october
      $str .= $this->reporter->col(number_format($value->moct, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->moct != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->moct / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->moct, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // november
      $str .= $this->reporter->col(number_format($value->mnov, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mnov != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mnov / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mnov, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

      // december
      $str .= $this->reporter->col(number_format($value->mdec, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col(number_format($value->target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      if ($value->mdec != 0 && $value->target != 0) {
        $str .= $this->reporter->col(number_format(($value->mdec / $value->target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
      }
      $str .= $this->reporter->col(number_format($value->target - $value->mdec, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');


      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class