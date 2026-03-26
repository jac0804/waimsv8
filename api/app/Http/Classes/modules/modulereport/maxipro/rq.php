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

class rq
{
  private $modulename = "Purchase Requisition";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, "prepared.label", "Requisition by");
    data_set($col1, "received.label", "Checked by");

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '$username' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function maxipro_query($trno)
  {
    $query = "select date(head.dateid) as dateid, head.docno, client.client, 
      client.clientname, head.address, 
      head.terms,head.rem, item.barcode,
      item.itemname, stock.rrqty as qty, stock.uom, 
      stock.rrcost as netamt, stock.disc, round(stock.ext,2) as ext,m.model_name as model,item.sizeid,
      prj.name as projectname, head.revision, sprj.subproject as subprojectname, stock.rqty,stock.rem as stockrem
      from prhead as head 
      left join prstock as stock on stock.trno=head.trno 
      left join client on client.client=head.client
      left join item on item.itemid = stock.itemid
      left join model_masterfile as m on m.model_id = item.model
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      where head.doc='RQ' and head.trno='$trno'
      union all
      select date(head.dateid) as dateid, head.docno, client.client, client.clientname, 
      head.address, head.terms,head.rem, item.barcode,
      item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, round(stock.ext,2) as ext,m.model_name as model,item.sizeid,
      prj.name as projectname, head.revision, sprj.subproject as subprojectname, stock.rqty,stock.rem as stockrem
      from hprhead as head 
      left join hprstock as stock on stock.trno=head.trno 
      left join client on client.client=head.client
      left join item on item.itemid = stock.itemid
      left join model_masterfile as m on m.model_id = item.model
      left join projectmasterfile as prj on prj.line = head.projectid
      left join subproject as sprj on sprj.line = head.subproject
      where head.doc='RQ' and head.trno='$trno'";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_default_query($trno)
  {
    return $this->maxipro_query($trno);
  } //end fn

  public function reportplotting($config, $data)
  {
    ini_set('memory_limit', '-1');
    return $this->maxipro_layout_PDF($config, $data);
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
    $mdc = URL::to('/images/reports/mdc.jpg');
    $tuv = URL::to('/images/reports/tuv.jpg');

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

    $str .= "</div>";
    $str .= "</div>";
    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '600', null, false, "2px solid ", 'T', 'C', $font, '14', 'B', 'red', '2px');
    $str .= $this->reporter->col("", '250', null, false, "2px solid ", 'T', 'C', $font, '14', 'B', 'red', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '600', null, false, $border, '', 'C', $font, '14', 'B', 'red', '2px');
    $str .= $this->reporter->col("HO Control No.: _________________", '250', null, false, $border, '', 'L', $font, '14', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("PURCHASE REQUISITION NO. " . "<b style='color:red;'>" . $data[0]['docno'] . "</b>", '250', null, false, $border, '', 'C', $font, '14', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Name of Office/Project: " . "<b>" . $data[0]['projectname'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("Date: " . "<b>" . $data[0]['dateid'] . "</b>", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Location: " . "<b>" . $data[0]['projectname'] . "</b>", '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("ITEM NO.", '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("DESCRIPTION", '250', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("QTY", '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("UNIT", '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("UNIT PRICE", '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col("AMOUNT", '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '2px');

    $totalext = 0;

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($i + 1, '100', null, false, $border, 'L', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, 'L', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $decimal), '100', null, false, $border, 'L', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data[$i]['uom'], '100', null, false, $border, 'L', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt']), '100', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col("₱" . number_format($data[$i]['ext']), '100', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '5px');
      $totalext = $totalext + $data[$i]['ext'];
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '100', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '250', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'LR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("==========", '100', null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '100', null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '250', null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '100', null, false, $border, 'BLR', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("₱" . number_format($totalext, $decimal), '100', null, false, $border, 'BLR', 'R', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<b>PURPOSE: </b>" . $data[0]['rem'], '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col("", '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Requisition By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col("<div style='width: 250px; border-bottom: 1px solid; text-align: center;'>" . $params['params']['dataparams']['prepared'] . "</div> 
      SITE ENGINEER", '100', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= '<br>';
    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Checked By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col("<div style='width: 250px; border-bottom: 1px solid; text-align: center;'>" . $params['params']['dataparams']['received'] . "</div> 
      PROJECT IN CHARGE/PROJECT MANAGER", '100', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col("<div style='width: 250px; border-bottom: 1px solid; text-align: center;'>" . $params['params']['dataparams']['approved'] . "</div> 
      DEPARTMENT/PROJECT HEAD", '100', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function maxipro_layout_header_PDF($params, $data)
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

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);

    $this->reportheader->getheader($params);

    PDF::MultiCell(0, 0, "\n");

    // PDF::MultiCell(700, 20, "Page " . PDF::PageNo() . "  ", '', 'R', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(700, 0, 'HO Control No.: _________________', '', 'R', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(350, 0, "PURCHASE REQUISITION NO.", '', 'R', false, 0, '',  '');
    PDF::SetTextColor(255, 0, 0);

    if ($data[0]['revision'] != "") {
      $data[0]['revision'] = ' - Revision: ' . $data[0]['revision'];
    }

    PDF::MultiCell(350, 20, $data[0]['docno'] . $data[0]['revision'], '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetTextColor(0, 0, 0);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    $prj_height = PDF::GetStringHeight(400, $data[0]['projectname']);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 20, 'Name of Office/Project: ', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(400, 20, $data[0]['projectname'], '', 'L', 0, 0, '', '', true, 0, true, false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, 'Date: ', '', '', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, date('M d, Y', strtotime($data[0]['dateid'])), '', 'L', false);

    if ($prj_height < 20) {
      PDF::MultiCell(0, '', "\n");
    } else {
      PDF::MultiCell(0, $prj_height, "\n");
    }

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, 'Sub Project: ', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(625, 20, $data[0]['subprojectname'], '', 'L', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(65, 0, '', 'TLR', 'C', false, 0);
    PDF::MultiCell(155, 0, '', 'TLR', 'C', false, 0);
    PDF::MultiCell(110, 0, '', 'TLR', 'C', false, 0);
    PDF::MultiCell(70, 0, '', 'TLR', 'C', false, 0);
    PDF::MultiCell(60, 0, '', 'TLR', 'C', false, 0);
    PDF::MultiCell(100, 0, '', 'TLR', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'TLR', 'C', false);



    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(65, 0, "ITEM NO.", 'LR', 'C', false, 0);
    PDF::MultiCell(155, 0, "DESCRIPTION", 'LR', 'C', false, 0);
    PDF::MultiCell(110, 0, "NOTES", 'LR', 'C', false, 0);
    PDF::MultiCell(70, 0, "QTY", 'LR', 'C', false, 0);
    PDF::MultiCell(60, 0, "UOM", 'LR', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", 'LR', 'C', false, 0);
    PDF::MultiCell(140, 0, "AMOUNT", 'LR', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(65, 0, '', 'LRB', 'C', false, 0);
    PDF::MultiCell(155, 0, '', 'LRB', 'C', false, 0);
    PDF::MultiCell(110, 0, '', 'LRB', 'C', false, 0);
    PDF::MultiCell(70, 0, '', 'LRB', 'C', false, 0);
    PDF::MultiCell(60, 0, '', 'LRB', 'C', false, 0);
    PDF::MultiCell(100, 0, '', 'LRB', 'C', false, 0);
    PDF::MultiCell(140, 0, '', 'LRB', 'C', false);
  }

  public function maxipro_layout_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $requisitionby = $params['params']['dataparams']['prepared'];
    $checkedby = $params['params']['dataparams']['received'];
    $approvedby = $params['params']['dataparams']['approved'];

    $count = $page = 26;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    PDF::SetMargins(40, 40);
    $this->maxipro_layout_header_PDF($params, $data);

    $counter = 0;
    $amt = 0;
    $totalext = 0;
    $newpageadd = 1;
    $peso = '<span>&#8369;</span>'; // petot tign


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'LR');

    if (!empty($data)) {

      for ($i = 0; $i < count($data); $i++) {
        $counter++;
        $maxrow = 1;

        $itemname = $data[$i]['itemname'];
        $stockrem = $data[$i]['stockrem'];
        $rqty = number_format($data[$i]['rqty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['netamt'], 2);

        $tamt = $data[$i]['ext'];

        $arr_counter = $this->reporter->fixcolumn([$counter . ""], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '28', 0);
        $arr_stockrem = $this->reporter->fixcolumn([$stockrem], '28', 0);
        $arr_rqty = $this->reporter->fixcolumn([$rqty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '9', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '17', 0);
        $arr_tamt = $this->reporter->fixcolumn([$peso . ' ' . number_format($tamt, 2) . ""], '27', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_counter, $arr_itemname, $arr_stockrem, $arr_rqty, $arr_uom, $arr_amt, $arr_tamt]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(65, 20, (isset($arr_counter[$r]) ? $arr_counter[$r] : ''), 'L', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(155, 20, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(110, 20, ' ' . (isset($arr_stockrem[$r]) ? $arr_stockrem[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 20, (isset($arr_rqty[$r]) ? $arr_rqty[$r] : '') . ' ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(60, 20, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 20, (isset($arr_amt[$r]) ? $arr_amt[$r] : '') . ' ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFont('dejavusans', 'R', $fontsize);
          PDF::MultiCell(140, 20, (isset($arr_tamt[$r]) ? $arr_tamt[$r] : '') . ' ', 'R', 'R', false, 1, '',  '', true, 0, true, true, 0, 'M', false);
        }

        $totalext += $tamt;
        if (PDF::getY() >= 850) { //680
          $this->addrow();
          $this->maxipro_layout_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, '==========', 'LR', 'R');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::setCellPadding('0', '', '', '');
    PDF::MultiCell(550, 25, '', 'BL', 'R', false, 0, 40, PDF::getY());
    PDF::MultiCell(150, 25,  'Php ' . number_format($totalext, 2), 'BR', 'R', false);

    if (PDF::getY() >= 850) {
      $this->addrow();
      $this->headerpage($params, $data);
    }

    $this->footer($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function headerpage($params, $data)
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

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);

    $this->reportheader->getheader($params);

    PDF::MultiCell(0, 0, "\n");

    // PDF::MultiCell(700, 20, "Page " . PDF::PageNo() . "  ", '', 'R', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);
  }

  public function footer($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $requisitionby = $params['params']['dataparams']['prepared'];
    $checkedby = $params['params']['dataparams']['received'];
    $approvedby = $params['params']['dataparams']['approved'];
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $left = '10';
    $top = '';
    $right = '';
    $bottom = '';
    PDF::setCellPadding($left, $top, $right, $bottom);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, "");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, 'PURPOSE:', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(630, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 20, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, $requisitionby, '', 'C');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, 'Requisition By :	', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, 'SITE ENGINEER', 'T', 'C', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, $checkedby, '', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::MultiCell(100, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, $approvedby, '', 'C', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, 'Checked By :', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, 'PROJECT IN CHARGE/PROJECT MANAGER', 'T', 'C', 0, 0, '', '', true, 0, true, false);

    PDF::MultiCell(100, 0, '', '', 'C', 0, 0, '', '', true, 0, true, false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, 'Approved By :', '', 'L', 0, 0, '', '', true, 0, true, false);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 0, 'DEPARTMENT/PROJECT HEAD', 'T', 'C', 0, 0, '', '', true, 0, true, false);
  }

  private function addrow()
  {
    PDF::MultiCell(700, 15, "", 'T', 'C', false);
  }
}
