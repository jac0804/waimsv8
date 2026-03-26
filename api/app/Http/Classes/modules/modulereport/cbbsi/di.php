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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class di
{

  private $modulename = "DISCREPANCY NOTICE";
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

  public function createreportfilter($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'prepared', 'approved', 'received'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $signatories = $this->othersClass->getSignatories($config);
    $prepared = '';
    $approved = '';
    $received =  '';
    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'prepared':
          $prepared = $value->fieldvalue;
          break;
        case 'approved':
          $approved = $value->fieldvalue;
          break;
        case 'received':
          $received = $value->fieldvalue;
          break;
      }
    }
    $paramstr = "select
                  'PDFM' as print,
                  '" . $prepared . "' as prepared,
                  '" . $approved . "' as approved,
                  '" . $received . "' as received";

    return $this->coreFunctions->opentable($paramstr);
  }
  // qwe @123qwE123
  public function report_default_query($trno)
  {
    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.barcode,client.addr,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, 
        stock.ext,m.model_name as model,item.sizeid,if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemdesc,
        head.yourref,head.ourref,hinfo.carrier,hinfo.waybill,client.fax,stock.ref
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join stockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
        left join headinfotrans as hinfo on hinfo.trno=head.trno
        where head.doc='di' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.barcode,client.addr,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, 
        stock.ext,m.model_name as model,item.sizeid,if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemdesc,
        head.yourref,head.ourref,hinfo.carrier,hinfo.waybill,client.fax,stock.ref
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join hstockinfotrans as sit on sit.trno = stock.trno and sit.line=stock.line
        left join hheadinfotrans as hinfo on hinfo.trno=head.trno
        where head.doc='di' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_di_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      return $this->default_DI_PDF($params, $data);
    }
  }

  public function default_header($params, $data)
  {
    $companyid = $params['params']['companyid'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code,name,address,tel,email from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    $str .= $this->reporter->col($reporttimestamp, null, null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address . ' ' . $headerdata[0]->email), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '80', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '400', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col('Docno #: ', '70', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '150', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '400', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col('Doc Date : ', '70', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Fax No : ', '80', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col((isset($data[0]['fax']) ? $data[0]['fax'] : ''), '400', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col(' ', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '700', null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Invoice No:', '80', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '400', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Carrier:', '80', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col((isset($data[0]['carrier']) ? $data[0]['carrier'] : ''), '400', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col('Waybill: ', '70', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col((isset($data[0]['waybill']) ? $data[0]['waybill'] : ''), '150', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Notes:', '80', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '400', null, false, $border, '', 'L', $font, '12', '');
    $str .= $this->reporter->col('Ref No:', '70', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '150', null, false, $border, '', 'L', $font, '12', '', '', '');
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
    $str .= $this->reporter->col('CODE', '50', null, false, '1px dotted', 'TB', 'C', $font, '12', '', '30px', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '200', null, false, '1px dotted', 'TB', 'L', $font, '12', '', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '60', null, false, '1px dotted', 'TB', 'C', $font, '12', '', '30px', '8px');
    $str .= $this->reporter->col('PO No.', '125', null, false, '1px dotted', 'TB', 'L', $font, '12', '', '30px', '8px');
    $str .= $this->reporter->col('QTY', '65', null, false, '1px dotted', 'TB', 'C', $font, '12', '', '30px', '8px');
    $str .= $this->reporter->col('Price', '90', null, false, '1px dotted', 'TB', 'R', $font, '12', '', '30px', '8px');
    $str .= $this->reporter->col('Amount', '110', null, false, '1px dotted', 'TB', 'R', $font, '12', '', '30px', '8px');
    $str .= $this->reporter->endrow();
    return $str;
  }


  public function default_di_layout($params, $data)
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

      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['ref'], '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '65', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price', $params['params'])), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');


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
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '435', null, false, '1px dotted ', 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL :', '65', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '110', null, false, '1px dotted ', 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Notes By:', '300', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Truly Yours,', '300', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, 'B', 'L', $font, '12', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'B', 'L', $font, '12', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '12', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '12', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Purchasing Officer', '300', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_DI_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
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

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(430, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Docno #:", '', 'L', false, 0, '',  '');
    PDF::MultiCell(120, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(430, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Doc Date: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(120, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Fax No: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(620, 0, (isset($data[0]['fax']) ? $data[0]['fax'] : ''), '', 'L', false, 1);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(700, 0, $this->modulename, '', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Invoice No: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(520, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Carrier: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(400, 0, (isset($data[0]['carrier']) ? $data[0]['carrier'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Waybill:", '', 'L', false, 0, '',  '');
    PDF::MultiCell(150, 0, (isset($data[0]['waybill']) ? $data[0]['waybill'] : ''), '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, "Notes: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(400, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(70, 0, "Ref No: ", '', 'L', false, 0, '',  '');
    PDF::MultiCell(150, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n");


    PDF::SetFont($font, '', 5);

    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4));
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(125, 0, "CODE", '', 'C', false, 0);
    PDF::MultiCell(175, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(60, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(125, 0, "PO No.", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "PRICE", '', 'R', false, 0);
    PDF::MultiCell(90, 0, "AMOUNT", '', 'R', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_DI_PDF($params, $data)
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
    $fontsize = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_DI_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemdesc'];
      $ref = $data[$i]['ref'];
      $qty = number_format($data[$i]['qty'], 0);
      $uom = $data[$i]['uom'];
      $netamt = number_format($data[$i]['netamt'], 2);
      $ext = number_format($data[$i]['ext'], 2);

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '25', 0);

      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '5', 0);
      //price
      $arr_netamt = $this->reporter->fixcolumn([$netamt], '13', 0);
      $arr_pono = $this->reporter->fixcolumn([$ref], '15', 0);
      //amout
      $arr_ext = $this->reporter->fixcolumn([$ext], '12', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_netamt, $arr_pono, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(125, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(175, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(60, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(125, 15, ' ' . (isset($arr_pono[$r]) ? $arr_pono[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 15, ' ' . (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(90, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() >= 920) {
        $this->default_DI_header_PDF($params, $data);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(535, 0, 'TOTAL: ', 'B', 'R', false, 0);
    PDF::MultiCell(165, 0, number_format($totalext, $decimalcurr), 'B', 'R');

    PDF::MultiCell(0, 0, "\n");


    PDF::SetLineStyle(array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, 'Notes By:', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Truly Yours,', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 1);



    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(250, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(250, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(250, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 1);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Purchasing Officer', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
