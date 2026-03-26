<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class items_received_per_supplier_invoice
{
  public $modulename = 'Supplier Invoice';
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
    // $systemtype = $this->companysetup->getsystemtype($config['params']);
    // $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'invoiceno', 'optionstatus'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'invoiceno.readonly', false);
    data_set($col1, 'invoiceno.required', true);
    data_set($col1, 'invoiceno.type', 'input');
    data_set($col1, 'invoiceno.label', 'Supplier Invoice');
    data_set($col1, 'optionstatus.options', [
      ['label' => 'Unposted', 'value' => '0', 'color' => 'green'],
      ['label' => 'Posted', 'value' => '1', 'color' => 'green'],
      ['label' => 'All', 'value' => '2', 'color' => 'green'],
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as invoiceno,
    '0' as status
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
    // $systemtype = $this->companysetup->getsystemtype($config['params']);
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {

    $invoiceno     = $config['params']['dataparams']['invoiceno'];
    $status     = $config['params']['dataparams']['status'];

    $filter   = "";

    if ($invoiceno != "") {
      $filter .= " and head.invoiceno = '" . $invoiceno . "'";
    }

    switch ($status) {
      case '0':
        $query = "select head.docno, stock.ref as podocno, cl.clientname,
      left(head.invoicedate, 10) as invoicedate,
      left(head.dateid, 10) as dateid, sum(stock.rrqty) as rrqty
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join client as cl on cl.client = head.client
      where head.doc = 'RR' $filter
      group by head.docno, stock.ref, cl.clientname, head.invoicedate, head.dateid";
        break;
      case '1':
        $query = "select head.docno, stock.ref as podocno, cl.clientname,
      left(head.invoicedate, 10) as invoicedate,
      left(head.dateid, 10) as dateid, sum(stock.rrqty) as rrqty
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join client as cl on cl.clientid = head.clientid
      where head.doc = 'RR' $filter
      group by head.docno, stock.ref, cl.clientname, head.invoicedate, head.dateid";
        break;
      default:
        $query = "select head.docno, stock.ref as podocno, cl.clientname,
      left(head.invoicedate, 10) as invoicedate,
      left(head.dateid, 10) as dateid, sum(stock.rrqty) as rrqty
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join client as cl on cl.client = head.client
      where head.doc = 'RR' $filter
      group by head.docno, stock.ref, cl.clientname, head.invoicedate, head.dateid
      union all
      select head.docno, stock.ref as podocno, cl.clientname,
      left(head.invoicedate, 10) as invoicedate,
      left(head.dateid, 10) as dateid, sum(stock.rrqty) as rrqty
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join client as cl on cl.clientid = head.clientid
      where head.doc = 'RR' $filter
      group by head.docno, stock.ref, cl.clientname, head.invoicedate, head.dateid";
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    // $invoiceno     = $config['params']['dataparams']['invoiceno'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ID', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Purchase Order', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Supplier Invoice Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Accepted Quantity', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $invoiceno     = $config['params']['dataparams']['invoiceno'];

    $count = 64;
    $page = 63;
    $this->reporter->linecounter = 0;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
    $deci_qty = $this->companysetup->getdecimal('qty', $config['params']);
    $totalqty = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->podocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->invoicedate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->rrqty, $deci_qty), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalqty += $data->rrqty;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL: ', '100', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, $deci_qty), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
