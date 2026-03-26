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

class location_list
{
  public $modulename = 'Location List';
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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'radioreporttype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Occuppied', 'value' => 'occupied', 'color' => 'orange'],
      ['label' => 'Vacant', 'value' => 'vacant', 'color' => 'orange'],
      ['label' => 'All', 'value' => 'all', 'color' => 'orange'],
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
      'all' as reporttype
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    $companyid = $config['params']['companyid'];

    $filter   = "";

    $query = "select line, code, name, area from loc order by line";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $companyid = $config['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';


    $reporttype     = $config['params']['dataparams']['reporttype'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    if ($companyid == 3) {
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
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
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LOCATION  LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Report Type : ' . strtoupper($reporttype), NULL, null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('NAME', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('AREA', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TENANT', '200', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $reporttype     = $config['params']['dataparams']['reporttype'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    switch ($reporttype) {
      case 'occupied':
        foreach ($result as $key => $data) {
          $loc = $this->coreFunctions->opentable("select clientname from client where locid = '" . $data->line . "' and istenant = 1 and isinactive = 0");
          foreach ($loc as $lkey => $ldata) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col($data->code, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->name, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->area, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($ldata->clientname, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->page_break();
              $str .= $this->displayHeader($config);
              $page = $page + $count;
            }
          }
        } //end foreach
        break;
      case 'vacant':
        $cl_locid = $this->coreFunctions->opentable("select group_concat(locid) as locid from client where istenant = 1 and isinactive = 0");
        $locid = "(" . $cl_locid[0]->locid . ")";

        $location = $this->coreFunctions->opentable("select code, name, area from loc where line not in $locid");
        foreach ($location as $lkey => $ldata) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($ldata->code, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($ldata->name, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($ldata->area, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col("", '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config);
            $page = $page + $count;
          }
        }
        break;
      default:
        foreach ($result as $lkey => $data) {
          $clientname = $this->coreFunctions->datareader("select clientname as value from client where locid = '" . $data->line . "' and istenant = 1 and isinactive = 0");

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($data->code, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->name, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($data->area, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          if ($clientname != "") {
            $str .= $this->reporter->col($clientname, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          } else {
            $str .= $this->reporter->col("", '200', null, false, $border, '', '', $font, $font_size, '', '', '');
          }
          $str .= $this->reporter->endrow();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->displayHeader($config);
            $page = $page + $count;
          }
        }

        break;
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);

    $vacant = 0;
    $occupied = 0;
    $totalloc = 0;
    #VACANT
    $cl_locid = $this->coreFunctions->opentable("select group_concat(locid) as locid from client where istenant = 1 and isinactive = 0");
    $locid = "(" . $cl_locid[0]->locid . ")";
    $location = $this->coreFunctions->opentable("select area from loc where line not in $locid group by area");
    foreach ($location as $k => $data) {
      $vacant += $data->area;
    }
    #OCCUPIED
    foreach ($result as $i => $data) {
      $loc = $this->coreFunctions->opentable("select area from client where locid = '" . $data->line . "' and istenant = 1 and isinactive = 0");
      $occupied += $data->area;
    }
    $totalloc = $vacant + $occupied;
    if ($reporttype == 'all') {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('OCCUPIED - ' . $occupied . ' ' . 'VACANT - ' . $vacant . ' ' . 'TOTAL LOCATION - ' . $totalloc, '200', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class