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

class loanapplicationportal
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

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    
    $query = "select ss.trno, ss.docno, date(ss.dateid) as ssdate, ss.remarks,
                concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
                st.batch, date(st.dateid) as stdate, st.db, st.cr, st.ismanual  
                from loanapplication as ss
                left join standardtrans as st on ss.trno = st.line
                left join employee as e on ss.empid = e.empid
                where ss.trno = $trno
                order by ss.dateid";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
    if($config['params']['dataparams']['print'] == "default"){
      $str = $this->rpt_loanportal_layout($config, $data);
    }else{
      $str = $this->rpt_loanportal_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_default_header_PDF($config, $data)
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
    PDF::MultiCell(800, 30, "EARNING AND DEDUCTION SETUP", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "Document # : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(420, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false,0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(80, 0, "Date : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, (isset($data[0]['ssdate']) ? $data[0]['ssdate'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(660, 0, '', '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 20, "EMPLOYEE NAME : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(660, 20, $data[0]['empname'], '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(190, 20, "Batch", '', 'L', false, 0);
    PDF::MultiCell(190, 20, "Date", '', 'C', false, 0);
    PDF::MultiCell(190, 20, "Debit", '', 'R', false, 0);
    PDF::MultiCell(190, 20, "Credit", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

  }

  public function rpt_loanportal_PDF($config, $data)
  {
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
    $this->rpt_default_header_PDF($config, $data);

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $arr_batch = $this->reporter->fixcolumn([$data[$i]['batch']],'16',0);
      $arr_stdate = $this->reporter->fixcolumn([$data[$i]['stdate']],'16',0);
      $arr_debit = $this->reporter->fixcolumn([$data[$i]['db']],'16',0);
      $arr_credit = $this->reporter->fixcolumn([$data[$i]['cr']],'16',0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_batch, $arr_stdate, $arr_debit, $arr_credit]);

      for($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(190, 10, (isset($arr_batch[$r]) ? $arr_batch[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(190, 10, (isset($arr_stdate[$r]) ? $arr_stdate[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(190, 10, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(190, 10, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 1, '', '', true, 0, false, false);
      }

      if (intVal($i) + 1 == $page) {
        $this->rpt_default_header_PDF($config, $data);
        $page += $count;
      }
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 20, "Remarks : ", '', 'L', false,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(660, 20, $data[0]['remarks'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['received'], '', 'L');
   
    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function rpt_default_header($config, $data)
  {

    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EARNING AND DEDUCTION SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col('Docno :', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['docno'], '620', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('Date :', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['ssdate'], '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col('Employee Name:', '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['empname'], '690', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Batch', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Date', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Debit', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Credit', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function rpt_loanportal_layout($config, $data)
  {
    // $data     = $this->report_default_query($config);
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($config, $data);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['batch'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['stdate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['db'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['cr'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($config, $data);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col('Remarks :', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['remarks'], '720', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
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
  } //end fn

  

  

}
