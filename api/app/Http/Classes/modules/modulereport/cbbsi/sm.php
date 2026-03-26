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

class sm
{
  private $modulename = "Supplier Invoice";
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'snstock';
  public $hstock = 'hsnstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
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

  public function createreportfilter()
  {
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Supplier Invoice', 'value' => 'rep1', 'color' => 'red'],
      ['label' => 'Price Change Computation Report', 'value' => 'rep2', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        'rep1' as reporttype,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  public function report_default_query($params, $trno)
  {
    $report = $params['params']['dataparams']['reporttype'];
    switch ($report) {
      case 'rep1':
        $query = "select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid,head.yourref, 
                          head.terms, head.rem, item.barcode, item.itemname, stock." . $this->damt . " as gross, 
                          stock." . $this->hamt . " as netamt, stock." . $this->dqty . " as qty,stock.uom, stock.disc, stock.ext, stock.line, wh.client as wh, wh.clientname as whname, item.sizeid, 
                          m.model_name as model, numinfo.freight,concat(left(stock.ref,2),right(stock.ref,7)) as ref
                  from lahead as head 
                  left join snstock as stock on stock.trno=head.trno
                  left join cntnum on head.trno = cntnum.trno
                  left join client as wh on wh.clientid = stock.whid
                  left join item on item.itemid = stock.itemid
                  left join model_masterfile as m on m.model_id = item.model
                  left join cntnuminfo as numinfo on numinfo.trno=head.trno
                  where head.trno='$trno'
                  union all
                  select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid,
                          head.yourref,  head.terms, head.rem,item.barcode, item.itemname, 
                          stock." . $this->damt . " as gross, stock." . $this->hamt . " as netamt, 
                          stock." . $this->dqty . " as qty,stock.uom, stock.disc, stock.ext, stock.line, 
                          wh.client as wh, wh.clientname as whname,item.sizeid, 
                          m.model_name as model, numinfo.freight,concat(left(stock.ref,2),right(stock.ref,7)) as ref
                  from glhead as head 
                  left join hsnstock as stock on stock.trno=head.trno
                  left join cntnum on head.trno = cntnum.trno
                  left join item on item.itemid=stock.itemid
                  left join client as wh on wh.clientid = stock.whid
                  left join model_masterfile as m on m.model_id = item.model
                  left join cntnuminfo as numinfo on numinfo.trno=head.trno
                  where head.trno='$trno'
                  order by line";
        break;

      case 'rep2':
        $query = "select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid,
                        head.yourref,head.ourref,head.terms, head.rem, item.barcode, item.itemname, 
                        stock." . $this->damt . " as gross, stock." . $this->hamt . " as landedcost, 
                        (stock.cost * stock.rrqty) as landedamt,
                        stock." . $this->dqty . " as qty,stock.uom, stock.disc, stock.ext, stock.line, 
                        wh.client as wh, wh.clientname as whname, item.sizeid,m.model_name as model, numinfo.freight,item.amt as cursrp,item.body,rrs.rrqty,
                        format(stock.lastcost*uom.factor,2) as prevcost,
                        (select sum(bal) from rrstatus as stat where stat.itemid=stock.itemid and stat.whid=hwh.clientid) as whqty,uom.factor,
                        (select sum(bal) from rrstatus as stat where stat.itemid=stock.itemid) as ttlqty,
                        ifnull((select round(rrcost,2) from
                        (select po.rrcost,rr.trno,rr.line
                          from hpostock as po
                          left join glstock as rr on rr.refx = po.trno and rr.linex=po.line
                          left join glhead as rrh on rrh.trno=rr.trno
                          where rrh.doc='RR') as k where k.trno=stock.refx and k.line = stock.linex),0) as pocost
                  from lahead as head 
                  left join snstock as stock on stock.trno=head.trno
                  left join cntnum on head.trno = cntnum.trno
                  left join client as wh on wh.clientid = stock.whid
                  left join client as hwh on hwh.client = head.wh
                  left join item on item.itemid = stock.itemid
                  left join model_masterfile as m on m.model_id = item.model
                  left join cntnuminfo as numinfo on numinfo.trno=head.trno
                  left join glstock as rrs on rrs.trno=stock.refx and rrs.line=stock.linex
                  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                  where head.trno='$trno'
                  union all
                  select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid,
                          head.yourref, head.ourref, head.terms, head.rem,item.barcode, item.itemname, 
                          stock." . $this->damt . " as gross, stock." . $this->hamt . " as landedcost, 
                           (stock.cost * stock.rrqty) as landedamt,
                          stock." . $this->dqty . " as qty,stock.uom, stock.disc, stock.ext, stock.line, 
                          wh.client as wh, wh.clientname as whname,item.sizeid, 
                          m.model_name as model, numinfo.freight,item.amt as cursrp,item.body,rrs.rrqty,
                        format(stock.lastcost*uom.factor,2) as prevcost,
                        (select sum(bal) from rrstatus as stat where stat.itemid=stock.itemid and stat.whid=head.whid) as whqty,uom.factor,
                        (select sum(bal) from rrstatus as stat where stat.itemid=stock.itemid) as ttlqty,
                        ifnull((select round(rrcost,2) from
                        (select po.rrcost,rr.trno,rr.line
                          from hpostock as po
                          left join glstock as rr on rr.refx = po.trno and rr.linex=po.line
                          left join glhead as rrh on rrh.trno=rr.trno
                          where rrh.doc='RR') as k where k.trno=stock.refx and k.line = stock.linex),0) as pocost
                  from glhead as head 
                  left join hsnstock as stock on stock.trno=head.trno
                  left join cntnum on head.trno = cntnum.trno
                  left join item on item.itemid=stock.itemid
                  left join client as wh on wh.clientid = stock.whid
                  left join model_masterfile as m on m.model_id = item.model
                  left join cntnuminfo as numinfo on numinfo.trno=head.trno
                  left join glstock as rrs on rrs.trno=stock.refx and rrs.line=stock.linex
                  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                  where head.trno='$trno'
                  order by line";
        break;
    }
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', -1);
    $report = $params['params']['dataparams']['reporttype'];

    if ($report == 'rep1') {
      return $this->default_SM_PDF($params, $data);
    } else {
      return $this->Price_Change_SM_PDF($params, $data);
    }
  }


  public function Price_Change_SM_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $fsize10 = 10;
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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(620, 0, 'Price Change Computation Report', '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, " Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(220, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(560, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, " Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(560, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, " Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Yourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, "Ourref: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(60, 0, " Freight: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(220, 0, (isset($data[0]['freight']) ? $data[0]['freight'] : ''), 'B', 'L', false);


    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(905, 5, '', 'T');

    PDF::SetFont($font, 'B', $fsize10);
    PDF::MultiCell(40, 0, "NEW SRP", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "CURRENT SRP", '', 'C', false, 0);
    PDF::MultiCell(90, 0, "ITEMCODE", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "DESCRIPTION", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(70, 0, "BIN", '', 'C', false, 0);
    PDF::MultiCell(55, 0, "RR QTY", '', 'R', false, 0);
    PDF::MultiCell(55, 0, "INV QTY", '', 'R', false, 0);
    PDF::MultiCell(75, 0, "PO COST", '', 'R', false, 0);
    PDF::MultiCell(75, 0, "LANDED COST", '', 'R', false, 0);
    PDF::MultiCell(75, 0, "PREV COST", '', 'R', false, 0);
    PDF::MultiCell(55, 0, "WH QTY", '', 'R', false, 0);
    PDF::MultiCell(55, 0, "TTL QTY", '', 'R', false);

    PDF::MultiCell(905, 20, '', 'B');
  }

  public function Price_Change_SM_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totallandedcost = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    $fsize10 = 10;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->Price_Change_SM_header_PDF($params, $data);
    PDF::MultiCell(0, 0, "\n");

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $amt = number_format($data[$i]['cursrp'], $decimalqty);
      $barcode = $data[$i]['barcode'];
      $itemname = $data[$i]['itemname'];
      $uom = $data[$i]['uom'];
      $bin = $data[$i]['body'];
      $qty = $data[$i]['qty'] != 0 ? number_format($data[$i]['qty'], $decimalqty) : '-';
      $rrqty = $data[$i]['rrqty'] != 0 ? number_format($data[$i]['rrqty'], $decimalqty) : '-';
      $pocost = $data[$i]['pocost'] != 0 ? number_format($data[$i]['pocost'], $decimalprice) : '-';
      $landedcost = $data[$i]['landedcost'] != 0 ? number_format($data[$i]['landedcost'], $decimalprice) : '-';
      $prevcost = $data[$i]['prevcost'] != 0 ? number_format($data[$i]['prevcost'], $decimalprice) : '-';
      $whqty = $data[$i]['whqty'] != 0 ? number_format($data[$i]['whqty'], $decimalqty) : '-';
      $ttlqty = $data[$i]['ttlqty'] != 0 ? number_format($data[$i]['ttlqty'], $decimalqty) : '-';

      $arr_amt = $this->reporter->fixcolumn([$amt], '8', 0);
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '28', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '10', 0);
      $arr_bin = $this->reporter->fixcolumn([$bin], '12', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '9', 0);
      $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '9', 0);
      $arr_pocost = $this->reporter->fixcolumn([$pocost], '10', 0);
      $arr_landedcost = $this->reporter->fixcolumn([$landedcost], '10', 0);
      $arr_prevcost = $this->reporter->fixcolumn([$prevcost], '10', 0);
      $arr_whqty = $this->reporter->fixcolumn([$whqty], '9', 0);
      $arr_ttlqty = $this->reporter->fixcolumn([$ttlqty], '9', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_amt, $arr_barcode, $arr_itemname, $arr_uom, $arr_bin, $arr_qty, $arr_rrqty, $arr_pocost, $arr_landedcost, $arr_prevcost, $arr_whqty, $arr_ttlqty]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fsize10);
        PDF::MultiCell(40, 0, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(50, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(150, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(60, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 0, (isset($arr_bin[$r]) ? $arr_bin[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(55, 0, (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(55, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(75, 0, (isset($arr_pocost[$r]) ? $arr_pocost[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(75, 0, (isset($arr_landedcost[$r]) ? $arr_landedcost[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(75, 0, (isset($arr_prevcost[$r]) ? $arr_prevcost[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(55, 0, (isset($arr_whqty[$r]) ? $arr_whqty[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(55, 0, (isset($arr_ttlqty[$r]) ? $arr_ttlqty[$r] : ''), '', 'R', 0, 1, '', '', true, 0, true, false);
      }

      $totalext += $data[$i]['ext'];
      $totallandedcost += $data[$i]['landedamt'];

      if (intVal($i) + 1 == $page) {
        $this->Price_Change_SM_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(905, 0, '', 'B');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(805, 0, 'TOTAL INVOICE AMOUNT : ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), '', 'R');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(805, 0, 'TOTAL LANDED COST : ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totallandedcost, $decimalprice), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(855, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(301, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(301, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(303, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(301, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(301, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(303, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_SM_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(120, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Yourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 0, "Ourref: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, " Freight: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, (isset($data[0]['freight']) ? $data[0]['freight'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(295, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
    PDF::MultiCell(125, 0, "TOTAL", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "REF", '', 'C', false);

    PDF::MultiCell(720, 0, '', 'B');
  }

  public function default_SM_PDF($params, $data)
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
    $this->default_SM_header_PDF($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $qty = number_format($data[$i]['qty'], $decimalqty);
      $uom = $data[$i]['uom'];
      $itemname = $data[$i]['itemname'];
      $netamt = number_format($data[$i]['netamt'], $decimalprice);
      $ref = $data[$i]['ref'];
      $ext = number_format($data[$i]['ext'], $decimalprice);

      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
      $arr_netamt = $this->reporter->fixcolumn([$netamt], '13', 0);
      $arr_ref = $this->reporter->fixcolumn([$ref], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_itemname, $arr_netamt, $arr_ref, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(50, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(50, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(295, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 0, (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(125, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'C', 0, 1, '', '', true, 0, true, false);
      }

      $totalext += $data[$i]['ext'];

      if (intVal($i) + 1 == $page) {
        $this->default_SM_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(720, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(550, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(150, 0, number_format($totalext, $decimalprice), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
