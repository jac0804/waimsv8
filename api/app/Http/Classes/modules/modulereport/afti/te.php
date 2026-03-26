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

class te
{
  private $modulename = "TASK/ERRAND MODULE";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

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
    $fields = ['print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received"
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $errandtype = $this->get_errandtype($config);

    switch ($errandtype) {
      case 'ERRAND':
        $query = $this->pick_unit_query($trno);
        break;
      case 'PPIO':
        $query = $this->query_ppio_query($trno);
        break;
    }

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function pick_unit_query($trno)
  {
    $query = "
      select date_format(head.datereq, '%Y-%m-%d %I:%i %p') as datereq, head.company, head.companyaddress, 
      head.contactperson, head.contact, head.assignid, head.rem,
      cl.client, cl.clientname
      from tehead as head
      left join client as cl on cl.clientid = head.clientid
      where head.trno = '" . $trno . "'
      union all
      select date_format(head.datereq, '%Y-%m-%d %I:%i %p') as datereq, head.company, head.companyaddress, 
      head.contactperson, head.contact, head.assignid, head.rem,
      cl.client, cl.clientname
      from htehead as head
      left join client as cl on cl.clientid = head.clientid
      where head.trno = '" . $trno . "'";

    return $query;
  } //end fn

  public function query_ppio_query($trno)
  {
    $query = "
    select date(head.datereq) as datereq, date(head.dateneed) as dateneed, date(head.due) as returndate, 
    head.company, head.companyaddress,
    head.contactperson, head.contact, head.assignid, head.rem,
    cl.client, cl.clientname,
    stock.itemname, stock.brand, stock.model, 
    stock.serialno, stock.qty, stock.uom,
    ppio.docno as ppio
    from tehead as head
    left join testock as stock on stock.trno = head.trno
    left join client as cl on cl.clientid = head.clientid
    left join ppio_series as ppio on ppio.trno = head.trno
    where head.trno = '" . $trno . "'
    union all
    select date(head.datereq) as datereq, date(head.dateneed) as dateneed, date(head.due) as returndate, 
    head.company, head.companyaddress,
    head.contactperson, head.contact, head.assignid, head.rem,
    cl.client, cl.clientname,
    stock.itemname, stock.brand, stock.model, 
    stock.serialno, stock.qty, stock.uom,
    ppio.docno as ppio
    from htehead as head
    left join htestock as stock on stock.trno = head.trno
    left join client as cl on cl.clientid = head.clientid
    left join ppio_series as ppio on ppio.trno = head.trno
    where head.trno = '" . $trno . "'
  ";

    return $query;
  } //end fn

  public function reportplotting($params, $data)
  {
    $errandtype = $this->get_errandtype($params);
    switch ($errandtype) {
      case 'ERRAND':
        return $this->pick_unit_PDF($params, $data);
        break;
      case 'PPIO':
        return $this->ppio_PDF($params, $data);
        break;
    }
  }

  public function get_errandtype($params)
  {
    $trno = $params['params']['dataid'];
    $qry = "
    select errandtype as value from tehead where trno = ?
    union all 
    select errandtype as value from htehead where trno = ? 
    LIMIT 1";

    $errandtype = $this->coreFunctions->datareader($qry, [$trno, $trno]);

    return $errandtype;
  }

  public function pick_unit_pdfheader($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(535, 0, "PLEASE WRITE LEGIBLY", '', 'C', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 0, "", '', 'C', false, 0, '',  '');
    PDF::MultiCell(335, 0, "Note: if there is deadline/timeline; earliest/lastest date of delivery or submission; always request for a flexible time; file your request atleast 2 days before required date", '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(200, 0, $data[0]['datereq'], '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(80, 10, "", 'TL', 'C', false, 0);
    PDF::MultiCell(80, 10, "", 'TL', 'C', false, 0);
    PDF::MultiCell(120, 10, "CONTACT", 'TLR', 'C', false, 0);
    PDF::MultiCell(120, 10, "FILE DATE", 'TLR', 'C', false, 0);
    PDF::MultiCell(135, 10, "", 'TLR', 'C', false, 0);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(80, 30, "COMPANY / CLIENT", 'L', 'C', false, 0);
    PDF::MultiCell(80, 30, "COMPLETE ADDRESS", 'LR', 'C', false, 0);
    PDF::MultiCell(60, 30, "PERSON", 'TLR', 'C', false, 0);
    PDF::MultiCell(60, 30, "NUMBER", 'TLR', 'C', false, 0);
    PDF::MultiCell(60, 30, "DATE", 'TLR', 'C', false, 0);
    PDF::MultiCell(60, 30, "BY", 'TLR', 'C', false, 0);
    PDF::MultiCell(135, 30, "REMARKS/ INSTRUCTION", 'TLR', 'C', false, 0);

    PDF::MultiCell(535, 0, '', '');
  }

  public function pick_unit_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 10;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->pick_unit_pdfheader($params, $data);

    PDF::MultiCell(0, 0, "\n");
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        PDF::SetFont($font, '', 9);
        $company = $data[$i]['company'];
        $companyaddress = $data[$i]['companyaddress'];
        $contactperson = $data[$i]['contactperson'];
        $contact = $data[$i]['contact'];
        $datereq = date('M d, Y', strtotime($data[$i]['datereq']));
        $clientname = $data[$i]['clientname'];
        $rem = $data[$i]['rem'];

        $company_height = PDF::GetStringHeight(100, $company);
        $companyaddress_height = PDF::GetStringHeight(100, $companyaddress);
        $contactperson_height = PDF::GetStringHeight(100, $contactperson);
        $contact_height = PDF::GetStringHeight(100, $contact);
        $datereq_height = PDF::GetStringHeight(100, $datereq);
        $clientname_height = PDF::GetStringHeight(100, $clientname);
        $rem_height = PDF::GetStringHeight(100, $rem);

        $max_height = max(
          $company_height,
          $companyaddress_height,
          $contactperson_height,
          $contact_height,
          $datereq_height,
          $clientname_height,
          $rem_height
        );

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
        PDF::MultiCell(80, 700, $company, 'LRTB', 'C', false, 0, '', '');
        PDF::MultiCell(80, 700, $companyaddress, 'LRTB', 'C', false, 0, '', '');
        PDF::MultiCell(60, 700, $contactperson, 'LRTB', 'C', false, 0, '', '');
        PDF::MultiCell(60, 700, $contact, 'LRTB', 'C', false, 0, '', '');
        PDF::MultiCell(60, 700, $datereq, 'LRTB', 'C', '', '');
        PDF::MultiCell(60, 700, $clientname, 'LRTB', 'C', false, 0, '', '');
        PDF::MultiCell(135, 700, $rem, 'LRTB', 'L', false, 0, '', '');

        if (intVal($i) + 1 == $page) {
          $this->pick_unit_pdfheader($params, $data);
          $page += $count;
        }
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function ppio_pdfheader($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(535, 0, "PROPERTY PASS IN/OUT FORM (PPIO)", '', 'C', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(535, 0, "Page ___1__of___1__", '', 'L', false, 0, '600',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(150, 0, 'A C C E S S F R O N T I E R', '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(255, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(100, 0, 'PPIO Series Number', '', 'L', false, 0, '',  '');
    PDF::MultiCell(130, 0, $data[0]['ppio'], 'B', 'C', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(255, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(100, 0, 'Date Filed', '', 'L', false, 0, '',  '');
    PDF::MultiCell(130, 0, $data[0]['datereq'], 'B', 'C', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(255, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(100, 0, 'Date Needed', '', 'L', false, 0, '',  '');
    PDF::MultiCell(130, 0, $data[0]['dateneed'], 'B', 'C', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(255, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(100, 0, 'Date of Return', '', 'L', false, 0, '',  '');
    PDF::MultiCell(130, 0, $data[0]['returndate'], 'B', 'C', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(75, 0, "Property OUT", '', 'L', false, 0, '',  '60');
    $checkbox1 = '<input type="checkbox" name="prop" value="1" checked="checked">';

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(75, 0, "Property IN", '', 'L', false, 0, '',  '80');
    $checkbox1 = '<input type="checkbox" name="prop" value="0">';

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(130, 0, 'Prepared By: ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(200, 0, $data[0]['clientname'], '', 'L', false, 1, '',  '');

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(130, 0, 'Contact Person: ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(200, 0, $data[0]['contactperson'], '', 'L', false, 1, '',  '');

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(130, 0, 'Company & Address: ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(200, 0, $data[0]['company'] . ' - ' . $data[0]['companyaddress'], '', 'L', false, 1, '',  '');

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(130, 0, 'Remarks / Reason / Purpose: ', '', 'L', false, 0, '',  '');
    PDF::MultiCell(395, 0, $data[0]['rem'], '', 'L', false, 1, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(90, 10, "", 'TL', 'C', false, 0);
    PDF::MultiCell(90, 10, "", 'TL', 'C', false, 0);
    PDF::MultiCell(355, 10, "ITEM DESCRIPTION", 'TLR', 'C', false, 0);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', 10);
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    PDF::MultiCell(90, 10, "QTY", 'BL', 'C', false, 0);
    PDF::MultiCell(90, 10, "U/M", 'BLR', 'C', false, 0);
    PDF::MultiCell(115, 10, "Brand", 'BTL', 'C', false, 0);
    PDF::MultiCell(115, 10, "Model", 'BTLR', 'C', false, 0);
    PDF::MultiCell(125, 10, "Serial No", 'BTLR', 'C', false, 0);

    PDF::MultiCell(700, 0, '', '');
  }
  public function ppio_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 780;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->ppio_pdfheader($params, $data);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $brand = $data[$i]['brand'];
        $model = $data[$i]['model'];
        $serialno = $data[$i]['serialno'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_brand = $this->reporter->fixcolumn([$brand], '20', 0);
        $arr_model = $this->reporter->fixcolumn([$model], '23', 0);
        $arr_serialno = $this->reporter->fixcolumn([$serialno], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_brand, $arr_model, $arr_serialno]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 9);
          if ($r == 0) {
            PDF::MultiCell(90, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LRT', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(90, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'RT', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(115, 15, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), 'RT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(115, 15, ' ' . (isset($arr_model[$r]) ? $arr_model[$r] : ''), 'RT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(125, 15, ' ' . (isset($arr_serialno[$r]) ? $arr_serialno[$r] : ''), 'RT', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            if (PDF::getY() >= $page) {
              $this->addrow();
            }
          } else {
            if (PDF::getY() >= $page) {
              PDF::MultiCell(90, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(90, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(115, 15, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(115, 15, ' ' . (isset($arr_model[$r]) ? $arr_model[$r] : ''), 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(125, 15, ' ' . (isset($arr_serialno[$r]) ? $arr_serialno[$r] : ''), 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            } else {
              PDF::MultiCell(90, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(90, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(115, 15, ' ' . (isset($arr_brand[$r]) ? $arr_brand[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(115, 15, ' ' . (isset($arr_model[$r]) ? $arr_model[$r] : ''), 'LR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(125, 15, ' ' . (isset($arr_serialno[$r]) ? $arr_serialno[$r] : ''), 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            }
          }
        }
        if (PDF::getY() >= $page) {
          $this->ppio_pdfheader($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(535, 0, "1. Submit the request form at least one (1) day before required date (cut off time 12:noon)- Note: NO PPIO, NO issuance of units. 
    \n 2. The unit shall be inspected including its accessories before leaviong and upon arrival in the office by authorized AFTI stock custodian. Requestor/Receiver & Witnessed.
    \n 3. For late return of units back to office, requestor should coordinate with stock custodian for the time of arrival. Designated holding area: Unit 702 Techical Room.
    \n 4. The requestor/Reciever is accountable (loss including cleanliness) for the units from time it was released until its return to the office.
    \n 5. All company properties that will be taken from office premise should be covered by this form.
    ", 'BTLR', 'L', false, 1);

    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(280, 0, "   Released/Issued By;", 'TL', 'L', false, 0);
    PDF::MultiCell(255, 0, "   Witnessed By:", 'TR', 'L', false, 1);

    PDF::MultiCell(535, 0, "\n", "LR");

    PDF::MultiCell(280, 0, "_________________________________", 'L', 'L', false, 0);
    PDF::MultiCell(255, 0, "_________________________________", 'R', 'L', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(280, 0, "(Signature Over Printed Name)", 'L', 'L', false, 0);
    PDF::MultiCell(255, 0, "(Signature Over Printed Name)", 'R', 'L', false, 1);

    PDF::MultiCell(535, 0, "\n", 'LR', 'L', false, 1);
    PDF::MultiCell(535, 0, "\n", 'LR', 'L', false, 1);
    PDF::MultiCell(535, 0, "\n", 'LR', 'L', false, 1);

    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(280, 0, "   Received By: ", 'L', 'L', false, 0);
    PDF::MultiCell(255, 0, "   Noted/Approved By:", 'R', 'L', false, 1);

    PDF::MultiCell(535, 0, "\n", "LR");

    PDF::MultiCell(280, 0, "_________________________________", 'L', 'L', false, 0);
    PDF::MultiCell(255, 0, "_________________________________", 'R', 'L', false, 0);

    PDF::MultiCell(535, 0, "\n", "LR");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(280, 0, "(Signature Over Printed Name)", 'L', 'L', false, 0);
    PDF::MultiCell(255, 0, "(Signature Over Printed Name)", 'R', 'L', false, 1);

    PDF::MultiCell(535, 0, "\n", "LR");

    PDF::MultiCell(535, 0, "revised: RBSo91813", 'LRB', 'L', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrow()
  {
    PDF::MultiCell(90, 15, ' ', 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(90, 15, ' ', 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(115, 15, ' ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(115, 15, ' ', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(125, 15, ' ', 'T', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
  }
}
