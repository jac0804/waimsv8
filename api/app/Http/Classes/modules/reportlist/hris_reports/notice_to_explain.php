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

class notice_to_explain
{
  public $modulename = 'Notice to Explain';
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
    $filter1   = 0;

    if ($client != "") {
      $filter .= " and emp.client = '$client'";
    }
    if ($deptid != $filter1) {
      $filter .= " and dept.clientid = $deptid";
    }
    // head.fempjob,
    $query = "select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
                    head.artid,femp.client as fempcode, femp.clientname as fempname,
                    emp.clientname as empname,head.empjob,head.fjobtitle,
                    chead.description as articlename,cdetail.description as sectionname,
                    head.refx, head.hplace,head.line, head.explanation,
                    date(head.ddate) as ddate, head.htime, head.comments,
                    head.hdatetime, head.remarks,emp.client as empcode,dept.client as dept,
                    dept.clientname as deptname,head.deptid,ir.docno as irno,ir.idescription as irdesc,
                    chead.code as artcode,cdetail.section as sectioncode,head.fempid,
                    ifnull(jtitle.jobtitle, '') as jobtitle
              from notice_explain as head
              left join client as emp on emp.clientid=head.empid
              left join employee as employee on employee.empid = emp.clientid
              left join jobthead as jtitle on jtitle.line = employee.jobid
              left join client as dept on dept.clientid=head.deptid
              left join hincidenthead as ir on head.refx=ir.trno
              left join codehead as chead on chead.artid=head.artid
              left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
              left join client as femp on head.fempid=femp.clientid
              where date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
              union all
              select head.trno, head.docno, head.empid, date(head.dateid) as dateid,
                    head.artid,femp.client as fempcode, femp.clientname as fempname,
                    emp.clientname as empname,head.empjob,head.fjobtitle,
                    chead.description as articlename,cdetail.description as sectionname,
                    head.refx, head.hplace,head.line, head.explanation,
                    date(head.ddate) as ddate, head.htime, head.comments,
                    head.hdatetime, head.remarks,emp.client as empcode,dept.client as dept,
                    dept.clientname as deptname,head.deptid,ir.docno as irno,ir.idescription as irdesc,
                    chead.code as artcode,cdetail.section as sectioncode,head.fempid,
                    ifnull(jtitle.jobtitle, '') as jobtitle
              from hnotice_explain as head
              left join client as emp on emp.clientid=head.empid
              left join employee as employee on employee.empid = emp.clientid
              left join jobthead as jtitle on jtitle.line = employee.jobid
              left join client as dept on dept.clientid=head.deptid
              left join hincidenthead as ir on head.refx=ir.trno
              left join codehead as chead on chead.artid=head.artid
              left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
              left join client as femp on head.fempid=femp.clientid
              where date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
              order by docno";
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
    $str .= $this->reporter->col('NOTICE TO EXPLAIN REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
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
    $count = 30;
    $page = 30;
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
      $str .= $this->reporter->col('FROM EMPLOYEE', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->fempname, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '210', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('JOB TITLE', '80', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->fjobtitle, '80', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '210', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ARTICLE', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->artcode, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->articlename, '250', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '210', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('SECTION', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->sectioncode, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->sectionname, '250', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '210', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('REF INCIDENT REPORT NO.', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->irno, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->irdesc, '250', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '210', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DEADLINE', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->ddate, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('ADMINISTRATIVE HEARING DATE', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->hdatetime, '210', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('TIME', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->htime, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('PLACE', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->hplace, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('REMARKS', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->remarks, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '250', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '210', null, false, $border, 'LB', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('EXPLANATION', '170', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->explanation, '170', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('COMMENTS', '250', null, false, $border, 'LB', 'L', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->col($data->comments, '210', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'LBR', 'C', $font, $font_size, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100px', null, false, $border2, 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border2, 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border2, 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border2, 'B', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border2, 'B', 'C', $font, $font_size, '', '', '');
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
}//end class