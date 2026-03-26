<?php

namespace App\Http\Classes\modules\modulereport\housegem;

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
    $fields = ['radioprint', 'radiostatus', 'prepared', 'received', 'approved',];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    data_set($col1, 'received.label', 'Noted by');

    data_set($col1, 'radiostatus.label', 'Report Type');
    data_set($col1, 'radiostatus.options', [
      ['label' => 'Layout 1', 'value' => '1', 'color' => 'blue'],
      ['label' => 'Layout 2', 'value' => '2', 'color' => 'blue']
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $paramstr = "select
                  'PDFM' as print,
                  '1' as status,
                  '' as prepared,
                  '' as approved,
                  '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }
  // qwe @123qwE123
  public function report_default_query($trno)
  {
    $query = "select head.trno,date(head.dateid) as dateid, head.docno, 
                     client.clientname, head.address,head.terms,head.rem, head.yourref,
                     item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, 
                     stock.disc, stock.ext,stock.sortline,stock.line,info.prevamt,date(info.prevdate) as prevdate
              from pohead as head 
              left join postock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid = stock.itemid
              left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='po' and head.trno='$trno'
              union all
              select head.trno,date(head.dateid) as dateid, head.docno,  
                     client.clientname,head.address, head.terms,head.rem, head.yourref,
                     item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, 
                     stock.disc, stock.ext,stock.sortline,stock.line,info.prevamt,date(info.prevdate) as prevdate
              from hpohead as head 
              left join hpostock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid = stock.itemid
              left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='po' and head.trno='$trno' order by sortline,line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {

    // if ($params['params']['dataparams']['print'] == "default") {
    //   return $this->default_po_layout($params, $data);
    // } else if ($params['params']['dataparams']['print'] == "PDFM") {
    //   // return $this->default_PO_PDF($params, $data);
    //   return $this->hgc_PO_PDF($params, $data);
    // }
    $reporttype = $params['params']['dataparams']['status'];

    switch ($reporttype) {
      case 1:
        return $this->hgc_PO_PDF($params, $data);
        break;
      case 2:
        return $this->hgc_PO_PDF_2($params, $data);
        break;
    }
  }

  //start new layout
  public function H1_layout($params, $data, $position)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($fontbold, '', 30);

    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    if ($position == 'secP') {
      PDF::MultiCell(720, 20, '', '', 'L', 1, 0, 15, 10);
    } else {
      PDF::MultiCell(720, 20, '', '', 'L', 1, 0, 15);
    }
    PDF::SetFillColor(255, 255, 255);
    PDF::MultiCell(80, 20, '', '', 'L', 1);

    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(720, 20, '', '', 'L', 1, 0, 15);
    PDF::SetFillColor(255, 255, 255);
    PDF::MultiCell(80, 20, '', '', 'L', 1);


    PDF::SetFont($fontbold, '', 20);
    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(450, 20, 'HOUSEGEM CONSTRUCTION ELEMENTS', '', 'L', 1, 0, 25, 15);

    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(350, 20, 'CORPORATION', '', 'L', 1, 0, 25, 45);

    PDF::SetFont($fontbold, '', 25);
    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(250, 20, 'PURCHASE ORDER', '', 'R', 1, 0, 450, 15);

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, 25,  100);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(190, 18, "P.O. NO. ", '', 'R', false, 0, '',  '');
    PDF::SetTextColor(170, 4, 45); //cherry
    PDF::MultiCell(490, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false);


    $date = $data[0]['dateid'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(110, 0, "", '', 'R', false, 0, '',  '');
    PDF::MultiCell(285, 18, "DATE ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(390, 18, isset($date) ? $date : '', '', 'L', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(255, 0, 0);
    PDF::MultiCell(110, 0, "", '', 'R', false, 0, '',  '');
    PDF::MultiCell(285, 18, "", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(390, 18, '', '', 'L', false);
  }

  public function hgc_PO_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(5, 18, "", '', 'L', false, 0);
    PDF::MultiCell(190, 18, "VENDOR", '', 'L', false);


    PDF::SetFont($fontbold, '', 30);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(690, 20, '', '', 'L', 1, 0, 90, 190);


    PDF::SetFont($fontbold, '', 13);
    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(670, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'C', 1, 0, 90, 200);
    PDF::MultiCell(5, 0, '', '', 'C', 1);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 9);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(740, 0, "Page " . PDF::PageNo() . "  ", '', 'R', false, 0, 30, 270);

    PDF::SetFont($fontbold, '', 22);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(740, 20, '', '', 'L', 1, 0, 30, 290);
    PDF::MultiCell(1, 0, '', '', 'C', 1);

    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [128, 128, 128]];
    PDF::SetLineStyle($style);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'TLR', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "SHIPPING METHOD", 'LR', 'C', false, 0);
    PDF::MultiCell(340, 0, "PAYMENT TERMS", 'R', 'C', false, 0);
    PDF::MultiCell(200, 0, "DELIVERY DATE", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LRB', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetTextColor(0, 0, 0);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LR', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'R', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LR', 'C', false, 0);
    PDF::MultiCell(340, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'R', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LRB', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);


    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($fontbold, '', 22);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(740, 20, '', '', 'L', 1, 0, 30, 370);
    PDF::MultiCell(1, 0, '', '', 'C', 1);

    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [128, 128, 128]];
    PDF::SetLineStyle($style);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'TLR', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(250, 0, "", 'TR', 'L', false, 0);
    PDF::MultiCell(140, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(95, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(115, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "QTY", 'LR', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", 'R', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", 'R', 'C', false, 0);
    PDF::MultiCell(140, 0, "PRICE/KG", 'R', 'C', false, 0);
    PDF::MultiCell(95, 0, "UNIT PRICE", 'R', 'C', false, 0);
    PDF::MultiCell(115, 0, "LINE TOTAL", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'LRB', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(250, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(140, 0, "", 'RB', 'R', false, 0);
    PDF::MultiCell(95, 0, "", 'RB', 'R', false, 0);
    PDF::MultiCell(115, 0, "", 'RB', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFillColor(204, 85, 0); //burnt orange
    // PDF::MultiCell(115, 0, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'TLR', 'C', 1, 0);
    PDF::MultiCell(70, 0, "", 'TRL', 'C', 1, 0);
    PDF::MultiCell(250, 0, "", 'TRL', 'L', 1, 0);
    PDF::MultiCell(70, 0, "", 'TR', 'R', 1, 0);
    PDF::MultiCell(70, 0, "", 'TRL', 'R', 1, 0);
    PDF::MultiCell(95, 0, "", 'TRL', 'R', 1, 0);
    PDF::MultiCell(115, 0, "", 'TRL', 'R', 1, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'LR', 'C', 1, 0);
    PDF::MultiCell(70, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(250, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(70, 0, "OLD", 'R', 'C', 1, 0);
    PDF::MultiCell(70, 0, "NEW", 'LR', 'C', 1, 0);
    PDF::MultiCell(95, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(115, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'LRB', 'C', 1, 0);
    PDF::MultiCell(70, 0, "", 'RBL', 'C', 1, 0);
    PDF::MultiCell(250, 0, "", 'RBL', 'L', 1, 0);
    PDF::MultiCell(70, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(70, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(95, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(115, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);
  }

  public function hgc_PO_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;


    $newpageadd = 1;
    $arrTotal = [];

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = 11;
    $fontsize10 = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $position = '';
    $this->H1_layout($params, $data, $position);
    $this->hgc_PO_header_PDF($params, $data);
    PDF::SetTextColor(0, 0, 0);

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
              union select sum(ext) as ext from hpostock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [128, 128, 128]];
    PDF::SetLineStyle($style);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $itemname =  $data[$i]['itemname'];
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        $prevamt =  number_format($data[$i]['prevamt'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);
        $uprice = number_format($data[$i]['ext'] / $data[$i]['qty'], $decimalcurr);


        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '43', 0);
        $arr_netamt = $this->reporter->fixcolumn([$netamt], '15', 0);
        $arr_prevamt = $this->reporter->fixcolumn([$prevamt], '10', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '18', 0);
        $arr_uprice = $this->reporter->fixcolumn([$uprice], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_netamt, $arr_prevamt, $arr_ext, $arr_uprice]);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(70, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(70, 0, "", 'TR', 'C', false, 0);
        PDF::MultiCell(250, 0, "", 'TR', 'L', false, 0);
        PDF::MultiCell(70, 0, "", 'TR', 'R', false, 0);
        PDF::MultiCell(70, 0, "", 'TR', 'R', false, 0);
        PDF::MultiCell(95, 0, "", 'TR', 'R', false, 0);
        PDF::SetFillColor(204, 85, 0); //burnt orange
        PDF::MultiCell(115, 0, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(10, 0, "", '', 'C', false);


        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(10, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_prevamt[$r]) ? $arr_prevamt[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(95, 15, ' ' . (isset($arr_uprice[$r]) ? $arr_uprice[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFillColor(204, 85, 0); //burnt orange
          PDF::MultiCell(115, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'LR', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(10, 15, '', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

          if (PDF::getY() > 935) { //580
            if (PDF::PageNo() == 1) {
            } else {
              PDF::SetFont($font, '', 5);
              PDF::MultiCell(10, 0, "", '', 'C', false, 0);
              PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
              PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
              PDF::MultiCell(250, 0, "", 'T', 'L', false, 0);
              PDF::MultiCell(70, 0, "", 'T', 'R', false, 0);
              PDF::MultiCell(70, 0, "", 'T', 'R', false, 0);
              PDF::MultiCell(95, 0, "", 'T', 'R', false, 0);
              PDF::MultiCell(115, 0, "", 'T', 'R', false, 0);
              PDF::MultiCell(10, 0, "", '', 'C', false);

              $this->footer_layout($params, $data, $grandtotal);
            }

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(10, 0, "", '', 'C', false, 0);
            PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
            PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
            PDF::MultiCell(250, 0, "", 'T', 'L', false, 0);
            PDF::MultiCell(70, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(70, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(95, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(115, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(10, 0, "", '', 'C', false);


            $position = 'secP';
            $this->H1_layout($params, $data, $position);
            $this->hgc_PO_header_PDF($params, $data);
            PDF::SetTextColor(0, 0, 0);
            $newpageadd++;
          } else {
            if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
              if (PDF::PageNo() == 1) {
                if (PDF::getY() <= 705) {
                  do {
                    PDF::SetFont($fontbold, '', $fontsize);
                    $this->addrowrem();
                  } while (PDF::getY() < 705); //705
                  $this->footer_layout($params, $data, $grandtotal);
                } else {
                  do {
                    PDF::SetFont($fontbold, '', $fontsize);
                    $this->addrowrem();
                  } while (PDF::getY() < 960); //705
                  PDF::MultiCell(0, 0, "\n\n\n\n");

                  $position = 'secP';
                  $this->H1_layout($params, $data, $position);
                  $this->hgc_PO_header_PDF($params, $data);
                  PDF::SetTextColor(0, 0, 0);

                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(740, 15, "", 'LR', 'R', false, 1, 30); //eto yung pang last page -> subtotal
                  $this->footer_layout($params, $data, $grandtotal);
                }
              } else {
                if (PDF::getY() <= 705) {
                  $this->footer_layout($params, $data, $grandtotal);
                } else {
                  do {
                    PDF::SetFont($fontbold, '', $fontsize);
                    $this->addrowrem();
                  } while (PDF::getY() < 960); //705
                  PDF::MultiCell(0, 0, "\n\n\n\n");

                  $position = 'secP';
                  $this->H1_layout($params, $data, $position);
                  $this->hgc_PO_header_PDF($params, $data);
                  PDF::SetTextColor(0, 0, 0);

                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(740, 15, "", 'LR', 'R', false, 1, 30); //eto yung pang last page -> subtotal
                  $this->footer_layout($params, $data, $grandtotal);
                }
              }
            }
          }
        }

        $totalext += $data[$i]['ext'];
      }
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrowrem()
  {
    PDF::MultiCell(10, 20, "", '', 'C', false, 0);
    PDF::MultiCell(70, 20, "", 'TLRB', 'C', false, 0);
    PDF::MultiCell(70, 20, "", 'TLRB', 'C', false, 0);
    PDF::MultiCell(250, 20, "", 'TLRB', 'L', false, 0);
    PDF::MultiCell(70, 20, "", 'TLRB', 'R', false, 0);
    PDF::MultiCell(70, 20, "", 'TLRB', 'R', false, 0);
    PDF::MultiCell(95, 20, "", 'TLRB', 'R', false, 0);
    // PDF::SetFillColor(240, 128, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(115, 20, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 20, "", '', 'C', false);
  }

  public function footer_layout($params, $data, $totalext)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize10 = 10;
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
    PDF::MultiCell(300, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'T', 'R', false, 0);
    PDF::MultiCell(95, 15, "", 'TLR', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(115, 15, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(530, 15, '1. Please send two copies of your invoice.', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(95, 15, "SUBTOTAL", 'LRB', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(115, 15, number_format($totalext, 2), 'RBL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", '', 'C', false, 0);
    PDF::MultiCell(245, 0, "", '', 'L', false, 0);
    PDF::MultiCell(115, 0, "", '', 'R', false, 0);
    PDF::MultiCell(95, 15, "", 'TLR', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(115, 15, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(530, 15, '2. Enter this order in accordance with the prices, terms, delivery method, and specifications listed above.', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(95, 15, "SALES TAX", 'LRB', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(115, 15, '', 'RBL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", '', 'C', false, 0);
    PDF::MultiCell(245, 0, "", '', 'L', false, 0);
    PDF::MultiCell(115, 0, "", '', 'R', false, 0);
    PDF::MultiCell(95, 15, "", 'TLR', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(115, 15, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(530, 0, '3. Please notify us immediately if you are unable to ship as specified.', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(95, 15, "TOTAL", 'LRB', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(115, 15, number_format($totalext, 2), 'RBL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", '', 'C', false, 0);
    PDF::MultiCell(245, 0, "", '', 'L', false, 0);
    PDF::MultiCell(115, 0, "", '', 'R', false, 0);
    PDF::MultiCell(95, 15, "", '', 'R', false, 0);
    PDF::MultiCell(115, 15, "", '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(530, 0, '4. Send all correspondence to:', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(95, 15, "", '', 'R', false, 0);
    PDF::MultiCell(115, 15, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);


    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(720, 0, '', 'TLR', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0);
    PDF::MultiCell(720, 20, '  Comments & Special Instructions: ', 'RL', 'L', false, 0);
    PDF::MultiCell(10, 20, '', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0);
    PDF::SetTextColor(255, 0, 0);
    PDF::MultiCell(720, 20, '  ' . $data[0]['rem'], 'RL', 'L', false, 0);
    PDF::MultiCell(10, 20, '', '', 'L', false);

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(720, 0, '', 'LRB', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false);

    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'Prepared By: ', '', 'L', false, 0, 20, 890);
    PDF::MultiCell(200, 0, 'Approved By: ', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, ' ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'Noted By: ' . $params['params']['dataparams']['received'], '', 'L', false, 1, 220, 920);

    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(600, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0, 40, 930);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], '', 'L', false);


    PDF::SetFont($fontbold, '', 15);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(770, 20, '', '', 'L', 1, 0, 15, 959);
    PDF::MultiCell(1, 0, '', '', 'C', 1);
  }

  //end


  ##################################################################

  //start new layout - option 2
  public function H1_layout_2($params, $data, $position)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($fontbold, '', 30);

    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    if ($position == 'secP') {
      PDF::MultiCell(720, 20, '', '', 'L', 1, 0, 15, 10);
    } else {
      PDF::MultiCell(720, 20, '', '', 'L', 1, 0, 15);
    }
    PDF::SetFillColor(255, 255, 255);
    PDF::MultiCell(80, 20, '', '', 'L', 1);

    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(720, 20, '', '', 'L', 1, 0, 15);
    PDF::SetFillColor(255, 255, 255);
    PDF::MultiCell(80, 20, '', '', 'L', 1);


    PDF::SetFont($fontbold, '', 20);
    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(450, 20, 'HOUSEGEM CONSTRUCTION ELEMENTS', '', 'L', 1, 0, 25, 15);

    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(350, 20, 'CORPORATION', '', 'L', 1, 0, 25, 45);

    PDF::SetFont($fontbold, '', 25);
    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(250, 20, 'PURCHASE ORDER', '', 'R', 1, 0, 450, 15);

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, 25,  100);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(190, 18, "P.O. NO. ", '', 'R', false, 0, '',  '');
    PDF::SetTextColor(170, 4, 45); //cherry
    PDF::MultiCell(490, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false);


    $date = $data[0]['dateid'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(110, 0, "", '', 'R', false, 0, '',  '');
    PDF::MultiCell(285, 18, "DATE ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(390, 18, isset($date) ? $date : '', '', 'L', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(255, 0, 0);
    PDF::MultiCell(110, 0, "", '', 'R', false, 0, '',  '');
    PDF::MultiCell(285, 18, "", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(390, 18, '', '', 'L', false);
  }

  public function hgc_PO_header_PDF_2($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(5, 18, "", '', 'L', false, 0);
    PDF::MultiCell(190, 18, "VENDOR", '', 'L', false);


    PDF::SetFont($fontbold, '', 30);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(690, 20, '', '', 'L', 1, 0, 90, 190);


    PDF::SetFont($fontbold, '', 13);
    PDF::SetTextColor(255, 255, 255);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(670, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'C', 1, 0, 90, 200);
    PDF::MultiCell(5, 0, '', '', 'C', 1);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 9);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(740, 0, "Page " . PDF::PageNo() . "  ", '', 'R', false, 0, 30, 270);

    PDF::SetFont($fontbold, '', 22);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(740, 20, '', '', 'L', 1, 0, 30, 290);
    PDF::MultiCell(1, 0, '', '', 'C', 1);

    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [128, 128, 128]];
    PDF::SetLineStyle($style);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'TLR', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "SHIPPING METHOD", 'LR', 'C', false, 0);
    PDF::MultiCell(340, 0, "PAYMENT TERMS", 'R', 'C', false, 0);
    PDF::MultiCell(200, 0, "DELIVERY DATE", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LRB', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetTextColor(0, 0, 0);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LR', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'R', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LR', 'C', false, 0);
    PDF::MultiCell(340, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'R', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'LRB', 'C', false, 0);
    PDF::MultiCell(340, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);


    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($fontbold, '', 22);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(740, 20, '', '', 'L', 1, 0, 30, 370);
    PDF::MultiCell(1, 0, '', '', 'C', 1);

    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [128, 128, 128]];
    PDF::SetLineStyle($style);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
    PDF::MultiCell(50, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'TR', 'L', false, 0);
    PDF::MultiCell(65, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(140, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(60, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(75, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(100, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", 'R', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", 'R', 'C', false, 0);
    PDF::MultiCell(65, 0, "DATE", 'R', 'C', false, 0);
    PDF::MultiCell(140, 0, "PRICE/KG", 'R', 'C', false, 0);
    PDF::MultiCell(60, 0, "DISCOUNT", 'R', 'C', false, 0);
    PDF::MultiCell(75, 0, "UNIT PRICE", 'R', 'C', false, 0);
    PDF::MultiCell(100, 0, "LINE TOTAL", 'R', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "", 'LRB', 'C', false, 0);
    PDF::MultiCell(50, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(200, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(65, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(140, 0, "", 'RB', 'R', false, 0);
    PDF::MultiCell(60, 0, "", 'RB', 'C', false, 0);
    PDF::MultiCell(75, 0, "", 'RB', 'R', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);


    PDF::SetFillColor(204, 85, 0); //burnt orange
    // PDF::MultiCell(115, 0, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "", 'TLR', 'C', 1, 0);
    PDF::MultiCell(50, 0, "", 'TRL', 'C', 1, 0);
    PDF::MultiCell(200, 0, "", 'TRL', 'L', 1, 0);
    PDF::MultiCell(65, 0, "", 'TRL', 'C', 1, 0);
    PDF::MultiCell(70, 0, "", 'TRL', 'R', 1, 0);
    PDF::MultiCell(70, 0, "", 'TRL', 'R', 1, 0);
    PDF::MultiCell(60, 0, "", 'TRL', 'C', 1, 0);
    PDF::MultiCell(75, 0, "", 'TRL', 'R', 1, 0);
    PDF::MultiCell(100, 0, "", 'TRL', 'R', 1, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "", 'LR', 'C', 1, 0);
    PDF::MultiCell(50, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(200, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(65, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(70, 0, "OLD", 'RL', 'C', 1, 0);
    PDF::MultiCell(70, 0, "NEW", 'LR', 'C', 1, 0);
    PDF::MultiCell(60, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(75, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(100, 0, "", 'RL', 'C', 1, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "", 'LRB', 'C', 1, 0);
    PDF::MultiCell(50, 0, "", 'RBL', 'C', 1, 0);
    PDF::MultiCell(200, 0, "", 'RBL', 'L', 1, 0);
    PDF::MultiCell(65, 0, "", 'RBL', 'C', 1, 0);
    PDF::MultiCell(70, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(70, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(60, 0, "", 'RBL', 'C', 1, 0);
    PDF::MultiCell(75, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(100, 0, "", 'RBL', 'R', 1, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);
  }

  public function hgc_PO_PDF_2($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;


    $newpageadd = 1;
    $arrTotal = [];

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = 11;
    $fontsize10 = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $position = '';
    $this->H1_layout_2($params, $data, $position);
    $this->hgc_PO_header_PDF_2($params, $data);
    PDF::SetTextColor(0, 0, 0);

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
              union select sum(ext) as ext from hpostock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [128, 128, 128]];
    PDF::SetLineStyle($style);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $itemname =  $data[$i]['itemname'];
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        $prevamt =  number_format($data[$i]['prevamt'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);
        $uprice = number_format($data[$i]['ext'] / $data[$i]['qty'], $decimalcurr);
        $prevdate =  $data[$i]['prevdate'];
        $disc =  $data[$i]['disc'];


        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_netamt = $this->reporter->fixcolumn([$netamt], '15', 0);
        $arr_prevamt = $this->reporter->fixcolumn([$prevamt], '10', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '18', 0);
        $arr_uprice = $this->reporter->fixcolumn([$uprice], '13', 0);
        $arr_prevdate = $this->reporter->fixcolumn([$prevdate], '10', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '7', 0);

        $maxrow = $this->othersClass->getmaxcolumn([
          $arr_itemname, $arr_qty, $arr_uom, $arr_netamt, $arr_prevamt,
          $arr_ext, $arr_uprice, $arr_prevdate, $arr_disc
        ]);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(50, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(200, 0, "", 'TLR', 'L', false, 0);
        PDF::MultiCell(65, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(70, 0, "", 'TLR', 'R', false, 0);
        PDF::MultiCell(70, 0, "", 'TLR', 'R', false, 0);
        PDF::MultiCell(60, 0, "", 'TLR', 'C', false, 0);
        PDF::MultiCell(75, 0, "", 'TLR', 'R', false, 0);
        PDF::SetFillColor(204, 85, 0); //burnt orange
        PDF::MultiCell(100, 0, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(10, 0, "", '', 'C', false);


        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(10, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(65, 15, ' ' . (isset($arr_prevdate[$r]) ? $arr_prevdate[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_prevamt[$r]) ? $arr_prevamt[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), 'LR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(75, 15, ' ' . (isset($arr_uprice[$r]) ? $arr_uprice[$r] : ''), 'LR', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFillColor(204, 85, 0); //burnt orange
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'LR', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(10, 15, '', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

          if (PDF::getY() > 935) { //580
            if (PDF::PageNo() == 1) {
            } else {
              $this->footer_layout_2($params, $data, $grandtotal);
            }

            PDF::SetFont($font, '', 5);
            PDF::MultiCell(10, 0, "", '', 'C', false, 0);
            PDF::MultiCell(50, 0, "", 'T', 'C', false, 0);
            PDF::MultiCell(50, 0, "", 'T', 'C', false, 0);
            PDF::MultiCell(200, 0, "", 'T', 'L', false, 0);
            PDF::MultiCell(65, 0, "", 'T', 'C', false, 0);
            PDF::MultiCell(70, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(70, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(60, 0, "", 'T', 'C', false, 0);
            PDF::MultiCell(75, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(100, 0, "", 'T', 'R', false, 0);
            PDF::MultiCell(10, 0, "", '', 'C', false);

            $position = 'secP';
            $this->H1_layout_2($params, $data, $position);
            $this->hgc_PO_header_PDF_2($params, $data);
            PDF::SetTextColor(0, 0, 0);
            $newpageadd++;
          } else {
            if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
              if (PDF::PageNo() == 1) {
                if (PDF::getY() <= 705) {
                  do {
                    PDF::SetFont($fontbold, '', $fontsize);
                    $this->addrowrem_2();
                  } while (PDF::getY() < 705); //705

                  $this->footer_layout_2($params, $data, $grandtotal);
                } else {
                  do {
                    PDF::SetFont($fontbold, '', $fontsize);
                    $this->addrowrem_2();
                  } while (PDF::getY() < 960); //705
                  PDF::MultiCell(0, 0, "\n\n\n\n");

                  $position = 'secP';
                  $this->H1_layout_2($params, $data, $position);
                  $this->hgc_PO_header_PDF_2($params, $data);
                  PDF::SetTextColor(0, 0, 0);

                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(740, 15, "", 'LR', 'R', false, 1, 30);
                  $this->footer_layout_2($params, $data, $grandtotal);
                }
              } else {
                if (PDF::getY() <= 705) {
                  $this->footer_layout_2($params, $data, $grandtotal);
                } else {
                  do {
                    PDF::SetFont($fontbold, '', $fontsize);
                    $this->addrowrem_2();
                  } while (PDF::getY() < 960); //705
                  PDF::MultiCell(0, 0, "\n\n\n\n");

                  $position = 'secP';
                  $this->H1_layout_2($params, $data, $position);
                  $this->hgc_PO_header_PDF_2($params, $data);
                  PDF::SetTextColor(0, 0, 0);

                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(740, 15, "", 'LR', 'R', false, 1, 30); //eto yung pang last page -> subtotal
                  $this->footer_layout_2($params, $data, $grandtotal);
                }
              }
            }
          }
        }

        $totalext += $data[$i]['ext'];
      }
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrowrem_2()
  {
    PDF::MultiCell(10, 20, "", '', 'C', false, 0);
    PDF::MultiCell(50, 20, "", 'TLRB', 'C', false, 0);
    PDF::MultiCell(50, 20, "", 'TLRB', 'C', false, 0);
    PDF::MultiCell(200, 20, "", 'TLRB', 'L', false, 0);
    PDF::MultiCell(65, 20, "", 'TLRB', 'C', false, 0);
    PDF::MultiCell(70, 20, "", 'TLRB', 'R', false, 0);
    PDF::MultiCell(70, 20, "", 'TLRB', 'R', false, 0);
    PDF::MultiCell(60, 20, "", 'TLRB', 'C', false, 0);
    PDF::MultiCell(75, 20, "", 'TLRB', 'R', false, 0);
    // PDF::SetFillColor(240, 128, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(100, 20, "", 'TRLB', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 20, "", '', 'C', false);
  }

  public function footer_layout_2($params, $data, $totalext)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize10 = 10;
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'T', 'C', false, 0);
    PDF::MultiCell(335, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'T', 'R', false, 0);
    PDF::MultiCell(75, 15, "", 'TLR', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(100, 15, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(565, 15, '1. Please send two copies of your invoice.', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 15, "SUBTOTAL", 'LRB', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(100, 15, number_format($totalext, 2), 'RBL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", '', 'C', false, 0);
    PDF::MultiCell(280, 0, "", '', 'L', false, 0);
    PDF::MultiCell(115, 0, "", '', 'R', false, 0);
    PDF::MultiCell(75, 15, "", 'TLR', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(100, 15, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(565, 15, '2. Enter this order in accordance with the prices, terms, delivery method, and specifications listed above.', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 15, "SALES TAX", 'LRB', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(100, 15, '', 'RBL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", '', 'C', false, 0);
    PDF::MultiCell(280, 0, "", '', 'L', false, 0);
    PDF::MultiCell(115, 0, "", '', 'R', false, 0);
    PDF::MultiCell(75, 15, "", 'TLR', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(100, 15, "", 'TRL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(565, 0, '3. Please notify us immediately if you are unable to ship as specified.', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 15, "TOTAL", 'LRB', 'R', false, 0);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::MultiCell(100, 15, number_format($totalext, 2), 'RBL', 'R', 1, 0, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "", '', 'C', false, 0);
    PDF::MultiCell(280, 0, "", '', 'L', false, 0);
    PDF::MultiCell(115, 0, "", '', 'R', false, 0);
    PDF::MultiCell(75, 15, "", '', 'R', false, 0);
    PDF::MultiCell(100, 15, "", '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(565, 0, '4. Send all correspondence to:', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 15, "", '', 'R', false, 0);
    PDF::MultiCell(100, 15, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false);


    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(720, 0, '', 'TLR', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0);
    PDF::MultiCell(720, 20, '  Comments & Special Instructions: ', 'RL', 'L', false, 0);
    PDF::MultiCell(10, 20, '', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0);
    PDF::SetTextColor(255, 0, 0);
    PDF::MultiCell(720, 20, '  ' . $data[0]['rem'], 'RL', 'L', false, 0);
    PDF::MultiCell(10, 20, '', '', 'L', false);

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(720, 0, '', 'LRB', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false);

    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'Prepared By: ', '', 'L', false, 0, 20, 890);
    PDF::MultiCell(200, 0, 'Approved By: ', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, ' ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'Noted By: ' . $params['params']['dataparams']['received'], '', 'L', false, 1, 220, 920);

    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(600, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0, 40, 930);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], '', 'L', false);


    PDF::SetFont($fontbold, '', 15);
    PDF::SetFillColor(204, 85, 0); //burnt orange
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(770, 20, '', '', 'L', 1, 0, 15, 959);
    PDF::MultiCell(1, 0, '', '', 'C', 1);
  }

  //end



  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '140', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();


    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->title_header($params);
    return $str;
  }

  public function title_header($params)
  {
    $border = "1px solid ";
    $font =  "Century Gothic";
    $str = "";

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '475', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function default_po_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty',  $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '475', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price',  $params['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');

      $totalext = $totalext + $data[$i]['ext'];
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM(S)', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '440', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_PO_header_PDF($params, $data)
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
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


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
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(245, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(85, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "TOTAL", '', 'R', false);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }

  public function default_PO_PDF($params, $data)
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
    $this->default_PO_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $barcode =  $data[$i]['barcode'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $itemname =  $data[$i]['itemname'];
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        $disc =  $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '14', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_netamt = $this->reporter->fixcolumn([$netamt], '15', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '15', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_netamt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(245, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 15, ' ' . (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_PO_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(635, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(85, 0, number_format($totalext, $decimalcurr), '', 'R');

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
