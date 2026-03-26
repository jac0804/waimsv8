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

class job_offer
{
  public $modulename = 'Job Offer';
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
    if ($config['params']['companyid'] == 58) { //cdo
      $fields = ['radioprint', 'dbranchname', 'jobcode', 'deptrep'];
    } else {
      $fields = ['radioprint', 'jobcode', 'deptrep'];
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'jobcode.lookupclass', 'lookupjobtitlerep');
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
          0 as jobid,
          '' as jobcode,
          '' as jobtitle,
          '' as deptid,
          '' as deptname,
          adddate(left(now(),10),-360) as start,
            left(now(),10) as `end`,
          '' as deptrep,
          '' as dbranchname,
          '' as branchcode,
          '0' as branchid
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
    $jobcode     = $config['params']['dataparams']['jobtitle'];
    $deptid     = $config['params']['dataparams']['deptid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filter   = "";

    if ($jobcode != "") {
      $filter .= " and head.emptitle = '$jobcode'";
    }
    if ($deptid != 0) {
      $filter .= " and dept.clientid = $deptid";
    }

    if ($config['params']['companyid'] == 58) { //cdo
      $branchid     = $config['params']['dataparams']['branchid'];

      if ($branchid != 0) {
        $filter .= " and head.branchid = $branchid";
      }
    }


    $query = "select '' as client, head.docno, head.empid, app.empcode, date(head.dateid) as dateid, head.emptitle, 
                      head.effectdate, head.classrate,head.rate, head.empstat, head.monthsno, head.empname,
                      head.jobtitle, head.empno, head.nodep, head.dcode, head.dname, head.emptitle as jobcode,
                      dept.clientid as deptid, dept.client as dept, dept.clientname as deptname, section.sectid,
                      section.sectname, section.sectcode, pgroup.paygroup as tpaygroup, pgroup.line as paygroupid,
                      empstat.empstatus as empdesc
              from joboffer as head
              left join client as cl on cl.clientid=head.empid
              left join app on app.empid = head.empid
              left join client as dept on head.deptid=dept.clientid
              left join paygroup as pgroup on pgroup.line=head.paygroupid
              left join section as section on section.sectid=head.sectid
              left join empstatentry as empstat on empstat.line = head.empstat
              where date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
              union all
              SELECT '' as client, head.docno, head.empid, app.empcode, date(head.dateid) as dateid, head.emptitle, 
                      head.effectdate, head.classrate,head.rate, head.empstat, head.monthsno, head.empname,
                      head.jobtitle, head.empno, head.nodep, head.dcode, head.dname, head.emptitle as jobcode,
                      dept.clientid as deptid, dept.client as dept, dept.clientname as deptname, section.sectid,
                      section.sectname, section.sectcode, pgroup.paygroup as tpaygroup, pgroup.line as paygroupid,
                      empstat.empstatus as empdesc
              from hjoboffer as head
              left join client as cl on cl.clientid=head.empid
              left join app on app.empid = head.empid
              left join client as dept on head.deptid=dept.clientid
              left join paygroup as pgroup on pgroup.line=head.paygroupid
              left join section as section on section.sectid=head.sectid
              left join empstatentry as empstat on empstat.line = head.empstat
              where date(head.dateid) between '" . $start . "' and '" . $end . "' $filter 
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
    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB OFFER REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '110', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('DATE', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('APPLICANT NAME', '200', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('JOB TITLE', '180', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('STATUS', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('RATE', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('RATE TYPE', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
    $str .= $this->reporter->col('DEPARTMENT', '190', null, $bgcolors, $border, 'RTB', 'C', $font, $font_size, 'B', $fontcolor, '5px');
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

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);
    $chkemp = "";
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    foreach ($result as $key => $data) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '110', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empname, '200', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '180', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->empstat, '80', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('' . number_format($data->rate, 2), '80', null, false, $border, 'LB', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('' . $data->classrate, '80', null, false, $border, 'LB', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->deptname, '190', null, false, $border, 'LBR', 'LT', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      $chkemp = $data->client;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->col('', '110', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '190', null, false, $border, 'T', '', $font, $font_size, '', '', '');


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class