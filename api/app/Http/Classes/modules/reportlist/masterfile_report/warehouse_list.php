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

class warehouse_list
{
  public $modulename = 'Warehouse List';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $company = $config['params']['companyid'];
    switch ($company) {
      case 3: //CONTI
        $fields = ['radioprint', 'dwhname', 'whtype'];
        break;
      default:
        if ($systemtype == "FAMS") {
          $fields = ['radioprint', 'region', 'floor', 'room'];
        } else {
          $fields = ['radioprint', 'dwhname'];
        }
        break;
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dwhname.lookupclass', 'whs');
    data_set($col1, 'dwhname.label', 'Warehouse');

    data_set($col1, 'region.readonly', true);
    data_set($col1, 'floor.lookupclass', 'lookupfloor');
    data_set($col1, 'floor.action', 'lookupfloor');
    data_set($col1, 'floor.type', 'lookup');
    data_set($col1, 'floor.required', false);

    data_set($col1, 'room.lookupclass', 'lookuproom');
    data_set($col1, 'room.action', 'lookuproom');
    data_set($col1, 'room.type', 'lookup');
    data_set($col1, 'room.required', false);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as wh,
    '' as whname,
    '' as dwhname,'' as whtype,
    '' as region,
    '' as floor,
    '' as room
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

  public function reportdatacsv($config)
  {
    $data = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating CSV successfully', 'name' => 'dr', 'data' => $data];
  }

  public function createcsv($data, $isfieldname)
  {
    $itemlist = '';
    $itemline = '';
    $fieldname = '';
    $tmpfield = '';
    $finallist = '';
    foreach ($data as $row => $value) {
      //'~'
      $itemline = '';
      foreach ($value as $row2 => $value2) {
        if ($fieldname == '' && $isfieldname == 1) {
          if ($tmpfield == '') {
            $tmpfield = $row2;
          } else {
            $tmpfield = $tmpfield . '~' . $row2;
          }
        }
        if ($itemline == '') {
          $itemline = trim($value2);
        } else {
          $itemline = $itemline . '~' . trim($value2);
        }
      }
      $itemlist = $itemlist . $itemline . PHP_EOL;
      $fieldname = $tmpfield;
    }
    if ($itemlist != '') {
      if ($isfieldname == 1) {
        $finallist = $fieldname . PHP_EOL . $itemlist . 'ENDFILE';
      } else {
        $finallist = $itemlist . 'ENDFILE';
      }
    }
    return $finallist;
  }


  public function reportplotting($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    if ($systemtype == "FAMS") {
      return $this->reportDefaultLayout_fams($config);
    } else {
      return $this->reportDefaultLayout($config);
    }
  }

  public function reportDefault($config)
  {
    // QUERY

    $whcode     = $config['params']['dataparams']['wh'];
    $type     = $config['params']['dataparams']['whtype'];

    $filter   = "";

    if ($whcode != "") {
      $filter .= " and client = '$whcode'";
    }

    if ($type != "") {
      $filter .= " and type = '$type'";
    }

    $query = "select client,clientname, addr, tel, tel2, tin from client where iswarehouse=1 and clientname<>'' $filter order by clientname";

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_fams($config)
  {
    // QUERY

    $region     = $config['params']['dataparams']['region'];
    $room     = $config['params']['dataparams']['room'];
    $floor     = $config['params']['dataparams']['floor'];

    $filter   = "";

    if ($region != "") {
      $filter .= " and cl.region = '$region'";
    }

    if ($floor != "") {
      $filter .= " and cl.floor = '$floor'";
    }

    if ($room != "") {
      $filter .= " and clinfo.room = '$room'";
    }

    $query = "select cl.client,cl.clientname, cl.addr, cl.tel, cl.tel2, cl.tin,
    cl.region, cl.floor, clinfo.room, cl.fax, cl.contact
    from client as cl
    left join clientinfo as clinfo on clinfo.clientid = cl.clientid
    where cl.iswarehouse=1 and cl.clientname<>'' 
    $filter 
    order by cl.clientname";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
    $font_size = '10';
    $padding = '';
    $margin = '';

    $whcode     = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $type       = $config['params']['dataparams']['whtype'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    if ($whname == '') {
      $whname = 'ALL WAREHOUSE';
    } else {
      $whname = $whname;
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
    $str .= $this->reporter->col('WAREHOUSE  LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($whname == '') {
      $str .= $this->reporter->col('WAREHOUSE : ALL WAREHOUSE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('WAREHOUSE : ' . strtoupper($whname), NULL, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '', '');
    }
    if ($type == '') {
      $str .= $this->reporter->col('TYPE : ALL TYPE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('TYPE : ' . strtoupper($type), NULL, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  private function default_table_cols($layoutsize, $border, $font, $font_size)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('W A R E H O U S E &nbsp N A M E', '250', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '350', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('T E L E P H O N E #', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
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
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->addr, '350', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel2, '150', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
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

  private function displayHeader_fams($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
    $font_size = '10';
    $padding = '';
    $margin = '';

    $whcode     = $config['params']['dataparams']['wh'];
    $whname     = $config['params']['dataparams']['whname'];
    $type       = $config['params']['dataparams']['whtype'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    if ($whname == '') {
      $whname = 'ALL WAREHOUSE';
    } else {
      $whname = $whname;
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WAREHOUSE  LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($whname == '') {
      $str .= $this->reporter->col('WAREHOUSE : ALL WAREHOUSE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('WAREHOUSE : ' . strtoupper($whname), NULL, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '', '');
    }
    if ($type == '') {
      $str .= $this->reporter->col('TYPE : ALL TYPE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('TYPE : ' . strtoupper($type), NULL, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('LOCATION', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('FLOOR', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ROOM', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TEL NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('MOBILE NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('FAX NO', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CONTRACT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('REGION', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout_fams($config)
  {
    $result = $this->reportDefault_fams($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
    $font_size = '10';
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
    $str .= $this->displayHeader_fams($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->addr, '150', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->floor, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->room, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel2, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fax, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->contact, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->region, '100', null, false, $border, $border_line, '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_fams($config);
        $page = $page + $count;
      }
    } //end foreach


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class