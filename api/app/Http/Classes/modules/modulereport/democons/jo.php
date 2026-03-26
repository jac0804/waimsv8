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
      select head.docno, cl.client, cl.clientname, cl.addr as address, cl.email, cl.tel, 
      cl.contact as contactperson,
      prj.name as projectname, prj.code as projectcode,
      sprj.subproject as subprojectname,
      head.workdesc, head.terms, date(head.dateid) as dateid,
      head.workloc, item.itemname, stock." . $this->hqty . " as qty, stock.uom, stock." . $this->damt . " as unitprice, stock.ext,
      head.rem, head.revision, right(head.yourref,13) as yourref, date(head.start) as startdate, 
      date(head.due) as enddate, stock.rem as notes
      from " . $this->head . " as head
      left join " . $this->stock . " as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.client = head.client
      left join item as item on item.itemid = stock.itemid
      where num.doc = 'JO' and head.trno = '$trno'
      union all 
      select head.docno, cl.client, cl.clientname, cl.addr as address, cl.email, cl.tel, 
      cl.contact as contactperson,
      prj.name as projectname, prj.code as projectcode,
      sprj.subproject as subprojectname,
      head.workdesc, head.terms, date(head.dateid) as dateid,
      head.workloc, item.itemname, stock." . $this->hqty . " as qty, stock.uom, stock." . $this->damt . " as unitprice, stock.ext,
      head.rem, head.revision, right(head.yourref,13) as yourref, date(head.start) as startdate, 
      date(head.due) as enddate, stock.rem as notes
      from " . $this->hhead . " as head
      left join " . $this->hstock . " as stock on stock.trno = head.trno
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.client = head.client
      left join item as item on item.itemid = stock.itemid
      where num.doc = 'JO' and head.trno = '$trno'
    ";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    return $this->maxipro_layout_PDF($params, $data);
  }

  public function default_JO_headerpdf($params, $data, $font)
  {
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
    PDF::AddPage('p', [800, 1000]);


    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(550, 25, '', 'TLR', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(210, 25, '', 'TLR', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(550, 40, 'JOB ORDER', 'LRB', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::SetTextColor(255, 0, 0);
    PDF::MultiCell(210, 40, $data[0]['docno'], 'LRB', 'C', false);

    PDF::SetTextColor(0, 0, 0);

    if ($data[0]['revision'] != '') {
      PDF::SetFont($fontbold, '', 10);
      PDF::MultiCell(210, 0, 'Revision: ' . $data[0]['revision'], '', 'C', false, 1, 565, 80);

      PDF::SetFont($fontbold, '', 6);
      PDF::MultiCell(0, 0, "\n");
    }

    PDF::Image($this->companysetup->getlogopath($params['params']).'sbc.png', '25', '40', 100, 55);
   

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

    $yourref = $data[0]['yourref'] * 1;
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 20, 'JO Requisition No.: ', 'LTB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, $yourref, 'RTB', 'L', false);

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

    $countarrnotes = 0;
    for ($i = 0; $i < count($data); $i++) {
      $counter++;

      $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], 40, 0);
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
            $unitprice = number_format($data[$i]['unitprice'], $decimalprice);
            $tamt = number_format($data[$i]['ext'], $decimalprice);
            $php = 'Php ';
          } else {
            $inum = '';
            $qty = '';
            $uom = '';
            $unitprice = '';
            $tamt = '';
            $php = '';
          }
          PDF::SetFont($font, '', $fontsize);
          PDF::setCellPadding('2', '', '', '');
          PDF::MultiCell(50, 0, $inum, 'L', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(230, 0, isset($itemname[$j]) ? $itemname[$j] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(130, 0, isset($notesname[$j]) ? $notesname[$j] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(85, 0, $qty, '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(80, 0, $uom, '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(85, 0, $unitprice, '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(30, 0, $php, '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(70, 0, $tamt, 'R', 'R', 0, 1, '', '', true, 0, false, false);

          if (PDF::getY() >= 775) { //780
            $newpageadd = 1;
            $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
            $this->maxipro_layout_PDF_FOOTER($params, $data);
            $this->default_JO_headerpdf($params, $data, $font);
          }
        }
      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() > 780) {
        $newpageadd = 1;
        $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
        $this->maxipro_layout_PDF_FOOTER($params, $data);
        $this->default_JO_headerpdf($params, $data, $font);
      }
    }



    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(760, 0, '***NOTHING TO FOLLOWS***', 'LR', 'C');



    PDF::SetFont($fontbold, '', $fontsize);
    PDF::setCellPadding('0', '', '', '');
    PDF::MultiCell(660, 20, 'Total: ', 'TLB', 'R', false, 0, 20, PDF::getY());
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(30, 20, 'Php ', 'TB', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20,  number_format($totalext, $decimalprice), 'RTB', 'R', false);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(760, 0, '', 'LR', 'L');

    $maxh = 30;
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(760, 0, ' Remarks: ', 'LR', 'L');
    PDF::SetFont($font, '', $fontsize);
    PDF::setCellPadding('0', '', '', '');

    $arr_rem = $this->reporter->fixcolumn([$data[0]['rem']], 140);
    for ($i = 0; count($arr_rem) > $i; $i++) {
      PDF::MultiCell(60, 0, '', 'L', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(700, 0, isset($arr_rem[$i]) ? $arr_rem[$i] : '', 'R', 'L');
      if (PDF::getY() >= 800) { //800
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
    } while (PDF::getY() < 790); //790

    $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
    $this->maxipro_layout_PDF_FOOTER($params, $data, $totalext);


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
    PDF::MultiCell(360, 40, $this->companysetup->getcompanyname($params['params']), 'RBL', 'C', false, 0);
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


  private function addrow()
  {
    PDF::MultiCell(760, 0, '', 'LR', 'L', false);
  }

  private function addrowrem()
  {
    PDF::MultiCell(760, 0, '', 'LR', 'L', false);
  }
}
