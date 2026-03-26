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

class sales_report_per_product
{
  public $modulename = 'Sales Report Per Product';
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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'start', 'end', 'cur', 'industry'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    if ($companyid == 12) { //afti usd
      data_set($col1, 'cur.label', 'USD to SGD Rate');
    } else {
      data_set($col1, 'cur.label', 'PHP to SGD Rate');
    }

    data_set($col1, 'cur.type', 'input');
    data_set($col1, 'cur.readonly', false);
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
      0 as cur,
      '0' as posttype,
      '' as industry
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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
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
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $posttype  = $config['params']['dataparams']['posttype'];
    $sgdrate = $config['params']['dataparams']['cur'];
    // $forex     = isset($config['params']['dataparams']['forex']) ? $config['params']['dataparams']['forex'] : 1;
    $indus = $config['params']['dataparams']['industry'];

    $filter = "";
    if ($indus != "") {
      $filter .= " and client.industry = '$indus'";
    }

    $query = "
      select prjname, sum(ext) as ext, sum(ext) as totaldollar
      from (
      select 'SO' as ttype, prj.name as prjname, case hqshead.cur when 'SGD' then sum((hqsstock.isqty-(hqsstock.voidqty/case ifnull(uom.factor,0) when 0 then 1 else uom.factor end))*(hqsstock.amt*(ifnull(uom.factor,1)))) else sum(((hqsstock.isqty-(hqsstock.voidqty/case ifnull(uom.factor,0) when 0 then 1 else uom.factor end))*(hqsstock.amt*(ifnull(uom.factor,1))))*(case  when " . $sgdrate . "= 0 then hqsstock.sgdrate else " . $sgdrate . " end)) end as ext
      from hsqhead as sohead
      left join hqshead as hqshead on hqshead.sotrno = sohead.trno
      left join hqsstock as hqsstock on hqsstock.trno = hqshead.trno
      left join item as item on item.itemid = hqsstock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = hqsstock.uom
      left join projectmasterfile as prj on prj.line = hqsstock.projectid
      left join client as ag on ag.client = hqshead.agent
      left join client on client.client=hqshead.client
      where date(sohead.dateid) between '" . $start . "' and '" . $end . "' $filter
      group by prj.name,hqshead.cur
      union all
      select 'SSO' as ttype, prj.name as prjname, case qshead.cur when 'SGD' then sum((hqtstock.isqty-(hqtstock.voidqty/case ifnull(uom.factor,0) when 0 then 1 else uom.factor end))*(hqtstock.amt*(ifnull(uom.factor,1)))) else sum(((hqtstock.isqty-(hqtstock.voidqty/case ifnull(uom.factor,0) when 0 then 1 else uom.factor end))*(hqtstock.amt*(ifnull(uom.factor,1))))*(case when " . $sgdrate . "= 0 then hqtstock.sgdrate else " . $sgdrate . " end)) end as ext
      from hqshead as qshead
      left join hqtstock as hqtstock on qshead.trno = hqtstock.trno
      left join hsrhead on hsrhead.qtrno = qshead.trno
      left join hsshead as sohead on sohead.trno = hsrhead.sotrno
      left join item as item on item.itemid = hqtstock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = hqtstock.uom
      left join projectmasterfile as prj on prj.line = hqtstock.projectid
      left join client as ag on ag.client = qshead.agent
      left join client on client.client=qshead.client
      where item.islabor = 1 and date(qshead.due) between '" . $start . "' and '" . $end . "' $filter
      group by prj.name,qshead.cur) as a
      group by prjname";
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
    $layoutsize = '1000';
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM GROUP', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SGD AMOUNT', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    // $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    // $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);

    // $gtotalext = 0;
    // $gtotalbal = 0;
    $gtotaldollar = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->prjname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->totaldollar, $decimalprice), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->header_DEFAULT($config);
        $page = $page + $count;
      } //end if

      $gtotaldollar += $data->totaldollar;
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL: ', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gtotaldollar, $decimalprice), '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class