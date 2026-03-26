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
use NumberFormatter;

class leavesetup
{

  private $modulename = "Leave Setup";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "select ls.empid, ls.docno, date(ls.dateid) as headdateid, ls.remarks,
      concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
      date(lt.dateid) as leavedate, lt.daytype, lt.batch,
      pac.code as acno, pac.codename as acnoname, ls.bal, ls.days,
      date(ls.prdstart) as prdstart, date(ls.prdend) as prdend,
      lt.adays as leavehours, lt.remarks as leaverem,
      case 
        when lt.status = 'A' then 'APPROVED'
        when lt.status = 'E' then 'ENTRY'
        when lt.status = 'O' then 'ON-HOLD'
        when lt.status = 'P' then 'PROCESSED'
      end as leavestatus
      from leavesetup as ls
      left join leavetrans as lt on ls.trno = lt.trno
      left join employee as e on ls.empid = e.empid
      left join paccount as pac on pac.line = ls.acnoid
      where ls.trno = $trno
      order by lt.dateid";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if($params['params']['dataparams']['print'] == "default") {
      return $this->rpt_leavesetup_masterfile_layout($params, $data);
    } else if($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_leavesetup_PDF($params, $data);
    }
  }

  public function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

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
    $str .= $this->reporter->col('LEAVE SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee Name: ' . $data[0]['empname'], '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document No.', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Account No.', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Account Name', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Entitled (Hours)', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Remaining (Hours)', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Period Start', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Period End', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function rpt_leavesetup_masterfile_layout($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    // for($i=0;$i<count($data);$i++){
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($data[0]['docno'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['acno'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['acnoname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($data[0]['days'], $decimal), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col(number_format($data[0]['bal'], $decimal), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['prdstart'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['prdend'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();

    // if($this->reporter->linecounter==$page){
    //     $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->page_break();
    //     $str .= $this->rpt_default_header($data,$filters);
    //     $str .= $this->reporter->printline();
    //     $page=$page + $count;
    //     }
    // }   

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Applied Leaves: ', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date Applied', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('No of Hours', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Remarks', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    // for ($i=0; $i < count($data); $i++) { 
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['leavedate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($data[0]['leavehours'], $decimal), '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['leaverem'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($data[0]['leavestatus'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    // }
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
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  public function default_leavesetup_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($center) . ' ' . 'RSSC', '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Employee Name: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(600, 0, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(100, 0, "Document No.", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Account No.", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Account Name", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Entitled (Hours)", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Remaining (Hours)", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Period Start", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Period End", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_leavesetup_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_leavesetup_header_PDF($params, $data);

    // for ($i = 0; $i < count($data); $i++) {
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(100, 0, $data[0]['docno'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[0]['acno'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[0]['acnoname'], '', 'L', 0, 0, '', '');
      PDF::MultiCell(100, 0, number_format($data[0]['days'], $decimalqty), '', 'R', 0, 0, '', '');
      PDF::MultiCell(100, 0, number_format($data[0]['bal'], $decimalqty), '', 'R', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[0]['prdstart'], '', 'C', 0, 0, '', '');
      PDF::MultiCell(100, 0, $data[0]['prdend'], '', 'C', 0, 0, '', '');
      PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '');
    //   if (intVal($i) + 1 == $page) {
    //     $this->default_leavesetup_header_PDF($params, $data);
    //     $page += $count;
    //   }
    // }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(700, 0, "", "T");
    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, 'Applied Leave: ', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Date Applied', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'No of Hours', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Remarks', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Status', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, $data[0]['leavedate'], '', 'L', false, 0);
    PDF::MultiCell(100, 0, number_format($data[0]['leavehours'], $decimalqty), '', 'L', false, 0);
    PDF::MultiCell(100, 0, $data[0]['leaverem'], '', 'L', false, 0);
    PDF::MultiCell(100, 0, $data[0]['leavestatus'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    // PDF::MultiCell(253, 0, 'Prepared By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Approved By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Received By: ', '', 'C');


    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    //PDF::AddPage();
    //$b = 62;
    //for ($i = 0; $i < 1000; $i++) {
    //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //  PDF::MultiCell(0, 0, "\n");
    //  if($i==$b){
    //    PDF::AddPage();
    //    $b = $b + 62;
    //  }
    //}

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
