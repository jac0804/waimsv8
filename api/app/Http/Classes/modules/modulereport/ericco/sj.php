<?php

namespace App\Http\Classes\modules\modulereport\ericco;

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
use App\Http\Classes\modules\seastar\fa;
use DateTime;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class sj
{

  private $modulename = "Sales Journal";
  private $reportheader;
  private $commonsbc;
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
    $this->reportheader = new reportheader;
    $this->commonsbc = new commonsbc;
  }

  public function createreportfilter($config)
  {

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

    //  data_set($col1, 'radioreporttype.options', [
    //    ['label' => 'HANDYMAN', 'value' => '0', 'color' => 'red'],
    //    ['label' => 'METRO GAISANO', 'value' => '1', 'color' => 'red']
    // ]);
    // data_set($col1, 'radioreporttype.label', 'Report Type');

    $fields = ['radioreporttype', 'prepared', 'checked', 'approved', 'delivered', 'received', 'print'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'radioreporttype.label', "Format");
    data_set(
      $col2,
      'radioreporttype.options',
      [
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'Delivery Receipt', 'value' => 'deliveryR', 'color' => 'red'],
        ['label' => 'Outright Receipt', 'value' => 'outrightR', 'color' => 'red'],
        ['label' => 'Consignment receipt', 'value' => 'consignmentR', 'color' => 'red'],

      ]
    );
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        '' as prepared,
        '' as checked,
        '' as approved,
        '' as delivered,
        '' as received,
        'default' as reporttype
        "
    );
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
            right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.itemname, stock.isqty as qty,uom.printuom as uom , stock.isamt as amt, stock.disc, stock.ext, head.agent,
            item.sizeid, left(ag.clientname,17) as agname, item.brand,ifnull(client.registername,'') as registername,date(head.due) as due,if(head.contact !='', head.contact, ifnull(client.contact,'')) as contact,
            wh.client as whcode, wh.clientname as whname,month(head.dateid) as month,year(head.dateid) as year,client.clientid, sku.sku, sg.stockgrp_name, req.category as group1, req.reqtype as repacker1, req.code as repacker2, req.position as repacker3, req2.category as group2, req2.reqtype as repacker4, req2.code as repacker5, req2.position as repacker6, date(head.sdate1) as sdate1, date(head.sdate2) as sdate2, format(head.amount,0) as amount
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
            left join client as ag on ag.client=head.agent
            left join client as wh on wh.client=head.wh
            left join stockgrp_masterfile as sg on sg.stockgrp_id = item.groupid
            left join reqcategory as req on req.line=head.partreqtypeid
            left join reqcategory as req2 on req2.line=head.pltrno
            left join sku on sku.itemid = item.itemid and sku.groupid = client.groupid and sku.issku =1
            where head.doc='sj' and head.trno='$trno' and stock.noprint<>1
            UNION ALL
            select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
            right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.itemname, stock.isqty as qty, uom.printuom as uom , stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
            item.sizeid, left(ag.clientname,17) as agname,item.brand,
            ifnull(client.registername,'') as registername,date(head.due) as due,if(head.contact !='', head.contact, ifnull(client.contact,'')) as contact,
            wh.client as whcode, wh.clientname as whname,month(head.dateid) as month,year(head.dateid) as year,client.clientid, sku.sku, sg.stockgrp_name, req.category as group1, req.reqtype as repacker1, req.code as repacker2, req.position as repacker3, req2.category as group2, req2.reqtype as repacker4, req2.code as repacker5, req2.position as repacker6, date(head.sdate1) as sdate1, date(head.sdate2) as sdate2, format(head.amount,0) as amount
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
            left join client as ag on ag.clientid=head.agentid
            left join client as wh on wh.clientid=head.whid
            left join stockgrp_masterfile as sg on sg.stockgrp_id = item.groupid 
            left join reqcategory as req on req.line=head.partreqtypeid
            left join reqcategory as req2 on req2.line=head.pltrno
            left join sku on sku.itemid = item.itemid and sku.groupid = client.groupid and sku.issku =1
            where head.doc='sj' and head.trno='$trno' and stock.noprint<>1 order by line";

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
    // $print = $params['params']['dataparams']['print'];
    // $reporttype = $params['params']['dataparams']['reporttype'];

    // switch ($print) {
    //   case 'PDFM':
    //     switch ($reporttype) {
    //       case '0': //HANDYMAN
    //         return $this->handyman_layout_PDF($params, $data);
    //         break;
    //       case '1': //METRO GAISANO
    //         return $this->metro_gaisano_layout_PDF($params, $data);
    //         break;

    //     }
    //     break;
    //   default:
    //     return $this->default_sj_layout($params, $data);
    //     break;
    // }

    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_sj_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      switch ($params['params']['dataparams']['reporttype']) {
        case 'default':
          return $this->default_sj_layout($params, $data);
          break;
        case 'deliveryR':
          return $this->delivery_receipt_layout_PDF($params, $data);
          break;
        case 'outrightR':
          return $this->outright_receipt_layout_PDF($params, $data);
          break;
        case 'consignmentR':
          return $this->consignment_receipt_layout_PDF($params, $data);
          break;
      }
      return $this->default_sj_PDF($params, $data);
    }
  }

  public function default_sj_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->report_default_header($params, $data);

    $totalext = 0;

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        // <--- Header
        $str .= $this->report_default_header($params, $data);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      } //end if
    } //end for

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R P T I O N', '500px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    return $str;
  }

  public function reportsalesinvoice($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Arial";
    $fontsize = "12";
    $border = "1px solid ";
    $border1 = "1px solid ; background-color: lightgray";

    $str .= $this->reporter->beginreport();
    $str .= $this->report_SI_header($params, $data);

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $unitprice = $data[$i]['amt'] - $data[$i]['disc'];
      $linetotal = $data[$i]['qty'] * $unitprice;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, $border, 'TLBR', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col('', '80', null, false, $border, 'TLBR', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['brand_desc'], '80', null, false, $border, 'TLBR', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, 'TLBR', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col('', '90', null, false, $border, 'TLBR', 'R', $font, $fontsize, 'TLBR', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, 'TLBR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('PHP ' . number_format($unitprice, $decimal), '100', null, false, $border, 'TLBR', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col('PHP ' . number_format($linetotal, $decimal), '100', null, false, $border, 'TLBR', 'R', $font, $fontsize, '', '', '2px');

      if ($data[0]['vattype'] == 'VATABLE') {
        $vatsales = $vatsales + $linetotal;
      } else {
        $vatsales = 0;
        $totalext = $totalext + $linetotal;
      }


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        // <--- Header
        $str .= $this->report_default_header($params, $data);

        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->printline();
        $page = $page + $count;
      } //end if
    } //end for


    $str .= $this->reporter->endtable();

    if ($data[0]['vattype'] == 'VATABLE') {
      $vat = $vatsales * .12;
      $totalext = $vatsales + $vat;
    } else {
      $vat = 0;
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Vat Sales', '100', null, false, $border1, 'LBR', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('PHP', '10', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($vatsales, $decimal), '90', null, false, $border, 'BR', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border1, 'LBR', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('12% VAT', '100', null, false, $border1, 'LBR', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('PHP', '20', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($vat, $decimal), '100', null, false, $border, 'BR', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'LT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('VAT Exempt', '100', null, false, $border1, 'LBR', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('PHP', '10', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('0.00', '90', null, false, $border, 'BR', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'LT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Zero Rated', '100', null, false, $border1, 'LBR', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('PHP', '10', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('0.00', '90', null, false, $border, 'BR', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'LT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('LESS: WTax', '100', null, false, $border1, 'LBR', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('PHP', '10', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('0.00', '90', null, false, $border, 'BR', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'LT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('Delivery Charge', '100', null, false, $border1, 'LBR', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('PHP', '10', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('0.00', '90', null, false, $border, 'BR', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '600', null, false, $border, '', 'LT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('Amount Due:', '100', null, false, $border1, 'LRB', 'CT', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('PHP', '10', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '90', null, false, $border, 'BR', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_SI_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $query = "select code,name,address,tel,tin from center where code = " . $center . "";
    $result = $this->coreFunctions->opentable($query);

    $str = '';
    $font = "Arial";
    $fontsize = "11";
    $fontsize2 = "12";
    $fontsize3 = "13";
    $border = "1px solid ";
    $border1 = "1px solid ; background-color: lightgray";

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('A C C E S S' . '&nbsp&nbsp&nbsp' . 'F R O N T I E R', '400', null, false, $border, '', 'C', $font, '17', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, '18', 'B', '', '8px');
    $str .= $this->reporter->col('SALES INVOICE - ORIGINAL', '300', null, false, $border, '', 'C', $font, '18', 'B', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($result[0]->name, '450', null, false, $border, '', 'L', $font, $fontsize3, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('&nbsp' . 'Invoice No.', '100', null, false, $border1, 'LTRB', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('&nbsp' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '200', null, false, $border, 'TRB', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($result[0]->address, '450', null, false, $border, '', 'L', $font, $fontsize3, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('&nbsp' . 'Invoice Date', '100', null, false, $border1, 'LTRB', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('&nbsp' . date("F d,Y", strtotime($data[0]['dateid'])), '200', null, false, $border, 'TRB', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($result[0]->tel, '450', null, false, $border, '', 'L', $font, $fontsize3, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('&nbsp' . 'Do No.', '100', null, false, $border1, 'LTRB', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('&nbsp' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '200', null, false, $border, 'TRB', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize3, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('&nbsp' . 'Cust. PO No.', '100', null, false, $border1, 'LTRB', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('&nbsp' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '200', null, false, $border, 'TRB', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('VAT REG TIN: ' . $result[0]->tin, '450', null, false, $border, '', 'L', $font, $fontsize3, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('&nbsp' . 'Payment Terms', '100', null, false, $border1, 'LTRB', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('&nbsp' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '200', null, false, $border, 'TRB', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize3, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('&nbsp' . 'Page No.', '100', null, false, $border1, 'LTRB', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->pagenumber('&nbsp' . 'Page ', '200', null, false, $border, 'TRB', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp' . 'CUSTOMER NAME', '400', null, false, $border1, 'TLR', 'L', $font, '15', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '400', null, false, $border, 'LR', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp' . 'TIN: ', '40', null, false, $border, 'L', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['tin']) ? $data[0]['tin'] : ''), '360', null, false, $border, 'R', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp' . 'Business Name/Style: ', '145', null, false, $border, 'L', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('', '255', null, false, $border, 'R', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp', '145', null, false, $border, 'L', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('', '255', null, false, $border, 'R', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize2, '', '', '' . '<br>');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp' . 'Contact Name', '100', null, false, $border, 'L', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'R', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp' . 'Contact No.', '100', null, false, $border, 'BL', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'BR', 'L', $font, $fontsize2, 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize2, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('No.', '50', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    $str .= $this->reporter->col('Part #', '80', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    $str .= $this->reporter->col('Mfr', '80', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    $str .= $this->reporter->col('Description', '250', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    $str .= $this->reporter->col('Trans. Type', '90', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    $str .= $this->reporter->col('Qty', '50', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    $str .= $this->reporter->col('Unit Price', '100', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    $str .= $this->reporter->col('Line Total', '100', null, false, $border1, 'TLBR', 'C', $font, $fontsize2, 'B', '', '4px');
    return $str;
  }


  public function default_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, '', 9);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
    $this->reportheader->getheader($params);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(500, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_sj_PDF($params, $data)
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
    $this->default_sj_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->default_sj_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    //PDF::AddPage();
    //$b = 62;
    //for ($i = 0; $i < 1000; $i++) {
    //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //  PDF::MultiCell(0, 0, "\n");
    //  if($i==$b){
    //    PDF::AddPage();
    //    $b = $b + 62;
    //  }
    //}

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  //handyman

  public function handymanHeader_layout_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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
    PDF::SetMargins(40, 40); //720

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n");
    // PDF::SetFont($font, '', 8);
    // PDF::MultiCell(720, 0, '', '');

    PDF::SetXY(40, 118.75);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, '', true);
    PDF::MultiCell(368, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // $y=PDF::getY();
    PDF::SetXY(40, 133);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, '', true);
    PDF::MultiCell(368, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(40, 149);
    // $y=PDF::getY();
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(368, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    //  $y=PDF::getY();
    PDF::SetXY(40, 167);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(368, 20, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    //right

    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('F j,Y');

    PDF::SetXY(40, 95);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(70, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(373, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20,  $datehere, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // $y=PDF::getY();
    // PDF::SetXY(40, 109);
    // PDF::SetFont($fontbold, '', 10);
    // PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(127, 20,(isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // PDF::SetXY(40, 123);
    // PDF::SetFont($fontbold, '', 10);
    // PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(127, 20,(isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // $due = $data[0]['due'];
    // $due1 = new DateTime($due);
    // $duedate = $due1->format('F j,Y');

    // PDF::SetXY(40, 136);
    // PDF::SetFont($fontbold, '', 10);
    // PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(127, 20,$duedate, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // PDF::SetXY(40, 150);
    // PDF::SetFont($fontbold, '', 10);
    // PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(127, 20, (isset($data[0]['agname']) ? $data[0]['agname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // PDF::SetXY(40, 165);
    // PDF::SetFont($fontbold, '', 10);
    // PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(127, 20,  (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

  }

  public function handyman_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $trno = $params['params']['dataid'];

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->handymanHeader_layout_PDF($params, $data);

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', '');

    // // $countarr = 0;
    //pag lahat ng sj ng customer 
    // $totalext= $this->coreFunctions->datareader("
    //           select sum(ext) as value from (
    //           select sum(stock.ext) as ext from lahead as h 
    //                  left join lastock as stock on stock.trno=h.trno
    //                  left join client as cl on cl.client=h.client
    //                   where cl.clientid='".$data[0]['clientid']."' and month(h.dateid)='".$data[0]['month']."' and year(h.dateid)='".$data[0]['year']."'
    //            union all
    //            select sum(stock.ext) as ext from glhead as h
    //                  left join glstock as stock  on stock.trno=h.trno
    //                  left join client as cl on cl.clientid=h.clientid 
    //                  where cl.clientid='".$data[0]['clientid']."' and month(h.dateid)='".$data[0]['month']."' and year(h.dateid)='".$data[0]['year']."' ) as a");


    $totalext = $this->coreFunctions->datareader("
              select sum(ext) as value from (
              select sum(stock.ext) as ext from lahead as h 
                     left join lastock as stock on stock.trno=h.trno
                     left join client as cl on cl.client=h.client
                      where h.trno='" . $trno . "' and month(h.dateid)='" . $data[0]['month'] . "' and year(h.dateid)='" . $data[0]['year'] . "'
               union all
               select sum(stock.ext) as ext from glhead as h
                     left join glstock as stock  on stock.trno=h.trno
                     left join client as cl on cl.clientid=h.clientid 
                     where h.trno='" . $trno . "' and month(h.dateid)='" . $data[0]['month'] . "' and year(h.dateid)='" . $data[0]['year'] . "' ) as a");

    // var_dump($totalext);

    PDF::SetXY(40, 220);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, '', '', 'L', false, 0);
    PDF::MultiCell(225, 0, 'SALES REPORT FOR THE MONTH OF', '', 'C', false, 0);
    PDF::MultiCell(175, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(200, 0, '', '', 'R', false, 1);


    $date = $data[0]['dateid']; //7
    $datetime = new DateTime($date);
    $month = $datetime->format('F');
    $year = $datetime->format('Y');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, '', '', 'L', false, 0);
    PDF::MultiCell(225, 0, $month . ' ' . $year, '', 'C', false, 0);
    PDF::MultiCell(225, 0, '', '', 'R', false, 0);
    PDF::MultiCell(150, 0, '', '', 'R', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, '', '', 'L', false, 0);
    PDF::MultiCell(225, 0, 'NET SALES OF DISCOUNT', '', 'C', false, 0);
    PDF::MultiCell(225, 0, '', '', 'R', false, 0);
    PDF::MultiCell(150, 0, '', '', 'R', false, 1);




    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(400, 0, 'SALES REPORT FOR THE MONTH OF', 'LR', 'R', false, 0);
    // PDF::MultiCell(127, 0, number_format($totalext, $decimalcurr), 'R', 'R',false,1);



    // if (!empty($data)) {
    //   for ($i = 0; $i < count($data); $i++) {

    // $maxrow = 1;

    // $barcode = $data[$i]['barcode'];
    // $itemname = $data[$i]['itemname'];
    // $qty = number_format($data[$i]['qty'], 2);
    // $uom = $data[$i]['uom'];
    // $amt = number_format($data[$i]['amt'], 2);
    // $disc = $data[$i]['disc'];
    // $ext = number_format($data[$i]['ext'], 2);

    // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
    // $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
    // $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
    // $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
    // $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
    // $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
    // $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

    // $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
    // for ($r = 0; $r < $maxrow; $r++) {

    //   PDF::SetFont($font, '', $fontsize);
    //   PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    //   PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    //   PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    //   PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    //   PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    //   PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    //   PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    // }

    //     $totalext += $data[$i]['ext'];

    //     if (PDF::getY() > 900) {
    //       $this->handymanHeader_layout_PDF($params, $data);
    //     }
    //   }
    // }

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', 'B');

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', '');

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    // PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    // PDF::MultiCell(0, 0, "\n");

    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    // PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    // PDF::MultiCell(0, 0, "\n\n\n");


    // PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    // PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    // PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    // PDF::MultiCell(0, 0, "\n");

    // PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    // PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    // PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    //PDF::AddPage();
    //$b = 62;
    //for ($i = 0; $i < 1000; $i++) {
    //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //  PDF::MultiCell(0, 0, "\n");
    //  if($i==$b){
    //    PDF::AddPage();
    //    $b = $b + 62;
    //  }
    //}

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function delivery_receipt_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $date = date('F d, Y', strtotime($data[0]['dateid']));
    $printtime = date('g:i A', strtotime($current_timestamp));

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

    PDF::SetY(30);

    PDF::SetFont($font,  '', 10);
    PDF::SetXY(650, 20);
    PDF::MultiCell(150, 0, 'Page ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages(), 0, 'R');




    // $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    // PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
    // $this->reportheader->getheader($params);
    PDF::SetY(30);
    PDF::SetXY(270, 30);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
    PDF::SetY(30);
    PDF::SetXY(270, 40);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->tel) . "\n", '', 'L');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::Image(public_path('images/ericco/ericco_logo.jpg'), 100, 20, 160, 50);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(560, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'No. of Box', 'LRTB', 'C', false, 0, '600',  '50', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(70, 0, ($data[0]['amount'] != 0) ? $data[0]['amount'] : '', 'LRTB', 'C', false, 1, '', '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(230, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(240, 0, 'DELIVERY RECEIPT', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(230, 0, 'No. ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);



    // PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Deliver to : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', '15');
    PDF::MultiCell(480, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Date         : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, $date, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Address    : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(480, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 20, "Print Time : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, $printtime, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "     ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(430, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, " Start Date: " . date('n/j/y', strtotime($data[0]['sdate1'])), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(20, 20, "     ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, 'End Date: ' . date('n/j/y', strtotime($data[0]['sdate2'])), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, ' ', 'LT', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'T', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(200, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(200, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'RT', 'R');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(50, 0, "Qty", 'LBT', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(50, 0, "Uom", 'LBT', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(100, 0, "Item Code", 'LTB', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(250, 0, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(150, 0, "SKU", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(50, 0, "SRP", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(50, 0, "Total", 'TBR', 'C', false, 1, '',  '', true, 0, false, true, 25, 'M', true);
  }


  public function delivery_receipt_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    // $group = isset($data[0]['group']) ? $data[0]['group'] : '';
    $count = $page = 35;
    $totalqty = 0;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->delivery_receipt_header_PDF($params, $data);

    $countarr = 0;

    $currentPage = 1;
    $tableStartY = PDF::getY();


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;


    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $sku = $data[$i]['sku'];
        $amt = number_format($data[$i]['amt'], 2);
        $ext = number_format($data[$i]['ext'], 2);

        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '40', 0);
        $arr_sku = $this->reporter->fixcolumn([$sku], '45', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_sku, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        $estimatedRowHeight = $maxrow * 27;

        $currentY = PDF::getY();


        if ($currentY + $estimatedRowHeight > $contentLimit) {
          $this->delivery_receipt_footer($params, $data);
          $this->delivery_receipt_header_PDF($params, $data);
          $currentPage++;
          PDF::SetY($tableStartY);
        }

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(100, 20, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(250, 20, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(150, 20, '' . (isset($arr_sku[$r]) ? $arr_sku[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'R', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
        }

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];
      }
    }


    $currentY = PDF::getY();

    $estimatedRemainingHeight = 120;


    if ($currentY + $estimatedRemainingHeight > $contentLimit) {

      $this->delivery_receipt_footer($params, $data);
      $this->delivery_receipt_header_PDF($params, $data);
      $currentPage++;
      PDF::SetY($tableStartY);


      $currentY = PDF::getY();
    }

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(500, 0, '', '', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'R', 'R');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(50, 0, number_format($totalqty), 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, 'Total Qty', 'LR', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '------------------------Nothing follows------------------------', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(490, 0, 'TOTAL AMOUNT: ', 'B', 'l', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), 'B', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(40, 0, 'Group: ', '', 'l', false, 0);
    $groups = [];
    if (isset($data[0]['group1'])) $groups[] = $data[0]['group1'];
    if (isset($data[0]['group2'])) $groups[] = $data[0]['group2'];
    PDF::MultiCell(550, 0, !empty($groups) ? implode(', ', $groups) : '', '', 'L', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    //space
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(70, 0, 'Repackers: ', '', 'l', false, 0);

    $repackers = [];
    if (isset($data[0]['repacker1'])) $repackers[] = $data[0]['repacker1'];
    if (isset($data[0]['repacker2'])) $repackers[] = $data[0]['repacker2'];
    if (isset($data[0]['repacker3'])) $repackers[] = $data[0]['repacker3'];

    $cellWidth = 173;

    for ($i = 0; $i < 3; $i++) {
      $repackerName = isset($repackers[$i]) ? $repackers[$i] : '';


      if (!empty($repackerName) && $i < 2) {
        $repackerName .= ',';
      }

      PDF::MultiCell($cellWidth, 0, $repackerName, '', 'L', false, 0);
    }

    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    //space
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(70, 0, ' ', '', 'l', false, 0);

    $repackers2 = [];
    if (isset($data[0]['repacker4'])) $repackers2[] = $data[0]['repacker4'];
    if (isset($data[0]['repacker5'])) $repackers2[] = $data[0]['repacker5'];
    if (isset($data[0]['repacker6'])) $repackers2[] = $data[0]['repacker6'];

    $cellWidth = 173;


    for ($i = 0; $i < 3; $i++) {
      $repackerName = isset($repackers2[$i]) ? $repackers2[$i] : '';


      if (!empty($repackerName) && $i < 2) {
        $repackerName .= ',';
      }

      PDF::MultiCell($cellWidth, 0, $repackerName, '', 'L', false, 0);
    }
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    $currentY = PDF::getY();
    $estimatedRemarksHeight = 20;
    $estimatedFooterHeight = 10;

    if ($currentY + $estimatedRemarksHeight + $estimatedFooterHeight > $contentLimit) {
      // Won't fit, need new page for remarks and footer
      $this->delivery_receipt_footer($params, $data);
      $this->delivery_receipt_header_PDF($params, $data);
      $currentPage++;
      PDF::SetY($tableStartY);
    }
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(60, 0, 'Remarks: ', '', 'l', false, 0);
    PDF::MultiCell(530, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    $this->delivery_receipt_footer($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function delivery_receipt_footer($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;


    $fillerLimit = 850;

    $currentY = PDF::getY();
    $footerStartPosition = $contentLimit;


    $spaceNeededForFiller = $fillerLimit - $currentY;



    if ($currentY < $fillerLimit) {

      $fillRows = floor($spaceNeededForFiller / 20);

      for ($f = 0; $f < $fillRows; $f++) {
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(300, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }


      $currentY = PDF::getY();
      $remainingSpace = $fillerLimit - $currentY;

      if ($remainingSpace > 0 && $remainingSpace < 20) {
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(300, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, $remainingSpace, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }
    }


    PDF::SetY($fillerLimit);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(300, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);


    PDF::SetY($footerStartPosition);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(660, 0, 'This is not an invoice and not to be paid when presented. Our invoice will follow in due time.', '', 'L', false, 1);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 0, 'Received in good order and condition', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Prepared By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, 'Delivered By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, $params['params']['dataparams']['delivered'], '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Checked By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['checked'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, 'By     : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(75, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '  ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, 'Signature over Printed Name', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Approved By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['approved'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, 'Date : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n");
  }




  public function outright_receipt_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $date = date('F d, Y', strtotime($data[0]['dateid']));
    $printtime = date('g:i A', strtotime($current_timestamp));

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

    PDF::SetY(30);

    PDF::SetFont($font,  '', 10);
    PDF::SetXY(650, 20);
    PDF::MultiCell(150, 0, 'Page ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages(), 0, 'R');




    // $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    // PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
    // $this->reportheader->getheader($params);
    PDF::SetY(30);
    PDF::SetXY(270, 30);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
    PDF::SetY(30);
    PDF::SetXY(270, 40);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->tel) . "\n", '', 'L');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::Image(public_path('images/ericco/ericco_logo.jpg'), 100, 20, 160, 50);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(560, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'No. of Box', 'LRTB', 'C', false, 0, '600',  '50', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(70, 0, ($data[0]['amount'] != 0) ? $data[0]['amount'] : '', 'LRTB', 'C', false, 1, '', '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(230, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(240, 0, 'OUTRIGHT RECEIPT', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(230, 0, 'No. ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);



    // PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Deliver to : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', '15');
    PDF::MultiCell(480, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Date         : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, $date, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Address    : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(480, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 20, "Print Time : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, $printtime, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "     ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(430, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, " Start Date: " . date('n/j/y', strtotime($data[0]['sdate1'])), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(20, 20, "     ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, 'End Date: ' . date('n/j/y', strtotime($data[0]['sdate2'])), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, ' ', 'LT', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'T', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(200, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(200, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'RT', 'R');

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(50, 0, "Qty", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Uom", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(80, 0, "Item Code", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(170, 0, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(150, 0, "SKU", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "SRP", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Gross", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Disc", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Total", 'TRB', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);
  }


  public function outright_receipt_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalqty = 0;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->outright_receipt_header_PDF($params, $data);

    $countarr = 0;

    $currentPage = 1;
    $tableStartY = PDF::getY();


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;


    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $barcode = $data[$i]['barcode'];
        $amt = number_format($data[$i]['amt'], 2);
        $sku = $data[$i]['sku'];
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $gross = number_format((float)$data[$i]['amt'] * (float)$data[$i]['qty'], 2);

        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        $arr_sku = $this->reporter->fixcolumn([$sku], '30', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_gross = $this->reporter->fixcolumn([$gross], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_barcode, $arr_itemname, $arr_sku, $arr_disc, $arr_amt, $arr_ext, $arr_gross]);

        $estimatedRowHeight = $maxrow * 27;

        $currentY = PDF::getY();


        if ($currentY + $estimatedRowHeight > $contentLimit) {
          $this->outright_receipt_footer($params, $data);
          $this->outright_receipt_header_PDF($params, $data);
          $currentPage++;
          PDF::SetY($tableStartY);
        }

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(80, 20, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(170, 20, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(150, 20, '' . (isset($arr_sku[$r]) ? $arr_sku[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_gross[$r]) ? $arr_gross[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'R', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
        }

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];
      }
    }


    $currentY = PDF::getY();

    $estimatedRemainingHeight = 120;


    if ($currentY + $estimatedRemainingHeight > $contentLimit) {

      $this->outright_receipt_footer($params, $data);
      $this->outright_receipt_header_PDF($params, $data);
      $currentPage++;
      PDF::SetY($tableStartY);


      $currentY = PDF::getY();
    }

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(500, 0, '', '', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'R', 'R');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(50, 0, number_format($totalqty), 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, 'Total Qty', 'LR', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '------------------------Nothing follows------------------------', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(490, 0, 'TOTAL AMOUNT: ', 'B', 'l', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), 'B', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(40, 0, 'Group: ', '', 'l', false, 0);
    $groups = [];
    if (isset($data[0]['group1'])) $groups[] = $data[0]['group1'];
    if (isset($data[0]['group2'])) $groups[] = $data[0]['group2'];
    PDF::MultiCell(550, 0, !empty($groups) ? implode(', ', $groups) : '', '', 'L', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    //space
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(70, 0, 'Repackers: ', '', 'l', false, 0);

    $repackers = [];
    if (isset($data[0]['repacker1'])) $repackers[] = $data[0]['repacker1'];
    if (isset($data[0]['repacker2'])) $repackers[] = $data[0]['repacker2'];
    if (isset($data[0]['repacker3'])) $repackers[] = $data[0]['repacker3'];

    $cellWidth = 173;

    for ($i = 0; $i < 3; $i++) {
      $repackerName = isset($repackers[$i]) ? $repackers[$i] : '';


      if (!empty($repackerName) && $i < 2) {
        $repackerName .= ',';
      }

      PDF::MultiCell($cellWidth, 0, $repackerName, '', 'L', false, 0);
    }

    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    //space
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(70, 0, ' ', '', 'l', false, 0);

    $repackers2 = [];
    if (isset($data[0]['repacker4'])) $repackers2[] = $data[0]['repacker4'];
    if (isset($data[0]['repacker5'])) $repackers2[] = $data[0]['repacker5'];
    if (isset($data[0]['repacker6'])) $repackers2[] = $data[0]['repacker6'];

    $cellWidth = 173;


    for ($i = 0; $i < 3; $i++) {
      $repackerName = isset($repackers2[$i]) ? $repackers2[$i] : '';


      if (!empty($repackerName) && $i < 2) {
        $repackerName .= ',';
      }

      PDF::MultiCell($cellWidth, 0, $repackerName, '', 'L', false, 0);
    }
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    $currentY = PDF::getY();
    $estimatedRemarksHeight = 20;
    $estimatedFooterHeight = 10;

    if ($currentY + $estimatedRemarksHeight + $estimatedFooterHeight > $contentLimit) {
      // Won't fit, need new page for remarks and footer
      $this->delivery_receipt_footer($params, $data);
      $this->delivery_receipt_header_PDF($params, $data);
      $currentPage++;
      PDF::SetY($tableStartY);
    }
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(60, 0, 'Remarks: ', '', 'l', false, 0);
    PDF::MultiCell(530, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    $this->outright_receipt_footer($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function outright_receipt_footer($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;


    $fillerLimit = 850;

    $currentY = PDF::getY();
    $footerStartPosition = $contentLimit;


    $spaceNeededForFiller = $fillerLimit - $currentY;


    // Always fill to exactly 850
    if ($currentY < $fillerLimit) {

      $fillRows = floor($spaceNeededForFiller / 20);

      for ($f = 0; $f < $fillRows; $f++) {
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(225, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 20, ' ', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }


      $currentY = PDF::getY();
      $remainingSpace = $fillerLimit - $currentY;

      if ($remainingSpace > 0 && $remainingSpace < 20) {
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(225, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, $remainingSpace, '', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }
    }

    // Make sure we're at exactly 850
    PDF::SetY($fillerLimit);

    // Add bottom border line to close the table
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(225, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 0, '', 'T', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetY($footerStartPosition);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(660, 0, 'This is not an invoice and not to be paid when presented. Our invoice will follow in due time.', '', 'L', false, 1);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 0, 'Received in good order and condition', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Prepared By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, 'Delivered By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, $params['params']['dataparams']['delivered'], '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Checked By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['checked'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' By     : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(75, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '  ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, 'Signature over Printed Name', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Approved By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['approved'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' Date : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n");
  }



  public function consignment_receipt_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $date = date('F d, Y', strtotime($data[0]['dateid']));
    $printtime = date('g:i A', strtotime($current_timestamp));

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

    PDF::SetY(30);

    PDF::SetFont($font,  '', 10);
    PDF::SetXY(650, 20);
    PDF::MultiCell(150, 0, 'Page ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages(), 0, 'R');




    // $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    // PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
    // $this->reportheader->getheader($params);
    PDF::SetY(30);
    PDF::SetXY(270, 30);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'L');
    PDF::SetY(30);
    PDF::SetXY(270, 40);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->tel) . "\n", '', 'L');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::Image(public_path('images/ericco/ericco_logo.jpg'), 100, 20, 160, 50);

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(560, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, 'No. of Box', 'LRTB', 'C', false, 0, '600',  '50', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(70, 0, ($data[0]['amount'] != 0) ? $data[0]['amount'] : '', 'LRTB', 'C', false, 1, '', '', true, 0, false, true, 25, 'M', true);
    PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(230, 0, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(240, 0, 'CONSIGNMENT RECEIPT', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(230, 0, 'No. ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);



    // PDF::MultiCell(0, 0, "\n");


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Deliver to : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', '15');
    PDF::MultiCell(480, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Date         : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, $date, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "Address    : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(480, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 20, "Print Time : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, $printtime, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(60, 20, "     ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(430, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, " Start Date: " . date('n/j/y', strtotime($data[0]['sdate1'])), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(20, 20, "     ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, 'End Date: ' . date('n/j/y', strtotime($data[0]['sdate2'])), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, ' ', 'LT', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'T', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(200, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(200, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'l', false, 0);
    PDF::MultiCell(50, 0, '', 'RT', 'R');


    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(50, 0, "Qty", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Uom", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(80, 0, "Item Code", 'TLB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(170, 0, "Item Description", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(150, 0, "SKU", 'TB', 'C', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "SRP", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Gross", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Disc", 'TB', 'R', false, 0, '',  '', true, 0, false, true, 20, 'M', true);
    PDF::MultiCell(50, 0, "Total", 'TRB', 'C', false, 1, '',  '', true, 0, false, true, 20, 'M', true);
  }


  public function consignment_receipt_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalqty = 0;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->consignment_receipt_header_PDF($params, $data);

    $countarr = 0;

    $currentPage = 1;
    $tableStartY = PDF::getY();


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;


    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $barcode = $data[$i]['barcode'];
        $amt = number_format($data[$i]['amt'], 2);
        $sku = $data[$i]['sku'];
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $gross = number_format((float)$data[$i]['amt'] * (float)$data[$i]['qty'], 2);

        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        $arr_sku = $this->reporter->fixcolumn([$sku], '30', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_gross = $this->reporter->fixcolumn([$gross], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_qty, $arr_uom, $arr_barcode, $arr_itemname, $arr_sku, $arr_disc, $arr_amt, $arr_ext, $arr_gross]);

        $estimatedRowHeight = $maxrow * 27;

        $currentY = PDF::getY();


        if ($currentY + $estimatedRowHeight > $contentLimit) {
          $this->consignment_receipt_footer($params, $data);
          $this->consignment_receipt_header_PDF($params, $data);
          $currentPage++;
          PDF::SetY($tableStartY);
        }

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(80, 20, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(170, 20, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(150, 20, '' . (isset($arr_sku[$r]) ? $arr_sku[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_gross[$r]) ? $arr_gross[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', true);
          PDF::MultiCell(50, 20, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'R', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);
        }

        $totalqty += $data[$i]['qty'];
        $totalext += $data[$i]['ext'];
      }
    }


    $currentY = PDF::getY();

    $estimatedRemainingHeight = 120;


    if ($currentY + $estimatedRemainingHeight > $contentLimit) {

      $this->consignment_receipt_footer($params, $data);
      $this->consignment_receipt_header_PDF($params, $data);
      $currentPage++;
      PDF::SetY($tableStartY);


      $currentY = PDF::getY();
    }

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(500, 0, '', '', 'l', false, 0);
    PDF::MultiCell(100, 0, '', 'R', 'R');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(50, 0, number_format($totalqty), 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, 'Total Qty', 'LR', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '------------------------Nothing follows------------------------', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', 'B', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(490, 0, 'TOTAL AMOUNT: ', 'B', 'l', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), 'B', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(40, 0, 'Group: ', '', 'l', false, 0);
    $groups = [];
    if (isset($data[0]['group1'])) $groups[] = $data[0]['group1'];
    if (isset($data[0]['group2'])) $groups[] = $data[0]['group2'];
    PDF::MultiCell(550, 0, !empty($groups) ? implode(', ', $groups) : '', '', 'L', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    //space
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(70, 0, 'Repackers: ', '', 'l', false, 0);

    $repackers = [];
    if (isset($data[0]['repacker1'])) $repackers[] = $data[0]['repacker1'];
    if (isset($data[0]['repacker2'])) $repackers[] = $data[0]['repacker2'];
    if (isset($data[0]['repacker3'])) $repackers[] = $data[0]['repacker3'];

    $cellWidth = 173;

    for ($i = 0; $i < 3; $i++) {
      $repackerName = isset($repackers[$i]) ? $repackers[$i] : '';


      if (!empty($repackerName) && $i < 2) {
        $repackerName .= ',';
      }

      PDF::MultiCell($cellWidth, 0, $repackerName, '', 'L', false, 0);
    }

    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    //space
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(70, 0, ' ', '', 'l', false, 0);

    $repackers2 = [];
    if (isset($data[0]['repacker4'])) $repackers2[] = $data[0]['repacker4'];
    if (isset($data[0]['repacker5'])) $repackers2[] = $data[0]['repacker5'];
    if (isset($data[0]['repacker6'])) $repackers2[] = $data[0]['repacker6'];

    $cellWidth = 173;


    for ($i = 0; $i < 3; $i++) {
      $repackerName = isset($repackers2[$i]) ? $repackers2[$i] : '';


      if (!empty($repackerName) && $i < 2) {
        $repackerName .= ',';
      }

      PDF::MultiCell($cellWidth, 0, $repackerName, '', 'L', false, 0);
    }
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    $currentY = PDF::getY();
    $estimatedRemarksHeight = 20;
    $estimatedFooterHeight = 10;

    if ($currentY + $estimatedRemarksHeight + $estimatedFooterHeight > $contentLimit) {
      // Won't fit, need new page for remarks and footer
      $this->delivery_receipt_footer($params, $data);
      $this->delivery_receipt_header_PDF($params, $data);
      $currentPage++;
      PDF::SetY($tableStartY);
    }
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(50, 0, ' ', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(60, 0, 'Remarks: ', '', 'l', false, 0);
    PDF::MultiCell(530, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'LR', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'LR', 'l', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'l', false, 0);
    PDF::MultiCell(590, 0, '', '', 'C', false, 0);
    PDF::MultiCell(5, 0, ' ', 'R', 'l', false, 1);


    $this->consignment_receipt_footer($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function consignment_receipt_footer($params, $data)
  {
    $font = "";
    $fontbold = "";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }


    $footerHeight = 50;
    $pageBottomMargin = 900;
    $contentLimit = $pageBottomMargin - $footerHeight;


    $fillerLimit = 850;

    $currentY = PDF::getY();
    $footerStartPosition = $contentLimit;


    $spaceNeededForFiller = $fillerLimit - $currentY;


    // Always fill to exactly 850
    if ($currentY < $fillerLimit) {

      $fillRows = floor($spaceNeededForFiller / 20);

      for ($f = 0; $f < $fillRows; $f++) {
        PDF::SetFont($font, '', $fontsize);

        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(225, 20, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, 20, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, 20, ' ', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }

      // Add any remaining space that's less than a full row
      $currentY = PDF::getY();
      $remainingSpace = $fillerLimit - $currentY;

      if ($remainingSpace > 0 && $remainingSpace < 20) {
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', 'LR', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(100, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(225, $remainingSpace, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(50, $remainingSpace, ' ', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
        PDF::MultiCell(75, $remainingSpace, '', 'R', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
      }
    }

    // Make sure we're at exactly 850
    PDF::SetY($fillerLimit);

    // Add bottom border line to close the table
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(225, 0, '', 'T', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(75, 0, '', 'T', 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetY($footerStartPosition);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(40, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(660, 0, 'This is not an invoice and not to be paid when presented. Our invoice will follow in due time.', '', 'L', false, 1);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(250, 0, 'Received in good order and condition', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Prepared By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, 'Delivered By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, $params['params']['dataparams']['delivered'], '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 5, '', 0, 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Checked By : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['checked'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, ' ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, 'By     : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(75, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, ' ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '  ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, 'Signature over Printed Name', 'T', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(75, 15, 'Approved By: ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 15, $params['params']['dataparams']['approved'], 'B', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(30, 15, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(80, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(115, 15, '', 'B', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(50, 0, 'Date : ', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(200, 15, '', 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n");
  }
}
