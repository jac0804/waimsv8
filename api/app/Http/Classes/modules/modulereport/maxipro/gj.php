<?php

namespace App\Http\Classes\modules\modulereport\maxipro;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class gj
{
  private $modulename = "General Journal";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;
  private $reportheader;

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
    $fields = ['radioprint', 'prepared', 'approved', 'checked', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if ((isset($name[0]['name']) ? $name[0]['name'] : '') != '') {
      $user = $name[0]['name'];
    }

    $signatories = $this->othersClass->getSignatories($config);
    $approved = '';
    $checked = '';


    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'approved':
          $approved = $value->fieldvalue;
          break;

        case 'checked':
          $checked = $value->fieldvalue;
          break;
      }
    }


    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '$user' as prepared,
      '$approved' as approved,
      '$checked' as checked
      "
    );
  }

  public function report_default_query($filters)
  {
    $trno = md5($filters['params']['dataid']);
    $query = "
    select head.trno, date(head.dateid) as dateid, head.docno, 
    head.clientname, head.address, head.yourref, 
    left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client,client.clientname as vendor, detail.ref, 
    date(detail.postdate) as postdate, detail.checkno, 
    detail.db, detail.cr, detail.line,head.rem,proj.name as projname, proj.code as projcode,concat(headproj.code,' - ',headproj.name) as project,subproj.subproject, 
    left(detail.ref,2) as pref,right(detail.ref,9) as num
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    left join projectmasterfile as proj on proj.line=detail.projectid
    left join subproject as subproj on subproj.line=detail.subproject
    left join projectmasterfile as headproj on headproj.line=head.projectid
    where head.doc='gj' and md5(head.trno)='$trno' and detail.db <> 0
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, 
    head.clientname, head.address, head.yourref, 
    left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client,client.clientname as vendor, detail.ref, 
    date(detail.postdate) as postdate, detail.checkno, 
    detail.db, detail.cr, detail.line,head.rem,proj.name as projname, proj.code as projcode,concat(headproj.code,' - ',headproj.name) as project,subproj.subproject, 
    left(detail.ref,2) as pref,right(detail.ref,9) as num
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    left join projectmasterfile as proj on proj.line=detail.projectid
    left join subproject as subproj on subproj.line=detail.subproject
    left join projectmasterfile as headproj on headproj.line=head.projectid
    where head.doc='gj' and md5(head.trno)='$trno' and detail.db <> 0
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, 
    head.clientname, head.address, head.yourref, 
    left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client,client.clientname as vendor, detail.ref, 
    date(detail.postdate) as postdate, detail.checkno, 
    detail.db, detail.cr, detail.line,head.rem,proj.name as projname, proj.code as projcode,concat(headproj.code,' - ',headproj.name) as project,subproj.subproject, 
    left(detail.ref,2) as pref,right(detail.ref,9) as num
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    left join projectmasterfile as proj on proj.line=detail.projectid
    left join subproject as subproj on subproj.line=detail.subproject
    left join projectmasterfile as headproj on headproj.line=head.projectid
    where head.doc='gj' and md5(head.trno)='$trno' and detail.cr <> 0
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, 
    head.clientname, head.address, head.yourref, 
    left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, client.client,client.clientname as vendor, detail.ref, 
    date(detail.postdate) as postdate, detail.checkno, 
    detail.db, detail.cr, detail.line,head.rem,proj.name as projname, proj.code as projcode,concat(headproj.code,' - ',headproj.name) as project,subproj.subproject, 
    left(detail.ref,2) as pref,right(detail.ref,9) as num
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    left join projectmasterfile as proj on proj.line=detail.projectid
    left join subproject as subproj on subproj.line=detail.subproject
    left join projectmasterfile as headproj on headproj.line=head.projectid
    where head.doc='gj' and md5(head.trno)='$trno' and detail.cr <> 0";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    return $this->Maxipro_GJ_PDF($params, $data);
  }

  public function Maxipro_GJ_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $this->reportheader->getheader($params);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(450, 0, strtoupper($this->modulename), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(110, 0, "Document#   :  ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(140, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'C', false);

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4));

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "CLIENT ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(410, 20, ':  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(50, 20, "DATE ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(25, 20, ":   ", '', 'R', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(125, 20, date('M-d-Y', strtotime($data[0]['dateid'])), 'B', 'C', false);

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(0, 0, "");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "ADDRESS ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(410, 20, ':  ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 20, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(50, 20, "YOURREF ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(25, 20, ":   ", '', 'R', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(125, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'C', false);

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(0, 0, "");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "REMARKS ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(630, 20, ':  ' . (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(0, 0, "");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "PROJECT ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(630, 20, ':  ' . (isset($data[0]['project']) ? $data[0]['project'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($font, '', 9);
    PDF::MultiCell(300, 0, "Print Date: " . date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '', 'L', false, 0);
    PDF::MultiCell(425, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);
    PDF::MultiCell(700, 5, "\n");
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 5, '', 'T');
    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4));


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(50, 0, "ACCT#", 'B', 'C', false, 0);
    PDF::MultiCell(130, 0, "ACCOUNT NAME", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "PROJECT", 'B', 'C', false, 0);
    PDF::MultiCell(100, 0, "SUBPROJECT", 'B', 'C', false, 0);
    PDF::MultiCell(95, 0, "VENDOR", 'B', 'C', false, 0);
    PDF::MultiCell(75, 0, "DEBIT", 'B', 'R', false, 0);
    PDF::MultiCell(75, 0, "CREDIT", 'B', 'R', false, 0);
    PDF::MultiCell(75, 0, "REFERENCE #", 'B', 'C', false);
  }

  public function Maxipro_GJ_PDF($params, $data)
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
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetMargins(40, 40);
    $this->Maxipro_GJ_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {

        $acnonamedescs = $this->reporter->fixcolumn([$data[$i]['acnoname']], 20, 0);
        $countarr = count($acnonamedescs);

        $project = $this->reporter->fixcolumn([$data[$i]['projcode']], 15, 0);
        $countarrp = count($project);

        $subproject = $this->reporter->fixcolumn([$data[$i]['subproject']], 15, 0);
        $countarrsp = count($subproject);

        $vendor = $this->reporter->fixcolumn([$data[$i]['vendor']], 15, 0);
        $countarrv = count($vendor);

        $maxrow = max($countarr, $countarrp, $countarrsp, $countarrv);

        if ($data[$i]['acnoname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $acno =  $data[$i]['acno'];
              $ref = $data[$i]['pref'] . $data[$i]['num'];
              $debit = number_format($data[$i]['db'], $decimalcurr);
              if ($debit == 0) {
                $debit = '-';
              }

              $credit = number_format($data[$i]['cr'], $decimalcurr);
              if ($credit == 0) {
                $credit = '-';
              }
            } else {
              $acno = '';
              $ref = '';
              $debit = '';
              $credit = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0,   $acno, '', 'L', false, 0);
            PDF::MultiCell(130, 0, isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, isset($project[$r]) ? $project[$r] : '', '', 'L', false, 0);
            PDF::MultiCell(100, 0, isset($subproject[$r]) ? $subproject[$r] : '', '', 'L', false, 0);
            PDF::MultiCell(95, 0, isset($vendor[$r]) ? $vendor[$r] : '', '', 'L', false, 0);
            PDF::MultiCell(75, 0, $debit, '', 'R', false, 0);
            PDF::MultiCell(75, 0, $credit, '', 'R', false, 0);
            PDF::MultiCell(75, 0, $ref, '', 'C', false);
          }
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (PDF::getY() >= 900) { //780
          $this->Maxipro_GJ_header_PDF($params, $data);
        }
      }
    }

    PDF::MultiCell(700, 0, '', 'B');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(460, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(90, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totalcr, $decimalprice), '', 'R', false);

    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));

    PDF::MultiCell(700, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);

    PDF::MultiCell(200, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Approved By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['checked'], 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'B', 'C');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_GJ_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

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

    $this->reportheader->getheader($params);
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(520, 0, strtoupper($this->modulename), '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "DOCUMENT # : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "CUSTOMER/SUPPLIER : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "DATE : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "ADDRESS : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(490, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "REF : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(100, 0, "ACCOUNT NO.", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(75, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_GJ_PDF($params, $data)
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
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_GJ_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '');

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {

        $debit = number_format($data[$i]['db'], $decimalcurr);
        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $debit = $debit < 0 ? '-' : $debit;
        $credit = $credit < 0 ? '-' : $credit;

        $maxrow = 1;
        $arr_acno = $this->reporter->fixcolumn([$data[$i]['acno']], '16', 0);
        $arr_acnoname = $this->reporter->fixcolumn([$data[$i]['acnoname']], '40', 0);
        $arr_ref = $this->reporter->fixcolumn([$data[$i]['ref']], '16', 0);
        $arr_postdate = $this->reporter->fixcolumn([$data[$i]['postdate']], '16', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        $arr_client = $this->reporter->fixcolumn([$data[$i]['client']], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(200, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(100, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(75, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(75, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false, 'M');
          PDF::MultiCell(100, 18, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'C', 0, 1, '', '', true, 0, false, false, 'M');
        }

        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (PDF::getY() >= 900) { //780
          $this->default_GJ_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, '', 'B');

    PDF::MultiCell(700, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(475, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
