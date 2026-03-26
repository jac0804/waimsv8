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

class driver
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
    $fields = ['radioprint', 'prepared','approved','received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    if ($config['params']['companyid'] == 10) { // afti
        data_set($col1, 'prepared.readonly',true);
        data_set($col1, 'prepared.type','lookup');
        data_set($col1, 'prepared.action','lookupclient');
        data_set($col1, 'prepared.lookupclass','prepared');

        data_set($col1, 'approved.readonly',true);
        data_set($col1, 'approved.type','lookup');
        data_set($col1, 'approved.action','lookupclient');
        data_set($col1, 'approved.lookupclass','approved');

        data_set($col1, 'received.readonly',true);
        data_set($col1, 'received.type','lookup');
        data_set($col1, 'received.action','lookupclient');
        data_set($col1, 'received.lookupclass','received');
    }
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

  public function report_default_query($config){

    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = md5($config['params']['dataid']);

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $query = "select emp.empid, client.client as empcode, CONCAT(UPPER(emplast), ', ', empfirst, ' ', empmiddle, '.') AS employee, emp.address,department.clientname as deptname,division.divname,section.sectname,
    emp.city, emp.country, emp.zipcode, emp.telno, emp.mobileno, emp.email, emp.citizenship, emp.religion, emp.status,
    emp.gender, emp.alias, emp.picpath, date(emp.bday) as bday, emp.idbarcode, emp.tin, emp.sss, emp.hdmf, emp.bankacct, emp.phic,
    emp.atm, emp.paymode, emp.jobtitle, emp.jobcode, emp.jobdesc, date(emp.hired) as hired, emp.regular, emp.resigned, emp.division,
    emp.dept, emp.orgsection, emp.supervisor, emp.school, emp.course, emp.yrgrad, emp.yrsattend, emp.gpa, emp.prevcomp,
    emp.prevjob, emp.prevjstart, emp.prevjend, emp.yrsexp, emp.teu, emp.nodeps, emp.isactive, emp.classrate,
    emp.maidname, emp.isconfidential, emp.shiftcode,  con.contact1, con.relation1, con.addr1, con.homeno1, con.mobileno1, con.officeno1, con.ext1,
    con.notes1, con.contact2, con.relation2, con.addr2, con.homeno2, con.mobileno2, con.officeno2, con.ext2,con.notes2,emp.paygroup
     FROM employee AS emp  
     left join contacts AS con on con.empid=emp.empid
     left join client as department on department.clientid=Emp.deptid
     left join division on division.divid=emp.divid
     left join section on section.sectid=emp.sectid
     left join client on client.clientid=emp.empid
     where md5(emp.empid)='$clientid'";

    return $this->coreFunctions->opentable($query);
  } //end fn

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
    if($config['params']['dataparams']['print'] == "default"){
      $str = $this->rpt_employee_layout($config, $data);
    }else{
      $str = $this->rpt_employee_PDF($config, $data);
    }
    return $str;
  }


  public function rpt_employee_PDF($config)
  {
    $data     = $this->report_default_query($config);
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $fontsize = "11";
    $count = 55;
    $page = 54;
    $font = "";
    $fontbold = "";

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
    PDF::MultiCell(0, 0, $center.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.$username, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 30, "EMPLOYEE MASTERFILE", '', 'L', false);

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
    PDF::MultiCell(90, 15, "Full Name : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, '(' . (isset($data[0]->empcode) ? $data[0]->empcode : '') . ')' . '  ' . (isset($data[0]->employee) ? $data[0]->employee : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "Alias : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->alias) ? $data[0]->alias : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 15, "Birthdate : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, (isset($data[0]->bday) ? $data[0]->bday : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "Address : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->address) ? $data[0]->address : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 15, "Gender : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, (isset($data[0]->gender) ? $data[0]->gender : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "Citizenship : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->citizenship) ? $data[0]->citizenship : ''), '', 'L', false);    

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 15, "Civil Status : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, (isset($data[0]->status) ? $data[0]->status : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "Religion : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->religion) ? $data[0]->religion : ''), '', 'L', false);      

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 15, "Tel. No. : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, (isset($data[0]->telno) ? $data[0]->telno : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "Mobile No. : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->mobileno) ? $data[0]->mobileno : ''), '', 'L', false);      

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 15, "Email Address : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "SSS No. : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->sss) ? $data[0]->sss : ''), '', 'L', false);       

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 15, "Tin No. : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "Philhealth No. : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->phic) ? $data[0]->phic : ''), '', 'L', false);       

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 15, "HDMF No. : ", '', 'L', false,0);
    PDF::MultiCell(290, 15, (isset($data[0]->hdmf) ? $data[0]->hdmf : ''), '', 'L', false,0);
    PDF::MultiCell(110, 15, "Bank Account No. : ", '', 'L', false,0);
    PDF::MultiCell(270, 15, (isset($data[0]->bankacct) ? $data[0]->bankacct : ''), '', 'L', false);      

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 0, "JOB & ORGANIZATION", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");    

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Job Title : ", '', 'L', false,0);
    PDF::MultiCell(153, 15, (isset($data[0]->jobtitle) ? $data[0]->jobtitle : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Date Hired : ", '', 'L', false,0);
    PDF::MultiCell(153, 15, (isset($data[0]->hired) ? $data[0]->hired : ''), '', 'L', false,0);   
    PDF::MultiCell(100, 15, "Regular : ", '', 'L', false,0);
    PDF::MultiCell(154, 15, (isset($data[0]->regular) ? $data[0]->regular : ''), '', 'L', false);   

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Division : ", '', 'L', false,0);
    PDF::MultiCell(153, 15, (isset($data[0]->divname) ? $data[0]->divname : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Department : ", '', 'L', false,0);
    PDF::MultiCell(153, 15, (isset($data[0]->deptname) ? $data[0]->deptname : ''), '', 'L', false,0);   
    PDF::MultiCell(100, 15, "Section : ", '', 'L', false,0);
    PDF::MultiCell(154, 15, (isset($data[0]->sectname) ? $data[0]->sectname : ''), '', 'L', false); 

    if ($data[0]->classrate == 'M') {
      $classrate = 'MONTHLY';
    } else {
      $classrate = 'DAILY';
    }

    if ($data[0]->paymode == 'M') {
      $paymode = 'MONTHLY';
    } elseif ($data[0]->paymode == 'W') {
      $paymode = 'WEEKLY';
    } elseif ($data[0]->paymode == 'D') {
      $paymode = 'DAILY';
    } elseif ($data[0]->paymode == 'P') {
      $paymode = 'PIECE';
    } else {
      $paymode = '';
    }

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Payroll Group : ", '', 'L', false,0);
    PDF::MultiCell(153, 15, (isset($data[0]->paygroup) ? $data[0]->paygroup : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Class Rate : ", '', 'L', false,0);
    PDF::MultiCell(153, 15, (isset($classrate) ? $classrate : ''), '', 'L', false,0);   
    PDF::MultiCell(100, 15, "Mode of Payment : ", '', 'L', false,0);
    PDF::MultiCell(154, 15, (isset($paymode) ? $paymode : ''), '', 'L', false); 

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Supervisor : ", '', 'L', false,0);
    PDF::MultiCell(153, 15, (isset($data[0]->supervisor) ? $data[0]->supervisor : ''), '', 'L', false);

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
    PDF::MultiCell(100, 15, "Contact : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->contact1) ? $data[0]->contact1 : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Contact : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->contact2) ? $data[0]->contact2 : ''), '', 'L', false); 

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Relation : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->relation1) ? $data[0]->relation1 : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Relation : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->relation2) ? $data[0]->relation2 : ''), '', 'L', false); 

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Address : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->addr1) ? $data[0]->addr1 : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Address : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->addr2) ? $data[0]->addr2 : ''), '', 'L', false); 

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Tel No. : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->homeno1) ? $data[0]->homeno1 : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Tel No. : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->homeno2) ? $data[0]->homeno2 : ''), '', 'L', false); 

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Mobile No. : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->mobileno1) ? $data[0]->mobileno1 : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Mobile No. : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->mobileno2) ? $data[0]->mobileno2 : ''), '', 'L', false); 

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, "Office No. : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->officeno1) ? $data[0]->officeno1 : ''), '', 'L', false,0);
    PDF::MultiCell(100, 15, "Office No. : ", '', 'L', false,0);
    PDF::MultiCell(280, 15, (isset($data[0]->officeno2) ? $data[0]->officeno2 : ''), '', 'L', false); 
 
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 0, "EDUCATIONAL HISTORY", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");  

    $qry = "select empid, line, school, address, course, sy, gpa, honor from education where empid= " . $data[0]->empid . " order by line ";
    $dataeduc = $this->coreFunctions->opentable($qry);


    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(220, 15, "School", '', 'L', false,0);
    PDF::MultiCell(220, 15, "Address", '', 'L', false,0);
    PDF::MultiCell(120, 15, "Course", '', 'L', false,0);
    PDF::MultiCell(100, 15, "School Yr", '', 'L', false,0);
    PDF::MultiCell(100, 15, "Honor", '', 'L', false);

    foreach ($dataeduc as $key => $data1) {
      $maxrow = 1;
      $arr_school = $this->reporter->fixcolumn([$data1->school],'35',0);
      $arr_address = $this->reporter->fixcolumn([$data1->address],'35',0);
      $arr_course = $this->reporter->fixcolumn([$data1->course],'25',0);
      $arr_sy = $this->reporter->fixcolumn([$data1->sy],'16',0);
      $arr_honor = $this->reporter->fixcolumn([$data1->honor],'16',0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_school, $arr_address, $arr_course, $arr_sy, $arr_honor]);

      for($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(220, 15, (isset($arr_school[$r]) ? $arr_school[$r] : ''), '', 'L', false,0);
        PDF::MultiCell(220, 15, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false,0); 
        PDF::MultiCell(120, 15, (isset($arr_course[$r]) ? $arr_course[$r] : ''), '', 'L', false,0);
        PDF::MultiCell(100, 15, (isset($arr_sy[$r]) ? $arr_sy[$r] : ''), '', 'L', false,0); 
        PDF::MultiCell(100, 15, (isset($arr_honor[$r]) ? $arr_honor[$r] : ''), '', 'L', false); 
      }
    }
 
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(760, 0, "EMPLOYMENT HISTORY", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");  

    $qry = "select empid, line, company, jobtitle, period, address, salary, reason from employment where empid= " . $data[0]->empid . " order by line ";
    $dataemploy = $this->coreFunctions->opentable($qry);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(190, 15, "Company", '', 'L', false,0);
    PDF::MultiCell(130, 15, "Jobtitle", '', 'L', false,0);
    PDF::MultiCell(100, 15, "Salary", '', 'R', false,0);
    PDF::MultiCell(20, 15, "", '', 'R', false,0);
    PDF::MultiCell(120, 15, "Period", '', 'L', false,0);
    PDF::MultiCell(200, 15, "Reason of Leaving", '', 'L', false);

    foreach ($dataemploy as $key => $data1) {
      $maxrow = 1;
      $arr_company = $this->reporter->fixcolumn([$data1->company],'25',0);
      $arr_jobtitle = $this->reporter->fixcolumn([$data1->jobtitle],'25',0);
      $arr_salary = $this->reporter->fixcolumn([$data1->salary],'16',0);
      $arr_period = $this->reporter->fixcolumn([$data1->period],'16',0);
      $arr_reason = $this->reporter->fixcolumn([$data1->reason],'25');

      $maxrow = $this->othersClass->getmaxcolumn([$arr_company, $arr_jobtitle, $arr_salary, $arr_period, $arr_reason]);

      for($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(190, 15, (isset($arr_company[$r]) ? $arr_company[$r] : ''), '', 'L', false,0);
        PDF::MultiCell(130, 15, (isset($arr_jobtitle[$r]) ? $arr_jobtitle[$r] : ''), '', 'L', false,0); 
        PDF::MultiCell(100, 15, (isset($arr_salary[$r]) ? $arr_salary[$r] : ''), '', 'R', false,0);
        PDF::MultiCell(20, 15, '', '', 'R', false,0);
        PDF::MultiCell(120, 15, (isset($arr_period[$r]) ? $arr_period[$r] : ''), '', 'L', false,0); 
        PDF::MultiCell(200, 15, (isset($arr_reason[$r]) ? $arr_reason[$r] : ''), '', 'L', false); 
      }
    }
    
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $approved, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $received, '', 'L');
    
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_employee_layout($config)
  {
    $data     = $this->report_default_query($config);
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();

    if($companyid == 3){
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
    }else {
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE MASTERFILE', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PERSONAL DETAILS', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Full Name : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]->empcode) ? $data[0]->empcode : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->employee) ? $data[0]->employee : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Alias : ', '40', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->alias) ? $data[0]->alias : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Birthdate : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->bday) ? $data[0]->bday : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->address) ? $data[0]->address : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Gender : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->gender) ? $data[0]->gender : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Citizenship : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->citizenship) ? $data[0]->citizenship : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Civil Status : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->status) ? $data[0]->status : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Religion : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->religion) ? $data[0]->religion : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tel. No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->telno) ? $data[0]->telno : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Mobile No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->mobileno) ? $data[0]->mobileno : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Email Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->email) ? $data[0]->email : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('SSS No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->sss) ? $data[0]->sss : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tin No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->tin) ? $data[0]->tin : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Philhealth No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->phic) ? $data[0]->phic : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('HDMF No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->hdmf) ? $data[0]->hdmf : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('BankAccount No. : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->bankacct) ? $data[0]->bankacct : ''), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB & ORGANIZATION', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->jobtitle) ? $data[0]->jobtitle : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Date Hired : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->hired) ? $data[0]->hired : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Regular : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->regular) ? $data[0]->regular : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Division : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->divname) ? $data[0]->divname : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Department : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->deptname) ? $data[0]->deptname : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('Section : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->sectname) ? $data[0]->sectname : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Payroll Group : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->paygroup) ? $data[0]->paygroup : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

    if ($data[0]->classrate == 'M') {
      $classrate = 'MONTHLY';
    } else {
      $classrate = 'DAILY';
    }
    $str .= $this->reporter->col('Class Rate : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($classrate) ? $classrate : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

    if ($data[0]->paymode == 'M') {
      $paymode = 'MONTHLY';
    } elseif ($data[0]->paymode == 'W') {
      $paymode = 'WEEKLY';
    } elseif ($data[0]->paymode == 'D') {
      $paymode = 'DAILY';
    } elseif ($data[0]->paymode == 'P') {
      $paymode = 'PIECE';
    } else {
      $paymode = '';
    }

    $str .= $this->reporter->col('Mode of Payment : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($paymode) ? $paymode : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supervisor : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->supervisor) ? $data[0]->supervisor : ''), '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
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
    $str .= $this->reporter->printline();


    $str .= $this->reporter->begintable('800');

    $qry = "select empid, line, school, address, course, sy, gpa, honor from education where empid= " . $data[0]->empid . " order by line ";
    $dataeduc = $this->coreFunctions->opentable($qry);


    foreach ($dataeduc as $key => $data1) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('School : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->school) ? $data1->school : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('Address : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->address) ? $data1->address : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('Course : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->course) ? $data1->course : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('School Yr : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->sy) ? $data1->sy : ''), '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('Honor : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->honor) ? $data1->honor : ''), '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');

      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYMENT HISTORY', null, null, false, $border, '', '', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();


    $str .= $this->reporter->begintable('800');

    $qry = "select empid, line, company, jobtitle, period, address, salary, reason from employment where empid= " . $data[0]->empid . " order by line ";
    $dataemploy = $this->coreFunctions->opentable($qry);


    foreach ($dataemploy as $key => $data1) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Company : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->company) ? $data1->company : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('Jobtitle : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->jobtitle) ? $data1->jobtitle : ''), '60', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('Salary : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->salary) ? $data1->salary : ''), '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('Period : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->period) ? $data1->period : ''), '30', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col('Reason of Leaving : ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col((isset($data1->reason) ? $data1->reason : ''), '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();


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
  }

}
