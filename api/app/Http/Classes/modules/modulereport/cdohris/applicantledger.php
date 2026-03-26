<?php

namespace App\Http\Classes\modules\modulereport\cdohris;

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

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'radioreporttype', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Pre-Employment Form', 'value' => 'default', 'color' => 'red'],
      ['label' => 'Background Investigation Form', 'value' => 'bifa', 'color' => 'red']
    ]);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'PDFM' as print,
      'default' as reporttype,
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

    $query = "select 
    app.empid, app.empcode, app.emplast, app.empfirst, app.empmiddle, app.address, app.city, 
    app.country, app.zipcode, app.telno, app.mobileno, app.email,
    app.citizenship, app.religion, app.alias, date(app.bday) as bday, year(now())-year(app.bday) as age,
    app.jobtitle, 
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
      if ($config['params']['dataparams']['reporttype'] == 'bifa') {
        $str = $this->rpt_background_applicant_PDF($config, $data);
      } else {
        $str = $this->rpt_applicant_PDF($config, $data);
        // $str = $this->rpt_applicant_layout($config, $data);
      }
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
    PDF::SetMargins(40, 70);

    // PDF::Image('public/images/cdohris/cdohris_logo.png', '', '', 310, 80);
    PDF::Image(public_path('images/cdohris/cdohris_logo.png'), '10', '40', 640, 60);

    PDF::MultiCell(70, 70, "", ['TBLR' => ['dash' => 2]], 'C', false, 1, '660', '55', true, 0, true);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(70, 20, "Paste a recent", '', 'L', false, 1, '665', '80', true, 0, true);

    PDF::MultiCell(70, 20, "1x1 ID picture with your", '', 'L', false, 1, '665', '100', true, 0, true);

    // $pdf->SetLineStyle(array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
    // $pdf->SetLineStyle(array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => '2,2', 'color' => array(0, 0, 0)));



    PDF::SetFont($fontbold, '', 23);
    PDF::MultiCell(720, 10, "", '', 'L', false);

    PDF::MultiCell(720, 30, "APPLICATION FOR EMPLOYMENT", '', 'C', false, 1, '10', '120', true, 0, true);
    // PDF::MultiCell(720, 30, "APPLICATION FOR EMPLOYMENT", '', 'C', false);



    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(720, 30, "Please type or print all entries clearly. Do not leave blanks. Indicate N / A if not applicable.", '', 'C', false, 1, '10', '170', true, 0, true);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 10, "<u>Attach</u>: resume, photocopy of transcript/grades, photocopy of proof of eligibility/PRC license/Bar rating, ID picture", '', 'C', false, 1, '', '', true, 0, true);


    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(80, 20, "Respondent to:", '', 'L', false, 1, '10', '245', true, 0, true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(15, 20, "", ['TBLR' => ['dash' => 0]], 'C', false, 1, '90', '245', true, 0, true);
    PDF::MultiCell(100, 20, "Walk-In", '', 'L', false, 1, '110', '245', true, 0, true);


    PDF::MultiCell(15, 20, "", 'TBLR', 'C', false, 1, '90', '265', true, 0, true);
    PDF::MultiCell(100, 20, "Email", '', 'L', false, 1, '110', '265', true, 0, true);


    PDF::MultiCell(15, 20, "", 'TBLR', 'C', false, 1, '90', '285', true, 0, true);
    PDF::MultiCell(100, 20, "Others:", '', 'L', false, 1, '110', '285', true, 0, true);
    PDF::MultiCell(100, 20, "", 'B', 'L', false, 1, '150', '285', true, 0, true);



    PDF::SetFont($font, '', 10);
    PDF::MultiCell(15, 20, "", 'TBLR', 'C', false, 1, '250', '245', true, 0, true);
    PDF::MultiCell(150, 20, "Referral by MGC Employee", '', 'L', false, 1, '270', '245', true, 0, true);

    PDF::MultiCell(60, 20, "Name:", '', 'R', false, 1, '265', '265', true, 0, true);
    PDF::MultiCell(175, 20, "", 'B', 'L', false, 1, '325', '265', true, 0, true);

    PDF::MultiCell(60, 20, "Dept/Br:", '', 'R', false, 1, '265', '285', true, 0, true);
    PDF::MultiCell(85, 20, "", 'B', 'L', false, 1, '325', '285', true, 0, true);
    PDF::MultiCell(50, 20, "Local:", '', 'R', false, 1, '390', '285', true, 0, true);
    PDF::MultiCell(60, 20, "", 'B', 'L', false, 1, '440', '285', true, 0, true);


    PDF::SetFont($font, '', 10);

    PDF::MultiCell(60, 20, "Nickname:", '', 'L', false, 1, '540', '245', true, 0, true);
    PDF::MultiCell(120, 20, "", 'B', 'L', false, 1, '600', '245', true, 0, true);

    PDF::SetFont($fontbold, '', 10);

    PDF::MultiCell(100, 20, "Expected Salary:", '', 'L', false, 1, '540', '265', true, 0, true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(150, 20, "(Do not leave blank)", '', 'L', false, 1, '540', '285', true, 0, true);
    PDF::MultiCell(80, 20, "", 'B', 'R', false, 1, '640', '285', true, 0, true);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 25, "", '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(180, 40, "", 'TBL', 'L', false, 0);
    PDF::MultiCell(180, 40, "", 'TBL', 'L', false, 0);

    PDF::MultiCell(180, 40, "", 'TBL', 'L', false, 0);
    PDF::MultiCell(180, 40, "", 'TBLR', 'L', false);

    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(150, 10, "Preferred Assignment", '', 'L', false, 1, '40', '330', true, 0, true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 10, "(Specify Head Office Dept or Branch)", '', 'L', false, 1, '40', '355', true, 0, true);

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(40, 10, "1st choice:", '', 'L', false, 1, '230', '335', true, 0, true);
    PDF::MultiCell(40, 10, "2nd choice:", '', 'L', false, 1, '410', '335', true, 0, true);
    PDF::MultiCell(40, 10, "3rd choice:", '', 'L', false, 1, '590', '335', true, 0, true);


    PDF::MultiCell(180, 40, "", '', 'L', false);

    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(720, 20, "PERSONAL DATA", 'TBLR', 'L', true);
    PDF::SetFillColor(255, 255, 255);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(190, 10, "Last Name: ", 'TL', 'L', false, 0);
    PDF::MultiCell(220, 10, "First Name: ", 'TL', 'L', false, 0);

    PDF::MultiCell(130, 10, "Middle Name: ", 'TL', 'L', false, 0);
    PDF::MultiCell(180, 10, "Maiden Name: (if married)", 'TLR', 'L', false);


    if (strlen($data[0]->emplast) <= 20) {
      PDF::SetFont($fontbold, '', 13);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }

    PDF::MultiCell(190, 30, " " . $data[0]->emplast, 'BL', 'L', false, 0);



    if (strlen($data[0]->empfirst) <= 25) {
      PDF::SetFont($fontbold, '', 13);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }

    PDF::MultiCell(220, 30,  " " . $data[0]->empfirst, 'BL', 'L', false, 0);


    if (strlen($data[0]->empmiddle) <= 15) {
      PDF::SetFont($fontbold, '', 13);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }


    PDF::MultiCell(130, 30,  " " . $data[0]->empmiddle, 'BL', 'L', false, 0);



    if (strlen($data[0]->maidname) <= 18) {
      PDF::SetFont($fontbold, '', 13);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }
    PDF::MultiCell(180, 30,  " " . $data[0]->maidname, 'BLR', 'L', false);


    PDF::SetFont($font, '', 8);

    /////
    PDF::MultiCell(50, 10, "Age: ", 'TL', 'L', false, 0);
    PDF::MultiCell(140, 10, "Date of Birth(MM-DD-YY): ", 'TL', 'L', false, 0);

    PDF::MultiCell(160, 10, "Place of Birth: ", 'TL', 'L', false, 0);
    PDF::MultiCell(90, 10, "Citizenship: ", 'TL', 'L', false, 0);
    PDF::MultiCell(100, 10, "Civil Status: ", 'TL', 'L', false, 0);

    PDF::MultiCell(110, 10, "Religion: ", 'TL', 'L', false, 0);
    PDF::MultiCell(70, 10, "Sex: ", 'TLR', 'L', false);



    // if(strlen($data[0]->age)<=18){
    PDF::SetFont($fontbold, '', 13);
    // }else{
    //   PDF::SetFont($fontbold, '', 12);
    // }
    PDF::MultiCell(50, 30, " " . $data[0]->age, 'BL', 'L', false, 0);

    PDF::MultiCell(140, 30, " " . date("m-d-y", strtotime($data[0]->bday)), 'BL', 'L', false, 0);

    PDF::MultiCell(160, 30, " " . $data[0]->bplace, 'BL', 'L', false, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(90, 30, " " . $data[0]->citizenship, 'BL', 'L', false, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(100, 30, " " . $data[0]->status, 'BL', 'L', false, 0);

    PDF::MultiCell(110, 30, " " . $data[0]->religion, 'BL', 'L', false, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(70, 30, " " . $data[0]->gender, 'BLR', 'L', false);




    PDF::SetFont($font, '', 8);
    PDF::MultiCell(540, 10, "Present Address: ", 'TL', 'L', false, 0);
    PDF::MultiCell(180, 10, "Telephone/Fax: ", 'TLR', 'L', false);

    if (strlen($data[0]->address) <= 100) {
      PDF::SetFont($fontbold, '', 13);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }

    PDF::MultiCell(540, 30, " " . $data[0]->address, 'BL', 'L', false, 0);

    if (strlen($data[0]->address) <= 20) {
      PDF::SetFont($fontbold, '', 13);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }
    PDF::MultiCell(180, 30, " " . $data[0]->telno, 'BLR', 'L', false);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(540, 10, "Provincial Address", 'TL', 'L', false, 0);
    PDF::MultiCell(180, 10, "Telephone/Fax", 'TLR', 'L', false);


    PDF::MultiCell(540, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BLR', 'L', false);



    PDF::SetFont($font, '', 8);
    PDF::MultiCell(540, 10, "Email Address: ", 'TL', 'L', false, 0);
    PDF::MultiCell(180, 10, "Telephone/Fax: ", 'TLR', 'L', false);


    if (strlen($data[0]->email) <= 100) {
      PDF::SetFont($fontbold, '', 13);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }
    PDF::MultiCell(540, 30, " " . $data[0]->email, 'BL', 'L', false, 0);

    if (strlen($data[0]->mobileno) <= 20) {
      PDF::SetFont($fontbold, '', 17);
    } else {
      PDF::SetFont($fontbold, '', 12);
    }
    PDF::MultiCell(180, 30, " " . $data[0]->mobileno, 'BLR', 'L', false);



    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(720, 20, "EDUCATIONAL BACKGROUND", 'TBLR', 'L', true);
    PDF::SetFillColor(255, 255, 255);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(114, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(184, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(184, 10, "Course / Degree / Major", 'TL', 'L', false, 0);
    PDF::MultiCell(84, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(154, 10, "", 'TLR', 'L', false);

    PDF::MultiCell(114, 10, "Level", 'L', 'L', false, 0);
    PDF::MultiCell(184, 10, "Name of School / Address", 'L', 'L', false, 0);
    PDF::MultiCell(184, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(84, 10, "Year Graduated", 'L', 'L', false, 0);
    PDF::MultiCell(154, 10, "Honors / Awards", 'LR', 'L', false);

    PDF::MultiCell(114, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(184, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(184, 10, "(if undergraduate, indicate number of units)", 'L', 'L', false, 0);
    PDF::MultiCell(84, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(154, 10, "", 'LR', 'L', false);

    PDF::MultiCell(114, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(184, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(184, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(84, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(154, 10, "", 'BLR', 'L', false);


    $qry = "select line, school, address, course, sy, gpa, honor
    from aeducation where empid= " . $data[0]->empid . " order by line ";
    $dataeducation = $this->coreFunctions->opentable($qry);

    $edcount = 0;

    if (empty($dataeducation)) {

      PDF::MultiCell(114, 30, "Post Graduate", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(84, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(154, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(114, 30, "College", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(84, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(154, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(114, 30, "High School", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(84, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(154, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(114, 30, "Elementary", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(84, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(154, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(114, 30, "Others", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(84, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(154, 30, "", 'BLR', 'L', false);
    } else {
      foreach ($dataeducation as $key => $data1) {
        $maxrow = 1;
        $arr_school = $this->reporter->fixcolumn([$data1->school], '35', 0);
        $arr_address = $this->reporter->fixcolumn([$data1->address], '16', 0);
        $arr_course = $this->reporter->fixcolumn([$data1->course], '16', 0);
        $arr_sy = $this->reporter->fixcolumn([$data1->sy], '16', 0);
        $arr_gpa = $this->reporter->fixcolumn([$data1->gpa], '16', 0);
        $arr_honor = $this->reporter->fixcolumn([$data1->honor], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_school, $arr_address, $arr_course, $arr_sy, $arr_gpa, $arr_honor]);

        for ($r = 0; $r < $maxrow; $r++) {
          $edcount++;
          PDF::SetFont($fontbold, '', 13);
          PDF::MultiCell(114, 30, '', 'BL', 'L', false, 0);
          PDF::MultiCell(184, 30, " " . (isset($arr_school[$r]) ? $arr_school[$r] : ''), 'BL', 'L', false, 0);
          PDF::MultiCell(184, 30, " " . (isset($arr_course[$r]) ? $arr_course[$r] : ''), 'BL', 'L', false, 0);
          PDF::MultiCell(84, 30, " " . (isset($arr_sy[$r]) ? $arr_sy[$r] : ''), 'BL', 'L', false, 0);
          PDF::MultiCell(154, 30, " " . (isset($arr_gpa[$r]) ? $arr_gpa[$r] : '') . ' / ' . (isset($arr_honor[$r]) ? $arr_honor[$r] : ''), 'BLR', 'L', false);
        }
      }

      while ($edcount < 5) {

        PDF::MultiCell(114, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(184, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(84, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(154, 30, "", 'BLR', 'L', false);
        $edcount++;
      }
    }

    ///


    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(720, 20, "GOVERNMENT AND PROFESSIONAL EXAMINATION/S PASSED", 'TBLR', 'L', true);
    PDF::SetFillColor(255, 255, 255);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(260, 20, "Title of Examination", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 20, "Date of Examination (MM-DD-YY)", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 20, "Place of Examination", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 20, "Rating", 'BLR', 'L', false);

    PDF::MultiCell(260, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 30, "", 'BLR', 'L', false);


    PDF::MultiCell(260, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 30, "", 'BLR', 'L', false);


    PDF::MultiCell(260, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(200, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 30, "", 'BLR', 'L', false);



    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 70);

    // PDF::MultiCell(720, 20, "", '', 'L', true);



    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(180, 20, "EMPLOYMENT RECORD ", 'TBL', 'L', true, 0, 40);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(540, 20, "(start from most recent. Indicate history of employment since 16th birthday. Use additional sheets if necessary.)", 'TBR', 'C', true);
    PDF::SetFillColor(255, 255, 255);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(90, 10, "Inclusive Dates", 'TL', 'L', false, 0);
    PDF::MultiCell(90, 10, "(MM-DD-YY)", 'T', 'L', false, 0);
    PDF::MultiCell(110, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(70, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(180, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(90, 10, "Gross Monthly", 'TL', 'L', false, 0);
    PDF::MultiCell(90, 10, "Reason for", 'TLR', 'L', false);


    PDF::MultiCell(90, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "", '', 'L', false, 0);
    PDF::MultiCell(110, 10, "Position", 'L', 'L', false, 0);
    PDF::MultiCell(70, 10, "Status of", 'L', 'L', false, 0);
    PDF::MultiCell(180, 10, "Employer / Location", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "Salary", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "Leaving", 'LR', 'L', false);


    PDF::MultiCell(90, 10, "From", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "To", 'L', 'L', false, 0);
    PDF::MultiCell(110, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 10, "Employment", 'L', 'L', false, 0);
    PDF::MultiCell(180, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "", 'LR', 'L', false);


    PDF::MultiCell(90, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(110, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(70, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 10, "", 'BLR', 'L', false);

    $qry = "select line,
            company,
            jobtitle,
            period,
            address,
            salary,
            reason
      from aemployment where empid= " . $data[0]->empid . " order by line ";
    $dataemployment = $this->coreFunctions->opentable($qry);

    $empcount = 0;
    if (empty($dataemployment)) {



      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(110, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(70, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(110, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(70, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(110, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(70, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(110, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(70, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BLR', 'L', false);

      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(110, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(70, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
      PDF::MultiCell(90, 30, "", 'BLR', 'L', false);
    } else {
      foreach ($dataemployment as $key => $data1) {
        $maxrow = 1;

        $arr_company = $this->reporter->fixcolumn([$data1->company], '35', 0);
        $arr_jobtitle = $this->reporter->fixcolumn([$data1->jobtitle], '16', 0);
        $arr_period = $this->reporter->fixcolumn([$data1->period], '16', 0);
        $arr_address = $this->reporter->fixcolumn([$data1->address], '16', 0);
        $arr_salary = $this->reporter->fixcolumn([$data1->salary], '16', 0);
        $arr_reason = $this->reporter->fixcolumn([$data1->reason], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_company, $arr_jobtitle, $arr_period, $arr_address, $arr_salary, $arr_reason]);

        for ($r = 0; $r < $maxrow; $r++) {
          // PDF::SetFont($font, '', 8);
          PDF::SetFont($fontbold, '', 13);
          $empcount++;
          PDF::MultiCell(90, 30, '', 'BL', 'L', false, 0);
          PDF::MultiCell(90, 30, '', 'BL', 'L', false, 0);
          PDF::MultiCell(110, 30, " " . (isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : ''), 'BL', 'L', false, 0);
          PDF::MultiCell(70, 30, '', 'BL', 'L', false, 0);
          PDF::MultiCell(180, 30, " " . (isset($arr_company[$r]) ? $arr_company[$r] : '') . ' / ' . (isset($arr_address[$r]) ? $arr_address[$r] : ''), 'BL', 'L', false, 0);
          PDF::MultiCell(90, 30, " " . (isset($arr_salary[$r]) ? $arr_salary[$r] : ''), 'BL', 'L', false, 0);
          PDF::MultiCell(90, 30, " " . (isset($arr_reason[$r]) ? $arr_reason[$r] : ''), 'BLR', 'L', false);
        }
      }

      while ($empcount < 5) {

        PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(110, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(70, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
        PDF::MultiCell(90, 30, "", 'BLR', 'L', false);
        $empcount++;
      }
    }

    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(270, 20, "SEMINARS / TRAINING PROGRAMS ATTENDED ", 'TBL', 'L', true, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(450, 20, "(Start from most recent. Use additional sheets if necessary.)", 'TBR', 'L', true);
    PDF::SetFillColor(255, 255, 255);

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(90, 10, "Inclusive Dates", 'TL', 'L', false, 0);
    PDF::MultiCell(90, 10, "(MM-DD-YY)", 'T', 'L', false, 0);
    PDF::MultiCell(180, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(120, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(60, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(180, 10, "", 'TLR', 'L', false);

    PDF::MultiCell(90, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "", '', 'L', false, 0);
    PDF::MultiCell(180, 10, "Title of Course", 'L', 'L', false, 0);
    PDF::MultiCell(120, 10, "Venue", 'L', 'L', false, 0);
    PDF::MultiCell(60, 10, "Number of", 'L', 'L', false, 0);
    PDF::MultiCell(180, 10, "Organized / Sponsored by", 'LR', 'L', false);

    PDF::MultiCell(90, 10, "From", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 10, "To", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 10, "Hours", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 10, "", 'BLR', 'L', false);

    ////
    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BLR', 'L', false);


    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BLR', 'L', false);


    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BLR', 'L', false);


    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BLR', 'L', false);


    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BLR', 'L', false);




    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 20, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "", 'BLR', 'L', false);



    PDF::MultiCell(90, 40, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 40, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 40, "", 'BL', 'L', false, 0);
    PDF::MultiCell(120, 40, "", 'BL', 'L', false, 0);
    PDF::MultiCell(60, 40, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 40, "", 'BLR', 'L', false);



    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 20, "FAMILY BACKGROUND", 'TBL', 'L', true, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(570, 20, "(Use additional sheets if necessary.)", 'TBR', 'L', true);
    PDF::SetFillColor(255, 255, 255);


    PDF::SetFont($font, '', 8);
    PDF::MultiCell(315, 40, "Name of Spouse (If Married)", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 40, "Age", 'TL', 'L', false, 0);
    PDF::MultiCell(360, 40, "Address of Spouse", 'TLR', 'L', false);


    PDF::MultiCell(180, 40, "Date of Marriage (MM-DD-YY)", 'TL', 'L', false, 0);
    PDF::MultiCell(180, 40, "Place of Marriage", 'TL', 'L', false, 0);
    PDF::MultiCell(135, 40, "Occupation of Spouse", 'TL', 'L', false, 0);
    PDF::MultiCell(225, 40, "Spouse's Employer / Address", 'TLR', 'L', false);


    PDF::MultiCell(315, 20, "Name of Parents and In-Laws", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 20, "Age", 'TL', 'L', false, 0);
    PDF::MultiCell(225, 20, "Address", 'TL', 'L', false, 0);
    PDF::MultiCell(135, 20, "Occupation / Employer", 'TLR', 'L', false);


    PDF::MultiCell(315, 30, "Father", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 30, "", 'TL', 'L', false, 0);
    PDF::MultiCell(225, 30, "", 'TL', 'L', false, 0);
    PDF::MultiCell(135, 30, "", 'TLR', 'L', false);

    PDF::MultiCell(315, 30, "Mother", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 30, "", 'TL', 'L', false, 0);
    PDF::MultiCell(225, 30, "", 'TL', 'L', false, 0);
    PDF::MultiCell(135, 30, "", 'TLR', 'L', false);

    PDF::MultiCell(315, 30, "Father-In-Law", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 30, "", 'TL', 'L', false, 0);
    PDF::MultiCell(225, 30, "", 'TL', 'L', false, 0);
    PDF::MultiCell(135, 30, "", 'TLR', 'L', false);

    PDF::MultiCell(315, 30, "Mother-In-Law", 'TBL', 'L', false, 0);
    PDF::MultiCell(45, 30, "", 'TBL', 'L', false, 0);
    PDF::MultiCell(225, 30, "", 'TBL', 'L', false, 0);
    PDF::MultiCell(135, 30, "", 'TBLR', 'L', false);


    PDF::MultiCell(315, 20, "Name of Dependents", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 20, "Age", 'TL', 'L', false, 0);
    PDF::MultiCell(225, 20, "Relationship", 'TL', 'L', false, 0);
    PDF::MultiCell(135, 20, "Date of Birth", 'TLR', 'L', false);

    $qry = "select line, name, relation, date(bday) as bday, year(now())-year(bday) as age
    from adependents where empid= " . $data[0]->empid . " order by line ";
    $datadependents = $this->coreFunctions->opentable($qry);

    $depcount = 0;
    if (empty($datadependents)) {

      PDF::MultiCell(315, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(45, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(225, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(135, 30, "", 'TBLR', 'L', false);


      PDF::MultiCell(315, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(45, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(225, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(135, 30, "", 'TBLR', 'L', false);


      PDF::MultiCell(315, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(45, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(225, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(135, 30, "", 'TBLR', 'L', false);

      PDF::MultiCell(315, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(45, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(225, 30, "", 'TBL', 'L', false, 0);
      PDF::MultiCell(135, 30, "", 'TBLR', 'L', false);
    } else {
      foreach ($datadependents as $key => $data1) {
        $maxrow = 1;
        $arr_name = $this->reporter->fixcolumn([$data1->name], '35', 0);
        $arr_age = $this->reporter->fixcolumn([$data1->age], '16', 0);
        $arr_relation = $this->reporter->fixcolumn([$data1->relation], '16', 0);
        $arr_bday = $this->reporter->fixcolumn([$data1->bday], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_name, $arr_age, $arr_relation, $arr_bday]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($fontbold, '', 13);
          $depcount++;


          PDF::MultiCell(315, 30, " " . (isset($arr_name[$r]) ? $arr_name[$r] : ''), 'TL', 'L', false, 0);
          PDF::MultiCell(45, 30, " " . (isset($arr_age[$r]) ? $arr_age[$r] : ''), 'TL', 'L', false, 0);
          PDF::MultiCell(225, 30, " " . (isset($arr_relation[$r]) ? $arr_relation[$r] : ''), 'TL', 'L', false, 0);
          PDF::MultiCell(135, 30, " " . (isset($arr_bday[$r]) ? $arr_bday[$r] : ''), 'TLR', 'L', false);
        }
      }
      while ($depcount < 4) {

        PDF::MultiCell(315, 30, "", 'TBL', 'L', false, 0);
        PDF::MultiCell(45, 30, "", 'TBL', 'L', false, 0);
        PDF::MultiCell(225, 30, "", 'TBL', 'L', false, 0);
        PDF::MultiCell(135, 30, "", 'TBLR', 'L', false);
        $depcount++;
      }
    }

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(315, 20, "Name of Brothers and Sisters", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 20, "Age", 'TL', 'L', false, 0);
    PDF::MultiCell(250, 20, "Occupation/Employer", 'TL', 'L', false, 0);
    PDF::MultiCell(110, 20, "Civil Status", 'TLR', 'L', false);

    PDF::MultiCell(315, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(250, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(110, 20, "", 'TLR', 'L', false);

    PDF::MultiCell(315, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(250, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(110, 20, "", 'TLR', 'L', false);

    PDF::MultiCell(315, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(250, 20, "", 'TL', 'L', false, 0);
    PDF::MultiCell(110, 20, "", 'TLR', 'L', false);

    PDF::MultiCell(315, 20, "", 'TBL', 'L', false, 0);
    PDF::MultiCell(45, 20, "", 'TBL', 'L', false, 0);
    PDF::MultiCell(250, 20, "", 'TBL', 'L', false, 0);
    PDF::MultiCell(110, 20, "", 'TBLR', 'L', false);



    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 70);

    // PDF::MultiCell(720, 20, "", '', 'L', true);



    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(720, 20, "RESIDENCE OF MORE THAN SIX (6) MONTHS DURATION FROM 15TH BIRTHDAY", 'TBL', 'L', true, 1, 40);
    PDF::SetFillColor(255, 255, 255);


    PDF::MultiCell(90, 10, "Inclusive Dates", 'TL', 'L', false, 0);
    PDF::MultiCell(90, 10, "(MM-DD-YY)", 'T', 'L', false, 0);
    PDF::MultiCell(540, 10, "", 'TLR', 'L', false);


    PDF::MultiCell(90, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "", '', 'L', false, 0);
    PDF::MultiCell(540, 10, "Complete Address", 'LR', 'L', false);



    PDF::MultiCell(90, 10, "From", 'L', 'L', false, 0);
    PDF::MultiCell(90, 10, "To", 'L', 'L', false, 0);
    PDF::MultiCell(540, 10, "", 'LR', 'L', false);


    PDF::MultiCell(90, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(540, 10, "", 'BLR', 'L', false);


    PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(540, 30, "", 'BLR', 'L', false);


    PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(90, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(540, 30, "", 'BLR', 'L', false);

    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 20, "ADDITIONAL INFORMATION", 'TBL', 'L', true, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(570, 20, "(Please mark your responses. Use additional sheets if necessary.)", 'TBR', 'L', true);
    PDF::SetFillColor(255, 255, 255);



    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'TLR', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, "YES", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "NO", 'L', 'C', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(630, 10, "1. Have you applied to any other company than Motormate Group of Companies", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'BLR', 'L', false);

    ////


    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(630, 10, "2. Have you ever been found guilty or been penalized for any offense or violation involving moral turpitude or carrying the penalty of", 'TLR', 'L', false);


    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(630, 10, "disqualification to hold public office? If yes, please check nature of offense, and specify name of court or administrative board and", 'LR', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, "YES", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "NO", 'L', 'C', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(630, 10, "disposition of case:", 'LR', 'L', false);


    PDF::MultiCell(15, 20, "", 'TBLR', 'C', false, 1, '170', '305', true, 0, true);
    PDF::MultiCell(100, 20, "Administrative", '', 'L', false, 1, '190', '305', true, 0, true);


    PDF::MultiCell(15, 20, "", 'TBLR', 'C', false, 1, '170', '325', true, 0, true);
    PDF::MultiCell(100, 20, "Civil", '', 'L', false, 1, '190', '325', true, 0, true);

    PDF::MultiCell(100, 10, "Provide Details:", '', 'L', false, 1, '320', '325', true, 0, true);


    PDF::MultiCell(15, 20, "", 'TBLR', 'C', false, 1, '170', '345', true, 0, true);
    PDF::MultiCell(100, 20, "Criminal", '', 'L', false, 1, '190', '345', true, 0, true);

    PDF::MultiCell(45, 75, "", 'BL', 'L', false, 0, '40', '290', true, 0, true);
    PDF::MultiCell(45, 75, "", 'BL', 'L', false, 0);
    PDF::MultiCell(630, 75, "", 'BLR', 'L', false);


    ////
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'TLR', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, "YES", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "NO", 'L', 'C', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(630, 10, "3. Have you ever been suspended, discharged or forced to resign from any of your previous positions? If yes, provide details:", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'BLR', 'L', false);

    ////


    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'TLR', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, "YES", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "NO", 'L', 'C', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(630, 10, "4. Are you willing to be reassigned to any Branches of Motormate Group of Companies? If No, Why?", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'BLR', 'L', false);


    ////


    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(630, 10, "5. Have you applied to any of our Motormate Group Affiliates?", 'TLR', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, "YES", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "NO", 'L', 'C', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(210, 10, "Date:", 'L', 'C', false, 0);
    PDF::MultiCell(210, 10, "Venue:", '', 'C', false, 0);
    PDF::MultiCell(210, 10, "Status:", 'R', 'C', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);

    PDF::MultiCell(630, 10, "", 'LR', 'L', false);


    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'BLR', 'L', false);

    ////


    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'TLR', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, "YES", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "NO", 'L', 'C', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(630, 10, "6. Are you willing to Accept contractual employment?", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'BL', 'L', false, 0);
    PDF::MultiCell(630, 10, "", 'BLR', 'L', false);

    ////


    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'TL', 'L', false, 0);
    PDF::MultiCell(630, 10, "7. Do you have any relative working with Motormate Group of Companies? If yes, provide details:", 'TLR', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, "YES", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "NO", 'L', 'C', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(630, 10, "", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(30, 10, "", 'L', 'C', false, 0);
    PDF::MultiCell(320, 10, "Name/s of:", '', 'L', false, 0);
    PDF::MultiCell(280, 10, "Relationship:", 'R', 'L', false);

    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'L', false, 0);

    PDF::MultiCell(30, 10, "", 'L', 'C', false, 0);
    PDF::MultiCell(320, 10, "Relative/s:", '', 'L', false, 0);
    PDF::MultiCell(280, 10, "", 'R', 'C', false);


    PDF::MultiCell(45, 10, "", 'L', 'C', false, 0);
    PDF::MultiCell(45, 10, "", 'L', 'C', false, 0);
    PDF::MultiCell(630, 10, "", 'LR', 'L', false);

    PDF::MultiCell(45, 10, "", 'BL', 'C', false, 0);
    PDF::MultiCell(45, 10, "", 'BL', 'C', false, 0);
    PDF::MultiCell(630, 10, "", 'BLR', 'L', false);

    ////



    //


    PDF::SetFillColor(224, 224, 224);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 20, "REFERENCES", 'TBL', 'L', true, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(570, 20, "(Do not include relatives.)", 'TBR', 'L', true);
    PDF::SetFillColor(255, 255, 255);


    PDF::MultiCell(180, 20, "Name", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "Occupation", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "Address", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 20, "Contact Details (Cellphone / Email)", 'BLR', 'L', false);

    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BLR', 'L', false);

    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BLR', 'L', false);

    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BL', 'L', false, 0);
    PDF::MultiCell(180, 30, "", 'BLR', 'L', false);

    PDF::MultiCell(720, 10, "", 'TBLR', 'L', false);


    PDF::SetFont($font, '', 11);
    PDF::MultiCell(510, 10, "", 'TL', 'C', false, 0);
    PDF::MultiCell(210, 10, "", 'TR', 'C', false);

    PDF::MultiCell(30, 10, "", 'L', 'R', false, 0);
    PDF::MultiCell(480, 10, "I hereby certify that the statements made by me are true, complete,", '', 'L', false, 0);
    PDF::MultiCell(210, 10, "", 'R', 'C', false);

    PDF::MultiCell(420, 10, "accurate, and correct to the best of my knowledge and belief. Any false", 'L', 'L', false, 0);
    PDF::MultiCell(300, 10, "", 'BR', 'C', false);

    PDF::MultiCell(420, 10, "information contained herein may serve as grounds for cancellation of my", 'L', 'L', false, 0);
    PDF::MultiCell(300, 10, "Applicant's Signature over Printed Name", 'R', 'C', false);

    PDF::MultiCell(510, 10, "application or dismissal in case I am employed. This likewise serves as an", 'L', 'L', false, 0);
    PDF::MultiCell(210, 10, "", 'R', 'C', false);

    PDF::MultiCell(470, 10, "authorization to conduct investigation on my personal background.", 'L', 'L', false, 0);
    PDF::MultiCell(30, 10, "", '', 'C', false, 0);
    PDF::MultiCell(220, 10, "", 'RB', 'C', false);

    PDF::MultiCell(470, 10, "", 'L', 'C', false, 0);
    PDF::MultiCell(30, 10, "", '', 'C', false, 0);
    PDF::MultiCell(220, 10, "Date Accomplished", 'R', 'C', false);

    PDF::MultiCell(510, 10, "", 'BL', 'C', false, 0);
    PDF::MultiCell(210, 10, "", 'BR', 'C', false);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function cdo_background_applicant($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontarial = "";
    $fontsize = 12;
    $timesbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHICB.TTF')) {
      $timesbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
      $fontarial = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
      $fontarialB = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($font, '', $fontsize);
    PDF::Image($this->companysetup->getlogopath($config['params']) . 'cyclesM.png', '80', '15', 160, 114);
    PDF::Image($this->companysetup->getlogopath($config['params']) . 'mbclogo.png', '280', '30', 143, 83);
    PDF::Image($this->companysetup->getlogopath($config['params']) . 'ridefundpaf.png', '425', '60', 272, 51);


    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n");

    PDF::SetFont($timesbold, '', 27);
    PDF::MultiCell(30, 0, "", '', 'C', false, 0);
    PDF::MultiCell(660, 0, "BACKGROUND INVESTIGATION FOR APPLICANT ", 'B', 'C', false, 0);
    PDF::MultiCell(30, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($timesbold, 'B', $fontsize);

    PDF::MultiCell(45, 18, "NAME", 'TL', 'L', false, 0, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(315, 18, "" . $data[0]->clientname, 'T', 'L', false, 0, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(130, 18, "DESIRED POSITION: ", 'TL', 'L', false, 0, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(190, 18, "" . $data[0]->jobtitle, 'TR', 'L', false, 0, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(40, 18, "", '', 'L', false, 1, '',  '', true, 0, false, true, 18, 'M', false);

    PDF::SetFont($timesbold, 'B', $fontsize);
    PDF::MultiCell(100, 18, "CONTACT NO #: ", 'BTL', 'C', false, 0, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(260, 18, "" . $data[0]->mobileno, 'RBT', 'L', false, 0, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(70, 18, "ADDRESS: ", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(250, 18, "" . $data[0]->address, 'TRB', 'L', false, 1, '',  '', true, 0, false, true, 18, 'M', false);
    PDF::MultiCell(40, 18, "" . $data[0]->address, '', 'L', false, 1, '',  '', true, 0, false, true, 18, 'M', false);
  }
  public function rpt_background_applicant_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontarial = "";
    $border = "1px solid ";
    $fontsize = "12";
    $timesbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
      $timesbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
    }
    $this->cdo_background_applicant($config, $data);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($timesbold, 'B', $fontsize);
    PDF::MultiCell(30, 10, "A. ", '', 'C', false, 0);
    PDF::MultiCell(275, 10, "APPLICANT FAMILY HISTORY/DESCRIPTION:", 'B', 'L', false, 0);
    PDF::MultiCell(415, 10, "", '', 'L', false, 1);

    PDF::SetFont($timesbold, 'B', 5);

    PDF::MultiCell(30, 5, "", '', 'L', false, 0);
    PDF::MultiCell(295, 5, "", '', 'L', false, 0);
    PDF::MultiCell(395, 5, "", '', 'L', false, 1);

    PDF::SetFont($timesbold, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'LT', '', false, 0);
    PDF::MultiCell(700, 0, "", 'RT', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(110, 0, "Name of Mother: ", '', '', false, 0);
    PDF::MultiCell(590, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(80, 0, "Occupation: ", '', '', false, 0);
    PDF::MultiCell(620, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(110, 0, "Name of Father: ", '', '', false, 0);
    PDF::MultiCell(590, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(80, 0, "Occupation: ", '', '', false, 0);
    PDF::MultiCell(620, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(205, 0, "Number of Siblings (if applicable): ", '', '', false, 0);
    PDF::MultiCell(495, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(265, 0, "Occupation of Sibling (if student, what year): ", '', '', false, 0);
    PDF::MultiCell(435, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(100, 0, "", '', '', false, 0);
    PDF::MultiCell(600, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(205, 0, "Name of Neighbor: (comment)", '', '', false, 0);
    PDF::MultiCell(495, 0, "", 'R', '', false);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(555, 0, "If the applicant has any criminal record, offense, etc., write down the cause, and status of the ", '', '', false, 0);
    PDF::MultiCell(145, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(700, 0, "applicant if it’s already resolved.", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(700, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(700, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(700, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'LB', '', false, 0);
    PDF::MultiCell(700, 0, "", 'BR', '', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($timesbold, 'B', $fontsize);
    PDF::MultiCell(30, 10, "B. ", '', 'C', false, 0);
    PDF::MultiCell(210, 10, "APPLICANT RESIDENCE PICTURE:", 'B', 'L', false, 0);
    PDF::MultiCell(470, 10, "", '', 'L', false, 1);

    PDF::SetFont($timesbold, 'B', 5);

    PDF::MultiCell(30, 5, "", '', 'L', false, 0);
    PDF::MultiCell(295, 5, "", '', 'L', false, 0);
    PDF::MultiCell(395, 5, "", '', 'L', false, 1);

    PDF::SetFont($timesbold, 'B', $fontsize);
    PDF::MultiCell(720, 0, "PROVIDE ACTUAL RESIDENCE (NOT HOUSE SKETCH)", 'LRT', '', false);
    PDF::SetTextColor(255, 0, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "Note: If the applicant’s residence is difficult to visit, kindly write notations as to why you couldn’t", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "attach the residence’s applicant’s picture, instead just attach the house sketch.", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "For example: Pita ila balay lisod adtoan delikado ilang lugar, maong wala nako na adtoan.", 'LR', '', false);
    PDF::SetTextColor(0, 0, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "", 'LR', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, "", 'LRB', '', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($timesbold, 'B', $fontsize);
    PDF::MultiCell(30, 10, "C. ", '', 'C', false, 0);
    PDF::MultiCell(325, 10, "SOURCE/REFERENCE COMMENT OF THE APPLICANT:", 'B', 'L', false, 0);
    PDF::MultiCell(345, 10, "", '', 'L', false, 1);

    PDF::SetFont($timesbold, 'B', 5);

    PDF::MultiCell(30, 5, "", '', 'L', false, 0);
    PDF::MultiCell(295, 5, "", '', 'L', false, 0);
    PDF::MultiCell(390, 5, "", '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'LT', '', false, 0);
    PDF::MultiCell(15, 0, " •", 'T', '', false, 0);
    PDF::MultiCell(685, 0, "Applicant’s previous job.", 'RT', '', false);


    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " Name of establishment:", 'R', '', false);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " Name of applicant’s previous co-employee (provide atleast 1):", 'R', '', false);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " Contact # of the reference:", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(700, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " *Does the applicant has any criminal records?", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " -", 'R', '', false);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " *Does the applicant have remaining liabilities in the company?", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " -", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " *Did the applicant resigned, AWOL, or outright resigned?", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " -", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(700, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(700, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'LB', '', false, 0);
    PDF::MultiCell(700, 0, "", 'BR', '', false);

    if (PDF::getY() > 900) {
      PDF::MultiCell(0, 0, "\n\n\n");
    }

    PDF::SetFont($timesbold, 'B', 5);

    PDF::MultiCell(30, 5, "", '', 'L', false, 0);
    PDF::MultiCell(295, 5, "", '', 'L', false, 0);
    PDF::MultiCell(395, 5, "", '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'LT', '', false, 0);
    PDF::MultiCell(690, 0, " ", 'RT', '', false);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " *Why the applicant leave from the previous job?", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " -", 'R', '', false);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " *Can you give comments regarding the applicant’s attitude and work performance?", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " -", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " *If given a chance would you re-hire the applicant?", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " -", 'R', '', false);
    PDF::SetTextColor(255, 0, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " NOTE: This is not applicant for fresh grad, or no work experience. In addition, if you", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " encounter difficulties contacting references, or if he/she works in abroad, kindly write", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", 'L', '', false, 0);
    PDF::MultiCell(690, 0, " notations stating the reason.", 'R', '', false);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(15, 0, "", 'LB', '', false, 0);
    PDF::MultiCell(705, 0, "", 'BR', '', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($timesbold, 'B', $fontsize);
    PDF::MultiCell(20, 10, "D. ", '', 'C', false, 0);
    PDF::MultiCell(130, 10, "RECOMMENDATION:", 'B', 'L', false, 0);
    PDF::MultiCell(570, 10, "", '', 'L', false, 1);

    PDF::SetFont($timesbold, 'B', 5);
    PDF::MultiCell(20, 5, "", '', 'L', false, 0);
    PDF::MultiCell(295, 5, "", '', 'L', false, 0);
    PDF::MultiCell(405, 5, "", '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'TL', '', false, 0);
    PDF::MultiCell(5, 0, "", 'T', '', false, 0);
    PDF::MultiCell(695, 0, "Kindly check the box of your option, and write down your overall remarks of the applicant.", 'TR', '', false);

    PDF::SetFillColor(230, 230, 250);
    PDF::Rect(85, 282, 8, 8, 'D');
    PDF::SetFillColor(0, 0, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::SetFont($fontarial, 'B', $fontsize);
    PDF::MultiCell(660, 0, "FOR HIRED (write down target date of employment", 'R', '', false);


    PDF::SetFillColor(230, 230, 250);
    PDF::Rect(85, 296.5, 8, 8, 'D');
    PDF::SetFillColor(0, 0, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::SetFont($fontarial, 'B', $fontsize);
    PDF::MultiCell(660, 0, "FURTHER ASSESSMENT", 'R', '', false);


    PDF::SetFillColor(230, 230, 250);
    PDF::Rect(85, 311.5, 8, 8, 'D');
    PDF::SetFillColor(0, 0, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::SetFont($fontarial, 'B', $fontsize);
    PDF::MultiCell(660, 0, "DID NOT PASSED B.I. (write down the reason)", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::MultiCell(660, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::MultiCell(660, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::MultiCell(660, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(25, 0, "", '', '', false, 0);
    PDF::SetFont($fontarial, 'B', $fontsize);
    PDF::MultiCell(675, 0, "OVERALL COMMENT: (What can you say about the applicant based on your investigation)", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(25, 0, "", '', '', false, 0);
    PDF::MultiCell(675, 0, "-", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::MultiCell(660, 0, "", 'R', '', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'L', '', false, 0);
    PDF::MultiCell(40, 0, "", '', '', false, 0);
    PDF::MultiCell(660, 0, "", 'R', '', false);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(20, 0, "", 'BL', '', false, 0);
    PDF::MultiCell(700, 0, "", 'BR', '', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
