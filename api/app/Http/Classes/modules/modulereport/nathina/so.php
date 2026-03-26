<?php

namespace App\Http\Classes\modules\modulereport\nathina;

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

class so
{

  private $modulename = "Sales Order";
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
      ['label' => 'Quotation', 'value' => 'SO', 'color' => 'red'],
      
    ]);
    data_set($col1, 'radioreporttype.name', 'format');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      'default' as format,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($trno)
  {
    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address,
    date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
    item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
    stock.uom, stock.disc, stock.ext, stock.line,item.brand,wh.clientname as whname,
    item.sizeid,m.model_name as model,cust.acct as fbname,head.shipto,cust.tel as contact,
    head.ms_freight as othercharge,head.mlcp_freight as chargedes
    from sohead as head left join sostock as stock on stock.trno=head.trno 
    left join item on item.itemid=stock.itemid
    left join model_masterfile as m on m.model_id = item.model
    left join client as wh on wh.client=head.wh
    left join client as cust on cust.client = head.client
    where head.trno='$trno'
    union all
    select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
    date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
    item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
    stock.uom, stock.disc, stock.ext, stock.line,item.brand,wh.clientname as whname,
    item.sizeid,m.model_name as model,cust.acct as fbname,head.shipto,cust.tel as contact,
    head.ms_freight as othercharge,head.mlcp_freight as chargedes
    from hsohead as head
    left join hsostock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as m on m.model_id = item.model
    left join client as wh on wh.client=head.wh
    left join client as cust on cust.client = head.client
    where head.doc='so' and head.trno='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_so_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      switch ($params['params']['dataparams']['format']) {
        case 'SO':
          return $this->quotation_so_PDF($params, $data);
          break;

        default:
          return $this->default_so_PDF($params, $data);
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

    
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    
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
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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

  public function quotation_so_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');


    
    PDF::Image("public/images/nathina/natlogo.png", '180', '20', 120, 85);

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    
    PDF::Image('public/images/nathina/skinfluence.png', '495', '20', 75, 75);

    
    PDF::SetFont($fontbold, '', 22);
    PDF::MultiCell(520, 0, 'Quotation', '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(380, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(170, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(380, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, "Contact No: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(170, 0, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Ship to: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(380, 0, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, "FB Name: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(170, 0, (isset($data[0]['fbname']) ? $data[0]['fbname'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(330, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(20, 0, "", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function quotation_so_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('', $params['params']);
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
    $this->quotation_so_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    $numitems = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {



        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);
        $maxrow = 1;

        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          
          PDF::MultiCell(100, 0, number_format($data[$i]['qty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(330, 0, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['gross'], $decimalcurr), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(20, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalcurr), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $qty = number_format($data[$i]['qty'], $decimalqty);
              $uom = $data[$i]['uom'];
              $gross = number_format($data[$i]['gross'], $decimalcurr);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], $decimalcurr);
            } else {
              $qty = '';
              $uom = '';
              $gross = '';
              $disc = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, $qty, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(330, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $gross, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(20, 0, '', '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ext, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, '', '', 'R', false, 1, '', '', false, 0);
          }
        }

        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_so_header_PDF($params, $data);
          $page += $count;

        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(430, 0, '', '', '', false, 1, 100, 535);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, $data[0]['rem'], '', 'L', false);
    if ($data[0]['othercharge'] <> 0) {
      PDF::MultiCell(400, 0, $data[0]['chargedes'], '', 'L', false, 0);
      PDF::MultiCell(300, 0, $data[0]['othercharge'], '', 'R', false);
    } else {
      PDF::MultiCell(400, 0, '', '', 'L', false);
    }

    
    PDF::MultiCell(700, 0, '', 'B');

    $gtotal = $totalext + $data[0]['othercharge'];
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(100, 0, 'No Of Items: ', '', 'L', false, 0);
    PDF::MultiCell(100, 0, $numitems, '', 'L', false, 0);
    PDF::MultiCell(400, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($gtotal, $decimalcurr), '', 'R', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(700, 0, 'Amount in Words: ***' . $this->ftNumberToWordsConverter(number_format((float)$gtotal, 2, '.', '')) . '***', '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(700, 0, 'TERMS AND CONDITIONS: ', '', 'L');
    PDF::MultiCell(700, 0, '-100% Payment acceptance of Quotation.', '', 'L');
    PDF::MultiCell(700, 0, '-Validity of the Quotation is seven (7) days.', '', 'L');
    PDF::MultiCell(700, 0, '-Quoted fees will be subject to any change without prior notice.', '', 'L');
    PDF::MultiCell(700, 0, "-RETURNED GOODS will not be accepted after seven (7) days from the date of receipt. A charge of 10% will be added on all returned goods upon customer's request to cover handling expenses.", '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(253, 0, 'Received the above goods in good order and condition.', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    
    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(20, 0,  'By: ', '', 'L', false, 0);
    PDF::MultiCell(203, 0, $params['params']['dataparams']['received'], 'B', 'L', false, 0);
    PDF::MultiCell(30, 0,  '', '', 'L', false);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_so_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
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
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_so_PDF($params, $data)
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
    $this->default_so_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;


    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {



        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);

        $maxrow = 1;

        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          
          PDF::MultiCell(100, 0, number_format($data[$i]['qty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(250, 0, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['gross'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, $data[$i]['disc'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $qty = number_format($data[$i]['qty'], $decimalqty);
              $uom = $data[$i]['uom'];
              $gross = number_format($data[$i]['gross'], $decimalprice);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], $decimalprice);
            } else {
              $qty = '';
              $uom = '';
              $gross = '';
              $disc = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 0, $qty, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(250, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $gross, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $disc, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ext, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, '', '', 'R', false, 1, '', '', false, 0);
          }
        }

        $totalext += $data[$i]['ext'];



        if (intVal($i) + 1 == $page) {
          $this->default_so_header_PDF($params, $data);
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
          $string = $dictionary[(int)$number];
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

}
