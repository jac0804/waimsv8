<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class supplier_list
{
  public $modulename = 'Supplier List';
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $fields = ['radioprint', 'area', 'region', 'province', 'radiosortby'];
        break;
      default:
        if ($systemtype == "FAMS") {
          $fields = ['radioprint', 'groupid', 'area', 'region', 'province'];
        } else {
          $fields = ['radioprint', 'area', 'region', 'province'];
        }
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'area.readonly', true);
    data_set($col1, 'region.readonly', true);
    data_set($col1, 'province.readonly', true);
    data_set($col1, 'radiosortby.options', [
      ['label' => 'Supplier', 'value' => 'clientname', 'color' => 'orange'],
      ['label' => 'Area', 'value' => 'area', 'color' => 'orange']
    ]);
    if ($companyid == 56 || $companyid == 60) { //homework//transpower
      data_set($col1, 'radioprint.options', [
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
      ]);
    }

    data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
    data_set($col1, 'groupid.action', 'lookupclientgroupledger');
    data_set($col1, 'groupid.class', 'csgroup ');
    data_set($col1, 'groupid.readonly', true);

    if ($config['params']['companyid'] == 8) { //maxipro
      $fields = ['radioposttype', 'print'];
      $col2 = $this->fieldClass->create($fields);

      data_set(
        $col2,
        'radioposttype.options',
        [
          ['label' => 'Supplier', 'value' => '0', 'color' => 'teal'],
          ['label' => 'Subcontractor', 'value' => '1', 'color' => 'teal'],
          ['label' => 'All', 'value' => '2', 'color' => 'teal']
        ]
      );
    } else {
      $fields = ['print'];
      $col2 = $this->fieldClass->create($fields);
    }

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    // NAME NG INPUT YUNG NAKA ALIAS

    $paramstr = "select 
    'default' as print,
    '' as area,
    '' as region,
    '' as province,
    'clientname' as sortby,
    0 as groupid,
    '0' as posttype";

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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];

    if ($systemtype == "FAMS") {
      return $this->reportDefaultLayout_fams($config);
    } else {
      return $this->reportDefaultLayout($config);
    }
  }

  public function reportDefault($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];

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

    if ($config['params']['companyid'] == 8) { //maxipro
      $posttype   = $config['params']['dataparams']['posttype'];
      switch ($posttype) {
        case 0:
          $filter .= " and issupplier = 1";
          break;
        case 1:
          $filter .= " and iscontractor = 1";
          break;
        case 2:
          $filter .= " and (iscontractor = 1 or issupplier = 1)";
          break;
      }
    }

    $query = "select client,clientname, province,region,area, addr, tel, tin,tel2,contact,email,accountname,accountnum
              from client 
              where issupplier=1 and clientname<>'' 
              $filter
              order by $sortby";

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_fams($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $group = $config['params']['dataparams']['groupid'];

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
    if ($group != "") {
      $filter .= " and groupid = '$group'";
    }

    $query = "select client, clientname, province, region, area, addr, tel, tel2, fax, tin, groupid,
              email, tin, left(start, 10) as start, area, status, contact, terms
              from client 
              where issupplier=1 and clientname<>'' 
              $filter";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeaderTable($config)
  {
    if ($config['params']['companyid'] == 8) { //maxipro
      $layoutsize = "1950";
    } else {
      $layoutsize = "1000";
    }
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('C O D E', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('S U P P L I E R &nbsp N A M E', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '350', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T E L E P H O N E #', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T I N #', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    if ($config['params']['companyid'] == 8) { //maxipro
      $str .= $this->reporter->col('C O N T A C T &nbsp P E R S O N', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('C O N T A C T &nbsp N U M B E R', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('E M A I L &nbsp A D D R E S S', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('A C C O U N T &nbsp N A M E', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('A C C O U N T &nbsp N U M B E R', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function displayHeader($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];

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
    if ($config['params']['companyid'] == 8) { //maxipro
      $layoutsize = '1950';
    } else {
      $layoutsize = '1000';
    }
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER  LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($area == '') {
      $str .= $this->reporter->col('Area : ALL AREA', NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Area : ' . strtoupper($area), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    if ($province == '') {
      $str .= $this->reporter->col('Province : ALL PROVINCE', NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Province : ' . strtoupper($province), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    if ($region == '') {
      $str .= $this->reporter->col('Region : ALL REGION', NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Region : ' . strtoupper($region), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 64;
    $page = 63;
    $this->reporter->linecounter = 0;
    if ($config['params']['companyid'] == 8) { //maxipro
      $layoutsize = '1950';
    } else {
      $layoutsize = '1000';
    }

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
    $str .= $this->displayHeaderTable($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '350', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '125', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '125', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      if ($config['params']['companyid'] == 8) { //maxipro
        $str .= $this->reporter->col($data->contact, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->tel2, '150', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->email, '400', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->accountname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->accountnum, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader($config);
        $str .= $this->displayHeaderTable($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function displayHeadertable_fams($config)
  {
    $layoutsize = '1000';
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NAME', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TEL NO.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MOBILE NO.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FAX NO', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CONTACT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TERMS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('EMAIL', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TIN', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('START', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AREA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function displayHeader_fams($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];

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
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER  LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($area == '') {
      $str .= $this->reporter->col('Area : ALL AREA', NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Area : ' . strtoupper($area), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    if ($province == '') {
      $str .= $this->reporter->col('Province : ALL PROVINCE', NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Province : ' . strtoupper($province), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    if ($region == '') {
      $str .= $this->reporter->col('Region : ALL REGION', NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    } else {
      $str .= $this->reporter->col('Region : ' . strtoupper($region), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_fams($config)
  {
    $result = $this->reportDefault_fams($config);

    $count = 45;
    $page = 45;
    $this->reporter->linecounter = 0;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_fams($config);
    $str .= $this->displayHeadertable_fams($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel2, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->fax, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->contact, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->email, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->start, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->status, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->area, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader_fams($config);
        $str .= $this->displayHeadertable_fams($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function reportdatacsv($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];
    $companyid = $config['params']['companyid'];

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
    if ($companyid == 60) { //transpower
      $query = "select client.client as `code`, client.clientname, client.addr as `address`, client.tel as `telno`, client.fax as `faxno`, client.email, client.contact,
        client.tel2 as `mobile/tel2`, client.tin, client.status as `Status`, client.registername as `RegisteredName`
          from client where issupplier=1 and clientname<>'' $filter order by $sortby";
    } else {
      $query = "select client.client as `CODE`,client,clientname `SUPPLIER NAME`, addr as ADDRESS, tel as `TELEPHONE#`, tin as `TIN#`
                from client 
                where issupplier=1 and clientname<>'' 
                $filter
                order by $sortby";
    }
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
