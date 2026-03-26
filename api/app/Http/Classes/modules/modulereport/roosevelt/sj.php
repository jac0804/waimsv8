<?php

namespace App\Http\Classes\modules\modulereport\roosevelt;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\common\commonsbc;
use DateTime;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class sj
{

  private $modulename = "Sales Journal";
  private $reportheader;
  private $commonsbc;
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
    $this->reportheader = new reportheader;
    $this->commonsbc = new commonsbc;
  }

  public function createreportfilter($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'radioreporttype', 'checked', 'approved', 'delivered', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Delivery Order', 'value' => '0', 'color' => 'red'],
      ['label' => 'Sales Invoice', 'value' => '1', 'color' => 'red']

    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '0' as reporttype,
      '' as checked,
      '' as approved,
      '' as delivered,
      '0' as reporttype
      "
    );
  }

  // group_concat(distinct stock.ref separator ', ') as refs
  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
            right(year(head.dateid),2) as year,left(head.dateid,10) as dateid,concat(left(head.docno,2),right(head.docno,9)) as docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, head.agent,
            ag.clientname as agname, item.brand,
            wh.client as whcode, wh.clientname as whname,concat(left(stock.ref,2),right(stock.ref,9)) as ref
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.client=head.agent
            left join client as wh on wh.client=head.wh
            where head.doc='sj' and head.trno='$trno'
    

            UNION ALL
            select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
            right(year(head.dateid),2) as year,left(head.dateid,10) as dateid,concat(left(head.docno,2),right(head.docno,9)) as docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
             ag.clientname as agname, item.brand,
            wh.client as whcode, wh.clientname as whname,concat(left(stock.ref,2),right(stock.ref,9)) as ref
             from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.clientid=head.agentid
            left join client as wh on wh.clientid=head.whid
            where head.doc='sj' and head.trno='$trno'
        
             order by line";
    // var_dump($query);
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_sj_query($trno)
  {

    $query = "select stock.line,stock.rem as srem,head.rem,
          left(head.dateid,10) as dateid, concat(left(head.docno,2),right(head.docno,9)) as docno, client.client, client.clientname,
           head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
          item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
          item.sizeid,  ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join item on item.itemid=stock.itemid
          left join client as ag on ag.client=head.agent
          left join client as wh on wh.client=head.wh
          left join part_masterfile as part on part.part_id = item.part
          left join frontend_ebrands as brands on brands.brandid = item.brand
          where head.doc='sj' and head.trno='$trno'
          UNION ALL
          select stock.line,stock.rem as srem,head.rem,
          left(head.dateid,10) as dateid, concat(left(head.docno,2),right(head.docno,9)) as docno, client.client, client.clientname,
          head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
          item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
          item.sizeid,  ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.clientid
          left join item on item.itemid=stock.itemid
          left join client as ag on ag.clientid=head.agentid
          left join client as wh on wh.clientid=head.whid
          left join part_masterfile as part on part.part_id = item.part
          left join frontend_ebrands as brands on brands.brandid = item.brand
          where head.doc='sj' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['reporttype'] == '0') {
      $document = empty($data) ? 0 : $data[0]['docno'];
      $prefix = substr($document, 0, 2);
      if ($prefix == 'DR') {
        return $this->default_del_PDF($params, $data);
      } else {
        return $this->noprintmsg($params);
      }
      // return $this->default_del_PDF($params, $data);
    } else {
      // return $this->default_si_PDF($params, $data);
      // $document = empty($data) ? 0 : $data[0]['docno'];
      // $prefix = substr($document, 0, 2);
      // if ($prefix == 'SI') {
      return $this->default_si_PDF($params, $data);
      // } else {
      //   return $this->noprintmsg($params);
      // }
    }
  }


  public function default_del_header_PDF($params, $data)
  {
    // var_dump($y);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
    $font2 = "";

    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }


    if (Storage::disk('sbcpath')->exists('/fonts/BroadwayRegular.ttf')) {
      $font2 = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/BroadwayRegular.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    // $y = PDF::getY(); //10.00125
    $y = (float)30;
    $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
    $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 30, 30, 120, 120) : 'No image found'; //x, y,width,height
    PDF::SetFont($font2, '', 33);
    $name = "ROOSEVELT CHEMICAL INC.";
    $address = "73 F. Mariano Avenue Dela Paz NCR, Second District 1600 City of Pasig Philippines";
    $tel = "Contact Number: 8645-1089; 7900-9642 Fax: 8645-3425";
    PDF::MultiCell(720, 0, $name, '', 'C', false, 1,  '', $y + 5);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, $address . "\n" . $tel, '', 'C', false, 1,  '', $y + 45); //Rowen
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(720, 0, 'VAT REG TIN: 000-282-667-00000 ', '', 'C', false, 1,  '', $y + 80);

    // PDF::MultiCell(0, 0, "\n");
    $x = PDF::getX();
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(400, 0, '', '', '', false, 0, $x, $y + 105);
    PDF::MultiCell(320, 0, 'Delivery Order', '', 'L', false, 1,  $x + 400, $y + 105);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(400, 0, '', '', '', false, 0,  $x, $y + 125);
    PDF::MultiCell(320, 0, 'No. ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1,  $x + 400, $y + 125);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(50, 0, 'Sold To:', 'TL', 'L', false, 0,  $x, $y + 145);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(340, 0, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'TR', 'L', false, 0,  $x + 50, $y + 145);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 390, $y + 145);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'Date:', 'TL', 'L', false, 0,  $x + 400, $y + 145);
    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('M d,Y');
    PDF::MultiCell(75, 0, $datehere, 'TR', 'L', false, 0,  $x + 450, $y + 145);
    PDF::MultiCell(50, 0, 'Terms:', 'T', 'L', false, 0,  $x + 525, $y + 145);
    PDF::MultiCell(145, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'TR', 'L', false, 1,  $x + 575, $y + 145);


    $add = isset($data[0]['address']) ? $data[0]['address'] : '';
    $maxChars = 55;
    $adds = strlen($add);
    $firstLine = '';
    $remaininglines = [];
    $addsz = '';

    if ($adds > $maxChars) {
      $firstLine = substr($add, 0, $maxChars);
      $remaining = substr($add, $maxChars);
      // Split remaining address into multiple lines without cutting words
      while (strlen($remaining) > $maxChars) {
        // Find the last space within the maxChars limit
        $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

        // If there's no space, just cut at maxChars
        if ($spacePos === false) {
          $nextLine = substr($remaining, 0, $maxChars);
          $remaining = substr($remaining, $maxChars);
        } else {
          $nextLine = substr($remaining, 0, $spacePos);
          $remaining = substr($remaining, $spacePos + 1);
        }

        $remainingLines[] = $nextLine;
      }
      // Add the final remaining part if it's less than or equal to $maxChars
      if (strlen($remaining) > 0) {
        $remainingLines[] = $remaining;
      }
    } else {
      $addsz = $add;
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 0, 0, 0);
    PDF::MultiCell(50, 0, 'Address:', 'L', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(340, 0, $firstLine, 'R', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', $fontsize);

    $refs = [];
    foreach ($data as $row) {
      if ($row['ref'] != '') {
        $refs[] = $row['ref'];
      }
    }
    $refString = implode("\n", array_unique($refs));
    PDF::MultiCell(50, 0, 'P.O. No.:', 'TL', 'L', false, 0);
    PDF::MultiCell(75, 0, $refString, 'TR', 'L', false, 0);
    PDF::MultiCell(50, 0, 'RC No.:', 'T', 'L', false, 0);
    PDF::MultiCell(145, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'TR', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 2, 0, 0);
    PDF::MultiCell(50, 0, 'TIN:', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(340, 0, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'R', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(125, 0, '', 'L', '', false, 0);
    PDF::MultiCell(50, 0, 'Issued:', 'L', 'L', false, 0);
    PDF::MultiCell(145, 0, (isset($data[0]['issued']) ? $data[0]['issued'] : ''), 'R', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 0, 0);
    PDF::MultiCell(78, 0, 'Ship Via', 'TL', 'C', false, 0);
    PDF::MultiCell(78, 0, 'M / V', 'T', 'C', false, 0);
    PDF::MultiCell(78, 0, 'Voyage No.', 'T', 'C', false, 0);
    PDF::MultiCell(78, 0, 'B.L. No.', 'T', 'C', false, 0);
    PDF::MultiCell(78, 0, 'No. Ctn.', 'TR', 'C', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(125, 0, '', 'L', '', false, 0);
    PDF::MultiCell(50, 0, 'Place:', 'L', 'L', false, 0); // (isset($data[0]['place']) ? $data[0]['place'] : '')
    PDF::MultiCell(145, 0, '', 'R', 'L', false);

    PDF::MultiCell(78, 0, '', 'LB', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'BR', 'C', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(50, 0, 'Type:', 'TL', '', false, 0);
    PDF::MultiCell(75, 0, (isset($data[0]['type']) ? $data[0]['type'] : ''), 'T', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Salesman:', 'L', 'L', false, 0);

    // PDF::SetFont($font, '', $fontsize);
    $text = isset($data[0]['agname']) ? $data[0]['agname'] : '';
    $maxWidth = 145;       // maximum width ng MultiCell
    $baseFontSize = 10;    // maximum/base font size
    $minFontSize = 2;      // pinakamaliit na puwede

    $fontSize = $baseFontSize;
    PDF::SetFont($font, '', $fontSize);

    // Loop to shrink font until text fits the width 
    //Kung lampas sa maxWidth → babaan ng kaunti ang font (0.50 step).
    //Ulitin hanggang kasya sa width o maabot ang minimum font size (2)
    while (PDF::GetStringWidth($text) > $maxWidth && $fontSize > $minFontSize) {
      $fontSize -= 0.50; // babawas sa font hanggat hindi nagkakasya sa 145
      PDF::SetFont($font, '', $fontSize);
    }

    // Line height proportional sa font
    $lineHeight = $fontSize * 1.2; //1.2 → para hindi masikip ang text, may konting space sa itaas at ibaba ng letra

    PDF::MultiCell($maxWidth, $lineHeight, $text, 'R', 'L', false);

    PDF::SetFont($font, '', $fontsize);

    PDF::MultiCell(400, 0, '', '', '', false, 0);
    PDF::MultiCell(125, 0, '', 'LB', '', false, 0);
    PDF::MultiCell(50, 0, 'Page: ', 'LB', 'L', false, 0);
    PDF::MultiCell(145, 0, PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'BR', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    // $y = PDF::getY(); //283.00125
    $y = (float)273;
    $x = PDF::GetX();
    // var_dump($y);
    PDF::SetFont($fontbold, '', 10);
    PDF::SetCellPaddings(4, 4, 4, 4);
    // // SetCellPaddings($left, $top, $right, $bottom)
    PDF::MultiCell(390, 0, 'DESCRIPTION', 'TLRB', 'L', false, 0, $x, $y);
    PDF::MultiCell(66, 0, 'QTY', 'TRB', 'C', false, 0, $x + 390, $y);
    PDF::MultiCell(78, 0, 'UNIT COST', 'TRB', 'C', false, 0, $x + 456, $y);
    PDF::MultiCell(54, 0, 'DISC.', 'TRB', 'C', false, 0, $x + 534, $y);
    PDF::MultiCell(66, 0, 'DISC. AMT', 'TRB', 'C', false, 0, $x + 588, $y);
    PDF::MultiCell(66, 0, 'AMOUNT', 'TRB', 'C', false, 1, $x + 654, $y);
  }

  public function default_del_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $page = 15;
    $totalext = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }
    // $y = (float)80;
    $this->default_del_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetCellPaddings(0, 4, 0, 0);
    $rowCount = 0;
    // $y = PDF::getY();
    $x = PDF::GetX();
    $y = (float)295;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);

        $ext = number_format($data[$i]['ext'], 2);
        $discamt = 0;
        $disc = $data[$i]['disc'];
        // if ($disc != 0) {
        //   if (strpos($disc, '%') != false) {
        //     $discamt = ($data[$i]['amt'] * $data[$i]['qty']) * ($data[$i]['disc'] / 100);
        //   } else {
        //     // Walang percent sign (naka-number na siya)
        //     $dis1 = ($data[$i]['amt'] * $data[$i]['qty']) - $disc;
        //     $discamt = ($data[$i]['amt'] * $data[$i]['qty']) - $dis1;
        //   }
        // }
        if ($disc != 0) {
          $total = $data[$i]['amt'] * $data[$i]['qty']; // original na  total
          $discamt = 0;

          // Hatiin by "/"
          $parts = explode("/", $disc);

          foreach ($parts as $d) {
            $d = trim($d); // tanggal spaces
            if (strpos($d, '%') !== false) {
              // Remove % sign then convert to number
              $percent = floatval(str_replace('%', '', $d));
              // $less = $total * ($percent / 100);
              $less = round($total * ($percent / 100), 2);
              $discamt += $less;
              $total -= $less;
            } else {
              // Fixed amount
              // $less = floatval($d);
              $less = round(floatval($d), 2);
              $discamt += $less;
              $total -= $less;
            }
          }
        }


        $damt = number_format($discamt, 2);
        $discamt = number_format($discamt, 2);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_discamt = $this->reporter->fixcolumn([$discamt], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_discamt, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          $discamt = isset($arr_discamt[$r]) ? $arr_discamt[$r] : 0;
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? strtoupper($arr_uom[$r]) : ''), '', 'L', false, 0, $x,  $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(340, 15, ' ' . (isset($arr_itemname[$r]) ? strtoupper($arr_itemname[$r]) : ''), '', 'L', false, 0, $x + 50, $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, $x + 390,  $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(78, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, $x + 456,  $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(54, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, $x + 534,  $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, 15, ($discamt == 0) ? '' : $discamt, '', 'R', false, 0, $x + 588,  $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, $x + 654,  $y, true, 0, false, true, 0, 'M', false);
          $y = PDF::getY();
          $rowCount++;
          // kapag naka-15 lines na, lipat ng page
          // if ($rowCount >= $page && $i < count($data) - 1) {
          //   $this->default_del_header_PDF($params, $data);
          //   $rowCount = 0; // reset counter
          //   $y = (float)325; //reset ng y para dito uli magsimula sa next page
          // }
          if ($rowCount >= $page && $i < count($data) - 1) {
            $this->default_del_footer($params, $data);
            $rowCount = 0;
            $y = (float)295;
            $this->default_del_header_PDF($params, $data);
            PDF::SetCellPaddings(0, 4, 0, 0);
          }
        }
        $totalext += $data[$i]['ext'];
        $totaldisc += $damt;
      }
    }
    $this->default_del_footer($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_del_footer($params, $data)
  {
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }
    // $totaldisc = 0;
    // $totalext = 0;
    // foreach ($data as $row) {
    //   $discamt = 0; //

    //   $disc = $row['disc'];
    //   $amt = $row['amt'];
    //   $qty = $row['qty'];

    //   if ($disc != 0) {
    //     $total = $amt * $qty;

    //     $parts = explode("/", $disc);
    //     foreach ($parts as $d) {
    //       $d = trim($d);
    //       if (strpos($d, '%') !== false) {
    //         $percent = floatval(str_replace('%', '', $d));
    //         $less = $total * ($percent / 100);
    //         $discamt += $less;
    //         $total -= $less;
    //       } else {
    //         $less = floatval($d);
    //         $discamt += $less;
    //         $total -= $less;
    //       }
    //     }
    //   }

    //   $totalext += $row['ext'];
    //   $totaldisc += $discamt; //
    // }

    $totaldisc = 0;
    $totalext = 0;

    foreach ($data as $row) {
      $discamt = 0;

      $disc = $row['disc'];
      $amt = $row['amt'];
      $qty = $row['qty'];

      if ($disc != 0) {
        $total = $amt * $qty;

        $parts = explode("/", $disc);
        foreach ($parts as $d) {
          $d = trim($d);
          if (strpos($d, '%') !== false) {
            $percent = floatval(str_replace('%', '', $d));
            $less = round($total * ($percent / 100), 2);
            $discamt += $less;
            $total -= $less;
          } else {
            $less = round(floatval($d), 2);
            $discamt += $less;
            $total -= $less;
          }
        }
      }

      $totalext += $row['ext'];
      $totaldisc += $discamt;
    }


    $totaldisc = round($totaldisc, 2);





    $nvat = $totalext / 1.12;
    $lessvat = $nvat * .12;


    // $y = PDF::GetY(); //float(463.00395833333)
    $y1 = (float)540;
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', 'B', '', false, 0,  '', $y1);

    $x = (float)40;
    $nvat = $totalext / 1.12;
    $lessvat = $nvat * .12;

    // $row_y = $y1 + 10;
    // // $row2 = $y1 + 25;

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  $x, $y1);
    // PDF::MultiCell(147, 0,  'Standard Rated Amount :', '', 'L', false, 0,  $x + 60, $row_y);
    // PDF::MultiCell(153, 0,  number_format($nvat, 2), '', 'R', false, 0,  $x + 207, $row_y);
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  $x + 360, $row_y);
    // PDF::MultiCell(155, 0, 'Total Sales (Tax Inclusive) :', '', 'L', false, 0,  $x + 370, $row_y);
    // PDF::MultiCell(195, 0, number_format($totalext, 2), '', 'R', false, 1,  $x + 525, $row_y);

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(147, 0,  'Zero Rated Amount: ', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(153, 0,  '', '', 'R', false, 0, '',  '');
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(140, 0, 'Less: VAT :', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(210, 0, number_format($lessvat, 2), '', 'R', false, 1,  '',  '');


    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0, '', '');
    // PDF::MultiCell(147, 0,  'Exempted Amount: ', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(140, 0, 'Amount: Net of VAT :', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(210, 0, number_format($nvat, 2), '', 'R', false, 1,  '',  '');


    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(147, 0,  'VAT Amount: ', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(153, 0,   number_format($lessvat, 2), '', 'R', false, 0,  '',  '');
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(140, 0, 'Less: SC/PWD Discount :', '', 'L', false, 0,  '',  '');
    // PDF::MultiCell(210, 0, '', '', 'R', false, 1, '',  '');


    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(140, 0, 'Amount Due :', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(210, 0, number_format($nvat, 2), '', 'R', false, 1,  '',  '');


    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0, '', '');
    // PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(153, 0,  '', '', 'R', false, 0, '',  '');
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(140, 0, 'Add: VAT :', '', 'L', false, 0,  '',  '');
    // PDF::MultiCell(210, 0, number_format($lessvat, 2), '', 'R', false, 1,  '',  '');

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(140, 0, 'Less: Withholding Tax :', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(210, 0, '0.00', '', 'R', false, 1,  '',  '');


    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0,  '',  '');
    // PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    // PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    // PDF::MultiCell(140, 0, 'Total Amount Due :', '', 'L', false, 0,  '',  '');
    // PDF::MultiCell(50, 0, 'PHP', '', 'R', false, 0, '',  '');
    // PDF::MultiCell(160, 0, number_format($totalext, 2), '', 'R', false, 1,  '',  '');

    // $ys = (float)668;
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(560, 0,  '', '', 'L', false, 0,  $x, $ys);
    // PDF::MultiCell(160, 0, '', 'B', 'R', false, 1,  $x + 560,  $ys);

    // $y1 = PDF::GetY();
    $y2 = (float)690;
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetCellPaddings(2, 2, 2, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(360, 20,  'IMPORTANT: GOODS TRAVEL AT THE RISK OF THE BUYER', 'TBL', 'L', false, 0,  $x, $y2, true, 0, false, true, 0, 'M', false);
    PDF::SetFillColor(109, 100, 86);
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(360, 20,  'MAKE ALL CHECKS PAYABLE TO ROOSEVELT CHEMICAL, INC', 'TLBR', 'C', true, 1,  $x + 360, $y2, true, 0, false, true, 0, 'M', false);
    PDF::SetFillColor(0, 0, 0); // black background (or none)
    PDF::SetTextColor(0, 0, 0); // black text

    // SetCellPaddings($left, $top, $right, $bottom)
    PDF::SetFont($font, '',  8);
    PDF::SetCellPaddings(3, 2, 3, 0);

    PDF::MultiCell(620, 20,  'The undersigned declares to have received from ROOSEVELT CHEMICAL, INC. of Pasig City the goods detailed in order and condition and promise to pay the full amount of this Invoice in Pasig City within the term stated hereon with full understanding that all the said merchadise is and will still be the property of ROOSEVELT CHEMICAL, INC, until the amount therefore paid in full interest of prevailing rate is to be paid by the buyer on all overdue account. In case of suit, an additional sum equivalent of 25% of the amount due will be charged by the buyer for attorney\'s fee plus cost of suit and any legal action arising from this contract shall be instituted in the Court of Pasig City.', 'LR', 'J', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    $total = number_format($totaldisc, 2);
    PDF::MultiCell(100, 20,    "Discount: " . $total . "\n\nBPO\n", 'R', '', false, 1,  '', '');

    PDF::MultiCell(100, 20, '', 'LR', 'L', false, 1,  $x + 620, $y2 + 45); //r
    PDF::MultiCell(720, 20, '', 'LB', '', false, 1,  $x, $y2 + 45); //Bottom

    $checked = $params['params']['dataparams']['checked'];
    $approved = $params['params']['dataparams']['approved'];
    $delivered = $params['params']['dataparams']['delivered'];

    $y2 = (float)765;

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(350, 0, 'Received the above items in good order & condition', '', 'C', false, 0,  $x, $y2);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2);
    PDF::MultiCell(90, 0, 'Checked By :', 'T', '', false, 0,  $x + 370, $y2);
    PDF::MultiCell(260, 0,  $checked, 'RT', '', false, 1,  $x + 460, $y2);

    PDF::MultiCell(350, 0, '', 'B', 'C', false, 0,  $x, $y2 + 20);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 20);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2 + 20);
    PDF::MultiCell(90, 0, 'Date :', 'T', '', false, 0,  $x + 370, $y2 + 20);
    PDF::MultiCell(260, 0,  '', 'RT', '', false, 1,  $x + 460, $y2 + 20);


    PDF::MultiCell(350, 0, 'Authorized Signature', '', 'C', false, 0,  $x, $y2 + 40);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 40);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2 + 40);
    PDF::MultiCell(90, 0, 'Delivered By :', 'T', '', false, 0,  $x + 370, $y2 + 40);
    PDF::MultiCell(260, 0,  $delivered, 'RT', '', false, 1,  $x + 460, $y2 + 40);


    PDF::MultiCell(350, 0, '', 'B', 'C', false, 0,  $x, $y2 + 55);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 60);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2 + 60);
    PDF::MultiCell(90, 0, 'Date :', 'T', '', false, 0,  $x + 370, $y2 + 60);
    PDF::MultiCell(260, 0,  '', 'RT', '', false, 1,  $x + 460, $y2 + 60);

    PDF::MultiCell(350, 0, 'Printed Name', '', 'C', false, 0,  $x, $y2 + 73);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 80);
    PDF::MultiCell(10, 0, '', 'LTB', '', false, 0,  $x + 360, $y2 + 80);
    PDF::MultiCell(90, 0, 'Approved By :', 'TB', '', false, 0,  $x + 370, $y2 + 80);
    PDF::MultiCell(260, 0,  $approved, 'RTB', '', false, 1,  $x + 460, $y2 + 80);

    PDF::MultiCell(350, 0, '', 'B', 'C', false, 0,  $x, $y2 + 80);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 100);
    PDF::MultiCell(360, 0, '', '', '', false, 1,  $x + 360, $y2 + 100);

    PDF::MultiCell(350, 0, 'Date', '', 'C', false, 0,  $x, $y2 + 100);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 100);
    PDF::MultiCell(360, 0, '', '', '', false, 1,  $x + 360, $y2 + 100);
    PDF::SetCellPaddings(0, 0, 0, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(360, 0, '', '', 'C', false, 0, '', '');
    PDF::MultiCell(305, 0, '"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX"', 'B', 'L', false, 0, '', '');
    PDF::MultiCell(55, 0, '', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, 'Acknowledgement Certificate Control No.:', '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'Date Issued: January 01, 0001', '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'Inclusion Series: DO000000001 To: DO999999999', '', 'L', false, 1, '', '');


    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, 'QNE SOFTWARE PHILIPPINES, INC', '', 'L', false, 0, '', '');
    PDF::MultiCell(180, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(270, 0, 'Printed Date/Time: ' . $formattedDate, '', 'L', false, 0, '', '');
    PDF::MultiCell(50, 0, '', '', 'L', false, 1, '', '');

    PDF::MultiCell(400, 0, 'Unit 806 Pearl of the Orient Tower, 1240 Roxas Blvd., Ermita, Manila', '', 'L', false, 0, '', '');
    PDF::MultiCell(320, 0, 'Printed By: ' . $username, '', 'L', false, 1, '', '');

    PDF::MultiCell(400, 0, 'TIN: 006-934-485-000', '', 'L', false, 0, '', '');
    PDF::MultiCell(320, 0, 'QNE Optimum Version 2024.1.0.7', '', 'L', false, 1, '', '');
  }


  public function default_si_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $page = 15;
    $totalext = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }
    // $y = (float)80;
    $this->default_si_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetCellPaddings(0, 4, 0, 0);
    $rowCount = 0;
    $rowHeight = 15;
    $x = PDF::GetX();
    $y = (float)295;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);

        $ext = number_format($data[$i]['ext'], 2);
        $discamt = 0;
        $disc = $data[$i]['disc'];
        // if ($disc != 0) {
        //   if (strpos($disc, '%') != false) {
        //     $discamt = ($data[$i]['amt'] * $data[$i]['qty']) * ($data[$i]['disc'] / 100);
        //   } else {
        //     // Walang percent sign (naka-number na siya)
        //     $dis1 = ($data[$i]['amt'] * $data[$i]['qty']) - $disc;
        //     $discamt = ($data[$i]['amt'] * $data[$i]['qty']) - $dis1;
        //   }
        // }
        if ($disc != 0) {
          $total = $data[$i]['amt'] * $data[$i]['qty']; // original na  total
          $discamt = 0;

          // Hatiin by "/"
          $parts = explode("/", $disc);

          foreach ($parts as $d) {
            $d = trim($d); // tanggal spaces
            if (strpos($d, '%') !== false) {
              // Remove % sign then convert to number
              $percent = floatval(str_replace('%', '', $d));
              // $less = $total * ($percent / 100);
              $less = round($total * ($percent / 100), 2);
              $discamt += $less;
              $total -= $less;
            } else {
              // Fixed amount
              // $less = floatval($d);
              $less = round(floatval($d), 2);
              $discamt += $less;
              $total -= $less;
            }
          }
        }
        $damt = number_format($discamt, 2);
        $discamt = number_format($discamt, 2);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_discamt = $this->reporter->fixcolumn([$discamt], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_discamt, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          $discamt = isset($arr_discamt[$r]) ? $arr_discamt[$r] : 0;
          PDF::SetFont($font, '', $fontsize);
          PDF::SetXY($x, $y);
          PDF::MultiCell(50, $rowHeight, ' ' . (isset($arr_uom[$r]) ? strtoupper($arr_uom[$r]) : ''), '', 'L', false, 0, $x, $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(340, $rowHeight, ' ' . (isset($arr_itemname[$r]) ? strtoupper($arr_itemname[$r]) : ''), '', 'L', false, 0, $x + 50, $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, $rowHeight, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, $x + 390, $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(82, $rowHeight, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, $x + 456, $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, $rowHeight, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, $x + 538, $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, $rowHeight, ($discamt == 0) ? '' : $discamt, '', 'R', false, 0, $x + 588, $y, true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, $rowHeight, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1,  $x + 654, $y, true, 0, false, true, 0, 'M', false);

          $y = PDF::getY();
          $rowCount++;

          if ($rowCount >= $page && $i < count($data) - 1) {
            $this->default_footer($params, $data);
            $rowCount = 0;
            $y = (float)295;
            $this->default_si_header_PDF($params, $data);
            PDF::SetCellPaddings(0, 4, 0, 0);
          }
        }
        $totalext += $data[$i]['ext'];
        $totaldisc += $damt;
      }
    }

    $this->default_footer($params, $data);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function default_si_header_PDF($params, $data)
  {
    // var_dump($y);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
    $font2 = "";

    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }


    if (Storage::disk('sbcpath')->exists('/fonts/BroadwayRegular.ttf')) {
      $font2 = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/BroadwayRegular.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    // $y = PDF::getY(); //10.00125
    $y = (float)30;
    $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
    $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 30, 30, 120, 120) : 'No image found'; //x, y,width,height
    $name = "ROOSEVELT CHEMICAL INC.";
    $address = "73 F. Mariano Avenue Dela Paz NCR, Second District 1600 City of Pasig Philippines";
    $tel = "Contact Number: 8645-1089; 7900-9642 Fax: 8645-3425";
    PDF::SetFont($font2, '', 33);
    PDF::MultiCell(720, 0, $name, '', 'C', false, 1,  '', $y + 5);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, $address . "\n" . $tel, '', 'C', false, 1,  '', $y + 45); //Rowen
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(720, 0, 'VAT REG TIN: 000-282-667-00000 ', '', 'C', false, 1,  '', $y + 80);

    // PDF::MultiCell(0, 0, "\n");
    $x = PDF::getX();
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(400, 0, '', '', '', false, 0, $x, $y + 105);
    PDF::MultiCell(320, 0, 'Sales Invoice', '', 'L', false, 1,  $x + 400, $y + 105);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(400, 0, '', '', '', false, 0,  $x, $y + 125);
    PDF::MultiCell(320, 0, 'No. ' . (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 1,  $x + 400, $y + 125);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(50, 0, 'Sold To:', 'TL', 'L', false, 0,  $x, $y + 145);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(340, 0, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), 'TR', 'L', false, 0,  $x + 50, $y + 145);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 390, $y + 145);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'Date:', 'TL', 'L', false, 0,  $x + 400, $y + 145);
    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('M d,Y');
    PDF::MultiCell(75, 0, $datehere, 'TR', 'L', false, 0,  $x + 450, $y + 145);
    PDF::MultiCell(50, 0, 'Terms:', 'T', 'L', false, 0,  $x + 525, $y + 145);
    PDF::MultiCell(145, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'TR', 'L', false, 1,  $x + 575, $y + 145);

    $add = isset($data[0]['address']) ? $data[0]['address'] : '';
    $maxChars = 55;
    $adds = strlen($add);
    $firstLine = '';
    $remaininglines = [];
    $addsz = '';

    if ($adds > $maxChars) {
      $firstLine = substr($add, 0, $maxChars);
      $remaining = substr($add, $maxChars);
      // Split remaining address into multiple lines without cutting words
      while (strlen($remaining) > $maxChars) {
        // Find the last space within the maxChars limit
        $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

        // If there's no space, just cut at maxChars
        if ($spacePos === false) {
          $nextLine = substr($remaining, 0, $maxChars);
          $remaining = substr($remaining, $maxChars);
        } else {
          $nextLine = substr($remaining, 0, $spacePos);
          $remaining = substr($remaining, $spacePos + 1);
        }

        $remainingLines[] = $nextLine;
      }
      // Add the final remaining part if it's less than or equal to $maxChars
      if (strlen($remaining) > 0) {
        $remainingLines[] = $remaining;
      }
    } else {
      $addsz = $add;
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 0, 0, 0);
    PDF::MultiCell(50, 0, 'Address:', 'L', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(340, 0, $firstLine, 'R', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', $fontsize);

    $refs = [];
    foreach ($data as $row) {
      if ($row['ref'] != '') {
        $refs[] = $row['ref'];
      }
    }
    $refString = implode("\n", array_unique($refs));
    PDF::MultiCell(50, 0, 'P.O. No.:', 'TL', 'L', false, 0);
    PDF::MultiCell(75, 0, $refString, 'TR', 'L', false, 0);
    PDF::MultiCell(50, 0, 'RC No.:', 'T', 'L', false, 0);
    PDF::MultiCell(145, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'TR', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 2, 0, 0);
    PDF::MultiCell(50, 0, 'TIN:', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(340, 0, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'R', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(125, 0, '', 'L', '', false, 0);
    PDF::MultiCell(50, 0, 'Issued:', 'L', 'L', false, 0);
    PDF::MultiCell(145, 0, (isset($data[0]['issued']) ? $data[0]['issued'] : ''), 'R', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 0, 0);
    PDF::MultiCell(78, 0, 'Ship Via', 'TL', 'C', false, 0);
    PDF::MultiCell(78, 0, 'M / V', 'T', 'C', false, 0);
    PDF::MultiCell(78, 0, 'Voyage No.', 'T', 'C', false, 0);
    PDF::MultiCell(78, 0, 'B.L. No.', 'T', 'C', false, 0);
    PDF::MultiCell(78, 0, 'No. Ctn.', 'TR', 'C', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(125, 0, '', 'L', '', false, 0);
    PDF::MultiCell(50, 0, 'Place:', 'L', 'L', false, 0); // (isset($data[0]['place']) ? $data[0]['place'] : '')
    PDF::MultiCell(145, 0, '', 'R', 'L', false);

    PDF::MultiCell(78, 0, '', 'LB', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(78, 0, '', 'BR', 'C', false, 0);
    PDF::MultiCell(10, 0, '', '', '', false, 0);
    PDF::MultiCell(50, 0, 'Type:', 'TL', '', false, 0);
    PDF::MultiCell(75, 0, (isset($data[0]['type']) ? $data[0]['type'] : ''), 'T', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Salesman:', 'L', 'L', false, 0);
    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(107, 0, (isset($data[0]['agname']) ? $data[0]['agname'] : ''), 'R', 'L', false);
    $text = isset($data[0]['agname']) ? $data[0]['agname'] : '';
    $maxWidth = 145;       // maximum width ng MultiCell
    $baseFontSize = 10;    // maximum/base font size
    $minFontSize = 2;      // pinakamaliit na puwede

    $fontSize = $baseFontSize;
    PDF::SetFont($font, '', $fontSize);

    // Loop to shrink font until text fits the width 
    //Kung lampas sa maxWidth → babaan ng kaunti ang font (0.50 step).
    //Ulitin hanggang kasya sa width o maabot ang minimum font size (2)
    while (PDF::GetStringWidth($text) > $maxWidth && $fontSize > $minFontSize) {
      $fontSize -= 0.50; // babawas sa font hanggat hindi nagkakasya sa 145
      PDF::SetFont($font, '', $fontSize);
    }

    // Line height proportional sa font
    $lineHeight = $fontSize * 1.2; //1.2 → para hindi masikip ang text, may konting space sa itaas at ibaba ng letra

    PDF::MultiCell($maxWidth, $lineHeight, $text, 'R', 'L', false);

    PDF::SetFont($font, '', $fontsize);


    PDF::MultiCell(400, 0, '', '', '', false, 0);
    PDF::MultiCell(125, 0, '', 'LB', '', false, 0);
    PDF::MultiCell(50, 0, 'Page: ', 'LB', 'L', false, 0);
    PDF::MultiCell(145, 0, PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'BR', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    // $y = PDF::getY(); //283.00125
    $y = (float)273;
    $x = PDF::GetX();
    // var_dump($y);
    PDF::SetFont($fontbold, '', 10);
    PDF::SetCellPaddings(4, 4, 4, 4);
    // // SetCellPaddings($left, $top, $right, $bottom)
    PDF::MultiCell(390, 0, 'DESCRIPTION', 'TLRB', 'L', false, 0, $x, $y);
    PDF::MultiCell(66, 0, 'QTY', 'TRB', 'C', false, 0, $x + 390, $y);
    PDF::MultiCell(82, 0, 'UNIT COST', 'TRB', 'C', false, 0, $x + 456, $y);
    PDF::MultiCell(50, 0, 'DISC.', 'TRB', 'C', false, 0, $x + 538, $y);
    PDF::MultiCell(66, 0, 'DISC. AMT', 'TRB', 'C', false, 0, $x + 588, $y);
    PDF::MultiCell(66, 0, 'AMOUNT', 'TRB', 'C', false, 1, $x + 654, $y);
  }
  public function default_footer($params, $data)
  {
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }

    // $totaldisc = 0;
    // $totalext = 0;

    // foreach ($data as $row) {
    //   $discamt = 0; //

    //   $disc = $row['disc'];
    //   $amt = $row['amt'];
    //   $qty = $row['qty'];

    //   if ($disc != 0) {
    //     $total = $amt * $qty;

    //     $parts = explode("/", $disc);
    //     foreach ($parts as $d) {
    //       $d = trim($d);
    //       if (strpos($d, '%') !== false) {
    //         $percent = floatval(str_replace('%', '', $d));
    //         $less = $total * ($percent / 100);
    //         $discamt += $less;
    //         $total -= $less;
    //       } else {
    //         $less = floatval($d);
    //         $discamt += $less;
    //         $total -= $less;
    //       }
    //     }
    //   }

    //   $totalext += $row['ext'];
    //   $totaldisc += $discamt; //


    // }
    $totaldisc = 0;
    $totalext = 0;

    foreach ($data as $row) {
      $discamt = 0;

      $disc = $row['disc'];
      $amt = $row['amt'];
      $qty = $row['qty'];

      if ($disc != 0) {
        $total = $amt * $qty;

        $parts = explode("/", $disc);
        foreach ($parts as $d) {
          $d = trim($d);
          if (strpos($d, '%') !== false) {
            $percent = floatval(str_replace('%', '', $d));
            $less = round($total * ($percent / 100), 2);
            $discamt += $less;
            $total -= $less;
          } else {
            $less = round(floatval($d), 2);
            $discamt += $less;
            $total -= $less;
          }
        }
      }

      $totalext += $row['ext'];
      $totaldisc += $discamt;
    }


    $totaldisc = round($totaldisc, 2);





    $nvat = $totalext / 1.12;
    $lessvat = $nvat * .12;


    // $y = PDF::GetY(); //float(463.00395833333)
    $y1 = (float)540;
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B', '', false, 0,  '', $y1);

    $x = (float)40;
    $nvat = $totalext / 1.12;
    $lessvat = $nvat * .12;

    $row_y = $y1 + 10;
    // $row2 = $y1 + 25;

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  $x, $y1);
    PDF::MultiCell(147, 0,  'Vatable Sales :', '', 'L', false, 0,  $x + 60, $row_y);
    PDF::MultiCell(153, 0,  number_format($nvat, 2), '', 'R', false, 0,  $x + 207, $row_y);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  $x + 360, $row_y);
    PDF::MultiCell(155, 0, 'Total Sales (Tax Inclusive) :', '', 'L', false, 0,  $x + 370, $row_y);
    PDF::MultiCell(195, 0, number_format($totalext, 2), '', 'R', false, 1,  $x + 525, $row_y);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(147, 0,  'Zero Rated Amount: ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(153, 0,  '', '', 'R', false, 0, '',  '');
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(140, 0, 'Less: VAT :', '', 'L', false, 0, '',  '');
    PDF::MultiCell(210, 0, number_format($lessvat, 2), '', 'R', false, 1,  '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0, '', '');
    PDF::MultiCell(147, 0,  'VAT Exempt Sales: ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(140, 0, 'Amount: Net of VAT :', '', 'L', false, 0, '',  '');
    PDF::MultiCell(210, 0, number_format($nvat, 2), '', 'R', false, 1,  '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(147, 0,  'VAT Amount: ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(153, 0,   number_format($lessvat, 2), '', 'R', false, 0,  '',  '');
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(140, 0, 'Less: SC/PWD Discount :', '', 'L', false, 0,  '',  '');
    PDF::MultiCell(210, 0, '', '', 'R', false, 1, '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(140, 0, 'Amount Due :', '', 'L', false, 0, '',  '');
    PDF::MultiCell(210, 0, number_format($nvat, 2), '', 'R', false, 1,  '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0, '', '');
    PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(153, 0,  '', '', 'R', false, 0, '',  '');
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(140, 0, 'Add: VAT :', '', 'L', false, 0,  '',  '');
    PDF::MultiCell(210, 0, number_format($lessvat, 2), '', 'R', false, 1,  '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(140, 0, 'Less: Withholding Tax :', '', 'L', false, 0, '',  '');
    PDF::MultiCell(210, 0, '0.00', '', 'R', false, 1,  '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0,  '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(147, 0,  ' ', '', 'L', false, 0,  '',  '');
    PDF::MultiCell(153, 0,  '', '', 'R', false, 0,  '',  '');
    PDF::MultiCell(10, 0, '', '', 'L', false, 0,  '', '');
    PDF::MultiCell(140, 0, 'Total Amount Due :', '', 'L', false, 0,  '',  '');
    PDF::MultiCell(50, 0, 'PHP', '', 'R', false, 0, '',  '');
    PDF::MultiCell(160, 0, number_format($totalext, 2), '', 'R', false, 1,  '',  '');

    $ys = (float)668;
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(560, 0,  '', '', 'L', false, 0,  $x, $ys);
    PDF::MultiCell(160, 0, '', 'B', 'R', false, 1,  $x + 560,  $ys);

    // $y1 = PDF::GetY();
    $y2 = (float)690;

    PDF::SetCellPaddings(2, 2, 2, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(360, 20,  'IMPORTANT: GOODS TRAVEL AT THE RISK OF THE BUYER', 'TBL', 'L', false, 0,  $x, $y2, true, 0, false, true, 0, 'M', false);
    PDF::SetFillColor(109, 100, 86);
    PDF::SetTextColor(255, 255, 255);
    PDF::MultiCell(360, 20,  'MAKE ALL CHECKS PAYABLE TO ROOSEVELT CHEMICAL, INC', 'TLBR', 'C', true, 1,  $x + 360, $y2, true, 0, false, true, 0, 'M', false);
    PDF::SetFillColor(0, 0, 0); // black background (or none)
    PDF::SetTextColor(0, 0, 0); // black text
    // SetCellPaddings($left, $top, $right, $bottom)
    PDF::SetFont($font, '',  8);
    PDF::SetCellPaddings(3, 2, 3, 0);

    PDF::MultiCell(620, 20,  'The undersigned declares to have received from ROOSEVELT CHEMICAL, INC. of Pasig City the goods detailed in order and condition and promise to pay the full amount of this Invoice in Pasig City within the term stated hereon with full understanding that all the said merchadise is and will still be the property of ROOSEVELT CHEMICAL, INC, until the amount therefore paid in full interest of prevailing rate is to be paid by the buyer on all overdue account. In case of suit, an additional sum equivalent of 25% of the amount due will be charged by the buyer for attorney\'s fee plus cost of suit and any legal action arising from this contract shall be instituted in the Court of Pasig City.', 'LR', 'J', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    $total = number_format($totaldisc, 2);
    PDF::MultiCell(100, 20,    "Discount: " . $total . "\n\nBPO\n", 'R', '', false, 1,  '', '');

    PDF::MultiCell(100, 20, '', 'LR', 'L', false, 1,  $x + 620, $y2 + 45); //r
    PDF::MultiCell(720, 20, '', 'LB', '', false, 1,  $x, $y2 + 45); //Bottom

    $checked = $params['params']['dataparams']['checked'];
    $approved = $params['params']['dataparams']['approved'];
    $delivered = $params['params']['dataparams']['delivered'];

    $y2 = (float)765;

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(350, 0, 'Received the above items in good order & condition', '', 'C', false, 0,  $x, $y2);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2);
    PDF::MultiCell(90, 0, 'Checked By :', 'T', '', false, 0,  $x + 370, $y2);
    PDF::MultiCell(260, 0,  $checked, 'RT', '', false, 1,  $x + 460, $y2);

    PDF::MultiCell(350, 0, '', 'B', 'C', false, 0,  $x, $y2 + 20);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 20);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2 + 20);
    PDF::MultiCell(90, 0, 'Date :', 'T', '', false, 0,  $x + 370, $y2 + 20);
    PDF::MultiCell(260, 0,  '', 'RT', '', false, 1,  $x + 460, $y2 + 20);


    PDF::MultiCell(350, 0, 'Authorized Signature', '', 'C', false, 0,  $x, $y2 + 40);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 40);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2 + 40);
    PDF::MultiCell(90, 0, 'Delivered By :', 'T', '', false, 0,  $x + 370, $y2 + 40);
    PDF::MultiCell(260, 0,  $delivered, 'RT', '', false, 1,  $x + 460, $y2 + 40);


    PDF::MultiCell(350, 0, '', 'B', 'C', false, 0,  $x, $y2 + 55);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 60);
    PDF::MultiCell(10, 0, '', 'LT', '', false, 0,  $x + 360, $y2 + 60);
    PDF::MultiCell(90, 0, 'Date :', 'T', '', false, 0,  $x + 370, $y2 + 60);
    PDF::MultiCell(260, 0,  '', 'RT', '', false, 1,  $x + 460, $y2 + 60);

    PDF::MultiCell(350, 0, 'Printed Name', '', 'C', false, 0,  $x, $y2 + 73);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 80);
    PDF::MultiCell(10, 0, '', 'LTB', '', false, 0,  $x + 360, $y2 + 80);
    PDF::MultiCell(90, 0, 'Approved By :', 'TB', '', false, 0,  $x + 370, $y2 + 80);
    PDF::MultiCell(260, 0,  $approved, 'RTB', '', false, 1,  $x + 460, $y2 + 80);

    PDF::MultiCell(350, 0, '', 'B', 'C', false, 0,  $x, $y2 + 80);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 100);
    PDF::MultiCell(360, 0, '', '', '', false, 1,  $x + 360, $y2 + 100);

    PDF::MultiCell(350, 0, 'Date', '', 'C', false, 0,  $x, $y2 + 100);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 350, $y2 + 100);
    PDF::MultiCell(360, 0, '', '', '', false, 1,  $x + 360, $y2 + 100);

    PDF::SetCellPaddings(0, 0, 0, 0);

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(400, 0, '', '', 'C', false, 0, '', '');
    // PDF::MultiCell(300, 0, '"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX"', 'B', 'L', false, 0, $x+400, $y2+180);
    // PDF::MultiCell(20, 0, '', '', 'L', false, 1, '', '');
    $y3 = (float)890;
    PDF::SetFont($font, '', $fontsize);
    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(720, 0, 'Acknowledgement Certificate Control No.:', '', 'L', false, 1, '', '');
    // PDF::MultiCell(720, 0, 'Date Issued: January 01, 0001', '', 'L', false, 1, '', '');
    // PDF::MultiCell(720, 0, 'Inclusion Series: DO000000001 To: DO999999999', '', 'L', false, 1, '', '');

    // $printeddate = $this->othersClass->getCurrentTimeStamp();
    // $datetime = new DateTime($printeddate);

    // // Format with AM/PM
    // $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    // $username = $params['params']['user'];
    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(220, 0, 'QNE SOFTWARE PHILIPPINES, INC', '', 'L', false, 0, '', '');
    // PDF::MultiCell(180, 0, '', '', 'L', false, 0, '', '');
    // PDF::MultiCell(270, 0, 'Printed Date/Time: ' . $formattedDate, '', 'L', false, 0, '', '');
    // PDF::MultiCell(50, 0, '', '', 'L', false, 1, '', '');

    // PDF::MultiCell(400, 0, 'Unit 806 Pearl of the Orient Tower, 1240 Roxas Blvd., Ermita, Manila', '', 'L', false, 0, '', '');
    // PDF::MultiCell(320, 0, 'Printed By: ' . $username, '', 'L', false, 1, '', '');

    // PDF::MultiCell(400, 0, 'TIN: 006-934-485-000', '', 'L', false, 0, '', '');
    // PDF::MultiCell(320, 0, 'QNE Optimum Version 2024.1.0.7', '', 'L', false, 1, '', '');

    PDF::MultiCell(360, 0, 'Acknowledgement Certificate Control No.:', '', 'L', false, 0, '', $y3);
    PDF::MultiCell(360, 0, 'QNE SOFTWARE PHILIPPINES, INC', '', 'L', false, 1, '', '');

    PDF::MultiCell(360, 0, 'Date Issued: January 01, 0001', '', 'L', false, 0, '', '');
    PDF::MultiCell(360, 0, 'Unit 806 Pearl of the Orient Tower, 1240 Roxas Blvd., Ermita, Manila', '', 'L', false, 1, '', '');
    PDF::MultiCell(360, 0, 'Inclusion Series: DO000000001 To: DO999999999', '', 'L', false, 0, '', '');
    PDF::MultiCell(360, 0, 'TIN: 006-934-485-000', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '', 'L', false, 1, '', '');

    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, 'Printed Date/Time: ' . $formattedDate, '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'Printed By: ' . $username, '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'QNE Optimum Version 2024.1.0.7', '', 'L', false, 1, '', '');
  }

  public function noprintmsg($params)
  {
    $font = "";
    $fontbold = "";
    $fontsize = "11";

    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    $label = "";
    if ($params['params']['dataparams']['reporttype'] == '0') { // DR
      $label = "DR";
    } else { // SI
      $label = "SI";
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(500, 0, "The prefix must be '" . $label . "' to generate the report.", '', 'L', false);

    PDF::SetFont($font, '', 5);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
