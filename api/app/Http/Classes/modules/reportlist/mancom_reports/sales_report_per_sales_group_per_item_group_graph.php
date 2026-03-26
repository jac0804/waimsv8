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

class sales_report_per_sales_group_per_item_group_graph
{
  public $modulename = 'Sales Report Per Sales Group Per Item Group Graph';
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
    $fields = ['radioprint', 'start', 'end', 'cur', 'industry', 'repitemgroup'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'cur.lookupclass', 'lookupcurrency');
    data_set($col1, 'industry.type', 'lookup');
    data_set($col1, 'industry.lookupclass', 'lookupindustry');
    data_set($col1, 'industry.action', 'lookupindustry');

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
      MAKEDATE(year(now()),1) as start,
      left(now(),10) as end,
      '' as cur,
      '0' as posttype,'' as industry,
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
    $result = $this->reportDefault($config);
    $overall = json_decode(json_encode($this->coreFunctions->opentable($this->QUERY_for_overall($config))), true);

    $str = $this->header_DEFAULT($config);
    $tablestr = $this->layout_DEFAULT($config, $result, $overall);
    $graph = $this->report_graph($config, $result, $overall, $tablestr);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'graph' => $graph, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
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

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype  = $config['params']['dataparams']['posttype'];
    $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 0;
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

    $query = "select sgroup, ifnull(a.igroup,'NO GROUP') as itemgroup, ifnull(sum(ext),0) as ext, sum(ext) as totaldollar,
    sgid,prjid,m
      from (
      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,sg.groupname as sgroup,
      timestampdiff(month, '$start', '$end' ) as m,sg.line as sgid,prj.line as prjid
      from hsqhead as sohead
      left join hqshead as head on head.sotrno = sohead.trno
      left join hqsstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = head.agent
      left join client on client.client=head.client
      left join salesgroup as sg on sg.line=ag.salesgroupid
      left join projectmasterfile as prj on prj.line = stock.projectid
      where date(sohead.pdate) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code, sg.groupname,m,sg.line,prj.line
      
      UNION ALL

      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,sg.groupname as sgroup, 
      timestampdiff(month, '$start', '$end' ) as m,sg.line as sgid,prj.line as prjid
      from hqshead as sohead
      left join hqtstock as stock on sohead.trno = stock.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = sohead.agent
      left join client on client.client=sohead.client
      left join salesgroup as sg on sg.line=ag.salesgroupid
      left join projectmasterfile as prj on prj.line = stock.projectid
      where date(sohead.pdate) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code, sg.groupname,  m,sg.line,prj.line
      ) as a where a.ext <> 0
      group by a.igroup, a.sgroup,m,sgid,prjid order by sgroup,itemgroup
      ";

