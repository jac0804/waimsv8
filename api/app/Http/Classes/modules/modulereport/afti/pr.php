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
use Symfony\Component\VarDumper\VarDumper;

class pr
{

  private $modulename = "Purchase Requisition";
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

  public function createreportfilter($config){
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti
      data_set($col1, 'prepared.readonly',true);
      data_set($col1, 'prepared.type','lookup');
      data_set($col1, 'prepared.action','lookupclient');
      data_set($col1, 'prepared.lookupclass','prepared');

      data_set($col1, 'approved.readonly',true);
      data_set($col1, 'approved.type','lookup');
      data_set($col1, 'approved.action','lookupclient');
      data_set($col1, 'approved.lookupclass','approved');

      data_set($col1, 'received.readonly',true);
      data_set($col1, 'received.type','lookup');
      data_set($col1, 'received.action','lookupclient');
      data_set($col1, 'received.lookupclass','received');
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
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($trno){

    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
        head.terms,head.rem, item.barcode,item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt,
        stock.disc, stock.ext,m.model_name as model,item.sizeid, iteminfo.itemdescription,stock.sortline,stock.line,head.cur
        from prhead as head left join prstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join iteminfo on iteminfo.itemid = item.itemid
        where head.doc='PR' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname, 
        head.address, head.terms,head.rem, item.barcode,item.itemname, stock.rrqty as qty, stock.uom,
        stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid, iteminfo.itemdescription,stock.sortline,stock.line,head.cur
        from hprhead as head left join hprstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join iteminfo on iteminfo.itemid = item.itemid
        where head.doc='PR' and head.trno='$trno' order by sortline,line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data) {
    if($params['params']['dataparams']['print'] == "default") {
      return $this->default_pr_layout($params, $data);
    } else if($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_PR_PDF($params, $data);
    }
  }

  private function reportheader($params, $data){

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
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
    $str .= $this->reporter->col('PURCHASE REQUISITION', '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
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
    $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    
    return $str;
  }

  public function default_pr_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 28;
    $page = 28;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->reportheader($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '475', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
            $str .= $this->reportheader($params, $data);
        $str .= $this->reporter->endrow();
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
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', ''); //$data[0]['rem']
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_PR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,tin,email from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";
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
    PDF::SetMargins(10, 10);

    $fontsize9 = "9";
    $fontsize11 = "9";
    $fontsize12 = "10";
    $fontsize13 = '10';
    $fontsize14 = "11";
    $border = "1px solid ";

    PDF::Image($this->companysetup->getlogopath($params['params']) .'qslogo.png', '', '', 310, 80);
    PDF::MultiCell(0, 20, "\n");
    PDF::SetFont($font, 'B', 18, $border);
    PDF::MultiCell(320, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
    PDF::MultiCell(265, 0, 'PURCHASE REQUISITION', '', 'C', 0, 0, '', '', false, 0, false, false, 0);
    PDF::MultiCell(0, 30, "\n");

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(245, 15, '', '', 'R', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Document No.',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), $border, 'L', false);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(245, 15, '', '', 'R', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Date',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . date("F d,Y", strtotime($data[0]['dateid'])), $border, 'L', false);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(245, 15, '', '', 'R', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Supplier',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), $border, 'L', false);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(245, 15, '', '', 'R', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Terms',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), $border, 'L', false);

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(245, 15, $headerdata[0]->name, '', 'L', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Page No.',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . 'Page    ' . PDF::PageNo().'    of    '.PDF::getAliasNbPages(), $border, 'L', false);

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(320, 15, $headerdata[0]->address, '', 'L', false, 0);
    PDF::MultiCell(50, 15, '', '', 'L', false, 0);
    PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
    PDF::MultiCell(155, 15, '', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(320, 15, $headerdata[0]->tel, '', 'L', false, 0);
    PDF::MultiCell(50, 15, '', '', 'L', false, 0);
    PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
    PDF::MultiCell(155, 15, '', '', 'L', false);

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(320, 15, 'Email: '.$headerdata[0]->email, '', 'L', false, 0);
    PDF::MultiCell(50, 15, '', '', 'L', false, 0);
    PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
    PDF::MultiCell(155, 15, '', '', 'L', false);

    if($params['params']['companyid']==10){
        PDF::SetFont($font, '', $fontsize13);
        PDF::MultiCell(320, 15, 'VAT REG TIN: ' . $headerdata[0]->tin, '', 'L', false, 0);
        PDF::MultiCell(50, 15, '', '', 'L', false, 0);
        PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
        PDF::MultiCell(165, 15, '', '', 'L', false);
    }


        PDF::MultiCell(0, 20, "\n");


    PDF::SetFont($font, 'B', $fontsize9);
    // PDF::MultiCell(100, 0, "Item Name", '', 'C', false, 0);
    // PDF::MultiCell(50, 0, "Quantity", '', 'C', false, 0);
    // PDF::MultiCell(225, 0, "Description", '', 'L', false, 0);
    // PDF::MultiCell(50, 0, "Unit Price", '', 'C', false, 0);
    // PDF::MultiCell(25, 0, "", '', 'C', false, 0);
    // PDF::MultiCell(25, 0, "", '', 'C', false, 0);
    // PDF::MultiCell(100, 0, "Line Total", '', 'C', false);
    
    PDF::MultiCell(100, 0, "Item Name", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "Quantity", '', 'C', false, 0);
    PDF::MultiCell(265, 0, "Description", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "Unit Price", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "Line Total", '', 'C', false);


    PDF::MultiCell(575, 0, '', 'B');
  }

  public function default_PR_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 750;
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
    $this->default_PR_header_PDF($params, $data);

    $newpageadd = 0;
    for ($i = 0; $i < count($data); $i++) {
      $itemcoldes = $this->reporter->fixcolumn([$data[$i]['itemdescription']],'30',1);
      $itemcoldescount = count($itemcoldes);

      $barcodecols = $this->reporter->fixcolumn([$data[$i]['itemname']],'15',1);
      $barcodecolscount = count($barcodecols);

      $qtycols = $this->reporter->fixcolumn([number_format($data[$i]['qty'],$decimalqty).' '.$data[$i]['uom']], '25',1);
      $qtycolscount = count($qtycols);

      $uomcols = $this->reporter->fixcolumn([$data[$i]['uom']], '10',1);
      $uomcolscount = count($uomcols);

      $netamt = $this->reporter->fixcolumn([$data[$i]['cur'].' '.number_format($data[$i]['netamt'],2)], '20',1);
      $netamtcolscount = count($netamt);

      $extcols = $this->reporter->fixcolumn([$data[$i]['cur'].' '.number_format($data[$i]['ext'],2)], '20',1);
      $extcolscount = count($extcols);

      $maxrow = max($itemcoldescount,$barcodecolscount,$qtycolscount,$extcolscount,$netamtcolscount);
      if($data[$i]['itemname'] == '') {

      } else {
        
        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize9);
          PDF::MultiCell(100, 0, isset($barcodecols[$r]) ? ' '.$barcodecols[$r] : '', '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(60, 0, isset($qtycols[$r]) ? ' '.$qtycols[$r] : '', '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(265, 0, isset($itemcoldes[$r]) ? ' '.$itemcoldes[$r] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, isset($netamt[$r]) ? ' '.$netamt[$r] : '', '', 'C', 0, 0, '', '', true, 0, true, false);
          
          PDF::MultiCell(100, 0, isset($extcols[$r]) ? ' '.$extcols[$r] : '', '', 'R', 0, 1, '', '', true, 0, true, false);

          if (PDF::getY() >= $page) {
            $newpageadd = 1;
            PDF::MultiCell(575, 0, '', 'B', 'C', false, 0, '', '', true, 1);
            $this->blankpage($params, $data, $font);
          }
        }
      }
      
      $totalext += $data[$i]['ext'];

      if(PDF::getY()>720){
        PDF::MultiCell(575, 0, '', 'B', 'C', false, 0, '', '', true, 1);
        $newpageadd = 1;
        $this->default_PR_header_PDF($params, $data);
      }
    }

    PDF::MultiCell(575, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(420, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(155, 0, $data[0]['cur'].' '.number_format($totalext, 2), '', 'R');

    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
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

  public function blankpage($params, $data, $font)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

    $qry = "select name,concat(address,' ',zipcode) as address,tel,tin,email from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(575, 0, '', 'LRB', 'C', false, 0, '', '', true, 1);
  }


}
