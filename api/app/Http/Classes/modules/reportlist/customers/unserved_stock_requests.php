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

class unserved_stock_requests
{
  public $modulename = 'Unserved Stock Requests';
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
    $fields = ['radioprint', 'start', 'end',  'ddeptname', 'dcentername'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'default', 'color' => 'red'],
    ]);
    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select
          'default' as print,
          adddate(left(now(),10),-360) as start,
          left(now(),10) as end,
          '' as ddeptname,
          '' as dept,
          '' as deptname,
          '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername";
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
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $username = $config['params']['user'];
    $result = $this->reportDefaultLayout_Customer($config);
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid     = $config['params']['companyid'];
    $center     = $config['params']['dataparams']['center'];

    $deptid = $config['params']['dataparams']['ddeptname'];

    if ($deptid == "") {
      $dept = "";
    } else {
      $dept = $config['params']['dataparams']['deptid'];
    }
    $filter = "";
    $datefilter = "";


    if ($deptid != "") {
      $filter .= " and client.clientid = $dept";
    }

    if ($center != "") {
      $filter .= " and transnum.center='$center'";
    }

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $datefilter = " and date(head.dateid) between '" . $start . "' and '" . $end . "'";

    $query = "select  head.docno,
        client.clientname, item.itemname,
        date(head.dateid) as dateid,stock.reqqty as qty, stock.qa,
        (stock.reqqty-stock.qa) as unserved,
        item.uom, item.itemid,item.barcode,stock.ext,stock.rrcost,stock.disc
        from ((trhead as head left join trstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid)
        left join client on client.client=head.client
        left join transnum on transnum.trno=head.trno
        where stock.void=0 and (stock.reqqty-stock.qa)>0  " . $filter . " " . $datefilter . "  
        union all
        select  head.docno,
        client.clientname, item.itemname,
        date(head.dateid) as dateid,stock.reqqty as ordered, stock.qa,
        (stock.reqqty-stock.qa) as unserved,
        item.uom, item.itemid,item.barcode,stock.ext,stock.rrcost,stock.disc
        from ((htrhead as head left join htrstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid)
        left join client on client.client=head.client
        left join transnum on transnum.trno=head.trno
        where stock.void=0 and (stock.reqqty-stock.qa)>0   " . $filter . " " . $datefilter . "  and item.isofficesupplies= 0 
        order by docno";

    return $this->coreFunctions->opentable($query);
  }


  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $filtercenter       = $config['params']['dataparams']['dcentername'];
    $start = date("m/d/Y", strtotime($config['params']['dataparams']['start']));
    $end = date("m/d/Y", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '';
    $layoutsize == '1150';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    $str .= $this->reporter->begintable('1150');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('UNSERVED STOCK REQUESTS', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $filtercenter = $filtercenter == '' ? 'ALL' : $filtercenter;
    $str .= $this->reporter->begintable($layoutsize);



    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->col('Center : ' . $filtercenter, '1150', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->col('Date Range :  ' . $start . ' ' . '-' . ' ' . $end, '1150', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DEPARTMENT', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ITEMNAME', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ORDERED', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SERVED', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    return $str;
  }

  private function reportDefaultLayout_Customer($config)
  {
    $result     = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $str = '';
    $count = 60; #60
    $page = 59; #59
    $layoutsize = '1150';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $amttotal = 0;
    $ordertotal = 0;
    $tbalance = 0;
    $gtotal = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $dept = '';
    $i = 0;

    foreach ($result as $key => $data) {
      $display = $data->itemname;
      $docno = $data->docno;
      $barcode = $data->barcode;
      $date = $data->dateid;
      $order = $data->qty;
      $served = $data->qa;
      $bal = $data->unserved;
      $uom = $data->uom;
      $amt = $bal * $data->rrcost - $data->disc;

      if ($dept != '' && $dept != ($data->clientname)) {
        TotalHere:
        $gtotal += $amttotal;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '800', null, false,  $border, '', 'L', $font, $fontsize, 'B', 'b', '');
        $str .= $this->reporter->col('TOTAL:', '50', null, false,  $border, '', 'L', $font, $fontsize, $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($ordertotal, 2), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false,  $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($tbalance, 2), '100', null, false,  $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($amttotal, 2), '100', null, false,  $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $amttotal = 0;
        $ordertotal = 0;
        $tbalance = 0;

        if ($i == (count((array)$result) - 1)) {
          break;
        }
        $str .= $this->reporter->addline();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $page = $page + $count;
        }
      }

      if ($dept == '' || $dept != ($data->clientname)) {

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, '200', null, false,  $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '950', null, false,  $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->addline();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $page = $page + $count;
        }
      }


      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($docno, '100', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($date, '100', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($barcode, '100', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($display, '300', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($uom, '50', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col(number_format($order, 2), '50', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col(number_format($served, 2), '50', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col(number_format($bal, 2), '100', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $amttotal += $amt;
      $ordertotal += $order;
      $tbalance += $bal;

      $dept = $data->clientname;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }

      if ($i == (count((array)$result) - 1)) {
        goto TotalHere;
      }
      $i++;
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '950', null, false,  $border, 'T', 'L', $font, $fontsize, 'B', 'b', '');
    $str .= $this->reporter->col(' Grand Total:', '100', null, false,  $border, 'T', 'R', $font, $fontsize, $font, $fontsize, 'B');
    $str .= $this->reporter->col(number_format($gtotal, 2), '100', null, false,  $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class
