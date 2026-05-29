<?php

namespace App\Http\Classes\modules\modulereport\yulick;

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
use App\Http\Classes\reportheader;

class po
{

  private $modulename = "PURCHASE ORDER";
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
    $fields = ['radioprint','prepared','checked','approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
  $paramstr = "select
  'PDFM' as print, 
  '' as prepared,
  '' as checked,
  '' as approved ";
    return $this->coreFunctions->opentable($paramstr);
  }
  public function report_default_query($trno)
  {
    $query = "select head.wh, stock.ref,  date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, client.vattype, 
        head.terms,head.rem, item.partno, concat(left(item.barcode, 2), right(item.barcode, 5)) as barcode, head.terms, client.tel, head.createby, client.tin, client.contact, client.email,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid, head.shipto, date(head.deldate) as deldate
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join prhead as pr  on head.trno = pr.trno
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno=$trno
        union all
        select head.wh, stock.ref, date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, client.vattype,
        head.terms,head.rem, item.partno, concat(left(item.barcode, 2), right(item.barcode, 5)) as barcode, head.terms, client.tel, head.createby, client.tin, client.contact, client.email,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid, head.shipto, date(head.deldate) as deldate
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join prhead as pr  on head.trno = pr.trno
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po'and head.trno=$trno 
        ";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    return $this->default_PO_PDF($params, $data);
  }
  public function default_PO_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $font = "";
    $fontbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
    }
    
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(55, 55);
    PDF::SetXY(0, 0);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 10, "\n\n\n");

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(500, 0, ' '.strtoupper($headerdata[0]->name), 'LTR', 'L', false);
    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(500, 0, ' '.strtoupper($headerdata[0]->address), 'LR', 'L', false);
    PDF::MultiCell(500, 0, "\n",'LR');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(500, 0, $this->modulename, 'LR', 'R', false);

    PDF::SetFont($font, '', 8);
    PDF::Cell(100, 15, ' NAME OF SUPPLIER: ', 'L', 0, 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::Cell(280, 15, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 0, 'L');
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(30, 15, 'P.O.# ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(90, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'R', 'C', false);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(50, 0, ' ADDRESS: ', 'L', 'L', false, 0);
    PDF::MultiCell(300, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);
    PDF::MultiCell(60, 0, 'ORDER DATE:', '', 'R', false, 0);
    PDF::MultiCell(90, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'R', 'C', false);

    PDF::MultiCell(100, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    PDF::MultiCell(90, 0, '', 'R', 'C', false);

    PDF::MultiCell(50, 10, ' TIN', 'L', 'L', false, 0);
    PDF::MultiCell(250, 10, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    PDF::MultiCell(110, 10, 'DELIVERY DATE:', '', 'R', false, 0);
    PDF::MultiCell(90, 10, (isset($data[0]['deldate']) ? $data[0]['deldate'] : ''), 'R', 'C', false);
    
    PDF::MultiCell(50, 10, ' TEL. NO.', 'L', 'L', false, 0);
    PDF::MultiCell(250, 10, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), '', 'L', false, 0);
    PDF::MultiCell(110, 10, 'TERMS:', '', 'R', false, 0);
    PDF::MultiCell(90, 10, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'R', 'C', false);
    
    PDF::MultiCell(100, 10, ' CONTACT PERSION ', 'L', 'L', false, 0);
    PDF::MultiCell(400, 10, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), 'R', 'L', false);
    PDF::MultiCell(50, 10, ' EMAIL', 'L', 'L', false, 0);
    PDF::MultiCell(450, 10, (isset($data[0]['email']) ? $data[0]['email'] : ''), 'R', 'L', false);
    PDF::MultiCell(500, 10, "\n", 'LR'); 

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(45, 20, 'Item No.', 'LT', 'C', false, 0);
    PDF::MultiCell(55, 0, 'Item Code', 'LT', 'C', false, 0);
    PDF::MultiCell(140, 0, 'ITEM DESCRIPTION', 'LBT', 'C', false, 0);
    PDF::MultiCell(50, 0, 'UOM', 'LT', 'C', false, 0);
    PDF::MultiCell(40, 0, 'QTY', 'LT', 'C', false, 0);
    PDF::MultiCell(60, 20, 'UNIT COST', 'LT', 'C', false, 0);
    PDF::MultiCell(40, 0, 'DISC%', 'LT', 'C', false, 0);
    PDF::MultiCell(70, 0, 'TOTAL COST', 'LTR', 'C', false, 1);
  }
  public function default_PO_PDF($params, $data)
  {
    $totalext = 0;

    $font = "";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
    }
    $this->default_PO_header_PDF($params, $data);
    $counted = count($data);
    $row = 0;
    $rowPerPage = 0;
    $maxRowsPerPage = 20;
    $count = 0;
    $vat = 0;
    $totalvat = 0;
    $totalnonvat = 0;
    $totalnet = 0;
    $vat12 = 0;
    $totalvat12 = 0;

    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 2;
      $count ++;
      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $uom = $data[$i]['uom'];
      $qty = number_format($data[$i]['qty'], 2);
      $amt = number_format($data[$i]['netamt'], 2);
      $disc = isset($data[$i]['disc']) ? $data[$i]['disc'] : '';
      $ext = number_format($data[$i]['ext'], 2);
      
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '15', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_barcode,$arr_qty, $arr_uom, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {
        
        if ($rowPerPage == $maxRowsPerPage) { //when new page
          $this->footer_layout($params, $data, $totalext, $totalnet, $totalvat12);
          $this->default_PO_header_PDF($params, $data);
          $rowPerPage = 0; // reset
        }
        $rowPerPage++;
        $row++;
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(45, 10,($r == 0 ? $count : '') , 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(55, 10,(isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(140, 10,(isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'TL', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 10,(isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 10,(isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(60, 10,(isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'TL', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 10,(isset($arr_disc[$r]) ? $arr_disc[$r] : ''), 'TLR', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 10,(isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'TLR', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        if ($maxRowsPerPage<= $rowPerPage) {
          $this->footer_layout($params, $data, $totalext, $totalnet, $totalvat12);
          $this->default_PO_header_PDF($params, $data);
          $rowPerPage = 0;
        }
      }

      if ($data[$i]['vattype'] == 'VATABLE') {
        $vat = ($data[$i]['ext']/1.12);  
        $vat12 = $data[$i]['ext'] - $vat;
        $totalvat += $vat;
        $totalvat12 += $vat12;
      }else if ($data[$i]['vattype'] == 'NON-VATABLE') {
        $totalnonvat += $data[$i]['ext'];
      }



      $totalext += $data[$i]['ext'];
    }
    $totalnet += ($totalvat + $totalnonvat);

    $emptyRows = $maxRowsPerPage - $rowPerPage;
    for ($i = 0; $i < $emptyRows; $i++) {
    PDF::MultiCell(45, 10, ' ' , 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(55, 10, ' ' , 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(140, 10, ' ' , 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(50, 10, ' ', 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(40, 10, ' ' , 'TL', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(60, 10, ' ' , 'TL', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(40, 10, ' ' , 'TL', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(70, 10, ' ', 'TLR', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    }
    $this->footer_layout($params, $data, $totalext, $totalnet, $totalvat12);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function footer_layout($params, $data,  $totalext, $totalnet, $totalvat12)
  {
    $prepared = $params['params']['dataparams']['prepared'];
    $checked = $params['params']['dataparams']['checked'];
    $approved = $params['params']['dataparams']['approved'];

    $pagenumber = 'PAGE ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages();
    
    $font = "";
    $fontbold = "";
    $fontsize = 8;
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.TTF');
    }
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 12, 'Delivery Address' , 'TL', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(210, 12, (isset($data[0]['shipto']) ? $data[0]['shipto'] : '') , 'TL', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(90, 12, 'TOTALNET' , 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(70, 12, number_format($totalnet, 2) , 'TLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 12, 'Delivery Day & Time' , 'TL', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(210, 12, 'MONDAY TO FRIDAY 8:00 am - 3:00 pm' , 'TL', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($font, '', 9);

    PDF::MultiCell(90, 12, 'VAT 12%' , 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(70, 12, $totalvat12 == 0 ? '' : number_format($totalvat12, 2) , 'TLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($fontbold, '', 8);
    PDF::SetCellPaddings(0, 8, 0, 0); // left, top, right, bottom
    PDF::MultiCell(130, 20, 'REMARKS' , 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);  
    PDF::MultiCell(210, 20, (isset($data[0]['rem']) ? $data[0]['rem'] : '') , 'TL', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(90, 20, 'TOTAL AMOUNT ' , 'TL', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, number_format($totalext, 2) , 'TLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetCellPaddings(0, 0, 0, 0); 
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(180, 10, 'PREPARED BY/DATE' , 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(150, 10, 'CHECKED BY/DATE', 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(170, 10, 'APPROVED BY/DATE', 'TLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::SetCellPaddings(0, 20, 0, 0); // left, top, right, bottom
    PDF::MultiCell(180, 30, $prepared , 'TL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(150, 30, $checked, 'TL', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(170, 30, $approved, 'TLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetCellPaddings(0, 0, 0, 0); 
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(500, 10, ' 1. Delivery personnel must bring the original Sale invoice and Delivery Receipt together with photo copy of signed PO' , 'TLR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(500, 10, ' 2. Only original copy of SI and original DR are being left to the receiver.' , 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(500, 10, ' 3. Notify us immediately if you are unable to deliver as specified' , 'LR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(500, 10, ' 4. Advise us converning delivery of any Back Order covered by this P.O.' , 'BLR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(500, 10, $pagenumber , 'TBLR', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
  }




}
