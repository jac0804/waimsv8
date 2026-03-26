<?php

namespace App\Http\Classes\modules\modulereport\transpower;

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
use App\Http\Classes\reportheader;
use DateTime;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class so
{

  private $modulename = "Quotation Form";
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
   
    $fields = ['radioprint', 'radioisassettag', 'radiopaidstatus','prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set($col1, 'radioisassettag.options', [
      ['label' => 'Original Amount', 'value' => '0', 'color' => 'red'],
      ['label' => 'Agent Amount', 'value' => '1', 'color' => 'red']
    ]);
    data_set($col1, 'radioisassettag.label', 'Print Price Option');


    data_set($col1, 'radiopaidstatus.options', [
      ['label' => 'Single Price Show', 'value' => '0', 'color' => 'red'],
      ['label' => 'Orig. Amount and Agent Amount Show', 'value' => '1', 'color' => 'red']
    ]);
    data_set($col1, 'radiopaidstatus.label', 'Price Layout Option');
    data_set($col1, 'prepared.label', 'Released By');


    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
        $paramstr = "select
          'PDFM' as print,
          '0' as isassettag,
          '0' as paidstatus,
          '' as prepared,
          '' as approved,
          '' as received";
    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($trno)
  {
    $query = "select head.rtype,head.rdate,cust.tel,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname,stock.agentamt as agtamt,head.ourref
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname,stock.agentamt as agtamt,head.ourref
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.doc='so' and head.trno='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_so_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
    //   return $this->default_so_PDF($params, $data);
    // }




    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->so_origamt_singleprice($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->so_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->so_agent_single_price($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->so_agent2($params, $data);
            break;
        }
        break;
    }

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
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('SALES ORDER', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
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
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '500px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');

    return $str;
  }

  public function default_so_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $str .= $this->default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['gross'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
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
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Released By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

   //////////// 
    public function default_so_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
     if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

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
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);

        // PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

   public function so_origamt_singleprice($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
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
     if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->so_new_header($params, $data);

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
        $amt = number_format($data[$i]['gross'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
              // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(300, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              //  PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
               PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
             
              PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
           }
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->so_new_header($params, $data);
          }
        }

              PDF::SetFont($font, '', 5);
              PDF::MultiCell(700, 0, '', 'B');

              PDF::SetFont($font, '', 3);
              PDF::MultiCell(700, 0, '', '');

              PDF::SetFont($fontbold, '', $fontsize);
              // PDF::MultiCell(100, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(300, 15, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, '', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15,'GRAND TOTAL:', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, number_format($totalext, $decimalcurr), 'B', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::SetFont($font, '', 5);
              PDF::MultiCell(700, 0, '', '');
                PDF::SetFont($font, '', $fontsize);
              PDF::SetTextColor(255, 0, 0);
              PDF::MultiCell(700, 15, '*All price and availability is subject to change without prior notice.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::SetTextColor(0, 0, 0);
      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', '');


      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
      PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

      PDF::MultiCell(0, 0, "\n\n\n");


      PDF::MultiCell(253, 0, 'Released By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


      return PDF::Output($this->modulename . '.pdf', 'S');
    }
  }

  
  public function default_so_header2_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

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
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "PRICE", '', 'C', false, 0); //AGENT AMT
        PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
        // PDF::MultiCell(70, 0, "(+/-) %", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }


  public function so_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalagntext=0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->so_new_header($params, $data);

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
        $amt = number_format($data[$i]['gross'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamt = number_format($data[$i]['agtamt'], 2);

         if ($agentamt != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
        } else {
          $agentext = 0;
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);
        $arr_agtamt = $this->reporter->fixcolumn([$agentamt], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
              // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(300, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
               PDF::SetTextColor(7, 13, 246);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
               PDF::SetTextColor(0, 0, 0);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              // PDF::MultiCell(70, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            
        
        }
          $totalext += $data[$i]['ext'];
          // $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->so_new_header($params, $data);
          }
      }

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', 'B');
         PDF::SetFont($font, '', 3);
          PDF::MultiCell(700, 0, '', '');

             PDF::SetFont($fontbold, '', $fontsize);
              // PDF::MultiCell(100, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15,'', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(300, 15, 'GRAND TOTAL:', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
               PDF::SetTextColor(7, 13, 246);
              PDF::MultiCell(100, 15, number_format($totalext, $decimalcurr), 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::SetTextColor(0, 0, 0);
              PDF::MultiCell(100, 15, '', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              // PDF::MultiCell(70, 15, '', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, number_format($totalext, $decimalcurr), 'B', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::SetFont($font, '', 5);
              PDF::MultiCell(700, 0, '', '');
              PDF::SetFont($font, '', $fontsize);
              PDF::SetTextColor(255, 0, 0);
              PDF::MultiCell(700, 15, '*All price and availability is subject to change without prior notice.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::SetTextColor(0, 0, 0);
      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', '');




      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
      PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

      PDF::MultiCell(0, 0, "\n\n\n");


      PDF::MultiCell(253, 0, 'Released By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


      return PDF::Output($this->modulename . '.pdf', 'S');
    }
  }



  ////////////  agent amounttttt
    public function default_so_agentheader_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
     if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

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
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);

        // PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(150, 0, "UNIT PRICE", '', 'R', false, 0);
        PDF::MultiCell(150, 0, "TOTAL", '', 'R', false);
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

   public function so_agent_single_price($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
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
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->so_new_header($params, $data);

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
        $amt = number_format($data[$i]['gross'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamt = number_format($data[$i]['agtamt'], 2);

        if ($agentamt != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);
        $arr_agtamt = $this->reporter->fixcolumn([$agentamt], '13', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_disc, $arr_ext,$arr_agtamt,$arr_agentext]);
        for ($r = 0; $r < $maxrow; $r++) {
              PDF::SetFont($font, '', $fontsize);
              // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(330, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(120, 15, ' ' . (isset($arr_agtamt[$r]) ? $arr_agtamt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(150, 15, ' ' . (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            
        }
          $totalext += $agentext;
          if (PDF::getY() > 900) {
            $this->so_new_header($params, $data);
          }
      }

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', 'B');

       PDF::SetFont($font, '', 3);
          PDF::MultiCell(700, 0, '', '');
       PDF::SetFont($fontbold, '', $fontsize);
              // PDF::MultiCell(100, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(330, 15, '', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(120, 15,'GRAND TOTAL:', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(150, 15, number_format($totalext, $decimalcurr), 'B', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

               PDF::SetFont($font, '', 5);
              PDF::MultiCell(700, 0, '', '');
               PDF::SetFont($font, '', $fontsize);
             PDF::SetTextColor(255, 0, 0);
              PDF::MultiCell(700, 15, '*All price and availability is subject to change without prior notice.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::SetTextColor(0, 0, 0);
      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', '');


      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
      PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

      PDF::MultiCell(0, 0, "\n\n\n");


      PDF::MultiCell(253, 0, 'Released By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


      return PDF::Output($this->modulename . '.pdf', 'S');
    }
  }



  public function default_so_agentheader2_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

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
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
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


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);

        // PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "PRICE", '', 'L', false, 0);
        PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
        // PDF::MultiCell(70, 0, "(+/-) %", '', 'R', false, 0);
        PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
   
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function so_agent2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    // $amtformat = $params['params']['dataparams']['amountformat'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalagntext=0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->so_new_header($params, $data);

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
        $amt = number_format($data[$i]['gross'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamt = number_format($data[$i]['agtamt'], 2);
     if ($agentamt != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);
        $arr_agtamt = $this->reporter->fixcolumn([$agentamt], '13', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_agtamt,$arr_agentext]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
              // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(300, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
               PDF::SetTextColor(7, 13, 246);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
               PDF::SetTextColor(0, 0, 0);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_agtamt[$r]) ? $arr_agtamt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              // PDF::MultiCell(70, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        
        }
          $totalext += $data[$i]['ext'];
          $totalagntext += $agentext;

          if (PDF::getY() > 900) {
            $this->so_new_header($params, $data);
          }
      }

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', 'B');

        PDF::SetFont($font, '', 3);
          PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
              // PDF::MultiCell(100, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15,'', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, '', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(300, 15, 'GRAND TOTAL:', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::SetTextColor(7, 13, 246);
              PDF::MultiCell(100, 15, number_format($totalext, $decimalcurr), 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
               PDF::SetTextColor(0, 0, 0);
              PDF::MultiCell(100, 15, '', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              // PDF::MultiCell(70, 15, '', 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, number_format($totalagntext, $decimalcurr), 'B', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

              PDF::SetFont($font, '', 5);
              PDF::MultiCell(700, 0, '', '');

              PDF::SetFont($font, '', $fontsize);
              PDF::SetTextColor(255, 0, 0);
              PDF::MultiCell(700, 15, '*All price and availability is subject to change without prior notice.', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::SetTextColor(0, 0, 0);
      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', '');




      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
      PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

      PDF::MultiCell(0, 0, "\n\n\n");


      PDF::MultiCell(253, 0, 'Released By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
      PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
      PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');


      return PDF::Output($this->modulename . '.pdf', 'S');
    }
  }



   public function so_new_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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

    PDF::SetFont($font, '', 9);

  
   
    PDF::MultiCell(0, 0, '', '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::SetTextColor(110, 150, 112);
    PDF::MultiCell(520, 0,  'QUOTATION FORM', '', 'L', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(400, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(180, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(120, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Customer", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Address", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "CONTACT #", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(450, 20, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "REFERENCE : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y/m/d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    // PDF::MultiCell(70, 20, "Printed Date", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20,  $formattedDate, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 20, "Printed by : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(170, 20, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(230, 20,  'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(70, 20, "Printed Date", 0, 'L', false, 0);
    PDF::MultiCell(10, 20, ":", 0, 'L', false, 0);
    PDF::MultiCell(150, 20, $formattedDate, 0, 'L', false, 0);
    PDF::MultiCell(60, 20, "Printed by : ", 0, 'L', false, 0);
    PDF::MultiCell(170, 20, $username, 0, 'L', false, 0);
    PDF::MultiCell(0, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 0, 'R', false, 1);


    //   PDF::SetFont($font, '', 10);R

    // PDF::MultiCell(0, 0, "\n");

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(700, 0, '', 'T');

  
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];
    
    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
              PDF::SetFont($font, 'B', 13);
              // PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "(+/-) %", '', 'C', false, 0);
              // PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

            break;
          case '1': // Orig. Amount and Agent Amount Show
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(100, 0, "PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
              PDF::SetFont($font, 'B', 13);
              PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(330, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(120, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(150, 0, "TOTAL", '', 'R', false);
            break;
          case '1': // Orig. Amount and Agent Amount Show
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(100, 0, "PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
            break;
        }
        break;
    }


    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }






}
