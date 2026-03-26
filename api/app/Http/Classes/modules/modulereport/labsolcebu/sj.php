<?php

namespace App\Http\Classes\modules\modulereport\labsolcebu;

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
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'DELIVERY RECEIPT', 'value' => '0', 'color' => 'blue'],
        ['label' => 'SALES INVOICE', 'value' => '1', 'color' => 'blue'],
        ['label' => 'NEW SALES INVOICE', 'value' => '2', 'color' => 'blue']
      ]
    );

    data_set($col1, 'approved.label', 'Ordered By');
    data_set($col1, 'received.label', 'Delivered By');

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
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
                      right(year(head.dateid),2) as year,date_format(head.dateid,'%m-%d-%Y') as dateid, 
                      head.docno, client.client, client.clientname,head.address, head.terms, 
                      item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
                      (case when info.itemdesc <> '' then info.itemdesc else item.itemname end) as itemname,
                      stock.isqty as qty, stock.uom, stock.isamt as amt, 
                      stock.disc, stock.ext, head.agent,item.sizeid, ag.clientname as agname, 
                      item.brand, head.vattype,wh.client as whcode, wh.clientname as whname, 
                      stock.loc, stock.expiry,cat.cat_name as category,ag.alias,head.trno
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid=stock.itemid
              left join client as ag on ag.client=head.agent
              left join client as wh on wh.client=head.wh
              left join category_masterfile as cat on cat.cat_id = client.category
              left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='sj' and head.trno='$trno'
              UNION ALL
              select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
                     right(year(head.dateid),2) as year,date_format(head.dateid,'%m/%d/%Y') as dateid, 
                     head.docno, client.client, client.clientname,head.address, head.terms, 
                     item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
                     (case when info.itemdesc <> '' then info.itemdesc else item.itemname end) as itemname,
                     stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, 
                     stock.ext, ag.client as agent,item.sizeid, ag.clientname as agname, 
                     item.brand, head.vattype,wh.client as whcode, wh.clientname as whname, 
                     stock.loc, stock.expiry,cat.cat_name as category,ag.alias,head.trno
              from glhead as head
              left join glstock as stock on stock.trno=head.trno
              left join client on client.clientid=head.clientid
              left join item on item.itemid=stock.itemid
              left join client as ag on ag.clientid=head.agentid
              left join client as wh on wh.clientid=head.whid
              left join category_masterfile as cat on cat.cat_id = client.category
              left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
              where head.doc='sj' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    switch ($params['params']['dataparams']['reporttype']) {
      case '0':
        $str = $this->default_sj_PDF($params, $data); // dr report
        break;
      case '1':
        $str = $this->default_si_PDF($params, $data);
        break;
      case '2':
        $str = $this->default_newsi_PDF($params, $data);
        break;
    }
    return $str;
  }


  public function default_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

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
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, "\n\n");
    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 20, '');


    $client = $data[0]['clientname'];
    $cclient = strlen($client);
    if ($cclient <= 44) {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(300, 0, $client, '', 'L', false, 1, 280, 55);
    } else {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(300, 0, $client, '', 'L', false, 1, 280, 50);
    }

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(300, 0, $data[0]['clientname'], '', 'L', false, 1, 280, 60);

    $addr = $data[0]['address'];
    $caddr = strlen($addr);
    PDF::SetFont($font, '', 10);
    if ($caddr <= 44) {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(290, 0, $addr, '', 'L', false, 1, 280, 80);
    } else {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(290, 0, $addr, '', 'L', false, 1, 280, 70);
    }
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(300, 0, $data[0]['docno'], '', 'L', false, 1, 600, 40);
    PDF::MultiCell(300, 0, $data[0]['dateid'], '', 'L', false, 1, 600, 60);
    PDF::MultiCell(300, 0, $data[0]['agent'], '', 'L', false, 1, 620, 80);
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

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_sj_header_PDF($params, $data);
    PDF::MultiCell(0, 40, "");

    $countarr = 0;
    $c = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $c++;
        $itemname = $data[$i]['itemname'] . ' ' . $data[$i]['loc'] . ' ' . $data[$i]['expiry'];
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $ext = number_format($data[$i]['ext'], 2);


        $arr_c = $this->reporter->fixcolumn([number_format($c, 0)], '5', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_c, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
          PDF::SetFont($font, '', $fontsize);
          $itemno = isset($arr_c[$r]) ? $arr_c[$r] : '';
          $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
          $unit = isset($arr_uom[$r]) ? $arr_uom[$r] : '';
          $item = isset($arr_itemname[$r]) ? $arr_itemname[$r] : '';
          $amt = isset($arr_amt[$r]) ? $arr_amt[$r] : '';
          $sub = isset($arr_ext[$r]) ? $arr_ext[$r] : '';

          PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(100, 0, $itemno, '', 'C', false, 0, '15', '', false, 1);
          PDF::MultiCell(100, 0, $qty, '', 'R', false, 0, '55', '', false, 1);
          PDF::MultiCell(100, 0, $unit, '', 'L', false, 0, '190', '', false, 1);
          PDF::MultiCell(300, 0, $item, '', 'L', false, 0, '260', '', false, 1);
          PDF::MultiCell(100, 0, $amt, '', 'R', false, 0, '520', '', false, 1);
          PDF::MultiCell(100, 0, $sub, '', 'R', false, 1, '624', '', false, 1);
        }

        $totalext += $data[$i]['ext'];
      }
    }


    PDF::SetFont($font, '', 25);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(15, 0, '', '', 'R', false, 0);
    PDF::MultiCell(50, 0, 'Notes: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, $data[0]['rem'], '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R', false, 1, '620', '410', false, 1);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0, 575, 450);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0, 575, 475);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L', false, 0, 575, 500);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_si_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

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

    PDF::MultiCell(0, 0, "\n\n");
    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 20, '');
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(300, 0, '', '', 'L', false, 1, 160, 120); //Business Sty;e
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(300, 0, $data[0]['docno'], '', 'L', false, 1, 420, 50); //510
    PDF::setFontSpacing(1);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(300, 0, $data[0]['monthid'] . '/', '', 'L', false, 0, 485, 80);
    PDF::MultiCell(90, 0, $data[0]['year'], '', 'L', false, 0, 560, 80);
    PDF::setFontSpacing(0);

    $client = $data[0]['clientname'];

    $cclient = strlen($client);
    PDF::SetFont($font, '', 10);
    if ($cclient <= 50) {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(280, 0, $client, '', 'L', false, 1, 45, 138);
    } else {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(280, 0, $client, '', 'L', false, 1, 45, 125);
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(00, 0, $data[0]['tin'], '', 'L', false, 1, 355, 140);


    ///////
    $addr = $data[0]['address'];
    $caddr = strlen($addr);
    PDF::SetFont($font, '', 10);
    if ($caddr <= 40) {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(250, 0, $addr, '', 'L', false, 1, 510, 140);
    } else {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(250, 0, $addr, '', 'L', false, 1, 510, 125);
    }

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(300, 0, $data[0]['category'], '', 'L', false, 1, 45, 195); //Business Style
    PDF::MultiCell(300, 0, '', '', 'L', false, 1, 190, 240); //OSCA/PWD No.
    PDF::MultiCell(300, 0, $data[0]['terms'], '', 'L', false, 1, 408, 195);
    PDF::MultiCell(300, 0, $data[0]['yourref'], '', 'L', false, 1, 510, 195);
    PDF::MultiCell(300, 0, $data[0]['agent'], '', 'L', false, 1, 600, 195);


    // PDF::MultiCell(0, 35, "");
  }


  public function default_si_PDF($params, $data)
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
    $this->default_si_header_PDF($params, $data);

    PDF::MultiCell(0, 30, "");

    $countarr = 0;
    $c = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $c++;
        $itemname = $data[$i]['itemname'] . ' ' . $data[$i]['loc'] . ' ' . $data[$i]['expiry'];
        $qty = number_format($data[$i]['qty'], 0);
        // $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $ext = number_format($data[$i]['ext'], 2);
        $uom = $data[$i]['uom'];

        $arr_ctr = $this->reporter->fixcolumn([$c . ""], 10);
        $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_ctr, $arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 12);
          $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
          $uom = isset($arr_uom[$r]) ? $arr_uom[$r] : '';
          $item = isset($arr_itemname[$r]) ? $arr_itemname[$r] : '';
          $amt = isset($arr_amt[$r]) ? $arr_amt[$r] : '';
          $sub = isset($arr_ext[$r]) ? $arr_ext[$r] : '';


          PDF::MultiCell(100, 22, isset($arr_ctr[$r]) ? $arr_ctr[$r] : '', '', 'R', false, 0, '-30', '', true, 1);
          PDF::MultiCell(100, 0, $qty, '', 'R', false, 0, '40', '', false, 1);
          PDF::MultiCell(100, 0, $uom, '', 'L', false, 0, '160', '', false, 1);
          PDF::MultiCell(300, 0, $item, '', 'L', false, 0, '210', '', false, 1);
          PDF::MultiCell(100, 0, $amt, '', 'R', false, 0, '545', '', false, 1);
          PDF::MultiCell(100, 0, $sub, '', 'R', false, 1, '658', '', false, 1);
        }

        $totalext += $data[$i]['ext'];
      }
    }
    PDF::SetFont($font, '', 25);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(15, 0, '', '', 'R', false, 0);
    PDF::MultiCell(50, 0, 'Notes: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, $data[0]['rem'], '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0, '658', '535', false, 1);

    if ($data[0]['vattype'] == 'VATABLE') {
      $vat = ($totalext / 1.12) * .12;
      $netvat = $totalext - $vat;
      PDF::MultiCell(100, 0, number_format($vat, 2), '', 'R', false, 0, '658', '560', false, 1);
      PDF::MultiCell(100, 0, number_format($netvat, 2), '', 'R', false, 0, '658', '585', false, 1);
    }


    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0, '658', '630', false, 1);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R', false, 1, '658', '690', false, 1);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0, 38, 718);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L', false, 0, 210, 718); //Ordered
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0, 370, 718); //Delivered

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function default_newsi_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 15;
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
    PDF::SetMargins(35, 35);

    PDF::MultiCell(0, 0, "\n\n");
    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 20,'');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 0, $data[0]['docno'], '', 'L', false, 1, 600, 60); //Business Sty;e
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0, 40, 95); //510
    PDF::MultiCell(300, 0, '', '', 'L', false, 1, 630, 130); //510 $data[0]['docno']
    PDF::MultiCell(60, 0, '', '', 'L', false, 0, 40, 120); //510
    // PDF::setFontSpacing(1)
    // PDF::setFontSpacing(0);
    PDF::MultiCell(300, 0, $data[0]['dateid'], '', 'L', false, 1, 600, 100); //$data[0]['dateid']
    PDF::SetFont($font, '', 9.5);
    PDF::MultiCell(0, 0, '', '', 'L', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(7, 20, '', '', 'L', false, 0);
    PDF::MultiCell(90, 20, '', '', 'L', false, 0); # SOLD TO   : 
    PDF::SetFont($font, '', 16);
    PDF::MultiCell(630, 20, $data[0]['clientname'], '', 'L', false, 1);
    PDF::SetFont($font, '', 3);
    PDF::MultiCell(0, 0, '', '', 'L', false, 1);
    $labelWidth = 145;
    $colonWidth = 10;
    $lineHeight = 24.5;
    $indent = 7;
    $rows = [
      'Registered Name' => $data[0]['clientname'],
      'TIN'  => $data[0]['tin'],
      'Business Address' => $data[0]['address'],
    ];
    PDF::SetFont($font, '', 17);
    foreach ($rows as $label => $value) {
      PDF::Cell($indent, $lineHeight, '', 0, 0); // empty space
      PDF::Cell($labelWidth, $lineHeight, $label != "" ? '' : '', 0, 0);
      PDF::Cell($colonWidth, $lineHeight, '', 0, 0);
      if($label == 'Business Address'){
        PDF::SetFont($font, '', 13);
      }
      PDF::Cell(0, $lineHeight, $value, 0, 1);
    }
    PDF::SetFont('helvetica', 'B', $fontsize);
    // $h = 7; // row height
    // PDF::SetFont($fontbold, '', $fontsize);
    // ITEM NO. QUANTITY UOM ARTICLE / DESCRIPTION UNIT PRICE AMOOUNT P
    PDF::MultiCell(50, 0, '', '', 'C', false, 0); # ITEM NO.
    PDF::MultiCell(55, 0, '', '', 'C', false, 0); # QUANTITY
    PDF::MultiCell(60, 0, '', '', 'C', false, 0); # UOM
    PDF::MultiCell(325, 0, '', '', 'C', false, 0); # ARTICLE / DESCRIPTION
    PDF::MultiCell(120, 0, '', '', 'C', false, 0); # UNIT PRICE
    PDF::MultiCell(120, 0, '', '', 'C', false, 1); # AMOOUNT P
  }
  public function default_newsi_PDF($params, $data)
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
    $fontsize = "15";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_newsi_header_PDF($params, $data);
    PDF::SetFont($font, '', 5.5);
    PDF::MultiCell(0, 0, '', '', 'L', false, 1);

    // PDF::setCellPaddings(0, 2, 0, 2); //left top right bottom
    $itemno = 0;
    if (!empty($data)) {
      $j = 0;
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $itemname = $data[$i]['itemname'] . ' ' . $data[$i]['loc'] . ' ' . $data[$i]['expiry'];// $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $ext = number_format($data[$i]['ext'], 2);

        $arr_uom = $this->reporter->fixcolumn([$uom], '6', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $itemno++;
        $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_itemname, $arr_qty, $arr_amt, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          $j++;
          PDF::SetFont($font, '', $fontsize);

          PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(50, 23.5, $r == 0 ? $itemno : '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(55, 23.5, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(60, 23.5, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(333, 23.5, isset($arr_itemname[$r]) ? $arr_itemname[$r] : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(110, 23.5, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(110, 23.5, isset($arr_ext[$r]) ? $arr_ext[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(5, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);
        }
        $totalext += $data[$i]['ext'];
      }
      PDF::MultiCell(0, 0, '', '', 'L', false, 1);
      PDF::MultiCell(10, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(720, 23.5, 'Notes: '.$data[0]['rem'], '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      
      $this->getlines($j);
      PDF::setCellPaddings(0, 0, 0, 0);
      $vat = 0;
      $vatsale = 0;
      if ($data[0]['vattype'] == 'VATABLE') {
        $vatsale = $totalext / 1.12;
        $vat = ($totalext / 1.12) * .12;
        $netvat = $totalext - $vat;
      }

      PDF::SetFont($font, '', 4);
      PDF::MultiCell(0, 0, '', '', 'L', false, 1);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(190, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false); # VATable Sales
      PDF::MultiCell(140, 23.5, $vatsale != 0 ? number_format($vatsale, 2) : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(8, 23.5, '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(240, 23.5, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false); # Total Sales (VAT Inclusives)
      PDF::MultiCell(136, 23.5, number_format($totalext, 2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(9, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(190, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false); # VAT
      PDF::MultiCell(140, 23.5,  $vat != 0 ? number_format($vat, 2) : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(8, 23.5, '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(240, 23.5, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false); # Less: VAT
      PDF::MultiCell(136, 23.5, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(9, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(190, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false); # Zero-Rated Sales
      PDF::MultiCell(140, 23.5, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(8, 23.5, '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(240, 23.5, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false); # Amount: Net of VAT
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(136, 23.5, $vatsale != 0 ? number_format($vatsale, 2) : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(9, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(190, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false); # VAT-Exempt Sales
      PDF::MultiCell(140, 23.5, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(8, 23.5, '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(240, 23.5, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false); # Less Discount (SC/PWD/NAAC/MOV/SP)
      PDF::MultiCell(136, 23.5, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(9, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(190, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(140, 23.5, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(8, 23.5, '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(240, 23.5, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false); # Add : VAT
      PDF::MultiCell(136, 23.5, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(9, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);


      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(190, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(140, 23.5, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(8, 23.5, '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(240, 23.5, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false); # Less: Withholding tax
      PDF::MultiCell(136, 23.5, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(9, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(7, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(190, 23.5, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(140, 23.5, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(8, 23.5, '', '', '', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(240, 23.5, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false); # TOTAL AMOUNT DUE
      PDF::MultiCell(136, 23.5, number_format($totalext, 2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
      PDF::MultiCell(9, 23.5, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function getlines($lines)
  {

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = 14;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    if ($lines < 15) {
      $lines = 15 - $lines;
      for ($i = 0; $i < $lines; $i++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(45, 23,  '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
        PDF::MultiCell(50, 23,  '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false);
        PDF::MultiCell(55, 23,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
        PDF::MultiCell(60, 23,  '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
        PDF::MultiCell(320, 23, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
        PDF::MultiCell(100, 23, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
        PDF::MultiCell(100, 23, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);
      }
    }
  }
}
