<?php

namespace App\Http\Classes\modules\modulereport\maxipro;

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
use App\Http\Classes\reportheader;

class rr
{
  private $modulename = "Receiving Items";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
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

    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'approved':
          $approved = $value->fieldvalue;
          break;
      }
    }

    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '$user' as prepared,
      '$approved' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select head.docno,head.trno, head.clientname, head.address, date_format(head.dateid, '%M %d, %Y') as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model,stock.ref
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno'
        union all
        select head.docno, head.trno, head.clientname, head.address, date_format(head.dateid, '%M %d, %Y') as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model,stock.ref
        from (glhead as head
        left join glstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno'
        order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config, $data)
  {
    ini_set('memory_limit', '-1');
    return $this->Inventory_RR_PDF($config, $data);
  }

  public function Inventory_RR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize10 = 10;
    $fontsize = 11;
    $fontsize12 = 12;
    $fontsize13 = 13;
    $fontsize14 = 14;
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

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(460, 0, 'RECEIVING ITEMS', '', 'L', false, 0, '',  '140');
    PDF::SetFont($fontbold, '', $fontsize14);
    PDF::MultiCell(100, 0, "Document #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize14);
    PDF::MultiCell(140, 0, $data[0]['docno'], 'B', 'C', false, 0, '',  '');

    PDF::MultiCell(520, 0, "", '', 'L', false, 0, '',  '');

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4));
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize13);
    PDF::MultiCell(80, 0, "Supplier ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize12);
    PDF::MultiCell(390, 25, ': ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(15, 0, '', '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize13);
    PDF::MultiCell(50, 0, "Date ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize12);
    PDF::MultiCell(165, 25, ': ' . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize13);
    PDF::MultiCell(80, 0, "Address ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize12);
    PDF::MultiCell(390, 25, ': ' . (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(15, 0, '', '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize13);
    PDF::MultiCell(50, 0, "Terms ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize12);
    PDF::MultiCell(165, 25, ': ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(300, 0, "Print Date: " . date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '', 'L', false, 0);
    PDF::MultiCell(425, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(700, 10, '', '');
    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4));

    PDF::MultiCell(75, 25, "QTY", 'B', 'C', false, 0);
    PDF::MultiCell(60, 25, "UNIT", 'B', 'C', false, 0);
    PDF::MultiCell(180, 25, "DESCRIPTION", 'B', 'C', false, 0);
    PDF::MultiCell(115, 25, "PO REFERENCE", 'B', 'C', false, 0);
    PDF::MultiCell(110, 25, "UNIT PRICE", 'B', 'R', false, 0);
    PDF::MultiCell(60, 25, "(+/-) %", 'B', 'R', false, 0);
    PDF::MultiCell(100, 25, "TOTAL", 'B', 'R', false);
  }

  public function Inventory_RR_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 25;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetMargins(40, 40);
    $this->Inventory_RR_header_PDF($params, $data);

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '30', 0);
        $maxrow = 1;
        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(75, 0, number_format($data[$i]['qty'], 2), 'B', 'C', false, 0);
          PDF::MultiCell(60, 0, $data[$i]['uom'], 'B', 'C', false, 0);
          PDF::MultiCell(180, 0, $data[$i]['itemname'], 'B', 'C', false, 0);
          PDF::MultiCell(115, 0, $data[$i]['ref'], 'B', 'C', false, 0);
          PDF::MultiCell(110, 0, number_format($data[$i]['gross'], $decimalcurr), 'B', 'C', false, 0);
          PDF::MultiCell(60, 0, $data[$i]['disc'], 'B', 'C', false, 0);
          PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalcurr), 'B', 'C', false);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $barcode =  $data[$i]['barcode'];
              $qty = number_format($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['gross'], $decimalcurr);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], $decimalcurr);
              $ref = $data[$i]['ref'];
            } else {
              $barcode = '';
              $qty = '';
              $uom = '';
              $amt = '';
              $disc = '';
              $ext = '';
              $ref = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(75, 0, $qty, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(180, 0, (isset($itemname[$r]) ? $itemname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(115, 0, $ref, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(110, 0, $amt, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0, $disc, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ext, '', 'R', false, 1, '', '', false, 1);
          }
        }
        $totalext += $data[$i]['ext'];

        // if (intVal($i) + 1 == $page) {
        //   $this->Inventory_RR_header_PDF($params, $data);
        //   $page += $count;
        // }

        if (PDF::getY() > 920) {
          $this->Inventory_RR_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::MultiCell(700, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 0, "", "T");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(203, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(203, 0, 'Received By: ', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(203, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(203, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(203, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(203, 0, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
