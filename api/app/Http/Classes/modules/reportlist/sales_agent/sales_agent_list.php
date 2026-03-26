<?php

namespace App\Http\Classes\modules\reportlist\sales_agent;

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

class sales_agent_list
{
  public $modulename = 'Sales Agent List';
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
    $fields = ['radioprint', 'area', 'region', 'province'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'area.readonly', true);
    data_set($col1, 'region.readonly', true);
    data_set($col1, 'province.readonly', true);
    if ($config['params']['companyid'] == 60) { //transpower
      data_set($col1, 'radioprint.options', [
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
      ]);
    }
    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as area,
    '' as region,
    '' as province
    ");
  }

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
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];

    $filter   = "";

    if ($area != "") {
      $filter .= " and area = '$area'";
    }
    if ($region != "") {
      $filter .= " and region = '$region'";
    }
    if ($province != "") {
      $filter .= " and province = '$province'";
    }

    $query = "select client,clientname, province,region,area, addr, tel, tin from client where isagent=1 and clientname<>'' $filter ";

    return $this->coreFunctions->opentable($query);
  }


  private function displayHeader($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    if ($area == '') {
      $area = 'ALL AREA';
    } else {
      $area = $area;
    }
    if ($region == '') {
      $region = 'ALL REGION';
    } else {
      $region = $region;
    }
    if ($province == '') {
      $province = 'ALL PROVINCE';
    } else {
      $province = $province;
    }

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES AGENT LIST', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Area :' . $area, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Region :' . $region, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Province :' . $province, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    return $str;
  }

  public function displayHeaderTable($config)
  {
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AGENT NAME', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('ADDRESS', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TELEPHONE #', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TIN #', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 55;
    $page  = 55;
    $this->reporter->linecounter = 0;
    $str   = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport('1000');

    $str .= $this->displayHeader($config);
    $str .=  $this->displayHeaderTable($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->client, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader($config);
        $str .= $this->displayHeaderTable($config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportdatacsv($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];

    $filter   = "";
    if ($area != "") $filter .= " and area = '$area'";
    if ($region != "") $filter .= " and region = '$region'";
    if ($province != "") $filter .= " and province = '$province'";
    $query = "select client as `AgentCode`, clientname as `AgentName`, addr as `Address`, tin as `Tin`, tel as `TelNo`, fax as `FaxNo`, tel2 as `Mobile`,
      email as `E-mail`, contact as `Contact` from client where isagent=1 and clientname<>'' $filter order by client";
    $data = $this->coreFunctions->opentable($query);
    $status =  true;
    $msg = 'Generating CSV successfully';
    if (empty($data)) {
      $status =  false;
      $msg = 'No data Found';
    }
    return ['status' => $status, 'msg' => $msg, 'data' => $data, 'params' => $this->reportParams, 'name' => 'ItemList'];
  }
}
