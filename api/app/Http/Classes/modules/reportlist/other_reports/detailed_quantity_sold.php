<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class detailed_quantity_sold
{
  public $modulename = 'Daily Sales';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname', 'brandname', 'radioposttype'];
    $col1 = $this->fieldClass->create($fields);

    data_set(
      $col1,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
     0 as clientid,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as agent,
    0 as agentid,
    '' as agentname,
    '' as dagentname,
    '' as brandname,
    '' as brand,
    '0' as posttype,
    0 as brandid
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
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $data = $this->reportDefault($config);

    return $this->reportDefaultLayout($config, $data);
  }

  public function reportDefault($config)
  {
    $center   = $config['params']['center'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
    $client   = $config['params']['dataparams']['client'];
    $clientid   = $config['params']['dataparams']['clientid'];
    $agent   = $config['params']['dataparams']['agent'];
    $agentid   = $config['params']['dataparams']['agentid'];
    $brandid   = $config['params']['dataparams']['brandid'];
    $brandname   = $config['params']['dataparams']['brandname'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    $joinclient = "";
    $postedfilter = "";
    $unpostedfilter = "";
    if ($client != "") {
      switch ($posttype) {
        case 0: //posted
          $filter .= " and head.clientid = '$clientid'"; //posted
          break;
        case 1: //unposted
          $joinclient = "left join client on client.client = head.client";
          $filter .= " and client.clientid = '$clientid'";
          break;
        case 2: //all
          $joinclient .= "left join client on client.client = head.client";
          $postedfilter .=  "and head.clientid= '" . $clientid . "' ";
          $unpostedfilter .= " and client.clientid = '" . $clientid . "'";
          break;
      }
    }

    if ($agent != "") {
      $filter .= " and ag.clientid = '$agentid'";
    }

    if ($brandname != "") {
      $filter .= " and item.brand = '$brandid'";
    }

    switch ($posttype) {
      case '0': // posted
        $query = "select year(head.dateid) as year, date_format(head.dateid, '%M') as dateid,
         head.clientname, head.docno, sum(stock.iss) as qty, item.itemname, stock.amt, brand.brand_desc as brandname, ag.clientname as agentname
        from glhead as head
        left join glstock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join client as ag on ag.clientid = head.agentid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        where head.doc = 'sj' and head.dateid between '$start' and '$end' $filter
        group by  year(head.dateid), date_format(head.dateid, '%M'), head.clientname, head.docno, item.itemname, stock.amt, brand.brand_desc, ag.clientname
        order by year, docno";
        break;
      case '1': // unposted
        $query = "select year(head.dateid) as year, date_format(head.dateid, '%M') as dateid, head.clientname, head.docno, sum(stock.iss) as qty, item.itemname, stock.amt, brand.brand_desc as brandname, ag.clientname as agentname
        from lahead as head
        left join lastock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join client as ag on ag.client = head.agent
        left join frontend_ebrands as brand on brand.brandid = item.brand
        $joinclient
        where head.doc = 'sj' and head.dateid between '$start' and '$end' $filter
        group by year(head.dateid), date_format(head.dateid, '%M'), head.clientname, head.docno, item.itemname, stock.amt, brand.brand_desc, ag.clientname
        order by year, docno";
        break;
      default: // all
        $query = "select year, dateid, clientname, docno, sum(qty) as qty, itemname, amt, brandname, agentname 
        from (
          select year(head.dateid) as year, date_format(head.dateid, '%M') as dateid, head.clientname, head.docno, sum(stock.iss) as qty, item.itemname, stock.amt, brand.brand_desc as brandname, ag.clientname as agentname
        from glhead as head
        left join glstock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join client as ag on ag.clientid = head.agentid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        where head.doc = 'sj' and head.dateid between '$start' and '$end' $filter $postedfilter
        group by  year(head.dateid), date_format(head.dateid, '%M'), head.clientname, head.docno, item.itemname, stock.amt, brand.brand_desc, ag.clientname
        union all
        select year(head.dateid) as year, date_format(head.dateid, '%M') as dateid, head.clientname, head.docno, sum(stock.iss) as qty, item.itemname, stock.amt, brand.brand_desc as brandname, ag.clientname as agentname
        from lahead as head
        left join lastock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join client as ag on ag.client = head.agent
        left join frontend_ebrands as brand on brand.brandid = item.brand
        $joinclient
        where head.doc = 'sj' and head.dateid between '$start' and '$end' $filter $unpostedfilter
        group by  year(head.dateid), date_format(head.dateid, '%M'), head.clientname, head.docno, item.itemname, stock.amt, brand.brand_desc, ag.clientname
        ) as x
        group by year, dateid, clientname, docno, itemname, amt, brandname, agentname
        order by year,  docno";
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config, $layoutsize)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $center   = $config['params']['center'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
    $client   = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $agent   = $config['params']['dataparams']['agent'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $brandid   = $config['params']['dataparams']['brandid'];
    $brandname   = $config['params']['dataparams']['brandname'];

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Detailed Quantity Sold', null, null, false, '1px solid ', '', 'C', $font, '17', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From ' . date('M d, Y', strtotime($start)) . ' To ' . date('M d, Y', strtotime($end)), null, null, false, '1px solid ', '', 'C', $font, '10', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>CUSTOMER : </b>' . ($clientname != '' ? $clientname : 'ALL'), null, null, false, '1px solid ', '', 'L', $font, '10', '');
    $str .= $this->reporter->col('<b>BRAND : </b>' . ($brandname != '' ? $brandname : 'ALL'), null, null, false, '1px solid ', '', 'L', $font, '10', '');
    $str .= $this->reporter->col('<b>AGENTNAME : </b>' . ($agentname != '' ? $agentname : 'ALL'), null, null, false, '1px solid ', '', 'L', $font, '10', '');
    $str .= $this->reporter->pagenumber('<b>Page</b>', null, null, false, '1px solid ', '', 'R', $font, '10', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '90', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('CLIENT', '220', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('DR/INVOICE #  ', '120', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('QTY', '80', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('PRODUCT', '250', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('PRICE', '80', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('TYPE', '80', null, false, '1px solid ', 'BT', 'L', $font, '10', 'B');
    $str .= $this->reporter->col('SALES REP', '80', null, false, '1px solid ', 'BT', 'R', $font, '10', 'B');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {
    $font = $this->companysetup->getrptfont($config['params']);
    $center   = $config['params']['center'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));;
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));;
    $client   = $config['params']['dataparams']['client'];
    $agent   = $config['params']['dataparams']['agent'];
    $brandid   = $config['params']['dataparams']['brandid'];
    $brandname   = $config['params']['dataparams']['brandname'];

    $count = 26;
    $page = 24;
    $str = '';
    $layoutsize = 1000;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config, $layoutsize, $result);
    $year = "";
    foreach ($result as $key => $data) {

      if ($year != $data->year) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data->year, '90', null, false, '1px solid ', '', 'L', $font, '10', 'B', '', '5px');
        $str .= $this->reporter->col("", '220', null, false, '1px solid ', '', 'L', $font, '10', '', '', '5px');
        $str .= $this->reporter->col("", '120', null, false, '1px solid ', '', 'L', $font, '10', '', '', '5px');
        $str .= $this->reporter->col("", '80', null, false, '1px solid ', '', 'L', $font, '10', '', '', '5px');
        $str .= $this->reporter->col("", '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '5px');
        $str .= $this->reporter->col("", '80', null, false, '1px solid ', '', 'L', $font, '10', '', '', '5px');
        $str .= $this->reporter->col("", '80', null, false, '1px solid ', '', 'L', $font, '10', '', '', '5px');
        $str .= $this->reporter->col("", '80', null, false, '1px solid ', '', 'R', $font, '10', '', '', '5px');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->dateid, '90', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->clientname, '220', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->docno, '120', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '80', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '250', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2), '80', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->brandname, '80', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->agentname, '80', null, false, '1px solid ', '', 'R', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $layoutsize, $result);
        $page = $page + $count;
      } //end if

      $year = $data->year;
    }

    $str .= $this->reporter->endtable($layoutsize);
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class