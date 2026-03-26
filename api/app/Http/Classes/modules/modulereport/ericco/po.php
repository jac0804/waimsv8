<?php

namespace App\Http\Classes\modules\modulereport\ericco;

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
use App\Http\Classes\reportheader;
use DateTime;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'prepared', 'approved', 'received','attention'];
    $col1 = $this->fieldClass->create($fields);
     data_set($col1, 'attention.type', 'input');
    //  data_set($col1, 'attention.class', 'csattention');
       data_set($col1, 'attention.readonly', false);
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
   
        $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received,
           '' as attention";

    return $this->coreFunctions->opentable($paramstr);
  }
  // qwe @123qwE123
  public function report_default_query($trno)
  {
    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.partno, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid,client.fax
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.partno, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid,client.fax
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
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
    $companyid = $params['params']['companyid'];

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
    $companyid = $params['params']['companyid'];
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

    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_PO_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];


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
    PDF::Image($this->companysetup->getlogopath($params['params']) . 'ericco_logo.jpg', '40', '15', 200, 60);//x,y,width,height

    // $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(0, 0, '', '', 'L');
  
     $faxno='';
    $fax= (isset($data[0]['fax']) ? $data[0]['fax'] : '');
    if($fax !=''){
      $faxno='Fax No. ' .strtoupper($fax);
    }
    // $y = PDF::getY();
    PDF::SetXY(40, 27.50);
    // $this->reportheader->getheader($params);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(220, 0, '', '', 'C',false,0);
		PDF::MultiCell(400, 0, $headerdata[0]->address, '', 'L',false,0);
    PDF::MultiCell(100, 0,  'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(220, 0, '', '', 'C',false,0);
		PDF::MultiCell(400, 0, strtoupper($headerdata[0]->tel). ' '.$faxno, '', 'L',false,0);
    PDF::MultiCell(100, 0,  '', '', 'R', false, 1);
				

    PDF::MultiCell(0, 0, "\n\n");
    

    PDF::SetXY(40, 82.50);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 0, $this->modulename, '', 'C', false, 1);

    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('F d, Y');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 20, "Supplier", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 20,':', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 20, (isset($data[0]['client']) ? $data[0]['client'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 20, "PO No", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(160, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 20, "Supplier", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 20,':', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 20, "Date", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(160, 20, $datehere, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    $attention= $params['params']['dataparams']['attention'];    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 20, "Fax No.", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 20,':', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 20, (isset($data[0]['fax']) ? $data[0]['fax'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(70, 20, "Attention", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(160, 20, $attention, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', 'T');

     PDF::SetXY(40, 171.35);
     PDF::SetFont($font, 'B', $fontsize);

        PDF::MultiCell(50, 20, "Qty.", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, "Unit", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 20, "Item Code", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(200, 20, "Item Description", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 20, "Price", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 20, "Discount", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 20, "Net Price", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(80, 20, "Amount", 'T', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

        PDF::SetFont($font, '', 1);
        PDF::MultiCell(45, 0, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(40, 0, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(90, 0, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(190, 0, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 0, "", 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 0, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(70, 0, "", 'T', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(10, 0, "", '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 0, "", 'T', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
   
  }

  public function default_PO_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
 
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_PO_header_PDF($params, $data);
  
    PDF::SetFont($font, '', 5);
    PDF::MultiCell( 720, 0, '', '');

    $rowCount = 0;
    $page = 46;
    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $partno = $data[$i]['partno'];
      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'], 2);
      $uom = $data[$i]['uom'];
      $amt = number_format($data[$i]['netamt'], 2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'], 2);

      $discamt1 = $this->othersClass->Discount($data[$i]['netamt'], $disc);
      $discamt = number_format($discamt1, 2);

      // $arr_partno = $this->reporter->fixcolumn([$partno], '50', 0);
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '28', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '9', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);
      $arr_discamt = $this->reporter->fixcolumn([$discamt], '13', 0);


      $maxrow = $this->othersClass->getmaxcolumn([ $arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext,$arr_discamt]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_discamt[$r]) ? $arr_discamt[$r]: ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
           $rowCount++;
          }

       if ($rowCount >= $page && $i < count($data) - 1) {
            $this->po_footer($params, $data);
            $rowCount = 0;
            $this->default_PO_header_PDF($params, $data);
          }

      $totalext += $data[$i]['ext'];

      // if (PDF::getY() > 900) {
      //   $this->default_PO_header_PDF($params, $data);
      // }
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 20, '***** NOTHING FOLLOWS *****', '', 'C', false, 1);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');
 
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL ', '', 'L', false, 0);
    PDF::MultiCell(120, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', 'B');
   

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 0, 'NOTE: Please Attached faxed P.O. during delivery.', '', 'L', false, 1);
    

     PDF::SetXY(40, 915);
     PDF::SetCellPaddings(0, 0, 0, 2);
   
    $preparedby= $params['params']['dataparams']['prepared'];
    $approvedby= $params['params']['dataparams']['approved'];
    $receivedby= $params['params']['dataparams']['received'];

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 15, 'Prepared By: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(260, 15, $preparedby, 'B', 'L', false, 0);
    PDF::MultiCell(40, 15,'', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 15, 'Approved By:', '', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 15, $approvedby, 'B', 'L', false, 0);
    PDF::MultiCell(20, 15, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 15, 'Fax Received By: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(260, 15, $receivedby, 'B', 'L', false, 0);
    PDF::MultiCell(40, 15,'', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 15, 'Cancel Date:', '', 'L', false, 0);
    PDF::MultiCell(200,15, '', 'B', 'L', false, 0);
    PDF::MultiCell(20, 15, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');

    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('F d, Y');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 15, '', '', 'L', false, 0);
    PDF::MultiCell(260, 15, '', '', 'L', false, 0);
    PDF::MultiCell(40, 15,'', '', 'L', false, 0);
    PDF::MultiCell(100, 15, 'Delivery Date:', '', 'L', false, 0);
    PDF::MultiCell(200, 15, $datehere, 'B', 'L', false, 0);
    PDF::MultiCell(20, 15, '', '', 'L', false, 1);

    PDF::SetCellPaddings(0, 0, 0, 0);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function po_footer($params,$data){
      $companyid = $params['params']['companyid'];
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
      $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
      $center = $params['params']['center'];
      $username = $params['params']['user'];
  
      $totalext = 0;

      $font = "";
      $fontbold = "";
      $border = "1px solid ";
      $fontsize = "11";
      if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
      }

       PDF::SetXY(40, 915);
       PDF::SetCellPaddings(0, 0, 0, 2);
   
        $preparedby= $params['params']['dataparams']['prepared'];
        $approvedby= $params['params']['dataparams']['approved'];
        $receivedby= $params['params']['dataparams']['received'];

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, 'Prepared By: ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(260, 15, $preparedby, 'B', 'L', false, 0);
        PDF::MultiCell(40, 15,'', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, 'Approved By:', '', 'L', false, 0);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(200, 15, $approvedby, 'B', 'L', false, 0);
        PDF::MultiCell(20, 15, '', '', 'L', false, 1);

        PDF::SetFont($font, '', 2);
        PDF::MultiCell(720, 0, '', '');
        
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, 'Fax Received By: ', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(260, 15, $receivedby, 'B', 'L', false, 0);
        PDF::MultiCell(40, 15,'', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, 'Cancel Date:', '', 'L', false, 0);
        PDF::MultiCell(200,15, '', 'B', 'L', false, 0);
        PDF::MultiCell(20, 15, '', '', 'L', false, 1);

        PDF::SetFont($font, '', 2);
        PDF::MultiCell(720, 0, '', '');

        $date = $data[0]['dateid'];
        $datetime = new DateTime($date);
        $datehere = $datetime->format('F d, Y');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, '', '', 'L', false, 0);
        PDF::MultiCell(260, 15, '', '', 'L', false, 0);
        PDF::MultiCell(40, 15,'', '', 'L', false, 0);
        PDF::MultiCell(100, 15, 'Delivery Date:', '', 'L', false, 0);
        PDF::MultiCell(200, 15, $datehere, 'B', 'L', false, 0);
        PDF::MultiCell(20, 15, '', '', 'L', false, 1);

        
      PDF::SetCellPaddings(0, 0, 0, 0);


  }
}
