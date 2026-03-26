<?php

namespace App\Http\Classes\modules\modulereport\kinggeorge;

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

class cm
{
  private $modulename = "SALES RETURN";
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
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($trno)
  { 

    $query = "select head.vattype, head.tax, client.tel, stock.rem as remarks, m.model_name as model,item.sizeid,
      date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms, head.rem,head.yourref,head.ourref,
      item.barcode,item.brand,
      item.itemname, stock.rrqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, stock.ref,ag.clientname as agname,
      ag.client as agcode,wh.client as whcode,wh.clientname as whname, stock.line
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client on client.client=head.client
      left join client as ag on ag.client=head.agent
      left join client as wh on wh.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      left join model_masterfile as m on m.model_id=item.model
      where head.doc='cm' and md5(head.trno)='" . md5($trno) . "'
      union all
      select head.vattype, head.tax, client.tel, stock.rem as remarks, m.model_name as model,item.sizeid,
      date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms, head.rem,head.yourref,head.ourref,
      item.barcode,item.brand,
      item.itemname, stock.rrqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, stock.ref,ag.clientname as agname,
      ag.client as agcode,wh.client as whcode,wh.clientname as whname, stock.line
      from glhead as head left join glstock as stock on stock.trno=head.trno
      left join client on client.clientid=head.clientid
      left join item on item.itemid=stock.itemid
      left join model_masterfile as m on m.model_id=item.model
      left join client as ag on ag.clientid=head.agentid
      left join client as wh on wh.clientid=stock.whid
      where head.doc='cm' and md5(head.trno)='" . md5($trno) . "'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data) {
    return $this->default_CM_PDF($params, $data);
  }

  public function default_CM_header_PDF($params, $data){
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 9);
    PDF::SetFont($fontbold, '', 14);
    PDF::SetTextColor(2,128,9);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C', false);
    PDF::SetTextColor(0,0,0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 0, '', '', 'C', false, 0);
    PDF::MultiCell(400, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel), '', 'C', false, 0);
    PDF::MultiCell(150, 0, '', '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n\n\n");
   
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(480, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(100, 0, "Document# : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(140, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 3, 'color' => 'black'));

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 25, "SUPPLIER", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 25, ': '.(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(20, 25, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 25, "DATE", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(140, 25, ': '.(isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 25, "ADDRESS", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 25, ': '.(isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(20, 25, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 25, "TERMS", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(140, 25, ': '.(isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(400, 0,'Print Date : ' . date_format(date_create($current_timestamp), 'm/d/Y  h:i:s A'), '', 'L', false, 0);
    PDF::MultiCell(350, 0,' ' . 'Page    ' . PDF::PageNo().'    of    '.PDF::getAliasNbPages(), '', 'R');

    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => 'black'));
    PDF::MultiCell(0, 0, "\n", 'B');
    PDF::SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => 'black'));


    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(70, 25, "QTY", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(85, 25, "UNIT", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(265, 25, "DESCRIPTION", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(85, 25, "UNIT PRICE", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(85, 25, "(+/-) %", 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(130, 25, "TOTAL", 'B', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

  }

  public function default_CM_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_CM_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'],2);
      $uom = $data[$i]['uom'];
      $amt = number_format($data[$i]['amt'],2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'],2);

      $arr_barcode = $this->reporter->fixcolumn([$barcode],'15',0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname],'35',0);
      $arr_qty = $this->reporter->fixcolumn([$qty],'13',0);
      $arr_uom = $this->reporter->fixcolumn([$uom],'13',0);
      $arr_amt = $this->reporter->fixcolumn([$amt],'13',0);
      $arr_disc = $this->reporter->fixcolumn([$disc],'13',0);
      $arr_ext = $this->reporter->fixcolumn([$ext],'15',0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
      for($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(70, 20, ' '. (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 20, ' '. (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(265, 20, ' '. (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 20, ' '. (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(85, 20, ' '. (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(130, 20, ' '. (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() > 900) {
        $this->default_CM_header_PDF($params, $data);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => 'black'));
    PDF::MultiCell(600, 20, 'GRAND TOTAL: ', 'B', 'R', false, 0);
    PDF::MultiCell(120, 20, number_format($totalext, $decimalcurr), 'B', 'R');
    // PDF::MultiCell(0, 0, "", 'B');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE : ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Received By: ', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Approved By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

}
