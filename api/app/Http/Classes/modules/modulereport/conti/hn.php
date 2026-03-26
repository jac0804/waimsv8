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
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
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

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
      if($config['params']['dataparams']['print'] == "default"){
        $str = $this->rpt_hn_layout($config, $data);
      }else if($config['params']['dataparams']['print'] == "PDFM") {
        $str = $this->rpt_hn_PDF($config, $data);
      }
      return $str;
  }

  public function report_default_query($filters){
    $trno = $filters['params']['dataid'];
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
      head.ddate, head.htime, head.comments,
      head.hdatetime, head.remarks,
      emp.client as empcode,
      dept.client as dept,
      dept.clientname as deptname,
      head.deptid,
      ir.docno as irno,
      ir.idescription as irdesc,
      chead.code as artcode,
      cdetail.section as sectioncode,
      head.fempid
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
      head.ddate, head.htime, head.comments,
      head.hdatetime, head.remarks,
      emp.client as empcode,
      dept.client as dept,
      dept.clientname as deptname,
      head.deptid,
      ir.docno as irno,
      ir.idescription as irdesc,
      chead.code as artcode,
      cdetail.section as sectioncode,
      head.fempid
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

  public function rpt_hn_PDF($params, $data)
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
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');

    
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(720, 0, "MEMORANDUM", '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(60, 15, "DATE", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": ".(isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']), 'F d, Y') : ''), '', 'L', false);
    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(60, 15, "To", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": ".(isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false);

    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(60, 15, "", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": ".(isset($data[0]['empjob']) ? $data[0]['empjob'] : ''), '', 'L', false);

    
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(60, 15, "Subject", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 15, ": Notice To Explain", '', 'L', false);

    
    PDF::SetLineWidth(2);

    PDF::MultiCell(720, 15, "", 'T', 'L', false);
    PDF::SetLineWidth(1);

    
    
    PDF::MultiCell(0, 0, "\n\n\n");

    

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 15, "Please submit a written explanation within 5 days from the date this Notice is served upon you why no", '', 'L', false);
    PDF::MultiCell(720, 15, "disciplinary action should be imposed on your violation of Keywest Shipping and Line Corp. various", '', 'L', false);
    PDF::MultiCell(720, 15, "Code of Conduct.", '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 15, "This disciplinary action emanates from the following violations stated below you committed as reported", '', 'L', false);
    PDF::MultiCell(720, 15, "by the HR Officer and to the Management.", '', 'L', false);

    
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(60, 15, (isset($data[0]['sectioncode']) ? $data[0]['sectioncode'] : ''), '', 'L', false,0);
    PDF::MultiCell(660, 15, (isset($data[0]['sectionname']) ? $data[0]['sectionname'] : ''), '', 'L', false);

    
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 0, "Kindly give this matter with utmost priority and importance.", '', 'L',false,0,'','500');

  
    
    PDF::MultiCell(0, 0, "\n\n");
    
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 15, "Prepared By:", '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, $prepared, 'B', 'L', false,0);
    PDF::MultiCell(300, 15, '', '', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(120, 15, 'HR Manager', '', 'L', false,0);
    PDF::MultiCell(300, 15, '', '', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);

    
    PDF::MultiCell(0, 0, "\n\n");
    
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 15, "Received By:", '', 'R', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(300, 15, 'Noted By:', '', 'L', false,0);
    PDF::MultiCell(300, 15, '', '', 'C', false,0);
    PDF::MultiCell(120, 15, $received, 'B', 'R', false);

    
    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(120, 15, $approved, 'B', 'L', false,0);
    PDF::MultiCell(300, 15, '', '', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(120, 15, 'President', '', 'L', false,0);
    PDF::MultiCell(300, 15, '', '', 'C', false,0);
    PDF::MultiCell(300, 15, '', '', 'L', false);
    
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_hn_layout($params, $data){
 
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

    $str .= $this->reporter->col('NOTICE TO EXPLAIN', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->col((isset($data[0]['empjob']) ? $data[0]['empjob'] : ''), '650', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
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
    $str .= $this->reporter->col('EXPLANATION : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['explanation'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMMENTS : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['comments'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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
