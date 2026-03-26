<?php

namespace App\Http\Classes\modules\modulereport\afti;

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

class cm
{

  private $modulename = "Sales Return";
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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'radiosjafti', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 10) {
      data_set($col1, 'prepared.type', 'lookup');
      data_set($col1, 'prepared.action', 'lookuppreparedby');
      data_set($col1, 'prepared.lookupclass', 'prepared');
      data_set($col1, 'prepared.readonly', true);

      data_set($col1, 'approved.type', 'lookup');
      data_set($col1, 'approved.action', 'lookuppreparedby');
      data_set($col1, 'approved.lookupclass', 'approved');
      data_set($col1, 'approved.readonly', true);
    }
    data_set($col1, 'radiosjafti.label', 'Report Type');

    data_set($col1, 'radiosjafti.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'red'],
      ['label' => 'Credit Note', 'value' => '1', 'color' => 'red']
    ]);

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
      '' as received,
      '0' as radiosjafti
      "
    );
  }

  public function report_default_query($trno)
  {

    $query = "select head.vattype, head.tax, client.tel, stock.rem as remarks, m.model_name as model,item.sizeid,
      date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms, head.rem,head.yourref,head.ourref,
      item.barcode,item.brand,
      item.itemname, stock.rrqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, stock.ref,ag.clientname as agname,
      ag.client as agcode,wh.client as whcode,wh.clientname as whname, stock.line, head.cur
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client on client.client=head.client
      left join client as ag on ag.client=head.agent
      left join client as wh on wh.clientid=stock.whid
      left join item on item.itemid=stock.itemid
      left join model_masterfile as m on m.model_id=item.model
      where head.doc='cm' and md5(head.trno)='" . md5($trno) . "'
      union all
      select head.vattype, head.tax, client.tel, stock.rem as remarks, m.model_name as model,item.sizeid,
      date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms, head.rem,head.yourref,head.ourref,
      item.barcode,item.brand,
      item.itemname, stock.rrqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, stock.ref,ag.clientname as agname,
      ag.client as agcode,wh.client as whcode,wh.clientname as whname, stock.line, head.cur
      from glhead as head left join glstock as stock on stock.trno=head.trno
      left join client on client.clientid=head.clientid
      left join item on item.itemid=stock.itemid
      left join model_masterfile as m on m.model_id=item.model
      left join client as ag on ag.clientid=head.agentid
      left join client as wh on wh.clientid=stock.whid
      where head.doc='cm' and md5(head.trno)='" . md5($trno) . "'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_cm_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      switch ($params['params']['dataparams']['radiosjafti']) {
        case 1:
          return $this->creditnote_cm_PDF($params, $data);
          break;

        default:
          return $this->default_cm_PDF($params, $data);
          break;
      }
    }
  }

  private function report_default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '70', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '500', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DISC', '50', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_cm_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->report_default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $ext = number_format($data[$i]['ext'], $decimal);
      if ($ext < 1) {
        $ext = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'] )), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $this->companysetup->getdecimal('price', $params['params'] )), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ext, '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        // ------------ HEADER ----------------
        $str .= $this->report_default_header($params, $data);

        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    } // end for

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '150', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function default_cm_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    PDF::SetFont($font, '', 9);
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(320, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(75, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(75, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(70, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(145, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(80, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(80, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(80, 0, "TOTAL", '', 'R', false);

    PDF::MultiCell(535, 0, '', 'B');
  }

  public function default_cm_PDF($params, $data)
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
    $this->default_cm_header_PDF($params, $data);

    $arritemname = array();
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $arritemname = (str_split($data[$i]['itemname'], 38));
        $itemcodedescs = [];

        if (!empty($arritemname)) {
          foreach ($arritemname as $arri) {
            if (strstr($arri, "\n")) {
              $array = preg_split("/\r\n|\n|\r/", $arri);
              foreach ($array as $arr) {
                array_push($itemcodedescs, $arr);
              }
            } else {
              array_push($itemcodedescs, $arri);
            }
          }
        }
        $countarr = count($itemcodedescs);

        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(70, 0, number_format($data[$i]['qty'], $decimalqty), '', 'R', false, 0, '', '', true, 1);
          PDF::MultiCell(80, 0, $data[$i]['uom'], '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(145, 0, $data[$i]['itemname'], '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(80, 0, number_format($data[$i]['amt'], $decimalprice), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(80, 0, $data[$i]['disc'], '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(80, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', false, 1, '', '', false, 1);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $qty = number_format($data[$i]['qty'], $decimalqty);
              $uom = $data[$i]['uom'];
              $gross = number_format($data[$i]['amt'], $decimalcurr);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], $decimalcurr);
            } else {
              $qty = '';
              $uom = '';
              $gross = '';
              $disc = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(70, 0, $qty, '', 'R', false, 0, '', '', true, 1);
            PDF::MultiCell(80, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(145, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 0, $gross, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 0, $disc, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 0, $ext, '', 'R', false, 1, '', '', false, 1);
          }
        }
        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_cm_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::MultiCell(535, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(435, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(485, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function creditnote_header_cm_PDF($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $font = '';
    $fontbold = '';
    $fontsize = '11';
    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";


    PDF::SetFont($font, '', 14);

    PDF::Image('/images/afti/qslogo.png', '', '', 200, 50);
    PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(290, 0, '', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);

    $drdocno = isset($data[0]['docno']) ? $data[0]['docno'] : '';

    PDF::MultiCell(0, 40, "\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->name, '', 'L', false, 0, '', '');
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, ' ' . '',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, ' ', '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->address, '', '', false, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, '',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, '', '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(0, 40, "\n");

    PDF::SetFont($font, 'B', 14);
    PDF::MultiCell(525, 0, 'CREDIT NOTE', '', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "CN NO.: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Date: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Currency: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['cur']) ? $data[0]['cur'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Page: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n");

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $totalext += $data[$i]['ext'];
    }

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, " Credit to : ", 'TLR', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'TLR', 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, " Amount : ", 'TLR', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0,  '  ' . (isset($data[0]['cur']) ? $data[0]['cur'] : '') . number_format($totalext, $decimalprice), 'TLR', 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 120, " Description : ", 'TLRB', 'R', false, 0, '',  '');
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(470, 120, '  ' . (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'TLRB', 'L', false, 1);

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(535, 0, 'This is a system-generated document Signature of approver is not required.', '', 'C', false, 1);
  }

  public function creditnote_cm_PDF($params, $data)
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
    $this->creditnote_header_cm_PDF($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
