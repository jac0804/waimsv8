<?php

namespace App\Http\Classes\modules\modulereport\msse;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\common\commonsbc;

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
  private $reportheader;
  private $commonsbc;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->reportheader = new reportheader;
    $this->commonsbc = new commonsbc;
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'prepared', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],

    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        '0' as reporttype,
        '' as prepared
        "
    );
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
            right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, head.agent,
            item.sizeid, ag.clientname as agname, item.brand,
            wh.client as whcode, wh.clientname as whname from lahead as head
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
            item.itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
            item.sizeid, ag.clientname as agname, item.brand,
            wh.client as whcode, wh.clientname as whname from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.clientid=head.agentid
            left join client as wh on wh.clientid=head.whid
            where head.doc='sj' and head.trno='$trno' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_sj_query($trno)
  {

    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
          right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
          head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
          item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
          item.sizeid, ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc
          from lahead as head
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join item on item.itemid=stock.itemid
          left join client as ag on ag.client=head.agent
          left join client as wh on wh.client=head.wh
          left join part_masterfile as part on part.part_id = item.part
          left join frontend_ebrands as brands on brands.brandid = item.brand
          where head.doc='sj' and head.trno='$trno'
          UNION ALL
          select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
          right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
          head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
          item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
          item.sizeid, ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc
          from glhead as head
          left join glstock as stock on stock.trno=head.trno
          left join client on client.clientid=head.clientid
          left join item on item.itemid=stock.itemid
          left join client as ag on ag.clientid=head.agentid
          left join client as wh on wh.clientid=head.whid
          left join part_masterfile as part on part.part_id = item.part
          left join frontend_ebrands as brands on brands.brandid = item.brand
          where head.doc='sj' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    return $this->default_sjmsse_PDF($params, $data);
  }

  public function default_sjmsse_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "courier";
    $fontbold = "";
    $fontsize = 13;

    if (Storage::disk('sbcpath')->exists('/fonts/Courier bold.ttf')) {
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/Courier bold.ttf');
    }
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(20, 20);
    PDF::AddPage('p', [800, 1000]);



    PDF::SetFont($fontbold, '', $fontsize);

    $date = $data[0]['dateid'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, 615, 87); //615,75

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(550, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, 120,  115); //120,105
    PDF::MultiCell(500, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, 103,  140); //103,130


    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['yourref']) ? $data[0]['yourref'] : '', '', 'L', false, 1, 630, 115);
    PDF::MultiCell(190, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, 630, 140); //630,130
    PDF::MultiCell(190, 0, isset($data[0]['agname']) ? $data[0]['agname'] : '', '', 'L', false, 1, 630, 170); //630,155
  }

  public function default_sjmsse_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 13;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";

    $font = "";
    $fontbold = "";
    if (Storage::disk('sbcpath')->exists('/fonts/Courier bold.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/Courier bold.ttf');
    }
    $this->default_sjmsse_header_PDF($params, $data);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(700, 0, '', '', '', false, 1, '', '208'); //198
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        if ($data[$i]['disc'] != '') {
          $Disc = explode('/', $data[$i]['disc']);
        } else {
          $Disc = [];
        } //end if

        $Disc1 = '';
        for ($a = 0; (count($Disc) - 1) >= $a; $a++) {
          if ($this->commonsbc->left($Disc[$a], 1) == '+') {
            if ($Disc1 == '') {
              $Disc1 .=  str_replace('+', 'Add', $Disc[$a]);
            } else {
              $Disc1 .=  '/' . str_replace('+', 'Add', $Disc[$a]);
            }
          } else {
            if ($Disc1 == '') {
              $Disc1 .= 'L' . $Disc[$a];
            } else {
              $Disc1 .= '/' . 'L' . $Disc[$a];
            }
          }
        } //end each

        $arr_item = $this->reporter->fixcolumn([$data[$i]['itemname']], 180);
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 0)], 12);
        $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], 10);
        $arr_srem = $this->reporter->fixcolumn([$data[$i]['srem']], 20);
        $arr_disc = $this->reporter->fixcolumn([$Disc1], 15);
        $arr_amt = $this->reporter->fixcolumn([number_format($data[$i]['amt'], 2)], 20);
        $arr_ext = $this->reporter->fixcolumn([number_format($data[$i]['ext'], 2)], 20);

        $maxrow = $this->othersClass->getmaxcolumn($arr_item, $arr_qty, $arr_uom, $arr_srem, $arr_disc, $arr_amt, $arr_ext);
        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $amt = $data[$i]['amt'];
            } else {
              $amt = '';
            }

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(70, 26, '', '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(100, 26, isset($arr_qty[$r]) ? $arr_qty[$r] : '', '', 'R', false, 0, '-10', '', false, 1);
            PDF::MultiCell(100, 26, isset($arr_uom[$r]) ? $arr_uom[$r] : '', '', 'L', false, 0, '100', '', false, 1);
            PDF::MultiCell(300, 26, isset($arr_item[$r]) ? $arr_item[$r] : '', '', 'L', false, 0, '150', '', false, 1);
            PDF::MultiCell(80, 26, isset($arr_disc[$r]) ? $arr_disc[$r]  : '', '', 'R', false, 0, '453', '', false, 1);
            PDF::MultiCell(100, 26, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0, '523', '', false, 1);
            PDF::MultiCell(100, 26, isset($arr_ext[$r]) ? $arr_ext[$r] : '', '', 'R', false, 1, '638', '', false, 1);
          }
        }
        $totalext += $data[$i]['ext'];
      }
    }
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);


    PDF::MultiCell(325, 0, '', '', 'C', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, 2), '', 'R', false, 1, 640, 652); //640,632


    $prep = $params['params']['dataparams']['prepared'];

    PDF::MultiCell(175, 15, $prep, '', 'C', false, 0, '103', '650');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
