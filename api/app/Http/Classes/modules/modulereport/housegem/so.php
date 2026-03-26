<?php

namespace App\Http\Classes\modules\modulereport\housegem;

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
  private $othersClass;
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
      ['label' => 'Loading Sheet Form', 'value' => 'loadingSheet', 'color' => 'red'],
      ['label' => 'Loading Map Form', 'value' => 'loadingMap', 'color' => 'red'],
      ['label' => 'Loading Sheet Per WH Form', 'value' => 'loadingWH', 'color' => 'red'],
      ['label' => 'Request Order', 'value' => 'requestOrder', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      'default' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($trno)
  {
    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, if(head.shipto='',head.address,head.shipto) as address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,stock.sortline,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, stock.rem as srem, stock.weight, truck.capacity,stock.isqty*stock.weight as totalkg,'' as dateapproved,concat(left(head.docno,2),'-',right(head.docno,5)) as sodocno
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join headinfotrans as info on info.trno=head.trno
      left join clientinfo as truck on truck.clientid=info.truckid
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, if(head.shipto='',head.address,head.shipto) as address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,stock.sortline,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, stock.rem as srem, stock.weight, truck.capacity,stock.isqty*stock.weight as totalkg,'' as dateapproved,concat(left(head.docno,2),'-',right(head.docno,5)) as sodocno
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join hheadinfotrans as info on info.trno=head.trno
      left join clientinfo as truck on truck.clientid=info.truckid
      where head.doc='so' and head.trno='$trno' order by sortline, line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function default_qry($trno, $type)
  {
    $sortby = 'sortline, line';
    switch ($type) {
      case 'requestOrder':
        $sortby = 'itemname,sodocno';
        break;
      case 'loadingWH':
        $sortby = 'whcode,barcode';
        break;
    }

    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, if(head.shipto='',head.address,head.shipto) as address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,stock.sortline,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, stock.rem as srem, stock.weight, truck.capacity,stock.isqty*stock.weight as totalkg,'' as dateapproved,concat(left(head.docno,2),'-',right(head.docno,5)) as sodocno,
      wh.client as whcode,wh.clientname as stockwhname
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join headinfotrans as info on info.trno=head.trno
      left join clientinfo as truck on truck.clientid=info.truckid
      left join client as wh on wh.clientid=stock.whid
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, if(head.shipto='',head.address,head.shipto) as address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,stock.sortline,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, stock.rem as srem, stock.weight, truck.capacity,stock.isqty*stock.weight as totalkg,'' as dateapproved,concat(left(head.docno,2),'-',right(head.docno,5)) as sodocno,
      wh.client as whcode,wh.clientname as stockwhname
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join hheadinfotrans as info on info.trno=head.trno
      left join clientinfo as truck on truck.clientid=info.truckid
      left join client as wh on wh.clientid=stock.whid
      where head.doc='so' and head.trno='$trno' order by " . $sortby;

    return $query;
  } //end fn  

  public function default_qry_summary($trno)
  {
    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, sum(stock.isqty) as qty,
      stock.uom, stock.disc, stock.ext, item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, stock.rem as srem, stock.weight, truck.capacity,sum(stock.isqty*stock.weight) as totalkg,
      '' as dateapproved,concat(left(head.docno,2),'-',right(head.docno,5)) as sodocno
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join headinfotrans as info on info.trno=head.trno
      left join clientinfo as truck on truck.clientid=info.truckid
      where head.trno='$trno'
      group by head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      head.dateid,head.terms, head.rem,head.agent,head.wh, item.barcode, item.itemname, stock.isamt, stock.amt,
      stock.uom, stock.disc, stock.ext, item.brand,client.clientname,
      item.sizeid,m.model_name,agent.clientname, stock.rem, stock.weight, truck.capacity,head.docno
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, sum(stock.isqty) as qty,
      stock.uom, stock.disc, stock.ext, item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model, left (agent.clientname,7) as agentname, stock.rem as srem, stock.weight, truck.capacity,sum(stock.isqty*stock.weight) as totalkg,
      '' as dateapproved,concat(left(head.docno,2),'-',right(head.docno,5)) as sodocno
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join client as agent on agent.client=head.agent
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join hheadinfotrans as info on info.trno=head.trno
      left join clientinfo as truck on truck.clientid=info.truckid
      where head.doc='so' and head.trno='$trno' 
      group by head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      head.dateid,head.terms, head.rem,head.agent,head.wh, item.barcode, item.itemname, stock.isamt, stock.amt,
      stock.uom, stock.disc, stock.ext, item.brand,client.clientname,
      item.sizeid,m.model_name,agent.clientname, stock.rem, stock.weight, truck.capacity,head.docno
      order by itemname";

    return $query;
  } //end fn  

  public function generateResult($config, $trno)
  {

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case 'loadingSheet':
        $query = $this->default_qry_summary($trno);
        break;

      default:
        $query = $this->default_qry($trno, $reporttype);
        break;
    }

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {

    switch ($params['params']['dataparams']['reporttype']) {
      case 'loadingSheet':
        return $this->loadingSheet_PDF($params, $data);
        break;
      case 'requestOrder':
        return $this->requestOrder_PDF($params, $data);
        break;
      case 'loadingMap':
        return $this->loadingMap_PDF($params, $data);
        break;
      case 'loadingWH':
        return $this->loadingWH_PDF($params, $data);
        break;
      default:
        if ($params['params']['dataparams']['print'] == "default") {
          return $this->default_so_layout($params, $data);
        } else if ($params['params']['dataparams']['print'] == "PDFM") {
          return $this->default_so_PDF($params, $data);
        }
        break;
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

  public function default_so_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(530, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(110, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Sales Person: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 0, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 0, '', '', 'L', false, 1, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(75, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);

    PDF::MultiCell(185, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(90, 0, "NOTES", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }

  public function default_so_PDF($params, $data)
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
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_so_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $qty = $data[$i]['qty'];
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $gross = $data[$i]['gross'];
        $disc = $data[$i]['disc'];
        $ext = $data[$i]['ext'];
        $snotes = $data[$i]['srem'];


        $arr_qty = $this->reporter->fixcolumn([number_format($qty, $decimalqty)], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '32', 0);
        $arr_snotes = $this->reporter->fixcolumn([$snotes], '15', 0);
        $arr_gross = $this->reporter->fixcolumn([number_format($gross, $decimalcurr)], '15', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '15', 0);
        $arr_ext = $this->reporter->fixcolumn([number_format($ext, $decimalprice)], '15', 0);
        $arr_snotes = $this->reporter->fixcolumn([$snotes], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_itemname, $arr_gross, $arr_disc, $arr_ext, $arr_snotes]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(75, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(185, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 15, ' ' . (isset($arr_snotes[$r]) ? $arr_snotes[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_gross[$r]) ? $arr_gross[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }


        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_so_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(620, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

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

  public function requestOrder_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalqty = 0;
    $totalweight = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->requestOrder_header_PDF($params, $data);


    $barcode = '';
    $subtotalqty = 0;
    $itemcount = 0;
    $checkbarcode = '';
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $gross = $data[$i]['gross'];
        $qty = $data[$i]['qty'];
        $uom = $data[$i]['uom'];
        $weight = number_format($data[$i]['weight'], $decimalqty);
        $weightperitem = $qty * $weight;

        $subtotalqty += $data[$i]['qty'];
        $itemcount += 1;

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_gross = $this->reporter->fixcolumn([number_format($gross, $decimalcurr)], '15', 0);
        $arr_qty = $this->reporter->fixcolumn([number_format($qty, $decimalqty)], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_weight = $this->reporter->fixcolumn([number_format($weight, $decimalqty)], '15', 0);
        $arr_weightperitem = $this->reporter->fixcolumn([$weightperitem], '7', 0);
        $arr_subtotalqty = $this->reporter->fixcolumn([$subtotalqty], '9', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_gross, $arr_qty, $arr_uom, $arr_weight, $arr_weightperitem, $arr_subtotalqty]);


        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(40, 15, $i + 1, 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(225, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          PDF::MultiCell(80, 15, ' ' . (isset($arr_gross[$r]) ? $arr_gross[$r] : ''), 'LTB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(45, 15, ' ' . (isset($arr_weight[$r]) ? $arr_weight[$r] : ''), 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          if (isset($data[$i + 1]['barcode'])) { // check if existing
            $checkbarcode = $data[$i + 1]['barcode']; //pass value
            if ($barcode != $checkbarcode) { //check current to next value
              PDF::MultiCell(80, 15, number_format($subtotalqty, $decimalqty), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              $subtotalqty = 0;
            } else { //equals
              PDF::MultiCell(80, 15, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            }
          } else {
            PDF::MultiCell(80, 15, number_format($subtotalqty, $decimalqty), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            $subtotalqty = 0;
          }


          PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          PDF::MultiCell(100, 15, ' ' . (isset($weightperitem) ? number_format($weightperitem, 6) : ''), 'LTRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];
        $totalweight += $weightperitem;

        if (intVal($i) + 1 == $page) {
          $this->requestOrder_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, "", 'LTB', 'L', false, 0);
    PDF::MultiCell(225, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(45, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", 'LTB', 'C', false, 0);
    PDF::MultiCell(150, 0, number_format($totalweight, 2), 'LTBR', 'C', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, "", 'LTB', 'L', false, 0);
    PDF::MultiCell(225, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(45, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(100, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(150, 0, number_format($data[0]['capacity'], 2) . ' TONS', 'LTBR', 'C', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, "", 'LTB', 'L', false, 0);
    PDF::MultiCell(225, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(45, 0, "TOTAL", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, number_format($totalqty, 2), 'LTB', 'C', false, 0);
    PDF::MultiCell(100, 0, "", 'LTB', 'C', false, 0);
    PDF::MultiCell(150, 0, "COD - " . number_format($totalext, $decimalcurr), 'LTBR', 'C', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, 'REMARKS: ', 'LT', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 0, $data[0]['rem'], 'R', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 0, '', 'LR', 'C', false, 1, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(720, 15, ' ' .  $data[0]['clientname'], 'LR', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(720, 0, "", 'LRB', 'C', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(155, 0, "PREPARED BY:", 'L', 'L', false, 0);
    PDF::MultiCell(60, 0, "", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "", '', 'C', false, 0);
    PDF::MultiCell(45, 0, "", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "RECEIVED BY:", '', 'R', false, 0);
    PDF::MultiCell(150, 0, "", 'R', 'C', false, 1);

    PDF::MultiCell(720, 0, "", 'LR', 'C', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(155, 0, $params['params']['dataparams']['prepared'], 'L', 'L', false, 0);
    PDF::MultiCell(60, 0, "", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "", '', 'C', false, 0);
    PDF::MultiCell(45, 0, "", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "", '', 'R', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], 'R', 'L', false, 1);

    PDF::MultiCell(720, 0, "", 'LR', 'C', false, 1);
    PDF::MultiCell(720, 0, "", 'LR', 'C', false, 1);

    PDF::MultiCell(720, 0, "", 'LBR', 'C', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function requestOrder_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 0, $this->modulename, 'TLR', 'C', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, "DATE: ", 'LTB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(430, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'LTB', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Docno #: ", 'LTB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(190, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'RTB', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, "No. ", 'LTB', 'L', false, 0);
    PDF::MultiCell(225, 0, "ITEMS", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "SELLING PRICE", 'LTB', 'C', false, 0);
    PDF::MultiCell(45, 0, "KGS", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "QTY", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "TOTAL QTY", 'LTB', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNITS", 'LTB', 'C', false, 0);
    PDF::MultiCell(100, 0, "WEIGHT", 'LTBR', 'C', false, 1);
  }

  public function loadingSheet_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(400, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0);
    PDF::MultiCell(140, 0, "", '', 'R', false, 0);
    PDF::MultiCell(180, 0, "HGC LOADING SHEET (LS)", 'LRTB', 'R', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "Customer Name: ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, "Address: ", '', 'C', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(230, 0, (isset($data[0]['addr']) ? $data[0]['addr'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Loading Date: ", '', 'C', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);

    PDF::MultiCell(250, 0, "Description", 'LTB', 'C', false, 0);
    PDF::MultiCell(60, 0, "UOM", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "QTY", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "TOTAL QTY", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "Crane (WT.)", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "Truck (WT.)", 'LTB', 'C', false, 0);
    PDF::MultiCell(90, 0, "WEIGHT", 'LTBR', 'C', false, 1);
  }

  public function loadingSheet_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalqty = 0;
    $totalweight = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->loadingSheet_header_PDF($params, $data);


    $barcode = '';
    $subtotalqty = 0;
    $itemcount = 0;
    $checkbarcode = '';
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $gross = $data[$i]['gross'];
        $qty = $data[$i]['qty'];
        $uom = $data[$i]['uom'];
        $weight = $data[$i]['weight'];
        $weightperitem = $qty * $weight;

        $subtotalqty += $data[$i]['qty'];
        $itemcount += 1;

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([number_format($qty, $decimalqty)], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_weightperitem = $this->reporter->fixcolumn([number_format($weightperitem, $decimalqty)], '18', 0);
        $arr_subtotalqty = $this->reporter->fixcolumn([number_format($subtotalqty, $decimalqty)], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname,  $arr_qty, $arr_uom, $arr_weightperitem, $arr_subtotalqty]); // $arr_weight, $arr_gross,

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          PDF::MultiCell(60, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          if (isset($data[$i + 1]['barcode'])) { // check if existing
            $checkbarcode = $data[$i + 1]['barcode']; //pass value
            if ($barcode != $checkbarcode) { //check current to next value
              PDF::MultiCell(80, 15, number_format($subtotalqty, $decimalqty), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              $subtotalqty = 0;
            } else { //equals
              PDF::MultiCell(80, 15, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            }
          } else {
            PDF::MultiCell(80, 15, number_format($subtotalqty, $decimalqty), 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            $subtotalqty = 0;
          }

          PDF::MultiCell(80, 15, '', 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, '', 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 15, ' ' . (isset($weightperitem) ? number_format($weightperitem, 6) : ''), 'LTRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];
        $totalweight += $weightperitem;

        if (intVal($i) + 1 == $page) {
          $this->loadingSheet_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "", '', 'L', false, 0);
    PDF::MultiCell(200, 0, "", '', 'L', false, 0);
    PDF::MultiCell(80, 0, "", '', 'C', false, 0);
    PDF::MultiCell(90, 0, "", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "TOTAL", 'LTB', 'C', false, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(160, 0, number_format($totalweight, 6), 'LTBR', 'R', false, 1);


    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(80, 0, "Prepared By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(90, 0, "Loaded By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(90, 0, "Verified", 'TLR', 'L', false, 0);
    PDF::MultiCell(100, 0, "Approved By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(190, 0, "Received By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(70, 0, "Verified By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(100, 0, "Approved By:", 'TLR', 'L', false, 1);

    PDF::MultiCell(80, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "Endorsed By:", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(190, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 1);


    PDF::MultiCell(80, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(190, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 1);


    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", '', 'L', false, 0);

    PDF::MultiCell(10, 0, "", '', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(50, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize - 5);
    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(60, 0, "Logistics Supervisor", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "Warehouse Checked", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "Warehouse Supervisor", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "Warehouse Manager", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "Truck Driver", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", '', 'L', false, 0);

    PDF::MultiCell(10, 0, "", '', 'L', false, 0);
    PDF::MultiCell(70, 0, "Plate", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(50, 0, "Outgoing", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "Logistics Manager", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 1);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'B', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(50, 0, "Compliance", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 1);

    PDF::MultiCell(720, 0, "", '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize - 3);
    PDF::MultiCell(400, 0, "DISTRIBUTION OF COPIES", '', 'C', false, 0);

    PDF::MultiCell(160, 0, "", 'TLR', 'L', false, 0);
    PDF::MultiCell(160, 0, "", 'TLR', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize - 3);
    PDF::MultiCell(153, 0, "WHITE-Warehouse Supervisor/Checker", '', 'C', false, 0);
    PDF::MultiCell(133, 0, "YELLOW-Main Gate Guard", '', 'C', false, 0);
    PDF::MultiCell(114, 0, "BLUE-HGC FIle", '', 'C', false, 0);

    PDF::SetFont($fontbold, '', $fontsize - 2);
    PDF::MultiCell(160, 0, "D.R. NO.:", 'BLR', 'L', false, 0);
    PDF::MultiCell(160, 0, "D.R. Date:", 'BLR', 'L', false, 1);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function loadingWH_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(400, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0);
    PDF::MultiCell(140, 0, "", '', 'R', false, 0);
    PDF::MultiCell(180, 0, "HGC LOADING SHEET (LS)", 'LRTB', 'R', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "Customer Name: ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, "Address: ", '', 'C', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(230, 0, (isset($data[0]['addr']) ? $data[0]['addr'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Loading Date: ", '', 'C', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1);

    PDF::MultiCell(0, 0, "\n");
  }

  public function loadingWH_tablehead_PDF($params, $wh)
  {

    PDF::MultiCell(300, 0, "WH: " . $wh, '', 'L', false, 1);

    PDF::MultiCell(250, 0, "Description", 'LTB', 'C', false, 0);
    PDF::MultiCell(60, 0, "UOM", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "QTY", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "TOTAL QTY", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "Crane (WT.)", 'LTB', 'C', false, 0);
    PDF::MultiCell(80, 0, "Truck (WT.)", 'LTB', 'C', false, 0);
    PDF::MultiCell(90, 0, "WEIGHT", 'LTBR', 'C', false, 1);
  }

  public function loadingWH_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalqty = 0;
    $totalweight = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->loadingWH_header_PDF($params, $data);
    $mainqry = $this->default_qry($params['params']['dataid'], '');

    $data = json_decode(json_encode($this->coreFunctions->opentable(
      "select barcode,itemname,sum(qty) as qty,uom,sum(weight) as weight,whcode,stockwhname from(" .
        $mainqry
        . ") as a
        group by barcode,itemname,uom,whcode,stockwhname
        order by whcode,itemname"
    )), true);

    $barcode = '';
    $subtotalqty = 0;
    $itemcount = 0;
    $checkbarcode = '';
    $checkwh = '';

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        if ($checkwh == '' || $checkwh != $data[$i]['whcode']) {
          PDF::SetFont($fontbold, '', $fontsize);
          $this->loadingWH_tablehead_PDF($params, $data[$i]['whcode'] . '~' . $data[$i]['stockwhname']);
        }
        $checkwh = $data[$i]['whcode'];
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = $data[$i]['qty'];
        $uom = $data[$i]['uom'];
        $weight = $data[$i]['weight'];
        $weightperitem = $qty * $weight;

        $subtotalqty += $data[$i]['qty'];
        $itemcount += 1;

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([number_format($qty, $decimalqty)], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
        $arr_weight = $this->reporter->fixcolumn([$weight], '15', 0);
        $arr_weightperitem = $this->reporter->fixcolumn([number_format($weightperitem, $decimalqty)], '13', 0);
        $arr_subtotalqty = $this->reporter->fixcolumn([$subtotalqty], '9', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_weight, $arr_weightperitem, $arr_subtotalqty]); //$arr_gross, 


        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);

          PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);


          PDF::MultiCell(60, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);


          if (isset($data[$i + 1]['barcode'])) { // check if existing
            $checkbarcode = $data[$i + 1]['barcode']; //pass value
            if ($barcode != $checkbarcode) { //check current to next value
              PDF::MultiCell(80, 15, $subtotalqty, 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              $subtotalqty = 0;
            } else { //equals
              PDF::MultiCell(80, 15, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            }
          } else {
            PDF::MultiCell(80, 15, $subtotalqty, 'LB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            $subtotalqty = 0;
          }

          PDF::MultiCell(80, 15, '', 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, '', 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 15, ' ' . (isset($weightperitem) ? number_format($weightperitem, 6) : ''), 'LTRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalqty += $data[$i]['qty'];
        $totalweight += $weightperitem;

        if (intVal($i) + 1 == $page) {
          $this->loadingWH_header_PDF($params, $data);
          $this->loadingWH_tablehead_PDF($params);
          $page += $count;
        }
      }
    }

    $sorefh = 0; //30 * $maxrow;

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $sorefh, "", 'LB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, $sorefh, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, $sorefh, "", 'B', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, $sorefh, "TOTAL", 'B', 'C', false, 0);
    PDF::MultiCell(80, $sorefh, number_format($totalqty, 2), 'LTB', 'C', false, 0);
    PDF::MultiCell(250, $sorefh, number_format($totalweight, 6), 'LTBR', 'R', false, 1);


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Prepared By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(90, 0, "Loaded By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(90, 0, "Verified", 'TLR', 'L', false, 0);
    PDF::MultiCell(100, 0, "Approved By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(190, 0, "Received By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(70, 0, "Verified By:", 'TLR', 'L', false, 0);
    PDF::MultiCell(100, 0, "Approved By:", 'TLR', 'L', false, 1);

    PDF::MultiCell(80, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "Endorsed By:", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(190, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 1);


    PDF::MultiCell(80, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(90, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(190, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'LR', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'LR', 'L', false, 1);


    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", '', 'L', false, 0);

    PDF::MultiCell(10, 0, "", '', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(50, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 1);

    /////////

    PDF::SetFont($font, '', $fontsize - 5);
    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(60, 0, "Logistics Supervisor", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "Warehouse Checked", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(70, 0, "Warehouse Supervisor", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "Warehouse Manager", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "Truck Driver", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", '', 'L', false, 0);

    PDF::MultiCell(10, 0, "", '', 'L', false, 0);
    PDF::MultiCell(70, 0, "Plate", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(50, 0, "Outgoing", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(80, 0, "Logistics Manager", '', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'R', 'L', false, 1);

    ///////////////////////

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'B', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(70, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(50, 0, "Compliance", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 0);

    PDF::MultiCell(10, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(10, 0, "", 'RB', 'L', false, 1);

    /////////

    PDF::MultiCell(720, 0, "", '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize - 3);
    PDF::MultiCell(400, 0, "DISTRIBUTION OF COPIES", '', 'C', false, 0);

    PDF::MultiCell(160, 0, "", 'TLR', 'L', false, 0);
    PDF::MultiCell(160, 0, "", 'TLR', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize - 3);
    PDF::MultiCell(153, 0, "WHITE-Warehouse Supervisor/Checker", '', 'C', false, 0);
    PDF::MultiCell(133, 0, "YELLOW-Main Gate Guard", '', 'C', false, 0);
    PDF::MultiCell(114, 0, "BLUE-HGC FIle", '', 'C', false, 0);

    PDF::SetFont($fontbold, '', $fontsize - 2);
    PDF::MultiCell(160, 0, "D.R. NO.:", 'BLR', 'L', false, 0);
    PDF::MultiCell(160, 0, "D.R. Date:", 'BLR', 'L', false, 1);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function loadingMap_header_PDF($params, $data)
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
    PDF::AddPage('p', [1000, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(400, 0, strtoupper($headerdata[0]->name), '', 'L', false, 0);
    PDF::MultiCell(340, 0, "", '', 'R', false, 0);
    PDF::MultiCell(180, 0, "HGC LOADING MAP", 'LRTB', 'R', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, "Customer Name: ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'C', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(320, 0, (isset($data[0]['addr']) ? $data[0]['addr'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, "Loading Date: ", '', 'C', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);

    PDF::MultiCell(60, 0, "TERMS", 'LT', 'C', false, 0); //10
    PDF::MultiCell(90, 0, "S.O.", 'LT', 'C', false, 0); //20
    PDF::MultiCell(170, 0, "CUSTOMER NAME", 'LT', 'C', false, 0); //60
    PDF::MultiCell(180, 0, "ITEM", 'LT', 'C', false, 0); //60
    PDF::MultiCell(60, 0, "QTY", 'LT', 'C', false, 0); //10
    PDF::MultiCell(60, 0, "UNIT PRICE", 'LT', 'C', false, 0); //10
    PDF::MultiCell(90, 0, "AMOUNT", 'LT', 'C', false, 0); //10
    PDF::MultiCell(60, 0, "WEIGHT", 'LT', 'C', false, 0); //10
    PDF::MultiCell(70, 0, "TOTAL", 'LT', 'C', false, 0); //10
    PDF::MultiCell(80, 0, "DATE", 'LTR', 'C', false, 1);


    PDF::MultiCell(60, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(90, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(170, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(180, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(90, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(60, 0, "/PC", 'LB', 'C', false, 0);
    PDF::MultiCell(70, 0, "KG", 'LB', 'C', false, 0);
    PDF::MultiCell(80, 0, "APPROVED", 'LBR', 'C', false, 1);
  }

  public function loadingMap_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 900;
    $totalext = 0;
    $totalqty = 0;
    $totalweight = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->loadingMap_header_PDF($params, $data);

    $query = $this->default_qry($params['params']['dataid'], '');

    $total = json_decode(json_encode($this->coreFunctions->opentable(
      "select sum(a.ext) as ext,sum(a.totalkg) as totalkg from(" .
        $query
        . ") as a"
    )), true);

    PDF::SetTextColor(255, 0, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(90, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(170, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(180, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(90, 0, number_format($total[0]['ext'], 2), 'L', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(70, 0, number_format($total[0]['totalkg'], 2), 'L', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LR', 'C', false, 1);

    PDF::SetTextColor(0, 0, 0, 100);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(90, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(170, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(180, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(90, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'L', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LR', 'C', false, 1);

    $barcode = '';
    $subtotalqty = 0;
    $itemcount = 0;
    $checkbarcode = '';
    $docno = '';
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $terms = $data[$i]['terms'];
        $sodocno = $data[$i]['sodocno'];
        if ($docno == '') {
          $docno = $sodocno;
        }
        $customer = $data[$i]['clientname'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $gross = number_format($data[$i]['gross'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);
        $weight = number_format($data[$i]['weight'], $decimalqty);
        $totalkg = number_format($data[$i]['totalkg'], $decimalqty);
        $dateapproved = $data[$i]['dateapproved'];
        $arr_terms = $this->reporter->fixcolumn([$terms], '10', 0);
        $arr_sodocno = $this->reporter->fixcolumn([$sodocno], '25', 0);
        $arr_customer = $this->reporter->fixcolumn([$customer], '30', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '15', 0);
        $arr_gross = $this->reporter->fixcolumn([$gross], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '20', 0);
        $arr_weight = $this->reporter->fixcolumn([$weight], '20', 0);
        $arr_totalkg = $this->reporter->fixcolumn([$totalkg], '15', 0);
        $arr_dateapproved = $this->reporter->fixcolumn([$dateapproved], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_terms, $arr_sodocno, $arr_customer, $arr_itemname, $arr_qty, $arr_gross, $arr_ext, $arr_weight, $arr_totalkg, $arr_dateapproved]);

        $display = 0;
        if ($docno != $sodocno) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(60, 0, ' ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 0, ' ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(170, 0, ' ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(180, 0, ' ', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 0, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 0, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 0, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 0, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 0, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ', 'LR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $docno = $sodocno;
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);

          PDF::MultiCell(60, 0, ' ' . (isset($arr_terms[$r]) ? $arr_terms[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 0, ' ' . (isset($arr_sodocno[$r]) ? $arr_sodocno[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(170, 0, ' ' . (isset($arr_customer[$r]) ? $arr_customer[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(180, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          PDF::MultiCell(60, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 0, ' ' . (isset($arr_gross[$r]) ? $arr_gross[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(90, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          PDF::MultiCell(60, 0, ' ' . (isset($arr_weight[$r]) ? $arr_weight[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

          if ($display == 0) {
            PDF::MultiCell(70, 0, ' ' . (isset($arr_totalkg[$r]) ? $arr_totalkg[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_dateapproved[$r]) ? $arr_dateapproved[$r] : ''), 'LR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            $display = 1;
          } else {
            PDF::MultiCell(70, 0, ' ', 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ', 'LR', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          }
        }

        $display = 0;

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];

        if (PDF::getY() >= $page) {
          $this->row1($font, $fontsize);
          $this->loadingMap_header_PDF($params, $data);
        }
      }
    }

    PDF::MultiCell(920, 0, "", 'T', 'L', false, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function row1($font, $fontsize)
  {
    PDF::SetFont($font, '', $fontsize);

    PDF::MultiCell(60, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(90, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(170, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(180, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(90, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(60, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(70, 0, "", 'LB', 'C', false, 0);
    PDF::MultiCell(80, 0, "", 'LBR', 'C', false, 1);
  }
}
