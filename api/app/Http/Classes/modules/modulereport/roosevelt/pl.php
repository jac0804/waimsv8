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

    $query = "select right(head.docno,10) as invoice,date(head.dateid) as dateid,it.itemname,it.sizeid,stock.uom, sum(stock.iss) as qty,sum(stock.ext) as ext,
           hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item as it on it.itemid=stock.itemid
            left join $table as hpl on hpl.trno=head.pltrno
            left join transnum as num on num.trno = head.trno
            left join client on client.client = hpl.client
            where head.pltrno = '$trno' and num.center='" . $center . "'
            group by head.docno,date(head.dateid),it.itemname,it.sizeid,stock.uom,
            hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address


       union all

    select right(head.docno,10) as invoice,date(head.dateid) as dateid,it.itemname,it.sizeid,stock.uom, sum(stock.iss) as qty,sum(stock.ext) as ext,
             hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join item as it on it.itemid=stock.itemid
            left join $table as hpl on hpl.trno=head.pltrno
            left join transnum as num on num.trno = head.trno
            left join client on client.client = hpl.client
            where head.pltrno = '$trno' and num.center='" . $center . "'
            group by head.docno,date(head.dateid),it.itemname,it.sizeid,stock.uom,
            hpl.doc,hpl.docno,hpl.dateid,hpl.trno , client.clientname,hpl.address";

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


  public function roosevelt_pl_header_PDF($params, $data)
  {
    // var_dump($y);
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


    $fontsize = 12;
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
    PDF::SetFont($font, '', 12);
    $name = "Roosevelt Chemicals Incorporated";
    $address = "F. Mariano Ave. Bo. de la Paz, Pasig, Metrno Manila";
    $tel = "Contact Number: 8645-1089; 7900-9642 Fax: 8645-3425";
    PDF::MultiCell(720, 0, $name, '', 'C', false, 1,  '', $y + 5);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 0, $address, '', 'C', false, 1,  '', $y + 20); //Rowen

    // PDF::MultiCell(0, 0, "\n");
    $x = PDF::getX();
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(720, 0, 'Packing List', '', 'C', false, 1,  $x, $y + 35);


    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0,  $x, $y + 55);
    PDF::MultiCell(90, 0, 'DATE', '', 'L', false, 0,  $x + 30, $y + 55);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0,  $x + 120, $y + 55);
    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('m/d/Y');
    PDF::MultiCell(340, 0, $datehere, '', 'L', false, 1, $x + 135, $y + 55);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, 'PL#', '', 'L', false, 1, $x + 475, $y + 55);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0,  $x + 505, $y + 55);
    PDF::MultiCell(200, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, $x + 520, $y + 55); //(isset($data[0]['docno']) ? $data[0]['docno'] : '')



    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(90, 0, 'SHIPPER', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0);
    PDF::MultiCell(383, 0, 'Roosevelt Chemicals Incorporated', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(90, 0, 'CONSIGNEE', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0);
    PDF::MultiCell(585, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(90, 0, 'DESTINATION', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'C', false, 0);
    PDF::MultiCell(585, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1);

    // PDF::MultiCell(0, 0, "\n");
    $y1 = (float)184;
    PDF::SetXY($x, $y1);
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(65, 0, 'QTY', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'UOM', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'SIZEID', '', 'C', false, 0);
    PDF::MultiCell(65, 0, '', '', 'C', false, 0);
    PDF::MultiCell(420, 0, 'ITEMNAME', '', 'L', false, 1);

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
    $count = $page = 25;
    $totalext = 0;


    $border = "1px solid ";
    $fontsize = 12;
    $font = "Courier";
    $fontbold = "CourierB";

    $this->roosevelt_pl_header_PDF($params, $data);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetCellPaddings(0, 4, 0, 0);
    $rowCount = 0;
    $countarr = 0;
    $y = (float)210;
    $x = PDF::GetX();

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

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_sizeid]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::SetXY($x, $y);
          PDF::MultiCell(30, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(65, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_sizeid[$r]) ? $arr_sizeid[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(65, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(420, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          $y = PDF::getY();
          $rowCount++;
          if ($rowCount >= $page && $i < count($data) - 1) {
            $this->default_footer1($params, $data);
            $this->default_footer2($params, $data);
            $rowCount = 0;
            $y = (float)210;
            $this->roosevelt_pl_header_PDF($params, $data);
            PDF::SetCellPaddings(0, 4, 0, 0);
          }
        }
      }
    }


    $this->default_footer1($params, $data);
    $this->default_footer2($params, $data);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function default_footer1($params, $data)
  {
    $fontsize = 12;
    $font = "Courier";
    $fontbold = "CourierB";

    $invoices = [];
    $totalqty = 0;
    foreach ($data as $row) {
      $totalqty += $row['qty'];
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
    PDF::MultiCell(135, 0,  number_format($totalqty, 2), 'T', 'L', false, 0);
    PDF::MultiCell(555, 0,  'ctns.', '', 'L', false, 1);


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
    $fontsize = 12;
    $font = "Courier";
    $fontbold = "CourierB";

    $totalext = 0;
    foreach ($data as $row) {
      $totalext += $row['ext'];
    }

    PDF::SetY(830);
    PDF::MultiCell(720, 0, '', 'T', 'L', false, 1);

    PDF::SetY(835);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(170, 0, 'VIA : JADES CARGO', '', 'L', false, 0);
    PDF::MultiCell(80, 0, 'WAYBILL# :', '', '', false, 0);
    PDF::MultiCell(240, 0, '', '', '', false, 0);
    PDF::MultiCell(110, 0, 'AMOUNT :', '', 'R', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0,  number_format($totalext, 2), '', 'R', false, 1);

    PDF::SetY(860);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(250, 0, 'Received in good order & condition', '', 'L', false, 0, '', '');
    PDF::MultiCell(440, 0, '', '', 'L', false, 1, '', '');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(250, 0, 'Signature over printed name', 'T', 'C', false, 0, '', '');
    PDF::MultiCell(440, 0, '', '', 'L', false, 1, '', '');

    PDF::MultiCell(0, 0, "\n");
    // Format with AM/PM
    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);
    $formattedDate = $datetime->format('Y/m/d h:i:s a'); //2025-09-25 16:46:32 pm
    PDF::MultiCell(614, 0,  $formattedDate, '', 'L', false, 0, '', '');
    PDF::MultiCell(106, 0,  'Page ' . PDF::PageNo(), '', 'R', false, 1);
  }
}
