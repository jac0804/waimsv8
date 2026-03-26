<?php

namespace App\Http\Classes\modules\modulereport\afti;

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

class gc
{

  private $modulename = "Credit Memo";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
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
    $trno = md5($filters['params']['dataid']);
    $query = "select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, 
    head.yourref, left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,head.rem,p.name as costcenter,dept.clientname as department,ifnull(branch.clientname,'') as branch
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    left join projectmasterfile as p on p.line=detail.projectid
    left join client as dept on dept.clientid=detail.deptid
    left join client as branch on branch.clientid=detail.branch
    where head.doc='" . $filters['params']['doc'] . "' and md5(head.trno)='$trno'
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref, left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line,head.rem,p.name as costcenter,dept.clientname as department,ifnull(branch.clientname,'') as branch
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    left join projectmasterfile as p on p.line=detail.projectid
    left join client as dept on dept.clientid=detail.deptid
    left join client as branch on branch.clientid=detail.branch
    where head.doc='" . $filters['params']['doc'] . "' and md5(head.trno)='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_gc_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_GC_PDF($params, $data);
    }
  }

  public function default_gc_layout($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER/SUPPLIER: ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->clientname) ? $result[0]->clientname : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->address) ? $result[0]->address : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->yourref) ? $result[0]->yourref : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
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
    $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');

    $totaldb = 0;
    $totalcr = 0;
    foreach ($result as $key => $data) {
      $debit = number_format($data->db, 2);
      if ($debit < 1) {
        $debit = '-';
      }
      $credit = number_format($data->cr, 2);
      if ($credit < 1) {
        $credit = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->acno, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->acnoname, '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->ref, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->postdate, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->client, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $totaldb = $totaldb + $data->db;
      $totalcr = $totalcr + $data->cr;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('GENERAL JOURNAL', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]->docno) ? $data[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER/SUPPLIER: ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]->clientname) ? $data[0]->clientname : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]->dateid) ? $data[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($data[0]->address) ? $data[0]->address : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]->yourref) ? $data[0]->yourref : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '350', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
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
    $str .= $this->reporter->col($prepared, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_GC_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $trno = $data[0]['trno'];

    $font = "";
    $fontbold = "";
    $fontsize9 = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'aftilogo.png', '35', '30', 60, 50);
    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(60, 0, "", '', 'L', false, 0);
    PDF::MultiCell(350, 0, strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(60, 0, "", '', 'L', false, 0);
    PDF::MultiCell(350, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(530, 0, $this->modulename, '', 'C', false, 0, '',  '100');
    PDF::MultiCell(0, 40, "\n");

    PDF::SetFont($fontbold, '', $fontsize9 + 2);
    PDF::MultiCell(70, 0, "Pay To: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(290, 0, $data[0]['clientname'], 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "JV#: ", '', 'R', false, 0, '',  '');
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(70, 0, "Customer PO: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(290, 0, $data[0]['yourref'], 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Date: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(530, 0, 'PARTICULARS', '', 'C', false);
    PDF::MultiCell(530, 0, '', 'B');

    $particulars = $this->coreFunctions->opentable("select rem,amount from particulars where trno = ? union all select rem,amount from hparticulars where trno = ? ", [$trno, $trno]);
    $particulars =  json_decode(json_encode($particulars), true);
    $remarks = '';
    $tamt = 0;
    if (!empty($particulars)) {
      PDF::MultiCell(0, 0, "\n");
      for ($x = 0; $x < count($particulars); $x++) {
        if ($particulars[$x]['rem'] != '') {

          $arrrem = $this->reporter->fixcolumn([$particulars[$x]['rem']], 80, 0);
          $crem = count($arrrem);
          $camt = count($particulars[$x]['amount']);
          $maxrow = 1;
          $maxrow = max($crem, $camt);
          for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(350, 0, isset($arrrem[$r]) ? ' ' . $arrrem[$r] : '', '', 'L', false, 0);
            if ($r == 0) {
              $partiamt = $particulars[$x]['amount'];
            } else {
              $partiamt = '';
            }
            PDF::MultiCell(180, 0,  $partiamt, '', 'R', false, 350);
          }
        }
        $tamt = $tamt + $particulars[$x]['amount'];
      }

      PDF::MultiCell(0, 0, "\n");
      PDF::MultiCell(530, 0, '', 'T', 'C', false);
      PDF::SetFont($fontbold, '', $fontsize9);
      PDF::MultiCell(350, 0, 'TOTAL: ', '', 'L', false, 0);
      PDF::MultiCell(180, 0, number_format($tamt, 2), '', 'R', false, 0);
      PDF::MultiCell(0, 0, "\n");
    } else {
      if ($remarks == $data[0]['rem']) {
        $remarks = "";
      } else {
        $remarks = $data[0]['rem'];
      }

      PDF::SetFont($font, '', $fontsize9);
      PDF::MultiCell(350, 25, $remarks, '', 'L', false, 0);
      PDF::MultiCell(180, 25, '', '', 'R', false);
    }

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(530, 0, '', '');

    $fontsize9 = "9";
    PDF::SetFont($fontbold, 'B', $fontsize9);
    PDF::MultiCell(530, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(150, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "DEBIT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "CREDIT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "ITEM GROUP", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "DEPARTMENT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "BRANCH", '', 'C', false);
    PDF::MultiCell(530, 0, '', 'B');
  }

  public function default_GC_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize9 = "9";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_GC_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $arracnoname = (str_split($data[$i]['acnoname'], 40));
        $acnonamedescs = [];

        if (!empty($arracnoname)) {
          foreach ($arracnoname as $arri) {
            if (strstr($arri, "\n")) {
              $array = preg_split("/\r\n|\n|\r/", $arri);
              foreach ($array as $arr) {
                array_push($acnonamedescs, $arr);
              }
            } else {
              array_push($acnonamedescs, $arri);
            }
          }
        }
        $countarr = count($acnonamedescs);

        $maxrow = $countarr;

        if ($data[$i]['acnoname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $acno =  $data[$i]['acno'];
              $ref = $data[$i]['ref'];
              $center = $data[$i]['costcenter'];
              $dept = $data[$i]['department'];
              $branch = $data[$i]['branch'];
              $postdate = $data[$i]['postdate'];
              $debit = number_format($data[$i]['db'], $decimalcurr);
              $debit = $debit < 0 ? '-' : $debit;

              $credit = number_format($data[$i]['cr'], $decimalcurr);
              $credit = $credit < 0 ? '-' : $credit;
              $client = $data[$i]['client'];
            } else {
              $acno = '';
              $ref = '';
              $postdate = '';
              $center = '';
              $dept = '';
              $branch = '';
              $debit = '';
              $credit = '';
              $client = '';
            }
            $accountlen = strlen(isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '') / 30;

            if ($acnonamedescs[$r] == '') {
              $accountlen = 1;
            }
            $padding = 20 * $accountlen;
            PDF::SetFont($font, '', $fontsize9);

            PDF::MultiCell(150, $padding, isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '', '', 'L', false, 0);
            PDF::MultiCell(75, $padding, $debit, '', 'R', false, 0);
            PDF::MultiCell(75, $padding, $credit, '', 'R', false, 0);
            PDF::MultiCell(75, $padding, $center, '', 'C', false, 0);
            PDF::MultiCell(75, $padding, $dept, '', 'C', false, 0);
            PDF::MultiCell(75, $padding, $branch, '', 'C', false);
          }
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->GC_header_PDF($params, $data);
          $page += $count;
        }
      }
    }


    PDF::MultiCell(150, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(75, 0, number_format($totaldb, $decimalcurr), 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totalcr, $decimalcurr), 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(80, 0, '', 'T', 'R', false);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 0);
    PDF::MultiCell(65, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 0);
    PDF::MultiCell(65, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(200, 0, 'Prepared By: ', '', 'c', false, 0);
    PDF::MultiCell(65, 0, '', '', 'C', false, 0);
    PDF::MultiCell(65, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, 'Approved By:', '', 'c', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(65, 0, '', '', 'C', false, 0);
    PDF::MultiCell(65, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'B', 'C', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(385, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Page : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