    return $query;
  }

  public function QUERY_for_overall($config)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype  = $config['params']['dataparams']['posttype'];
    $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 0;
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

    $query = "select ifnull(a.igroup,'NO GROUP') as igroup, ifnull(sum(ext),0) as ext, sum(ext) as totaldollar,if(m=0,1,m) as m,prjid
      from (
      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,prj.line as prjid, timestampdiff(month, '$start', '$end' ) as m
      from hsqhead as sohead
      left join hqshead as head on head.sotrno = sohead.trno
      left join hqsstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = head.agent
      left join client on client.client=head.client
      left join salesgroup as sg on sg.line=ag.salesgroupid
      left join projectmasterfile as prj on prj.line = stock.projectid
      where stock.void <>1 and date(sohead.dateid) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code,prj.line

      UNION ALL

      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,prj.line as prjid, timestampdiff(month, '$start', '$end' ) as m
      from hqshead as sohead
      left join hqtstock as stock on sohead.trno = stock.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = sohead.agent
      left join client on client.client=sohead.client
      left join salesgroup as sg on sg.line=ag.salesgroupid
      left join projectmasterfile as prj on prj.line = stock.projectid
      where stock.void <>1 and  date(sohead.due) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code,prj.line
      ) as a where a.ext <> 0
      group by a.igroup,a.prjid,a.m";
      
    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $indus   = $config['params']['dataparams']['industry'];


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

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Industry: ' . $indus, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function layout_DEFAULT($config, $result, $result2)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    // $start      = date_create(date("Y-m-d", strtotime($config['params']['dataparams']['start'])));
    // $end        = date_create(date("Y-m-d", strtotime($config['params']['dataparams']['end'])));
    // $diff = ($start->diff($end)->m) + 1;
    // $project = $config['params']['dataparams']['projectid'];
    $yr = date('Y', strtotime($config['params']['dataparams']['start']));

    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $str = '';
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    // $gtotal = 0;
    // $gtarget = 0;
    $targetval = 0;
    $var = 0;

    $str .= '<br/>';

    $proj = [];
    $projx = [];
    $target = [];
    // $strprj = [];
    $strprjx = [];
    $sgroup = [];
    $var = [];
    // $amtx = [];
    // $series = [];

    foreach ($result as $key => $value) {
      $strprjx[$value->itemgroup] = $value->itemgroup;
      $sgroup[$value->sgroup] = $value->sgroup;
    }

    $layoutsize = (count($strprjx) * 400) + 800;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
    foreach ($strprjx as $key => $value) {
      $str .= $this->reporter->col($value, '400', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('TOTAL', '400', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES GROUP', '100', null, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '');
    foreach ($strprjx as $key => $value) {
      $str .= $this->reporter->col('AMT', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TARGET', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('%', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('VARIANCE', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('AMT', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TARGET', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('%', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('VARIANCE', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $totalsalesgrp = [];
    $totaltargetgrp = [];
    $totalvargrp = [];
    $totalpergrp = [];
    $totaltarget = 0;
    foreach ($result2 as $key2 => $value2) {
      $totaltarget = $this->coreFunctions->datareader("select ifnull(sum(sgq.amt),0) as value from salesgroupqouta as sgq left join client as c on c.clientid = sgq.agentid
          left join salesgroup as sg on sg.line = c.salesgroupid where sgq.yr = ? and sgq.projectid = ?", [$yr, $value2['prjid']]);

      $totalsalesgrp[$value2['igroup']] = $value2['ext'];
      $totaltargetgrp[$value2['igroup']] = $totaltarget * $value2['m'];
      $totalvargrp[$value2['igroup']] = $totaltarget - $value2['ext'];
      if ($totaltarget != 0) {
        $totalpergrp[$value2['igroup']] = ($value2['ext'] / $totaltarget) * 100;
      } else {
        $totalpergrp[$value2['igroup']] = 0;
      }
    }

    $sgrp = '';
    $totalactual = 0;
    $totalstarget = 0;
    $totalvar = 0;
    $totalpercent = 0;

    foreach ($result as $key => $value) {
      if ($key != 0) {
        if ($sgrp != $value->sgroup) {
          $totalactual = 0;
          $totalstarget = 0;
          $totalvar = 0;
          $totalpercent = 0;
        }
      }
      $targetval = $this->coreFunctions->datareader("select ifnull(sum(sgq.amt),0) as value from salesgroupqouta as sgq left join client as c on c.clientid = sgq.agentid
        left join salesgroup as sg on sg.line = c.salesgroupid where sgq.yr = ? and sgq.projectid = ? and sg.line =?", [$yr, $value->prjid, $value->sgid]);
      $targetval = $targetval * $value->m;

      if ($targetval != 0) {
        $proj[$value->sgroup][$value->itemgroup] = number_format(($value->ext / $targetval) * 100, 2) . '%';
        $projx[$value->sgroup][$value->itemgroup] = number_format(($value->ext), $decimalprice);
        $target[$value->sgroup][$value->itemgroup] = number_format(($targetval), $decimalprice);
        $var[$value->sgroup][$value->itemgroup] = number_format(($targetval - $value->ext), 2);

        $totalpercent = $totalpercent + ($value->ext / $targetval) * 100;
        $totalactual = $totalactual + ($value->ext);
        $totalstarget = $totalstarget + $targetval;
        $totalvar = $totalvar + ($targetval - $value->ext);

        $proj[$value->sgroup]['total'] = $totalpercent;
        $projx[$value->sgroup]['total'] = $totalactual;
        $target[$value->sgroup]['total'] = $totalstarget;
        $var[$value->sgroup]['total'] = $totalvar;
      } else {
        $proj[$value->sgroup][$value->itemgroup] = '-';
        $projx[$value->sgroup][$value->itemgroup] = number_format($value->ext, $decimalprice);
        $target[$value->sgroup][$value->itemgroup] = number_format($targetval, $decimalprice);
        $var[$value->sgroup][$value->itemgroup] = number_format(($targetval - $value->ext), 2);

        $totalactual = $totalactual + ($value->ext);
        $totalstarget = $totalstarget + $targetval;
        $totalvar = $totalvar + ($targetval - $value->ext);

        $proj[$value->sgroup]['total'] = $totalpercent;
        $projx[$value->sgroup]['total'] = $totalactual;
        $target[$value->sgroup]['total'] = $totalstarget;
        $var[$value->sgroup]['total'] = $totalvar;
      }

      $sgrp = $value->sgroup;
      $strprjx[$value->itemgroup] = $value->itemgroup;
      $sgroup[$value->sgroup] = $value->sgroup;
    }


    foreach ($sgroup as $key => $value) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value, '100', null, false, $border, 'BLR', 'C', $font, $fontsize, '', '', '');
      foreach ($strprjx as $keyx => $valuex) {
        if (isset($proj[$value][$valuex])) {
          $str .= $this->reporter->col($projx[$value][$valuex], '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '', '', 0, '', 1);
          $str .= $this->reporter->col($target[$value][$valuex], '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '', '', 0, '', 1);
          $str .= $this->reporter->col($proj[$value][$valuex], '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($var[$value][$valuex], '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', '', '', 0, '', 1);
        } else {
          $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('-', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
        }
      }
      $str .= $this->reporter->col(number_format($projx[$value]['total'], 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 1);
      $str .= $this->reporter->col(number_format($target[$value]['total'], 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 1);
      if ($target[$value]['total'] != 0) {
        $str .= $this->reporter->col(number_format(($projx[$value]['total'] / $target[$value]['total']) * 100, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
      } else {
        $str .= $this->reporter->col(0, '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
      }

      $str .= $this->reporter->col(number_format($var[$value]['total'], 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 1);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total ', '100', null, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '');

    $totalactual = 0;
    $totalstarget = 0;
    $totalvar = 0;
    $totalpercent = 0;
    foreach ($strprjx as $keyx => $valuex) {
      $str .= $this->reporter->col(number_format($totalsalesgrp[$valuex], $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totaltargetgrp[$valuex], $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalpergrp[$valuex], $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($totalvargrp[$valuex], $decimalprice), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');

      $totalactual = $totalactual + $totalsalesgrp[$valuex];
      $totalstarget = $totalstarget + $totaltargetgrp[$valuex];
      $totalvar = $totalvar + $totalvargrp[$valuex];
      $totalpercent = $totalpercent + $totalpergrp[$valuex];
    }
    $str .= $this->reporter->col(number_format($totalactual, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col(number_format($totalstarget, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->col($totalstarget != 0 ? number_format(($totalactual / $totalstarget) * 100, 2) : '0.00', '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvar, 2), '100', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 1);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '</br></br></br>';
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_graph($config, $data, $data2, $str)
  {
    $proj = [];
    $projx = [];
    $strprj = [];
    $strprjx = [];
    $sgroup = [];
    $sgx = [];
    $itmx = [];
    $series = [];
    $totalsalesgrp = [];
    $yr = date('Y', strtotime($config['params']['dataparams']['start']));

    foreach ($data2 as $key2 => $value2) {
      $totalsalesgrp[$value2['igroup']] = $value2['ext'];
    }

    foreach ($data as $key => $value) {
      $targetval = $this->coreFunctions->datareader("select ifnull(sum(sgq.amt),0) as value from salesgroupqouta as sgq left join client as c on c.clientid = sgq.agentid
      left join salesgroup as sg on sg.line = c.salesgroupid where sgq.yr = ? and  sgq.projectid = ? and sg.line =?", [$yr, $value->prjid, $value->sgid]);
      $targetval = $targetval * $value->m;

      if ($targetval != 0) {
        $proj[$value->sgroup][$value->itemgroup] = number_format(($value->ext / $targetval) * 100, 2) . '%';
        $projx[$key] = number_format(($value->ext / $targetval) * 100, 2) . '%';
      } else {
        $proj[$value->sgroup][$value->itemgroup] = '';
        $projx[$key] = '';
      }
      $strprjx[$value->itemgroup] = $value->itemgroup;
      $sgroup[$value->sgroup] = $value->sgroup;
    }

    foreach ($sgroup as $key => $value) {
      foreach ($strprjx as $keyx => $valuex) {
        if (isset($proj[$value][$valuex])) {
          $itmx[] = $proj[$value][$valuex];
        } else {
          $itmx[] = 0;
        }
      }
      $sgx['name'] = $key;
      $sgx['data'] = $itmx;
      array_push($series, $sgx);
      $itmx = [];
    }

    foreach ($strprjx as $key => $value) {
      array_push($strprj, $value);
    }
    $layoutsize = (count($strprjx) * 200) + 200;

    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500, 'width' => $layoutsize],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Sales Report Per Item Group Graph', 'align' => 'left', 'style' => ['color' => 'white']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['title' => ['text' => 'Sales Group'], 'categories' => $strprj],
      'yaxis' => ['title' => ['text' => 'Sales %']],
      'fill' => ['opacity' => 1]
    ];
    return array('series' => $series, 'chartoption' => $chartoption, 'report' => $str);
  }
}//end class