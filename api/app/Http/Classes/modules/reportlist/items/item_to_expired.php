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


class item_to_expired
{
  public $modulename = 'Item to Expired';
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

    $fields = ['radioprint', 'start', 'categoryname', 'subcatname', 'brand'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'project.label', 'Item Group/Project');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'start.label', 'Balance as of');
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');
    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      left(now(),10) as start,
      '' as category,
      '' as subcat,
      '' as subcatname,
      '' as categoryname,
      '' as brand,
      0 as brandid,
      '' as brandname,
      '' as project,
      0 as projectid,
      '' as projectname";

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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 14: //majesty
        $result = $this->MAJESTY_Layout($config);
        break;
      default:
        $result = $this->reportDefaultLayout($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    // $center    = $config['params']['center'];
    $asof       = date("Y/m/d", strtotime($config['params']['dataparams']['start']));
    $companyid = $config['params']['companyid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcatname'];
    $brandname     = $config['params']['dataparams']['brandname'];

    $filter = "";
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $project = $config['params']['dataparams']['project'];
      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
        $filter .= " and item.projectid=" . $projectid;
      }
    } else {
      $filter .= "";
    }

    if ($categoryname != "") {
      $category = $config['params']['dataparams']['category'];
      $filter .= " and item.category='$category'";
    }
    if ($subcatname != "") {
      $subcat = $config['params']['dataparams']['subcat'];
      $filter .= " and item.subcat='$subcat'";
    }
    if ($brandname != "") {
      $brandid = $config['params']['dataparams']['brandid'];
      $filter .= " and item.brand=" . $brandid;
    }

    $addonfield = '';
    if ($companyid == 14) { //majesty
      $addonfield = ',rr.cost,item.uom';
    }

    $query = "select item.sizeid as size,item.brand,ifnull(mm.model_name,'') as model,
    item.barcode, item.itemname, rr.dateid, rr.docno,
    ifnull(sum(rr.qty),0) as qty,
    ifnull(sum(rr.bal),0) as bal, rr.expiry as expdate,
    datediff(now(), rr.expiry) as no_day,
    ifnull(cat.name, '') as category, ifnull(subcat.name, '') as subcatname, ifnull(brand.brand_desc, '') as brand $addonfield
    
    from rrstatus as rr
    left join item on item.itemid=rr.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join client as wh on wh.clientid=rr.whid
    left join client on client.clientid=rr.clientid
    left join cntnum on cntnum.trno=rr.trno

    left join itemcategory as cat on cat.line = item.category
    left join itemsubcategory as subcat on subcat.line = item.subcat
    left join frontend_ebrands as brand on brand.brandid = item.brand

    where rr.bal<>0 and rr.expiry >= '$asof' $filter and item.isofficesupplies=0
    and rr.expiry <> ''
    group by item.sizeid,item.brand,mm.model_name,
    item.barcode, item.itemname, rr.dateid, rr.docno,
    rr.expiry,cat.name, subcat.name, brand.brand_desc $addonfield
    order by rr.expiry";

    return $this->coreFunctions->opentable($query);
  }


  private function MAJESTY_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $projname = "";
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM TO EXPIRE', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL',  '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname,  '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('BARCODE', '150', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('ITEMNAME', '250', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('COST', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '150', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('EXPIRED DATE', '150', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function MAJESTY_Layout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->MAJESTY_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '0px 0px 0px 40px');
      $str .= $this->reporter->col($data->brand, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->itemname, '350', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->uom, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->cost, 2), '100', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->bal, 2), '150', null, false, $border, '', 'R', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col(date("m/d/Y", strtotime($data->expdate)), '150', null, false, $border, '', 'C', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function default_displayHeader($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $categoryname  = $config['params']['dataparams']['categoryname'];
    $subcatname =  $config['params']['dataparams']['subcat'];

    $projname = "";
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM TO EXPIRED', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project : ' . $projname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL',  '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . $categoryname,  '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL', '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    } else {
      $subcatname =  $config['params']['dataparams']['subcatname'];
      $str .= $this->reporter->col('Sub-Category : ' . $subcatname, '150', null, '', $border, '', 'L', $font, $font_size, '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('BARCODE', '150', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('BRAND', '150', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('ITEMNAME', '350', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', '150', null, false, $border, 'B', 'R', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('EXPIRED DATE', '200', null, false, $border, 'B', 'C', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $result = $this->reportDefault($config);
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'LT', $font, $font_size, '', '', '0px 0px 0px 40px');
      $str .= $this->reporter->col($data->brand, '150', null, false, $border, '', 'CT', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->itemname, '350', null, false, $border, '', 'LT', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->bal, 2), '150', null, false, $border, '', 'RT', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->expdate, '200', null, false, $border, '', 'CT', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class