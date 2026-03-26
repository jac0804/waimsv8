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

class inventory_checksheet
{
  public $modulename = 'Inventory Checksheet';
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
    $fields = ['radioprint', 'start', 'dwhname', 'loc', 'class'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'Balance as of');
    data_set($col1, 'loc.lookupclass', 'replookuploc');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-30) as start,
    '' as loc,
    '' as wh,
    '' as whname,
    '' as dwhname,
    '' as class,
    '' as classid,
    '' as classic
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

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $center    = $config['params']['center'];
    $asof       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $loc    = $config['params']['dataparams']['loc'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];

    $filter = "";
    if ($loc != "") {
      $filter .= " and rrstat.loc='$loc'";
    }

    if ($wh != "") {
      $filter .= " and wh.client='$wh'";
    }

    if ($classid != "") {
      $filter .= " and item.class='$classid'";
    }

    $query = "
    select wh.clientname as whname, wh.client as swh,
    rrstat.itemid, item.itemname, item.barcode, rrstat.loc, 
    rrstat.expiry,
    ifnull(class.cl_name,'') as classname, rrstat.uom,
    sum(rrstat.bal) as balance
    from rrstatus as rrstat
    left join item on item.itemid = rrstat.itemid
    left join client as wh on wh.clientid = rrstat.whid
    left join item_class as class on class.cl_id = item.class
    left join uom on uom.itemid = rrstat.itemid and uom.uom = rrstat.uom
    where date(rrstat.dateid) <= '$asof' and rrstat.bal <> 0 " . $filter . "
    group by wh.clientname, wh.client, rrstat.itemid, item.itemname,
    item.barcode, rrstat.loc, rrstat.expiry, class.cl_name, rrstat.uom
    order by wh.client,rrstat.loc
  ";
    return $this->coreFunctions->opentable($query);
  }

  public function reportDefaultLayout($config)
  {
    $classid    = $config['params']['dataparams']['loc'];
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $layoutsize = '800';
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $str = "";
    $count = 26;
    $page = 26;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport();

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .=  $this->reporter->startrow();
    $str .=   $this->reporter->col('Inventory Checksheet', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';
    $str .=    $this->reporter->endrow();


    $str .=    $this->reporter->startrow(NULL, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '', '');
    $str .=  $this->reporter->col('Warehouse : ' . $whname, null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .=   $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $totalbalqty = 0;
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WAREHOUSE/LOCATION', '90', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('CLASS', '110', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('DESCRIPTION', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('UNIT', '50', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('QUANTITY', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('CHECK', '50', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('REMARKS', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', 'B', '', '', '');

    $decimal = $this->companysetup->getdecimal('qty', $config['params']);
    $whgrp = "";
    $loc = "";
    $totalext = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      if (strtoupper($whgrp) == strtoupper($data->swh)) {
        if (strtoupper($loc) == strtoupper($data->loc)) {
          $whgrp = "";
          $loc = "";
        } else {
          $loc = strtoupper($data->loc);
          $whgrp = strtoupper($data->swh);
        }
      } else {
        if (strtoupper($loc) == strtoupper($data->loc)) {
          $whgrp = strtoupper($data->swh);
          $loc = "";
        } else {
          $loc = strtoupper($data->loc);
          $whgrp = strtoupper($data->swh);
        }
      }


      $balance = number_format($data->balance, $decimal);
      if ($balance == 0) {
        $balance = '-';
      }

      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($whgrp, '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', 'Bi', '', '');
      $str .=  $this->reporter->col($loc, '110', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', 'Bi', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .=  $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '90', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data->classname, '110', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data->barcode, '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data->itemname, '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col($balance, '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col('[&nbsp&nbsp&nbsp]', '50', null, false, '1px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
      $whgrp = strtoupper($data->swh);
      $loc = strtoupper($data->loc);
      $totalbalqty = $totalbalqty + $data->balance;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= '<br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TB', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('OVERALL STOCKS :', '250', null, false, '1px solid ', 'TB', 'L', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'TB', 'R', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col(number_format($totalbalqty, $decimal), '120', null, false, '1px solid ', 'TB', 'R', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '40', null, false, '1px solid ', 'TB', 'C', 'Century Gothic', '10', 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TB', 'R', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class