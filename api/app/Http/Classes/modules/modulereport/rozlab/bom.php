<?php

namespace App\Http\Classes\modules\modulereport\rozlab;

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

class bom
{

  private $modulename = "FG BOM";
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
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
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

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
         select  item.barcode as bbarcode, bom.bclientname, item.itemname as bitemname, date(bom.dateid) as dateid, bom.uom2, bom.batchsize, bom.yield, 
         format(ifnull((select sum(isqty * cost)/ifnull(bom.batchsize,0) from component where itemid=item.itemid),0),6) as costuom, c.barcode,
         c.itemname, c.isqty, c.qty, c.uom, c.cost, c.amount, bom.rem, (c.isqty * c.cost) as ext
         from bom 
        left join item on item.itemid = bom.itemid
        left join component as c on c.itemid = bom.itemid
        left join client on client.clientid = bom.bclientid
        where bom.itemid = " . $trno . "        
    ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    return $this->default_BOM_PDF($params, $data);
  }

  public function default_BOM_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    //$width = 800; $height = 1000;

    $qry = "select name, code, address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(650, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    PDF::SetFont($fontbold, '', $fontsize);
    $style = array(
      'border' => false,
      'padding' => 0,
    );


    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(380, 20, (isset($data[0]['bclientname']) ? $data[0]['bclientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Product Name : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(380, 20, $data[0]['bbarcode'] . ' - ' . ((isset($data[0]['bitemname']) || isset($data[0]['uom2'])) ? $data[0]['bitemname'] . ' - ' . $data[0]['uom2'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Batch Size : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 20, (isset($data[0]['batchsize']) ? number_format($data[0]['batchsize'], $decimalprice) : '') . (isset($data[0]['uom2']) ? ' - ' . $data[0]['uom2'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Theoretical Yield : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(380, 20, (isset($data[0]['yield']) ? number_format($data[0]['yield'], $decimalprice) : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Lot No : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 20, (isset($data[0]['lot']) ? $data[0]['lot'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);



    $maxrow = 1;
    $arr_note = $this->reporter->fixcolumn([$data[0]['rem']], '30', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_note]);
    $remh = 10 * $maxrow;

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $remh, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', false);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(380, $remh, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, $remh, "Cost/Uom : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', false);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, $remh, (isset($data[0]['costuom']) ? $data[0]['costuom'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', false);


    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 11);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "COST", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_BOM_PDF($params, $data)
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
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_BOM_header_PDF($params, $data);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = $totalqty =  0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['isqty'], $decimalqty);
      $uom = $data[$i]['uom'];
      $cost = number_format($data[$i]['cost'], $decimalprice);
      $ext = number_format($data[$i]['ext'], $decimalcurr);

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_cost = $this->reporter->fixcolumn([$cost], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_cost, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      $totalext += $data[$i]['ext'];
      $totalqty += $data[$i]['isqty'];

      if (PDF::getY() > 900) {
        $this->default_BOM_header_PDF($params, $data);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, 'No. of Items : ' . count($data), '', 'L', false, 0);
    PDF::MultiCell(75, 0, number_format($totalqty, $decimalqty), '', 'R', false, 0);
    PDF::MultiCell(325, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'GRAND TOTAL  : ', '', 'L', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

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
