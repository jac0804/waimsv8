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

class sales_per_item_group_per_sales_person_db
{
  public $modulename = 'Sales Per Item Group Per Sales Person';
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
    $fields = ['radioprint', 'start', 'end', 'cur'];
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
      adddate(left(now(),10),-60) as start,
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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
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

    $query = "
                    
          select 
        ifnull(a.igroup,'no item group') as igroup,
        ifnull(a.sperson,'no salesperson') as sperson,
        sum(a.ext) as ext, sum(a.ext) as totaldollar,
        (target * if(m = 0,1, m)) as target
        from (
          select sum(hqsstock.ext*hqsstock.sgdrate) as ext, ag.clientname as sperson,prj.code as igroup, 
                  sgq.amt as target, timestampdiff(month, '$start', '$end' )+1 as m
          from hsqhead as sohead
          left join hqshead as hqshead on hqshead.sotrno = sohead.trno
          left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
          left join item as item on item.itemid = hqsstock.itemid
          left join client as ag on ag.client = hqshead.agent
          left join client on client.client=hqshead.client
          left join projectmasterfile as prj on prj.line = hqsstock.projectid
          left join salesgroupqouta as sgq on sgq.projectid = prj.line and sgq.agentid = ag.clientid and year(sohead.pdate) = sgq.yr
          where  sohead.pdate between '$start' and '$end'
          group by ag.clientname,prj.code,sgq.amt
          union all
          select sum(hqtstock.ext*hqtstock.sgdrate) as ext, ag.clientname as sperson,prj.code as igroup, 
                  sgq.amt as target, timestampdiff(month, '$start', '$end' )+1 as m
          from hqshead as sohead
          left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
          left join item as item on item.itemid = hqtstock.itemid
          left join client as ag on ag.client = sohead.agent
          left join client on client.client=sohead.client
          left join projectmasterfile as prj on prj.line = hqtstock.projectid
          left join salesgroupqouta as sgq on sgq.projectid = prj.line and sgq.agentid = ag.clientid and year(sohead.pdate) = sgq.yr
           where  sohead.pdate between '$start' and '$end' 
          group by ag.clientname,prj.code,sgq.amt
        ) as a
        where ext is not null
        group by a.igroup,a.sperson,a.target,m
        order by a.igroup,a.sperson
        
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

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date : ' . $start . ' To ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
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
    $filter = "";

    $yr = date("Y");
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

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $query = "
      select 'YEAR TO DATE SALES PERFORMANCE' as sperson union all
       select 
        ifnull(a.sperson,'') as sperson
        from (
          select sum(hqsstock.ext*hqsstock.sgdrate) as ext, ag.clientname as sperson,prj.code as igroup, 
                  sgq.amt as target, timestampdiff(month, '$start', '$end' )+1 as m
          from hsqhead as sohead
          left join hqshead as hqshead on hqshead.sotrno = sohead.trno
          left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
          left join item as item on item.itemid = hqsstock.itemid
          left join client as ag on ag.client = hqshead.agent
          left join client on client.client=hqshead.client
          left join projectmasterfile as prj on prj.line = hqsstock.projectid
          left join salesgroupqouta as sgq on sgq.projectid = prj.line and sgq.agentid = ag.clientid and year(sohead.pdate) = sgq.yr
          where  sohead.pdate between '$start' and '$end'
          group by ag.clientname,prj.code,sgq.amt
          union all
          select sum(hqtstock.ext*hqtstock.sgdrate) as ext, ag.clientname as sperson,prj.code as igroup, 
                  sgq.amt as target, timestampdiff(month, '$start', '$end' )+1 as m
          from hqshead as sohead
          left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
          left join item as item on item.itemid = hqtstock.itemid
          left join client as ag on ag.client = sohead.agent
          left join client on client.client=sohead.client
          left join projectmasterfile as prj on prj.line = hqtstock.projectid
          left join salesgroupqouta as sgq on sgq.projectid = prj.line and sgq.agentid = ag.clientid and year(sohead.pdate) = sgq.yr
           where  sohead.pdate between '$start' and '$end' 
          group by ag.clientname,prj.code,sgq.amt
        ) as a
        where ext is not null
        group by sperson
        union all
        select '" . $yr . " CATA Team Revise' as sperson
      ";


    $cols =  $this->coreFunctions->opentable($query);

