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

class personnel_requisition
{
  public $modulename = 'Personnel Requisition';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1000'];

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
      $fields = ['radioprint',  'divrep',  'month', 'month2', 'year',];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'year.required', true);
      data_set($col1, 'month.type', 'lookup');
      data_set($col1, 'month.readonly', true);
      data_set($col1, 'month.action', 'lookuprandom');
      data_set($col1, 'month.lookupclass', 'lookup_month');
      data_set($col1, 'month2.type', 'lookup');
      data_set($col1, 'month2.readonly', true);
      data_set($col1, 'month2.action', 'lookuprandom');
      data_set($col1, 'month2.lookupclass', 'lookup_month2');
      data_set($col1, 'divrep.label', 'Company Name');

      $fields = ['radioreporttype'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'radioreporttype.options', [
        ['label' => 'Detailed', 'value' => '0', 'color' => 'orange'],
        ['label' => 'Summarized', 'value' => '1', 'color' => 'orange']
      ]);
    } else {
      $fields = ['radioprint', 'dclientname', 'deptrep'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
      data_set($col1, 'dclientname.label', 'Requesting Personnel');
      data_set($col1, 'deptrep.lookupclass', 'lookupddeptname');
      data_set($col1, 'deptrep.label', 'Requesting Department');

      $fields = ['start', 'end'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'start.required', true);
      data_set($col2, 'end.required', true);
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
          'default' as print,
          '' as client,'' as clientname,'' as dclientname,
          '' as deptid,'' as deptname,'' as deptrep,
          adddate(left(now(),10),-360) as start,left(now(),10) as `end`,
          '0' as reporttype,left(now(),4) as year, 
          '' as bmonth,'' as month,  '' as bmonth2,'' as month2, 
          '' as divid,'' as divcode,'' as divname,'' as divrep,'' as division
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
    if ($config['params']['companyid'] == 58) { //cdo
      $reporttype = $config['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case '0':
          return $this->CDO_detailed($config);
          break;
        case '1':
          return $this->CDO_summarized($config);
          break;
      }
    } else {
      return $this->reportDefaultLayout($config);
    }
  }

  public function reportDefault($config)
  {
    // QUERY
    $filter   = "";
    $filter1   = "";
    if ($config['params']['companyid'] == 58) { //cdo
      $year = $config['params']['dataparams']['year'];
      $bmonth = $config['params']['dataparams']['bmonth'];
      $bmonth2 = $config['params']['dataparams']['bmonth2'];
      $divid     = $config['params']['dataparams']['divid'];
      $divrep    = $config['params']['dataparams']['divrep'];
      if ($divrep != '') {
        $filter = " and emp.divid = $divid";
      }
      $reporttype = $config['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case '0':
          $query = "select head.docno,date(head.dateid) as dateid, head.dept, personnel, job,
                          headcount, d.clientname as deptname, em.clientname as personnelname, head.empid, jh.jobtitle,
                          date(head.startdate) as startdate,date(head.enddate) as enddate,
                          concat(reason.category,' ',head.hirereason) as reason,DATEDIFF(head.enddate,head.startdate) as totalrange,
                          group_concat(concat(app.empfirst,' ',app.empmiddle,' ',app.emplast) separator '\r\n') as applicant
                    from personreq as head
                    left join client as em on em.clientid = head.empid
                    left join employee as emp on emp.empid=head.empid
                    left join empstatentry as empstat on empstat.line = head.empstatusid
                    left join client as d on d.client = head.dept
                    left join hrisnum as num on num.trno = head.trno
                    left join jobthead as jh on jh.docno=head.job
                    left join reqcategory as reason on reason.line=head.reason
                    left join hpersonreqdetail as detail on detail.trno=head.trno
                    left join app on app.empid=detail.appid
                    where year(head.dateid)= '$year'  and month(head.dateid) between '$bmonth' and '$bmonth2' and (head.status1<>'D' and head.status2<>'D' and head.status3<>'D') $filter
                    group by head.docno,head.dateid, head.dept, personnel, job,
                            headcount, d.clientname, em.clientname, head.empid, jh.jobtitle,
                            head.startdate,head.enddate,reason.category,head.hirereason
                    union all
                    SELECT head.docno,  date(head.dateid) as dateid, head.dept, personnel, job,
                          headcount,d.clientname as deptname, em.clientname as personnelname, head.empid, jh.jobtitle,
                          date(head.startdate) as startdate,date(head.enddate) as enddate,
                          concat(reason.category,' ',head.hirereason) as reason,DATEDIFF(head.enddate,head.startdate) as totalrange,
                          group_concat(concat(app.empfirst,' ',app.empmiddle,' ',app.emplast) separator '\r\n') as applicant
                    from hpersonreq as head
                    left join client as em on em.clientid = head.empid
                    left join employee as emp on emp.empid=head.empid
                    left join empstatentry as empstat on empstat.line = head.empstatusid
                    left join client as d on d.client = head.dept
                    left join hrisnum as num on num.trno = head.trno
                    left join jobthead as jh on jh.docno=head.job
                    left join reqcategory as reason on reason.line=head.reason
                    left join hpersonreqdetail as detail on detail.trno=head.trno
                    left join app on app.empid=detail.appid
                    where year(head.dateid)= '$year'  and month(head.dateid) between '$bmonth' and '$bmonth2' and (head.status1<>'D' and head.status2<>'D' and head.status3<>'D') $filter
                    group by head.docno,head.dateid, head.dept, personnel, job,
                            headcount, d.clientname, em.clientname, head.empid, jh.jobtitle,
                            head.startdate,head.enddate,reason.category,head.hirereason
                    order by docno";
          break;
        case '1':
          $query = "select jobtitle,branchname,sum(headcount) as ctrdocno from(
                    select jh.jobtitle,ifnull(branch.clientname,'') as branchname,head.headcount
                    from personreq as head
                    left join employee as emp on emp.empid=head.empid
                    left join hrisnum as num on num.trno = head.trno
                    left join jobthead as jh on jh.docno=head.job
                    left join client as branch on branch.clientid = head.branchid
                    where year(head.dateid)= '$year'  and month(head.dateid) between '$bmonth' and '$bmonth2' and (head.status1<>'D' and head.status2<>'D' and head.status3<>'D') $filter
                    union all
                    SELECT jh.jobtitle,ifnull(branch.clientname,'') as branchname,head.headcount
                    from hpersonreq as head
                    left join employee as emp on emp.empid=head.empid
                    left join hrisnum as num on num.trno = head.trno
                    left join jobthead as jh on jh.docno=head.job
                    left join client as branch on branch.clientid = head.branchid
                    where year(head.dateid)= '$year'  and month(head.dateid) between '$bmonth' and '$bmonth2' and (head.status1<>'D' and head.status2<>'D' and head.status3<>'D') $filter
                    ) as k
                    where jobtitle is not null
                    group by jobtitle,branchname
                    order by jobtitle,branchname";
          break;
      }
    } else {
      $client     = $config['params']['dataparams']['client'];
      $deptid     = $config['params']['dataparams']['deptid'];
      $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
      $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
      if ($client != "") {
        $filter .= " and em.client = '$client'";
      }
      if ($deptid != 0) {
        $filter1 .= " and d.clientid = $deptid";
      }
      $query = "select '' as client,head.docno,date(head.dateid) as dateid, head.dept, personnel, dateneed, job, 
                     head.class, headcount, hpref, agerange,gpref, rank, empstat.line as empstatusid, empstat.empstatus as empstatus, reason, remark, refx, qualification,d.clientname as deptname, em.clientname as personnelname, head.empid, jh.jobtitle 
              from personreq as head
              left join client as em on em.clientid = head.empid
              left join empstatentry as empstat on empstat.line = head.empstatusid
              left join client as d on d.client = head.dept
              left join hrisnum as num on num.trno = head.trno
              left join jobthead as jh on jh.docno=head.job
              where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
              union all
              SELECT '' as client,head.docno,  date(head.dateid) as dateid, head.dept, personnel, dateneed, job, 
                    head.class, headcount, hpref, agerange,gpref, rank, empstat.line as empstatusid, empstat.empstatus as empstatus, reason, remark, refx, qualification,
                  d.clientname as deptname, em.clientname as personnelname, head.empid, jh.jobtitle
              from hpersonreq as head
              left join client as em on em.clientid = head.empid
              left join empstatentry as empstat on empstat.line = head.empstatusid
              left join client as d on d.client = head.dept
              left join hrisnum as num on num.trno = head.trno
              left join jobthead as jh on jh.docno=head.job
              where head.dateid between '" . $start . "' and '" . $end . "' $filter $filter1 
              order by docno ";
    }

    return $this->coreFunctions->opentable($query);
  }


  private function getReportSummarizedTimeline($config)
  {
    $filter   = "";
    $year = $config['params']['dataparams']['year'];
    $bmonth = $config['params']['dataparams']['bmonth'];
    $divid     = $config['params']['dataparams']['divid'];
    $divrep    = $config['params']['dataparams']['divrep'];
    if ($divrep != '') {
      $filter = " and emp.divid = $divid";
    }
    $query = "select jobtitle, (sum(totalrage)/sum(jobcount)) as avedays from(
          select jh.jobtitle,DATEDIFF(head.enddate,head.startdate) as totalrage, count(jh.jobtitle) as jobcount
          from personreq as head
          left join employee as emp on emp.empid=head.empid
          left join hrisnum as num on num.trno = head.trno
          left join jobthead as jh on jh.docno=head.job
          where year(head.dateid)= '$year' and month(head.dateid)= '$bmonth' $filter and head.enddate is not null
          group by jh.jobtitle,head.enddate,head.startdate
          union all
          SELECT jh.jobtitle,DATEDIFF(head.enddate,head.startdate) as totalrage, count(jh.jobtitle) as jobcount
          from hpersonreq as head
          left join employee as emp on emp.empid=head.empid
          left join hrisnum as num on num.trno = head.trno
          left join jobthead as jh on jh.docno=head.job
          where year(head.dateid)= '$year'  and month(head.dateid)= '$bmonth' $filter and head.enddate is not null
          group by jh.jobtitle,head.enddate,head.startdate
          ) as k where jobtitle is not null group by jobtitle
          order by jobtitle";
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
    $str .= $this->reporter->col('PERSONNEL REQUISITION REPORTS', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Date Covered: ' . strtoupper($start) . ' to ' . strtoupper($end), NULL, null, false, $border, '', 'L', $font, '10', '', '', '', '');
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', '115', null, $bgcolors, $border, 'LTB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('DATE', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('REQUESTING DEPARTMENT', '150', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('REQUESTING PERSONNEL', '180', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('JOB TITLE', '135', null, $bgcolors, $border, 'TB', 'L', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('HEAD COUNT', '50', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('RANK', '80', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('GENDER', '50', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('AGE', '50', null, $bgcolors, $border, 'TB', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('REASON', '110', null, $bgcolors, $border, 'RTB', 'C', $font, $font_size, 'B', $fontcolor, '');
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

      $str .= $this->reporter->col($data->docno, '115', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->deptname, '150', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->personnelname, '180', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, '135', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->headcount, '50', null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->rank, '80', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->gpref, '50', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->agerange, '50', null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->reason, '110', null, false, $border, 'LBR', 'LT', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '115', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '135', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', '', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, 'T', '', $font, $font_size, '', '', '');

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();


    return $str;
  }

  private function CDO_detailedheader($config)
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

    $str .= $this->reporter->begintable(1900);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable(1900);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RECRUITMENT PLACEMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $year = $config['params']['dataparams']['year'];
    $bmonth = $config['params']['dataparams']['month'];
    $bmonth2 = $config['params']['dataparams']['month2'];

    $str .= $this->reporter->begintable(800);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From ' . $bmonth . ' to ' . $bmonth2 . ' ' . $year, null, null, false, $border, '', '', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable(1900);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', 1340, null, false, $border, 'LTR', 'C', $font, $font_size, 'B', '', '10px');

    $str .= $this->reporter->col('RECRUITMENT TIMELINE', 560, null, false, $border, 'LTR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable(1900);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT NO.', 140, null, false, $border, 'LTBR', 'C', $font, $font_size, 'B', '', '10px');
    $str .= $this->reporter->col('DATE REQUESTED', 120, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('REASON FOR HIRING', 230, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('REQUESTING DEPARTMENT', 200, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('REQUESTING PERSONNEL', 250, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('JOB TITLE NEEDED', 200, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('NO. OF PERSONNEL NEEDED', 200, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE STARTED', 130, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DATE COMPLETED', 130, null, false, $border, 'TBR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('TOTAL RANGE', 100, null, false, $border, 'RTB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('POSITION FILL-IN BY', 200, null, false, $border, 'RTB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function CDO_detailed($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '9';
    $count = 55;
    $page = 55;
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport(1900);
    $str .= $this->CDO_detailedheader($config);
    $chkemp = "";

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable(1900);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, 140, null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col($data->dateid, 120, null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->reason, 230, null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->deptname, 200, null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->personnelname, 249, null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->jobtitle, 200, null, false, $border, 'LB', 'LT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->headcount, 200, null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->startdate, 130, null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->enddate, 130, null, false, $border, 'LB', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->totalrange, 100, null, false, $border, 'LBR', 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->applicant, 200, null, false, $border, 'LBR', 'LT', $font, $font_size, '', '', '');

      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->CDO_detailedheader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endreport();


    return $str;
  }

  private function CDO_summarizedheader($config)
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

    $str .= $this->reporter->begintable(800);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable(800);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MANPOWER ALLOCATION SUMMARY REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $year = $config['params']['dataparams']['year'];
    $bmonth = $config['params']['dataparams']['month'];
    $bmonth2 = $config['params']['dataparams']['month2'];

    $str .= $this->reporter->begintable(800);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('From ' . $bmonth . ' to ' . $bmonth2 . ' ' . $year, null, null, false, $border, '', '', $font, '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable(800);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB TITLE', 200, null, $bgcolors, $border, 'LTR', 'C', $font, $font_size, 'B', $fontcolor, '10px');
    $str .= $this->reporter->col('BRANCH TO BE FILED', 150, null, $bgcolors, $border, 'RT', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->col('NO. OF PERSONNEL NEEDED', 150, null, $bgcolors, $border, 'RT', 'C', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function CDO_summarized($config)
  {
    $result = $this->reportDefault($config);

    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = '9';
    $count = 55;
    $page = 55;
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport(800);
    $str .= $this->CDO_summarizedheader($config);
    $jobtitle = "";

    foreach ($result as $key => $data) {

      $bot = '';

      if (count($result) == $key + 1) {
        $bot = 'B';
      }

      $str .= $this->reporter->begintable(800);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      if ($jobtitle == $data->jobtitle) {
        $str .= $this->reporter->col('', 200, null, false, $border, 'L' .  $bot, 'CT', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($data->branchname, 150, null, false, $border, 'LRT' . $bot, 'CT', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($data->ctrdocno, 150, null, false, $border, 'LRT' . $bot, 'CT', $font, $font_size, '', '', '');
      } else {
        $str .= $this->reporter->col($data->jobtitle, 200, null, false, $border, 'LT' . $bot, 'CT', $font, $font_size, '', '', '5px');
        $str .= $this->reporter->col($data->branchname, 150, null, false, $border, 'LTR' . $bot, 'CT', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($data->ctrdocno, 150, null, false, $border, 'LTR' . $bot, 'CT', $font, $font_size, '', '', '');
        $jobtitle = $data->jobtitle;
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      // if ($this->reporter->linecounter == $page) {
      //   $str .= $this->reporter->endtable();
      //   $str .= $this->reporter->page_break();
      //   $str .= $this->CDO_summarizedheader($config);
      //   $page = $page + $count;
      // }
    }

    $str .= $this->reporter->col('', 200, null, false, $border, 'T', 'CT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 150, null, false, $border, 'T', 'CT', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', 150, null, false, $border, 'T', 'LT', $font, $font_size, '', '', '');



    //timeline summarized
    $str .= '<br/><br/><br/>';

    $str .= $this->reporter->begintable(800);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUMMARIZED RECRUITMENT TIMELINE', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $timeline = $this->getReportSummarizedTimeline($config);

    $fontcolor = '#FFFFFF'; //white
    $bgcolors = '#000000'; //black
    $str .= $this->reporter->begintable(800);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("JOB TITLE", 400, null, $bgcolors, $border, 'LTR', 'CT', $font, $font_size, 'B', $fontcolor, '10px');
    $str .= $this->reporter->col("GENERAL AVERAGE RANGE", 400, null, $bgcolors, $border, 'LRT', 'CT', $font, $font_size, 'B', $fontcolor, '');
    $str .= $this->reporter->endrow();

    $totaldays = 0;

    foreach ($timeline as $key => $tval) {
      $bot = '';

      if (count($timeline) == $key + 1) {
        $bot = 'B';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($tval->jobtitle, 400, null, false, $border, 'LT' . $bot, 'CT', $font, $font_size, '', '', '5px');
      $str .= $this->reporter->col((float) $tval->avedays, 400, null, false, $border, 'LRT' . $bot, 'CT', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $totaldays = $totaldays + (float) $tval->avedays;
    }
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $averagedays = $totaldays / count($timeline);

    $str .= $this->reporter->begintable(800);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("GENERAL AVERAGE OF DAYS: ", 400, null, false, $border, '', 'RT', $font, $font_size + 2, 'B', '', '');
    $str .= $this->reporter->col((float) $averagedays, 100, null, false, $border, 'B', 'CT', $font, $font_size + 2, 'B', '', '');
    $str .= $this->reporter->col("DAYS", 300, null, false, $border, '', 'LT', $font, $font_size + 2, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable(800);

    $str .= $this->reporter->endreport();


    return $str;
  }
}//end class