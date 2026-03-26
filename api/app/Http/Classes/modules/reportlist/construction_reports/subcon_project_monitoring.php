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

class subcon_project_monitoring
{
  public $modulename = 'Subcon Project Monitoring';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dclientname.label', 'Subcon');
    data_set($col1, 'dclientname.required', true);

    $fields = ['project', 'prepared', 'approved'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'project.name', "projectname");
    data_set($col2, 'project.required', true);

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
    '' as approved,
    '' as dclientname,
    '' as client,
    '' as clientname
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
    $query = $this->default_query($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_query($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $subprojectid = $config['params']['dataparams']['subproject'];
    $projectid = $config['params']['dataparams']['projectid'];
    $client = $config['params']['dataparams']['client'];

    $filter = "";

    $query = "
    select glhead.trno, gldetail.line,glhead.docno,
    case coa.alias 
    when 'AP4' 
    then (gldetail.cr-gldetail.db) 
    else 0 end as billing,
    case coa.alias 
    when 'APRT' 
    then (gldetail.cr-gldetail.db) 
    else 0 end as retention 
    from hjchead as glhead 
    left join gldetail on gldetail.trno = glhead.trno 
    left join coa on coa.acnoid = gldetail.acnoid
    left join projectmasterfile as project on project.line = gldetail.projectid
    left join subproject on subproject.line = gldetail.subproject
    left join client on client.client = glhead.client
    where glhead.doc ='JC' and client.client ='$client' and 
    project.line ='$projectid' and coa.alias in ('AP4','APRT') and 
    glhead.dateid between '$start' and '$end'
    group by glhead.docno,coa.alias, glhead.trno, gldetail.line, gldetail.cr, gldetail.db
  ";

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

    $projectname  = $config['params']['dataparams']['projectname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $client   = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];

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
    $str .= $this->reporter->col('SUBCON PROJECT MONITORING', '800', null, false, $border, '', 'C', $font, '14', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M d Y', strtotime($start)) . ' TO ' . date('M d Y', strtotime($end)), '800', null, false, $border, '', 'C', $font, '12', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Supplier/Subcon", '50', null, false, $border, '', 'L', $font, '10', 'B', '', '5px');

    if ($client == "") {
      $client = "ALL";
    }

    $str .= $this->reporter->col($client, '50', null, false, $border, '', 'L', $font, '10', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Project", '50', null, false, $border, '', 'L', $font, '10', 'B', '', '5px');

    if ($projectname == "") {
      $projectname = "ALL";
    }

    $str .= $this->reporter->col($projectname, '600', null, false, $border, '', 'L', $font, '10', 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Sub-Project", '50', null, false, $border, '', 'L', $font, '10', 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
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
    $projectid  = $config['params']['dataparams']['projectid'];
    $client  = $config['params']['dataparams']['client'];

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
    $grandtotal = 0;
    $prjname = "";
    $acnoname = "";

    $cols = "";
    $acnos = "";

    $str .= "<br>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $qry = "select sum(amt) as value from 
    (select sum(jostock.ext) as amt 
    from johead 
    left join jostock on jostock.trno = johead.trno
    left join projectmasterfile as project on project.line = johead.projectid
    left join subproject on subproject.line = johead.subproject
    left join client on client.client = johead.client
    where client.client ='$client' and project.line ='$projectid' 
    group by client.client,project.line
    union all
    select sum(jostock.ext) as amt 
    from hjohead as johead 
    left join hjostock as jostock on jostock.trno = johead.trno
    left join projectmasterfile as project on project.line = johead.projectid
    left join subproject on subproject.line = johead.subproject
    left join client on client.client = johead.client
    where client.client ='$client' and project.line ='$projectid' 
    group by client.client,project.line) as A;";
    $totalamt = $this->coreFunctions->datareader($qry);

    $str .= $this->reporter->col("Total Contract/JO/PO Amount", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totalamt, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Total Billings", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');

    $billing = 0;
    $retention = 0;

    foreach ($result as $key => $value) {
      $billing += $value->billing;
      $retention += $value->retention;
    }

    $str .= $this->reporter->col(number_format($billing, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Retention", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($retention, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Total Amount Due", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');

    $totaldue = $result[0]->billing - $result[0]->retention;
    $str .= $this->reporter->col(number_format($totaldue, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Outstanding Credit Memo's", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');

    $qrycm = "
    select sum(detail.db-detail.cr) as cm 
    from ladetail as detail 
    left join lahead as head on head.trno = detail.trno
    left join projectmasterfile as project on project.line = detail.projectid
    left join client as cl on cl.client = head.client
    where head.doc ='CM' and project.line = '$projectid' and cl.client = '$client'
    union all
    select sum(detail.db-detail.cr) as cm 
    from gldetail as detail 
    left join glhead as head on head.trno = detail.trno
    left join projectmasterfile as project on project.line = detail.projectid
    left join client as cl on cl.clientid = head.clientid
    where head.doc ='CM' and project.line = '$projectid' and cl.client = '$client'";
    $resultcm  = $this->coreFunctions->opentable($qrycm);

    $cm = !empty($resultcm) ? $resultcm[0]->cm : 0;

    $str .= $this->reporter->col(number_format($cm, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Total Cash Payments Made", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');

    $qrycr = "
    select sum(detail.db-detail.cr) as payment 
    from ladetail as detail 
    left join lahead as head on head.trno = detail.trno
    left join projectmasterfile as project on project.line = detail.projectid
    left join client as cl on cl.client = head.client
    where head.doc ='CM' and project.line = '$projectid' and cl.client = '$client'
    union all
    select sum(detail.db-detail.cr) as payment 
    from gldetail as detail 
    left join glhead as head on head.trno = detail.trno
    left join projectmasterfile as project on project.line = detail.projectid
    left join client as cl on cl.clientid = head.clientid
    where head.doc ='CM' and project.line = '$projectid' and cl.client = '$client'";
    $resultcr  = $this->coreFunctions->opentable($qrycr);

    $payment = !empty($resultcr) ? $resultcr[0]->payment : 0;

    $str .= $this->reporter->col(number_format($payment, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $current_due = $totaldue - ($cm - $payment);

    $str .= $this->reporter->col("Net Due Payable (Current)", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($current_due, 2), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->col('Billing', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('% Accomplishment', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Amount', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Recoupment', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Credit Memo Applied', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Cash Payment', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('Balance', '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');

    $grandtotalamt = 0;
    $grandtotalcm = 0;
    $grandtotalpayment = 0;
    $grandtotalbalance = 0;

    foreach ($result as $key => $data) {
      $qrycm = "
      select (detail.db-detail.cr) as cm 
      from ladetail as detail 
      left join lahead as head on head.trno = detail.trno
      where head.doc ='CM' and detail.refx = '" . $data->trno . "' and 
      detail.linex = '" . $data->line . "'
      union all
      select (detail.db-detail.cr) as cm 
      from gldetail as detail 
      left join glhead as head on head.trno = detail.trno
      where head.doc ='CM' and detail.refx = '" . $data->trno . "' and 
      detail.linex = '" . $data->line . "'";
      $cm = $this->coreFunctions->opentable($qrycm);

      $qrycr = "
      select (detail.db-detail.cr) as payment 
      from ladetail as detail 
      left join lahead as head on head.trno = detail.trno
      where head.doc ='CR' and detail.refx = '" . $data->trno . "' and 
      detail.linex = '" . $data->line . "'
      union all
      select (detail.db-detail.cr) as payment 
      from gldetail as detail 
      left join glhead as head on head.trno = detail.trno
      where head.doc ='CR' and detail.refx = '" . $data->trno . "' and 
      detail.linex = '" . $data->line . "'";
      $cr = $this->coreFunctions->opentable($qrycr);

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($key + 1, '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
      $percentage = ($data->billing / $totalamt) * 100;
      $str .= $this->reporter->col(number_format($percentage, 2) . ' %', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($data->billing, 2), '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');

      $cmemo = !empty($cm) ? $cm[0]->cm : 0;
      $cpayment = !empty($cr) ? $cr[0]->payment : 0;

      $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($cmemo, 2), '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($cpayment, 2), '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');

      $balance = $data->billing - ($cmemo + $cpayment);

      $str .= $this->reporter->col(number_format($balance, 2), '200', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px');
      $str .= $this->reporter->endrow();

      $grandtotalamt += $data->billing;
      $grandtotalcm += $cmemo;
      $grandtotalpayment += $cpayment;
      $grandtotalbalance += $balance;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($grandtotalamt, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($grandtotalcm, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($grandtotalpayment, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($grandtotalbalance, 2), '200', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '5px');
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