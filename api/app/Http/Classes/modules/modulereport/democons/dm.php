<?php

namespace App\Http\Classes\modules\modulereport\democons;

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

class dm
{

  private $modulename = "Purchase Return";
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

public function createreportfilter($config){
  
    $fields = ['radioprint','prepared','approved','received','print'];
    $col1 = $this->fieldClass->create($fields);
    
    data_set($col1, 'radioprint.options', [
    ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
  ]);
  return array('col1'=>$col1);
}

public function reportparamsdata($config){
  $user = $config['params']['user'];
  $qry="select name from useraccess where username='$user'";
  $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

  if((isset($name[0]['name']) ? $name[0]['name'] : '')!=''){
    $user = $name[0]['name'];
  }
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '$user' as prepared,
      '' as approved,
      '' as received
      ");
}

public function report_default_query($trno)
{
  $query = "select m.model_name as model,item.sizeid,date(head.dateid) as dateid, head.docno,head.wh, client.client, client.clientname,
  head.address, head.terms,head.rem, item.barcode,
  item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext,stock.ref,date(stock.expiry) as expiry
  from lahead as head
  left join lastock as stock on stock.trno=head.trno
  left join client on client.client=head.client
  left join item on item.itemid = stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  where head.doc='dm' and head.trno ='$trno'
  union all
  select m.model_name as model,item.sizeid,
  date(head.dateid) as dateid, head.docno, wh.client as wh,client.client, client.clientname,
  head.address, head.terms,head.rem, item.barcode,
  item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext,stock.ref,date(stock.expiry) as expiry
  from glhead as head
  left join glstock as stock on stock.trno=head.trno
  left join client as wh on wh.clientid=head.whid
  left join client on client.clientid=head.clientid
  left join item on item.itemid=stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  where head.doc='dm' and head.trno ='$trno' ";

  $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  return $result;
}//end fn


public function reportplotting($params, $data) 
{
  return $this->default_DM_PDF($params, $data);
}

public function default_DM_header_PDF($params, $data)
{
  $center = $params['params']['center'];
  $username = $params['params']['user'];
  //$width = 800; $height = 1000;

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

  PDF::MultiCell(0, 0, "\n");
 
  PDF::SetFont($fontbold, '', 14);
  PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
  PDF::SetFont($font, '', 12);
  PDF::MultiCell(0, 0,$headerdata[0]->address."\n".$headerdata[0]->tel."\n\n\n", '', 'C');

  PDF::Image($this->companysetup->getlogopath($params['params']).'sbc.png', '40', '20', 120, 65);
 

  // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
  PDF::SetFont($fontbold, '', 18);
  PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
  PDF::SetFont($fontbold, '', $fontsize);
  PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
  PDF::SetFont($font, '', 10);
  PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

  PDF::SetFont($font, '', $fontsize);
  PDF::MultiCell(0, 30, "", '', 'L');
  PDF::SetFont($fontbold, '', $fontsize);
  PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
  PDF::SetFont($font, '', $fontsize);
  PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
  PDF::SetFont($fontbold, '', $fontsize);
  PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
  PDF::SetFont($font, '', $fontsize);
  PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

  PDF::MultiCell(0, 0, "\n");

  PDF::SetFont($fontbold, '', $fontsize);
  PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
  PDF::SetFont($font, '', $fontsize);
  PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
  PDF::SetFont($fontbold, '', $fontsize);
  PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
  PDF::SetFont($font, '', $fontsize);
  PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0, '',  '');

  PDF::MultiCell(0, 0, "\n\n\n");

  PDF::SetFont($font, '', 5);
  PDF::MultiCell(700, 0, '', 'T');

  PDF::SetFont($font, 'B', 12);
  PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
  PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
  PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
  PDF::MultiCell(270, 0, "DESCRIPTION", '', 'L', false, 0);
  PDF::MultiCell(80, 0, "UNIT PRICE", '', 'R', false, 0);
  PDF::MultiCell(70, 0, "(+/-) %", '', 'R', false, 0);
  PDF::MultiCell(80, 0, "TOTAL", '', 'R', false);

  PDF::SetFont($font, '', 5);
  PDF::MultiCell(700, 0, '', 'B');
}

public function default_DM_PDF($params, $data)
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
  $this->default_DM_header_PDF($params, $data);

  PDF::SetFont($font, '', 5);
  PDF::MultiCell(700, 0, '', '');
  
  $countarr = 0;

  if (!empty($data)) {
    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $barcode = $data[$i]['barcode'];
      $qty = number_format($data[$i]['qty'],$decimalqty);
      $uom = $data[$i]['uom'];
      $itemname = $data[$i]['itemname'];
      $amt = number_format($data[$i]['amt'],$decimalcurr);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'],$decimalcurr);

      $arr_barcode = $this->reporter->fixcolumn([$barcode],'16',0);
      $arr_qty = $this->reporter->fixcolumn([$qty],'13',0);
      $arr_uom = $this->reporter->fixcolumn([$uom],'13',0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname],'35',0);
      $arr_amt = $this->reporter->fixcolumn([$amt],'13',0);
      $arr_disc = $this->reporter->fixcolumn([$disc],'13',0);
      $arr_ext = $this->reporter->fixcolumn([$ext],'13',0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_qty, $arr_uom, $arr_itemname, $arr_amt, $arr_disc, $arr_ext]);

      for($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 1);
        PDF::MultiCell(50, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(270, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(80, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(70, 0, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(80, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', false, 1);
      }
      $totalext += $data[$i]['ext'];
  
      if (intVal($i) + 1 == $page) {
        $this->default_DM_header_PDF($params, $data);
        $page += $count;
      }
    }
  }

  PDF::SetFont($font, '', 5);
  PDF::MultiCell(700, 0, '', 'B');

  PDF::SetFont($font, '', 5);
  PDF::MultiCell(700, 0, '', '');
  PDF::SetFont($fontbold, '', $fontsize);
  PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
  PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

  PDF::MultiCell(0, 0, "\n");

  PDF::SetFont($font, '', $fontsize);
  PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
  PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

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
