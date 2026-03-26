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
use Symfony\Component\VarDumper\VarDumper;

class po
{

  private $modulename = "Purchase Order";
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
    $user = $config['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if ((isset($name[0]['name']) ? $name[0]['name'] : '') != '') {
      $user = $name[0]['name'];
    }
    $paramstr = "select
                  'PDFM' as print,
                  '$user' as prepared,
                  '' as approved,
                  '' as received";
    return $this->coreFunctions->opentable($paramstr);
  }

  public function reportplotting($params, $data)
  {
    return $this->maxipro_layout_PDF($params, $data);
  }

  public function report_default_query($trno)
  {
    $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,
        head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as unitprice, stock.disc, round(stock.ext,2) as ext,
        m.model_name as model,item.sizeid,
        head.revision, client.tel, client.email, client.contact as contactperson,
        prj.name as projectname, prj.code as projectcode,
        sprj.subproject as subprojectname, head.yourref,
        left(head.deldate, 10) as deldate, head.deladdress, stock.rem as notes
        from pohead as head 
        left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join projectmasterfile as prj on prj.line = head.projectid
        left join subproject as sprj on sprj.line = head.subproject
        where head.doc='po' and head.trno='$trno'
        union all
        select date(head.dateid) as dateid, head.docno, client.client, client.clientname,
        head.address, head.terms,head.rem, item.barcode,
        item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as unitprice, stock.disc, round(stock.ext,2) as ext,
        m.model_name as model,item.sizeid,
        head.revision, client.tel, client.email, client.contact as contactperson,
        prj.name as projectname, prj.code as projectcode,
        sprj.subproject as subprojectname, head.yourref,
        left(head.deldate, 10) as deldate, head.deladdress, stock.rem as notes
        from hpohead as head 
        left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join projectmasterfile as prj on prj.line = head.projectid
        left join subproject as sprj on sprj.line = head.subproject
        where head.doc='po' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function default_PO_headerpdf($params, $data, $font, $noheader = 0)
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
    PDF::AddPage('p', [800, 1000]);


    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(550, 25, '', 'TLR', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(210, 25, '', 'TLR', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(550, 40, strtoupper($this->modulename), 'LRB', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::SetTextColor(255, 0, 0);
    PDF::MultiCell(210, 40, $data[0]['docno'], 'LRB', 'C', false);


    PDF::SetTextColor(0, 0, 0);

    if ($data[0]['revision'] != '') {
      PDF::SetFont($fontbold, '', 10);
      PDF::MultiCell(210, 0, 'Revision: ' . $data[0]['revision'], '', 'C', false, 1, 565, 80);

      PDF::SetFont($fontbold, '', 6);
      PDF::MultiCell(0, 0, "\n");
    }

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'sbc.png', '25', '40', 100, 55);

    $left = '10';
    $top = '';
    $right = '';
    $bottom = '';

    PDF::setCellPadding($left, $top, $right, $bottom);
    PDF::SetFont($fontbold, '', $fontsize);

    PDF::MultiCell(110, 20, 'Supplier Name: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(440, 20, $data[0]['clientname'], 'R', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, 'Date: ', 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(160, 20, date('M d, Y', strtotime($data[0]['dateid'])), 'RB', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 20, 'Address: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(440, 20, $data[0]['address'], 'R', 'L', false, 0);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(210, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 'R', 'C', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(550, 40, '', 'LR', 'L', false, 0);
    PDF::MultiCell(210, 40, 'The order number must be appear on the papers, invoices, packing list and correspondence.', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 20, 'Tel No.: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(440, 20, $data[0]['tel'], 'R', 'L', false, 0);
    PDF::MultiCell(210, 20, '', 'LR', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 20, 'Email: ', 'L', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(440, 20, $data[0]['email'], 'R', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(210, 20, '', 'LR', 'L', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 20, 'Contact Person: ', 'LB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(440, 20, $data[0]['contactperson'], 'BR', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(55, 20, 'PR No.: ', 'LTB', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(155, 20, $data[0]['yourref'], 'RTB', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(105, 20, 'Delivery Date: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(340, 20, date('M d, Y', strtotime($data[0]['deldate'])), '', 'L', false, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(115, 20, 'Terms of Payment: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 20, $data[0]['terms'], 'R', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(105, 20, 'Delivery Address: ', 'L', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(655, 20, $data[0]['deladdress'], 'R', 'L', false);

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
    PDF::MultiCell(105, 20, 'Project Code: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(648, 20, $data[0]['projectcode'], 'R', 'L', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(7, 20, '', 'L', 'L', false, 0);
    PDF::MultiCell(105, 20, 'Subproject Name: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(648, 20, $data[0]['subprojectname'], 'R', 'L', false);

    if ($noheader != 1) {
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 0, 'ITEM NO.', 'LRTB', 'C', false, 0);
      PDF::MultiCell(220, 0, 'DESCRIPTION', 'LRTB', 'C', false, 0);
      PDF::MultiCell(137, 0, 'NOTES', 'LRTB', 'C', false, 0);
      PDF::MultiCell(65, 0, 'QTY', 'LRTB', 'C', false, 0);
      PDF::MultiCell(50, 0, 'UOM', 'LRTB', 'C', false, 0);
      PDF::MultiCell(45, 0, 'DISC', 'LRTB', 'C', false, 0);
      PDF::MultiCell(93, 0, 'UNIT PRICE', 'LRTB', 'C', false, 0);
      PDF::MultiCell(100, 0, 'AMOUNT', 'LRTB', 'C', false);
    }

    $left = '3';
    PDF::setCellPadding($left, $top, $right, $bottom);

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
    $count = 890;
    $page = 880; //890
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

    $this->default_PO_headerpdf($params, $data, $font);

    $counter = 0;
    $cc = 0;
    $maxrow1 = 40;
    $countarrnotes = 0;

    $newpageadd = 0;
    $maxrow = 1;
    $peso = '<span>&#8369;</span>';
    for ($i = 0; $i < count($data); $i++) {
      $counter++;
      $arr_ctr = $this->reporter->fixcolumn([$counter . ""], 10);
      $arr_itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], 38);
      $arr_notes = $this->reporter->fixcolumn([$data[$i]['notes']], 20);
      $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 2)], 15);
      $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], 10);
      $arr_disc = $this->reporter->fixcolumn([$data[$i]['disc']], 10);
      $arr_unitprice = $this->reporter->fixcolumn([$peso . ' ' . number_format($data[$i]['unitprice'], $decimalcurr)], 23); #21,23
      $arr_ext = $this->reporter->fixcolumn([$peso . ' ' . number_format($data[$i]['ext'], $decimalcurr)], 25); #25

      $maxrow = $this->othersClass->getmaxcolumn([$arr_ctr, $arr_itemname, $arr_notes, $arr_qty, $arr_uom, $arr_disc, $arr_unitprice, $arr_ext]);

      if ($data[$i]['itemname'] != '') {
        for ($j = 0; $j < $maxrow; $j++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::setCellPadding('2', '', '', '');
          PDF::MultiCell(50, 0, isset($arr_ctr[$j]) ? $arr_ctr[$j] : '', 'L', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(220, 0, isset($arr_itemname[$j]) ? $arr_itemname[$j] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(137, 0, isset($arr_notes[$j]) ? $arr_notes[$j] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(65, 0, isset($arr_qty[$j]) ? $arr_qty[$j] : '', '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, isset($arr_uom[$j]) ? $arr_uom[$j] : '', '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(45, 0, isset($arr_disc[$j]) ? $arr_disc[$j] : '', '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::SetFont('dejavusans', 'R', $fontsize);
          PDF::MultiCell(93, 0, isset($arr_unitprice[$j]) ? $arr_unitprice[$j] : '', '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::SetFont('dejavusans', 'R', $fontsize);
          PDF::MultiCell(100, 0, isset($arr_ext[$j]) ? $arr_ext[$j] : '', 'R', 'R', 0, 1, '', '', true, 0, true, false);

          if (PDF::getY() >= 780) { //880
            $newpageadd = 1;
            $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
            $this->maxipro_layout_PDF_FOOTER($params, $data);
            $this->default_PO_headerpdf($params, $data, $font);
          }
        }
      }


      $totalext += $data[$i]['ext'];

      if (PDF::getY() > 780) {
        $newpageadd = 1;
        $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
        $this->maxipro_layout_PDF_FOOTER($params, $data);
        $this->default_PO_headerpdf($params, $data, $font);
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

    $maxh = 30; //30
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(760, 0, ' Remarks: ', 'LR', 'L');
    PDF::SetFont($font, '', $fontsize);
    PDF::setCellPadding('0', '', '', '');

    $arr_rem = $this->reporter->fixcolumn([$data[0]['rem']], 140);
    for ($i = 0; count($arr_rem) > $i; $i++) {
      PDF::MultiCell(60, 0, '', 'L', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(700, 0, isset($arr_rem[$i]) ? $arr_rem[$i] : '', 'R', 'L');
      if (PDF::getY() >= 800) { //880
        $newpageadd = 1;
        $this->maxipro_layout_PDF_FOOTER_Approved($params, $data);
        $this->maxipro_layout_PDF_FOOTER($params, $data);
        $this->default_PO_headerpdf($params, $data, $font, 1);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(760, 0, ' Remarks: ', 'TLR', 'L');
        PDF::SetFont($font, '', $fontsize);
        PDF::setCellPadding('0', '', '', '');
      }
      // code...
    }
    // end remarks
    // remarks footer
    do {
      $this->addrowrem();
    } while (PDF::getY() < 790); //comment

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

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(760, 0, '', 'LR', 'L');

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
    PDF::MultiCell(760, 25, ' Form No. PUR-002-0', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
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
    PDF::MultiCell(25, 0, '', '', 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(70, 0, '', '', 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(160, 0, '', '', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(60, 0, '', '', 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 0, '', '', 'R', false, 1, '', '', false, 0);
  }


  private function addrowrem()
  {
    PDF::MultiCell(760, 0, '', 'LR', 'L', false);
  }
}
