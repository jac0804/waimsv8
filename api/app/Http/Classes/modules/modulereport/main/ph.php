<?php

namespace App\Http\Classes\modules\modulereport\main;

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

class ph
{

  private $modulename = "Price Change Slip";
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
    $fields = ['radioprint', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 'PDFM' as print");
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "select head.trno, head.docno, date(head.dateid) as dateid, stock.line, stock.barcode, stock.itemname, stock.uom,
      stock.amt, stock.discr, stock.discws, stock.disca, stock.discb, stock.discc, stock.discd, stock.disce, stock.cashamt,
      stock.cashdisc, stock.wsamt, stock.wsdisc, stock.amt1, stock.disc1, stock.amt2, stock.disc2
      from phhead as head
      left join phstock as stock on stock.trno=head.trno
      where head.trno=" . $trno . "
      union all
      select head.trno, head.docno, date(head.dateid) as dateid, stock.line, stock.barcode, stock.itemname, stock.uom,
      stock.amt, stock.discr, stock.discws, stock.disca, stock.discb, stock.discc, stock.discd, stock.disce, stock.cashamt,
      stock.cashdisc, stock.wsamt, stock.wsdisc, stock.amt1, stock.disc1, stock.amt2, stock.disc2
      from hphhead as head
      left join hphstock as stock on stock.trno=head.trno
      where head.trno=" . $trno;
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    return $this->default_PH_PDF($params, $data);
  }

  public function default_PH_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('l', [1300, 800]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    if ($params['params']['companyid'] != 10 && $params['params']['companyid'] != 12) {
      $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    }
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(920, 0, $this->modulename, '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Doc #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(500, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'Doc Date:', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(150, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1260, 0, '', 'T');

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(95, 0, "ITEM CODE", '', 'C', false, 0);
    PDF::MultiCell(145, 0, "ITEM DESCRIPTION", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "BASE PRICE", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "DISC R", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "DISC WS", '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC A', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC B', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC C', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC D', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC E', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'RETAIL', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'WHOLESALE', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'PRICE 1', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'PRICE 2', '', 'C', false, 0);
    PDF::MultiCell(60, 0, 'DISC', '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1260, 0, '', 'B');
  }

  public function default_PH_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
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
    $this->default_PH_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1260, 0, '', '');

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 0;
      $arr_barcode = $this->reporter->fixcolumn([$data[$i]['barcode']], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '25', 0);
      $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], '10', 0);
      $arr_baseprice = $this->reporter->fixcolumn([number_format($data[$i]['amt'], 2)], '10', 0);
      $arr_discr = $this->reporter->fixcolumn([$data[$i]['discr']], '10', 0);
      $arr_discws = $this->reporter->fixcolumn([$data[$i]['discws']], '10', 0);
      $arr_disca = $this->reporter->fixcolumn([$data[$i]['disca']], '10', 0);
      $arr_discb = $this->reporter->fixcolumn([$data[$i]['discb']], '10', 0);
      $arr_discc = $this->reporter->fixcolumn([$data[$i]['discc']], '10', 0);
      $arr_discd = $this->reporter->fixcolumn([$data[$i]['discd']], '10', 0);
      $arr_disce = $this->reporter->fixcolumn([$data[$i]['disce']], '10', 0);
      $arr_cashamt = $this->reporter->fixcolumn([number_format($data[$i]['cashamt'], 2)], '10', 0);
      $arr_cashdisc = $this->reporter->fixcolumn([$data[$i]['cashdisc']], '10', 0);
      $arr_wsamt = $this->reporter->fixcolumn([number_format($data[$i]['wsamt'], 2)], '10', 0);
      $arr_wsdisc = $this->reporter->fixcolumn([$data[$i]['wsdisc']], '10', 0);
      $arr_amt1 = $this->reporter->fixcolumn([number_format($data[$i]['amt1'], 2)], '10', 0);
      $arr_disc1 = $this->reporter->fixcolumn([$data[$i]['disc1']], '10', 0);
      $arr_amt2 = $this->reporter->fixcolumn([number_format($data[$i]['amt2'], 2)], '10', 0);
      $arr_disc2 = $this->reporter->fixcolumn([$data[$i]['disc2']], '10', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_uom, $arr_baseprice, $arr_discr, $arr_discws, $arr_disca, $arr_discb, $arr_discc, $arr_discd, $arr_disce, $arr_cashamt, $arr_cashdisc, $arr_wsamt, $arr_wsdisc, $arr_amt1, $arr_disc1, $arr_amt2, $arr_disc2]);
      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(95, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(145, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_baseprice[$r]) ? $arr_baseprice[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_discr[$r]) ? $arr_discr[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_discws[$r]) ? $arr_discws[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_disca[$r]) ? $arr_disca[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_discb[$r]) ? $arr_discb[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_discc[$r]) ? $arr_discc[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_discd[$r]) ? $arr_discd[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_disce[$r]) ? $arr_disce[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_cashamt[$r]) ? $arr_cashamt[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_cashdisc[$r]) ? $arr_cashdisc[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_wsamt[$r]) ? $arr_wsamt[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_wsdisc[$r]) ? $arr_wsdisc[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_amt1[$r]) ? $arr_amt1[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_disc1[$r]) ? $arr_disc1[$r] : ''), '', 'C', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_amt2[$r]) ? $arr_amt2[$r] : ''), '', 'R', false, 0);
        PDF::MultiCell(60, 0, (isset($arr_disc2[$r]) ? $arr_disc2[$r] : ''), '', 'C', false);
      }
      // $maxrow = 1;

      // $barcode = $data[$i]['barcode'];
      // $itemname = $data[$i]['itemname'];
      // $qty = number_format($data[$i]['qty'], 2);
      // $uom = $data[$i]['uom'];
      // $amt = number_format($data[$i]['gross'], 2);
      // $disc = $data[$i]['disc'];
      // $ext = number_format($data[$i]['ext'], 2);

      // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      // $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
      // $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      // $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      // $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      // $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
      // $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      // $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

      // for ($r = 0; $r < $maxrow; $r++) {

      //   PDF::SetFont($font, '', $fontsize);
      //   PDF::MultiCell(110, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
      //   PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
      //   PDF::MultiCell(80, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
      //   PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
      //   PDF::MultiCell(80, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
      //   PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      // }

      // $totalext += $data[$i]['ext'];

      // if (PDF::getY() > 900) {
      //   $this->default_PH_header_PDF($params, $data);
      // }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
