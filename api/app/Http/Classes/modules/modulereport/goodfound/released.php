<?php

namespace App\Http\Classes\modules\modulereport\goodfound;

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

class released
{

  private $modulename = "RELEASED";
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

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'radiostatus', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);

    data_set($col1, 'radiostatus.label', 'Report Type');
    data_set($col1, 'radiostatus.options', [
      ['label' => 'Delivery Receipt', 'value' => '0', 'color' => 'blue'],
      ['label' => 'CWOR', 'value' => '1', 'color' => 'blue'],
      ['label' => 'CWOR Shooting', 'value' => '2', 'color' => 'blue'],
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '0' as status,
      '0' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($config)
  {

    ini_set('memory_limit', '-1');
    $trno = $config['params']['dataid'];
    
    $query = "select 
    case when month(head.dateid)<10 then concat('0',month(head.dateid)) else month(head.dateid) end as datemonth,
    day(head.dateid) as dateday,
    date_format(head.dateid,'%y') as dateyear,
    substring(client.areacode,1,1) as area1,
    substring(client.areacode,2,1) as area2,
    substring(client.areacode,3,1) as area3,
    9 as not4,
    right(year(head.dateid),1) as not5,
    case when month(head.dateid)<10 then 0 else left(month(head.dateid),1) end as not6,
    case when month(head.dateid)<10 then month(head.dateid) else right(month(head.dateid),1) end as not7,
    9 as not8,
    substring(stock.ref,14,1) as not9,
    substring(stock.ref,15,1) as not10,
    substring(stock.ref,6,1) as bir4,
    substring(stock.ref,7,1) as bir5,
    substring(stock.ref,8,1) as bir6,
    
    substring(stock.ref,9,1) as bir7,
    substring(stock.ref,10,1) as bir8,
    
    substring(stock.ref,11,1) as bir9,
    substring(stock.ref,12,1) as bir10,
    
    substring(stock.ref,13,1) as bir11,
    substring(stock.ref,14,1) as bir12,
    substring(stock.ref,15,1) as bir13,
    stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname, client.tin, client.area,
    head.driver, ifnull(hinfo.hauler,'') as hauler, 
    ifnull(hinfo.plateno,'') as plateno, ifnull(hinfo.licenseno,'') as licenseno, ifnull(hinfo.batchno,'') as batchno
    , ifnull(hinfo.cwano,'') as cwano,stock.ref,client.areacode,ifnull(hinfo.assignedlane,'') as assignedlane,
    ifnull(date(hinfo.releasedate),'') as releasedate,ifnull(hinfo.weightintime,'') as weightintime,ifnull(hinfo.weightouttime,'') as weightouttime
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    left join cntnuminfo as hinfo on hinfo.trno = head.trno
    where head.doc='sj' and head.trno='$trno' and stock.iscomponent=0
    UNION ALL
    select 
    case when month(head.dateid)<10 then concat('0',month(head.dateid)) else month(head.dateid) end as datemonth,
    day(head.dateid) as dateday,
    date_format(head.dateid,'%y') as dateyear,
    substring(client.areacode,1,1) as area1,
    substring(client.areacode,2,1) as area2,
    substring(client.areacode,3,1) as area3,
    9 as not4,
    right(year(head.dateid),1) as not5,
    case when month(head.dateid)<10 then 0 else left(month(head.dateid),1) end as not6,
    case when month(head.dateid)<10 then month(head.dateid) else right(month(head.dateid),1) end as not7,
    9 as not8,
    substring(stock.ref,14,1) as not9,
    substring(stock.ref,15,1) as not10,
    substring(stock.ref,6,1) as bir4,
    substring(stock.ref,7,1) as bir5,
    substring(stock.ref,8,1) as bir6,
    
    substring(stock.ref,9,1) as bir7,
    substring(stock.ref,10,1) as bir8,
    
    substring(stock.ref,11,1) as bir9,
    substring(stock.ref,12,1) as bir10,
    
    substring(stock.ref,13,1) as bir11,
    substring(stock.ref,14,1) as bir12,
    substring(stock.ref,15,1) as bir13,
    stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname, client.tin, client.area,
    head.driver, ifnull(hinfo.hauler,'') as hauler, 
    ifnull(hinfo.plateno,'') as plateno, ifnull(hinfo.licenseno,'') as licenseno, ifnull(hinfo.batchno,'') as batchno
    , ifnull(hinfo.cwano,'') as cwano,stock.ref,client.areacode,ifnull(hinfo.assignedlane,'') as assignedlane,
    ifnull(date(hinfo.releasedate),'') as releasedate,ifnull(hinfo.weightintime,'') as weightintime,ifnull(hinfo.weightouttime,'') as weightouttime
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    left join hcntnuminfo as hinfo on hinfo.trno = head.trno
    where head.doc='sj' and head.trno='$trno' and stock.iscomponent=0 order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['status'];
    $temp=$params['params']['doc'];
    switch ($reporttype) {
      case 2:
        
        $params['params']['doc']='SJ';
        $str = app($this->companysetup->getreportpath($params['params']))->shooting_cwor_PDF($params, $data);
        $params['params']['doc']=$temp;
        return $str;
        break;
      case 1: // CWOR
        return $this->default_cwor_PDF($params, $data);
        break;

      default: // default 0
        return $this->default_dr_PDF($params, $data);
        break;
    }
  }


  
  public function default_cwor_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    

    $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
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
    PDF::SetMargins(40,40);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, 'B', 16);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::MultiCell(720, 15, strtoupper($headerdata[0]->name), '', 'C', false,1);
    PDF::SetFont($font, '', 11);
    PDF::SetCellPaddings(0, 0, 0, 0);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->address), '', 'C');
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(720, 20, "VAT REG TIN: ".strtoupper($headerdata[0]->tin), '', 'C');

    // FIX SETUP
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BATCH #. ", '', 'L', false, 0, '580',  '60');
    PDF::MultiCell(120, 0,$data[0]['batchno'], 'B', 'C', false, 1, '640',  '60');
    PDF::MultiCell(100, 30, ""); // for new line

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(720, 30, 'CEMENT WITHDRAWAL ORDER RECEIPT (CWOR)', '', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(260, 0, 'SALES ORDER No.', 'TL', 'L', 0, 0, '', '');
    PDF::MultiCell(200, 0, 'CWA NO.', 'TL', 'L', 0, 0, '', '');
    PDF::MultiCell(260, 0, 'ASSIGNED LANE No.', 'TLR', 'L', 0, 1, '', '');

    $bir=1;
    PDF::SetFont($fontbold, '', $fontsize+1);
    
    
    if($bir==0){
      PDF::MultiCell(35, 0, '', 'L', 'L', 0, 0, '', '');

      PDF::MultiCell(15, 0, $data[0]['area1'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['area2'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['area3'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(10, 0, '', '', 'C', 0, 0, '', '');
  
      PDF::MultiCell(15, 0, $data[0]['not4'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['not5'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, '', '', 'C', 0, 0, '', '');
  
      PDF::MultiCell(15, 0, $data[0]['not6'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['not7'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, '', '', 'C', 0, 0, '', '');
  
      PDF::MultiCell(15, 0, $data[0]['not8'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['not9'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['not10'], 'TBLR', 'C', 0, 0, '', '');

      PDF::MultiCell(35, 0, '', '', 'L', 0, 0, '', '');
    }else{
      PDF::MultiCell(15, 0, '', 'L', 'L', 0, 0, '', '');

      PDF::MultiCell(15, 0, $data[0]['area1'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['area2'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['area3'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(10, 0, '', '', 'C', 0, 0, '', '');

      PDF::MultiCell(15, 0, $data[0]['bir4'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['bir5'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['bir6'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(7, 0, '', '', 'C', 0, 0, '', '');
  
      PDF::MultiCell(15, 0, $data[0]['bir7'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['bir8'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(8, 0, '', '', 'C', 0, 0, '', '');
  
      PDF::MultiCell(15, 0, $data[0]['bir9'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['bir10'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(10, 0, '', '', 'C', 0, 0, '', '');
  
      PDF::MultiCell(15, 0, $data[0]['bir11'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['bir12'], 'TBLR', 'C', 0, 0, '', '');
      PDF::MultiCell(15, 0, $data[0]['bir13'], 'TBLR', 'C', 0, 0, '', '');

      PDF::MultiCell(15, 0, '', '', 'L', 0, 0, '', '');
    }
    


    PDF::MultiCell(20, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(160, 0, $data[0]['cwano'], '', 'C', 0, 0, '', '');
    PDF::MultiCell(20, 0, '', '', 'L', 0, 0, '', '');

    PDF::MultiCell(260, 0, $data[0]['assignedlane'], 'LR', 'C', 0, 1, '', '');
    

    PDF::SetFont($font, '', $fontsize-9);
    PDF::MultiCell(260, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(200, 0, '', 'L', 'L', 0, 0, '', '');
    PDF::MultiCell(260, 0, '', 'LR', 'L', 0, 1, '', '');

    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(260, 0, 'DISTRIBUTOR', 'LT', 'L', 0, 0, '', '');
    PDF::MultiCell(200, 0, 'QUANTITY', 'LT', 'L', 0, 0, '', '');
    PDF::MultiCell(200, 0, 'DATE :', 'LT', 'L', 0, 0, '', '');
    PDF::MultiCell(60, 0, 'TIME IN:', 'TR', 'R', 0, 1, '', '');
    
    $totalqty = 0;
    $itemnames = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $totalqty += $data[$i]['qty'];
        
      }
    }

    PDF::SetFont($fontbold, '', $fontsize+2);
    PDF::MultiCell(260, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'L', 'C', 0, 0, '', '');
    PDF::MultiCell(200, 0, number_format($totalqty,2), 'L', 'C', 0, 0, '', '');

    PDF::MultiCell(20, 0, '', 'L', 'L', 0, 0, '', '');

    
    PDF::MultiCell(25, 0, $data[0]['dateyear'], '', 'C', 0, 0, '', '');
    PDF::MultiCell(20, 0, '/', '', 'C', 0, 0, '', '');
    PDF::MultiCell(25, 0, $data[0]['datemonth'], '', 'C', 0, 0, '', '');
    PDF::MultiCell(20, 0, '/', '', 'C', 0, 0, '', '');
    PDF::MultiCell(25, 0, $data[0]['dateday'], '', 'C', 0, 0, '', '');

    PDF::MultiCell(60, 0, '', '', 'L', 0, 0, '', '');

    PDF::MultiCell(65, 0, '', 'R', 'R', 0, 1, '', '');

    
    $prepared = $params['params']['dataparams']['prepared'];
    $approved = $params['params']['dataparams']['approved'];
    $received = $params['params']['dataparams']['received'];

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(260, 0, 'AREA', 'TLR', 'L', 0, 0, '', '');
    PDF::MultiCell(200, 0, 'PRODUCT', 'TLR', 'L', 0, 0, '', '');
    PDF::MultiCell(260, 0, 'APPROVED BY: ', 'TLR', 'L', 0, 1, '', '');

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        
        if($i == 0){
          $itemname = $data[$i]['itemname'];
          $area = $data[0]['area'];
        }else{
          $itemname = $data[$i]['itemname'];
          $area = "";
          $prepared = "";
        }

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_area = $this->reporter->fixcolumn([$area], '30', 0);
        $arr_approved = $this->reporter->fixcolumn([$approved], '25', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname,$arr_area, $arr_approved]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(260, 0, ' ' . $area, 'LR', 'C', false, 0, '',  '');
          PDF::MultiCell(200, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LR', 'L', false, 0, '',  '');
          PDF::MultiCell(260, 0, ' ' . $prepared, 'LR', 'L', false, 1, '',  '');
        }
      }
    }

    
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, "HAULER", 'TL', 'L',0 ,0);
    PDF::SetFont($fontbold, '', $fontsize+2);
    PDF::MultiCell(200, 0, $data[0]['hauler'], 'TR', 'L',0 ,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "PLATE NO.", 'TL', 'L',0 ,0);
    PDF::SetFont($fontbold, '', $fontsize+2);
    PDF::MultiCell(120, 0, $data[0]['plateno'], 'TR', 'L',0 ,0);
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(260, 0, "AUTHORIZED BY:", 'TR', 'L',0 ,1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, "DRIVER", 'TL', 'L',0 ,0);
    PDF::MultiCell(200, 0, "", 'TR', 'L',0 ,0);
    PDF::MultiCell(80, 0, "LICENSE NO. ", 'TL', 'L',0 ,0);
    PDF::MultiCell(120, 0, "", 'TR', 'L',0 ,0);
    PDF::MultiCell(260, 0, "", 'LR', 'L',0 ,1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize+2);
    PDF::MultiCell(60, 0, "", 'L', 'L',0 ,0);
    PDF::MultiCell(200, 0, $data[0]['driver'], 'R', 'L',0 ,0);
    PDF::MultiCell(80, 0, "", 'L', 'L',0 ,0);
    PDF::MultiCell(120, 0, $data[0]['licenseno'], 'R', 'L',0 ,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(260, 0, "DISPATCHER:", 'BLR', 'L',0 ,1, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "SERVED BY", 'TLR', 'L',0 ,0);
    PDF::MultiCell(360, 0, "RECEIVED PRODUCTS IN GOOD CONDITION", 'TLR', 'L',0 ,0);
    PDF::MultiCell(260, 0, "RELEASED BY", 'TR', 'L',0 ,1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "", 'LR', 'L',0 ,0);
    PDF::MultiCell(360, 0, "", 'LR', 'L',0 ,0);
    PDF::MultiCell(260, 0, "", 'R', 'L',0 ,1);
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "", 'LR', 'L',0 ,0);
    PDF::MultiCell(360, 0, "", 'LR', 'L',0 ,0);
    PDF::MultiCell(260, 0, "", 'R', 'L',0 ,1);

    
    PDF::SetFont($font, '', $fontsize-5);
    PDF::MultiCell(100, 0, "UNIT HEAD / FOREMAN", 'BLR', 'L',0 ,0);
    PDF::MultiCell(160, 0, "SIGNATURE", 'BL', 'L',0 ,0);
    PDF::MultiCell(100, 0, "DATE", 'B', 'L',0 ,0);
    PDF::MultiCell(100, 0, "TIME", 'BR', 'L',0 ,0);
    PDF::MultiCell(100, 0, "WAREHOUSEMAN", 'B', 'L',0 ,0);
    PDF::MultiCell(80, 0, "DATE", 'B', 'L',0 ,0);
    PDF::MultiCell(80, 0, "TIME", 'BR', 'L',0 ,1);

    PDF::SetFont($font, '', $fontsize-1);
    PDF::MultiCell(620, 0, "This CWOR is part of the above SALES ORDER and is therefore subject to the quantites, prices, terms and conditions", '', 'L',0 ,0);
    PDF::MultiCell(100, 0, "", '', 'L',0 ,1);

    PDF::MultiCell(520, 0, "stated therein.", '', 'L',0 ,0);
    
    PDF::SetFont($fontbold, '', $fontsize-1);
    PDF::MultiCell(200, 0, "MKT10.01 15FEB12 REV1", '', 'R',0 ,1);

     return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_dr_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    

    $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }


    
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(40,40);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, 'B', 16);
    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::MultiCell(720, 15, strtoupper($headerdata[0]->name), 'TLR', 'C', false,1);
    PDF::SetFont($font, '', 11);
    PDF::SetCellPaddings(0, 0, 0, 0);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->address), 'LR', 'C');
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->tel), 'LR', 'C');
    PDF::MultiCell(720, 60, "VAT REG TIN: ".strtoupper($headerdata[0]->tin), 'LR', 'C');

    // FIX SETUP
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "NO. ", '', 'L', false, 0, '580',  '60');
    PDF::MultiCell(120, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '620',  '60');

    PDF::SetFont($font, '', 11);
    
    PDF::MultiCell(100, 0, "Date. ", '', 'L', false, 0, '580',  '90');
    PDF::SetFont($font, 'B', 11);
    
    PDF::MultiCell(120, 0, (date_format(date_create($data[0]['dateid']),"d-M-Y")), 'B', 'L', false, 1, '620',  '90');

    
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(720, 0, 'DELIVERY RECEIPT', 'B', 'C', false, 1);

    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(240, 15, 'CONSIGNEE (Customer)', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 15, 'DESTINATION:', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(70, 15, 'REF NO.:', 'TL', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(150, 15, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'TB', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(20, 15, '', 'TR', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(240, 40, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'BLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 40, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'LRB', 'C', 0, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(240, 40, '', 'LRB', 'C', 0, 1, '', '', true, 0, false, true, 0, 'T', true);

    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(240, 15, 'ADDRESS / TIN', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 15, 'DELIVERY VIA(Vessel name or truck No.)', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(120, 15, 'Credit Term', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(120, 15, 'Billed by Invoice No.', 'TLR', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    $prepared = $params['params']['dataparams']['prepared'];
    $approved = $params['params']['dataparams']['approved'];
    $received = $params['params']['dataparams']['received'];

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(240, 40, (isset($data[0]['address']) ? $data[0]['address'] : '').' - '.(isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'BLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 40, '', 'LRB', 'C', 0, 0, '', '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(120, 40, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'LRB', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(120, 40, '', 'LRB', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);


    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(120, 0, "QUANTITY", 'TLRB', 'C', false, 0);
    PDF::MultiCell(80, 0, "UNIT", 'TLRB', 'C', false, 0);
    PDF::MultiCell(520, 0, "DESCRIPTION", 'TLRB', 'C', false);

  }

  public function default_dr_PDF($params, $data)
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
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }
    $this->default_dr_header_PDF($params, $data);

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(120, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(520, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'TLRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 680) {
          $this->default_dr_header_PDF($params, $data);
        }
      }
      PDF::MultiCell(720, 15,'', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(720, 15,'', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(720, 15,'', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

    $prepared = $params['params']['dataparams']['prepared'];
    $approved = $params['params']['dataparams']['approved'];
    $received = $params['params']['dataparams']['received'];

    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(240, 35, 'Prepared by : ', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 35, 'Noted / Approved by :', 'TLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 35, 'Received the above Items in good order and condition (Printed name and sign lightly)', 'TLR', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);
   
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(240, 30, ''.$prepared, 'BLR', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 30, ''.$approved, 'LRB', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(30, 30, 'By ', 'B', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(90, 30, '<u>'.$received.'________</u>', 'B', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(30, 30, 'Date','B', 'L', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(90, 30, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''),'RB', 'L', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(65, 20, 'BIR Permit No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(100, 20, '0813-ELTRD-CAS-00211', 'B', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(55, 20, 'Date Issued:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(80, 20, (isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']),'F j, Y') : ''), 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    PDF::MultiCell(65, 20, 'Series No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, 'DR4000001-DR4000049', 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    PDF::MultiCell(65, 20, 'Printed Date:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, date_format(date_create($current_timestamp), 'm/d/Y'), 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);

    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

}
