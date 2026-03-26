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

class sales_per_sales_group_per_person_db
{
  public $modulename = 'Sales Per Sales Group Per Person';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1500px;max-width:1500px;';
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
    $fields = ['radioprint', 'start', 'end', 'cur', 'industry', 'salesgroup'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
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
      'adddate(left(now(),10),-60)' as start,
      left(now(),10) as end,
      '' as year,
      '' as cur,
      '0' as posttype,'' as industry,
      '' as salesgroupid,'' as salesgroup
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
    return [];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout_SUMMARIZED($config);

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
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y") . "-01-01";
    $end        = date("Y-m-d");

    $filter = "";
    $query = "select ifnull(a.sgroup,'no group') as sgroup,
        ifnull(a.igroup,'no item group') as igroup,
        ifnull(a.sperson,'no salesperson') as sperson,
        sum(a.ext) as ext, sum(a.ext) as totaldollar,m,agentid
        from (
          select sum(hqsstock.ext*hqsstock.sgdrate) as ext,sg.groupname as sgroup,ag.clientname as sperson,prj.groupid as igroup, ag.clientid as agentid,timestampdiff(month, '$start', '$end' )+1 as m
          from hsqhead as sohead
          left join hqshead as hqshead on hqshead.sotrno = sohead.trno
          left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
          left join item as item on item.itemid = hqsstock.itemid
          left join client as ag on ag.client = hqshead.agent
          left join client on client.client=hqshead.client
          left join salesgroup as sg on sg.line=ag.salesgroupid
          left join projectmasterfile as prj on prj.line = hqsstock.projectid
          where hqsstock.void<>1 and date(sohead.dateid) between '$start' and '$end'  $filter
          group by sg.groupname,ag.clientname,prj.groupid, ag.clientid, m
          union all
          select sum(hqtstock.ext*hqtstock.sgdrate) as ext,sg.groupname as sgroup,ag.clientname as sperson,prj.groupid as igroup,ag.clientid as agentid,timestampdiff(month, '$start', '$end' )+1 as m
          from hqshead as sohead
          left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
          left join item as item on item.itemid = hqtstock.itemid
          left join client as ag on ag.client = sohead.agent
          left join client on client.client=sohead.client
          left join salesgroup as sg on sg.line=ag.salesgroupid
          left join projectmasterfile as prj on prj.line = hqtstock.projectid
           where hqtstock.void<>1 and date(sohead.due) between '$start' and '$end'  $filter
          group by sg.groupname,ag.clientname,prj.groupid, ag.clientid, m
        ) as a
        where ext is not null
        group by  a.sgroup,a.igroup,a.sperson,a.agentid,m
        order by  a.sgroup,a.sperson,a.igroup
        
        ";

    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y") . "-01-01";
    $end        = date("Y-m-d");

    $layoutsize = 1000;


    $str = '';
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
    $str .= $this->reporter->col('Date : ' . $start . ' To ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Industry: All', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';
    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y") . "-01-01";
    $end        = date("Y-m-d");
    $yr      = date("Y");

    $filter = "";



    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $dborder = "1px dashed ";

    $total = [];
    $gtotal = [];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $query = "
      select 'Sales Person' as igroup ,timestampdiff(month, '$start', '$end' ) as m,0 as sort
      union all       
      select p.groupid as igroup ,timestampdiff(month, '$start', '$end' ) as m, case p.groupid when 'others' then 100 else 1 end as sort
      from salesgroupqouta as s
      left join client as a on a.clientid = s.agentid
      left join salesgroup as sg on sg.line = a.salesgroupid
      left join projectmasterfile as p on p.line = s.projectid
      where s.yr = $yr and s.amt<>0 group by p.groupid
      union all
      select 'Total' as igroup,timestampdiff(month, '$start', '$end' ) as m,101 as sort
      order by sort,igroup";

    $cols =  $this->coreFunctions->opentable($query);

    $layoutsize = ((count($cols)) * 400);
    $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow($layoutsize);
    //header per ig
    foreach ($cols as $key => $colx) {
      $str .= $this->reporter->col($colx->igroup, '400', null, false, $border, 'TLR', 'C', $font, '14', 'B', '', '4px');
      $total[$colx->igroup]['actual'] = 0;
      $total[$colx->igroup]['target'] = 0;
      $total[$colx->igroup]['variance'] = 0;
      $total[$colx->igroup]['percent'] = 0;
      $gtotal[$colx->igroup]['actual'] = 0;
      $gtotal[$colx->igroup]['target'] = 0;
      $gtotal[$colx->igroup]['variance'] = 0;
      $gtotal[$colx->igroup]['percent'] = 0;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // next col header
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow($layoutsize);
    foreach ($cols as $key => $colx) {
      if ($key == 0) {
        $str .= $this->reporter->col('&nbsp', '100', null, false, $border, 'TLB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('&nbsp', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('&nbsp', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('&nbsp', '100', null, false, $border, 'TRB', 'C', $font, '12', 'B', '', '4px');
      } else {
        $str .= $this->reporter->col('Actual', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('Target', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('%', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('Variance +/-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $sgroup = "";
    $sperson = "";
    $totactual = 0;
    $tottarget = 0;
    $totpercent = 0;
    $totvar = 0;


    foreach ($result as $keyx => $value) {
      if ($sgroup != $value->sgroup) {
        //total per sgroup
        if ($keyx != 0) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow($layoutsize);
          $totactual = 0;
          $tottarget = 0;
          $totpercent = 0;
          $totvar = 0;

          foreach ($total as $x => $v) {
            if ($x == "Sales Person") {
              $str .= $this->reporter->col('Sub Total', 400, null, false, $border, 'TLRB', 'L', $font, '12', 'B', '', '4px');
            } else {
              $str .= $this->reporter->col(number_format($v['actual'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
              $str .= $this->reporter->col(number_format($v['target'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
              $str .= $this->reporter->col(number_format($v['percent'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
              $str .= $this->reporter->col(number_format($v['variance'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
              $totactual += $v['actual'];
              $tottarget += $v['target'];
              $totpercent += $v['percent'];
              $totvar += $v['variance'];
            }
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $total = [];
        foreach ($cols as $key => $colx) {
          $total[$colx->igroup]['actual'] = 0;
          $total[$colx->igroup]['target'] = 0;
          $total[$colx->igroup]['variance'] = 0;
          $total[$colx->igroup]['percent'] = 0;
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow($layoutsize);
        $str .= $this->reporter->col($value->sgroup, $layoutsize, null, false, $border, 'LR', 'L', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow($layoutsize);

      foreach ($cols as $key => $colx) {
        $qry = "select ifnull(sum(s.amt),0) as value from salesgroupqouta as s
        left join client as a on a.clientid = s.agentid left join salesgroup as sg on sg.line = a.salesgroupid
        left join projectmasterfile as p on p.line = s.projectid where s.yr = $yr and sg.groupname ='$value->sgroup' and p.groupid ='$colx->igroup' and a.clientid =$value->agentid and s.amt<>0 group by sg.groupname";

        $target = floatval($this->coreFunctions->datareader("select ifnull(sum(s.amt),0) as value from salesgroupqouta as s
        left join client as a on a.clientid = s.agentid left join salesgroup as sg on sg.line = a.salesgroupid
        left join projectmasterfile as p on p.line = s.projectid where s.yr = ? and sg.groupname =? and p.groupid =? and a.clientid =? and s.amt<>0 group by sg.groupname", [$yr, $value->sgroup, $colx->igroup, $value->agentid]));

        if ($sperson != $value->sperson || $sgroup != $value->sgroup) {
          if ($colx->igroup == 'Sales Person') {
            $totactual = 0;
            $tottarget = 0;
            $totpercent = 0;
            $totvar = 0;
            $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $value->sperson, '400', null, false, $border, 'TLB', 'L', $font, '12', '', '', '4px');
            $total[$colx->igroup]['actual'] += 0;
            $total[$colx->igroup]['target'] += 0;
            $total[$colx->igroup]['variance'] += 0;
            $total[$colx->igroup]['percent'] += 0;
          } elseif ($colx->igroup == 'Total') {
            $str .= $this->reporter->col(number_format($totactual, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
            $str .= $this->reporter->col(number_format($tottarget, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
            $str .= $this->reporter->col(number_format($totpercent, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
            $str .= $this->reporter->col(number_format($totvar, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
            $total[$colx->igroup]['actual'] += $totactual;
            $total[$colx->igroup]['target'] += $tottarget;
            $total[$colx->igroup]['variance'] += $totvar;
            $total[$colx->igroup]['percent'] += $totpercent;
            $gtotal[$colx->igroup]['actual'] += $totactual;
            $gtotal[$colx->igroup]['target'] += $tottarget;
            $gtotal[$colx->igroup]['variance'] += $totvar;
            $gtotal[$colx->igroup]['percent'] += $totpercent;
          } else {
            $qry = "select ifnull(a.sgroup,'no group') as sgroup,
            ifnull(a.igroup,'no item group') as igroup,
            ifnull(a.sperson,'no salesperson') as sperson,
            sum(a.ext) as ext, sum(a.ext) as totaldollar,m
            from (
              select sum(hqsstock.ext*hqsstock.sgdrate) as ext,sg.groupname as sgroup,ag.clientname as sperson,prj.groupid as igroup,timestampdiff(month, '$start', '$end')+1 as m
              from hsqhead as sohead
              left join hqshead as hqshead on hqshead.sotrno = sohead.trno
              left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
              left join item as item on item.itemid = hqsstock.itemid
              left join client as ag on ag.client = hqshead.agent
              left join client on client.client=hqshead.client
              left join salesgroup as sg on sg.line=ag.salesgroupid
              left join projectmasterfile as prj on prj.line = hqsstock.projectid
              where  hqsstock.void<>1 and date(sohead.dateid) between '$start' and '$end'  $filter
              group by sg.groupname,ag.clientname,prj.groupid, m
              union all
              select sum(hqtstock.ext*hqtstock.sgdrate) as ext,sg.groupname as sgroup,ag.clientname as sperson,prj.groupid as igroup, timestampdiff(month, '$start', '$end' )+1 as m
              from hqshead as sohead
              left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
              left join item as item on item.itemid = hqtstock.itemid
              left join client as ag on ag.client = sohead.agent
              left join client on client.client=sohead.client
              left join salesgroup as sg on sg.line=ag.salesgroupid
              left join projectmasterfile as prj on prj.line = hqtstock.projectid
               where hqtstock.void<>1 and date(sohead.due) between '$start' and '$end'  $filter
              group by sg.groupname,ag.clientname,prj.groupid, m
            ) as a
            where ext is not null and sgroup = '$value->sgroup' and igroup = '$colx->igroup' and sperson = '$value->sperson'
            group by a.sgroup,a.igroup,a.sperson,a.m";

            $colxx =  $this->coreFunctions->opentable($qry);
            $target = $target * $value->m;
            if (!empty($colxx)) {
              $str .= $this->reporter->col(($colxx[0]->totaldollar != 0 ? number_format($colxx[0]->totaldollar, 2) : '-'), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'R', '', '4px');
              $str .= $this->reporter->col(number_format($target, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'R', '', '4px');
              if ($colxx[0]->totaldollar == 0 || $target == 0) {
                $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'R', '', '');
              } else {
                $str .= $this->reporter->col(number_format(($colxx[0]->totaldollar / $target) * 100, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'R', '', '4px');
                $totpercent = $totpercent + (($colxx[0]->totaldollar / $target) * 100);
              }
              $str .= $this->reporter->col(number_format($target - $colxx[0]->totaldollar, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'R', '', '4px');

              $totactual = $totactual + $colxx[0]->totaldollar;
              $tottarget = $tottarget + $target;
              $totvar = $totvar + ($target - $colxx[0]->totaldollar);

              $total[$colxx[0]->igroup]['actual'] += $colxx[0]->totaldollar;
              $total[$colxx[0]->igroup]['target'] += $target;
              $total[$colxx[0]->igroup]['variance'] += $target - $colxx[0]->totaldollar;
              if ($target != 0) {
                $total[$colxx[0]->igroup]['percent'] += (($colxx[0]->totaldollar / $target) * 100);
                $gtotal[$colxx[0]->igroup]['percent']  += (($colxx[0]->totaldollar / $target) * 100);
              }

              $gtotal[$colxx[0]->igroup]['actual'] += $colxx[0]->totaldollar;
              $gtotal[$colxx[0]->igroup]['target'] += $target;
              $gtotal[$colxx[0]->igroup]['variance'] += $target - $colxx[0]->totaldollar;
            } else {
              $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
              $str .= $this->reporter->col(number_format($target, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '4px');
              $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
              $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');

              $tottarget = $tottarget + $target;

              $total[$colx->igroup]['actual'] += 0;
              $total[$colx->igroup]['target'] += $target;
              $total[$colx->igroup]['variance'] += 0;
              $total[$colx->igroup]['percent'] += 0;

              $gtotal[$colx->igroup]['actual'] += 0;
              $gtotal[$colx->igroup]['target'] += $target;
              $gtotal[$colx->igroup]['variance'] += 0;
              $gtotal[$colx->igroup]['percent'] += 0;
            }
          }
        }
      } //end cols ig

      $sgroup = $value->sgroup;
      $sperson = $value->sperson;
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } //end for result

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow($layoutsize);
    $totactual = 0;
    $tottarget = 0;
    $totpercent = 0;
    $totvar = 0;

    foreach ($total as $x => $v) {
      if ($x == "Sales Person") {
        $str .= $this->reporter->col('Sub Total', 400, null, false, $border, 'TLRB', 'L', $font, '12', 'B', '', '4px');
      } else {
        $str .= $this->reporter->col(number_format($v['actual'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col(number_format($v['target'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col(number_format($v['percent'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col(number_format($v['variance'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $totactual += $v['actual'];
        $tottarget += $v['target'];
        $totpercent += $v['percent'];
        $totvar += $v['variance'];
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //grand totals
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow($layoutsize);
    foreach ($gtotal as $m => $val) {

      if ($m == 'Sales Person') {
        $str .= $this->reporter->col('Grand Total', '400', null, false, $border, 'TLB', 'L', $font, '12', 'B', '', '4px');
      } else {
        $str .= $this->reporter->col(number_format($val['actual'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col(number_format($val['target'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col(number_format($val['percent'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col(number_format($val['variance'], 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class