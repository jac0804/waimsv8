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


class profit_sales_report
{
  public $modulename = 'Profit Sales Report';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname', 'ditemname','radiolayoutformat'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'dclientname.lookupclass', 'rcustomer');

    data_set(
      $col1,
      'radiolayoutformat.options',
      [
        ['label' => 'By Customer Report', 'value' => 'client'],
        ['label' => 'By Salesman Report', 'value' => 'agent'],
        ['label' => 'By Item Report', 'value' => 'item']
      ]
    );

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
      'client' as layoutformat
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

    $layout    = $config['params']['dataparams']['layoutformat'];

    switch ($layout) {
      case 'client':
        $result = $this->report_client_Layout($config);
        break;
      case 'agent':
        $result = $this->report_agent_Layout($config);
        break;
      
      default:
        $result = $this->report_item_Layout($config);
        break;
        
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];

    $layout    = $config['params']['dataparams']['layoutformat'];

    switch ($layout) {
      case 'client':
        $query = $this->client_query($config);
        break;
      case 'agent':
        $query = $this->agent_query($config);
        break;
      
      default:
        $query = $this->item_query($config);
        break;
        
    }
    return $this->coreFunctions->opentable($query);
  }

  
  public function client_query($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $groupid    = $config['params']['dataparams']['groupid'];
    $client    = $config['params']['dataparams']['client'];
    $agent    = $config['params']['dataparams']['agent'];
    $itemname    = $config['params']['dataparams']['itemname'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $filter = "";

    // if ($groupid != "") {
    //   $filter .= " and stockgrp.stockgrp_id='$groupid'";
    // }

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($itemname != "") {
      $filter .= " and item.itemid='$itemid'";
    }

    $query = "select client,clientname,dateid, docno, barcode, itemname, ext as sales, (cost * qty) as cost, 
    (ext-(cost*qty)) as profit,((ext / (cost * qty))*100)-100 as margin
    from (
      select client.client,client.clientname,date(head.dateid) as dateid, head.docno, ifnull(item.itemname,'') as itemname,
      sum(iss) as qty, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
      from lahead as head
      left join cntnum as num on num.trno=head.trno
      left join lastock as stock on head.trno = stock.trno
      left join item on item.itemid = stock.itemid
      left join client as agent on agent.client = head.agent
      left join client on client.client = head.client
      where num.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter
      group by client.client,client.clientname,date(head.dateid), head.docno, item.itemname, item.barcode
      union all
      select client.client,client.clientname,date(head.dateid) as dateid, head.docno, ifnull(item.itemname,'') as itemname,
      sum(iss) as qty, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
      from glhead as head
      left join cntnum as num on num.trno=head.trno
      left join glstock as stock on head.trno = stock.trno
      left join item on item.itemid = stock.itemid
      left join client as agent on agent.clientid = head.agentid
      left join client on client.clientid = head.clientid
      where num.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter 
    group by client.client,client.clientname,date(head.dateid), head.docno, item.itemname, item.barcode
    ) as x order by client,clientname,dateid,docno,barcode";

    return $query;
  }

  
  public function agent_query($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $groupid    = $config['params']['dataparams']['groupid'];
    $client    = $config['params']['dataparams']['client'];
    $agent    = $config['params']['dataparams']['agent'];
    $itemname    = $config['params']['dataparams']['itemname'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $filter = "";

    // if ($groupid != "") {
    //   $filter .= " and stockgrp.stockgrp_id='$groupid'";
    // }

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($itemname != "") {
      $filter .= " and item.itemid='$itemid'";
    }

    $query = "select agent,agentname,client,clientname,dateid, docno, barcode, itemname, ext as sales, (cost * qty) as cost, 
    (ext-(cost*qty)) as profit,((ext / (cost * qty))*100)-100 as margin
    from (
      select case when agent.client is null or agent.client = 0 then 'No Agent' else agent.client end as agent,
      case when agent.clientname is null or agent.clientname = '' then 'No Agent' else agent.clientname end as agentname,
      client.client,client.clientname,date(head.dateid) as dateid, head.docno, ifnull(item.itemname,'') as itemname,
      sum(iss) as qty, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
      from lahead as head
      left join cntnum as num on num.trno=head.trno
      left join lastock as stock on head.trno = stock.trno
      left join item on item.itemid = stock.itemid
      left join client as agent on agent.client = head.agent
      left join client on client.client = head.client
      where num.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter
      group by agent.client,agent.clientname,client.client,client.clientname,date(head.dateid), head.docno, item.itemname, item.barcode
      union all
      select case when agent.client is null or agent.client = 0 then 'No Agent' else agent.client end as agent,
      case when agent.clientname is null or agent.clientname = '' then 'No Agent' else agent.clientname end as agentname,
      client.client,client.clientname,date(head.dateid) as dateid, head.docno, ifnull(item.itemname,'') as itemname,
      sum(iss) as qty, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
      from glhead as head
      left join cntnum as num on num.trno=head.trno
      left join glstock as stock on head.trno = stock.trno
      left join item on item.itemid = stock.itemid
      left join client as agent on agent.clientid = head.agentid
      left join client on client.clientid = head.clientid
      where num.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter 
    group by agent.client,agent.clientname,client.client,client.clientname,date(head.dateid), head.docno, item.itemname, item.barcode
    ) as x order by agent,agentname,client,clientname,dateid,docno,barcode";

    return $query;
  }


  public function item_query($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    // $groupid    = $config['params']['dataparams']['groupid'];
    $agent    = $config['params']['dataparams']['agent'];
    $client    = $config['params']['dataparams']['client'];
    $itemname    = $config['params']['dataparams']['itemname'];
    $itemid    = $config['params']['dataparams']['itemid'];
    $filter = "";
    $itemdesc = " item.itemname ";

    // if ($groupid != "") {
    //   $filter .= " and stockgrp.stockgrp_id='$groupid'";
    // }

    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($itemname != "") {
      $filter .= " and item.itemid='$itemid'";
    }

    $query = "
      select dateid, docno, agentname,barcode, itemname, qty, uom, amt, disc, ext, (cost * qty) as cost, (amt-cost) as markup,  (((amt-cost)/amt) * 100) as markupper  from (
        select date(head.dateid) as dateid, head.docno, ifnull(agent.clientname,'') as agentname,  ifnull(".$itemdesc.",'') as itemname,
        sum(iss) as qty, stock.uom, sum(stock.amt) as amt, stock.disc, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
        from lahead as head
        left join cntnum as num on num.trno=head.trno
        left join lastock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
        left join client as agent on agent.client = head.agent
        left join client on client.client = head.client
        where num.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter
        group by date(head.dateid), head.docno, agent.clientname, item.itemname, stock.uom, stock.disc,item.barcode
        union all
        select date(head.dateid) as dateid, head.docno, ifnull(agent.clientname,'') as agentname,  ifnull(item.itemname,'') as itemname,
        sum(iss) as qty, stock.uom, sum(stock.amt) as amt, stock.disc, sum(stock.ext) as ext, sum(stock.cost) as cost,item.barcode
        from glhead as head
        left join cntnum as num on num.trno=head.trno
        left join glstock as stock on head.trno = stock.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
        left join client as agent on agent.clientid = head.agentid
        left join client on client.clientid = head.clientid
        where num.doc = 'SJ' and date(head.dateid) between '$start' and '$end' and stock.ext <> 0 $filter 
      group by date(head.dateid), head.docno, agent.clientname, item.itemname, stock.uom, stock.disc,item.barcode) as x order by dateid,docno,barcode
    ";

    return $query;
  }

  //CLIENT
    private function client_displayHeader($config)
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
      // $groupname  = $config['params']['dataparams']['stockgrp'];
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
      $str .= $this->reporter->col('SALES PROFIT BY CUSTOMER', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
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
      $str .= $this->reporter->col('Date', '150', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Document No.', '150', '', '', $border, 'TB', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Item Description', '200', '', '', $border, 'TB', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Sales', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Cost', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Profit', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('% Margin', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();

      return $str;
    }

    public function report_client_Layout($config)
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
      $str .= $this->client_displayHeader($config);


      $net = 0;
      $markup = 0;
      $client = '';

      $totalsales = 0;
      $totalcost = 0;
      $totalprofit = 0;
      $totalmargin = 0;

      
      $overallsales = 0;
      $overallcost = 0;
      $overallprofit = 0;
      $overallmargin = 0;
      foreach ($result as $key => $data) {

        
        if($client == '' || $client != $data->clientname){

          if($client != $data->clientname && $client != ''){
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', 'B', '', '', '');
            $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
            $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
            $str .= $this->reporter->col(number_format($totalsales, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            $str .= $this->reporter->col(number_format($totalcost, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            $str .= $this->reporter->col(number_format($totalprofit, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            $totalmargin = (($totalsales / $totalcost) * 100) - 100;
            $str .= $this->reporter->col(number_format($totalmargin, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            // ((ext / (cost * qty))*100)-100 as margin

            $str .= $this->reporter->endrow();

                            
            $overallsales += $totalsales;
            $overallcost += $totalcost;
            $overallprofit += $totalprofit;

            $totalsales = 0;
            $totalcost = 0;
            $totalprofit = 0;
            $totalmargin = 0;
          }
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<u>'.$data->clientname.'</u>', '150', null, false, '1px dotted ', '', 'L', $font, '12', 'B', '', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          
          $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col($data->docno, '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->sales, 2), '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->cost, 2), '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->profit, 2), '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->margin, 2). ' %', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->endrow();

          
        $totalsales += $data->sales;
        $totalcost += $data->cost;
        $totalprofit += $data->profit;
        
        $client = $data->clientname;

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->client_displayHeader($config);
          $page = $page + $count;
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($totalsales, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($totalcost, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($totalprofit, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      $totalmargin = (($totalsales / $totalcost) * 100) - 100;
      $str .= $this->reporter->col(number_format($totalmargin, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      // ((ext / (cost * qty))*100)-100 as margin

      $str .= $this->reporter->endrow();

                      
      $overallsales += $totalsales;
      $overallcost += $totalcost;
      $overallprofit += $totalprofit;

      $totalsales = 0;
      $totalcost = 0;
      $totalprofit = 0;
      $totalmargin = 0;
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, '1.5px solid', 'T', 'L', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1.5px solid', 'T', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col('', '200', null, false, '1.5px solid', 'T', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($overallsales, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($overallcost, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($overallprofit, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      $overallmargin = (($overallsales / $overallcost) * 100) - 100;
      $str .= $this->reporter->col(number_format($overallmargin, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      // ((ext / (cost * qty))*100)-100 as margin

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();

      return $str;
    }
  //CLIENT

  //AGENT
    private function agent_displayHeader($config)
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
      // $groupname  = $config['params']['dataparams']['stockgrp'];
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
      $str .= $this->reporter->col('SALES PROFIT BY AGENT', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
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
      $str .= $this->reporter->col('Date', '150', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Document No.', '150', '', '', $border, 'TB', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Item Description', '200', '', '', $border, 'TB', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Sales', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Cost', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('Profit', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('% Margin', '125', '', '', $border, 'TB', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();

      return $str;
    }

    public function report_agent_Layout($config)
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
      $str .= $this->agent_displayHeader($config);


      $net = 0;
      $markup = 0;
      $agent = '';
      $client = '';

      $totalsales = 0;
      $totalcost = 0;
      $totalprofit = 0;
      $totalmargin = 0;

      
      $overallsales = 0;
      $overallcost = 0;
      $overallprofit = 0;
      $overallmargin = 0;
      foreach ($result as $key => $data) {

        //appears at start
        if($agent == '' || $agent != $data->agentname){
        
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->agentname, '150', null, false, '1px dotted ', '', 'L', $font, '12', 'B', '', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');

        }

        //appears at the start of agent
        if($client == '' || $client != $data->clientname){

          //appears at end of agent-client
          if($client != $data->clientname && $client != ''){
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', 'B', '', '', '');
            $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
            $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
            $str .= $this->reporter->col(number_format($totalsales, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            $str .= $this->reporter->col(number_format($totalcost, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            $str .= $this->reporter->col(number_format($totalprofit, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            $totalmargin = (($totalsales / $totalcost) * 100) - 100;
            $str .= $this->reporter->col(number_format($totalmargin, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
            // ((ext / (cost * qty))*100)-100 as margin

            $str .= $this->reporter->endrow();

                            
            $overallsales += $totalsales;
            $overallcost += $totalcost;
            $overallprofit += $totalprofit;

            $totalsales = 0;
            $totalcost = 0;
            $totalprofit = 0;
            $totalmargin = 0;
          }
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
          $str .= $this->reporter->col($data->clientname, '150', null, false, '1px dotted ', '', 'L', $font, '12', 'B', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
          
          $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col($data->docno, '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->sales, 2), '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->cost, 2), '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->profit, 2), '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->col(number_format($data->margin, 2). ' %', '125', null, false, '1px dotted ', '', 'R', $font, '12', '', '', '', '');
        $str .= $this->reporter->endrow();

          
        $totalsales += $data->sales;
        $totalcost += $data->cost;
        $totalprofit += $data->profit;
        
        $agent = $data->agentname;
        $client = $data->clientname;

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->agent_displayHeader($config);
          $page = $page + $count;
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($totalsales, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($totalcost, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($totalprofit, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      $totalmargin = (($totalsales / $totalcost) * 100) - 100;
      $str .= $this->reporter->col(number_format($totalmargin, 2), '125', null, false, '1px dotted ', 'T', 'R', $font, '12', '', '', '', '');
      // ((ext / (cost * qty))*100)-100 as margin

      $str .= $this->reporter->endrow();

                      
      $overallsales += $totalsales;
      $overallcost += $totalcost;
      $overallprofit += $totalprofit;

      $totalsales = 0;
      $totalcost = 0;
      $totalprofit = 0;
      $totalmargin = 0;
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, '1.5px solid', 'T', 'L', $font, '12', 'B', '', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1.5px solid', 'T', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col('', '200', null, false, '1.5px solid', 'T', 'L', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($overallsales, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($overallcost, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      $str .= $this->reporter->col(number_format($overallprofit, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      $overallmargin = (($overallsales / $overallcost) * 100) - 100;
      $str .= $this->reporter->col(number_format($overallmargin, 2), '125', null, false, '1.5px solid', 'T', 'R', $font, '12', '', '', '', '');
      // ((ext / (cost * qty))*100)-100 as margin

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();

      return $str;
    }
  //CLIEN
  //AGENT

  //ITEM
    private function item_displayHeader($config)
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
      // $groupname  = $config['params']['dataparams']['stockgrp'];
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

    public function report_item_Layout($config)
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
      // $groupname  = $config['params']['dataparams']['stockgrp'];
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
      $str .= $this->item_displayHeader($config);


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
          $str .= $this->item_displayHeader($config);
          $page = $page + $count;
        }
      }
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();

      return $str;
    }
  //ITEM
}//end class