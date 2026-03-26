<?php

namespace App\Http\Classes\modules\modulereport\main;

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

class st
{

  private $modulename = "Stock Transfer";
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


  public function createreportfilter()
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
    $companyid = $config['params']['companyid'];

    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
    $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);

    switch ($companyid) {
      case 40:
        $paramstr = "select
          'PDFM' as print,
          '$username' as prepared,
          '$approved' as approved,
          '$received' as received";
        break;
      default:
        $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";
        break;
    }

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($trno)
  {

    $query = "select head.docno,head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.isamt as netamt, stock.isqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model,item.partno
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno 
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno' and stock.tstrno=0
        union all
        select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.isamt as netamt, stock.isqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model,item.partno
        from (glhead as head 
        left join glstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        where head.trno='$trno' and stock.tstrno=0
        order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_ST_LAYOUT($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_ST_PDF($params, $data);
    }
  }

  public function default_header($params, $data)
  {
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
    $str .= $this->reporter->col('STOCK TRANSFER', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT : ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('BARCODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '475', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');

    return $str;
  }

  public function default_ST_LAYOUT($params, $data)
  {

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $count = 35;
    $page = 35;
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
      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '475', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price', $params['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ext, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
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

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function vitaline_ST_header_PDF($params, $data)
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
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
    if ($params['params']['companyid'] == 40) { //cdo
      PDF::MultiCell(80, 0, "Branch: ", '', 'L', false, 0, '',  '');
    } else {
      PDF::MultiCell(80, 0, "Department: ", '', 'L', false, 0, '',  '');
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    if ($params['params']['companyid'] == 40) { //cdo
      PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
      PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
      PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
      PDF::MultiCell(180, 0, "DESCRIPTION", '', 'L', false, 0);
      PDF::MultiCell(90, 0, "PART #", '', 'C', false, 0);
      PDF::MultiCell(80, 0, "UNIT PRICE", '', 'R', false, 0);
      PDF::MultiCell(70, 0, "(+/-) %", '', 'R', false, 0);
      PDF::MultiCell(80, 0, "TOTAL", '', 'R', false);
    } else {
      PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
      PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
      PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
      PDF::MultiCell(270, 0, "DESCRIPTION", '', 'L', false, 0);
      PDF::MultiCell(80, 0, "UNIT PRICE", '', 'R', false, 0);
      PDF::MultiCell(70, 0, "(+/-) %", '', 'R', false, 0);
      PDF::MultiCell(80, 0, "TOTAL", '', 'R', false);
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_ST_PDF($params, $data)
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
    $this->vitaline_ST_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        // ///////////////// start itemname
        // $arritem = array();
        // $itemname = [];
        // $itemword=[];
        // $itemword=explode(' ',$data[$i]['itemname']);
        // $itemwordstring='';
        // foreach($itemword as $word) {
        //   $itemwordstring=$itemwordstring.$word.' ';
        //   if(strlen($itemwordstring)>100){
        //     $itemwordstring=str_replace($word,'',$itemwordstring);
        //     array_push($arritem,$itemwordstring);
        //     $itemwordstring='';
        //     $itemwordstring=$itemwordstring.$word.' ';
        //   }
        // }
        // array_push($arritem,$itemwordstring);
        // $itemwordstring='';
        // ///////////////// itemname
        // if(!empty($arritem)) {
        //   foreach($arritem as $arri) {
        //     if(strstr($arri, "\n")) {
        //       $array = preg_split("/\r\n|\n|\r/", $arri);
        //       foreach($array as $arr) {
        //         array_push($itemname, $arr);
        //       }
        //     } else {
        //       array_push($itemname, $arri);
        //     }
        //   }
        // }
        // ////////////////////// end itemname
        // $maxrow = 1;
        // $countarr = count($itemname);
        // $maxrow = $countarr;

        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], $decimalcurr);
        $partno = $data[$i]['partno'];

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        if ($params['params']['companyid'] == 40) { //cdo
          $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
        } else {
          $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        }
        $arr_netamt = $this->reporter->fixcolumn([$netamt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);
        $arr_partno = $this->reporter->fixcolumn([$partno], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_qty, $arr_uom, $arr_itemname, $arr_netamt, $arr_disc, $arr_ext, $arr_partno]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          if ($params['params']['companyid'] == 40) { //cdo
            PDF::MultiCell(100, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(50, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(50, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(180, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(90, 0, (isset($arr_partno[$r]) ? $arr_partno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(80, 0, (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(70, 0, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(80, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false);
          } else {
            PDF::MultiCell(100, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(50, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(50, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(270, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(80, 0, (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(70, 0, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(80, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false);
          }
        }

        // if ($data[$i]['itemname'] == '') {
        // } else {
        //     for($r = 0; $r < $maxrow; $r++) {
        //       if($r == 0) {
        //           $barcode = $data[$i]['barcode'];
        //           $qty = number_format($data[$i]['qty'],2);
        //           $uom = $data[$i]['uom'];
        //           $netamt = number_format($data[$i]['netamt'],2);
        //           $disc = $data[$i]['disc'];
        //           $ext = number_format($data[$i]['ext'],2);
        //       } else {
        //           $barcode = '';
        //           $qty = '';
        //           $uom = '';
        //           $netamt = '';
        //           $disc = '';
        //           $ext = '';
        //       }
        //       PDF::SetFont($font, '', $fontsize);
        //       PDF::MultiCell(100, 0, $barcode, '', 'C', false, 0,'','',true,1);
        //       PDF::MultiCell(50, 0, $qty, '', 'C', false, 0, '', '', false, 1);
        //       PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
        //       PDF::MultiCell(270, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '', '', false, 1);
        //       PDF::MultiCell(80, 0, $netamt, '', 'R', false, 0, '', '', false, 1);
        //       PDF::MultiCell(70, 0, $disc, '', 'R', false, 0, '', '', false, 1);
        //       PDF::MultiCell(80, 0, $ext, '', 'R', false, 0, '', '', false, 1);
        //       PDF::MultiCell(100, 0, '', '', 'R', false, 1, '', '', false, 0);
        //     }
        // }

        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->vitaline_ST_header_PDF($params, $data);
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
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    // PDF::MultiCell(760, 0, '', 'B');
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
