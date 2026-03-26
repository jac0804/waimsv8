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

class employee_listing
{
  public $modulename = 'Employee Listing';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1100'];

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
    $fields = ['radioprint', 'dclientname', 'divrep', 'deptrep', 'sectrep',];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
    data_set($col1, 'sectrep.label', 'Section');

    $fields = ['radioreportempstatus'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as client,
    '' as clientname,
    '' as dclientname,
    '' as divid,
    '' as divname,
    '' as divrep,
    '' as deptid,
    '' as deptname,
    '' as deptrep,
    '' as sectrep,
    '' as sectid,
    '' as sectname,
    '(0,1)' as empstatus
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
    $divid     = $config['params']['dataparams']['divid'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $sectid     = $config['params']['dataparams']['sectid'];
    $empstatus = $config['params']['dataparams']['empstatus'];
    $filters   = "";

    if ($client != "") {
      $filters .= " and client.client = '$client'";
    }
    if ($divid != "") {
      $filters .= " and e.divid = $divid";
    }
    if ($deptid != "") {
      $filters .= " and e.deptid = $deptid";
    }
    if ($sectid != "") {
      $filters .= " and e.sectid = $sectid";
    }
    switch ($empstatus) {
      case '(0)':
        $filters .= " and e.isactive = 0 ";
        break;
      case '(1)':
        $filters .= " and e.isactive = 1 ";
        break;
      default:
        $filters .= " and e.isactive in (0,1) ";
        break;
    }
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select client.client, CONCAT(UPPER(e.emplast), ', ', e.empfirst, ' ', LEFT(e.empmiddle, 1), '.') as clientname, e.address,e.telno,
              date(e.hired) as hired, date(e.bday) as bday, e.jobtitle, e.tin, e.sss, e.hdmf, e.phic 
              from employee as e 
              left join client on client.clientid=e.empid 
              where e.emplast<>'' and e.level in $emplvl $filters
              order by e.emplast";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '9';
    $padding = '';
    $margin = '';

    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $divid     = $config['params']['dataparams']['divid'];
    $divname     = $config['params']['dataparams']['divname'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $deptname   = $config['params']['dataparams']['deptname'];
    $sectid     = $config['params']['dataparams']['sectid'];
    $sectname   = $config['params']['dataparams']['sectname'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

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

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username, $config);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE LISTING', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    if ($client == '') {
      $str .= $this->reporter->col('EMPLOYEE : ALL EMPLOYEE', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('EMPLOYEE : ' . strtoupper($clientname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }
    if ($divid == 0) {
      $str .= $this->reporter->col('COMPANY : ALL COMPANY', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('COMPANY : ' . strtoupper($divname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    if ($deptid == 0) {
      $str .= $this->reporter->col('DEPARTMENT : ALL DEPARTMENT', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('DEPARTMENT : ' . strtoupper($deptname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }

    if ($sectid == 0) {
      $str .= $this->reporter->col('SECTION : ALL SECTION', NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    } else {
      $str .= $this->reporter->col('SECTION : ' . strtoupper($sectname), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    }



    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE &nbsp NAME', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('POSITION', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE &nbsp HIRED', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BIRTH &nbsp DATE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS', '150', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('MOBILE #', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TIN #', '60', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SSS #', '60', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PHIC #', '60', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('HDMF #', '60', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '9';
    $padding = '';
    $margin = '';
    $total = 0;
    $count = 55;
    $page = 55;
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->client, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->hired, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->bday, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->address, '150', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->telno, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tin, '60', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sss, '60', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->phic, '60', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->hdmf, '60', null, false, $border, '', '', $font, $font_size, '', '', '');
      $total = $total + 1;
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total Employee: ', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($total, '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', '', $font, $font_size, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class