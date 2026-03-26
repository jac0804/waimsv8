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

class ha
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

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
    select
      dept.clientname as deptname, dept.client as dept, head.trno, head.docno, 
      head.empid, head.dateid, head.empname,
      head.jobtitle, head.type, head.title,
      head.venue, date(head.date1) as date1, date(head.date2) as date2, 
      head.purpose, head.budget,head.deptid,
      emp.client as empcode,
      emp.clientname as empname
      from traindev as head
      left join hrisnum as num on num.trno = head.trno
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      where num.trno = '$trno' and num.doc='HA'
      union all
    select
      dept.clientname as deptname, dept.client as dept, head.trno, head.docno, 
      head.empid, head.dateid, head.empname,
      head.jobtitle, head.type, head.title,
      head.venue, date(head.date1) as date1, date(head.date2) as date2, 
      head.purpose, head.budget,head.deptid,
      emp.client as empcode,
      emp.clientname as empname
      from Htraindev as head
      left join hrisnum as num on num.trno = head.trno
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      where num.trno = '$trno' and num.doc='HA'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
      if($config['params']['dataparams']['print'] == "default"){
        $str = $this->rpt_hq_layout($config, $data);
      }else if($config['params']['dataparams']['print'] == "PDFM") {
        $str = $this->rpt_hq_PDF($config, $data);
      }
      return $str;
  }

  public function rpt_hq_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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
    $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n", '', 'C');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(800, 30, "REQUEST TRAINING AND DEVELOPMENT", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 25, "Date Start : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(240, 25, isset($data[0]['date1']) ? $data[0]['date1'] : '', '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 25, "Date End : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(240, 25, isset($data[0]['date2']) ? $data[0]['date2'] : '', '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 25, "Employee Name : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(300, 25, '(' . (isset($data[0]['empcode']) ? $data[0]['empcode'] : '') . ')' . ' ' . (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 25, "Department : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(300, 25, '(' . (isset($data[0]['dept']) ? $data[0]['dept'] : '') . ')' . ' ' . (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 25, "Job Title : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(300, 25, isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : '', '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 25, "Title : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(300, 25, isset($data[0]['title']) ? $data[0]['title'] : '', '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 25, "Type : ", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(300, 25, isset($data[0]['type']) ? $data[0]['type'] : '', '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['received'], '', 'L');

    
    return PDF::Output($this->modulename . '.pdf', 'S');
   
  } //end fn

  public function rpt_hq_layout($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $prepared = $params['params']['dataparams']['prepared'];
    $received = $params['params']['dataparams']['received'];
    $approved = $params['params']['dataparams']['approved'];
    
    $str = '';
    $border = "1px solid ";
    $font = "Century Gothic ";
    $fontsize = "11";
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
    $str .= $this->reporter->col('REQUEST TRAINING AND DEVELOPMENT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
   
    
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Start:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['date1']) ? $data[0]['date1'] : '', '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date End:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['date2']) ? $data[0]['date2'] : '', '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee Name:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]['empcode']) ? $data[0]['empcode'] : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]['dept']) ? $data[0]['dept'] : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : '', '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Title:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['title']) ? $data[0]['title'] : '', '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Type:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['type']) ? $data[0]['type'] : '', '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


}
