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

class sales_report_item_history
{
  public $modulename = 'Sales Report Item History';
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

    $fields = [
      'radioprint', 'start', 'end'
    ];

    switch ($companyid) {
      case 32: //3M
        array_push($fields, 'area', 'brgy', 'dagentname', 'radioreporttype', 'sortby');
        $col1 = $this->fieldClass->create($fields);
        data_set(
          $col1,
          'radioreporttype.options',
          [
            ['label' => 'Detailed', 'value' => 'default', 'color' => 'blue'],
            ['label' => 'By Customer', 'value' => 'customer', 'color' => 'blue'],
            ['label' => 'By Item', 'value' => 'item', 'color' => 'blue']
          ]
        );
        data_set($col1, 'sortby.type', 'radio');
        data_set($col1, 'sortby.label', 'Sorting Option');
        data_set(
          $col1,
          'sortby.options',
          [
            ['label' => 'By Customer', 'value' => 'clientname', 'color' => 'blue'],
            ['label' => 'By Agent', 'value' => 'agentname', 'color' => 'blue'],
            ['label' => 'By Area', 'value' => 'area', 'color' => 'blue'],
            ['label' => 'By Highest Sales', 'value' => 'totalsales desc', 'color' => 'blue'],
            ['label' => 'By Highest Frequency', 'value' => 'frequency desc', 'color' => 'blue']
          ]
        );
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      adddate(left(now(),10),1) as end
     ";

    if ($companyid == 32) { //3M
      $paramstr .= ",'clientname' as sortby,'default' as reporttype, '' as agent, '' as agentname, '' as agentid, '' as area, '' as brgy";
    }
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
    switch ($companyid) {
      case 32: //3m
        $result = $this->mmm_Layout($config);
        break;

      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 32: //3m
        $qry = $this->mmm_query($config);
        break;
      default:
        $qry = $this->default_query($config);
        break;
    }
    return $this->coreFunctions->opentable($qry);
  }


  public function default_query($config)
  {
    // QUERY
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";

    $query = "
    select concat(left(head.docno,2),right(head.docno,6)) as docno,ifnull(concat(left(cm.docno,2),right(cm.docno,6)),'') as cmdocno,
    date(head.dateid) as shipdate,
    date(head.deldate) as deldate,ifnull(date(cm.returndate),'') as cmreturndate,
    agent.clientname as agentname,cm.agentname as cmagentname,
    head.clientname,cm.clientname as cmclientname,
    i.itemname,cm.itemname as cmitemname,
    stock.iss,cm.rrqty as cmrrqty,
    stock.isamt,cm.amt as cmamt,cust.area,cust.brgy
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client as agent on agent.client=head.agent
    left join client as cust on cust.client=head.client
    left join item as i on i.itemid=stock.itemid
    left join (
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from lahead as cmhead
      left join lastock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.client=cmhead.agent
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
      union all
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from glhead as cmhead
      left join glstock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.clientid=cmhead.agentid
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
    ) as cm on cm.refx=stock.trno and cm.linex=stock.line
    where head.doc in ('SJ') and date(head.deldate) between '$start' and '$end' $filter

    union all

    select concat(left(head.docno,2),right(head.docno,6)) as docno,ifnull(concat(left(cm.docno,2),right(cm.docno,6)),'') as cmdocno,
    date(head.dateid) as shipdate,
    date(head.deldate) as deldate,ifnull(date(cm.returndate),'') as cmreturndate,
    agent.clientname as agentname,cm.agentname as cmagentname,
    head.clientname,cm.clientname as cmclientname,
    i.itemname,cm.itemname as cmitemname,
    stock.iss,cm.rrqty as cmrrqty,
    stock.isamt,cm.amt as cmamt,cust.area,cust.brgy
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as agent on agent.clientid=head.agentid
    left join client as cust on cust.clientid=head.clientid
    left join item as i on i.itemid=stock.itemid
    left join (
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from lahead as cmhead
      left join lastock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.client=cmhead.agent
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
      union all
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from glhead as cmhead
      left join glstock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.clientid=cmhead.agentid
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
    ) as cm on cm.refx=stock.trno and cm.linex=stock.line
    where head.doc in ('SJ') and date(head.deldate) between '$start' and '$end' $filter
    
    ";

    return $query;
  }

