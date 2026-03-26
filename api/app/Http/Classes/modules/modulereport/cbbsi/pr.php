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
use Symfony\Component\VarDumper\VarDumper;

class pr
{
  private $modulename = "Purchase Requisition";
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      ['label' => 'Excel', 'value' => 'excel', 'color' => 'red']
    ]);

    data_set($col1, 'radioreporttype.options', [
      ['label' => 'PR', 'value' => 'default', 'color' => 'red'],
      ['label' => 'PR With ID', 'value' => 'withid', 'color' => 'red']
    ]);

    return array('col1' => $col1);
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

    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      'default' as reporttype,
      '" . $prepared . "' as prepared,
      '" . $approved . "' as approved,
      '" . $received . "' as received
      "
    );
  }

  public function report_default_query($trno)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
        head.terms,head.rem, item.itemid,item.barcode,
        item.itemname, round(stock.rrqty) as qty, stock.uom, stock.rrcost as baseprice, stock.cost unitprice, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from prhead as head left join prstock as stock on stock.trno=head.trno 
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='PR' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname, 
        head.address, head.terms,head.rem, item.itemid,item.barcode,
        item.itemname, round(stock.rrqty) as qty, stock.uom, stock.rrcost as baseprice, stock.cost unitprice, stock.disc, stock.ext,m.model_name as model,item.sizeid
        from hprhead as head left join hprstock as stock on stock.trno=head.trno 
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='PR' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    switch ($params['params']['dataparams']['print']) {
      case 'PDFM':
        switch ($params['params']['dataparams']['reporttype']) {
          case 'withid':
            return $this->withid_PR_PDF($params, $data);
          default:
            return $this->default_PR_PDF($params, $data);
            break;
        }
        break;
      default:
        return $this->default_pr_layout($params, $data);
        break;
    }
  }

  private function reportheader($params, $data)
  {
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = '800';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PURCHASE REQUISITION', '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '140', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br><br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // $str .= $this->reporter->printline();
    // ($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    switch ($params['params']['dataparams']['reporttype']) {
      case 'withid':
        $str .= $this->reporter->col('ITEMID', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('ITEMCODE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DESCRIPTION', '300', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        break;
      default:
        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '60', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '60', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DESCRIPTION', '240', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('BASE PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DISC', '60', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT PRICE', '90', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL', '90', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
        break;
    }

    return $str;
  }

  public function default_pr_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $str = '';
    $count = 28;
    $page = 28;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = '800';

    $str .= $this->reporter->beginreport();

    $str .= $this->reportheader($params, $data);

    $totalext = 0;

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $itemid = (string) $data[$i]['itemid'];
      $itemid = str_replace('I', '1', $this->othersClass->Padj('I' . $itemid, 12));


      switch ($params['params']['dataparams']['reporttype']) {
        case 'withid':
          $str .= $this->reporter->col($itemid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col($data[$i]['uom'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col($data[$i]['itemname'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

          $totalext = $totalext + $data[$i]['ext'];
          break;
        default:
          $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col($data[$i]['uom'], '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col($data[$i]['itemname'], '240', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['baseprice'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['disc'], '60', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['unitprice'], $decimal), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');

          $totalext = $totalext + $data[$i]['ext'];
          break;
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->reportheader($params, $data);
        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    switch ($params['params']['dataparams']['reporttype']) {
      case 'withid':
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '240', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $decimal), '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br><br>";

    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', ''); //$data[0]['rem']
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable($layoutsize);
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

  public function withid_PR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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
    PDF::SetMargins(20, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename . ' With ID', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(500, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, "Supplier : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(505, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(65, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(115, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(505, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(65, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(115, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(90, 25, "ITEMID", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(105, 25, "ITEMCODE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(105, 25, "QTY", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(105, 25, "UNIT", 'TB', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(275, 25, "DESCRIPTION", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(80, 25, "PRICE", 'TB', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    // $w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false
    PDF::SetFont($font, '', 5);
  }

  public function withid_PR_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    PDF::SetMargins(20, 20);

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->withid_PR_header_PDF($params, $data);

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;
      $itemid = (string) $data[$i]['itemid'];
      $itemid = str_replace('I', '1', $this->othersClass->Padj('I' . $itemid, 12));
      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = $data[$i]['qty'];


      $uom = $data[$i]['uom'];
      $ext = number_format($data[$i]['ext'], $decimalcurr);

      // $bprice = number_format($data[$i]['baseprice'], $decimalprice);
      // $uprice = number_format($data[$i]['unitprice'], $decimalprice);
      // $disc = $data[$i]['disc'];

      // $arr_itemid = $this->reporter->fixcolumn([$itemid], '15', 0);
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '13', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '30', 0);

      // $arr_bprice = $this->reporter->fixcolumn([$bprice], '13', 0);
      // $arr_uprice = $this->reporter->fixcolumn([$uprice], '13', 0);
      // $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_ext]);
      // $w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false
      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);

        if ($r == 0) {
          PDF::MultiCell(90, 0, ' ' . (isset($itemid) ? $itemid : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        } else {

          PDF::MultiCell(90, 0, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        }
        PDF::MultiCell(105, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(105, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(105, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(275, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(80, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() > 900) {
        $this->withid_PR_header_PDF($params, $data);
      }
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', 'B');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(660, 18, 'GRAND TOTAL: ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 18, number_format($totalext, $decimalcurr), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(710, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_PR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(500, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(130, 0, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, "Supplier : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(505, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(65, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(115, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(505, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(65, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(115, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(90, 25, "CODE", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(205, 25, "ITEMNAME", 'TB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "UNIT", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "QTY", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "PRICE", 'TB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "DISC", 'TB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 25, "PRICE", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(90, 25, "AMOUNT", 'TB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);

    // PDF::SetFont($font, '', 5);
  }

  public function default_PR_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    // PDF::SetMargins(20, 20);

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    $this->default_PR_header_PDF($params, $data);

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {

      $maxrow = 1;

      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $qty = $data[$i]['qty'];


      $uom = $data[$i]['uom'];
      $ext = number_format($data[$i]['ext'], $decimalcurr);
      $bprice = number_format($data[$i]['baseprice'], $decimalprice);
      $uprice = number_format($data[$i]['unitprice'], $decimalprice);
      $disc = $data[$i]['disc'];

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '13', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '34', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
      $arr_bprice = $this->reporter->fixcolumn([$bprice], '13', 0);
      $arr_uprice = $this->reporter->fixcolumn([$uprice], '13', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_ext, $arr_bprice, $arr_uprice, $arr_disc]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(90, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(205, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 0, ' ' . (isset($arr_bprice[$r]) && $arr_bprice[$r] != 0 ? $arr_bprice[$r] : '-'), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 0, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(75, 0, ' ' . (isset($arr_uprice[$r]) ? $arr_uprice[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(90, 0, ' ' . (isset($arr_ext[$r]) && $arr_ext[$r] != 0 ? $arr_ext[$r] : '-'), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() > 900) {
        $this->default_PR_header_PDF($params, $data);
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(660, 18, 'TOTAL: ', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(100, 18, number_format($totalext, $decimalcurr), 'B', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(710, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(200, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], 'B', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
