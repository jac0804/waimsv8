<?php

namespace App\Http\Classes\modules\modulereport\conti;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class hj
{

  private $modulename;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($config){
    $trno = $config['params']['dataid'];
    $query = "
    select
      num.trno, num.docno, head.empid, app.empcode, head.dateid, head.emptitle, head.effectdate, head.classrate,
      head.rate, head.empstat, head.monthsno, head.empname,
      head.jobtitle, head.empno, head.nodep, head.dcode, head.dname, head.emptitle as jobcode,
      dept.clientid as deptid, dept.client as dept, dept.clientname as deptname, section.sectid,
      section.sectname, section.sectcode, pgroup.paygroup as tpaygroup, pgroup.line as paygroupid,
      empstat.empstatus as empdesc
    from joboffer as head
    left join client as cl on cl.clientid=head.empid
    left join hrisnum as num on num.trno = head.trno
    left join app on app.empid = head.empid 
    left join client as dept on head.deptid=dept.clientid
    left join paygroup as pgroup on pgroup.line=head.paygroupid
    left join section as section on section.sectid=head.sectid
    left join empstatentry as empstat on empstat.line = head.empstat
    where num.trno = '$trno' and num.doc='HJ'
    union all
    select
      num.trno, num.docno, head.empid, app.empcode, head.dateid, head.emptitle, head.effectdate, head.classrate,
      head.rate, head.empstat, head.monthsno, head.empname,
      head.jobtitle, head.empno, head.nodep, head.dcode, head.dname, head.emptitle as jobcode,
      dept.clientid as deptid, dept.client as dept, dept.clientname as deptname, section.sectid,
      section.sectname, section.sectcode, pgroup.paygroup as tpaygroup, pgroup.line as paygroupid,
      empstat.empstatus as empdesc
    from hjoboffer as head
    left join client as cl on cl.clientid=head.empid   
    left join hrisnum as num on num.trno = head.trno
    left join app on app.empid = head.empid
    left join client as dept on head.deptid=dept.clientid
    left join paygroup as pgroup on pgroup.line=head.paygroupid
    left join section as section on section.sectid=head.sectid
    left join empstatentry as empstat on empstat.line = head.empstat
    where num.trno = '$trno' and num.doc='HJ'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
      if($config['params']['dataparams']['print'] == "default"){
        $str = $this->rpt_hj_layout($config, $data);
      }else if($config['params']['dataparams']['print'] == "PDFM") {
        $str = $this->rpt_hj_PDF($config, $data);
      }
      return $str;
  }

  public function rpt_hj_PDF($params, $data)
  { 
    $border = '1px solid';
    $font_size = '11';

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    
    $count = 55;
    $page = 54;

    $prepared = $params['params']['dataparams']['prepared'];
    $received = $params['params']['dataparams']['received'];
    $approved = $params['params']['dataparams']['approved'];

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.strtoupper($center). ' ' .'RSSC', '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 30, "JOB OFFER", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Applicant Name : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(240, 20, '(' . (isset($data[0]['empcode']) ? $data[0]['empcode'] : '') . ')' . ' ' . (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Department : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(240, 20, '(' . (isset($data[0]['dept']) ? $data[0]['dept'] : '') . ')' . ' ' . (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '', 'L', false);
    
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 20, "Job Title : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(240, 20, '(' . (isset($data[0]['jobcode']) ? $data[0]['jobcode'] : '') . ')' . ' ' . (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $font_size);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, 'B', $font_size);
    PDF::MultiCell(266, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
   
  } //end fn


  public function rpt_hj_layout($params, $data)
  { 
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';


    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $prepared = $params['params']['dataparams']['prepared'];
    $received = $params['params']['dataparams']['received'];
    $approved = $params['params']['dataparams']['approved'];

    
    $str = '';
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
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

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB OFFER ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
   
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Applicant Name:', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]['empcode']) ? $data[0]['empcode'] : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '750', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department:', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]['dept']) ? $data[0]['dept'] : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '750', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title:', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]['jobcode']) ? $data[0]['jobcode'] : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '750', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

}
