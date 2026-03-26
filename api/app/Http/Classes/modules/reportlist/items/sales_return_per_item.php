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

class sales_return_per_item
{
  public $modulename = 'Sales Return Per Item';
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
    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

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
       left(now(),10) as end
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

  public function getquery($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end      = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $query = "select barcode, itemname, sum(qty) as qty, uom, amt, sum(ext) as ext, disc, isamt, sum(rrqty) as rrqty, abs(sum(lessvat)) as lessvat, abs(sum(pwdamt)) as pwdamt, abs(sum(sramt)) as sramt from (
          select head.doc, head.dateid, item.barcode, item.itemname, stock.qty, stock.uom, stock.amt, (stock.ext-abs(info.lessvat+info.pwdamt+info.sramt)) as ext, stock.disc, stock.isamt, stock.rrqty, info.lessvat, info.pwdamt, info.sramt
          from lahead as head
          left join lastock as stock on head.trno = stock.trno left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join item on item.itemid = stock.itemid
          where head.doc='CM' and head.dateid between '$start' and '$end' 
          union all 
          select head.doc, head.dateid, item.barcode, item.itemname, stock.qty, stock.uom, stock.amt, (stock.ext-abs(info.lessvat+info.pwdamt+info.sramt)) as ext, stock.disc, stock.isamt, stock.rrqty, info.lessvat, info.pwdamt, info.sramt
          from glhead as head
          left join glstock as stock on head.trno = stock.trno left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
          left join item on item.itemid = stock.itemid
          where head.doc='CM' and head.dateid between '$start' and '$end' 
          ) as a
          group by barcode, itemname, uom, amt, disc, isamt, rrqty
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

      $font = $this->companysetup->getrptfont($params['params']);
      $font_size = '10';
      $str = '';
      $this->reporter->linecounter = 0;
      $str .= $this->reporter->beginreport('1000');
      $str .= $this->SALES_RETURN_PER_ITEM_HEADER($params, $data);

      $gt = 0;
      for ($i = 0; $i < count($data); $i++) {
        if ($companyid == 14) { //majesty
          $qty = $data[$i]['rrqty'];
          $netprice = $this->othersClass->Discount($data[$i]['isamt'], $data[$i]['disc']);
        } else {
          $qty = $data[$i]['qty'];
          $netprice = $data[$i]['amt'];
        }
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['barcode'], '160', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data[$i]['itemname'], '300', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($qty, 2), '80', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data[$i]['uom'], '80', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($netprice, 2), '90', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['lessvat'], 2), '90', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['sramt'] + $data[$i]['pwdamt'], 2), '90', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '90', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $gt += $data[$i]['ext'];
      }
      $str .= '<br>';
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '160', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '300', null, false, '1px solid ', 'T', 'C', 'Century Gothicw', '10', 'B', '', '');
      $str .= $this->reporter->col('', '80', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '280', null, false, '1px solid ', 'T', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '90', null, false, '1px solid ', 'T', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '90', null, false, '1px solid ', 'T', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('Grand Total:', '90', null, false, '1px solid ', 'T', 'L', $font, $font_size, 'B', '', '');
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
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';

    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('1000');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES RETURN PER ITEM', null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('F d, Y', strtotime($params['params']['dataparams']['start'])) . ' - ' . date('F d, Y', strtotime($params['params']['dataparams']['end'])), null, null, '', '1px solid ', '', 'l', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Barcode', '160', null, false, '1px solid ', 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Itemname', '300', null, false, '1px solid ', 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Qty', '80', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Unit', '80', null, false, '1px solid ', 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Net Price', '90', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Less VAT', '90', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Senior/PWD Disc', '90', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Total', '90', null, false, '1px solid ', 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class
