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

class commission_report_by_agent
{
  public $modulename = 'Commission Report by Agent';
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
    $fields = ['radioprint', 'start', 'end', 'dagentname'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);


    $fields = ['radioposttype', 'print'];
    $col2 = $this->fieldClass->create($fields);

    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("
    select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      0 as agentid,
      '' as dagentname,
      '' as agent,
      '' as agentname,
      '0' as posttype");
  }

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

    // $result = $this->eapp_Detail_Layout($config);

    $result = $this->report_Detail_Layout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->Detail_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function Detail_QUERY($config)
  {
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $agentid  = $config['params']['dataparams']['agentid'];
    $agent     = $config['params']['dataparams']['agent'];
    $posttype = $config['params']['dataparams']['posttype'];

    $filter = "";
    $query = "";
    if ($agent != "") {
      $filter .= " and a.clientid =$agentid ";
    }

    switch ($posttype) {
      case '0': //posted

        $query = " select  a.agname, a.docno, a.dateid, a.customer,a.itemname,a.amount,a.commrate,a.agcode
         from (
          select concat(left(head.docno,2),right(head.docno,8)) as docno,date(head.dateid) as dateid,cust.clientname as customer,
          ifnull(i.itemname,'') as itemname,stock.amt as amount, i.commrate,
          ifnull(a.clientname,'') as agname,stock.isamt,ifnull(a.client,'') as agcode
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client as a on a.clientid=head.agentid
          left join client as cust on cust.clientid=head.clientid
          left join item as i on i.itemid=stock.itemid
          where head.doc ='SJ'  and  i.itemname <>'' and a.clientname<>'' and a.client <> '' and date(head.dateid) between  '$start' and '$end' $filter ) as a
      group by a.agname, a.docno, a.dateid, a.customer,a.itemname,a.amount,a.commrate,a.agcode
      order by a.agname desc";

        break;
      case '1': //unposted

        $query = " select  a.agname, a.docno, a.dateid, a.customer,a.itemname,a.amount,a.commrate,a.agcode
         from (
          select concat(left(head.docno,2),right(head.docno,8)) as docno,date(head.dateid) as dateid,cust.clientname as customer,
          ifnull(i.itemname,'') as itemname,stock.amt as amount, i.commrate,
          ifnull(a.clientname,'') as agname,stock.isamt,ifnull(a.client,'') as agcode
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client as a on a.client=head.agent
          left join client as cust on cust.client=head.client
          left join item as i on i.itemid=stock.itemid
          where head.doc ='SJ'  and  i.itemname <>'' and a.clientname<>''  and a.client <> '' and date(head.dateid) between  '$start' and '$end' $filter ) as a
      group by a.agname, a.docno, a.dateid, a.customer,a.itemname,a.amount,a.commrate,a.agcode
      order by a.agname desc";

        break;
      default; //all
        $query = " select  a.agname, a.docno, a.dateid, a.customer,a.itemname,a.amount,a.commrate,a.agcode
         from (
          select concat(left(head.docno,2),right(head.docno,8)) as docno,date(head.dateid) as dateid,cust.clientname as customer,
          ifnull(i.itemname,'') as itemname,stock.amt as amount, i.commrate,
          ifnull(a.clientname,'') as agname,stock.isamt,ifnull(a.client,'') as agcode
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client as a on a.client=head.agent
          left join client as cust on cust.client=head.client
          left join item as i on i.itemid=stock.itemid
          where head.doc ='SJ'  and  i.itemname <>'' and a.clientname<>''  and a.client <> '' and date(head.dateid) between  '$start' and '$end' $filter
          union all
          select concat(left(head.docno,2),right(head.docno,8)) as docno,date(head.dateid) as dateid,cust.clientname as customer,
          ifnull(i.itemname,'') as itemname,stock.amt as amount, i.commrate,
          ifnull(a.clientname,'') as agname,stock.isamt,ifnull(a.client,'') as agcode
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client as a on a.clientid=head.agentid
          left join client as cust on cust.clientid=head.clientid
          left join item as i on i.itemid=stock.itemid
          where head.doc ='SJ'  and  i.itemname <>'' and a.clientname<>'' and a.client <> '' and date(head.dateid) between  '$start' and '$end' $filter ) as a
      group by a.agname, a.docno, a.dateid, a.customer,a.itemname,a.amount,a.commrate,a.agcode
      order by a.agname desc";
        break;
    }
    return $query;
  }


