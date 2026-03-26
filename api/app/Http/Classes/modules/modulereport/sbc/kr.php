<?php

namespace App\Http\Classes\modules\modulereport\sbc;

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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'attention', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    
    data_set($col1, 'attention.readonly', false);
    
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
      '' as received,
      '' as attention
      "
    );
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
    head2.yourref as krourref,
    coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, head2.rem as particulars, ar.docno as ref,client.tel
    from (krhead as head 
    left join arledger as ar on ar.kr=head.trno)
    left join coa on coa.acnoid=ar.acnoid
    left join glhead as head2 on head2.trno = ar.trno 
    left join client on client.client = head.client
    where head.trno='$trno'
    union all
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
    head2.yourref as krourref,
    coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, head2.rem as particulars, ar.docno as ref,client.tel
    from (hkrhead as head 
    left join arledger as ar on ar.kr=head.trno)
    left join coa on coa.acnoid=ar.acnoid
    left join glhead as head2 on head2.trno = ar.trno 
    left join client on client.client = head.client
    where head.trno='$trno' order by postdate,dateid, docno";

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

  // public function default_KR_header_PDF($params, $data)
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

  //   //$width = PDF::pixelsToUnits($width);
  //   //$height = PDF::pixelsToUnits($height);
  //   PDF::SetTitle($this->modulename);
  //   PDF::SetAuthor('Solutionbase Corp.');
  //   PDF::SetCreator('Solutionbase Corp.');
  //   PDF::SetSubject($this->modulename . ' Module Report');
  //   PDF::setPageUnit('px');
  //   PDF::AddPage('p', [800, 1000]);
  //   PDF::SetMargins(40, 40);

  //   // SetFont(family, style, size)
  //   // MultiCell(width, height, txt, border, align, x, y)
  //   // write2DBarcode(code, type, x, y, width, height, style, align)

  //   PDF::SetFont($font, '', 9);
  //   if ($params['params']['companyid'] != 10 && $params['params']['companyid'] != 12) {
  //     $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
  //     PDF::SetFont($font, '', 9);
  //     PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
  //   }

  //   $this->reportheader->getheader($params);

  //   // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
  //   PDF::SetFont($fontbold, '', 18);
  //   PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
  //   PDF::SetFont($font, '', 10);
  //   PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(0, 30, "", '', 'L');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

  //   PDF::MultiCell(0, 0, "\n");

  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


  //   PDF::MultiCell(0, 0, "\n\n\n");

  //   PDF::SetFont($font, '', 5);
  //   PDF::MultiCell(700, 0, '', 'T');

  //   PDF::SetFont($font, 'B', 12);
  //   PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
  //   PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
  //   PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
  //   PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
  //   PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
  //   PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
  //   PDF::MultiCell(10, 0, "", '', 'R', false, 0);
  //   PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

  //   PDF::SetFont($font, '', 5);
  //   PDF::MultiCell(700, 0, '', 'B');
  // }

  public function default_KR_header_PDF($params, $data)
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

      // Company Header - From desired layout
      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(0, 0, "SOLUTIONBASE CORPORATION", '', 'L');
      
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(0, 0, "255 Gregorio Araneta Ave,", '', 'L');
      PDF::MultiCell(0, 0, "Quezon City, 113 Metro Manila", '', 'L');
      
      PDF::SetTextColor(71, 199, 39);
      PDF::MultiCell(0, 0, "www.sbc.ph", '', 'L');
      PDF::SetTextColor(0, 0, 0);

      //logo
      PDF::Image($this->companysetup->getlogopath($params['params']) .'sbclogo.png', '600', '20', 143, 100);
      
      // Horizontal line separator
      // PDF::SetLineWidth(0.5);
      // PDF::Line(40, PDF::GetY(), 760, PDF::GetY());
      // PDF::MultiCell(0, 0, "\n");
      
      
      
      #grey 
      #160, 160, 160
      #lighter grey 
      #224, 224, 224
      #black 
      #0, 0, 0
      #white 
      #255, 255, 255
      
      PDF::MultiCell(0, 0, "\n\n\n\n");
      PDF::SetFillColor(160, 160, 160);
      
      PDF::SetTextColor(255, 255, 255);
      
						// $style = ['width' => 3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [0, 0, 0]];
						// PDF::SetLineStyle($style);
            // setCellPaddings($left='', $top='', $right='', $bottom='') {

      PDF::SetFont($fontbold, '', $fontsize+5);
      PDF::MultiCell(720, 20, 'STATEMENT OF ACCOUNTS', 'TBLR', 'C', true, 1, '', '130');
      PDF::SetTextColor(0, 0, 0);

      
      $style = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2.3, 'color' => [0, 0, 0]];
      PDF::SetLineStyle($style);

      /////////////
      $part_pos_adjust = 0;
      // $data[0]['clientname'] = 'TRANS POWER TRANS POWER TRANS POWER TRANS POWER TRANS POWER TRANS POWER TRANS POWER';
      // $data[0]['clientname'] = 'TRANS POWER TRANS POWER';
      // for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $arr_name = $this->reporter->fixcolumn([$data[0]['clientname']],'55',0);
        $arr_date = $this->reporter->fixcolumn([$data[0]['dateid']],'16',0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_name, $arr_date]);

        for($r = 0; $r < $maxrow; $r++) {
          
          PDF::SetFillColor(224,224,224);
          PDF::setCellPaddings(5, 0, 0, 0);
          PDF::SetFont($font, '', $fontsize+1);
          if($r==0){
            PDF::MultiCell(130, 20, 'Company Name :', 'TLR', 'L', 1, 0, '', '', true, 0, true, false);
            
            PDF::SetFont($fontbold, '', $fontsize+1);
            PDF::setCellPaddings(15, 0, 0, 0);
            PDF::MultiCell(440, 20, (isset($arr_name[$r]) ? $arr_name[$r] : ''), 'TLR', 'L', 0, 0, '', '', true, 0, true, false);
            
            PDF::SetFont($font, '', $fontsize+1);
            PDF::setCellPaddings(5, 0, 0, 0);
            PDF::MultiCell(50, 20, 'Date:', 'TLR', 'L', 1, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 20, (isset($arr_date[$r]) ? $arr_date[$r] : ''), 'TLR', 'L', 0, 1, '', '', true, 0, true, false);
            $part_pos_adjust = 20;
          }else{
            PDF::MultiCell(130, 20, '', 'LRB', 'L', 1, 0, '', '', true, 0, true, false);

            PDF::SetFont($fontbold, '', $fontsize+1);
            PDF::setCellPaddings(15, 0, 0, 0);
            PDF::MultiCell(440, 20, (isset($arr_name[$r]) ? $arr_name[$r] : ''), 'BLR', 'L', 0, 0, '', '', true, 0, true, false);

            PDF::SetFont($font, '', $fontsize+1);
            PDF::setCellPaddings(5, 0, 0, 0);
            PDF::MultiCell(50, 20, '', 'BLR', 'L', 1, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 20, (isset($arr_date[$r]) ? $arr_date[$r] : ''), 'BLR', 'L', 0, 1, '', '', true, 0, true, false);
            $part_pos_adjust = -5;
          }

        }
    
      PDF::SetFont($font, '', $fontsize+1);
      PDF::MultiCell(130, 20, "Company Address :", 'TBLR', 'L', true, 0);
    
    


      PDF::SetFont($fontbold, '', $fontsize+1);
      PDF::setCellPaddings(15, 0, 0, 0);
      PDF::MultiCell(590, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'TBLR', 'L', false);

      PDF::SetFont($font, '', $fontsize+1);
      PDF::setCellPaddings(5, 0, 0, 0);
      PDF::MultiCell(130, 20, "Attention to :", 'TBLR', 'L', true, 0);

      PDF::SetFont($fontbold, '', $fontsize+1);
      PDF::setCellPaddings(15, 0, 0, 0);
      PDF::MultiCell(590, 20, (isset($params['params']['dataparams']['attention']) ? $params['params']['dataparams']['attention'] : ''), 'BLR', 'L', false);

      PDF::MultiCell(0, 0, "\n");
      
      $style = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [0, 0, 0]];
			PDF::SetLineStyle($style);
      PDF::SetFillColor(160, 160, 160);
      PDF::SetTextColor(255, 255, 255);
      
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(570, 15, 'P A R T I C U L A R', 'TBLR', 'C', true, 0, '', '');
      PDF::MultiCell(150, 15, 'AMOUNT', 'TBLR', 'C', true, 1, '', '');
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(570, 550, '', 'BLR', 'C', false, 0, '', '');
      PDF::MultiCell(150, 550, '', 'BR', 'C', false, 1, '', '');
      
      PDF::MultiCell(0, 0, "\n");
      
      $pos_adjust = 255 - $part_pos_adjust;
      PDF::MultiCell(720, 0, '', '', 'L', false, 1, '', $pos_adjust);
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
      
      $this->default_KR_header_PDF($params, $data);

      
      if (!empty($data)) {
        $total = 0;
        for ($i = 0; $i < count($data); $i++) {
          $maxrow = 1;
          $particulars = $data[$i]['particulars'];

          $amount = 0;
          if ($data[$i]['db'] != 0) {
              $amount = $data[$i]['db'];
          } elseif ($data[$i]['cr'] != 0) {
              $amount = $data[$i]['cr'];
          }
          
          $total += $amount;

          // if ($db < 1) $db = '-';
          // if ($cr < 1) $cr = '-';

          $arr_particulars = $this->reporter->fixcolumn([$particulars], '65', 0);
          $arr_amt = $this->reporter->fixcolumn([number_format($amount,$decimalprice)], '18', 0);

          $maxrow = $this->othersClass->getmaxcolumn([$arr_particulars, $arr_amt]);
          
          
          for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize+3);
            PDF::MultiCell(570, 30, ' ' . (isset($arr_particulars[$r]) ? $arr_particulars[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::setCellPaddings(0, 0, 25, 0);
            PDF::MultiCell(150, 30, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::setCellPaddings(15, 0, 0, 0);
          }
          // PDF::setCellPaddings(0, 0, 0, 0);


          if (PDF::getY() > 600) {
            $this->default_KR_header_PDF($params, $data);
          }
        }
      }

      if($total>0){

        $part_pos_adjust = 0;
        // $data[0]['clientname'] = 'TRANS POWER TRANS POWER TRANS POWER TRANS POWER TRANS POWER TRANS POWER TRANS POWER';
        // $data[0]['clientname'] = 'TRANS POWER TRANS POWER';
        // for ($i = 0; $i < count($data); $i++) {
          $maxrow = 1;
          $arr_name = $this->reporter->fixcolumn([$data[0]['clientname']],'55',0);
          $arr_date = $this->reporter->fixcolumn([$data[0]['dateid']],'16',0);

          $maxrow = $this->othersClass->getmaxcolumn([$arr_name, $arr_date]);

          for($r = 0; $r < $maxrow; $r++) {
            
           
            if($r==0){
              
              $part_pos_adjust = 790;
            }else{
              
              $part_pos_adjust = 810;
            }

          }
        
          
        $pos_adjust = $part_pos_adjust;
        PDF::SetFont($fontbold, '', $fontsize+3);
        PDF::MultiCell(570, 25, 'GRAND TOTAL', 'BLR', 'C', false, 0, '', $pos_adjust);
        PDF::setCellPaddings(0, 0, 25, 0);
        PDF::MultiCell(150, 25, number_format($total,2), 'BR', 'R', false, 1, '', '');
        PDF::setCellPaddings(0, 0, 0, 0);
      }

      
      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize);
      
      PDF::setCellPaddings(0, 0, 0, 0);
      PDF::MultiCell(720, 15, 'Looking forward to your immediate settlement', '', 'L', false, 1, '', '');

      PDF::MultiCell(0, 0, "\n");
      PDF::MultiCell(170, 40, 'Prepared By:', 'B', 'L', false, 0, '', '');
      PDF::MultiCell(250, 40, '', '', 'L', false, 0, '', '');

      PDF::setCellPaddings(15, 0, 0, 0);
      PDF::MultiCell(100, 40, 'Received By:', '', 'L', false, 0, '', '');
      PDF::MultiCell(200, 40, '', 'B', 'L', false, 1, '', '');
      
      
      PDF::setCellPaddings(0, 0, 0, 0);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(170, 20, (isset($params['params']['dataparams']['prepared']) ? $params['params']['dataparams']['prepared'] : ''), '', 'L', false, 0, '', '');
      PDF::MultiCell(250, 20, '', '', 'L', false, 0, '', '');

      PDF::SetFont($font, '', $fontsize);
      PDF::setCellPaddings(15, 0, 0, 0);
      PDF::MultiCell(170, 20, 'Receive Date:', '', 'L', false, 0, '', '');
      PDF::MultiCell(200, 20, '', '', 'L', false, 1, '', '');

      
      PDF::setCellPaddings(0, 0, 0, 0);
      PDF::MultiCell(170, 10, 'Programmer', '', 'L', false, 0, '', '');
      PDF::MultiCell(250, 10, '', '', 'L', false, 0, '', '');

      PDF::setCellPaddings(15, 0, 0, 0);
      PDF::MultiCell(100, 10, '', '', 'L', false, 0, '', '');
      PDF::MultiCell(200, 10, '', 'B', 'L', false, 1, '', '');
      // PDF::MultiCell(420, 15, 'Prepared By:', 'B', 'L', false, 1, '', '');
      // PDF::MultiCell(300, 15, 'Received By:', 'B', 'L', false, 0, '', '');
      
      

      return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
