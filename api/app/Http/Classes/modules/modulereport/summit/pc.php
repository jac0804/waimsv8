<?php

namespace App\Http\Classes\modules\modulereport\summit;

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

class pc
{

  private $modulename = "Physical Count";
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
    data_set($col1, 'refresh.action', 'history');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($filters)
  {
    switch ($this->companysetup->getsystemtype($filters['params'])) {
      case 'FAMS':
        return $this->fams_default_query($filters);
        break;
      default:
        return $this->default_query($filters);
        break;
    }
  }

  public function default_query($filters)
  {
    $trno = md5($filters['params']['dataid']);
    $query = "
    select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
    head.terms,head.rem, item.barcode,
    item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
    head.yourref, head.ourref, stock.rem as srem, stock.consignee
    from pchead as head 
    left join pcstock as stock on stock.trno=head.trno 
    left join client on client.client=head.wh
    left join item on item.itemid = stock.itemid
    where head.doc='pc' and md5(head.trno)='$trno'
    union all
    select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
    head.terms,head.rem, item.barcode,
    item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
    head.yourref, head.ourref, stock.rem as srem, stock.consignee
    from hpchead as head 
    left join hpcstock as stock on stock.trno=head.trno 
    left join client on client.client=head.wh
    left join item on item.itemid = stock.itemid
    where head.doc='pc' and md5(head.trno)='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $result;
  }

  public function fams_default_query($filters)
  {
    $trno = md5($filters['params']['dataid']);
    $query = "
    select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
    head.terms,head.rem, item.barcode,
    item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
    head.yourref, head.ourref, stock.rem as srem, stock.consignee,
    head.wh as hloccode,
    location.client as sloccode
    from pchead as head 
    left join pcstock as stock on stock.trno=head.trno 
    left join client on client.client=head.wh
    left join item on item.itemid = stock.itemid
    left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
    left join client as location on location.clientid = iteminfo.locid
    where head.doc='pc' and md5(head.trno)='$trno'
    union all
    select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
    head.terms,head.rem, item.barcode,
    item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
    head.yourref, head.ourref, stock.rem as srem, stock.consignee,
    head.wh as hloccode,
    location.client as sloccode
    from hpchead as head 
    left join hpcstock as stock on stock.trno=head.trno 
    left join client on client.client=head.wh
    left join item on item.itemid = stock.itemid
    left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
    left join client as location on location.clientid = iteminfo.locid
    where head.doc='pc' and md5(head.trno)='$trno'";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $result;
  }

  public function reportplotting($params, $data)
  {
    switch ($this->companysetup->getsystemtype($params['params'])) {
      case 'FAMS':
        return $this->fams_PC_PDF($params, $data);
        break;
      default:
        return $this->default_PC_PDF($params, $data);
        break;
    }
  }

  public function default_pc_layout($config, $result)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PHYSICAL COUNT', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WAREHOUSE : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->clientname) ? $result[0]->clientname : ''), '510', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('QTY', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '550px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('COST', '125px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');

