<?php

namespace App\Http\Classes\modules\modulereport\md;

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

  private $modulename = "Purchase Order";
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
    $fields = ['radioprint','approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {

  $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);


    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $ispurchases = $this->companysetup->getispurchases($config['params']);
    if($systemtype=='FAMS' && $ispurchases){
      $paramstr = "select
      'PDFM' as print,
      '' as barcode,
      '' as itemdesc,
      '' as plbyitemlookup,
      '' as prepared,
      '$approved' as approved ";
    }else{
      $paramstr = "select
      'PDFM' as print, 
      '' as prepared,
      '$approved' as approved ";
    }
    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($trno)
  {
    $query = "select head.wh, stock.ref,  date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.partno, item.barcode, head.terms, head.tel, head.createby,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join prhead as pr  on head.trno = pr.trno
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno=$trno
        union all
        select head.wh, stock.ref, date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.partno, item.barcode, head.terms, head.tel, head.createby,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
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
    PDF::AddPage('p', 'LETTER');
    PDF::SetMargins(35, 35);
    PDF::SetXY(0, 0);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    // PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::MultiCell(0, 10, "\n\n\n");

    $logo1 = public_path('images/metrodragon/mdlogo.png');
    if (file_exists($logo1)) { //temp logo
      PDF::Image($logo1, 40, 25, 60, 37);
    }

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(50, 0, '', '', 'C', false, 0);
    PDF::MultiCell(300, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(50, 0, '', '', 'C', false, 0);
    PDF::MultiCell(300, 0, strtoupper($headerdata[0]->address), '', 'C'); 
    PDF::MultiCell(50, 0, '', '', 'C', false, 0);
    PDF::MultiCell(300, 0, 'Tel. # '.strtoupper($headerdata[0]->tel), '', 'C', false, 0);
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(300, 0, 'No. '. (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(0, 0, '', '', 'C', false);

    PDF::SetLineWidth(5);
    PDF::SetFont($font, '', 6);
    PDF::MultiCell(350, 0, '', 'B', 'C', false, 0);
    PDF::SetFont($fontbold, '', 13  );
    PDF::MultiCell(140, 0, 'PURCHASE ORDER', '', 'C', false, 0); 
    PDF::SetFont($font, '', 6);
    PDF::MultiCell(50, 0, '', 'B', 'C', false);

    PDF::MultiCell(0, 10, '', '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(40, 0, '', '', 'C', false, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(60, 0, 'SUPPLIER', '', 'C', false, 0);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(200, 0, '', '', 'C', false);

    PDF::SetLineWidth(0.5);
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(40, 10, '', 'LT', 'C', false, 0);
    PDF::SetFont($font, '', 10  );
    PDF::MultiCell(60, 10, '', '', 'C', false, 0);
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(240, 10, '', 'TR', 'C', false, 0);
    PDF::MultiCell(30, 10, '', '', 'C', false, 0);//space
    PDF::MultiCell(170, 10, '', 'LTR', 'C', false);
    
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(60, 0, 'NAME', 'B', 'L', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(260, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0); //name varaible
    PDF::MultiCell(10, 0, '', 'R', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);//space
    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(60, 0, 'DATE', 'B', 'L', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0); //date varaible
    PDF::MultiCell(10, 0, '', 'R', 'C', false);

    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(60, 0, 'ADDRESS', 'B', 'L', false, 0);
    PDF::SetFont($font, '', 10);
    $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
    $addressLines = array_pad(explode("\n", wordwrap($address, 50, "\n", true)),4,'');//split

    PDF::MultiCell(260, 0, $addressLines[0], 'B', 'L', false, 0);//address variable
    PDF::MultiCell(10, 0, '', 'R', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);//space
    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(60, 0, 'TERMS', 'B', 'L', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(90, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0); // terms variable
    PDF::MultiCell(10, 0, '', 'R', 'C', false);

    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(320, 0, $addressLines[1], 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);//space
    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', 'C', false);

    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(60, 0, 'PHONE', 'B', 'L', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(260, 0, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false, 0); //contact number variable
    PDF::MultiCell(10, 0, '', 'R', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);//space
    PDF::MultiCell(10, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, '', 'R', 'C', false);

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(340, 0, '', 'LBR', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);//sapce
    PDF::MultiCell(170, 0, '', 'LBR', 'C', false);

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(700, 0, '', '');
    PDF::SetFont($font, '', 10);  

    PDF::MultiCell(90, 0, 'PR NO.', 'LBT', 'C', false, 0);
    PDF::MultiCell(40, 0, 'QTY.', 'LBT', 'C', false, 0);
    PDF::MultiCell(40, 0, 'UNITS', 'LBT', 'C', false, 0);
    PDF::MultiCell(90, 0, 'ITEM CODE', 'LBT', 'C', false, 0);
    PDF::MultiCell(150, 0, 'DESCRIPTION', 'LBT', 'C', false, 0);
    PDF::MultiCell(60, 0, 'UNIT PRICE', 'LBT', 'C', false, 0);
    PDF::MultiCell(70, 0, 'AMOUNT', 'LBTR', 'C', false);
    //space
    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(90, 0, ' ', 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(40, 0, ' ', 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(40, 0, ' ', 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(90, 0, ' ', 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(150, 0, ' ', 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(60, 0, ' ', 'L', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(70, 0, ' ', 'LR', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);

  }
  public function default_PO_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $totalext = 0;

    $font = "";
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_PO_header_PDF($params, $data);
    $rem = '';

    $counted = count($data);
    $row = 0;
    $rowPerPage = 0;
    $maxRowsPerPage = 12;

    for ($i = 0; $i < ($counted); $i++) {
      $maxrow = 2;

      $prno = $data[$i]['ref'];
      $qty = number_format($data[$i]['qty'], 2);
      $uom = $data[$i]['uom'];
      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $amt = number_format($data[$i]['netamt'], 2);
      $ext = number_format($data[$i]['ext'], 2);
      $rem = isset($data[$i]['rem']) ? $data[$i]['rem'] : '';

      $arr_prno = $this->reporter->fixcolumn([$prno], '15', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_prno, $arr_barcode,$arr_qty, $arr_uom, $arr_amt, $arr_ext]);
      for ($r = 0; $r < $maxrow; $r++) {
        if ($rowPerPage == $maxRowsPerPage) { //when new page
          $this->footer_layout($params, $data, $totalext, $decimalcurr);
          $this->default_PO_header_PDF($params, $data);
          $rowPerPage = 0; // reset
        }
        $rowPerPage++;
        $row++;
        PDF::SetFont($font, '', $fontsize);
        
        PDF::MultiCell(90, 15, ' ' . (isset($arr_prno[$r]) ? $arr_prno[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(40, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(90, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(150, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(60, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'L', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'LR', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

        if ($maxRowsPerPage<= $rowPerPage) {
          $this->footer_layout($params, $data, $totalext, $decimalcurr);
          $this->default_PO_header_PDF($params, $data);
          $rowPerPage = 0;

        }
      }
      $totalext += $data[$i]['ext'];
    }
    $width = 470;
    $rowHeight = ($maxRowsPerPage - $rowPerPage) * 15;

    if ($rem != ''){
      PDF::MultiCell($width, $rowHeight, 'Remarks: '. $rem, 'TL', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(70, $rowHeight, number_format($totalext, $decimalcurr) , 'LTR', 'R', false);
    }else {
      PDF::MultiCell(90, $rowHeight, ' ' , 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(40, $rowHeight, ' ' , 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(40, $rowHeight, ' ' , 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(90, $rowHeight, ' ', 'L', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(150, $rowHeight, ' ' , 'L', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(60, $rowHeight, ' ' , 'L', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(70, $rowHeight, ' ', 'LR', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
    }
    
    $this->footer_layout($params, $data, $totalext, $decimalcurr);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  public function footer_layout($params, $data,  $totalext, $decimalcurr)
  {
    $approved = $params['params']['dataparams']['approved'];
    $font = "";
    $fontbold = "";
    $fontsize = 9;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(390, 20, '', 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 20, 'TOTAL: ', 'TB', 'R', false, 0);
    PDF::MultiCell(70, 20, number_format($totalext, $decimalcurr), 'TBLR', 'R');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(10, 20, '', 'L', '', false, 0);
    PDF::MultiCell(260, 20, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(260, 20, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(10, 20, '', 'R', '', false);

    PDF::MultiCell(540, 10, '', 'LR', 'L', false);

    PDF::MultiCell(60, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(155, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), 'B', 'C', false, 0);
    PDF::MultiCell(110, 0, '', '', 'C', false, 0);
    PDF::MultiCell(155, 0, $approved, 'B', 'C', false, 0);
    PDF::MultiCell(60, 0, '', 'R', 'C', false);

    PDF::SetFont($fontbold, '', 4);
    PDF::MultiCell(540, 5, '', 'LBR', 'C', false);

    PDF::SetFont($font, '', 6);
    PDF::MultiCell(180, 5, 'FORM CODE:'. (isset($data[0]['wh']) ? $data[0]['wh'] : ''), '', 'L', false, 0);
    PDF::MultiCell(240, 5, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 7);
    PDF::MultiCell(120, 5, 'ORIGINAL COPY - SUPPLIER', '', 'L', false);


    PDF::SetFont($font, '', 6);
    PDF::MultiCell(80, 5, 'REV.', '', 'L', false, 0);
    PDF::MultiCell(340, 5, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 7);
    PDF::MultiCell(120, 5, 'DUPLICATE COPY - ACCOUNTING', '', 'L', false);
  }




}
