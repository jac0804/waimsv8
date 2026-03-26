<?php

namespace App\Http\Classes\modules\modulereport\ericco;

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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ts
{

  private $modulename = "Transfer Slip";
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'checked', 'approved', 'delivered', 'received', 'noted', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'red'],
      ['label' => 'Pull - Out Item Layout', 'value' => '1', 'color' => 'red'],
      ['label' => 'Delivery Receipt', 'value' => '2', 'color' => 'red'],
      ['label' => 'Outright Receipt', 'value' => '3', 'color' => 'red'],
      ['label' => 'Consignment Receipt', 'value' => '4', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {

    $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as checked,
          '' as approved,
          '' as delivered,
          '' as received,
          '' as noted,
          '0' as reporttype";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "
    select head.vattype, head.tax, stock.rem as remarks, 
    client.tel, wh.tel as wtel, date(head.dateid) as dateid, 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,stock.line,
    item.itemname, item.color, item.sizeid, stock.isqty as qty, stock.uom, 
    stock.cost as acost,stock.isamt as cost,stock.amt, 
    stock.disc, stock.ext, wh.client as swh, 
    wh.clientname as whname,stock.expiry, wh.addr, 
    client.addr as fromaddr, stock.loc, stock.loc2,ifnull(partno,'') as sku,head.trno, sku.sku as tssku
    from lahead as head 
    left join lastock as stock on stock.trno=head.trno 
    left join client on client.client=head.client
    left join client as wh on wh.clientid = stock.whid
    left join item on item.itemid=stock.itemid
    left join sku on sku.itemid = item.itemid and sku.groupid = client.groupid
    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
    union all
    select head.vattype, head.tax,  stock.rem as remarks,  
    client.tel, wh.tel as wtel,  date(head.dateid) as dateid, 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,stock.line,
    item.itemname, item.color, item.sizeid, stock.isqty as qty, stock.uom, 
    stock.cost as acost,stock.isamt as cost,stock.amt, 
    stock.disc, stock.ext, wh.client  as swh, 
    wh.clientname as whname,stock.expiry, wh.addr, 
    client.addr as fromaddr, stock.loc, stock.loc2,ifnull(partno,'') as sku,head.trno, sku.sku as tssku
    from glhead as head left join glstock as stock on stock.trno=head.trno 
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid 
    left join client as wh on wh.clientid=stock.whid
    left join sku on sku.itemid = item.itemid and sku.groupid = client.groupid
    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
    order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    $print = $params['params']['dataparams']['print'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    switch ($print) {
      case 'PDFM':
        switch ($reporttype) {
          case '1': //PULL OUT LAYOUT
            return $this->default_TS_PDF($params, $data);
            break;
          case '2': //Delivery Receipt Layout
            return $this->delivery_receipt_layout_PDF($params, $data);
            break;
          case '3':
          case '4': //Outright and Consignment Receipt Layout
            return $this->outright_receipt_layout_PDF($params, $data);
            break;
          default:
            return $this->default_TS_PDF_orig($params, $data);
            break;
        }
        break;
      default:
        return $this->default_ts_layout($params, $data);
        break;
    }
  }

  public function rpt_default_header($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    if ($companyid == 3) {
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col("TRANSFER SLIP", '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SOURCE WH : ', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['whname']) ? $data[0]['whname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DESTINATION : ', '110', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '690', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY', '50', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '500', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('COST', '125', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function default_ts_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($params, $data);

    $totalext = 0;
    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $ext = number_format($data[$i]['ext'], $decimal);
      $ext = $ext < 0 ? '-' : $ext;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['cost'], $this->companysetup->getdecimal('price', $params['params'])), '125', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($ext, '125', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];
      $str .= $this->reporter->endrow();


      if ($this->reporter->linecounter == $page) {

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_default_header($params, $data);

        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '500', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '125', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('', '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_TS_header_PDF_orig($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);

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
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    if ($params['params']['companyid'] != 10 && $params['params']['companyid'] != 12) {
      $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    }
    $this->reportheader->getheader($params);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Source WH: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Destination", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "", '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(110, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    if ($viewcost == '1') {
      PDF::MultiCell(80, 0, "COST", '', 'R', false, 0);
      PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
    } else {
      PDF::MultiCell(80, 0, "", '', 'R', false, 0);
      PDF::MultiCell(100, 0, "", '', 'R', false);
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_TS_PDF_orig($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);
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
    $this->default_TS_header_PDF_orig($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $qty = number_format($data[$i]['qty'], 2);
      $uom = $data[$i]['uom'];
      $amt = number_format($data[$i]['cost'], 2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'], 2);

      $itemlen = '';
      $itemname = '';
      $barcodelen = '';

      if ($companyid == 47) {
        $itemname = $data[$i]['itemname'] . ' ' . $data[$i]['color'] . ' ' . $data[$i]['sizeid'];
        $itemlen = '65';
        $barcodelen = '12';
      } else {
        $itemname = $data[$i]['itemname'];
        $itemlen = '40';
        $barcodelen = '13';
      }

      $arr_barcode = $this->reporter->fixcolumn([$barcode], $barcodelen, 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], $itemlen, 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(110, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(250, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        if ($viewcost == '1') {
          PDF::MultiCell(80, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        } else {
          PDF::MultiCell(80, 15, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, '', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }
      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() > 900) {
        $this->default_TS_header_PDF_orig($params, $data);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    //PDF::AddPage();
    //$b = 62;
    //for ($i = 0; $i < 1000; $i++) {
    //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //  PDF::MultiCell(0, 0, "\n");
    //  if($i==$b){
    //    PDF::AddPage();
    //    $b = $b + 62;
    //  }
    //}

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_TS_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);
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
    $this->default_TS_header_PDF($params, $data);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(700, 0, '', '');
    //  PDF::SetCellPaddings($left, $top, $right, $bottom);
    PDF::SetCellPaddings(0, 5, 0, 5);
    $countarr = 0;

    $rowCount = 0;
    $pageLimit = 27;
    $trno = $params['params']['dataid'];
    $totalext = $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='" . $trno . "'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='" . $trno . "') as a");

    if (!empty($data)) {

      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['sku'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['cost'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $itemname = $data[$i]['itemname'];
        $stock_remarks = $data[$i]['remarks'];

        $arr_barcode = $this->reporter->fixcolumn([$barcode], 13, 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], 30, 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_remarks = $this->reporter->fixcolumn([$stock_remarks], 15, 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext, $arr_remarks]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);

          if ($rowCount >= $pageLimit) {
            $this->ts_footer($params, $totalext);
            $this->default_TS_header_PDF($params, $data);
            $rowCount = 0; // reset counter
          }
          PDF::MultiCell(24, 23, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, 23, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(66, 23, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 23, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(203, 23, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(86, 23, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(86, 23, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(92, 23, ' ' . (isset($arr_remarks[$r]) ? $arr_remarks[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(37, 23, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

          $rowCount++;
        }
      }
    }

    $trno = $params['params']['dataid'];
    $totalext = $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='" . $trno . "'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='" . $trno . "') as a");
    PDF::SetXY(40, 895);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(24, 0, '', '', 'R', false, 0);
    PDF::MultiCell(395, 0, '', '', 'R', false, 0);
    PDF::MultiCell(170, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(94, 0, '', '', 'R', false, 0);
    PDF::MultiCell(37, 0, '', '', 'R', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(24, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(113, 0, '', '', 'L', false, 0);
    PDF::MultiCell(111, 0,  $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, $params['params']['dataparams']['noted'], '', 'L', false, 0);
    PDF::MultiCell(37, 0, '', '', 'L', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_TS_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);

    $font = "";
    $fontbold = "";
    $fontsize = 13;
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
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // $y= PDF::getY();
    PDF::SetXY(40, 175);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "", '', 'L', false, 0);
    PDF::MultiCell(440, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 1);

    PDF::SetXY(40, 193.75);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "", '', 'L', false, 0);
    PDF::MultiCell(427, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false, 0);
    PDF::MultiCell(133, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0);
    PDF::MultiCell(50, 0, "", '', 'L', false, 1);

    PDF::MultiCell(0, 0, "\n\n");
  }

  // jan 27 Header for outright, delivery, consignment receipt- Clent
  public function outright_receipt_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $printTime = date('h:i A', strtotime($current_timestamp));
    $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $dateFormatted = '';

    if (!empty($data[0]['dateid'])) {
      $dateFormatted = date('F d, Y', strtotime($data[0]['dateid']));
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

    PDF::SetFont($font, '', 10);
    PDF::SetXY(650, 20); // top-right
    PDF::MultiCell(150, 0, 'Page ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages(), 0, 'R');

    // PDF::MultiCell(0, 0, "\n");

    PDF::Image(public_path('images/ericco/ericco_logo.jpg'), 80, 20, 160, 50);
    PDF::SetXY(240, 30);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
    PDF::SetXY(240, 45);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->tel), '', 'L');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(560, 0, '', '', 'R', false, 0);
    PDF::MultiCell(70, 0, 'No. of Box', 'LRTB', 'C', false, 0, '600',  '50', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(70, 0, '', 'LRTB', 'R', false, 1, '',  '', true, 0, false, true, 25, 'M', true);

    switch ($reporttype) {
      case 3:
        $title = 'OUTRIGHT RECEIPT';
        break;
      case 4:
        $title = 'CONSIGNMENT RECEIPT';
        break;
      default:
        $title = 'RECEIPT';
        break;
    }
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(215, 0, '', '', 'C', false, 0, '',  '100');
    PDF::MultiCell(290, 0, $title, '', 'C', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(60, 0, "NO.", '', 'R', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(135, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false, 0, '',  '');
    // PDF::MultiCell(20, 0, '', '', 'R', false, 1, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont('', '', $fontsize);
    PDF::MultiCell(80, 0, "Deliver To   :", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(450, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, "Date       : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, $dateFormatted, '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Address      :", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, "Print Time:", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, $printTime, '', 'L', false, 1, '',  '');

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(500, 0, '', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'Start Date:', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'End Date:', '', 'L', false, 0, '',  '');

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(500, 0, '', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'Start Date:', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'End Date:', '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(50, 0, "Qty", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Uom", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(100, 0, "Item Code", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(170, 0, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(140, 0, "SKU", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(40, 0, "SRP", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Gross", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(40, 0, "Disc", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    switch ($reporttype) {
      case 3:
        $totalLabel = 'Total Net';
        break;
      case 4:
        $totalLabel = 'Total';
        break;
      default:
        $totalLabel = 'Total';
        break;
    }
    PDF::MultiCell(60, 0, $totalLabel, 'TRB', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', 'B');
  }

  // jan 27 Outright Receipts Layout- Clent
  public function outright_receipt_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalqty = 0;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->outright_receipt_header_PDF($params, $data);

    $countarr = 0;

    $currentPage = 1;
    $tableStartY = PDF::getY();


    $footerHeight = 45;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $rowH = 15;

        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $barcode = $data[$i]['barcode'];
        $amt = number_format($data[$i]['amt'], 2);
        $sku = $data[$i]['tssku'];
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $gross = number_format((float)$data[$i]['amt'] * (float)$data[$i]['qty'], 2);


        $itemlen = '';
        $itemname = '';
        $barcodelen = '';

        if ($companyid == 47) {
          $itemname = $data[$i]['itemname'] . ' ' . $data[$i]['color'] . ' ' . $data[$i]['sizeid'];
          $itemlen = '65';
          $barcodelen = '12';
        } else {
          $itemname = $data[$i]['itemname'];
          $itemlen = '30';
          $barcodelen = '13';
        }

        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_barcode = $this->reporter->fixcolumn([$barcode], $barcodelen, 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], $itemlen, 0);
        $arr_sku = $this->reporter->fixcolumn([$sku], '20', 1);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0); // for srp
        $arr_gross = $this->reporter->fixcolumn([$gross], '13', 0); // for gross
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_sku, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        $estimatedRowHeight = $maxrow * $rowH;

        $currentY = PDF::getY();

        if ($currentY + $estimatedRowHeight > $contentLimit) {
          $this->outright_footer_PDF($params);
          $this->outright_receipt_header_PDF($params, $data);
          $currentPage++;
          PDF::SetY($tableStartY);
        }

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'R',  'C', false, 0);
          PDF::MultiCell(100, $rowH, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0);
          PDF::MultiCell(170, $rowH, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(140, $rowH, ' ' . (isset($arr_sku[$r]) ? $arr_sku[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(40, $rowH, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_gross[$r]) ? $arr_gross[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(40, $rowH, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(10, $rowH, ' ', 'R', 'R', false, 1);
        }

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];
      }
    }

    $yBefore = PDF::GetY();

    PDF::startTransaction();

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(500, 0, '', '', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'R', 'R');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, isset($totalqty) ? number_format($totalqty) : number_format(0), 'LR', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Total Qty', 'LR', 'l', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(600, 0, '----------------------------------Nothing follows----------------------------------', 'R', 'C', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(90, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(10, 0, ' ', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, ' ', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'TOTAL AMOUNT: ', 'TB', 'l', false, 0);
    PDF::MultiCell(90, 0, isset($totalext) ? number_format($totalext, $decimalcurr) : number_format(0, $decimalcurr), 'TB', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Group:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Repackers:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, 'Remarks:', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    $yAfter = PDF::GetY();
    $blockHeight = $yAfter - $yBefore;

    // Undo the “test print”
    PDF::rollbackTransaction(true);

    // Now do the real fit-check using your existing limit
    $currentY = PDF::GetY();
    if ($currentY + $blockHeight > $contentLimit) {
      $this->outright_footer_PDF($params);
      $this->outright_receipt_header_PDF($params, $data);
      PDF::SetY($tableStartY);
    }

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(500, 0, '', '', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'R', 'R');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, isset($totalqty) ? number_format($totalqty) : number_format(0), 'LR', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Total Qty', 'LR', 'l', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, '----------------------------------Nothing follows----------------------------------', 'R', 'C', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(90, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(10, 0, ' ', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, ' ', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'TOTAL AMOUNT: ', 'TB', 'l', false, 0);
    PDF::MultiCell(90, 0, isset($totalext) ? number_format($totalext, $decimalcurr) : number_format(0, $decimalcurr), 'TB', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Group:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Repackers:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, 'Remarks:', '', 'l', false, 0);
    PDF::MultiCell(520, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    // Footer will automatically handle spacing based on current Y position
    $this->outright_footer_PDF($params);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  // jan 29 Outright Receipt Footer- Clent
  private function outright_footer_PDF($params)
  {
    $font = "";
    $fontbold = "";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;


    $fillerLimit = 850;

    $currentY = PDF::getY();
    $footerStartPosition = $contentLimit;


    $spaceNeededForFiller = $fillerLimit - $currentY;


    // Always fill to exactly 850
    if ($currentY < $fillerLimit) {

      $fillRows = floor($spaceNeededForFiller / 20);

      for ($f = 0; $f < $fillRows; $f++) {
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(225, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 20, ' ', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }


      $currentY = PDF::getY();
      $remainingSpace = $fillerLimit - $currentY;

      if ($remainingSpace > 0 && $remainingSpace < 20) {
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(225, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, $remainingSpace, '', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }
    }

    // Make sure we're at exactly 850
    PDF::SetY($fillerLimit);

    // Add bottom border line to close the table
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(225, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 0, '', 'T', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetY($footerStartPosition);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(660, 0, 'This is not an invoice and not to be paid when presented. Our invoice will follow in due time.', '', 'L', false, 1);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(80, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(125, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(15, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 0, 'Received in good order and condition', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(80, 0, 'Prepared By  : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 0, 'Delivered By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(125, 0, $params['params']['dataparams']['delivered'], '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(80, 0, 'Checked By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, $params['params']['dataparams']['checked'], 'B', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 0, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(125, 0, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(15, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(35, 0, 'By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(80, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 0, '  ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(125, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, 'Signature over Printed Name', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(80, 15, 'Approved By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 15, $params['params']['dataparams']['approved'], 'B', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(125, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' Date : ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 15, '', 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(0, 0, "\n");
  }

  // feb 2 Header for outright, delivery, consignment receipt- Clent
  public function delivery_receipt_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $reporttype = $params['params']['dataparams']['reporttype'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $printTime = date('h:i A', strtotime($current_timestamp));
    $viewcost = $this->othersClass->checkAccess($params['params']['user'], 368);

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $dateFormatted = '';

    if (!empty($data[0]['dateid'])) {
      $dateFormatted = date('F d, Y', strtotime($data[0]['dateid']));
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

    PDF::SetFont($font, '', 10);
    PDF::SetXY(650, 20); // top-right
    PDF::MultiCell(150, 0, 'Page ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages(), 0, 'R');

    // PDF::MultiCell(0, 0, "\n")

    PDF::Image(public_path('images/ericco/ericco_logo.jpg'), 80, 20, 160, 50);
    PDF::SetXY(240, 30);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
    PDF::SetXY(240, 45);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->tel), '', 'L');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(560, 0, '', '', 'R', false, 0);
    PDF::MultiCell(70, 0, 'No. of Box', 'LRTB', 'C', false, 0, '600',  '50', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(70, 0, '', 'LRTB', 'R', false, 1, '',  '', true, 0, false, true, 25, 'M', true);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(215, 0, '', '', 'C', false, 0, '',  '100');
    PDF::MultiCell(290, 0, "DELIVERY RECEIPT", '', 'C', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(60, 0, "NO.", '', 'R', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(135, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false, 0, '',  '');
    // PDF::MultiCell(20, 0, '', '', 'R', false, 1, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont('', '', $fontsize);
    PDF::MultiCell(80, 0, "Deliver To   :", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(450, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, "Date       : ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, $dateFormatted, '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Address      :", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 0, "Print Time:", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, $printTime, '', 'L', false, 1, '',  '');

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(500, 0, '', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'Start Date:', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'End Date:', '', 'L', false, 0, '',  '');

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(500, 0, '', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'Start Date:', '', 'L', false, 0, '',  '');
    // PDF::MultiCell(100, 0, 'End Date:', '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(50, 0, "Qty", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Uom", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(100, 0, "Item Code", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(230, 0, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(160, 0, "SKU", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "SRP", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(60, 0, "Total", 'TRB', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', 'B');
  }

  // jan 30 Delivery Receipts Layout- Clent
  public function delivery_receipt_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalqty = 0;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->delivery_receipt_header_PDF($params, $data);

    $countarr = 0;

    $currentPage = 1;
    $tableStartY = PDF::getY();


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $rowH = 15;

        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $barcode = $data[$i]['barcode'];
        $amt = number_format($data[$i]['amt'], 2);
        $sku = $data[$i]['tssku'];
        $ext = number_format($data[$i]['ext'], 2);


        $itemlen = '';
        $itemname = '';
        $barcodelen = '';

        if ($companyid == 47) {
          $itemname = $data[$i]['itemname'] . ' ' . $data[$i]['color'] . ' ' . $data[$i]['sizeid'];
          $itemlen = '65';
          $barcodelen = '12';
        } else {
          $itemname = $data[$i]['itemname'];
          $itemlen = '36';
          $barcodelen = '13';
        }

        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_barcode = $this->reporter->fixcolumn([$barcode], $barcodelen, 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], $itemlen, 0);
        $arr_sku = $this->reporter->fixcolumn([$sku], '20', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0); // for srp
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_sku, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        $estimatedRowHeight = $maxrow * $rowH;

        $currentY = PDF::getY();

        if ($currentY + $estimatedRowHeight > $contentLimit) {
          $this->outright_footer_PDF($params);
          $this->delivery_receipt_header_PDF($params, $data);
          $currentPage++;
          PDF::SetY($tableStartY);
        }

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'R',  'C', false, 0);
          PDF::MultiCell(100, $rowH, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0);
          PDF::MultiCell(230, $rowH, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(160, $rowH, ' ' . (isset($arr_sku[$r]) ? $arr_sku[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(50, $rowH, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(10, $rowH, ' ', 'R', 'R', false, 1);
        }

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];
      }
    }

    $yBefore = PDF::GetY();

    PDF::startTransaction();

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(500, 0, '', '', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'R', 'R');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, isset($totalqty) ? number_format($totalqty) : number_format(0), 'LR', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Total Qty', 'LR', 'l', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(600, 0, '----------------------------------Nothing follows----------------------------------', 'R', 'C', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(90, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(10, 0, ' ', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, ' ', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'TOTAL AMOUNT: ', 'TB', 'l', false, 0);
    PDF::MultiCell(90, 0, isset($totalext) ? number_format($totalext, $decimalcurr) : number_format(0, $decimalcurr), 'TB', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Group:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Repackers:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, 'Remarks:', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'l', false, 0);
    PDF::MultiCell(520, 0, '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    $yAfter = PDF::GetY();
    $blockHeight = $yAfter - $yBefore;

    // Undo the “test print”
    PDF::rollbackTransaction(true);

    // Now do the real fit-check using your existing limit
    $currentY = PDF::GetY();
    if ($currentY + $blockHeight > $contentLimit) {
      $this->outright_footer_PDF($params);
      $this->delivery_receipt_header_PDF($params, $data);
      PDF::SetY($tableStartY);
    }

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(500, 0, '', '', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'R', 'R');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, isset($totalqty) ? number_format($totalqty) : number_format(0), 'LR', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Total Qty', 'LR', 'l', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, '----------------------------------Nothing follows----------------------------------', 'R', 'C', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(90, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(10, 0, ' ', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, ' ', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'TOTAL AMOUNT: ', 'TB', 'l', false, 0);
    PDF::MultiCell(90, 0, isset($totalqty) ? number_format($totalqty) : number_format(0), 'TB', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Group:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, 'Repackers:', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(490, 0, '', '', 'l', false, 0);
    PDF::MultiCell(90, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'L', 'l', false, 0);
    PDF::MultiCell(10, 0, '', 'L', '', false, 0);
    PDF::MultiCell(60, 0, 'Remarks:', '', 'l', false, 0);
    PDF::MultiCell(520, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', '', false, 1);

    // Footer will automatically handle spacing based on current Y position
    $this->outright_footer_PDF($params);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrow($border)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(24, 23, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(66, 23, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(66, 23, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(60, 23, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(203, 23, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(86, 23, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(86, 23, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(92, 23, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(37, 23, '', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
  }

  public function ts_footer($params, $totalext)
  {


    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    PDF::SetXY(40, 895);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(24, 0, '', '', 'R', false, 0);
    PDF::MultiCell(395, 0, '', '', 'R', false, 0);
    PDF::MultiCell(170, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(94, 0, '', '', 'R', false, 0);
    PDF::MultiCell(37, 0, '', '', 'R', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(24, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(113, 0, '', '', 'L', false, 0);
    PDF::MultiCell(111, 0,  $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, $params['params']['dataparams']['noted'], '', 'L', false, 0);
    PDF::MultiCell(37, 0, '', '', 'L', false, 1);
  }
}
