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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class hd
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
    $fields = ['radioprint','radioreporttype','prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);  
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Notice of Disciplinary Action', 'value' => 'discipline', 'color' => 'orange'],
      ['label' => 'Notice of Resolution', 'value' => 'resolution', 'color' => 'orange']
    ]);

    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared,
      'discipline' as reporttype
    ");
  }

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
    $reporttype = $config['params']['dataparams']['reporttype'];    
      if($config['params']['dataparams']['print'] == "default"){
        $str = $this->rpt_hd_layout($config, $data);
      }else if($config['params']['dataparams']['print'] == "PDFM") {
       
        switch ($reporttype) {
          case 'discipline':
            $str = $this->rpt_hd_discipline_PDF($config, $data);
  
          break;
          case 'resolution':
            $str = $this->rpt_hd_resolution_PDF($config, $data);

          break;
        }
      }
      return $str;
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query="
    select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      head.artid, head.sectionno, head.violationno,
      head.startdate, head.enddate, head.amt,
      head.detail, emp.clientname as empname,
      head.jobtitle,
      chead.description as articlename,
      cdetail.description as sectionname,
      head.penalty, head.numdays,
      head.refx,
      emp.client as empcode,
      dept.client as dept,
      dept.clientname as deptname,
      head.deptid,
      ir.docno as irno,
      ir.idescription as irdesc,
      chead.code as artcode,
      cdetail.section as sectioncode,head.supervisor
      from disciplinary as head
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join hincidenthead as ir on head.refx=ir.trno
      left join codehead as chead on chead.artid=head.artid
      left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HD'
      union all
      select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      head.artid, head.sectionno, head.violationno,
      head.startdate, head.enddate, head.amt,
      head.detail, emp.clientname as empname,
      head.jobtitle,
      chead.description as articlename,
      cdetail.description as sectionname,
      head.penalty, head.numdays,
      head.refx,
      emp.client as empcode,
      dept.client as dept,
      dept.clientname as deptname,
      head.deptid,
      ir.docno as irno,
      ir.idescription as irdesc,
      chead.code as artcode,
      cdetail.section as sectioncode,head.supervisor
      from hdisciplinary as head
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join hincidenthead as ir on head.refx=ir.trno
      left join codehead as chead on chead.artid=head.artid
      left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HD'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  
  public function rpt_hd_resolution_PDF($params, $data)
  {
    $border = '1px solid';
    $fontsize = '11';
    
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
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(720, 0, "MEMORANDUM", '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(120, 15, "DATE", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": ".(isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']), 'F d, Y') : ''), '', 'L', false);
    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(120, 15, "To", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": ".(isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(120, 15, "Position", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": ".(isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(120, 15, "Subject", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": Notice of Resolution", '', 'L', false);

 
    PDF::SetLineWidth(2);

    PDF::MultiCell(720, 15, "", 'T', 'L', false);
    PDF::SetLineWidth(1);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(720, 15, "IMPORTANT DETAILS OF INFRACTION", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 15, "1. Indicate all-important details (act, date, place, etc.)", '', 'L', false);
    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 15, "2. If appropriate, Attach employee's written explanation.", '', 'L', false);

    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 15, "This action has been deemed necessary as we have determined that you have committed the following:", '', 'L', false);

    PDF::MultiCell(220, 15, "3.3", '', 'L', false,0);
    PDF::MultiCell(220, 15, "Tardiness", '', 'L', false,1);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(720, 15, "DISCIPLINARY ACTION", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(240, 15, "( ) Corrective Interview", '', 'L', false,0);
    PDF::MultiCell(240, 15, "( ) Verbal Warning or Reprimand", '', 'L', false,0);
    PDF::MultiCell(240, 15, "( ) Last Written Warning or Reprimand", '', 'L', false);

    
    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(240, 15, "( ) Suspension Days:", '', 'L', false,0);
    PDF::MultiCell(240, 15, "( ) Stop Of Service When", '', 'L', false,0);
    PDF::MultiCell(240, 15, "( ) Penalty", '', 'L', false);

    
    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(720, 15, "Signed By:", '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(300, 15, '', '', 'L', false,0);
    PDF::MultiCell(120, 15, $prepared, 'B', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);

    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(300, 15, '', '', 'L', false,0);
    PDF::MultiCell(120, 15, 'HR Manager', '', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);
    
    PDF::MultiCell(0, 0, "\n\n");
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(720, 15, "Approved By:", '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(300, 15, '', '', 'L', false,0);
    PDF::MultiCell(120, 15, $approved, 'B', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(300, 15, '', '', 'L', false,0);
    PDF::MultiCell(120, 15, 'PRESIDENT', '', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(400, 15, "", '', 'R', false,0);
    PDF::MultiCell(320, 15, "I fully understand and accept this disciplinary action", '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 12);
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(430, 15, "", '', 'R', false,0);
    PDF::MultiCell(260, 15, "", 'B', 'C', false,0);
    PDF::MultiCell(30, 15, "", '', 'C', false);
    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(400, 15, "", '', 'R', false,0);
    PDF::MultiCell(320, 15, "Employee's Signature over Printed Name / Date", '', 'C', false);
    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_hd_discipline_PDF($params, $data)
  {
    $border = '1px solid';
    $fontsize = '11';

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
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(720, 0, "NOTICE OF DISCIPLINARY ACTION", '', 'C');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Doc No.", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(300, 15, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false,0);
    PDF::MultiCell(40, 15, "", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(50, 15, "DATE : ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(200, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Section/Dept", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(600, 15, (isset($data[0]['sectionname']) ? $data[0]['sectionname'] : ''), '', 'L', false,1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Employee", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(600, 15, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false,1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Position", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(600, 15, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '', 'L', false,1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "IRF No.", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(600, 15, (isset($data[0]['irno']) ? $data[0]['irno'] : ''), '', 'L', false,1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Date of Incident", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(600, 15, (isset($data[0]['startdate']) ? $data[0]['startdate'] : ''), '', 'L', false,1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Reports ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(600, 15, (isset($data[0]['penalty']) ? $data[0]['penalty'] : ''), '', 'L', false,1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Details ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(600, 15, (isset($data[0]['detail']) ? $data[0]['detail'] : ''), '', 'L', false,1);


    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, "Prepared by ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(220, 15, (isset($params['params']['dataparams']['prepared']) ? $params['params']['dataparams']['prepared'] : ''), 'B', 'L', false,1);
    
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(300, 15, "Employee's Explanation", '', 'L');

    PDF::MultiCell(700, 20, "",'B');
    PDF::MultiCell(700, 20, "",'B');
    PDF::MultiCell(700, 20, "",'B');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(220, 15, "(HR to fill up) ", '', 'L', false,1);
    PDF::MultiCell(220, 15, "Violation Committed:", '', 'L', false,1);

    PDF::MultiCell(220, 15, "CC0000002", '', 'L', false,0);
    PDF::MultiCell(220, 15, "3.3 TARDINESS", '', 'L', false,1);

    PDF::MultiCell(220, 15, "3.3", '', 'L', false,0);
    PDF::MultiCell(220, 15, "Tardiness", '', 'L', false,1);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(420, 15, "Recommended Disciplinary Action to be Taken:", '', 'L', false,1);

    PDF::MultiCell(220, 15, "Nth Time Violated", '', 'L', false,0);
    PDF::MultiCell(220, 15, ":".(isset($data[0]['violationno']) ? $data[0]['violationno'] : ''), '', 'L', false,1);
    PDF::MultiCell(220, 15, "Penalty", '', 'L', false,0);
    PDF::MultiCell(220, 15, ":".(isset($data[0]['penalty']) ? $data[0]['penalty'] : ''), '', 'L', false,1);

    PDF::MultiCell(220, 15, "Effective Date", '', 'L', false,0);
    PDF::MultiCell(220, 15, "", '', 'L', false,1);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(220, 15, "Effective Date", '', 'L', false,0);
    PDF::MultiCell(220, 15, "", '', 'L', false,1);


    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(220, 15, "Conforme", '', 'L', false,0);
    PDF::MultiCell(220, 15, "", 'B', 'L', false,1);

    PDF::MultiCell(220, 15, "Noted by:", '', 'L', false,0);
    PDF::MultiCell(220, 15, "", 'B', 'L', false,1);

    PDF::MultiCell(220, 15, "", '', 'L', false,0);
    PDF::MultiCell(220, 15, (isset($data[0]['supervisor']) ? $data[0]['supervisor'] : ''), '', 'L', false,1);

    PDF::MultiCell(0, 0, "\n\n");


    PDF::MultiCell(220, 15, "Signed:", '', 'L', false,1);
    
    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(180, 15, "Date ______________________", '', 'L', false,0);
    PDF::MultiCell(180, 15, "Date ______________________", '', 'L', false,0);
    PDF::MultiCell(180, 15, "Date ______________________", '', 'L', false,0);
    PDF::MultiCell(180, 15, "Date ______________________", '', 'L', false,1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_hd_layout($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
 
    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
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
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
  
    $str .= $this->reporter->col('NOTICE OF DISCIPLINARY ACTION', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['empname']) ? $data[0]['empname'] : ''), '360', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '75', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '650', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '650', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TIMES VIOLATED : ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['violationno'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PENALTY : ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['penalty'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('AMOUNT : ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['amt'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

}
