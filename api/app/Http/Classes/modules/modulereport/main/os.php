<?php

namespace App\Http\Classes\modules\modulereport\main;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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

class os
{

  private $modulename = "OUTSOURCE";
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

    public function createreportfilter($config){
      $companyid = $config['params']['companyid'];        
      $fields = ['print'];
      $col1 = $this->fieldClass->create($fields);
      
      return array('col1'=>$col1);
    }

    public function reportparamsdata($config){
      $companyid = $config['params']['companyid'];
      return $this->coreFunctions->opentable(
          "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received
        ");        
    }

  public function report_os_query($trno){

    $query ="select head.trno, date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
    head.terms,head.rem, item.barcode, item.inhouse, item.partno,
    item.itemname, stock.qty as qty, stock.uom, stock.cost as netamt, stock.disc, stock.ext,
    m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stockinfo.leaddur, stockinfo.validity,
    head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
    head.deptid,dept.clientname as deptname, head.yourref, item.inhouse, part.part_name as partname, brand.brand_desc as brand,
    stock.currency, iteminfo.itemdescription,head.customer,stockinfo.isvalid,ifnull(stockinfo.ovaliddate,'') as ovaliddate,head.ostech,
    stockinfo.leadtimesettings,stock.sortline,stock.line
    from oshead as head
    left join osstock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid = stock.itemid
    left join iteminfo on item.itemid = iteminfo.itemid
    left join model_masterfile as m on m.model_id = item.model
    left join stockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
    left join client as wh on wh.client=head.wh
    left join client as dept on dept.clientid = head.deptid
    left join client as branch on branch.clientid = head.branch
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    where head.doc='os' and head.trno='$trno'
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms,head.rem, item.barcode, item.inhouse, item.partno,
    item.itemname, stock.qty as qty, stock.uom, stock.cost as netamt, stock.disc, stock.ext,
    m.model_name as model,item.sizeid,stockinfo.rem as itemrem, stockinfo.leaddur, stockinfo.validity,
    head.wh,wh.clientname as warehouse,head.rem as headrem,head.branch,branch.clientname as branchname,
    head.deptid,dept.clientname as deptname, head.yourref, item.inhouse, part.part_name as partname, brand.brand_desc as brand,
    stock.currency, iteminfo.itemdescription,head.customer,stockinfo.isvalid,ifnull(stockinfo.ovaliddate,'') as ovaliddate,head.ostech,
     stockinfo.leadtimesettings,stock.sortline,stock.line
    from hoshead as head
    left join hosstock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid = stock.itemid
    left join iteminfo on item.itemid = iteminfo.itemid
    left join model_masterfile as m on m.model_id = item.model
    left join hstockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
    left join client as wh on wh.client=head.wh
    left join client as dept on dept.clientid = head.deptid
    left join client as branch on branch.clientid = head.branch
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    where head.doc='os' and head.trno='$trno' order by sortline,line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }//end fn

  public function default_pdfheader($params, $data, $font) {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $count = $page = 35;
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '".$center."'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\rr')->modulename;

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename.' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($font, 'I', 18);
    PDF::MultiCell(0, 0, '', '', 'L');

    PDF::SetFont($font, 'bi', 20);
    PDF::SetFillColor(255,255,0);
    PDF::MultiCell(400, 0, 'AFTI IN-HOUSE CODE', 0, 'L', 1, 1, '', '', true, 0, false, true, 0, 'T');
    
    PDF::SetFont($font, 'b', 15);
    PDF::MultiCell(0, 0, 'TECHNICAL DEPT.', '', 'L');

    PDF::SetFont($font, 'b', 12);
    PDF::setCellMargins(4, 3, 4, 3);
    PDF::MultiCell(100, 15, $data[0]['ostech'], 0, 'L', 0, 1, '', '', true, 1, false, true, 0, 'M', true);
    // PDF::Ln(2);

    PDF::SetFont($font, 'b', 12);
    PDF::SetTextColor(0,0,220);
    PDF::MultiCell(90, 0, 'Customer :', 0, 'L', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetTextColor(0,0,0);
    PDF::MultiCell(470, 0, $data[0]['customer'], 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);

    PDF::SetTextColor(0,0,220);
    PDF::MultiCell(90, 0, 'Reference #:', 0, 'L', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetTextColor(0,0,0);
    PDF::MultiCell(470, 0, $data[0]['yourref'], 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);
    
    // PDF::Ln(2);
    PDF::SetFont($font, '', 0);
    // border
    PDF::SetLineStyle(array('width' => 3, 'cap' => 'round', 'dash' => 0, 'color' => array(210, 0, 0)));
    PDF::MultiCell(530, 0, '', 'T', 'T', 0, 1, '', '', true, 0, false, false, 0, 'M', true);
 
  }

  public function reportplottingpdf($params, $data){
  $companyid = $params['params']['companyid'];
  $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
  $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
  $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
  $center = $params['params']['center'];
  $username = $params['params']['user'];
  $count = $page = 35;
  $totalext = 0;

  $font = 'times';
  // if(Storage::disk('sbcpath')->exists('/fonts/CENTURY.TTF')) {
  //   $font = TCPDF_FONTS::addTTFfont(database_path().'/images/fonts/CENTURY.TTF');
  // }

  

  // PDF::Image('/images/afti/qslogo.png', '', '', '500','100');
    $count =0;
  for ($i = 0; $i < count($data); $i++) {
    if($count == 0){
      $this->default_pdfheader($params, $data, $font);
    }
    if($count == 2){
      $count =0;
    }else{
      $count++;
    }
    
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'dash' => 0, 'color' => array(0, 0, 0)));
    PDF::MultiCell(530, 0, '', 'T', 'T', 0, 1);
    // border
  
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Inhouse # : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0, $data[$i]['inhouse'], 0, 'L');
  
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Selling Price : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0, number_format($data[$i]['netamt'],2), 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);
  
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Currency : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0,$data[$i]['currency'], 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);
  
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Part # : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0,$data[$i]['model'], 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);
  
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Brand : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0,$data[$i]['brand'], 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);
  
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Description : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0,$data[$i]['itemdescription'], 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
  
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Quantity : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0, number_format($data[$i]['qty']).' '.$data[$i]['uom'], 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);
    
    switch (strtoupper($data[$i]['leadtimesettings'])) {
        case 'EX-STOCK': 
           $leadtime = 'LT: EX:STK; SUBJ TO PRIOR SALES;';
        break;

        case '2-3 WEEKS':
          $leadtime = 'LT: 2 TO 3 WEEKS; SUBJ TO PRIOR SALES;';
          break;

        default:
           $leadtime = $data[$i]['leaddur'];
        break;
    }

    if($data[$i]['isvalid']){
      $isreturn = 'NON-RETURNABLE AND NON-CANCELLABLE;';
    }else{
      $isreturn = '';
    }
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Leadtime : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0,$leadtime.' '.$isreturn, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
  
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Remarks : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0, $data[$i]['itemrem'], 0, 'L', 0, 1, '', '', true, 1, false, false, 0, 'M', true);
  
    $validity = 0;
   
    switch ($data[$i]['validity']) {
      case '15 Days':
        $validity = 15;
        break;
      case '30 Days':
        $validity = 30;
        break;
      case '45 Days':
        $validity = 45;
        break;
      case '60 Days':
        $validity = 60;
        break;
      default:
        if($data[$i]['ovaliddate']!=''){
          $validity = date("F d, Y", strtotime($data[$i]['ovaliddate']));
        }else{
          $validity = '';
        }
        break;
    }

    if($validity!=''){
      if ($data[$i]['validity']!='Others'){
        $d = $data[$i]['ovaliddate'];
        $d = date_create($d);
        $d = date_format($d,"F d, Y");
        $d = date("F d, Y",strtotime($d. ' + '.$validity.' Days'));
        $d = date_create($d);
        $c = $d;
        $d = date_format($d,"F d, Y");
        $c = date_format($c,'w');
        switch ($c) {
          case 0:
            $d = date("F d, Y",strtotime($d. ' + 1 Days'));
            break;
          case 6:
            $d = date("F d, Y",strtotime($d. ' + 2 Days'));
            break;
        }
      }else{
        $d = $data[$i]['ovaliddate'];
        $d = date_create($d);
        $d = date_format($d,"F d, Y");
        $d = date("F d, Y",strtotime($d));
      }
    }else{
      $d = '';
    }
    
    PDF::SetFont($font, 'b', 9);
    PDF::MultiCell(60, 0, 'Validity : ', 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    // PDF::MultiCell(60, 0, $daycheck, 0, 'R', 0, 0, '', '', true, 1, false, false, 0, 'M', true);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(470, 0, $d, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    // PDF::MultiCell(470, 0,$dayscounted, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
  
    PDF::SetFont($font, '', 5);
    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'dash' => 0, 'color' => array(0, 0, 0)));
    PDF::MultiCell(530, 0, '', 'T', 'T', 0);
    

    //  if (intVal($i) + 1 == $page) {
    //   $this->default_pdfheader($params, $data,$font);
    //   $page += $count;
    // }
  }
  return PDF::Output($this->modulename.'.pdf', 'S');  


  }

}
