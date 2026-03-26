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

class item_sales_report
{
  public $modulename = 'Item Sales Report';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'area', 'ditemname', 'groupid', 'dcentername', 'categoryname', 'subcatname', 'part', 'dwhname', 'radioposttype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'area.readonly', true);
    data_set($col1, 'groupid.readonly', true);
    data_set($col1, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-30) as start,
      adddate(left(now(),10),1) as end,
      '' as dclientname, '' as client, '' as clientname, 0 as clientid, '' as area, '' as itemname,
      0 as itemid, '' as ditemname, '' as groupid, '' as center, '' as dcentername, '' as centername,
      '' as categoryname, 0 as categoryid, '' as subcatname, '' as subcat, '' as partname, 0 as partid,
      '' as wh, '' as whname, 0 as whid, '' as dwhname, '' as part, '0' as posttype, '' as barcode, 
      '' as groupname, '' as dpartname
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
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config)
  {
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    $qry = $this->default_query($config);
    return $this->coreFunctions->opentable($qry);
  }


  public function default_query($config)
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client = $config['params']['dataparams']['client'];
    $area = $config['params']['dataparams']['area'];
    $barcode = $config['params']['dataparams']['barcode'];
    $groupid = $config['params']['dataparams']['groupid'];
    $center = $config['params']['dataparams']['center'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname = $config['params']['dataparams']['subcatname'];
    $partname = $config['params']['dataparams']['partname'];
    $wh = $config['params']['dataparams']['wh'];
    $posttype = $config['params']['dataparams']['posttype'];
    $filter = "";
    if ($client != '') $filter .= " and c.clientid=".$config['params']['dataparams']['clientid'];
    if ($area != '') $filter .= " and c.area='".$area."'";
    if ($barcode != '') $filter .= " and stock.itemid=".$config['params']['dataparams']['itemid'];
    if ($groupid != '') $filter .= " and item.groupid=".$groupid;
    if ($center != '') $filter .= " and num.center='".$center."'";
    if ($categoryname != '') $filter .= " and item.category='".$config['params']['dataparams']['category']."'";
    if ($subcatname != '') $filter .= " and item.subcat='".$config['params']['dataparams']['subcat']."'";
    if ($partname != '') $filter .= " and item.part='".$config['params']['dataparams']['part']."'";
    if ($wh != '') $filter .= " and stock.whid=".$config['params']['dataparams']['whid'];

    switch ($posttype) {
      case '0': // posted
        $query = "select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.isqty) as qty, 
          sum(stock.amt) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          left join client as c on c.clientid=head.clientid
          left join cntnum as num on num.trno=head.trno
          where head.doc in ('sj', 'cm') and item.barcode is not null and head.dateid between '$start' and '$end' $filter and item.isofficesupplies=0
          group by item.barcode, item.itemname, docno, head.dateid,item.uom, brand.brand_desc, 
          part.part_name,model.model_name,item.sizeid,item.body,stock.ref";
        break;
      case '1': // unposted
        $query = "select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.isqty) as qty, 
          sum(stock.amt) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          left join client as c on c.client=head.client
          left join cntnum as num on num.trno=head.trno
          where head.doc in ('sj', 'cm') and item.barcode is not null and date(head.dateid) between '$start' and '$end' $filter and item.isofficesupplies=0
          group by item.barcode, item.itemname, head.dateid,item.uom, brand.brand_desc, part.part_name,
          model.model_name,item.sizeid,item.body, head.docno,stock.ref";
        break;
      default: // all
        $query = "select barcode, itemname, docno, dateid , qty, price, 
          uom, brand, part,model,sizeid,body,ponum
          from (
          select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.isqty) as qty, 
          sum(stock.amt) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          left join client as c on c.client=head.client
          left join cntnum as num on num.trno=head.trno
          where head.doc in ('sj', 'cm') and item.barcode is not null and date(head.dateid) between '$start' and '$end' $filter and item.isofficesupplies=0
          group by item.barcode, item.itemname, head.dateid,item.uom, brand.brand_desc, part.part_name,
          model.model_name,item.sizeid,item.body, head.docno,stock.ref
          UNION ALL
          select item.barcode, item.itemname, head.docno, date(head.dateid) as dateid, sum(stock.isqty) as qty, 
          sum(stock.amt) as cost, sum(stock.ext) as price, item.uom, brand.brand_desc as brand, 
          part.part_name as part,model.model_name as model,item.sizeid,item.body,stock.ref as ponum
          from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join part_masterfile as part on part.part_id = item.part
          left join model_masterfile as model on model.model_id = item.model
          left join client as c on c.clientid=head.clientid
          left join cntnum as num on num.trno=head.trno
          where head.doc in ('sj', 'cm') and item.barcode is not null and head.dateid between '$start' and '$end' $filter and item.isofficesupplies=0
          group by item.barcode, item.itemname, docno, head.dateid,item.uom, brand.brand_desc, 
          part.part_name,model.model_name,item.sizeid,item.body,stock.ref) as ip
          order by brand, part, itemname";
        break;
    }

    return $query;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $groupname = $config['params']['dataparams']['groupname'];
    $centername = $config['params']['dataparams']['centername'];
    $categoryname = $config['params']['dataparams']['categoryname'];
    $subcatname = $config['params']['dataparams']['subcatname'];
    $partname = $config['params']['dataparams']['partname'];
    $whname = $config['params']['dataparams']['whname'];
    $barcode = $config['params']['dataparams']['barcode'];
    $clientname = $config['params']['dataparams']['clientname'];

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
        $str .= $this->reporter->col('ITEM PURCHASE REPORT', null, null, false, $border, '', 'C', $font, '15', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, '', $border, '', 'l', '', '10', '', '', '');
        $str .= $this->reporter->col('Items : ' . ($barcode == '' ? 'ALL' : $barcode), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('WH : ' . ($whname == '' ? 'ALL' : $whname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Group : ' . ($groupname == '' ? 'ALL' : $groupname), null, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
        $str .= $this->reporter->col('Category : '.($categoryname == '' ? 'ALL' : $categoryname), null, null, false, '1px solid', '', 'L', $font, $font_size, '', '', $padding, $margin);
        $str .= $this->reporter->col('Sub-Category : ' . ($subcatname == '' ? 'ALL' : $subcatname), null, null, false, '1px solid ', '', 'L', $font, $font_size, '', '', $padding, $margin);
        $str .= $this->reporter->col('Part : ' . ($partname == '' ? 'ALL' : $partname), null, null, false, '1px solid', '', 'L', $font, $font_size, '', '', $padding, $margin);
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer : '.($clientname == '' ? 'ALL' : $clientname), '500', null, false, '1px solid', '', 'L', $font, $font_size, '', '', $padding, $margin);
        $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM CODE', '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('ITEM DESCRIPTION', '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('SIZE', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('UOM', '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('QTY', '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
        $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '8';
    $result = $this->reportDefault($config);
    $count = 26;
    $page = 25;

    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $item = null;
    $subtotal = 0;
    $amt = 0;
    $totalcount = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($item != $data->barcode) {
        if ($item != "") {
          $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp;', '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->endrow();
          $subtotal = 0;
        }
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->barcode, '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->sizeid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->docno, '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->qty,2), '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->price,2), '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data->barcode, '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->sizeid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->docno, '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->qty,2), '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->price,2), '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      }
      $item = $data->barcode;
      $totalcount += $data->qty;
      $subtotal = $subtotal + $data->price;
      $amt = $amt + $data->price;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('GRAND TOTAL', '110', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TLRB', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '125', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, '', '', '', '');
      $str .= $this->reporter->col(number_format($totalcount,2), '90', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($amt,2), '100', null, false, $border, 'TLBR', 'C', 'Verdana', $font_size, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class