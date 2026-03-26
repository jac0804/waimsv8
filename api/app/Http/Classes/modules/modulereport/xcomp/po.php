<?php

namespace App\Http\Classes\modules\modulereport\xcomp;

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

class po
{
  private $modulename = "Purchase Order";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'prepared', 'approved', 'checked', 'received'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
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
    $received = '';
    $checked = '';


    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'approved':
          $approved = $value->fieldvalue;
          break;
        case 'received':
          $received = $value->fieldvalue;
          break;
        case 'checked':
          $checked = $value->fieldvalue;
          break;
      }
    }


    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '$user' as prepared,
      '$approved' as approved,
      '$received' as received,
      '$checked' as checked
      "
    );
  }
  // qwe @123qwE123
  public function report_default_query($trno)
  {
    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, 
        stock.ext,m.model_name as model,item.sizeid,if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemdesc,client.tin
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
        where head.doc='po' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, 
        stock.ext,m.model_name as model,item.sizeid,if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemdesc,client.tin
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join hstockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
        where head.doc='po' and head.trno='$trno'";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_po_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_PO_PDF($params, $data);
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
    $str .= $this->title_header($params);
    return $str;
  }

  public function title_header($params)
  {
    $border = "1px solid ";
    $font =  "Century Gothic";
    $str = "";

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '475', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();


    return $str;
  }



  public function default_po_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
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
      $str .= $this->reporter->endrow();

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
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_PO_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)


    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
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
    PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    if (!empty($data[0]['address'])) {
      $maxrow = 1;
      $address = $data[0]['address'];
      $arr_address = $this->reporter->fixcolumn([$address], '70', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
      for ($r = 0; $r < $maxrow; $r++) {

        if ($r == 0) {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), 'B', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');
          
        } else {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, '', '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), 'B', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, '', '', 'L', false, 1, '',  '');
        }
      }
    

      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(80, 0, "Tin #: ", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(170, 0, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false, 1, '',  '');
    }

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(125, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(50, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(90, 0, "TOTAL", '', 'R', false);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_PO_PDF($params, $data)
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
    $fontsize = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_PO_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemdesc'];
      $qty = number_format($data[$i]['qty'], 0);
      $uom = $data[$i]['uom'];
      $netamt = number_format($data[$i]['netamt'], 2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'], 2);

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '14', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '5', 0);
      $arr_netamt = $this->reporter->fixcolumn([$netamt], '13', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '12', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_netamt, $arr_disc, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(125, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(60, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(90, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() >= 920) {
        $this->default_PO_header_PDF($params, $data);
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
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(175, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(175, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
