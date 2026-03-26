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


class best_seller_report
{
  public $modulename = 'Best Seller Report';
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

    $fields = ['radioprint', 'start', 'end', 'ditemname', 'divsion', 'brandname', 'brandid', 'class', 'dwhname'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname');
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'divsion.label', 'Group');

    $fields = ['year', 'radioposttype', 'radioreportitemtype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'year.label', 'Top # of Items');
    data_set($col2, 'year.required', true);

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
      left(adddate(now(),-30),10) as start,
      left(now(),10) as end,
      '' as ditemname,
      '' as barcode,
      '' as groupid,
      '' as stockgrp,
      '' as brandid,
      '' as brandname,
      '' as classid,
      '' as classic,
      '' as wh,
      '' as whname,
      '0' as posttype,
      '(0,1)' as itemtype,
      '10' as year,
      '' as divsion,
      '' as brand,
      '' as class,
      '' as dwhname ";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= ", '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname ";
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $result = $this->reportDefaultLayout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case '0': // POSTED
        $query = $this->default_QUERY_POSTED($config);
        break;
      case '1': // UNPOSTED
        $query = $this->default_QUERY_UNPOSTED($config);
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_POSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $brand  = $config['params']['dataparams']['brand'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $isqty = "stock.iss";

    $filter = " and item.isimport in $loc";
    $filter1 = "";
    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }
    if ($groupid != "") {
      $filter .=  " and stockgrp.stockgrp_id='$groupid'";
    }
    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brand'";
    }
    if ($classname != "") {
      $filter = $filter . " and item.class='$classname'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and item.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body from (
    select item.sizeid as size,'P' as tr, ifnull(item.groupid,'') as groupid, ifnull(frontend_ebrands.brand_desc,'') as brand,
    ifnull(parts.part_name,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,stock.uom
    from glhead as head left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid 
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as mm on mm.model_id = item.model
    left join item_class as cc on cc.cl_id = item.class
    left join part_masterfile as parts on parts.part_id = item.part
    left join frontend_ebrands on frontend_ebrands.brandid = item.brand
    left join cntnum on cntnum.trno=head.trno
    left join client as wh on wh.clientid=stock.whid
    where head.doc in ('sj','sd','se','sf') and head.dateid between '$start' and '$end' 
    and ifnull(item.itemid,'')<>'' $filter $filter1
    group by item.sizeid,item.groupid, frontend_ebrands.brand_desc,parts.part_name,cc.cl_name,wh.client,
    item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
    item.barcode,stock.uom) as FM 
    group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,uom,part,body,qty 
    order by qty desc $top";

    return $query;
  }

  public function default_QUERY_UNPOSTED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classid    = $config['params']['dataparams']['classid'];
    $classname  = $config['params']['dataparams']['classic'];
    $brandid    = $config['params']['dataparams']['brandid'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupid    = $config['params']['dataparams']['groupid'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $loc        = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $isqty = "stock.iss";

    $filter = " and item.isimport in $loc";
    $filter1 = "";
    if ($barcode != "") {
      $filter = $filter . " and item.barcode='$barcode'";
    }
    if ($groupid != "") {
      $filter .=  " and stockgrp.stockgrp_id='$groupid'";
    }
    if ($brandname != "") {
      $filter = $filter . " and item.brand='$brandname'";
    }
    if ($classname != "") {
      $filter = $filter . " and item.class='$classname'";
    }
    if ($wh != "") {
      $filter = $filter . " and wh.client='$wh'";
    }

    if ($top != "") {
      $top = " limit " . $top . "";
    } else {
      $top = " limit 1 ";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and item.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and head.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select size,model,tr,groupid,brand,class,whcode,
    whname,itemname,barcode,qty,uom,part,body from (
    select item.sizeid as size, 'U' as tr, ifnull(item.groupid,'') as groupid, ifnull(item.brand,'') as brand,
    ifnull(item.part,'') as part,ifnull(mm.model_name,'') as model,item.body,
    ifnull(cc.cl_name,'') as class,wh.client as whcode,ifnull(wh.clientname,'') as whname,
    ifnull(item.itemname,'') as itemname,item.barcode,sum(" . $isqty . ") as qty,stock.uom
    from lahead as head left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid 
    left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
    left join model_masterfile as mm on mm.model_id = item.model
    left join item_class as cc on cc.cl_id = item.class
    left join cntnum on cntnum.trno=head.trno
    left join client as wh on wh.clientid=stock.whid
    where head.doc in ('sj','sd','se','sf') and head.dateid between '$start' and '$end' 
    and ifnull(item.itemid,'')<>'' $filter $filter1
    group by item.sizeid,item.groupid, item.brand,cc.cl_name,wh.client,
    item.part,item.model,item.body,wh.clientname,item.barcode,item.itemname,mm.model_name,
    item.barcode,stock.uom) as FM 
    group by size,model,tr,groupid,brand,class,whcode,whname,itemname,barcode,qty,uom,part,body 
    order by qty desc $top";

    return $query;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $classname  = $config['params']['dataparams']['classic'];
    $brandname  = $config['params']['dataparams']['brandname'];
    $groupname  = $config['params']['dataparams']['stockgrp'];
    $barcode    = $config['params']['dataparams']['barcode'];
    $wh         = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $itemtype   = $config['params']['dataparams']['itemtype'];
    $top        = $config['params']['dataparams']['year'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

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
    $str .= $this->reporter->col('FAST MOVING ITEMS', null, null, false, $border, '', '', 'Verdana', '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . $start . ' TO ' . $end, null, null, false, $border, '', 'L', $font, $font_size, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else {
      $posttype = 'Unposted';
    }

    switch ($itemtype) {
      case '(0)':
        $itemtype = 'Local';
        break;
      case '(1)':
        $itemtype = 'Import';
        break;
      case '(0,1)':
        $itemtype = 'Both';
        break;
    }

    if ($whname == "") {
      $whname = "ALL";
    }
    if ($barcode == "") {
      $barcode = "ALL";
    }
    if ($groupname == "") {
      $groupname = "ALL";
    }
    if ($brandname == "") {
      $brandname = "ALL";
    }
    if ($classname == "") {
      $classname = "ALL";
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item : ' . $barcode, '300', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group : ' . $groupname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Warehouse : ' . $whname, '150', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '150', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand : ' . $brandname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class : ' . $classname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), '150', null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Item :' . $barcode, '150', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col('Group :' . $groupname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Brand :' . $brandname, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class' . $classname, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Transaction: ' . strtoupper($posttype), null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, '', $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Item Type : ' . strtoupper($itemtype), null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('WH : ' . $wh, null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', null, null, '', $border, '', 'l', $font, $font_size, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRODUCT CODE', '150', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('PRODUCT DESCRIPTION', '500', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '50', null, false, $border, 'B', 'C', 'Verdana', $font_size, 'B', '', '', '');

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);

    $count = 39;
    $page = 40;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      $item_desc = $data->itemname;

      if ($data->brand != "") {
        $item_desc = $data->brand . " " . $item_desc;
      } //end if

      if ($data->model != "") {
        $item_desc = $item_desc . " " . $data->model;
      } //end if

      if ($data->size != "") {
        $item_desc = $item_desc . " " . $data->size;
      } //end if        

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($item_desc, '500', null, false, $border, '', 'L', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, 2), '100', null, false, $border, '', 'R', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->col($data->uom, '50', null, false, $border, '', 'C', 'Verdana', $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class