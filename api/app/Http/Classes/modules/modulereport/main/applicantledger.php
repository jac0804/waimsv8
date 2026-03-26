<?php

namespace App\Http\Classes\modules\modulereport\main;

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
    // $fields = ['radioprint','prepared','approved','received', 'print'];
    $fields = ['prepared', 'approved', 'received', 'print'];
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    $col1 = $this->fieldClass->create($fields);
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
    app.empid, app.empcode, app.emplast, app.empfirst, app.empmiddle, app.address, app.city, 
    app.country, app.zipcode, app.telno, app.mobileno, app.email,
    app.citizenship, app.religion, app.alias, date(app.bday) as bday, app.jobtitle, 
    app.jobcode, app.jobdesc, app.maidname, date(app.appdate) as appdate, app.remarks, app.type,
    app.jstatus, app.mapp, app.bplace, app.child, app.status, app.gender, 
    app.ishired, date(app.hired) as hired, app.idno, app.jobid, app.createby, app.center,
    app.viewby, app.editby, app.viewdate, app.editdate, app.createdate,
      concat(app.empfirst, ' ', app.empmiddle, ' ', app.emplast) as clientname,
      app.empcode as client,  con.contact1, con.relation1, con.addr1, con.homeno1, con.mobileno1, con.officeno1, con.ext1,
      con.notes1, con.contact2, con.relation2, con.addr2, con.homeno2, con.mobileno2, con.officeno2, con.ext2,con.notes2
      from app 
      LEFT JOIN acontacts AS con ON con.empid=app.empid
      where app.empid='$clientid'";

    return $this->coreFunctions->opentable($query);
  } //end fn


  public function reportplotting($config, $data)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_applicant_layout($config, $data);
    } else {
      $str = $this->rpt_applicant_PDF($config, $data);
    }
    return $str;
  }

  private function rpt_applicant_layout($config, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';
    $fontsize = 11;
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('APPLICANT LEDGER - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PERSONAL DETAILS', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Full Name : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Applied Position : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->jobtitle) ? $data[0]->jobtitle : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->address) ? $data[0]->address : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Date Applied : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->appdate) ? $data[0]->appdate : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Type : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->type) ? $data[0]->type : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Status : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->jstatus) ? $data[0]->jstatus : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Birthday : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->bday) ? $data[0]->bday : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Gender : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->gender) ? $data[0]->gender : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Marital Status : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->status) ? $data[0]->status : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('No. of Children : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->child) ? $data[0]->child : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Birthplace : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->bplace) ? $data[0]->bplace : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Citizenship : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->citizenship) ? $data[0]->citizenship : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Religion : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->religion) ? $data[0]->religion : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Email Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->email) ? $data[0]->email : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Mobile No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->mobileno) ? $data[0]->mobileno : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Tel. No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->telno) ? $data[0]->telno : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Zipcode : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->zipcode) ? $data[0]->zipcode : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Alias : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->alias) ? $data[0]->alias : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CONTACTS', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Contact : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->contact1) ? $data[0]->contact1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Contact : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->contact2) ? $data[0]->contact2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Relation : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->relation1) ? $data[0]->relation1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Relation : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->relation2) ? $data[0]->relation2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->addr1) ? $data[0]->addr1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->addr2) ? $data[0]->addr2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tel No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->homeno1) ? $data[0]->homeno1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Tel No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->homeno2) ? $data[0]->homeno2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Mobile No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->mobileno1) ? $data[0]->mobileno1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Mobile No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->mobileno2) ? $data[0]->mobileno2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Office No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->officeno1) ? $data[0]->officeno1 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Office No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->officeno2) ? $data[0]->officeno2 : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EDUCATIONAL HISTORY', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');

    $qry = "select empid, line, school, address, course, sy, gpa, honor from aeducation where empid= " . $data[0]->empid . " order by line ";
    $dataeduc = $this->coreFunctions->opentable($qry);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('School ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Address ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Course ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('School Yr ', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Honor ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col(
      '',
      '60',
      null,
      false,
      $border,
      '',
      'L',
      $font,
      $fontsize,
      '',
      '',
      '1px'
    );
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    foreach ($dataeduc as $key => $data1) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col((isset($data1->school) ? $data1->school : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->address) ? $data1->address : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->course) ? $data1->course : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->sy) ? $data1->sy : ''), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->honor) ? $data1->honor : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYMENT HISTORY', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');

    $qry = "select empid, line, company, jobtitle, period, address, salary, reason from aemployment where empid= " . $data[0]->empid . " order by line ";
    $dataemploy = $this->coreFunctions->opentable($qry);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Company ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Jobtitle ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Salary ', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Period ', '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Reason of Leaving ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    foreach ($dataemploy as $key => $data1) {
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col((isset($data1->company) ? $data1->company : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

      $str .= $this->reporter->col((isset($data1->jobtitle) ? $data1->jobtitle : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

      $str .= $this->reporter->col((isset($data1->salary) ? $data1->salary : ''), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->period) ? $data1->period : ''), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->reason) ? $data1->reason : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REQUIREMENTS', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');

    $qry = "select line, empid, reqs, date(submitdate) as submitdate, notes, issubmitted, pin, reqid from arequire where empid= " . $data[0]->empid . " order by line ";
    $datarequire = $this->coreFunctions->opentable($qry);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Requirements ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Date Submitted ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Submitted ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

    $str .= $this->reporter->col('Notes ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    foreach ($datarequire as $key => $data1) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col((isset($data1->reqs) ? $data1->reqs : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->submitdate) ? $data1->submitdate : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

      if ($data1->issubmitted == 0) {
        $srequire = 'NO';
      } else {
        $srequire = 'YES';
      }
      $str .= $this->reporter->col((isset($srequire) ? $srequire : ''), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

      $str .= $this->reporter->col((isset($data1->notes) ? $data1->notes : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PRE-EMPLOYMENT TEST', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');

    $qry = "select line, empid, preemptest, result, notes, pin, emptestid from apreemploy where empid= " . $data[0]->empid . " order by line ";
    $datapreemploy = $this->coreFunctions->opentable($qry);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Test ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Result ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Notes ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    foreach ($datapreemploy as $key => $data1) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col((isset($data1->preemptest) ? $data1->preemptest : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->result) ? $data1->result : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->notes) ? $data1->notes : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->printline();

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

  private function rpt_applicant_PDF($config, $data)
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

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

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
      $maxrow = 1;
      $school = $data1->school;
      $address = $data1->address;
      $course = $data1->course;
      $sy = $data1->sy;
      $honor = $data1->honor;

      $arr_school = $this->reporter->fixcolumn([$data1->school], '35', 0);
      $arr_address = $this->reporter->fixcolumn([$data1->address], '25', 0);
      $arr_course = $this->reporter->fixcolumn([$data1->course], '25', 0);
      $arr_sy = $this->reporter->fixcolumn([$data1->sy], '16', 0);
      $arr_honor = $this->reporter->fixcolumn([$data1->honor], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_school, $arr_address, $arr_course, $arr_sy, $arr_honor]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 10, (isset($arr_school[$r]) ? $arr_school[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(152, 10, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(152, 10, (isset($arr_course[$r]) ? $arr_course[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, (isset($arr_sy[$r]) ? $arr_sy[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, (isset($arr_honor[$r]) ? $arr_honor[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
      }
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
      $maxrow = 1;
      $arr_company = $this->reporter->fixcolumn([$data1->company], '35', 0);
      $arr_jobtitle = $this->reporter->fixcolumn([$data1->jobtitle], '25', 0);
      $arr_salary = $this->reporter->fixcolumn([$data1->salary], '16', 0);
      $arr_period = $this->reporter->fixcolumn([$data1->period], '16', 0);
      $arr_reason = $this->reporter->fixcolumn([$data1->reason], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_company, $arr_jobtitle, $arr_salary, $arr_period, $arr_reason]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 10, (isset($arr_company[$r]) ? $arr_company[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(152, 10, (isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(152, 10, (isset($arr_salary[$r]) ? $arr_salary[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, (isset($arr_period[$r]) ? $arr_period[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, (isset($arr_reason[$r]) ? $arr_reason[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
      }
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
      $maxrow = 1;
      $arr_reqs = $this->reporter->fixcolumn([$data1->reqs], '35', 0);
      $arr_submitdate = $this->reporter->fixcolumn([$data1->submitdate], '16', 0);
      $arr_notes = $this->reporter->fixcolumn([$data1->notes], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_reqs, $arr_submitdate, $arr_notes]);

      if ($data1->issubmitted == 0) {
        $srequire = 'NO';
      } else {
        $srequire = 'YES';
      }
      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 10, (isset($arr_reqs[$r]) ? $arr_reqs[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(152, 10, (isset($arr_submitdate[$r]) ? $arr_submitdate[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);

        PDF::MultiCell(152, 10, (isset($srequire) ? $srequire : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, '', '', 'L', 0, 1, '', '', true, 0, false, false);
      }
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
      $maxrow = 1;
      $arr_preemptest = $this->reporter->fixcolumn([$data1->preemptest], '25', 0);
      $arr_result = $this->reporter->fixcolumn([$data1->result], '16', 0);
      $arr_notes = $this->reporter->fixcolumn([$data1->notes], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_preemptest, $arr_result, $arr_notes]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(200, 10, (isset($arr_preemptest[$r]) ? $arr_preemptest[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(152, 10, (isset($arr_result[$r]) ? $arr_result[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(152, 10, (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(128, 10, '', '', 'L', 0, 1, '', '', true, 0, false, false);
      }
    }
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
}
