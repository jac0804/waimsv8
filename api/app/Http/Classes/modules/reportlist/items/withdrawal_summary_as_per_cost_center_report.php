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

class withdrawal_summary_as_per_cost_center_report
{
  public $modulename = 'Withdrawal Summary As Per Cost Center Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1000px;max-width:1000px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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

    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'divsion.label', 'Group');
    data_set($col1, 'brandid.name', 'brandid');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    $fields = ['radiosalescustomerperitem'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      left(now(),10) as end,
      'sales' as options
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

    $result = $this->reportDefault($config);

    $str = $this->reportplotting($config, $result);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    // <-- layout switching
    switch ($config['params']['dataparams']['print']) {
      default:
        $result = $this->reportDefaultLayout($config, $result);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    $query = $this->DEFAULT_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  // QUERY
  public function DEFAULT_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $option     = $config['params']['dataparams']['options'];

    $filter = "";
    $filter1 = "";

    $fieldoption = 'a.ext';
    if ($option == 'qty') {
      $fieldoption = 'a.iss';
    }

    $order = " order by a.dept asc";

    // default://ALL
    $query = "select right(a.dept,4) as dept,sum($fieldoption) as value from (
          select dept.client as dept,stock.iss,stock.ext from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client as dept on dept.clientid=head.deptid
          where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
          union all
          select dept.client as dept,stock.iss,stock.ext from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client as dept on dept.clientid=head.deptid
          where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
          ) as a
          group by a.dept
          $order";
    return $query;
  }


  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 10;
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $option     = $config['params']['dataparams']['options'];

    $layoutsize = '800';

    $supp = '';
    if ($companyid == 21) $supp = $config['params']['dataparams']['client']; //kinggeorge

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('Withdrawal Summary As Per Cost Center Report', '800', null, false, $border, '', 'C', $font, $fontsize + 4, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('Date Range: ', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($start, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('to', '30', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($end, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '530', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $fieldoption = 'Amount';
    if ($option == 'qty') {
      $fieldoption = 'Quantity';
    }
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('Option: ', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($fieldoption, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '530', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('Cost Center', '150', null, false, $border, 'TL', 'C', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('Total Amount', '150', null, false, $border, 'TLR', 'C', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize + 2, '', '', '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config, $result)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 10;
    $padding = '';
    $margin = '';
    $this->reporter->linecounter = 0;
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $option     = $config['params']['dataparams']['options'];


    $fieldoption = 'a.ext';
    if ($option == 'qty') {
      $fieldoption = 'a.iss';
    }

    $lastmonthqry = "
      select sum($fieldoption) as value from (
      select stock.iss,stock.ext from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client as dept on dept.clientid=head.deptid
      where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between (DATE_SUB('$start', INTERVAL 30 DAY)) and (DATE_SUB('$end', INTERVAL 30 DAY))
      union all
      select stock.iss,stock.ext from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client as dept on dept.clientid=head.deptid
      where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between (DATE_SUB('$start', INTERVAL 30 DAY)) and (DATE_SUB('$end', INTERVAL 30 DAY))
      ) as a";

    $lastmonth = json_decode(json_encode($this->coreFunctions->opentable($lastmonthqry)), true);

    $thismonthbagqry = "
      select sum($fieldoption) as value from (
      select stock.iss,stock.ext from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client as dept on dept.clientid=head.deptid
      left join item on item.itemid=stock.itemid
      where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
      and item.body='BAG'
      union all
      select stock.iss,stock.ext from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client as dept on dept.clientid=head.deptid
      left join item on item.itemid=stock.itemid
      where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
      and item.body='BAG'
      ) as a";

    $thismonthbag = json_decode(json_encode($this->coreFunctions->opentable($thismonthbagqry)), true);

    $lastmonthbagqry = "
      select sum($fieldoption) as value from (
      select stock.iss,stock.ext from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client as dept on dept.clientid=head.deptid
      left join item on item.itemid=stock.itemid
      where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between (DATE_SUB('$start', INTERVAL 30 DAY)) and (DATE_SUB('$end', INTERVAL 30 DAY))
      and item.body='BAG'
      union all
      select stock.iss,stock.ext from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client as dept on dept.clientid=head.deptid
      left join item on item.itemid=stock.itemid
      where doc in ('RN','RM') and stock.iscomponent = 0 and date(head.dateid) between (DATE_SUB('$start', INTERVAL 30 DAY)) and (DATE_SUB('$end', INTERVAL 30 DAY))
      and item.body='BAG'
      ) as a";

    $lastmonthbag = json_decode(json_encode($this->coreFunctions->opentable($lastmonthbagqry)), true);


    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '800';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $part = "";
    $brand = "";
    $count = 1;
    $totalqty = 0;
    $totalweightin = 0;
    $totalweightout = 0;
    $totalweightavg = 0;
    $totalvalue = 0;
    $count = 1;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->dept, '150', null, false, $border, 'TL', 'C', $font, $fontsize - 1, '', '', '');
      $str .= $this->reporter->col(number_format($data->value, 2), '150', null, false, $border, 'TLR', 'R', $font, $fontsize - 1, '', '', '');
      if ($count == count($result) - 1) {
        $str .= $this->reporter->col('Total Amount', '150', null, false, $border, 'TR', 'C', $font, $fontsize - 1, '', '', '');
      } else if ($count == count($result)) {
        $date = date_create($start);
        date_modify($date, "-30 days");
        date_format($date, "M");
        $str .= $this->reporter->col('Last ' . date_format($date, "F") . ', ' . date_format($date, "Y"), '150', null, false, $border, 'R', 'C', $font, $fontsize - 1, 'B', '', '');
      } else {

        $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $fontsize - 1, '', '', '');
      }
      $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $count++;


      $totalvalue += $data->value;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('Grand Total Amt. P =', '150', null, false, $border, 'T', 'C', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalvalue, 2), '150', null, false, $border, 'TBLR', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($lastmonth[0]['value'], 2), '150', null, false, $border, 'TBLR', 'C', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Cement Bags', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($thismonthbag[0]['value'], 2), '150', null, false, $border, 'TBLR', 'R', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col(number_format($lastmonthbag[0]['value'], 2), '150', null, false, $border, 'TBLR', 'C', $font, $fontsize - 1, 'B', '', '');
    $str .= $this->reporter->col('', '350', null, false, $border, '', 'C', $font, $fontsize, '', '', '');


    $str .= $this->reporter->endtable();



    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class