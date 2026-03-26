<?php

namespace App\Http\Classes\modules\modulereport\maxipro;

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

class mt
{
  private $modulename = "Material Transfer";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;
  private $reportheader;
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


  public function createreportfilter()
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
    $user = $config['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if ((isset($name[0]['name']) ? $name[0]['name'] : '') != '') {
      $user = $name[0]['name'];
    }

    $signatories = $this->othersClass->getSignatories($config);
    $approved = '';


    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'approved':
          $approved = $value->fieldvalue;
          break;
      }
    }


    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
         '$user' as prepared,
      '$approved' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select head.vattype, head.tax, stock.rem as remarks, client.tel, wh.tel as wtel, date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
      head.rem, item.barcode,stock.line,
      item.itemname, stock.isqty as qty, stock.uom, stock.cost as acost,stock.isamt as cost,stock.amt, 
      stock.disc, stock.ext, wh.client as swh, wh.clientname as whname,stock.expiry, wh.addr, client.addr as fromaddr, stock.loc, stock.loc2
      from lahead as head left join lastock as stock on stock.trno=head.trno 
      left join client on client.client=head.client
      left join client as wh on wh.clientid = stock.whid
      left join item on item.itemid=stock.itemid
      where head.doc='mt' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
      union all
      select head.vattype, head.tax,  stock.rem as remarks,  client.tel, wh.tel as wtel,  date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
      head.rem, item.barcode,stock.line,
      item.itemname, stock.isqty as qty, stock.uom, stock.cost as acost,stock.isamt as cost,stock.amt, 
      stock.disc, stock.ext, wh.client  as swh, wh.clientname as whname,stock.expiry, wh.addr, client.addr as fromaddr, stock.loc, stock.loc2
      from glhead as head left join glstock as stock on stock.trno=head.trno 
      left join client on client.clientid=head.clientid
      left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid
      where head.doc='mt' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
      order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    return $this->default_MT_PDF($params, $data);
  }

  private function rpt_default_header($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
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

  public function default_MT_layout($params, $data)
  {
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
  } //end fn

  public function default_MT_header_PDF($params, $data)
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
    PDF::AddPage('p', [800, 1000]);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)
    $this->reportheader->getheader($params);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '120');
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
    PDF::MultiCell(80, 0, "Destination: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(230, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(70, 0, "COST", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_MT_PDF($params, $data)
  {
    $trno = $params['params']['dataid'];
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
    PDF::SetMargins(40, 40);
    $this->default_MT_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $barcode = $data[$i]['barcode'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $amt = number_format($data[$i]['amt'], $decimalprice);
        $ext = number_format($data[$i]['ext'], $decimalprice);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '16', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '16', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '16', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_qty, $arr_uom, $arr_itemname, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 1);
          PDF::MultiCell(100, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(230, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(70, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', false, 1);
        }


        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_MT_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    $query = "select trno from glhead where trno = $trno";
    $chkposted = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    if (!empty($chkposted)) {
      $qry = "select coa.acno, coa.acnoname, detail.postdate,detail.db,detail.cr,
                    detail.clientid,detail.projectid,proj.code as projcode,sproj.subproject,
                    client.clientname as whname,client.client as wh
              from gldetail as detail
              left join coa on coa.acnoid=detail.acnoid
              left join projectmasterfile as proj on proj.line=detail.projectid
              left join subproject as sproj on sproj.line=detail.subproject
              left join client on client.clientid=detail.clientid
              where detail.trno = $trno";

      $detail = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(90, 15, 'ACCT#', 'TBL', 'C', false, 0);
      PDF::MultiCell(150, 15, 'ACCOUNTING ENTRY', 'TBL', 'C', false, 0);
      PDF::MultiCell(100, 15, 'WAREHOUSE', 'TBL', 'C', false, 0);
      PDF::MultiCell(100, 15, 'DEBIT', 'TBL', 'C', false, 0);
      PDF::MultiCell(100, 15, 'CREDIT', 'TBLR', 'C', false, 0);
      PDF::MultiCell(60, 15, 'PROJECT', 'TBLR', 'C', false, 0);
      PDF::MultiCell(100, 15, 'SUBPROJECT', 'TBLR', 'C', false);

      PDF::SetFont($font, '', 1);
      PDF::MultiCell(90, 5, '', 'L', 'C', false, 0);
      PDF::MultiCell(150, 5, '', 'L', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'L', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'L', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'L', 'C', false, 0);
      PDF::MultiCell(60, 5, '', 'L', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'LR', 'R', false);


      $totaldb = 0;
      $totalcr = 0;
      $acname = "";
      $wh = "";
      $accode = "";



      for ($k = 0; $k < count($detail); $k++) {

        $accode = $detail[$k]['acno'];
        $acname = $detail[$k]['acnoname'];
        $wh = $detail[$k]['wh'];
        $debit = number_format($detail[$k]['db'], 2);
        $debit = $debit < 0 ? '-' : $debit;
        $credit = number_format($detail[$k]['cr'], 2);
        $credit = $credit < 0 ? '-' : $credit;
        $proj = $detail[$k]['projcode'];
        $subproj = $detail[$k]['subproject'];


        $arr_accode = $this->reporter->fixcolumn([$accode], '10', 0);
        $arr_acname = $this->reporter->fixcolumn([$acname], '22', 0);
        $arr_wh = $this->reporter->fixcolumn([$wh], '12', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        $arr_proj = $this->reporter->fixcolumn([$proj], '9', 0);
        $arr_subproj = $this->reporter->fixcolumn([$subproj], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_accode, $arr_acname, $arr_wh, $arr_debit, $arr_credit, $arr_proj, $arr_subproj]);

        for ($r = 0; $r < $maxrow; $r++) {
          if ($r == 0) {
            $cur = "<span>&#8369;</span>";
          } else {
            $cur = '';
          }
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(90, 0, (isset($arr_accode[$r]) ? $arr_accode[$r] : ''), 'RL', 'C', false, 0, '', '', true, 1);

          PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(140, 0, (isset($arr_acname[$r]) ? $arr_acname[$r] : ''), 'R', 'L', false, 0, '', '', false, 1);

          PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(90, 0, (isset($arr_wh[$r]) ? $arr_wh[$r] : ''), 'R', 'L', false, 0, '', '', false, 1);

          PDF::SetFont('dejavusans', '', 9, '', true);
          PDF::MultiCell(20, 15, $cur, 'L', 'C', false, 0, '', '', false, 1, true);
          PDF::MultiCell(70, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(10, 0, '', 'R', 'R', false, 0, '', '', false, 1);

          PDF::SetFont('dejavusans', '', 9, '', true);
          PDF::MultiCell(20, 15, $cur, 'L', 'C', false, 0, '', '', false, 1, true);
          PDF::MultiCell(70, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(10, 0, '', 'R', 'R', false, 0, '', '', false, 1);
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(5, 0, '', '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(55, 0, (isset($arr_proj[$r]) ? $arr_proj[$r] : ''), 'R', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(5, 0, '', '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(95, 0, (isset($arr_subproj[$r]) ? $arr_subproj[$r] : ''), 'R', 'L', false, 1, '', '', false, 1);
        }
      }

      PDF::SetFont($font, '', 1);
      PDF::MultiCell(90, 5, '', 'LRB', 'C', false, 0);
      PDF::MultiCell(150, 5, '', 'LRB', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'LRB', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'LRB', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'LRB', 'C', false, 0);
      PDF::MultiCell(60, 5, '', 'LRB', 'C', false, 0);
      PDF::MultiCell(100, 5, '', 'LRB', 'C', false);

      PDF::SetFont($font, '', $fontsize);

      PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    }

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