    $totalext = 0;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data->qty, 2), '50px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->uom, '50px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->itemname, '550px', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col(number_format($data->cost, 2), '125px', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col(number_format($data->ext, 2), '125px', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $totalext = $totalext + $data->ext;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->reporter->begintable('800');
        $str .= $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('PHYSICAL COUNT', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('WAREHOUSE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
        $str .= $this->reporter->col((isset($result[0]->clientname) ? $result[0]->clientname : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
        $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
        $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('QTY', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('D E S C R I P T I O N', '550px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('COST', '125px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '125px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '120px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '125px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

    // MODIFIED IF NULL DATA [JIKS] FEB.19.2020
    if (!empty($result)) {
      $str .= $this->reporter->col($result[0]->rem, '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    }

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

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_PC_header_PDF($params, $data)
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

    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

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
    PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "", '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(125, 0, "COST", '', 'R', false, 0);
    PDF::MultiCell(125, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_PC_PDF($params, $data)
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
    $this->default_PC_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;


        $barcode =  $data[$i]['barcode'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $cost = number_format($data[$i]['cost'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '13', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '8', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_cost = $this->reporter->fixcolumn([$cost], '15', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_cost, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(120, 15, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(120, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }
        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->default_PC_header_PDF($params, $data);
        }
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
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

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

  public function fams_PC_header_PDF($params, $data)
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
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(700, 0, "INVENTORY COUNT", '', 'C', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, $data[0]['docno'], '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Yourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['yourref'], '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, $data[0]['dateid'], '', 'L', false, 0, '',  '');
    PDF::MultiCell(0, 20, "", '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Location: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, $data[0]['client'], '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Ourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['ourref'], '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Notes: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, $data[0]['rem'], '', 'L', false, 0, '',  '');
    PDF::MultiCell(0, 20, "", '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, $data[0]['clientname'], '', 'L', false, 0, '',  '');
    PDF::MultiCell(0, 20, "", '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "ITEMNAME", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "CONSIGNEE", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "REMARKS", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function fams_PC_PDF($params, $data)
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

    $this->fams_PC_header_PDF($params, $data);

    $arritemname = array();
    $countarr = 0;

    $itemcount = count($data);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $arritemname = (str_split($data[$i]['itemname'], 35));
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

        $setfill = false;
        if ($data[0]['hloccode'] != $data[$i]['sloccode']) {
          $setfill = true;
        }

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::SetFillColor(255, 0, 0);
          PDF::MultiCell(100, 0, $data[$i]['barcode'], '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(200, 0, $data[$i]['itemname'], '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(200, 0, $data[$i]['consignee'], '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(200, 0, $data[$i]['srem'], '', 'L', false, 1, '', '', false, 1);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $barcode =  $data[$i]['barcode'];
              $consignee = $data[$i]['consignee'];
              $srem = $data[$i]['srem'];
            } else {
              $barcode = '';
              $consignee = '';
              $srem = '';
            }

            PDF::SetFont($font, '', $fontsize);
            PDF::SetFillColor(255, 0, 0);
            PDF::MultiCell(100, 0, $barcode, '', 'L', $setfill, 0, '', '', true, 1);
            PDF::MultiCell(200, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', $setfill, 0, '', '', false, 1);
            PDF::MultiCell(200, 0, $consignee, '', 'L', $setfill, 0, '', '', false, 1);
            PDF::MultiCell(200, 0, $srem, '', 'L', $setfill, 1, '', '', false, 1);
          }
        }

        if (intVal($i) + 1 == $page) {
          $this->fams_PC_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(700, 0, "Total Item Count: " . $itemcount, 'TB', 'L', false, 0);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");


    $qry = "select group_concat(itemid) as ids 
      from (
        select itemid 
        from iteminfo 
        where itemid <> 0 and locid <> 0 
        group by itemid
      ) as t";
    $itemids = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $query = "
      select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
      head.terms,head.rem, item.barcode,
      item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
      head.yourref, head.ourref, stock.rem as srem, stock.consignee,
      head.wh as hloccode,
      location.client as sloccode
      from pchead as head 
      left join pcstock as stock on stock.trno=head.trno 
      left join client on client.client=head.wh
      left join item on item.itemid = stock.itemid
      left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
      left join client as location on location.clientid = iteminfo.locid
      where head.doc='pc' and stock.itemid NOT IN (" . $itemids[0]['ids'] . ") and item.isfa = 1
      union all
      select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
      head.terms,head.rem, item.barcode,
      item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as cost, stock.disc, stock.ext,
      head.yourref, head.ourref, stock.rem as srem, stock.consignee,
      head.wh as hloccode,
      location.client as sloccode
      from hpchead as head 
      left join hpcstock as stock on stock.trno=head.trno 
      left join client on client.client=head.wh
      left join item on item.itemid = stock.itemid
      left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
      left join client as location on location.clientid = iteminfo.locid
      where head.doc='pc' and stock.itemid NOT IN (" . $itemids[0]['ids'] . ") and item.isfa = 1
    ";
    $result2 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "ITEMNAME", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "CONSIGNEE", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DATE ISSUED", '', 'C', false);
    PDF::MultiCell(700, 0, '', 'B');


    $arritemname = array();
    $countarr = 0;

    $itemcount = count($result2);
    if (!empty($result2)) {
      for ($i = 0; $i < count($result2); $i++) {

        $arritemname = (str_split($result2[$i]['itemname'], 35));
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

        $setfill = false;
        if ($result2[0]['hloccode'] != $result2[$i]['sloccode']) {
          $setfill = true;
        }

        if ($result2[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::SetFillColor(255, 0, 0);
          PDF::MultiCell(100, 0, $result2[$i]['barcode'], '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(200, 0, $result2[$i]['itemname'], '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(200, 0, $result2[$i]['consignee'], '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(200, 0, $result2[$i]['dateid'], '', 'L', false, 1, '', '', false, 1);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $barcode =  $result2[$i]['barcode'];
              $consignee = $result2[$i]['consignee'];
              $srem = $result2[$i]['dateid'];
            } else {
              $barcode = '';
              $consignee = '';
              $srem = '';
            }

            PDF::SetFont($font, '', $fontsize);
            PDF::SetFillColor(255, 0, 0);
            PDF::MultiCell(100, 0, $barcode, '', 'L', $setfill, 0, '', '', true, 1);
            PDF::MultiCell(200, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', $setfill, 0, '', '', false, 1);
            PDF::MultiCell(200, 0, $consignee, '', 'L', $setfill, 0, '', '', false, 1);
            PDF::MultiCell(200, 0, $srem, '', 'L', $setfill, 1, '', '', false, 1);
          }
        }

        if (intVal($i) + 1 == $page) {
          PDF::SetFont($font, 'B', 12);
          PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
          PDF::MultiCell(200, 0, "ITEMNAME", '', 'C', false, 0);
          PDF::MultiCell(200, 0, "CONSIGNEE", '', 'C', false, 0);
          PDF::MultiCell(200, 0, "DATE ISSUED", '', 'C', false);
          PDF::MultiCell(700, 0, '', 'B');
          $page += $count;
        }
      }
    }
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(700, 0, "Total Item Count: " . $itemcount, 'TB', 'L', false, 0);

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
