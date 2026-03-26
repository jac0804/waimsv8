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

class leaveapplicationportal
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
    $fields = ['prepared','approved','received', 'print'];

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

  public function report_default_query($config){

    $trno = $config['params']['dataid'];
    $query = "select ls.empid, ls.docno, date(ls.dateid) as headdateid, ls.remarks,
                concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
                date(lt.dateid) as ltdateid, lt.daytype, lt.status, lt.batch,
                date(lt.effectivity) as effectivity, lt.adays,ls.days,ls.bal,date(ls.prdstart) as prdstart,date(ls.prdend) as prdend,p.codename
                from leavesetup as ls
                left join leavetrans as lt on lt.trno = ls.trno
                left join employee as e on ls.empid = e.empid
                left join paccount as p on p.line=ls.acnoid
                where ls.trno = $trno
                order by lt.dateid";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
    if($config['params']['dataparams']['print'] == "default"){
      $str = $this->rpt_leaveportal_layout($config, $data);
    }else{
      $str = $this->rpt_leaveportal_PDF($config, $data);
    }
    return $str;
  }


  private function rpt_default_header_PDF($config,$data)
  {

    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $fontsize = "11";
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

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(800, 30, "LEAVE APPLICATION", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 15, "DOCUMENT # : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(170, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false,0);
    PDF::MultiCell(260, 15, '', '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(80, 15, "DATE : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, (isset($data[0]['headdateid']) ? $data[0]['headdateid'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 15, "Employee Name : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(170, 15, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false,0);
    PDF::MultiCell(260, 15, '', '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(80, 15, "Leave Type : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, (isset($data[0]['codename']) ? $data[0]['codename'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 15, "Entitled Days : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(170, 15, (isset($data[0]['days']) ? $data[0]['days'] : ''), '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(60, 15, 'Balance : ', '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 15, isset($data[0]['bal']) ? $data[0]['bal'] : "", '', 'L', false,0);
    PDF::MultiCell(230, 15, '', '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 15, "From : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(170, 15, (isset($data[0]['prdstart']) ? $data[0]['prdstart'] : ''), '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(60, 15, 'To : ', '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 15, isset($data[0]['prdend']) ? $data[0]['prdend'] : "", '', 'L', false,0);
    PDF::MultiCell(230, 15, '', '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(100, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(660, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(190, 20, "Create Date", '', 'L', false, 0);
    PDF::MultiCell(190, 20, "Effectivity of Leave", '', 'L', false, 0);
    PDF::MultiCell(190, 20, "# of Hours", '', 'L', false, 0);
    PDF::MultiCell(190, 20, "Status", '', 'L', false);
  }

  public function rpt_leaveportal_PDF($config,$data)
  {
    // $data     = $this->report_default_query($config);
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
    $this->rpt_default_header_PDF($config,$data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(100, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(660, 0, "", 'T', 'L', false);

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $arr_ltdateid = $this->reporter->fixcolumn([$data[$i]['ltdateid']],'16',0);
      $arr_effectivity = $this->reporter->fixcolumn([$data[$i]['effectivity']],'16',0);
      $arr_adays = $this->reporter->fixcolumn([$data[$i]['adays']],'16',0);
      $arr_status = $this->reporter->fixcolumn([$data[$i]['status']],'16',0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_ltdateid, $arr_effectivity, $arr_adays, $arr_status]);

      for($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(190, 10, (isset($arr_ltdateid[$r]) ? $arr_ltdateid[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(190, 10, (isset($arr_effectivity[$r]) ? $arr_effectivity[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(190, 10, (isset($arr_adays[$r]) ? $arr_adays[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(190, 10, (isset($arr_status[$r]) ? $arr_status[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
      }
  
      if (intVal($i) + 1 == $page) {
        $this->rpt_default_header_PDF($config,$data);
        $page += $count;
      }

    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(100, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(660, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "Remarks : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(560, 0, $data[0]['remarks'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['received'], '', 'L');
  
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function rpt_default_header($config,$data)
  {

    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LEAVE APPLICATION', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Document #:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['docno']) ? $data[0]['docno'] : "", '640', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('Date :', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['headdateid']) ? $data[0]['headdateid'] : "", '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Employee Name:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['empname']) ? $data[0]['empname'] : "", '640', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('Leave Type :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['codename']) ? $data[0]['codename'] : "", '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Entitled Days:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['days']) ? $data[0]['days'] : "", '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('Balance :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['bal']) ? $data[0]['bal'] : "", '550', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('From:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['prdstart']) ? $data[0]['prdstart'] : "", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('To:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['prdend']) ? $data[0]['prdend'] : "", '550', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable('800');
    //   $str .= $this->reporter->startrow(null,null,false,$border,'','R',$font,'10','','','4px');
    //     $str .= $this->reporter->col('Employee Name:','110',null,false,$border,'','L',$font,$fontsize,'B','','2px');
    //     $str .= $this->reporter->col($data[0]['empname'],'690',null,false,$border,'','L',$font,$fontsize,'','','2px');
    //     $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Create Date', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Effectivity of Leave', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    
    $str .= $this->reporter->col('# of Hours', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Status', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
    $str .= $this->reporter->endrow();
    return $str;
  }
  
  public function rpt_leaveportal_layout($config,$data)
  {
    // $data     = $this->report_default_query($config);
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($config,$data);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['ltdateid'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['effectivity'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      
      $str .= $this->reporter->col($data[$i]['adays'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['status'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($config,$data);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Remarks :', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['remarks']) ? $data[0]['remarks'] : "", '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($config['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  

}
