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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class leaveapplication
{

  private $modulename = "Leave Application";
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
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      ['label' => 'Default', 'value' => 'default', 'color' => 'red']
    ]);

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
      date(lt.dateid) as ltdateid, lt.daytype, lt.status, lt.batch,
      date(lt.effectivity) as effectivity, lt.adays,
      ls.days,ls.bal,date(ls.prdstart) as prdstart ,date(ls.prdend) as prdend ,pa.codename,
      (case when lt.status='A' then 'Approved' when lt.status='E' then 'ENTRY' when lt.status='O' then 'On-Hold' when lt.status='P' then 'Processed' else '' end) as status
      from leavesetup as ls
      left join leavetrans as lt on lt.trno = ls.trno
      left join employee as e on ls.empid = e.empid
      left join paccount as pa on pa.line=ls.acnoid
      where ls.trno = $trno
      order by lt.effectivity";



    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->rpt_leaveapplication_masterfile_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_leaveapplication_PDF($params, $data);
    }
  }

  public function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

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
    $str .= $this->reporter->col($data[0]['docno'], '640', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('Date :', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['headdateid']) ? $data[0]['headdateid'] : "", '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Employee Name:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col(isset($data[0]['empname']) ? $data[0]['empname'] : "", '640', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('Leave Type:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['codename'], '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Entitled Hrs:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['days'], '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('Balance Hrs:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['bal'], '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('From:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['prdstart'], '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('To:', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['prdend'], '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Create Date', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Effectivity of Leave', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('No. of Hours', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Status', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function rpt_leaveapplication_masterfile_layout($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
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
        $str .= $this->rpt_default_header($data, $filters);
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
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn


  // public function default_leaveapplication_header_PDF($params, $data)
  // {
  //   $center = $params['params']['center'];
  //   $username = $params['params']['user'];
  //   //$width = 800; $height = 1000;

  //   $qry = "select name,address,tel,code from center where code = '" . $center . "'";
  //   $headerdata = $this->coreFunctions->opentable($qry);
  //   $current_timestamp = $this->othersClass->getCurrentTimeStamp();

  //   $font = "";
  //   $fontbold = "";
  //   $fontsize = 11;
  //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
  //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
  //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
  //   }

  //   PDF::setPageUnit('px');
  //   PDF::AddPage('p', [800, 1000]);
  //   PDF::SetMargins(40, 40);

  //   PDF::Image(public_path('images/cdohris/cdohris_logo.png'), '10', '40', 640, 60);

  //   PDF::SetFont($fontbold, '', 18);
  //   PDF::MultiCell(720, 0, 'LEAVE APPLICATION SLIP', '', 'C', false, 0, '',  '100');

  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(0, 30, "", '', 'L');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(50, 0, "Docno: ", '', 'L', false, 0, '',  '');
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(500, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '',  '');

  // }
  public function default_leaveapplication_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // Company Logo and Header
    PDF::Image(public_path('images/cdohris/cdohris_logo.png'), 40, 40, 720, 60);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 20, 'LEAVE APPLICATION SLIP', 0, 'C', false, 1, 40, 100);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetXY(40, 120);


    // Date Filed
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(440, 10, "", 0, 'L', false, 0);
    PDF::MultiCell(80, 10, "DATE FILED:", 0, 'L', false, 0);
    PDF::MultiCell(150, 10, '', 'B', 'L', false, 0);
    PDF::MultiCell(50, 10, '', '', 'L', false);


    // Availability Section
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(360, 10, "AVAILMENT:", 0, 'L', false, 0);
    PDF::MultiCell(100, 10, "DATE HIRED:", 0, 'L', false, 0);
    PDF::MultiCell(150, 10, "", "B", 'L', false, 0);
    PDF::MultiCell(110, 10, "", "", 'L', false);

    PDF::MultiCell(680, 75, "", 'TBLR', 'L', false, 1, 60, 155);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(15, 15, "", 'TBLR', 'L', false, 0, 70, 165);
    PDF::MultiCell(200, 10, "VACATION LEAVE", 0, 'L', false, 0, 90, 165);


    PDF::MultiCell(15, 15, "", 'TBLR', 'L', false, 0, 70, 185);
    PDF::MultiCell(200, 10, "SICK LEAVE", 0, 'L', false, 0, 90, 185);


    PDF::MultiCell(15, 15, "", 'TBLR', 'L', false, 0, 70, 205);
    PDF::MultiCell(200, 10, "MATERNITY/PATERNITY LEAVE", 0, 'L', false, 0, 90, 205);



    PDF::MultiCell(15, 15, "", 'TBLR', 'L', false, 0, 430, 165);
    PDF::MultiCell(200, 10, "BEREAVEMENT LEAVE", 0, 'L', false, 0, 450, 165);


    PDF::MultiCell(15, 15, "", 'TBLR', 'L', false, 0, 430, 185);
    PDF::MultiCell(200, 10, "OTHERS SPECIFY", 0, 'L', false, 0, 450, 185);
    PDF::MultiCell(150, 10, "", 'B', 'L', false, 0, 540, 185);


    PDF::MultiCell(15, 15, "", 'TBLR', 'L', false, 0, 430, 205);
    PDF::MultiCell(200, 10, "ABSENT", 0, 'L', false, 1, 450, 205);

    // Employee Information
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 10, "NAME:", 0, 'L', false, 0, 40, 255);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 10, '', 'B', 'L', false, 0);
    PDF::MultiCell(80, 10, '', '', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 10, "DEPT. & BRANCH:", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 10, '', 'B', 'L', false, 1);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 10, "", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 10, '', '', 'L', false, 0);
    PDF::MultiCell(60, 10, '', '', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 10, "COMPANY / AFFILIATE:", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(190, 10, '', 'B', 'L', false, 1);


    PDF::MultiCell(720, 30, "", '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 10, "LEAVE/ABSENT from", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(180, 10, '', 'B', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 10, "to", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(210, 10, '', 'B', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 10, "NO. Of Days (", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(40, 10, '', 'B', 'L', false, 0);
    PDF::MultiCell(10, 10, ")", 0, 'L', false, 1);


    PDF::MultiCell(720, 30, "", '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 10, "REASON:", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(310, 10, '', 'B', 'L', false, 0);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 10, "ADDRESS WHILE ON LEAVE/ABSENT", 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 10, '', 'B', 'L', false, 1);


    PDF::MultiCell(720, 50, "", '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize - 1);
    PDF::MultiCell(40, 10, "", '', 'C', false, 0);
    PDF::MultiCell(280, 10, "BRANCH HEAD / OIC / MANAGER", 'T', 'C', false, 0);
    PDF::MultiCell(80, 10, "", '', 'C', false, 0);
    PDF::MultiCell(280, 10, "HR DEPARTMENT", 'T', 'C', false, 0);
    PDF::MultiCell(40, 10, "", '', 'C', false);



    PDF::MultiCell(720, 30, "", '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize - 1);
    PDF::MultiCell(40, 10, "", '', 'C', false, 0);
    PDF::MultiCell(280, 10, "CEO / CFO", 'T', 'C', false, 0);
    PDF::MultiCell(80, 10, "", '', 'C', false, 0);
    PDF::MultiCell(280, 10, "General Manager", 'T', 'C', false, 0);
    PDF::MultiCell(40, 10, "", '', 'C', false);



    PDF::MultiCell(720, 30, "", '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize - 1);
    PDF::MultiCell(40, 10, "", '', 'C', false, 0);
    PDF::MultiCell(230, 10, "", '', 'C', false, 0);
    PDF::MultiCell(180, 10, "SIGNATURE OF EMPLOYEE", 'T', 'C', false, 0);
    PDF::MultiCell(230, 10, "", '', 'C', false, 0);
    PDF::MultiCell(40, 10, "", '', 'C', false);




    PDF::MultiCell(720, 120, "", '', 'L', false, 1);

    PDF::Ln(5);
  }

  public function default_leaveapplication_PDF($params, $data)
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
    $this->default_leaveapplication_header_PDF($params, $data);




    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $arr_ltdateid = $this->reporter->fixcolumn([$data[$i]['ltdateid']], '16', 0);
      $arr_effectivity = $this->reporter->fixcolumn([$data[$i]['effectivity']], '16', 0);
      $arr_adays = $this->reporter->fixcolumn([$data[$i]['adays']], '16', 0);
      $arr_status = $this->reporter->fixcolumn([$data[$i]['status']], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_ltdateid, $arr_effectivity, $arr_adays, $arr_status]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 0, (isset($arr_ltdateid[$r]) ? $arr_ltdateid[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(150, 0, (isset($arr_effectivity[$r]) ? $arr_effectivity[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(150, 0, (isset($arr_adays[$r]) ? $arr_adays[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(150, 0, (isset($arr_status[$r]) ? $arr_status[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '');
      }
      if (intVal($i) + 1 == $page) {
        $this->default_leaveapplication_header_PDF($params, $data);
        $page += $count;
      }
    }

    // PDF::MultiCell(0, 0, "\n");
    // PDF::MultiCell(700, 0, "", "T");
    // PDF::MultiCell(760, 0, '', 'B');
    // PDF::MultiCell(0, 0, "\n");

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(50, 0, "Remarks: ", '', 'L', false, 0, '',  '');
    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(365, 0, (isset($data[0]['remarks']) ? $data[0]['remarks'] : ''), '', 'L', false, 0, '',  '');

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(560, 0, '', '', 'L');

    // PDF::MultiCell(0, 0, "\n\n\n");


    //PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    //PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    //PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    // PDF::MultiCell(253, 0, 'Prepared By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Approved By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Received By: ', '', 'C');


    // PDF::MultiCell(0, 0, "\n");

    // PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    // PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    // PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

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
