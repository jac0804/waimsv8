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

class jb
{
  private $modulename;
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
    $fields = ['radiopoafti', 'prepared', 'checked', 'noted', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);

    if ($companyid == 10) {
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
        ['label' => 'USD Format', 'value' => 'TC', 'color' => 'red'],

        // ['label' => 'PO  Terms and Condition', 'value' => 'TC', 'color' => 'red'],
      ]);
    }

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
            'AFTI' as radiopoafti,
            '' as noted,
            '' as prepared,
            '' as approved,
            '' as checked
        "
    );
  }

  public function report_default_query($trno)
  {
    $query = "select head.trno,date(head.dateid) as dateid, head.docno, concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as refno,client.client, client.clientname,  concat(CONCAT_WS('\r\n', NULLIF(bill.addrline1, ''), NULLIF(bill.addrline2, ''), NULLIF(bill.city, ''), NULLIF(bill.province, ''), NULLIF(bill.country, ''), NULLIF(bill.zipcode, '') ),'\r\n','Phone:',bill.contactno,'\r\n','Fax:',bill.fax) as address,concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as docno2,
              head.terms,head.rem, item.barcode,
              item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,
              m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stock.cost as amt,
              head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
              head.deptid,dept.clientname as deptname,
              bill.addr as billaddr,bill.contact as billcontact,bill.contactno as billcontactno,
              ship.addr as shipaddr,ship.contact as shipcontact,ship.contactno as shipcontactno,
              client.tel2, client.contact as suppcontact,'' as empcode, '' as empname,
              '' as emptel2,stockinfo.rem as inforem,head.tax, head.vattype, brands.brand_desc,
              iteminfo.itemdescription, whreceiver.clientname as whreceivername, head.cur,sr.clientname as srclientname, head.yourref
            from johead as head
            left join jostock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid = stock.itemid
            left join model_masterfile as m on m.model_id = item.model
            left join stockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
            left join client as wh on wh.client=head.wh
            left join client as dept on dept.clientid = head.deptid
            left join client as branch on branch.clientid = head.branch
            left join billingaddr as bill on bill.line = client.billid and bill.clientid = client.clientid
            left join billingaddr as ship on ship.line = client.shipid and ship.clientid = client.clientid
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
            left join client as whreceiver on whreceiver.clientid = head.whreceiver
            left join hsrhead as sr on sr.trno = stock.refx
            left join transnum as num on num.trno=head.trno
            where head.doc='jb' and stock.void<>1  and head.trno='$trno'
            union all
            select head.trno,date(head.dateid) as dateid, head.docno, concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as refno,client.client, client.clientname,
            concat(CONCAT_WS('\r\n', NULLIF(bill.addrline1, ''), NULLIF(bill.addrline2, ''), NULLIF(bill.city, ''), NULLIF(bill.province, ''), NULLIF(bill.country, ''), NULLIF(bill.zipcode, '') ),'\r\n','Phone:',bill.contactno,'\r\n','Fax:',bill.fax) as address, concat(right(num.bref,1),right(num.yr,2),right(head.docno,5)) as docno2,head.terms,head.rem, item.barcode,
              item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,
              m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stock.cost as amt,
              head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
              head.deptid,dept.clientname as deptname,
              bill.addr as billaddr,bill.contact as billcontact,bill.contactno as billcontactno,
              ship.addr as shipaddr,ship.contact as shipcontact,ship.contactno as shipcontactno,
              client.tel2, client.contact as suppcontact,'' as empcode, '' as empname,
              '' as emptel2,stockinfo.rem as inforem,head.tax, head.vattype, brands.brand_desc,
              iteminfo.itemdescription, whreceiver.clientname as whreceivername, head.cur,sr.clientname as srclientname, head.yourref
            from hjohead as head
            left join hjostock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid = stock.itemid
            left join model_masterfile as m on m.model_id = item.model
            left join stockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
            left join client as wh on wh.client=head.wh
            left join client as dept on dept.clientid = head.deptid
            left join client as branch on branch.clientid = head.branch
            left join billingaddr as bill on bill.line = client.billid and bill.clientid = client.clientid
            left join billingaddr as ship on ship.line = client.shipid and ship.clientid = client.clientid
            left join frontend_ebrands as brands on brands.brandid = item.brand
            left join iteminfo as iteminfo on iteminfo.itemid = stock.itemid
            left join client as whreceiver on whreceiver.clientid = head.whreceiver
            left join hsrhead as sr on sr.trno = stock.refx
            left join transnum as num on num.trno=head.trno
            where head.doc='jb' and stock.void<>1  and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
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
          // $str = app($this->companysetup->getreportpath($config['params']))->reportorderplotting($config, $data);
          $str = $this->reportorderplottingpdf($config, $data);
          break;
          // case 'TC':
          //     $str = $this->terms_condition_report($config, $data);
          // break;
        default:
          $str = $this->reportgenplottingpdf($config, $data);
          break;
      }
    }

    return $str;
  }


  public function PDF_JB_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize9 = 9;
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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(320, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(340, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(340, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(100, 0, "CODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(170, 0, "DESCRIPTION", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(50, 0, "(+/-)%", '', 'R', false, 0);
    PDF::MultiCell(50, 0, "TOTAL", '', 'R', false);

    PDF::MultiCell(540, 0, '', 'B');
  }

  public function JB_PDF($params, $data)
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
    $fontsize9 = "9";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->PDF_JB_header($params, $data);

    $arritemname = array();
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $arritemname = (str_split($data[$i]['itemname'], 32));
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
          PDF::SetFont($font, '', $fontsize9);
          PDF::MultiCell(100, 0, $data[$i]['barcode'], '', 'C', false, 0, '', '', true, 1);
          PDF::MultiCell(50, 0, number_format($data[$i]['qty'], $decimalqty), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(170, 0, $data[$i]['itemname'], '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, number_format($data[$i]['netamt'], $decimalcurr), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, $data[$i]['disc'], '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', false, 1, '', '', false, 1);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $barcode =  $data[$i]['barcode'];
              $qty = number_format($data[$i]['qty'], $decimalqty);
              $uom = $data[$i]['uom'];
              $netamt = number_format($data[$i]['netamt'], $decimalcurr);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], $decimalprice);
            } else {
              $barcode = '';
              $qty = '';
              $uom = '';
              $netamt = '';
              $disc = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(100, 0, $barcode, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(50, 0, $qty, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(170, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $netamt, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $disc, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $ext, '', 'R', false, 1, '', '', false, 1);
          }
        }
        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->PDF_JB_header($params, $data);
          $page += $count;
        }
      }
    }

    PDF::MultiCell(540, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(420, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(153, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(153, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
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
    $str .= $this->reporter->col($this->modulename, '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '140', null, false, $border, 'B', 'R', $font, '12', '', '', '');
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
    $str .= $this->reporter->col('CODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '475', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_jb_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params'] );

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
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '475', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price', $params['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
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
    $str .= $this->reporter->col('ITEM(S)', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '440', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
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
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function local_pdfheader($params, $data, $font)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\jb')->modulename;

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

    $fontsize9 = "9";
    $fontsize10 = "10";
    $fontsize11 = "11";

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

    PDF::SetFont($font, '', $fontsize9);
    PDF::Image('public/images/afti/qslogo.png', '30', '', 220, 50);
    PDF::SetFont($font, 'b', $fontsize11);
    PDF::MultiCell(0, 40, "");
    if ($params['params']['dataparams']['radiopoafti'] == 'AFTI') {
      PDF::MultiCell(540, 0, 'Job Order: ' . '  ' . (isset($data[0]['docno2']) ? $data[0]['docno2'] : ''), '', 'L', 0, 1, '', '', false, 0, false, false, 0);
    } else {
      PDF::MultiCell(540, 0, 'Job Order: ' . '  ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', 0, 1, '', '', false, 0, false, false, 0);
    }
    $style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [155, 155, 155]];
    PDF::SetLineStyle($style);

    PDF::Line(PDF_MARGIN_LEFT, PDF::getY(), PDF::getPageWidth() - PDF_MARGIN_LEFT, PDF::getY());
    PDF::MultiCell(0, 10, "");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(100, 20, "Supplier Name: ", '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 20, 'Date: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(100, 20, 'Vendor Address: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 20, 'Ship to: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 20, $headerdata[0]->address, '', 'L', false);

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
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0);
    PDF::MultiCell(200, 20, 'Phone: (+632) 892-3883', '', 'L', false);

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
    $fontsize9 = "9";
    $arritemname = array();
    $countarr = 0;

    $totalctr = 0;
    $cur = $data[0]['cur'];

    $style = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [175, 175, 175]];
    PDF::SetLineStyle($style);

    $style = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [175, 175, 175]];
    PDF::SetLineStyle($style);

    for ($i = 0; $i < count($data); $i++) {

      $ext = number_format($data[$i]['ext'], $decimalcurr);
      $ext = $ext < 0 ? '-' : $ext;
      $netamt = number_format($data[$i]['netamt'], $decimalcurr);
      $netamt = $netamt < 0 ? '-' : $netamt;


      $barcode = $data[$i]['barcode'];
      $itemdescription = $data[$i]['itemdescription'];
      $inforem = $data[$i]['inforem'];

      if ($inforem != "") {
        $inforem = "\n" . $inforem;
      }

      if ($itemdescription != "") {
        $itemdescription = "\n" . $itemdescription;
      }

      $itemname = $data[$i]['itemname'] . ", " . $data[$i]['model'] . ", " . $data[$i]['brand_desc'] . $itemdescription . $inforem;
      $uom = $data[$i]['uom'];
      $qty = round($data[$i]['qty'], 0);

      $itemdesc = $this->reporter->fixcolumn([$itemname], '40');
      $countitemdesc = count($itemdesc);

      $itemqty = (str_split(trim($uom . ' ' . $qty), 12));
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

    if ($data[0]['vattype'] == 'VATABLE') {
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
    PDF::MultiCell(100, 10, $cur . ' ' . number_format($total, $decimalcurr), '', 'R');

    PDF::SetFont($font, 'b', $fontsize9);
    PDF::MultiCell(355, 10, '12% VAT ', '', 'R', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 10, $cur . ' ' . number_format($vat, $decimalprice), '', 'R');

    PDF::SetFont($font, 'b', $fontsize9);
    PDF::MultiCell(355, 10, 'Grand Total ', '', 'R', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 10, $cur . ' ' . number_format($totalext, $decimalcurr), '', 'R');

    PDF::SetFont($font, 'b', $fontsize9);
    PDF::MultiCell(355, 10, 'In Words ', '', 'R', false, 0);
    PDF::MultiCell(20, 10, '', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(180, 10, $this->reporter->ftNumberToWordsConverter(round($totalext, $decimalcurr), false, $cur) . ' ONLY', '', 'L', false, 0);

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

    if ($data[0]['clientname'] != "AFTECH") {
      $this->terms_condition_report($params, $data);
    }


    PDF::MultiCell(760, 0, '', '');
    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
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
    PDF::Image('public/images/afti/qslogo.png', '70', '70', 200, 50);
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

    $no1 = $poterms[0]->poterms;
    PDF::writeHTML($no1, true, false, true, false, '');

    PDF::MultiCell(0, 7, "\n\n\n");

    PDF::MultiCell(760, 0, '', '');
  }

  public function signature2($params, $font)
  {
    $fontsize9 = "9";
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Prepared By', '', '', false, 0);
    PDF::MultiCell(120, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Noted & Approved by', '', 'L', false, 0);
    PDF::MultiCell(20, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(120, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(20, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'Procurement', '', 'C', false, 0);
    PDF::MultiCell(120, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'O.P.L.P Head', '', 'C', false, 0);
    PDF::MultiCell(20, 50, '', '', 'R');
  }

  public function signature3($params, $font)
  {
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
    PDF::MultiCell(180, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Noted By', '', 'C', false, 0);
    PDF::MultiCell(120, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(180, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['noted'], 'B', 'C', false, 0);
    PDF::MultiCell(120, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(180, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, 'O.P.L.P. Head', '', 'C', false, 0);
    PDF::MultiCell(120, 50, '', '', 'R');
  }

  public function signature4($params, $font)
  {
    $fontsize9 = "9";
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Prepared By', '', 'C', false, 0);
    PDF::MultiCell(120, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Checked by', '', 'C', false, 0);
    PDF::MultiCell(20, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(120, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['checked'], 'B', 'C', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(20, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, '', '', 'C', false, 0);
    PDF::MultiCell(120, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, '', '', 'C', false, 0);
    PDF::MultiCell(20, 50, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Noted By', '', 'C', false, 0);
    PDF::MultiCell(120, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Approved by', '', 'C', false, 0);
    PDF::MultiCell(20, 0, '', '', 'R');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['noted'], 'B', 'C', false, 0);
    PDF::MultiCell(120, 20, '', '', 'L', false, 0);
    PDF::MultiCell(150, 20, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'R', $fontsize9);
    PDF::MultiCell(20, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, '', '', 'C', false, 0);
    PDF::MultiCell(120, 50, '', '', 'L', false, 0);
    PDF::MultiCell(150, 50, '', '', 'C', false, 0);
    PDF::MultiCell(20, 50, '', '', 'R');
  }


  public function ftNumberToWordsConverter($number)
  {
    $numberwords = $this->ftNumberToWordsBuilder($number);

    if (strpos($numberwords, "/") == false) {
      $numberwords .= " PESOS ";
    } else {
      $numberwords = str_replace(" AND ", " PESOS AND ", $numberwords);
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
        1000000000000000000 => 'Quintillion'
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

        $string .= $decimal . ' ' . $fraction .  '/100';
        $words = array();
        $string .= implode(' ', $words);
      } //end if

      return strtoupper($string);
    } //end
  } //end fn

  public function reportgenplottingpdf($params, $data)
  {
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

    PDF::SetFont($font, '', $fontsize10);
    PDF::MultiCell(0, 20, strtoupper($headerdata[0]->name), '', 'C');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 20, "Report Detail Imported PO", '', 'C');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 30, " " . "AFTi Ref No.", 'TLBR', 'L', false, 0);
    PDF::MultiCell(100, 30, " " . "Cust Name", 'TLBR', 'L', false, 0);
    PDF::MultiCell(80, 30, " " . "Cust PO#", 'TLBR', 'L', false, 0);
    PDF::MultiCell(70, 30, " " . "PO Cust Date", 'TLBR', 'R', false, 0);
    PDF::MultiCell(180, 30, " " . "Product Description", 'TLBR', 'L', false, 0);
    PDF::MultiCell(50, 30, " " . "PO Cust Qty", 'TLBR', 'L', false, 0);
    PDF::MultiCell(50, 30, " " . "B/O", 'TLBR', 'L', false, 0);
    PDF::MultiCell(80, 30, " " . "Status & DO# & INV#", 'TLBR', 'L', false, 0);
    PDF::MultiCell(80, 30, " " . "Transfer Price", 'TLBR', 'L', false, 0);
    PDF::MultiCell(80, 30, " " . "Total", 'TLBR', 'L');

    for ($i = 0; $i < count($data); $i++) {
      $ext =  number_format($data[$i]['qty'] * $data[$i]['amt'], $decimalcurr);
      $ext = $ext < 0 ? '-' : $ext;
      $netamt = number_format($data[$i]['amt'], $decimalcurr);
      $netamt = $netamt < 0 ? '-' : $netamt;

      $cur = $data[0]['cur'];
      $refno = $data[$i]['refno'];
      $clientname = $data[$i]['srclientname'];
      $yourref = $data[$i]['yourref'];
      $dateid = date("d-M-Y", strtotime($data[0]['dateid']));
      $itemname = $data[$i]['itemname'];
      $itemdescription = $data[$i]['itemdescription'];
      $qty = number_format($data[$i]['qty'], 0) . "  " . $data[$i]['uom'];
      $bo = '0';

      $arrrefno = $this->reporter->fixcolumn([$refno], '8');
      $crefno = count($arrrefno);

      $arrclientname = $this->reporter->fixcolumn([$clientname], '14');
      $cclientname = count($arrclientname);

      $arrdocno = $this->reporter->fixcolumn([$yourref], '14');
      $cdocno = count($arrdocno);

      $arrdateid = (str_split(trim($dateid), 12));
      $cdateid = count($arrdateid);

      $arritemname = $this->reporter->fixcolumn([$itemname, $itemdescription], '30');
      $citemname = count($arritemname);

      $arrbo = (str_split(trim($bo) . ' ', 2));
      $cbo = count($arrbo);

      $arrqty = (str_split(trim($qty) . ' ', 10));
      $cqty = count($arrqty);

      $arrnetamt = (str_split(trim($cur . ' ' . $netamt), 14));
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
        PDF::MultiCell(50, 10, isset($arrrefno[$r]) ? ' ' . $arrrefno[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(100, 10, isset($arrclientname[$r]) ? ' ' . $arrclientname[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(80, 10, isset($arrdocno[$r]) ? ' ' . $arrdocno[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(70, 10, isset($arrdateid[$r]) ? ' ' . $arrdateid[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(180, 10, isset($arritemname[$r]) ? ' ' . $arritemname[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(50, 10, isset($arrqty[$r]) ? ' ' . $arrqty[$r] : '', 'LR', 'L', false, 0);
        PDF::MultiCell(50, 10, isset($arrbo[$r]) ? ' ' . $arrbo[$r] : '', 'LR', 'C', false, 0);
        PDF::MultiCell(80, 10, "", 'LR', 'L', false, 0);
        PDF::MultiCell(80, 10, isset($arrnetamt[$r]) ? ' ' . $arrnetamt[$r] : '', 'LR', 'R', false, 0);
        PDF::MultiCell(80, 10, isset($arrext[$r]) ? ' ' . $arrext[$r] : '', 'LR', 'R');
      }
      $this->addrowusd('LRB');
      $totalext = $totalext + $data[$i]['ext'];
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrowusd($border)
  {
    PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(100, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(80, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(70, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(180, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', false, 1);
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

  private function addrow($border, $height = 0)
  {
    PDF::MultiCell(50, $height, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(235, $height, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(80, $height, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(90, $height, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, $height, '', $border, 'R', false, 1, '', '', false, 0);
  }
}
