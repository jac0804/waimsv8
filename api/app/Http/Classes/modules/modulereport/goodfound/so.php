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
    $fields = ['radioprint', 'radioreporttype', 'radiostatus', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'received.label', 'Noted By');
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
      ['label' => 'SO Ledger Detail', 'value' => 'ledger', 'color' => 'red']
    ]);
    data_set($col1, 'radiostatus.options', [
      ['label' => 'MKTG', 'value' => 'mktg', 'color' => 'red'],
      ['label' => 'FINANCIAL', 'value' => 'finance', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      'default' as reporttype,
      'mktg' as status,
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
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, cust.area, head.due,
        
      substring(cust.areacode,1,1) as area1,
      substring(cust.areacode,2,1) as area2,
      substring(cust.areacode,3,1) as area3,
      9 as not4,
      right(year(head.dateid),1) as not5,
      case when month(head.dateid)<10 then 0 else left(month(head.dateid),1) end as not6,
      case when month(head.dateid)<10 then month(head.dateid) else right(month(head.dateid),1) end as not7,
      9 as not8,
      substring(head.docno,14,1) as not9,
      substring(head.docno,15,1) as not10,
      substring(head.docno,6,1) as bir4,
      substring(head.docno,7,1) as bir5,
      substring(head.docno,8,1) as bir6,
      
      substring(head.docno,9,1) as bir7,
      substring(head.docno,10,1) as bir8,
      
      substring(head.docno,11,1) as bir9,
      substring(head.docno,12,1) as bir10,
      
      substring(head.docno,13,1) as bir11,
      substring(head.docno,14,1) as bir12,
      substring(head.docno,15,1) as bir13,

      head.salestype

      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, cust.area, head.due,
      
      substring(cust.areacode,1,1) as area1,
      substring(cust.areacode,2,1) as area2,
      substring(cust.areacode,3,1) as area3,
      9 as not4,
      right(year(head.dateid),1) as not5,
      case when month(head.dateid)<10 then 0 else left(month(head.dateid),1) end as not6,
      case when month(head.dateid)<10 then month(head.dateid) else right(month(head.dateid),1) end as not7,
      9 as not8,
      substring(head.docno,14,1) as not9,
      substring(head.docno,15,1) as not10,
      substring(head.docno,6,1) as bir4,
      substring(head.docno,7,1) as bir5,
      substring(head.docno,8,1) as bir6,
      
      substring(head.docno,9,1) as bir7,
      substring(head.docno,10,1) as bir8,
      
      substring(head.docno,11,1) as bir9,
      substring(head.docno,12,1) as bir10,
      
      substring(head.docno,13,1) as bir11,
      substring(head.docno,14,1) as bir12,
      substring(head.docno,15,1) as bir13,

      head.salestype

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

  public function ledger_SO_query($trno)
  {
    $query = "select
    head.clientname as distributor,head.docno as sodocno,i.barcode,stock.isqty as sobalance,
    sjhead.dateid,hinfo.cwano as cwa,sjhead.docno as cwor,sjstock.isqty as sjqty,hinfo.plateno
    from lahead as sjhead
    left join lastock as sjstock on sjstock.trno=sjhead.trno
    left join hsostock as stock on stock.trno=sjstock.refx and stock.line=sjstock.linex
    left join hsohead as head on head.trno=stock.trno
    left join client as cust on cust.client=head.client
    left join item as i on i.itemid=stock.itemid
    left join cntnuminfo as hinfo on hinfo.trno = sjhead.trno
    where head.doc='SO' and head.trno=$trno
    union all
    select
    head.clientname as distributor,head.docno as sodocno,i.barcode,stock.isqty as sobalance,
    sjhead.dateid,hinfo.cwano as cwa,sjhead.docno as cwor,sjstock.isqty as sjqty,hinfo.plateno
    from glhead as sjhead
    left join glstock as sjstock on sjstock.trno=sjhead.trno
    left join hsostock as stock on stock.trno=sjstock.refx and stock.line=sjstock.linex
    left join hsohead as head on head.trno=stock.trno
    left join client as cust on cust.client=head.client
    left join item as i on i.itemid=stock.itemid
    left join hcntnuminfo as hinfo on hinfo.trno = sjhead.trno
    where head.doc='SO' and head.trno=$trno
    order by barcode,cwor";
    return $query;
  }

  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case 'ledger':
        $qry = $this->ledger_SO_query($params['params']['dataid']);
        return $this->ledger_SO_PDF($params, $qry);
        break;

      default:
        return $this->default_SO_PDF($params, $data);
        break;
    }
  }

  public function ledger_SO_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
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


    PDF::SetFont($font, 'B', 14);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    PDF::MultiCell(0, 0, "\n", '');
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(720, 25, "SO Ledger Detail", '', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);


    PDF::MultiCell(720, 0, " ", '', 'L', false, 1);
    PDF::MultiCell(720, 0, " ", '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(180, 0, "DISTRIBUTOR : ", '', 'L', false, 0);
    PDF::MultiCell(540, 0, (isset($data[0]['distributor']) ? $data[0]['distributor'] : ''), '', 'L', false, 1);

    PDF::MultiCell(180, 0, "SALES ORDER : ", '', 'L', false, 0);
    PDF::MultiCell(540, 0, (isset($data[0]['sodocno']) ? $data[0]['sodocno'] : ''), '', 'L', false, 1);

    // >>>>>> FIX SET UP TEXT

  }

  public function ledger_SO_PDF($params, $qry)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;

    $data = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }
    $this->ledger_SO_header_PDF($params, $data);


    PDF::MultiCell(720, 0, " ", '', 'L', false, 1);
    PDF::MultiCell(720, 0, " ", '', 'L', false, 1);

    $countarr = 0;
    $barcode = '';
    $count = 0;
    $display = 0;
    $sobalance = 0;
    $runningbal = 0;
    $datedisplay = 0;
    PDF::SetCellPaddings(2, 2, 2, 2);
    for ($i = 0; $i < count($data); $i++) {

      if ($barcode == '') {
        $barcode = $data[$i]['barcode'];
        $sobalance = $data[$i]['sobalance'];
        $runningqty = 0;
      }
      $count = $i + 1;


      if ($barcode != $data[$i]['barcode']) {
        $barcode = $data[$i]['barcode'];
        $sobalance = $data[$i]['sobalance'];
        $runningqty = 0;
        $datedisplay = 0;

        PDF::MultiCell(720, 0, " ", '', 'L', false, 1);
        PDF::MultiCell(720, 0, " ", '', 'L', false, 1);
        PDF::MultiCell(720, 0, "DESCRIPTION : " . $barcode, '', 'L', false, 1);
        PDF::MultiCell(720, 0, "BALANCE : " . $data[$i]['sobalance'], '', 'L', false, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(110, 20, 'DATE', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, 'CWA', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, 'CWOR', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, 'QTY', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, 'PLATENO', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(120, 20, 'BALANCE', 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      } else {
        if ($display == 0) {
          PDF::MultiCell(720, 0, "DESCRIPTION : " . $barcode, '', 'L', false, 1);
          PDF::MultiCell(720, 0, "BALANCE : " . $data[$i]['sobalance'], '', 'L', false, 1);

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(110, 20, 'DATE', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(110, 20, 'CWA', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(110, 20, 'CWOR', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(110, 20, 'QTY', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(110, 20, 'PLATENO', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(120, 20, 'BALANCE', 'TLRB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
          $display = 1;
        }
      }




      $maxrow = 1;

      $dateid = $data[$i]['dateid'];
      $cwa = $data[$i]['cwa'];
      $cwor = $data[$i]['cwor'];
      $sjqty = number_format($data[$i]['sjqty'], 2);
      $plateno = $data[$i]['plateno'];

      $runningqty +=  $data[$i]['sjqty'];

      $runningbal =  $sobalance - $runningqty;
      $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
      $arr_cwa = $this->reporter->fixcolumn([$cwa], '15', 0);
      $arr_cwor = $this->reporter->fixcolumn([$cwor], '15', 0);
      $arr_sjqty = $this->reporter->fixcolumn([$sjqty], '15', 0);
      $arr_plateno = $this->reporter->fixcolumn([$plateno], '20', 0);
      $arr_runningbal = $this->reporter->fixcolumn([number_format($runningbal, 2)], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_dateid, $arr_cwa, $arr_cwor, $arr_sjqty, $arr_plateno, $arr_runningbal]);

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        if ($datedisplay == 0) {
          PDF::MultiCell(110, 20, ' ' . (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          $datedisplay = 1;
        } else {
          PDF::MultiCell(110, 20, ' ', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        }

        PDF::MultiCell(110, 20, ' ' . (isset($arr_cwa[$r]) ? $arr_cwa[$r] : ''), 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, ' ' . (isset($arr_cwor[$r]) ? $arr_cwor[$r] : ''), 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, ' ' . (isset($arr_sjqty[$r]) ? $arr_sjqty[$r] : ''), 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(110, 20, ' ' . (isset($arr_plateno[$r]) ? $arr_plateno[$r] : ''), 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(120, 20, ' ' . (isset($arr_runningbal[$r]) ? $arr_runningbal[$r] : ''), 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      }
    }




    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function default_SO_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;
    $qry = "select left(name,29) as name1,substring(name,29,length(name)) as name2,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, 'B', 14);
    PDF::MultiCell(300, 0, strtoupper($headerdata[0]->name1), '', 'C', 0, 0, '250', '10');
    PDF::MultiCell(300, 0, strtoupper($headerdata[0]->name2), '', 'C', 0, 1, '250', '30');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(720, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(720, 25, strtoupper($this->modulename), '', 'C', 0, 1, '', '90', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 50, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 50, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 50, '', 'TBLR', 'C', 0, 1, '', '', true, 0, false, true, 0, 'M', true);

    // >>>>>> FIX SET UP TEXT
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(80, 0, "DISTRIBUTOR", '', 'L', false, 0, '50',  '118');
    PDF::MultiCell(80, 0, "TIN : ", '', 'L', false, 0, '50',  '148');
    PDF::MultiCell(80, 0, "SALES ORDER", '', 'L', false, 0, '490',  '123');
    PDF::MultiCell(80, 0, "DATE ISSUED", '', 'L', false, 0, '510',  '173');
    PDF::MultiCell(80, 0, "ADDRESS", '', 'L', false, 1, '50',  '173');
    PDF::MultiCell(200, 0, "AREA OF DISTRUBUTION", '', 'L', false, 1, '50',  '218');
    PDF::MultiCell(80, 0, "DATE EXPIRE", '', 'L', 0, 1, '510',  '218');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '50',  '133', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0, '80',  '148', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '50',  '188', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['area']) ? $data[0]['area'] : ''), '', 'L', false, 0, '50',  '233', true, 0, false, true, 0, 'T', true);

    $bir = 1;
    if ($bir == 0) {
      PDF::MultiCell(35, 0, '', '', 'L', 0, 0, '500', '138');

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
    } else {
      PDF::MultiCell(15, 0, '', '', 'L', 0, 0, '500', '138');

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

    PDF::MultiCell(0, 20, (isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']), "m/d/Y") : ''), '', 'L', false, 0, '610',  '188', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(0, 20, (isset($data[0]['due']) ? date_format(date_create($data[0]['due']), "m/d/Y") : ''), '', 'L', false, 0, '610',  '233', true, 0, false, true, 0, 'T', true);

    // VERTICAL LINE
    PDF::MultiCell(0, 148, '', 'L', 'C', 0, 1, '480', '116', true, 0, false, true, 0, 'M', true);

    // END FIX SETUP
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(320, 25, "PRODUCTS ORDERED", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 25, "QUANTITY", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 25, "UNIT PRICE", 'TBLR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 25, "AMOUNT", 'TBLR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
  }

  public function default_SO_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_SO_header_PDF($params, $data);

    $countarr = 0;
    PDF::SetCellPaddings(2, 2, 2, 2);
    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $itemname = $data[$i]['itemname'];
      $qty = number_format($data[$i]['qty'], 2);
      $amt = number_format($data[$i]['netamt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '15', 0);
      $arr_amt = $this->reporter->fixcolumn([$amt], '15', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '20', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_amt, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(320, 20, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(120, 20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(120, 20, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(160, 20, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
      }

      $totalext += $data[$i]['ext'];
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(320, 20, ' ', 'TLRB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 20, ' ', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 20, ' ', 'TLRB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 20, ' ', 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(560, 20, 'TOTAL AMOUNT', 'TLRB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(160, 20, number_format($totalext, $decimalcurr), 'TLRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 20, 'The total amount of this SALES ORDER is:', 'TLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 20, $this->reporter->ftNumberToWordsConverter($totalext,  false), 'LRB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);


    PDF::SetFont($fontbold, '', $fontsize);

    $salestype = $data[0]['salestype'];
    $label1 = 'DISTRIBUTOR';
    $label2 = 'picked-up';
    if (strtoupper($salestype) == 'DELIVER') {
      $label1 = 'MANUFACTURER';
      $label2 = 'delivered';
    }


    PDF::MultiCell(720, 0, 'Terms & Condition:', 'TLR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 0, '1. The withdraw of products will be by installments executed in the form of CEMENT WITHDRAWAL AUTHORIZATION (CWA) confirmed by', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 0, '    ' . $label1 . ' and CEMENT WITHDRAWAL ORDER RECEIPT (CWOR) received by their representative (Hauler) shall automatically', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 0, '    form a part of this SALES ORDER.', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 0, '2.  The payment should be made upon confirmation of the SALES ORDER by a dated or postdated check. The check should be cashable w/ 30 days.', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 0, '3.  The products are to be ' . $label2 . ' by the assigned hauler of the ' . $label1 . '.', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(720, 0, '4.  This SALES ORDER is non-transferable and shall not to be used for other transactions.', 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize);
    PDF::SetCellPaddings(4, 4, 4, 4);

    $prepared = $params['params']['dataparams']['prepared'];
    $received = $params['params']['dataparams']['received'];
    $approved = $params['params']['dataparams']['approved'];

    PDF::startTransaction();
    PDF::MultiCell(240, 10, 'PREPARED BY ', 'TL', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 10, 'NOTED BY', 'T', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 10, 'APPROVED BY', 'TR', 'C', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::MultiCell(240, 40, $prepared, 'L', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 40, $received, '', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 40, $approved, 'R', 'C', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::MultiCell(240, 20, "MARKETING DEP\'T", 'LB', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, "FINANCE DEP\'T", 'B', 'C', 0, 0, '', '', true, 0, true, true, 0, 'T', true);
    PDF::MultiCell(240, 20, "S.V.P./PRESIDENT", 'RB', 'C', 0, 1, '', '', true, 0, true, true, 0, 'T', true);

    PDF::SetCellPaddings(0, 4, 0, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(65, 20, 'BIR Permit No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(100, 20, '0813-ELTRD-CAS-00211', 'B', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(55, 20, 'Date Issued:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(80, 20, (isset($data[0]['dateid']) ? date_format(date_create($data[0]['dateid']), 'F j, Y') : ''), 'B', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);

    $status = $params['params']['dataparams']['status'];

    if ($status == 'mktg') {
      PDF::MultiCell(415, 0, 'MKTG DEPT', '', 'R', false, 1, '',  '');
    } else {

      PDF::MultiCell(415, 0, 'FINANCIAL DEPT', '', 'R', false, 1, '',  '');
    }


    PDF::MultiCell(65, 20, 'Series No.:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, 'SO4000001-SO4000049', 'B', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(410, 0, 'MKT10.02 14SEP REV3 RET3', '', 'R', false, 1, '',  '');

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    PDF::MultiCell(65, 20, 'Printed Date:', '', 'L', false, 0, '',  '', true, 0, true, true, 0, 'M', true);
    PDF::MultiCell(245, 20, date_format(date_create($current_timestamp), 'm/d/Y'), 'B', 'L', false, 1, '',  '', true, 0, true, true, 0, 'M', true);



    PDF::commitTransaction();



    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
