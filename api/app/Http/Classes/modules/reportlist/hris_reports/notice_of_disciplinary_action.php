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

class notice_of_disciplinary_action
{
  public $modulename = 'Notice of Disciplinary Action';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
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

    return $this->reportDefaultLayout($config);
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
      $filter .= " and emp.client = '$client'";
    }
    if ($deptid != 0) {
      $filter1 .= " and dept.clientid = $deptid";
    }

    $query = "select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
  head.artid, head.sectionno, head.violationno,head.startdate, head.enddate, head.amt,
  head.detail, emp.clientname as empname,head.jobtitle,
  chead.description as articlename,cdetail.description as sectionname,
  head.penalty, head.numdays,head.refx,
  emp.client as empcode,dept.client as dept,
  dept.clientname as deptname,
  head.deptid,ir.docno as irno,
  ir.idescription as irdesc,
  chead.code as artcode,
  cdetail.section as sectioncode
  from disciplinary as head
  left join client as emp on emp.clientid=head.empid
  left join client as dept on dept.clientid=head.deptid
  left join hincidenthead as ir on head.refx=ir.trno
  left join codehead as chead on chead.artid=head.artid
  left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
  where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
  union all
  select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
  head.artid, head.sectionno, head.violationno,head.startdate, head.enddate, head.amt,
  head.detail, emp.clientname as empname,head.jobtitle,
  chead.description as articlename,cdetail.description as sectionname,
  head.penalty, head.numdays,head.refx,
  emp.client as empcode,dept.client as dept,
  dept.clientname as deptname,
  head.deptid,ir.docno as irno,
  ir.idescription as irdesc,
  chead.code as artcode,
  cdetail.section as sectioncode
  from hdisciplinary as head
  left join client as emp on emp.clientid=head.empid
  left join client as dept on dept.clientid=head.deptid
  left join hincidenthead as ir on head.refx=ir.trno
  left join codehead as chead on chead.artid=head.artid
  left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
  where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
  order by docno ";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '10';
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black
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
    $str .= $this->reporter->col('NOTICE OF DISCIPLINARY ACTION REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '170', null,  $bgcolors, $border, 'TB', 'L', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DATE', '170', null,  $bgcolors, $border, 'TB', 'L', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('EMPLOYEE', '250', null,  $bgcolors, $border, 'TB', 'L', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('DEPARMENT', '210', null,  $bgcolors, $border, 'TB', 'L', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->col('JOB TITLE', '200', null,  $bgcolors, $border, 'TB', 'L', $font, $font_size, 'B',  $fontcolor, '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid #C0C0C0 !important';
    $font = 'Century Gothic';
    $font_size = '10';
    $count = 55;
    $page = 55;
    $str = '';
    $border2 = '1px solid';

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

      $str .= $this->reporter->col($data->docno, '170', null, false, $border, 'TLB', '', $font, '12', 'B', '', '');
      $str .= $this->reporter->col($data->dateid, '170', null, false, $border, 'TLB', '', $font, '12', 'B', '', '');
      $str .= $this->reporter->col($data->empname, '250', null, false, $border, 'TLB', '', $font, '12', 'B', '', '');
      $str .= $this->reporter->col($data->deptname, '210', null, false, $border, 'TLB', '', $font, '12', 'B', '', '');
      $str .= $this->reporter->col($data->jobtitle, '200', null, false, $border, 'TLBR', '', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('REF INCIDENT REPORT NO.', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->irno, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('INCIDENT DESCRIPTION', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->irdesc, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ARTICLE', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->artcode, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->articlename, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SECTION', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->sectioncode, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->sectionname, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('NO. TIME VIOLATED', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->violationno, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '210', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('PENALTY', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->penalty, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('NO. OF DAYS', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->numdays, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('EFFECTIVITY DATE', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->startdate, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('TO', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->enddate, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('AMOUNT', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2), '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('DETAILS', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->detail, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();



      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100px',  null, false, $border2, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px',  null, false, $border2, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px',  null, false, $border2, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px',  null, false, $border2, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border2, 'TB', 'C', $font, $font_size, '', '', '');
      // $str .= $this->reporter->col('', '',  '100px', false, $border2, 'TB', 'C', $font, $font_size, '', '', '');
      // $str .= $this->reporter->col('', '',  '100px', false, $border2, 'TB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class