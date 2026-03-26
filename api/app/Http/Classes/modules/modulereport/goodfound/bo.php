<?php

namespace App\Http\Classes\modules\modulereport\goodfound;

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

class bo
{

  private $modulename = "Bad Order";
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

  public function createreportfilter()
  {
    $fields = ['radioprint','radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => 'default', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      'default' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($trno)
  {
    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, cust.area, head.due
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, cust.area, head.due
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.doc='so' and head.trno='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

 

  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      default:
        return $this->default_BO_PDF($params, $data);
        break;
    }
      
  }

  public function default_BO_header_PDF($params, $data)
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
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
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

    PDF::SetFont($font, 'B', 14);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    PDF::MultiCell(0, 0, "\n", '');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(720, 25, strtoupper($this->modulename), '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 50, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 50, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 50, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);

    // >>>>>> FIX SET UP TEXT
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(80, 0, "DISTRIBUTOR", '', 'L', false, 0, '50',  '100');
    PDF::MultiCell(80, 0, "TIN : ", '', 'L', false, 0, '50',  '130');
    PDF::MultiCell(80, 0, "SALES ORDER", '', 'L', false, 0, '610',  '105');
    PDF::MultiCell(80, 0, "DATE ISSUED", '', 'L', false, 0, '610',  '155');
    PDF::MultiCell(80, 0, "ADDRESS", '', 'L', false, 1, '50',  '155');
    PDF::MultiCell(200, 0, "AREA OF DISTRUBUTION", '', 'L', false, 1, '50',  '200');
    PDF::MultiCell(80, 0, "DATE EXPIRE", '', 'L', 0, 1, '610',  '200');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '50',  '115', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0, '80',  '130', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '50',  '170', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['area']) ? $data[0]['area'] : ''), '', 'L', false, 0, '50',  '215', true, 0, false, true, 0, 'T', true);

    PDF::MultiCell(0, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '640',  '120', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']),"m/d/Y") : ''), '', 'L', false, 0, '640',  '170', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['due']) ? date_format(date_create($data[0]['due']),"m/d/Y") : ''), '', 'L', false, 0, '640',  '215', true, 0, false, true, 0, 'T', true);

    // VERTICAL LINE
    PDF::MultiCell(0, 148, '', 'L', 'C', 0, 1, '600', '100', true, 0, false, true, 0, 'M', true);

    // END FIX SETUP
    // PDF::MultiCell(0, 0, "\n\n\n\n\n\n", '');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(320, 25, "PRODUCTS ORDERED", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 25, "QUANTITY", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 25, "UNIT PRICE", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 25, "AMOUNT", 'TBLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

  }

  public function default_BO_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }
    $this->default_BO_header_PDF($params, $data);

    $countarr = 0;
    PDF::SetCellPaddings(2, 2, 2, 2);
    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'],2);
      $amt = number_format($data[$i]['netamt'],2);
      $ext = number_format($data[$i]['ext'],2);

      $arr_itemname = $this->reporter->fixcolumn([$itemname],'40',0);
      $arr_qty = $this->reporter->fixcolumn([$qty],'15',0);
      $arr_amt = $this->reporter->fixcolumn([$amt],'15',0);
      $arr_ext = $this->reporter->fixcolumn([$ext],'20',0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_amt, $arr_ext]);

      for($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(320, 20, ' '. (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(120, 20, ' '. (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(120, 20, ' '. (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(160, 20, ' '. (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      }

      $totalext += $data[$i]['ext'];
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(320, 20, ' ', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 20, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 20, ' ', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 20, ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(560, 20, 'TOTAL AMOUNT', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 20, number_format($totalext, $decimalcurr), 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 20, 'The total amount of this SALES ORDER is:', 'TLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 20, $this->reporter->ftNumberToWordsConverter($totalext,  false), 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(720, 50, 
      '<b>Terms & Condition:</b><br>
      1.&nbsp;&nbsp;&nbsp;The withdraw of products will be by installments executed in the form of CEMENT WITHDRAWAL AUTHORIZATION (CWA) confirmed by<br>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DISTRIBUTOR and CEMENT WITHDRAWAL ORDER RECEIPT (CWOR) received by their representative (Hauler) shall automatically<br>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;form a part of this SALES ORDER.<br>
      2.&nbsp;&nbsp;&nbsp;The payment should be made upon confirmation of the SALES ORDER by a dated check<br>
      3.&nbsp;&nbsp;&nbsp;The products are to be picked up by the assigned hauler of the DISTRIBUTOR.<br>
      4.&nbsp;&nbsp;&nbsp;This SALES ORDER is non-transferable and shall not to be used for other transactions.<br>
      ', 'TLRB', 'L', 0, 1, '', '', true, 0, true, true, 1, 'T', true);

    PDF::SetCellPaddings(4, 4, 4, 4);

    PDF::startTransaction();
    PDF::MultiCell(240, 50,'PREPARED BY ', 'TL', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 50,'NOTED BY', 'T', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 50,'APPROVED BY', 'TR', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::MultiCell(240, 20,"MARKETING DEP\'T", 'LB', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20,"FINANCE DEP\'T", 'B', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20,"S.V.P./PRESIDENT", 'RB', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(65, 20, 'BIR Permit No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(100, 20, '0813-ELTRD-CAS-00211', 'B', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(55, 20, 'Date Issued:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(80, 20, (isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']),'F j, Y') : ''), 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    PDF::MultiCell(65, 20, 'Series No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, 'SO4000001-SO4000049', 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    PDF::MultiCell(65, 20, 'Printed Date:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, date_format(date_create($current_timestamp), 'm/d/Y'), 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    PDF::commitTransaction();



    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
