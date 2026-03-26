<?php

namespace App\Http\Classes\modules\modulereport\roosevelt;

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

class gd
{

  private $modulename = "Debit Memo";
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
    $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($filters)
  {
    $trno = md5($filters['params']['dataid']);
    $query = "
    
    select trno, dateid,docno, clientname,address,  yourref, alias, acno,
      acnoname,client, sum(db) as db, sum(cr) as cr,rem,concat(acnoname,' -',ifnull(rem,'')) as particular from (

    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, 
    head.yourref, left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client, detail.ref, detail.checkno, sum(detail.db) as db, sum(detail.cr) as cr,detail.rem
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    where head.doc='" . $filters['params']['doc'] . "' and md5(head.trno)='$trno' and detail.db<>0
    group by head.trno, head.dateid, head.docno, head.clientname, head.address,head.yourref, coa.alias, coa.acno,
    coa.acnoname, client.client, detail.ref,  detail.checkno,detail.rem
    union all
    select head.trno, head.dateid, head.docno, head.clientname, head.address, head.yourref, left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client, detail.ref, detail.checkno, sum(detail.db) as db, sum(detail.cr) as cr,detail.rem
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    where head.doc='" . $filters['params']['doc'] . "' and md5(head.trno)='$trno' and detail.db<>0 
    group by head.trno, head.dateid, head.docno, head.clientname, head.address,head.yourref, coa.alias, coa.acno,
    coa.acnoname, client.client, detail.ref,  detail.checkno,detail.rem order by acnoname
    ) as a
      group by trno, dateid,docno, clientname,address,  yourref, alias, acno,
      acnoname,client,rem
      order by  acnoname";
    // var_dump($query);
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_gd_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->roosevelt_gd_PDF($params, $data);
    }
  }

  public function default_gd_layout($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    if ($companyid == 3) {
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
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
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
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
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data->cr, 2);
      $credit = $credit < 0 ? '-' : $credit;
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

  public function default_GD_header_PDF($params, $data, $yy)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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


    $x = PDF::GetX();
    // $y = PDF::GetY();
    $y = $yy;

    $docno = (isset($data[0]['docno']) ?  strtoupper($data[0]['docno'])  : '');
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(720, 0,  $docno, '', 'L', false, 0,  $x + 510, $y + 55);


    //MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = 0, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0)
    PDF::SetFont($fontbold, '', $fontsize);
    $clientname = (isset($data[0]['clientname']) ?  strtoupper($data[0]['clientname'])  : '');
    $clname = $clientname;

    $maxChars = 39;
    $clnames = strlen($clname);
    $firstLine = '';
    $remainingLines = [];
    $clnamesz = '';

    if ($clnames > $maxChars) {
      $firstLine = substr($clname, 0, $maxChars);

      $remainings = substr($clname, $maxChars);
      // Split remaining address into multiple lines without cutting words
      while (strlen($remainings) > $maxChars) {
        // Find the last space within the maxChars limit
        $spacePoss = strrpos(substr($remainings, 0, $maxChars), ' ');

        // If there's no space, just cut at maxChars
        if ($spacePoss === false) {
          $nextLines = substr($remainings, 0, $maxChars);
          $remainings = substr($remainings, $maxChars);
        } else {
          $nextLines = substr($remainings, 0, $spacePoss);
          $remainings = substr($remainings, $spacePoss + 1);
        }

        $remainingLines[] = $nextLines;
      }
      // Add the final remaining part if it's less than or equal to $maxChars
      if (strlen($remainings) > 0) {
        $remainingLines[] = $remainings;
      }
    } else {
      $clnamesz = $clname;
    }

    $lineCount = count($remainingLines);


    if ($clnames > $maxChars) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(555, 0, strtoupper($firstLine), '', 'L', false, 0,  $x + 123, $y + 83);
      $datenow = strtoupper((new DateTime($data[0]['dateid']))->format('F j, Y'));
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(145, 0, $datenow, '', 'L', false, 0,   $x + 522, $y + 83);
      $lineY = PDF::GetY();
      for ($i = 0; $i < $lineCount; $i++) {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(555, 0, strtoupper($remainingLines[$i]), '', 'L', false, 0,  $x + 123, $lineY + 13);
        PDF::MultiCell(150, 0, '', '', 'C', false, 1,  $x + 522, $lineY - 5);
        $lineY = PDF::GetY();
      }
    } else {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(555, 0, strtoupper($clnamesz), '', 'L', false, 0,  $x + 123, $y + 83);
      PDF::SetFont($font, '', $fontsize);
      $datenow = strtoupper((new DateTime($data[0]['dateid']))->format('F j, Y'));
      PDF::MultiCell(145, 0, $datenow, '', 'L', false, 0,   $x + 522, $y + 83);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(720, 0, "", '', 'L', false, 1,  $x + 123, $y + 87);
    }


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "", '', 'L');

    $address = (isset($data[0]['address']) ? strtoupper($data[0]['address']) : '');
    $whadd = $address;

    $maxCharss = 48;
    $whadds = strlen($whadd);
    $firstLines = '';
    $remainingLiness = [];
    $whaddsz = '';

    if ($whadds > $maxCharss) {
      $firstLines = substr($whadd, 0, $maxCharss);

      $remainings = substr($whadd, $maxCharss);
      // Split remaining address into multiple lines without cutting words
      while (strlen($remainings) > $maxCharss) {
        // Find the last space within the maxChars limit
        $spacePoss = strrpos(substr($remainings, 0, $maxCharss), ' ');

        // If there's no space, just cut at maxChars
        if ($spacePoss === false) {
          $nextLines = substr($remainings, 0, $maxCharss);
          $remainings = substr($remainings, $maxCharss);
        } else {
          $nextLines = substr($remainings, 0, $spacePoss);
          $remainings = substr($remainings, $spacePoss + 1);
        }

        $remainingLiness[] = $nextLines;
      }
      // Add the final remaining part if it's less than or equal to $maxChars
      if (strlen($remainings) > 0) {
        $remainingLiness[] = $remainings;
      }
    } else {
      $whaddsz = $whadd;
    }

    $lineCount = count($remainingLiness); //sample 4 yung linecount

    if ($whadds > $maxCharss) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(70, 0, "", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(480, 0, strtoupper($firstLines), '', 'L', false, 0,  $x + 45, $y + 110);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(170, 0, strtoupper($this->modulename), '', 'C', false, 0, $x + 520, $y + 123);
      $lineY = PDF::GetY();
      // var_dump($lineY);
      for ($i = 0; $i < $lineCount; $i++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(480, 0,  strtoupper($remainingLiness[$i]), '', 'L', false, 0,  $x + 45, $lineY - 2);
        PDF::MultiCell(170, 0, '', '', 'C', false, 1, $x + 520, $lineY - 5);
        $lineY = PDF::GetY();
      }
    } else {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(70, 0, "", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(475, 0, strtoupper($whaddsz), '', 'L', false, 0,  $x + 45, $y + 110);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(170, 0, strtoupper($this->modulename), '', 'C', false, 1, $x + 520, $y + 123);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(720, 0, "", '', 'L', false, 1,  $x + 45, $y + 525);
    }


    $total = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $credit = $data[$i]['cr'];
        $total += $credit;
      }
    }
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 0, number_format($total, 2), '', 'L', false, 0,  $x + 470, $y + 145);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 15);
    PDF::MultiCell(720, 0, '', '');
  }

  public function default_GD_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    // $count = $page = 12;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->default_GD_header_PDF($params, $data, $y = (float) 10.00125);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    $x = PDF::GetX();
    $y = PDF::GetY() + 33;

    $rowCount = 0;
    $page = 12;

    if (!empty($data)) {
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $particular = $data[$i]['particular'];
        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $credit = $credit < 0 ? '-' : $credit;

        $client = $data[$i]['client'];
        $arr_particular = $this->reporter->fixcolumn([$particular], '70', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        $arr_client = $this->reporter->fixcolumn([$client], '16', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_credit, $arr_client, $arr_particular]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(20, 0, '', '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(600, 0, (isset($arr_particular[$r]) ? strtoupper($arr_particular[$r]) : ''), '', 'L', false, 0,  $x - 15, $y, true, 1);
          PDF::MultiCell(100, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 1, $x + 580, $y, true, 1);
          $y = PDF::GetY();
          // increment line counter per printed line
          $rowCount++;

          // kapag naka-12 lines na, lipat ng page
          if ($rowCount >= $page && $i < count($data) - 1) {
            $y = (float) 10.00125;
            $this->default_GD_header_PDF($params, $data, $y);
            // $y = PDF::GetY() + 35;
            $y = (float) 215.00125;
            $rowCount = 0; // reset counter
          }
        }
        $totalcr += $data[$i]['cr'];
      }
    }

    $x = PDF::GetX();
    $y = PDF::GetY();
    $testy = (float) 390;
    if ($y < $testy) {
      $add = $testy - $y;
      $y = PDF::GetY() + $add;
    } else {
      $minus = $y - $testy;
      $y = PDF::GetY() - $minus;
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(520, 0, '', '', 'R', false, 0);
    PDF::MultiCell(200, 0, number_format($totalcr, $decimalprice), '', 'R', false, 1,  $x + 480, $y + 5);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function roosevelt_gd_header_PDF($params, $data)
  {
    // var_dump($y);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
    $font2 = "";

    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }


    if (Storage::disk('sbcpath')->exists('/fonts/BroadwayRegular.ttf')) {
      $font2 = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/BroadwayRegular.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    // $y = PDF::getY(); //10.00125
    // $y = (float)30;
    $y = (float)30;
    $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
    $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 30, 30, 120, 120) : 'No image found'; //x, y,width,height
    PDF::SetFont($font2, '', 33);
    $name = "ROOSEVELT CHEMICAL INC.";
    $address = "73 F. Mariano Avenue Dela Paz NCR, Second District 1600 City of Pasig Philippines";
    $tel = "Contact Number: 8645-1089; 7900-9642 Fax: 8645-3425";
    PDF::MultiCell(720, 0, $name, '', 'C', false, 1,  '', $y + 5);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, $address . "\n" . $tel, '', 'C', false, 1,  '', $y + 45); //Rowen
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(720, 0, 'VAT REG TIN: 000-282-667-00000 ', '', 'C', false, 1,  '', $y + 80);

    // PDF::MultiCell(0, 0, "\n");
    $x = PDF::getX();
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(720, 0, 'Customer Debit Note', '', 'C', false, 1,  $x, $y + 105);

    PDF::SetY(165);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, '', 'LT', 'L', false, 0);
    PDF::MultiCell(300, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'TR', '', false, 0);
    PDF::MultiCell(65, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', '', 'L', false, 0);
    PDF::MultiCell(260, 0, '', '', 'L', false);

    PDF::SetY(175);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, '', 'L', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(300, 0, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(95, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, 'No.', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(230, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1);


    $add = isset($data[0]['address']) ? $data[0]['address'] : '';
    $maxChars = 64;
    $adds = strlen($add);
    // var_dump($adds);
    $firstLine = '';
    $remaininglines = [];
    $addsz = '';

    if ($adds > $maxChars) {
      $firstLine = substr($add, 0, $maxChars);
      $remaining = substr($add, $maxChars);
      // Split remaining address into multiple lines without cutting words
      while (strlen($remaining) > $maxChars) {
        // Find the last space within the maxChars limit
        $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');
        // If there's no space, just cut at maxChars
        if ($spacePos === false) {
          $nextLine = substr($remaining, 0, $maxChars);
          $remaining = substr($remaining, $maxChars);
        } else {
          $nextLine = substr($remaining, 0, $spacePos);
          $remaining = substr($remaining, $spacePos + 1);
        }

        $remainingLines[] = $nextLine;
      }
      // Add the final remaining part if it's less than or equal to $maxChars
      if (strlen($remaining) > 0) {
        $remainingLines[] = $remaining;
      }
    } else {
      $addsz = $add;
    }


    if ($adds > $maxChars) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(10, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $firstLine, '', 'L', false, 0);
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(95, 0, '', '', 'L', false, 0);
      PDF::MultiCell(75, 0, 'Reference No', '', 'L', false, 0);
      PDF::MultiCell(230, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1);

      // Loop through remaining lines and print them
      foreach ($remainingLines as $line) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10, 0, '', '', 'L', false, 0);
        PDF::MultiCell(300, 0, $line, '', 'L', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 0, '', '', 'L', false, 0);
        PDF::MultiCell(75, 0, 'Date', '', 'L', false, 0);
        $date = $data[0]['dateid'];
        $datetime = new DateTime($date);
        $datehere = $datetime->format('m/d/Y');
        PDF::MultiCell(230, 0, $datehere, '', 'L', false, 1);
      }
    } else {

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(10, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, $addsz, '', 'L', false, 0);
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(95, 0, '', '', 'L', false, 0);
      PDF::MultiCell(75, 0, 'Reference No', '', 'L', false, 0);
      PDF::MultiCell(230, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(10, 0, '', '', 'L', false, 0);
      PDF::MultiCell(300, 0, '', '', 'L', false, 0);
      PDF::MultiCell(10, 0, '', '', '', false, 0);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(95, 0, '', '', 'L', false, 0);
      PDF::MultiCell(75, 0, 'Date', '', 'L', false, 0);
      $date = $data[0]['dateid'];
      $datetime = new DateTime($date);
      $datehere = $datetime->format('m/d/Y');
      PDF::MultiCell(230, 0, $datehere, '', 'L', false, 1);
    }


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(300, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(95, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', '', 'L', false, 0);
    PDF::MultiCell(230, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(300, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(95, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', '', 'L', false, 0);
    PDF::MultiCell(230, 0, '', '', 'L', false, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(300, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(95, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', '', 'L', false, 0);
    PDF::MultiCell(230, 0, '', '', 'L', false, 1);



    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, '', 'LB', 'L', false, 0);
    PDF::MultiCell(300, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'RB', '', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(95, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', '', 'L', false, 0);
    PDF::MultiCell(230, 0, '', '', 'L', false, 1);


    PDF::MultiCell(0, 0, "\n");
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(605, 0, 'PARTICULAR', 'TB', 'L', false, 0);
    PDF::MultiCell(115, 0, 'AMOUNT', 'TB', 'R', false, 1);

    PDF::SetCellPaddings(0, 0, 0, 0);
  }



  public function roosevelt_gd_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 26;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->roosevelt_gd_header_PDF($params, $data);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetCellPaddings(0, 4, 0, 0);
    $rowCount = 0;
    $countarr = 0;
    $y = (float)295;
    $x = PDF::GetX();

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $itemname = $data[$i]['particular'];
        $debit = number_format($data[$i]['db'], $decimalcurr);
        $debit = $debit < 0 ? '-' : $debit;



        $arr_particular = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_debit, $arr_particular]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::SetXY($x, $y);
          PDF::MultiCell(605, 15, ' ' . (isset($arr_particular[$r]) ? $arr_particular[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          PDF::MultiCell(115, 15, ' ' . (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          $y = PDF::getY();
          $rowCount++;
          if ($rowCount >= $page && $i < count($data) - 1) {
            $this->default_footer($params, $data);
            $rowCount = 0;
            $y = (float)295;
            $this->roosevelt_gd_header_PDF($params, $data);
            PDF::SetCellPaddings(0, 4, 0, 0);
          }
        }
      }
    }


    $this->default_footer($params, $data);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function default_footer($params, $data)
  {
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }
    $totalext = 0;
    foreach ($data as $row) {
      $totalext += $row['db'];
    }

    $words = $this->reporter->ftNumberToWordsConverter($totalext,  false) . ' ONLY';

    $maxChars = 89;
    $adds = strlen($words);
    $remaininglines = [];
    $addsz = '';

    if ($adds > $maxChars) {
      $firstLine = substr($words, 0, $maxChars);
      $remaining = substr($words, $maxChars);
      // Split remaining address into multiple lines without cutting words
      while (strlen($remaining) > $maxChars) {
        // Find the last space within the maxChars limit
        $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

        // If there's no space, just cut at maxChars
        if ($spacePos === false) {
          $nextLine = substr($remaining, 0, $maxChars);
          $remaining = substr($remaining, $maxChars);
        } else {
          $nextLine = substr($remaining, 0, $spacePos);
          $remaining = substr($remaining, $spacePos + 1);
        }

        $remainingLines[] = $nextLine;
      }
      // Add the final remaining part if it's less than or equal to $maxChars
      if (strlen($remaining) > 0) {
        $remainingLines[] = $remaining;
      }
    } else {
      $addsz = $words;
    }

    if ($adds > $maxChars) {
      PDF::SetY(760);
      PDF::SetFont($font, '', $fontsize);
      PDF::SetCellPaddings(3, 3, 3, 3);
      PDF::MultiCell(720, 0, $firstLine, '', 'L', false, 1);

      foreach ($remainingLines as $line) {
        PDF::SetY(772);
        PDF::MultiCell(720, 0, $line, '', 'L', false, 0, '',  '');
      }
    } else {
      PDF::SetY(760);
      PDF::SetFont($font, '', $fontsize);
      PDF::SetCellPaddings(3, 3, 3, 3);
      PDF::MultiCell(720, 0, $addsz, '', 'L', false, 1);
    }

    PDF::SetY(760);
    PDF::MultiCell(180, 0, '', 'T', '', false, 0);
    PDF::MultiCell(180, 0, '', 'T', '', false, 0);
    PDF::MultiCell(150, 0, '', 'T', '', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0,  'TOTAL AMOUNT', 'T', 'L', false, 0);
    PDF::MultiCell(30, 0,  '', 'T', '', false, 0);
    PDF::MultiCell(90, 0,  number_format($totalext, 2), 'T', 'R', false, 1); //number_format($totalext, 2)


    // PDF::SetY(805);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(150, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0,  'NET AMOUNT', '', 'L', false, 0);
    PDF::MultiCell(30, 0,  'PHP', '', '', false, 0);
    PDF::MultiCell(90, 0,  number_format($totalext, 2), 'B', 'R', false, 1);

    PDF::SetY(780);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(160, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0,  '', '', '', false, 0);
    PDF::MultiCell(30, 0,  '', '', '', false, 0);
    PDF::MultiCell(90, 0,  '', 'B', '', false, 1);


    PDF::SetY(790);
    PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(33, 0, '', '', 'C', false, 0);
    PDF::MultiCell(720, 0, 'Notes', '', 'L', false, 1); //687

    PDF::SetY(805);
    PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(33, 0, '', '', 'C', false, 0);
    PDF::MultiCell(720, 0, '1. All cheques should be crossed and made payable to', '', 'L', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetY(819);
    PDF::MultiCell(45, 0, '', '', 'L', false, 0);
    PDF::MultiCell(675, 0, 'ROOSEVELT CHEMICAL INC.', '', 'L', false, 1);

    PDF::SetY(835);
    PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(33, 0, '', '', 'C', false, 0);
    PDF::MultiCell(720, 0, '2. All goods are  not returnable or exchangeable.', '', '', false, 1);



    PDF::SetY(850);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(180, 0, '', '', 'C', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0,  'Authorised Signature', 'T', 'C', false, 1);

    PDF::SetY(875);
    PDF::SetCellPaddings(0, 0, 0, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(360, 0, '', '', 'C', false, 0, '', '');
    PDF::MultiCell(305, 0, '"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX"', 'B', 'L', false, 0, '', '');
    PDF::MultiCell(55, 0, '', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, 'Acknowledgement Certificate Control No.:', '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'Date Issued: January 01, 0001', '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'Inclusion Series: DN000000001 To: DN999999999', '', 'L', false, 1, '', '');


    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, 'QNE SOFTWARE PHILIPPINES, INC', '', 'L', false, 0, '', '');
    PDF::MultiCell(180, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(270, 0, 'Printed Date/Time: ' . $formattedDate, '', 'L', false, 0, '', '');
    PDF::MultiCell(50, 0, '', '', 'L', false, 1, '', '');

    PDF::MultiCell(400, 0, 'Unit 3103 The Stiles Enterprise Plaza Bldg. Podium 2 Hippodromo Street Circuit', '', 'L', false, 0, '', '');
    PDF::MultiCell(320, 0, 'Printed By: ' . $username, '', 'L', false, 1, '', '');

    PDF::MultiCell(400, 0, 'Carmona 1207 City Of Makati NCR, Fourth District Philippines', '', 'L', false, 0, '', '');
    PDF::MultiCell(320, 0, 'QNE Optimum Version 2024.1.0.7', '', 'L', false, 1, '', '');

    PDF::MultiCell(720, 0, 'TIN: 006-934-485-000', '', 'L', false, 1, '', '');
  }
}
