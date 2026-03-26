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

class hc
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
        $str = $this->rpt_hc_layout($config, $data);
      }else if($config['params']['dataparams']['print'] == "PDFM") {
        $str = $this->rpt_hc_PDF($config, $data);
      }
      return $str;
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query="
    select
      num.trno,
      num.docno,
      head.empid,
      em.client as empcode,
      em.clientname as empname,
      head.deptid,
      d.client as dept,
      d.clientname as deptname,
      date(head.dateid) as dateid, 
      head.hired,
      date(head.lastdate) lastday,
      head.jobtitle,
      head.empheadid,
      cl.client as emphead,
      cl.clientname as empheadname,
      head.cause
      from clearance as head
      left join client as cl on cl.clientid=head.empheadid   
      left join client as em on em.clientid=head.empid
      left join client as d on d.clientid=head.deptid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HC'
      union all
      select
      num.trno,
      num.docno,
      head.empid,
      em.client as empcode,
      em.clientname as empname,
      head.deptid,
      d.client as dept,
      d.clientname as deptname,
      date(head.dateid) as dateid, 
      head.hired,
      date(head.lastdate) lastday,
      head.jobtitle,
      head.empheadid,
      cl.client as emphead,
      cl.clientname as empheadname,
      head.cause
      from hclearance as head
      left join client as cl on cl.clientid=head.empheadid   
      left join client as em on em.clientid=head.empid
      left join client as d on d.clientid=head.deptid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HC'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function rpt_hc_PDF($params, $data)
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
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(520, 0, 'CLEARANCE', '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "DOCUMENT # : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 10, "EMPLOYEE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 10, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 10, "DATE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 10, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 10, "JOB TITLE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 10, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'B', 'L', false);
    
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 10, "DEPARTMENT : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 10, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 10, "IMMEDIATE HEAD : ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(550, 10, (isset($data[0]['empheadname']) ? $data[0]['empheadname'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 10, "CAUSE OF SEPARATION : ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(550, 10, (isset($data[0]['cause']) ? $data[0]['cause'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 10, "LAST DAY OF WORK : ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(550, 10, (isset($data[0]['lastday']) ? $data[0]['lastday'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(266, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $params['params']['dataparams']['received'], '', 'L');

    
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_hc_layout($params, $data)
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
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('CLEARANCE', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->col('IMMEDIATE HEAD : ', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['empheadname'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->col('CAUSE OF SEPERATION : ', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['cause'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->col('LAST DAY OF WORK : ', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['lastday'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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
