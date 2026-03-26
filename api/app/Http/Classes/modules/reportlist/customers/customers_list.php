<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class customers_list
{
  public $modulename = 'Customers List';
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

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $fields = ['radioprint', 'area', 'region', 'province', 'agentname', 'radiosortby'];
        break;
      case 21: //kinggeorge
        $fields = ['radioprint', 'start', 'end', 'area', 'region', 'province', 'agentname'];
        break;
      case 32: //3m
        $fields = ['radioprint', 'brgy', 'area', 'region', 'province'];
        break;
      case 35: //aquamax
        $fields = ['radioprint', 'brgy', 'area', 'region', 'province'];
        break;
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'area', 'region', 'province', 'cur', 'industry'];
        break;
      default:
        $fields = ['radioprint', 'area', 'region', 'province'];
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'brgy.readonly', true);
    data_set($col1, 'area.readonly', true);
    data_set($col1, 'region.readonly', true);
    data_set($col1, 'province.readonly', true);
    data_set($col1, 'radiosortby.options', [
      ['label' => 'Customer', 'value' => 'clientname', 'color' => 'orange'],
      ['label' => 'Area', 'value' => 'area', 'color' => 'orange']
    ]);
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.action', 'lookuprandom');
        data_set($col1, 'industry.readonly', true);
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        break;
      case 56: //homework
      case 60: //transpower
        data_set($col1, 'radioprint.options', [
          ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
          ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
        ]);
        break;
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
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as brgy,
    '' as area,
    '' as region,
    '' as province,
    '' as agentname,
    '' as agent,
    '0' as agentid,
    '' as cur,
    '' as industry,
    'clientname' as sortby
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
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    switch ($companyid) {
      case 10: //aftiphp
      case 12: //aftiusd
        return $this->AftiCustomerListReport($config);
        break;
      case 34: //evergreen
        return $this->EvergreenLayout($config);
        break;
      case 35: //aquamax
        return $this->AquamaxLayout($config);
        break;
      case 21: //kg
        return $this->kglayout($config);
        break;
      default:
        return $this->reportDefaultLayout($config);
        break;
    }
  }



  //default

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];
    $agentname = "";
    $agentid = "";
    $agent = "";
    if (isset($config['params']['dataparams']['agentname'])) {
      $agentname   = $config['params']['dataparams']['agentname'];
      $agent   = $config['params']['dataparams']['agent'];
      $agentid   = $config['params']['dataparams']['agentid'];
    }

    $filter   = "";
    $addleftjoin = "";
    $addfields = "";
    $billaddr = ",concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress";

    if ($area != "") {
      $filter .= " and cust.area = '$area'";
    }

    if ($region != "") {
      $filter .= " and cust.region = '$region'";
    }
    if ($province != "") {
      $filter .= " and cust.province = '$province'";
    }
    if ($companyid == 21) { //kinggeorge
      $filter .= " and date(cust.start) between '$start' and '$end'";
    }

    if ($agent != '') {
      $filter .= " and agent.clientid='$agentid'";
    }

    $query = "select 
      cust.client,cust.clientname, cust.province,cust.region,cust.area, cust.addr, cust.email , cust.tel, cust.tel2, cust.tin, cust.contact,
      agent.client as agentcode, agent.clientname as agentname,cust.terms
      " . $billaddr . ", cust.industry,cat.cat_name as category,cust.groupid,cust.brgy " . $addfields . "
      from client as cust
      left join client as agent on agent.client = cust.agent
      left join billingaddr as bill on  bill.clientid = cust.clientid and bill.line = cust.billid
      Left join category_masterfile as cat on cat.cat_id = cust.category
      " . $addleftjoin . "
      where cust.iscustomer=1 and cust.clientname<>'' 
      $filter 
      order by cust.$sortby";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config, $recordCount)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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

    // total num head
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
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

    if ($config['params']['resellerid'] == 2) {
      $str .= $this->reporter->col('Total No. of Customers: ' . $recordCount, NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function displayHeadertable($config)
  {
    $str = "";
    $companyid = $config['params']['companyid'];

    $layoutsize = '1000';


    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('C O D E', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('C U S T O M E R &nbsp N A M E', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '350', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T E L E P H O N E #', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T I N #', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A G E N T', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 33;
    $page = 34;

    $layoutsize = '1000';

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config, count($result));
    $str .= $this->displayHeadertable($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $str .= $this->reporter->col($data->client, '130', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '350', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '140', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '140', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '140', null, false, $border, '', '', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader($config, count($result));
        $str .= $this->displayHeadertable($config);
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


  private function kgdisplayHeadertable($config)
  {
    $str = "";

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('C O D E', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('C U S T O M E R &nbsp N A M E', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T E L #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T I N #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A G E N T', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T E R M S', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }


  public function kglayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 33;
    $page = 34;

    $layoutsize = '1000';

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config, count($result));
    $str .= $this->kgdisplayHeadertable($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $str .= $this->reporter->col($data->client, '120', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '250', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->terms, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeader($config, count($result));
        $str .= $this->kgdisplayHeadertable($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }





  //default

  //AQUAMAX 35

  public function reportAquamax($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $agent   = $config['params']['dataparams']['agent'];

    $filter   = "";

    if ($area != "") {
      $filter .= " and cust.area = '$area'";
    }
    if ($region != "") {
      $filter .= " and cust.region = '$region'";
    }
    if ($province != "") {
      $filter .= " and cust.province = '$province'";
    }


    $query = "select 
      cust.client,cust.clientname, cust.province,cust.region,cust.area, cust.addr, cust.email , cust.tel, cust.tel2, cust.tin, cust.contact,
      agent.client agentcode, agent.clientname as agentname ,
      concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress, cust.industry,cat.cat_name as category,cust.groupid,cust.brgy
      from client as cust
      left join client as agent on agent.client = cust.agent
      left join billingaddr as bill on  bill.clientid = cust.clientid and bill.line = cust.billid
      Left join category_masterfile as cat on cat.cat_id = cust.category
      where cust.iscustomer=1 and cust.clientname<>'' 
      $filter 
      order by cust.$sortby";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeaderAquamax($config, $recordCount)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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

    // total num head
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
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

    if ($config['params']['resellerid'] == 2) {
      $str .= $this->reporter->col('Total No. of Customers: ' . $recordCount, NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function displayHeadertableAquamax($config)
  {
    $str = "";
    $companyid = $config['params']['companyid'];
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('C O D E', '120', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('C U S T O M E R &nbsp N A M E', '180', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('E M A I L', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T E L E P H O N E #', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T I N #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A G E N T', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function AquamaxLayout($config)
  {
    $result = $this->reportAquamax($config);

    $count = 50;
    $page = 50;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeaderAquamax($config, count($result));
    $str .= $this->displayHeadertableAquamax($config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->client, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->email, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeaderAquamax($config, count($result));
        $str .= $this->displayHeadertableAquamax($config);
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
  //AQUAMAX

  //evergreen 34

  public function reportEvergreen($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $agent   = $config['params']['dataparams']['agent'];

    $filter   = "";

    if ($area != "") {
      $filter .= " and cust.area = '$area'";
    }

    if ($region != "") {
      $filter .= " and cust.region = '$region'";
    }
    if ($province != "") {
      $filter .= " and cust.province = '$province'";
    }



    $query = "select e.clientname as planholder,
      cust.client,cust.clientname, cust.province,cust.region,cust.area, cust.addr,cust.tel, cust.tel2, cust.tin, cust.contact,
      agent.client agentcode, agent.clientname as agentname ,
      concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress, cust.industry,cat.cat_name as category,cust.groupid,cust.brgy
      from client as cust
      left join client as agent on agent.client = cust.agent
      left join billingaddr as bill on  bill.clientid = cust.clientid and bill.line = cust.billid
      Left join category_masterfile as cat on cat.cat_id = cust.category
      left join heahead as ea on ea.client = cust.client
      left join heainfo as e on e.trno = ea.trno
      where cust.iscustomer=1 and cust.clientname<>'' 
      $filter 
      order by cust.client,e.clientname";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeaderEvergreen($config, $recordCount)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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

    // total num head
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
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

    if ($config['params']['resellerid'] == 2) {
      $str .= $this->reporter->col('Total No. of Customers: ' . $recordCount, NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function displayHeadertableEvergreen($config)
  {
    $str = "";
    $companyid = $config['params']['companyid'];

    $layoutsize = '1000';


    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('C O D E', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('P L A N  H O L D E R', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('P A Y O R', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '350', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T E L E P H O N E #', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('T I N #', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('A G E N T', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function EvergreenLayout($config)
  {
    $result = $this->reportEvergreen($config);

    $count = 50;
    $page = 50;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeaderEvergreen($config, count($result));
    $str .= $this->displayHeadertableEvergreen($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->client, '130', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->planholder, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '350', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '140', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tin, '140', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '140', null, false, $border, '', '', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->displayHeaderEvergreen($config, count($result));
        $str .= $this->displayHeadertableEvergreen($config);
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
  //evergreen

  //afti 10 12

  public function reportAfti($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $agent   = $config['params']['dataparams']['agent'];
    $agentid   = $config['params']['dataparams']['agentid'];

    $filter   = "";

    if ($area != "") {
      $filter .= " and cust.area = '$area'";
    }

    $addleftjoin = "";
    $addfields = "";
    $billaddr = ",concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress";

    $cur = $config['params']['dataparams']['cur'];
    $industry = $config['params']['dataparams']['industry'];
    if ($region != "") {
      $filter .= " and cust.region = '$region'";
    }
    if ($province != "") {
      $filter .= " and bill.province = '$province'";
    }
    if ($cur != '') $filter .= " and forex.cur='" . $cur . "'";
    if ($industry != '') $filter .= " and cust.industry='" . $industry . "'";
    $addleftjoin = "left join forex_masterfile as forex on forex.line=cust.forexid";
    $billaddr = "";
    $addfields = ",bill.addrline1, bill.addrline2, bill.city, bill.province, bill.zipcode, cust.vattype, cust.terms, cust.crlimit, forex.cur";


    $query = "select 
      cust.client,cust.clientname, cust.province,cust.region,cust.area, cust.addr, cust.email , cust.tel, cust.tel2, cust.tin, cust.contact,
      agent.client agentcode, agent.clientname as agentname
      " . $billaddr . ", cust.industry,cat.cat_name as category,cust.groupid,cust.brgy " . $addfields . "
      from client as cust
      left join client as agent on agent.client = cust.agent
      left join billingaddr as bill on  bill.clientid = cust.clientid and bill.line = cust.billid
      Left join category_masterfile as cat on cat.cat_id = cust.category
      " . $addleftjoin . "
      where cust.iscustomer=1 and cust.clientname<>'' 
      $filter 
      order by cust.$sortby";

    return $this->coreFunctions->opentable($query);
  }

  private function AftiCustomerListHeader($config)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $cur      = $config['params']['dataparams']['cur'];
    $industry = $config['params']['dataparams']['industry'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $area = $area == '' ? 'ALL AREA' : $area;
    $region = $region == '' ? 'ALL REGION' : $region;
    $province = $province == '' ? 'ALL PROVINCE' : $province;
    $cur = $cur == '' ? 'ALL' : $cur;
    $industry = $industry == '' ? 'ALL' : $industry;

    $str = '';
    $layoutsize = '1500';
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
    $str .= $this->reporter->col('CUSTOMER LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Area : ' . $area, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Province : ' . $province, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Region : ' . $region, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Currency : ' . $cur, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Industry : ' . $industry, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BRANCH', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BUSINESS STYLE', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INDUSTRY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS LINE 1', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS LINE 2', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CITY/TOWN', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PROVINCE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ZIPCODE', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TELEPHONE #', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TIN#', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT TYPE', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TERMS', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREDIT LIMIT', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CURRENCY', '60', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES AGENT', '85', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function AftiCustomerListReport($config)
  {
    $result = $this->reportAfti($config);

    $count = 10;
    $page = 10;
    $layoutsize = '1500';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->AftiCustomerListHeader($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '75', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->clientname, '140', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->groupid, '75', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->category, '75', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->industry, '100', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->addrline1, '150', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->addrline2, '150', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->city, '100', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->province, '100', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->zipcode, '75', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->tel, '75', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->tin, '60', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->vattype, '60', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->terms, '60', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->crlimit, '60', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->cur, '60', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->agentname, '85', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
  //afti

  //3m 32

  public function reportThreeM($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];
    $agentname   = $config['params']['dataparams']['agentname'];
    $agent   = $config['params']['dataparams']['agent'];
    $agentid   = $config['params']['dataparams']['agentid'];

    $filter   = "";

    if ($area != "") {
      $filter .= " and cust.area = '$area'";
    }

    $addleftjoin = "";
    $addfields = "";
    $billaddr = ",concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress";

    $brgy = $config['params']['dataparams']['brgy'];
    if ($brgy != '') $filter .= " and cust.brgy = '" . $brgy . "'";
    if ($region != '') $filter .= " and cust.region = '" . $region . "'";
    if ($province != '') $filter .= " and cust.province = '" . $province . "'";


    $query = "select 
      cust.client,cust.clientname, cust.province,cust.region,cust.area, cust.addr, cust.email , cust.tel, cust.tel2, cust.tin, cust.contact,
      agent.client agentcode, agent.clientname as agentname
      " . $billaddr . ", cust.industry,cat.cat_name as category,cust.groupid,cust.brgy " . $addfields . "
      from client as cust
      left join client as agent on agent.client = cust.agent
      left join billingaddr as bill on  bill.clientid = cust.clientid and bill.line = cust.billid
      Left join category_masterfile as cat on cat.cat_id = cust.category
      " . $addleftjoin . "
      where cust.iscustomer=1 and cust.clientname<>'' 
      $filter 
      order by cust.$sortby";

    return $this->coreFunctions->opentable($query);
  }

  private function threemHeadertable($config)
  {
    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '170', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AGENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TELEPHONE#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('REGION', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AREA', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BRGY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function threemHeader($config, $recordCount)
  {
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER LIST', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
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

    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function threemLayout($config)
  {
    $result = $this->reportThreeM($config);

    $count = 50;
    $page = 50;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $companyid = $config['params']['companyid'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->threemHeader($config, count($result));
    $str .= $this->threemHeadertable($config);

    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->client, '130', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '170', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->tel, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->addr, '150', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->region, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->area, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->brgy, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$isfirstpageheader) $str .= $this->threemHeader($config, count($result));
        $str .= $this->threemHeadertable($config);
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
    // QUERY
    $area     = $config['params']['dataparams']['area'];
    $region   = $config['params']['dataparams']['region'];
    $province = $config['params']['dataparams']['province'];
    $sortby   = $config['params']['dataparams']['sortby'];
    $companyid = $config['params']['companyid'];
    $agentid = "";
    $agent = "";
    if (isset($config['params']['dataparams']['agentname'])) {
      $agentname   = $config['params']['dataparams']['agentname'];
      $agent   = $config['params']['dataparams']['agent'];
      $agentid   = $config['params']['dataparams']['agentid'];
    }

    $filter   = "";
  
    if ($area != "") {
      $filter .= " and cust.area = '$area'";
    }

    if ($region != "") {
      $filter .= " and cust.region = '$region'";
    }
    if ($province != "") {
      $filter .= " and cust.province = '$province'";
    }

    if ($agent != '') {
      $filter .= " and agent.clientid='$agentid'";
    }
    if ($companyid == 60) { //transpower
      $query = "select cust.client as `CustomerCode`, cust.clientname as `CustomerName`, cust.addr as `Address`, cust.tin as `Tin`, cust.tel as `TelephoneNumber`,
        cust.fax as `FaxNumber`, cust.email as `Email`, cust.contact as `ContactPerson`, cust.area as `Area`, cust.province as `Province`, cust.region as `Region`,
        cust.crlimit as `CreditLimit`, cust.terms as `Terms`, cust.agent as `AgentCode`, cust.groupid as `GroupID`, cust.start as `Start`, cust.status as `Status`,
        cust.registername as `Registeredname`, cust.tel2 as `Tel2/Other`
        from client as cust
          left join client as agent on agent.client=cust.agent
          left join category_masterfile as cat on cat.cat_id=cust.category
        where cust.iscustomer=1 and cust.clientname<>'' $filter order by cust.$sortby";
    } else {

      $query = "select 
        cust.client as CODE,cust.clientname as `CUSTOMER NAME`,cust.addr as ADDRESS,cust.tel as `TELEPHONE#`, cust.tin as TIN, agent.clientname as AGENT
        from client as cust
        left join client as agent on agent.client = cust.agent
        Left join category_masterfile as cat on cat.cat_id = cust.category
        where cust.iscustomer=1 and cust.clientname<>'' 
        $filter 
        order by cust.$sortby";
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
}//end class