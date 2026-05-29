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
use DateTime;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class pl
{

  private $modulename = "Packing List";
  private $reportheader;
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
  }

  public function createreportfilter($config)
  {
    $companyid = $config['params']['companyid'];
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
    $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $center = $config['params']['center'];
    $isposted = $this->othersClass->isposted2($trno, 'transnum');
    $table = 'hplhead';
    if (!$isposted) {
      $table = 'plhead';
    }

    $query = "select concat(left(head.docno,2),right(head.docno,10)) as invoice,date(head.dateid) as dateid,it.itemname,it.sizeid,stock.uom, sum(stock.iss) as qty,sum(stock.ext) as ext,
           hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address,format(hpl.amount,2) as amount
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item as it on it.itemid=stock.itemid
            left join $table as hpl on hpl.trno=head.pltrno
            left join transnum as num on num.trno = head.trno
            left join client on client.client = hpl.client
            where head.pltrno = '$trno' and num.center='" . $center . "'
            group by head.docno,date(head.dateid),it.itemname,it.sizeid,stock.uom,
            hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address,hpl.amount


       union all

    select concat(left(head.docno,2),right(head.docno,10)) as invoice,date(head.dateid) as dateid,it.itemname,it.sizeid,stock.uom, sum(stock.iss) as qty,sum(stock.ext) as ext,
             hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address,format(hpl.amount,2) as amount
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join item as it on it.itemid=stock.itemid
            left join $table as hpl on hpl.trno=head.pltrno
            left join transnum as num on num.trno = head.trno
            left join client on client.client = hpl.client
            where head.pltrno = '$trno' and num.center='" . $center . "'
            group by head.docno,date(head.dateid),it.itemname,it.sizeid,stock.uom,
            hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address,hpl.amount";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_pl_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->roosevelt_pl_PDF($params, $data);
    }
  }


  public function roosevelt_pl_header_PDF($params, $data, $tablehead)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    // $font = "";
    // $fontbold = "";
    // $fontsize = 10;
    // $font2 = "";

    // if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //   $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //   $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    // }
    // if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
    //   $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
    //   $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    // }


    $fontsize = 15;
    $font = "Courier";
    $fontbold = "CourierB";

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
    PDF::SetFont($font, '', 15);
    $name = "Roosevelt Chemicals Incorporated";
    $address = "F. Mariano Ave. Bo. de la Paz, Pasig, Metro Manila";
    $tel = "Contact Number: 8645-1089; 7900-9642 Fax: 8645-3425";
    PDF::MultiCell(720, 0, $name, '', 'C', false, 1,  '', $y + 5);
    PDF::SetFont($font, '', 15);
    PDF::MultiCell(720, 0, $address, '', 'C', false, 1,  '', $y + 20); //Rowen


    $x = PDF::getX();
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(720, 0, 'Packing List', '', 'C', false, 1,  $x, $y + 35);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0,  $x, $y + 65);
    PDF::MultiCell(105, 0, 'DATE', '', 'L', false, 0,  $x + 30, $y + 65);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0,  $x + 135, $y + 65);
    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('m/d/Y');
    PDF::MultiCell(315, 0, $datehere, '', 'L', false, 1, $x + 150, $y + 65);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(40, 0, 'PL#', '', 'L', false, 1, $x + 465, $y + 65);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0,  $x + 505, $y + 65);
    PDF::MultiCell(200, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, $x + 520, $y + 65); //(isset($data[0]['docno']) ? $data[0]['docno'] : '')



    PDF::SetFont($font, '', $fontsize);
    // PDF::SetCellPaddings($left, $top, $right, $bottom);
    PDF::SetCellPaddings(0, 0, 0, 3);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(105, 0, 'SHIPPER', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0);
    PDF::MultiCell(570, 0, 'Roosevelt Chemicals Incorporated', '', 'L', false, 1);

    PDF::setCellHeightRatio(1.8);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(105, 0, 'CONSIGNEE', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0);
    PDF::MultiCell(570, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');

    $line1 = PDF::getNumLines($address, 570);

    if ($line1 > 1) {
      PDF::setCellHeightRatio(1);
    }

    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(105, 0, 'DESTINATION', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0);
    PDF::MultiCell(570, 0, $address, '', 'L', false, 1);

    // PDF::MultiCell(0, 0, "\n");
    if ($tablehead != 1) {
      PDF::setCellHeightRatio(1.25);
      $y1 = (float)208; //184 //192
      PDF::SetXY($x, $y1);
      PDF::SetCellPaddings(4, 4, 4, 4);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(30, 0, '', '', 'C', false, 0);
      PDF::MultiCell(85, 0, 'QTY', '', 'R', false, 0);
      // PDF::MultiCell(5, 0, '', '', 'C', false, 0);
      PDF::MultiCell(100, 0, 'UOM', '', 'L', false, 0);
      PDF::MultiCell(505, 0, 'ITEMNAME', '', 'L', false, 1);
    }

    PDF::SetCellPaddings(0, 0, 0, 0);
  }


  public function roosevelt_pl_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 30; //30 45
    $totalext = 0;


    $border = "1px solid ";
    $fontsize = 15;
    $font = "Courier";
    $fontbold = "CourierB";

    $this->roosevelt_pl_header_PDF($params, $data, $tablehead = 0);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    // PDF::SetCellPaddings(0, 0, 0, 0); //left ,top, right,bottom
    $rowCount = 0;
    $countarr = 0;
    $y = (float)230;
    $x = PDF::GetX();
    PDF::setCellHeightRatio(1);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $sizeid = $data[$i]['sizeid'];

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '15', 0);
        $arr_sizeid = $this->reporter->fixcolumn([$sizeid], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::SetXY($x, $y);
          PDF::MultiCell(30, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false); //(isset($arr_qty[$r]) ? $arr_qty[$r] : '')
          // PDF::MultiCell(5, 0, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(505, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          $y = PDF::getY();
          $rowCount++;
          if ($rowCount >= $page && $i < count($data) - 1) {
            $this->continuation_footer($params, $data);
            $this->default_footer3($params, $data);
            $rowCount = 0;
            $y = (float)230;
            $this->roosevelt_pl_header_PDF($params, $data, $tablehead = 0);
            // PDF::SetCellPaddings(0, 1, 0, 0);
            PDF::setCellHeightRatio(1);
          }
        }
      }
    }
    // if ($rowCount > 36 && $rowCount <= $count) {
    //   $this->continuation_footer($params, $data);
    //   $this->default_footer3($params, $data);
    //   $rowCount = 0;
    //   $y = (float)230;
    //   $tablehead = 1;
    //   $this->roosevelt_pl_header_PDF($params, $data, $tablehead);
    //   PDF::MultiCell(0, 0, "\n");
    //   $this->default_footer1($params, $data);
    //   $this->default_footer2($params, $data);
    // } else { //36 pababa
    $this->default_footer1($params, $data);
    $this->default_footer2($params, $data);
    // }


    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function default_footer1($params, $data)
  {
    $fontsize = 15;
    $font = "Courier";
    $fontbold = "CourierB";

    $invoices = [];
    $totalctns = 0;
    foreach ($data as $row) {
      // $totalctns += $row['amount'];
      $totalctns = $row['amount'];
      if ($row['invoice'] != '') {
        $invoices[] = $row['invoice'];
      }
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', ''); //space

    PDF::SetFont($font, '', $fontsize);
    $invoicesstring = implode(" , ", array_unique($invoices));
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(30, 0,  '', '', 'L', false, 0);
    PDF::MultiCell(90, 0,  number_format($totalctns, 2), 'T', 'R', false, 0);
    PDF::MultiCell(85, 0,  ' ' . 'ctns.', 'T', 'L', false, 0);
    PDF::MultiCell(515, 0,  '', '', 'L', false, 1);


    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0,  '', '', 'L', false, 0);
    PDF::MultiCell(690, 0, 'List of invoices', '', 'L', false, 1);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(30, 0,  '', '', 'L', false, 0);
    PDF::MultiCell(135, 0,  '', 'B', 'L', false, 0);
    PDF::MultiCell(555, 0,  '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(30, 0,  '', '', 'L', false, 0);
    PDF::MultiCell(690, 0,  $invoicesstring, '', 'L', false, 1);
  }

  public function default_footer2($params, $data)
  {
    $fontsize = 15;
    $font = "Courier";
    $fontbold = "CourierB";

    $totalext = 0;
    foreach ($data as $row) {
      $totalext += $row['ext'];
    }

    PDF::SetY(870);
    PDF::MultiCell(720, 0, '', 'T', 'L', false, 1);

    PDF::SetY(875);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(170, 0, 'VIA : JADES CARGO', '', 'L', false, 0);
    PDF::MultiCell(90, 0, 'WAYBILL# :', '', '', false, 0);
    PDF::MultiCell(230, 0, '', '', '', false, 0);
    PDF::MultiCell(110, 0, 'AMOUNT :', '', 'R', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0,  number_format($totalext, 2), '', 'R', false, 1);

    PDF::SetY(890);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(690, 0, 'Received in good order & condition', '', 'L', false, 1, '', '');
    // PDF::MultiCell(400, 0, '', '', 'L', false, 1, '', '');

    PDF::SetY(940);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(250, 0, 'Signature over printed name', 'T', 'C', false, 0, '', '');
    PDF::MultiCell(440, 0, '', '', 'L', false, 1, '', '');

    PDF::SetY(962);
    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);
    $formattedDate = $datetime->format('Y/m/d h:i:s a'); //2025-09-25 16:46:32 pm
    PDF::MultiCell(614, 0,  $formattedDate, '', 'L', false, 0, '', '');
    PDF::MultiCell(106, 0,  'Page ' . PDF::PageNo(), '', 'R', false, 1);
  }



  public function default_footer3($params, $data)
  {
    $fontsize = 15;
    $font = "Courier";
    // Format with AM/PM
    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);
    $formattedDate = $datetime->format('Y/m/d h:i:s a'); //2025-09-25 16:46:32 pm
    PDF::SetFont($font, '', $fontsize);
    PDF::SetY(956);
    PDF::MultiCell(614, 0,  $formattedDate, '', 'L', false, 0, '', '');

    $currentPage = PDF::getAliasNumPage();
    $totalPages = PDF::getAliasNbPages();
    PDF::MultiCell(106, 0,  'Page ' . PDF::PageNo(), '', 'R', false, 1);
    //PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages()


    // $pageText = 'Page ' . $currentPage . ' of ' . $totalPages;
    // PDF::MultiCell(306, 0, $pageText, '', 'R', false, 1);
  }

  public function continuation_footer($params, $data)
  {
    $fontsize = 15;
    $font = "Courier";
    PDF::SetFont($font, '', $fontsize);
    // PDF::SetY(945);
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(620, 0,  "**CONTINUED ON NEXT PAGE**", '', 'C', false, 1, '', '');
  }
}
