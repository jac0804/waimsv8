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

class hs
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Employment Status Change', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Embarkation Order', 'value' => '1', 'color' => 'orange'],
      ['label' => 'Disembarkation Order', 'value' => '2', 'color' => 'orange']
    ]);
    data_set($col1, 'prepared.label', 'Recommended By');     

    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared,
      '0' as reporttype
    ");
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query="
    select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid, 
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      case 
        when head.fpaymode = 'S' then 'Semi-monthly' 
        when head.fpaymode = 'W' then 'Weekly' 
        when head.fpaymode = 'M' then 'Monthly' 
        when head.fpaymode = 'D' then 'Daily' 
        when head.fpaymode = 'P' then 'Piece Rate' 
        else ''
      end as fpaymode, head.fpaygroup, 
      case 
      when head.fpayrate = 'D' then 'Daily' 
      when head.fpayrate = 'M' then 'Monthly' 
      else ''
      end as fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, head.isactive,
      attention.client as attentioncode, attention.clientname as attentionname,
      fromjob.jobtitle as fromjobtitlename,
      fromdept.clientname as fromdeptname,
      tojob.jobtitle as tojobtitlename,
      todept.clientname as todeptname,date(emp.hired) as datehired,
      fempstat.empstatus as fempstatus,tempstat.empstatus as tempstatus,'UNPOSTED' as posted,
      attenjob.jobtitle as attentionjobtitle,attendept.clientname as attentiondept,division.divname as empdivision,
      empdept.clientname as employeedept,frole.name as frole,trole.name as trole,fdiv.divname as fdivname, 
      tdiv.divname as tdivname,fsec.sectname as fsectname,tsec.sectname as tsectname
      from eschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join hrisnum as num on num.trno = head.trno
      left join jobthead as jt on jt.line = emp.jobid
      left join jobthead as fromjob on fromjob.docno = head.fjobcode
      left join client as attention on attention.clientid = head.attentionid
      left join client as fromdept on fromdept.client = head.fdeptcode
      left join jobthead as tojob on tojob.docno = head.tjobcode
      left join client as todept on todept.client = head.tdeptcode
      left join empstatentry as fempstat on fempstat.line = head.fempstatcode
      left join empstatentry as tempstat on tempstat.code = head.tempstatcode
      left join employee as attemp on attemp.empid = head.attentionid
      left join jobthead as attenjob on attenjob.line = attemp.jobid
      left join client as attendept on attendept.clientid = attemp.deptid
      left join division on division.divid=emp.divid
      left join client as empdept on empdept.clientid = emp.deptid
      left join rolesetup as frole on frole.line = head.froleid
      left join rolesetup as trole on trole.line = head.troleid
      left join division as fdiv on fdiv.divid = head.fdivid
      left join division as tdiv on tdiv.divid = head.tdivid
      left join section as fsec on fsec.sectid = head.fsectid
      left join section as tsec on tsec.sectid = head.tsectid
      where num.trno = '$trno' and num.doc='HS'
      union all
      select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid, 
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      case 
        when head.fpaymode = 'S' then 'Semi-monthly' 
        when head.fpaymode = 'W' then 'Weekly' 
        when head.fpaymode = 'M' then 'Monthly' 
        when head.fpaymode = 'D' then 'Daily' 
        when head.fpaymode = 'P' then 'Piece Rate' 
        else ''
      end as fpaymode, head.fpaygroup, 
      case 
      when head.fpayrate = 'D' then 'Daily' 
      when head.fpayrate = 'M' then 'Monthly' 
      else ''
      end as fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, head.isactive,
      attention.client as attentioncode, attention.clientname as attentionname,
      fromjob.jobtitle as fromjobtitlename,
      fromdept.clientname as fromdeptname,
      tojob.jobtitle as tojobtitlename,
      todept.clientname as todeptname,date(emp.hired) as datehired,
      fempstat.empstatus as fempstatus,tempstat.empstatus as tempstatus,'POSTED' as posted,
      attenjob.jobtitle as attentionjobtitle,attendept.clientname as attentiondept,division.divname as empdivision,
      empdept.clientname as employeedept,frole.name as frole,trole.name as trole,fdiv.divname as fdivname, 
      tdiv.divname as tdivname,fsec.sectname as fsectname,tsec.sectname as tsectname
      from heschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join hrisnum as num on num.trno = head.trno
      left join jobthead as jt on jt.line = emp.jobid
      left join jobthead as fromjob on fromjob.docno = head.fjobcode
      left join client as attention on attention.clientid = head.attentionid
      left join client as fromdept on fromdept.client = head.fdeptcode
      left join jobthead as tojob on tojob.docno = head.tjobcode
      left join client as todept on todept.client = head.tdeptcode
      left join empstatentry as fempstat on fempstat.line = head.fempstatcode
      left join empstatentry as tempstat on tempstat.code = head.tempstatcode
      left join employee as attemp on attemp.empid = head.attentionid
      left join jobthead as attenjob on attenjob.line = attemp.jobid
      left join client as attendept on attendept.clientid = attemp.deptid
      left join division on division.divid=emp.divid
      left join client as empdept on empdept.clientid = emp.deptid
      left join rolesetup as frole on frole.line = head.froleid
      left join rolesetup as trole on trole.line = head.troleid
      left join division as fdiv on fdiv.divid = head.fdivid
      left join division as tdiv on tdiv.divid = head.tdivid
      left join section as fsec on fsec.sectid = head.fsectid
      left join section as tsec on tsec.sectid = head.tsectid
      where num.trno = '$trno' and num.doc='HS'";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }


  public function reportplotting($params,$data)
  { 
    $data = $this->report_default_query($params);
    $reporttype = $params['params']['dataparams']['reporttype'];    
      if($params['params']['dataparams']['print'] == "default"){
        switch ($reporttype) {
          case '0':
            $str = $this->rpt_hs_layout($params, $data);
         
          break;
          case '1':
            $str = $this->rpt_hs_embarkation_layout($params, $data);
    
          break;
          case '2':
            $str = $this->rpt_hs_disembarkation_layout($params, $data);
      
          break;
        }
      
      }else if($params['params']['dataparams']['print'] == "PDFM") {
        switch ($reporttype) {
          case '0':
            $str = $this->rpt_hs_PDF($params, $data);

          break;
          case '1':
            $str = $this->rpt_hs_embarkation_PDF($params, $data);
    
          break;
          case '2':
            $str = $this->rpt_hs_disembarkation_PDF($params, $data);

          break;
        }
      
      }
      return $str;
  }

  public function rpt_hs_PDF($params, $data)
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
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(100, 0, $data[0]['posted'], '', 'L',false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, 'EMPLOYMENT STATUS CHANGE'."\n\n\n", '', 'C');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 16, "Employee : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false,0);
    PDF::MultiCell(90, 16, "DocNo : ", '', 'L', false,0);
    PDF::MultiCell(150, 16, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::MultiCell(130, 16, "Job Title : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '', 'L', false,0);
    PDF::MultiCell(90, 16, "Doc Date : ", '', 'L', false,0);
    PDF::MultiCell(150, 16, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(130, 16, "Department : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['employeedept']) ? $data[0]['employeedept'] : ''), '', 'L', false,0);
    PDF::MultiCell(90, 16, "Date Hired : ", '', 'L', false,0);
    PDF::MultiCell(150, 16, (isset($data[0]['datehired']) ? $data[0]['datehired'] : ''), '', 'L', false);

    PDF::MultiCell(130, 16, "Effective Date : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['effdate']) ? $data[0]['effdate'] : ''), '', 'L', false);

    PDF::MultiCell(130, 16, "Status Change : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['statdesc']) ? $data[0]['statdesc'] : ''), '', 'L', false);

    PDF::MultiCell(130, 16, "Description : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['description']) ? $data[0]['description'] : ''), '', 'L', false);

    PDF::MultiCell(130, 16, "End of Employment : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['resigned']) ? $data[0]['resigned'] : ''), '', 'L', false);

    PDF::MultiCell(130, 16, "Remarks : ", '', 'L', false,0);
    PDF::MultiCell(390, 16, (isset($data[0]['remarks']) ? $data[0]['remarks'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    
    PDF::MultiCell(760, 0, '', 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(130, 0, "", '', 'L', false,0);
    PDF::MultiCell(390, 0, "", '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(267, 0, 'DESCRIPTION', '', 'L', false,0);
    PDF::MultiCell(266, 0, 'FROM', '', 'L', false,0);
    PDF::MultiCell(266, 0, 'TO', '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(130, 0, "", '', 'L', false,0);
    PDF::MultiCell(390, 0, "", '', 'L', false);

    PDF::MultiCell(760, 0, '', 'T', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(267, 16, 'TYPE', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['ftype']) ? $data[0]['ftype'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['ttype']) ? $data[0]['ttype'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'LEVEL', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['flevel']) ? $data[0]['flevel'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tlevel']) ? $data[0]['tlevel'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'JOB TITLE', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fromjobtitlename']) ? $data[0]['fromjobtitlename'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tojobtitlename']) ? $data[0]['tojobtitlename'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'EMPLOYMENT STATUS', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fempstatus']) ? $data[0]['fempstatus'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tempstatus']) ? $data[0]['tempstatus'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'RANK', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['frank']) ? $data[0]['frank'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['trank']) ? $data[0]['trank'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'JOB GRADE', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fjobgrade']) ? $data[0]['fjobgrade'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tjobgrade']) ? $data[0]['tjobgrade'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'DEPARTMENT', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fromdeptname']) ? $data[0]['fromdeptname'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['todeptname']) ? $data[0]['todeptname'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'LOCATION', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['flocation']) ? $data[0]['flocation'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tlocation']) ? $data[0]['tlocation'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'MODE OF PAYMENT', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fpaymode']) ? $data[0]['fpaymode'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tpaymode']) ? $data[0]['tpaymode'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'PAY GROUP', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fpaygroup']) ? $data[0]['fpaygroup'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tpaygroup']) ? $data[0]['tpaygroup'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'CLASS RATE', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fpayrate']) ? $data[0]['fpayrate'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tpayrate']) ? $data[0]['tpayrate'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'ALLOWANCE', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fallowrate']) ? $data[0]['fallowrate'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tallowrate']) ? $data[0]['tallowrate'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'BASIC SALARY', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fbasicrate']) ? $data[0]['fbasicrate'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tbasicrate']) ? $data[0]['tbasicrate'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'DIVISION', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fdivname']) ? $data[0]['fdivname'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tdivname']) ? $data[0]['tdivname'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'SECTION', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['fsectname']) ? $data[0]['fsectname'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['tsectname']) ? $data[0]['tsectname'] : ''), '', 'L', false);

    PDF::MultiCell(267, 16, 'ROLE', '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['frole']) ? $data[0]['frole'] : ''), '', 'L', false,0);
    PDF::MultiCell(266, 16, (isset($data[0]['trole']) ? $data[0]['trole'] : ''), '', 'L', false);




    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Recommended By : ', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(140, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'T', 'C', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'T', 'C', false, 0);
    PDF::MultiCell(140, 0, '', '', 'L');

    
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function rpt_hs_embarkation_PDF($params, $data)
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
    PDF::AddPage('p', [640, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    
    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(600, 10, $data[0]['empdivision'], '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Attention : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['attentionname']) ? $data[0]['attentionname'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Position : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['attentionjobtitle']) ? $data[0]['attentionjobtitle'] : ''), '', 'L', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Department : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['attentiondept']) ? $data[0]['attentiondept'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Date : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(600, 16, 'EMBARKATION ORDER', '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Name : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Position : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Vessel : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['tlocation']) ? $data[0]['tlocation'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Department : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['employeedept']) ? $data[0]['employeedept'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Date : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Description : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['description']) ? $data[0]['description'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(600, 0, 'Prepared By : ', '', 'L', false);
    PDF::MultiCell(0, 0, "\n\n");
    PDF::MultiCell(600, 0, $params['params']['dataparams']['prepared'], '', 'L', false);
    PDF::MultiCell(600, 0, 'HR Officer', '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(300, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'Conforme : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', $fontsize);
    
    PDF::MultiCell(300, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    
    PDF::MultiCell(300, 0, '', '', 'L');

    PDF::MultiCell(300, 0, 'DPA/Port Captain', '', 'L', false,0);
    PDF::MultiCell(200, 0, '', 'B', 'L', false);

    
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function rpt_hs_disembarkation_PDF($params, $data)
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
    PDF::AddPage('p', [640, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    
    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(600, 10, $data[0]['empdivision'], '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Attention : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['attentionname']) ? $data[0]['attentionname'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Position : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['attentionjobtitle']) ? $data[0]['attentionjobtitle'] : ''), '', 'L', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Department : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['attentiondept']) ? $data[0]['attentiondept'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Date : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(600, 16, 'DISEMBARKATION ORDER', '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 16, "Name : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Position : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Vessel : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['flocation']) ? $data[0]['flocation'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Department : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['employeedept']) ? $data[0]['employeedept'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Date : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 16, "Description : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 16, (isset($data[0]['description']) ? $data[0]['description'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(600, 0, 'Prepared By : ', '', 'L', false);
    PDF::MultiCell(0, 0, "\n\n");
    PDF::MultiCell(600, 0, $params['params']['dataparams']['prepared'], '', 'L', false);
    PDF::MultiCell(600, 0, 'HR Officer', '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(300, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'Conforme : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', $fontsize);
    
    PDF::MultiCell(300, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    
    PDF::MultiCell(300, 0, '', '', 'L');

    PDF::MultiCell(300, 0, 'DPA/Port Captain', '', 'L', false,0);
    PDF::MultiCell(200, 0, '', 'B', 'L', false);

    
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function rpt_hs_layout($params, $data)
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

    $str .= $this->reporter->begintable('600');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '500', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
        $str .=  $this->reporter->col($data[0]['posted'], '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '17', 'B', '', '');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('600');
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') ;
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Employment Status Change', null, null, false, '1px solid ', '', 'c', 'Century Gothic', '15', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['empname']) ? $data[0]['empname'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('DocNo : ', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Doc Date : ', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['employeedept']) ? $data[0]['employeedept'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Hired : ', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['datehired']) ? $data[0]['datehired'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Effective Date : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['effdate']) ? $data[0]['effdate'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Status Change : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['statdesc']) ? $data[0]['statdesc'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Description : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['description']) ? $data[0]['description'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('End of Employment : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['resigned']) ? $data[0]['resigned'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Remarks : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['remarks']) ? $data[0]['remarks'] : ''), '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DESCRIPTION', '200', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col('FROM', '200', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col('TO', '200', null, false, $border, 'TB', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TYPE', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['ftype'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['ttype'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LEVEL', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['flevel'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tlevel'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB TITLE', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fromjobtitlename'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tojobtitlename'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYMENT STATUS', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fempstatus'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tempstatus'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RANK', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['frank'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['trank'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB GRADE', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fjobgrade'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tjobgrade'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fromdeptname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['todeptname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LOCATION', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['flocation'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tlocation'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MODE OF PAYMENT', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fpaymode'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tpaymode'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAY GROUP', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fpaygroup'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tpaygroup'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CLASS RATE', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fpayrate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tpayrate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ALLOWANCE', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fallowrate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tallowrate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BASIC SALARY', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fbasicrate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tbasicrate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DIVISION', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fdivname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tdivname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SECTION', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['fsectname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['tsectname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ROLE', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['frole'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['trole'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    
    $str .= '<br/><br/><br/><br/><br/><br/><br/><br/>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Recommended By : ', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '180', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '180', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function rpt_hs_embarkation_layout($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $fontsize12 = "12";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('600');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['empdivision'], '600', null, false, $border, '', 'C', $font, 14, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Attention: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['attentionname'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Position: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['attentionjobtitle'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Vessel: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['tlocation'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['attentiondept'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['dateid'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMBARKATION ORDER ', '600', null, false, $border, '', 'C', $font, 14, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['empname'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Position: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['jobtitle'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Vessel: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['tlocation'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['employeedept'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['dateid'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Description: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['description'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '600', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '600', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('HR Officer', '600', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Approved By :', '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('Conforme :', '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DPA', '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function rpt_hs_disembarkation_layout($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $fontsize12 = "12";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('600');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['empdivision'], '600', null, false, $border, '', 'C', $font, 14, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Attention: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['attentionname'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Position: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['attentionjobtitle'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Vessel: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['flocation'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['attentiondept'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['dateid'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DISEMBARKATION ORDER ', '600', null, false, $border, '', 'C', $font, 14, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['empname'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Position: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['jobtitle'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Vessel: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['flocation'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['employeedept'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['dateid'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Description: ', '100', null, false, $border, '', 'L', $font, $fontsize12, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['description'], '500', null, false, $border, '', 'L', $font, $fontsize12, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '600', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '600', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('HR Officer', '600', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Approved By :', '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('Conforme :', '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DPA', '300', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize12, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

}
