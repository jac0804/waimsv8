<?php

namespace App\Http\Classes\modules\modulereport\main;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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

class fs
{

  private $modulename;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $companyid = $config['params']['companyid'];
    $fields = ['prepared', 'checked', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    return $this->coreFunctions->opentable(
      "select
          'PDFM' as print,
          '' as prepared,
          '' as checked
       "
    );
  }

  public function report_default_query($trno)
  {
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         client.groupid,ifnull(project.code,'') as projectcode,
         hinfo.reservationdate, hinfo.dueday, head.phaseid, ph.code as phase,
         head.modelid, hm.model as housemodel, 
         head.blklotid, bl.blk as blklot, bl.lot,
         hinfo.reservationdate, ifnull(hinfo.reservationfee,0) as reservationfee, ifnull(hinfo.farea,0) as farea, 
         ifnull(hinfo.fpricesqm,0) as fpricesqm, ifnull(hinfo.ftcplot,0) as ftcplot,
         ifnull(hinfo.ftcphouse,0) as ftcphouse, ifnull(hinfo.fma1,0) as fma1, 
         ifnull(hinfo.fma2,0) as fma2, ifnull(hinfo.fma3,0) as fma3,
         ifnull(hinfo.finterestrate,0) as finterestrate, ifnull(hinfo.termspercentdp,0) as termspercentdp,
         ifnull(hinfo.termsmonth,0) as termsmonth, ifnull(hinfo.termspercent,0) as termspercent, 
         ifnull(hinfo.termsyear,0) as termsyear, ifnull(hinfo.fsellingpricegross,0) as fsellingpricegross, 
         ifnull(hinfo.fdiscount,0) as fdiscount,
         ifnull(hinfo.fsellingpricenet,0) as fsellingpricenet, ifnull(hinfo.fmiscfee,0) as fmiscfee, 
         ifnull(hinfo.fcontractprice,0) as fcontractprice, ifnull(hinfo.fmonthlydp,0) as fmonthlydp, 
         ifnull(hinfo.fmonthlyamortization,0) as fmonthlyamortization,
        ifnull(hinfo.ffi,0) as ffi, ifnull(hinfo.fmri,0) as fmri,
        ifnull(hinfo.loanamt,0) as loanamt
          ";

    $query = $qryselect . " from lahead as head
        left join cntnum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        where head.trno = " . $trno . "
        union all " . $qryselect . " from glhead as head
        left join cntnum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        where head.trno =" . $trno;

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn



  public function reportplotting($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $str = $this->reportgenplottingpdf($config, $data);

    return $str;
  }

  public function reportgenplottingpdf($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $trno = $params['params']['dataid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = '';
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\receivable\fs')->modulename;

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);

    $fontsize8 = 8;
    $fontsize9 = 9;
    $fontsize10 = 10;
    $fontsize12 = 12;

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(5, 5);
    PDF::AddPage('l', [800, 1500]);

    PDF::SetFont($font, 'b', 14);
    PDF::MultiCell(0, 20, "PAYMENT SCHEDULE", '', 'C');

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(0, 20, strtoupper($headerdata[0]->name), '', 'L');

    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(600, 20, "DATE: " . date("F d, Y", strtotime($data[0]['dateid'])), '', 'L', false, 0);
    PDF::MultiCell(600, 20, "NAME: " . $data[0]['clientname'], '', 'L');
    PDF::MultiCell(600, 20, "TERMS: " . $data[0]['termspercentdp'] . "% " . $data[0]['termsmonth'] . " mos DP; " . $data[0]['termspercent'] . "% BAL-" . $data[0]['termsyear'] . " yrs", '', 'L');

    // PDF::MultiCell(0, 20, '', '', 'L');
    $html = '<hr>';
    PDF::writeHTML($html, true, false, true, false, '');

    PDF::MultiCell(400, 20, "PROJECT: " . $data[0]['projectname'], '', 'L', false, 0);
    PDF::MultiCell(400, 20, "PHASE: " . $data[0]['phase'], '', 'L', false, 0);
    PDF::MultiCell(400, 20, "BLK & LOT: " . $data[0]['blklot'] . ' ' . $data[0]['lot'], '', 'L');
    PDF::MultiCell(400, 20, "HOUSE MODEL: " . $data[0]['housemodel'], '', 'L', false, 0);
    PDF::MultiCell(400, 20, "DATE OF RESERVATION: " . date("F d, Y", strtotime($data[0]['reservationdate'])), '', 'L', false, 0);

    $locale = 'en_US';
    $nf = numfmt_create($locale, \NumberFormatter::ORDINAL);

    PDF::MultiCell(280, 20, "DUE DATE: every " . $nf->format($data[0]['dueday']) . " of the month", '', 'L');

    // PDF::MultiCell(0, 20, "", '', 'L');
    $html = '<hr>';
    PDF::writeHTML($html, true, false, true, false, '');

    PDF::MultiCell(400, 20, "RESERVATION FEE:" . number_format($data[0]['reservationfee'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "AREA:" . number_format($data[0]['farea'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "PRICE PER SQM:" . number_format($data[0]['fpricesqm'], 2), '', 'L');
    PDF::MultiCell(400, 20, "TCP OF LOT:" . number_format($data[0]['ftcplot'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "TCP OF HOUSE:" . number_format($data[0]['ftcphouse'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "Total Selling Price(Gross):" . number_format($data[0]['fsellingpricegross'], 2), '', 'L');
    PDF::MultiCell(400, 20, "Discount:" . number_format($data[0]['fdiscount'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "Total Selling Price(Net):" . number_format($data[0]['fsellingpricenet'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "Misc. Fee:" . number_format($data[0]['fmiscfee'], 2), '', 'L');
    PDF::MultiCell(400, 20, "Total Contract Price:" . number_format($data[0]['fcontractprice'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "MONTHLY DP:" . number_format($data[0]['fmonthlydp'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "MONTHLY AMORTIZATION:" . number_format($data[0]['fmonthlyamortization'], 2), '', 'L');
    PDF::MultiCell(400, 20, "FI:" . number_format($data[0]['ffi'], 2), '', 'L', false, 0);
    PDF::MultiCell(400, 20, "MRI:" . number_format($data[0]['fmri'], 2), '', 'L', false, 0);

    PDF::MultiCell(0, 20, "", '', 'L');
    PDF::MultiCell(0, 20, "", '', 'L');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::MultiCell(73, 30, " " . "TERM", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "DUE DATE", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "PR", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "OR", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "CHECK NUMBER", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "ACTUAL PAYMENT", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "ACTUAL DATE", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "MONTHLY PAYMENT", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "INT. RATE", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "FI", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "MRI", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "INTEREST", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "PRINCIPAL", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "BALANCE (H&L)", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "BALANCE (LOT)", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "BALANCE (HOUSE)", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "TOTAL PRINCIPAL", 'TLBR', 'L', false, 0);
    PDF::MultiCell(73, 30, " " . "%", 'TLBR', 'L');

    $qry = "select detail.db,detail.postdate,detail.rem,head.projectid,head.phaseid,head.modelid,head.blklotid,hinfo.dueday,hinfo.reservationdate, ifnull(hinfo.reservationfee,0) as reservationfee, ifnull(hinfo.farea,0) as farea, ifnull(hinfo.fpricesqm,0) as fpricesqm, ifnull(hinfo.ftcplot,0) as ftcplot,
    ifnull(hinfo.ftcphouse,0) as ftcphouse, ifnull(hinfo.fma1,0) as fma1, ifnull(hinfo.fma2,0) as fma2, ifnull(hinfo.fma3,0) as fma3,
    ifnull(hinfo.finterestrate,0) as finterestrate, ifnull(hinfo.termspercentdp,0) as termspercentdp, ifnull(hinfo.termsmonth,0) as termsmonth, ifnull(hinfo.termspercent,0) as termspercent, 
    ifnull(hinfo.termsyear,0) as termsyear, ifnull(hinfo.fsellingpricegross,0) as fsellingpricegross, ifnull(hinfo.fdiscount,0) as fdiscount,
    ifnull(hinfo.fsellingpricenet,0) as fsellingpricenet, ifnull(hinfo.fmiscfee,0) as fmiscfee, ifnull(hinfo.fcontractprice,0) as fcontractprice, ifnull(hinfo.fmonthlydp,0) as fmonthlydp, ifnull(hinfo.fmonthlyamortization,0) as fmonthlyamortization,
   ifnull(hinfo.ffi,0) as ffi, ifnull(hinfo.fmri,0) as fmri,ifnull(hinfo.loanamt,0) as loanamt,di.fi,di.mri,di.interest,di.principal,di.lotbal,di.housebal,di.hlbal,di.ortrno,di.checkno,
   case di.ortrno when 0 then 0 else di.payment end as payment,di.principalcol,di.percentage,ifnull(di.paymentdate,'') as paymentdate,'' as orno 
   from lahead as head left join cntnuminfo as hinfo on hinfo.trno = head.trno left join ladetail as detail on detail.trno = head.trno
   left join coa on coa.acnoid = detail.acnoid
    left join detailinfo as di on di.trno = detail.trno and di.line = detail.line where head.trno = ? and left(coa.alias,2) in ('AR') 
    union all
    select detail.db,detail.postdate,detail.rem,head.projectid,head.phaseid,head.modelid,head.blklotid,hinfo.dueday,hinfo.reservationdate, ifnull(hinfo.reservationfee,0) as reservationfee, ifnull(hinfo.farea,0) as farea, ifnull(hinfo.fpricesqm,0) as fpricesqm, ifnull(hinfo.ftcplot,0) as ftcplot,
    ifnull(hinfo.ftcphouse,0) as ftcphouse, ifnull(hinfo.fma1,0) as fma1, ifnull(hinfo.fma2,0) as fma2, ifnull(hinfo.fma3,0) as fma3,
    ifnull(hinfo.finterestrate,0) as finterestrate, ifnull(hinfo.termspercentdp,0) as termspercentdp, ifnull(hinfo.termsmonth,0) as termsmonth, ifnull(hinfo.termspercent,0) as termspercent, 
    ifnull(hinfo.termsyear,0) as termsyear, ifnull(hinfo.fsellingpricegross,0) as fsellingpricegross, ifnull(hinfo.fdiscount,0) as fdiscount,
    ifnull(hinfo.fsellingpricenet,0) as fsellingpricenet, ifnull(hinfo.fmiscfee,0) as fmiscfee, ifnull(hinfo.fcontractprice,0) as fcontractprice, ifnull(hinfo.fmonthlydp,0) as fmonthlydp, ifnull(hinfo.fmonthlyamortization,0) as fmonthlyamortization,
   ifnull(hinfo.ffi,0) as ffi, ifnull(hinfo.fmri,0) as fmri,ifnull(hinfo.loanamt,0) as loanamt,di.fi,di.mri,di.interest,di.principal,di.lotbal,di.housebal,di.hlbal,di.ortrno,di.checkno,
   case di.ortrno when 0 then 0 else di.payment end as payment,di.principalcol,di.percentage,
    ifnull(di.paymentdate,'') as paymentdate,concat(cr.bref,cr.seq) as orno
    from glhead as head left join hcntnuminfo as hinfo on hinfo.trno = head.trno left join gldetail as detail on detail.trno = head.trno left join coa on coa.acnoid = detail.acnoid
    left join hdetailinfo as di on di.trno = detail.trno and di.line = detail.line left join cntnum as cr on cr.trno = di.ortrno where head.trno = ? and left(coa.alias,2) in ('AR') ";

    $detail = json_decode(json_encode($this->coreFunctions->opentable($qry, [$trno, $trno])), true);
    //$this->addrowusd('LRB');
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    for ($i = 0; $i < count($detail); $i++) {
      $h = 25;
      PDF::SetFont($font, '', $fontsize9);

      $rem = $detail[$i]['rem'];
      $orno = $detail[$i]['orno'];
      $checkno = $detail[$i]['checkno'];
      $postdate = date("m/d/Y", strtotime($detail[$i]['postdate']));
      $paymentdate = $detail[$i]['paymentdate'] == "" ? "" : date("m/d/Y", strtotime($detail[$i]['paymentdate']));
      $db = number_format($detail[$i]['db'], 2);
      $payment = number_format($detail[$i]['payment'], 2);
      $finterestrate = number_format($detail[0]['finterestrate'], 2);
      $fi = number_format($detail[$i]['fi'], 2);
      $mri = number_format($detail[$i]['mri'], 2);
      $interest = number_format($detail[$i]['interest'], 2);
      $principal = number_format($detail[$i]['principal'], 2);
      $hlbal = number_format($detail[$i]['hlbal'], 2);
      $lotbal = number_format($detail[$i]['lotbal'], 2);
      $housebal = number_format($detail[$i]['housebal'], 2);
      $principalcol = number_format($detail[$i]['principalcol'], 2);
      $percentage = number_format($detail[$i]['percentage'], 2) . "%";

      $arrorno = $this->reporter->fixcolumn([$orno], '14', 0);
      $arrcheckno = $this->reporter->fixcolumn([$checkno], '14', 0);
      $arrrem = $this->reporter->fixcolumn([$rem], '14', 0);
      $arrpostdate = $this->reporter->fixcolumn([$postdate], '14', 0);
      $arrdb = $this->reporter->fixcolumn([$db], '14', 0);
      $arrpay = $this->reporter->fixcolumn([$payment], '14', 0);
      $arrpaydate = $this->reporter->fixcolumn([$paymentdate], '14', 0);
      $arrfinterestrate = $this->reporter->fixcolumn([$finterestrate], '14', 0);
      $arrfi = $this->reporter->fixcolumn([$fi], '14', 0);
      $arrmri = $this->reporter->fixcolumn([$mri], '14', 0);
      $arrinterest = $this->reporter->fixcolumn([$interest], '14', 0);
      $arrprincipal = $this->reporter->fixcolumn([$principal], '14', 0);
      $arrhlbal = $this->reporter->fixcolumn([$hlbal], '14', 0);
      $arrlotbal = $this->reporter->fixcolumn([$lotbal], '14', 0);
      $arrhousebal = $this->reporter->fixcolumn([$housebal], '14', 0);
      $arrprincipalcol = $this->reporter->fixcolumn([$principalcol], '14', 0);
      $arrpercentage = $this->reporter->fixcolumn([$percentage . '%'], '14', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arrorno, $arrrem, $arrcheckno, $arrpostdate, $arrdb, $arrpay, $arrpaydate, $arrfinterestrate, $arrfi, $arrmri, $arrinterest, $arrprincipal, $arrhlbal, $arrlotbal, $arrhousebal, $arrprincipalcol, $arrpercentage]);

      $h *= $maxrow;

      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

      PDF::MultiCell(73, $h, $rem, 'TLBR', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(73, $h, $postdate, 'TLBR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(73, $h, "", 'TLBR', 'L', false, 0);
      PDF::MultiCell(73, $h, $orno, 'TLBR', 'L', false, 0);
      PDF::MultiCell(73, $h, $checkno, 'TLBR', 'L', false, 0);
      PDF::MultiCell(73, $h, $payment, 'TLBR', 'R', false, 0);
      PDF::MultiCell(73, $h, $paymentdate, 'TLBR', 'R', false, 0);

      if ($interest < 0 && $detail[$i]['percentage'] > 100) {
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $finterestrate, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, 0, 'TLBR', 'R', false, 1);
      } else {
        PDF::MultiCell(73, $h, $db, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $finterestrate, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $fi, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $mri, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $interest, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $principal, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $hlbal, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $lotbal, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $housebal, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $principalcol, 'TLBR', 'R', false, 0);
        PDF::MultiCell(73, $h, $percentage, 'TLBR', 'R', false, 1);
      }


      // $this->addrowusd('LRB');
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
