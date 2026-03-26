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

class employee_list
{
  public $modulename = 'Employee List';
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
    $systype = $this->companysetup->getsystemtype($config['params']);
    $fields = ['radioprint'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'dclientname', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'dclientname.lookupclass', 'lookupemployeelist');
        data_set($col1, 'dclientname.label', 'Employee');
        break;

      default:
        switch ($systype) {
          case 'FAMS':
            array_push($fields, 'dwhname', 'area', 'region', 'province');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'dwhname.label', 'Location');
            break;

          default:
            array_push($fields, 'dclientname');
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'dclientname.lookupclass', 'lookupemployeelist');
            data_set($col1, 'dclientname.label', 'Employee');
            break;
        }
        break;
    }

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
    '' as client,
    '' as clientname
    ";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $paramstr .= ", '' as ddeptname, '' as dept, '' as deptname ";
    }
    if ($systype == 'FAMS') {
      $paramstr .= ", '' as dclientname,
      '' as wh,
      '' as whname,
      '' as area,
      '' as region,
      '' as province";
    }

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
    $systype = $this->companysetup->getsystemtype($config['params']);
    if ($systype == 'FAMS') {
      return $this->reportFAMSLayout($config);
    } else {
      return $this->reportDefaultLayout($config);
    }
  }

  private function FAMSdisplayHeader($config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
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

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
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
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($systype == 'FAMS') {
      $str .= $this->reporter->col('EMPLOYEE LISTING', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    } else {
      $str .= $this->reporter->col('EMPLOYEE  LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($client == '') {
        $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', '600', null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . $deptname, '200', null, false, $border, '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), '600', null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . $deptname, '200', null, false, $border, '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($client == '') {
        $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  private function FAMS_table_cols($layoutsize, $border, $font, $font_size)
  {
    $str = '';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Name', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Address', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Tel No.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Mobile No.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Email', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TIN No.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Location', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Hired', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Status', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Area', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Region', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportFAMS($config)
  {
    // QUERY

    $systype = $this->companysetup->getsystemtype($config['params']);
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $companyid = $config['params']['companyid'];

    $filter   = "";
    $filter1 = "";

    if ($client != "") {
      $filter .= " and c.client = '$client'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and c.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }
    if ($systype == 'FAMS') {
      $area = $config['params']['dataparams']['area'];
      $region = $config['params']['dataparams']['region'];
      $province = $config['params']['dataparams']['province'];
      $wh = $config['params']['dataparams']['wh'];
      if ($area == "") {
      } else {
        $filter .= " and c.area = '$area'";
      }
      if ($region == "") {
      } else {
        $filter .= " and c.region = '$region'";
      }
      if ($province == "") {
      } else {
        $filter .= " and c.province = '$province'";
      }
      if ($wh == "") {
      } else {
        $filter .= " and loc.client = '$wh'";
      }
    }

    $query = "select c.client,c.clientname, c.addr, c.tel, c.tel2, c.tin,c.email,ifnull(loc.clientname,'') as location,ifnull(date(c.start),'') as hiredate,'' as stat,c.area,c.region,c.province
            from client as c
            left join client as dept on dept.clientid = c.deptid
            left join client as loc on loc.client=c.wh
            where c.isemployee=1 and c.clientname <> '' $filter $filter1
            order by c.clientname";
    return $this->coreFunctions->opentable($query);
  }

  public function reportFAMSLayout($config)
  {
    $result = $this->reportFAMS($config);

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
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->FAMSdisplayHeader($config);
    $str .= $this->FAMS_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->addr, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel2, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->email, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tin, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->location, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->hiredate, '100', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->stat, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->area, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->region, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->FAMSdisplayHeader($config);
        }
        $str .= $this->FAMS_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11);
        $page = $page + $count;
      }
    } //end foreach

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function displayHeader($config)
  {

    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED
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

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

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
    $str .= $this->reporter->col('EMPLOYEE  LIST', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      if ($client == '') {
        $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', '600', null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . $deptname, '200', null, false, $border, '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), '600', null, false, $border, '', 'L', $font, '10', '', '', '', '');
        $str .= $this->reporter->col('DEPARTMENT : ' . $deptname, '200', null, false, $border, '', 'L', $font, '10', '', '', '', '');
      }
    } else {
      if ($client == '') {
        $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
      } else {
        $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
      }
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
    $str .= $this->reporter->col('E M P L O Y E E &nbsp N A M E', '250', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('A D D R E S S', '350', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('T E L E P H O N E #', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $companyid = $config['params']['companyid'];

    $filter   = "";
    $filter1 = "";

    if ($client != "") {
      $filter .= " and c.client = '$client'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptid = $config['params']['dataparams']['ddeptname'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($deptid != "") {
        $filter1 .= " and c.deptid = $dept";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select c.client,c.clientname, c.addr, c.tel, c.tel2, c.tin
            from client as c
            left join client as dept on dept.clientid = c.deptid
            where c.isemployee=1 and c.clientname <> '' $filter $filter1
            order by c.clientname";
    return $this->coreFunctions->opentable($query);
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
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '250', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->addr, '350', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tel2, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
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