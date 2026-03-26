<?php

namespace App\Http\Classes\modules\modulereport\democons;

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

class jc
{

  private $modulename = "Job Completion";
  public $tablenum = 'cntnum';
  public $head = 'jchead';
  public $hhead = 'hjchead';
  public $stock = 'jcstock';
  public $hstock = 'hjcstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';

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
    $fields = ['radioprint', 'prepared', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, "approved.label", "Checked by");

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

  public function report_default_query($trno)
  {
    $query = "
      select head.docno, cl.client, cl.clientname, prj.name as projectname, coa.acno, coa.acnoname, detail.db, detail.cr, sprj.subproject as subprojectname, date(head.dateid) as dateid,
      head.workdesc, head.rem
      from " . $this->hhead . " as head
      left join " . $this->hdetail . " as detail on detail.trno = head.trno
      left join coa as coa on coa.acnoid = detail.acnoid
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      left join client as cl on cl.client = head.client
      left join " . $this->tablenum . " as num on num.trno = head.trno
      where num.doc = 'JC' and head.trno = '$trno'
      union all
      select head.docno, cl.client, cl.clientname, prj.name as projectname, coa.acno, coa.acnoname, detail.db, detail.cr, sprj.subproject as subprojectname, date(head.dateid) as dateid,
      head.workdesc, head.rem
      from " . $this->head . " as head
      left join " . $this->detail . " as detail on detail.trno = head.trno
      left join coa as coa on coa.acnoid = detail.acnoid
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      left join client as cl on cl.client = head.client
      left join " . $this->tablenum . " as num on num.trno = head.trno
      where num.doc = 'JC' and head.trno = '$trno';
    ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    return $this->JC_layout_PDF($params, $data);
  }


  private function generateReportHeader($center, $username)
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

