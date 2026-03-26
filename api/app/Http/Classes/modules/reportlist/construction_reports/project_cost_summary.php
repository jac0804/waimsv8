<?php

namespace App\Http\Classes\modules\reportlist\construction_reports;

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
use Illuminate\Support\Facades\URL;

class project_cost_summary
{
  public $modulename = 'Project Cost Summary';
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
    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['project', 'subprojectname', 'radioposttype', 'prepared', 'approved'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'project.name', "projectname");
    data_set($col2, 'project.required', false);
    data_set($col2, 'subprojectname.type', "lookup");
    data_set($col2, 'subprojectname.action', "lookupsubproject");
    data_set($col2, 'subprojectname.addedparams', ['projectid']);
    data_set($col2, 'subprojectname.lookupclass', 'default');
    data_set($col2, 'subprojectname.required', false);

    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
     adddate(left(now(),10),-360) as start,
    left(now(),10) as `end`,
    '0' as posttype,
    '0' as subproject,
    '' as subprojectname,
    '' as project,
    '' as projectcode,
    '0' as projectid,
    '' as projectname,
    '' as prepared,
    '' as approved
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

    $result = $this->default_layout($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $posttype   = $config['params']['dataparams']['posttype'];

    $query = $this->default_query($config);

    
    return $this->coreFunctions->opentable($query);
  }

  public function default_query($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $subprojectid = $config['params']['dataparams']['subproject'];
    $projectid = $config['params']['dataparams']['projectid'];

    $filter = "";

    if ($projectid != 0) {
      $filter .= " and prj.line = '$projectid'";
    }

    if ($subprojectid != 0) {
      $filter .= " and sprj.line = '$subprojectid'";
    }

    switch ($posttype) {
      case 0: // posted
        $query = "select acnoname,sum(amount)as amount,prjname from (
        select head.docno, date(head.dateid) as dateid, 
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.clientid = head.clientid
        where
          head.doc in ('PV', 'CV', 'GJ','RR','MI') and
          date(head.dateid) between '$start' and '$end' and
          (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5')) $filter
         group by head.docno, head.dateid, prj.name, coa.acnoname
         union all
        select head.docno, date(head.dateid) as dateid,
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from hjchead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.client = head.client
        where 
          date(head.dateid) between '$start' and '$end'
         and (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5'))  $filter
              group by head.docno, head.dateid, prj.name, coa.acnoname) as k
      group by prjname,acnoname
      order by prjname,acnoname";

        break;
      case 1: // unposted
        $query = "select acnoname,sum(amount)as amount,prjname from (
        select head.docno, date(head.dateid) as dateid, 
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from lahead as head
        left join ladetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.client = head.client
        where
          head.doc in ('PV', 'CV', 'GJ','RR','MI') and
          date(head.dateid) between '$start' and '$end' and
          (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5')) $filter
         group by head.docno, head.dateid, prj.name, coa.acnoname
         union all
        select head.docno, date(head.dateid) as dateid, 
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from jchead as head
        left join ladetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.client = head.client
        where 
          date(head.dateid) between '$start' and '$end' and
          (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5')) $filter
         group by head.docno, head.dateid, prj.name, coa.acnoname) as k
        group by prjname,acnoname
        order by prjname,acnoname";
        break;
      default: // all
        $query = "select acnoname,sum(amount)as amount,prjname from (
        select head.docno, date(head.dateid) as dateid, 
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.clientid = head.clientid
        where
          head.doc in ('PV', 'CV', 'GJ','RR','MI') and
          date(head.dateid) between '$start' and '$end' and
          (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5')) $filter
         group by head.docno, head.dateid, prj.name, coa.acnoname
         union all
        select head.docno, date(head.dateid) as dateid,
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from hjchead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.client = head.client
        where 
          date(head.dateid) between '$start' and '$end'
         and (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5'))  $filter
              group by head.docno, head.dateid, prj.name, coa.acnoname
        union all 
        select head.docno, date(head.dateid) as dateid, 
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from lahead as head
        left join ladetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.client = head.client
        where
          head.doc in ('PV', 'CV', 'GJ','RR','MI') and
          date(head.dateid) between '$start' and '$end' and
          (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5')) $filter
         group by head.docno, head.dateid, prj.name, coa.acnoname
         union all
        select head.docno, date(head.dateid) as dateid, 
        coa.acnoname, sum(detail.db - detail.cr) as amount,
        prj.name as prjname
        from jchead as head
        left join ladetail as detail on detail.trno = head.trno
        left join coa as coa on coa.acnoid = detail.acnoid
        left join cntnum as num on num.trno = head.trno
        left join projectmasterfile as prj on prj.line = detail.projectid
        left join subproject as sprj on sprj.line = detail.subproject
        left join client as cl on cl.client = head.client
        where 
          date(head.dateid) between '$start' and '$end' and
          (coa.cat = 'E' or coa.alias in ('AR5', 'AP3','AP5')) $filter
         group by head.docno, head.dateid, prj.name, coa.acnoname) as k
group by prjname,acnoname
order by prjname,acnoname
      ";
        break;
    }
    return $query;
  }

  private function generateReportHeader($center, $username)
  {
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    return $str;
  } //end function generate report header

  public function default_header($config)
  {
    $mdc = URL::to('/images/reports/mdc.jpg');
    $tuv = URL::to('/images/reports/tuv.jpg');

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $subprojectname  = $config['params']['dataparams']['subprojectname'];
    $projectname  = $config['params']['dataparams']['projectname'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $str = "";
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str .= "<div style='position: relative;'>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->generateReportHeader($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<div style='position:absolute; top: 60px;'>";
    $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('<img src ="' . $tuv . '" alt="TUV" width="140px" height ="70px" style="margin-left: 500px;">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
    $str .= "</div>";

    $str .= "</div>";

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT COST SUMMARY', '800', null, false, $border, '', 'C', $font, '14', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M d Y', strtotime($start)) . ' TO ' . date('M d Y', strtotime($end)), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    switch ($posttype) {
      case '0':
        $posttype = "Posted";
        break;
      case '1':
        $posttype = "Unposted";
        break;
      default:
        $posttype = "ALL";
        break;
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Project: " . $projectname, '800', null, false, $border, '', 'C', $font, '12', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Subproject: " . $subprojectname, '800', null, false, $border, '', 'C', $font, '12', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Status: " . $posttype, '800', null, false, $border, '', 'C', $font, '12', 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT NAME', '200', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('EXPENSES', '200', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('AMOUNT', '200', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function default_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 38;

    $str = '';
    $layoutsize = '800';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header($config);
    $totalamt = 0;
    $subtotal = 0;
    $prjname = "";
    $acnoname = "";

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();

      if ($prjname != $data->prjname) {
        if ($prjname != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("SUB TOTAL: ", '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotal, $decimal), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $subtotal = 0;
        }
      }

      $str .= $this->reporter->startrow();
      if ($prjname != $data->prjname) {
        $str .= $this->reporter->col($data->prjname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col("", '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col("", '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col("", '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("", '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->acnoname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, $decimal), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt += $data->amount;
      $subtotal += $data->amount;
      $prjname = $data->prjname;
      $acnoname = $data->acnoname;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("SUB TOTAL: ", '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotal, $decimal), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '200', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("GRAND TOTAL: ", '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $decimal), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PREPARED BY', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('APPROVED BY', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($config['params']['dataparams']['prepared'], '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col($config['params']['dataparams']['approved'], '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class