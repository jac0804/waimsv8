<?php

namespace App\Http\Classes\modules\modulereport\afti;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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

class rf
{
  private $modulename;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;
  private $table = 'rfhead';
  private $htable = 'hrfhead';
  private $stock = 'rfstock';
  private $hstock = 'hrfstock';
  private $tablenum = 'transnum';

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
    $fields = ['prepared', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'prepared.label', 'Stock Custodian');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
          'PDFM' as print,
          'Calangi, Deanete' as prepared
        "
    );
  }

  public function report_rf_query($trno)
  {

    $qryselect = "select 
       num.center,
       head.trno, 
       client.client,
       head.clientname,
       head.docno,
       head.email,
       head.tel,
       head.reason,
       head.others,
       head.fileby,
       head.cperson,
       concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode) as shipaddress,
       left(head.dateid,10) as dateid, 
       head.complain,
       head.sotrno,
       head.yourref,
       head.recommend,
       item.itemname,
       item.barcode,
       stock.iss,
       stock.amt,
       emp.clientname as empname,
       head.awb,
       head.action,
       head.rfnno,
       head.shipid,
       head.billid,
       head.shipcontactid,
       head.billcontactid,
       left(head.dateclose,10) as dateclose, 
       case stock.refx when 0 then stock.ref else ghead.docno end  as sjdocno,
       left(num.postdate, 10) as postdate,
       concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription, 
       ifnull(group_concat(distinct rr.serial separator '\\n\\r'),stock.serialno) as serialno,
       ifnull(group_concat(attch.title),'')  as attchment,head.ourref,head.invoiceno
     ";

    $qry = $qryselect . " from " . $this->table . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno
    left join " . $this->stock . " as stock on stock.trno = head.trno
    left join item on stock.itemid = item.itemid
    left join client on head.client = client.client
    left join client as emp on head.empid = emp.clientid
    left join hsqhead as sq on sq.trno = head.sotrno
    left join hqshead as qs on qs.sotrno = sq.trno
    left join glstock as gstock on gstock.trno = stock.refx and gstock.line = stock.linex
    left join glhead as ghead on ghead.trno = gstock.trno
    left join billingaddr as s on s.line=head.shipid
    left join billingaddr as b on b.line=head.billid
    left join contactperson as bc on bc.line=head.billcontactid
    left join contactperson as sc on sc.line=head.shipcontactid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.rftrno = stock.trno and rr.rfline = stock.line
    left join transnum_picture as attch on attch.trno = head.trno
    where head.trno = ?
    group by num.center, head.trno, client.client,
    head.clientname, head.docno, head.email, head.tel,
    head.fileby, head.cperson, head.shipaddress, head.dateid, 
    head.complain, head.sotrno, head.yourref, head.recommend,
    item.itemname, item.barcode, stock.iss, stock.amt,
    emp.clientname, head.awb, head.action, head.shipid,
    head.billid, head.shipcontactid, head.billcontactid, head.dateclose, ghead.docno, num.postdate,
    item.brand, mm.model_name, brand.brand_desc, i.itemdescription,head.rfnno,head.reason,
    head.others,s.addrline1,s.addrline2,s.city,s.province,s.country,s.zipcode,stock.ref,stock.refx,head.ourref,head.invoiceno,stock.serialno
    union all 
    " . $qryselect . " from " . $this->htable . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno
    left join " . $this->hstock . " as stock on stock.trno = head.trno
    left join item on stock.itemid = item.itemid
    left join client on head.client = client.client
    left join client as emp on head.empid = emp.clientid
    left join hsqhead as sq on sq.trno = head.sotrno
    left join hqshead as qs on qs.sotrno = sq.trno
    left join glstock as gstock on gstock.trno = stock.refx and gstock.line = stock.linex
    left join glhead as ghead on ghead.trno = gstock.trno
    left join billingaddr as s on s.line=head.shipid
    left join billingaddr as b on b.line=head.billid
    left join contactperson as bc on bc.line=head.billcontactid
    left join contactperson as sc on sc.line=head.shipcontactid
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join serialout as rr on rr.rftrno = stock.trno and rr.rfline = stock.line
    left join transnum_picture as attch on attch.trno = head.trno
    where head.trno = ? 
    group by num.center, head.trno, client.client,
    head.clientname, head.docno, head.email, head.tel,
    head.fileby, head.cperson, shipaddress, head.dateid, 
    head.complain, head.sotrno, head.yourref, head.recommend,
    item.itemname, item.barcode, stock.iss, stock.amt,
    emp.clientname, head.awb, head.action, head.shipid,
    head.billid, head.shipcontactid, head.billcontactid, head.dateclose, ghead.docno, num.postdate,
    item.brand, mm.model_name, brand.brand_desc, i.itemdescription
    ,head.rfnno
    ,head.reason
    ,head.others,s.addrline1,s.addrline2,s.city,s.province,s.country,s.zipcode,stock.ref,stock.refx,head.ourref,head.invoiceno,stock.serialno
    ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($qry, [$trno, $trno])), true);
    return $result;
  } //end fn


  public function reportplottingpdf($params, $data)
  {
    $this->othersClass->setDefaultTimeZone();
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,email,tin from center where code = '" . $center . "'";
    $headerdata = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    //$count = $page = 35;
    // $count = $page = 900;
    $count = $page = 900;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;

    $font = '';

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $fontsize9 = "9";
    $fontsize10 = "9";
    $fontsize11 = "9";
    $fontsize12 = "9";
    $fontsize13 = '10';
    $fontsize14 = "10";
    $border = "1px solid ";

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    PDF::Image('public/images/afti/qslogo.png', '', '', 330, 80);
    PDF::MultiCell(100, 0, '', 0, 'L', 0, 0, '', '', false, 0, false, false, 0);
    PDF::SetFont($font, 'B', $fontsize13);
    PDF::MultiCell(155, 0, 'RFRFORMr2_20200604', 0, 'C', 0, 1, '420', '50', true, 0, false, true, 0, 'M', true);

    // statement of account email
    PDF::SetFont($font, 'B', $fontsize14);
    PDF::MultiCell(0, 10, "\n\n\n");
    PDF::MultiCell(585, 0, ' REQUEST FOR REPLACEMENT/RETURN ', 0, 'C', false);

    PDF::MultiCell(0, 10, "");
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(450, 10, 'RFR No. :', 0, 'R', false, 0);
    PDF::MultiCell(100, 10, (isset($data[0]['rfnno']) ? $data[0]['rfnno'] : ''), 'B', 'L', false);

    PDF::MultiCell(70, 10, 'Filed By :', 0, 'L', false, 0);
    PDF::MultiCell(130, 10, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false, 0);

    PDF::MultiCell(20, 10, '', '', 'L', false, 0);

    PDF::MultiCell(60, 10, 'ERP # :', 0, 'L', false, 0);
    PDF::MultiCell(130, 10, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 0);

    PDF::MultiCell(0, 10, "\n");

    PDF::MultiCell(70, 10, 'Date Posted. :', 0, 'L', false, 0);
    PDF::MultiCell(130, 10, (isset($data[0]['postdate']) ? $data[0]['postdate'] : ''), 'B', 'L', false, 0);

    PDF::MultiCell(20, 10, '', '', 'L', false, 0);

    PDF::MultiCell(60, 10, 'Invoice # :', 0, 'L', false, 0);
    PDF::MultiCell(130, 10, (isset($data[0]['invoiceno']) ? $data[0]['invoiceno'] : ''), 'B', 'L', false, 0);

    PDF::MultiCell(0, 10, "\n");
    PDF::MultiCell(575, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(0, 10, "\n");

    PDF::MultiCell(0, 10, "\n");

    PDF::MultiCell(80, 10, 'Company Name :', 0, 'L', false, 0);
    PDF::MultiCell(250, 10, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false);
    PDF::MultiCell(80, 10, 'Contact Person :', 0, 'L', false, 0);
    PDF::MultiCell(250, 10, (isset($data[0]['cperson']) ? $data[0]['cperson'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(20, 10, '', 0, 'L', false, 0);
    PDF::MultiCell(80, 10, 'Email Address :', 0, 'R', false, 0);
    PDF::MultiCell(140, 10, (isset($data[0]['email']) ? $data[0]['email'] : ''), 'B', 'L', false);
    PDF::MultiCell(80, 10, 'Contact Number  :', 0, 'L', false, 0);
    PDF::MultiCell(250, 10, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false);
    PDF::MultiCell(80, 10, 'Shipping Address :', 0, 'L', false, 0);
    PDF::MultiCell(250, 10, (isset($data[0]['shipaddress']) ? $data[0]['shipaddress'] : ''), 'B', 'L', false);

    // start data
    PDF::MultiCell(0, 10, "\n"); // new line
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100)));
    PDF::MultiCell(575, 0, "", 'B', 'L', false, 1);
    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(211, 211, 211)));

    PDF::MultiCell(100, 15, ' PO # ', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 15, 'Delivery Receipt No. ', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(200, 15, ' Order Code/Description/SN ', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 15, ' QTY ', 'LRB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 15, ' Unit Price ', 'LRB', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    // $peso = TCPDF_FONTS::unichr(8369); //php
    $total = 0;
    $serial = '';
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        if ($data[$i]['serialno'] != '') {
          $serial = 'Serial No.: ' . $data[$i]['serialno'];
        }
        $itemdescription = $data[$i]['itemdescription'] . ' ' . $serial;
        $itemdescription_height = PDF::getStringHeight(200, $itemdescription);

        PDF::SetFont($font, 'R', $fontsize11);
        PDF::MultiCell(100, $itemdescription_height, ' ' . $data[$i]['yourref'], 'LRB', 'L', false, 0);
        PDF::MultiCell(100, $itemdescription_height, ' ' . $data[$i]['sjdocno'], 'LRB', 'L', false, 0);
        PDF::MultiCell(200, $itemdescription_height, $itemdescription, 'RB', 'L', false, 0);
        PDF::MultiCell(75, $itemdescription_height, ' ' . number_format($data[$i]['iss'], 0), 'LRB', 'C', false, 0);
        PDF::MultiCell(100, $itemdescription_height, ' ' . number_format($data[$i]['amt'], $decimalprice), 'LRB', 'R', false);
      }
    }

    PDF::SetLineStyle(array('width' => 1.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100)));

    PDF::MultiCell(0, 15, "\n"); // new line
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(155, 15, 'Reason for Replacement/Return: ', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(410, 15, (isset($data[0]['reason']) ? $data[0]['reason'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(0, 30, "\n"); // new line
    PDF::MultiCell(565, 15, 'Recommendation:', 0, 'L', false);

    $r1 = '';
    $r2 = '';
    $r3 = '';
    $r4 = '';
    if ($data[0]['recommend'] == 'Replacement of Order Code') {
      $r1 = 'checked="checked"';
    } else if ($data[0]['recommend'] == 'Replacement by new stock') {
      $r2 = 'checked="checked"';
    } else if ($data[0]['recommend'] == 'Repair') {
      $r3 = 'checked="checked"';
    } else {
      $r4 = 'checked="checked"';
    }


    $html = '
  <form  action="http://localhost/printvars.php" enctype="multipart/form-data">
  <input type="checkbox" name="agree1" value="1" readonly="true"  ' . $r1 . '/> <label for="agree1">Replacement of Order Code</label><br />
  <input type="checkbox" name="agree2" value="2" readonly="true"  ' . $r2 . '/> <label for="agree2">Replacement by new stock</label><br />
  <input type="checkbox" name="agree3" value="3" readonly="true"  ' . $r3 . '/> <label for="agree3">Repair</label><br />
  <input type="checkbox" name="agree4" value="4" readonly="true"  ' . $r4 . '/> <label for="agree4">Others, please Specify: <u>' . (isset($data[0]['others']) ? $data[0]['others'] : '') . '</u></label><br />
  </form>';

    PDF::writeHTML($html, true, 0, true, 0);
    $prepared = $params['params']['dataparams']['prepared'];
    PDF::SetFont($font, 'B', $fontsize10);
    PDF::SetLineStyle(array('width' => 1.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100)));
    PDF::MultiCell(565, 10, 'Checked/Received Stock by:', 0, 'L', false);
    PDF::MultiCell(0, 10, "\n\n"); // new line
    PDF::MultiCell(200, 10, $prepared, 'B', 'C', false);
    PDF::MultiCell(200, 10, 'Stock Custodian/Date', 'T', 'C', false);
    PDF::MultiCell(0, 10, "\n\n"); // new line
    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(565, 10, $data[0]['attchment'], 'B', 'L', false);
    PDF::SetFont($font, 'B', $fontsize10);
    PDF::MultiCell(565, 10, 'To be filled-up by Stock Custodian: ', '', 'L', false);
    PDF::MultiCell(0, 15, "\n"); // new line

    PDF::SetFont($font, 'B', $fontsize10);
    PDF::MultiCell(80, 15, 'AWB # :', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(250, 15, (isset($data[0]['awb']) ? $data[0]['awb'] : ''), 'B', 'L', false);
    PDF::SetFont($font, 'B', $fontsize10);
    PDF::MultiCell(80, 15, 'Action Taken :', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(485, 15, (isset($data[0]['action']) ? $data[0]['action'] : ''), 'B', 'L', false);
    PDF::MultiCell(565, 15, '', 'B', 'L', false);
    PDF::MultiCell(565, 15, '', 'B', 'L', false);
    PDF::MultiCell(565, 15, '', 'B', 'L', false);
    PDF::MultiCell(0, 15, "\n"); // new line
    PDF::SetFont($font, 'B', $fontsize10);
    PDF::MultiCell(80, 10, 'Date Closed :', 0, 'L', false, 0);
    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(200, 10, (isset($data[0]['dateclose']) ? $data[0]['dateclose'] : ''), 'B', 'L', false);

    // output the HTML content
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
