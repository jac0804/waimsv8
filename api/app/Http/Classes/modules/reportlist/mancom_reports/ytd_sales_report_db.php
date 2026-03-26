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

class ytd_sales_report_db
{
  public $modulename = 'YTD Sales Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = true;
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
      '0' as projectid,
      0 as projectid
    ");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
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

  public function default_QUERY($config, $igrp = '')
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $year  =  date("Y");
    $forex     = 0;
    $filter = "";

    if ($igrp != "") {
      $filter .= " and prj.groupid = '$igrp'";
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
      sum(mdec) as mdec
          from (
          select 
          (case when month(sohead.dateid)=1 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mjan,
          (case when month(sohead.dateid)=2 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mfeb,
          (case when month(sohead.dateid)=3 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mmar,
          (case when month(sohead.dateid)=4 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mapr,
          (case when month(sohead.dateid)=5 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mmay,
          (case when month(sohead.dateid)=6 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mjun,
          (case when month(sohead.dateid)=7 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mjul,
          (case when month(sohead.dateid)=8 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as maug,
          (case when month(sohead.dateid)=9 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as msep,
          (case when month(sohead.dateid)=10 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as moct,
          (case when month(sohead.dateid)=11 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mnov,
          (case when month(sohead.dateid)=12 then sum(hqsstock.ext*(case $forex when 0 then hqsstock.sgdrate else $forex end)) else 0 end) as mdec,
          prj.groupid as igroup, igq.amt as target
          from hsqhead as sohead
          left join hqshead as hqshead on hqshead.sotrno = sohead.trno
          left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
          left join item as item on item.itemid = hqsstock.itemid
          left join client as ag on ag.client = hqshead.agent
          left join client on client.client=hqshead.client
          left join projectmasterfile as prj on prj.line = hqsstock.projectid
          left join itemgroupqouta igq on prj.line = igq.projectid and igq.yr = '$year'
          where hqsstock.void<>1 and hqsstock.ext is not null and  year(sohead.dateid) = '$year' $filter
          group by prj.groupid,sohead.dateid,target
          union all
          select
          (case when month(sohead.dateid)=1 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mjan,
          (case when month(sohead.dateid)=2 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mfeb,
          (case when month(sohead.dateid)=3 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mmar,
          (case when month(sohead.dateid)=4 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mapr,
          (case when month(sohead.dateid)=5 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mmay,
          (case when month(sohead.dateid)=6 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mjun,
          (case when month(sohead.dateid)=7 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mjul,
          (case when month(sohead.dateid)=8 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as maug,
          (case when month(sohead.dateid)=9 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as msep,
          (case when month(sohead.dateid)=10 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as moct,
          (case when month(sohead.dateid)=11 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mnov,
          (case when month(sohead.dateid)=12 then sum(hqtstock.ext*(case $forex when 0 then hqtstock.sgdrate else $forex end)) else 0 end) as mdec,
          prj.groupid as igroup,igq.amt as target
          from hqshead as sohead
          left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
          left join item as item on item.itemid = hqtstock.itemid
          left join client as ag on ag.client = sohead.agent
          left join client on client.client=sohead.client
          left join projectmasterfile as prj on prj.line = hqtstock.projectid
          left join itemgroupqouta igq on prj.line = igq.projectid and igq.yr = '$year'
          where hqtstock.void<>1 and hqtstock.ext is not null and  year(sohead.due) = '$year' $filter
          group by prj.groupid,sohead.dateid,target
          ) as a 
          group by a.igroup";

    return $query;
  }


  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $year  =  date("Y");


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
    $str .= $this->reporter->col('Industry : ALL', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Group : ALL', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function layout_DEFAULT($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $year  =  date("Y");
    $forex     = 0;

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

    $totvjan = 0;
    $totvfeb = 0;
    $totvmar = 0;
    $totvapr = 0;
    $totvmay = 0;
    $totvjun = 0;
    $totvjul = 0;
    $totvaug = 0;
    $totvsep = 0;
    $totvoct = 0;
    $totvnov = 0;
    $totvdec = 0;

    $totpjan = 0;
    $totpfeb = 0;
    $totpmar = 0;
    $totpapr = 0;
    $totpmay = 0;
    $totpjun = 0;
    $totpjul = 0;
    $totpaug = 0;
    $totpsep = 0;
    $totpoct = 0;
    $totpnov = 0;
    $totpdec = 0;

    $totalactual = 0;
    $totaltarget = 0;
    $totalvar = 0;
    $totalper = 0;

    $str = '';
    $layoutsize = '5350';
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
    $str .= $this->reporter->col('TOTAL', '400', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '4px');
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

    $str .= $this->reporter->col('Actual', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Target', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('%', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Variance +/-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $igrp = $this->coreFunctions->opentable("select p.groupid,sum(amt) as amt from itemgroupqouta as ig left join projectmasterfile as p on p.line = ig.projectid where p.groupid <> '' and  ig.yr = " . $year . " group by p.groupid");
    foreach ($igrp as $p => $v) {
      $target = $v->amt; //$this->coreFunctions->datareader("select SUM(i.amt) as value from itemgroupqouta as i left join projectmasterfile as p on p.line = i.projectid where i.yr =". $year ." and p.groupid ='".$value->itemgroup."'");
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($v->groupid, '150', null, false, $border, 'LRTB', 'L', $font, $fontsize, '', '', '4px');

      $result = $this->coreFunctions->opentable($this->default_QUERY($config, $v->groupid));
      if (!empty($result)) {
        foreach ($result as $key => $value) {
          // january
          $str .= $this->reporter->col(number_format($value->mjan, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totjan = $totjan + $value->mjan;
          $tottjan = $tottjan + $target;
          if ($value->mjan != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mjan / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpjan = $totpjan + (($value->mjan / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mjan, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvjan = $totvjan + ($target - $value->mjan);
          }


          // february
          $str .= $this->reporter->col(number_format($value->mfeb, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totfeb = $totfeb + $value->mfeb;
          $tottfeb = $tottfeb + $target;
          if ($value->mfeb != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mfeb / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpfeb = $totpfeb + (($value->mfeb / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mfeb, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvfeb = $totvfeb + ($target - $value->mfeb);
          }

          // march
          $str .= $this->reporter->col(number_format($value->mmar, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totmar = $totmar + $value->mmar;
          $tottmar = $tottmar + $target;
          if ($value->mmar != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mmar / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpmar = $totpmar + (($value->mmar / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mmar, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvmar = $totvmar + ($target - $value->mmar);
          }

          // april
          $str .= $this->reporter->col(number_format($value->mapr, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totapr = $totapr + $value->mapr;
          $tottapr = $tottapr + $target;
          if ($value->mapr != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mapr / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpapr = $totpapr + (($value->mapr / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }

          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mapr, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvapr = $totvapr + ($target - $value->mapr);
          }

          // may
          $str .= $this->reporter->col(number_format($value->mmay, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totmay = $totmay + $value->mmay;
          $tottmay = $tottmay + $target;
          if ($value->mmay != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mmay / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpmay = $totpmay + (($value->mmay / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mmay, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvmay = $totvmay + ($target - $value->mmay);
          }

          // june
          $str .= $this->reporter->col(number_format($value->mjun, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totjun = $totjun + $value->mjun;
          $tottjun = $tottjun + $target;
          if ($value->mjun != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mjun / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpjun = $totpjun + (($value->mjun / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mjun, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvjun = $totvjun + ($target - $value->mjun);
          }

          // jul
          $str .= $this->reporter->col(number_format($value->mjul, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totjul = $totjul + $value->mjul;
          $tottjul = $tottjul + $target;
          if ($value->mjul != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mjul / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpjul = $totpjul + (($value->mjul / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mjul, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvjul = $totvjul + ($target - $value->mjul);
          }

          // august
          $str .= $this->reporter->col(number_format($value->maug, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totaug = $totaug + $value->maug;
          $tottaug = $tottaug + $target;
          if ($value->maug != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->maug / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpaug = $totpaug + (($value->maug / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->maug, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvaug = $totvaug + ($target - $value->maug);
          }

          // september
          $str .= $this->reporter->col(number_format($value->msep, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totsep = $totsep + $value->msep;
          $tottsep = $tottsep + $target;
          if ($value->msep != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->msep / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpsep = $totpsep + (($value->msep / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->msep, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvsep = $totvsep + ($target - $value->msep);
          }

          // october
          $str .= $this->reporter->col(number_format($value->moct, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totoct = $totoct + $value->moct;
          $tottoct = $tottoct + $target;
          if ($value->moct != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->moct / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpoct = $totpoct + (($value->moct / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->moct, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvoct = $totvoct + ($target - $value->moct);
          }

          // november
          $str .= $this->reporter->col(number_format($value->mnov, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totnov = $totnov + $value->mnov;
          $tottnov = $tottnov + $target;
          if ($value->mnov != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mnov / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpnov = $totpnov + (($value->mnov / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mnov, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvnov = $totvnov + ($target - $value->mnov);
          }

          // december
          $str .= $this->reporter->col(number_format($value->mdec, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $totdec = $totdec + $value->mdec;
          $tottdec = $tottdec + $target;
          if ($value->mdec != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($value->mdec / $target) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totpdec = $totpdec + (($value->mdec / $target) * 100);
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format($target - $value->mdec, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
            $totvdec = $totvdec + ($target - $value->mdec);
          }

          // total per itemgroup

          $totalactual = $value->mjan + $value->mfeb + $value->mmar + $value->mapr + $value->mmay + $value->mjun + $value->mjul + $value->maug + $value->msep + $value->moct + $value->mnov + $value->mdec;
          $str .= $this->reporter->col(number_format($totalactual, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          $str .= $this->reporter->col(number_format($target * 12, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          if ($totalactual != 0 && $target != 0) {
            $str .= $this->reporter->col(number_format(($totalactual / ($target * 12)) * 100, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          if ($target == 0) {
            $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          } else {
            $str .= $this->reporter->col(number_format(($target * 12) - $totalactual, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
          }
          $totaltarget = $totaltarget + ($target * 12);
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');

        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col(number_format($target * 12, $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '4px');
        $totaltarget = $totaltarget + ($target * 12);
        $str .= $this->reporter->endrow();
      }
    }



    //totals monthly
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("TOTAL", '150', null, false, $border, 'LRTB', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totjan, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottjan, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpjan, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvjan, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totfeb, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottfeb, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpfeb, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvfeb, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totmar, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottmar, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpmar, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvmar, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totapr, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottapr, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpapr, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvapr, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totmay, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottmay, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpmay, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvmay, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totjun, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottjun, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpjun, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvjun, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totjul, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottjul, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpjul, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvjul, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totaug, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottaug, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpaug, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvaug, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totsep, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottsep, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpsep, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvsep, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totoct, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottoct, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpoct, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvoct, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totnov, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottnov, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpnov, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvnov, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col(number_format($totdec, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($tottdec, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totpdec, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totvdec, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $totalactual = $totjan + $totfeb + $totmar + $totapr + $totmay + $totjun + $totjul + $totaug + $totsep + $totoct + $totnov + $totdec;
    $str .= $this->reporter->col(number_format($totalactual, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($totaltarget, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    if($totaltarget == 0 || $totalactual == 0){
      $str .= $this->reporter->col('0', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    }else{
      $str .= $this->reporter->col(number_format(($totalactual / $totaltarget) * 100, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');
    }
   
    $str .= $this->reporter->col(number_format($totaltarget - $totalactual, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class