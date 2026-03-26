<?php

namespace App\Http\Classes\modules\modulereport\pdpi;

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

class ts
{
  private $modulename = "Transfer Slip";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'releaseby', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'releaseby.readonly', false);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared,
      '' as releaseby
    ");
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "
    select head.vattype, head.tax,  stock.rem as remarks,  
    client.tel, wh.tel as wtel,  date(head.dateid) as dateid, 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,
    item.itemname, sum(stock.isqty) as qty, stock.uom, 
    wh.client  as swh, 
    wh.clientname as whname,stock.expiry, wh.addr, 
    client.addr as fromaddr
    from lahead as head 
    left join lastock as stock on stock.trno=head.trno 
    left join client on client.client=head.client
    left join client as wh on wh.clientid = stock.whid
    left join item on item.itemid=stock.itemid
    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
    group by 
    head.vattype, head.tax,  stock.rem,  
    client.tel, wh.tel,  date(head.dateid), 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,
    item.itemname, stock.uom, 
    wh.client , 
    wh.clientname,stock.expiry, wh.addr, 
    client.addr
    union all
    select head.vattype, head.tax,  stock.rem as remarks,  
    client.tel, wh.tel as wtel,  date(head.dateid) as dateid, 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,
    item.itemname, sum(stock.isqty) as qty, stock.uom, 
    wh.client  as swh, 
    wh.clientname as whname,stock.expiry, wh.addr, 
    client.addr as fromaddr
    from glhead as head 
    left join glstock as stock on stock.trno=head.trno 
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid 
    left join client as wh on wh.clientid=stock.whid
    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
    group by 
    head.vattype, head.tax,  stock.rem,  
    client.tel, wh.tel,  date(head.dateid), 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,
    item.itemname, stock.uom, 
    wh.client , 
    wh.clientname,stock.expiry, wh.addr, 
    client.addr
    order by itemname";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->PDPI_TS_PDF($params, $data);
    }
  }

  public function PDPI_TS_header_PDF($params, $data)
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::MultiCell(0, 20, "\n\n");


    PDF::Image($this->companysetup->getlogopath($params['params']) . 'pdpi.png', '320', '10', 150, 84);

    PDF::MultiCell(0, 20, "\n");
    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(185, 0, '', '', 'C', 0, 0);
    PDF::MultiCell(350, 0, strtoupper($headerdata[0]->address), '', 'C', 0, 0);
    PDF::MultiCell(185, 0, '', '', 'C', 0, 1);
    PDF::MultiCell(0, 10, "\n");
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->tel), '', 'C');


    PDF::MultiCell(0, 20, "\n");


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Source WH: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Destination", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "", '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(110, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(430, 0, "DESCRIPTION", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function PDPI_TS_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalqty = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDPI_TS_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;
      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'], 2);
      $uom = $data[$i]['uom'];

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);


      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(110, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(430, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      $totalqty += $data[$i]['qty'];
      if (PDF::getY() > 900) {
        $this->PDPI_TS_header_PDF($params, $data);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 15, 'GRAND TOTAL QTY', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(80, 15, number_format($totalqty, $decimalcurr), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(80, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(430, 15, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(75, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(625, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(175, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Received By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Release By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(175, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['received'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['releaseby'], '', 'L');
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
