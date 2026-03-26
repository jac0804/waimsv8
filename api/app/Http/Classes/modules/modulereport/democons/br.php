<?php

namespace App\Http\Classes\modules\modulereport\maxipro;

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

class br
{

  private $modulename = "Budget Request";
  public $tablenum = 'transnum';
  public $head = 'brhead';
  public $hhead = 'hbrhead';
  public $stock = 'brstock';
  public $hstock = 'hbrstock';
  public $dqty = 'qty';
  public $damt = 'rrcost';

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

    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'received.label', 'Verified by');

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if ((isset($name[0]['name']) ? $name[0]['name'] : '') != '') {
      $user = $name[0]['name'];
    }
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '$user' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function maxipro_query($trno)
  {
    $qry = "
      select head.docno, stock.particulars, date(head.dateid) as dateid,  
      stock." . $this->dqty . " as qty, stock.uom, stock.ext, head.address,
      stock.amount, stock.rrcost, stock.rem, prj.name as projectname, prj.code as projectcode,
      head.start, head.end
      from " . $this->hhead . " as head
      left join " . $this->hstock . " as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      left join " . $this->tablenum . " as num on num.trno = head.trno
      where num.doc = 'BR' and head.trno = '$trno'
      union all
      select head.docno, stock.particulars, date(head.dateid) as dateid,  
      stock." . $this->dqty . " as qty, stock.uom, stock.ext, head.address,
      stock.amount, stock.rrcost, stock.rem, prj.name as projectname, prj.code as projectcode,
      head.start, head.end
      from " . $this->head . " as head
      left join " . $this->stock . " as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      left join " . $this->tablenum . " as num on num.trno = head.trno
      where num.doc = 'BR' and head.trno = '$trno'
    ";
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  }

  public function report_default_query($trno)
  {
    return $this->maxipro_query($trno);
  }

  public function reportplotting($config, $data)
  {
    return $this->default_BR_PDF($config, $data);
  }

  public function generateReportHeader($center, $username)
  {
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    return $str;
  } //end function generate report header

  public function maxipro_layout($params, $data)
  {
    $mdc = URL::to('public/images/reports/mdc.jpg');
    $tuv = URL::to('public/images/reports/tuv.jpg');

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
    $str .= "<div style='position: relative;'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->generateReportHeader($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<div style='position:absolute; top: 60px;'>";
    $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->col('<img src ="' . $tuv . '" alt="TUV" width="140px" height ="70px" style="margin-left: 510px;">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
    $str .= "</div>";

    $str .= "</div>";

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("BUDGET REQUEST", '800', null, false, $border, '', 'C', $font, '14', 'B', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Period Covered: " . date('M d Y', strtotime($data[0]['start'])) . ' - ' . date('M d Y', strtotime($data[0]['end'])), '800', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Project: " . "<b>" . $data[0]['projectname'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("BR No.: " . "<b>" . $data[0]['docno'] . "</b>", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Contract No.: " . "<b>" . $data[0]['projectcode'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("Date: " . "<b>" . $data[0]['dateid'] . "</b>", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Location: " . "<b>" . $data[0]['address'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("ITEM NO.", '75', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("Particulars", '150', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("Qty", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("Unit", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("Estimated Amount", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("Amount", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("Approved Budget", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("Remarks", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($i + 1, '75', null, false, $border, 'TBRL', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['particulars'], '150', null, false, $border, 'TBRL', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $decimal), '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['rrcost'], $decimal), '100', null, false, $border, 'TBRL', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, 'TBRL', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['amount'], $decimal), '100', null, false, $border, 'TBRL', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['rem'], '150', null, false, $border, 'TBRL', 'L', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '75', null, false, '1px solid ', 'TBRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TBRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px solid ', 'TBRL', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'TBRL', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'TBRL', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Verified By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_BR_header_PDF($params, $data)
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
    PDF::AddPage('l', [1000, 800]);
    PDF::SetMargins(40, 40);

    PDF::MultiCell(0, 0, "\n");

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n", '', 'C');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper(strtoupper($this->modulename)), '', 'C');
    PDF::MultiCell(0, 0, "\n\n");

    PDF::Image('public/images/reports/mdc.jpg', '70', '20', 120, 65);
    PDF::Image('public/images/reports/tuv.jpg', '810', '20', 120, 65);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    $itemlen = strlen($data[0]['projectname']) / 40;

    if ($data[0]['projectname'] == '') {
      $itemlen = 1;
    }

    $padding = 8 * $itemlen;

 

    $projectname = isset($data[0]['projectname']) ? $data[0]['projectname'] : '';
    $arrprojname = $this->reporter->fixcolumn([$projectname], 100, 0);
    $cproj = count($arrprojname);

    for ($r = 0; $r < $cproj; $r++) {
      if ($r == 0) {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, $padding, "Project: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(545, $padding, (isset($arrprojname[$r]) ? $arrprojname[$r] : ''), '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, $padding, "BR No.: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, $padding, $data[0]['docno'], '', 'L', false);
      } else {
        $labelproj = '';
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, $padding, $labelproj, '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(545, $padding, (isset($arrprojname[$r]) ? $arrprojname[$r] : ''), '', 'L', false);
      }
    }




    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, $padding, "Project Code: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(545, $padding, (isset($data[0]['projectcode']) ? $data[0]['projectcode'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, $padding, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, $padding, $data[0]['dateid'], '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, $padding, "Location: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(545, $padding, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, $padding, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, $padding, '', '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(50, 20, "ITEM NO.", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(200, 20, "Particulars", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 20, "Qty", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 20, "Unit", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, "Estimated Amount", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, "Amount", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);;
    PDF::MultiCell(100, 20, "Approved Budget", 'TLB', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(250, 20, "Remarks", 'TLBR', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
  }

  public function default_BR_PDF($params, $data)
  {
    
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_BR_header_PDF($params, $data);

    $totalext = 0;
    $totalamt = 0;

    if (!empty($data)) {

      for ($i = 0; $i < count($data); $i++) {
       
        $maxrow = 1;
       
        $particulars = $data[$i]['particulars'];
        $qty = number_format($data[$i]['qty'],$decimalqty);
        $uom = $data[$i]['uom'];
        $rrcost = number_format($data[$i]['rrcost'],$decimalcurr);
        $ext = number_format($data[$i]['ext'],$decimalprice);
        $amount = number_format($data[$i]['amount'],$decimalprice);
        $rem = $data[$i]['rem'];
        $amount = $amount < 0 ? '' : $amount;

        $arr_particulars = $this->reporter->fixcolumn([$particulars],'30',0);
        $arr_qty = $this->reporter->fixcolumn([$qty],'13',0);
        $arr_uom = $this->reporter->fixcolumn([$uom],'13',0);
        $arr_rrcost = $this->reporter->fixcolumn([$rrcost],'13',0);
        $arr_ext = $this->reporter->fixcolumn([$ext],'13',0);
        $arr_amount = $this->reporter->fixcolumn([$amount],'13',0);
        $arr_rem = $this->reporter->fixcolumn([$rem],'16',0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_particulars, $arr_qty, $arr_uom, $arr_rrcost, $arr_ext, $arr_amount, $arr_rem]);

        $this->addrow('LRT');
        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(50, 0, $i + 1, 'L', 'C', false, 0, '', '', true, 1);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(200, 0, (isset($arr_particulars[$r]) ? $arr_particulars[$r] : ''), 'L', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'L', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(50, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'L', 'C', false, 0, '', '', false, 1);
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(100, 0, (isset($arr_rrcost[$r]) ? $arr_rrcost[$r] : ''), 'L', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'L', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_amount[$r]) ? $arr_amount[$r] : ''), 'L', 'R', false, 0, '', '', false, 1);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(250, 0, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), 'LR', 'L', false);
        }

       
        $totalext += $data[$i]['ext'];
        $totalamt += $data[$i]['amount'];

        if (intVal($i) + 1 == $page) {
          $this->default_BR_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, '', 'LB', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(600, 20, '***** NOTHING FOLLOWS *****', 'LB', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(250, 20, '', 'LRB', 'L', false);

    if ($totalamt == 0) {
      $totalamt = '';
    } else {
      $totalamt = number_format($totalamt, $decimalcurr);
    }
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(450, 20, "TOTAL ", 'TLB', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 20, number_format($totalext, $decimalcurr) . ' ', 'TLB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 20, $totalamt . ' ', 'TLB', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(250, 20, '', 'TLBR', 'L', false, 1, '', '', false, 1);

   
    PDF::MultiCell(0, 0, "\n");


    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'Verified By: ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'Approved By: ', '', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['prepared'], '', 'C', false, 0);
    PDF::MultiCell(90, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['received'], '', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'C', false, 0);
    PDF::MultiCell(70, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, $params['params']['dataparams']['approved'], '', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'C');


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(80, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, 'Project In Charge', 'T', 'C', false, 0);
    PDF::MultiCell(90, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, 'VP Operation', 'T', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'C', false, 0);
    PDF::MultiCell(70, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'C');

    
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  private function addrow($border)
  {
    PDF::SetFont('', '', 5);
    PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(200, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 0, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 0, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 0, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(250, 0, '', $border, 'L', false);
  }
}
