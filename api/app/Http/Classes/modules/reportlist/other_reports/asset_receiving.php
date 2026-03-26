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

class asset_receiving
{
  public $modulename = 'Asset Receiving';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1100'];

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

    $fields = [
      'radioprint',
      'start',
      'end',
      'dclientname',
      'clientname',
      'ditemname',
      'divsion',
      'brandname',
      'model',
      'class',
      'sizeid',
      'company'
    ];
    $col1 = $this->fieldClass->create($fields);
    data_set($col3, 'dclientname.lookupclass', 'stockcardsupplier');
    data_set($col1, 'clientname.type', 'lookup');
    data_set($col1, 'clientname.lookupclass', 'employee');
    data_set($col1, 'clientname.action', 'lookupclient');
    data_set($col1, 'clientname.label', 'Employee');
    data_set($col1, 'clientname.name', 'empname');

    data_set($col1, 'ditemname.lookupclass', 'genitemlist');

    data_set($col1, 'company.action', 'lookupcompany_fams');
    data_set($col1, 'company.lookupclass', 'lookupcompany_fams');
    data_set($col1, 'company.type', 'lookup');
    data_set($col1, 'company.readonly', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10), -365) as start,
    adddate(left(now(),10), 0) as end,
    '' as dclientname,
    '' as clientname,
    '' as client,
     0 as clientid,
    '' as empcode,
    '' as empname,
    '0' as empid,
    '' as ditemname,
    '' as divsion,
    '' as brandname,
    '' as model,
    '' as class,
    '0' as itemid,
    '' as itemname,
    '' as barcode,
    '' as groupid,
    '' as stockgrp,
    '' as brandid,
    '' as brandname,
    '' as classid,
    '' as classic,
    '' as modelid,
    '' as modelname,
    '' as sizeid,
    '' as company
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

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY

    $itemid     = $config['params']['dataparams']['itemid'];
    $itemname     = $config['params']['dataparams']['itemname'];
    $brandid     = $config['params']['dataparams']['brandid'];
    $brandname     = $config['params']['dataparams']['brandname'];
    $modelid     = $config['params']['dataparams']['modelid'];
    $groupid     = $config['params']['dataparams']['groupid'];
    $classid     = $config['params']['dataparams']['classid'];
    $sizeid     = $config['params']['dataparams']['sizeid'];
    $company     = $config['params']['dataparams']['company'];
    $clientname     = $config['params']['dataparams']['clientname'];
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $empname     = $config['params']['dataparams']['empname'];
    $empid     = $config['params']['dataparams']['empid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];

    $filter   = "";

    if ($empname != '') {
      $filter .= " and head.empid = '$empid'";
    }
    if ($clientname != '') {
      $filter .= " and supp.clientid = '$clientid'";
    }
    if ($itemname != '') {
      $filter .= " and item.itemid = '$itemid'";
    }
    if ($brandname != '') {
      $filter .= " and item.brandid = '$brandid'";
    }
    if ($modelid != '') {
      $filter .= " and item.model = '$modelid'";
    }
    if ($groupid != '') {
      $filter .= " and item.groupid = '$groupid'";
    }
    if ($classid != '') {
      $filter .= " and item.classid = '$classid'";
    }
    if ($sizeid != '') {
      $filter .= " and item.sizeid = '$sizeid'";
    }
    if ($company != '') {
      $filter .= " and iteminfo.company = '$company'";
    }

    $query = "
    select  supp.clientname as supplier, item.barcode, item.itemname,
    left(iteminfo.dateacquired, 10) as dateacquired, iteminfo.invoiceno, 
    iteminfo.pono, left(iteminfo.podate, 10) as podate,
    emp.clientname as purchaser, iteminfo.serialno, iteminfo.plateno, stock.rrcost as cost
    from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join client as supp on supp.client = head.client
    left join item as genitem on genitem.itemid = stock.itemid
    left join item as item on item.itemid = stock.itemid
    left join iteminfo as iteminfo on iteminfo.itemid = item.itemid
    left join client as emp on emp.clientid = iteminfo.purchaserid
    where genitem.isgeneric = 1  and date(head.dateid) between '$start' and '$end'
    " . $filter . "
    union all
    select  supp.clientname as supplier,  item.barcode, item.itemname,
    left(iteminfo.dateacquired, 10) as dateacquired, iteminfo.invoiceno, 
    iteminfo.pono, left(iteminfo.podate, 10) as podate,
    emp.clientname as purchaser, iteminfo.serialno, iteminfo.plateno, stock.rrcost as cost
    from glhead as head
    left join glstock as stock on stock.trno = head.trno
    left join client as supp on supp.clientid = head.clientid
    left join item as genitem on genitem.itemid = stock.itemid
    left join item as item on item.itemid = stock.itemid
    left join iteminfo as iteminfo on iteminfo.itemid = item.itemid
    left join client as emp on emp.clientid = iteminfo.purchaserid
    where genitem.isgeneric = 1  and date(head.dateid) between '$start' and '$end'
    " . $filter . "
    order by supplier
  ";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $start   = date("M d, Y", strtotime($config['params']['dataparams']['start']));
    $end     = date("M d, Y", strtotime($config['params']['dataparams']['end']));
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ASSET RECEIVING', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("FROM $start TO $end", '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE ACQ.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('INVOICE NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('INVOICE DATE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PO NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PURCHASER', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ITEM TAG', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DESCRIPTION', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SERIAL NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PLATE NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('COST', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $this->reporter->linecounter = 0;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $decimal_currency = $this->companysetup->getdecimal('currency', $config['params']);
    $totalext = 0;

    $supplier = "";
    $subtotal = 0;
    $grandtotal = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($supplier != $data->supplier) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->supplier, '150', null, false, $border, $border_line, '', $font, 12, '', '', '');
        $str .= $this->reporter->endrow();
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateacquired, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->invoiceno, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->pono, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->podate, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->purchaser, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->itemname, '150', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->serialno, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->plateno, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cost, '100', null, false, $border, $border_line, 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $subtotal += $data->cost;
      $grandtotal += $data->cost;
      if ($supplier != $data->supplier) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('SUB TOTAL: ', null, null, false, '1px dotted', 'BT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($subtotal, null, null, false, '1px dotted', 'BT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $subtotal = 0;
      }

      $supplier = $data->supplier;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    } //end foreach
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('SUB TOTAL: ', null, null, false, '1px dotted', 'BT', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotal, $decimal_currency), null, null, false, '1px dotted', 'BT', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', null, null, false, '1px dotted', 'B', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', null, null, false, '1px dotted', 'B', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, $decimal_currency), null, null, false, '1px dotted', 'B', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class