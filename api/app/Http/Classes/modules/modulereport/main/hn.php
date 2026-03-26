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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class hn
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

  public function createreportfilter(){
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);  
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($config){
    $trno = $config['params']['dataid'];
    $query="
    select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid, 
      head.artid,
      femp.client as fempcode, femp.clientname as fempname, head.fempjob,
      emp.clientname as empname,
      head.empjob,
      chead.description as articlename, 
      cdetail.description as sectionname,
      head.refx, head.hplace,
      head.line, head.explanation,
      date(head.ddate) as ddate, head.htime, head.comments,
      date(head.hdatetime) as hdatetime, head.remarks,
      emp.client as empcode,
      dept.client as dept,
      dept.clientname as deptname,
      head.deptid,
      ir.docno as irno,
      ir.idescription as irdesc,
      chead.code as artcode,
      cdetail.section as sectioncode,
      head.fempid,date(ir.idate) as idate
      from notice_explain as head
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join hincidenthead as ir on head.refx=ir.trno
      left join codehead as chead on chead.artid=head.artid
      left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
      left join client as femp on head.fempid=femp.clientid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HN'
      union all
      select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid, 
      head.artid,
      femp.client as fempcode, femp.clientname as fempname, head.fempjob,
      emp.clientname as empname,
      head.empjob,
      chead.description as articlename, 
      cdetail.description as sectionname,
      head.refx, head.hplace,
      head.line, head.explanation,
      date(head.ddate) as ddate, head.htime, head.comments,
      date(head.hdatetime) as hdatetime, head.remarks,
      emp.client as empcode,
      dept.client as dept,
      dept.clientname as deptname,
      head.deptid,
      ir.docno as irno,
      ir.idescription as irdesc,
      chead.code as artcode,
      cdetail.section as sectioncode,
      head.fempid,date(ir.idate) as idate
      from hnotice_explain as head
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join hincidenthead as ir on head.refx=ir.trno
      left join codehead as chead on chead.artid=head.artid
      left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
      left join client as femp on head.fempid=femp.clientid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HN'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
    if($config['params']['dataparams']['print'] == "default"){
      $str = $this->rpt_HN_layout($config, $data);
    } else if($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_HN_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_HN_PDF($config, $data){
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $fontsize = "11";
    $count = 35;
    $page = 35;
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



    if ($companyid == 58) { // cdohris

      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.$username, '', 'L');
      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'L');
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'L');

      $query = "select division from employee where empid = " . $data[0]['empid'] . "";
      $empcomp = $this->coreFunctions->opentable($query);
      if (!empty($empcomp)) {
        if ($empcomp[0]->division == '001') {
          PDF::Image($this->companysetup->getlogopath($config['params']) . 'logocdo2cycles.jpg', '645', '-10', 160, 160);
        }
        if ($empcomp[0]->division == '002') {
          PDF::Image($this->companysetup->getlogopath($config['params']) . 'logombc.jpg', '645', '-10', 160, 160);
        }
        if ($empcomp[0]->division == '003') {
          PDF::Image($this->companysetup->getlogopath($config['params']) . 'logoridefund.png', '645', '-10', 160, 160);
        }
      }
    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.$username, '', 'L');
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');
    }

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(760, 18, "NOTICE TO EXPLAIN", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "DOCUMENT # : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 18, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false,0);
    PDF::MultiCell(260, 18, '', '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "DATE : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 18, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);
    
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "To Employee : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(250, 18, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(80, 18, "Job Title : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 18, (isset($data[0]['empjob']) ? $data[0]['empjob'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 18, "Department : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 18, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);  
    
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "From Employee : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(250, 18, (isset($data[0]['fempname']) ? $data[0]['fempname'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(80, 18, "Job Title : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 18, (isset($data[0]['fempjob']) ? $data[0]['fempjob'] : ''), 'B', 'L', false);
 
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);
    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Article Code : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 18, (isset($data[0]['artcode']) ? $data[0]['artcode'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 18, "Article Description : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(360, 18, (isset($data[0]['articlename']) ? $data[0]['articlename'] : ''), 'B', 'L', false);
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Section Code : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 18, (isset($data[0]['sectioncode']) ? $data[0]['sectioncode'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 18, "Section Description : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(360, 18, (isset($data[0]['sectionname']) ? $data[0]['sectionname'] : ''), 'B', 'L', false);
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Ref Incident # : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 18, (isset($data[0]['irno']) ? $data[0]['irno'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 18, "Incident Description : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(360, 18, (isset($data[0]['irdesc']) ? $data[0]['irdesc'] : ''), 'B', 'L', false);
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);
    
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Deadline Date : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(110, 18, (isset($data[0]['ddate']) ? $data[0]['ddate'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Incident Date : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(110, 18, (isset($data[0]['idate']) ? $data[0]['idate'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(60, 18, "Time : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(110, 18, (isset($data[0]['htime']) ? $data[0]['htime'] : ''), 'B', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(60, 18, "Place : ", '', 'R', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(110, 18, (isset($data[0]['hplace']) ? $data[0]['hplace'] : ''), 'B', 'L', false);
   
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");
    
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "EXPLANATION : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(560, 0, $data[0]['explanation'], '', 'L', false);
    
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "COMMENTS : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(560, 0, $data[0]['comments'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);
    
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "REMARKS : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(560, 0, $data[0]['remarks'], '', 'L', false);
    
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(253, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(254, 0, $config['params']['dataparams']['received'], '', 'L');
  
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_HN_layout($config, $data){
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];
    
    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    
    $str .= $this->reporter->col('NOTICE TO EXPLAIN', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
   
    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DOCUMENT # :', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') ;   
      $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
     
      $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '80', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('To Employee : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['empname']) ? $data[0]['empname'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Job Title : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['empjob']) ? $data[0]['empjob'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Department : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      
      $str .= $this->reporter->endrow();
      
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('From Employee : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['fempname']) ? $data[0]['fempname'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Job Title : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['fempjob']) ? $data[0]['fempjob'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->printline();
    
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Article Code : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['artcode']) ? $data[0]['artcode'] : ''), '30', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Article Description : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['articlename']) ? $data[0]['articlename'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Section Code : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['sectioncode']) ? $data[0]['sectioncode'] : ''), '30', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Section Description : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['sectionname']) ? $data[0]['sectionname'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Ref Incident # : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['irno']) ? $data[0]['irno'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Incident Description : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['irdesc']) ? $data[0]['irdesc'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->endrow();
    
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Deadline Date : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['ddate']) ? $data[0]['ddate'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Incident Date : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['idate']) ? $data[0]['idate'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Time : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['htime']) ? $data[0]['htime'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col('Place : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
      $str .= $this->reporter->col((isset($data[0]['hplace']) ? $data[0]['hplace'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
      
      $str .= $this->reporter->endrow();    
      $str .= $this->reporter->endtable();


    // $str .= $this->reporter->printline();
    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EXPLANATION : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['explanation'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMMENTS : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['comments'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REMARKS : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['remarks'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
  
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

}
