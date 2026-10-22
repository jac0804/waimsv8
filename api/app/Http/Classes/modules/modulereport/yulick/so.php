<?php

namespace App\Http\Classes\modules\modulereport\yulick;

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

  public function __construct($params = null)
  {
    $this->fieldClass = new txtfieldClass($params);
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->reportheader = new reportheader;
  }

  // Fit To Paper dapat ang setup sa printer
  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'radiorepamountformat', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radiorepamountformat.options', [
      ['label' => 'SO with Price', 'value' => '0', 'color' => 'orange'],
      ['label' => 'SO without Price', 'value' => '1', 'color' => 'orange'],
      ['label' => 'Agathon Trading', 'value' => '002', 'color' => 'orange'],
      ['label' => 'Buena Gente Import Incorporated', 'value' => '003', 'color' => 'orange'],
      ['label' => 'Cemceir Trading Corp', 'value' => '004', 'color' => 'orange'],
      ['label' => 'CG Agriventures, Inc', 'value' => '005', 'color' => 'orange'],
      ['label' => 'D`Oragons General Goods Company OPC', 'value' => '006', 'color' => 'orange'],
      ['label' => 'Gabrien Food Company', 'value' => '007', 'color' => 'orange'],
      ['label' => 'GCR Food Products Trading', 'value' => '008', 'color' => 'orange'],
      ['label' => 'Isarog Food Distributtion OPC', 'value' => '009', 'color' => 'orange'],
      ['label' => 'Jita Meat Corp.', 'value' => '010', 'color' => 'orange'],
      ['label' => 'Malboc Food Compony LTD.', 'value' => '011', 'color' => 'orange'],
      ['label' => 'Manunggal Trading OPC', 'value' => '012', 'color' => 'orange'],
      ['label' => 'MMC Import Export Company', 'value' => '013', 'color' => 'orange'],
      ['label' => 'Nevaeh Food Company', 'value' => '014', 'color' => 'orange'],
      ['label' => 'Orokke Distribution Company LTD.', 'value' => '015', 'color' => 'orange'],
      ['label' => 'Shokuryohin Philippines Inc.', 'value' => '016', 'color' => 'orange'],
      ['label' => 'Yasui Kakaku Grocery OPC', 'value' => '017', 'color' => 'orange'],
    ]);
    data_set($col1, 'radiorepamountformat.label', 'Price Format');
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
    $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);
    $paramstr = "select
      'PDFM' as print,
      '0' as amountformat,
      '' as prepared,
      '' as approved,
      '' as received";
    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($trno)
  {
    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,cust.tin,cust.clientpref as conaddr,cust.vendorname as rfno,head.docno,head.trno,case when cl.isparentname = 1 then parentcode.clientname else  head.clientname end as clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,coalesce(nullif(trim(stock.rem), ''), item.itemname) as subclass,stock.isamt as amt,cust.bstyle,cust.registername,head.yourref,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,((stock.isamt * stock.isqty) - stock.ext) as disc_amount,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname
      from sohead as head 
      left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client as cl on cl.client=head.client
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join client as parentcode on cl.grpcode = parentcode.client
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,cust.tin,cust.clientpref as conaddr,cust.vendorname as rfno,head.docno,head.trno, case when cl.isparentname = 1 then parentcode.clientname else  head.clientname end as clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,coalesce(nullif(trim(stock.rem), ''), item.itemname) as subclass,stock.isamt as amt,cust.bstyle,cust.registername,head.yourref,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,((stock.isamt * stock.isqty) - stock.ext) as disc_amount,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client as cl on cl.client=head.client
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
       left join client as parentcode on cl.grpcode = parentcode.client
      where head.doc='so' and head.trno='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  // public function reportplotting($params, $data)
  // {
  //   $print = $params['params']['dataparams']['print'];
  //   $reporttype = $params['params']['dataparams']['amountformat'];

  //   switch ($print) {
  //     case 'PDFM':
  //       switch ($reporttype) {
  //         case '002': //AGATHON TRADING
  //           return $this->agathon_layout_PDF($params, $data);
  //           break;
  //         case '003': //BUENA GENTE IMPORT INCORPORATED
  //           return $this->buena_layout_PDF($params, $data);
  //           break;
  //         case '004': //CEMCEIR TRADING CORP
  //           return $this->cemceir_layout_PDF($params, $data);
  //           break;
  //         case '005': //CG AGRIVENTUIRES, INC
  //           return $this->cgagri_layout_PDF($params, $data);
  //           break;
  //         case '006': //D`ORAGONS GENERAL GOODS COMPANY OPC
  //           return $this->doragons_layout_PDF($params, $data);
  //           break;
  //       }
  //       break;
  //     default:
  //       return $this->default_so_PDF($params, $data);
  //       break;
  //   }
  // }

  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['amountformat'];

    switch ($reporttype) {
      case '002': //AGATHON TRADING
        return $this->agathon_layout_PDF($params, $data);
        break;
      case '003': //BUENA GENTE IMPORT INCORPORATED
        return $this->buena_layout_PDF($params, $data);
        break;
      case '004': //CEMCEIR TRADING CORP
        return $this->cemceir_layout_PDF($params, $data);
        break;
      case '005': //CG AGRIVENTUIRES, INC
        return $this->cgagri_layout_PDF($params, $data);
        break;
      case '006': //D`ORAGONS GENERAL GOODS COMPANY OPC
        return $this->doragons_layout_PDF($params, $data);
        break;
      case '007': //GABRIEN FOOD COMPANY
        return $this->gfc_layout_PDF($params, $data);
        break;
      case '008': //GCR FOOD PRODUCTS TRADING
        return $this->gcr_layout_PDF($params, $data);
        break;
      case '009': //ISAROG TRADING
        return $this->isarog_layout_PDF($params, $data);
        break;
      case '010': //JITA MEAT CORP.
        return $this->jita_layout_PDF($params, $data);
        break;
      case '011': //MALBOC FOOD COMPANY
        return $this->malboc_layout_PDF($params, $data);
        break;
      case '012': //MANUNGGAL TRADING OPC
        return $this->manunggal_layout_PDF($params, $data);
        break;
      case '013': //MMC IMPORT EXPORT COMPANY
        return $this->mmc_layout_PDF($params, $data);
        break;
      case '014': //NEVAH FOOD COMPANY
        return $this->nevah_layout_PDF($params, $data);
        break;
      case '015': //OROKKE DISTRIBUTION COMPANY LTD.
        return $this->orokke_layout_PDF($params, $data);
        break;
      case '016': //SHOKURYO PHILIPPINES INC.
        return $this->shokuryo_layout_PDF($params, $data);
        break;
      case '017': //YASUI KAKAKU GROCERY OPC
        return $this->yasui_layout_PDF($params, $data);
        break;
      default:
        return $this->default_so_PDF($params, $data);
    }
  }


  public function default_so_header_PDF($params, $data)
  {
    $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(500, 0, "", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Document # : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Notes : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 20, "Sales Person : ", '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);

    if ($amtformat == '0') {
      PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
      PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
      PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
      PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
      PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
      PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
      PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
    } else {
      PDF::MultiCell(120, 0, "BARCODE", '', 'C', false, 0);
      PDF::MultiCell(70, 0, "QTY", '', 'C', false, 0);
      PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
      PDF::MultiCell(440, 0, "DESCRIPTION", '', 'L', false);
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_so_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $amtformat = $params['params']['dataparams']['amountformat'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_so_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

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
        if ($amtformat == '0') {
          $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        } else {
          $arr_itemname = $this->reporter->fixcolumn([$itemname], '80', 0);
        }
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          if ($amtformat == '0') {
            PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          } else {
            PDF::MultiCell(120, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(440, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }


          if (PDF::getY() > 900) {
            $this->default_so_header_PDF($params, $data);
          }
        }
        $totalext += $data[$i]['ext'];
      }

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', 'B');

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', '');

      if ($amtformat == '0') {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
        PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');
      }

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

  private function formatQty($number)
  {
    return rtrim(rtrim(number_format($number, 2), '0'), '.');
  }

  public function agathon_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
    $fontsize2 = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetXY(15, 142);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(240, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(45, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");
    PDF::SetXY(15, 165);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(240, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(80, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 190);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(155, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(145, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(90, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 212);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(205, 20, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(65, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
  }

  public function agathon_layout_PDF($params, $data)
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
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->agathon_header_PDF($params, $data);

    PDF::SetY(268);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 11;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '25', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(15,  25, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(48,  25, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(50,  25, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(185, 25, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70,  25, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(95,  25, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(15, 15, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(48, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(185, 15, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(95, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetY(516);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(368, 0, '', '', 'R', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::SetY(558);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(368, 0, '', '', 'R', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function buena_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
    $fontsize2 = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetXY(15, 143);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(240, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(55, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(115, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '45', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 3); // two lines only, line 3 and beyond are never printed

    PDF::SetXY(15, ($maxrow > 1 ? 160 : 163));
    PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(85, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(85, 0, '', '', 'R', false, 0);
        PDF::MultiCell(240, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      } else {
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(85, 0, '', '', 'R', false, 0);
        PDF::MultiCell(240, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      }
    }


    PDF::SetXY(400, 163);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(90, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetXY(15, 185);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(135, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(175, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 207);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(200, 20, 'Retail Trade', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 20, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(85, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(75, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
  }

  public function buena_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totaldisc = 0;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->buena_header_PDF($params, $data);

    // PDF::SetY(268);
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 8;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(35,  25, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(50,  25, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(50,  25, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(210, 25, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70,  25, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(85,  25, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
          $totaldisc += $data[$i]['disc_amount'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(35, 25, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 25, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 25, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(210, 25, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 25, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 25, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['disc_amount'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }


    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetY(462);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0);
    PDF::MultiCell(213, 0, '******NOTHING FOLLOWS******', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', '', 'R');

    PDF::SetY(488);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0);
    PDF::MultiCell(213, 0, 'VENDOR CODE : ' . (isset($data[0]['rfno']) ? $data[0]['rfno'] : ''), '', 'L', false, 0);
    // PDF::MultiCell(200, 0, 'VENDOR CODE : '. (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'R', false, 0);
    PDF::MultiCell(200, 0, '', '', 'R');

    PDF::SetY(500);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0);
    PDF::MultiCell(213, 0, 'SODEXO : ' . (isset($data[0]['conaddr']) ? $data[0]['conaddr'] : ''), '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', '', 'R');

    //Right Side
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(415, 480);
    PDF::MultiCell(85, 5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(415, 505);
    PDF::MultiCell(85, 5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(415, 530);
    PDF::MultiCell(85, 5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(415, 555);
    PDF::MultiCell(85, 5, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetXY(415, 580);
    PDF::MultiCell(85, 5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetXY(415, 605);
    PDF::MultiCell(85, 5, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetXY(415, 630);
    PDF::MultiCell(85, 5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    //Left Side
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(225, 530);
    PDF::MultiCell(65, 15, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetXY(225, 555);
    PDF::MultiCell(65, 15, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetXY(225, 580);
    PDF::MultiCell(65, 15, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetXY(225, 605);
    PDF::MultiCell(65, 15, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 10, 0, 0);
    PDF::SetCellPaddings(0, 0, 0, 0);

    // PDF::SetY(516);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(368, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R');

    // PDF::SetY(558);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(368, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function cemceir_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
    $fontsize2 = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    // PDF::MultiCell(0, 0, "\n");
    // PDF::MultiCell(0, 0, "\n");

    PDF::SetXY(435, 103);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(135, 20, (isset($data[0]['dateid']) ? date('F j, Y', strtotime($data[0]['dateid'])) : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetXY(15, 123);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(325, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(120, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '60', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    PDF::SetXY(15, ($maxrow > 1 ? 142.5 : 144.5));
    PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(85, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetCellPaddings(0, 0, 0, 0); // left,top,right,bottom
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(70, 20, '', '', 'R', false, 0);
        PDF::MultiCell(320, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      } else {
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(70, 20, '', '', 'R', false, 0);
        PDF::MultiCell(320, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      }
    }


    PDF::SetXY(460, 140);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(120, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 180);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(45, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(345, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
  }

  public function cemceir_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totaldisc = 0;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "15";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->cemceir_header_PDF($params, $data);

    // PDF::SetY(268);
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 11;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(17,  20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(60,  20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(60,  20, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(260, 20, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70,  20, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(93,  20, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
          $totaldisc += $data[$i]['disc_amount'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(17, 20, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 20, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(260, 20, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 20, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(93, 20, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['disc_amount'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }


    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    //Right Side
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(476, 467);
    PDF::MultiCell(93, 5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(476, 487);
    PDF::MultiCell(93, 5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(476, 507);
    PDF::MultiCell(93, 5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(476, 527);
    PDF::MultiCell(93, 5, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetXY(476, 547);
    PDF::MultiCell(93, 5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetXY(476, 567);
    PDF::MultiCell(93, 5, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetXY(476, 587);
    PDF::MultiCell(93, 5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    //Left Side
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(140, 510);
    PDF::MultiCell(100, 15, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetXY(140, 530);
    PDF::MultiCell(100, 15, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetXY(140, 550);
    PDF::MultiCell(100, 15, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetXY(140, 570);
    PDF::MultiCell(100, 15, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 10, 0, 0);
    PDF::SetCellPaddings(0, 0, 0, 0);

    // PDF::SetY(516);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(368, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R');

    // PDF::SetY(558);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(368, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function cgagri_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
    $fontsize2 = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    $registername = (isset($data[0]['registername']) ? $data[0]['registername'] : '');

    $arr_registername = $this->reporter->fixcolumn([$registername], '40', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_registername]);

    PDF::SetXY(15, ($maxrow > 1 ? 130 : 137)); // two lines: move up, one line: stays
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(105, 15, '', '', 'R', false, 0);
        PDF::MultiCell(270, 15, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 14);
        PDF::MultiCell(105, 20, '', '', 'R', false, 0);
        PDF::MultiCell(270, 20, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      }
    }
    PDF::SetXY(420, 135);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(115, 20, (isset($data[0]['dateid']) ? date('F j, Y', strtotime($data[0]['dateid'])) : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '38', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 3); // three lines only, line 4 and beyond are never printed

    PDF::SetXY(15, ($maxrow > 1 ? 157 : 163)); // two lines: move up, one line: stays
    PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(85, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 13.5);
        PDF::MultiCell(100, 10, '', '', 'R', false, 0);
        PDF::MultiCell(270, 10, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 14);
        PDF::MultiCell(100, 20, '', '', 'R', false, 0);
        PDF::MultiCell(270, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      }
    }

    PDF::SetXY(415, 157);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(105, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 180);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(400, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(105, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
  }

  public function cgagri_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totaldisc = 0;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->cgagri_header_PDF($params, $data);

    // PDF::SetY(268);
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 10;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(17,  25, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(57,  25, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(47,  25, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(270, 25, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70,  25, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(93,  25, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
          $totaldisc += $data[$i]['disc_amount'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(17, 25, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(57, 25, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(47, 25, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(270, 25, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 25, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(93, 25, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['disc_amount'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }


    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    //Right Side
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(460, 485);
    PDF::MultiCell(100, 15, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetXY(460, 510);
    PDF::MultiCell(100, 15, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetXY(460, 535);
    PDF::MultiCell(100, 15, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetXY(460, 565);
    PDF::MultiCell(100, 15, $sales2 != 0 ? number_format($sales2, 2)  : '', '', 'R', false);
    PDF::SetXY(460, 595);
    PDF::MultiCell(100, 15, $totalAmtDue != 0 ? number_format($totalAmtDue, 2)  : '', '', 'R', false);
    PDF::SetCellPaddings(0, 10, 0, 0);
    PDF::SetCellPaddings(0, 0, 0, 0);





    $rem = (isset($data[0]['rem']) ? $data[0]['rem'] : '');

    $arr_rem = $this->reporter->fixcolumn([$rem], '80', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_rem]);

    PDF::SetXY(15, 538);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, 'SODEXO IPMC : ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 20, (isset($data[0]['conaddr']) ? $data[0]['conaddr'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 555);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, 'VENDOR CODE : ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 20, (isset($data[0]['rfno']) ? $data[0]['rfno'] : '') . ' - ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function doragons_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
    $fontsize2 = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    $clientname = (isset($data[0]['clientname']) ? $data[0]['clientname'] : '');

    $arr_clientname = $this->reporter->fixcolumn([$clientname], '40', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_clientname]);

    PDF::SetXY(15, 117);
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(50, 20, '', '', 'R', false, 0);
        PDF::MultiCell(255, 20, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(50, 20, '', '', 'R', false, 0);
        PDF::MultiCell(255, 20, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false);
      }
    }
    PDF::SetXY(355, 115);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(115, 20, (isset($data[0]['dateid']) ? date('F j, Y', strtotime($data[0]['dateid'])) : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetXY(15, 132);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(375, 132);
    PDF::SetFont($font, '', $fontsize2);
    PDF::MultiCell(105, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // PDF::MultiCell(0, 0, "\n");

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '42', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    PDF::SetXY(15, 153);
    PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(85, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow == 2) {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(60, 0, '', '', 'R', false, 0);
        PDF::MultiCell(255, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(60, 20, '', '', 'R', false, 0);
        PDF::MultiCell(255, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      }
    }

    PDF::SetXY(15, 183);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 20, '', '', 'R', false, 0);
    PDF::MultiCell(200, 20, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::MultiCell(0, 0, "\n\n");

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
  }

  public function doragons_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totaldisc = 0;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->doragons_header_PDF($params, $data);

    // PDF::SetY(268);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 15;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(17,  15, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(37,  15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(35,  15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(253, 15, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(60,  15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(65,  15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
          $totaldisc += $data[$i]['disc_amount'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(17, 15, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(37, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(35, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(253, 15, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(65, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['disc_amount'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }


    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $amountDue = $netVatamt - $lessDisc;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    //Right Side
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(410, 507);
    PDF::MultiCell(63, 5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(410, 524);
    PDF::MultiCell(63, 5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(410, 543);
    PDF::MultiCell(63, 5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(410, 563);
    PDF::MultiCell(63, 5, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetXY(410, 593);
    PDF::MultiCell(63, 5, $amountDue != 0 ? number_format($amountDue, 2) : '', '', 'R', false);
    PDF::SetXY(410, 609);
    PDF::MultiCell(63, 5, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetXY(410, 627);
    PDF::MultiCell(63, 5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    //Left Side
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(140, 507);
    PDF::MultiCell(100, 15, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetXY(140, 523);
    PDF::MultiCell(100, 15, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetXY(140, 543);
    PDF::MultiCell(100, 15, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetXY(140, 563);
    PDF::MultiCell(100, 15, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 10, 0, 0);
    PDF::SetCellPaddings(0, 0, 0, 0);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function gfc_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
    $fontsize2 = 14;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    $clientname = (isset($data[0]['clientname']) ? $data[0]['clientname'] : '');

    $arr_clientname = $this->reporter->fixcolumn([$clientname], '50', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_clientname]);

    PDF::SetXY(15, 144.5);
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(70, 20, '', '', 'R', false, 0);
        PDF::MultiCell(360, 20, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(70, 20, '', '', 'R', false, 0);
        PDF::MultiCell(360, 20, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false);
      }
    }
    PDF::SetXY(355, 115);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 20, (isset($data[0]['dateid']) ? date('F j, Y', strtotime($data[0]['dateid'])) : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    $registername = (isset($data[0]['registername']) ? $data[0]['registername'] : '');
    $arr_registername = $this->reporter->fixcolumn([$registername], '30', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_registername]);

    PDF::SetXY(15, ($maxrow > 1 ? 159 : 165));
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(117, 7, '', '', 'R', false, 0);
        PDF::MultiCell(170, 7, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(117, 20, '', '', 'R', false, 0);
        PDF::MultiCell(170, 20, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      }
    }

    PDF::SetXY(15, 163);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(332, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 184);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(117, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(170, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 184);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(332, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '60', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    PDF::SetXY(15, ($maxrow > 1 ? 200 : 205));
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(117, 0, '', '', 'R', false, 0);
        PDF::MultiCell(330, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(117, 20, '', '', 'R', false, 0);
        PDF::MultiCell(330, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      }
    }

    PDF::MultiCell(0, 0, "\n\n");

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
  }

  public function gfc_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totaldisc = 0;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->gfc_header_PDF($params, $data);

    PDF::SetY(260);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 8;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
            PDF::MultiCell(13,  23, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(35,  23, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(218, 23, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(47,  23, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(55,  23, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(85,  23, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
          $totaldisc += $data[$i]['disc_amount'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::SetCellPaddings(0, 4, 0, 0); // left, top, right, bottom
          PDF::MultiCell(13, 23, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(35, 23, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(218, 23, ' ' . (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(47,  23, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(55, 23, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(85, 23, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['disc_amount'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }


    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $amountDue = $netVatamt - $lessDisc;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    //Right Side
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(374, 455);
    PDF::MultiCell(85, 5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(374, 475);
    PDF::MultiCell(85, 5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(374, 495);
    PDF::MultiCell(85, 5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(374, 516);
    PDF::MultiCell(85, 5, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetXY(374, 536);
    PDF::MultiCell(85, 5, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetXY(374, 556);
    PDF::MultiCell(85, 5, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetXY(374, 576);
    PDF::MultiCell(85, 5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    //Left Side
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(60, 455);
    PDF::MultiCell(200, 15, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetXY(60, 476);
    PDF::MultiCell(200, 15, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetXY(60, 490);
    PDF::MultiCell(200, 15, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetXY(60, 523);
    PDF::MultiCell(200, 15, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 10, 0, 0);
    PDF::SetCellPaddings(0, 0, 0, 0);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function gcr_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
    $fontsize2 = 14;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    $clientname = (isset($data[0]['clientname']) ? $data[0]['clientname'] : '');

    $arr_clientname = $this->reporter->fixcolumn([$clientname], '80', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_clientname]);

    PDF::SetXY(15, 150);
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 14);
        PDF::MultiCell(110, 20, '', '', 'R', false, 0);
        PDF::MultiCell(400, 20, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(110, 20, '', '', 'R', false, 0);
        PDF::MultiCell(400, 20, (isset($arr_clientname[$r]) ? $arr_clientname[$r] : ''), '', 'L', false);
      }
    }
    PDF::SetXY(410, 125);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 20, (isset($data[0]['dateid']) ? date('F j, Y', strtotime($data[0]['dateid'])) : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    $registername = (isset($data[0]['registername']) ? $data[0]['registername'] : '');
    $arr_registername = $this->reporter->fixcolumn([$registername], '80', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_registername]);

    PDF::SetXY(15, 170);
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 14);
        PDF::MultiCell(155, 20, '', '', 'R', false, 0);
        PDF::MultiCell(220, 20, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(155, 20, '', '', 'R', false, 0);
        PDF::MultiCell(220, 20, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      }
    }

    PDF::SetXY(15, 182);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(155, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(170, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(15, 182);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(405, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '70', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    PDF::SetXY(15, ($maxrow > 1 ? 197 : 202));
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(155, 0, '', '', 'R', false, 0);
        PDF::MultiCell(400, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(155, 20, '', '', 'R', false, 0);
        PDF::MultiCell(400, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      }
    }

    PDF::MultiCell(0, 0, "\n\n");

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
  }

  public function gcr_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totaldisc = 0;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11.9";
    $fontsize2 = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->gcr_header_PDF($params, $data);

    PDF::SetY(245);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 21;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '40', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::SetCellPaddings(0, 0.6, 0, 0); // left, top, right, bottom
            PDF::MultiCell(32,  10.9, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(254, 10.9, ' ' . strtoupper((isset($arr_subclass[$r]) ? $arr_subclass[$r] : '')), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(48,  10.9, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(52,  10.9, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(71,  10.9, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(100,  10.9, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          }
          $totalext += $data[$i]['ext'];
          $totaldisc += $data[$i]['disc_amount'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::SetCellPaddings(0, 0.6, 0, 0); // left, top, right, bottom
          PDF::MultiCell(32, 10.9, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(254, 10.9, ' ' . strtoupper((isset($arr_subclass[$r]) ? $arr_subclass[$r] : '')), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(48,  10.9, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(52,  10.9, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(71, 10.9, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 10.9, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['disc_amount'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }


    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $amountDue = $netVatamt - $lessDisc;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    //Right Side
    PDF::SetFont($fontbold, '', $fontsize2);
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(465, 572);
    PDF::MultiCell(100, 5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(465, 596);
    PDF::MultiCell(100, 5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(465, 606);
    PDF::MultiCell(100, 5, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetXY(465, 618);
    PDF::MultiCell(100, 5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function isarog_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 14;
    $fontsize2 = 14;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    // PDF::SetMargins(40, 40);

    $clientname = (isset($data[0]['clientname']) ? $data[0]['clientname'] : '');

    $arr_clientname = $this->reporter->fixcolumn([$clientname], '80', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_clientname]);

    PDF::SetXY(15, 147);
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(75, 20, '', '', 'R', false, 0);
        PDF::MultiCell(300, 20, strtoupper((isset($arr_clientname[$r]) ? $arr_clientname[$r] : '')), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(75, 20, '', '', 'R', false, 0);
        PDF::MultiCell(300, 20, strtoupper((isset($arr_clientname[$r]) ? $arr_clientname[$r] : '')), '', 'L', false);
      }
    }
    PDF::SetXY(374, 119);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 20, (isset($data[0]['dateid']) ? date('F j, Y', strtotime($data[0]['dateid'])) : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    $registername = (isset($data[0]['registername']) ? $data[0]['registername'] : '');
    $arr_registername = $this->reporter->fixcolumn([$registername], '60', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_registername]);

    PDF::SetXY(15, 166);
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(125, 20, '', '', 'R', false, 0);
        PDF::MultiCell(220, 20, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(125, 20, '', '', 'R', false, 0);
        PDF::MultiCell(220, 20, (isset($arr_registername[$r]) ? $arr_registername[$r] : ''), '', 'L', false);
      }
    }

    PDF::SetXY(15, 183);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(33, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '60', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    // PDF::SetXY(15, 200);
    PDF::SetXY(15, ($maxrow > 1 ? 197 : 200));
    PDF::SetFont($fontbold, '', $fontsize);
    for ($r = 0; $r < $maxrow; $r++) {
      if ($maxrow > 1) {
        PDF::SetFont($font, '', 12);
        PDF::MultiCell(130, 0, '', '', 'R', false, 0);
        PDF::MultiCell(355, 0, strtoupper((isset($arr_address[$r]) ? $arr_address[$r] : '')), '', 'L', false);
      } else {
        PDF::SetFont($font, '', 13);
        PDF::MultiCell(130, 20, '', '', 'R', false, 0);
        PDF::MultiCell(355, 20, strtoupper((isset($arr_address[$r]) ? $arr_address[$r] : '')), '', 'L', false);
      }
    }

    PDF::MultiCell(0, 0, "\n\n");

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');
  }

  public function isarog_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totaldisc = 0;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "13";
    $fontsize2 = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->isarog_header_PDF($params, $data);

    PDF::SetY(251);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    $maxRowsPerPage = 13;
    $rowCount = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        // $maxrow = 1;
        if ($rowCount >= $maxRowsPerPage) {
          break;
        }

        $barcode = $data[$i]['barcode'];
        $subclass = $data[$i]['subclass'];
        $qty = $this->formatQty($data[$i]['qty']);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_subclass = $this->reporter->fixcolumn([$subclass], '30', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_subclass, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        if (($rowCount + $maxrow) > $maxRowsPerPage) {
          $allowedRows = $maxRowsPerPage - $rowCount;

          for ($r = 0; $r < $allowedRows; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::SetCellPaddings(0, 3.8, 0, 0); // left, top, right, bottom
            PDF::MultiCell(17, 20.3, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(201, 20.3, ' ' . strtoupper((isset($arr_subclass[$r]) ? $arr_subclass[$r] : '')), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(63, 20.3, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(93, 20.3, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(95, 20.3, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
            PDF::SetCellPaddings(0, 0, 0, 0);
          }
          $totalext += $data[$i]['ext'];
          $totaldisc += $data[$i]['disc_amount'];
          break;
        }

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::SetCellPaddings(0, 3.8, 0, 0); // left, top, right, bottom
          PDF::MultiCell(17, 20.3, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(201, 20.3, ' ' . strtoupper((isset($arr_subclass[$r]) ? $arr_subclass[$r] : '')), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(63, 20.3, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(93, 20.3, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(95, 20.3, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetCellPaddings(0, 0, 0, 0);
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['disc_amount'];
        // $rowCount++;
        $rowCount += $maxrow;

        // if (PDF::getY() > 900) {
        //   $this->agathon_header_PDF($params, $data);
        // }
      }
    }

    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }


    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $amountDue = $netVatamt - $lessDisc;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 15, (isset($data[0]['rfno']) ? $data[0]['rfno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 15, (isset($data[0]['conaddr']) ? $data[0]['conaddr'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    //Right Side
    PDF::SetFont($fontbold, '', $fontsize2);
    PDF::SetCellPaddings(0, 5, 0, 0);
    PDF::SetXY(380, 525);
    PDF::MultiCell(95, 5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(380, 552);
    PDF::MultiCell(95, 5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(380, 577);
    PDF::MultiCell(95, 5, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetXY(380, 587);
    PDF::MultiCell(95, 5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function jita_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);

    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

    PDF::SetY(103);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(330, 0, $date, '', 'R', false);

    PDF::SetY(125);
    PDF::MultiCell(50, 18, '', '', 'R', false, 0);
    PDF::MultiCell(245, 18, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(50, 18, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'R', false);

    PDF::SetY(137);
    PDF::MultiCell(90, 18, '', '', 'R', false, 0);
    PDF::MultiCell(255, 18, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 10, '', '', 'R', false, 0);
    PDF::MultiCell(295, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);

    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '56', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(90, 8, '', '', 'R', false, 0);
      PDF::MultiCell(250, 8, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
    }
  }

  public function jita_layout_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->jita_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 13;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;

    PDF::SetY(215);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = number_format($data[$i]['qty'], 2);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '35', 0); //23
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {

        if ($rowPerPage == $maxRowsPerPage) {
          break 2;
        }
        $rowPerPage++;
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10,  12.8, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30,  12.8, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(166, 12.8, (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(33,  12.8, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(38,  12.8, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(68,  12.8, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  12.8, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

    //Right Side
    PDF::SetFont($fontbold, '', 9);
    PDF::SetXY(288, 395);
    PDF::MultiCell(75, 4, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetX(288);
    PDF::MultiCell(75, 4, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetX(288);
    PDF::MultiCell(75, 4, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetX(288);
    PDF::MultiCell(75, 4, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetX(288);
    PDF::MultiCell(75, 4, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetX(288);
    PDF::MultiCell(75, 2, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetXY(288, 460);
    PDF::MultiCell(75, 4, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);


    //Left Side
    PDF::SetXY(135, 397);
    PDF::MultiCell(65, 4, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetX(135);
    PDF::MultiCell(65, 4, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetX(135);
    PDF::MultiCell(65, 4, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetX(135);
    PDF::MultiCell(65, 4, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 9, 0, 0);
    PDF::SetX(135);
    PDF::MultiCell(65, 20, '', '', 'R', false);
    PDF::SetCellPaddings(0, 12, 0, 0);
    PDF::SetX(135);
    PDF::MultiCell(65, 20, '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function malboc_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsizeM = 0;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);

    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('F d, Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';
    $month = $date ? date('F', strtotime($date)) : '';
    $monthLength = strlen($month);

    if ($monthLength >= 7 && $monthLength <= 9) {
      $fontsizeM = 7;
    } elseif ($monthLength >= 5 && $monthLength <= 6) {
      $fontsizeM = 8;
    } elseif ($monthLength >= 3 && $monthLength <= 4) {
      $fontsizeM = 9;
    } else {
      $fontsizeM = 9;
    }

    $fontsizeM = 8;
    PDF::SetY(105);
    PDF::SetFont($fontbold, '',  $fontsizeM);
    PDF::MultiCell(270, 18, '', '', 'R', false, 0);
    PDF::MultiCell(82, 0, strtoupper($date), '', 'C', false);

    PDF::SetFont($fontbold, '',  10);
    PDF::SetY(137);
    PDF::MultiCell(90, 18, '', '', 'R', false, 0);
    PDF::MultiCell(255, 18, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false);

    PDF::SetY(150);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 10, '', '', 'R', false, 0);
    PDF::MultiCell(295, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);


    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces

    $arr_address = $this->reporter->fixcolumn([$address], '60', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(90, 8, '', '', 'R', false, 0);
      PDF::MultiCell(250, 8, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
    }
  }

  public function malboc_layout_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->malboc_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 10;
    $rowHeight = 0;
    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;

    PDF::SetY(207);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = $this->formatQty($data[$i]['qty']);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '33', 0); //27
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {
        if ($rowPerPage == $maxRowsPerPage) {
          break 2;
        }
        $rowPerPage++;
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10,  15.8, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(158, 15.8, (isset($arr_subclass[$r]) ? $arr_subclass[$r] : '') . ' ' . (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40,  15.8, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70,  15.8, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70,  15.8, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  15.8, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;



    //Remarks
    $rowHeight = ($maxRowsPerPage - $rowPerPage) * 15.9;
    if ($rowHeight > 0) {
      PDF::MultiCell(10,  $rowHeight, ' ', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 15, (isset($data[0]['rfno']) ? $data[0]['rfno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 15, (isset($data[0]['conaddr']) ? $data[0]['conaddr'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

    //Right Side
    PDF::SetFont($fontbold, '', 9);
    PDF::SetXY(290, 416);
    PDF::MultiCell(75, 15.9, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetX(290);
    PDF::MultiCell(75, 15.9, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetX(290);
    PDF::MultiCell(75, 15.9, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetX(290);
    PDF::MultiCell(75, 15.9, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function manunggal_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 0;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);

    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

    PDF::SetY(78);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(400, 0, $date, '', 'R', false);

    PDF::MultiCell(75, 20, '', '', 'R', false, 0);
    PDF::MultiCell(475, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

    PDF::MultiCell(75, 20, '', '', 'R', false, 0);
    PDF::MultiCell(475, 20, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 20, '', '', 'R', false, 0);
    PDF::MultiCell(90, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    PDF::MultiCell(55, 20, '', '', 'R', false, 0);
    PDF::MultiCell(110, 20, ' ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 0);
    PDF::MultiCell(20, 20, '', '', 'R', false, 0);
    PDF::MultiCell(80, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false);

    PDF::SetY(147);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $small = PDF::GetStringWidth($address) > 360;

    if ($small) {
      PDF::SetFont($font, '', 8);
      $arr_address = $this->reporter->fixcolumn([$address], '90', 0);
    } else {
      PDF::SetFont($font, '', 10);
      $arr_address = $this->reporter->fixcolumn([$address], '70', 0);
    }

    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    for ($r = 0; $r < $maxrow; $r++) {
      $currentY = PDF::GetY();
      if ($small && $maxrow > 1) { //if two rows
        PDF::MultiCell(75, 0, '', '', 'R', false, 0);
        PDF::MultiCell(360, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      } else if ($small && $maxrow == 1) {
        PDF::SetCellPaddings(0, 9, 0, 0);
        PDF::MultiCell(75, 20, '', '', 'R', false, 0);
        PDF::MultiCell(360, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      } else {
        PDF::SetCellPaddings(0, 6, 0, 0);
        PDF::MultiCell(75, 20, '', '', 'R', false, 0);
        PDF::MultiCell(360, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      }
      PDF::SetY($currentY + 9);
    }
  }

  public function manunggal_layout_PDF($params, $data)
  {
    $prepared = $params['params']['dataparams']['prepared'];
    $received = $params['params']['dataparams']['received'];
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->manunggal_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 13;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;

    PDF::SetY(190);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = number_format($data[$i]['qty'], 2);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '45', 0); //35
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {

        if ($rowPerPage == $maxRowsPerPage) { //when new page
          $this->manunggal_header_PDF($params, $data);
          $rowPerPage = 0; // reset
        }
        $rowPerPage++;
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10,  17, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30,  17, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(237, 17, (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(33,  17, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(58,  17, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75,  17, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  17, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

    //Right Side
    PDF::SetFont($fontbold, '', 10);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(380, 410);
    PDF::MultiCell(80, 5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetX(385);
    PDF::MultiCell(80, 5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetX(385);
    PDF::MultiCell(80, 5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 3, 0, 0);
    PDF::SetX(385);
    PDF::MultiCell(80, 5, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetX(385);
    PDF::MultiCell(80, 5, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetX(385);
    PDF::MultiCell(80, 5, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetX(385);
    PDF::MultiCell(80, 5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    //Left Side
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(225, 410);
    PDF::MultiCell(65, 15, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetX(225);
    PDF::MultiCell(65, 15, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetX(225);
    PDF::MultiCell(65, 15, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetX(225);
    PDF::MultiCell(65, 15, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 9, 0, 0);
    PDF::SetX(225);
    PDF::MultiCell(65, 20, '', '', 'R', false);
    PDF::SetCellPaddings(0, 12, 0, 0);
    PDF::SetX(225);
    PDF::MultiCell(65, 20, '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    PDF::SetFont($font, '', 9);
    PDF::SetXY(28, 487);
    PDF::MultiCell(55, 15, $prepared, '', 'L', false, 0);
    PDF::MultiCell(20, 15, '', '', 'L', false, 0);
    PDF::MultiCell(55, 15, $received, '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function mmc_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $small = 0;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);

    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';


    PDF::SetY(98);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(250, 0, '', '', 'R', false, 0);
    PDF::MultiCell(82, 0, $date, '', 'C', false);

    PDF::SetY(110);
    PDF::MultiCell(45, 10, '', '', 'R', false, 0);
    PDF::MultiCell(290, 10, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

    PDF::SetY(122);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $small = PDF::GetStringWidth($address) > 290; // If it width exceeds
    if ($small) {
      PDF::SetFont($font, '', 7);
      $arr_address = $this->reporter->fixcolumn([$address], '90', 0);
    } else {
      PDF::SetFont($font, '', 8);
      $arr_address = $this->reporter->fixcolumn([$address], '90', 0);
    }

    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    for ($r = 0; $r < $maxrow; $r++) {
      $currentY = PDF::GetY();
      if ($small && $maxrow > 1) { //if two rows
        PDF::setFontSpacing(-0.20);
        PDF::MultiCell(45, 10, '', '', 'R', false, 0);
        PDF::MultiCell(290, 10, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::setFontSpacing(0);
      } else if ($small && $maxrow == 1) {
        PDF::setFontSpacing(-0.20);
        PDF::SetCellPaddings(0, 9, 0, 0);
        PDF::MultiCell(45, 10, '', '', 'R', false, 0);
        PDF::MultiCell(290, 10, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
        PDF::setFontSpacing(0);
      } else {
        PDF::SetCellPaddings(0, 6, 0, 0);
        PDF::MultiCell(45, 10, '', '', 'R', false, 0);
        PDF::MultiCell(290, 10, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      }
      PDF::SetY($currentY + 6);
    }

    PDF::SetY(136);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, '', '', 'R', false, 0);
    PDF::MultiCell(195, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    PDF::MultiCell(20, 10, '', '', 'R', false, 0);
    PDF::MultiCell(75, 10, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'C', false);

    PDF::SetY(152);
    PDF::MultiCell(80, 10, '', '', 'R', false, 0);
    PDF::MultiCell(160, 10, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false, 0);
    PDF::MultiCell(20, 10, '', '', 'R', false, 0);
    PDF::MultiCell(75, 10, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'C', false);
  }

  public function mmc_layout_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->mmc_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 10;
    $rowHeight = 0;
    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;
    $amountDue = 0;

    PDF::SetY(184);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = $this->formatQty($data[$i]['qty']);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '24', 0); //20
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {
        if ($rowPerPage == $maxRowsPerPage) {
          break 2;
        }
        $rowPerPage++;
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(7,  17.2, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(28,  17.2, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40,  17.2, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(143, 17.2, (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(55,  17.2, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(64,  17.2, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  17.2, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $amountDue   = $netVatamt - $lessDisc;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat;


    //Right Side
    PDF::SetFont($fontbold, '', 11);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(273, 348);
    PDF::MultiCell(80, 17.5, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetX(273);
    PDF::MultiCell(80, 17.5, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetX(273);
    PDF::MultiCell(80, 17.5, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 3, 0, 0);
    PDF::SetX(273);
    PDF::MultiCell(80, 17.5, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetX(273);
    PDF::MultiCell(80, 17.5, $amountDue != 0 ? number_format($amountDue, 2) : '', '', 'R', false);
    PDF::SetX(273);
    PDF::MultiCell(80, 17.5, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetX(273);
    PDF::MultiCell(80, 17.5, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);
    PDF::SetCellPaddings(0, 0, 0, 0);

    //Left Side
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetXY(100, 384);
    PDF::MultiCell(80, 17.5, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetX(100);
    PDF::MultiCell(80, 17.5, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetX(100);
    PDF::MultiCell(80, 17.5, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetX(100);
    PDF::MultiCell(80, 17.5, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);

    PDF::SetCellPaddings(0, 0, 0, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function nevah_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);

    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';


    PDF::SetY(97);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(250, 0, '', '', 'R', false, 0);
    PDF::MultiCell(82, 0, $date, '', 'C', false);

    PDF::SetY(96);
    PDF::MultiCell(45, 10, '', '', 'R', false, 0);
    PDF::MultiCell(200, 10, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

    PDF::SetY(112);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // remove new lines/extra spaces
    $arr_address = $this->reporter->fixcolumn([$address], '40', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // two lines only, line 3 and beyond are never printed

    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(45, 0, '', '', 'R', false, 0);
      PDF::MultiCell(200, 10, trim(isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
    }
    PDF::SetFont($font, '',  $fontsize);
    PDF::SetXY(270, 110); //right
    PDF::MultiCell(80, 10, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'C', false);

    PDF::SetY(134);
    PDF::MultiCell(80, 10, '', '', 'R', false, 0);
    PDF::MultiCell(160, 10, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false);

    PDF::SetY(148);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(45, 10, '', '', 'R', false, 0);
    PDF::MultiCell(195, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);
  }

  public function nevah_layout_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->nevah_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 12;
    $rowHeight = 0;
    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;
    $amountDue = 0;

    PDF::SetY(184);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = $this->formatQty($data[$i]['qty']);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '25', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {
        if ($rowPerPage == $maxRowsPerPage) {
          break 2;
        }
        $rowPerPage++;
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(7,  13, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30,  13, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(45,  13, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(141, 13, (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(56,  13, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(65,  13, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  13, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $amountDue   = $netVatamt - $lessDisc;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat;


    //Right Side
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetXY(283, 361);
    PDF::MultiCell(80, 13, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetX(283);
    PDF::MultiCell(80, 13, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetX(283);
    PDF::MultiCell(80, 13, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetX(283);
    PDF::MultiCell(80, 13, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetX(283);
    PDF::MultiCell(80, 13, $amountDue != 0 ? number_format($amountDue, 2) : '', '', 'R', false);
    PDF::SetX(283);
    PDF::MultiCell(80, 13, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetX(283);
    PDF::MultiCell(80, 13, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

    //Left Side
    PDF::SetXY(155, 389);
    PDF::MultiCell(80, 13, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetX(155);
    PDF::MultiCell(80, 13, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetX(155);
    PDF::MultiCell(80, 13, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetX(155);
    PDF::MultiCell(80, 13, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function orokke_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);
    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('m.d.Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

    PDF::SetY(97);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(250, 0, '', '', 'R', false, 0);
    PDF::MultiCell(82, 0, $date, '', 'C', false);

    PDF::SetY(111);
    PDF::MultiCell(45, 10, '', '', 'R', false, 0);
    PDF::MultiCell(310, 10, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

    PDF::SetY(121);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $address = trim(preg_replace('/\s+/', ' ', $address)); // trim the newline/extra spaces
    $small = PDF::GetStringWidth($address) > 200; // If it width exceeds
    if ($small) {
      PDF::SetFont($font, '', 8);
      $arr_address = $this->reporter->fixcolumn([$address], '50', 0);
    } else {
      PDF::SetFont($font, '', 10);
      $arr_address = $this->reporter->fixcolumn([$address], '40', 0);
    }
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    $maxrow = min($maxrow, 2); // print only 2 lines max

    for ($r = 0; $r < $maxrow; $r++) {
      if ($small && $maxrow > 1) { //if two rows
        PDF::MultiCell(45, 0, '', '', 'R', false, 0);
        PDF::MultiCell(200, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
      } else if ($small && $maxrow == 1) {
        PDF::SetCellPaddings(0, 9, 0, 0);
        PDF::MultiCell(45, 20, '', '', 'R', false, 0);
        PDF::MultiCell(200, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      } else {
        PDF::SetCellPaddings(0, 6, 0, 0);
        PDF::MultiCell(45, 20, '', '', 'R', false, 0);
        PDF::MultiCell(200, 20, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
        PDF::SetCellPaddings(0, 0, 0, 0);
      }
    }

    PDF::SetFont($font, '',  $fontsize);
    PDF::SetXY(279, 127); //right
    PDF::MultiCell(40, 10, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'C', false, 0);
    PDF::MultiCell(20, 10, '', '', 'C', false, 0);
    PDF::MultiCell(40, 10, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'C', false);

    PDF::SetY(140);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(45, 10, '', '', 'R', false, 0);
    PDF::MultiCell(190, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);

    PDF::SetY(153);
    PDF::MultiCell(80, 10, '', '', 'R', false, 0);
    PDF::MultiCell(155, 10, (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false);
  }

  public function orokke_layout_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->orokke_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 12;
    $rowHeight = 0;
    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;
    $amountDue = 0;

    PDF::SetY(190);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = $this->formatQty($data[$i]['qty']);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '20', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {
        if ($rowPerPage == $maxRowsPerPage) {
          break 2;
        }
        $rowPerPage++;
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10,  14.8, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30,  14.8, (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(37,  14.8, (isset($arr_uom[$r])      ? $arr_uom[$r]      : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(175, 14.8, (isset($arr_subclass[$r]) ? $arr_subclass[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(53,  14.8, (isset($arr_amt[$r])      ? $arr_amt[$r]      : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(53,  14.8, (isset($arr_ext[$r])      ? $arr_ext[$r]      : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  14.8, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $amountDue   = $netVatamt - $lessDisc;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat;


    //Right Side
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetXY(296, 367);
    PDF::MultiCell(80, 14.8, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetX(296);
    PDF::MultiCell(80, 14.8, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetX(296);
    PDF::MultiCell(80, 14.8, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetX(296);
    PDF::MultiCell(80, 14.8, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);
    PDF::SetX(296);
    PDF::MultiCell(80, 14.8, $amountDue != 0 ? number_format($amountDue, 2) : '', '', 'R', false);
    PDF::SetX(296);
    PDF::MultiCell(80, 14.8, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetX(296);
    PDF::MultiCell(80, 14.8, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);


    //Left Side
    PDF::SetXY(135, 396);
    PDF::MultiCell(80, 14.8, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetX(135);
    PDF::MultiCell(80, 14.8, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);
    PDF::SetX(135);
    PDF::MultiCell(80, 14.8, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetX(135);
    PDF::MultiCell(80, 14.8, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function shokuryo_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);
    $width = 0;
    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('F d, Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

    PDF::SetY(87);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(290, 0, '', '', 'R', false, 0);
    PDF::MultiCell(130, 0, strtoupper($date), '', 'L', false);

    PDF::SetY(112);
    PDF::MultiCell(50, 18, '', '', 'R', false, 0);
    PDF::MultiCell(295, 18, strtoupper((isset($data[0]['clientname']) ? $data[0]['clientname'] : '')), '', 'L', false);

    PDF::SetY(124);
    PDF::MultiCell(90, 18, '', '', 'R', false, 0);
    PDF::MultiCell(255, 18, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false);

    PDF::SetY(137);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $arr_address = $this->reporter->fixcolumn([$address], '57', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', 11);
      if ($r == 0) { //indent
        PDF::MultiCell(90, 10, '', '', 'R', false, 0);
        $width = 310;
      } else {
        $arr_address = $this->reporter->fixcolumn([$address], '45', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        $width = 230;
      }
      PDF::MultiCell($width, 10, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
    }
    PDF::SetXY(280, 150);
    PDF::MultiCell(150, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);
  }

  public function shokuryo_layout_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->shokuryo_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 13;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;

    PDF::SetY(192);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = number_format($data[$i]['qty'], 2);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '35', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {

        if ($rowPerPage == $maxRowsPerPage) {
          break 2;
        }
        $rowPerPage++;
        $currentY = PDF::GetY();
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10,  11, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30,  11, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(199, 11, strtoupper((isset($arr_subclass[$r]) ? $arr_subclass[$r] : '')), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(45,  11, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(45,  11, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(83,  11, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  11, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        PDF::SetY($currentY + 13);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

    //Right Side
    PDF::SetFont($fontbold, '', 11);
    $currentY = PDF::GetY();

    PDF::SetXY(354, 362);
    PDF::MultiCell(75, 11, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(354, 377);
    PDF::MultiCell(75, 11, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(354, 390);
    PDF::MultiCell(75, 11, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetXY(354, 401);
    PDF::MultiCell(75, 11, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);

    PDF::SetXY(354, 419);
    PDF::MultiCell(75, 11, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetXY(354, 428);
    PDF::MultiCell(75, 11, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetXY(354, 442);
    PDF::MultiCell(75, 11, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

    //Left Side
    PDF::SetXY(135, 366);
    PDF::MultiCell(65, 11, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetXY(135, 379);
    PDF::MultiCell(65, 11, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetXY(135, 389);
    PDF::MultiCell(65, 11, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetXY(135, 402);
    PDF::MultiCell(65, 11, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function yasui_header_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(20, 20);
    $width = 0;
    $date = '';
    $date = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') ? date('F d, Y', strtotime((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''))) : '';

    PDF::SetY(92);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(290, 0, '', '', 'R', false, 0);
    PDF::MultiCell(130, 0, strtoupper($date), '', 'L', false);

    PDF::SetY(115);
    PDF::MultiCell(50, 18, '', '', 'R', false, 0);
    PDF::MultiCell(295, 18, strtoupper((isset($data[0]['clientname']) ? $data[0]['clientname'] : '')), '', 'L', false);

    PDF::SetY(128);
    PDF::MultiCell(90, 18, '', '', 'R', false, 0);
    PDF::MultiCell(255, 18, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false);

    PDF::SetY(142);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $arr_address = $this->reporter->fixcolumn([$address], '55', 0);
    $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', 11);
      if ($r == 0) { //indent
        PDF::MultiCell(90, 10, '', '', 'R', false, 0);
        $width = 310;
      } else {
        $arr_address = $this->reporter->fixcolumn([$address], '45', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
        PDF::MultiCell(10, 10, '', '', 'R', false, 0);
        $width = 230;
      }
      PDF::MultiCell($width, 10, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false);
    }
    PDF::SetXY(280, 155);
    PDF::MultiCell(150, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false);
  }

  public function yasui_layout_PDF($params, $data)
  {
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIALNB.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
    }
    $this->yasui_header_PDF($params, $data);
    $counted = count($data);
    $rowPerPage = 0;
    $totalext = 0;
    $totaldisc = 0;
    $maxRowsPerPage = 13;

    $sales1 = 0;
    $sales2 = 0;
    $sales3 = 0;
    $vat = 0;
    $netVatamt = 0;
    $lessVat = 0;
    $lessDisc = 0;
    $addVat = 0;
    $lessWithholdingTax = 0;
    $totalAmtDue = 0;

    PDF::SetY(194);
    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 1;

      $uom = $data[$i]['uom'];
      $subclass = $data[$i]['subclass'];
      $qty = number_format($data[$i]['qty'], 2);
      $amt = number_format($data[$i]['amt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_subclass = $this->reporter->fixcolumn([$subclass], '35', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_uom, $arr_subclass, $arr_qty, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {

        if ($rowPerPage == $maxRowsPerPage) {
          break 2;
        }
        $rowPerPage++;
        $currentY = PDF::GetY();
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(10,  11, '', '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(30,  11, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(199, 11, strtoupper((isset($arr_subclass[$r]) ? $arr_subclass[$r] : '')), '',  'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(46,  11, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '',  'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(45,  11, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '',  'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(81,  11, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(5,  11, '', '',  'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        PDF::SetY($currentY + 13);
      }
      $totalext += $data[$i]['ext'];
      $totaldisc += $data[$i]['disc_amount'];
    }
    $vattype = isset($data[0]['vattype']) ? $data[0]['vattype'] : '';
    $ewtrate = isset($data[0]['ewtrate']) ? $data[0]['ewtrate'] : 0;

    if ($vattype == 'VATABLE') {
      $vat = $totalext / 1.12 * 0.12;
      $netVat = $totalext / 1.12;
      $lessWithholdingTax = $netVat * ($ewtrate / 100);
      $sales1 = $totalext;
    } else if ($vattype == 'NON-VATABLE') {
      $vat = 0;
      $sales2 = $totalext;
    } else if ($vattype == 'ZERO-RATED') {
      $vat = 0;
      $sales3 = $totalext;
    }

    $lessVat = $vat;
    $addVat = $lessVat;
    $lessDisc = $totaldisc;
    $netVatamt = $totalext - $lessVat;
    $lessWithholdingTax = 0;
    $totalAmtDue = $netVatamt - $lessDisc + $addVat - $lessWithholdingTax;

    //Right Side
    PDF::SetFont($fontbold, '', 11);
    $currentY = PDF::GetY();

    PDF::SetXY(354, 367);
    PDF::MultiCell(75, 11, $totalext != 0 ? number_format($totalext, 2) : '', '', 'R', false);
    PDF::SetXY(354, 380);
    PDF::MultiCell(75, 11, $lessVat != 0 ? number_format($lessVat, 2) : '', '', 'R', false);
    PDF::SetXY(354, 393);
    PDF::MultiCell(75, 11, $netVatamt != 0 ? number_format($netVatamt, 2) : '', '', 'R', false);
    PDF::SetXY(354, 406);
    PDF::MultiCell(75, 11, $totaldisc != 0 ? number_format($totaldisc, 2) : '', '', 'R', false);

    PDF::SetXY(354, 423);
    PDF::MultiCell(75, 11, $addVat != 0 ? number_format($addVat, 2) : '', '', 'R', false);
    PDF::SetXY(354, 434);
    PDF::MultiCell(75, 11, $lessWithholdingTax != 0 ? number_format($lessWithholdingTax, 2) : '', '', 'R', false);
    PDF::SetXY(354, 445);
    PDF::MultiCell(75, 11, $totalAmtDue != 0 ? number_format($totalAmtDue, 2) : '', '', 'R', false);

    //Left Side
    PDF::SetXY(135, 370);
    PDF::MultiCell(65, 11, $sales1 != 0 ? number_format($sales1, 2) : '', '', 'R', false);
    PDF::SetXY(135, 383);
    PDF::MultiCell(65, 11, $vat != 0 ? number_format($vat, 2) : '', '', 'R', false);
    PDF::SetXY(135, 393);
    PDF::MultiCell(65, 11, $sales3 != 0 ? number_format($sales3, 2) : '', '', 'R', false);
    PDF::SetXY(135, 406);
    PDF::MultiCell(65, 11, $sales2 != 0 ? number_format($sales2, 2) : '', '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
