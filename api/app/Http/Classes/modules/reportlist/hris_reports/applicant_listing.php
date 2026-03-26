<?php

namespace App\Http\Classes\modules\reportlist\hris_reports;

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

class applicant_listing
{
  public $modulename = 'Applicant Listing';
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

    $fields = ['radioprint', 'aclientname', 'month', 'month2', 'year', 'radioreporttype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'aclientname.lookupclass', 'lookupallapplicant');
    data_set($col1, 'aclientname.label', 'Applicant');
    data_set($col1, 'aclientname.action', 'lookupallapplicant');
    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', true);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month');
    data_set($col1, 'month2.type', 'lookup');
    data_set($col1, 'month2.readonly', true);
    data_set($col1, 'month2.action', 'lookuprandom');
    data_set($col1, 'month2.lookupclass', 'lookup_month2');

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
    '' as aclientname,
      left(now(),4) as year,
     '' as bmonth,'' as month,  '' as bmonth2,'' as month2, 
       '0' as reporttype");
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
    $companyid =  $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];


    switch ($companyid) {
      case 58:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $reportdata =  $this->summarized_layout_cdohris($config);
            break;
          case '1': // DETAILED
            $reportdata =  $this->detailed_layout_cdohris($config);
            break;
        }
        break;
      default:
        switch ($reporttype) {
          case '0': // SUMMARIZED
            $reportdata =  $this->summarized_layout($config);
            break;
          case '1': // DETAILED
            $reportdata =  $this->detailed_layout($config);
            break;
        }
        break;
    }



    return $reportdata;
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $month1 = $config['params']['dataparams']['bmonth'];
    $month2 = $config['params']['dataparams']['bmonth2'];
    $year = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and empcode = '$client'";
    }

    $query = "select empid, empcode as client, CONCAT(UPPER(emplast), ', ', empfirst, ' ', LEFT(empmiddle, 1), '.') as clientname, 
              address, city, country, zipcode, telno, mobileno,
              email, citizenship, religion, alias, date(bday) as bday, jobtitle, jobcode, 
              jobdesc, maidname, date(appdate) as appdate,
              remarks, type, jstatus, mapp, bplace, child, status, gender, 
              ishired, hired, idno, jobid from app where emplast<>'' and jstatus <> '' and month(appdate) between $month1 and $month2 and year(appdate)='$year' $filter order by emplast";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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
    $str .= $this->reporter->col('APPLICANT  LISTING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('APPLICANT : ALL APPLICANT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('APPLICANT : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '120', null, $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('APPLICANT NAME', '150', null, $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('APPLIED POSITION', '180', null, $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('DATE APPLIED', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('BIRTH DATE', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('STATUS', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('ADDRESS', '150', null, $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('MOBILE #', '100', null, $bgcolors, $border, 'RTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->endrow();


    return $str;
  }


  private function displayHeaderCDO($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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
    $str .= $this->reporter->col('APPLICANT  LISTING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('APPLICANT : ALL APPLICANT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('APPLICANT : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '120', null, $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('APPLICANT NAME', '150', null, $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('APPLIED POSITION', '180', null, $bgcolors, $border, 'LTB', 'L', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('DATE APPLIED', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('BIRTH DATE', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('SCREENING', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('EXAMINATION', '150', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('BACKGROUND INVESTIGATION', '100', null, $bgcolors, $border, 'RTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('FINAL INTERVIEW', '100', null, $bgcolors, $border, 'RTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('PRE-EMPLOYEMENT', '100', null, $bgcolors, $border, 'RTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function detailed_layout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';
    $companyid = $config['params']['companyid'];

    $count = 55;
    $page = 55;
    $layoutsize = '1000';
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
    // $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '120', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '180', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->appdate, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->bday, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jstatus, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->address, '150', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->mobileno, '100', null, false, $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function detailed_cdo_layout($config)
  {
    $result = $this->reportDefault($config);
    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeaderCDO($config);
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '120', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '180', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->appdate, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->bday, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jstatus, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->address, '150', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->mobileno, '100', null, false, $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      // if ($this->reporter->linecounter == $page) {
      //   $str .= $this->reporter->endtable();
      //   $str .= $this->reporter->page_break();
      //   $str .= $this->displayHeader($config);
      //   $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      //   $page = $page + $count;
      // }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function summary_qry($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $month1 = $config['params']['dataparams']['bmonth'];
    $month2 = $config['params']['dataparams']['bmonth2'];
    $year = $config['params']['dataparams']['year'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and empcode = '$client'";
    }

    $query = " select count(empid) as empcount,jstatus,remarks
              from app where jstatus <> ''  and emplast<>'' and month(appdate) between $month1 and $month2 and year(appdate)='$year' $filter 
              group by jstatus,remarks order by jstatus";

    return $this->coreFunctions->opentable($query);
  }

  public function summary_qry_cdo($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $month1 = $config['params']['dataparams']['bmonth'];
    $month2 = $config['params']['dataparams']['bmonth2'];
    $year = $config['params']['dataparams']['year'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and empcode = '$client'";
    }

    $query = " select count(empid) as empcount,jstatus,remarks
              from app where emplast<>'' and month(appdate) between $month1 and $month2 and year(appdate)='$year' $filter 
              group by jstatus,remarks order by jstatus";
    // var_dump($query);

    return $this->coreFunctions->opentable($query);
  }


  private function summarized_Header($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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
    $str .= $this->reporter->col('APPLICANT  LISTING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('APPLICANT : ALL APPLICANT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('APPLICANT : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATUS', '200', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('NO. OF PERSONNEL', '200', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('REMARKS', '400', null, $bgcolors, $border, 'LTBR', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function summarized_layout($config)
  {
    $result = $this->summary_qry($config);
    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->summarized_Header($config);
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->jstatus, '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empcount, '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('&nbsp' . $data->remarks, '400', null, false, $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->summarized_Header($config);
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  ////////2.27.2026


  public function detailed_layout_cdohris($config)
  {
    $result = $this->reportDefault_cdohris($config);


    // var_dump($result2);

    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';
    $companyid = $config['params']['companyid'];

    $count = 55;
    $page = 55;
    $layoutsize = '1150';

    $this->reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => $layoutsize];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '96px;margin-top:10px;margin-left:20px;');
    $str .= $this->displayHeader_cdohris($config);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->client, '120', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '180', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->appdate, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->bday, '100', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->screening_days == 0 ? '' : $data->screening_days, '70', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->exam_days == 0 ? '' : $data->exam_days, '70', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->background_check_days == 0 ? '' : $data->background_check_days, '70', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->final_interview_days == 0 ? '' : $data->final_interview_days, '70', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->for_hiring == 0 ? '' : $data->for_hiring, '70', null, false, $border, 'LBR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jstatus, '50', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->remarks, '100', null, false, $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_cdohris($config);
        $str .= $this->reporter->begintable($layoutsize, null, false, false, '', '', '', '', '', '', '', '96px;margin-top:10px;margin-left:20px;');
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function displayHeader_cdohris($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $str = '';
    $layoutsize = '1150';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('APPLICANT  LISTING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('APPLICANT : ALL APPLICANT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('APPLICANT : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '120', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('APPLICANT NAME', '150', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('APPLIED POSITION', '180', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('DATE APPLIED', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('BIRTH DATE', '100', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('SCREENING', '70', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('EXAMINATION', '70', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('BACKGROUND INVESTIGATION', '70', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('FINAL INTERVIEW', '70', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('PRE-EMPLOYMENT', '70', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('STATUS', '50', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->col('REMARKS', '150', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '4px');
    $str .= $this->reporter->endrow();

    return $str;
  }




  private function summarized_Header_cdohris($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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
    $str .= $this->reporter->col('APPLICANT  LISTING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('APPLICANT : ALL APPLICANT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('APPLICANT : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATUS', '200', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('NO. OF PERSONNEL', '200', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('REMARKS', '400', null, $bgcolors, $border, 'LTBR', 'C', $font, $font_size, 'B', $fontcolor, '6px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $font_size, '', '', '6px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function summarized_layout_cdohris($config)
  {
    $result = $this->summary_qry_cdo($config);
    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';

    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->summarized_Header_cdohris($config);
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $status = '';
    $status_border = '';
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $status_border = 'L';
      if ($status == '' || $status != $data->jstatus) {
        if ($status != $data->jstatus) {
          $status_border = 'LT';
        }
        $str .= $this->reporter->col($data->jstatus == '' ? 'IN-PROCESS' : $data->jstatus, '200', null, false, $border, $status_border, 'C', $font, $font_size, '', '', '');
        $status = $data->jstatus;
      } else {
        $str .= $this->reporter->col('', '200', null, false, $border, $status_border, 'C', $font, $font_size, '', '', '');
      }

      $str .= $this->reporter->col($data->empcount, '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('&nbsp' . $data->remarks, '400', null, false, $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      //   if ($this->reporter->linecounter == $page) {
      //     $str .= $this->reporter->endtable();
      //     $str .= $this->reporter->page_break();
      //     $str .= $this->summarized_Header_cdohris($config);
      //     $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      //     $page = $page + $count;
      //   }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    //timeline summarized
    $str .= '<br/><br/><br/>';

    $str .= $this->reporter->begintable(1000);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUMMARIZED AVERAGE DAYS OF RECRUITMENT PROCESS PER APPLICANT LEDGER', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $averagedays = $this->average_days_summarized($config);
    if (empty($averagedays)) {
      return $this->othersClass->emptydata($config);
    }

    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black
    $str .= $this->reporter->begintable(1000);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("SCREENING", 200, null, $bgcolors, $border, 'LTR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->col("EXAMINATION", 200, null, $bgcolors, $border, 'LTR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->col("BACKGROUND INVESTIGATION", 200, null, $bgcolors, $border, 'LTR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->col("FINAL INTERVIEW", 200, null, $bgcolors, $border, 'LTR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->col("PRE-EMPLOYMENT", 200, null, $bgcolors, $border, 'LRT', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    $str .= $this->reporter->endrow();


    $ave = 0;

    foreach ($averagedays as $key => $data2) {

      $screening = $data2->screening_days != 0 ? $data2->screening_days : 0;
      $exam      = $data2->exam_days != 0 ? $data2->exam_days : 0;
      $bc        = $data2->background_check_days != 0 ? $data2->background_check_days : 0;
      $final     = $data2->final_interview_days  != 0 ? $data2->final_interview_days : 0;
      $hiring    = $data2->for_hiring  != 0 ? $data2->for_hiring : 0;
      $empid_count = $data2->empid_count != 0 ? $data2->empid_count : 0;
      // var_dump($screening);
      // var_dump($empid_count);
      $screening1 = $screening / $empid_count;
      $exam1 = $exam / $empid_count;
      $bc1 = $bc / $empid_count;
      $final1 = $final / $empid_count;
      $hiring1 = $hiring / $empid_count;


      $str .= $this->reporter->col(number_format($screening1, 2), '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '3px');
      $str .= $this->reporter->col(number_format($exam1, 2),      '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '3px');
      $str .= $this->reporter->col(number_format($bc1, 2),       '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '3px');
      $str .= $this->reporter->col(number_format($final1, 2),     '200', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '3px');
      $str .= $this->reporter->col(number_format($hiring1, 2),    '200', null, false, $border, 'LBR', 'C', $font, $font_size, '', '', '3px');
      $ave += $screening1 + $exam1 + $bc1 + $final1 + $hiring1;
    }

    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable(1000);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("AVERAGE SUM OF DAYS = ", 400, null, false, $border, '', 'RT', $font, $font_size + 2, 'B', '', '');
    $str .= $this->reporter->col(number_format($ave, 2), 100, null, false, $border, 'B', 'CT', $font, $font_size + 2, 'B', '', '');
    $str .= $this->reporter->col("DAYS", 500, null, false, $border, '', 'LT', $font, $font_size + 2, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }



  public function average_days_summarized($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $month1 = $config['params']['dataparams']['bmonth'];
    $month2 = $config['params']['dataparams']['bmonth2'];
    $year = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and app.empcode = '$client'";
    }


    $query = "
            select sum(screening_days) as screening_days, sum(exam_days) as exam_days, sum(background_check_days) as background_check_days,
                   sum(final_interview_days) as final_interview_days, sum(for_hiring) as for_hiring, count(empid) as empid_count from (

            select   app.empid,
            
            if(ex.examstart is not null, DATEDIFF(date(ex.examstart), date(app.createdate)) + 1,if(app.createdate is not null, 1, 0)) as screening_days,


            if(exe.exam_end is not null, DATEDIFF(date(exe.exam_end), date(ex.examstart)) + 1, if(date(ex.examstart) > date(bc.back_check_start),1,
            DATEDIFF(DATE(bc.back_check_start), DATE(ex.examstart))+1)) AS exam_days,


            if(bce.back_check_end is not null, DATEDIFF(date(bce.back_check_end), date(bc.back_check_start)) + 1, if(date(bc.back_check_start) > date(fi.final_start),1,
            DATEDIFF(DATE(fi.final_start), DATE(bc.back_check_start))+1)) as background_check_days,


            if(fie.final_end is not null, DATEDIFF(date(fie.final_end), date(fi.final_start))+1, if(date(fi.final_start) > date(fh.forhiring_start),1,
            DATEDIFF(DATE(fh.forhiring_start), DATE(fi.final_start))+1)) as final_interview_days,

            if(fhe.forhiring_end is not null, DATEDIFF(date(fhe.forhiring_end), date(fh.forhiring_start)) + 1, if(fh.forhiring_start is not null, 1, 0)) as for_hiring

          from app
  
            left join ( select aps.trno as empid,
                      date(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as examstart
                      from app_stat aps
                      where aps.oldversion = 'Tag for Pre-Employment Exam.') ex ON ex.empid = app.empid


            left join ( select   aps.trno as empid, DATE(aps.dateid3) as exam_end
                  from app_stat aps
                  where aps.oldversion = 'Tag for Pre-Employment Exam.') exe on exe.empid = app.empid



              left join (select aps.trno as empid,  DATE(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as back_check_start
                  from app_stat aps
                  where aps.oldversion = 'Tag for Background Checking.'
              ) bc on bc.empid = app.empid


              left join ( select   aps.trno as empid,  DATE(aps.dateid3) as back_check_end
                  from app_stat aps
                  where aps.oldversion = 'Tag for Background Checking.'
              ) bce on bce.empid = app.empid

              left join ( select  aps.trno as empid, DATE(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as final_start
                  from app_stat aps
                  where aps.oldversion = 'Tag for Final Interview.'
              ) fi on fi.empid = app.empid

              left join ( select   aps.trno as empid,  DATE(aps.dateid3) as final_end
                  from app_stat aps
                  where aps.oldversion = 'Tag for Final Interview.'
              ) fie on fie.empid = app.empid


              left join (  select  aps.trno as empid,DATE(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as forhiring_start
                  from app_stat aps
                  where aps.oldversion = 'Tag for Hiring & Pre-Employment Requirements.'
              ) fh on fh.empid = app.empid

              left join (
                  select aps.trno as empid,DATE(aps.dateid3)  as forhiring_end
                  from app_stat aps
                  where aps.oldversion = 'Tag for Hiring & Pre-Employment Requirements.'
              ) fhe on fhe.empid = app.empid

          where app.emplast<>'' and month(appdate) between $month1 and $month2 and year(appdate)='$year' $filter 
             group by app.empid,ex.examstart,app.createdate,exe.exam_end,bc.back_check_start,bce.back_check_end,fi.final_start,fie.final_end,fh.forhiring_start,fhe.forhiring_end ) as a";

    // var_dump($query);
    return $this->coreFunctions->opentable($query);
  }


  public function reportDefault_cdohris($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $month1 = $config['params']['dataparams']['bmonth'];
    $month2 = $config['params']['dataparams']['bmonth2'];
    $year = $config['params']['dataparams']['year'];
    $companyid = $config['params']['companyid'];

    $filter   = "";

    if ($client != "") {
      $filter .= " and app.empcode = '$client'";
    }

    $query = "select  empcode as client, CONCAT(UPPER(emplast), ', ', empfirst, ' ', LEFT(empmiddle, 1), '.') as clientname, app.jstatus,app.remarks,
                       jobtitle,date(appdate) as appdate,date(bday) as bday,
            if(ex.examstart is not null, DATEDIFF(date(ex.examstart), date(app.createdate)) + 1,if(app.createdate is not null, 1, 0)) as screening_days,


            if(exe.exam_end is not null, DATEDIFF(date(exe.exam_end), date(ex.examstart)) + 1, if(date(ex.examstart) > date(bc.back_check_start),1,
            DATEDIFF(DATE(bc.back_check_start), DATE(ex.examstart))+1)) AS exam_days,


            if(bce.back_check_end is not null, DATEDIFF(date(bce.back_check_end), date(bc.back_check_start)) + 1, if(date(bc.back_check_start) > date(fi.final_start),1,
            DATEDIFF(DATE(fi.final_start), DATE(bc.back_check_start))+1)) as background_check_days,


            if(fie.final_end is not null, DATEDIFF(date(fie.final_end), date(fi.final_start))+1, if(date(fi.final_start) > date(fh.forhiring_start),1,
            DATEDIFF(DATE(fh.forhiring_start), DATE(fi.final_start))+1)) as final_interview_days,

            if(fhe.forhiring_end is not null, DATEDIFF(date(fhe.forhiring_end), date(fh.forhiring_start)) + 1, if(fh.forhiring_start is not null, 1, 0)) as for_hiring

          from app

          left join ( select aps.trno as empid,
                      date(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as examstart
                      from app_stat aps
                      where aps.oldversion = 'Tag for Pre-Employment Exam.') ex ON ex.empid = app.empid


         left join ( select   aps.trno as empid, DATE(aps.dateid3) as exam_end
              from app_stat aps
              where aps.oldversion = 'Tag for Pre-Employment Exam.') exe on exe.empid = app.empid



          left join (select aps.trno as empid,  DATE(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as back_check_start
              from app_stat aps
              where aps.oldversion = 'Tag for Background Checking.'
          ) bc on bc.empid = app.empid


          left join ( select   aps.trno as empid,  DATE(aps.dateid3) as back_check_end
              from app_stat aps
              where aps.oldversion = 'Tag for Background Checking.'
          ) bce on bce.empid = app.empid

          left join ( select  aps.trno as empid, DATE(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as final_start
              from app_stat aps
              where aps.oldversion = 'Tag for Final Interview.'
          ) fi on fi.empid = app.empid

          left join ( select   aps.trno as empid,  DATE(aps.dateid3) as final_end
              from app_stat aps
              where aps.oldversion = 'Tag for Final Interview.'
          ) fie on fie.empid = app.empid


          left join (  select  aps.trno as empid,DATE(if(aps.dateid2 is null, aps.dateid, aps.dateid2)) as forhiring_start
              from app_stat aps
              where aps.oldversion = 'Tag for Hiring & Pre-Employment Requirements.'
          ) fh on fh.empid = app.empid

          left join (
              select aps.trno as empid,DATE(aps.dateid3)  as forhiring_end
              from app_stat aps
              where aps.oldversion = 'Tag for Hiring & Pre-Employment Requirements.'
          ) fhe on fhe.empid = app.empid

          where app.emplast<>'' and month(appdate) between $month1 and $month2 and year(appdate)='$year' $filter 
          group by app.empcode, app.jobtitle,app.appdate,app.bday,app.emplast,app.empfirst,app.empmiddle,ex.examstart,app.createdate,exe.exam_end,
          bc.back_check_start,bce.back_check_end,fi.final_start,fie.final_end,fh.forhiring_start,fhe.forhiring_end, app.jstatus,app.remarks ";

    return $this->coreFunctions->opentable($query);
  }
}//end class