  public function report_Detail_Header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '1000';
    $font = "Tahoma";
    $fontsize = "11";
    $border = "1px solid ";

    $agentname   = $config['params']['dataparams']['agentname'];

    if ($agentname != "") {
      $agentname = $config['params']['dataparams']['agentname'];
    } else {
      $agentname = "ALL";
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1000', null, false, '1px solid', '', 'C', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address),  '1000', null, false, '1px solid', '', 'C', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel),  '1000', null, false, '1px solid', '', 'C', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $startdate = date('M d, Y', strtotime($start));
    $enddate = date('M d, Y', strtotime($end));

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($startdate . ' to ' . $enddate, '1000', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent: ' . $agentname, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_detail_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Document#', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '250', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Item Description', '270', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('%', '80', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Commission', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  public function report_Detail_Layout($config)
  {

    $result = $this->reportDefault($config);
    $companyid  = $config['params']['companyid'];
    $count = 0;
    $pageLimit = 69;

    $str = '';
    $layoutsize = '1000';
    $font = "Tahoma";
    $fontsize = "11";
    $border = "1px solid ";
    $subborder = "1px dotted ";
    $agname = '';

    $subtotalamt = 0;
    $grandtotalamt = 0;

    $subtotalcomm = 0;
    $grandtotalcomm = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->report_Detail_Header($config);
    $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);

    foreach ($result as $key => $data) {

      // bagong agent
      if ($agname != $data->agname) {
        // subtotal ng previous agent
        if ($agname != '') {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
          $str .= $this->reporter->col('', '250', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
          $str .= $this->reporter->col('', '270', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotalamt, 2), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotalcomm, 2), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->report_Detail_Header($config);
          $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);
        }

        $agname = $data->agname;
        $count = 0;
        $subtotalamt = 0;
        $subtotalcomm = 0;

        // agent header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->agname, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $count++;
      }

      $docno = $data->docno;
      $date = $data->dateid;
      $customer = $data->customer;
      $itemname = $data->itemname;
      $amount = number_format($data->amount, 2);

      $arr_docno = $this->reporter->fixcolumn([$docno], '15', 0);
      $arr_date = $this->reporter->fixcolumn([$date], '15', 0);
      $arr_customer = $this->reporter->fixcolumn([$customer], '30', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '33', 0);
      $arr_amount = $this->reporter->fixcolumn([$amount], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_date, $arr_customer, $arr_itemname, $arr_amount]);

      $rate = '';
      $commission = 0;

      if ($data->commrate != 0) {
        $rate = number_format($data->commrate, 2) . ' %';
        $commission = $data->amount * ($data->commrate / 100);
      }

      $comm = $commission != 0 ? number_format($commission, 2) : '';

      // total ng AMOUNT
      $subtotalamt += floatval($data->amount);
      $grandtotalamt += floatval($data->amount);

      $subtotalcomm += floatval($commission);
      $grandtotalcomm += floatval($commission);

      for ($r = 0; $r < $maxrow; $r++) {

        // page break kada 50 rows
        if ($count >= $pageLimit) {

          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->report_Detail_Header($config);
          $str .= $this->default_detail_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize + 1, $config);

          // ulit agent name kada new page
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->agname, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();


          $count = 1;
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col(isset($arr_docno[$r]) ? $arr_docno[$r] : '', 100, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($arr_date[$r]) ? $arr_date[$r] : '', 100, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($arr_customer[$r]) ? $arr_customer[$r] : '', 250, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($arr_itemname[$r]) ? $arr_itemname[$r] : '', 270, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(isset($arr_amount[$r]) ? $arr_amount[$r] : '', 100, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($r != 0 ? '' : $rate, 80, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($r != 0 ? '' : $comm, 100, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $count++;
      }
    }

    // last agent subtotal
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '270', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotalamt, 2), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotalcomm, 2), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    // grand total amount
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '250', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '270', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotalamt, 2), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotalcomm, 2), '100', null, false, $subborder, 'BT', 'R', $font, $fontsize - 1, 'B', '', '');


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
