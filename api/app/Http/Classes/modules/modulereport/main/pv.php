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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class pv
{

  private $modulename = "Accounts Payable Voucher";
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'checked', 'payor', 'tin', 'position', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.label', 'Print Cash/Check Voucher');
    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'VOUCHER', 'value' => '0', 'color' => 'blue'],
        // ['label' => 'CHECK', 'value'=>'1','color'=>'blue'],
        // ['label' => 'BIR Form 2307', 'value'=>'2','color'=>'blue'],
        ['label' => 'BIR Form 2307 (New)', 'value' => '2', 'color' => 'blue']
      ]
    );
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti
      data_set($col1, 'prepared.readonly', true);
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookupclient');
      data_set($col1, 'prepared.lookupclass', 'prepared');

      data_set($col1, 'approved.readonly', true);
      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookupclient');
      data_set($col1, 'approved.lookupclass', 'approved');

      data_set($col1, 'received.readonly', true);
      data_set($col1, 'received.type', 'lookup');
      data_set($col1, 'received.action', 'lookupclient');
      data_set($col1, 'received.lookupclass', 'received');
    }
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    switch ($config['params']['companyid']) {
      case 26:
        return $this->coreFunctions->opentable(
          "select
          'PDFM' as print,
          'Maricon Rallos' as prepared,
          'Charles Ley' as approved,
          '' as received,
          'Chennie Marcello' as checked,
          '' as payor,
          '' as tin,
          '' as position,
          '0' as reporttype
          "
        );
        break;
      default:
        return $this->coreFunctions->opentable(
          "select 
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received,
          '' as checked,
          '' as payor,
          '' as tin,
          '' as position,
          '0' as reporttype
          "
        );
        break;
    }
  }

  public function reportplotting($config, $data)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');
    if ($config['params']['dataparams']['print'] == "default") {
      switch ($config['params']['dataparams']['reporttype']) {
        case 0: // VOUCHER
          $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
          break;
          // case 1:
          //   $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT2($data,$config);
          //   break;
        case 2:
          $str = $this->rpt_CV_WTAXREPORT($data, $config);
          break;
        case 3:
          $str = $this->rpt_CV_WTAXREPORT_NEW($data, $config);
          break;
      }
    } else if ($config['params']['dataparams']['print'] == "PDFM") {

      switch ($config['params']['dataparams']['reporttype']) {
        case 0: // VOUCHER
          $str = $this->PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
          break;
        case 2:
          $str = $this->PDF_CV_WTAXREPORT_NEW($data, $config);
          break;
        case 3:
          $str = $this->PDF_CV_WTAXREPORT($data, $config);
          break;
      }
    }
    return $str;
  }

  public function PDF_default_header_ccvoucher($params, $data)
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

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    if ($params['params']['companyid'] != 10 && $params['params']['companyid'] != 12) {
      $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    }
    $this->reportheader->getheader($params);

    PDF::MultiCell(0, 0, "\n\n");


    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(300, 25, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 25, "Docno #: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 25, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false);

    PDF::SetFont($fontbold, '',  $fontsize);
    PDF::MultiCell(70, 25, 'PAID TO : ', '', 'L', false, 0);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(350, 25, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '',  $fontsize);
    PDF::MultiCell(175, 25, 'DATE:', '', 'R', false, 0);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(100, 25, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'R', false);

    PDF::SetFont($fontbold, '',  $fontsize);
    PDF::MultiCell(70, 25, 'ADDRESS : ', '', 'L', false, 0);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(350, 25, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '',  $fontsize);
    PDF::MultiCell(175, 25, 'YOURREF :', '', 'R', false, 0);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(100, 25, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'R', false);

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(550, 25, 'PARTICULARS', 'TBL', 'C', false, 0);
    PDF::MultiCell(150, 25, 'AMOUNT', 'TBLR', 'R', false);
  }

  public function PDF_DEFAULT_CCVOUCHER_LAYOUT1($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_default_header_ccvoucher($params, $data);

    $totaldb = 0;
    $totalcr = 0;
    $remarks = "";

    for ($i = 0; $i < count($data); $i++) {

      $totaldb = $totaldb + $data[$i]['db'];
      $totalcr = $totalcr + $data[$i]['cr'];

      if ($this->reporter->linecounter == $page) {

        PDF::MultiCell(720, 0, "", "T");
        $page = $page + $count;
      }
    }

    // if ($remarks == $data[0]['rem']) {
    //   $remarks = "";
    // } else {
    //   $remarks = $data[0]['rem'];
    //   // Remove all types of line breaks
    //   // $remarks = str_replace(["\r\n", "\r", "\n"], ' ', $remarks);
    // }

    if ($remarks == $data[0]['rem']) {
      $remarks = "";
    } else {
      $remarks = $data[0]['rem'];
    }

    PDF::SetFont($font, '', 11);

    $remarks_height = PDF::getStringHeight(550, $remarks);

    $cell_height = max(75, $remarks_height);

    PDF::MultiCell(550, $cell_height, $remarks, 'TBL', 'L', false, 0);
    PDF::MultiCell(150, $cell_height, number_format($totaldb, 2), 'TBLR', 'R', false, 1);

    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(550, 75, $remarks, 'TBL', 'L', false, 0);
    // PDF::MultiCell(150, 75, number_format($totaldb, 2), 'TBLR', 'R', false);

    PDF::MultiCell(700, 25, '', '', 'R', false);

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(500, 25, 'ACCOUNTING ENTRY', 'TBL', 'C', false, 0);
    PDF::MultiCell(100, 25, 'DEBIT', 'TBL', 'R', false, 0);
    PDF::MultiCell(100, 25, 'CREDIT', 'TBLR', 'R', false);


    $totaldb = 0;
    $totalcr = 0;
    $acname = "";
    $drem = "";

    for ($i = 0; $i < count($data); $i++) {
      $debit = number_format($data[$i]['db'], 2);
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data[$i]['cr'], 2);
      $credit = $credit < 0 ? '-' : $credit;
      if ($acname == $data[$i]['acnoname']) {
        $acname = "";
      } else {
        $acname = $data[$i]['acnoname'];
      }

      if ($drem == $data[$i]['drem']) {
        $drem = "";
      } else {
        $drem = $data[$i]['drem'] != '' ? ' - ' . $data[$i]['drem'] : '';
      }
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(500, 25, $acname . $drem . ' ' . $data[$i]['project'], 'TBL', 'L', false, 0);

      PDF::MultiCell(100, 25, $debit, 'TBL', 'R', false, 0);
      PDF::MultiCell(100, 25, $credit, 'TBLR', 'R', false);
    }


    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');


    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function PDF_CV_WTAXREPORT($data, $params)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $border = '2px solid';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1400]);
    PDF::SetMargins(10, 10);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(680, 0, '', 'LT', 'R', false, 0);
    PDF::MultiCell(100, 0, 'BIR FORM No.', 'TR', 'C', false);

    PDF::MultiCell(780, 0, '', 'LR', 'C', false);
    PDF::MultiCell(780, 0, '', 'LR', 'C', false);



    //1st row
    PDF::Image(public_path() . '/images/afti/birlogo.png', '15', '', 90, 90, '', '', '', false, 300, '', false, false, 'TBLR');
    PDF::MultiCell(780, 0, '', 'LR', 'C', false);
    PDF::MultiCell(100, 0, '', 'L', 'R', false, 0);
    PDF::MultiCell(150, 0, 'Republika ng' . "\n" . 'Pilipinas' . "\n" . 'Kagawaran ng' . "\n" . 'Pananalapi', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(400, 0, 'Certificate of Creditable Tax Withheld', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 22);
    PDF::MultiCell(105, 0, '2307', '', 'R', false, 0);
    PDF::MultiCell(25, 0, '', 'R', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 0, '', 'LR', 'C', false, 1, 10, 65);

    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(150, 0, '', 'L', 'R', false, 0);
    PDF::MultiCell(400, 0, 'At Source', '', 'C', false, 0);
    PDF::MultiCell(230, 0, '', 'R', 'C', false);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 0, '', 'LR', 'C', false, 1, 10, 75);
    PDF::MultiCell(780, 0, '', 'LR', 'C', false);
    PDF::MultiCell(780, 0, '', 'LR', 'C', false);
    PDF::MultiCell(780, 0, '', 'LR', 'C', false);
    // PDF::MultiCell(780, 0, 'x', 'array(LRTB=>array("width" => 10))', 'C',false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(705, 0, '', 'L', 'R', false, 0);
    PDF::MultiCell(75, 0, 'September', 'R', 'L', false);
    PDF::MultiCell(705, 0, '', 'L', 'R', false, 0);
    PDF::MultiCell(75, 0, '2005 (ENCS)', 'R', 'L', false);

    //2nd row
    // PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 9);
    // PDF::MultiCell(100, 10, '', 'T', '',false);
    PDF::MultiCell(780, 10, '', 'T', '', false);
    PDF::MultiCell(780, 10, '', 'T', '', false, 1, 10, 143);
    PDF::MultiCell(780, 10, '', 'T', '', false, 1, 10, 144);
    PDF::MultiCell(780, 10, '', 'LRT', '', false, 1, 10, 145);
    // PDF::MultiCell(80, 10, '', 'T', '',false);

    //3rd row -> 1 for the period 40, 120, 70, 10, 10, 10, 270, 10, 10, 10, 340
    $d1 = '';
    $m1 = '';
    $y1 = '';

    $d2 = '';
    $m2 = '';
    $y2 = '';

    $month = "";
    $year = "";

    $trno = $params['params']['dataid'];
    if ($data['head'][0]['month'] == "" || $data['head'][0]['yr'] == "") {
      $mmyy = $this->coreFunctions->opentable("select month, right(yr,2) as yr from (select month(dateid) as month, year(dateid) as yr from lahead
        where doc= 'PV' and trno = $trno
        union all
        select month(dateid) as month, year(dateid) as year from glhead
        where doc = 'PV' and trno = $trno) as a");
      $month = $mmyy[0]->month;
      $year = $mmyy[0]->yr;
    } else {
      $month = $data['head'][0]['month'];
      $year = $data['head'][0]['yr'];
    }

    switch ($month) {
      case '1':
      case '2':
      case '3':
        $d1 = '01';
        $m1 = '01';
        $y1 = $year;

        $d2 = '03';
        $m2 = '31';
        $y2 = $year;
        break;

      case '4':
      case '5':
      case '6':
        $d1 = '04';
        $m1 = '01';
        $y1 = $year;

        $d2 = '06';
        $m2 = '30';
        $y2 = $year;
        break;

      case '7':
      case '8':
      case '9':
        $d1 = '07';
        $m1 = '01';
        $y1 = $year;

        $d2 = '09';
        $m2 = '30';
        $y2 = $year;
        break;

      default:
        $d1 = '10';
        $m1 = '01';
        $y1 = $year;

        $d2 = '12';
        $m2 = '31';
        $y2 = $year;
        break;
    }
    // PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 16);
    PDF::MultiCell(50, 10, '', 'L', 'C', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false, 0);

    PDF::MultiCell(30, 15, '', 'TL', 'C', false, 0);
    PDF::MultiCell(30, 15, '', 'TL', 'C', false, 0);
    PDF::MultiCell(30, 15, '', 'TLR', 'C', false, 0);

    PDF::MultiCell(170, 10, '', '', '', false, 0);

    PDF::MultiCell(30, 15, '', 'TL', 'C', false, 0);
    PDF::MultiCell(30, 15, '', 'TL', 'C', false, 0);
    PDF::MultiCell(30, 15, '', 'TLR', 'C', false, 0);
    PDF::MultiCell(280, 10, '', 'R', '', false);


    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 10, '1', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 10, 'For the Period', '', 'L', false, 0);

    PDF::MultiCell(30, 25, $d1, 'LB', 'C', false, 0);
    PDF::MultiCell(30, 25, $m1, 'LB', 'C', false, 0);
    PDF::MultiCell(30, 25, $y1, 'LBR', 'C', false, 0);

    PDF::MultiCell(170, 10, '', '', '', false, 0);

    PDF::MultiCell(30, 25, $d2, 'LB', 'C', false, 0);
    PDF::MultiCell(30, 25, $m2, 'LB', 'C', false, 0);
    PDF::MultiCell(30, 25, $y2, 'LBR', 'C', false, 0);
    PDF::MultiCell(280, 10, '', 'R', '', false);


    //4th row -> from

    // PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(780, 18, '', 'LR', '', false);

    // PDF::MultiCell(50, 10, '', 'TBLR', 'C','',false,0);
    PDF::MultiCell(130, 10, 'From', 'L', 'R', false, 0);
    PDF::MultiCell(100, 10, '', '', '', false, 0);
    PDF::MultiCell(140, 10, '(MM/DD/YY)', '', 'L', false, 0);
    PDF::MultiCell(130, 10, 'To', '', 'L', false, 0);
    PDF::MultiCell(140, 10, '(MM/DD/YY)', '', 'L', false, 0);
    PDF::MultiCell(140, 10, '', 'R', '', false);

    PDF::MultiCell(780, 18, '', 'T', '', false, 1, 10, 222);
    PDF::MultiCell(780, 18, '', 'T', '', false, 1, 10, 223);

    //5th row -> part 1
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 18, 'Part I', 'LTRB', 'C', false, 0, 10, 224);
    PDF::MultiCell(730, 18, 'Payee Information', 'LTRB', 'C', false, 1, 60, 224);

    PDF::MultiCell(780, 18, '', 'T', '', false, 1, 10, 241);
    PDF::MultiCell(780, 18, '', 'LRT', '', false, 1, 10, 242);



    //6th row -> blank 
    // PDF::MultiCell(0, 0, "\n");


    //7th row -> 2 tax payer
    // PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 22, '2', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 22, 'Tax Payer', '', 'L', false, 0);
    PDF::MultiCell(220, 20, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'TBLR', 'L', false, 0, 165, 252);
    // PDF::MultiCell(220, 30, '', 'TBLR', 'C',false,0,165,252);
    PDF::MultiCell(405, 30, '', 'R', 'C', false);

    //8th row -> 2 Identification
    // PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(160, 10, 'Identification Number', 'L', 'C', false, 0);
    PDF::MultiCell(620, 10, '', 'R', 'C', false);

    //9th row -> 2 Identification
    // PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(780, 20, '', 'LR', '', false, 1, 10, 290);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 26, '3', 'L', 'C', false, 0, 10, 310);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 26, "Payee's Name", '', 'L', false, 0, 40, 310);
    PDF::MultiCell(610, 20, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'TBLR', 'L', false, 0, 165, 302);
    PDF::MultiCell(15, 30, '', 'R', 'C', false);

    //10th row -> registered name
    PDF::MultiCell(200, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(580, 20, '(Last Name, First Name, Middle Name for Individuals)(Registered Name for Non-Individuals)', 'R', 'L', false);


    //11th row -> 4 registered address
    PDF::MultiCell(780, 20, '', 'LR', '', false, 1, 10, 340);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 25, '4', 'L', 'C', false, 0, 10, 353);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 25, "Registered Address", '', 'L', false, 0, 40, 353);
    PDF::MultiCell(410, 20, (isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), 'TBLR', 'L', false, 0, 165, 348);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 25, '4A', '', 'R', false, 0, 575, 353);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 25, 'Zip Code', '', 'L', false, 0, 605, 353);
    PDF::MultiCell(100, 20, (isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), 'TBLR', 'C', false, 0, 675, 348);
    // PDF::MultiCell(100, 30, '', 'TBLR', 'C',false,0,675,348);
    PDF::MultiCell(15, 30, '', 'R', 'C', false);

    //12th row -> blank 
    PDF::MultiCell(780, 20, '', 'LR', 'C', false, 1, 10, 372);


    //13th row -> 4 foreign address
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 25, '5', 'L', 'C', false, 0, 10, 390);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 25, "Foreign Address", '', 'L', false, 0, 40, 390);
    PDF::MultiCell(410, 20, '', 'TBLR', 'C', false, 0, 165, 385);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 25, '5A', '', 'R', false, 0, 575, 390);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 25, 'Zip Code', '', 'L', false, 0, 605, 390);
    PDF::MultiCell(100, 20, '', 'TBLR', 'C', false, 0, 675, 385);
    PDF::MultiCell(15, 30, '', 'R', 'C', false);

    //14th row -> blank 
    PDF::MultiCell(780, 20, '', 'LBR', 'C', false, 1, 10, 402);


    //15th row -> blank 
    PDF::MultiCell(780, 20, '', 'T', 'C', false, 1, 10, 423);


    //16th row -> blank 
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 20, '', 'LT', 'C', false, 1, 10, 424);
    PDF::MultiCell(730, 20, 'Payor Information', 'RT', 'C', false, 1, 60, 424);


    //17th row -> 6 tax payer
    PDF::MultiCell(780, 20, '', 'T', 'C', false, 1, 10, 444);
    PDF::MultiCell(780, 20, '', 'T', 'C', false, 1, 10, 445);
    PDF::MultiCell(780, 20, '', 'LR', 'C', false, 1, 10, 445);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 22, '6', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 22, 'Tax Payer', '', 'L', false, 0);
    PDF::MultiCell(220, 20, (isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), 'TBLR', 'L', false, 0, 165, 450);
    //PDF::MultiCell(220, 30, '', 'TBLR', 'C',false,0,165,455);
    PDF::MultiCell(405, 30, '', 'R', 'C', false);

    //18th row
    PDF::MultiCell(160, 10, 'Identification Number', 'L', 'C', false, 0);
    PDF::MultiCell(620, 10, '', 'R', 'C', false);

    //19th row
    PDF::MultiCell(780, 20, '', 'LR', '', false, 1, 10, 495);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 26, '7', 'L', 'C', false, 0, 10, 505);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 26, "Payor's Name", '', 'L', false, 0, 40, 505);
    PDF::MultiCell(610, 20, (isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : ''), 'TBLR', 'L', false, 0, 165, 495);
    PDF::MultiCell(15, 30, '', 'R', 'C', false);

    //20th row
    PDF::MultiCell(200, 10, "", 'L', 'L', false, 0);
    PDF::MultiCell(580, 20, '(Last Name, First Name, Middle Name for Individuals)(Registered Name for Non-Individuals)', 'R', 'L', false);

    //21st row
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 25, '8', 'L', 'C', false, 0, 10, 545);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 25, "Registered Address", '', 'L', false, 0, 40, 545);
    PDF::MultiCell(410, 20, (isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), 'TBLR', 'L', false, 0, 165, 545);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 25, '8A', '', 'R', false, 0, 575, 545);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(70, 25, 'Zip Code', '', 'L', false, 0, 605, 545);
    PDF::MultiCell(100, 20, (isset($data['res'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), 'TBLR', 'C', false, 0, 675, 545);
    // PDF::MultiCell(100, 30, '', 'TBLR', 'C',false,0,675,540);
    PDF::MultiCell(15, 30, '', 'R', 'C', false);

    //22nd row -> blank 
    PDF::MultiCell(780, 20, '', 'LBR', '', false, 0, 10, 560);
    PDF::MultiCell(780, 20, '', 'T', '', false, 0, 10, 580);
    PDF::MultiCell(780, 20, '', 'T', '', false, 0, 10, 581);


    //23rd row -> part II
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 20, 'Part II', 'LT', 'C', false, 0, 10, 581);
    PDF::MultiCell(730, 20, 'Details of Monthly Income Payments and Tax Withheld for the Quarter', 'RT', 'C', false, 0, 60, 581);
    PDF::MultiCell(780, 20, '', 'LTR', '', false, 1, 10, 595);

    //24th row -> income payments 
    PDF::MultiCell(200, 20, '', 'LTR', 'C', false, 0, 10, 596);
    PDF::MultiCell(80, 20, '', 'LTR', 'C', false, 0, 210, 596);
    PDF::MultiCell(380, 20, 'AMOUNT OF INCOME PAYMENTS', 'LTR', 'C', false, 0, 290, 596);
    PDF::MultiCell(120, 20, '', 'LTR', 'C', false, 1, 670, 596);


    //25th row -> month header
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 20, 'Income Payments Subject to' . "\n" . ' Expanded Withholding Tax', 'LTR', 'C', false, 0);
    PDF::MultiCell(80, 20, 'ATC', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '1st Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '2nd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '3rd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, 'Total', 'LTR', 'C', false, 0);
    PDF::MultiCell(120, 20, 'Tax Withheld for the' . "\n" . 'Quarter', 'LTR', 'C', false, 1);


    //26th row -> blank 
    PDF::MultiCell(780, 20, '', 'T', '', false);


    //27th row -> line
    PDF::MultiCell(780, 20, '', 'T', '', false, 1, 10, 640);

    PDF::MultiCell(200, 20, '', 'LR', '', false, 0, 10, 640);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0, 210, 640);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 290, 640);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 385, 640);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 480, 640);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 575, 640);

    PDF::MultiCell(120, 20, '', 'LR', 'R', false, 1, 670, 640);


    //28th row -> atc1

    $total = 0;
    $a = -1;
    foreach ($data['detail'] as $key => $value) {
      $a++;
      PDF::MultiCell(200, 20, $data['res'][$a]['ewtdesc'], 'LRB', '', false, 0);
      PDF::MultiCell(80, 20, $key, 'LRB', '', false, 0);

      switch ($data['head'][0]['month']) {
        case '1':
        case '2':
        case '3':
          PDF::MultiCell(95, 20, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          break;
        case '4':
        case '5':
        case '6':
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          break;
        default:
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      PDF::MultiCell(95, 20, $total, 'LRB', 'R', false, 0);
      PDF::MultiCell(120, 20, number_format($data['detail'][$key]['xamt'], 2), 'LRB', 'R', false);
    }

    //29th row -> total
    $totaltax = 0;
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'LR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    foreach ($data['detail'] as $key => $value) {
      $totaltax = $totaltax + $data['detail'][$key]['xamt'];
    }
    PDF::MultiCell(120, 20, number_format($totaltax, 2), 'LR', 'R', false);
    PDF::SetFont($font, '', 9);
    //30th row -> space for total 
    PDF::MultiCell(200, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'LR', 'R', false);


    //31st row -> money payments row
    PDF::MultiCell(200, 10, 'Money Payments Subjects to', 'TLR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 10, 'Withholding of Business Tax', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    PDF::MultiCell(200, 10, '(Government & Private)', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    //32nd row to 37th
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //38th
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //39th
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(780, 20, 'We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by me, and to the best of my knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof.', 'TLR', 'L', false);
    //40th
    PDF::MultiCell(780, 20, '', 'LR', 'R', false);

    //41st Signature Line
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    if ($params['params']['dataparams']['payor'] == '') {
      PDF::MultiCell(395, 20, (isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : ''), 'B', 'C', false, 0);
    } else {
      PDF::MultiCell(395, 20, ucwords($params['params']['dataparams']['payor']), 'B', 'C', false, 0);
    }
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, $params['params']['dataparams']['tin'], 'B', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, ucwords($params['params']['dataparams']['position']), 'B', 'C', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);



    //42nd Signature Line
    PDF::MultiCell(780, 10, '', 'LR', 'R', false);

    //43rd Authorized Signature
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, "Payor/Payor's Authorized Representative/Accredited Tax Agent", '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);



    PDF::MultiCell(165, 20, 'TIN of Signatory', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, 'Title/Position of Signatory', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);







    //44th Authorized Signature
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, "(Signature Over Printed Name)", '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, '', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, '', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);

    //45th row -> blank space after authorized signature 2
    PDF::MultiCell(780, 10, '', 'LR', 'R', false);



    //46th row -> signature line 1
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);

    //47th Signature Line
    PDF::MultiCell(780, 10, '', 'LR', 'R', false);

    //48th row
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, "Tax Agent Accreditation No./Attorney Roll No. (if applicable)", '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, 'Date of Issuance', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, 'Date of Expiry', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);


    //49th row Signature Line
    PDF::MultiCell(780, 10, '', 'LTR', 'R', false);
    PDF::MultiCell(780, 10, '', 'T', 'R', false, 1, 10, 1077);

    //50th row Space after Declaration
    PDF::MultiCell(780, 10, 'Conforme', 'LR', 'L', false, 1, 10, 1100);

    //51st row
    PDF::MultiCell(780, 20, '', 'LR', 'L', false);

    //52nd row
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(110, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(10, 20, '', '', 'R', false, 0);
    PDF::MultiCell(130, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(10, 20, '', '', 'R', false, 0);
    PDF::MultiCell(85, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);

    //53rd row
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, "Payee/Payee's Authorized Representative/Accredited Tax Agent", '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(110, 20, 'TIN of Signatory', '', 'C', false, 0);
    PDF::MultiCell(10, 20, '', '', 'R', false, 0);
    PDF::MultiCell(130, 20, 'Title/Position of Signatory', '', 'C', false, 0);
    PDF::MultiCell(10, 20, '', '', 'R', false, 0);
    PDF::MultiCell(85, 20, 'Date Signed', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);

    //54th row
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, "(Signature Over Printed Name)", '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(110, 20, '', '', 'C', false, 0);
    PDF::MultiCell(10, 20, '', '', 'R', false, 0);
    PDF::MultiCell(130, 20, '', '', 'C', false, 0);
    PDF::MultiCell(10, 20, '', '', 'R', false, 0);
    PDF::MultiCell(85, 20, '', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);

    //55th row
    PDF::MultiCell(780, 20, '', 'LR', 'L', false);

    //56th Row Signature Line
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);

    //57th Signature Line
    PDF::MultiCell(780, 10, '', 'LR', 'R', false);

    //58th Authorized Signature
    PDF::MultiCell(10, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(395, 20, "Tax Agent Accreditation No./Attorney Roll No. (if applicable)", '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, 'Date of Issuance', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', '', 'R', false, 0);
    PDF::MultiCell(165, 20, 'Date of Expiry', '', 'C', false, 0);
    PDF::MultiCell(15, 20, '', 'R', 'R', false);

    //59th Authorized Signature
    PDF::MultiCell(780, 10, '', 'LR', 'R', false);
    PDF::MultiCell(780, 10, '', 'LRB', 'R', false);




    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function PDF_CV_WTAXREPORT_NEW($data, $params)
  {
    ini_set('memory_limit', '-1');
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $border = '2px solid';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(10, 10);



    //Row 1 Logo
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(780, 10, '', '', 'L', false);
    PDF::MultiCell(50, 10, 'For BIR' . "\n" . 'Use Only', '', 'L', false, 0);
    PDF::MultiCell(50, 10, 'BCS/' . "\n" . 'Item:', '', 'L', false, 0);
    PDF::MultiCell(270, 10, '', '', 'L', false, 0);
    PDF::MultiCell(140, 10, 'Republic of the Philippines' . "\n" . 'Department of Finance' . "\n" . 'Bureau of Internal Revenue', '', 'C', false, 0);
    PDF::MultiCell(270, 10, '', '', 'L', false);

    //Row 2
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(120, 55, '', 'TBLR', 'L', false, 0, 10);
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(460, 55, 'Certificate of Credible Tax' . "\n" . 'Withheld at Source', 'TBLR', 'C', false, 0, 130);

    PDF::MultiCell(200, 55, '', 'TBLR', 'L', false, 1, 590);
    $this->reportheader->getheader($params);

    //Row 3
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 10, 'Fill in all applicable spaces. Mark all appropriate boxes with an "X"', 'TBLR', 'L', false, 1, 10, 129);


    //Row 4
    $d1 = '';
    $m1 = '';
    $y1 = '';

    $d2 = '';
    $m2 = '';
    $y2 = '';

    $month = "";
    $year = "";

    $trno = $params['params']['dataid'];
    if ($data['head'][0]['month'] == "" || $data['head'][0]['yr'] == "") {
      $mmyy = $this->coreFunctions->opentable("select month, right(yr,2) as yr from (select month(dateid) as month, year(dateid) as yr from lahead
        where doc= 'PV' and trno = $trno
        union all
        select month(dateid) as month, year(dateid) as year from glhead
        where doc = 'PV' and trno = $trno) as a");
      $month = $mmyy[0]->month;
      $year = $mmyy[0]->yr;
    } else {
      $month = $data['head'][0]['month'];
      $year = $data['head'][0]['yr'];
    }

    switch ($month) {
      case '1':
      case '2':
      case '3':
        $d1 = '01';
        $m1 = '01';
        $y1 = $year;

        $d2 = '03';
        $m2 = '31';
        $y2 = $year;
        break;

      case '4':
      case '5':
      case '6':
        $d1 = '04';
        $m1 = '01';
        $y1 = $year;

        $d2 = '06';
        $m2 = '30';
        $y2 = $year;
        break;

      case '7':
      case '8':
      case '9':
        $d1 = '07';
        $m1 = '01';
        $y1 = $year;

        $d2 = '09';
        $m2 = '30';
        $y2 = $year;
        break;

      default:
        $d1 = '10';
        $m1 = '01';
        $y1 = $year;

        $d2 = '12';
        $m2 = '31';
        $y2 = $year;
        break;
    }

    PDF::SetFont($font, '', 16);
    PDF::MultiCell(780, 10, '', 'LR', '', false, 0, 10, 142);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 10, '1', 'L', 'C', false, 0, 10, 145);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 10, 'For the Period', '', 'L', false, 0);
    PDF::MultiCell(90, 10, '', '', '', false, 0);
    PDF::MultiCell(35, 10, 'From', '', '', false, 0);
    PDF::MultiCell(20, 10, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(25, 15, $d1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $m1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $y1, 'LTBR', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 10, '', '', '', false, 0);
    PDF::MultiCell(25, 15, $d2, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $m2, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $y2, 'LTBR', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
    PDF::MultiCell(95, 10, '', 'R', '', false);

    //Row 5
    PDF::MultiCell(780, 18, '', 'LTBR', '', false, 0, 10, 163);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part I - Payee Information', 'LTBR', 'C', false, 1, 10, 164);

    //Row 6
    PDF::MultiCell(780, 25, '', 'LTBR', '', false, 0);
    PDF::MultiCell(50, 25, '2', '', 'C', false, 0, 10, 185);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
    PDF::MultiCell(520, 18, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'LTBR', 'L', false, 0);
    PDF::MultiCell(10, 25, '', '', 'C', false);

    //Row 7
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '3', 'LT', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Payee's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'TR', 'L', false);

    //Row 8
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 9
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '4', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 15, '4A', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
    PDF::MultiCell(10, 15, '', 'R', 'L', false);

    //Row 10
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(630, 18, (isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, (isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), 'LTRB', 'C', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 11
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '5', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Foreign Address If Applicable", 'R', 'L', false);

    //Row 12
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, "", 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 13
    PDF::MultiCell(780, 18, '', 'LRB', '', false, 1, 10, 295);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part II - Payor Information', 'LTRB', 'C', false);

    //Row 14
    PDF::MultiCell(780, 25, '', 'LTR', '', false, 0);
    PDF::MultiCell(50, 25, '6', '', 'C', false, 0, 10, 335);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
    PDF::MultiCell(520, 18, (isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), 'LTBR', 'L', false, 0);
    PDF::MultiCell(10, 25, '', '', 'C', false);

    //Row 15
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 25, '', 'LR', '', false, 1, 10, 340);
    PDF::MultiCell(50, 15, '7', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Payor's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'R', 'L', false);

    //Row 16
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);

    PDF::MultiCell(720, 18, (isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);


    //Row 17
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '8', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 15, '8A', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
    PDF::MultiCell(10, 15, '', 'R', 'L', false);

    //Row 18
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(630, 18, (isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, (isset($data['head'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), 'LTRB', 'C', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);


    //Row 13
    PDF::MultiCell(780, 1, '', 'LRB', '', false, 1, 10, 425);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part III - Details of Monthly Income Payments and Taxes Withheld', 'LTRB', 'C', false);

    //Row 14
    PDF::MultiCell(200, 20, '', 'LTR', 'C', false, 0, 10, 457);
    PDF::MultiCell(80, 20, '', 'LTR', 'C', false, 0, 210, 457);
    PDF::MultiCell(380, 20, 'AMOUNT OF INCOME PAYMENTS', 'LTR', 'C', false, 0, 290, 457);
    PDF::MultiCell(120, 20, '', 'LTR', 'C', false, 1, 670, 457);

    //Row 15
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 20, 'Income Payments Subject to' . "\n" . ' Expanded Withholding Tax', 'LR', 'C', false, 0);
    PDF::MultiCell(80, 20, 'ATC', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '1st Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '2nd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '3rd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, 'Total', 'LTR', 'C', false, 0);
    PDF::MultiCell(120, 20, 'Tax Withheld for the' . "\n" . 'Quarter', 'LTR', 'C', false, 1);

    //Row 16
    PDF::MultiCell(780, 20, '', 'T', '', false);

    //Row 17
    PDF::MultiCell(780, 20, '', 'T', '', false, 1, 10, 500);

    PDF::MultiCell(200, 10, '', 'LR', '', false, 0, 10, 500);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0, 210);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 290);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 385);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 480);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0, 575);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false, 1, 670);

    //Row 18 ---atc1

    $total = 0;
    $a = -1;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;

    foreach ($data['detail'] as $key => $value) {
      $a++;

      $ewt_height = PDF::GetStringHeight(200, $data['res'][$a]['ewtdesc']);
      $key_height = PDF::GetStringHeight(80, $key);
      $max_height = max($ewt_height, $key_height);

      if ($max_height > 25) {
        $max_height = $max_height + 15;
      }
      PDF::MultiCell(200, $max_height, $data['res'][$a]['ewtdesc'], 'LRB', '', false, 0);
      PDF::MultiCell(80, $max_height, $key, 'LRB', '', false, 0);

      switch ($data['head'][0]['month']) {
          // case '1': case '2': case '3':
        case '1':
        case '4':
        case '7':
        case '10':
          PDF::MultiCell(95, $max_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
          $totalwtx1 +=  $data['detail'][$key]['oamt'];
          break;
          // case '4': case '5': case '6':
        case '2':
        case '5':
        case '8':
        case '11':
          PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $max_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
          $totalwtx2 +=  $data['detail'][$key]['oamt'];
          break;
        default:
          PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $max_height, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, $max_height, number_format($data['detail'][$key]['oamt'], 2), 'LRB', 'R', false, 0);
          $totalwtx3 +=  $data['detail'][$key]['oamt'];
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      PDF::MultiCell(95, $max_height, $total, 'LRB', 'R', false, 0);
      PDF::MultiCell(120, $max_height, number_format($data['detail'][$key]['xamt'], 2), 'LRB', 'R', false);

      $totalwtx += $data['detail'][$key]['oamt'];
    }

    //Row 19 ----total
    $totaltax = 0;
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'LR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, ($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), 'LR', 'R', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx != 0 ? number_format($totalwtx, 2) : ''), 'LR', 'R', false, 0);
    foreach ($data['detail'] as $key => $value) {
      $totaltax = $totaltax + $data['detail'][$key]['xamt'];
    }
    PDF::MultiCell(120, 10, number_format($totaltax, 2), 'LR', 'R', false);
    PDF::SetFont($font, '', 9);

    //Row 20 ---space for total 
    PDF::MultiCell(200, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    //Row 21
    PDF::MultiCell(200, 10, 'Money Payments Subjects to', 'TLR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 10, 'Withholding of Business Tax', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    PDF::MultiCell(200, 10, '(Government & Private)', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    //Row 22
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);


    //Row 23
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //Row 24
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //Row 25
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, number_format($totaltax, 2), 'TLR', 'R', false);

    //Row 26
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 20, 'We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.', 'TLR', 'C', false);

    //Row 27
    PDF::MultiCell(10, 30, '', 'LT', '', false, 0);
    PDF::MultiCell(395, 30, '', 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'T', '', false, 0);
    PDF::MultiCell(175, 30, '', 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'T', '', false, 0);
    PDF::MultiCell(170, 30, '', 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'TR', '', false);

    //Row 28
    if ($params['params']['dataparams']['payor'] == '') {
      $payor = isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : '' . ' / ';
    } else {
      $payor = isset($params['params']['dataparams']['payor']) ? $params['params']['dataparams']['payor'] : '' . ' / ';
    }

    if ($params['params']['dataparams']['tin'] == '') {
      $tin = isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : '';
    } else {
      $tin = $params['params']['dataparams']['tin'];
    }

    if ($params['params']['dataparams']['position'] == '') {
      $position = '';
    } else {
      $position = ' / ' . $params['params']['dataparams']['position'];
    }


    PDF::MultiCell(780, 30, ucwords($payor) . $tin . ucwords($position), 'LTRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(780, 30, 'Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

    //Row 29

    PDF::MultiCell(780, 25, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
    PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
    PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
    PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
    PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);

    //Row 30
    PDF::MultiCell(780, 15, 'CONFORME:', 'LTRB', 'C', false);

    //Row 31
    PDF::MultiCell(780, 30, '', 'LTRB', '', false);

    //Row 32
    PDF::MultiCell(780, 30, 'Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

    //Row 29
    PDF::MultiCell(780, 30, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
    PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
    PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
    PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
    PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];

    switch ($filters['params']['dataparams']['reporttype']) {
      case 2:
      case 3:
        $query = "select * from(
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        client.addr as address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        detail.db, detail.cr, detail.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname



        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        left join coa on coa.acnoid=detail.acnoid
        where head.doc='PV' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
        union all
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, head.docno, client.client, client.clientname,
        client.addr as address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref, detail.postdate,
        detail.db, detail.cr, dclient.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join client as dclient on dclient.clientid=detail.clientid
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        where head.doc='PV' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
        as tbl order by tbl.ewtdesc";
        $this->coreFunctions->LogConsole($query);
        $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        $arrs = [];
        $arrss = [];
        $ewt = '';
        foreach ($result1 as $key => $value) {
          $ewtrateval = floatval($value['ewtrate']) / 100;
          if ($value['db'] == 0) {
            //FOR CR
            if ($value['cr'] < 0) {
              $db = $value['cr'];
            } else {
              $db = floatval($value['cr']) * -1;
            } //end if

            if ($value['isvewt'] == 1) {
              $db = $db / 1.12;
            }

            $ewtamt = $db * $ewtrateval;
          } else {
            //FOR DB
            if ($value['db'] < 0) {
              $db = floatval($value['db']) * -1;
            } else {
              $db = $value['db'];
            } //end if

            if ($value['isvewt'] == 1) {
              $db = $db / 1.12;
            }
            $ewtamt = $db * $ewtrateval;
          } //end if

          if ($ewt != $value['ewtcode']) {
            $arrs[$value['ewtcode']]['oamt'] = $db;
            $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
            $arrs[$value['ewtcode']]['month'] = $value['month'];
          } else {
            array_push($arrss, $arrs);
            $arrs[$value['ewtcode']]['oamt'] = $db;
            $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
            $arrs[$value['ewtcode']]['month'] = $value['month'];
          }

          $ewt = $value['ewtcode'];
        } //end for each

        array_push($arrss, $arrs);
        $keyers = '';
        $finalarrs = [];
        foreach ($arrss as $key => $value) {
          foreach ($value as $key => $y) {
            if ($keyers == '') {
              $keyers = $key;
              $finalarrs[$key]['oamt'] = $y['oamt'];
              $finalarrs[$key]['xamt'] = $y['xamt'];
            } else {
              if ($keyers == $key) {
                $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($y['oamt']);
                $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($y['xamt']);
              } else {
                $finalarrs[$key]['oamt'] = $y['oamt'];
                $finalarrs[$key]['xamt'] = $y['xamt'];
              } //end if
            } //end if
            $finalarrs[$key]['month'] = $y['month'];
          }
        } //end for each
        if (empty($result1)) {
          $returnarr[0]['payee'] = '';
          $returnarr[0]['tin'] = '';
          $returnarr[0]['address'] = '';
          $returnarr[0]['month'] = '';
          $returnarr[0]['yr'] = '';
        } else {
          $returnarr[0]['payee'] = $result1[0]['clientname'];
          $returnarr[0]['tin'] = $result1[0]['tin'];
          $returnarr[0]['payortin'] = $result1[0]['payortin'];
          $returnarr[0]['address'] = $result1[0]['address'];
          $returnarr[0]['month'] = $result1[0]['month'];
          $returnarr[0]['yr'] = $result1[0]['yr'];
          $returnarr[0]['payorcompname'] = $result1[0]['payorcompname'];
          $returnarr[0]['payoraddress'] = $result1[0]['payoraddress'];
          $returnarr[0]['payorzipcode'] = $result1[0]['payorzipcode'];
        }

        $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
        break;

      default:
        $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
          head.rem as hrem,head.rem, detail.rem as drem, head.yourref, head.ourref,
          coa.acno, coa.acnoname, detail.ref, detail.postdate, detail.db, detail.cr,
          detail.client as dclient, detail.checkno, head.project
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno 
          left join client on client.client=head.client
          left join coa on coa.acnoid=detail.acnoid
          where head.doc='pv' and md5(head.trno)='" . md5($trno) . "'
          union all
          select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
          head.rem as hrem,head.rem, detail.rem as drem, head.yourref, head.ourref,
          coa.acno, coa.acnoname, detail.ref, detail.postdate, detail.db, detail.cr,
          dclient.client as dclient, detail.checkno, head.project
          from glhead as head 
          left join gldetail as detail on detail.trno=head.trno 
          left join client on client.clientid=head.clientid
          left join coa on coa.acnoid=detail.acnoid left join client as dclient on dclient.clientid=detail.clientid
          where head.doc='pv' and md5(head.trno)='" . md5($trno) . "'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        break;
    } // end switch
    return $result;
  }

  public function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCOUNTS PAYABLE VOUCHER', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAYEE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '480', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REFERENCE # :', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTES : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '720', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CHECK DETAILS', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REMARKS', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function rpt_default_header_ccvoucher($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $prepared = $filters['params']['dataparams']['prepared'];
    $received = $filters['params']['dataparams']['received'];
    $approved = $filters['params']['dataparams']['approved'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    // $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTS PAYABLE VOUCHER', '400', null, false, '', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('# :', '251', null, false, '1px  ', '', 'R', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '149', null, false, '', '', 'R', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAID&nbspTO&nbsp:', '70', null, false, '1px  ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '530', null, false, '1px  ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE :', '100', null, false, '', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS&nbsp:', '30', null, false, '1px  ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '405', null, false, '', '', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('YOURREF&nbsp:', '10', null, false, '', '', 'R', 'Century Gothic', '12', 'B', '', '4px');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '120', null, false, '1px  ', 'B', 'R', 'Century Gothic', '12', '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('P A R T I C U L A R S', '650', null, false, '1px solid ', 'LRTB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('AMOUNT', '150', null, false, '1px solid ', 'LRTB', 'R', 'Century Gothic', '12', 'B', '', '3px');
    return $str;
  }

  public function rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $prepared = $filters['params']['dataparams']['prepared'];
    $received = $filters['params']['dataparams']['received'];
    $approved = $filters['params']['dataparams']['approved'];


    $str = '';
    $count = 60;
    $page = 58;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header_ccvoucher($data, $filters);
    $totaldb = 0;
    $totalcr = 0;
    $remarks = "";

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $totaldb = $totaldb + $data[$i]['db'];
      $totalcr = $totalcr + $data[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    if ($remarks == $data[0]['rem']) {
      $remarks = "";
    } else {
      $remarks = $data[0]['rem'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($remarks, '650', null, false, '1px solid ', 'LRBT', 'L', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col(number_format($totaldb, 2), '150', null, false, '1px solid ', 'LRBT', 'R', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    // $str .= '<br/><br/>';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTING ENTRY', '500', null, false, '1px solid ', 'LTB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', 'RTB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'LRTB', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'LRTB', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $totaldb = 0;
    $totalcr = 0;
    $acname = "";
    for ($i = 0; $i < count($data); $i++) {
      $debit = number_format($data[$i]['db'], 2);
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data[$i]['cr'], 2);
      $credit = $credit < 0 ? '-' : $credit;
      if ($acname == $data[$i]['acnoname']) {
        $acname = "";
      } else {
        $acname = $data[$i]['acnoname'];
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($acname, '500', null, false, '1px solid ', 'L', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['project'], '100', null, false, '1px solid ', 'R', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($debit, '100', null, false, '1px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($credit, '100', null, false, '1px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '3px');
    }

    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('', '500', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    // $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '215', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '320', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function rpt_DEFAULT_CCVOUCHER_LAYOUT2($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $trno = $filters['params']['dataid'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';

    $qry = "select DATE_FORMAT(left(postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = '$trno' and left(coa.alias,2) = 'AP' group by detail.checkno,coa.acno
    UNION ALL
    select DATE_FORMAT(left(postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = '$trno'
    and left(coa.alias,2) = 'AP' group by detail.checkno,coa.acno";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


    for ($i = 0; $i < count($data2); $i++) {
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
      }
      $cc = $data2[$i]['cr'];
      $cdate = $data2[$i]['postdate'];

      $str .= '<div style="margin-top:-2px;letter-spacing: 3px;">';
      $str .= $this->reporter->beginreport('900');


      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col('', '670', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col(('' . isset($cdate) ? $cdate : ''), '180', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col($data[0]['clientname'], '720', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col((isset($cc) ? number_format($cc, $decimal) : ''), '150', null, false, '1px solid ', '', 'C', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('920');
      $dd = number_format((float)$cc, 2, '.', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col($this->ftNumberToWordsConverter($dd) . ' ONLY', '900', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();
      $str .= '</div>';
      $this->reporter->linecounter = 30;
    }
    return $str;
  } //end fn

  public function rpt_CV_WTAXREPORT($data, $filters)
  {

    $str = '';
    $count = 60;
    $page = 58;

    $birlogo = URL::to('fimages/reports/birlogo.PNG');

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    //1st row


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="80px" height ="80px">', '95', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('Republika ng Pilipinas Kagawaran ng Pananalapi', '90', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Certificate of Creditable Tax Withheld At Source', '350', null, false, '2px solid ', 'TB', 'C', 'Century Gothic', '20', 'B', '', '');

    $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', 'B', '', '');

    $str .= $this->reporter->col('BIR Form No. <h4><b> 2307 </b></h4> September 2005 (ENCS)', '18', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'RTB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //2nd row blank
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //3rd row -> 1 for the period
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('1', '40', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('For the Period', '120', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '');

    switch ($data['head'][0]['month']) {
      case '1':
      case '2':
      case '3':
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('', '270', null, false, '2px solid', '', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('03', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        break;

      case '4':
      case '5':
      case '6':
        $str .= $this->reporter->col('04', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('', '270', null, false, '2px solid', '', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('06', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        break;

      case '7':
      case '8':
      case '9':
        $str .= $this->reporter->col('07', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('', '270', null, false, '2px solid', '', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('09', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        break;

      default:
        $str .= $this->reporter->col('10', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('', '270', null, false, '2px solid', '', 'C', 'Century Gothic', '14', '', '', '8px');

        $str .= $this->reporter->col('12', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        $str .= $this->reporter->col('18', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '14', '', '', '8px');
        break;
    }




    $str .= $this->reporter->col('', '340', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //4th row -> from
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('From', '90', null, false, '2px solid ', 'B', 'R', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '60', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'B', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('(MM/DD/YY)', '140', null, false, '2px solid', 'B', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('To', '120', null, false, '2px solid ', 'B', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '0', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '100', null, false, '2px solid ', 'B', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('(MM/DD/YY)', '240', null, false, '2px solid', 'RB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //5th row -> part 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part I', '50', null, false, '2px solid ', 'TLB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('Payee Information', '750', null, false, '2px solid ', 'TRB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //6th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //7th row -> 2 tax payer
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('2', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Tax Payer', '100', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '250', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col('', '370', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //8th row -> 2 identification
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Identification Number', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '3px');
    $str .= $this->reporter->col('', '630', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //9th row -> 3 payees name
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('3', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("Payee's Name", '100', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '610', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '12', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //10th row -> registered name
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '30', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '10px');

    $str .= $this->reporter->col('(Last Name, First Name, Middle Name for Individuals)(Registered Name for Non-Individuals)', '560', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '40', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //11th row -> 4 registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('4', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Registered Address', '125', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '0', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '380', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');
    $str .= $this->reporter->col('4A', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Zip Code', '90', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '145', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //12th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //13th row -> 4 foreign address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('5', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Foreign Address', '127', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '0', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col('', '380', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '20', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');
    $str .= $this->reporter->col('5A', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Zip Code', '90', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '145', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //14th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //15th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, '2px solid ', 'TLB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('Payor Information', '750', null, false, '2px solid ', 'TRB', 'C', 'Century Gothic', '12', 'B', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //16th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //17th row -> 6 tax payer
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('6', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Tax Payer', '100', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col('', '250', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col('', '370', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //18th row ->  identification
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Identification Number', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '3px');
    $str .= $this->reporter->col('', '630', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //19th row -> 7 payors name
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('7', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("Payor's Name", '100', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col('', '610', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //20th row -> registered name
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '90', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '30', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '10px');

    $str .= $this->reporter->col('(Last Name, First Name, Middle Name for Individuals)(Registered Name for Non-Individuals)', '560', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '40', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //21th row -> 8 registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('8', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Registered Address', '127', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '0', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');

    $str .= $this->reporter->col('', '380', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '20', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '20', '', '', '15px');
    $str .= $this->reporter->col('8A', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Zip Code', '90', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '145', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //22th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //23th row -> part II
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part II', '50', null, false, '2px solid ', 'TLB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->col('Details of Monthly Income Payments and Tax Withheld for the Quarter', '750', null, false, '2px solid ', 'TRB', 'C', 'Century Gothic', '12', 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //24th row -> income payments 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRT', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '380', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '11', 'B', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //25th row -> month header
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('ATC', '80', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('1st Month of the Quarter', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('2nd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('3rd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Total', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Tax Withheld For the Quarter', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //26th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //27th row -> line
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LTRB', 'C', 'Century Gothic', '12', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //28th row -> atc1
    $str .= $this->reporter->begintable('800');

    $total = 0;
    $a = -1;
    foreach ($data['detail'] as $key => $value) {
      $a++;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data['res'][$a]['ewtdesc'], '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '15px');
      $str .= $this->reporter->col($key, '80', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

      switch ($data['head'][0]['month']) {
        case '1':
        case '2':
        case '3':
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', '', '', '');

          break;
        case '4':
        case '5':
        case '6':
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', '', '', '');

          break;
        default:
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', '', '', '');

          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      $str .= $this->reporter->col($total, '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
      $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'], 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //29th row -> total
    $str .= $this->reporter->begintable('800');
    $totaltax = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', 'B', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', '', '', '');

    foreach ($data['detail'] as $key => $value) {

      $totaltax = $totaltax + $data['detail'][$key]['xamt'];
    }

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //30th row -> space for total 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '15px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //31th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //33th row -> declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by me, and to the best of my knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof.', '800', null, false, '2px solid ', 'LRT', 'L', 'Century Gothic', '11', '', '', '10px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //34th row -> space after declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', 'B', '', '10px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //35th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['payor']), '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['tin'], '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['position']), '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //35th row -> signature line 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //36th row -> authorized signature
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("Payor/Payor's Authorized Representative/Accredited Tax Agent", '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('TIN of Signatory', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Title/Position of Signatory', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //37th row -> authorized signature 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("(Signature Over Printed Name)", '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //38th row -> blank space after authorized signature 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '10px');
    $str .= $this->reporter->col("", '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //39th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //40th row -> signature line 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //41th row -> authorized signature
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("Tax Agent Accreditation No./Attorney Roll No. (if applicable)", '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Date of Issuance', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Date of Expiry', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //42th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '11', 'B', '', '5px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //43th row -> space after declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Conforme', '800', null, false, '2px solid ', 'LRT', 'L', 'Century Gothic', '11', 'B', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //44th row -> space after declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', 'B', '', '10px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //45th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '85', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //45th row -> signature line 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '85', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //46th row -> authorized signature
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("Payee/Payee's Authorized Representative/Accredited Tax Agent", '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('TIN of Signatory', '120', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Title/Position of Signatory', '140', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Date Signed', '85', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //47th row -> authorized signature 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("(Signature Over Printed Name)", '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //48th row -> blank space after authorized signature 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '10px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //49th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //50th row -> signature line 2
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //51th row -> authorized signature
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col("Tax Agent Accreditation No./Attorney Roll No. (if applicable)", '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Date of Issuance', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('Date of Expiry', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //52th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '11', 'B', '', '10px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();




    $str .= $this->reporter->endreport();
    return $str;
  }

  public function rpt_CV_WTAXREPORT_NEW($data, $filters)
  {
    $str = '';
    $count = 60;
    $page = 58;

    $birlogo = URL::to('/images/reports/birlogo.png');
    $birblogo = URL::to('/images/reports/birbarcode.png');
    $bir = URL::to('/images/reports/bir2307.png');
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->endtable();
    $str .= '';

    //1st row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For BIR&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspBCS/<br/>Use Only&nbsp&nbsp&nbspItem:', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    //$str .= $this->reporter->col('BCS/<br/>Item:','10',null,false,'2px solid ','','L','Century Gothic','11','','','');
    $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="60px" height ="60px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue', '60', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('<img src ="'.$birlogo.'" alt="BIR" width="80px" height ="80px">','95',null,false,'2px solid ','LTB','C','Century Gothic','15','B','','8px');
    $str .= $this->reporter->col('<img src ="' . $bir . '">', '135', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Certificate of Creditable Tax <br> Withheld At Source', '450', null, false, '2px solid ', 'RTB', 'C', 'Century Gothic', '16', 'B', '', '');

    //$str .= $this->reporter->col('','55',null,false,'2px solid ','TB','L','Century Gothic','11','B','','');

    $str .= $this->reporter->col('<img src ="' . $birblogo . '" alt="BIR" width="200px" height ="50px">', '130', null, false, '2px solid ', 'TB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', 'RTB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"', '100', null, false, '2px solid ', 'LRTB', 'L', 'Century Gothic', '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    //2nd row blank
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //3rd row -> 1 for the period
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('1', '40', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('For the Period', '120', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('From', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    switch ($data['head'][0]['month']) {
      case '1':
      case '2':
      case '3':
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('03', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      case '4':
      case '5':
      case '6':
        $str .= $this->reporter->col('04', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        //$str .= $this->reporter->col('','270',null,false,'2px solid','LR','C','Century Gothic','14','','','8px');
        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('06', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      case '7':
      case '8':
      case '9':
        $str .= $this->reporter->col('07', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('09', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      default:
        $str .= $this->reporter->col('10', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('12', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;
    }

    $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //5th row -> part 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part I-Payee Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //6th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //7th row -> 2 tax payer
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('2', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');
    // / $str .= $this->reporter->col('','10',null,false,'2px solid','LRTB','L','Century Gothic','11','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //blank row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //9th row -> 3 payees name

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('3', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Payee`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //payees name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('4', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('4A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //address name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col((isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // 5 foreign address

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('5', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Foreign Address, <i>if applicable <i/>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //f address box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col('', '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '10px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //14th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //part II
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part II-Payor Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //16th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TIN payor
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('6', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');
    // / $str .= $this->reporter->col('','10',null,false,'2px solid','LRTB','L','Century Gothic','11','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //payor
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('7', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Payor`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //Payor name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('8', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('8A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //address name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col((isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '2px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //22th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //part III
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part III-Details of Monthly Income Payments and Taxes Withheld', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //24th row -> income payments 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRT', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '380', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '11', 'B', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //25th row -> month header
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '2px');
    $str .= $this->reporter->col('ATC', '80', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('1st Month of the Quarter', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('2nd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('3rd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Total', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Tax Withheld For the Quarter', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //26th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //27th row -> line
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LTRB', 'C', 'Century Gothic', '12', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //28th row -> atc1
    $str .= $this->reporter->begintable('800');

    $total = 0;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;
    $a = -1;
    foreach ($data['detail'] as $key => $value) {
      $a++;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data['res'][$a]['ewtdesc'], '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '2px');
      $str .= $this->reporter->col($key, '80', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

      switch ($data['detail'][$key]['month']) {
        case '1':
        case '4':
        case '7':
        case '10':
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx1 +=  $data['detail'][$key]['oamt'];
          break;
        case '2':
        case '5':
        case '8':
        case '11':
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx2 +=  $data['detail'][$key]['oamt'];
          break;
        default:
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx3 +=  $data['detail'][$key]['oamt'];
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      $str .= $this->reporter->col($total, '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'], 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');

      $totalwtx += $data['detail'][$key]['oamt'];
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //29th row -> total
    $str .= $this->reporter->begintable('800');
    $totaltax = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx != 0 ? number_format($totalwtx, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');

    foreach ($data['detail'] as $key2 => $value2) {

      $totaltax = $totaltax + $data['detail'][$key2]['xamt'];
    }

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //30th row -> space for total 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //31th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //33th row -> declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent  to the processing of our information as contemplated under  the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful  purposes.', '800', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory from parameter
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['payor']), '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['tin'], '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['position']), '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //line after signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent
            <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TAX Agent
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //39th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
              Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //42th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //43th row -> space after declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CONFORME:', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory from parameter
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //line after signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent
            <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TAX Agent
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //39th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
                Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //52th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function ftNumberToWordsConverter($number)
  {
    $numberwords = $this->ftNumberToWordsBuilder($number);

    if (strpos($numberwords, "/") == false) {
      $numberwords .= " PESOS ";
    } else {
      $numberwords = str_replace(" AND ", " PESOS AND ", $numberwords);
    } //end if

    return $numberwords;
  } //end function convert to words

  public function ftNumberToWordsBuilder($number)
  {
    if ($number == 0) {
      return 'Zero';
    } else {
      $hyphen      = ' ';
      $conjunction = ' ';
      $separator   = ' ';
      $negative    = 'negative ';
      $decimal     = ' and ';
      $dictionary  = array(
        0                   => '',
        1                   => 'One',
        2                   => 'Two',
        3                   => 'Three',
        4                   => 'Four',
        5                   => 'Five',
        6                   => 'Six',
        7                   => 'Seven',
        8                   => 'Eight',
        9                   => 'Nine',
        10                  => 'Ten',
        11                  => 'Eleven',
        12                  => 'Twelve',
        13                  => 'Thirteen',
        14                  => 'Fourteen',
        15                  => 'Fifteen',
        16                  => 'Sixteen',
        17                  => 'Seventeen',
        18                  => 'Eighteen',
        19                  => 'Nineteen',
        20                  => 'Twenty',
        30                  => 'Thirty',
        40                  => 'Forty',
        50                  => 'Fifty',
        60                  => 'Sixty',
        70                  => 'Seventy',
        80                  => 'Eighty',
        90                  => 'Ninety',
        100                 => 'Hundred',
        1000                => 'Thousand',
        1000000             => 'Million',
        1000000000          => 'Billion',
        1000000000000       => 'Trillion',
        1000000000000000    => 'Quadrillion',
        1000000000000000000 => 'Quintillion'
      );

      if (!is_numeric($number)) {
        return false;
      } //end if

      if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        return false;
      } //end if

      if ($number < 0) {
        return $negative . $this->ftNumberToWordsBuilder(abs($number));
      } //end if

      $string = $fraction = null;

      if (strpos($number, '.') !== false) {
        $fractionvalues = explode('.', $number);
        if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
          list($number, $fraction) = explode('.', $number);
        } //end if
      } //end if

      switch (true) {
        case $number < 21:
          $string = $dictionary[$number];
          break;

        case $number < 100:
          $tens   = ((int) ($number / 10)) * 10;
          $units  = $number % 10;
          $string = $dictionary[$tens];
          if ($units) {
            $string .= $hyphen . $dictionary[$units];
          } //end if
          break;

        case $number < 1000:
          $hundreds  = $number / 100;
          $remainder = $number % 100;
          $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
          if ($remainder) {
            $string .= $conjunction . $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;

        default:
          $baseUnit = pow(1000, floor(log($number, 1000)));
          $numBaseUnits = (int) ($number / $baseUnit);
          $remainder = $number % $baseUnit;
          $string = $this->ftNumberToWordsBuilder($numBaseUnits) . ' ' . $dictionary[$baseUnit];
          if ($remainder) {
            $string .= $remainder < 100 ? $conjunction : $separator;
            $string .= $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;
      } //end switch
      if (null !== $fraction && is_numeric($fraction)) {

        $string .= $decimal . ' ' . $fraction .  '/100';
        $words = array();
        $string .= implode(' ', $words);
      } //end if

      return strtoupper($string);
    } //end
  } //end fn

}
