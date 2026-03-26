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

class sales_report_per_item_group_graph
{
  public $modulename = 'Sales Report Per Item Group Graph';
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
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as cur,
      '0' as posttype,
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
    $result = $this->reportDefault($config);
    $overall = json_decode(json_encode($this->coreFunctions->opentable($this->QUERY_for_overall($config))), true);

    $str = $this->header_DEFAULT($config);
    $tablestr = $this->layout_DEFAULT($config, $result, $overall);
    $graph = $this->report_graph($config, $result, $overall, $tablestr);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'graph' => $graph];
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
    $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 1;
    $indus = $config['params']['dataparams']['industry'];
    $proj = $config['params']['dataparams']['repitemgroup'];

    $filter = "";
    if ($indus != "") {
      $filter .= " and client.industry = '$indus'";
    }
    if ($proj != "") {
      $projid = $config['params']['dataparams']['projectid'];
      $filter .= " and stock.projectid=" . $projid;
    }

    $query = "select ifnull(a.igroup,'NO GROUP') as itemgroup, ifnull(sum(ext),0) as ext, sum(ext) as totaldollar,ifnull(a.projectid,0) as projectid
      from (
      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,prj.line as projectid
      from hsqhead as sohead
      left join hqshead as head on head.sotrno = sohead.trno
      left join hqsstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = head.agent
      left join client on client.client=head.client
      left join projectmasterfile as prj on prj.line = stock.projectid
      where date(sohead.dateid) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code,prj.line
      
      UNION ALL

      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,prj.line as projectid
      from hqshead as sohead
      left join hqtstock as stock on sohead.trno = stock.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = sohead.agent
      left join client on client.client=sohead.client
      left join projectmasterfile as prj on prj.line = stock.projectid
      where date(sohead.due) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code,prj.line
    ) as a
    group by a.igroup,a.projectid";

    return $query;
  }

  public function QUERY_for_overall($config)
  {
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype  = $config['params']['dataparams']['posttype'];
    $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 1;
    $indus = $config['params']['dataparams']['industry'];
    $project = $config['params']['dataparams']['repitemgroup'];

    $filter = "";
    if ($indus != "") {
      $filter .= " and client.industry = '$indus'";
    }
    if ($project != "") {
      $projid = $config['params']['dataparams']['projectid'];
      $filter .= " and stock.projectid=" . $projid;
    }

    $query = "select ifnull(sum(ext),0) as ext, sum(ext) as totaldollar,sum(amt) as target
      from (
      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,iq.amt
      from hsqhead as sohead
      left join hqshead as head on head.sotrno = sohead.trno
      left join hqsstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = head.agent
      left join client on client.client=head.client
      left join projectmasterfile as prj on prj.line = stock.projectid
      left join itemgroupqouta as iq on iq.projectid = prj.line and iq.yr = year(sohead.pdate)
      where date(sohead.pdate) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code,iq.amt
      
      UNION ALL
      
      select sum(stock.ext*(case $forex when 0 then stock.sgdrate else $forex end)) as ext,prj.code as igroup,iq.amt
      from hqshead as sohead
      left join hqtstock as stock on sohead.trno = stock.trno
      left join item as item on item.itemid = stock.itemid
      left join client as ag on ag.client = sohead.agent
      left join client on client.client=sohead.client
      left join projectmasterfile as prj on prj.line = stock.projectid
      left join itemgroupqouta as iq on iq.projectid = prj.line and iq.yr = year(sohead.pdate)
      where date(sohead.pdate) between '" . $start . "' and '" . $end . "' $filter
      group by prj.code,iq.amt
    ) as a";

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

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Industry: ' . $indus, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function layout_DEFAULT($config, $result, $result2)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date_create(date("Y-m-d", strtotime($config['params']['dataparams']['start'])));
    $end        = date_create(date("Y-m-d", strtotime($config['params']['dataparams']['end'])));
    $diff = ($start->diff($end)->m) + 1;

    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $str = '';
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $gtotal = 0;
    $gtarget = 0;
    $target = 0;
    $var = 0;

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM GROUP', '70', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ACTUAL', '50', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TARGET', '50', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('%', '50', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VARIANCE', '50', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    foreach ($result as $key => $data) {
      $gettarget = $this->coreFunctions->getfieldvalue("itemgroupqouta", "amt", "projectid = ? and yr = ?", [$data->projectid, date('Y', strtotime($config['params']['dataparams']['start']))]);
      $str .= $this->reporter->addline();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->itemgroup, '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->ext, $decimalprice), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $target = $gettarget * $diff;
      if (floatval($gettarget) != 0) {
        $str .= $this->reporter->col(number_format($target, $decimalprice), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format(($data->ext / $target) * 100, 2) . '%', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($target, '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->col(number_format($target - $data->ext, $decimalprice), '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $var += ($target - $data->ext);
      $gtarget += $target;
      $gtotal += $data->ext;
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtotal, $decimalprice), '50', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtarget, $decimalprice), '50', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($var, $decimalprice), '50', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';


    return $str;
  }

  private function report_graph($config, $data, $data2, $str)
  {

    $proj = [];
    $strprj = [];
    foreach ($data as $key => $value) {

      if ($value->ext != 0) {
        $proj[$key] = number_format(($value->ext / $data2[0]['ext']) * 100, 2) . '%';
      } else {
        $proj[$key] = number_format($value->ext, 2) . '%';
      }

      $strprj[$key] = $value->itemgroup;
    }
    $series = [['name' => 'itemgroup', 'data' => $proj]];
    $chartoption = [
      'chart' => ['type' => 'bar', 'height' => 500],
      'plotOptions' => ['bar' => ['horizontal' => false, 'columnWidth' => '45%', 'endingShape' => 'rounded']],
      'title' => ['text' => 'Sales Report Per Item Group Graph', 'align' => 'left', 'style' => ['color' => 'white']],
      'dataLabels' => ['enabled' => false],
      'stroke' => ['show' => true, 'width' => 2, 'color' => ['transparent']],
      'xaxis' => ['title' => ['text' => 'Item Group'], 'categories' => $strprj],
      'yaxis' => ['title' => ['text' => 'Sales %']],
      'fill' => ['opacity' => 1]
    ];
    return array('series' => $series, 'chartoption' => $chartoption, 'report' => $str);
  }
}//end class