<?php

namespace App\Http\Classes\modules\modulereport\roosevelt;

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
use DateTime;

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
    $companyid = $config['params']['companyid'];
    // $fields = ['radioprint', 'prepared', 'checked', 'radioreporttype', 'print'];
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'checked', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Order Form', 'value' => '0', 'color' => 'red'],
      ['label' => 'Order Form Recopy', 'value' => '1', 'color' => 'red']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'checked' and doc =? ", [$config['params']['doc']]);
    $paramstr = "select
      'PDFM' as print,
      '' as prepared,
      '' as checked,
      '0' as reporttype,
      '' as amountformat";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($trno)
  {
    $query = "select concat(left(head.docno,2),right(head.docno,9)) as docno ,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross,  stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      agent.clientname as agentname,head.shipto,cust.client,head.yourref
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where  head.doc='so' and head.trno='$trno'
      union all
      select concat(left(head.docno,2),right(head.docno,9)) as docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross,  stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
       agent.clientname as agentname,head.shipto,cust.client,head.yourref
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      where head.doc='so' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function report_pending_query($trno)
  {
    $query = "select concat(left(head.docno,2),right(head.docno,9)) as docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross,  stock.iss-stock.qa as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
       agent.clientname as agentname,head.shipto,cust.client,head.yourref
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join transnum as num on num.trno=head.trno
      where head.doc='so' and  head.trno='$trno' and  stock.iss>stock.qa and stock.void=0
      union all
      select concat(left(head.docno,2),right(head.docno,9)) as docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross,   stock.iss-stock.qa as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
       agent.clientname as agentname,head.shipto,cust.client,head.yourref
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join transnum as num on num.trno=head.trno
      where head.doc='so' and head.trno='$trno' and  stock.iss>stock.qa and stock.void=0 and num.postdate is not null order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_so_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      $reporttype = $params['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case '0': //order form
          return $this->default_orderform_PDF($params, $data);
          break;
        case '1': //recopy
          return $this->default_pending_so_PDF($params, $data);
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
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
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

    // $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
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
  public function default_pending_so_PDF($params, $data)
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

    $fontsize = 14;
    $font = "Courier";
    $fontbold = "CourierB";
    $border = "1px solid ";
    $trno = $params['params']['dataid'];
    $data = $this->report_pending_query($trno);
    $this->default_so_header_PDF($params, $data, $next = 0);

    $countarr = 0;
    PDF::SetCellPaddings(5, 5, 5, 5);
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 0)], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], '13', 0);
        $arr_desc = $this->reporter->fixcolumn([$data[$i]['itemname']], '51', 0);
        // $arr_rem = $this->reporter->fixcolumn([$data[$i]['rem']], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_desc]);
        for ($r = 0; $r < $maxrow; $r++) {
          $border = '';
          $border2 = '';
          if ($r == 0 && $maxrow == 1) {
            $border = 'TL'; // single-line row
            $border2 = 'LRT';
          } else {
            // PDF::SetCellPaddings(left, top, right, bottom);
            // PDF::SetCellPaddings(2, 1, 2, 1);
            $border = 'L'; // middle line(s)
            $border2 = 'LR';
          }
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), $border, 'C', false, 0, '', '', true, 0, false, true, 20, 'M', false);
          PDF::MultiCell(80, 20, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), $border, 'L', false, 0, '', '', true, 0, false, true, 20, 'M', false);
          PDF::MultiCell(360, 20, ' ' . (isset($arr_desc[$r]) ? $arr_desc[$r] : ''), $border, 'L', false, 0, '', '', true, 0, false, true, 20, 'M', false);
          PDF::MultiCell(200, 20, '', $border2, 'L', false, 1, '', '', true, 0, false, true, 20, 'M', false);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $next = 1;
            $this->default_footer($params, $data);
            PDF::SetCellPaddings(0, 0, 0, 0);
            $this->default_so_header_PDF($params, $data, $next);
            PDF::SetCellPaddings(5, 5, 5, 5);
          }
        }
      }

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(720, 0, '', 'T');

      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize);
      // PDF::MultiCell(75, 0, 'REMARKS: ', 'TLB', 'L', false, 0);
      // PDF::MultiCell(645, 0, $data[0]['rem'], 'TRB', 'L');


      $remarksLabel = 'REMARKS:';
      $remarksText  = strtoupper($data[0]['rem']);
      $labelW = 80;
      $textW  = 645;
      $lineH  = 5;
      $textHeight = PDF::getStringHeight($textW, $remarksText, false, true, '', 1, $lineH);
      PDF::MultiCell($labelW, $textHeight, $remarksLabel, 'TLB', 'L', false, 0);
      PDF::MultiCell($textW, $textHeight, $remarksText, 'TRB', 'L', false, 1);

      PDF::MultiCell(0, 0, "\n\n\n");


      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, $params['params']['dataparams']['checked'], 'B', 'L', false, 0);
      PDF::MultiCell(60, 0, '', '', '');

      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, 'Prepared By', '', 'C', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, 'Checked By', '', 'C', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false);

      PDF::MultiCell(0, 0, "\n");

      // $y = (float) 960;
      // $x = (float) 40;
      $y = (float) 945; //955
      $x = (float) 40;
      $username = $params['params']['user'];
      $datex = $this->othersClass->getCurrentTimeStamp();
      $date = new DateTime($datex);
      $cdate = $date->format('m/d/Y'); //04/21/2024
      $time = $date->format('h:i:s'); //08:02:25
      PDF::MultiCell(200, 0, 'Printed by: ' . $username, '', 'L', false, 0, $x, $y);
      PDF::MultiCell(100, 0, $cdate, '', 'L', false, 0, $x + 200, $y);
      PDF::MultiCell(100, 0, $time, '', 'L', false, 0, $x + 300, $y);
      PDF::MultiCell(250, 0, '', '', 'L', false, 0, $x + 400, $y);
      PDF::MultiCell(70, 0, 'Page ' . PDF::PageNo(), '', 'R', false, 1, $x + 650, $y);
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_so_header_PDF($params, $data, $next)
  {

    $companyid = $params['params']['companyid'];
    $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $fontsize = 14;
    $font = "Courier";
    $fontbold = "CourierB";

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // // $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    // // $y = (float)30;
    // // $x = PDF::GetX();
    // // $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
    // // // $logohere=isset($imagePath) ? PDF::Image($imagePath, 40, 20, 300, 65) :  'No image found';
    // // $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 30, 30, 90, 90) : 'No image found'; //x, y,width,height
    // // PDF::SetFont($font, '', 9);
    // // PDF::MultiCell(0, 0, '', '', 'L');
    // // PDF::SetFont($fontbold, '', 14);
    // // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C', false, 1,  '', $y + 5);
    // // PDF::SetFont($font, '', 13);
    // // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C', false, 1,  $x + 80, '');
    // // // PDF::SetFont($font, '', 9);
    // // // PDF::MultiCell(0, 0, '', '', 'L');
    // // // PDF::SetFont($fontbold, '', 14);
    // // // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C', false, 1,  '', $y + 5);
    // // // PDF::SetFont($font, '', 13);
    // // // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    // $yStart = 30;
    // $spaceBetween = 15;     // pagitan ng logo at text
    // $logoWidth = 90;
    // $logoHeight = 90;
    // $contentWidth = 720;    // 800 total width - 40 margins

    // //namesss
    // $companyName = strtoupper($headerdata[0]->name);
    // $address = strtoupper($headerdata[0]->address);
    // $tel = strtoupper($headerdata[0]->tel);

    // // sukat
    // PDF::SetFont($fontbold, '', 14);
    // $nameWidth = PDF::GetStringWidth($companyName);

    // PDF::SetFont($font, '', 13);
    // $addressWidth = PDF::GetStringWidth($address);
    // $telWidth = PDF::GetStringWidth($tel);

    // //  pinakamalapad na name dito
    // $textWidth = max($nameWidth, $addressWidth, $telWidth);

    // if ($textWidth >= 585) { //limt
    //   $spaceBetween = 3;
    // } else {
    //   $spaceBetween = 15;
    // }

    // $marginLeft = 40;
    // $textX = $marginLeft + ($contentWidth - $textWidth) / 2;
    // $textY = $yStart;

    // $logoX = $textX - $logoWidth - $spaceBetween;

    // $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
    // $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, $logoX,  20, $logoWidth, $logoHeight) : 'No image found'; //x, y,width,height

    // // var_dump($textWidth); //float(397.8)
    // PDF::SetFont($fontbold, '', 14);
    // PDF::SetXY($textX, $textY);
    // PDF::MultiCell($textWidth, 0, $companyName, 0, 'C', false);

    // PDF::SetFont($font, '', 13);

    // // Address
    // $buffer = 5;
    // if (!empty(trim($headerdata[0]->address))) {
    //   PDF::SetX($textX);
    //   PDF::MultiCell($textWidth + $buffer, 0, $address, 0, 'C', false);
    // }

    // if (!empty(trim($headerdata[0]->tel))) {
    //   PDF::SetX($textX);
    //   PDF::MultiCell($textWidth + $buffer, 0, $tel, 0, 'C', false);
    // }

    
    if($next == 1)  {
    $y = (float)20;
    $x = PDF::GetX();
    PDF::SetXY($x, $y);
   
    }else{
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(764, 0, '', '');
    }
 
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");



    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 0, 'ORDER FORM RECOPY', '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 0, 'Customer Code', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
    PDF::MultiCell(365, 0, (isset($data[0]['client']) ? $data[0]['client'] : ''), '', 'L', false, 0);
    PDF::MultiCell(80, 0, 'OF #', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
    PDF::MultiCell(105, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::MultiCell(140, 0, 'Customer Name', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
    PDF::MultiCell(365, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(80, 0, 'OF Date', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
   
    if (isset($data[0]['dateid'])) {
      $dateid = $data[0]['dateid'];
      $date = new DateTime($dateid);
      $cdate = $date->format('m/d/Y'); //04/21/2024
      PDF::MultiCell(105, 0, $cdate, '', 'L', false);
    }

    if ($next == 0) { //first page
      $add = isset($data[0]['address']) ? $data[0]['address'] : '';
        $maxChars = 43;
        $adds = strlen($add);
        $firstLine = '';
        $remaininglines = [];
        $addsz = '';

        if ($adds > $maxChars) {
            $firstLine = substr($add, 0, $maxChars);
            $remaining = substr($add, $maxChars);
            // Split remaining address into multiple lines without cutting words
            while (strlen($remaining) > $maxChars) {
                // Find the last space within the maxChars limit
                $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

                // If there's no space, just cut at maxChars
                if ($spacePos === false) {
                    $nextLine = substr($remaining, 0, $maxChars);
                    $remaining = substr($remaining, $maxChars);
                } else {
                    $nextLine = substr($remaining, 0, $spacePos);
                    $remaining = substr($remaining, $spacePos + 1);
                }

                $remainingLines[] = $nextLine;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remaining) > 0) {
                $remainingLines[] = $remaining;
            }
        } else {
            $addsz = $add;
        }


           if ($adds > $maxChars) {
            PDF::MultiCell(140, 0, 'Customer Address', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(365, 0, $firstLine, '', 'L', false, 0);
            PDF::MultiCell(80, 0, 'PO#', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(105, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false);
            // Loop through remaining lines and print them
            foreach ($remainingLines as $line) {
                      PDF::MultiCell(140, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(15, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(365, 0, $line, '', 'L', false, 0);
                      PDF::MultiCell(80, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(15, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(105, 0, '', '', 'L', false);

                    PDF::MultiCell(140, 0, 'Deliver to', '', 'L', false, 0);
                    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
                    PDF::MultiCell(565, 0, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), '', 'L', false);

                    PDF::MultiCell(140, 0, 'Salesman', '', 'L', false, 0);
                    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
                    PDF::MultiCell(565, 0, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), '', 'L', false);
            }
        } else {
            PDF::MultiCell(140, 0, 'Customer Address', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(365, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);
            PDF::MultiCell(80, 0, 'PO#', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(105, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false);

            PDF::MultiCell(140, 0, 'Deliver to', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(565, 0, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), '', 'L', false);

            PDF::MultiCell(140, 0, 'Salesman', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(565, 0, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), '', 'L', false);
        }
    }
    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($font, 'B', 11);
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::MultiCell(80, 0, 'QTY', 'TLB', 'C', false, 0);
    PDF::MultiCell(80, 0, 'UNIT', 'TLB', 'C', false, 0);
    PDF::MultiCell(360, 0, 'DESCRIPTION', 'TLB', 'C', false, 0);
    PDF::MultiCell(200, 0, 'REMARKS', 'TLRB', 'C', false);
  }

  public function default_orderform_PDF($params, $data)
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

    $fontsize = 14;
    $font = "Courier";
    $fontbold = "CourierB";
    $border = "1px solid ";
    $this->default_orderform_header_PDF($params, $data, $next = 0);
    $countarr = 0;
    PDF::SetCellPaddings(5, 5, 5, 5);
    $ys = PDF::getY();
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 0)], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], '13', 0);
        $arr_desc = $this->reporter->fixcolumn([$data[$i]['itemname']], '55', 0);
        // $arr_rem = $this->reporter->fixcolumn([$data[$i]['rem']], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_desc]);
        for ($r = 0; $r < $maxrow; $r++) {

          $border = '';
          $border2 = '';
          $height = '';

          if ($r == 0 && $maxrow == 1) {
            $border = 'TL'; // single-line row
            $border2 = 'LRT';
            $height = '20';
          } else {
            // PDF::SetCellPaddings(left, top, right, bottom);
            // PDF::SetCellPaddings(2, 1, 2, 1);
            $border = 'L'; // middle line(s)
            $border2 = 'LR';
            $height = '20';
          }

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, $height, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), $border, 'C', false, 0, '', '', true, 0, false, true, 20, 'M', false);
          PDF::MultiCell(80, $height, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), $border, 'L', false, 0, '', '', true, 0, false, true, 20, 'M', false);
          PDF::MultiCell(480, $height, ' ' . (isset($arr_desc[$r]) ? $arr_desc[$r] : ''), $border, 'L', false, 0, '', '', true, 0, false, true, 20, 'M', false);
          PDF::MultiCell(80, $height, '', $border2, 'L', false, 1, '', '', true, 0, false, true, 20, 'M', false);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $next = 1;
            $this->default_footer($params, $data);
            PDF::SetCellPaddings(0, 0, 0, 0);
            $this->default_orderform_header_PDF($params, $data, $next);
            PDF::SetCellPaddings(5, 5, 5, 5);
          }
        }
      }

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(720, 0, '', 'T');

      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($font, '', $fontsize);
      // PDF::MultiCell(75, 0, 'REMARKS: ', 'TLB', 'L', false, 0);
      // PDF::MultiCell(645, 0, $data[0]['rem'], 'TRB', 'L');

      $remarksLabel = 'REMARKS:';
      $remarksText  = strtoupper($data[0]['rem']);
      $labelW = 80; //75
      $textW  = 645;
      $lineH  = 5;
      $textHeight = PDF::getStringHeight($textW, $remarksText, false, true, '', 1, $lineH);
      PDF::MultiCell($labelW, $textHeight, $remarksLabel, 'TLB', 'L', false, 0);
      PDF::MultiCell($textW, $textHeight, $remarksText, 'TRB', 'L', false, 1);


      PDF::MultiCell(0, 0, "\n\n\n");


      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, $params['params']['dataparams']['checked'], 'B', 'L', false, 0);
      PDF::MultiCell(60, 0, '', '', '');

      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, 'Prepared By', '', 'C', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false, 0);
      PDF::MultiCell(240, 0, 'Checked By', '', 'C', false, 0);
      PDF::MultiCell(60, 0, '', '', '', false);

      PDF::MultiCell(0, 0, "\n");

      $y = (float) 945; //960
      $x = (float) 40;
      $username = $params['params']['user'];
      $datex = $this->othersClass->getCurrentTimeStamp();
      $date = new DateTime($datex);
      $cdate = $date->format('m/d/Y'); //04/21/2024
      $time = $date->format('h:i:s'); //08:02:25
      PDF::MultiCell(200, 0, 'Printed by: ' . $username, '', 'L', false, 0, $x, $y);
      PDF::MultiCell(100, 0, $cdate, '', 'L', false, 0, $x + 200, $y);
      PDF::MultiCell(100, 0, $time, '', 'L', false, 0, $x + 300, $y);
      PDF::MultiCell(250, 0, '', '', 'L', false, 0, $x + 400, $y);
      PDF::MultiCell(70, 0, 'Page ' . PDF::PageNo(), '', 'R', false, 1, $x + 650, $y);

      return PDF::Output($this->modulename . '.pdf', 'S');
    }
  }

  public function default_orderform_header_PDF($params, $data, $next)
  {

    $companyid = $params['params']['companyid'];
    $amtformat = $params['params']['dataparams']['amountformat'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $fontsize = 14;
    $font = "Courier";
    $fontbold = "CourierB";

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);
    // $y = (float)30;
    // $x = PDF::GetX();

    // $yStart = 30;
    // $spaceBetween = 15;     // pagitan ng logo at text
    // $logoWidth = 90;
    // $logoHeight = 90;
    // $contentWidth = 720;    // 800 total width - 40 margins

    // //namesss
    // $companyName = strtoupper($headerdata[0]->name);
    // $address = strtoupper($headerdata[0]->address);
    // $tel = strtoupper($headerdata[0]->tel);

    // // sukat
    // PDF::SetFont($fontbold, '', 14);
    // $nameWidth = PDF::GetStringWidth($companyName);

    // PDF::SetFont($font, '', 13);
    // $addressWidth = PDF::GetStringWidth($address);
    // $telWidth = PDF::GetStringWidth($tel);

    // //  pinakamalapad na name dito
    // $textWidth = max($nameWidth, $addressWidth, $telWidth);

    // if ($textWidth >= 585) { //limt
    //   $spaceBetween = 3;
    // } else {
    //   $spaceBetween = 15;
    // }

    // $marginLeft = 40;
    // $textX = $marginLeft + ($contentWidth - $textWidth) / 2;
    // $textY = $yStart;

    // $logoX = $textX - $logoWidth - $spaceBetween;

    // $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
    // $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, $logoX,  20, $logoWidth, $logoHeight) : 'No image found'; //x, y,width,height

    // // var_dump($textWidth); //float(397.8)
    // PDF::SetFont($fontbold, '', 14);
    // PDF::SetXY($textX, $textY);
    // PDF::MultiCell($textWidth, 0, $companyName, 0, 'C', false);

    // PDF::SetFont($font, '', 13);

    // // Address
    // $buffer = 5;
    // if (!empty(trim($headerdata[0]->address))) {
    //   PDF::SetX($textX);
    //   PDF::MultiCell($textWidth + $buffer, 0, $address, 0, 'C', false);
    // }

    // if (!empty(trim($headerdata[0]->tel))) {
    //   PDF::SetX($textX);
    //   PDF::MultiCell($textWidth + $buffer, 0, $tel, 0, 'C', false);
    // }

    if($next == 1)  {
    $y = (float)20;
    $x = PDF::GetX();
    PDF::SetXY($x, $y);
   
    }else{
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(764, 0, '', '');
    }
 
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 0, 'ORDER FORM', '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 0, 'Customer Code', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
    PDF::MultiCell(365, 0, (isset($data[0]['client']) ? $data[0]['client'] : ''), '', 'L', false, 0);
    PDF::MultiCell(80, 0, 'OF #', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
    PDF::MultiCell(105, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);


    PDF::MultiCell(140, 0, 'Customer Name', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
    PDF::MultiCell(365, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(80, 0, 'OF Date', '', 'L', false, 0);
    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
    $dateid = $data[0]['dateid'];
    $date = new DateTime($dateid);
    $cdate = $date->format('m/d/Y'); //04/21/2024
    PDF::MultiCell(105, 0, $cdate, '', 'L', false);

    if ($next == 0) { //first page
      // PDF::MultiCell(120, 0, 'Customer Address', '', 'L', false, 0);
      // PDF::MultiCell(10, 0, ':', '', 'L', false, 0);
      // PDF::MultiCell(590, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false);
    
      $add = isset($data[0]['address']) ? $data[0]['address'] : '';
        $maxChars = 43;
        $adds = strlen($add);
        $firstLine = '';
        $remaininglines = [];
        $addsz = '';

        if ($adds > $maxChars) {
            $firstLine = substr($add, 0, $maxChars);
            $remaining = substr($add, $maxChars);
            // Split remaining address into multiple lines without cutting words
            while (strlen($remaining) > $maxChars) {
                // Find the last space within the maxChars limit
                $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

                // If there's no space, just cut at maxChars
                if ($spacePos === false) {
                    $nextLine = substr($remaining, 0, $maxChars);
                    $remaining = substr($remaining, $maxChars);
                } else {
                    $nextLine = substr($remaining, 0, $spacePos);
                    $remaining = substr($remaining, $spacePos + 1);
                }

                $remainingLines[] = $nextLine;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remaining) > 0) {
                $remainingLines[] = $remaining;
            }
        } else {
            $addsz = $add;
        }


           if ($adds > $maxChars) {
            PDF::MultiCell(140, 0, 'Customer Address', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(365, 0, $firstLine, '', 'L', false, 0);
            PDF::MultiCell(80, 0, 'PO#', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(105, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false);
            // Loop through remaining lines and print them
            foreach ($remainingLines as $line) {
                      PDF::MultiCell(140, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(15, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(365, 0, $line, '', 'L', false, 0);
                      PDF::MultiCell(80, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(15, 0, '', '', 'L', false, 0);
                      PDF::MultiCell(105, 0, '', '', 'L', false);

                    PDF::MultiCell(140, 0, 'Deliver to', '', 'L', false, 0);
                    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
                    PDF::MultiCell(565, 0, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), '', 'L', false);

                    PDF::MultiCell(140, 0, 'Salesman', '', 'L', false, 0);
                    PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
                    PDF::MultiCell(565, 0, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), '', 'L', false);
            }
        } else {
            PDF::MultiCell(140, 0, 'Customer Address', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(365, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);
            PDF::MultiCell(80, 0, 'PO#', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(105, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false);

            PDF::MultiCell(140, 0, 'Deliver to', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(565, 0, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), '', 'L', false);

            PDF::MultiCell(140, 0, 'Salesman', '', 'L', false, 0);
            PDF::MultiCell(15, 0, ':', '', 'L', false, 0);
            PDF::MultiCell(565, 0, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), '', 'L', false);
        }

      // PDF::MultiCell(120, 0, 'Customer Address', '', 'L', false, 0);
      // PDF::MultiCell(10, 0, ':', '', 'L', false, 0);
      // PDF::MultiCell(390, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0);
      // PDF::MultiCell(80, 0, 'PO#', '', 'L', false, 0);
      // PDF::MultiCell(10, 0, ':', '', 'L', false, 0);
      // PDF::MultiCell(110, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false);


      // PDF::MultiCell(120, 0, 'Deliver to', '', 'L', false, 0);
      // PDF::MultiCell(10, 0, ':', '', 'L', false, 0);
      // PDF::MultiCell(590, 0, (isset($data[0]['shipto']) ? $data[0]['shipto'] : ''), '', 'L', false);

      // PDF::MultiCell(120, 0, 'Salesman', '', 'L', false, 0);
      // PDF::MultiCell(10, 0, ':', '', 'L', false, 0);
      // PDF::MultiCell(590, 0, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), '', 'L', false);
    }
    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($font, 'B', 11);
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::MultiCell(80, 0, 'QTY', 'TLB', 'C', false, 0);
    PDF::MultiCell(80, 0, 'UNIT', 'TLB', 'C', false, 0);
    PDF::MultiCell(480, 0, 'DESCRIPTION', 'TLB', 'C', false, 0); //520
    PDF::MultiCell(80, 0, 'REMARKS', 'TLRB', 'C', false);
  }

  public function default_footer($params, $data)
  {
    $fontsize = 11;
    $font = "Courier";
    $fontbold = "CourierB";

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');


    $y = (float) 945; //955
    $x = (float) 40;
    $username = $params['params']['user'];
    $datex = $this->othersClass->getCurrentTimeStamp();
    $date = new DateTime($datex);
    $cdate = $date->format('m/d/Y'); //04/21/2024
    $time = $date->format('h:i:s'); //08:02:25
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'Printed by: ' . $username, '', 'L', false, 0, $x, $y);
    PDF::MultiCell(100, 0, $cdate, '', 'L', false, 0, $x + 200, $y);
    PDF::MultiCell(100, 0, $time, '', 'L', false, 0, $x + 300, $y);
    PDF::MultiCell(250, 0, '', '', 'L', false, 0, $x + 400, $y);
    PDF::MultiCell(70, 0, 'Page ' . PDF::PageNo(), '', 'R', false, 1, $x + 650, $y);
  }
 
}
