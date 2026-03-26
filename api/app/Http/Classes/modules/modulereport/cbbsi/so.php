<?php

namespace App\Http\Classes\modules\modulereport\cbbsi;

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
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'radioreporttype', 'checked', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.label', 'Print as');
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Sales Order', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Sales Order WS', 'value' => '1', 'color' => 'orange'],

    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $signatories = $this->othersClass->getSignatories($config);
    $checked = '';
    $approved = '';
    $received =  '';
    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'checked':
          $checked = $value->fieldvalue;
          break;
        case 'approved':
          $approved = $value->fieldvalue;
          break;
        case 'received':
          $received = $value->fieldvalue;
          break;
      }
    }

    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '" . $checked . "' as checked,
      '" . $approved . "' as approved,
      '" . $received . "' as received,
      '0' as reporttype"
    );
  }

  public function report_default_query($trno)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,agent.clientname as agentname,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model,date(head.due) as due,head.yourref,head.ourref, info.trnxtype,
      ifnull(uom.factor,1) as factor
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join client as agent on agent.client=head.agent
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join headinfotrans as info on info.trno=head.trno
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,agent.clientname as agentname,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model,date(head.due) as due,head.yourref,head.ourref,info.trnxtype,
      ifnull(uom.factor,1) as factor
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join client as agent on agent.client=head.agent
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join hheadinfotrans as info on info.trno=head.trno
      where head.doc='so' and head.trno='$trno' order by line";
    


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', -1);

    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_so_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      $reporttype = $params['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case 0: // so 
          return $this->default_so_PDF($params, $data);
          break;
        case 1: // so ws
          return $this->default_so_ws_PDF($params, $data);
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

  public function default_so_header($params, $data)
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
    $str .= $this->reporter->col('Credit Approved By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    $strlen = strlen($data[0]['address']);
    $add_lines = $strlen / 80;
    // $count = 0;
    // $add_part = 0;
    // $constant = 79;

    if (!empty($data[0]['address'])) {
      $maxrow = 1;
      $address = $data[0]['address'];
      $arr_address = $this->reporter->fixcolumn([$address], '80', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
      for ($r = 0; $r < $maxrow; $r++) {

        if ($r == 0) {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), 'B', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, 'Terms: ', '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');
          
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, '', '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, '', '', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, "Due: ", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($data[0]['due']) ? $data[0]['due'] : ''), 'B', 'L', false, 1, '',  '');
        } else {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, '', '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, '', '', 'L', false, 1, '',  '');
        }
      }


      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', 2);
      PDF::MultiCell(470, 0, '', 'T', 'L', false, 0, '',  '');
      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(50, 0, '', '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', 2);
      PDF::MultiCell(100, 0, '', '', 'L', false);
    }

    // $add = '';
    // if(isset($data[0]['address'])){
    //   $strlen = strlen($data[0]['address']);
    //   $add_lines = $strlen / 80;
    //   $count = 0;
    //   $add_part = 0;
    //   $constant = 79;

    //   for ($i=0; $i < $add_lines; $i++) { 

    //     $add_part += $constant;
    //     $add = substr($data[0]['address'],$count,$add_part);
    //     // if($i==0){
    //     //   PDF::SetFont($fontbold, '', $fontsize);
    //     //   PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($font, '', $fontsize);
    //     //   PDF::MultiCell(470, 0, $add, '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($fontbold, '', $fontsize);
    //     //   PDF::MultiCell(50, 0, 'Terms: ', '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($font, '', $fontsize);
    //     //   PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');

    //     // }else{
    //     //   PDF::SetFont($fontbold, '', $fontsize);
    //     //   PDF::MultiCell(80, 0, '', '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($font, '', $fontsize);
    //     //   PDF::MultiCell(470, 0, $add, '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($fontbold, '', $fontsize);
    //     //   PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($font, '', $fontsize);
    //     //   PDF::MultiCell(100, 0, '', '', 'L', false, 1, '',  '');
    //     // }
    //     //tester
    //     // if($i==0){
    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(80, 10, $i, '', 'L', false, 0, '',  '');
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(470, 10, $add, '', 'L', false, 0, '',  '');
    //       // PDF::MultiCell(470, 20, $count.'~'.$add_part, '', 'L', false, 0, '',  '');
    //       PDF::SetFont($fontbold, '', $fontsize);
    //       PDF::MultiCell(50, 10, "", '', 'L', false, 0, '',  '');
    //       PDF::SetFont($font, '', $fontsize);
    //       PDF::MultiCell(100, 10, '', '', 'L', false);
    //     // }else{
    //     //   PDF::SetFont($fontbold, '', $fontsize);
    //     //   PDF::MultiCell(80, 0, $i, '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($font, '', $fontsize);
    //     //   PDF::MultiCell(470, 0, 'zxc', '', 'L', false, 0, '',  '');
    //     //   // PDF::MultiCell(470, 0, $count.'~'.$add_part, '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($fontbold, '', $fontsize);
    //     //   PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    //     //   PDF::SetFont($font, '', $fontsize);
    //     //   PDF::MultiCell(100, 0, '', '', 'L', false, 1, '',  '');
    //     // }


    //     $count += $constant;

    //   }

    //   PDF::SetFont($fontbold, '', 2);
    //   PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    //   PDF::SetFont($font, '', 2);
    //   PDF::MultiCell(470, 0, '', 'T', 'L', false, 0, '',  '');
    //   PDF::SetFont($fontbold, '', 2);
    //   PDF::MultiCell(50, 0, '', '', 'L', false, 0, '',  '');
    //   PDF::SetFont($font, '', 2);
    //   PDF::MultiCell(100, 0, '', '', 'L', false);

    // }

    $agentname = '';
    if (isset($data[0]['agentname'])) {
      $agentname = (isset($data[0]['agentname']) ? $data[0]['agentname'] : '');
    }


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Agent: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, $agentname, 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(25, 0, '', '', 'L', false, 0, '',  '');
    PDF::MultiCell(50, 0, "Yourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(25, 0, '', '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (isset($data[0]['wh']) ? $data[0]['wh'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Trnx Type:", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 0, (isset($data[0]['trnxtype']) ? $data[0]['trnxtype'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
    PDF::MultiCell(700, 0, '', '');
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "CODE", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_so_PDF($params, $data)
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
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $unitprice =  number_format($data[$i]['gross'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_unitprice = $this->reporter->fixcolumn([$unitprice], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_barcode, $arr_qty, $arr_uom, $arr_unitprice, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_unitprice[$r]) ? $arr_unitprice[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }


        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 900) {
          $this->default_so_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(175, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Credit Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    $user = $params['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $username = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


    if ((isset($username[0]['name']) ? $username[0]['name'] : '') != '') {
      $user = $username[0]['name'];
    }

    PDF::MultiCell(175, 0, $user, '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_so_ws_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');


    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, 'Sales Order WS', '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");


    $add = '';
    if (isset($data[0]['address'])) {
      $count = strlen($data[0]['address']);
      $add_lines = $count / 80;
      $count = 0;
      $add_part = 0;
      $constant = 79;

      for ($i = 0; $i < $add_lines; $i++) {

        $add_part += $constant;
        $add = substr($data[0]['address'], $count, $add_part);
        if ($i == 0) {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, $add, 'B', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, 'Terms: ', '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');

          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, '', '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, '', '', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, "Due: ", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($data[0]['due']) ? $data[0]['due'] : ''), 'B', 'L', false, 1, '',  '');
        } else {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(80, 0, '', '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(470, 0, $add, '', 'L', false, 0, '',  '');
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, '', '', 'L', false, 1, '',  '');
        }

        $count += $constant;
      }

      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', 2);
      PDF::MultiCell(470, 0, '', 'T', 'L', false, 0, '',  '');
      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(50, 0, '', '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', 2);
      PDF::MultiCell(100, 0, '', '', 'L', false, 1, '',  '');
    }

    $agentname = '';
    if (isset($data[0]['agentname'])) {
      $agentname = (isset($data[0]['agentname']) ? $data[0]['agentname'] : '');
    }


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Agent: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, $agentname, 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(25, 0, '', '', 'L', false, 0, '',  '');
    PDF::MultiCell(50, 0, "Yourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(25, 0, '', '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Trnx Type:", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 0, (isset($data[0]['trnxtype']) ? $data[0]['trnxtype'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
    PDF::MultiCell(700, 0, '', '');
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(80, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "ISSUED", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "CODE", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(80, 0, "PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_so_ws_PDF($params, $data)
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
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_so_ws_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $netofdisc =  number_format($data[$i]['factor'] * $data[$i]['netamt'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_netofdisc = $this->reporter->fixcolumn([$netofdisc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_barcode, $arr_qty, $arr_uom, $arr_netofdisc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          if ($r == 0) {
            PDF::MultiCell(70, 15, ' ', 'B', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          } else {
            PDF::MultiCell(70, 15, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          }
          PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_netofdisc[$r]) ? $arr_netofdisc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }


        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 900) {
          $this->default_so_ws_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(175, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Credit Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    $user = $params['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $username = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


    if ((isset($username[0]['name']) ? $username[0]['name'] : '') != '') {
      $user = $username[0]['name'];
    }
    PDF::MultiCell(175, 0, $user, '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
