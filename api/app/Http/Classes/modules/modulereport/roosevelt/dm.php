<?php

namespace App\Http\Classes\modules\modulereport\roosevelt;

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
use DateTime;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class dm
{

  private $modulename = "Purchase Return";
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
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($trno)
  {
    $query = "select m.model_name as model,item.sizeid,date(head.dateid) as dateid, head.docno,head.wh, client.client, client.clientname,
  head.address, head.terms,head.rem, item.barcode,head.vattype,
  concat(stock.uom,' ',item.itemname) as itemdesc, stock.isqty as qty, stock.isamt as amt, stock.disc, stock.ext,if(stock.ref != '', concat(left(stock.ref,2), right(stock.ref,5)), '') as ref,date(stock.expiry) as expiry
  from lahead as head
  left join lastock as stock on stock.trno=head.trno
  left join client on client.client=head.client
  left join item on item.itemid = stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  where head.doc='dm' and head.trno ='$trno'
  union all
  select m.model_name as model,item.sizeid,
  date(head.dateid) as dateid, head.docno, wh.client as wh,client.client, client.clientname,
  head.address, head.terms,head.rem, item.barcode,head.vattype,
  concat(stock.uom,' ',item.itemname) as itemdesc, stock.isqty as qty, stock.isamt as amt, stock.disc, stock.ext,if(stock.ref != '', concat(left(stock.ref,2), right(stock.ref,5)), '') as ref,date(stock.expiry) as expiry
  from glhead as head
  left join glstock as stock on stock.trno=head.trno
  left join client as wh on wh.clientid=head.whid
  left join client on client.clientid=head.clientid
  left join item on item.itemid=stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  where head.doc='dm' and head.trno ='$trno' ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_dm_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->roosevelt_dm_PDF($params, $data);
    }
  }

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    // $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('# :', '20', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '150', null, false, $border, 'B', 'R', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '90', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '510', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '90', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '510', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '500px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');

    return $str;
  }

  public function default_dm_layout($params, $data)
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
    $str .= $this->default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '590', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_DM_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
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

    if ($params['params']['companyid'] != 10 && $params['params']['companyid'] != 12) {
      $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    }
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(500, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Supplier : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_DM_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
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
    $this->default_DM_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->default_DM_header_PDF($params, $data);
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


  public function roosevelt_dm_header_PDF($params, $data)
  {
    // var_dump($y);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
    $font2 = "";

    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }


    if (Storage::disk('sbcpath')->exists('/fonts/BroadwayRegular.ttf')) {
      $font2 = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/BroadwayRegular.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    // $y = PDF::getY(); //10.00125
    $y = (float)30;
    $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
    $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 30, 30, 120, 120) : 'No image found'; //x, y,width,height
    PDF::SetFont($font2, '', 33);
    $name = "ROOSEVELT CHEMICAL INC.";
    $address = "73 F. Mariano Avenue Dela Paz NCR, Second District 1600 City of Pasig Philippines";
    $tel = "Contact Number: 8645-1089; 7900-9642 Fax: 8645-3425";
    PDF::MultiCell(720, 0, $name, '', 'C', false, 1,  '', $y + 5);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, $address . "\n" . $tel, '', 'C', false, 1,  '', $y + 45); //Rowen
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(720, 0, 'VAT REG TIN: 000-282-667-00000 ', '', 'C', false, 1,  '', $y + 80);

    // PDF::MultiCell(0, 0, "\n");
    $x = PDF::getX();
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(720, 0, 'Sales Debit Note', '', 'C', false, 1,  $x, $y + 105);


    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 3, 3, 3);
    PDF::MultiCell(50, 0, 'Bill To:', '', 'L', false, 0,  $x, $y + 145);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(340, 0, (isset($data[0]['clientname']) ? strtoupper($data[0]['clientname']) : ''), '', 'L', false, 0,  $x + 50, $y + 145);
    PDF::MultiCell(10, 0, '', '', '', false, 0,  $x + 390, $y + 145);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0,  $x + 400, $y + 145);
    PDF::MultiCell(75, 0, 'No.', '', 'L', false, 0,  $x + 450, $y + 145);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(195, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1,  $x + 525, $y + 145);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 0, 0, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(400, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);
    PDF::MultiCell(75, 0, 'Date', '', 'L', false, 0);
    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('m/d/Y');
    PDF::MultiCell(195, 0, $datehere, '', 'L', false, 1);



    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 0, 0, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(400, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, 'Reference No', '', 'L', false, 0);
    PDF::MultiCell(195, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 0, 0, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(400, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, 'Salesman', '', 'L', false, 0);
    PDF::MultiCell(195, 0, (isset($data[0]['agname']) ? $data[0]['agname'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 0, 0, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(400, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, 'Terms', '', 'L', false, 0);
    PDF::MultiCell(195, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(3, 0, 0, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(400, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, 'Page', '', 'L', false, 0);
    PDF::MultiCell(195, 0, PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'L', false, 1);


    PDF::MultiCell(0, 0, "\n");
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(290, 0, 'DESCRIPTION', 'TB', 'L', false, 0);
    PDF::MultiCell(75, 0, 'QTY', 'TB', 'C', false, 0);
    PDF::MultiCell(80, 0, 'PRICE', 'TB', 'R', false, 0);
    PDF::MultiCell(80, 0, 'DISC', 'TB', 'C', false, 0);
    PDF::MultiCell(80, 0, 'DISC. AMT', 'TB', 'C', false, 0);
    PDF::MultiCell(115, 0, 'AMOUNT', 'TB', 'R', false, 1);

    PDF::SetCellPaddings(0, 0, 0, 0);
  }



  public function roosevelt_dm_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 26;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->roosevelt_dm_header_PDF($params, $data);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetCellPaddings(0, 4, 0, 0);
    $rowCount = 0;
    $countarr = 0;
    $y = (float)295;
    $x = PDF::GetX();

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $itemname = $data[$i]['itemdesc'];
        $qty = number_format($data[$i]['qty'], 0);
        $amt = number_format($data[$i]['amt'], 2);
        $ext = number_format($data[$i]['ext'], 2);
        $disc = $data[$i]['disc'];
        $discamt = 0;

        if ($disc != 0) {
          $total = $data[$i]['amt'] * $data[$i]['qty']; // original na  total
          $discamt = 0;

          // Hatiin by "/"
          $parts = explode("/", $disc);

          foreach ($parts as $d) {
            $d = trim($d); // tanggal spaces
            if (strpos($d, '%') !== false) {
              // Remove % sign then convert to number
              $percent = floatval(str_replace('%', '', $d));
              // $less = $total * ($percent / 100);
              $less = round($total * ($percent / 100), 2);
              $discamt += $less;
              $total -= $less;
            } else {
              // Fixed amount
              // $less = floatval($d);
              $less = round(floatval($d), 2);
              $discamt += $less;
              $total -= $less;
            }
          }
        }
        $damt = number_format($discamt, 2);
        $discamt = number_format($discamt, 2);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_discamt = $this->reporter->fixcolumn([$discamt], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_amt, $arr_disc, $arr_discamt, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          $discamt = isset($arr_discamt[$r]) ? $arr_discamt[$r] : 0;
          PDF::SetFont($font, '', $fontsize);
          PDF::SetXY($x, $y);
          PDF::MultiCell(290, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(75, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ($discamt == 0) ? '' : $discamt, '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(115, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          $y = PDF::getY();
          $rowCount++;
          if ($rowCount >= $page && $i < count($data) - 1) {
            $this->default_footer($params, $data);
            $rowCount = 0;
            $y = (float)295;
            $this->roosevelt_dm_header_PDF($params, $data);
            PDF::SetCellPaddings(0, 4, 0, 0);
          }
        }
      }
    }


    $this->default_footer($params, $data);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function default_footer($params, $data)
  {
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/tahoma.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahoma.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/tahomabd.ttf');
    }
    // $refs = [];
    $totalext = 0;
    foreach ($data as $row) {
      $totalext += $row['ext'];
      // if ($row['ref'] != '') {
      //   $refs[] = $row['ref'];
      // }
    }
    // $refString = implode(" ", array_unique($refs));
    // PDF::SetY(740);
    // PDF::SetCellPaddings(2, 2, 2, 1);
    // PDF::MultiCell(720, 0,  'Reason : ' . (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false, 1);
    // PDF::MultiCell(720, 0,  'Reference Document No.: ' . $refString, '', 'L', false, 1);

    $words = $this->reporter->ftNumberToWordsConverter($totalext,  false) . ' ONLY';

    $maxChars = 89;
    $adds = strlen($words);
    $remaininglines = [];
    $addsz = '';

    if ($adds > $maxChars) {
      $firstLine = substr($words, 0, $maxChars);
      $remaining = substr($words, $maxChars);
      // Split remaining address into multiple lines without cutting words
      while (strlen($remaining) > $maxChars) {
        // Find the last space within the maxChars limit
        $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

        // If there's no space, just cut at maxChars
        if ($spacePos === false) {
          $nextLine = substr($remaining, 0, $maxChars);
          $remaining = substr($remaining, $maxChars);
        } else {
          $nextLine = substr($remaining, 0, $spacePos);
          $remaining = substr($remaining, $spacePos + 1);
        }

        $remainingLines[] = $nextLine;
      }
      // Add the final remaining part if it's less than or equal to $maxChars
      if (strlen($remaining) > 0) {
        $remainingLines[] = $remaining;
      }
    } else {
      $addsz = $words;
    }

    if ($adds > $maxChars) {
      PDF::SetY(760);
      PDF::SetFont($font, '', $fontsize);
      PDF::SetCellPaddings(3, 3, 3, 3);
      PDF::MultiCell(720, 0, $firstLine, '', 'L', false, 1);

      foreach ($remainingLines as $line) {
        PDF::SetY(772);
        PDF::MultiCell(720, 0, $line, '', 'L', false, 0, '',  '');
      }
    } else {
      PDF::SetY(760);
      PDF::SetFont($font, '', $fontsize);
      PDF::SetCellPaddings(3, 3, 3, 3);
      PDF::MultiCell(720, 0, $addsz, '', 'L', false, 1);
    }

    PDF::SetY(760);
    PDF::MultiCell(180, 0, '', 'T', '', false, 0);
    PDF::MultiCell(180, 0, '', 'T', '', false, 0);
    PDF::MultiCell(150, 0, '', 'T', '', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0,  'TOTAL AMOUNT', 'T', 'L', false, 0);
    PDF::MultiCell(30, 0,  '', 'T', '', false, 0);
    PDF::MultiCell(90, 0,  number_format($totalext, 2), 'T', 'R', false, 1); //number_format($totalext, 2)

    if ($data[0]['vattype'] == 'VATABLE') {
      $vatsale = $totalext / 1.12;
    } else {
      $vatsale = $totalext;
    }
    // PDF::SetY(805);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(150, 0, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0,  'NET AMOUNT', '', 'L', false, 0);
    PDF::MultiCell(30, 0,  'PHP', '', '', false, 0);
    PDF::MultiCell(90, 0,  number_format($vatsale, 2), 'B', 'R', false, 1);

    PDF::SetY(780);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(160, 0, '', '', '', false, 0);
    PDF::MultiCell(80, 0,  '', '', '', false, 0);
    PDF::MultiCell(30, 0,  '', '', '', false, 0);
    PDF::MultiCell(90, 0,  '', 'B', '', false, 1);



    PDF::SetY(805);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, 'Notes: 1. All cheques should be crossed and made payable to', '', 'L', false, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetY(819);
    PDF::MultiCell(45, 0, '', '', 'L', false, 0);
    PDF::MultiCell(675, 0, 'ROOSEVELT CHEMICAL INC.', '', 'L', false, 1);

    PDF::SetY(835);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(33, 0, '', '', 'C', false, 0);
    PDF::MultiCell(327, 0, '2. Goods sold are neither returnable nor refundable. Otherwise', '', '', false, 0);
    PDF::MultiCell(360, 0,  '', '', 'C', false, 1);

    PDF::SetY(850);
    PDF::MultiCell(45, 0, '', '', 'C', false, 0);
    PDF::MultiCell(315, 0, 'a cancellation fee of 20% on purchase price will be imposed.', '', '', false, 0);
    PDF::MultiCell(360, 0,  '', '', 'C', false, 1);

    PDF::SetY(850);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(180, 0, '', '', 'C', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0, '', '', '', false, 0);
    PDF::MultiCell(180, 0,  'Authorised Signature', 'T', 'C', false, 1);

    PDF::SetY(875);
    PDF::SetCellPaddings(0, 0, 0, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(360, 0, '', '', 'C', false, 0, '', '');
    PDF::MultiCell(305, 0, '"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX"', 'B', 'L', false, 0, '', '');
    PDF::MultiCell(55, 0, '', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, 'Acknowledgement Certificate Control No.:', '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'Date Issued: January 01, 0001', '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, 'Inclusion Series: DN000000001 To: DN999999999', '', 'L', false, 1, '', '');


    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, 'QNE SOFTWARE PHILIPPINES, INC', '', 'L', false, 0, '', '');
    PDF::MultiCell(180, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(270, 0, 'Printed Date/Time: ' . $formattedDate, '', 'L', false, 0, '', '');
    PDF::MultiCell(50, 0, '', '', 'L', false, 1, '', '');

    PDF::MultiCell(400, 0, 'Unit 3103 The Stiles Enterprise Plaza Bldg. Podium 2 Hippodromo Street Circuit', '', 'L', false, 0, '', '');
    PDF::MultiCell(320, 0, 'Printed By: ' . $username, '', 'L', false, 1, '', '');

    PDF::MultiCell(400, 0, 'Carmona 1207 City Of Makati NCR, Fourth District Philippines', '', 'L', false, 0, '', '');
    PDF::MultiCell(320, 0, 'QNE Optimum Version 2024.1.0.7', '', 'L', false, 1, '', '');

    PDF::MultiCell(720, 0, 'TIN: 006-934-485-000', '', 'L', false, 1, '', '');
  }
}
