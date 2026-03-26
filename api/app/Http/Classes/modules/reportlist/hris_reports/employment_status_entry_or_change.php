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

class employment_status_entry_or_change
{
  public $modulename = 'Employment Status Entry or Change';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '900'];

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
    $fields = ['radioprint', 'dclientname', 'deptrep'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
    data_set($col1, 'deptrep.label', 'Department');

    $fields = ['start', 'end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

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
      '' as deptid,
      '' as deptname,
      adddate(left(now(),10),-360) as start,
        left(now(),10) as `end`,
      '' as deptrep
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
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case '58':
        return $this->CDOHRIS_Layout($config);
        break;

      default:
        return $this->reportDefaultLayout($config);
        break;
    }
  }

  public function reportDefault($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";
    $filter1   = "";

    if ($client != "") {
      $filter .= " and c.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and dept.clientid = $deptid";
    }

    $query = "SELECT head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      head.fpaymode, head.fpaygroup, head.fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, head.isactive
      from eschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join jobthead as jt on jt.line = emp.jobid
      where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
      union all
      select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      head.fpaymode, head.fpaygroup, head.fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, head.isactive
      from heschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join jobthead as jt on jt.line = emp.jobid
      where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
      order by docno ";

    return $this->coreFunctions->opentable($query);
  }


  public function CDOHRIS_QRY($config)
  {
    // QUERY
    $client     = $config['params']['dataparams']['client'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";
    $filter1   = "";

    if ($client != "") {
      $filter .= " and c.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and dept.clientid = $deptid";
    }

    $query = "select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      head.fpaymode, head.fpaygroup, head.fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, head.isactive
      from eschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join jobthead as jt on jt.line = emp.jobid
      where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
      union all
      select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      head.fpaymode, head.fpaygroup, head.fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, head.isactive
      from heschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join jobthead as jt on jt.line = emp.jobid
      where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
      order by docno ";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '10';

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';


    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYMENT STATUS ENTRY OR CHANGE REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('EMPLOYEE', '130', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DEPARMENT', '100', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('JOB TITLE', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('REMARKS', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }


  private function CDOHRIS_header($config)
  {

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '10';
    $layoutsize = '1000';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYMENT STATUS ENTRY OR CHANGE REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('DOCUMENT NO.', '120', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    // $str .= $this->reporter->col('DATE', '100', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    // $str .= $this->reporter->col('EMPLOYEE', '200', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    // $str .= $this->reporter->col('DEPARMENT', '140', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    // $str .= $this->reporter->col('JOB TITLE', '140', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    // $str .= $this->reporter->col('STATUS', '100', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    // $str .= $this->reporter->col('REMARKS', '200', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
    // $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '10';
    $count = 55;
    $page = 55;
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);
    $chkemp = "";

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, '130', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->deptname, '100', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->statdesc, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->remarks, '80', null, false, $border, '', '', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('DESCRIPTION', '80', null, false, false, 'TB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('FROM', '80', null, false, false, 'TB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('TO', '80', null, false, false, 'TB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('TYPE', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->ftype, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->ttype, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('LEVEL', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->flevel, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tlevel, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('JOBTITLE', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fjobcode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tjobcode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('EMP. STATUS', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fempstatcode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tempstatcode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('RANK', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->frank, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->trank, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('JOB GRADE', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fjobgrade, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tjobgrade, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('DEPARTMENT', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fdeptcode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tdeptcode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('LOCATION', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->flocation, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tlocation, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('MODE OF PAY', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fpaymode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tpaymode, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('PAY GROUP', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fpaygroup, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tpaygroup, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('PAY RATE', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fpayrate, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tpayrate, '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('ALLOWANCE', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->fallowrate, 2), '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->tallowrate, 2), '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('BASIC SALARY', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->fbasicrate, 2), '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->tbasicrate, 2), '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, false, 'TB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }


  public function CDOHRIS_Layout($config)
  {
    $result = $this->CDOHRIS_QRY($config);

    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';
    $count = 6; //2
    $page = 5; //1
    $str = '';
    $layoutsize = '1000';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->CDOHRIS_header($config);
    $chkemp = "";

    foreach ($result as $key => $data) {

      $str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DOCUMENT NO.', '120', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
      $str .= $this->reporter->col('DATE', '100', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
      $str .= $this->reporter->col('EMPLOYEE', '200', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
      $str .= $this->reporter->col('DEPARMENT', '140', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
      $str .= $this->reporter->col('JOB TITLE', '140', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
      $str .= $this->reporter->col('STATUS', '100', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
      $str .= $this->reporter->col('REMARKS', '200', null,  $bgcolors, $border, 'TBLR', 'C', $font, $font_size, 'B', $fontcolor, '8px');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      // $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '120', null, false, $border, 'TLB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'TLB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, '200', null, false, $border, 'TLB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->deptname, '140', null, false, $border, 'TLB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '140', null, false, $border, 'TLB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->statdesc, '100', null, false, $border, 'TLB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->remarks, '200', null, false, $border, 'TLBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DESCRIPTION', '250', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'B', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('FROM', '350', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('TO', '350', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('TYPE', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->ftype, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->ttype, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('LEVEL', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->flevel, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tlevel, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('JOBTITLE', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fjobcode, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tjobcode, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('EMP. STATUS', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fempstatcode, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tempstatcode, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('RANK', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->frank, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->trank, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('JOB GRADE', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fjobgrade, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tjobgrade, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DEPARTMENT', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fdeptcode, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tdeptcode, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('LOCATION', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->flocation, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tlocation, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('MODE OF PAY', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fpaymode, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tpaymode, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('PAY GROUP', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fpaygroup, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tpaygroup, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('PAY RATE', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->fpayrate, '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->tpayrate, '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ALLOWANCE', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->fallowrate, 2), '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->tallowrate, 2), '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('BASIC SALARY', '250', null, false,  $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(':', '50', null, false,  $border, 'B', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->fbasicrate, 2), '350', null, false,  $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->tbasicrate, 2), '350', null, false,  $border, 'LBR', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();



      // $str .= $this->reporter->startrow();
      // $str .= $this->reporter->col('', '250px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      // $str .= $this->reporter->col('', '50px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      // $str .= $this->reporter->col('', '350px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      // $str .= $this->reporter->col('', '350px', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      // // $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      // // $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      // // $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $font_size, '', '', '');
      // $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();

      $allowanceqry = "select date(dateid) as dateid,date(dateeffect) as deffect,date(dateend) as dend,a.remarks,a.allowance,p.code,p.codename, p.alias
        from allowsetup as a
        left join paccount as p on p.line=a.acnoid
        where empid = $data->empid and a.acnoid<>0 ";
      $allowance = $this->coreFunctions->opentable($allowanceqry);
      if (!empty($allowance)) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Allowance', '1000', null, false, $border, 'LR', 'L', $font, $font_size + 3, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Code', '120', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Codename', '210', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Allowance', '160', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Effect Date', '160', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('End Date', '150', null, false, $border, 'TLB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Remarks', '200', null, false, $border, 'TLBR', 'C', $font, $font_size, 'B', '', '');

        foreach ($allowance as $key => $adata) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($adata->code, '150', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($adata->codename, '210', null, false, $border, '', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->col(number_format($adata->allowance, 2), '160', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($adata->deffect, '160', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($adata->dend, '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col($adata->remarks, '200', null, false, $border, 'R', 'L', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '210', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '160', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '160', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '30px');

        $str .= $this->reporter->endtable();
      } else {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '210', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

  
      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        // $str .= $this->CDOHRIS_header($config);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '210', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $font_size, 'B', '', '30px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class