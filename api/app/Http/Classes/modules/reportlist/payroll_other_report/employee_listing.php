<?php

namespace App\Http\Classes\modules\reportlist\payroll_other_report;

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

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1400'];

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
    $fields = ['radioprint', 'dclientname', 'userlevel', 'emploc', 'divrep', 'deptrep', 'sectrep', 'tpaygroup', 'project', 'biometric'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'dclientname.class', 'csdclientname');
    data_set($col1, 'divrep.lookupclass', 'lookupempdivision');
    data_set($col1, 'divrep.label', 'Company');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');
    data_set($col1, 'sectrep.lookupclass', 'lookupempsection');
    data_set($col1, 'sectrep.label', 'Section');

    data_set($col1, 'biometric.action', 'lookupbiometric');
    data_set($col1, 'tpaygroup.label', 'Pay Group');
    data_set($col1, 'biometric.class', 'cssbiometic sbccsreadonly');
    data_set($col1, 'biometric.readonly', true);

    data_set($col1, 'project.label', 'Project');
    data_set($col1, 'project.class', 'cssproject sbccsreadonly');
    data_set($col1, 'project.required', false);

    data_set($col1, 'emploc.action', 'lookupemplocation');
    data_set($col1, 'emploc.type', 'lookup');
    data_set($col1, 'emploc.lookupclass', 'lookupemplocation');
    data_set($col1, 'userlevel.lookupclass', 'useremplevel');


    $fields = ['radioreportempstatus'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioreportempstatus.options', [
      ['label' => 'Active', 'value' => '(1)', 'color' => 'orange'],
      ['label' => 'Inactive', 'value' => '(0)', 'color' => 'orange'],
      ['label' => 'Resigned', 'value' => '(2)', 'color' => 'orange'],
      ['label' => 'All', 'value' => '(0,1)', 'color' => 'orange']
    ]);

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
    '' as division,
    '' as deptid,
    '' as deptname,
    '' as sectid,
    '' as sectname,
    '' as divrep,
    '' as deptrep,
    '' as sectrep,
    '' as project,
    '' as projectid,
    '' as biometric,
    '' as biometricid,
    '' as emploc,
    '' as paygroup,
    '' as tpaygroup,
    '(0,1)' as empstatus,
    '' as userlevel,
    '0' as userid
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
    $emploc = $config['params']['dataparams']['emploc'];
    $paygroup = $config['params']['dataparams']['tpaygroup'];
    $projectid = $config['params']['dataparams']['projectid'];
    $biometricid = $config['params']['dataparams']['biometricid'];
    $userid = $config['params']['dataparams']['userid'];
    $userlevel = $config['params']['dataparams']['userlevel'];
    $user = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $adminid = $config['params']['adminid'];

    $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5228);
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

    if ($emploc != "") {
      $filters .= " and e.emploc = '$emploc' ";
    }
    if ($paygroup != "") {
      $filters .= " and paygroup.paygroup =  '$paygroup' ";
    }
    if ($projectid != "") {
      $filters .= " and e.projectid =  '$projectid' ";
    }
    if ($biometricid != "") {
      $filters .= " and e.biometricid =  '$biometricid' ";
    }
    if ($userid != 0) {
      $filters .= " and e.biometricid =  '$biometricid' ";
    }
    if ($userlevel != '') {
      $filters .= " and users.username =  '$userlevel' ";
    }

    // testing
    $leftjoin = "";
    $check = $this->othersClass->checkapproversetup($config, $adminid, '','e');
    if ($check['filter'] != "") {
      $filters .= $check['filter'];
    }
    if ($check['leftjoin'] != "") {
      $leftjoin .= $check['leftjoin'];
    }

    switch ($empstatus) {
      case '(0)':
        $filters .= " and e.isactive = 0 ";
        break;
      case '(1)':
        $filters .= " and e.isactive = 1 ";
        break;
      case '(2)':
        $filters .= " and e.resigned is not null ";
        break;
      default:
        $filters .= " and e.isactive in (0,1) and (e.resigned is null OR e.resigned is not null)";
        break;
    }
    $emplvl = $this->othersClass->checksecuritylevel($config);

    $query = "select client.client, CONCAT(UPPER(e.emplast), ', ', e.empfirst, ' ', LEFT(e.empmiddle, 1), '.') as clientname, e.address,e.telno,
  date(e.hired) as hired, date(e.bday) as bday, e.jobtitle, e.tin, e.sss, e.hdmf, e.phic ,e.idbarcode as bioid,users.username as userlvl
  from employee as e 
  left join client on client.clientid=e.empid 
  left join paygroup on paygroup.line = e.paygroup
  left join users on users.idno=client.userid
  $leftjoin
  where e.emplast<>'' and e.level in $emplvl $filters
  order by users.username, e.emplast";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($config['params']);
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

    $str = '';
    $layoutsize = '1000';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
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

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE &nbsp NAME', '180', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('POSITION', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE LEVEL', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BIO ID', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE &nbsp HIRED', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BIRTH &nbsp DATE', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('ADDRESS', '180', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('MOBILE #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TIN #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('SSS #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('PHIC #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('HDMF #', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

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
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '9';
    $padding = '';
    $margin = '';
    $total = 0;
    $count = 39;
    $page = 39;
    $layoutsize = '1400';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '50;margin-top:10px;margin-left:10px;');
    $str .= $this->displayHeader($config);

    $userlvl = "";
    foreach ($result as $key => $data) {


      $str .= $this->reporter->addline();


      if (strtoupper($userlvl) == strtoupper($data->userlvl)) {
        $userlvl = "";
      } else {
        $userlvl = strtoupper($data->userlvl);
      } //end if

      if ($userlvl != "") {
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($userlvl, '80', null, false, $border, '', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '180', null, false, $border, '', '', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->client, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->clientname, '180', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(strtoupper($data->userlvl), '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->bioid, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->hired, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->bday, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->address, '180', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->telno, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tin, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sss, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->phic, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->hdmf, '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $userlvl = strtoupper($data->userlvl);
      $total = $total + 1;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total Employee: ', '100', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col($total, '1300', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class