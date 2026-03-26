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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ep
{

  private $modulename = "Employee Record";
  private $reportheader;
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
    $this->reportheader = new reportheader;
  }

  public function createreportfilter($config)
  {

    $fields = ['radioprint', 'radiorepamountformat', 'prepared', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radiorepamountformat.options', [
      ['label' => 'Certificate of Employment', 'value' => '0', 'color' => 'orange'],
    ]);
    data_set($col1, 'radiorepamountformat.label', 'Layout');
    data_set($col1, 'prepared.label', 'Requested by:');
    data_set($col1, 'prepared.type', 'hidden');


    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '0' as amountformat";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config, $trno)
  {
    $format = $config['params']['dataparams']['amountformat'];

    switch ($format) {
      default:
        $query = "select concat(e.emplast,', ', e.empfirst,' ', e.empmiddle) as fullname,e.emplast, 
        e.empfirst, e.empmiddle, e.gender,date(e.hired) as hired,
        date(e.resigned) as resigned,e.jobid,j.jobtitle,'' as cases ,
        date(now()) as dateid,d.divcode,e.resignedtype,dept.deptname
        from employee as e
        left join jobthead as j on j.line=e.jobid 
        left join rolesetup as r on r.line = e.roleid
        left join division as d on d.divid = e.divid 
        left join department as dept on dept.deptid = e.deptid
        where empid='$trno'";


        // $qry = $qryselect . " from " . $this->head . " 
        // left join " . $this->headOther . " on " . $this->headOther . ".empid = " . $this->head . ".clientid 
        // left join " . $this->contact . " on " . $this->contact . ".empid=" . $this->head . ".clientid 

        // left join rolesetup as role on role.line = " . $this->headOther . ".roleid
        // left join division as `div` on `div`.divid = role.divid 

        break;
    }
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    $format = $params['params']['dataparams']['amountformat'];

    switch ($format) {

      default:
        return $this->certificate_of_employment_PDF($params, $data);
        break;
    }
  }

  // public function default_ep_header_PDF($params, $data)
  // {
  //   $companyid = $params['params']['companyid'];
  //   $amtformat = $params['params']['dataparams']['amountformat'];
  //   $center = $params['params']['center'];
  //   $username = $params['params']['user'];
  //   //$width = 800; $height = 1000;

  //   $qry = "select name, address, tel, code from center where code = '" . $center . "'";
  //   $headerdata = $this->coreFunctions->opentable($qry);
  //   $current_timestamp = $this->othersClass->getCurrentTimeStamp();

  //   $font = "";
  //   $fontbold = "";
  //   $fontsize = 11;
  //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
  //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
  //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
  //   }

  //   //$width = PDF::pixelsToUnits($width);
  //   //$height = PDF::pixelsToUnits($height);
  //   PDF::SetTitle($this->modulename);
  //   PDF::SetAuthor('Solutionbase Corp.');
  //   PDF::SetCreator('Solutionbase Corp.');
  //   PDF::SetSubject($this->modulename . ' Module Report');
  //   PDF::setPageUnit('px');
  //   PDF::AddPage('p', [800, 1000]);
  //   PDF::SetMargins(30, 30);

  //   $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
  //   PDF::SetFont($font, '', 9);
  //   PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
  //   PDF::SetFont($fontbold, '', 14);
  //   PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
  //   PDF::SetFont($fontbold, '', 13);
  //   PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
  //   PDF::MultiCell(0, 0, "\n");

  //   // SetFont(family, style, size)
  //   // MultiCell(width, height, txt, border, align, x, y)
  //   // write2DBarcode(code, type, x, y, width, height, style, align)

  //   // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
  //   PDF::SetFont($fontbold, '', 18);
  //   PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
  //   PDF::SetFont($font, '', 10);
  //   PDF::MultiCell(100, 0, "", '', 'L', false);

  //   PDF::MultiCell(0, 0, "\n");

  //   PDF::SetFont($fontbold, '', 18);
  //   PDF::MultiCell(500, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(100, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($font, '', 10);
  //   PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

  //   // PDF::SetFont($font, '', $fontsize);
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(80, 20, "Supplier : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

  //   // PDF::MultiCell(0, 0, "\n");

  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

  //   PDF::MultiCell(0, 0, "\n\n");

  //   PDF::SetFont($font, '', 5);
  //   PDF::MultiCell(735, 0, '', 'T');

  //   PDF::SetFont($font, 'B', 11);


  //   if ($amtformat == '0') {
  //     PDF::MultiCell(70, 0, "BARCODE", '', 'C', false, 0);
  //     PDF::MultiCell(50, 0, "QTY", '', 'R', false, 0);
  //     PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
  //     PDF::MultiCell(60, 0, "PCS / PACK", '', 'R', false, 0);
  //     PDF::MultiCell(70, 0, "TOTAL PCS", '', 'R', false, 0);
  //     PDF::MultiCell(10, 0, "", '', 'L', false, 0);
  //     PDF::MultiCell(165, 0, "DESCRIPTION", '', 'L', false, 0);
  //     PDF::MultiCell(70, 0, "NOTES", '', 'L', false, 0);
  //     PDF::MultiCell(70, 0, "UNIT PRICE", '', 'R', false, 0);
  //     PDF::MultiCell(50, 0, "(+/-) %", '', 'R', false, 0);
  //     PDF::MultiCell(70, 0, "TOTAL", '', 'R', false);
  //   } else {
  //     PDF::MultiCell(70, 0, "BARCODE", '', 'C', false, 0);
  //     PDF::MultiCell(80, 0, "QTY", '', 'R', false, 0);
  //     PDF::MultiCell(80, 0, "UNIT", '', 'C', false, 0);
  //     PDF::MultiCell(70, 0, "PCS / PACK", '', 'R', false, 0);
  //     PDF::MultiCell(90, 0, "TOTAL PCS", '', 'R', false, 0);
  //     PDF::MultiCell(10, 0, "", '', 'L', false, 0);
  //     PDF::MultiCell(245, 0, "DESCRIPTION", '', 'L', false, 0);
  //     PDF::MultiCell(90, 0, "NOTES", '', 'L', false);
  //   }

  //   PDF::SetFont($font, '', 5);
  //   PDF::MultiCell(735, 20, '', 'B');
  // }

  // public function default_ep_PDF($params, $data)
  // {
  //   $companyid = $params['params']['companyid'];
  //   $amtformat = $params['params']['dataparams']['amountformat'];
  //   $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
  //   $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
  //   $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
  //   $center = $params['params']['center'];
  //   $username = $params['params']['user'];
  //   $count = $page = 35;
  //   $totalext = 0;

  //   $font = "";
  //   $fontbold = "";
  //   $border = "1px solid ";
  //   $fontsize = "11";
  //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
  //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
  //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
  //   }
  //   $this->default_ep_header_PDF($params, $data);

  //   PDF::SetFont($font, '', 5);
  //   PDF::MultiCell(800, 0, '', '');

  //   $countarr = 0;

  //   if (!empty($data)) {
  //     for ($i = 0; $i < count($data); $i++) {
  //       $maxrow = 1;
  //       $barcode = $data[$i]['barcode'];
  //       $itemname = $data[$i]['itemname'];
  //       $qty = number_format($data[$i]['qty'], 2);
  //       $factor = number_format($data[$i]['factor'], 2);
  //       $iss = number_format(($data[$i]['qty'] * $data[$i]['factor']), 2);
  //       $uom = $data[$i]['uom'];
  //       $amt = number_format($data[$i]['gross'], 2);
  //       $disc = $data[$i]['disc'];
  //       $ext = number_format($data[$i]['ext'], 2);
  //       $notes = $data[$i]['notes'];

  //       if ($amtformat == '0') {
  //         $arr_barcode = $this->reporter->fixcolumn([$barcode], '9', 0);
  //         $arr_itemname = $this->reporter->fixcolumn([$itemname], '24', 0);
  //         $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
  //         $arr_factor = $this->reporter->fixcolumn([$factor], '10', 0);
  //         $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
  //         $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
  //         $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
  //         $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
  //         $arr_ext = $this->reporter->fixcolumn([$ext], '10', 0);
  //         $arr_notes = $this->reporter->fixcolumn([$notes], '10', 0);

  //         $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_factor, $arr_iss, $arr_uom, $arr_amt, $arr_disc, $arr_ext, $arr_notes]);
  //       } else {
  //         $arr_barcode = $this->reporter->fixcolumn([$barcode], '9', 0);
  //         $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
  //         $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
  //         $arr_factor = $this->reporter->fixcolumn([$factor], '10', 0);
  //         $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
  //         $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);
  //         $arr_notes = $this->reporter->fixcolumn([$notes], '18', 0);

  //         $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_factor, $arr_iss, $arr_uom, $arr_notes]);
  //       }

  //       for ($r = 0; $r < $maxrow; $r++) {

  //         PDF::SetFont($font, '', $fontsize);

  //         if ($amtformat == '0') {
  //           PDF::MultiCell(70, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(60, 15, ' ' . (isset($arr_factor[$r]) ? $arr_factor[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(70, 15, ' ' . (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(165, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(70, 15, ' ' . (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(70, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(50, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(70, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
  //         } else {
  //           PDF::MultiCell(70, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(80, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(70, 15, ' ' . (isset($arr_factor[$r]) ? $arr_factor[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(90, 15, ' ' . (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(245, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
  //           PDF::MultiCell(90, 15, ' ' . (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
  //         }



  //         if (PDF::getY() > 900) {
  //           $this->default_ep_header_PDF($params, $data);
  //         }
  //       }
  //       $totalext += $data[$i]['ext'];
  //     }

  //     PDF::SetFont($font, '', 5);
  //     PDF::MultiCell(735, 0, '', 'B');

  //     PDF::SetFont($font, '', 5);
  //     PDF::MultiCell(735, 0, '', '');

  //     if ($amtformat == '0') {
  //       PDF::SetFont($fontbold, '', $fontsize);
  //       PDF::MultiCell(605, 15, 'GRAND TOTAL: ', '', 'R', false, 0);
  //       PDF::MultiCell(130, 0, number_format($totalext, $decimalcurr) . ' ', '', 'R');
  //     }

  //     PDF::SetFont($font, '', $fontsize);

  //     PDF::MultiCell(0, 0, "\n\n\n");


  //     PDF::MultiCell(245, 0, 'Prepared By: ', '', 'L', false, 0);
  //     PDF::MultiCell(245, 0, 'Approved By: ', '', 'L', false, 0);
  //     PDF::MultiCell(245, 0, 'Received By: ', '', 'L');

  //     PDF::MultiCell(0, 0, "\n");

  //     PDF::MultiCell(245, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
  //     PDF::MultiCell(245, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
  //     PDF::MultiCell(245, 0, $params['params']['dataparams']['received'], '', 'L');


  //     return PDF::Output($this->modulename . '.pdf', 'S');
  //   }
  // }

  public function default_coe_PDF($params, $data)
  {

    $font = "";
    $fontbold = "";
    $fontsize = 16;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALUNIMS.OTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMS.OTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMSBOLD.OTF');
    }

    $employee = isset($data[0]) ? $data[0] : [];

    $employee_name = isset($employee['fullname']) ? $employee['fullname'] : '';
    $company_name = 'CDO 2 CYCLES MARKETING CORPORATION';
    $position = isset($employee['jobtitle']) ? $employee['jobtitle'] : '';

    $date_start = isset($employee['hired']) ? date('F d, Y', strtotime($employee['hired'])) : '';
    $date_end = isset($employee['resigned']) ? date('F d, Y', strtotime($employee['resigned'])) : '';

    $employee_surname = isset($employee['emplast']) ? $employee['emplast'] : '';
    $prepared = $params['params']['dataparams']['prepared'];

    $date_issued = isset($employee['dateid']) ? $employee['dateid'] : '';
    $hr_manager_name = 'Ms. Chenny Jean S. Go-Maestrado';
    $hr_manager_position = 'HR / ADMIN MANAGER';


    $record = 'no pending administrative or criminal case';

    $asof = date('jS \d\a\y \o\f F Y', strtotime($date_issued));

    // $center = $params['params']['center'];
    $center = isset($employee['divcode']) ? $employee['divcode'] : '';
    //  var_dump($center);
    $logo = '';
    $width = "720px";
    $height = "100px";
    switch ($center) {
      case '001':
        // $logo = URL::to('/images/cdohris/cdohris_logo.png');
        $logo = '/images/cdohris/cdohris_logo.png';
        $width = "720px";
        $height = "100px";
        $align = "C";
        break;
      case '002':
        // $logo = URL::to('/images/cdohris/mbcpaflogo.png');
        $logo = '/images/cdohris/mbcpaflogo.png';
        break;
      case '003':
        // $logo = URL::to('/images/cdohris/ridefundpaf.png');
        $logo = '/images/cdohris/ridefundpaf.png';
        break;
      case '004':
        // $logo = URL::to('/images/cdohris/motormate.png');
        $logo = '/images/cdohris/samplelogo.png';
        $width = "720px";
        $height = "100px";
        $align = "C";
        break;
    }

    if ($logo != '') {
      PDF::Image(public_path($logo), '40', '10', $width, $height);
    }

    PDF::SetY(150);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 22);

    PDF::setFontSpacing(1.5);
    PDF::MultiCell(0, 0, 'C E R T I F I C A T E   O F   E M P L O Y M E N T', 0, 'C', 0, 1);

    PDF::setFontSpacing(0);


    $html_content = '<br /><br /><strong>To whom it may concern:</strong>';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);


    $fontsize = 17;
    $html_content = '<br />This is to certify that Mr. / Ms. <strong>' . $employee_name . '</strong> was employed with ' . $company_name . ', having the position as <strong>' . $position . '</strong>. The stated name herein has been employed with the company from ' . $date_start . ' up to ' . $date_end . '.';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; text-indent: 50px;">' . $html_content . '</p>', 0, 1);


    $html_content = '<br />It is further certified that as per available records show that she has <strong>' . $record . '</strong> in this company.';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; text-indent: 50px;">' . $html_content . '</p>', 0, 1);

    $html_content = '<br />This certification is issued upon the request of Mr. / Ms. <strong>' . $employee_surname . '</strong> for any legal purpose that may serve.';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; text-indent: 50px;">' . $html_content . '</p>', 0, 1);

    $html_content = '<br />Given this ' . $asof . '.';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; text-indent: 50px;">' . $html_content . '</p>', 0, 1);



    PDF::MultiCell(0, 0, "\n\n\n");


    $html_content = '<br />Certified True and Correct:';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . ';">' . $html_content . '</p>', 0, 1);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);

    PDF::MultiCell(300, 0, $hr_manager_name, 'B', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, $hr_manager_position, 0, 'L', 0, 1);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);

    PDF::MultiCell(720, 0, '', '', 'L', 0, 1, '40', '900', true, 0, false, true, 0, 'B', true);
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: right; line-height: 1.5; font-size:9px; font-family:' . $font . '; "><I>Not valid without company seal</I></p>', 0, 1);
    PDF::MultiCell(720, 0, '', 'T', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);






    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, 'Contact No. (088) 811 - 2844 loc. 127', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(720, 0, 'Email us at cdo2cycles.hrd@gmail.com', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function company_logo($params, $data)
  {

    $font = "";
    $fontbold = "";
    $fontsize = 16;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALUNIMS.OTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMS.OTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMSBOLD.OTF');
    }

    $employee = isset($data[0]) ? $data[0] : [];

    $center = isset($employee['divcode']) ? $employee['divcode'] : '';
    $logo = '';
    // $center = '004';
    $width = "720px";
    $height = "100px";
    switch ($center) {
      case '001':
        // $logo = URL::to('/images/cdohris/cdohris_logo.png');
        $logo = '/images/cdohris/cdohris_logo.png';
        $width = "720px";
        $height = "100px";
        $align = "C";
        break;
      case '002':
        // $logo = URL::to('/images/cdohris/mbcpaflogo.png');
        $logo = '/images/cdohris/mbcpaflogo.png';
        break;
      case '003':
        // $logo = URL::to('/images/cdohris/ridefundpaf.png');
        $logo = '/images/cdohris/ridefundpaf.png';
        break;
      case '004':
        // $logo = URL::to('/images/cdohris/motormate.png');
        $logo = '/images/cdohris/samplelogo.png';
        $width = "720px";
        $height = "100px";
        $align = "C";
        break;
    }

    if ($logo != '') {
      PDF::Image(public_path($logo), '40', '10', $width, $height);
    }

    PDF::SetY(150);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 22);

    PDF::setFontSpacing(1.5);
    PDF::MultiCell(0, 0, 'C E R T I F I C A T E   O F   E M P L O Y M E N T', 0, 'C', 0, 1);

    PDF::setFontSpacing(0);
  }



  public function default_coe_PDF2($params, $data)
  {

    $font = "";
    $fontbold = "";
    $fontsize = 16;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALUNIMS.OTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMS.OTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMSBOLD.OTF');
    }

    $employee = isset($data[0]) ? $data[0] : [];

    $employee_name = isset($employee['fullname']) ? $employee['fullname'] : '';
    $company_name = 'CDO 2 CYCLES MARKETING CORPORATION';
    $position = isset($employee['jobtitle']) ? $employee['jobtitle'] : '';

    $date_start = isset($employee['hired']) ? date('F d, Y', strtotime($employee['hired'])) : '';
    $date_end = isset($employee['resigned']) ? date('F d, Y', strtotime($employee['resigned'])) : '';

    $employee_surname = isset($employee['emplast']) ? $employee['emplast'] : '';
    $prepared = $params['params']['dataparams']['prepared'];

    $date_issued = isset($employee['dateid']) ? $employee['dateid'] : '';
    $hr_manager_name = 'Ms. Chenny Jean S. Go-Maestrado';
    $hr_manager_position = 'HR / ADMIN MANAGER';

    $resignedtype = isset($employee['resignedtype']) ? $employee['resignedtype'] : '';
    $deptname = isset($employee['deptname']) ? $employee['deptname'] : '';


    $record = 'no pending administrative or criminal case';

    $asof = date('jS \d\a\y \o\f F Y', strtotime($date_issued));

    $datenow = date('F d, Y', strtotime($date_issued));

    $dept = '';
    if ($deptname != '') {
      $dept = ' under the ' . $deptname . ' Department.';
    } else {
      $dept = '.';
    }

    $this->company_logo($params, $data);

    $html_content = '<br /><br /><strong>Date:</strong>  ' . $datenow . '';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

    $html_content = '<br /><br />To whom it may concern,';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);

    $fontsize = 17;
    $html_content = '<br />This is to certify that Mr. / Ms. <strong>' . $employee_name . '</strong> was employed with ' . $company_name . ' from ' . $date_start . ' to ' . $date_end . ' , holding the position of <strong>' . $position . '</strong>' . $dept;
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . ';">' . $html_content . '</p>', 0, 1);


    $html_content = '<br /> <strong>Reason for Separation </strong>: ' . $resignedtype . '';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . '; ">' . $html_content . '</p>', 0, 1);


    $html_content = '<br />This certificate is issued upon the employee\'s request for documentation purposes. As per company policy, a Certificate of Employment with Good Moral is not issued in cases of outright resignation.';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . ';">' . $html_content . '</p>', 0, 1);

    $html_content = '<br />For verification or further inquiries, you may contact us at 09177120932 or cdo2cycles.hrd@gmail.com.';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . ';">' . $html_content . '</p>', 0, 1);



    PDF::MultiCell(0, 0, "\n\n");


    $html_content = '<br />Sincerely,';
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: justify; line-height: 1.5; font-size:' . $fontsize . 'px; font-family:' . $font . ';">' . $html_content . '</p>', 0, 1);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);

    PDF::MultiCell(300, 0, $hr_manager_name, 'B', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, $hr_manager_position, 0, 'L', 0, 1);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);

    PDF::MultiCell(720, 0, '', '', 'L', 0, 1, '40', '900', true, 0, false, true, 0, 'B', true);
    PDF::writeHTMLCell(720, 0, '', '', '<p style="text-align: right; line-height: 1.5; font-size:9px; font-family:' . $font . '; "><I>Not valid without company seal</I></p>', 0, 1);
    PDF::MultiCell(720, 0, '', 'T', 'L', 0, 1, '', '', true, 0, false, true, 0, 'B', true);



    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, 'Contact No. (088) 811 - 2844 loc. 127', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(720, 0, 'Email us at cdo2cycles.hrd@gmail.com', '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'B', true);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function certificate_of_employment_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 16;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALUNIMS.OTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMS.OTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALUNIMSBOLD.OTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40, 80);


    $employee = isset($data[0]) ? $data[0] : [];

    $date_end = isset($employee['resigned']) ? date('F d, Y', strtotime($employee['resigned'])) : '';


    $resignedstatus = isset($employee['resignedtype']) ? strtoupper($employee['resignedtype']) : '';

    $testing = 1;
    if ($date_end == '') {
      PDF::SetFont($fontbold, '', 30);
      PDF::MultiCell(0, 0, 'Employee Not Yet Resigned.', 0, 'C', 0, 1, '', '400');
    } else {

      switch ($resignedstatus) {
        case 'RESIGNED':
          return $this->default_coe_PDF($params, $data);
          break;
        default:
          return $this->default_coe_PDF2($params, $data);
          break;
      }


      // if($resignedstatus=='RESIGNED'){
      //   return $this->default_coe_PDF($params,$data);
      // }else{
      //   return $this->resigned_coe_PDF($params,$data);
      // }

    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