  public function mmm_query($config)
  {
    // QUERY
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid         = $config['params']['dataparams']['agentid'];
    $agentname         = $config['params']['dataparams']['agentname'];
    $area         = $config['params']['dataparams']['area'];
    $brgy         = $config['params']['dataparams']['brgy'];
    $reporttype         = $config['params']['dataparams']['reporttype'];
    $sortby         = $config['params']['dataparams']['sortby'];

    $companyid = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    if ($agentname != "") {
      $filter = $filter . " and agent.clientid='$agentid'";
    }

    if ($area != "") {
      $filter = $filter . " and cust.area='$area'";
    }

    if ($brgy != "") {
      $filter = $filter . " and cust.brgy='$brgy'";
    }


    $query = "
    select concat(left(head.docno,2),right(head.docno,6)) as docno,ifnull(concat(left(cm.docno,2),right(cm.docno,6)),'') as cmdocno,
    date(head.dateid) as shipdate,
    date(head.deldate) as deldate,ifnull(date(cm.returndate),'') as cmreturndate,
    agent.clientname as agentname,cm.agentname as cmagentname,
    head.clientname,cm.clientname as cmclientname,
    i.itemname,cm.itemname as cmitemname,
    stock.iss,cm.rrqty as cmrrqty,
    stock.isamt,stock.ext,cm.amt as cmamt,cust.area,cust.brgy
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client as agent on agent.client=head.agent
    left join client as cust on cust.client=head.client
    left join item as i on i.itemid=stock.itemid
    left join (
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from lahead as cmhead
      left join lastock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.client=cmhead.agent
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
      union all
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from glhead as cmhead
      left join glstock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.clientid=cmhead.agentid
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
    ) as cm on cm.refx=stock.trno and cm.linex=stock.line
    where head.doc in ('SJ') and date(head.deldate) between '$start' and '$end' $filter

    union all

    select concat(left(head.docno,2),right(head.docno,6)) as docno,ifnull(concat(left(cm.docno,2),right(cm.docno,6)),'') as cmdocno,
    date(head.dateid) as shipdate,
    date(head.deldate) as deldate,ifnull(date(cm.returndate),'') as cmreturndate,
    agent.clientname as agentname,cm.agentname as cmagentname,
    head.clientname,cm.clientname as cmclientname,
    i.itemname,cm.itemname as cmitemname,
    stock.iss,cm.rrqty as cmrrqty,
    stock.isamt,stock.ext,cm.amt as cmamt,cust.area,cust.brgy
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as agent on agent.clientid=head.agentid
    left join client as cust on cust.clientid=head.clientid
    left join item as i on i.itemid=stock.itemid
    left join (
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from lahead as cmhead
      left join lastock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.client=cmhead.agent
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
      union all
      select cmhead.docno,cmstock.refx,cmstock.linex,
      cmhead.returndate,cmagent.clientname as agentname,cmhead.clientname,cmi.itemname,cmstock.rrqty,cmstock.amt
      from glhead as cmhead
      left join glstock as cmstock on cmstock.trno=cmhead.trno
      left join client as cmagent on cmagent.clientid=cmhead.agentid
      left join item as cmi on cmi.itemid=cmstock.itemid
      where cmhead.doc in ('CM') and date(cmhead.returndate) between '$start' and '$end'
    ) as cm on cm.refx=stock.trno and cm.linex=stock.line
    where head.doc in ('SJ') and date(head.deldate) between '$start' and '$end' $filter
    
    ";
    switch ($reporttype) {
      case 'customer':
        $query =
          "select a.clientname,a.brgy,a.area,a.agentname as salesrep,sum(a.ext) as totalsales,count(itemname) as frequency from(
          " . $query . "
          ) as a
          group by
          clientname,brgy,area,agentname,itemname
          order by $sortby
        ";
        return $query;
        break;
      case 'item':
        $query =
          "select a.clientname,a.brgy,a.area,a.agentname as salesrep,a.itemname,sum(a.iss) as qty,sum(a.ext) as totalsales,count(itemname) as frequency from(
          " . $query . "
          ) as a
          group by
          clientname,brgy,area,agentname,itemname
          order by $sortby
          ";
        return $query;
        break;

