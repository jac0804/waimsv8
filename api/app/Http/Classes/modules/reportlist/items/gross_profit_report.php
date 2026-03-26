<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class gross_profit_report
{
  public $modulename = 'Gross Profit Report';
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
    $fields = ['radioprint', 'start', 'end', 'project', 'agentname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'agentname.label', 'Sales Person');
    data_set($col1, 'project.required', false);
    data_set($col1, 'project.label', 'Item Group');


    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable(" select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as agentname,
        '' as agent,
        0 as agentid,
        '' as project,
        '' as projectid,
        '' as projectname
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $project = $config['params']['dataparams']['project'];
    $projectid = $config['params']['dataparams']['projectid'];

    $filter   = "";

    if ($agent != "") {
      $filter .= " and head.agentid = '$agentid'";
    }

    if ($project != "") {
      $filter .= " and item.projectid = '$projectid'";
    }

    $query = "select docno, yourref, clientname, postdate, barcode, itemname, itemgroup, brandname, ifnull(sum(gsales),0) as gsales,
    ifnull(sum(discount),0) as disc,ifnull(sum(sreturn),0) as sreturn, ifnull(sum(sales),0) as sales, ifnull(sum(cogs),0) as cogs, 
    ifnull(sum(qtysold),0) as qty
    from (select head.docno, head.yourref, head.clientname, date(cnt.postdate) as postdate, item.barcode, item.itemname, 
    ifnull(proj.name,'') as itemgroup, ifnull(brand.brand_desc,'') as brandname,
    sum(stock.isamt*stock.isqty) as gsales, sum((stock.isamt*stock.isqty)-stock.ext) as discount,
    0 as sreturn, sum(stock.ext) as sales, sum(stock.cost*stock.iss) as cogs, sum(stock.iss) as qtysold
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join item on item.itemid = stock.itemid
    left join projectmasterfile as proj on proj.line = item.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join cntnum as cnt on cnt.trno = head.trno
    left join iteminfo as info on info.line = stock.line
    where head.doc in ('sj','ai') and stock.isamt>0 and item.barcode<>'' and date(cnt.postdate) between '$start' and '$end' $filter
    group by head.docno, head.yourref, head.clientname, date(cnt.postdate), item.barcode, item.itemname, 
    proj.name, brand.brand_desc
    ) as x
    group by docno, yourref, clientname, postdate, barcode, itemname, itemgroup, brandname";

    return $this->coreFunctions->opentable($query);
  }


  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $font_size9 = '9';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agent = $config['params']['dataparams']['agent'];
    $agentname = $config['params']['dataparams']['agentname'];
    $project = $config['params']['dataparams']['project'];
    $projectname = $config['params']['dataparams']['projectname'];

    $str = '';
    $layoutsize = '1200';

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, $font_size, '', '');
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent Name : ' . ($agent != '' ? $agentname : 'ALL'), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Item Group : ' . ($project != '' ? $projectname : 'ALL'), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Invoice', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Customer PO', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Customer', '100', '', '', $border, 'TB', 'L', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Posting Date', '100', '', '', $border, 'TB', 'L', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Item Code', '100', '', '', $border, 'TB', 'L', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Item Name', '100', '', '', $border, 'TB', 'L', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Item Group', '100', '', '', $border, 'TB', 'L', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Brand', '100', '', '', $border, 'TB', 'L', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Qty', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Avg. Selling', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Avg. Buying', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Selling', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Buying', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Gross Profit', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->col('Gross profit %', '100', '', '', $border, 'TB', 'C', $font, $font_size9, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size9 = '9';
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $count = 33;
    $page = 34;
    $this->reporter->linecounter = 0;
    $result = $this->reportDefault($config);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1200';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);


    $gqty = $gselling = $gbuying = $gnet = $gcogs = $gprofitt =  0;

    foreach ($result as $key => $data) {

      $gsales = number_format($data->gsales, 2);
      $sales = number_format($data->sales, 2);
      $disc = number_format($data->disc, 2);
      $sreturn = number_format($data->sreturn, 2);
      $cogs = number_format($data->cogs, 2);

      $netsales = $data->sales - $data->sreturn;
      $grossprofit = $netsales - $data->cogs;
      if ($cogs != 0) {
        $marginvscost = ($grossprofit / $data->cogs) * 100;
      } else {
        $marginvscost = 0;
      }
      if ($netsales != 0) {
        $marginvssales = ($grossprofit / $netsales) * 100;
      } else {
        $marginvssales = 0;
      }
      if ($data->qty != 0) {
        $averageprice = $netsales / $data->qty;
      } else {
        $averageprice = 0;
      }
      if ($data->qty != 0) {
        $averagecost = $data->cogs / $data->qty;
      } else {
        $averagecost = 0;
      }

      $totp = $averageprice * $data->qty;
      $totc = $averagecost * $data->qty;
      $grossprofit = $totp - $totc;
      $pgrossprofit = ($grossprofit / $totp) * 100;

      $gqty += $data->qty;
      $gselling += $averageprice;
      $gbuying += $averagecost;
      $gnet += $totp;
      $gcogs += $totc;
      $gprofitt += $grossprofit;

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col($data->clientname, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col($data->postdate, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col($data->itemgroup, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col($data->brandname, '100', '', '', $border, '', 'L', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 0), '100', '', '', $border, '', 'C', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col(number_format($averageprice, $decimalprice), '100', '', '', $border, '', 'R', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col(number_format($averagecost, $decimalprice), '100', '', '', $border, '', 'R', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col(number_format($totp, $decimalprice), '100', '', '', $border, '', 'R', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col(number_format($totc, $decimalprice), '100', '', '', $border, '', 'R', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col(number_format($grossprofit, $decimalprice), '100', '', '', $border, '', 'R', $font, $font_size9, '', '', '');
      $str .= $this->reporter->col(number_format($pgrossprofit, 2), '100', '', '', $border, '', 'R', $font, $font_size9, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('GRAND  TOTAL', '100', '', '', $border, 'T', 'L', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col(number_format($gqty, 0), '100', '', '', $border, 'T', 'C', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col(number_format($gselling, $decimalprice), '100', '', '', $border, 'T', 'R', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col(number_format($gbuying, $decimalprice), '100', '', '', $border, 'T', 'R', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col(number_format($gnet, $decimalprice), '100', '', '', $border, 'T', 'R', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col(number_format($gcogs, $decimalprice), '100', '', '', $border, 'T', 'R', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col(number_format($gprofitt, $decimalprice), '100', '', '', $border, 'T', 'R', $font, $font_size9, '', '', '');
    $str .= $this->reporter->col('', '100', '', '', $border, 'T', 'R', $font, $font_size9, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class