<?php

namespace App\Http\Classes\modules\modulereport\vitaline;

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

class sj
{
  private $modulename = "Sales Journal";
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
    // data_set($col1, 'radioprint.options', [
    //   ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    //   // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    // ]);
    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'DELIVERY RECEIPT', 'value' => '0', 'color' => 'blue'],
        ['label' => 'SALES INVOICE', 'value' => '1', 'color' => 'blue']
      ]
    );

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '0' as reporttype,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand, head.vattype,
    wh.client as whcode, wh.clientname as whname, stock.loc, stock.expiry
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    where head.doc='sj' and head.trno='$trno'
    UNION ALL
    select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand, head.vattype,
    wh.client as whcode, wh.clientname as whname, stock.loc, stock.expiry
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    where head.doc='sj' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    switch ($params['params']['dataparams']['reporttype']) {
      case '0':
        $str = $this->vitaline_drreport($params, $data); // dr report
        // $str = $this->vitaline_drreport_PDF($params, $data); // dr report
      break;
      case '1':
        $str = $this->vitaline_sireport($params, $data);
        // $str = $this->vitaline_sireport_PDF($params, $data);
      break;
    }
    return $str;
  }


  private function vitaline_drreport($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $border = "1px solid ";
    $fontsize = "14";
    $str .= '<br/><br/><br/><br/><br/><br/>';
    $str .= $this->reporter->beginreport('800');
    $str .= '<div style="margin-left:-60px;margin-top:70px;">';

    $str .= $this->reporter->begintable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '350', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('&nbsp', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '350', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('&nbsp', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['agent']) ? $data[0]['agent'] : ''), '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable();
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data[$i]['barcode'], '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      if ($data[$i]['expiry'] != "") {
        $expiry = date('M-d-Y', strtotime($data[$i]['expiry']));
      } else {
        $expiry = "";
      }
      $str .= $this->reporter->col($data[$i]['itemname'] . "<br> Lot: " . $data[$i]['loc'] . "  Exp: " . $expiry, '375', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])) . $data[$i]['uom'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col('&nbsp;&nbsp;' . number_format($data[$i]['amt'], $decimal), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col('&nbsp;&nbsp;' . number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];
      $str .= $this->reporter->endrow();
    } //end for
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', null, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', null, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('', '375', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('TOTAL: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->col('&nbsp;&nbsp;' . number_format($totalext, $decimal), '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', null, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "</div>";
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function vitaline_sireport($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $border = "1px solid ";
    $fontsize = "14";
    $str .= '<br/><br/><br/><br/><br/><br/>';
    $str .= $this->reporter->beginreport('800');
    $str .= '<div style="margin-left:-60px;margin-top:50px; position: relative;">';

    $str .= $this->reporter->begintable();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('&nbsp','50',null,false, $border,'','R', $font, $fontsize,'B','','');
    $str .= $this->reporter->col("<div style='margin-top: -10px;'>" . (isset($data[0]['dateid']) ? $data[0]['dateid'] : '') . "</div>", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('','100',null,false, $border,'','R', $font, $fontsize,'B','','');
    $str .= $this->reporter->col("<div style='margin-top: -10px;'>" . (isset($data[0]['yourref']) ? $data[0]['yourref'] : '') . "</div>", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('&nbsp','50',null,false, $border,'','R', $font, $fontsize,'B','','');
    $str .= $this->reporter->col("<div style='margin-top: -13px;'>" . (isset($data[0]['terms']) ? $data[0]['terms'] : '') . "</div>", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= "<div style='margin-top: 15px;'>";
    $str .= $this->reporter->begintable();
    $totalext = 0;
    $x = 25;
    $y = 1;
    for ($i = 0; $i < count($data); $i++) {
      if ($x > $i) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col("<div style='margin-top: -20px;'>" . number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])) . "</div>", '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col("<div style='margin-top: -20px;'>" . $data[$i]['uom'] . "</div>", '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col($data[$i]['itemname'] . "<br> Lot: " . $data[$i]['loc'] . "  Exp: " . date('M-d-Y', strtotime($data[$i]['expiry'])), '450', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col("<div style='margin-top: -20px;'>" . '&nbsp;&nbsp;' . number_format($data[$i]['amt'], $decimal) . "</div>", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col("<div style='margin-top: -20px;'>" . '&nbsp;&nbsp;' . number_format($data[$i]['ext'], $decimal) . "</div>", '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $totalext = $totalext + $data[$i]['ext'];
        $str .= $this->reporter->endrow();
      }
      $y++;
    } //end for
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    if ($data[0]['vattype'] == 'VATABLE') {
      $vat = (($totalext / 1.12) * 0.12);
      $amountdue = ($totalext / 1.12);
    } else {
      $vat = 0.00;
      $amountdue = $totalext;
    }
    $str .= "<br>";
    $str .= "<div style='position:absolute; top: 586px; left: -20px;'>";
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '450', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($vat, $decimal), '100', null, false, '1px dotted ', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '450', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($amountdue, $decimal), '100', null, false, '1px dotted ', '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '450', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("<div style='margin-top: 3px;'>" . number_format($totalext, $decimal) . "</div>", '100', null, false, '1px dotted ', '', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endtable();
    $str .= "</div>";
    $str .= "</div>";
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function vitaline_drreport_header_PDF($params, $data, $font)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(0, 150, "", '', 'L');
    PDF::MultiCell(480, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '120',  '');
    PDF::MultiCell(75, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(100, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false, 0, '120',  '');
    PDF::MultiCell(0, 0, "\n\n\n");
  }

  public function vitaline_drreport_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $border = "1px solid ";
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    }
    $this->vitaline_drreport_header_PDF($params, $data, $font);

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $arr_barcode = $this->reporter->fixcolumn([$data[$i]['barcode']],'16',0);
      $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'],$decimalqty).$data[$i]['uom']],'13',0);
      $arr_amt = $this->reporter->fixcolumn([number_format($data[$i]['amt'],$decimalprice)],'13',0);
      $arr_ext = $this->reporter->fixcolumn([number_format($data[$i]['ext'],$decimalprice)],'13',0);
      if ($data[$i]['expiry'] != "") {
        $expiry = date('M-d-Y', strtotime($data[$i]['expiry']));
      } else {
        $expiry = "";
      }
      $arr_desc = $this->reporter->fixcolumn([$data[$i]['itemname'].'<br>Lot: '.$data[$i]['loc'].' Exp: '.$expiry],'40',0);
      $arr_expiry = $this->reporter->fixcolumn([$expiry],'16',0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_qty, $arr_amt, $arr_ext, $arr_expiry]);

      for($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(125, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
        PDF::MultiCell(300, 0, (isset($arr_desc[$r]) ? $arr_desc[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
        PDF::MultiCell(100, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
        PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
        PDF::MultiCell(100, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
        PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false, 40, 'B');
      }
      $totalext += $data[$i]['ext'];

      if (intVal($i) + 1 == $page) {
        $this->vitaline_drreport_header_PDF($params, $data, $font);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, '', '', '', false, 0);
    PDF::MultiCell(350, 0, '', '', '', false, 0);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'TOTAL: ', '', 'L', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function vitaline_sireport_header_PDF($params, $data, $font)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(0, 150, "", '', 'L');
    PDF::MultiCell(480, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '150',  '');
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0, '600',  '');
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(480, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '150',  '180');
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', 0, 0, '600', '200');
    PDF::MultiCell(0, 0, "\n\n\n");
  }

  public function vitaline_sireport_PDF($params, $data)
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
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->vitaline_sireport_header_PDF($params, $data, $font);

    PDF::MultiCell(0, 20, "");
    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(80, 0, number_format($data[$i]['qty'], $decimalqty), '', 'L', 0, 0, '70', '', true, 0, true, false, 40, 'B');
      PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
      // PDF::MultiCell(125, 0, $data[$i]['barcode'], '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
      if ($data[$i]['expiry'] != "") {
        $expiry = date('M-d-Y', strtotime($data[$i]['expiry']));
      } else {
        $expiry = "";
      }
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(350, 0, $data[$i]['itemname'] . "<br>Lot: " . $data[$i]['loc'] . "  Exp: " . $expiry, '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
      PDF::MultiCell(100, 0, number_format($data[$i]['amt'], $decimalqty), '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
      PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalprice), '', 'L', 0, 0, '', '', true, 0, true, false, 40, 'B');
      PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false, 40, 'B');
      $totalext += $data[$i]['ext'];

      if (intVal($i) + 1 == $page) {
        $this->vitaline_sireport_header_PDF($params, $data, $font);
        $page += $count;
      }
    }

    if ($data[0]['vattype'] == 'VATABLE') {
      $vat = (($totalext / 1.12) * 0.12);
      $amountdue = ($totalext / 1.12);
    } else {
      $vat = 0.00;
      $amountdue = $totalext;
    }

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, '', '', '', false, 0);
    PDF::MultiCell(350, 0, '', '', '', false, 0);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, number_format($vat, $decimalcurr), '', 'R', 0, 0, '650', '680');
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(100, 0, number_format($amountdue, $decimalcurr), '', 'R', 0, 0, '650', '700');
    PDF::MultiCell(0, 0, "\n");
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R', 0, 0, '650', '800', true, 0, true, true, 0);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
