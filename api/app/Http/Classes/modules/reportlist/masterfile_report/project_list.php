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

class project_list
{
  public $modulename = 'Project List';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

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
    $company = $config['params']['companyid'];

    $fields = ['radioprint', 'project'];

    if ($company == 10 || $company == 12) {


      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'project.lookupclass', 'master');
      data_set($col1, 'project.required', false);
      data_set($col1, 'project.label', 'Item Group');
    } else


      $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'project.lookupclass', 'master');
    data_set($col1, 'project.required', false);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as project,
    '' as projectcode,
    '' as projectid,
    '' as projectname
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

    $projid     = $config['params']['dataparams']['projectid'];
    $filter   = "";

    if ($projid != "") {
      $filter .= " and line = '$projid'";
    }

    $query = "select code,name from projectmasterfile where 1=1 $filter order by line";
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
    $padding = '';
    $margin = '';

    $projname     = $config['params']['dataparams']['projectname'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    if ($projname == '') {
      $projname = 'ALL PROJECT';
    } else {
      $projname = $projname;
    }

    $str = '';
    $layoutsize = '1000';

   
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT  LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($projname == '') {
      $str .= $this->reporter->col('PROJECT : ALL PROJECT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('PROJECT : ' . strtoupper($projname), NULL, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    return $str;
  }



  private function default_table_cols($layoutsize, $border, $font, $fontsize)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('P R O J E C T &nbsp N A M E', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
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
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $this->reporter->linecounter = 0;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);


    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->code, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->name, '250', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->displayHeader($config);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);
        $page = $page + $count;
      }
    } //end foreach


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class