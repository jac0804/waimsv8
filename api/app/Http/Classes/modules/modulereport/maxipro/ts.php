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
use App\Http\Classes\reportheader;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ts
{
  private $modulename = "Transfer Slip";
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
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
     '$username' as prepared
    ");
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "
    select head.vattype, head.tax, stock.rem as remarks, 
    client.tel, wh.tel as wtel, date(head.dateid) as dateid, 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,stock.line,
    item.itemname, stock.isqty as qty, stock.uom, 
    stock.cost as acost,stock.isamt as cost,stock.amt, 
    stock.disc, stock.ext, wh.client as swh, 
    wh.clientname as whname,stock.expiry, wh.addr, 
    client.addr as fromaddr, stock.loc, stock.loc2
    from lahead as head 
    left join lastock as stock on stock.trno=head.trno 
    left join client on client.client=head.client
    left join client as wh on wh.clientid = stock.whid
    left join item on item.itemid=stock.itemid
    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
    union all
    select head.vattype, head.tax,  stock.rem as remarks,  
    client.tel, wh.tel as wtel,  date(head.dateid) as dateid, 
    head.docno, client.client, client.clientname, head.address, head.terms,
    head.rem, item.barcode,stock.line,
    item.itemname, stock.isqty as qty, stock.uom, 
    stock.cost as acost,stock.isamt as cost,stock.amt, 
    stock.disc, stock.ext, wh.client  as swh, 
    wh.clientname as whname,stock.expiry, wh.addr, 
    client.addr as fromaddr, stock.loc, stock.loc2
    from glhead as head left join glstock as stock on stock.trno=head.trno 
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid 
    left join client as wh on wh.clientid=stock.whid
    where head.doc='ts' and stock.tstrno=0 and md5(head.trno)='" . md5($trno) . "'
    order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_ts_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->Maxipro_TS_PDF($params, $data);
    }
  }

  public function rpt_default_header($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

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
    $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '12', 'B', '', '');
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

  public function Maxipro_TS_header_PDF($params, $data)
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

    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(470, 0, strtoupper($this->modulename), '', 'L', false, 0, '',  '140');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(105, 15, "DOCUMENT#  :  ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(125, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'C', false, 0, '',  '');

    PDF::SetLineStyle(array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 3));

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 15, "WAREHOUSE", '', 'L', false, 0, '',  '');
    PDF::MultiCell(400, 15, ':  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(20, 15, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(55, 15, "DATE", '', 'L', false, 0, '',  '');
    PDF::MultiCell(145, 15, ':  ' . (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(300, 0, "Print Date: " . date_format(date_create($current_timestamp), 'm/d/Y H:i:s'), '', 'L', false, 0);
    PDF::MultiCell(425, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", "");
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 5, '', 'T');
    PDF::SetLineStyle(array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 3));

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(70, 18, "QTY", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(50, 18, "UNIT", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(320, 18, "DESCRIPTION", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "COST", 'B', 'C', false, 0);
    PDF::MultiCell(10, 0, "", '', 'C', false, 0);
    PDF::MultiCell(120, 18, "TOTAL", 'B', 'C', false);
  }

  public function Maxipro_TS_PDF($params, $data)
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
    PDF::SetMargins(40, 40);
    $this->Maxipro_TS_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", '', 'C', false);

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        ///////////////// start itemname
        $arritem = array();
        $itemname = [];

        $itemword = [];

        $itemword = explode(' ', $data[$i]['itemname']);
        $itemwordstring = '';
        foreach ($itemword as $word) {
          $itemwordstring = $itemwordstring . $word . ' ';
          if (strlen($itemwordstring) > 100) {
            $itemwordstring = str_replace($word, '', $itemwordstring);
            array_push($arritem, $itemwordstring);
            $itemwordstring = '';
            $itemwordstring = $itemwordstring . $word . ' ';
          }
        }
        array_push($arritem, $itemwordstring);
        $itemwordstring = '';
        ///////////////// itemname

        if (!empty($arritem)) {
          foreach ($arritem as $arri) {
            if (strstr($arri, "\n")) {
              $array = preg_split("/\r\n|\n|\r/", $arri);
              foreach ($array as $arr) {
                array_push($itemname, $arr);
              }
            } else {
              array_push($itemname, $arri);
            }
          }
        }
        ////////////////////// end itemname

        $maxrow = 1;

        $countarr = count($itemname);

        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {

              $qty = number_format($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $cost = number_format($data[$i]['cost'], $decimalcurr);
              $ext = number_format($data[$i]['ext'], $decimalcurr);
            } else {
              $qty = '';
              $uom = '';
              $cost = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);

            PDF::MultiCell(70, 0, $qty, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(320, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $cost == 0 ? '-' : $cost, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(120, 0, $ext == 0 ? '-' : $ext, '', 'R', false, 1, '', '', false, 1);
          }
        }
        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->Maxipro_TS_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", "");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", "T");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(580, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(120, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "");

    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(700, 5, '', 'T');
    PDF::SetLineStyle(array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));

    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE : ', '', 'L', false, 0);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 20, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 20, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 20, $params['params']['dataparams']['received'], 'B', 'C');
    PDF::MultiCell(53, 0, '', '', 'C', false, 0);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_TS_header_PDF($params, $data)
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
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');

    PDF::Image('public/images/reports/mdc.jpg', '45', '35', 100, 40);
    PDF::Image('public/images/reports/tuv.jpg', '630', '35', 100, 40);

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

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(125, 0, "COST", '', 'R', false, 0);
    PDF::MultiCell(125, 0, "TOTAL", '', 'R', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_TS_PDF($params, $data)
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
    $this->default_TS_header_PDF($params, $data);

    $arritemname = array();
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $arritemname = (str_split($data[$i]['itemname'], 40));
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
          PDF::MultiCell(100, 0, $data[$i]['barcode'], '', 'C', false, 0, '', '', true, 1);
          PDF::MultiCell(50, 0, number_format($data[$i]['qty'], 2), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(250, 0, $data[$i]['itemname'], '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(125, 0, number_format($data[$i]['cost'], $decimalprice), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(125, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', false, 1, '', '', false, 1);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $barcode =  $data[$i]['barcode'];
              $qty = number_format($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $cost = number_format($data[$i]['cost'], $decimalprice);
              $ext = number_format($data[$i]['ext'], $decimalprice);
            } else {
              $barcode = '';
              $qty = '';
              $uom = '';
              $cost = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, $barcode, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(50, 0, $qty, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(250, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(125, 0, $cost, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(125, 0, $ext, '', 'R', false, 1, '', '', false, 1);
          }
        }
        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_TS_header_PDF($params, $data);
          $page += $count;
        }
      }
    }


    PDF::MultiCell(700, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), '', 'R');

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
}
