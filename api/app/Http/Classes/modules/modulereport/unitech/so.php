<?php

namespace App\Http\Classes\modules\modulereport\unitech;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class so
{

  private $modulename = "Sales Order";
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

    $fields = ['radioprint', 'radiorepamountformat', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radiorepamountformat.options', [
      ['label' => 'SO with Price', 'value' => '0', 'color' => 'orange'],
      ['label' => 'SO without Price', 'value' => '1', 'color' => 'orange'],
      ['label' => 'Delivery Label', 'value' => '2', 'color' => 'orange'],
      ['label' => 'Delivery Label With Logo', 'value' => '3', 'color' => 'orange'],
      ['label' => 'Unserved Items with price', 'value' => '4', 'color' => 'orange'],
      ['label' => 'Unserved Items without price', 'value' => '5', 'color' => 'orange']
    ]);
    data_set($col1, 'radiorepamountformat.label', 'Price Format');


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
          '' as received,
          '0' as amountformat";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config, $trno)
  {
    $format = $config['params']['dataparams']['amountformat'];
    $addfilter="";
    $addf=",stock.isqty as qty, stock.ext ";
    
    if($format == '4' || $format == '5'){ //unserved items
          $addfilter=" and stock.qa <> stock.iss and stock.void = 0";
          $addf = ", (stock.iss - stock.qa) / uom.factor as qty, (((stock.iss - stock.qa) / uom.factor) * stock.isamt) as ext ";
        }

    switch ($format) {
      case '2':
      case '3':
        $query = "select head.client,head.clientname,item.barcode,item.itemname,date(head.due) as due,
                        stock.isqty,stock.iss,head.yourref as ponum,stock.uom,uom.factor,
                        (case when uom.factor = 1 then 'default' else 'other' end) as chkfactor
                  from sohead as head
                  left join sostock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
                  where head.trno= '$trno' $addfilter
                  union all
                  select head.client,head.clientname,item.barcode,item.itemname,date(head.due) as due,
                        stock.isqty,stock.iss,head.yourref as ponum,stock.uom,uom.factor,
                        (case when uom.factor = 1 then 'default' else 'other' end) as chkfactor
                  from hsohead as head
                  left join hsostock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
                  where head.trno= '$trno' $addfilter";
        break;

      default:
        $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, 
                     head.address, date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
                     item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, 
                     stock.uom, stock.disc, stock.line,item.brand,
                     client.clientname as whname,item.sizeid,m.model_name as model, 
                     left(agent.clientname,7) as agentname,stock.rem as notes,stock.iss,
                     ifnull((select 1 from uom where itemid=stock.itemid and uom 
                     in ('PC','PCS','PIECE','PIECES','pc','pcs','piece','pieces') limit 1),0) as uompcs,uom.factor  $addf
              from sohead as head 
              left join sostock as stock on stock.trno=head.trno 
              left join item on item.itemid=stock.itemid
              left join client as agent on agent.client=head.agent
              left join model_masterfile as m on m.model_id = item.model
              left join client on client.client=head.wh
              left join client as cust on cust.client = head.client
              left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
              where head.trno='$trno' $addfilter
              union all
              select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, 
                     head.address, date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
                     item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, 
                     stock.uom, stock.disc, stock.line,item.brand,
                     client.clientname as whname,item.sizeid,m.model_name as model, 
                     left(agent.clientname,7) as agentname,stock.rem as notes,stock.iss,
                     ifnull((select 1 from uom where itemid=stock.itemid and uom 
                     in ('PC','PCS','PIECE','PIECES','pc','pcs','piece','pieces') limit 1),0) as uompcs,uom.factor  $addf
              from hsohead as head 
              left join hsostock as stock on stock.trno=head.trno
              left join item on item.itemid=stock.itemid 
              left join client as agent on agent.client=head.agent
              left join model_masterfile as m on m.model_id = item.model
              left join client on client.client=head.wh
              left join client as cust on cust.client = head.client
              left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
              where head.doc='so' and head.trno='$trno' $addfilter order by line";

        break;
    }
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    $format = $params['params']['dataparams']['amountformat'];

    switch ($format) {
      case '2':
        return $this->default_deliverylabel_PDF($params, $data);
        break;
      case '3':
        return $this->default_deliverylabel_withlogo_PDF($params, $data);
        break;

      default:
        $sotrno = empty($data) ? 0 : $data[0]['trno'];
         if ($sotrno == 0) {
          return $this->no_trans();
          } else {
          return $this->default_so_PDF($params, $data);
          }
        break;
    }
  }


  public function default_so_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14.5;
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
    PDF::SetMargins(30, 30);

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
    PDF::MultiCell(735, 0, '', 'T');

    PDF::SetFont($font, 'B', 11);


    if ($amtformat == '0' || $amtformat == '4') {
      PDF::MultiCell(70, 0, "BARCODE", '', 'L', false, 0);
      PDF::MultiCell(50, 0, "QTY", '', 'R', false, 0);
      PDF::MultiCell(70, 0, "UNIT", '', 'R', false, 0);
      PDF::MultiCell(60, 0, "PCS / PACK", '', 'C', false, 0);
      PDF::MultiCell(70, 0, "TOTAL PCS", '', 'R', false, 0);
      PDF::MultiCell(5, 0, "", '', 'L', false, 0);
      PDF::MultiCell(185, 0, "DESCRIPTION", '', 'L', false, 0);
      PDF::MultiCell(60, 0, "NOTES", '', 'L', false, 0);
      PDF::MultiCell(65, 0, "UNIT PRICE", '', 'R', false, 0);
      PDF::MultiCell(40, 0, "(+/-) %", '', 'R', false, 0);
      PDF::MultiCell(75, 0, "TOTAL", '', 'R', false);
    } else {
      PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
      PDF::MultiCell(65, 0, "QTY", '', 'C', false, 0);
      PDF::MultiCell(65, 0, "UNIT", '', 'C', false, 0);
      PDF::MultiCell(70, 0, "PCS / PACK", '', 'R', false, 0);
      PDF::MultiCell(90, 0, "TOTAL PCS", '', 'R', false, 0);
      PDF::MultiCell(10, 0, "", '', 'L', false, 0);
      PDF::MultiCell(285, 0, "DESCRIPTION", '', 'L', false, 0);
      PDF::MultiCell(90, 0, "NOTES", '', 'L', false);
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(735, 20, '', 'B');
  }

  public function default_so_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $amtformat = $params['params']['dataparams']['amountformat'];
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
    $fontsize = "14.5";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
  
    $this->default_so_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(800, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $factor = number_format($data[$i]['factor'], 2);
        $iss = number_format(($data[$i]['qty'] * $data[$i]['factor']), 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['gross'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $notes = $data[$i]['notes'];

        if ($amtformat == '0' || $amtformat == '4') {
          $arr_barcode = $this->reporter->fixcolumn([$barcode], '7', 0);
          $arr_itemname = $this->reporter->fixcolumn([$itemname], '18', 0);
          $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
          $arr_factor = $this->reporter->fixcolumn([$factor], '10', 0);
          $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
          $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
          $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
          $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
          $arr_ext = $this->reporter->fixcolumn([$ext], '10', 0);
          $arr_notes = $this->reporter->fixcolumn([$notes], '10', 0);

          $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_factor, $arr_iss, $arr_uom, $arr_amt, $arr_disc, $arr_ext, $arr_notes]);
        } else {
          $arr_barcode = $this->reporter->fixcolumn([$barcode], '10', 0);
          $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
          $arr_qty = $this->reporter->fixcolumn([$qty], '10', 0);
          $arr_factor = $this->reporter->fixcolumn([$factor], '10', 0);
          $arr_iss = $this->reporter->fixcolumn([$iss], '10', 0);
          $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);
          $arr_notes = $this->reporter->fixcolumn([$notes], '18', 0);

          $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_factor, $arr_iss, $arr_uom, $arr_notes]);
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);

          if ($amtformat == '0' || $amtformat == '4') {
            PDF::MultiCell(70, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(60, 15, ' ' . (isset($arr_factor[$r]) ? $arr_factor[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(5, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(185, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(60, 15, ' ' . (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(65, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(40, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(75, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          } else {
            PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(65, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(65, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_factor[$r]) ? $arr_factor[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(90, 15, ' ' . (isset($arr_iss[$r]) ? $arr_iss[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(10, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(285, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(90, 15, ' ' . (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          }

          if (PDF::getY() > 900) {
            $this->default_so_header_PDF($params, $data);
          }
        }
        $totalext += $data[$i]['ext'];
      }

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(735, 0, '', 'B');

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(735, 0, '', '');

      if ($amtformat == '0' || $amtformat == '4') {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(605, 15, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(130, 0, number_format($totalext, $decimalcurr) . ' ', '', 'R');
      }

      PDF::SetFont($font, '', $fontsize);

      PDF::MultiCell(0, 0, "\n\n\n");


      PDF::MultiCell(245, 0, 'Prepared By: ', '', 'L', false, 0);
      PDF::MultiCell(245, 0, 'Approved By: ', '', 'L', false, 0);
      PDF::MultiCell(245, 0, 'Received By: ', '', 'L');

      PDF::MultiCell(0, 0, "\n");

      PDF::MultiCell(245, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
      PDF::MultiCell(245, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
      PDF::MultiCell(245, 0, $params['params']['dataparams']['received'], '', 'L');


      return PDF::Output($this->modulename . '.pdf', 'S');
    
  }
  }

  public function default_deliverylabel_PDF($params, $data)
  {
    $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 900]);
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, "\n\n");
    $bqty = 0;
    $bqty2 = 0;
    for ($i = 0; $i < count($data); $i++) {

      switch ($data[$i]['chkfactor']) {
        case 'other':
          $qty = ($data[$i]['iss'] / $data[$i]['factor']);
          $bqty = $data[$i]['factor'];
          $pbqty = $data[$i]['factor'];
          $result = ceil($qty); // 15

          for ($k = 1; $k <= $result; $k++) {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(80, 20, " CLIENT ", 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(340, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, "", 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(120, 20, "PO# ", 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 20, (isset($data[0]['ponum']) ? $data[0]['ponum'] : ''), 'TR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            $this->addrowspace();

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(80, 20, " ITEM ", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(340, 20, (isset($data[$i]['barcode']) ? $data[$i]['barcode'] . '-' . $data[$i]['itemname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(120, 20, "PACK NO ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 20, $k . '/' . $result, 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            $this->addrowspace();

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(80, 20, " DEL DATE ", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(340, 20, (isset($data[0]['due']) ? $data[0]['due'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(120, 20, "QUANTITY/PACK ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            if ($bqty > $data[$i]['iss']) {
              $pbqty = $data[$i]['iss'] - $bqty2;
            }
            PDF::MultiCell(100, 20, number_format($pbqty, 0), 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            $this->addrowspace();

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(80, 20, " QUANTITY ", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(340, 20, number_format($data[$i]['iss']), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(120, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 20, '', 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            $this->addrowspace();

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(80, 20, " FROM ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(340, 20, 'UNITECH PLASTIC INDUSTRY CORP.', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, "", 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(120, 20, "", 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 20, '', 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            PDF::MultiCell(0, 0, "\n\n");
            $bqty += $data[$i]['factor'];
            $bqty2 += $data[$i]['factor'];

            if (PDF::getY() > 800) {
              PDF::MultiCell(0, 50, "\n\n\n\n\n\n\n\n");
            }
          }

          break;

        default:
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(80, 20, " CLIENT ", 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(340, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(50, 20, "", 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(120, 20, "PO# ", 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 20, (isset($data[0]['ponum']) ? $data[0]['ponum'] : ''), 'TR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

          $this->addrowspace();

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(80, 20, " ITEM ", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(340, 20, (isset($data[$i]['barcode']) ? $data[$i]['barcode'] . '-' . $data[$i]['itemname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(120, 20, "PACK NO ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 20, '1/1', 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

          $this->addrowspace();

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(80, 20, " DEL DATE ", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(340, 20, (isset($data[0]['due']) ? $data[0]['due'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(120, 20, "QUANTITY/PACK ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 20, number_format($data[$i]['iss']), 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

          $this->addrowspace();

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(80, 20, " QUANTITY ", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(340, 20, number_format($data[$i]['iss']), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(120, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 20, '', 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

          $this->addrowspace();

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(80, 20, " FROM ", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(340, 20, 'UNITECH PLASTIC INDUSTRY CORP.', 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(50, 20, "", 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(120, 20, "", 'B', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 20, '', 'BR', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

          PDF::MultiCell(0, 0, "\n\n");
          break;
      }

      if (PDF::getY() > 900) {
        PDF::MultiCell(0, 50, "\n\n\n\n\n\n\n\n");
      }
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrowspace()
  {
    PDF::MultiCell(50, 5, '', '', 'L', false, 0);
    PDF::MultiCell(690, 5, '', 'LR', 'L', false);
  }
  public function default_deliverylabel_withlogo_PDF($params, $data)
  {
    $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 7;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 900]);
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, "\n\n");

    // for image
    // file path
    // horizontal position (mm or user units)
    // vertical position
    // width of the image
    // height of the image



    // PDF::Image(public_path('images/unitech/upic_logo.png'), 90, 15, 150, 150);288, 432
    //192 / 4
    // 432
    $bqty = 0;
    $bqty2 = 0;
    $vertical = 65;
    $horizontal = 50;
    for ($i = 0; $i < count($data); $i++) {

      $x = PDF::GetX();
      $y = PDF::GetY();

      switch ($data[$i]['chkfactor']) {
        case 'other':
          $qty = ($data[$i]['iss'] / $data[$i]['factor']);
          $bqty = $data[$i]['factor'];
          $pbqty = $data[$i]['factor'];
          $result = ceil($qty); // 15

          for ($k = 1; $k <= $result; $k++) {

            PDF::Image(public_path('images/unitech/upic_logo.png'), $x + $horizontal, $y, 100, 100);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'TR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

            $this->left_right_line($params, 10);

            #150/2 =

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, 'CLIENT ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(110, 10, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, 'ITEM ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(110, 10, (isset($data[$i]['barcode']) ? $data[$i]['barcode'] . '-' . $data[$i]['itemname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, 'DEL DATE ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(110, 10, (isset($data[0]['due']) ? $data[0]['due'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);


            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, 'QTY ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(110, 10, number_format($data[$i]['iss']), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, 'SO# ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(110, 10, (isset($data[0]['ponum']) ? $data[0]['ponum'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, 'PACK NO ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(110, 10, $k . '/' . $result, 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, 'QTY./PACK ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', 5);

            if ($bqty > $data[$i]['iss']) {
              $pbqty = $data[$i]['iss'] - $bqty2;
            }
            PDF::MultiCell(110, 10, $pbqty, 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
            PDF::MultiCell(40, 10, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true); //FROM 
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(110, 10, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true); //UNITECH PLASTIC INDUSTRY CORP.
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

            $this->left_right_line($params, 9);

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(48, 10, '', 'T', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);



            if (PDF::getY() > 700) {
              PDF::AddPage();
              PDF::MultiCell(0, 50, "\n\n\n\n\n");
            }
          }

          break;

        default:

          PDF::Image(public_path('images/unitech/upic_logo.png'), $x + $horizontal, $y, 100, 100);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'TR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

          $this->left_right_line($params, 10);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, 'CLIENT ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(110, 10, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, 'ITEM ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(110, 10, (isset($data[$i]['barcode']) ? $data[$i]['barcode'] . '-' . $data[$i]['itemname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, 'DEL DATE ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(110, 10, (isset($data[0]['due']) ? $data[0]['due'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);


          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, 'QTY ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(110, 10, number_format($data[$i]['iss']), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, 'SO# ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(110, 10, (isset($data[0]['ponum']) ? $data[0]['ponum'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, 'PACK NO ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(110, 10, '1/1', 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, 'QTY./PACK ', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', 5);

          $pbqty = 0;
          if ($bqty > $data[$i]['iss']) {
            $pbqty = $data[$i]['iss'] - $bqty2;
          }
          PDF::MultiCell(110, 10, $pbqty, 'B', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'L', 'R', false, 0, '',  '', true, 0, false, true, 15, 'M', true);
          PDF::MultiCell(40, 10, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true); //FROM 
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(110, 10, '', '', 'L', false, 0, '',  '', true, 0, false, true, 15, 'M', true); //UNITECH PLASTIC INDUSTRY CORP.
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(21, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 15, 'M', true);

          $this->left_right_line($params, 9);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(48, 10, '', 'T', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
          break;
      }

      if (PDF::getY() > 700) {
        PDF::AddPage();
        PDF::MultiCell(0, 50, "\n\n\n\n\n");
      }
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function left_right_line($config, $line)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 7;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    for ($i = 0; $i < $line; $i++) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(48, 10, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(48, 10, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(48, 10, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(48, 10, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    }
  }

    public function no_trans()
  {
    $font = "";
    $fontbold = "";
    $fontsize = "11";

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

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(500, 0, 'No transactions.', '', 'L', false);
    PDF::SetFont($font, '', 5);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