    $trno = $params['params']['dataid'];
    $qry = "
      select head.docno, item.itemname, stock.qty, stock.uom, stock.ext, stock.rrcost,
      stock.ref as jonum
      from " . $this->head . " as head
      left join " . $this->stock . " as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as num on num.trno = head.trno
      where head.trno = '$trno'
      union all 
      select head.docno, item.itemname, stock.qty, stock.uom, stock.ext, stock.rrcost,
      stock.ref as jonum
      from " . $this->hhead . " as head
      left join " . $this->hstock . " as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as num on num.trno = head.trno
      where head.trno = '$trno'";
    $data1 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

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
    $str .= $this->reporter->col("JOB COMPLETION", '800', null, false, $border, '', 'C', $font, '14', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Subcontract: " . "<b>" . $data[0]['clientname'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("JOC No.: " . "<b>" . $data[0]['docno'] . "</b>", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Project: " . "<b>" . $data[0]['projectname'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("Date: " . "<b>" . $data[0]['dateid'] . "</b>", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Work Description: " . "<b>" . $data[0]['workdesc'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("Job Order: " . "<b>" . $data1[0]['jonum'] . "</b>", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Important Notes: " . "<b>" . $data[0]['rem'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("PARTICULARS", '400', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("", '10', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("AMOUNT", '100', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();

    $totalext = 0;
    for ($i = 0; $i < count($data1); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data1[$i]['itemname'], '400', null, false, $border, 'L', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data1[$i]['uom'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($data1[$i]['qty'], 2), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col("₱", '10', null, false, $border, 'L', 'R', $font, $fontsize, 'B', '', '5px');
      $str .= $this->reporter->col(number_format($data1[$i]['ext'], $decimal), '100', null, false, $border, 'R', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->endrow();
      $totalext += $data1[$i]['ext'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '400', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col("Total: ", '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col("₱", '10', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, $border, 'TBR', 'R', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("ACCNT #", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("ACCOUNTING ENTRY", '200', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("SUBPROJECT", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("DEBIT", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("CREDIT", '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($data); $i++) {
      $db = $data[$i]['db'];
      $cr = $data[$i]['cr'];

      if ($db > 0) {
        $db = number_format($db, $decimal);
      } else {
        $db = "";
      }

      if ($cr > 0) {
        $cr = number_format($cr, $decimal);
      } else {
        $cr = "";
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['acno'], '100', null, false, $border, 'L', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '200', null, false, $border, 'L', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data[$i]['subprojectname'], '100', null, false, $border, 'L', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($db, '100', null, false, $border, 'L', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($cr, '100', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '5px');

    }
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '800', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Checked By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_JC_header_PDF($params, $data, $data1)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 10;
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

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n", '', 'C');

    PDF::Image($this->companysetup->getlogopath($params['params']).'sbc.png', '40', '20', 120, 65);


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(400, 0, strtoupper(strtoupper($this->modulename)), '', 'R');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, "", '', 'C');

    PDF::MultiCell(0, 0, "\n\n");


    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4));
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    $maxh = PDF::GetStringHeight(475, $data[0]['projectname']);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "Subcontract: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(350, $maxh, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "JOC No.: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, $maxh, $data[0]['docno'], 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(350, $maxh, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, $maxh, date('m/d/Y', strtotime($data[0]['dateid'])), 'B', 'L', false);

    PDF::MultiCell(0, $maxh, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "Project: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(350, $maxh, (isset($data[0]['projectname']) ? $data[0]['projectname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "Job Order No: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, $maxh, $data1[0]['jonum'], 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, $maxh, "\n\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "Work Description: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(350, $maxh, (isset($data[0]['workdesc']) ? $data[0]['workdesc'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, $maxh, '', '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, $maxh, "\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, $maxh, "Important Notes: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(400, $maxh, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, $maxh, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, $maxh, '', '', 'L', false, 0, '',  '');


    PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    PDF::MultiCell(0, 0, "\n\n\n");
  }

  public function JC_layout_PDF($params, $data)
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


    $arritemname = array();
    $countarr = 0;

    $trno = $params['params']['dataid'];
    $qry = "
      select head.docno, item.itemname, stock.qty, stock.uom, stock.ext, stock.rrcost,
      stock.ref as jonum
      from " . $this->head . " as head
      left join " . $this->stock . " as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as num on num.trno = head.trno
      where head.trno = '$trno'
      union all 
      select head.docno, item.itemname, stock.qty, stock.uom, stock.ext, stock.rrcost,
      stock.ref as jonum
      from " . $this->hhead . " as head
      left join " . $this->hstock . " as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join cntnum as num on num.trno = head.trno
      where head.trno = '$trno'";
    $data1 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $this->default_JC_header_PDF($params, $data, $data1);

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(50, 20, "", '', 'C', false, 0);
    PDF::MultiCell(500, 20, "PARTICULARS", 'TLB', 'C', false, 0);
    PDF::MultiCell(130, 20, "Amount   ", 'TLBR', 'R', false, 0);
    PDF::MultiCell(20, 20, "", '', 'R', false);


    if (!empty($data1)) {
      $maxh_item = 0;
      for ($i = 0; $i < count($data1); $i++) {
        $maxrow = 1;
        $arr_itemname = $this->reporter->fixcolumn([$data1[$i]['itemname']], '40', 0);
        $arr_uom = $this->reporter->fixcolumn([$data1[$i]['uom']], '16', 0);
        $arr_qty = $this->reporter->fixcolumn([number_format($data1[$i]['qty'], $decimalqty)], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([number_format($data1[$i]['ext'], $decimalprice)], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(50, 0, '', '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(10, 0, '', 'L', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(290, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::SetFont('dejavusans', '', 11, '', true);
          PDF::MultiCell(30, 0, "<span>&#8369;</span>", 'L', 'C', false, 0, '', '', false, 1, true);
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(100, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : '') . '   ', 'R', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(20, 0, '', '', 'R', false, 1, '', '', false, 1);
        }
        $totalext += $data1[$i]['ext'];
      }
    }

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(50, 20, "", '', 'C', false, 0);
    PDF::MultiCell(400, 20, "", 'TLB', 'R', false, 0);
    PDF::MultiCell(100, 20, "Total", 'TB', 'C', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::SetFont('dejavusans', '', 11, '', true);
    PDF::MultiCell(30, 20, "<span>&#8369;</span>", 'TLB', 'C', false, 0, '', '', false, 1, true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 20, number_format($totalext, $decimalprice) . '   ', 'TBR', 'R', false, 0);
    PDF::MultiCell(20, 20, "", '', 'R', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(50, 20, "", '', 'C', false, 0);
    PDF::MultiCell(50, 20, "ACCT #", 'TL', 'C', false, 0);
    PDF::MultiCell(200, 20, "ACCOUNTING ENTRY", 'TL', 'C', false, 0);
    PDF::MultiCell(160, 20, "SUBPROJECT", 'TL', 'C', false, 0);
    PDF::MultiCell(110, 20, "DEBIT", 'TL', 'C', false, 0);
    PDF::MultiCell(110, 20, "CREDIT", 'TLR', 'C', false, 0);
    PDF::MultiCell(20, 20, "", '', 'C', false);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(50, 0, '', '', '', false, 0);
    PDF::MultiCell(50, 0, '', 'TL', '', false, 0);
    PDF::MultiCell(200, 0, '', 'TL', '', false, 0);
    PDF::MultiCell(160, 0, '', 'TL', '', false, 0);
    PDF::MultiCell(110, 0, '', 'TL', '', false, 0);
    PDF::MultiCell(110, 0, '', 'TLR', '', false, 0);
    PDF::MultiCell(20, 0, '', '', '', false);
    $dbcur = '';
    $crcur = '';

    if (!empty($data)) {

      $subprj_height = 0;
      for ($i = 0; $i < count($data); $i++) {
        $db = $data[$i]['db'] != 0 ? number_format($data[$i]['db'], $decimalprice) : "";
        $cr = $data[$i]['cr'] != 0 ? number_format($data[$i]['cr'], $decimalprice) : "";

        $subprj_height = PDF::GetStringHeight(200, $data[$i]['subprojectname']);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(50, 20, $data[$i]['acno'], 'L', 'C', false, 0, '', '', false, 1);
        PDF::MultiCell(10, 20, '', 'L', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(190, 20, $data[$i]['acnoname'], '', 'L', false, 0, '', '', false, 1);
        PDF::MultiCell(160, 20, $data[$i]['subprojectname'], 'L', 'C', false, 0, '', '', false, 1);
   
        if ($data[$i]['db'] != 0) {
          $dbcur = "<span>&#8369;</span>";
        } else {
          $dbcur = '';
        }
        if ($data[$i]['cr'] != 0) {
          $crcur = "<span>&#8369;</span>";
        } else {
          $crcur = '';
        }
        PDF::SetFont('dejavusans', '', 11, '', true);
      
        PDF::MultiCell(20, 20, $dbcur, 'L', 'C', false, 0, '', '', false, 1, true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 20, $db, '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(10, 20, '', '', 'R', false, 0, '', '', false, 1);

        PDF::SetFont('dejavusans', '', 11, '', true);
        PDF::MultiCell(20, 20, $crcur, 'L', 'R', false, 0, '', '', false, 1, true);
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(80, 20, $cr, '', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(10, 20, '', 'R', 'R', false, 0, '', '', false, 1);
        PDF::MultiCell(20, 20, '', '', 'R', false, 1, '', '', false, 1);
      }
    }

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(50, 0, '', '', '', false, 0);
    PDF::MultiCell(50, 0, '', 'BL', '', false, 0);
    PDF::MultiCell(200, 0, '', 'BL', '', false, 0);
    PDF::MultiCell(160, 0, '', 'BL', '', false, 0);
    PDF::MultiCell(110, 0, '', 'BL', '', false, 0);
    PDF::MultiCell(110, 0, '', 'BLR', '', false, 0);
    PDF::MultiCell(20, 0, '', '', '', false);

    PDF::MultiCell(50, 50, '', '', '', false, 0);
    PDF::MultiCell(630, 50, '', '', '', false, 0);
    PDF::MultiCell(20, 50, '', '', '', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, '', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Checked By: ', '', 'L', false);
  

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(233, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(173, 0, '', '', 'L', false, 0);
    PDF::MultiCell(233, 0, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
   
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
