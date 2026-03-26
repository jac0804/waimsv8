<?php

namespace App\Http\Classes\modules\modulereport\housegem;

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

class rr
{
  private $modulename = "Receiving Items";
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
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
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
      '' as received
      "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select head.docno,head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,stock.sortline,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,left(stock.rem,15) as srem,item.sizeid,m.model_name as model,head.driver,head.plateno
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno'
        union all
        select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,stock.sortline,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,left(stock.rem,15) as srem,item.sizeid,m.model_name as model,head.driver,head.plateno
        from (glhead as head
        left join glstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno'
        order by sortline,line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_RR_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_RR_PDF($params, $data);
    }
  }

  public function default_header($params, $data)
  {
    $this->modulename = app('App\Http\Classes\modules\purchase\rr')->modulename;

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
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
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : '') . QrCode::size(100)->generate($data[0]['docno'] . '-' . $data[0]['trno']), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    //$str .= $this->reporter->col(DNS1D::getBarcodeHTML($data[0]['docno'].'-'.$data[0]['trno'], 'C39+', 1, 33, 'black', true), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
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
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('EXPIRY', '100px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DISC', '50px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function default_RR_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);


    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $ext = number_format($data[$i]['ext'], $decimal);
      $ext = $ext < 0 ? '-' : $ext;
      $netamt = number_format($data[$i]['netamt'], $decimal);
      $netamt = $netamt < 0 ? '-' : $netamt;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');

      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '400px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['expiry'], '100px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['gross'], $this->companysetup->getdecimal('price', $params['params'])), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ext, '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        //$str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }



    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
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

    //$str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn



  public function default_RR_header_PDF($params, $data)
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

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(20, 20);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, '', 30);
    PDF::MultiCell(760, 0, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(610, 0, $data[0]['docno'], '', 'L', false, 1, '152',  '55');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(400, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 0, '152',  '75');
    $dateid = date('m-d-Y', strtotime($data[0]['dateid']));
    PDF::MultiCell(210, 0, $dateid, '', 'L', false, 1, '555',  '75');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(400, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 0, '152',  '95');
    PDF::MultiCell(210, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, '555',  '95');

    PDF::SetFont($font, '', 16);
    PDF::MultiCell(760, 0, '');
  }

  public function default_RR_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 11;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_RR_header_PDF($params, $data);

    $countarr = 0;

    $rcount = 0;
    PDF::SetFont($font, '', 3);
    PDF::MultiCell(760, 0, '');

    for ($i = 0; $i < count($data); $i++) {

      // start itemname
      $arritem = [];
      $itemname = [];

      $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '40', 0);

      $maxrow = 1;
      $countarr = count($itemname);
      $maxrow = $countarr;

      if ($data[$i]['itemname'] == '') {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(150, 0, $data[$i]['barcode'], '', 'L', false, 0, '25', '', true, 1);
        PDF::MultiCell(100, 0, number_format($data[$i]['qty'], $decimalqty), '', 'R', false, 0, '140', '', false, 1);
        PDF::MultiCell(100, 0, $data[$i]['uom'], '', 'C', false, 0, '270', '', false, 1);
        PDF::MultiCell(235, 0, $data[$i]['itemname'], '', 'L', false, 1, '350', '', false, 0);
        PDF::MultiCell(175, 0, $data[$i]['srem'], '', 'L', false, 1, '350', '', false, 0);
      } else {
        if (($rcount + $maxrow) > $page) {
          $this->default_RR_footer_PDF($rcount, ($page + 3), $params, $data, $totalext, $decimalprice, $font, $fontbold, $fontsize);
          $this->default_RR_header_PDF($params, $data);
          $page += $count;
        }
        for ($r = 0; $r < $maxrow; $r++) {
          $rcount++;
          if ($r == 0) {
            $barcode = $data[$i]['barcode'];
            $qty = number_format($data[$i]['qty'], $decimalqty);
            $uom = $data[$i]['uom'];
            $netamt = number_format($data[$i]['netamt'], $decimalcurr);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], $decimalcurr);
            $srem = $data[$i]['srem'];
          } else {
            $barcode = '';
            $qty = '';
            $uom = '';
            $netamt = '';
            $disc = '';
            $ext = '';
            $srem = '';
          }
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(760, 0, '');

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(150, 0, $barcode, '', 'L', false, 0, '25', '', true, 1);
          PDF::MultiCell(100, 0, $qty, '', 'R', false, 0, '140', '', false, 1);
          PDF::MultiCell(100, 0, $uom, '', 'C', false, 0, '245', '', false, 1);
          PDF::MultiCell(100, 0, $srem, '', 'L', false, 0, '625', '', false, 1);
          PDF::MultiCell(235, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 1, '350', '', false, 0);
        }
      }
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(660, 0, '', '', 'L', false, 0);

    $this->default_RR_footer_PDF($rcount, ($page + 2), $params, $data, $totalext, $decimalprice, $font, $fontbold, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_RR_footer_PDF($rcount, $maxpage, $params, $data, $totalext, $decimalprice, $font, $fontbold, $fontsize)
  {
    for ($a = $rcount; $a < $maxpage; $a++) {
      PDF::SetFont($font, '', 5);
      PDF::MultiCell(760, 0, '', '', 'L', false, 1);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(760, 0, '', '', 'L', false, 1);
    }

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(300, 0, '', '', 'C', false, 0, '', '350');
    PDF::MultiCell(100, 0, ' ' . $data[0]['plateno'], '', 'L', false, 1, '345',  '355');
    PDF::MultiCell(300, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, ' ' . $data[0]['driver'], '', 'L', false, 1, '345',  '370');


    PDF::SetFont($font, '', 12);
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['prepared'], '', 'C', false, 0, '7',  '430');
    PDF::MultiCell(100, 0, ' ' . $params['params']['dataparams']['received'], '', 'C', false, 0);
    PDF::MultiCell(100, 0, ' ' . $params['params']['dataparams']['approved'], '', 'C', false, 1);
  }
}
