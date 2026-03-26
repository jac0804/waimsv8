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

class pu
{

  private $modulename = "Material Purchase Order";
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
    $fields = ['radioprint', 'attention', 'prepared', 'approved', 'received'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'attention.readonly', false);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $paramstr = "select
                  'PDFM' as print,
                  '' as attention,
                  '' as prepared,
                  '' as approved,
                  '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }
  // qwe @123qwE123
  public function report_default_query($trno)
  {
    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid, client.tin, client.tel, client.fax
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='pu' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid, client.tin, client.tel, client.fax
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='pu' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    return $this->default_PO_PDF($params, $data);
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
