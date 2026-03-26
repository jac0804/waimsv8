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
use App\Http\Classes\reportheader;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class jo
{

  private $modulename = "Job Order";
  public $tablenum = 'transnum';
  public $head = 'johead';
  public $hhead = 'hjohead';
  public $stock = 'jostock';
  public $hstock = 'hjostock';
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $reporter;
  private $reportheader;
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
    $fields = ['radioprint', 'db', 'radiostatus', 'prepared', 'approved', 'received', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    data_set($col1, 'radiostatus.options', [
      ['label' => 'Peso', 'value' => 'p', 'color' => 'teal'],
      ['label' => 'Dollar', 'value' => 'd', 'color' => 'teal'],
      ['label' => 'RMB', 'value' => 'r', 'color' => 'teal'],
      ['label' => 'CNY', 'value' => 'c', 'color' => 'teal'],
      ['label' => 'EURO', 'value' => 'e', 'color' => 'teal']
    ]);
    data_set($col1, 'radiostatus.label', 'Select Sign: ');
    data_set($col1, 'db.label', 'Forex (Conversion Rate)');
    data_set($col1, 'db.readonly', false);

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
      '' as received,
      '1' as db,
      'p' as status"
    );
  }

  public function report_default_query($trno)
  {
    $query = "select head.trno,(select group_concat(distinct right(ref,13)*1) from jostock where trno=head.trno) as yourref,
                    (select count(distinct ref) from jostock where trno = head.trno) as ctrref,
                    head.docno, head.clientname, cl.addr as address, cl.email, cl.tel,
                    cl.contact as contactperson,prj.name as projectname, prj.code as projectcode,
                    sprj.subproject as subprojectname,head.workdesc, head.terms,
                    date(head.dateid) as dateid,head.workloc, item.itemname, stock." . $this->hqty . " as qty,
                    stock.uom,stock." . $this->damt . " as unitprice, stock.ext,
                    head.rem, head.revision, date(head.start) as startdate,
                    date(head.due) as enddate, stock.rem as notes,stock.ref,stock.line
              from johead as head
              left join jostock as stock on stock.trno = head.trno
              left join projectmasterfile as prj on prj.line = head.projectid
              left join subproject as sprj on sprj.line = head.subproject
              left join client as cl on cl.client = head.client
              left join item as item on item.itemid = stock.itemid
              where head.trno = $trno
              group by head.trno,head.docno, head.clientname, cl.addr, cl.email, cl.tel,cl.contact,prj.name, prj.code,
                    sprj.subproject,head.workdesc, head.terms,head.dateid,head.workloc, item.itemname, stock.qty,
                    stock.uom,stock.rrcost, stock.ext,head.rem, head.revision, head.start,
                    head.due, stock.rem,stock.ref,stock.line,head.trno
              union all
              select head.trno,(select group_concat(distinct right(ref,13)*1) from hjostock where trno=head.trno) as yourref,
                    (select count(distinct ref) from jostock where trno = head.trno) as ctrref,
                    head.docno, head.clientname, cl.addr as address, cl.email, cl.tel,
                    cl.contact as contactperson,prj.name as projectname, prj.code as projectcode,
                    sprj.subproject as subprojectname,head.workdesc, head.terms,
                    date(head.dateid) as dateid,head.workloc, item.itemname, stock." . $this->hqty . " as qty,
                    stock.uom, stock." . $this->damt . " as unitprice, stock.ext,
                    head.rem, head.revision, date(head.start) as startdate,
                    date(head.due) as enddate, stock.rem as notes,stock.ref,stock.line
              from hjohead as head
              left join hjostock as stock on stock.trno = head.trno
              left join projectmasterfile as prj on prj.line = head.projectid
              left join subproject as sprj on sprj.line = head.subproject
              left join client as cl on cl.client = head.client
              left join item as item on item.itemid = stock.itemid
              where head.trno = $trno
              group by head.trno,head.docno, head.clientname, cl.addr, cl.email, cl.tel,cl.contact,prj.name, prj.code,
                    sprj.subproject,head.workdesc, head.terms,head.dateid,head.workloc, item.itemname, stock.qty,
                    stock.uom, stock.rrcost, stock.ext,head.rem, head.revision, head.start,
                    head.due, stock.rem,stock.ref,stock.line,head.trno
              order by line, ref";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    ini_set('memory_limit', '-1');
    return $this->maxipro_layout_PDF($params, $data);
  }

  public function default_JO_headerpdf($params, $data, $font)
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
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1200]);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(550, 25, '', 'TLR', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(210, 25, '', 'TLR', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(550, 40, 'PURCHASE ORDER', 'LRB', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::SetTextColor(255, 0, 0);
    PDF::MultiCell(210, 40, $data[0]['docno'], 'LRB', 'C', false);

    PDF::SetTextColor(0, 0, 0);

    if ($data[0]['revision'] != '') {
      PDF::SetFont($fontbold, '', 10);
      PDF::MultiCell(210, 0, 'Revision: ' . $data[0]['revision'], '', 'C', false, 1, 565, 80);

      PDF::SetFont($fontbold, '', 6);
      PDF::MultiCell(0, 0, "\n");
    }

    $this->reportheader->getHeader($params);

    $left = '10';
    $top = '';
    $right = '';
    $bottom = '';

    PDF::setCellPadding($left, $top, $right, $bottom);
    PDF::SetFont($fontbold, '', $fontsize);

    PDF::MultiCell(103, 20, 'Subcontractor: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(447, 20, $data[0]['clientname'], 'R', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, 'Date: ', 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(160, 20, date('M d, Y', strtotime($data[0]['dateid'])), 'RB', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(103, 20, 'Address: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(447, 20, $data[0]['address'], 'R', 'L', false, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(210, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'R', 'C', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(550, 40, '', 'LR', 'L', false, 0);
    PDF::MultiCell(210, 40, 'The order number must be appear on the papers, invoices, packing list and correspondence.', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(103, 20, 'Tel No.: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(447, 20, $data[0]['tel'], 'R', 'L', false, 0);
    PDF::MultiCell(210, 20, '', 'LR', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(103, 20, 'Email: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(447, 20, $data[0]['email'], 'R', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(210, 20, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(103, 20, 'Contact Person: ', 'LB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(447, 20, $data[0]['contactperson'], 'BR', 'L', false, 0);

    ////
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 20, '', 'LB', 'L', false, 0);
    PDF::MultiCell(100, 20, '', 'RB', 'L', false);


    ////

    // $yourref = $data[0]['yourref'];
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(110, 20, 'JO Requisition No.: ', 'LTB', 'L', false, 0);
    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(100, 20, $yourref, 'RTB', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(103, 0, 'Project Code: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(320, 0, $data[0]['projectcode'], '', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, 'Terms of Payment: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(227, 0, $data[0]['terms'], 'R', 'L', false);

    $left = '3';
    PDF::setCellPadding($left, $top, $right, $bottom);

    $arr_projname = $this->reporter->fixcolumn([$data[0]['projectname']], 110);
    $maxrowproj = $this->othersClass->getmaxcolumn([$arr_projname]);
    ///////////////////////////////////////////////////////////////////////////

    for ($k = 0; $k < $maxrowproj; $k++) {
      if ($k == 0) {
        $label = 'Project Name: ';
      } else {
        $label = '';
      }
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(7, 20, '', 'L', 'L', false, 0);
      PDF::MultiCell(105, 20, $label, '', 'L', false, 0);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(648, 20, isset($arr_projname[$k]) ? $arr_projname[$k] : '', 'R', 'L', false);
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(7, 20, '', 'L', 'L', false, 0);
    PDF::MultiCell(103, 20, 'Subproject Name: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(650, 20, $data[0]['subprojectname'], 'R', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(7, 20, '', 'L', 'L', false, 0);
    PDF::MultiCell(103, 20, 'Work Description: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(650, 20, $data[0]['workdesc'], 'R', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(7, 20, '', 'L', 'L', false, 0);
    PDF::MultiCell(70, 20, 'Start Date: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 20, date('M d, Y', strtotime($data[0]['startdate'])), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, 'End Date: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 20, date('M d, Y', strtotime($data[0]['enddate'])), '', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, 'Work Location: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(293, 20, $data[0]['workloc'], 'R', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(7, 20, '', 'L', 'L', false, 0);
    PDF::MultiCell(103, 20, 'JO Requisition No.: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(650, 20, $data[0]['yourref'], 'R', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'ITEM NO.', 'LRTB', 'C', false, 0);
    PDF::MultiCell(230, 0, 'DESCRIPTION', 'LRTB', 'C', false, 0);
    PDF::MultiCell(130, 0, 'NOTES', 'LRTB', 'C', false, 0);
    PDF::MultiCell(85, 0, 'QTY', 'LRTB', 'C', false, 0);
    PDF::MultiCell(80, 0, 'UOM', 'LRTB', 'C', false, 0);
    PDF::MultiCell(85, 0, 'UNIT PRICE', 'LRTB', 'C', false, 0);
    PDF::MultiCell(100, 0, 'AMOUNT', 'LRTB', 'C', false);

    PDF::SetFont($font, '', 0);
    PDF::MultiCell(760, 0, '', 'LR', 'L', false);

    $left = '10';
    PDF::setCellPadding($left, $top, $right, $bottom);
  }

  public function maxipro_layout_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = 890; //890
    $page = 880; //880
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetMargins(20, 20);

    $this->default_JO_headerpdf($params, $data, $font);

    $counter = 0;
    $cc = 0;
    $maxrow1 = 40;
    $ref = '';
    $subtotal = 0;

    $sign = $params['params']['dataparams']['status'];
    switch ($sign) {
      case 'p':
        $peso = '₱ ';
        $tp = 'Php ';
        break;
      case 'd':
        $peso = '$';
        $tp = '$ ';
        break;
      case 'c':
        $peso = 'CNY';
        $tp = 'CNY ';
        break;
      case 'e':
        $peso = '€';
        $tp = '€';
        break;
      default:
        $peso = 'RMB';
        $tp = 'RMB ';
        break;
    }
    $rate = $params['params']['dataparams']['db'];
    $extqry = $this->coreFunctions->opentable("select sum(round(ext,2)) as ext from (
            select ext
            from jostock where trno=? and void=0
            union all
            select ext
            from hjostock where trno=? and void=0) as k", [$data[0]['trno'], $data[0]['trno']]);
    $total = json_decode(json_encode($extqry), true);


    $countarrnotes = 0;
    for ($i = 0; $i < count($data); $i++) {


      if ($rate == 1) {
        $unitprice = $data[$i]['unitprice'];
        $totalext = $total[0]['ext'];
        $ext = $data[$i]['ext'];
      } else {
        $unitprice = $data[$i]['unitprice'] / $rate;
        $totalext = $total[0]['ext'] / $rate;
        $ext = $data[$i]['ext'] / $rate;
      }
      $counter++;

      $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], 35, 0);
      $notesname = $this->reporter->fixcolumn([$data[$i]['notes']], 20, 0);

      $maxrow = 1;

      $countarr = count($itemname);
      $countarrnotes = count($notesname);

      if ($countarr > $countarrnotes) {
        $maxrow = $countarr;
      } else {
        $maxrow = $countarrnotes;
      }

      if ($data[$i]['itemname'] != '') {
        for ($j = 0; $j < $maxrow; $j++) {
          $cc++;
          if ($j == 0) {
            $inum = $i + 1;
            $qty = number_format($data[$i]['qty'], 2);
            $uom = $data[$i]['uom'];
            $unitprice = $peso . ' ' . number_format($unitprice, $decimalprice);
            $tamt = $peso . ' ' . number_format($ext, $decimalprice);
            $php = 'Php ';
          } else {
            $inum = '';
            $qty = '';
            $uom = '';
            $unitprice = '';
            $tamt = '';
            $php = '';
          }


          if ($data[0]['ctrref'] > 1) {
            if ($ref != "" && $ref != $data[$i]['ref']) {
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::setCellPadding('0', '', '', '');
              PDF::MultiCell(660, 20, 'Sub Total: ', 'TLB', 'R', false, 0);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(30, 20, $tp, 'TB', 'L', false, 0);
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(70, 20,  number_format($subtotal, 2), 'RTB', 'R', false);
              $subtotal = 0;
            }
          }


          if ($ref == "" || $ref != $data[$i]['ref']) {
            $ref = $data[$i]['ref'];
          }

          PDF::SetFont($font, '', $fontsize);
          PDF::setCellPadding('2', '', '', '');
          PDF::MultiCell(50, 0, $inum, 'L', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(230, 0, isset($itemname[$j]) ? $itemname[$j] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(130, 0, isset($notesname[$j]) ? $notesname[$j] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(85, 0, $qty, '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(80, 0, $uom, '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::SetFont('dejavusans', 'R', $fontsize);
          PDF::MultiCell(85, 0, $unitprice, '', 'R', 0, 0, '', '', true, 0, true, false);
          // PDF::MultiCell(30, 0, $php, '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::SetFont('dejavusans', 'R', $fontsize);
          PDF::MultiCell(100, 0, $tamt, 'R', 'R', 0, 1, '', '', true, 0, false, false);

          if (PDF::getY() >= 970) { //780
            $newpageadd = 1;
            $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
            $this->maxipro_layout_PDF_FOOTER($params, $data);
            $this->default_JO_headerpdf($params, $data, $font);
          }
        }
      }

      if ($ref == $data[$i]['ref']) {
        $subtotal += $data[$i]['ext'] / $rate;
      }

      // $totalext += $data[$i]['ext'];

      if (PDF::getY() > 1000) {
        $newpageadd = 1;
        $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
        $this->maxipro_layout_PDF_FOOTER($params, $data);
        $this->default_JO_headerpdf($params, $data, $font);
      }
    }
    ///////
    if ($data[0]['ctrref'] > 1) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::setCellPadding('0', '', '', '');
      PDF::MultiCell(660, 20, 'Sub Total: ', 'TLB', 'R', false, 0);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(30, 20, $tp, 'TB', 'L', false, 0);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(70, 20,  number_format($subtotal, 2), 'RTB', 'R', false);
    }



    //////
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(760, 0, '***NOTHING TO FOLLOWS***', 'LR', 'C');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::setCellPadding('0', '', '', '');
    PDF::MultiCell(660, 20, 'Grand Total: ', 'TLB', 'R', false, 0, 20, PDF::getY());
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 20, $tp, 'TB', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20,  number_format($totalext, 2), 'RTB', 'R', false);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(760, 0, '', 'LR', 'L');

    $maxh = 30;
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(760, 0, ' Remarks: ', 'LR', 'L');
    PDF::SetFont($font, '', $fontsize);
    PDF::setCellPadding('0', '', '', '');

    $arr_rem = $this->reporter->fixcolumn([$data[0]['rem']], 130);
    for ($i = 0; count($arr_rem) > $i; $i++) {
      PDF::MultiCell(60, 0, '', 'L', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(700, 0, isset($arr_rem[$i]) ? $arr_rem[$i] : '', 'R', 'L');
      if (PDF::getY() >= 1000) { //800
        $newpageadd = 1;
        $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
        $this->maxipro_layout_PDF_FOOTER($params, $data);
        $this->default_JO_headerpdf($params, $data, $font, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(760, 0, ' Remarks: ', 'TLR', 'L');
        PDF::SetFont($font, '', $fontsize);
        PDF::setCellPadding('0', '', '', '');
      }
      // code...
    }

    do {
      $this->addrowrem();
    } while (PDF::getY() < 1000); //790

    $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
    $this->maxipro_layout_PDF_FOOTER($params, $data, $totalext);

    $this->maxipro_pageadd($params);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function maxipro_layout_PDF_FOOTER($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 20, 'Office Address: ', 'TRL', 'L', false, 0);
    PDF::MultiCell(360, 20, '', 'TRL', 'C', false, 0);
    PDF::MultiCell(200, 20, '', 'TRL', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 40, $headerdata[0]->address, 'RBL', 'L', false, 0);
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(360, 40, 'Maxipro Development Corporation', 'RBL', 'C', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 40, ' ' . $headerdata[0]->tel, 'RBL', 'L', false);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(760, 25, 'Form No. ECG-029-0', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
  }


  public function maxipro_layout_PDF_FOOTER_Approved($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(760, 0, '', 'TLR', 'L');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(30, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(280, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'Signify your acceptance and agreement with this', 'R', 'L', false);

    PDF::MultiCell(30, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(280, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(300, 0, 'order by signing below: ', 'R', 'L', false);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(30, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(180, 0, '', '', 'L', false, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L', false, 0);
    PDF::MultiCell(30, 0, '', 'R', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(180, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(90, 0, '', '', 'C', false, 0);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'CONFORME: ', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(30, 0, '', 'R', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(180, 0, 'President', '', 'C', false, 0);
    PDF::MultiCell(90, 0, '', '', 'C', false, 0);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'POSITION: ', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(30, 0, '', 'R', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, '', 'L', 'C', false, 0);
    PDF::MultiCell(180, 0, '', '', 'C', false, 0);
    PDF::MultiCell(90, 0, '', '', 'C', false, 0);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'DATE: ', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(30, 0, '', 'R', 'L', false);
  }

  private function addrowrem()
  {
    PDF::MultiCell(760, 0, '', 'LR', 'L', false);
  }

  public function maxipro_pageadd($params)
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
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALB.ttf');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);


    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 0, 'STANDARD TERMS AND CONDITIONS OF PURCHASE ORDER ISSUED BY', '', 'C', false);
    PDF::MultiCell(800, 0, 'MAXIPRO DEVELOPMENT CORPORATION', '', 'C', false);
    PDF::MultiCell(800, 0, '(MDC)', '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    //one
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LT', 'L', false, 0);
    PDF::MultiCell(700, 0, "These Terms and Conditions shall apply to this PO (Contract except as otherwise indicated and/or agreed upon in writing by Maxipro Development", 'T', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'RT', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(700, 0, "Corporation (MDC) and the Subcontractor/Supplier:", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //two
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Delivery", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Subcontractor/Supplier shall deliver the goods, services, or civil works in accordance with the requirements and", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "specifications of MDC within the delivery period specified in this PO (Contract) to authorized MDC personnel at the", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "place specified in the PO (Contract).", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //three
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(48, 0, "Payment", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(47, 0, "Local", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Supply of goods (materials/equipment) will be paid after the delivery of the goods, inspection, acceptance, and submission", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(48, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(47, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "of invoice and certificate of warranty, as applicable.", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(48, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(47, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Services/Civil works will be paid after an assessment, verification, and approval of Progress Billings by the Project Manager", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(48, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(47, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "based on actual accomplishment, submission of Invoice, and detailed accomplishment or progress of work with photos.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(48, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(47, 0, "Importation", 'R', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Supply of goods (materials/equipment) from outside the Philippines will be paid through Telegraphic Transfer after the", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(48, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(47, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "delivery of the goods, inspection, acceptance, and submission of shipping documents and certificate of warranty, as", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(48, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(47, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "applicable.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(48, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(647, 0, "Partial delivery is acceptable provided the Subcontractor/Supplier meets the delivery period, otherwise, Liquidated Damages shall apply.", 'B', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //four
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LTB', 'L', false, 0);
    PDF::MultiCell(100, 0, "Insurance", 'RTB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LTB', 'L', false, 0);
    PDF::MultiCell(595, 0, "The Subcontractor/Supplier shall be responsible for insurance of goods to be delivered or Employee Accident Insurance.", 'TB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RTB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //five
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Extension of", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Extension of the delivery period or completion period for the performance of goods or services or civil works under the", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Delivery/Completion", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "PO (Contract) may be granted when the delay is caused by force majeure or events clearly beyond the", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Period", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Subcontractor/Supplier’s control and upon submission of 1. Request for an extension by the Subcontractor/Supplier", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "addressed to the Chief Executive Officer; and 2. The request shall be submitted before the expiration of the delivery", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "period.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //six
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Packing and", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Packing and Crating shall be adequate and of suitable rigid construction to prevent damage in transit and to withstand", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Crating", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "any manipulations and rough handling by recognized acceptable standards.", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //seven
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Liquidated Damages", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "The Subcontractor/Supplier shall pay MDC liquidated damages an amount equal to one-tenth of one percent (1%) of the", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "delayed goods or services or civil works scheduled for delivery or performance for every day of delay for failure to", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "satisfactorily deliver goods or service or perform the works. The liquidated damages will be imposed until such goods or", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "services or civil works are finally delivered or performed and accepted by MDC. In no case shall the sum of liquidated", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "damages exceed ten percent (10%) of the total amount stated in the PO (Contract). If it does, the PO (Contract)", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "may be cancelled or rescinded at the option of MDC without prejudice to other courses of action and remedies open to it.", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "MDC may also take over the PO or award the same to a qualified Subcontractor/Supplier through negotiation.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //eight
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Limitation of Liability", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "The aggregate liability of the Subcontractor/Supplier to MDC shall not exceed the amount of PO (contract), provided", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "that the limitation shall not apply to the cost of repairing or replacing defective goods and services, except in cases of", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "criminal negligence, or willful misconduct, or infringement of patent rights.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //nine
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(100, 0, "Offsetting of Obligations", 'R', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Payments due to the Subcontractor/Supplier under this PO may be charged or offset against any outstanding obligations", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "of the", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "of the Subcontractor/Supplier subject to the audit requirements at the discretion of MDC.", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(100, 0, "Subcontractor/Supplier", 'RB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //ten
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Entire Agreement", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "This PO (Contract) contains the entire agreement between MDC and the Subcontractor/Supplier. It may not be modified", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "or terminated orally and no claimed modification, termination, or waiver shall be binding on MDC unless in writing and", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "signed by a duly authorized representative of MDC.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //eleven
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(100, 0, "Validity of Purchase", 'R', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "This PO (Contract) shall be valid and binding between MDC and the Subcontractor/Supplier, herein upon the signing", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Order (Contract)", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "hereof by the Subcontractor/Supplier or upon submission of written confirmation of receipt by the Subcontractor/Supplier", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "of email or fax copy of this PO (Contract) signifying the intention of the latter to be bound thereby.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //twelve
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(100, 0, "Subcontractor/Supplier`s", 'R', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Conditions of the Subcontractor/Supplier contrary to or inconsistent with any condition above are deemed not applicable", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "Condition", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "and without legal effect.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //thirteen
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Risk of Loss ", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "The risk of loss shall be borne by the Subcontractor/Supplier until the goods or services are finally delivered and received", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "by authorized MDC personnel at the place of delivery or upon final acceptance of the civil works by MDC, as the case may", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "be.", 'B', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //fourteen
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Arbitration ", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Any dispute arising out in connection with this PO, validity or termination, shall be referred to and finally resolved by", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, " ", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "arbitration in the Philippines. The language of arbitration shall be English, and the award of arbitration shall be final and", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, " ", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "binding on the Subcontractor/Supplier and MDC.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //fifteen
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Cancellation/ ", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "In case of breach, failure, and/or refusal of the Subcontractor/Supplier to comply with any of the terms and conditions of", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Termination Clause", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "this PO (Contract), for any reasons whatsoever, this PO (Contract) shall be deemed cancelled, rescinded, or", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "terminated without need of notice to the Supplier. Such cancellation or termination is without prejudice to other actions", '', 'J', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'LB', 'L', false, 0);
    PDF::MultiCell(595, 0, "and remedies under the law.", 'B', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'RB', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    //sixteen
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Notices", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "All notices, communications, and correspondences to MDC shall be addressed to:", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "and", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "Correspondences", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(595, 0, "MAXIPRO DEVELOPMENT CORPORATION", '', 'C', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "#86 Apo St., Barangay Lourdes,", '', 'C', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(5, 0, "", 'L', 'L', false, 0);
    PDF::MultiCell(595, 0, "Sta. Mesa Heights, Quezon City 1114", '', 'C', false, 0);
    PDF::MultiCell(5, 0, "", 'R', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(30, 0, "", '', 'L', false, 0);
    PDF::MultiCell(710, 0, "", 'BLR', 'L', false, 0);
    PDF::MultiCell(30, 0, "", '', 'L', false);
  }
}
