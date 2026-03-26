<?php

namespace App\Http\Classes\modules\modulereport\transpower;

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
use DateTime;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class kr
{

  private $modulename = "Counter Receipt";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '$username' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "select head.client,date(head.dateid) as dateid,
              concat(left(head.docno,3),right(head.docno,5)) as docno,
              head.clientname, head.address, head.yourref, head.ourref,
              ar.db,ar.cr,head2.ourref as ref2,head2.docno as sjdocno, date(ar.dateid) as postdate

              from (krhead as head
              left join arledger as ar on ar.kr=head.trno)
              left join coa on coa.acnoid=ar.acnoid
              left join glhead as head2 on head2.trno = ar.trno
              left join client on client.client = head.client where head.trno='$trno'


              union all
              select head.client,date(head.dateid) as dateid,
              concat(left(head.docno,3),right(head.docno,5)) as docno,
              head.clientname, head.address, head.yourref, head.ourref,
              ar.db,ar.cr,  head2.ourref as ref2,head2.docno as sjdocno, date(ar.dateid) as postdate

              from (hkrhead as head
              left join arledger as ar on ar.kr=head.trno)
              left join coa on coa.acnoid=ar.acnoid
              left join glhead as head2 on head2.trno = ar.trno
              left join client on client.client = head.client where head.trno='$trno' order by dateid, docno";
    // var_dump($query);
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_kr_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_KR_PDF($params, $data);
    }
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
    $str .= $this->reporter->col('COUNTER RECEIPT', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REFERENCE #', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_kr_layout($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    $totaldb = 0;
    $totalcr = 0;
    for ($i = 0; $i < count($data); $i++) {

      $debit = number_format($data[$i]['db'], $decimal);
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data[$i]['cr'], $decimal);
      $credit = $credit < 0 ? '-' : $credit;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['ref'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['postdate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['client'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $totaldb = $totaldb + $data[$i]['db'];
      $totalcr = $totalcr + $data[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '350', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, $decimal), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, $decimal), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  public function default_KR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

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
    if ($params['params']['companyid'] != 10 && $params['params']['companyid'] != 12) {
      $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    }

    $this->reportheader->getheader($params);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_KR_PDF($params, $data)
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
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->kr_new_header($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    $tldb=0;
    $tlcr=0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $sjdocno = $data[$i]['sjdocno'];
        $ref = $data[$i]['ref2'];
        $postdate = $data[$i]['postdate'];
        $debit = number_format($data[$i]['db'], $decimalcurr);
        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $debit = $debit <= 0 ? '-' : $debit;
        $credit = $credit <= 0 ? '-' : $credit;

        $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
        $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        $arr_sjdocno= $this->reporter->fixcolumn([$sjdocno], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_sjdocno,$arr_ref, $arr_postdate, $arr_debit, $arr_credit]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(150, 0, (isset($arr_sjdocno[$r]) ? $arr_sjdocno[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(250, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 1, '', '', false, 1);
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->kr_new_header($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

     $totaldb = number_format($totaldb, $decimalcurr);
     $totalcr = number_format($totalcr, $decimalcurr);
     $tldb = $totaldb <= 0 ? '-' : $totaldb;
     $tlcr = $totalcr <= 0 ? '-' : $totalcr;

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(500, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, $tldb, '', 'R', false, 0);
    PDF::MultiCell(100, 0, $tlcr, '', 'R', false, 0);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    
    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(700, 0, '', 'B');

        
    $style_solid = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);

    PDF::MultiCell(0, 0, "\n");

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(560, 0, '', '', 'L');

    // PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(180, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], 'B', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

   public function kr_new_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, '', '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::SetTextColor(110, 150, 112);
    PDF::MultiCell(520, 0,  $this->modulename, '', 'L', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Customer", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(440, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Document# : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Address", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(440, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Date", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y/m/d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(70, 20, "Printed Date", 0, 'L', false, 0);
    PDF::MultiCell(10, 20, ":", 0, 'L', false, 0);
    PDF::MultiCell(150, 20, $formattedDate, 0, 'L', false, 0);
    PDF::MultiCell(60, 20, "Printed by : ", 0, 'L', false, 0);
    PDF::MultiCell(170, 20, $username, 0, 'L', false, 0);
    PDF::MultiCell(0, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 0, 'R', false, 1);


    //   PDF::SetFont($font, '', 10);R

    // PDF::MultiCell(0, 0, "\n");

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(700, 0, '', 'T');

              PDF::SetFont($font, 'B', 13);
              PDF::MultiCell(150, 0, "SJ DOC#", '', 'C', false, 0);
              PDF::MultiCell(250, 0, "REFERENCE#", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "DATE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "DEBIT", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "CREDIT", '', 'C', false);

    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }
}
