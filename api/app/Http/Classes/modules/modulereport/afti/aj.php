<?php

namespace App\Http\Classes\modules\modulereport\afti;

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

class aj
{

  private $modulename = "Inventory Adjustment";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

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
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      ['label' => 'Excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set($col1, 'prepared.readonly', true);
    data_set($col1, 'prepared.type', 'lookup');
    data_set($col1, 'prepared.action', 'lookupclient');
    data_set($col1, 'prepared.lookupclass', 'prepared');

    data_set($col1, 'approved.readonly', true);
    data_set($col1, 'approved.type', 'lookup');
    data_set($col1, 'approved.action', 'lookupclient');
    data_set($col1, 'approved.lookupclass', 'approved');

    data_set($col1, 'received.readonly', true);
    data_set($col1, 'received.type', 'lookup');
    data_set($col1, 'received.action', 'lookupclient');
    data_set($col1, 'received.lookupclass', 'received');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "
    select head.vattype, head.tax, stock.rem as remarks, 
    item.sizeid,m.model_name as model,
    head.docno,head.trno, client.clientname, head.address, 
    date(head.dateid) as dateid, head.terms, head.rem,
    item.barcode, item.itemname, stock.rrcost as gross, 
    stock.cost as netamt, stock.rrqty as qty,
    stock.uom, stock.disc, stock.ext, stock.line, info.itemdescription,ifnull(group_concat(rr.serial separator '/ '),'') as serialno,
    sinfo.rem as srem
    from lahead as head 
    left join lastock as stock on stock.trno=head.trno
    left join stockinfo as sinfo on sinfo.trno = stock.trno and sinfo.line = stock.line
    left join item on item.itemid=stock.itemid
    left join model_masterfile as m on m.model_id = item.model
    left join client on client.client = head.wh
    left join iteminfo as info on info.itemid = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where head.trno='$trno' group by head.vattype, head.tax, stock.rem, 
    item.sizeid,m.model_name,
    head.docno,head.trno, client.clientname, head.address, 
    head.dateid, head.terms, head.rem,
    item.barcode, item.itemname, stock.rrcost, 
    stock.cost, stock.rrqty,
    stock.uom, stock.disc, stock.ext, stock.line, info.itemdescription, sinfo.rem
    union all
    select head.vattype, head.tax, stock.rem as remarks,  
    item.sizeid,m.model_name as model,
    head.docno, head.trno, client.clientname, head.address, 
    date(head.dateid) as dateid, head.terms, head.rem,
    item.barcode, item.itemname, stock.rrcost as gross, 
    stock.cost as netamt, stock.rrqty as qty,
    stock.uom, stock.disc, stock.ext, stock.line, info.itemdescription,ifnull(group_concat(rr.serial separator '/ '),'') as serialno,
    sinfo.rem as srem
    from (glhead as head 
    left join glstock as stock on stock.trno=head.trno
    left join hstockinfo as sinfo on sinfo.trno = stock.trno and sinfo.line = stock.line)
    left join item on item.itemid=stock.itemid
    left join model_masterfile as m on m.model_id = item.model
    left join client on client.clientid = head.whid
    left join iteminfo as info on info.itemid = item.itemid
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    where head.trno='$trno' group by head.vattype, head.tax, stock.rem, 
    item.sizeid,m.model_name,
    head.docno,head.trno, client.clientname, head.address, 
    head.dateid, head.terms, head.rem,
    item.barcode, item.itemname, stock.rrcost, 
    stock.cost, stock.rrqty,
    stock.uom, stock.disc, stock.ext, stock.line, info.itemdescription, sinfo.rem order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default" || $params['params']['dataparams']['print'] == "excel") {
      return $this->default_aj_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
    }
    return $this->default_AJ_PDF($params, $data);
  }

  private function rpt_default_header($data, $filters)
  {
    $decimal = $this->companysetup->getdecimal('qty', $filters['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INVENTORY ADJUSTMENT', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WAREHOUSE : ', '90', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '510', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM NAME', '150', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UOM', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('S/N', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('COST', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');

    return $str;
  }

  public function default_aj_layout($filters, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['itemname'], '150', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemdescription'], '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $decimalqty), '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['serialno'], '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'], $decimal), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_default_header($data, $filters);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '120px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '125px', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('', '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }


  public function default_AJ_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize9 = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    PDF::SetFont($font, '', 9);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(340, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(70, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(300, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(340, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, "", '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(100, 0, "ITEM NAME", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UOM", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "S/N", '', 'R', false, 0);
    PDF::MultiCell(60, 0, "COST", '', 'R', false, 0);
    PDF::MultiCell(60, 0, "TOTAL", '', 'R', false);

    PDF::MultiCell(530, 0, '', 'B');
  }

  public function default_AJ_PDF($params, $data)
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
    $fontsize9 = "9";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_AJ_header_PDF($params, $data);

    $arritemname = array();
    $countarr = 0;
    $maxrow = 1;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname =  $data[$i]['itemname'];
        $itemdescription =  $data[$i]['itemdescription'];
        $srem =  $data[$i]['srem'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $serialno = $data[$i]['serialno'];
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        $ext = number_format($data[$i]['ext'], 2);

        $arr_itemname = $this->reporter->fixcolumn([$itemname], '25');
        $arr_itemdescription = $this->reporter->fixcolumn([$itemdescription, $srem], '30', 1);
        $arr_qty = $this->reporter->fixcolumn([$qty], '15');
        $arr_uom = $this->reporter->fixcolumn([$uom], '15');
        $arr_serialno = $this->reporter->fixcolumn([$serialno], '8');
        $arr_netamt = $this->reporter->fixcolumn([$netamt], '15');
        $arr_ext = $this->reporter->fixcolumn([$ext], '18');

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_itemdescription, $arr_qty, $arr_uom, $arr_serialno, $arr_netamt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize9);
          PDF::MultiCell(100, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'T', false);
          PDF::MultiCell(150, 0, (isset($arr_itemdescription[$r]) ? $arr_itemdescription[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'T', false);
          PDF::MultiCell(50, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'T', false);
          PDF::MultiCell(50, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'T', false);
          PDF::MultiCell(50, 0, (isset($arr_serialno[$r]) ? $arr_serialno[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'T', false);
          PDF::MultiCell(65, 0, (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'T', false);
          PDF::MultiCell(65, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'T', false);
        }

        $arritemname = (str_split($data[$i]['itemdescription'], 40));
        $itemcodedescs = [];


        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_AJ_header_PDF($params, $data);
          $page += $count;
        }
      }
    }


    PDF::MultiCell(530, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(400, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(130, 0, number_format($totalext, $decimalcurr), '', 'R');


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(480, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(153, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(153, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
