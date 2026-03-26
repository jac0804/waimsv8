<?php

namespace App\Http\Classes\modules\reportlist\masterfile_report;

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


class chart_of_accounts
{
  public $modulename = 'Chart of Accounts';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dacnoname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.lookupclass', 'replookupcoa');
    data_set($col2, 'dacnoname.action', 'lookupcoa');

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {

    return $this->coreFunctions->opentable("select 'default' as print, '' as contra,'' as acnoname,'' as dacnoname");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' =>$str, 'params' => $this->reportParams];

  }


  public function default_query($filters)
  {
    $contra = $filters['params']['dataparams']['contra'];
    $filter = '';
    if ($contra != "") {
      $filter .= " where coa.parent = '\\" . $contra . "'";
    } //end if

    $query = "select coa.acno, coa.acnoname, coa.alias, coa.type, coa.levelid from coa " . $filter . " order by coa.acno, coa.acnoname";
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {

    $result = $this->default_query($config);
    $reportdata =  $this->DEFAULT_COA_LAYOUT($result, $config);


    return $reportdata;
  }


  private function DEFAULT_COA_LAYOUT($data, $params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $this->reporter->linecounter = 0;
    $count = 70;
    $page = 70;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->GENERATE_DEFAULT_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);

    foreach ($data as $key => $value) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $indent = '5' * ($value->levelid * 3);

      $str .= $this->reporter->col($value->acno, '200px', null, false, $border, $border_line, $alignment, $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->acnoname, '400', null, false, $border, $border_line, $alignment, $font, $font_size, '', '', '0px 0px 0px ' . $indent . 'px');
      $str .= $this->reporter->col($value->alias, '100px', null, false, $border, $border_line, $alignment, $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->type, '100px', null, false, $border, $border_line, $alignment, $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->GENERATE_DEFAULT_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);
        $page = $page + $count;
      } //end if
    } //end for each and everyday


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  private function GENERATE_DEFAULT_HEADER($params)
  {
    $companyid = $params['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($params['params']); //FONT UPDATED
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';

   
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
   
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CHART OF ACCOUNTS', '100px', null, false, '10px solid ', '', '', $font, '24', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow(null, null, false, $border, $border_line, $alignment, $font, $font_size, '', '', '');
    $str .= $this->reporter->col('Accounts :', '70', null, false, $border, $border_line, '', $font, $font_size, 'B', '', '');

    if ($params['params']['dataparams']['acnoname'] != "") {
      $accname = $params['params']['dataparams']['acnoname'];
    } else {
      $accname = "ALL";
    }

    $str .= $this->reporter->col($accname, '700', null, false, $border, $border_line, $alignment, $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();


    return $str;
  } //end fn

  private function default_table_cols($layoutsize, $border, $font, $fontsize)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '200px', null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ACCOUNT NAME', '400px', null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ALIAS', '100px', null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TYPE', '100px', null, false, $border, 'TB', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

}//end class