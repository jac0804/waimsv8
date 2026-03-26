<?php

namespace App\Http\Classes\modules\modulereport\afti;

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
use Illuminate\Support\Facades\URL;
use App\Http\Classes\reportheader;

class po
{
  private $modulename;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    if ($companyid == 12) {
      $fields = ['radioprint', 'print'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'radioprint.options', [
        ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
      ]);
    } else {
      $fields = ['radiopoafti', 'prepared', 'checked', 'noted', 'approved', 'print'];
      $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'noted.type', 'lookup');
      data_set($col1, 'noted.action', 'lookuppreparedby');
      data_set($col1, 'noted.lookupclass', 'noted');
      data_set($col1, 'noted.readonly', true);

      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookuppreparedby');
      data_set($col1, 'prepared.lookupclass', 'prepared');
      data_set($col1, 'prepared.readonly', true);

      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookuppreparedby');
      data_set($col1, 'approved.lookupclass', 'approved');
      data_set($col1, 'approved.readonly', true);

      data_set($col1, 'checked.type', 'lookup');
      data_set($col1, 'checked.action', 'lookuppreparedby');
      data_set($col1, 'checked.lookupclass', 'checked');
      data_set($col1, 'checked.readonly', true);

      data_set($col1, 'radiopoafti.options', [
        ['label' => 'AFTECH PO', 'value' => 'AFTI', 'color' => 'red'],
        ['label' => '2 signatories', 'value' => '2', 'color' => 'red'],
        ['label' => '3 signatories', 'value' => '3', 'color' => 'red'],
        ['label' => '4 signatories', 'value' => '4', 'color' => 'red'],
        ['label' => 'USD Format', 'value' => 'TC', 'color' => 'red']
      ]);
    }

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 12) {
      return $this->coreFunctions->opentable(
        "select
          'PDFM' as print
        "
      );
    } else {
      return $this->coreFunctions->opentable(
        "select
          'PDFM' as print,
          'AFTI' as radiopoafti,
          '' as noted,
          '' as prepared,
          '' as approved,
          '' as checked
       "
      );
    }
  }

  public function report_default_query($trno)
  {
    $query = "select   head.trno,date(head.dateid) as dateid, head.docno, concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as refno,client.client, client.clientname, concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as docno2,
                concat(CONCAT_WS('\r\n', NULLIF(bill.addrline1, ''), NULLIF(bill.addrline2, ''), NULLIF(bill.city, ''), NULLIF(bill.province, ''), NULLIF(bill.country, ''), NULLIF(bill.zipcode, '') ),'\r\n','Phone:',bill.contactno,'\r\n','Fax:',bill.fax) as address,
              head.terms,head.rem, item.barcode,
              item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,
              m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stock.cost as amt,
              head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
              head.deptid,dept.clientname as deptname,
              bill.addr as billaddr,bill.contact as billcontact,bill.contactno as billcontactno,
              ship.addr as shipaddr,ship.contact as shipcontact,ship.contactno as shipcontactno,
              client.tel2, concat(conbill.fname,' ',conbill.mname,' ',conbill.lname) as suppcontact,emp.client as empcode, emp.clientname as empname,
              whreceiver.tel2 as emptel2,stockinfo.rem as inforem,head.tax, head.vattype, brands.brand_desc,
              iteminfo.itemdescription, whreceiver.clientname as whreceivername,head.cur,head.yourref,head.sotrno,bill.fax,conbill.contactno,head.forex,p.name as itemgroup
            from pohead as head
            left join postock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid = stock.itemid
            left join model_masterfile as m on m.model_id = item.model
            left join stockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
            left join client as wh on wh.client=head.wh
            left join client as dept on dept.clientid = head.deptid
            left join client as branch on branch.clientid = head.branch
            left join billingaddr as bill on bill.line = client.billid and bill.clientid = client.clientid
            left join billingaddr as ship on ship.line = client.shipid and ship.clientid = client.clientid
            left join contactperson as conbill on conbill.line=head.billcontactid
            left join client as emp on emp.clientid = head.empid
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
            left join projectmasterfile as p on p.line = item.projectid
            left join client as whreceiver on whreceiver.clientid = head.whreceiver
            left join transnum as num on num.trno=head.trno
            where head.doc='po' and head.trno='$trno' and (stock.void<>1 or stock.qa<>0)
            union all
            select head.trno,date(head.dateid) as dateid, head.docno, concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as refno,client.client, client.clientname,concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as docno2,
               concat(CONCAT_WS('\r\n', NULLIF(bill.addrline1, ''), NULLIF(bill.addrline2, ''), NULLIF(bill.city, ''), NULLIF(bill.province, ''), NULLIF(bill.country, ''), NULLIF(bill.zipcode, '') ),'\r\n','Phone:',bill.contactno,'\r\n','Fax:',bill.fax) as address,
            head.terms,head.rem, item.barcode,
              item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,
              m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stock.cost as amt,
              head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
              head.deptid,dept.clientname as deptname,
              bill.addr as billaddr,bill.contact as billcontact,bill.contactno as billcontactno,
              ship.addr as shipaddr,ship.contact as shipcontact,ship.contactno as shipcontactno,
              client.tel2, concat(conbill.fname,' ',conbill.mname,' ',conbill.lname) as suppcontact,emp.client as empcode, emp.clientname as empname,
              whreceiver.tel2 as emptel2,stockinfo.rem as inforem,head.tax, head.vattype, brands.brand_desc,
              iteminfo.itemdescription, whreceiver.clientname as whreceivername,head.cur,head.yourref,head.sotrno,bill.fax,conbill.contactno,head.forex,p.name as itemgroup
            from hpohead as head
            left join hpostock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid = stock.itemid
            left join model_masterfile as m on m.model_id = item.model
            left join stockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
            left join client as wh on wh.client=head.wh
            left join client as dept on dept.clientid = head.deptid
            left join client as branch on branch.clientid = head.branch
            left join billingaddr as bill on bill.line = client.billid and bill.clientid = client.clientid
            left join billingaddr as ship on ship.line = client.shipid and ship.clientid = client.clientid
            left join contactperson as conbill on conbill.line=head.billcontactid
            left join client as emp on emp.clientid = head.empid
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
            left join projectmasterfile as p on p.line = item.projectid
            left join client as whreceiver on whreceiver.clientid = head.whreceiver
            left join transnum as num on num.trno=head.trno
            where head.doc='po' and head.trno='$trno' and (stock.void<>1 or stock.qa<>0)";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function local_pdfheader($params, $data, $font)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode,'\r\n','TIN: ',tin) as address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\po')->modulename;

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(20, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $prepared = $params['params']['dataparams']['prepared'];
    $approved = $params['params']['dataparams']['approved'];
    $noted = $params['params']['dataparams']['noted'];
    $checked = $params['params']['dataparams']['checked'];

    $fontsize9 = "8.5";
    $fontsize10 = "8.5";
    $fontsize11 = "8.5";
    $fontsize11 = "13";

    switch ($params['params']['dataparams']['radiopoafti']) {
      case '2':
        if ($prepared == "" || $approved == "") {
          PDF::MultiCell(530, 20, "PREPARED BY AND APPROVED BY ARE REQUIRED", '', 'L', false, 0);
          return PDF::Output($this->modulename . '.pdf', 'S');
        }
        break;
      case '3':
        if ($prepared == "" || $noted == "" || $approved == "") {
          PDF::MultiCell(530, 20, "PREPARED BY, APPROVED BY AND NOTED BY ARE REQUIRED", '', 'L', false, 0);
          return PDF::Output($this->modulename . '.pdf', 'S');
        }
        break;
      case '4':
        if ($prepared == "" || $noted == "" || $approved == "" || $checked == "") {
          PDF::MultiCell(530, 20, "PREPARED BY, APPROVED BY, CHECKED BY AND NOTED BY ARE REQUIRED", '', 'L', false, 0);
          return PDF::Output($this->modulename . '.pdf', 'S');
        }
        break;
    }

    $this->reportheader->getHeader($params);
    PDF::SetFont($font, 'b', $fontsize11);
    PDF::MultiCell(0, 40, "");

    if ($params['params']['dataparams']['radiopoafti'] == 'AFTI') {
      PDF::MultiCell(540, 0, 'Purchase Order: ' . '  ' . (isset($data[0]['docno2']) ? $data[0]['docno2'] : ''), '', 'L', 0, 1, '', '', false, 0, false, false, 0);
    } else {
      PDF::MultiCell(540, 0, 'Purchase Order: ' . '  ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', 0, 1, '', '', false, 0, false, false, 0);
    }
    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [155, 155, 155]];
    PDF::SetLineStyle($style);

    PDF::Line(PDF_MARGIN_LEFT, PDF::getY(), PDF::getPageWidth() - PDF_MARGIN_LEFT, PDF::getY());
    PDF::MultiCell(0, 10, "");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(45, 20, "", '', 'R', false, 0);
    PDF::MultiCell(50, 20, "Supplier Name: ", '', 'R', false, 0);
    PDF::MultiCell(5, 20, "", '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 20, 'Date: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['dateid']) ? date("F d, Y", strtotime($data[0]['dateid'])) : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $addh = PDF::GetStringHeight(360, isset($data[0]['address']) ? $data[0]['address'] : '');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(45, $addh, "", '', 'R', false, 0);
    PDF::MultiCell(50, $addh, 'Vendor Address: ', '', 'R', false, 0);
    PDF::MultiCell(5, $addh, "", '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, $addh, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, $addh, 'Ship to: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, $addh, $headerdata[0]->address, '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(100, 20, 'Contact: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['suppcontact']) ? $data[0]['suppcontact'] : ''), '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 20, 'Contact: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['whreceivername']) ? $data[0]['whreceivername'] : ''), '', 'L', false);

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(100, 20, 'Terms: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0);
    PDF::MultiCell(200, 20, 'Phone: (+632) 892-3883', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    $style = ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [155, 155, 155]];
    PDF::SetLineStyle($style);

    PDF::Line(20, PDF::getY(), PDF::getPageWidth() - 20, PDF::getY());

    $style = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [175, 175, 175]];
    PDF::SetLineStyle($style);

    PDF::SetFillColor(211, 211, 211);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false) 

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 20, "Sr", 'LR', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(235, 20, "  " . "Description", 'LR', 'L', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(80, 20, "Quantity" . "  ", 'LR', 'R', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(90, 20, "Rate" . "  ", 'LR', 'R', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, "Amount" . "  ", 'LR', 'R', true, 0, '', '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    $this->addrow('LR', 0);
  }

  public function reportorderplottingpdf($params, $data)
  {
    $prepared = $params['params']['dataparams']['prepared'];
    $approved = $params['params']['dataparams']['approved'];
    $noted = $params['params']['dataparams']['noted'];
    $checked = $params['params']['dataparams']['checked'];

    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $default = $this->companysetup->getdecimal('default', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $vatsales = 0;
    $vat = 0;
    $total = 0;
    $totalext = 0;
    $inum = 0;

    $font = "";
    $fontbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
    }

    $this->local_pdfheader($params, $data, $font);

    switch ($params['params']['dataparams']['radiopoafti']) {
      case '2':
        if ($prepared == "" || $approved == "") {
          return PDF::Output($this->modulename . '.pdf', 'S');
        }
        break;
      case '3':
        if ($prepared == "" || $noted == "" || $approved == "") {
          return PDF::Output($this->modulename . '.pdf', 'S');
        }
        break;
      case '4':
        if ($prepared == "" || $noted == "" || $approved == "" || $checked == "") {
          return PDF::Output($this->modulename . '.pdf', 'S');
        }
        break;
    }
    $fontsize9 = "8.5";
    $arritemname = array();
    $countarr = 0;

    $totalctr = 0;
    $cur = isset($data[0]['cur']) ? $data[0]['cur'] : '';

    $style = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [175, 175, 175]];
    PDF::SetLineStyle($style);

    $style = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [175, 175, 175]];
    PDF::SetLineStyle($style);

    for ($i = 0; $i < count($data); $i++) {

      $ext = number_format($data[$i]['ext'], $decimalcurr);
      if ($ext == 0) {
        $ext = '-';
      }
      $netamt = number_format($data[$i]['netamt'], $decimalcurr);
      if ($netamt == 0) {
        $netamt = '-';
      }


      $barcode = $data[$i]['barcode'];
      $itemdescription = $data[$i]['itemdescription'];
      $inforem = $data[$i]['inforem'];

      if ($inforem != "") {
        $inforem = "\n" . $inforem;
      }

      if ($itemdescription != "") {
        $itemdescription = "\n" . $itemdescription;
      }

      if ($data[$i]['itemname'] != $data[$i]['model']) {
        $items = $data[$i]['itemname'] . ", " . $data[$i]['model'];
      } else {
        $items = $data[$i]['itemname'];
      }
      $itemname =  $items . ", " . $data[$i]['itemgroup'] . ", " . $data[$i]['brand_desc'] . $itemdescription . $inforem;
      $uom = $data[$i]['uom'];
      $qty = round($data[$i]['qty'], 0);

      $itemdesc = $this->reporter->fixcolumn([$itemname], '45', 1);
      $countitemdesc = count($itemdesc);

      $itemqty = (str_split(trim($qty . ' ' . $uom), 12));
      $countqty = count($itemqty);

      $itemamt = (str_split(trim($cur . ' ' . $netamt), 18));
      $countamt = count($itemamt);

      $itemext = (str_split(trim($cur . ' ' . $ext), 18));
      $countext = count($itemext);

      $maxrow = 1;
      $maxrow = max($countitemdesc, $countqty, $countamt, $countext); // get max count

      for ($r = 0; $r < $maxrow; $r++) {
        if ($r == 0) {
          $inum = $i + 1;
        } else {
          $inum = '';
        }
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

        PDF::setCellPaddings(2);
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(50, 10, " " . $inum, 'LR', 'L', false, 0, '', '', false, 0, false, false, 0, 'B');
        PDF::MultiCell(235, 10, isset($itemdesc[$r]) ? ' ' . $itemdesc[$r] : '', 'LR', 'L', false, 0, '', '', false, 0, false, false, 0, 'M');
        // PDF::MultiCell(40, 10, isset($itemuom[$r]) ? ' '.$itemuom[$r] : '', 'L', 'R', false, 0, '', '', false, 1, false, true, 0, 'M');
        PDF::MultiCell(80, 10, isset($itemqty[$r]) ? ' ' . $itemqty[$r] : '', 'LR', 'C', false, 0, '', '', false, 0, false, false, 0, 'M');
        PDF::MultiCell(90, 10, isset($itemamt[$r]) ? ' ' . $itemamt[$r] : '', 'LR', 'R', false, 0, '', '', false, 0, false, false, 0, 'M');
        PDF::SetFont($fontbold, '', $fontsize9);
        PDF::MultiCell(100, 10, isset($itemext[$r]) ? ' ' . $itemext[$r] : '', 'LR', 'R', false, 1, '', '', false, 0, false, false, 0, 'M');
      }
      $this->addrow('LRB', 0);

      if ($data[0]['vattype'] == 'VATABLE') {
        $vatsales = $vatsales + $data[$i]['ext'];
        $total = $total + $data[$i]['ext'];
      } else {
        $vatsales = 0;
        $totalext = $totalext + $data[$i]['ext'];
        $total = $total + $data[$i]['ext'];
      }

      if (intVal($i) + 1 == $page) {
        $this->local_pdfheader($params, $data, $font);
        $page += $count;
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    if ($vattype == 'VATABLE') {
      $vat = $vatsales * .12;
      $totalext = $vatsales + $vat;
    } else {
      $vat = 0;
    }


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(355, 10, 'Total ', '', 'R', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 10, $cur . ' ' . number_format(round($total, $default), $default), '', 'R');

    PDF::SetFont($font, 'b', $fontsize9);
    PDF::MultiCell(355, 10, '12% VAT ', '', 'R', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 10, $cur . ' ' . number_format(round($vat, $default), $default), '', 'R');

    PDF::SetFont($font, 'b', $fontsize9);
    PDF::MultiCell(355, 10, 'Grand Total ', '', 'R', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 10, $cur . ' ' . number_format(round($totalext, $default), $default), '', 'R');

    PDF::SetFont($font, 'b', $fontsize9);
    PDF::MultiCell(355, 10, 'In Words ', '', 'R', false, 0);
    PDF::MultiCell(20, 10, '', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(180, 10, $cur . ' ' . $this->ftNumberToWordsConverter(round($totalext, $default)) . ' ONLY', '', 'L', false, 0);

    PDF::MultiCell(0, 50, "\n\n\n\n");

    switch ($params['params']['dataparams']['radiopoafti']) {
      case '2':
      case 'AFTI':
        $this->signature2($params, $font);
        break;
      case '3':
        $this->signature3($params, $font);
        break;
      case '4':
        $this->signature4($params, $font);
        break;
    }

    $clientname = isset($data[0]['clientname']) ? $data[0]['clientname'] : '';
    if ($clientname != "AFTECH") {
      $this->terms_condition_report($params, $data);
    }


    PDF::MultiCell(760, 0, '', '');
    PDF::MultiCell(0, 0, "\n");

    if (isset($params['params']['fromemail']) && $params['params']['fromemail']) {
      return PDF::Output($this->modulename . '.pdf', 'I');
    } else {
      return PDF::Output($this->modulename . '.pdf', 'S');
    }
  }

  public function terms_condition_report($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\po')->modulename;

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // $prepared = $params['params']['dataparams']['prepared'];
    // $approved = $params['params']['dataparams']['approved'];
    // $noted = $params['params']['dataparams']['noted'];
    // $checked = $params['params']['dataparams']['checked'];

    $fontsize9 = "9";
    PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '70', '70', 200, 50);
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(500, 0, 'ACCESS FRONTIER TECHNOLOGIES INC.', '', 'C', 0, 1);
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(100, 0, '', '', 'C', 0, 0);
    PDF::MultiCell(300, 0, 'PURCHASE ORDER (P.O.) TERMS AND CONDITIONS', 'B', 'C', 0, 0);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize9);

    $poterms = $this->coreFunctions->opentable("select poterms from poterms");

    if (!empty($poterms)) {
      $no1 = $poterms[0]->poterms;
      PDF::writeHTML($no1, true, false, true, false, '');
    }

    PDF::MultiCell(0, 7, "\n\n\n");

    PDF::MultiCell(760, 0, '', '');
  }

  public function terms_condition_report_fixlayout($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\po')->modulename;

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

    // $prepared = $params['params']['dataparams']['prepared'];
    // $approved = $params['params']['dataparams']['approved'];
    // $noted = $params['params']['dataparams']['noted'];
    // $checked = $params['params']['dataparams']['checked'];

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '70', '70', 330, 80);
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 19);
    PDF::MultiCell(700, 0, 'ACCESS FRONTIER TECHNOLOGIES INC.', '', 'C', 0, 1);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(200, 0, '', '', 'C', 0, 0);
    PDF::MultiCell(300, 0, 'PURCHASE ORDER (P.O.) TERMS AND CONDITIONS', 'B', 'C', 0, 0);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 12);

    $no1 = '<p style = "text-align: justify;"><b>1.</b> This Purchase Order ("PO") shall serve as the contract between the <b>VENDOR</b> and <b>AFTI</b> for the purchase of goods and services covered by this PO.</p>';
    PDF::writeHTML($no1, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no2 = '<p style = "text-align: justify;"><b>2.</b> <b>VENDOR</b> shall sign this Purchase Order (PO) within 48 hours from receipt.
  Upon signing of this PO by the <b>VENDOR</b>, all prices and terms/conditions indicated herein are deemed to be accepted and no changes shall be made without the
  written consent and approval of <b>AFTI</b>. <b>AFTI</b> shall have the right to amend or cancel any items on the PO if <b>VENDOR</b> fails to acknowledge the P.O within 48 Hours and
  <b>VENDOR</b> shall not penalize or charge <b>AFTI</b> any amount for such act. As such, this PO is valid and binding between <b>VENDOR</b> and <b>AFTI</b>.</p>';
    PDF::writeHTML($no2, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no3 = '<p style = "text-align: justify;"><b>3.</b> <b>AFTI</b> reserves the right to cancel this PO at any time if <b>VENDOR</b> fails to
  deliver all or any part of the Goods/Services in accordance with the terms of the PO. Acceptance of any or part of the PO shall not bind <b>AFTI</b> to accept future
  nonconforming deliveries.</p>';
    PDF::writeHTML($no3, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no4 = '<p style = "text-align: justify;"><b>4.</b> <b>AFTI</b> shall pay the <b>VENDOR</b> within thirty (30) calendar days or as per
  approved payment terms indicated in the PO. Day one (1) of the calendar days will start upon the receipt of the corresponding invoice and the appropriate
  delivery receipt and other <b>AFTI</b> required supporting documents</p>';
    PDF::writeHTML($no4, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no5 = '<p style = "text-align: justify;"><b>5.</b> All deliveries of goods should be accompanied by a PO duly executed by an
  authorized representative of the <b>AFTI</b>. The <b>AFTI</b> shall not accept any good/service which are not indicated in the PO. The PO number must be reflected clearly and
  legibly in all the copies of the invoices and delivery receipts to be delivered to the <b>AFTI</b>. The A<b>F</b>TI reserves the right to reject any delivery that does not comply with
  the stated requirements.</p>';
    PDF::writeHTML($no5, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no6 = '<p style = "text-align: justify;"><b>6.</b> In the event of non compliance of <b>VENDOR</b> on the provision of this PO
  Terms and Condition, any advance payment made by <b>AFTI</b> shall be refunded
  immediately by the <b>VENDOR</b> without prior demand.</p>';
    PDF::writeHTML($no6, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no7 = "<p style = 'text-align: justify;'><b>7.</b> Time is of the essence in the performance by the <b>VENDOR</b> of its obligations
  hereunder. <b>VENDOR</b> warrants that it will deliver the Goods/Services in accordance with the agreed delivery dates with AFTI. In the event the <b>VENDOR</b>
  should fail, for any reason whatsoever other than force majeure, or other than AFTI's fault or negligence, to make a delivery within the specified delivery
  schedule as provided in the PO confirmed and agreed upon by AFTI and the <b>VENDOR</b>, the <b>VENDOR</b> will be liable to pay AFTI liquidated damages at 1% of the
  value of the whole project PO for every day of delay until the Goods/Services have been delivered (reckoned from the date which the PO is acknowledged)
  Provided however, that AFTI may, at its sole option, purchase from any other <b>VENDOR</b>(s) such quantity and quality of the product as should have been
  delivered by the <b>VENDOR</b>, and charge the <b>VENDOR</b> with the difference between the selling price of the product purchased from the other <b>VENDOR</b>(s) and the
  selling price of the product under this PO, if the selling price of the product purchased from the other <b>VENDOR</b>(s) is greater than the selling price of the
  product as stated in this PO. It isunderstood that in the event that the selling price of the product purchased from the other <b>VENDOR</b>(s) is less than the selling price
  of the product as stated in this PO, <b>VENDOR</b> shall not be entitled to any reimbursement of the excess. The <b>VENDOR</b> shall reimburse to AFTI such
  difference between the selling price of the product under this PO and the purchase price of the same product from the other <b>VENDOR</b>(s) within seven (7)
  working days from receipt of the AFTI's notification and invoices relative to the purchase made with other <b>VENDOR</b>(s).</p>";
    PDF::writeHTML($no7, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no8 = "<p style = 'text-align: justify;'><b>8.</b> The goods/services delivered shall be subject to inspection and acceptance by end user or any of its authorized representative/s. <b>AFTI</b> shall not pay any
  invoice or bill for goods/services delivered unless the goods have been accepted by the end-user and proof of such acceptance by its authorized representative is
  properly presented. Goods rejected on account of inferior quality or workmanship, breakage, shortage, and/or substitution not in accordance with the
  specifications of this PO shall be returned to the <b>VENDOR</b> subject to the provisions of clauses 9 and 10 below. The transportation, hauling and other
  expenses incurred by the <b>AFTI</b> in this connection shall be for the account of the <b>VENDOR</b>.</p>";
    PDF::writeHTML($no8, true, false, true, false, '');

    PDF::AddPage('p', [800, 1000]);

    $no9 = "<p style = 'text-align: justify;'><b>9.</b> <b>VENDOR</b> unconditionally guarantees that the Goods/Services supplied/delivered shall:
  a) be of new manufacture and not second hand, reconditioned or used; b) be of the highest quality, fit for the purpose for which they are intended
  and in accordance with <b>AFTI</b> specifications; c) free from any liens, claims, encumbrances, security interests, charges,
  taxes and assessments whatsoever; and d) free from hidden defects, disabling codes, spywares or viruses.</p>";
    PDF::writeHTML($no9, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no10 = "<p style = 'text-align: justify;'><b>10.</b> <b>VENDOR</b> ensures that the Goods/Services are free from any defect in
  design, materials, workmanship, including wrong delivery. In this connection, the <b>VENDOR</b> shall replace the Goods/Services at no additional cost to <b>AFTI</b>.</p>";
    PDF::writeHTML($no10, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no11 = "<p style = 'text-align: justify;'><b>11.</b> This purchase will have a Warranty Period of months from the date
  indicated in the delivery receipt or date of delivery, whichever is later, based on the Warranties stated herein and over and above any Manufacturer's warranty,
  when applicable.</p>";
    PDF::writeHTML($no11, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no12 = "<p style = 'text-align: justify;'><b>12.</b> No other form of acceptance is binding on <b>AFTI</b>. <b>AFTI</b> expressly limits
  acceptance to the terms and condition stated in this PO and any additional or different terms proposed by the <b>VENDOR</b> shall not be binding on <b>AFTI</b> whether or
  not they will materially alter this order and are rejected. In the event of any conflict or inconsistency between this PO and the terms and conditions of
  <b>VENDOR</b> supplied documents attached, referred or delivered with the Goods/Services and for the purposes of interpretation, these <b>PO TERMS AND
  CONDITIONS</b> shall prevail.</p>";
    PDF::writeHTML($no12, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no13 = "<p style = 'text-align: justify;'><b>13.</b> Any dispute arising from the execution of or in connection with this
  Agreement shall be brought before the proper courts of Makati City, Metro Manila, to the exclusion of all other courts.</p>";
    PDF::writeHTML($no13, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");

    $no14 = "<p style = 'text-align: justify;'><b>14.</b> <b>VENDOR</b> hereby acknowledged and signed these PO Terms and Condition
  this _______ day of _________________ (month), ___________ (year)</p>";
    PDF::writeHTML($no14, true, false, true, false, '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 0, '', 'B', 'L', false, 1);
    PDF::MultiCell(250, 0, 'Signature over Printed Name/Date', '', 'L', false, 0);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 0, '', 'B', 'L', false, 1);
    PDF::MultiCell(250, 0, 'Designation', '', 'L', false, 0);

    PDF::MultiCell(0, 7, "\n\n\n");

    PDF::MultiCell(760, 0, '', '');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function signature2($params, $font)
  {

    if (PDF::getY() > 760) {
      PDF::AddPage('p', [595, 842]);
    }

    $fontsize9 = "9";
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Prepared By', '', '', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Approved by', '', 'L', false, 0);
    PDF::MultiCell(20, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(200, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(20, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'Procurement', '', 'C', false, 0);
    PDF::MultiCell(200, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'O.P.L.P Head', '', 'C', false, 0);
    PDF::MultiCell(20, 50, '', '', 'R');
  }

  public function signature3($params, $font)
  {
    if (PDF::getY() > 675) {
      PDF::AddPage('p', [595, 842]);
    }

    $fontsize9 = "9";
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Prepared By', '', 'C', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Approved by', '', 'C', false, 0);
    PDF::MultiCell(20, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(200, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(20, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'Procurement', '', 'C', false, 0);
    PDF::MultiCell(200, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'Management Accountant', '', 'C', false, 0);
    PDF::MultiCell(20, 50, '', '', 'R');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(170, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Noted By', '', 'C', false, 0);
    PDF::MultiCell(170, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(170, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['noted'], 'B', 'C', false, 0);
    PDF::MultiCell(170, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(170, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'O.P.L.P. Head', '', 'C', false, 0);
    PDF::MultiCell(170, 50, '', '', 'R');
  }

  public function signature4($params, $font)
  {
    if (PDF::getY() > 675) {
      PDF::AddPage('p', [595, 842]);
    }

    $fontsize9 = "9";
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Prepared By', '', 'C', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Checked By', '', 'C', false, 0);
    PDF::MultiCell(20, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(200, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['checked'], 'B', 'C', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(20, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'Procurement', '', 'C', false, 0);
    PDF::MultiCell(200, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'O.P.L.P Head', '', 'C', false, 0);
    PDF::MultiCell(20, 50, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Noted by', '', 'C', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Approved by', '', 'C', false, 0);
    PDF::MultiCell(20, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['noted'], 'B', 'C', false, 0);
    PDF::MultiCell(200, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(20, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'Management Accountant', '', 'C', false, 0);
    PDF::MultiCell(200, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'V.P. Director', '', 'C', false, 0);
    PDF::MultiCell(20, 50, '', '', 'R');
  }

  public function ftNumberToWordsConverter($number)
  {
    $numberwords = $this->ftNumberToWordsBuilder($number);

    if (strpos($numberwords, "/") == false) {
      $numberwords .= "  ";
    } else {
      $numberwords = str_replace(" AND ", "  AND ", $numberwords);
    } //end if

    return $numberwords;
  } //end function convert to words

  public function ftNumberToWordsBuilder($number)
  {
    if ($number == 0) {
      return 'Zero';
    } else {
      $hyphen      = ' ';
      $conjunction = ' ';
      $separator   = ' ';
      $negative    = 'negative ';
      $decimal     = ' and ';
      $dictionary  = array(
        0                   => '',
        1                   => 'One',
        2                   => 'Two',
        3                   => 'Three',
        4                   => 'Four',
        5                   => 'Five',
        6                   => 'Six',
        7                   => 'Seven',
        8                   => 'Eight',
        9                   => 'Nine',
        10                  => 'Ten',
        11                  => 'Eleven',
        12                  => 'Twelve',
        13                  => 'Thirteen',
        14                  => 'Fourteen',
        15                  => 'Fifteen',
        16                  => 'Sixteen',
        17                  => 'Seventeen',
        18                  => 'Eighteen',
        19                  => 'Nineteen',
        20                  => 'Twenty',
        30                  => 'Thirty',
        40                  => 'Forty',
        50                  => 'Fifty',
        60                  => 'Sixty',
        70                  => 'Seventy',
        80                  => 'Eighty',
        90                  => 'Ninety',
        100                 => 'Hundred',
        1000                => 'Thousand',
        1000000             => 'Million',
        1000000000          => 'Billion',
        1000000000000       => 'Trillion',
        1000000000000000    => 'Quadrillion',
        1000000000000000000 => 'Quintillion',
        '01' => 'One',
        '02' => 'Two',
        '03' => 'Three',
        '04' => 'Four',
        '05' => 'Five',
        '06' => 'Six',
        '07' => 'Seven',
        '08' => 'Eight',
        '09' => 'Nine'
      );

      if (!is_numeric($number)) {
        return false;
      } //end if

      if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        return false;
      } //end if

      if ($number < 0) {
        return $negative . $this->ftNumberToWordsBuilder(abs($number));
      } //end if

      $string = $fraction = null;

      if (strpos($number, '.') !== false) {
        $fractionvalues = explode('.', $number);
        if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
          list($number, $fraction) = explode('.', $number);
        } //end if
      } //end if
      switch (true) {
        case $number < 21:
          $string = $dictionary[$number];
          break;

        case $number < 100:
          $tens   = ((int) ($number / 10)) * 10;
          $units  = $number % 10;
          $string = $dictionary[$tens];
          if ($units) {
            $string .= $hyphen . $dictionary[$units];
          } //end if
          break;

        case $number < 1000:
          $hundreds  = $number / 100;
          $remainder = $number % 100;
          $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
          if ($remainder) {
            $string .= $conjunction . $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;

        default:
          $baseUnit = pow(1000, floor(log($number, 1000)));
          $numBaseUnits = (int) ($number / $baseUnit);
          $remainder = $number % $baseUnit;
          $string = $this->ftNumberToWordsBuilder($numBaseUnits) . ' ' . $dictionary[$baseUnit];
          if ($remainder) {
            $string .= $remainder < 100 ? $conjunction : $separator;
            $string .= $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;
      } //end switch
      if (null !== $fraction && is_numeric($fraction)) {

        $cent = $this->ftNumberToWordsBuilder($fraction);
        $string .= $decimal . ' ' . $cent . ' CENTS';
      } //end if

      return strtoupper($string);
    } //end
  } //end fn

  public function reportplotting($config, $data)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 12) {
      $str = $this->reportgenplottingpdf($config, $data);
    } else {
      $optionspo = $config['params']['dataparams']['radiopoafti'];
      switch ($optionspo) {
        case '2':
        case '3':
        case '4':
        case 'AFTI':
          $str = $this->reportorderplottingpdf($config, $data);
          break;
        default:
          $str = $this->reportgenplottingpdf($config, $data);
          break;
      }
    }

    return $str;
  }

  public function reportgenplottingpdf($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = '';
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\po')->modulename;

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);

    $fontsize8 = 8;
    $fontsize9 = 9;
    $fontsize10 = 10;

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('l', [595, 842]);
    PDF::SetMargins(10, 10);


    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', $fontsize9);
    // PDF::MultiCell(0, 0, $username.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s'), '', 'L');

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(0, 20, strtoupper($headerdata[0]->name), '', 'C');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 20, "Report Detail Imported PO", '', 'C');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(68, 30, " " . "AFTi Ref No.", 'TLBR', 'L', false, 0);
    PDF::MultiCell(95, 30, " " . "Cust Name", 'TLBR', 'L', false, 0);
    PDF::MultiCell(85, 30, " " . "Cust PO#", 'TLBR', 'L', false, 0);
    PDF::MultiCell(70, 30, " " . "PO Cust Date", 'TLBR', 'R', false, 0);
    PDF::MultiCell(180, 30, " " . "Product Description", 'TLBR', 'L', false, 0);
    PDF::MultiCell(50, 30, " " . "PO Cust Qty", 'TLBR', 'L', false, 0);
    PDF::MultiCell(32, 30, " " . "B/O", 'TLBR', 'L', false, 0);
    PDF::MultiCell(80, 30, " " . "Status & DO# & INV#", 'TLBR', 'L', false, 0);
    PDF::MultiCell(80, 30, " " . "Transfer Price", 'TLBR', 'L', false, 0);
    PDF::MultiCell(80, 30, " " . "Total", 'TLBR', 'L');

    for ($i = 0; $i < count($data); $i++) {

      $refno = $data[$i]['refno'];
      if ($companyid == 12) {
        $ext =  number_format($data[$i]['ext'], $decimalcurr);
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        if ($data[$i]['disc'] != '') {
          $netamt = number_format($this->othersClass->Discount($data[$i]['netamt'], $data[$i]['disc']), $decimalcurr);
        }
      } else {
        $ext =  number_format($data[$i]['qty'] * $data[$i]['amt'], $decimalcurr);
        $netamt = number_format($data[$i]['amt'], $decimalcurr);
      }
      $ext = $ext < 0 ? '-' : $ext;
      $netamt = $netamt < 0 ? '-' : $netamt;
      $cur = $data[0]['cur'];
      $clientname = $this->getclient($data[$i]['sotrno'], $params);

      $yourref = $data[$i]['yourref'];
      $dateid = date("d-M-Y", strtotime($data[0]['dateid']));
      $itemname = $data[$i]['itemname'];
      $itemdescription = $data[$i]['itemdescription'];
      $model = $data[$i]['model'];
      $itemgroup = $data[$i]['itemgroup'];
      $brand = $data[$i]['brand_desc'];
      $qty = number_format($data[$i]['qty'], 0) . "  " . $data[$i]['uom'];
      $bo = '0';

      if ($data[$i]['model'] == $data[$i]['itemname']) {
        $itemname =  $data[$i]['itemname'];
      } else {
        $itemname =  $data[$i]['itemname'] . ' ' . $data[$i]['model'];;
      }

      $arrrefno = $this->reporter->fixcolumn([$refno], '12', 1);
      $crefno = count($arrrefno);

      $arrclientname = $this->reporter->fixcolumn([$clientname], '15', 1);
      $cclientname = count($arrclientname);

      $arrdocno = $this->reporter->fixcolumn([$yourref], '12', 1);
      $cdocno = count($arrdocno);

      $arrdateid = (str_split(trim($dateid), 12));
      $cdateid = count($arrdateid);

      $arritemname = $this->reporter->fixcolumn([$itemname, $itemgroup, $brand, $itemdescription], '30', 0);
      $citemname = count($arritemname);

      $arrbo = (str_split(trim($bo) . ' ', 2));
      $cbo = count($arrbo);

      $arrqty = (str_split(trim($qty) . ' ', 11));
      $cqty = count($arrqty);

      $arrnetamt = (str_split(trim($cur . ' ' . $netamt), 16));
      $cnetamt = count($arrnetamt);

      $arrext = (str_split(trim($cur . ' ' . $ext), 14));
      $cext = count($arrext);

      $maxrow = 1;
      $maxrow = max($crefno, $cclientname, $cdocno, $cdateid, $citemname, $cqty, $cnetamt, $cext); // get max count

      for ($r = 0; $r < $maxrow; $r++) {
        if ($r == 0) {
          $inum = $i + 1;
        } else {
          $inum = '';
        }
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(68, 10, isset($arrrefno[$r]) ? ' ' . $arrrefno[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(95, 10, isset($arrclientname[$r]) ? ' ' . $arrclientname[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(85, 10, isset($arrdocno[$r]) ? ' ' . $arrdocno[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(70, 10, isset($arrdateid[$r]) ? ' ' . $arrdateid[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(180, 10, isset($arritemname[$r]) ? ' ' . $arritemname[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(50, 10, isset($arrqty[$r]) ? ' ' . $arrqty[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(32, 10, isset($arrbo[$r]) ? ' ' . $arrbo[$r] : '', 'LR', 'C', false, 0);
        PDF::MultiCell(80, 10, "", 'LR', 'L', false, 0);
        PDF::MultiCell(80, 10, isset($arrnetamt[$r]) ? ' ' . $arrnetamt[$r] : '', 'LR', 'R', false, 0);
        PDF::MultiCell(80, 10, isset($arrext[$r]) ? ' ' . $arrext[$r] : '', 'LR', 'R');
      }
      $this->addrowusd('LRB');
      $totalext = $totalext + $data[$i]['ext'];
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrow($border, $height = 0)
  {
    PDF::MultiCell(50, $height, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(235, $height, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(80, $height, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(90, $height, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, $height, '', $border, 'R', false, 1, '', '', false, 0);
  }

  private function addrowusd($border)
  {
    PDF::MultiCell(68, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(95, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(85, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(70, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(180, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(32, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(80, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(80, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(80, 0, '', $border, 'R', false, 1, '', '', false, 0);
  }


  private function getclient($trno, $params)
  {
    $center = $params['params']['center'];

    $query = "select client.clientname as value from sqhead as head
    left join hqshead as qt on qt.sotrno=head.trno
    left join client on qt.client = client.client
    left join transnum as num on num.trno = head.trno
    where head.trno = ? and num.center = ? 
    union all 
    select client.clientname as value from hsqhead as head
    left join hqshead as qt on qt.sotrno=head.trno
    left join client on qt.client = client.client
    left join transnum as num on num.trno = head.trno
    where head.trno = ? and num.center = ? 
    union all 
    select client.clientname as value from sshead as head
    left join hqshead as qt on qt.sotrno=head.trno
    left join client on qt.client = client.client
    left join transnum as num on num.trno = head.trno
    where head.trno = ? and num.center = ? 
    union all 
    select client.clientname as value from hsshead as head
    left join hqshead as qt on qt.sotrno=head.trno
    left join client on qt.client = client.client
    left join transnum as num on num.trno = head.trno
    where head.trno = ? and num.center = ? 
    ";

    return $this->coreFunctions->datareader($query, [$trno, $center, $trno, $center, $trno, $center, $trno, $center]);
  }
}
