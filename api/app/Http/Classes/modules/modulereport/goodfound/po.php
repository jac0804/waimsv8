<?php

namespace App\Http\Classes\modules\modulereport\goodfound;

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

class po
{

  private $modulename = "Purchase Order";
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
    $fields = ['radioprint', 'radioreporttype', 'attention', 'prepared', 'approved', 'received'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'attention.readonly', false);
    data_set($col1, 'prepared.label', 'Ordered By');

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);


    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => 'newpo', 'color' => 'red']
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);



    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $paramstr = "select
                  'PDFM' as print,
                  'newpo' as reporttype,
                  '' as attention,
                  '' as prepared,
                  '' as approved,
                  '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }
  // qwe @123qwE123
  public function report_default_query($trno)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid, client.tin, client.tel, client.fax,client.contact,head.yourref,head.tax,head.vattype,head.ewt,head.ewtrate
        from pohead as head 
        left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid, client.tin, client.tel, client.fax,client.contact,head.yourref,head.tax,head.vattype,head.ewt,head.ewtrate
        from hpohead as head 
        left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case 'newpo':
        return $this->new_PO_PDF($params, $data);
        break;

      default:
        return $this->default_PO_PDF($params, $data);
        break;
    }
  }


  public function new_PO_header_PDF($params, $data)
  {
    $attention = $params['params']['dataparams']['attention'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::Image('images/reports/gfc.png', '45', '35', 720, 60);
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(720, 15, strtoupper($this->modulename), '', 'C', false, 1, '40', '100');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(500, 0, "EXTERNAL PROVIDER", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(220, 0, "PURCHASE ORDER NO.", 'TR', 'C', false, 1, '', '');
    PDF::SetFont($fontbold, '', $fontsize + 2);
    PDF::MultiCell(250, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'TL', 'L', false, 0, '', '');
    if ($data[0]['contact'] == '') {
      PDF::MultiCell(250, 0,  "TO: " . (isset($attention) ? $attention : $attention), 'TR', 'L', false, 0, '', '');
    } else {
      PDF::MultiCell(250, 0,  "TO: " . (isset($data[0]['contact']) ? $data[0]['contact'] : $data[0]['contact']), 'TR', 'L', false, 0, '', '');
    }

    PDF::MultiCell(220, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'TR', 'C', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(350, 0, "ADDRESS", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(150, 0, "TEL/ FAX NO", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(220, 0, "DATE-", 'TR', 'C', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(350, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'TLR', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, (isset($data[0]['tel']) ? $data[0]['tel'] : '') . '/' . (isset($data[0]['fax']) ? $data[0]['fax'] : ''), 'TLR', 'C', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize + 2);
    PDF::MultiCell(220, 0, (isset($data[0]['dateid']) ? strtoupper(date_format(date_create($data[0]['dateid']), "F d,Y")) : ''), 'TR', 'C', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, "ITEM", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(270, 0, "SPECIFICATION", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(50, 0, "UNIT", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(60, 0, "QTY", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(100, 0, "UNIT PRICE", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(120, 0, "AMOUNT", 'TR', 'C', false, 1, '', '');
  }

  public function new_PO_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }
    $this->new_PO_header_PDF($params, $data);

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'], 2);
      $uom = $data[$i]['uom'];
      $amt = number_format($data[$i]['netamt'], 2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'], 2);

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), 'TL', 'L', false, 0, '',  '');
        PDF::MultiCell(270, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'TL', 'L', false, 0, '',  '');
        PDF::MultiCell(50, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'TL', 'C', false, 0, '',  '');
        PDF::MultiCell(60, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'TL', 'C', false, 0, '',  '');
        PDF::MultiCell(100, 0, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'TL', 'R', false, 0, '',  '');
        PDF::MultiCell(120, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'TLR', 'R', false, 1, '',  '');
      }

      $totalext += $data[$i]['ext'];
    }

    for ($x = 0; $x < 3; $x++) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(120, 0, ' ', 'TL', 'L', false, 0, '',  '');
      PDF::MultiCell(270, 0, ' ', 'TL', 'L', false, 0, '',  '');
      PDF::MultiCell(50, 0, ' ', 'TL', 'C', false, 0, '',  '');
      PDF::MultiCell(60, 0, ' ', 'TL', 'C', false, 0, '',  '');
      PDF::MultiCell(100, 0, ' ', 'TL', 'R', false, 0, '',  '');
      PDF::MultiCell(120, 0, ' ', 'TLR', 'R', false, 1, '',  '');
    }


    $vattype = $data[0]['vattype'];
    $tax = $data[0]['tax'];
    $ewt = $data[0]['ewt'];
    $ewtrate = floatval($data[0]['ewtrate']);
    $ewtamt = 0;
    $netamt = 0;
    $vatlabel = '';
    if ($vattype == 'VATABLE') {
      $netamt = $totalext / 1.12;
      $ewtamt = $netamt * ($ewtrate / 100);
      $vatlabel = 'VAT INCLUSIVE';
    } else {
      $ewtamt = $totalext * ($ewtrate / 100);
      $vatlabel = 'VAT EXCLUSIVE';
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, "", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(270, 0, "LESS: " . $ewtrate . "% TAX WITHHELD " . $totalext, 'TLR', 'L', false, 0, '', '');
    PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(60, 0, "", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(100, 0, "", 'TLR', 'C', false, 0, '', '');
    PDF::MultiCell(120, 0, "(" . number_format($ewtamt, 2) . ")", 'TR', 'C', false, 1, '', '');

    $vat = number_format($totalext - $ewtamt, 2);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, "REMARKS: ", 'TL', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(320, 0, $data[0]['rem'], 'T', 'L', false, 0, '', '');
    PDF::MultiCell(50, 0, "", 'T', 'C', false, 0, '', '');
    PDF::MultiCell(60, 0, "", 'T', 'C', false, 0, '', '');
    PDF::MultiCell(220, 0, "TOTAL P " . $vat, 'TLR', 'C', false, 1, '', '');

    PDF::MultiCell(70, 0, "", 'BL', 'L', false, 0, '', '');
    PDF::MultiCell(320, 0, "", 'B', 'L', false, 0, '', '');
    PDF::MultiCell(50, 0, "", 'B', 'C', false, 0, '', '');
    PDF::MultiCell(60, 0, "", 'B', 'C', false, 0, '', '');
    PDF::MultiCell(220, 0, $vatlabel, 'LBR', 'C', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 0, 'Terms & Condition:', 'LR', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize - 2);
    PDF::MultiCell(30, 0, '1.', 'L', 'L', false, 0, '', '');
    PDF::MultiCell(690, 0, 'External Provider are required to submit Company Profile, DTI / SEC Registration, BIR Certification, Mayor`s/Bus Permit and ISO Cert.', 'R', 'L', false, 1, '', '');

    PDF::MultiCell(30, 0, '', 'L', 'L', false, 0, '', '');
    PDF::MultiCell(690, 0, '(if your company/organization is ISO certified)', 'R', 'L', false, 1, '', '');

    PDF::MultiCell(30, 0, '2.', 'L', 'L', false, 0, '', '');
    PDF::MultiCell(690, 0, 'External Provider are required to provide MSDS (Material Safety Data Sheet) upon purchased/delivery of product w/ chemicals/ hazardous contents.', 'R', 'L', false, 1, '', '');


    PDF::MultiCell(30, 0, '3.', 'L', 'L', false, 0, '', '');
    PDF::MultiCell(690, 0, 'External Provider in any event upon controllable situation failed to comply/deliver within given lead time (Manila P.O.`s- 12 days local P.O.`s 9 working days)', 'R', 'L', false, 1, '', '');

    PDF::MultiCell(30, 0, '', 'L', 'L', false, 0, '', '');
    PDF::MultiCell(690, 0, 'receive demand letters and will automatically be removed from Updated List of Acceptable External Provider; may re-apply after a year.', 'R', 'L', false, 1, '', '');

    $ordered = $params['params']['dataparams']['prepared'];
    $approved = $params['params']['dataparams']['approved'];
    $received = $params['params']['dataparams']['received'];


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(280, 0, '', 'TLR', 'L', 0, 0, '', '');
    PDF::MultiCell(160, 0, 'ORDERED BY : ', 'TLR', 'L', 0, 0, '', '');
    PDF::MultiCell(280, 0, 'CONFIRMED BY : ', 'TLR', 'L', 0, 1, '', '');

    PDF::MultiCell(280, 0, 'TERMS OF PAYMENTS : ', 'LR', 'L', 0, 0, '', '');
    PDF::MultiCell(160, 0, '', 'LR', 'L', 0, 0, '', '');
    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 1, '', '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'LR', 'L', 0, 0, '', '');

    PDF::MultiCell(10, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(140, 0, '', 'B', 'L', 0, 0, '', '');
    PDF::MultiCell(10, 0, '', 'R', 'L', 0, 0, '', '');

    PDF::MultiCell(10, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(260, 0, '', 'B', 'L', 0, 0, '', '');
    PDF::MultiCell(10, 0, '', 'R', 'L', 0, 1, '', '');


    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 0, '', '');

    PDF::MultiCell(10, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(140, 0, $ordered, 'T', 'C', 0, 0, '', '');
    PDF::MultiCell(10, 0, '', 'R', 'L', 0, 0, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(260, 0, 'EXTERNAL PROVIDER Rep./MGR', 'T', 'C', 0, 0, '', '');
    PDF::MultiCell(10, 0, '', 'R', 'L', 0, 1, '', '');


    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 0, '', '');
    PDF::MultiCell(160, 0, '', 'LR', 'L', 0, 0, '', '');
    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 1, '', '');

    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 0, '', '');
    PDF::MultiCell(160, 0, 'APPROVED BY : ', 'LR', 'L', 0, 0, '', '');
    PDF::MultiCell(50, 0, 'DATE : ', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(150, 0, '', 'B', 'L', 0, 0, '', '');
    PDF::MultiCell(80, 0, '', 'R', 'L', 0, 1, '', '');


    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 0, '', '');

    PDF::MultiCell(10, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(140, 0, '', 'B', 'L', 0, 0, '', '');
    PDF::MultiCell(10, 0, '', 'R', 'L', 0, 0, '', '');

    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 1, '', '');

    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 0, '', '');


    PDF::SetFont($fontbold, '', $fontsize - 2);
    PDF::MultiCell(10, 0, '', 'L', 'L', 0, 0, '', '');
    if ($approved == '') {
      PDF::MultiCell(140, 0, 'MR. BEN LUN JUANG / President', 'T', 'C', 0, 0, '', '');
    } else {
      PDF::MultiCell(140, 0, $approved, 'T', 'C', 0, 0, '', '');
    }
    PDF::MultiCell(10, 0, '', 'R', 'L', 0, 0, '', '');

    PDF::MultiCell(280, 0, '', 'LR', 'L', 0, 1, '', '');


    PDF::SetFont($font, '', $fontsize - 4);
    PDF::MultiCell(720, 20, 'PUR01.10 18JUL19 REV2 RET3', 'T', 'R', false, 1);



    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_PO_header_PDF($params, $data)
  {
    $attention = $params['params']['dataparams']['attention'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, 'B', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    PDF::MultiCell(0, 0, "\n", '');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(720, 25, strtoupper($this->modulename), 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 50, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 30, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 25, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);

    // >>>>>> FIX SET UP TEXT
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(80, 0, "External Provider : ", '', 'L', false, 0, '50',  '105');
    PDF::MultiCell(80, 0, "TIN : ", '', 'L', false, 0, '50',  '125');
    PDF::MultiCell(80, 0, "PO No. : ", '', 'L', false, 0, '610',  '105');
    PDF::MultiCell(80, 0, "Date : ", '', 'L', false, 0, '610',  '155');
    PDF::MultiCell(80, 0, "Address : ", '', 'L', false, 0, '50',  '155');
    PDF::MultiCell(80, 0, "Attention To : ", '', 'L', false, 0, '50',  '185');
    PDF::MultiCell(80, 0, "Tel No. ", '', 'L', false, 0, '325',  '185');
    PDF::MultiCell(80, 0, "Fax No. ", '', 'L', false, 0, '545',  '185');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '130',  '105', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0, '80',  '124', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '650',  '120', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']), "d-M-Y") : ''), '', 'L', false, 0, '650',  '155', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '100',  '155', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($attention) ? $attention : ''), '', 'L', false, 0, '115',  '183', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), '', 'L', false, 0, '365',  '183', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['fax']) ? $data[0]['fax'] : ''), '', 'L', false, 0, '585',  '183', true, 0, false, true, 0, 'T', true);


    // VERTICAL LINE
    PDF::MultiCell(0, 80, '', 'L', 'C', 0, 1, '600', '98', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(0, 25, '', 'L', 'C', 0, 1, '320', '178', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(0, 25, '', 'L', 'C', 0, 1, '540', '178', true, 0, false, true, 0, 'M', true);

    // END FIX SETUP

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(125, 25, "PRODUCT", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(270, 25, "SPECIFICATION", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "UNIT", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "QUANTITY", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "UNIT PRICE", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "AMOUNT (PHP)", 'TBLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
  }

  public function default_PO_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }
    $this->default_PO_header_PDF($params, $data);

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'], 2);
      $uom = $data[$i]['uom'];
      $amt = number_format($data[$i]['netamt'], 2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'], 2);

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(125, 20, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(270, 20, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 20, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 20, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 20, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      }

      $totalext += $data[$i]['ext'];
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(125, 20, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(270, 20, ' ', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 20, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 20, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 20, ' ', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(125, 20, ' ', 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(270, 20, 'LESS WITHHOLDING TAX', 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 20, ' ', 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 20, ' ', 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 20, ' ', 'TLB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, 'PHP 0.00', 'TRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', 9);
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::MultiCell(45, 40, 'Remarks : ', 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(500, 40, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 40, 'TOTAL : ', 'TLB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(125, 40, 'PHP ' . number_format($totalext, $decimalcurr), 'TRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(
      720,
      50,
      '<b>Terms & Condition:</b><br>
      1.&nbsp;&nbsp;&nbsp;Suppliers are required to submit their Company Profile, DTI Certificate, Mayors Permit, Sec. Registration, and ISO <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Certificate
      (if your company is ISO certified).<br>
      2.&nbsp;&nbsp;&nbsp;Suppliers are also required to give a supporting SDS (Safety Data Sheet) to all Hazardous chemicals and materials ordered.<br>
      3.&nbsp;&nbsp;&nbsp;Suppliers who fails to deliver within the lead time Manila P.O.\'s working days Local P.O.\'s working days will receive demand
      letters and will be <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;automatically be removed from the list of acceptable suppliers.
      ',
      'TLRB',
      'L',
      0,
      1,
      '',
      '',
      true,
      0,
      true,
      true,
      1,
      'T',
      true
    );

    PDF::SetCellPaddings(0, 0, 0, 0);

    PDF::MultiCell(0, 0, "");

    PDF::SetCellPaddings(4, 4, 4, 4);

    PDF::startTransaction();
    PDF::MultiCell(240, 20, 'TERMS OF PAYMENTS : ', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, 'ORDERED BY : ________________________', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, 'CONFIRMED BY : ________________________', 'TLR', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::MultiCell(240, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'LR', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, '', 'LR', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, 'SUPPLIERS REP. / MGR.&nbsp;&nbsp;&nbsp;&nbsp;', 'LR', 'R', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::MultiCell(240, 20, '', 'LRB', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, 'MS. VENUS JAVIERTO&nbsp;&nbsp;&nbsp;&nbsp;', 'LRB', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, 'DATE : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;________________________', 'LRB', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(65, 20, 'BIR Permit No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(100, 20, '0813-ELTRD-CAS-00211', 'B', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(55, 20, 'Date Issued:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(80, 20, (isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']), 'F j, Y') : ''), 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    PDF::MultiCell(65, 20, 'Series No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, 'PO4000001-PO4000049', 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    PDF::MultiCell(65, 20, 'Printed Date:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, date_format(date_create($current_timestamp), 'm/d/Y'), 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    PDF::commitTransaction();



    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
