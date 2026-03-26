<?php

namespace App\Http\Classes\modules\reportlist\vehicle_scheduling_report;

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

class driver_list
{
  public $modulename = 'Driver List';
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
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $fields = ['radioprint'];

    array_push($fields, 'client', 'clientname');
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'driver');
    data_set($col1, 'client.label', 'Driver Code');
    data_set($col1, 'client.name', 'driver');
    data_set($col1, 'clientname.name', 'drivername');
    data_set($col1, 'client.label', 'Driver Name');
    data_set($col1, 'client.required', false);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
    'default' as print,
    '' as driver,
    0 as driverid,
    '' as drivername
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

  public function reportDefault($config)
  {

    $driver     = $config['params']['dataparams']['driver'];
    $driverid = $config['params']['dataparams']['driverid'];
    $drivername = $config['params']['dataparams']['drivername'];
    $companyid = $config['params']['companyid'];

    $filter   = "";
    $filter1 = "";

    if ($driver != "") {
      $filter .= " and c.client = '$driver'";
    }

    $query = "select c.client,c.clientname, c.addr, c.tel, c.tel2, c.tin
            from client as c
            where c.isdriver=1 and c.clientname <> '' $filter $filter1
            order by c.clientname";
    return $this->coreFunctions->opentable($query);
  }

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    return $this->reportDefaultLayout($config);
  }


  private function displayHeader($config)
  {

    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $client     = $config['params']['dataparams']['driver'];
    $clientname = $config['params']['dataparams']['drivername'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    if ($companyid == 3) { //conti
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
    $str .= $this->reporter->col('DRIVER  LIST', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($client == '') {
        $str .= $this->reporter->col('DRIVER : ALL DRIVER', '600', null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . $deptname, '200', null, false, $border, '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('DRIVER : ' . strtoupper($clientname), '600', null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . $deptname, '200', null, false, $border, '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($client == '') {
        $str .= $this->reporter->col('DRIVER : ALL DRIVER', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('DRIVER : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('D R I V E R &nbsp N A M E', '250', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
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
    $font = 'Century Gothic';
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
    $str .= $this->displayHeader($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->addr, '350', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel2, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }
}
