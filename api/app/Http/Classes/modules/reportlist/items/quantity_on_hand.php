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

class quantity_on_hand
{
  public $modulename = 'Quantity On Hand';
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
    $fields = ['radioprint', 'start', 'divsion', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'start.label', 'Balance as Of');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    left(now(),10) as start,
    '' as groupid,
    '' as stockgrp,
    '' as wh,
    '' as whname,
    '' as divsion,
    '' as dwhname
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
  // LAYOUT OF REPORT
  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout_DETAILED($config);

    return $result;
  }
  // RESULT QUERY
  public function reportDefault($config)
  {
    $query = $this->report_DEFAULT_QUERY($config);


    return $this->coreFunctions->opentable($query);
  }

  public function report_DEFAULT_QUERY($config)
  {
    // QUERY
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $wh     = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $groupid     = $config['params']['dataparams']['groupid'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];

    $filter = '';
    if ($wh != '') {
      $filter .= " and wh.client = '$wh'";
    }

    if ($groupid != '') {
      $filter .= " and stockgrp.stockgrp_id= '$groupid'";
    }

    $query = "
    select ib.itemid,barcode, itemname,
    ib.uom,
    sum(qty-iss)/(case when ifnull(uom.factor,0)=0 then 1 else uom.factor end) as bal,
    ib.amt
    from (
    select
     item.itemid,item.barcode, item.itemname,
    item.uom,
    stock.qty, stock.iss,
    item.amt 
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.barcode=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    where ifnull(item.barcode,'')<>'' $filter
    UNION ALL
    select
    item.itemid,item.barcode, item.itemname,
    item.uom,
    stock.qty, stock.iss,
    item.amt 
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join client as wh on wh.clientid=stock.whid
    left join cntnum on cntnum.trno=head.trno
    where ifnull(item.barcode,'')<>'' $filter) as ib
    left join uom on uom.itemid=ib.itemid and uom.uom=ib.uom
    group by ib.itemid, ib.barcode, ib.itemname, ib.uom, ib.amt, uom.factor
    HAVING (case 
      when sum(qty-iss)/(case when ifnull(uom.factor,0)=0 then 1 else uom.factor end) > 0 
      then 1 else 0 end) in (0, 1)
    order by itemname
  ";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $wh     = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $groupid     = $config['params']['dataparams']['groupid'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->startrow();
    $str .= '<br/>';
    $str .= $this->reporter->col('QUANTITY ON HAND', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BALANCE AS OF : ' . $start, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();

    if ($whname == "") {
      $str .= $this->reporter->col('WAREHOUSE : ALL', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('WAREHOUSE : ' . $whname, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    if ($groupid == "") {
      $str .= $this->reporter->col('GROUP : ALL', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('GROUP :' . $stockgrp, '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    }
    $str .= $this->reporter->col('', '450', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('DESCRIPTION', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('UNIT', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('BALANCE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result  = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $wh     = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $groupid     = $config['params']['dataparams']['groupid'];
    $stockgrp     = $config['params']['dataparams']['stockgrp'];

    $count = 48;
    $page = 50;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->default_displayHeader($config);
    $totalcost = 0;

    $name = "";
    $code = "";
    $subtotal = 0;
    $subqty = 0;
    $iitem = "";

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $balance = number_format($data->bal, 2);
      if ($balance == 0) {
        $balance = '-';
      }
      $str .= $this->reporter->col($data->barcode, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($balance, '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class