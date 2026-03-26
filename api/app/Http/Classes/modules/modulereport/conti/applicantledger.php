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

class applicantledger
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
    $fields = [
      'radioprint',
      'prepared',
      'approved',
      'received',
      'print'
    ];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
  ");
  }

  public function report_default_query($config)
  {

    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = $config['params']['dataid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];


    $query = "select 
      empid, empcode, emplast, empfirst, empmiddle, address, city, 
      country, zipcode, telno, mobileno, email,
      citizenship, religion, alias, bday, jobtitle, 
      jobcode, jobdesc, maidname, appdate, remarks, type,
      jstatus, mapp, bplace, child, status, gender, 
      ishired, hired, idno, jobid, createby, center,
      viewby, editby, viewdate, editdate, createdate,
      concat(empfirst, ' ', empmiddle, ' ', emplast) as clientname,
      empcode as client
    from app where empid='$clientid'";
    
    return $this->coreFunctions->opentable($query);
  } //end fn

  public function reportplotting($config, $data)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_applicant_layout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_applicant_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_applicant_layout($config, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
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
    $str .= $this->reporter->col('APPLICANT LEDGER - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '11', '', '', '');
    // $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, '11', '', '', '');
    // $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Applicant Name:', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '750', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->address) ? $data[0]->address : ''), '750', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
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
  }


  public function rpt_applicant_PDF($config, $data)
  {
    $border = '1px solid';
    $fontsize = '11';
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $count = 55;
    $page = 54;

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
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 30, "APPLICANT LEDGER - PROFILE", '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Applicant Name : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(300, 10, (isset($data[0]->client) ? $data[0]->client : ''), '', 'L', false);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Address : ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(220, 10, (isset($data[0]->address) ? $data[0]->address : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $approved, '', 'L');



    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function rpt_applicant_PDFx($config, $data)
  {
    $border = '1px solid';
    $fontsize = '11';
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 30, "APPLICANT LEDGER - PROFILE", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);


    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 0, "PERSONAL DETAILS", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Full Name : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, '(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . ' ' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Applied Position : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->jobtitle) ? $data[0]->jobtitle : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Address : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->address) ? $data[0]->address : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Date Applied : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->appdate) ? $data[0]->appdate : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Type : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->type) ? $data[0]->type : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Status : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->jstatus) ? $data[0]->jstatus : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Birthday : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->bday) ? $data[0]->bday : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Gender : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->gender) ? $data[0]->gender : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Marital Status : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->status) ? $data[0]->status : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "No. of Children : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->child) ? $data[0]->child : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Birthplace : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->bplace) ? $data[0]->bplace : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Citizenship : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->citizenship) ? $data[0]->citizenship : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Religion : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->religion) ? $data[0]->religion : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Email Address : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Mobile No. : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->mobileno) ? $data[0]->mobileno : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Tel. No. : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->telno) ? $data[0]->telno : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Zipcode : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->zipcode) ? $data[0]->zipcode : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Alias : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->alias) ? $data[0]->alias : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 0, "CONTACTS", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Contact : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->contact1) ? $data[0]->contact1 : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Contact : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->contact2) ? $data[0]->contact2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Relation : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->relation1) ? $data[0]->relation1 : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Relation : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->relation2) ? $data[0]->relation2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Address : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->addr1) ? $data[0]->addr1 : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Address : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->addr2) ? $data[0]->addr2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Tel No. : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->homeno1) ? $data[0]->homeno1 : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Tel No. : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->homeno2) ? $data[0]->homeno2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Mobile No. : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->mobileno1) ? $data[0]->mobileno1 : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Mobile No. : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->mobileno2) ? $data[0]->mobileno2 : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 10, "Office No. : ", '', 'L', false, 0);
    PDF::MultiCell(300, 10, (isset($data[0]->officeno1) ? $data[0]->officeno1 : ''), '', 'L', false, 0);
    PDF::MultiCell(120, 10, "Office No. : ", '', 'L', false, 0);
    PDF::MultiCell(220, 10, (isset($data[0]->officeno2) ? $data[0]->officeno2 : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 20, "EDUCATIONAL HISTORY", '', 'L', false);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 10, "School", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Address", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Course", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "School Yr", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "Honor", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $qry = "select empid, line, school, address, course, sy, gpa, honor from aeducation where empid= " . $data[0]->empid . " order by line ";
    $dataeduc = $this->coreFunctions->opentable($qry);

    foreach ($dataeduc as $key => $data1) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(200, 10, (isset($data1->school) ? $data1->school : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(152, 10, (isset($data1->address) ? $data1->address : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(152, 10, (isset($data1->course) ? $data1->course : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, (isset($data1->sy) ? $data1->sy : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, (isset($data1->honor) ? $data1->honor : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 20, "EMPLOYMENT HISTORY", '', 'L', false);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 10, "Company", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Jobtitle", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Salary", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "Period", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "Reason of Leaving", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $qry = "select empid, line, company, jobtitle, period, address, salary, reason from aemployment where empid= " . $data[0]->empid . " order by line ";
    $dataemploy = $this->coreFunctions->opentable($qry);

    foreach ($dataemploy as $key => $data1) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(200, 10, (isset($data1->company) ? $data1->company : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(152, 10, (isset($data1->jobtitle) ? $data1->jobtitle : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(152, 10, (isset($data1->salary) ? $data1->salary : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, (isset($data1->period) ? $data1->period : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, (isset($data1->reason) ? $data1->reason : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 20, "REQUIREMENTS", '', 'L', false);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 10, "Requirements", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Date Submitted", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Submitted", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "Notes", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $qry = "select line, empid, reqs, date(submitdate) as submitdate, notes, issubmitted, pin, reqid from arequire where empid= " . $data[0]->empid . " order by line ";
    $datarequire = $this->coreFunctions->opentable($qry);

    foreach ($datarequire as $key => $data1) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(200, 10, (isset($data1->reqs) ? $data1->reqs : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(152, 10, (isset($data1->submitdate) ? $data1->submitdate : ''), '', 'L', 0, 0, '', '', true, 0, true, false);

      if ($data1->issubmitted == 0) {
        $srequire = 'NO';
      } else {
        $srequire = 'YES';
      }

      PDF::MultiCell(152, 10, (isset($srequire) ? $srequire : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, (isset($data1->notes) ? $data1->notes : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, '', '', 'L', 0, 1, '', '', true, 0, false, false);
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 20, "PRE-EMPLOYMENT TEST", '', 'L', false);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(200, 10, "Test", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Result", '', 'L', false, 0);
    PDF::MultiCell(152, 10, "Notes", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "", '', 'L', false, 0);
    PDF::MultiCell(128, 10, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $qry = "select line, empid, preemptest, result, notes, pin, emptestid from apreemploy where empid= " . $data[0]->empid . " order by line ";
    $datapreemploy = $this->coreFunctions->opentable($qry);

    foreach ($datapreemploy as $key => $data1) {

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(200, 10, (isset($data1->preemptest) ? $data1->preemptest : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(152, 10, (isset($data1->result) ? $data1->result : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(152, 10, (isset($data1->notes) ? $data1->notes : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, '', '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(128, 10, '', '', 'L', 0, 1, '', '', true, 0, false, false);
    }
    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $approved, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
