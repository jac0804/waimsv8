<?php

namespace App\Http\Classes\modules\modulereport\sbc;

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
    $fields = ['radioprint', 'radioreporttype','prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

    data_set($col1, 'radioreporttype.options',
      [
        ['label' => 'SBC DR', 'value' => '0', 'color' => 'blue'],
        ['label' => 'BISMAC DR', 'value' => '1', 'color' => 'blue']
       
      ]
    );
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
      return $this->coreFunctions->opentable(
        "select
        'PDFM' as print,
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
            if(stock.rem != '',    concat(item.itemname, ' - ', stock.rem), item.itemname) as itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, head.agent,
            item.sizeid, ag.clientname as agname, item.brand,
            wh.client as whcode, wh.clientname as whname,client.tel,head.trno
            
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
            if(stock.rem != '',    concat(item.itemname, ' - ', stock.rem), item.itemname) as itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
            item.sizeid, ag.clientname as agname, item.brand,
            wh.client as whcode, wh.clientname as whname,client.tel,head.trno
            
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

  public function report_sj_query($trno)
  {

    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
          right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
          head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
          if(stock.rem != '',    concat(item.itemname, ' - ', stock.rem), item.itemname) as itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
          item.sizeid, ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc,client.tel,head.trno
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
          if(stock.rem != '',    concat(item.itemname, ' - ', stock.rem), item.itemname) as itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
          item.sizeid, ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc,client.tel,head.trno
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
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_sj_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
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


  public function default_sj_header_PDF($params, $data,$next)
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
    // if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //   $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //   $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    // }

     if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(50, 50);

    PDF::SetFont($font, '', 9);

		PDF::Image(public_path() . '/images/sbc/sbclogo1.jpg', '-30', '-55', 850, 200); //X,Y,width,height

        PDF::SetFont($font, '', 110);
        PDF::MultiCell(700, 0, '', '');
     
     $reporttype = $params['params']['dataparams']['reporttype'];

     $title='';
     if($reporttype == '0'){
      $title='B I L L I N G  S T A T E M E N T';

     }else{
        $title='D E L I V E R Y  R E C E I P T';
     }

    PDF::SetFont($fontbold, '', 17);
    PDF::MultiCell(200, 0, '', '', 'C', false, 0);
    PDF::MultiCell(25, 0, '', '', 'C', false, 0);
    PDF::MultiCell(250, 0, $title, 'B', 'C', false, 0);
    PDF::MultiCell(25, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, '', '', 'C', false, 1);

    // PDF::MultiCell(0, 0, "\n\n");
    $y=PDF::getY();
    // $y = (float) 640;
    PDF::SetXY(50, 230);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(5, 20, "", 'TL', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(70, 20, "Delivered To: ", 'T', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'TR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(10, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //SPACE
    PDF::MultiCell(5, 20, "", 'LT', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 20, "", 'T', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, '', 'RT', '', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      $add = isset($data[0]['address']) ? $data[0]['address'] : '';
       
        $maxChars = 85;
        $adds = strlen($add);
        $firstLine = '';
        $remaininglines = [];
        $addsz = '';

        if ($adds > $maxChars) {
            $firstLine = substr($add, 0, $maxChars);
            $remaining = substr($add, $maxChars);
            // Split remaining address into multiple lines without cutting words
            while (strlen($remaining) > $maxChars) {
                // Find the last space within the maxChars limit
                $spacePos = strrpos(substr($remaining, 0, $maxChars), ' ');

                // If there's no space, just cut at maxChars
                if ($spacePos === false) {
                    $nextLine = substr($remaining, 0, $maxChars);
                    $remaining = substr($remaining, $maxChars);
                } else {
                    $nextLine = substr($remaining, 0, $spacePos);
                    $remaining = substr($remaining, $spacePos + 1);
                }

                $remainingLines[] = $nextLine;
            }
            // Add the final remaining part if it's less than or equal to $maxChars
            if (strlen($remaining) > 0) {
                $remainingLines[] = $remaining;
            }
        } else {
            $addsz = $add;
        }


        if ($adds > $maxChars) {
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(50, 20, "Address: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(470, 20, $firstLine, 'R', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
             
              PDF::MultiCell(10, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //SPACE
             
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 20, "Document: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

            // Loop through remaining lines and print them
            foreach ($remainingLines as $line) {

              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(50, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(470, 20, $line, 'R', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

              PDF::MultiCell(10, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //SPACE

              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 20, "", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 20, '', 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
             
              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(5, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(50, 20, "Tel No: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(470, 20, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              
              PDF::MultiCell(10, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //SPACE

              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(5, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 20, "Date: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'RB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
            }
        } else {
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, "Address: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(470, 20, $addsz, 'R', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

            PDF::MultiCell(10, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //SPACE

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(5, 20, "", 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(60, 20, "Document: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'R', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(5, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(50, 20, "Tel No: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(470, 20, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
           
              PDF::MultiCell(10, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //SPACE

            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(5, 20, "", 'LB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(60, 20, "Date: ", 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'RB', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
        }


    if($next==1){
       PDF::MultiCell(0, 0, "\n");
       PDF::SetFont($font, '', 2);//10.3
       PDF::MultiCell(700, 0, '', '');

    }else{
      PDF::MultiCell(0, 0, "\n\n");
    }
  


    PDF::SetCellPaddings(0, 2, 0, 2);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(580, 0, "PARTICULARS", 'TLB', 'C', false, 0);
    PDF::MultiCell(20, 0, "", 'TLB', 'C', false, 0);
    PDF::MultiCell(100, 0, "AMOUNT", 'TRB', 'C', false);

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', 'B');
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
    // if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //   $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //   $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    // }
    if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    }

    $this->default_sj_header_PDF($params, $data, $next=0);
     PDF::SetCellPaddings(4, 2, 0, 2);
    
    $rowCount = 0;
    $pageLimit = 25;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $itemname = $data[$i]['itemname'];
        $ext = number_format($data[$i]['ext'], 2);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '75', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(580, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFont('dejavusans', '', 12);
          PDF::MultiCell(20, 15, '₱', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), 'R', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
           $rowCount++;
        }
        
        // $totalext += $data[$i]['ext'];
          $totalext= $this->coreFunctions->datareader("
              select sum(ext) as value from (
              select sum(stock.ext) as ext from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$data[$i]['trno']."') as a");
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->other_footer($params, $data,$rowCount,$totalext);
            $next=1;
            PDF::SetXY(50, 174.75);
            $this->default_sj_header_PDF($params, $data,$next);
            PDF::SetCellPaddings(4, 2, 0, 2);
            $rowCount = 0; // reset counter
          }
      }
    }


    // Add 3 blank rows before notes
      for ($i = 0; $i < 3; $i++) {
          $this->addrow('');
          $rowCount++;
      }

      $notesText = $data[0]['rem'];

      // Split by existing line breaks
      $lines = preg_split("/\r\n|\n|\r/", $notesText);

      $wrappedLines = [];
      foreach ($lines as $line) {
          $wrapped = wordwrap($line, 75, "\n", true);
          $wrappedLines = array_merge($wrappedLines, explode("\n", $wrapped));
      }


    foreach ($wrappedLines as $line) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(580, 15, $line, 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont('dejavusans', '', 11);
        PDF::MultiCell(20, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(100, 15, ''.' ', 'R', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        $rowCount++;
    }

    $emptyRows = $pageLimit - $rowCount;
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow('');
        }


    $y=PDF::getY();
    PDF::SetCellPaddings(4, 2, 0, 2);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(580, 15, 'TOTAL AMOUNT: ', 'TLB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(20, 15, '₱', 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, number_format($totalext, $decimalcurr).' ', 'TRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);


    PDF::MultiCell(0, 0, "\n\n\n");

    $x = PDF::GetX();
    $y=(float) 859.75;
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetCellPaddings(0, 4, 0, 2);
    PDF::MultiCell(50, 0, '', 'LT', 'L', false, 0,$x,$y);
    PDF::MultiCell(150, 0, 'Prepared By: ', 'T', 'L', false, 0,$x + 50,$y);
    PDF::MultiCell(50, 0, '', 'T', 'L', false, 0,$x + 200,$y);
    PDF::MultiCell(150, 0, 'Received By: ', 'T', 'L', false, 0,$x + 250,$y);
    PDF::MultiCell(50, 0, '', 'T', 'L', false, 0,$x + 400,$y);
    PDF::MultiCell(200, 0, 'Date Received: ', 'T', 'L',false,0,$x + 450,$y);
    PDF::MultiCell(50, 0, '', 'RT', 'L',false,1,$x + 650,$y);


    PDF::SetCellPaddings(0, 4, 0, 2);
    PDF::MultiCell(50, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, ' ', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', '', 'L',false,0);
    PDF::MultiCell(50, 0, '', 'R', 'L');


    $reporttype = $params['params']['dataparams']['reporttype'];
     if($reporttype == '0'){
        $x = PDF::GetX();
        $y=(float) 840;
        PDF::Image(public_path() . '/images/sbc/signature26.png', $x+70, $y, 110, 85);  //x,y,widht,height

     }

    $prepared='Mr.Leandro Habunal';
    PDF::MultiCell(50, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(150, 0, $prepared, 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L',false,0);
    PDF::MultiCell(50, 0, '', 'R', 'L');

    PDF::MultiCell(50, 0, '', 'LB', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Account Representative', 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L',false,0);
    PDF::MultiCell(50, 0, '', 'BR', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


    public function other_footer($params, $data,$rowCount,$totalext)
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
    // if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
    //   $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
    //   $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
    // }
     
     $pageLimit = 25;
     $emptyRows = $pageLimit - $rowCount;
  
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow('');
        }

    PDF::SetCellPaddings(4, 2, 0, 2);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(580, 15, 'TOTAL AMOUNT: ', 'TLB', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont('dejavusans', '', 11);
    PDF::MultiCell(20, 15, '₱', 'LTB', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, number_format($totalext, $decimalcurr), 'TRB', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

     PDF::SetCellPaddings(0, 4, 0, 2);
    PDF::MultiCell(50, 0, '', 'LT', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Prepared By: ', 'T', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Received By: ', 'T', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Date Received: ', 'T', 'L',false,0);
    PDF::MultiCell(50, 0, '', 'RT', 'L');

   
    $prepared='Mr.Leandro Habunal';


    PDF::MultiCell(50, 0, '', 'L', 'L', false, 0);
    PDF::MultiCell(150, 0, $prepared, 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, $params['params']['dataparams']['received'], 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L',false,0);
    PDF::MultiCell(50, 0, '', 'R', 'L');

    PDF::MultiCell(50, 0, '', 'LB', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Account Representative', 'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(200, 0, '', 'B', 'L',false,0);
    PDF::MultiCell(50, 0, '', 'BR', 'L');

  }


     private function addrow($border)
    {
           $font = "";
          $fontbold = "";
          $border = "1px solid ";
          $fontsize = "11";
          //  if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
          //   $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
          //   $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
          // }
            if (Storage::disk('sbcpath')->exists('/fonts/times.ttf')) {
              $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.ttf');
              $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.ttf');
            }
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(580, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont('dejavusans', '', 12);
          PDF::MultiCell(20, 15, '', 'L', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', true);
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, '', 'R', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }

  
}
