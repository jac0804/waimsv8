<?php

namespace App\Http\Classes\modules\modulereport\summit;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class sj
{

  private $modulename = "Sales Journal";
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Original Form', 'value' => '0', 'color' => 'green'],
      ['label' => 'Order Form', 'value' => '1', 'color' => 'green']
    ]);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
     return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        '0' as reporttype,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "select head.trno,stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, if(stock.uom = 'CASE' OR stock.uom = 'CASES', 'CASES',stock.uom) as uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname, head.ms_freight,head.lockdate,head.lockuser from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    where head.doc='sj' and head.trno='$trno'
    UNION ALL
    select head.trno,stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, if(stock.uom = 'CASE' OR stock.uom = 'CASES', 'CASES',stock.uom) as uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname, head.ms_freight,head.lockdate,head.lockuser from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    where head.doc='sj' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn



  public function reportplotting($params, $data)
  {
    switch ($params['params']['dataparams']['reporttype']) {
      case 0: // orignal form
        return $this->default_sj_PDF($params, $data);
        break;

      case 1: // order form
        return $this->orderform_layout($params, $data);
        break;
    }
  }

  public function orderform_layout($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalqty = 0;
    $subqty = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->orderform_header($params, $data);
    $uom = '';
    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(80, 0, number_format($data[$i]['qty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(20, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(300, 0, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(10, 0, $data[$i]['disc'], '', 'C', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 0, number_format($data[$i]['amt'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(140, 25, number_format($data[$i]['ext'], $decimalprice), '', 'R', 0, 1, '', '', true, 0, true, false);
      $totalext += $data[$i]['ext'];
      $totalqty += $data[$i]['qty'];

      if (intVal($i) + 1 == $page) {
        $this->orderform_header($params, $data);
        $page += $count;
      }
    }

    PDF::setCellMargins(0, 2, 0, 2);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(80, 0, number_format($totalqty, $decimalprice), 'T', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(50, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(20, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(300, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(10, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(110, 0, 'TOTAL AMOUNT : ', 'T', 'R', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(130, 0, number_format($totalext, $decimalprice), 'T', 'R', 0, 1);
   
    for ($i = 0; $i < count($data); $i++) {
      if ($uom != $data[$i]['uom']) {
        if ($uom != '') {
          PDF::setCellMargins(0, 8, 0, 0);
          PDF::SetFont($font, 'B', $fontsize);
          PDF::MultiCell(80, 0, number_format($subqty, $decimalprice), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, $uom, '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(20, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(250, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(80, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(140, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, '', '', 'R', 0, 1);
          $subqty = 0;
        }
      }
      $subqty +=  $data[$i]['qty'];
      $uom = $data[$i]['uom'];
    }
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(80, 0, number_format($subqty, $decimalprice), '', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(50, 0, $uom, '', 'R', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(20, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(250, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(80, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(140, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(100, 0, '', '', 'R', 0, 1);

    if (isset($data[0]['ms_freight'])) {
      $freight = $data[0]['ms_freight'];
      if ($freight != 0) {
        PDF::SetFont($font, '', $fontsize);
        PDF::setCellMargins(0, 10, 0, 0);
        PDF::MultiCell(70, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(60, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(250, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(80, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(140, 25, 'DELIVERY CHARGE : ', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 0, number_format($freight, $decimalprice), '', 'R', 0, 1, '', '', true, 0, true, false);

        $gtotal = $freight + $totalext;
        PDF::SetFont($font, '', $fontsize);
        PDF::setCellMargins(0, 7, 0, 2);
        PDF::MultiCell(70, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(80, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(230, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(80, 0, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::SetFont($font, 'B', $fontsize);
        PDF::MultiCell(140, 0, 'GRAND TOTAL : ', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 0, number_format($gtotal, $decimalprice), '', 'R');
        PDF::MultiCell(110, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false);
      }
    }

    PDF::setCellMargins(0, 15, 0, 0);
    PDF::MultiCell(0, 0, "\n\n", '', 'L', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n", '', 'L', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n", '', 'L', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n", '', 'L', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n", '', 'L', false, 1, '', '');
    PDF::MultiCell(0, 0, "\n\n", '', 'L', false, 1, '', '');

    PDF::MultiCell(300, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function orderform_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
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

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 100, "\n\n\n");
    PDF::MultiCell(0, 30, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(350, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(100, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(210, 30, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '',  '');

    PDF::MultiCell(80, 100, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(370, 100, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(80, 100, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 100, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 100, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(50, 100, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "", '', 'L', false, 1, '', '');

    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(400, 0, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(190, 0, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '');


    PDF::MultiCell(0, 0, "", '', 'L', false, 1, '', '245');
  }

  // pdf
  public function default_sj_header_PDF($params, $data)
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

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
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
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

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_sj_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $lockdate = $this->othersClass->getCurrentTimeStamp();
    if ($data[0]['lockuser'] == "") {
      $this->coreFunctions->execqry("update lahead set lockuser='" . $username . "', lockdate = '" . $lockdate . "' 
              where trno  = ? and lockdate is null", "update", [$params['params']['dataid']]);
    }


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_sj_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

   
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '33', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '8', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '8', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '7', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '14', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->default_sj_header_PDF($params, $data);
          //  $page += $count;
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'TOTAL AMOUNT: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    if (isset($data[0]['ms_freight'])) {
      $freight = $data[0]['ms_freight'];
      if ($freight != 0) {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(600, 0, 'DELIVERY CHARGE: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($freight, $decimalcurr), '', 'R');
      }
    } else {
      $freight = 0;
    }

    $gtotal = $freight + $totalext;
    PDF::MultiCell(700, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($gtotal, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', $fontsize);
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
