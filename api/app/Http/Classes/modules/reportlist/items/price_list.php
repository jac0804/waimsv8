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

class price_list
{
  public $modulename = 'Price List';
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

    $fields = ['radioprint', 'part', 'classname', 'categoryname', 'pricetype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'part.label', 'Principal');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as part,
    '' as partname,
    0 as partid,
    '' as categoryname,
    '' as category,
    '' as classname,
    '' as class,
    '' as pricetype
    ");
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

  public function reportDefault($config)
  {
    $query = $this->default_query($config);

    $data = $this->coreFunctions->opentable($query);
    return json_decode(json_encode($data), true);
  }

  public function reportplotting($config, $result)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 14: //majesty
        $result = $this->MAJESTY_Layout($config, $result);
        break;

      default:
        $result = $this->reportDefaultLayout($config, $result);
        break;
    }
    return $result;
  }

  public function default_query($config)
  {
    $partname   = $config['params']['dataparams']['partname'];
    $categoryname   = $config['params']['dataparams']['categoryname'];
    $classname    = $config['params']['dataparams']['classname'];
    $pricetype  = $config['params']['dataparams']['pricetype'];

    $filter = "";
    if ($partname != "") {
      $partid = $config['params']['dataparams']['partid'];
      $filter .= " and item.part=" . $partid;
    }
    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='" . $category . "' ";
    }
    if ($classname != "") {
      $class = $config['params']['dataparams']['class'];
      $filter .= " and item.class=" . $class;
    }

    switch ($pricetype) {
      case 'W':
        $str = "item.amt2,item.disc2";
        break;

      case 'A':
        $str = "item.famt,item.disc3";
        break;

      case 'B':
        $str = "item.amt4,item.disc4";
        break;

      case 'C':
        $str = "item.amt5,item.disc5";
        break;

      case 'D':
        $str = "item.amt6,item.disc6";
        break;

      case 'E':
        $str = "item.amt7,item.disc7";
        break;

      case 'F':
        $str = "item.amt8,item.disc8";
        break;

      case 'G':
        $str = "item.amt9,item.disc9";
        break;

      default:
        $str = "item.amt,item.disc";
        break;
    } //end switch

    $query = "select item.barcode, item.itemname, item.uom, " . $str . ",item.category, item.class,part.part_name as principal
          from item
          left join part_masterfile as part on part.part_id=item.part
          where item.isinactive <> '1' " . $filter . " order by item.itemname
    ";

    return $query;
  }


  private function MAJESTY_displayheader($params, $data)
  {
    $str = '';
    $font_size = '10';

    $center     = $params['params']['center'];
    $username   = $params['params']['user'];
    // $companyid = $params['params']['companyid'];
    // $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $font = $this->companysetup->getrptfont($params['params']);
    $partname   = $params['params']['dataparams']['partname'];
    $categoryname   = $params['params']['dataparams']['categoryname'];
    $classname    = $params['params']['dataparams']['classname'];
    $pricetype  = $params['params']['dataparams']['pricetype'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRICE LIST', null, null, false, '1px solid', '', 'C', $font, '18', 'B', '', '') . '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRINCIPAL : ' . ($partname != '' ? $partname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('PRICE TYPE : ' . ($pricetype != '' ? $pricetype : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CLASS : ' . ($classname != '' ? $classname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('CATEGORY : ' . ($categoryname != '' ? $categoryname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '150', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '250', null, false, '1px solid', 'B', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('PRINCIPAL', '80', null, false, '1px solid', 'B', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('UNIT', '80', null, false, '1px solid', 'B', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('PRICE', '80', null, false, '1px solid', 'B', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('DISCOUNT', '80', null, false, '1px solid', 'B', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('NET PRICE', '80', null, false, '1px solid', 'B', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function MAJESTY_Layout($params, $data)
  {
    $str = '';
    $font_size = '10';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $pricetype = $params['params']['dataparams']['pricetype'];

    $font = $this->companysetup->getrptfont($params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport('800');

    $str .= $this->MAJESTY_displayheader($params, $data);
    $amount = 0;
    $discount = '';

    for ($i = 0; $i < count($data); $i++) {

      switch ($pricetype) {
        case 'W':
          $amount = $data[$i]['amt2'];
          $discount = $data[$i]['disc2'];
          break;
        case 'A':
          $amount = $data[$i]['famt'];
          $discount = $data[$i]['disc3'];
          break;
        case 'B':
          $amount = $data[$i]['amt4'];
          $discount = $data[$i]['disc4'];
          break;
        case 'C':
          $amount = $data[$i]['amt5'];
          $discount = $data[$i]['disc5'];
          break;
        case 'D':
          $amount = $data[$i]['amt6'];
          $discount = $data[$i]['disc6'];
          break;
        case 'E':
          $amount = $data[$i]['amt7'];
          $discount = $data[$i]['disc7'];
          break;
        case 'F':
          $amount = $data[$i]['amt8'];
          $discount = $data[$i]['disc8'];
          break;
        case 'G':
          $amount = $data[$i]['amt9'];
          $discount = $data[$i]['disc9'];
          break;
        case 'H':
          $amount = $data[$i]['amt10'];
          $discount = $data[$i]['disc10'];
          break;
        case 'I':
          $amount = $data[$i]['amt11'];
          $discount = $data[$i]['disc11'];
          break;
        case 'J':
          $amount = $data[$i]['amt12'];
          $discount = $data[$i]['disc12'];
          break;
        case 'K':
          $amount = $data[$i]['amt13'];
          $discount = $data[$i]['disc13'];
          break;
        case 'L':
          $amount = $data[$i]['amt14'];
          $discount = $data[$i]['disc14'];
          break;
        case 'M':
          $amount = $data[$i]['amt15'];
          $discount = $data[$i]['disc15'];
          break;
        default:
          $amount = $data[$i]['amt'];
          $discount = $data[$i]['disc'];
          break;
      }
      $discamt = $this->othersClass->Discount($amount, $discount);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['principal'], '80', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['uom'], '80', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($amount, $decimal_currency), '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($discount, '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($discamt, $decimal_currency), '80', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

  private function report_default_header($params, $data)
  {
    $str = '';
    $font_size = '10';

    $center     = $params['params']['center'];
    $username   = $params['params']['user'];
    // $companyid = $params['params']['companyid'];
    // $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $font = $this->companysetup->getrptfont($params['params']);
    $partname   = $params['params']['dataparams']['partname'];
    $categoryname   = $params['params']['dataparams']['categoryname'];
    $classname    = $params['params']['dataparams']['classname'];
    $pricetype  = $params['params']['dataparams']['pricetype'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRICE LIST', null, null, false, '1px solid', '', 'C', $font, '18', 'B', '', '') . '<br/>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRINCIPAL : ' . ($partname != '' ? $partname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('PRICE TYPE : ' . ($pricetype != '' ? $pricetype : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CLASS : ' . ($classname != '' ? $classname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('CATEGORY : ' . ($categoryname != '' ? $categoryname : 'ALL'), '350', null, false, '1px solid', '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BARCODE', '150', null, false, '1px solid', 'B', 'L', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '250', null, false, '1px solid', 'B', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('UNIT', '100', null, false, '1px solid', 'B', 'C', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('PRICE', '100', null, false, '1px solid', 'B', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('DISCOUNT', '100', null, false, '1px solid', 'B', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->col('NET PRICE', '100', null, false, '1px solid', 'B', 'R', $font, $font_size, 'B', '', '8px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function reportDefaultLayout($params, $data)
  {
    $str = '';
    $count = 38;
    $page = 40;

    $font_size = '10';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $pricetype  = $params['params']['dataparams']['pricetype'];

    $font = $this->companysetup->getrptfont($params['params']);

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport('800');

    $str .= $this->report_default_header($params, $data);
    $amount = 0;
    $discount = '';

    for ($i = 0; $i < count($data); $i++) {

      switch ($pricetype) {
        case 'W':
          $amount = $data[$i]['amt2'];
          $discount = $data[$i]['disc2'];
          break;
        case 'A':
          $amount = $data[$i]['famt'];
          $discount = $data[$i]['disc3'];
          break;
        case 'B':
          $amount = $data[$i]['amt4'];
          $discount = $data[$i]['disc4'];
          break;
        case 'C':
          $amount = $data[$i]['amt5'];
          $discount = $data[$i]['disc5'];
          break;
        case 'D':
          $amount = $data[$i]['amt6'];
          $discount = $data[$i]['disc6'];
          break;
        case 'E':
          $amount = $data[$i]['amt7'];
          $discount = $data[$i]['disc7'];
          break;
        case 'F':
          $amount = $data[$i]['amt8'];
          $discount = $data[$i]['disc8'];
          break;
        case 'G':
          $amount = $data[$i]['amt9'];
          $discount = $data[$i]['disc9'];
          break;
        case 'H':
          $amount = $data[$i]['amt10'];
          $discount = $data[$i]['disc10'];
          break;
        case 'I':
          $amount = $data[$i]['amt11'];
          $discount = $data[$i]['disc11'];
          break;
        case 'J':
          $amount = $data[$i]['amt12'];
          $discount = $data[$i]['disc12'];
          break;
        case 'K':
          $amount = $data[$i]['amt13'];
          $discount = $data[$i]['disc13'];
          break;
        case 'L':
          $amount = $data[$i]['amt14'];
          $discount = $data[$i]['disc14'];
          break;
        case 'M':
          $amount = $data[$i]['amt15'];
          $discount = $data[$i]['disc15'];
          break;
        default:
          $amount = $data[$i]['amt'];
          $discount = $data[$i]['disc'];
          break;
      }
      $discamt = $this->othersClass->Discount($amount, $discount);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, '1px solid ', '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data[$i]['uom'], '100', null, false, '1px solid ', '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($amount, $decimal_currency), '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($discount, '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($discamt, $decimal_currency), '100', null, false, '1px solid ', '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->report_default_header($params, $data);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn



}//end class