<?php

namespace App\Http\Classes\modules\reportlist\items;

use DB;
use Session;
use ErrorException;
use App\Http\Requests;

use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use Illuminate\Http\Request;
use App\Http\Classes\sqlquery;
use App\Http\Classes\othersClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\modules\consignment\co;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class sales_summary_per_item_per_price
{
  public $modulename = 'Sales Summary Per Item Per Price';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:3000px;max-width:3000px;';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'divsion', 'class', 'category', 'brandname', 'brandid', 'part', 'subcatname', 'dprojectname'];
    $col1 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 14: //majesty
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'divsion.label', 'Division');
        data_set($col1, 'part.label', 'Principal');
        data_set($col1, 'class.label', 'Classification');
        break;
      case 17: //unihome
      case 28: //xcomp
      case 39: //CBBSI
        $fields = ['radioprint', 'start', 'end', 'divsion', 'dclientname', 'agentname', 'class', 'categoryname', 'brandname', 'brandid', 'part', 'subcatname', 'dprojectname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label',  'Customer');
        data_set($col1, 'agentname.label', 'Agent');
        data_set($col1, 'agentname.readonly', false);
        data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'categoryname.lookupclass', 'lookupcategoryitemstockcard');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'category.name', 'categoryname');

    unset($col1['divsion']['labeldata']);
    unset($col1['class']['labeldata']);
    unset($col1['part']['labeldata']);
    unset($col1['labeldata']['divsion']);
    unset($col1['labeldata']['class']);
    unset($col1['labeldata']['part']);
    data_set($col1, 'divsion.name', 'stockgrp');
    data_set($col1, 'class.name', 'classic');
    data_set($col1, 'part.name', 'partname');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
       adddate(left(now(),10),-360) as start,
       left(now(),10) as end,
       0 clientid, '' client, '' as clientname, '' as dclientname, 
       0 as agentid, '' as agent, '' as agentname,
       0 as groupid,'' as stockgrp,'' as divsion,
       0 as classid,'' as classic,'' as class,
       '' as category,'' as categoryname,
       '' as brandid,'' as brandname,
       0 as partid,'' as partname, '' as part,
       '' as subcat,'' as subcatname,
       0 as projectid, '' as dprojectname, '' as projectcode, '' as projectname";

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

  public function getquery($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $groupname    = $config['params']['dataparams']['stockgrp'];
    $classname    = $config['params']['dataparams']['classic'];
    $brandname    = $config['params']['dataparams']['brandname'];
    $partname     = $config['params']['dataparams']['partname'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname   = $config['params']['dataparams']['subcatname'];
    $clientname = $config['params']['dataparams']['clientname'];
    $client = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];

    $filter = '';
    $filterp = '';
    $filteru = '';

    if ($clientname != "") {
      $filteru .= " and head.client = '" . $clientid . "'";
      $filterp .= " and head.clientid = '" . $clientid . "'";
    }

    if ($config['params']['companyid'] == 17 || $config['params']['companyid'] == 39) { //unihome, cbbsi
      $dclientname = $config['params']['dataparams']['clientname'];
      $client = $config['params']['dataparams']['client'];
      $clientid = $config['params']['dataparams']['clientid'];
      $agentname = $config['params']['dataparams']['agentname'];
      $agent = $config['params']['dataparams']['agent'];
      $agentid = $config['params']['dataparams']['agentid'];

      if ($dclientname != "") {
        $filteru .= " and head.client='$client'";
        $filterp .= " and head.clientid=" . $clientid;
      }
      if ($agentname != "") {
        $filteru .= " and head.agent='$agent'";
        $filterp .= " and head.agentid=" . $agentid;
      }
    }

    if ($groupname != "") {
      $groupid = $config['params']['dataparams']['groupid'];
      $filter .= " and item.groupid=" . $groupid;
    }
    if ($classname != "") {
      $classid = $config['params']['dataparams']['classid'];
      $filter .= " and item.class=" . $classid;
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='" . $category . "'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='" . $subcat . "'";
    }

    $projectcode = $config['params']['dataparams']['projectcode'];
    $filterproj = '';
    if ($projectcode != "") {
      $projectid = $config['params']['dataparams']['projectid'];
      $filterproj = " and head.projectid=" . $projectid;
    }

    $query = "select barcode, itemname, sum(iss) as qty, a.uom, sum(ext) as ext,sum(a.cost*iss) as cost, a.isamt, sum(isqty) as isqty 
    FROM (select stock.itemid, stock.iss, stock.uom, (stock.ext-ifnull(info.lessvat,0)-ifnull(info.sramt,0)-ifnull(info.pwdamt,0)) as ext,stock.cost,stock.disc, stock.isamt, stock.isqty,
      (((ifnull(info.lessvat,0)+ifnull(info.sramt,0)+ifnull(info.pwdamt,0))/stock.iss)*uom.factor) AS otherdisc
      FROM lahead as head
      left join lastock as stock on head.trno = stock.trno
      left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
      left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
      WHERE head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' $filteru $filterproj
      
      UNION ALL

      select stock.itemid, stock.iss, stock.uom, (stock.ext-ifnull(info.lessvat,0)-ifnull(info.sramt,0)-ifnull(info.pwdamt,0)) as ext,stock.cost,stock.disc, stock.isamt, stock.isqty,
      (((ifnull(info.lessvat,0)+ifnull(info.sramt,0)+ifnull(info.pwdamt,0))/stock.iss)*uom.factor) AS otherdisc
      from glhead as head 
      left join glstock as stock on head.trno = stock.trno
      left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
      left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
      WHERE head.doc = 'SJ' and date(head.dateid) between '$start' and '$end' $filterp $filterproj
    ) as a 
    LEFT JOIN item ON item.itemid=a.itemid
    WHERE ''='' $filter
    group by barcode,itemname,a.uom, a.isamt
    order by itemname";

    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  }

  public function reportDefault($config)
  {
    $data = $this->getquery($config);
    return $data;
  }

  public function reportplotting($config)
  {
    $data = $this->reportDefault($config);
    $result = $this->SALES_RETURN_PER_ITEM($config, $data);
    return $result;
  }

  private function SALES_RETURN_PER_ITEM($params, $data)
  {
    try {
      $companyid = $params['params']['companyid'];
      $str = '';
      $font_size = 10;
      $count = 50;
      $page = 50;
      $font = $this->companysetup->getrptfont($params['params']);
      $this->reporter->linecounter = 0;
      $result = $this->reportDefault($params, $data);
      if (empty($result)) {
        return $this->othersClass->emptydata($params, $data);
      }

      $str .= $this->reporter->beginreport('800');
      $str .= $this->SALES_RETURN_PER_ITEM_HEADER($params, $data);

      $gt = 0;
      for ($i = 0; $i < count($data); $i++) {
        $cost = $price = 0;
        $qty = $data[$i]['qty'];
        if ($data[$i]['cost'] != 0) {
          $cost = $data[$i]['cost'] / $data[$i]['qty'];
        }
        if ($data[$i]['ext'] != 0) {
          $price = $data[$i]['ext'] / $data[$i]['qty'];
        }

        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['barcode'], '160', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data[$i]['itemname'], '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($qty, 2), '80', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data[$i]['uom'], '80', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($cost, 2), '90', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($price, 2), '90', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '90', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $gt += $data[$i]['ext'];
      }
      $str .= '<br>';
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '160', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '300', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '90', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('Grand Total', '90', null, false, '1px solid ', 'T', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(number_format($gt, 2), '90', null, false, '1px solid ', 'T', 'R', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();
      return $str;
    } catch (ErrorException $e) {
      echo $e;
    }
  }

  private function SALES_RETURN_PER_ITEM_HEADER($params, $data)
  {
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = 10;
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $group    = $params['params']['dataparams']['stockgrp'];
    $class    = $params['params']['dataparams']['classic'];
    $category = $params['params']['dataparams']['categoryname'];
    $brand    = $params['params']['dataparams']['brandname'];
    $part     = $params['params']['dataparams']['partname'];
    $subcat   = $params['params']['dataparams']['subcatname'];
    $clientname = $params['params']['dataparams']['clientname'];
    $agentname = $params['params']['dataparams']['agentname'];

    if ($clientname == "") {
      $clientname = "ALL";
    }

    if ($group == "") {
      $group = "ALL";
    }

    if ($class == "") {
      $class = "ALL";
    }

    if ($category == "") {
      $category = "ALL";
    }

    if ($brand == "") {
      $brand = "ALL";
    }

    if ($part == "") {
      $part = "ALL";
    }

    if ($subcat == "") {
      $subcat = "ALL";
    }

    switch ($params['params']['companyid']) {
      case 17: // UNIHOME
      case 39: //CBBSI
        $project = $params['params']['dataparams']['projectname'];
        if ($project == "") {
          $project = "ALL";
        }
        break;
    }

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES SUMMARY PER ITEM PER PRICE', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('F d, Y', strtotime($params['params']['dataparams']['start'])) . ' - ' . date('F d, Y', strtotime($params['params']['dataparams']['end'])), null, null, '', '1px solid ', '', 'l', $font, '12', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    if ($companyid == 14) { //majesty
      $str .= $this->reporter->col('Customer: ', '50', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($clientname, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Division: ', '50', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($group, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Classification: ', '50', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($class, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Group: ', '50', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($group, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Class: ', '50', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($class, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->col('Category: ', '50', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($category, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    if ($params['params']['companyid'] == 17) { //unihome
      $str .= $this->reporter->col('Agent: ', '50', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($agentname, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
    }

    switch ($params['params']['companyid']) {
      case 17: //UNIHOME
      case 39: //CBBSI
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Brand: ', '49', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($brand, '120.5', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Part: ', '49', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($part, '150.5', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Sub Category: ', '120', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($subcat, '150.5', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Project: ', '50', null, false, '1px solid', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($project, '150.5', null, false, '1px solid', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Customer: ', '50', null, false, '1px solid', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($clientname, '400', null, false, '1px solid', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        break;
      case 14: //majesty
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Brand: ', '50', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($brand, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Principal: ', '50', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($part, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Sub Category: ', '98', null, false, '1px solid ', '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($subcat, '170', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        break;
      default:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Brand: ', '50', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($brand, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Part: ', '50', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($part, '216', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Sub Category: ', '96', null, false, '1px solid ', '', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($subcat, '170', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        break;
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode', '160', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('Itemname', '300', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('Qty', '80', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('Unit', '80', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('Avg Cost', '90', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    //majesty
    $str .= $this->reporter->col(($companyid == 14 ? 'Price' : 'Avg Price'), '90', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->col('Total', '90', null, false, '1px solid ', 'B', 'C', $font, $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class
