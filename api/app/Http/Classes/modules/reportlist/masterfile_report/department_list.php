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

class department_list
{
  public $modulename = 'Department List';
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
    $fields = ['radioprint', 'dclientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'replookupdepartment');
    if ($companyid == 24) { //goodfound
      data_set($col1, 'dclientname.label', 'Cost Center');
    } else {
      data_set($col1, 'dclientname.label', 'Department');
    }

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as client,
    '' as clientname,
    '' as dclientname
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

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and client = '$client'";
    }

    $query = "select client,clientname, addr, tel, tel2, tin from client where isdepartment=1 $filter order by clientname";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {
    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
    $font_size = '10';
    $font_weight = 'B'; // B for bold letter I for Italic Blank normal font weight 
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';

  
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT  LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, $font_size, '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  private function default_table_cols($layoutsize, $border, $font, $fontsize)
  {
    $str = '';
    $font_weight = 'B';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '200', null, false, $border, 'TB', 'L', $font, $fontsize, $font_weight, '', '');
    $str .= $this->reporter->col('D E P A R T M E N T &nbsp N A M E', '300', null, false, $border, 'TB', 'L', $font, $fontsize, $font_weight, '', '');
    $str .= $this->reporter->col('A D D R E S S', '350', null, false, $border, 'TB', 'L', $font, $fontsize, $font_weight, '', '');
    $str .= $this->reporter->col('T E L E P H O N E #', '150', null, false, $border, 'TB', 'L', $font, $fontsize, $font_weight, '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
    $font_size = '10';
    $fontsize12 = 12;
    $font_weight = 'B'; // B for bold letter I for Italic Blank normal font weight 
    $padding = '';
    $margin = '';

    $this->reporter->linecounter = 0;
    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '200', null, false, '10px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, '10px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->addr, '350', null, false, '10px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->col($data->tel2, '150', null, false, '10px solid ', '', 'L', $font, '10', '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->displayHeader($config);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize12);
        $page = $page + $count;
      }
    } //end foreach


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class