      default:
        switch ($sortby) {
          case 'totalsales desc':
          case 'frequency desc':
            $sortby = "clientname";
            break;
        }
        return "select * from (" . $query . ") as a order by $sortby";
        break;
    }
  }


  private function mmm_displayHeader($config)
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

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('SALES REPORT ITEM HISTORY', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function mmm_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $reporttype         = $config['params']['dataparams']['reporttype'];

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    switch ($reporttype) {
      case 'customer':
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer Name', '175', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Barangay', '175', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Area', '175', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Sales Rep', '175', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Total Sales', '150', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Frequency', '150', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
      case 'item':
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer Name', '175', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Barangay', '80', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Area', '80', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Sales Rep', '100', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Item Description', '255', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Qty', '120', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Total Sales', '120', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Frequency', '70', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        break;

      default:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Invoice No.', '100', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Ship Date', '100', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Sales Rep', '150', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Customer Name', '150', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Barangay', '70', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Area', '70', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Description', '140', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Quantity', '60', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->col('Unit Price', '60', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
    }

    return $str;
  }

  public function mmm_Layout($config)
  {
    $companyid = $config['params']['companyid'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype         = $config['params']['dataparams']['reporttype'];

    $count = 26;
    $page = 25;
    $this->reporter->linecounter = 0;
    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->mmm_displayHeader($config);
    $str .= $this->mmm_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
    $part = "";
    $brand = "";
    $item = null;
    $subtotal = 0;
    $amt = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      switch ($reporttype) {
        case 'customer':
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->clientname, '175', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->brgy, '175', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->area, '175', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->salesrep, '175', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data->totalsales, $decimal), '150', null, false, $border, '', 'R', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->frequency, '150', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->endrow();
          break;
        case 'item':
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->clientname, '175', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->brgy, '80', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->area, '80', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->salesrep, '100', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->itemname, '255', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data->qty, $decimal), '120', null, false, $border, '', 'R', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data->totalsales, $decimal), '120', null, false, $border, '', 'R', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->frequency, '70', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->endrow();
          break;

        default:
          $docno = '';
          $deldate = '';
          $itemname = '';
          $qty = 0;
          $price = 0;
          if ($data->cmdocno == '') {
            $docno = $data->docno;
            $itemname = $data->itemname;
            $qty = $data->iss;
            $price = $data->isamt;
          } else {
            $docno = $data->docno . ', ' . $data->cmdocno;
            $itemname = $data->cmitemname;
            $qty = $data->cmrrqty;
            $price = $data->cmamt;
          }

          if ($data->cmreturndate == '') {
            $deldate = $data->deldate;
          } else {
            $deldate = $data->cmreturndate;
          }

          $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'C', 'Verdana', '10', '', '', ''); //
          $str .= $this->reporter->col($data->shipdate, '100', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($deldate, '100', null, false, $border, '', 'C', 'Verdana', '10', '', '', ''); //
          $str .= $this->reporter->col($data->agentname, '150', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->brgy, '70', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($data->area, '70', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col($itemname, '140', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
          $str .= $this->reporter->col(number_format($qty, $decimal), '60', null, false, $border, '', 'R', 'Verdana', '10', '', '', ''); //
          $str .= $this->reporter->col(number_format($price, $decimal), '60', null, false, $border, '', 'R', 'Verdana', '10', '', '', ''); //
          $str .= $this->reporter->endrow();
          break;
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->mmm_displayHeader($config);
        }
        $str .= $this->mmm_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
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

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('SALES REPORT ITEM HISTORY', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Invoice No.', '150', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('Ship Date', '77', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('Date', '77', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, $border, 'B', 'C', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('Sales Rep', '170', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '170', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('Description', '170', null, false, $border, 'B', 'L', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('Quantity', '77', null, false, $border, 'B', 'R', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->col('unit Price', '77', null, false, $border, 'B', 'R', 'Verdana', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $companyid = $config['params']['companyid'];

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
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 26;
    $page = 25;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $part = "";
    $brand = "";
    $item = null;
    $subtotal = 0;
    $amt = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $docno = '';
      $deldate = '';
      $itemname = '';
      $qty = 0;
      $price = 0;
      if ($data->cmdocno == '') {
        $docno = $data->docno;
        $itemname = $data->itemname;
        $qty = $data->iss;
        $price = $data->isamt;
      } else {
        $docno = $data->docno . ', ' . $data->cmdocno;
        $itemname = $data->cmitemname;
        $qty = $data->cmrrqty;
        $price = $data->cmamt;
      }

      if ($data->cmreturndate == '') {
        $deldate = $data->deldate;
      } else {
        $deldate = $data->cmreturndate;
      }

      $str .= $this->reporter->col($docno, '150', null, false, $border, '', 'C', 'Verdana', '10', '', '', ''); //
      $str .= $this->reporter->col($data->shipdate, '77', null, false, $border, '', 'C', 'Verdana', '10', '', '', '');
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', 'Verdana', '10', '', '', ''); //
      $str .= $this->reporter->col($deldate, '77', null, false, $border, '', 'C', 'Verdana', '10', '', '', ''); //
      $str .= $this->reporter->col('', '15', null, false, $border, '', 'C', 'Verdana', '10', '', '', ''); //
      $str .= $this->reporter->col($data->agentname, '170', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
      $str .= $this->reporter->col($data->clientname, '170', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
      $str .= $this->reporter->col($itemname, '170', null, false, $border, '', 'L', 'Verdana', '10', '', '', '');
      $str .= $this->reporter->col(number_format($qty, $decimal), '77', null, false, $border, '', 'R', 'Verdana', '10', '', '', ''); //
      $str .= $this->reporter->col(number_format($price, $decimal), '77', null, false, $border, '', 'R', 'Verdana', '10', '', '', ''); //
      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class