    $layoutsize = (count($cols)) * 300;
    $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => $layoutsize];
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $agents = [];
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow($layoutsize);
    foreach ($cols as $key => $colx) {
      $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, '14', 'B', '', '4px');
      $str .= $this->reporter->col($colx->sperson, '100', null, false, $border, 'T', 'C', $font, '12', 'B', '', '4px');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, '14', 'B', '', '4px');
      $agents[$colx->sperson] = 0;
    }
    $str .= $this->reporter->endrow();
    // next col

    $str .= $this->reporter->startrow($layoutsize);
    foreach ($cols as $key => $colx) {
      if ($key == 0) {
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, '14', 'B', '', '4px');
        $str .= $this->reporter->col('Item Group', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, '14', 'B', '', '4px');
      } elseif (count($cols) - 1 == $key) {
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, '14', 'B', '', '4px');
        $str .= $this->reporter->col('Total Target', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, '14', 'B', '', '4px');
      } else {
        $str .= $this->reporter->col('Target', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('Actual Sales', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('%', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $igroup = "";
    $sperson = "";
    $totaltarget = "";
    unset($cols[0]); // remove 1st col

    foreach ($result as $keyx => $value) {

      if ($igroup != $value->igroup) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow($layoutsize);
        $str .= $this->reporter->col($value->igroup, '300', null, false, $border, 'TLB', 'C', $font, '12', 'R', '', '4px');

        foreach ($cols as $key => $colx) {
          if ($sperson != $value->sperson || $igroup != $value->igroup) {

            $qry = "select ifnull(a.igroup,'no item group') as igroup,
                ifnull(a.sperson,'no salesperson') as sperson,
                sum(a.ext) as ext, sum(a.ext) as totaldollar,
                (target * if(m = 0,1, m)) as target
                from (
                  select sum(hqsstock.ext*hqsstock.sgdrate) as ext, ag.clientname as sperson,prj.code as igroup, 
                          sgq.amt as target, timestampdiff(month, '$start', '$end' )+1 as m
                  from hsqhead as sohead
                  left join hqshead as hqshead on hqshead.sotrno = sohead.trno
                  left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
                  left join item as item on item.itemid = hqsstock.itemid
                  left join client as ag on ag.client = hqshead.agent
                  left join client on client.client=hqshead.client
                  left join projectmasterfile as prj on prj.line = hqsstock.projectid
                  left join salesgroupqouta as sgq on sgq.projectid = prj.line and sgq.agentid = ag.clientid and year(sohead.pdate) = sgq.yr
                  where  sohead.pdate between '$start' and '$end'
                  group by ag.clientname,prj.code,sgq.amt
                  union all
                  select sum(hqtstock.ext*hqtstock.sgdrate) as ext, ag.clientname as sperson,prj.code as igroup, 
                          sgq.amt as target, timestampdiff(month, '$start', '$end' )+1 as m
                  from hqshead as sohead
                  left join hqtstock as hqtstock on sohead.trno = hqtstock.trno
                  left join item as item on item.itemid = hqtstock.itemid
                  left join client as ag on ag.client = sohead.agent
                  left join client on client.client=sohead.client
                  left join projectmasterfile as prj on prj.line = hqtstock.projectid
                  left join salesgroupqouta as sgq on sgq.projectid = prj.line and sgq.agentid = ag.clientid and year(sohead.pdate) = sgq.yr
                   where  sohead.pdate between '$start' and '$end' 
                  group by ag.clientname,prj.code,sgq.amt
                ) as a
                where ext is not null and sperson = '$colx->sperson' and igroup = '$value->igroup'
                group by a.igroup,a.sperson,a.target,m
                order by a.igroup,a.sperson
                
                ";
            $colx =  $this->coreFunctions->opentable($qry);

            if (!empty($colx)) {
              $str .= $this->reporter->col(number_format($colx[0]->target, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '4px');
              $str .= $this->reporter->col(($colx[0]->totaldollar != 0 ? number_format($colx[0]->totaldollar, 2) : '-'), '100', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '4px');
              if ($colx[0]->totaldollar == 0 || $colx[0]->target == 0) {
                $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
              } else {
                $str .= $this->reporter->col(number_format(($colx[0]->totaldollar / $colx[0]->target) * 100, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '4px');
              }
              $totaltarget += $colx[0]->target;
            } else {
              if (count($cols) == $key) {
                $str .= $this->reporter->col('', '100', null, false, $border, 'TLB', 'C', $font, '12', 'B', '', '4px');
                $str .= $this->reporter->col(number_format($totaltarget, 2), '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '', '4px');
                $str .= $this->reporter->col('', '100', null, false, $border, 'TRB', 'C', $font, '12', 'B', '', '4px');
                $totaltarget = 0;
              } else {
                $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
                $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
                $str .= $this->reporter->col('-', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
              }
            }
          }
        }

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endreport($layoutsize);
      }

      $igroup = $value->igroup;
      $sperson = $value->sperson;
      $agents[$value->sperson] += $value->target;
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow($layoutsize);

    $finaltotal = 0;
    foreach ($agents as $keyx => $totalcol) {
      if ($keyx == 'YEAR TO DATE SALES PERFORMANCE') {
        $str .= $this->reporter->col('TOTAL SALES - With Target', '200', null, false, $border, 'TLB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TRB', 'C', $font, '12', 'B', '', '4px');
      } elseif ($keyx == $yr . ' CATA Team Revise') {
        $str .= $this->reporter->col('', '100', null, false, $border, 'TLB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col(number_format($finaltotal, 2), '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '', '4px','', 0, '', 1);
        $str .= $this->reporter->col('', '100', null, false, $border, 'TRB', 'C', $font, '12', 'B', '', '4px');
      } else {
        $str .= $this->reporter->col(number_format($totalcol, 2), '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px', '', 0, '', 1);
        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '4px');
        $finaltotal += $totalcol;
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endreport($layoutsize);
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class