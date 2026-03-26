<?php

namespace App\Http\Classes\modules\reportlist\customers;

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


class sales_report_with_markup
{
  public $modulename = 'Sales Report With Markup';
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
    $fields = ['radioprint', 'start', 'end', 'prepared', 'approved', 'dclientname', 'ditemname', 'dagentname', 'divsion'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'dclientname.lookupclass', 'rcustomer');

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1,  'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end,
      '' as groupid,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '' as ditemname,
      '' as itemname,
      0 as itemid,
      '' as barcode,
      '' as groupid,
      '' as stockgrp,
      '' as divsion,
      '' as agentid,
      '' as agentname,
      '' as dagentname,
      '' as agent,
      '' as prepared,
      '' as approved
      ";

    return $this->coreFunctions->opentable($paramstr);
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
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];

    $query = $this->default_query($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_query($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $groupid    = $config['params']['dataparams']['groupid'];
    $agent    = $config['params']['dataparams']['agent'];
    $client    = $config['params']['dataparams']['client'];
    $itemname    = $config['params']['dataparams']['itemname'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $filter = "";
    $itemdesc = " item.itemname ";

    if ($groupid != "") {
      $filter .= " and stockgrp.stockgrp_id='$groupid'";
    }

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($itemname != "") {
      $filter .= " and item.itemid='$itemid'";
    }

    if($companyid == 47){//kstar
      $itemdesc =" concat(item.itemname,' - ',item.sizeid,' - ',item.color) ";
    }
    

    $query = "
      select dateid, docno, agentname,barcode, itemname, qty, uom, amt, disc, ext, (cost * qty) as cost, (amt-cost) as markup,  (((amt-cost)/amt) * 100) as markupper  from (
        select date(head.dateid) as dateid, head.docno, ifnull(agent.clientname,'') as agentname,  ifnull(".$itemdesc.",'') as itemname,
        sum(iss) as qty, stock.uom, sum(stock.amt) as amt, stock.disc, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
        from lahead as head
        left join lastock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
        left join client as agent on agent.client = head.agent
        left join client on client.client = head.client
        where doc = 'sj' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter
        group by date(head.dateid), head.docno, agent.clientname, item.itemname, stock.uom, stock.disc,item.barcode
        union all
        select date(head.dateid) as dateid, head.docno, ifnull(agent.clientname,'') as agentname,  ifnull(item.itemname,'') as itemname,
        sum(iss) as qty, stock.uom, sum(stock.amt) as amt, stock.disc, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
        from glhead as head
        left join glstock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
        left join client as agent on agent.clientid = head.agentid
        left join client on client.clientid = head.clientid
        where doc = 'sj' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter 
      group by date(head.dateid), head.docno, agent.clientname, item.itemname, stock.uom, stock.disc,item.barcode) as x order by dateid,docno,barcode
    ";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];


    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES REPORT W/ MARK-UP', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Document No.', '120', '', '', $border, 'TB', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Agent', '100', '', '', $border, 'TB', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Item Description', '250', '', '', $border, 'TB', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('QTY', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('UOM', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Price', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Disc', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Total Amount', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Total Cost', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Mark-up%', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Total Mark-up Amount', '75', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];

    $count = 33;
    $page = 34;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);


    $net = 0;
    $markup = 0;
    foreach ($result as $key => $data) {


      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '100', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col($data->docno, '120', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col($data->agentname, '100', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '75', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col($data->uom, '75', null, false, '1px dotted ', '', 'C', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2), '75', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col($data->disc, '75', null, false, '1px dotted ', '', 'C', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($data->ext, 2), '75', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2), '75', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($data->markupper, 2) . ' %', '75', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($data->markup, 2), '75', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class