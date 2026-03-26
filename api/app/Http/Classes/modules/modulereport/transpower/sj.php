<?php

namespace App\Http\Classes\modules\modulereport\transpower;

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
    $fields = ['radioprint', 'radioreporttype', 'radioisassettag', 'radiopaidstatus', 'prepared', 'approved', 'received', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

    data_set($col1, 'radioreporttype.options', [
      // ['label' => 'TRANS - CONSIGN', 'value' => '0', 'color' => 'red'],
      // ['label' => 'TRANS - S.I', 'value' => '1', 'color' => 'red'],
      // ['label' => 'SURE - S.I', 'value' => '2', 'color' => 'red'],
      // ['label' => 'MAIN OFFICE SJ', 'value' => '3', 'color' => 'red'],
      // ['label' => 'AB ELECT - S.I', 'value' => '4', 'color' => 'red'],
      // ['label' => 'BC MDSE - S.I', 'value' => '5', 'color' => 'red'],
      // ['label' => 'POWERCREST - S.I', 'value' => '6', 'color' => 'red'],
     
      // ['label' => 'POWERCREST 2025', 'value' => '8', 'color' => 'red']
       ['label' => 'SI OLD / CYA', 'value' => '9', 'color' => 'red'],
      //  ['label' => 'CASH SALES INVOICE / CYSI', 'value' => '10', 'color' => 'red'],
       ['label' => 'CONSIGN / QF', 'value' => '11', 'color' => 'red'], //NEW
       ['label' => 'QUOTATION', 'value' => '7', 'color' => 'red'],
       ['label' => 'SMARTFLEX SI', 'value' => '12', 'color' => 'red'],
    ]);
    data_set($col1, 'radioreporttype.label', 'Report Type');


    data_set($col1, 'radioisassettag.options', [
      ['label' => 'Original Amount', 'value' => '0', 'color' => 'red'],
      ['label' => 'Agent Amount', 'value' => '1', 'color' => 'red']
    ]);
    data_set($col1, 'radioisassettag.label', 'Print Price Option');


    data_set($col1, 'radiopaidstatus.options', [
      ['label' => 'Single Price Show', 'value' => '0', 'color' => 'red'],
      ['label' => 'Orig. Amount and Agent Amount Show', 'value' => '1', 'color' => 'red']
    ]);
    data_set($col1, 'radiopaidstatus.label', 'Price Layout Option');
    data_set($col1, 'prepared.label', 'Released By');


    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        '12' as reporttype,
        '0' as isassettag,
        '0' as paidstatus,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "select head.trno,stock.rem as srem,head.rem,left(head.dateid,10) as dateid, head.docno, client.client, head.clientname,
            head.address, head.terms, item.barcode, client.tin, head.yourref, head.ourref,
             if(stock.rem != '',    concat(item.itemname, ' -', stock.rem), item.itemname) as itemname,
            stock.isqty as qty,stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent, ag.clientname as agname,
            wh.client as whcode, wh.clientname as whname,if(client.tel !='',client.tel,client.tel2) as contact,head.createby, stock.agentamt as agtamt,
            ifnull(client.bstyle,'') as bstyle,head.vattype,ifnull(client.registername,'') as registername,head.ewtrate,head.cmtrno,cmref.docno as cmdocno
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.client=head.agent
            left join client as wh on wh.client=head.wh
            left join cntnum as cmref on cmref.trno=head.cmtrno
            where head.doc='sj' and head.trno='$trno'
            UNION ALL
            select head.trno,stock.rem as srem,head.rem,left(head.dateid,10) as dateid, head.docno, client.client, head.clientname,
            head.address, head.terms, item.barcode, client.tin, head.yourref, head.ourref,
             if(stock.rem != '',    concat(item.itemname, '-', stock.rem), item.itemname) as itemname,
            stock.isqty as qty,stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent, ag.clientname as agname,
            wh.client as whcode, wh.clientname as whname,if(client.tel !='',client.tel,client.tel2) as contact,head.createby,  stock.agentamt as agtamt,
            ifnull(client.bstyle,'') as bstyle,head.vattype,ifnull(client.registername,'') as registername,head.ewtrate,head.cmtrno,cmref.docno as cmdocno
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join client on client.clientid=head.clientid
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.clientid=head.agentid
            left join client as wh on wh.clientid=head.whid
            left join cntnum as cmref on cmref.trno=head.cmtrno
            where head.doc='sj' and head.trno='$trno' 
          
            order by docno";
    // var_dump($query);
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
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc,client.contact
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
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc,client.contact
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


   public function return_default_query($params,$data)
  {
 
    $cmdocno= $data[0]['cmdocno'];
    $cmtrno= $data[0]['cmtrno'];
    $trno = $params['params']['dataid'];

    if($cmdocno != ''){
        $query = " select sum(ext) as ext
            from (
            select sum(stock.ext) as ext
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            where head.doc='cm' and head.trno='$cmtrno'
            union all
            select sum(stock.ext) as ext
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            where head.doc='cm' and head.trno='$cmtrno' ) as x";
    }else{
       $query = " select sum(ext) as ext
            from (
            select sum(stock.ext) as ext
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            where head.doc='cm' and stock.refx='$trno'
            union all
            select sum(stock.ext) as ext
            from lahead as head
            left join lastock as stock on stock.trno=head.trno
            where head.doc='cm' and stock.refx='$trno' ) as x";
    }

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {

    $print = $params['params']['dataparams']['print'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    switch ($print) {
      case 'PDFM':
        switch ($reporttype) {
          case '0': //TRANS - CONSIGN
            return $this->trans_consign_layout($params, $data);
            break;
          case '1': //TRANS - S.I
            return $this->trans_si_layout($params, $data);
            break;
          case '2': //SURE - S.I
            return $this->sure_si_layout($params, $data);
            break;
          case '3': //MAIN OFFICE SJ
            return $this->quotation_form_layout($params, $data);
            break;
          case '4': //AB ELECT - S.I
            return $this->abelect_si_layout($params, $data);
            break;
          case '5': //BC MDSE - S.I
            return $this->bcmdse_layout($params, $data);
            break;
          case '6': //POWERCREST - S.I
            return $this->powercrest_si_layout($params, $data);
            break;
          case '7': //QUOTATION FORM
            return $this->quotation_form_layout($params, $data);
            break;
          case '8': //POWERCREST 2025
            return $this->powercrest_last_layout($params, $data);
            break;
          case '9': //CASH SALES INVOICE A
            return $this->cash_sales_invoice_a($params, $data);
            break;
          case '11': // quotation form new layout - free form
            return $this->qf_layout($params, $data);
            break;  
          case '10': //CASH SALES INVOICE B
            return $this->cash_sales_invoice_b($params, $data);
            break;
          case '12': //sales invoice
            return $this->sales_invoice($params, $data); //new SMARTFLEX
            break;
        }
        break;
      default:
        return $this->default_sj_layout($params, $data);
        break;
    }
    // if ($params['params']['dataparams']['print'] == "default") {
    //   return $this->default_sj_layout($params, $data);
    // } else if ($params['params']['dataparams']['print'] == "PDFM") {
    //   return $this->default_sj_PDF($params, $data);
    // }
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
    $str .= $this->reporter->col('Released By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
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


    PDF::MultiCell(253, 0, 'Released By: ', '', 'L', false, 0);
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


  public function trans_consign_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->trans_consign_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->trans_consign_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->trans_consign_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->trans_consign_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }

  public function trans_consign_origamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 20); //740
    //start 
    PDF::SetFont($font, '', 9);
    PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 300, 50);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");


    PDF::SetFont($font, '', 6);
    PDF::MultiCell(740, 0, '', '');

    $username = $params['params']['user'];

    PDF::SetFont($fontcalibri, '', 12);
    // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(70, 20, "Created By :", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 12);
    PDF::MultiCell(58, 20, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(60, 20, "Printed By :", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 12);
    PDF::MultiCell(349, 20, $username, '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(200, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);

    //format Y-m-d
    $cdate = $date->format('m-d-Y'); //initial date 10-30-2025

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 12);
    // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Customer :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(437, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(147, 20, $cdate, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 13);

    PDF::MultiCell(98, 20, "Address :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(437, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(147, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // PDF::MultiCell(0, 0, "\n");
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    //
    PDF::MultiCell(98, 20, "Contact # :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(639, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function trans_consign_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];




    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->trans_consign_origamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(740, 0, '', '');
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          if ($discText != '') {
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(20, 0, '', '', 'R', false, 0);
            PDF::MultiCell(376, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(53, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(8, 0, '', '', 'R', false, 0);
            PDF::MultiCell(110, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(110, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(10, 0, '', '', 'R', false, 1);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(620, 0, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 1);
          } else {
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(20, 0, '', '', 'R', false, 0);
            PDF::MultiCell(376, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(53, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(8, 0, '', '', 'R', false, 0);
            PDF::MultiCell(110, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(110, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(10, 0, '', '', 'R', false, 1);
          }

          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->trans_consign_origamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(740, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    // $charges = number_format(100, 2);
    $chargeArr = $this->reporter->fixcolumn([$charges], '10', 0);
    $charge = is_array($chargeArr) ? $chargeArr[0] : $chargeArr;


    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(355, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::MultiCell(58, 0, '', '', 'L', false, 0);
    PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    PDF::MultiCell(110, 0, $charge, '', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(372, 0, '', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    PDF::MultiCell(105, 0, '', 'B', 'R', false, 0);
    PDF::MultiCell(15, 0, '', '', 'R', false, 1);

    $tl = $totalext + $charges;
    $totalext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalext], '10', 0);
    $ext = is_array($tlext) ? $tlext[0] : $tlext;

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(377, 0, 'TOTAL:', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    PDF::MultiCell(105, 0, $ext, '', 'R', false, 0);
    PDF::MultiCell(15, 0, '', '', 'R', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(372, 0, '', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    PDF::MultiCell(105, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(15, 0, '', '', 'R', false, 1);


    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(372, 0, '', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    PDF::MultiCell(105, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(15, 0, '', '', 'R', false, 1);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function trans_consign_origamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 20); //740



    //start 
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");


    PDF::SetFont($font, '', 6);
    PDF::MultiCell(740, 0, '', '');

    $username = $params['params']['user'];

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(70, 20, "Created By :", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 12);
    PDF::MultiCell(58, 20, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(60, 20, "Printed By :", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 13);
    PDF::MultiCell(346, 20, $username, '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);

    //format Y-m-d
    $cdate = $date->format('m-d-Y'); //initial date 10-30-2025

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Customer :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(436, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(140, 20, $cdate, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);

    PDF::MultiCell(98, 20, "Address :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(436, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(140, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // PDF::MultiCell(0, 0, "\n");
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Contact # :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(631, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function trans_consign_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->trans_consign_origamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(740, 0, '', '');
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        // $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        // $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', 12);
          PDF::MultiCell(23, 0, '', '', 'R', false, 0);
          PDF::MultiCell(378, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(55, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(40, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
          PDF::SetFont($font, '', 9);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(50, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(80, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(114, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->trans_consign_origamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(740, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    // $charges = number_format(0, 2);
    $chargeArr = $this->reporter->fixcolumn([$charges], '10', 0);
    $charge = is_array($chargeArr) ? $chargeArr[0] : $chargeArr;


    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 0, '', '', 'R', false, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(80, 0, '', '', 'R', false, 0);
    PDF::MultiCell(114, 0, $charge, '', 'R', false, 1);


    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, '', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(50, 0, '', 'B', 'R', false, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(80, 0, '', '', 'R', false, 0);
    PDF::MultiCell(114, 0, '', 'B', 'R', false, 1);


    $tl = $totalext + $charges;
    $totalext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalext], '10', 0);
    $ext = is_array($tlext) ? $tlext[0] : $tlext;

    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, 'TOTAL:', '', 'L', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(50, 0, $ext, '', 'R', false, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(80, 0, '', '', 'R', false, 0);
    PDF::MultiCell(114, 0, $ext, '', 'R', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, '', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false, 0);
    PDF::MultiCell(114, 0, '', 'T', 'R', false, 1);


    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, '', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false, 0);
    PDF::MultiCell(114, 0, '', 'T', 'R', false, 1);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function trans_consign_agentamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 20); //740
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    //start
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");


    PDF::SetFont($font, '', 6);
    PDF::MultiCell(740, 0, '', '');

    $username = $params['params']['user'];

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(65, 20, "Created By :", '', 'L', false, 0, '', '', true);
    // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 12);
    PDF::MultiCell(55, 20, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(60, 20, "Printed By :", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 12);
    PDF::MultiCell(355, 20, $username, '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(197, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);

    //format Y-m-d
    $cdate = $date->format('m-d-Y'); //initial date 10-30-2025

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Customer :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(434, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(142, 20, $cdate, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Address :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(434, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(142, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // PDF::MultiCell(0, 0, "\n");
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Contact # :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(631, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  public function trans_consign_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->trans_consign_agentamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(740, 0, '', '');
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        // $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        // $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
        //  $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        // $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        // $arr_ext = $this->reporter->fixcolumn([$ext], '20', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(20, 0, '', '', 'R', false, 0);
          PDF::MultiCell(380, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(4, 0, '', '', 'R', false, 0);
          PDF::MultiCell(53, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(115, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(115, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          $totalext += $agentext;
          if (PDF::getY() > 900) {
            $this->trans_consign_agentamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(740, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    // $charges = number_format(0, 2);
    $chargeArr = $this->reporter->fixcolumn([$charges], '10', 0);
    $charge = is_array($chargeArr) ? $chargeArr[0] : $chargeArr;

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(380, 0, '', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(4, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(115, 0, '', '', 'R', false, 0);
    PDF::MultiCell(115, 0, '', '', 'R', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(380, 0, '', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(4, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(115, 0, '', '', 'R', false, 0);
    PDF::MultiCell(115, 0, '', 'B', 'R', false, 1);
    // PDF::MultiCell(10, 0, '', '', 'R', false, 1);
    $totalext = number_format($totalext, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalext], '10', 0);
    $ext = is_array($tlext) ? $tlext[0] : $tlext;

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(380, 0, 'TOTAL:', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(4, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(115, 0, '', '', 'R', false, 0);
    PDF::MultiCell(115, 0, $ext, '', 'R', false, 1);
    // PDF::MultiCell(15, 0, '', '', 'R', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(380, 0, '', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(4, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(115, 0, '', '', 'R', false, 0);
    PDF::MultiCell(115, 0, '', 'T', 'R', false, 1);
    // PDF::MultiCell(10, 0, '', '', 'R', false, 1);


    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(380, 0, '', '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    PDF::MultiCell(4, 0, '', '', 'R', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(115, 0, '', '', 'R', false, 0);
    PDF::MultiCell(115, 0, '', 'T', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function trans_consign_agentamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 20); //740



    //start 
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");


    PDF::SetFont($font, '', 6);
    PDF::MultiCell(740, 0, '', '');

    $username = $params['params']['user'];

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(10, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(68, 20, "Created By :", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 12);
    PDF::MultiCell(58, 20, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(60, 20, "Printed By :", '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontboldcalibri, '', 13);
    PDF::MultiCell(346, 20, $username, '', 'L', false, 0, '', '', true);

    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);

    //format Y-m-d
    $cdate = $date->format('m-d-Y'); //initial date 10-30-2025

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(10, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Customer :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(5, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(432, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(140, 20, $cdate, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);

    PDF::MultiCell(98, 20, "Address :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(436, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(55, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(140, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // PDF::MultiCell(0, 0, "\n");
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(740, 0, '', '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(98, 20, "Contact # :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(631, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function trans_consign_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->trans_consign_agentamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(740, 0, '', '');
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        // $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        // $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '20', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,  $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', 12);
          PDF::MultiCell(23, 0, '', '', 'R', false, 0);
          PDF::MultiCell(378, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(55, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(40, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
          PDF::SetFont($fontcalibri, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(50, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(84, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(110, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->trans_consign_agentamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(740, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    // $charges = number_format(0, 2);
    $chargeArr = $this->reporter->fixcolumn([$charges], '10', 0);
    $charge = is_array($chargeArr) ? $chargeArr[0] : $chargeArr;


    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, '', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontcalibri, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(55, 0,  $charge, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(114, 0, $charge, '', 'R', false, 1);


    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, '', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(40, 0, '', 'B', 'L', false, 0);
    // PDF::SetFont($font, '', 10);
    PDF::MultiCell(95, 0, '', 'B', 'R', false, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(78, 0, '', '', 'R', false, 0);
    PDF::MultiCell(106, 0, '', 'B', 'R', false, 0);
    PDF::MultiCell(8, 0, '', '', 'R', false, 1);


    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;


    $agent = $totalagntext + $charges;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;

    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(25, 0, '', '', 'R', false, 0);
    PDF::MultiCell(360, 0, 'TOTAL:', '', 'L', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(95, 0, $origext, '', 'R', false, 0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(114, 0, $agentext, '', 'R', false, 1);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(740, 0, '', '');

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, '', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(40, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(95, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(78, 0, '', '', 'R', false, 0);
    PDF::MultiCell(106, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(8, 0, '', '', 'R', false, 1);




    PDF::SetFont($font, '', 1);
    PDF::MultiCell(740, 0, '', '', '', false, 1);

    PDF::SetFont($fontbold, '', 2);
    PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    PDF::MultiCell(365, 0, '', '', 'R', false, 0);
    PDF::MultiCell(71, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(40, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(95, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(78, 0, '', '', 'R', false, 0);
    PDF::MultiCell(106, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(8, 0, '', '', 'R', false, 1);




    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function trans_consign_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 20); //740

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");


    // PDF::SetFont($font, '', 6);
    // PDF::MultiCell(740, 0, '', '');

    // $username = $params['params']['user'];

    // PDF::SetFont($fontcalibri, '', 12);
    // // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::MultiCell(70, 20, "Created By :", '', 'L', false, 0, '', '', true);
    // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::SetFont($fontboldcalibri, '', 12);
    // PDF::MultiCell(58, 20, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '', '', true);

    // PDF::SetFont($fontcalibri, '', 12);
    // PDF::MultiCell(60, 20, "Printed By :", '', 'L', false, 0, '', '', true);
    // PDF::SetFont($fontboldcalibri, '', 12);
    // PDF::MultiCell(349, 20, $username, '', 'L', false, 0, '', '', true);

    // PDF::SetFont($fontcalibri, '', 13);
    // PDF::MultiCell(200, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    // $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    // $date = new DateTime($datehere);

    // //format Y-m-d
    // $cdate = $date->format('m-d-Y'); //initial date 10-30-2025

    // PDF::SetFont($font, '', 4);
    // PDF::MultiCell(740, 0, '', '');

    // PDF::SetFont($font, '', 12);
    // // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::MultiCell(98, 20, "Customer :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::SetFont($font, '', 14);
    // PDF::MultiCell(437, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 13);
    // PDF::MultiCell(55, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 14);
    // PDF::MultiCell(147, 20, $cdate, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(740, 0, '', '');

    // PDF::SetFont($font, '', 13);

    // PDF::MultiCell(98, 20, "Address :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::SetFont($font, '', 14);
    // PDF::MultiCell(437, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 13);
    // PDF::MultiCell(55, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 14);
    // PDF::MultiCell(147, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    // // PDF::MultiCell(0, 0, "\n");
    // $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(740, 0, '', '');
    // // $tel = '0987-93984384';
    // PDF::SetFont($font, '', 12);
    // //
    // PDF::MultiCell(98, 20, "Contact # :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::SetFont($font, '', 14);
    // PDF::MultiCell(639, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);



    if ($priceoption == 0 && $pricelayoutoption == 0) { //original amount and single price show 

      //start 
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");


      PDF::SetFont($font, '', 6);
      PDF::MultiCell(740, 0, '', '');

      $username = $params['params']['user'];

      PDF::SetFont($fontcalibri, '', 12);
      // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::MultiCell(70, 20, "Created By :", '', 'L', false, 0, '', '', true);
      PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($fontboldcalibri, '', 12);
      PDF::MultiCell(58, 20, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '', '', true);

      PDF::SetFont($fontcalibri, '', 12);
      PDF::MultiCell(60, 20, "Printed By :", '', 'L', false, 0, '', '', true);
      PDF::SetFont($fontboldcalibri, '', 12);
      PDF::MultiCell(349, 20, $username, '', 'L', false, 0, '', '', true);

      PDF::SetFont($fontcalibri, '', 13);
      PDF::MultiCell(200, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

      $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
      $date = new DateTime($datehere);

      //format Y-m-d
      $cdate = $date->format('m-d-Y'); //initial date 10-30-2025

      PDF::SetFont($font, '', 4);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($font, '', 12);
      // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::MultiCell(98, 20, "Customer :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(437, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 13);
      PDF::MultiCell(55, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(147, 20, $cdate, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      PDF::SetFont($font, '', 9);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($font, '', 13);

      PDF::MultiCell(98, 20, "Address :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(437, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 13);
      PDF::MultiCell(55, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(147, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


      // PDF::MultiCell(0, 0, "\n");
      $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(740, 0, '', '');
      // $tel = '0987-93984384';
      PDF::SetFont($font, '', 12);
      //
      PDF::MultiCell(98, 20, "Contact # :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(639, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    } elseif ($priceoption == 0 && $pricelayoutoption == 1) { // original amount and orig. Amount and Agent Amount Show



    } elseif ($priceoption == 1 && $pricelayoutoption == 0) { //agent amount and single price show

      //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

      //start
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");


      PDF::SetFont($font, '', 6);
      PDF::MultiCell(740, 0, '', '');

      $username = $params['params']['user'];

      PDF::SetFont($fontcalibri, '', 12);
      PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
      PDF::MultiCell(65, 20, "Created By :", '', 'L', false, 0, '', '', true);
      // PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($fontboldcalibri, '', 12);
      PDF::MultiCell(55, 20, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '', '', true);

      PDF::SetFont($fontcalibri, '', 12);
      PDF::MultiCell(60, 20, "Printed By :", '', 'L', false, 0, '', '', true);
      PDF::SetFont($fontboldcalibri, '', 12);
      PDF::MultiCell(355, 20, $username, '', 'L', false, 0, '', '', true);

      PDF::SetFont($fontcalibri, '', 13);
      PDF::MultiCell(197, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

      $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
      $date = new DateTime($datehere);

      //format Y-m-d
      $cdate = $date->format('m-d-Y'); //initial date 10-30-2025

      PDF::SetFont($font, '', 4);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($font, '', 12);
      PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
      PDF::MultiCell(98, 20, "Customer :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(434, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 13);
      PDF::MultiCell(55, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(142, 20, $cdate, '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      PDF::SetFont($font, '', 9);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($font, '', 13);
      PDF::MultiCell(8, 20, "", '', 'L', false, 0, '', '', true);
      PDF::MultiCell(98, 20, "Address :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(434, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 13);
      PDF::MultiCell(55, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(142, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


      // PDF::MultiCell(0, 0, "\n");
      $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(740, 0, '', '');
      // $tel = '0987-93984384';
      PDF::SetFont($font, '', 12);
      //
      PDF::MultiCell(98, 20, "Contact # :", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(3, 20, "", '', 'L', false, 0, '', '', true);
      PDF::SetFont($font, '', 14);
      PDF::MultiCell(639, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    } elseif ($priceoption == 1 && $pricelayoutoption == 1) { // agent amount and orig. Amount and Agent Amount Show
    }
  }

  public function trans_consign_orig($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];




    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }
    $this->trans_consign_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(740, 0, '', '');
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

        // $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
        //  $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];

        $agentamts = 100;
        $agentamt = number_format(100, 2);
        $agentext = $agentamts * $data[$i]['qty'];
        $genttl = number_format($agentext, 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '20', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamt], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          if ($discText != '') {
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(20, 0, '', '', 'R', false, 0);
            PDF::MultiCell(376, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(53, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
            PDF::MultiCell(8, 0, '', '', 'R', false, 0);
            PDF::MultiCell(110, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] . 'testtt' : ''), '', 'R', false, 0);
            PDF::MultiCell(110, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
            $totalext += $data[$i]['ext'];
            PDF::MultiCell(10, 0, '', '', 'R', false, 1);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(620, 0, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 1);
          } else {

            // PDF::MultiCell(110, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
            // PDF::MultiCell(110, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);

            if ($priceoption == 0 && $pricelayoutoption == 0) { //original amount and single price show 


              PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 0, '', '', 'R', false, 0);
              PDF::MultiCell(376, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
              PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(53, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
              PDF::MultiCell(8, 0, '', '', 'R', false, 0);
              PDF::MultiCell(110, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(110, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(10, 0, '', '', 'R', false, 1);
              $totalext += $data[$i]['ext'];
            } elseif ($priceoption == 0 && $pricelayoutoption == 1) { // original amount and orig. Amount and Agent Amount Show


              PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 0, '', '', 'R', false, 0);
              PDF::MultiCell(376, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
              PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(53, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
              PDF::MultiCell(8, 0, '', '', 'R', false, 0);
              PDF::MultiCell(110, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(110, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(10, 0, '', '', 'R', false, 1);
              $totalext += $data[$i]['ext'];
            } elseif ($priceoption == 1 && $pricelayoutoption == 0) { //agent amount and single price show 
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 0, '', '', 'R', false, 0);
              PDF::MultiCell(380, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0);
              PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(4, 0, '', '', 'R', false, 0);
              PDF::MultiCell(53, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0);
              // PDF::MultiCell(30, 0, '', '', 'R', false, 0);
              PDF::MultiCell(115, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
              PDF::MultiCell(115, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
              // PDF::MultiCell(10, 0, '', '', 'R', false, 1);
              $totalext += $agentext;
            } elseif ($priceoption == 1 && $pricelayoutoption == 1) { // agent amount and orig. Amount and Agent Amount Show
            }
          }

          // $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->trans_consign_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(740, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    // $charges = number_format(0, 2);
    $chargeArr = $this->reporter->fixcolumn([$charges], '10', 0);
    $charge = is_array($chargeArr) ? $chargeArr[0] : $chargeArr;


    if ($priceoption == 0 && $pricelayoutoption == 0) { //original amount and single price show 
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(355, 0, 'Other Charges:', '', 'R', false, 0);
      PDF::MultiCell(65, 0, '', '', 'R', false, 0);
      PDF::MultiCell(58, 0, '', '', 'L', false, 0);
      PDF::MultiCell(12, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, $charge, '', 'R', false, 0);
      PDF::MultiCell(10, 0, '', '', 'R', false, 1);

      PDF::SetFont($font, '', 2);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($fontbold, '', 10);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(372, 0, '', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(12, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, '', '', 'R', false, 0);
      PDF::MultiCell(105, 0, '', 'B', 'R', false, 0);
      PDF::MultiCell(15, 0, '', '', 'R', false, 1);
      $totalext = number_format($totalext, $decimalcurr);
      $tlext = $this->reporter->fixcolumn([$totalext], '10', 0);
      $ext = is_array($tlext) ? $tlext[0] : $tlext;

      PDF::SetFont($font, '', 1);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(377, 0, 'TOTAL:', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(12, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, '', '', 'R', false, 0);
      PDF::MultiCell(105, 0, $ext, '', 'R', false, 0);
      PDF::MultiCell(15, 0, '', '', 'R', false, 1);

      PDF::SetFont($font, '', 2);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(372, 0, '', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(12, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, '', '', 'R', false, 0);
      PDF::MultiCell(105, 0, '', 'T', 'R', false, 0);
      PDF::MultiCell(15, 0, '', '', 'R', false, 1);


      PDF::SetFont($font, '', 1);
      PDF::MultiCell(740, 0, '', '', '', false, 1);

      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(372, 0, '', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(12, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, '', '', 'R', false, 0);
      PDF::MultiCell(105, 0, '', 'T', 'R', false, 0);
      PDF::MultiCell(15, 0, '', '', 'R', false, 1);
    } elseif ($priceoption == 0 && $pricelayoutoption == 1) { // original amount and orig. Amount and Agent Amount Show
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(355, 0, '', '', 'R', false, 0);
      PDF::MultiCell(65, 0, '', '', 'R', false, 0);
      PDF::MultiCell(58, 0, '', '', 'L', false, 0);
      PDF::MultiCell(12, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, '', '', 'R', false, 0);
      PDF::MultiCell(110, 0, '', '', 'R', false, 0);
      PDF::MultiCell(10, 0, '', '', 'R', false, 1);
    } elseif ($priceoption == 1 && $pricelayoutoption == 0) { //agent amount and single price show

      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(380, 0, '', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(4, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(115, 0, '', '', 'R', false, 0);
      PDF::MultiCell(115, 0, '', '', 'R', false, 1);

      PDF::SetFont($font, '', 2);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($fontbold, '', 10);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(380, 0, '', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(4, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(115, 0, '', '', 'R', false, 0);
      PDF::MultiCell(115, 0, '', 'B', 'R', false, 1);
      // PDF::MultiCell(10, 0, '', '', 'R', false, 1);
      $totalext = number_format($totalext, $decimalcurr);
      $tlext = $this->reporter->fixcolumn([$totalext], '10', 0);
      $ext = is_array($tlext) ? $tlext[0] : $tlext;

      PDF::SetFont($font, '', 1);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(380, 0, 'TOTAL:', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(4, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(115, 0, '', '', 'R', false, 0);
      PDF::MultiCell(115, 0, $ext, '', 'R', false, 1);
      // PDF::MultiCell(15, 0, '', '', 'R', false, 1);

      PDF::SetFont($font, '', 2);
      PDF::MultiCell(740, 0, '', '');

      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(380, 0, '', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(4, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(115, 0, '', '', 'R', false, 0);
      PDF::MultiCell(115, 0, '', 'T', 'R', false, 1);
      // PDF::MultiCell(10, 0, '', '', 'R', false, 1);


      PDF::SetFont($font, '', 1);
      PDF::MultiCell(740, 0, '', '', '', false, 1);

      PDF::SetFont($fontbold, '', 2);
      PDF::MultiCell(20, 0, '', '', 'R', false, 0);
      PDF::MultiCell(380, 0, '', '', 'L', false, 0);
      PDF::MultiCell(53, 0, '', '', 'R', false, 0);
      PDF::MultiCell(4, 0, '', '', 'R', false, 0);
      PDF::MultiCell(53, 0, '', '', 'L', false, 0);
      PDF::MultiCell(115, 0, '', '', 'R', false, 0);
      PDF::MultiCell(115, 0, '', 'T', 'R', false, 1);
      // PDF::MultiCell(10, 0, '', '', 'R', false, 1);
    } elseif ($priceoption == 1 && $pricelayoutoption == 1) { // agent amount and orig. Amount and Agent Amount Show
    }


    // PDF::SetFont($fontbold, '', 12);
    // PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(355, 0, 'Other Charges:', '', 'R', false, 0);
    // PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(58, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(110, 0, $charge, '', 'R', false, 0);
    // PDF::MultiCell(10, 0, '', '', 'R', false, 1);


    // PDF::SetFont($font, '', 2);
    // PDF::MultiCell(740, 0, '', '');

    // PDF::SetFont($fontbold, '', 10);
    // PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(372, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(105, 0, '', 'B', 'R', false, 0);
    // PDF::MultiCell(15, 0, '', '', 'R', false, 1);
    // $totalext = number_format($totalext, $decimalcurr);
    // $tlext = $this->reporter->fixcolumn([$totalext], '10', 0);
    // $ext = is_array($tlext) ? $tlext[0] : $tlext;

    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(740, 0, '', '');

    // PDF::SetFont($fontbold, '', 11);
    // PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(377, 0, 'TOTAL:', '', 'L', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(105, 0, $ext, '', 'R', false, 0);
    // PDF::MultiCell(15, 0, '', '', 'R', false, 1);

    // PDF::SetFont($font, '', 2);
    // PDF::MultiCell(740, 0, '', '');

    // PDF::SetFont($fontbold, '', 2);
    // PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(372, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(105, 0, '', 'T', 'R', false, 0);
    // PDF::MultiCell(15, 0, '', '', 'R', false, 1);


    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(740, 0, '', '', '', false, 1);

    // PDF::SetFont($fontbold, '', 2);
    // PDF::MultiCell(20, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(372, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(12, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(110, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(105, 0, '', 'T', 'R', false, 0);
    // PDF::MultiCell(15, 0, '', '', 'R', false, 1);


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



  public function trans_si_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->trans_si_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->trans_si_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->trans_si_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->trans_si_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }


  //ok na tooo
  public function trans_si_origamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 29);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(108, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(612, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function trans_si_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->trans_si_origamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          if ($discText != '') {
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(68, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(10, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(332, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 11);
            PDF::MultiCell(80, 0, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 0);
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(80, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);
          }


          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->trans_si_origamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(105, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    $tl = $totalext + $charges;
    $totalext = $tl;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 689;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);



    $y3 = (float) 783;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function trans_si_origamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 29);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(108, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(612, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function trans_si_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->trans_si_origamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          // $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          PDF::SetFont($font, '', 12);
          // PDF::MultiCell(15, 0, '', '', 'R', false, 0);
          PDF::MultiCell(68, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 22, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(55, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(292, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(100, 22, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 22, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->trans_si_origamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);


    // PDF::MultiCell(25, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(292, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    // // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(100, 0, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
    // // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
    // PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, 'Other Charges:', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    $tl = $totalext + $charges;
    $totalext = $tl;

    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 686;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    $y3 = (float) 780;
    // PDF::SetFont($fontcalibri, '', 12);
    // PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontbold, '', 13);
    // // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(192, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function trans_si_agentamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 29);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(108, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(612, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function trans_si_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->trans_si_agentamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        // $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        // $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(65, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 0, '', '', 'R', false, 0);
          PDF::MultiCell(57, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(413, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(80, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);

          $totalext += $agentext;
          if (PDF::getY() > 900) {
            $this->trans_si_agentamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(105, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 689;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);



    $y3 = (float) 783;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function trans_si_agentamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 23);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(135, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(585, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function trans_si_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->trans_si_agentamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($fontbold, '', 12);
          // PDF::MultiCell(17, 0, '', '', 'R', false, 0);
          PDF::MultiCell(67, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 22, '', '', 'R', false, 0);
          PDF::MultiCell(55, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(308, 22, (isset($arr_itemname[$r]) ? strtoupper($arr_itemname[$r]) : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(90, 22, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 22, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->trans_si_agentamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(410, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($fontbold, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(95, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(95, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    // $tl = $totalext + $charges;
    // $totalext = $tl;

    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;


    $agent = $totalagntext + $charges;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(410, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(90, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0, $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($agent / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(400, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(95, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $agent / 1.12;

    $y2 = (float) 687;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(400, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(95, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    $y3 = (float) 781;

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(162, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function sure_si_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {
      case '0': // Original Amount
        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->sure_si_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->sure_si_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->sure_si_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->sure_si_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }


  //ok na tooo
  public function sure_si_origamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 14);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 6);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 24);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(134, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(566, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function sure_si_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sure_si_origamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          if ($discText != '') {
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(68, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0);
            PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(332, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($font, '', 11);
            PDF::MultiCell(80, 0, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 0);
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(80, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);
          }


          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->sure_si_origamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(105, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    // $tl = $totalext + $charges;
    // $totalext = $tl;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 689;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);



    $y3 = (float) 783;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function sure_si_origamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 23);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(135, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(585, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function sure_si_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sure_si_origamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(68, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 22, '', '', 'R', false, 0);
          PDF::MultiCell(55, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(292, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(100, 22, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 22, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->sure_si_origamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);


    // PDF::MultiCell(25, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(292, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    // // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(100, 0, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
    // // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
    // PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    $tl = $totalext + $charges;
    $totalext = $tl;

    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 687;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    $y3 = (float) 781;
    // PDF::SetFont($fontcalibri, '', 12);
    // PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontbold, '', 13);
    // // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(192, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function sure_si_agentamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 23);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(135, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(585, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function sure_si_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sure_si_agentamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }


        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          // $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          PDF::SetFont($font, '', 12);
          // PDF::MultiCell(15, 0, '', '', 'R', false, 0);
          PDF::MultiCell(68, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 0, '', '', 'R', false, 0);
          PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(392, 0, (isset($arr_itemname[$r]) ? strtoupper($arr_itemname[$r]) : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(100, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          $totalext += $agentext;
          if (PDF::getY() > 900) {
            $this->sure_si_agentamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);


    // PDF::MultiCell(25, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(292, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    // // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(100, 0, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
    // // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
    // PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    $tl = $totalext + $charges;
    $totalext = $tl;

    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 687;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    $y3 = (float) 781;
    // PDF::SetFont($fontcalibri, '', 12);
    // PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontbold, '', 13);
    // // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(192, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function sure_si_agentamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 23);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(135, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(585, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function sure_si_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sure_si_agentamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($fontbold, '', 12);
          // PDF::MultiCell(17, 0, '', '', 'R', false, 0);
          PDF::MultiCell(67, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 22, '', '', 'R', false, 0);
          PDF::MultiCell(55, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(308, 22, (isset($arr_itemname[$r]) ? strtoupper($arr_itemname[$r]) : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(90, 22, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 22, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->sure_si_agentamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    // $tl = $totalext + $charges;
    // $totalext = $tl;

    $tl = $totalorigext;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;


    $agent = $totalagntext;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(410, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(90, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0, $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($agent / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(400, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(95, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $agent / 1.12;

    $y2 = (float) 687;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(400, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(95, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    $y3 = (float) 781;

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(162, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }





  public function abelect_si_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {
      case '0': // Original Amount
        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->abelect_si_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->abelect_si_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->abelect_si_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->abelect_si_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }

  //ok na tooo
  public function abelect_si_origamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 14);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 6);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', 24);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(90, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(630, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function abelect_si_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->abelect_si_origamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          if ($discText != '') {
            PDF::SetFont($font, '', 12);
            // PDF::MultiCell(15, 0, '', '', 'R', false, 0);
            PDF::MultiCell(68, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0);
            PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(332, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

            PDF::SetFont($font, '', 11);
            PDF::MultiCell(80, 0, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 0);
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(80, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);
          }


          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->abelect_si_origamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(433, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(17, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(105, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    $tl = $totalext + $charges;
    $totalext = $tl;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 689;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);



    $y3 = (float) 783;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(75, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(295, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  //ok na tooo
  public function abelect_si_agentamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 14);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 6);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', 24);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(90, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(630, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function abelect_si_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->abelect_si_agentamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          if ($discText != '') {
            PDF::SetFont($font, '', 12);

            PDF::MultiCell(15, 0, '', '', 'R', false, 0);
            PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0);
            PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(412, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

            // PDF::SetFont($font, '', 11);
            // PDF::MultiCell(80, 0, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 0);
            // PDF::SetFont($font, '', 12);
            PDF::MultiCell(80, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
            PDF::MultiCell(95, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          }


          $totalext += $agentext;
          if (PDF::getY() > 900) {
            $this->abelect_si_agentamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(105, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    // $tl = $totalext + $charges;
    // $totalext = $tl;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 689;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);



    $y3 = (float) 783;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(75, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(295, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function abelect_si_agentamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 23);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(90, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(630, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function abelect_si_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->abelect_si_agentamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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


        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);


        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          // $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          PDF::SetFont($font, '', 12);
          // PDF::MultiCell(15, 0, '', '', 'R', false, 0);
          PDF::MultiCell(68, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 22, '', '', 'R', false, 0);
          PDF::MultiCell(55, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(292, 22, (isset($arr_itemname[$r]) ? strtoupper($arr_itemname[$r]) : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(100, 22, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 22, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);

          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->abelect_si_agentamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);


    // PDF::MultiCell(25, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(292, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    // // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(100, 0, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
    // // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
    // PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    // $tl = $totalext + $charges;
    // $totalext = $tl;

    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;


    $agent = $totalagntext + $charges;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;

    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($agent / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $agent / 1.12;

    $y2 = (float) 687;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    $y3 = (float) 781;
    // PDF::SetFont($fontcalibri, '', 12);
    // PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontbold, '', 13);
    // // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(192, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0, $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function abelect_si_origamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(525, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(195, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('F d, Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(657, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1);

    $x = PDF::GetX();
    $y = PDF::GetY(); //97.50125
    //  MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(525, 20, "", '', 'L', false, 0,  $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(195, 20, $cdate, '', 'L', false, 1, $x + 525,  $y - 16, true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(63, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(187, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 23);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(90, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(630, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function abelect_si_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->abelect_si_origamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          // $discText   = isset($arr_disc[$r]) ? $arr_disc[$r] : '';
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(15, 22, '', '', 'R', false, 0);
          PDF::MultiCell(53, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(10, 22, '', '', 'R', false, 0);
          PDF::MultiCell(55, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(292, 22, (isset($arr_itemname[$r]) ? strtoupper($arr_itemname[$r]) : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(100, 22, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(95, 22, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->abelect_si_origamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);


    // PDF::MultiCell(25, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(53, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(55, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(292, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    // // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(100, 0, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
    // // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
    // PDF::MultiCell(95, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(432, 0, 'Other Charges:', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(63, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,   number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 640;
    $x = PDF::GetX();

    $tl = $totalext + $charges;
    $totalext = $tl;

    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt = ($totalext / 1.12) * .12;

    $y1 = (float) 664;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $nvat = $totalext / 1.12;

    $y2 = (float) 687;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(70, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(95, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0); //425
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);



    $y3 = (float) 781;
    // PDF::SetFont($fontcalibri, '', 12);
    // PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($font, '', 11);
    // PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontboldcalibri, '', 11);
    // PDF::MultiCell(292, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::SetFont($fontbold, '', 13);
    // // PDF::MultiCell(160, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(160, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 1);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(78, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(192, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(65, 0, number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(95, 0,  number_format($totalext, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }






  public function powercrest_si_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {
      case '0': // Original Amount
        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->powercrest_si_origamt_1($params, $data); //itutuloy bukasssss
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->powercrest_si_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->powercrest_si_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->powercrest_si_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }


  public function powercrest_si_origamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(170, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 12);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(518, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(92, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(188, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(552, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function powercrest_si_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->powercrest_si_origamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(77, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(15, 15, '', '', 'R', false, 0);
          PDF::MultiCell(56, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(305, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(75, 15, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);

          PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 15, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(92, 15, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', false);
          PDF::Ln(9);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->powercrest_si_origamt_1_header($params, $data);
          }
        }
      }
    }

    PDF::SetFont($font, '', 15);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(427, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(118, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(104, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(6, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 602;
    $x = PDF::GetX();

    $tl = $totalext + $charges;

    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false, 0,  '', $y);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(90, 0, number_format($totalext, $decimalcurr), '', 'R', false, 1);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(103, 0,  number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(7, 0,  '', '', 'R', false, 1);

    $vatamt = ($tl / 1.12) * .12;

    $y1 = (float) 625;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y1);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(90, 0, number_format($vatamt, $decimalcurr), '', 'R', false, 1);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(103, 0,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(7, 0,  '', '', 'R', false, 1);


    $nvat = $tl / 1.12;

    $y2 = (float) 648;
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(23, 0, ' ', '', 'R', false,  0,  '', $y2);
    // PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(417, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(90, 0, number_format($nvat, $decimalcurr), '', 'R', false, 1);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(115, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(103, 0,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(7, 0,  '', '', 'R', false, 1);



    $y3 = (float) 741;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(282, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(153, 0, number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(7, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function powercrest_si_origamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(170, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 12);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(518, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(92, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(188, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(552, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function powercrest_si_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalorigext2 = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->powercrest_si_origamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(77, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(13, 15, '', '', 'R', false, 0);
          PDF::MultiCell(58, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(303, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(80, 15, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(99, 15, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(92, 15, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1);

          PDF::Ln(9);
          $totalorigext += $data[$i]['ext'];
          $totalorigext2 += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->powercrest_si_origamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 15);
    PDF::MultiCell(725, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0);
    PDF::MultiCell(60, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(58, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(303, 0, 'Other Charges:', '', 'R', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(80, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);

    PDF::MultiCell(97, 0, '', '', 'R', false, 0);
    PDF::MultiCell(89, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 602;
    $x = PDF::GetX();


    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;


    $tl2 = $totalorigext2 + $charges;
    $totalorigext2 = number_format($tl2, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalorigext2], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext; //hindi ko na binago variable pero galing din yan sa ext

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y);
    PDF::MultiCell(60, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(58, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(303, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(80, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(100, 0, '', '', 'R', false, 0);
    PDF::MultiCell(189, 0, $agentext, '', 'R', false, 1);
    // PDF::MultiCell(5, 0,  '', '', 'R', false, 1);


    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($tl2 / 1.12) * .12;

    $y1 = (float) 625;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y1);
    PDF::MultiCell(62, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(56, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(302, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(81, 0, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);

    // PDF::MultiCell(91, 0, '', '', 'R', false, 0);
    PDF::MultiCell(187, 0, number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0,  '', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $tl2 / 1.12;

    $y2 = (float) 648;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y2);
    PDF::MultiCell(62, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(56, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(302, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(81, 0, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(91, 0, '', '', 'R', false, 0);
    PDF::MultiCell(187, 0, number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0,  '', '', 'R', false, 1);



    $y3 = (float) 741;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //278
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(170, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //448

    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(83, 0,  $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(89, 0, '', '', 'R', false, 0);
    PDF::MultiCell(100, 0, $agentext, '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function powercrest_si_agentamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(170, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 12);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(518, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(92, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(188, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(552, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function powercrest_si_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->powercrest_si_agentamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          // PDF::MultiCell(20, 0, '', '', 'R', false, 0);
          PDF::MultiCell(75, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(15, 15, '', '', 'R', false, 0);
          PDF::MultiCell(58, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(383, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(99, 15, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(92, 15, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          PDF::Ln(9);
          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->powercrest_si_agentamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 15);
    PDF::MultiCell(725, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0);
    PDF::MultiCell(60, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(58, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(303, 0, '', '', 'R', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(80, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);

    PDF::MultiCell(97, 0, '', '', 'R', false, 0);
    PDF::MultiCell(89, 0, '', '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 602;
    $x = PDF::GetX();


    // $tl = $totalorigext;
    // $totalorigext = number_format($tl, $decimalcurr);
    // $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    // $origext = is_array($tlext) ? $tlext[0] : $tlext;


    $tl2 = $totalagntext;
    $totalagntext = number_format($tl2, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagntext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext; //hindi ko na binago variable pero galing din yan sa ext

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y);
    PDF::MultiCell(60, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(58, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(303, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(80, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(100, 0, '', '', 'R', false, 0);
    PDF::MultiCell(189, 0, $agentext, '', 'R', false, 1);
    // PDF::MultiCell(5, 0,  '', '', 'R', false, 1);


    // $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($tl2 / 1.12) * .12;

    $y1 = (float) 625;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y1);
    PDF::MultiCell(62, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(56, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(302, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(81, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);

    // PDF::MultiCell(91, 0, '', '', 'R', false, 0);
    PDF::MultiCell(187, 0, number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0,  '', '', 'R', false, 1);


    // $nvat1 = $tl / 1.12;
    $nvat2 = $tl2 / 1.12;

    $y2 = (float) 648;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y2);
    PDF::MultiCell(62, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(56, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(302, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(81, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(91, 0, '', '', 'R', false, 0);
    PDF::MultiCell(187, 0, number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0,  '', '', 'R', false, 1);



    $y3 = (float) 741;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //278
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(170, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //448

    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(83, 0,  '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(89, 0, '', '', 'R', false, 0);
    PDF::MultiCell(100, 0, $agentext, '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }









  public function powercrest_si_agentamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(170, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 12);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(518, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(92, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(188, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(552, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function powercrest_si_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->powercrest_si_agentamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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


        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          // PDF::MultiCell(20, 0, '', '', 'R', false, 0);
          PDF::MultiCell(75, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(15, 15, '', '', 'R', false, 0);
          PDF::MultiCell(58, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(303, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 11);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(80, 15, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(102, 15, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(89, 15, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1);
          PDF::Ln(9);
          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->powercrest_si_agentamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 15);
    PDF::MultiCell(725, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0);
    PDF::MultiCell(60, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(58, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(303, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(80, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);

    PDF::MultiCell(97, 0, '', '', 'R', false, 0);
    PDF::MultiCell(89, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 602;
    $x = PDF::GetX();


    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;


    $agent = $totalagntext + $charges;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y);
    PDF::MultiCell(60, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(58, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(303, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(80, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    // PDF::MultiCell(100, 0, '', '', 'R', false, 0);
    PDF::MultiCell(189, 0, $agentext, '', 'R', false, 1);
    // PDF::MultiCell(5, 0,  '', '', 'R', false, 1);


    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($agent / 1.12) * .12;

    $y1 = (float) 625;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y1);
    PDF::MultiCell(62, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(56, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(302, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(81, 0, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(91, 0, '', '', 'R', false, 0);
    PDF::MultiCell(96, 0, number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0,  '', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $agent / 1.12;

    $y2 = (float) 648;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(30, 0, '', '', 'R', false, 0, '', $y2);
    PDF::MultiCell(62, 0, '', '', 'C',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(56, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(302, 0, '', '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(81, 0, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(91, 0, '', '', 'R', false, 0);
    PDF::MultiCell(96, 0, number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(5, 0,  '', '', 'R', false, 1);



    $y3 = (float) 741;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //278
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(170, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true); //448

    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(83, 0,  $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(89, 0, '', '', 'R', false, 0);
    PDF::MultiCell(100, 0, $agentext, '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }





  public function bcmdse_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->bcmdse_si_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->bcmdse_si_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->bcmdse_si_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->bcmdse_si_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }



  public function bcmdse_si_origamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(175, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(526, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(84, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(526, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(84, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(180, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(545, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function bcmdse_si_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcmdse_si_origamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(93, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 0, '', '', 'R', false, 0);
          PDF::MultiCell(62, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(326, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 10);
          PDF::MultiCell(55, 0, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'R', false, 0);
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(72, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(92, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(5, 0, '', '', 'R', false, 1);
          $totalorigext += $data[$i]['ext'];
          // $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->bcmdse_si_origamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 1);
    PDF::MultiCell(720, 0, '', '');

    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(465, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(158, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(92, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 620;
    $x = PDF::GetX();

    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(480, 0, '', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(140, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  $origext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt1 = ($tl / 1.12) * .12;

    $y1 = (float) 645;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(615, 0, ' ', '', 'R', false, 0, '', $y1);
    PDF::MultiCell(100, 0,  number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);

    $nvat1 = $tl / 1.12;

    $y2 = (float) 662;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(615, 0, ' ', '', 'R', false, 0, '', $y2);
    PDF::MultiCell(100, 0,  number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    $y3 = (float) 730;

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(110, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(130, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 13);
    // PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(195, 0,  $origext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function bcmdse_si_agentamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(175, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(526, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(84, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(526, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(84, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(180, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(545, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function bcmdse_si_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcmdse_si_agentamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(93, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 0, '', '', 'R', false, 0);
          PDF::MultiCell(62, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(381, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::MultiCell(72, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(92, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(5, 0, '', '', 'R', false, 1);
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->bcmdse_si_agentamt_1_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 1);
    PDF::MultiCell(720, 0, '', '');

    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(465, 0, '', '', 'R', false, 0);
    PDF::MultiCell(158, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(92, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 620;
    $x = PDF::GetX();

    $tl = $totalagntext + $charges;
    $totalagntext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalagntext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(480, 0, '', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(140, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  $origext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    $vatamt1 = ($tl / 1.12) * .12;

    $y1 = (float) 645;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(615, 0, ' ', '', 'R', false, 0, '', $y1);
    PDF::MultiCell(100, 0,  number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);

    $nvat1 = $tl / 1.12;

    $y2 = (float) 662;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(615, 0, ' ', '', 'R', false, 0, '', $y2);
    PDF::MultiCell(100, 0,  number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    $y3 = (float) 730;

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(110, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(130, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 13);
    // PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(195, 0,  $origext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function bcmdse_si_agentamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(175, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(526, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(84, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(526, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(84, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(180, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(545, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function bcmdse_si_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcmdse_si_agentamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($fontbold, '', 9);
          PDF::MultiCell(93, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 0, '', '', 'R', false, 0);
          PDF::MultiCell(62, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(295, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 9);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(60, 0, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(100, 0, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(90, 0, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(5, 0, '', '', 'R', false, 1);
          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->bcmdse_si_agentamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 0);
    PDF::MultiCell(720, 0, '', '');

    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(465, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(60, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(95, 0, '', '', 'R', false, 0);
    PDF::MultiCell(90, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 620;
    $x = PDF::GetX();

    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;

    $agent = $totalagntext + $charges;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;








    // $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    // PDF::SetFont($fontbold, '', 9);
    // PDF::MultiCell(465, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 0);
    // PDF::SetTextColor(7, 13, 246);
    // PDF::MultiCell(60, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    // PDF::SetTextColor(0, 0, 0);
    // PDF::SetFont($fontbold, '', 11);
    // PDF::MultiCell(95, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(90, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(430, 0, '', '', 'R', false, 0,  '', $y);
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(90, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($agent / 1.12) * .12;

    $y1 = (float) 645;
    PDF::SetFont($font, '', 13);



    PDF::MultiCell(430, 0, '', '', 'R', false, 0,  '', $y1);
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(85, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);

    // PDF::MultiCell(615, 0, ' ', '', 'R', false, 0, '', $y1);
    // PDF::MultiCell(100, 0,  number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $agent / 1.12;

    $y2 = (float) 662;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(430, 0, '', '', 'R', false, 0,  '', $y2);
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(85, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    $y3 = (float) 730;

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(110, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(135, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, $origext, '', 'R', false, 0); //45
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(90, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function bcmdse_si_origamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 35); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(550, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(175, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(526, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(84, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(526, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(84, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, '', '');
    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    // $tel = '0987-93984384';
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(180, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(545, 20,  $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }


  public function bcmdse_si_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcmdse_si_origamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(720, 0, '', '');

    // PDF::SetCellPaddings(left, top, right, bottom);
    PDF::SetCellPaddings(0, 4, 0, 4);
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($fontbold, '', 9);
          PDF::MultiCell(93, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R',  false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 0, '', '', 'R', false, 0);
          PDF::MultiCell(62, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(295, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0,  '',  '', true, 0, false, true, 0, 'B', true);

          PDF::SetFont($font, '', 9);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(60, 0, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'R', false, 0);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(90, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(5, 0, '', '', 'R', false, 1);
          $totalorigext += $data[$i]['ext'];
          $totalagntext +=  $data[$i]['ext']; //original din ito
          if (PDF::getY() > 900) {
            $this->bcmdse_si_origamt_2_header($params, $data);
          }
        }
      }
    }


    PDF::SetFont($font, '', 0);
    PDF::MultiCell(720, 0, '', '');

    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(465, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(5, 0, '', '', 'R', false, 0);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(60, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(95, 0, '', '', 'R', false, 0);
    PDF::MultiCell(90, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 620;
    $x = PDF::GetX();

    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;

    $agent = $totalagntext + $charges;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;








    // $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    // PDF::SetFont($fontbold, '', 9);
    // PDF::MultiCell(465, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(5, 0, '', '', 'R', false, 0);
    // PDF::SetTextColor(7, 13, 246);
    // PDF::MultiCell(60, 0, number_format($charges, $decimalcurr), '', 'R', false, 0);
    // PDF::SetTextColor(0, 0, 0);
    // PDF::SetFont($fontbold, '', 11);
    // PDF::MultiCell(95, 0, '', '', 'R', false, 0);
    // PDF::MultiCell(90, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);



    PDF::MultiCell(430, 0, '', '', 'R', false, 0,  '', $y);
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(90, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);


    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($agent / 1.12) * .12;

    $y1 = (float) 645;
    PDF::SetFont($font, '', 13);



    PDF::MultiCell(430, 0, '', '', 'R', false, 0,  '', $y1);
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(85, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);

    // PDF::MultiCell(615, 0, ' ', '', 'R', false, 0, '', $y1);
    // PDF::MultiCell(100, 0,  number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $agent / 1.12;

    $y2 = (float) 662;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(430, 0, '', '', 'R', false, 0,  '', $y2);
    PDF::SetFont($font, '', 10);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(85, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0, ' ', '', 'R', false, 1);


    $y3 = (float) 730;

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(110, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(135, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 0, $origext, '', 'R', false, 0); //45
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(90, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(100, 0,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(5, 0, ' ', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function powercrest_last_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->pw_si_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->pw_si_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->pw_si_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->pw_si_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }


  public function pw_si_origamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(168, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(543, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n");


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');

    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(125, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(453, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(142, 20, $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function pw_si_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->pw_si_origamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(5, 15, '', '', 'R', false, 0);
          PDF::MultiCell(317, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(73, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          // PDF::MultiCell(15, 15, '', '', 'R', false, 0);
          PDF::MultiCell(55, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetFont($font, '', 9.5);
          PDF::MultiCell(47, 15, isset($arr_disc[$r]) ? $arr_disc[$r] : '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetFont($font, '', 10);
          PDF::MultiCell(60, 15, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(157, 15, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(6, 15, '', '', 'R', false, 1);
          PDF::Ln(9);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->pw_si_origamt_1_header($params, $data);
          }
        }
      }
    }

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(453, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(257, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 615;
    $x = PDF::GetX();

    $tl = $totalext + $charges;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 0,  number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 0,  '', '', 'R', false, 1);

    $vatamt = ($tl / 1.12) * .12;

    $y1 = (float) 640;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 0,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 0,  '', '', 'R', false, 1);


    $nvat = $tl / 1.12;

    $y2 = (float) 665;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 0,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 0,  '', '', 'R', false, 1);



    $y3 = (float) 765;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(282, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(146, 0, number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function pw_si_origamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(168, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(543, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n");


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');

    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(125, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(453, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(142, 20, $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function pw_si_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->pw_si_origamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(5, 15, '', '', 'R', false, 0);
          PDF::MultiCell(317, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(73, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          // PDF::MultiCell(15, 15, '', '', 'R', false, 0);(isset($arr_uom[$r]) ? $arr_uom[$r] : '')
          PDF::MultiCell(45, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetFont($font, '', 10);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(57, 15, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(60, 15, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);

          PDF::SetFont($font, '', 12);
          PDF::MultiCell(157, 15, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(6, 15, '', '', 'R', false, 1);
          PDF::Ln(9);
          $totalext += $data[$i]['ext'];
          if (PDF::getY() > 900) {
            $this->pw_si_origamt_2_header($params, $data);
          }
        }
      }
    }

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(453, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(257, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 615;
    $x = PDF::GetX();

    $tl = $totalext + $charges;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(105, 15, ' ', '', 'R', false, 0,  '', $y);
    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(208, 15, number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(142, 15, '', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 15,  number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 15,  '', '', 'R', false, 1);

    $vatamt = ($tl / 1.12) * .12;

    $y1 = (float) 640;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(105, 15, ' ', '', 'R', false, 0,  '', $y1);
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(212, 15, number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(138, 15, '', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 15,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 15,  '', '', 'R', false, 1);


    $nvat = $tl / 1.12;

    $y2 = (float) 665;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(105, 15, ' ', '', 'R', false, 0,  '', $y2);
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(212, 15, number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(138, 15, '', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 15,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 15,  '', '', 'R', false, 1);



    $y3 = (float) 765;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(282, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(146, 0, number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function pw_si_agentamt_1_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(168, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(543, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n");


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');

    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(125, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(453, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(142, 20, $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function pw_si_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->pw_si_agentamt_1_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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


        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }


        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);


        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(5, 15, '', '', 'R', false, 0);
          PDF::MultiCell(321, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(65, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(60, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(103, 15, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(155, 15, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(11, 15, '', '', 'R', false, 1);
          PDF::Ln(9);
          $totalext += $agentext;
          if (PDF::getY() > 900) {
            $this->pw_si_agentamt_1_header($params, $data);
          }
        }
      }
    }

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(453, 0, '', '', 'R', false, 0);
    PDF::MultiCell(257, 0,  '', '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 620;
    $x = PDF::GetX();

    $tl = $totalext;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(102, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 0,  number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(23, 0,  '', '', 'R', false, 1);

    $vatamt = ($tl / 1.12) * .12;

    $y1 = (float) 640;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y1);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(102, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 0,  number_format($vatamt, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(23, 0,  '', '', 'R', false, 1);


    $nvat = $tl / 1.12;

    $y2 = (float) 665;
    PDF::SetFont($font, '', 13);

    PDF::MultiCell(25, 0, ' ', '', 'R', false, 0,  '', $y2);
    PDF::MultiCell(430, 0, '', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(102, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 0,  number_format($nvat, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(23, 0,  '', '', 'R', false, 1);



    $y3 = (float) 750;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 15, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 15, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 15, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 15, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 15, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(282, 15, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(139, 15, '', '', 'R', false, 0);
    PDF::MultiCell(23, 15, '', '', 'R', false, 1);

    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 15, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(280, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(139, 15, number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(23, 15, '', '', 'R', false, 1);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }













  public function pw_si_agentamt_2_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 20);
    PDF::MultiCell(720, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(168, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(110, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, $cdate, '', 'L', false, 1);


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(543, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0);
    PDF::MultiCell(518, 20, '', '', 'L', false, 0);
    PDF::MultiCell(92, 20, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n");


    PDF::SetFont($font, '', 2);
    PDF::MultiCell(720, 0, '', '');

    $tel = (isset($data[0]['tel']) ? $data[0]['tel'] : '');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(125, 20, "", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(453, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(142, 20, $tel, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
  }

  //ok na ito
  public function pw_si_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalorigext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->pw_si_agentamt_2_header($params, $data);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(720, 0, '', '');
    $countarr = 0;
    $x = PDF::GetX();
    $y = PDF::GetY();
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


        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '45', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11);
          PDF::MultiCell(5, 15, '', '', 'R', false, 0);
          PDF::MultiCell(317, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(73, 15, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          // PDF::MultiCell(15, 15, '', '', 'R', false, 0);(isset($arr_uom[$r]) ? $arr_uom[$r] : '')
          PDF::MultiCell(45, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetFont($font, '', 10);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(57, 15, isset($arr_amt[$r]) ? $arr_amt[$r] : '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(60, 15, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);

          PDF::SetFont($font, '', 12);
          PDF::MultiCell(157, 15, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', false);
          PDF::MultiCell(6, 15, '', '', 'R', false, 1);
          PDF::Ln(9);
          $totalorigext += $data[$i]['ext'];
          $totalagntext += $agentext;
          if (PDF::getY() > 900) {
            $this->pw_si_agentamt_2_header($params, $data);
          }
        }
      }
    }

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(720, 0, '', '');


    $charges = (isset($data[0]['charges']) ? $data[0]['charges'] : 0);
    PDF::SetFont($fontbold, '', 11);

    PDF::MultiCell(453, 0, 'Other Charges:', '', 'R', false, 0);
    PDF::MultiCell(257, 0,  number_format($charges, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(10, 0,  '', '', 'R', false, 1);



    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");


    $y = (float) 615;
    $x = PDF::GetX();

    // $tl = $totalext + $charges;

    $tl = $totalorigext + $charges;
    $totalorigext = number_format($tl, $decimalcurr);
    $tlext = $this->reporter->fixcolumn([$totalorigext], '10', 0);
    $origext = is_array($tlext) ? $tlext[0] : $tlext;

    $agent = $totalagntext + $charges;
    $totalagentext = number_format($agent, $decimalcurr);
    $tlagentext = $this->reporter->fixcolumn([$totalagentext], '10', 0);
    $agentext = is_array($tlagentext) ? $tlagentext[0] : $tlagentext;

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(105, 15, ' ', '', 'R', false, 0,  '', $y);
    PDF::SetTextColor(7, 13, 246);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(208, 15, $origext, '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(142, 15, '', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 15,  $agentext, '', 'R', false, 0);
    PDF::MultiCell(14, 15,  '', '', 'R', false, 1);

    $vatamt1 = ($tl / 1.12) * .12;
    $vatamt2 = ($agent / 1.12) * .12;


    $y1 = (float) 640;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(105, 15, ' ', '', 'R', false, 0,  '', $y1);
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(212, 15, number_format($vatamt1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(138, 15, '', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 15,  number_format($vatamt2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 15,  '', '', 'R', false, 1);


    $nvat1 = $tl / 1.12;
    $nvat2 = $agent / 1.12;

    $y2 = (float) 665;
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(105, 15, ' ', '', 'R', false, 0,  '', $y2);
    PDF::SetFont($font, '', 11);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(212, 15, number_format($nvat1, $decimalcurr), '', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(138, 15, '', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(20, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(110, 15, ' ', '', 'R', false, 0);
    PDF::MultiCell(101, 15,  number_format($nvat2, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 15,  '', '', 'R', false, 1);



    $y3 = (float) 765;
    PDF::SetFont($fontcalibri, '', 12);
    PDF::MultiCell(93, 0, "", '', 'L', false, 0, '',  $y3, true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(60, 0, "Created by", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 0, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(50, 0, (isset($data[0]['createby']) ? $data[0]['createby'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(65, 0, 'Printed By:', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontboldcalibri, '', 11);
    PDF::MultiCell(282, 0, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(146, 0, number_format($tl, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(14, 0, '', '', 'R', false, 1);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function quotation_form_layout($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->quotation_form_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->quotation_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->quotation_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->quotation_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }


   public function two_headers($params, $data)
  {

    $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '3': // MAIN OFFICE SJ
        return $this->main_office_header($params, $data);
        break;

      case '7': //QUOTATION FORM
            return $this->quotation_form_header($params, $data);
        break;
    }
  }

  public function main_office_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    // $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    // $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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

    // $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    // PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
    // $this->reportheader->getheader($params);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 15);
    PDF::SetTextColor(110, 150, 112);
    PDF::MultiCell(720, 0, 'TRANS POWER', '', 'C', false, 1);
    PDF::SetTextColor(0, 0, 0);
    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    // PDF::SetFont($font, '', 10);
    // PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(400, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(180, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(120, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Customer", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Address", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "CONTACT #", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(450, 20, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "REFERENCE : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y/m/d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    // PDF::MultiCell(70, 20, "Printed Date", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20,  $formattedDate, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 20, "Printed by : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(170, 20, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(230, 20,  'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(70, 20, "Printed Date", 0, 'L', false, 0);
    PDF::MultiCell(10, 20, ":", 0, 'L', false, 0);
    PDF::MultiCell(150, 20, $formattedDate, 0, 'L', false, 0);
    PDF::MultiCell(60, 20, "Printed by : ", 0, 'L', false, 0);
    PDF::MultiCell(170, 20, $username, 0, 'L', false, 0);
    PDF::MultiCell(0, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 0, 'R', false, 1);


    //   PDF::SetFont($font, '', 10);R

    // PDF::MultiCell(0, 0, "\n");

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    // PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }



  public function quotation_form_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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

    // $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    // PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
    // $this->reportheader->getheader($params);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
   
    PDF::MultiCell(0, 0, '', '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::SetTextColor(110, 150, 112);
    PDF::MultiCell(520, 0, 'QUOTATION FORM', '', 'L', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(400, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(180, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(120, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Customer", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Address", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "Contact #", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(450, 20, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, "REFERENCE : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y/m/d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    // PDF::MultiCell(70, 20, "Printed Date", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(10, 20, ":", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(150, 20,  $formattedDate, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(60, 20, "Printed by : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(170, 20, $username, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    // PDF::MultiCell(230, 20,  'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(70, 20, "Printed Date", 0, 'L', false, 0);
    PDF::MultiCell(10, 20, ":", 0, 'L', false, 0);
    PDF::MultiCell(150, 20, $formattedDate, 0, 'L', false, 0);
    PDF::MultiCell(60, 20, "Printed by : ", 0, 'L', false, 0);
    PDF::MultiCell(170, 20, $username, 0, 'L', false, 0);
    PDF::MultiCell(0, 20, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), 0, 'R', false, 1);


    //   PDF::SetFont($font, '', 10);R

    // PDF::MultiCell(0, 0, "\n");

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(700, 0, '', 'T');

    // PDF::SetFont($font, 'B', 12);
    // // PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    // PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    // PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    // PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
    // PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
    // PDF::MultiCell(100, 0, "(+/-) %", '', 'C', false, 0);
    // PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);


  
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];
    
    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
              PDF::SetFont($font, 'B', 13);
              // PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
              PDF::MultiCell(70, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(280, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "(+/-) %", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

            break;
          case '1': // Orig. Amount and Agent Amount Show
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(70, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(280, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(100, 0, "PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
              PDF::SetFont($font, 'B', 13);
              PDF::MultiCell(70, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(310, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(120, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(150, 0, "TOTAL", '', 'R', false);
            break;
          case '1': // Orig. Amount and Agent Amount Show
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(70, 0, "QTY", '', 'C', false, 0);
              PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
              PDF::MultiCell(280, 0, "DESCRIPTION", '', 'L', false, 0);
              PDF::MultiCell(100, 0, "PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
              PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);
            break;
        }
        break;
    }


    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function quotation_form_origamt_1($params, $data)
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
    $this->two_headers($params, $data);

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        // $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '47', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(280, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
             PDF::SetFont($font, '', 12);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->two_headers($params, $data);
        }
      }
    }

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
              PDF::SetFont($font, '',  $fontsize);
              PDF::MultiCell(50, 15, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
              PDF::MultiCell(650, 15,  ' - '.number_format($qry[0]['ext'], $decimalcurr), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      }

    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );

    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    // PDF::setCellPadding($left, $top, $right, $bottom);
    $style_solid = array(
      'width' => 1,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );

  
    PDF::SetLineStyle($style_solid);
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 10, 'GRAND TOTAL: ', 'B', 'R', false, 0);
    PDF::MultiCell(100, 10, number_format($totalext, $decimalcurr), 'B', 'R');

    PDF::setCellPaddings(0, 0, 0, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(180, 0, 'Released By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Received By: ', '', 'L');


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 1);

    // $style_solid = array(
    //   'width' => 1,
    //   'cap' => 'butt',
    //   'join' => 'miter',
    //   'dash' => 0, // ito ang nag-aalis ng dotted
    //   'color' => array(0, 0, 0)
    // );
    // PDF::SetLineStyle($style_solid);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L');
    // PDF::MultiCell(53, 0, '', '', 'L');

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


   public function quotation_origamt_2($params, $data)
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
    $this->two_headers($params, $data);

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
      $totalagntext=0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        // $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

         $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '47', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_agt]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(280, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
             PDF::SetFont($font, '', 12);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];
         $totalagntext += $agentext;
        if (PDF::getY() > 900) {
          $this->two_headers($params, $data);
        }
      }
    }

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
              PDF::SetFont($font, '',  $fontsize);
          PDF::MultiCell(50, 15, 'RETURN', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' , '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(300, 15, ' ' , '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetFont($font, '', 12);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(100, 15, '-' . number_format($qry[0]['ext'], $decimalcurr), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(100, 15, ' ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, '-' .number_format($qry[0]['ext'], $decimalcurr), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );

    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    // PDF::setCellPadding($left, $top, $right, $bottom);
    $style_solid = array(
      'width' => 1,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 10, '', 'B', 'R', false, 0);
    PDF::MultiCell(50, 10,'', 'B', 'R', false, 0);
    PDF::MultiCell(300, 10, 'GRAND TOTAL:', 'B', 'R', false, 0);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 10, number_format($totalext, $decimalcurr), 'B', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(100, 10, '', 'B', 'R', false, 0);
    PDF::MultiCell(100, 10, number_format($totalext, $decimalcurr), 'B', 'R', false, 1);

    PDF::setCellPaddings(0, 0, 0, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(180, 0, 'Released By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Received By: ', '', 'L');


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 1);

    // $style_solid = array(
    //   'width' => 1,
    //   'cap' => 'butt',
    //   'join' => 'miter',
    //   'dash' => 0, // ito ang nag-aalis ng dotted
    //   'color' => array(0, 0, 0)
    // );
    // PDF::SetLineStyle($style_solid);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L');
    // PDF::MultiCell(53, 0, '', '', 'L');

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

  public function quotation_agentamt_1($params, $data)
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
    $this->two_headers($params, $data);

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        // $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

         $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '47', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom,$arr_agt, $arr_agentext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(310, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::SetFont($font, '', 12);
           PDF::MultiCell(120, 15, ' ' . (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          // PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(150, 15, ' ' . (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $agentext;

        if (PDF::getY() > 900) {
          $this->two_headers($params, $data);
        }
      }
    }

    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );

    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    // PDF::setCellPadding($left, $top, $right, $bottom);
    $style_solid = array(
      'width' => 1,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 10, 'GRAND TOTAL: ', 'B', 'R', false, 0);
    PDF::MultiCell(100, 10, number_format($totalext, $decimalcurr), 'B', 'R');

    PDF::setCellPaddings(0, 0, 0, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(180, 0, 'Released By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Received By: ', '', 'L');


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 1);

    // $style_solid = array(
    //   'width' => 1,
    //   'cap' => 'butt',
    //   'join' => 'miter',
    //   'dash' => 0, // ito ang nag-aalis ng dotted
    //   'color' => array(0, 0, 0)
    // );
    // PDF::SetLineStyle($style_solid);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L');
    // PDF::MultiCell(53, 0, '', '', 'L');

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

  public function quotation_agentamt_2($params, $data)
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
    $this->two_headers($params, $data);

    $style_solid = array(
      'width' => 2,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
     $totalagntext=0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        // $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 2);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '47', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_agt, $arr_agentext]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          // PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
           PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(280, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::SetFont($font, '', 12); 
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

       $totalext += $data[$i]['ext'];
         $totalagntext += $agentext;

        if (PDF::getY() > 900) {
          $this->two_headers($params, $data);
        }
      }
    }

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
              PDF::SetFont($font, '',  $fontsize);

                PDF::MultiCell(50, 15, 'RETURN', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(50, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::SetFont($font, '', 10.5);
                PDF::MultiCell(300, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::SetFont($font, '', 12); 
                PDF::SetTextColor(7, 13, 246);
                PDF::MultiCell(100, 15, '-'.number_format($qry[0]['ext'], $decimalcurr), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::SetTextColor(0, 0, 0);
                PDF::MultiCell(100, 15, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                PDF::MultiCell(100, 15, '', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

    $dotted_style = array(
      'width' => 0.3,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => '0.5,1', // dots
      'phase' => 0,
      'color' => array(0, 0, 0)
    );

    PDF::SetLineStyle($dotted_style);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    // PDF::setCellPadding($left, $top, $right, $bottom);
    $style_solid = array(
      'width' => 1,
      'cap' => 'butt',
      'join' => 'miter',
      'dash' => 0, // ito ang nag-aalis ng dotted
      'color' => array(0, 0, 0)
    );
    PDF::SetLineStyle($style_solid);
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(600, 10, 'GRAND TOTAL: ', 'B', 'R', false, 0);
    // PDF::MultiCell(100, 10, number_format($totalext, $decimalcurr), 'B', 'R');
     PDF::MultiCell(50, 10, '', 'B', 'R', false, 0);
    PDF::MultiCell(50, 10,'', 'B', 'R', false, 0);
    PDF::MultiCell(300, 10, 'GRAND TOTAL:', 'B', 'R', false, 0);
    PDF::SetTextColor(7, 13, 246);
    PDF::MultiCell(100, 10, number_format($totalext, $decimalcurr), 'B', 'R', false, 0);
    PDF::SetTextColor(0, 0, 0);
    PDF::MultiCell(100, 10, '', 'B', 'R', false, 0);
    PDF::MultiCell(100, 10, number_format($totalagntext, $decimalcurr), 'B', 'R', false, 1);



    PDF::setCellPaddings(0, 0, 0, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(650, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(180, 0, 'Released By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, 'Received By: ', '', 'L');


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");
    PDF::setCellPaddings(0, 0, 0, 5);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['approved'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(180, 0, $params['params']['dataparams']['received'], 'B', 'C', false, 1);

    // $style_solid = array(
    //   'width' => 1,
    //   'cap' => 'butt',
    //   'join' => 'miter',
    //   'dash' => 0, // ito ang nag-aalis ng dotted
    //   'color' => array(0, 0, 0)
    // );
    // PDF::SetLineStyle($style_solid);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    // PDF::MultiCell(180, 0, '', 'T', 'L');
    // PDF::MultiCell(53, 0, '', '', 'L');

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

  
  public function cash_sales_invoice_a($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->cash_sales_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->cash_sales_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->cash_sales_agntamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->cash_sales_agntamt_2($params, $data);
            break;
        }
        break;
    }
  }

   public function headers($params, $data,$next){
   $reporttype = $params['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '9': // Cash sales header
        return $this->cash_sales_origamt_1_header($params, $data,$next);
        break;

      case '11': //QUOTATION FORM
            return $this->new_quotation_form_header($params, $data,$next);
        break;
    }
   }


  
    public function cash_sales_origamt_1_header($params, $data,$next)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(18, 18); //740


    if($next==1){

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 16.5);
    PDF::MultiCell(764, 0, '', '');

    }else{
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 23);
    PDF::MultiCell(764, 0, '', '');
    }
  


    $username = $params['params']['user'];

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(212, 20, '', '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 12);//naka 14
    PDF::MultiCell(30, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(240, 20, '', '', 'L', false, 0);
    PDF::MultiCell(309, 20, '', '', 'L', false, 0);
    PDF::MultiCell(185, 20, strtoupper(isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1);


    PDF::SetFont($font, '', 37);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(47, 20, '', '', 'L', false, 0);
    PDF::MultiCell(495, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '', '', true);
    PDF::MultiCell(222, 20, $cdate, '', 'L', false, 1);

    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(760, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(30, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(240, 20, strtoupper(isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 20, 'Contact No.:', '', 'L', false, 0);
    PDF::MultiCell(200, 20, strtoupper(isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 0);
    // PDF::MultiCell(170, 20, '', '', 'L', false, 0);
    PDF::MultiCell(194, 20, strtoupper(isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false, 1);

  
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(55, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(709, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
   
     if($next==1){
        PDF::SetFont($font, '', 25);
        PDF::MultiCell(764, 0, '', '');
     }else{
        PDF::SetFont($font, '', 8);
        PDF::MultiCell(764, 0, '', '');
     }
  

    PDF::SetFont($font, '', 16);
    PDF::MultiCell(115, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(125, 20, '', '', 'L', false, 0);
    PDF::MultiCell(280, 20, '', '', 'L', false, 0);
    PDF::MultiCell(244, 20, '', '', 'L', false, 1);
  }


   public function cash_sales_origamt_footer1($params, $data,$rowCount,$totalext){
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
     $reporttype = $params['params']['dataparams']['reporttype'];
       $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    $pageLimit = 19;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

      $vatable=0;
    $vatamt=0;

    if ($data[0]['vattype'] == 'VATABLE') {
        $vatable=$totalext/1.12;
        $vatamt=$vatable*.12;
      }
    
    $pwddisc=0;
  
  
    $emptyRows = $pageLimit - $rowCount;
   
      if($reporttype=='9'){//CASH SALES INVOICE
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow_cashsales_a('');
        }
      }
    
     PDF::SetFont($fontbold, '', $fontsize);
     //TOTAL SALES (Vat inclusive)
    //  PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22,'', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //vatex  and amount due
     

     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
      }

     //zero rated sales and less withholding
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatamt, $decimalcurr),'', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetFont($fontbold, '', 14);
     PDF::MultiCell(103, 22, number_format($totaldue, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);



    PDF::SetFont($font, '', 18);
    PDF::MultiCell(764, 15, '', '');

     
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
     PDF::SetTextColor(0, 0, 0);
     PDF::SetFont($font, '', 12);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
   }

   public function cash_sales_origamt_footer2($params, $data,$rowCount,$totalext){
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
     $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
     $pageLimit = 19;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

     $vatable=0;
     $vatamt=0;

      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
        }

  
    
    $pwddisc=0;
   

    $emptyRows = $pageLimit - $rowCount;
    if($reporttype=='9'){//CASH SALES INVOICE
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_a('');
    }
    }


    PDF::SetFont($fontbold, '', $fontsize);
     //TOTAL SALES (Vat inclusive)
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(107, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(107, 22,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(107, 22,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //vatex  and amount due

     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

       $withholdingTax = 0;

    if ($data[0]['ewtrate'] != 0) {
        $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
    }

     //zero rated sales and less withholding
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //vat amount and total amount due
     $totaldue=$totalext-$withholdingTax;
    PDF::SetFont($fontbold, '', 14);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatamt, $decimalcurr),'', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totaldue, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
   
      PDF::SetFont($font, '', 18);
      PDF::MultiCell(764, 15, '', '');
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

     PDF::SetFont($font, '', 12);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
   }

    public function cash_sales_agent_footer2($params, $data,$rowCount,$totalext,$totalagntext){

      $companyid = $params['params']['companyid'];
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
      $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
      
      $reporttype = $params['params']['dataparams']['reporttype'];
      $center = $params['params']['center'];
      $username = $params['params']['user'];
      $count = $page = 35;
      $border = "1px solid ";
      $font = "";
      $fontbold = "";
      $fontsize = 13;
      $pageLimit = 19;
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
      }

      $fontcalibri = "";
      $fontboldcalibri = "";
      if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
        $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
        $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
      }

      $vatable=0;
      $vatamt=0;
      $agentvatable=0;
      $agentvatamt=0;

      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
          $agentvatable=$totalagntext/1.12;
          $agentvatamt=$agentvatable*.12;
        }
        
        $pwddisc=0;
      
        //  $reporttype = $params['params']['dataparams']['reporttype'];

          $emptyRows = $pageLimit - $rowCount;
        if($reporttype=='9'){//CASH SALES INVOICE
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow_cashsales_a('');
        }
        }
    
                PDF::SetFont($fontbold, '', $fontsize);
                //TOTAL SALES (Vat inclusive)
                        PDF::SetTextColor(7, 13, 246);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(107, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetTextColor(0, 0, 0);
                PDF::MultiCell(105, 22, number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


                //Less Vat
                PDF::SetTextColor(7, 13, 246);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(107, 22, number_format($vatamt, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetTextColor(0, 0, 0);
                PDF::MultiCell(105, 22, number_format($agentvatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

                //Vatable sales 
                PDF::SetTextColor(7, 13, 246);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(107, 22, number_format($vatable, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetTextColor(0, 0, 0);
                PDF::MultiCell(105, 22, number_format($agentvatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

                
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(415, 22, number_format($agentvatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(105, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

             //vatex  and amount due

              PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(105, 22, number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

              //zero rated sales and less withholding
              
                $withholdingTax = 0;

                if ($data[0]['ewtrate'] != 0) {
                    $withholdingTax = ($totalagntext / 1.12) * ($data[0]['ewtrate'] / 100);
                }
              PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(105, 22, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

              //vat amount and total amount due
               $totaldue=$totalagntext-$withholdingTax;
              PDF::SetFont($fontbold, '', 14);
              PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              
              PDF::MultiCell(415, 22,number_format($agentvatamt, $decimalcurr) ,'', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(105, 22, number_format($totaldue, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          

              PDF::SetFont($font, '', 18);
              PDF::MultiCell(764, 15, '', '');

            
            $username = $params['params']['user'];
            $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
            $printeddate = $this->othersClass->getCurrentTimeStamp();
            $datetime = new DateTime($printeddate);
            $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

            PDF::SetFont($font, '', 12);
            PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    }

  //ok na ito
  public function cash_sales_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;

     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }


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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_disc]);

        for ($r = 0; $r < $maxrow; $r++) {
                  PDF::SetFont($font, '', 12);
                  PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
                  PDF::SetFont($font, '', 10.5);
                  PDF::MultiCell(345, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::SetFont($font, '', 12);
                  PDF::MultiCell(70, 22, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(94, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(103, 22, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $rowCount++;
        }
        
      //  $totalext= $this->coreFunctions->datareader("
      //         select sum(ext) as value from (
      //         select sum(stock.ext) as ext from lastock as stock where stock.trno='".$data[$i]['trno']."'
      //          union all
      //          select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$data[$i]['trno']."') as a");
        if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->cash_sales_origamt_footer1($params, $data,$rowCount,$totalext);
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
        
      }
    }
    
  
  
    $emptyRows = $pageLimit - $rowCount;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {

       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);

           if($emptyRows != 0){
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(606, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(38, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows-1;
              
           }else{ //walang empty na row pero may return
              $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
              $emptyRows = 0;
              $this->sales_invoice_header($params, $data,$next);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(606, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(38, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows + 12;
           }

      }

    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }
    
    $pwddisc=0;

    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_a('');
    }
   

     PDF::SetFont($fontbold, '', $fontsize);
     //TOTAL SALES (Vat inclusive)
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22,'', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //vatex  and amount due

     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


  
    $withholdingTax = 0;

    if ($data[0]['ewtrate'] != 0) {
        $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
    }


     //zero rated sales and less withholding
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatamt, $decimalcurr),'', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetFont($fontbold, '', 14);
     PDF::MultiCell(103, 22, number_format($totaldue, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    
    PDF::SetFont($font, '', 18);
    PDF::MultiCell(764, 15, '', '');

     
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

     PDF::SetFont($font, '', 12);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  //ok na ito
  public function cash_sales_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
     $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;
    $totalagntext=0;

     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }

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
        $agentamts = number_format($data[$i]['agtamt'], 2);
         if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
        } else {
          $agentext = 0;
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
         $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext, $arr_agt]);

        for ($r = 0; $r < $maxrow; $r++) {
             PDF::SetFont($font, '', 12);
           // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
            PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
            PDF::SetFont($font, '', 10.5);
            PDF::MultiCell(345, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 12);
            PDF::SetTextColor(7, 13, 246);
            PDF::MultiCell(70, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetTextColor(0, 0, 0);

            PDF::MultiCell(94, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);

            PDF::MultiCell(100, 22, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
       
        //  $totalext= $this->coreFunctions->datareader("
        //       select sum(ext) as value from (
        //       select sum(stock.ext) as ext from lastock as stock where stock.trno='".$data[$i]['trno']."'
        //        union all
        //        select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$data[$i]['trno']."') as a");
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->cash_sales_origamt_footer2($params, $data,$rowCount,$totalext);
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
      }
    }
  

    $emptyRows = $pageLimit - $rowCount;

      
    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
           if($emptyRows != 0){
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(606, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(38, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows-1;
              
           }else{ //walang empty na row pero may return
              $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
              $emptyRows = 0;
              $this->sales_invoice_header($params, $data,$next);
            PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(606, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(38, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows + 12;
           }

      }


        $vatable=0;
        $vatamt=0;
        $agentvatable=0;
        $agentvatamt=0;

      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
          $agentvatable=$totalagntext/1.12;
          $agentvatamt=$agentvatable*.12;
        }

      
        
        $pwddisc=0;
   
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow_cashsales_a('');
        }


    PDF::SetFont($fontbold, '', $fontsize);
     //TOTAL SALES (Vat inclusive)
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(107, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(107, 22,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(107, 22,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //vatex  and amount due

     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     $withholdingTax = 0;

    if ($data[0]['ewtrate'] != 0) {
        $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
    }
     //zero rated sales and less withholding
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      $totaldue=$totalext-$withholdingTax;
    //vat amount and total amount due
    PDF::SetFont($fontbold, '', 14);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatamt, $decimalcurr),'', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totaldue, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
 

      PDF::SetFont($font, '', 18);
      PDF::MultiCell(764, 15, '', '');
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

     PDF::SetFont($font, '', 12);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  //ok na ito
  public function cash_sales_agntamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;
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


        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom,$arr_agt, $arr_agentext, $arr_disc]);

        for ($r = 0; $r < $maxrow; $r++) {
              PDF::SetFont($font, '', 12);
              // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
              PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(345, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              // PDF::SetTextColor(7, 13, 246);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(70, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(94, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              
              PDF::SetFont($font, '', 12);
              // PDF::SetTextColor(7, 13, 246);
              PDF::MultiCell(103, 22, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              // PDF::SetTextColor(0, 0, 0);
              PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
        $totalext= $this->coreFunctions->datareader("
               select sum(agentamt) as value from (
               select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
         if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->cash_sales_origamt_footer1($params, $data,$rowCount,$totalext);
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
      }
    }
    $vatable=0;
    $vatamt=0;

    if ($data[0]['vattype'] == 'VATABLE') {
        $vatable=$totalext/1.12;
        $vatamt=$vatable*.12;
      } 
    
    $pwddisc=0;
   


     $emptyRows = $pageLimit - $rowCount;

      // $qry = $this->return_default_query($params);
      // if($qry[0]['ext'] != 0){
      //      if($emptyRows != 0){
      //         PDF::SetFont($font, '', 12);
      //         PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //         PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //         PDF::MultiCell(610, 25, number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //         PDF::MultiCell(34, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      //         $emptyRows = $emptyRows-1;
              
      //      }else{ //walang empty na row pero may return
      //         $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
      //         $emptyRows = 0;
      //         $this->sales_invoice_header($params, $data,$next);
      //       PDF::SetFont($font, '', 12);
      //         PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //         PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //         PDF::MultiCell(610, 25, number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //         PDF::MultiCell(34, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      //         $emptyRows = $emptyRows + 12;
      //      }

      // }
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_a('');
    }
    PDF::SetFont($fontbold, '', $fontsize);
     //TOTAL SALES (Vat inclusive)
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //vatex  and amount due

     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      
    $withholdingTax = 0;

    if ($data[0]['ewtrate'] != 0) {
        $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
    }

     //zero rated sales and less withholding
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //vat amount and total amount due
    $totaldue=$totalext-$withholdingTax;
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($vatamt, $decimalcurr) ,'', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, number_format($totaldue, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    
    
      PDF::SetFont($font, '', 18);
      PDF::MultiCell(764, 15, '', '');

     
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

     PDF::SetFont($font, '', 12);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

    //ok na ito
  public function cash_sales_agntamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;
    $totalagntext=0;


      $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    $qry = $this->return_default_query($params,$data);

   // kung may ext sa qry, ibawas sa totalext
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }

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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt,  $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
                  PDF::SetFont($font, '', 12);
                  PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
                  PDF::SetFont($font, '', 10.5);
                  PDF::MultiCell(345, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::SetFont($font, '', 12);
                  PDF::SetTextColor(7, 13, 246);
                  PDF::MultiCell(70, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::SetTextColor(0, 0, 0);
                  PDF::MultiCell(94, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(105, 22, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
                $rowCount++;
        }
        // $totalext= $this->coreFunctions->datareader("
        //        select sum(ext) as value from (
        //        select sum(stock.ext) as ext from lastock as stock where stock.trno='".$data[$i]['trno']."'
        //        union all
        //        select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$data[$i]['trno']."') as a");
        $totalagntext= $this->coreFunctions->datareader("
                select sum(agentamt) as value from (
              select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
         if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->cash_sales_agent_footer2($params, $data,$rowCount,$totalext,$totalagntext);
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
      }
    }

   
   

      $emptyRows = $pageLimit - $rowCount;

     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
           if($emptyRows != 0){
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(614, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(30, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows-1;
              
           }else{ //walang empty na row pero may return
              $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
              $emptyRows = 0;
              $this->sales_invoice_header($params, $data,$next);
            PDF::SetFont($font, '', 12);
              PDF::MultiCell(20, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(614, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(30, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows + 12;
           }

      }


       $vatable=0;
      $vatamt=0;
      $agentvatable=0;
      $agentvatamt=0;

    if ($data[0]['vattype'] == 'VATABLE') {
        $vatable=$totalext/1.12;
        $vatamt=$vatable*.12;
        $agentvatable=$totalagntext/1.12;
        $agentvatamt=$agentvatable*.12;
      }
      
      $pwddisc=0;

    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_a('');
    }
  
    PDF::SetFont($fontbold, '', $fontsize);
     //TOTAL SALES (Vat inclusive)
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(107, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(105, 22, number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(107, 22, number_format($vatamt, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(105, 22, number_format($agentvatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
     PDF::SetTextColor(7, 13, 246);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(208, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(107, 22, number_format($vatable, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::SetTextColor(0, 0, 0);
     PDF::MultiCell(105, 22, number_format($agentvatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, number_format($agentvatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //vatex  and amount due

     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      $withholdingTax = 0;

    if ($data[0]['ewtrate'] != 0) {
        $withholdingTax = ($totalagntext / 1.12) * ($data[0]['ewtrate'] / 100);
    }
     //zero rated sales and less withholding
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //vat amount and total amount due
    $totaldue=$totalagntext-$withholdingTax;
     PDF::SetFont($fontbold, '', 14);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    
     PDF::MultiCell(415, 22,number_format($agentvatamt, $decimalcurr) ,'', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(105, 22, number_format($totaldue, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    

      PDF::SetFont($font, '', 18);
      PDF::MultiCell(764, 15, '', '');

     
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

     PDF::SetFont($font, '', 12);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }


     public function new_quotation_form_header($params, $data,$next)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(18, 18); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 47);
    PDF::MultiCell(764, 0, '', '');
    $username = $params['params']['user'];


     PDF::SetFont($font, '', 12);
     PDF::MultiCell(25, 20, '', '', 'R', false, 0, '', '', true);
     PDF::MultiCell(470, 20, strtoupper(isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '', '', true);
     PDF::SetFont($font, '', 13);
     PDF::MultiCell(50, 20, '', '', 'R', false, 0, '', '', true);
     PDF::SetFont($font, '', 14);
     PDF::MultiCell(222, 20, '', '', 'L', false, 1);


    PDF::SetFont($font, '', 11);
    PDF::MultiCell(0, 0, "\n\n\n");



    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(212, 20, '', '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 7);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 20, '', '', 'L', false, 0);
    PDF::MultiCell(55, 20, 'Sold to:', '', 'L', false, 0);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(437, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(50, 20, 'Date:', '', 'R', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(197, 20, $cdate, '', 'L', false, 1);

    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(760, 0, '', '');

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 20, '', '', 'L', false, 0);
    PDF::MultiCell(35, 20, "TIN:", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(225, 20, strtoupper(isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(85, 20, 'Contact No.:', '', 'l', false, 0);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(125, 20, strtoupper(isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 0);
    

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(100, 20, 'Bus. Style:', '', 'R', false, 0);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(169, 20, strtoupper(isset($data[0]['bstyle']) ? $data[0]['bstyle'] : ''), '', 'L', false, 1);

  
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(25, 20, '', '', 'L', false, 0);
    PDF::MultiCell(60, 20, "Address:", '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(440, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(60, 20, 'Terms:', '', 'L', false, 0);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(179, 20, strtoupper(isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1);

    
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(764, 0, '', '');

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];
    
    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(25, 20, '', '', 'C', false, 0);
              PDF::MultiCell(345, 20, 'DESCRIPTION', '', 'C', false, 0);
              PDF::MultiCell(60, 20, "QTY", '', 'C', false, 0, '', '', true);
              PDF::MultiCell(60, 20, 'UNIT', '', 'C', false, 0);
              PDF::MultiCell(90, 20, 'UNIT PRICE', '', 'C', false, 0);
              PDF::MultiCell(56, 20, 'DISC', '', 'C', false, 0);
              // PDF::MultiCell(94, 20, 'UNIT PRICE', '', 'C', false, 0);
              PDF::MultiCell(100, 20, 'AMOUNT', '', 'C', false, 0);
              PDF::MultiCell(28, 20, '', '', 'L', false, 1);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(25, 20, '', '', 'C', false, 0);
              PDF::MultiCell(345, 20, 'DESCRIPTION', '', 'C', false, 0);
              PDF::MultiCell(60, 20, "QTY", '', 'C', false, 0, '', '', true);
              PDF::MultiCell(60, 20, 'UNIT', '', 'C', false, 0);
              PDF::MultiCell(90, 20, 'UNIT PRICE', '', 'C', false, 0);
              PDF::MultiCell(56, 20, 'PRICE', '', 'C', false, 0);
              // PDF::MultiCell(94, 20, 'UNIT PRICE', '', 'C', false, 0);
              PDF::MultiCell(100, 20, 'AMOUNT', '', 'C', false, 0);
              PDF::MultiCell(28, 20, '', '', 'L', false, 1);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(25, 20, '', '', 'C', false, 0);
              PDF::MultiCell(345, 20, 'DESCRIPTION', '', 'C', false, 0);
              PDF::MultiCell(60, 20, "QTY", '', 'C', false, 0, '', '', true);
              PDF::MultiCell(60, 20, 'UNIT', '', 'C', false, 0);
              // PDF::MultiCell(50, 20, '', '', 'C', false, 0);
              PDF::MultiCell(141, 20, 'UNIT PRICE', '', 'C', false, 0);
              PDF::MultiCell(103, 20, 'AMOUNT', '', 'C', false, 0);
              PDF::MultiCell(30, 20, '', '', 'L', false, 1);
            break;
          case '1': // Orig. Amount and Agent Amount Show
              PDF::SetFont($fontbold, '', 13);
              PDF::MultiCell(25, 20, '', '', 'C', false, 0);
              PDF::MultiCell(345, 20, 'DESCRIPTION', '', 'C', false, 0);
              PDF::MultiCell(60, 20, "QTY", '', 'C', false, 0, '', '', true);
              PDF::MultiCell(60, 20, 'UNIT', '', 'C', false, 0);
              PDF::MultiCell(90, 20, 'UNIT PRICE', '', 'C', false, 0);
              PDF::MultiCell(75, 20, 'PRICE', '', 'C', false, 0);
              // PDF::MultiCell(94, 20, 'UNIT PRICE', '', 'C', false, 0);
              PDF::MultiCell(81, 20, 'AMOUNT', '', 'C', false, 0);
              PDF::MultiCell(28, 20, '', '', 'L', false, 1);
            break;
        }
        break;
    }
    
  }

  public function sales_invoice($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
            return $this->sales_invoice_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
           return $this->sales_invoice_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
              return $this->sales_invoice_agentamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->sales_invoice_agentamt_2($params, $data);
            break;
        }
        break;
    }
  }

    public function sales_invoice_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(18, 18); //740
 

    
    $username = $params['params']['user'];
    // $y=PDF::getY();
     PDF::SetXY(18, 110);//115
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(47, 20, '', '', 'L', false, 0);
    PDF::MultiCell(545, 20, '', '', 'L', false, 0, '', '', true);
    PDF::MultiCell(172, 20,strtoupper(isset($data[0]['docno']) ? $data[0]['docno'] : '') , '', 'L', false, 1);

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(212, 20, '', '', 'L', false, 1, '', '', true);
    
    PDF::SetXY(18, 135);//135
    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

  

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, '', '', 'L', false, 0);
    PDF::MultiCell(507, 20, '', '', 'L', false, 0, '', '', true);
    PDF::MultiCell(172, 20, $cdate, '', 'L', false, 1);


    PDF::SetXY(18, 160); //160
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(113, 20, '', '', 'L', false, 0);
    PDF::MultiCell(479, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '', '', true);
    PDF::SetFont($font, '', 12.5);
    PDF::MultiCell(172, 20,  strtoupper(isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1);

   
    PDF::SetXY(18,200);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(205, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(559, 20, strtoupper(isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false, 1);

    //  PDF::SetFont($font, '', 4);
    // PDF::MultiCell(764, 0, '', '');

    //  PDF::SetXY(18, 165);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(205, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(240, 20, strtoupper(isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);

    PDF::MultiCell(100, 20, 'Contact No.:', '', 'L', false, 0);
    PDF::MultiCell(435, 20, strtoupper(isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 1);

    //  PDF::SetXY(18, 190);
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(205, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(559, 20, strtoupper(isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1);

  
      PDF::SetXY(18, 295);

    
  }



  public function sales_invoice_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);
    $y = PDF::GetY();
    $rowCount = 0;
    $pageLimit = 13;
    $stopItems = false;
    $pagecount = 1;
     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    $qry = $this->return_default_query($params,$data);

   // kung may ext sa qry, ibawas sa totalext
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        // $itemname = $data[$i]['itemname'];
         PDF::SetFont($font, '', 10.5);
         $maxWidth = 358;
         $itemname = $data[$i]['itemname'];

        $qty = number_format($data[$i]['qty'],0);

        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamts = number_format($data[$i]['agtamt'], 2);

        $qtyy=$qty.' '.$uom;

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '11', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_disc]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);//36
          PDF::SetFont($font, '', 10.5); //
          PDF::MultiCell(345, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);//90//(isset($arr_qty[$r]) ? $arr_qty[$r] : '')
            // PDF::MultiCell(75, 25, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          // PDF::SetTextColor(0, 0, 0);

          PDF::MultiCell(90, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(91, 25,(isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117// (isset($arr_disc[$r]) ? $arr_disc[$r] : '')
          PDF::SetFont($font, '', 12);
        
          
          PDF::MultiCell(80, 25, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(50, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        
          $rowCount++;
        }
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
  

    $emptyRows = $pageLimit - $rowCount;
  
    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);

           if($emptyRows != 0){
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(345, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(53, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows-1;
              
           }else{ //walang empty na row pero may return
              $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
              $emptyRows = 0;
              $this->sales_invoice_header($params, $data);
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(345, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(53, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows + 12;
           }

      }

    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }
   
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }
    

    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13); //293 ang y
     //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(176, 25, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(147, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(176, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::MultiCell(10, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(347, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
      }


     //zero rated sales and less withholding
     PDF::MultiCell(10, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(347, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(90, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(117, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(147, 25,  number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

       $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(147, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  
     public function sales_invoice_header_2($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(18, 18); //740

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(764, 0, '', '');
    $username = $params['params']['user'];

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(47, 20, '', '', 'L', false, 0);
    PDF::MultiCell(520, 20, '', '', 'L', false, 0, '', '', true);
    PDF::MultiCell(197, 20,strtoupper(isset($data[0]['docno']) ? $data[0]['docno'] : '') , '', 'L', false, 1);

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(212, 20, '', '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 50);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, '', '', 'L', false, 0);
    PDF::MultiCell(492, 20, '', '', 'L', false, 0, '', '', true);
    PDF::MultiCell(187, 20, $cdate, '', 'L', false, 1);

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, '', '', 'L', false, 0);
    PDF::MultiCell(492, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '', '', true);
    PDF::MultiCell(187, 20,  strtoupper(isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(130, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(634, 20, strtoupper(isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false, 1);

    //  PDF::SetFont($font, '', 4);
    // PDF::MultiCell(764, 0, '', '');


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(130, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(634, 20, strtoupper(isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(764, 0, '', '');


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(130, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(634, 20, strtoupper(isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1);
   
    
    PDF::SetFont($font, '', 12);//10.3
    PDF::MultiCell(764, 0, '', '');

    // PDF::SetFont($font, '', 2);
    // PDF::MultiCell(115, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::MultiCell(125, 20, '', '', 'L', false, 0);
    // PDF::MultiCell(280, 20, '', '', 'L', false, 0);
    // PDF::MultiCell(244, 20, '', '', 'L', false, 1);
  }

   //ok na ito
  public function sales_invoice_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalagntext=0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);

    $rowCount = 0;
    $pageLimit = 13;
    $stopItems = false;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    $qry = $this->return_default_query($params,$data);

   // kung may ext sa qry, ibawas sa totalext
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }
                


    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        // $itemname = $data[$i]['itemname'];
           PDF::SetFont($font, '', 10.5);
         $maxWidth = 358;
         $itemname = $data[$i]['itemname'];

        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        // $agentamtss =$data[$i]['agtamt'];
        $agentamts = number_format($data[$i]['agtamt'], 2);
        $qtyy=$qty.' '.$uom;

         if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
         PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(345, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);//90

          PDF::MultiCell(110, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
         
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(71, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          PDF::SetTextColor(0, 0, 0);

          // PDF::MultiCell(100, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          
          PDF::MultiCell(85, 25, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(45, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_orig2_footer($params, $data,$rowCount,$totalext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
    

    $emptyRows = $pageLimit - $rowCount;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);

           if($emptyRows != 0){
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(348, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(50, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows-1;
              
           }else{ //walang empty na row pero may return
              $this->sales_invoice_orig2_footer($params, $data,$rowCount,$totalext);
              $emptyRows = 0;
              $this->sales_invoice_header($params, $data);
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(348, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(50, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows + 12;
           }

      }

        $vatable=0;
        $vatamt=0;

      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
        }




    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }

    
    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
      PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($vatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, number_format($totalext, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
     
      PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, '' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(42, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(148, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(158, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
     
       $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
      }
      

     //zero rated sales and less withholding
     PDF::MultiCell(160, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(85, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(90, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(148, 25, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    
     PDF::SetFont($font, '', 1);//10.3
     PDF::MultiCell(764, 0, '', '');
       $totaldue=$totalext-$withholdingTax;
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm


     //vat amount and total amount due
        PDF::SetFont($font, '',  13);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(148, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
   public function sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext){
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
       $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
 
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    $pageLimit = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

     $vatable=0;
    $vatamt=0;

    if ($data[0]['vattype'] == 'VATABLE') {
        $vatable=$totalext/1.12;
        $vatamt=$vatable*.12;
      }

    $emptyRows = $pageLimit - $rowCount;
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }

    
    
    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(176, 25, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(147, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(176, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::MultiCell(10, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(347, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        $withholdingTax = 0;

        if ($data[0]['ewtrate'] != 0) {
            $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
        }


     //zero rated sales and less withholding
     PDF::MultiCell(10, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(347, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(90, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(117, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(147, 25, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    
      $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      //  PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(147, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
  }

    public function sales_invoice_orig2_footer($params, $data,$rowCount,$totalext)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
    
        $border = "1px solid ";
        $font = "";
        $fontbold = "";
        $fontsize = 13;
        $pageLimit = 13;
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
          $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
          $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }

        $fontcalibri = "";
        $fontboldcalibri = "";
        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
          $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
          $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        $vatable=0;
        $vatamt=0;


      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
        }

        $emptyRows = $pageLimit - $rowCount;
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow_sales_invoice('');
        }

        
        $pwddisc=0;
        PDF::SetFont($fontbold, '', 13);
        //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, number_format($vatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(112, 25, number_format($totalext, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(148, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        //Less Vat
        
          PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(112, 25, '' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(148, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        //Vatable sales 
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(148, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


          PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(148, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        

          PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(148, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

           $withholdingTax = 0;

          if ($data[0]['ewtrate'] != 0) {
              $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
          }

        

        //zero rated sales and less withholding
          PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(147, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        
        PDF::SetFont($font, '', 1);//10.3
        PDF::MultiCell(764, 0, '', '');

        $username = $params['params']['user'];
        $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

            $totaldue=$totalext-$withholdingTax;
        //vat amount and total amount due
            PDF::SetFont($font, '',  13);
          PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', 14);
          PDF::MultiCell(148, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  }

    public function sales_invoice_agnt2_footer($params, $data,$rowCount,$totalext,$totalagntext)
    {
      $companyid = $params['params']['companyid'];
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
      $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
      $center = $params['params']['center'];
      $username = $params['params']['user'];
      $count = $page = 35;
  
      $border = "1px solid ";
      $font = "";
      $fontbold = "";
      $fontsize = 13;
      $pageLimit = 13;
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
      }

      $fontcalibri = "";
      $fontboldcalibri = "";
      if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
        $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
        $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
      }

      $vatable=0;
      $vatamt=0;
      $agentvatable=0;
      $agentvatamt=0;


      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;

          $agentvatable=$totalagntext/1.12;
          $agentvatamt=$agentvatable*.12;
        }

        $emptyRows = $pageLimit - $rowCount;
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow_sales_invoice('');
        }

        // PDF::SetFont($font, '', 20);//10.3
        // PDF::MultiCell(764, 0, '', '');
        
      $pwddisc=0;
      PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($agentvatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, number_format($totalext, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
     
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($agentvatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, '' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($agentvatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($agentvatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(148, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(148, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalagntext / 1.12) * ($data[0]['ewtrate'] / 100);
      }

     //zero rated sales and less withholding
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(148, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      
      PDF::SetFont($font, '', 1);//10.3
      PDF::MultiCell(764, 0, '', '');
        $totaldue=$totalagntext-$withholdingTax;
      $username = $params['params']['user'];
      $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
      $printeddate = $this->othersClass->getCurrentTimeStamp();
      $datetime = new DateTime($printeddate);
      $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm


      //vat amount and total amount due
          PDF::SetFont($font, '',  13);
        PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
        PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(148, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  }


  //ok na ito
  public function sales_invoice_agentamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);

    $rowCount = 0;
    $pageLimit = 13;
    $stopItems = false;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
         $itemname = $data[$i]['itemname'];

        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamts = number_format($data[$i]['agtamt'], 2);
         $qtyy=$qty.' '.$uom;

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);
    

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty,$arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(345, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(105, 25,  (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          // PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(66, 25,'', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          // PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(50, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
          
          $totalext= $this->coreFunctions->datareader("
               select sum(agentamt) as value from
               (select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
      
        if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }

     $emptyRows = $pageLimit - $rowCount;

      

    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }
    
    $pwddisc=0;
    // PDF::SetTextColor(7, 13, 246);
     PDF::SetFont($fontbold, '', 13);

     //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(176, 25, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          //  PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(147, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Less Vat
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
       PDF::MultiCell(176, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(147, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Vatable sales 
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(187, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(100, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(147, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      //  PDF::SetTextColor(0, 0, 0);

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(147, 25, '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(147, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::SetTextColor(0, 0, 0);
    
      $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
      }

     //zero rated sales and less withholding
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(187, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(100, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(147, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //vat amount and total amount due
    //   PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
    //   PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(158, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

     $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      // PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(147, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
    

    
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


    //ok na ito
  public function sales_invoice_agentamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);
    $rowCount = 0;
    $pageLimit = 13;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    $qry = $this->return_default_query($params,$data);
     // kung may ext sa qry, ibawas sa totalext
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
         $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamts = number_format($data[$i]['agtamt'], 2);
        $qtyy=$qty.' '.$uom;
        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);
    

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty,$arr_agt, $arr_agentext,$arr_amt,$arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          if ($rowCount >= $pageLimit) {
            $stopItems = true;
            break;
            }
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(345, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        
        
          PDF::MultiCell(110, 25, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      
            PDF::SetTextColor(7, 13, 246);
            PDF::MultiCell(71, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetTextColor(0, 0, 0);
            PDF::MultiCell(85, 25, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(45, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
        $totalagntext= $this->coreFunctions->datareader("
               select sum(agentamt) as value from
               (select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
   
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_agnt2_footer($params, $data,$rowCount,$totalext,$totalagntext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
    

     $emptyRows = $pageLimit - $rowCount;

     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
 


   $vatable=0;
    $vatamt=0;
     $agentvatable=0;
    $agentvatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
      
      $agentvatable = $totalagntext / 1.12;
      $agentvatamt=$agentvatable*.12;
    }




    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }
    
    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
           
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(99, 25, number_format($agentvatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(98, 25, number_format($totalext, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
           PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(148, 25,  number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Less Vat
        
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(99, 25, number_format($agentvatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
       PDF::SetTextColor(7, 13, 246);
       PDF::MultiCell(98, 25,'' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($agentvatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //  PDF::MultiCell(148, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Vatable sales 
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
       PDF::MultiCell(99, 25,'', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(98, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::MultiCell(148, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
           PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(148, 25,  number_format($agentvatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::MultiCell(148, 25,  number_format($pwddisc, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(148, 25, '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(148, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    
          $withholdingTax = 0;

        if ($data[0]['ewtrate'] != 0) {
            $withholdingTax = ($totalagntext / 1.12) * ($data[0]['ewtrate'] / 100);
        }

     //zero rated sales and less withholding
       PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
       PDF::MultiCell(99, 25,'', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(98, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(148, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

       $totaldue=$totalagntext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(148, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(54, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
















































   public function sales_invoice_origamt_1_($params, $data) //2.25.2025
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);
    $y = PDF::GetY();
    $rowCount = 0;
    $pageLimit = 13;
    $stopItems = false;
    $pagecount = 1;
     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    $qry = $this->return_default_query($params,$data);

   // kung may ext sa qry, ibawas sa totalext
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        // $itemname = $data[$i]['itemname'];
         PDF::SetFont($font, '', 10.5);
         $maxWidth = 358;
         $itemname = $data[$i]['itemname'];

        $qty = number_format($data[$i]['qty'],0);

        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamts = number_format($data[$i]['agtamt'], 2);

        $qtyy=$qty.' '.$uom;

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '11', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_disc]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);//36
          PDF::SetFont($font, '', 10.5); //
          PDF::MultiCell(360, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);//90//(isset($arr_qty[$r]) ? $arr_qty[$r] : '')
            // PDF::MultiCell(75, 25, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          // PDF::SetTextColor(0, 0, 0);

          PDF::MultiCell(90, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(91, 25,(isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117// (isset($arr_disc[$r]) ? $arr_disc[$r] : '')
          PDF::SetFont($font, '', 12);
        
          
          PDF::MultiCell(100, 25, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(15, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        
          $rowCount++;
        }
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
  

    $emptyRows = $pageLimit - $rowCount;
  
    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);

           if($emptyRows != 0){
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(378, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(20, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows-1;
              
           }else{ //walang empty na row pero may return
              $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
              $emptyRows = 0;
              $this->sales_invoice_header($params, $data);
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(378, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(20, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows + 12;
           }

      }

    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }
   
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }
    

    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13); //293 ang y
     //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(176, 25, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(180, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(176, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::MultiCell(10, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(347, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

      $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
      }


     //zero rated sales and less withholding
     PDF::MultiCell(10, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(347, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(90, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(117, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(180, 25,  number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

       $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(180, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(53, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }




     public function sales_invoice_header_2_($params, $data) //2.25.2025
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(18, 18); //740

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(764, 0, '', '');
    $username = $params['params']['user'];

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(47, 20, '', '', 'L', false, 0);
    PDF::MultiCell(520, 20, '', '', 'L', false, 0, '', '', true);
    PDF::MultiCell(197, 20,strtoupper(isset($data[0]['docno']) ? $data[0]['docno'] : '') , '', 'L', false, 1);

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(212, 20, '', '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    PDF::SetFont($font, '', 50);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, '', '', 'L', false, 0);
    PDF::MultiCell(492, 20, '', '', 'L', false, 0, '', '', true);
    PDF::MultiCell(187, 20, $cdate, '', 'L', false, 1);

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(85, 20, '', '', 'L', false, 0);
    PDF::MultiCell(492, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '', '', true);
    PDF::MultiCell(187, 20,  strtoupper(isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(764, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(130, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(634, 20, strtoupper(isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false, 1);

    //  PDF::SetFont($font, '', 4);
    // PDF::MultiCell(764, 0, '', '');


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(130, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(634, 20, strtoupper(isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 1);

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(764, 0, '', '');


    PDF::SetFont($font, '', 14);
    PDF::MultiCell(130, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(634, 20, strtoupper(isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1);
   
    
    PDF::SetFont($font, '', 12);//10.3
    PDF::MultiCell(764, 0, '', '');

    // PDF::SetFont($font, '', 2);
    // PDF::MultiCell(115, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::MultiCell(125, 20, '', '', 'L', false, 0);
    // PDF::MultiCell(280, 20, '', '', 'L', false, 0);
    // PDF::MultiCell(244, 20, '', '', 'L', false, 1);
  }

   //ok na ito
  public function sales_invoice_origamt_2_($params, $data) //2.25.2025
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalagntext=0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);

    $rowCount = 0;
    $pageLimit = 13;
    $stopItems = false;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    $qry = $this->return_default_query($params,$data);

   // kung may ext sa qry, ibawas sa totalext
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }
                


    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        // $itemname = $data[$i]['itemname'];
           PDF::SetFont($font, '', 10.5);
         $maxWidth = 358;
         $itemname = $data[$i]['itemname'];

        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        // $agentamtss =$data[$i]['agtamt'];
        $agentamts = number_format($data[$i]['agtamt'], 2);
        $qtyy=$qty.' '.$uom;

         if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
         PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(360, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);//90

          PDF::MultiCell(90, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
         
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(91, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          PDF::SetTextColor(0, 0, 0);

          // PDF::MultiCell(100, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          
          PDF::MultiCell(100, 25, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(15, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_orig2_footer($params, $data,$rowCount,$totalext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
    

    $emptyRows = $pageLimit - $rowCount;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);

           if($emptyRows != 0){
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(378, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(20, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows-1;
              
           }else{ //walang empty na row pero may return
              $this->sales_invoice_orig2_footer($params, $data,$rowCount,$totalext);
              $emptyRows = 0;
              $this->sales_invoice_header($params, $data);
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(348, 25, 'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(378, 25, ' - '.number_format($qry[0]['ext'],2), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(20, 25, '', '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
              $emptyRows = $emptyRows + 12;
           }

      }

        $vatable=0;
        $vatamt=0;

      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
        }




    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }

    
    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
      PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($vatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, number_format($totalext, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
     
      PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, '' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
     
       $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
      }
      

     //zero rated sales and less withholding
     PDF::MultiCell(160, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(85, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(90, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(180, 25, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    
     PDF::SetFont($font, '', 1);//10.3
     PDF::MultiCell(764, 0, '', '');
       $totaldue=$totalext-$withholdingTax;
     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm


     //vat amount and total amount due
        PDF::SetFont($font, '',  13);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(180, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  //2.25.2025
   public function sales_invoice_agentamt_footer1_($params, $data,$rowCount,$totalext){
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
       $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
 
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    $pageLimit = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

     $vatable=0;
    $vatamt=0;

    if ($data[0]['vattype'] == 'VATABLE') {
        $vatable=$totalext/1.12;
        $vatamt=$vatable*.12;
      }

    $emptyRows = $pageLimit - $rowCount;
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }

    
    
    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(176, 25, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(180, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(176, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::MultiCell(10, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(347, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        $withholdingTax = 0;

        if ($data[0]['ewtrate'] != 0) {
            $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
        }


     //zero rated sales and less withholding
     PDF::MultiCell(10, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(347, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(90, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(117, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(180, 25, number_format($withholdingTax, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    
      $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      //  PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(180, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
  }
   
  //2.25.2025
    public function sales_invoice_orig2_footer_($params, $data,$rowCount,$totalext)
    {
        $companyid = $params['params']['companyid'];
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $count = $page = 35;
    
        $border = "1px solid ";
        $font = "";
        $fontbold = "";
        $fontsize = 13;
        $pageLimit = 13;
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
          $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
          $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }

        $fontcalibri = "";
        $fontboldcalibri = "";
        if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
          $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
          $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
        }

        $vatable=0;
        $vatamt=0;


      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
        }

        $emptyRows = $pageLimit - $rowCount;
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow_sales_invoice('');
        }

        
        $pwddisc=0;
        PDF::SetFont($fontbold, '', 13);
        //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, number_format($vatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(112, 25, number_format($totalext, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(180, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        //Less Vat
        
          PDF::MultiCell(180, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(112, 25, '' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(70, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(180, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        //Vatable sales 
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(180, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


          PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(180, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        

          PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(180, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

           $withholdingTax = 0;

          if ($data[0]['ewtrate'] != 0) {
              $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
          }

        

        //zero rated sales and less withholding
          PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(180, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        
        PDF::SetFont($font, '', 1);//10.3
        PDF::MultiCell(764, 0, '', '');

        $username = $params['params']['user'];
        $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

            $totaldue=$totalext-$withholdingTax;
        //vat amount and total amount due
            PDF::SetFont($font, '',  13);
          PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($fontbold, '', 14);
          PDF::MultiCell(180, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  }

  //2.25.2025
    public function sales_invoice_agnt2_footer_($params, $data,$rowCount,$totalext,$totalagntext)
    {
      $companyid = $params['params']['companyid'];
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
      $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
      $center = $params['params']['center'];
      $username = $params['params']['user'];
      $count = $page = 35;
  
      $border = "1px solid ";
      $font = "";
      $fontbold = "";
      $fontsize = 13;
      $pageLimit = 13;
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
      }

      $fontcalibri = "";
      $fontboldcalibri = "";
      if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
        $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
        $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
      }

      $vatable=0;
      $vatamt=0;
      $agentvatable=0;
      $agentvatamt=0;


      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;

          $agentvatable=$totalagntext/1.12;
          $agentvatamt=$agentvatable*.12;
        }

        $emptyRows = $pageLimit - $rowCount;
        for ($i = 0; $i < $emptyRows; $i++) {
            $this->addrow_sales_invoice('');
        }

        // PDF::SetFont($font, '', 20);//10.3
        // PDF::MultiCell(764, 0, '', '');
        
      $pwddisc=0;
      PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($agentvatable, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, number_format($totalext, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Less Vat
     
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, number_format($agentvatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(112, 25, '' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($agentvatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($agentvatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalagntext / 1.12) * ($data[0]['ewtrate'] / 100);
      }

     //zero rated sales and less withholding
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(85, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(112, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      
      PDF::SetFont($font, '', 1);//10.3
      PDF::MultiCell(764, 0, '', '');
        $totaldue=$totalagntext-$withholdingTax;
      $username = $params['params']['user'];
      $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
      $printeddate = $this->othersClass->getCurrentTimeStamp();
      $datetime = new DateTime($printeddate);
      $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm


      //vat amount and total amount due
          PDF::SetFont($font, '',  13);
        PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
        PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(180, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  }


  //ok na ito
  public function sales_invoice_agentamt_1_($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);

    $rowCount = 0;
    $pageLimit = 13;
    $stopItems = false;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
         $itemname = $data[$i]['itemname'];

        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamts = number_format($data[$i]['agtamt'], 2);
         $qtyy=$qty.' '.$uom;

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);
    

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty,$arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(345, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          // PDF::MultiCell(105, 25,  (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          // PDF::SetTextColor(7, 13, 246);
           PDF::MultiCell(51, 25,'', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(105, 25,  (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(40, 25,'', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(100, 25, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          // PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(15, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
          
          $totalext= $this->coreFunctions->datareader("
               select sum(agentamt) as value from
               (select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
      
        if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_agentamt_footer1($params, $data,$rowCount,$totalext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }

     $emptyRows = $pageLimit - $rowCount;

      

    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }
    
    $pwddisc=0;
    // PDF::SetTextColor(7, 13, 246);
     PDF::SetFont($fontbold, '', 13);

     //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(176, 25, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          //  PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(180, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Less Vat
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
       PDF::MultiCell(176, 25, number_format($vatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        // PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Vatable sales 
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(187, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(100, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      //  PDF::SetTextColor(0, 0, 0);

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(180, 25, '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::SetTextColor(0, 0, 0);
    
      $withholdingTax = 0;

      if ($data[0]['ewtrate'] != 0) {
          $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
      }

     //zero rated sales and less withholding
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(187, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(100, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //vat amount and total amount due
    //   PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
    //   PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(158, 25,  number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //   PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

     $totaldue=$totalext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      // PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(180, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::SetTextColor(0, 0, 0);
    

    
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


    //ok na ito
  public function sales_invoice_agentamt_2_($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $totalagntext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->sales_invoice_header($params, $data);
    $rowCount = 0;
    $pageLimit = 13;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    $qry = $this->return_default_query($params,$data);
     // kung may ext sa qry, ibawas sa totalext
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
         $itemname = $data[$i]['itemname'];
        $qty = number_format($data[$i]['qty'], 0);
        $uom = $data[$i]['uom'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], 2);
        $agentamts = number_format($data[$i]['agtamt'], 2);
        $qtyy=$qty.' '.$uom;
        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '55', 0);
        $arr_qty = $this->reporter->fixcolumn([$qtyy], '10', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);
    

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty,$arr_agt, $arr_agentext,$arr_amt,$arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          if ($rowCount >= $pageLimit) {
            $stopItems = true;
            break;
            }
          PDF::SetFont($font, '', 11.5);
          PDF::MultiCell(18, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::SetFont($font, '', 10.5);
          PDF::MultiCell(345, 25, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(90, 25, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        
        
          PDF::MultiCell(110, 25, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      
            PDF::SetTextColor(7, 13, 246);
            PDF::MultiCell(86, 25, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetTextColor(0, 0, 0);
            PDF::MultiCell(100, 25, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(14, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
        $totalagntext= $this->coreFunctions->datareader("
               select sum(agentamt) as value from
               (select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
   
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $this->sales_invoice_agnt2_footer($params, $data,$rowCount,$totalext,$totalagntext);
            $this->sales_invoice_header($params, $data);
            $rowCount = 0; // reset counter
          }
      }
    }
    

     $emptyRows = $pageLimit - $rowCount;

     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
 


   $vatable=0;
    $vatamt=0;
     $agentvatable=0;
    $agentvatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
      
      $agentvatable = $totalagntext / 1.12;
      $agentvatamt=$agentvatable*.12;
    }




    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_sales_invoice('');
    }
    
    $pwddisc=0;
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
           
          PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(99, 25, number_format($agentvatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(98, 25, number_format($totalext, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
           PDF::SetTextColor(0, 0, 0);
          PDF::MultiCell(180, 25,  number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Less Vat
        
      PDF::MultiCell(181, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(99, 25, number_format($agentvatamt, $decimalcurr) , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
       PDF::SetTextColor(7, 13, 246);
       PDF::MultiCell(98, 25,'' , '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(96, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($agentvatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //  PDF::MultiCell(180, 25,  number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    //  //Vatable sales 
      PDF::SetTextColor(7, 13, 246);
      PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
       PDF::MultiCell(99, 25,'', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(98, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::MultiCell(180, 25,  number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
           PDF::SetTextColor(0, 0, 0);
      PDF::MultiCell(180, 25,  number_format($agentvatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetTextColor(0, 0, 0);

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      // PDF::MultiCell(180, 25,  number_format($pwddisc, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25, '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    

      PDF::MultiCell(130, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(227, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(180, 25,  '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    
          $withholdingTax = 0;

        if ($data[0]['ewtrate'] != 0) {
            $withholdingTax = ($totalagntext / 1.12) * ($data[0]['ewtrate'] / 100);
        }

     //zero rated sales and less withholding
       PDF::MultiCell(160, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
       PDF::MultiCell(99, 25,'', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(98, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(90, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(180, 25,  number_format($withholdingTax, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     $username = $params['params']['user'];
     $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
     $printeddate = $this->othersClass->getCurrentTimeStamp();
     $datetime = new DateTime($printeddate);
     $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

       $totaldue=$totalagntext-$withholdingTax;
     //vat amount and total amount due
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
      PDF::MultiCell(432, 25, 'CREATED BY:'.$createby.' PRINTED BY:'.$username."\n".$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(117, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(180, 25,  number_format($totaldue, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(20, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  


  public function cash_sales_invoice_b($params, $data)
  {

    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

    switch ($priceoption) {

      case '0': // Original Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
             return $this->bcash_sales_origamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->bcash_sales_origamt_2($params, $data);
            break;
        }
        break;

      case '1': // Agent Amount

        switch ($pricelayoutoption) {
          case '0': // Single Price Show
             return $this->bcash_sales_agntamt_1($params, $data);
            break;
          case '1': // Orig. Amount and Agent Amount Show
            return $this->bcash_sales_agntamt_2($params, $data);
            break;
        }
        break;
    }
  }


    
    public function bcash_sales_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(18, 18); //740

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n");
    PDF::SetFont($font, '', 7);
    PDF::MultiCell(764, 0, '', '');
    $username = $params['params']['user'];

    PDF::MultiCell(552, 20, '', '', 'L', false, 0, '', '', true);
    PDF::SetFont($fontcalibri, '', 13);
    PDF::MultiCell(212, 20, '', '', 'L', false, 1, '', '', true);

    $datehere = (isset($data[0]['dateid']) ? $data[0]['dateid'] : '');
    $date = new DateTime($datehere);
    $cdate = $date->format('m-d-Y');

    

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(47, 20, '', '', 'L', false, 0);
    PDF::MultiCell(545, 20, strtoupper(isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '', '', true);
    PDF::MultiCell(172, 20, $cdate, '', 'L', false, 1);

    
    PDF::SetFont($font, '', 2);
    PDF::MultiCell(764, 0, '', '');
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(100, 20, '', '', 'L', false, 0);
    PDF::MultiCell(442, 20, strtoupper(isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false, 0, '', '', true);
    PDF::MultiCell(222, 20, '', '', 'L', false, 1);

    PDF::SetFont($font, '', 2);
    PDF::MultiCell(760, 0, '', '');

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(30, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(240, 20, strtoupper(isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0);
    PDF::MultiCell(300, 20, '', '', 'L', false, 0);
    PDF::MultiCell(194, 20, '', '', 'L', false, 1);

  
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(55, 20, "", '', 'L', false, 0, '', '', true);
    PDF::MultiCell(709, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
   
    
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(764, 0, '', '');

    // PDF::SetFont($font, '', 16);
    // PDF::MultiCell(115, 20, "", '', 'L', false, 0, '', '', true);
    // PDF::MultiCell(125, 20, '', '', 'L', false, 0);
    // PDF::MultiCell(280, 20, '', '', 'L', false, 0);
    // PDF::MultiCell(244, 20, '', '', 'L', false, 1);
  }


    //ok na ito
  public function bcash_sales_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcash_sales_header($params, $data);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(764, 0, '', '');
    $rowCount = 0;
    $pageLimit = 16;
    $stopItems = false;
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '58', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          if ($rowCount >= $pageLimit) {
            $stopItems = true;
            break;
            }
          PDF::SetFont($font, '', 13);
           // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
          PDF::MultiCell(60, 26, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 26, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(415, 26, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 26, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(107, 26, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
         $totalext += $data[$i]['ext'];
         if ($stopItems) break;
      }
    }
    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }

    // var_dump($rowCount);
    $emptyRows = $pageLimit - $rowCount;
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_b('');
    }
       
    $pwddisc=0;
    $reporttype = $params['params']['dataparams']['reporttype'];
   
    // PDF::SetFont($fontbold, '', 5);
    //  PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, number_format($vatamt, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($pwddisc, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      ///zero rated addvat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

       ///less withholding tax
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::SetFont($fontbold, '', 12);
     PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     // amount due
     PDF::SetFont($fontbold, '', 13);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

   
    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

    //ok na ito
  public function bcash_sales_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcash_sales_header($params, $data);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(764, 0, '', '');
    $rowCount = 0;
    $pageLimit = 16;
    $stopItems = false;
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '58', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_agt]);

        for ($r = 0; $r < $maxrow; $r++) {
          if ($rowCount >= $pageLimit) {
            $stopItems = true;
            break;
            }
          PDF::SetFont($font, '', 13);
           // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
          PDF::MultiCell(60, 26, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 26, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(373, 26, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(70, 26, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 13);
          PDF::MultiCell(62, 26, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(107, 26, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
         $totalext += $data[$i]['ext'];
         if ($stopItems) break;
      }
    }
    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }

    // var_dump($rowCount);
    $emptyRows = $pageLimit - $rowCount;
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_b('');
    }
       
    $pwddisc=0;
    $reporttype = $params['params']['dataparams']['reporttype'];
   
    // PDF::SetFont($fontbold, '', 5);
    //  PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($vatamt, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, number_format($vatable, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, number_format($vatamt, $decimalcurr), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($pwddisc, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      ///zero rated addvat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

       ///less withholding tax
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::SetFont($fontbold, '', 12);
     PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     // amount due
     PDF::SetFont($fontbold, '', 13);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

   
    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

      //ok na ito
  public function bcash_sales_agntamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcash_sales_header($params, $data);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(764, 0, '', '');
    $rowCount = 0;
    $pageLimit = 16;
    $stopItems = false;
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
        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '58', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          if ($rowCount >= $pageLimit) {
            $stopItems = true;
            break;
            }
          PDF::SetFont($font, '', 13);
           // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
          PDF::MultiCell(60, 26, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 26, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(415, 26, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(90, 26, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(107, 26, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
         $totalext += $agentext;
         if ($stopItems) break;
      }
    }
    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }

    // var_dump($rowCount);
    $emptyRows = $pageLimit - $rowCount;
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_b('');
    }
       
    $pwddisc=0;
    $reporttype = $params['params']['dataparams']['reporttype'];
   
    // PDF::SetFont($fontbold, '', 5);
    //  PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      ///zero rated addvat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

       ///less withholding tax
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::SetFont($fontbold, '', 12);
     PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     // amount due
     PDF::SetFont($fontbold, '', 13);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

   
    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

    //ok na ito
  public function bcash_sales_agntamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->bcash_sales_header($params, $data);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
    // PDF::SetFont($font, '', 1);
    // PDF::MultiCell(764, 0, '', '');
    $rowCount = 0;
    $pageLimit = 16;
    $stopItems = false;
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '58', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt,$arr_agt,$arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {
          if ($rowCount >= $pageLimit) {
            $stopItems = true;
            break;
            }
          PDF::SetFont($font, '', 13);
           // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
          PDF::MultiCell(60, 26, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 26, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(373, 26, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::SetFont($font, '', 12);
          PDF::MultiCell(70, 26, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          PDF::SetFont($font, '', 13);
          PDF::MultiCell(62, 26, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(107, 26, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
         $totalext += $agentext;
         if ($stopItems) break;
      }
    }
    $vatable=0;
    $vatamt=0;

   if ($data[0]['vattype'] == 'VATABLE') {
      $vatable=$totalext/1.12;
      $vatamt=$vatable*.12;
    }

    // var_dump($rowCount);
    $emptyRows = $pageLimit - $rowCount;
    for ($i = 0; $i < $emptyRows; $i++) {
        $this->addrow_cashsales_b('');
    }
       
    $pwddisc=0;
    $reporttype = $params['params']['dataparams']['reporttype'];
   
    // PDF::SetFont($fontbold, '', 5);
    //  PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
  
     PDF::SetFont($fontbold, '', 13);
     //TOTAL SALES (Vat inclusive)
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     //Less Vat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     //Vatable sales 
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, number_format($vatable, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '' , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(120, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(297, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


      ///zero rated addvat
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

       ///less withholding tax
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(98, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    //  PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     PDF::SetFont($fontbold, '', 12);
     PDF::MultiCell(764, 0, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


     // amount due
     PDF::SetFont($fontbold, '', 13);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(60, 26, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(2, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(415, 26, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(94, 26, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(98, 26, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(35, 26, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

   
    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



    private function addrow_cashsales_a($border)
    {
          $font = "";
          $fontbold = "";
          $fontsize = 12;
          if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
          }
   
          PDF::SetFont($font, '', 12);
           // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 22,'', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    }


   private function addrow_cashsales_b($border)
    {
          $font = "";
          $fontbold = "";
          if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
          }
          PDF::SetFont($font, '', 13);
          PDF::MultiCell(60, 22,'', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    }


     private function addrow_sales_invoice($border)
    {
        $font = "";
        $fontbold = "";
        $fontsize = 12;
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
          $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
          $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }
   
          PDF::SetFont($font, '', 13);
          PDF::MultiCell(15, 25, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
          PDF::MultiCell(342, 25,'', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(100, 25, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);//90
          PDF::MultiCell(107, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);//117
          PDF::MultiCell(158, 25, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(42, 25, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
    }


        public function qf_layout($params, $data)
    {

      $priceoption = $params['params']['dataparams']['isassettag'];
      $pricelayoutoption = $params['params']['dataparams']['paidstatus'];

      switch ($priceoption) {

        case '0': // Original Amount

          switch ($pricelayoutoption) {
            case '0': // Single Price Show
              return $this->qf_origamt_1($params, $data);
              break;
            case '1': // Orig. Amount and Agent Amount Show
              return $this->qf_origamt_2($params, $data);
              break;
          }
          break;

        case '1': // Agent Amount

          switch ($pricelayoutoption) {
            case '0': // Single Price Show
              return $this->qf_agntamt_1($params, $data);
              break;
            case '1': // Orig. Amount and Agent Amount Show
              return $this->qf_agntamt_2($params, $data);
              break;
          }
          break;
      }
    }


     public function qf_origamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;

     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_disc]);

        for ($r = 0; $r < $maxrow; $r++) {
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(25, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
              PDF::MultiCell(345, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
         
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(90, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(56, 22, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              // PDF::MultiCell(94, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 22, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
        //  $totalext += $data[$i]['ext'];
        
      //  $totalext= $this->coreFunctions->datareader("
      //         select sum(ext) as value from (
      //         select sum(stock.ext) as ext from lastock as stock where stock.trno='".$data[$i]['trno']."'
      //          union all
      //          select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$data[$i]['trno']."') as a");
        if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->qf_origamt_footer1($params, $data,$rowCount,$totalext);
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
        
      }
    }
    

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(25, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
              PDF::MultiCell(345, 22,'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(60, 22,'', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::MultiCell(90, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(56, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(100, 22,  ' - '.number_format($qry[0]['ext'], $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
      }

    //   $vatable=0;
    //   $vatamt=0;

    // if ($data[0]['vattype'] == 'VATABLE') {
    //     $vatable=$totalext/1.12;
    //     $vatamt=$vatable*.12;
    //   }
    
        $pwddisc=0;
          PDF::SetFont($fontbold, '', $fontsize);
          //TOTAL SALES (Vat inclusive)
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(509, 22, 'GRAND TOTAL: ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          //  PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

        

          PDF::SetFont($font, '', 18);
          PDF::MultiCell(764, 15, '', '');

          
          $username = $params['params']['user'];
          $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
          $printeddate = $this->othersClass->getCurrentTimeStamp();
          $datetime = new DateTime($printeddate);
          $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

          PDF::SetFont($font, '', 12);
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

   public function qf_origamt_footer1($params, $data,$rowCount,$totalext)
   {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $reporttype = $params['params']['dataparams']['reporttype'];
    $priceoption = $params['params']['dataparams']['isassettag'];
    $pricelayoutoption = $params['params']['dataparams']['paidstatus'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    $pageLimit = 19;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

      $vatable=0;
    $vatamt=0;

    if ($data[0]['vattype'] == 'VATABLE') {
        $vatable=$totalext/1.12;
        $vatamt=$vatable*.12;
      }
    
     $pwddisc=0;
  
      PDF::SetFont($fontbold, '', $fontsize);
      //TOTAL SALES (Vat inclusive)
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);

      PDF::MultiCell(509, 22, 'GRAND TOTAL: ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //  PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
   
      PDF::SetFont($font, '', 18);
      PDF::MultiCell(764, 15, '', '');

      
      $username = $params['params']['user'];
      $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
      $printeddate = $this->othersClass->getCurrentTimeStamp();
      $datetime = new DateTime($printeddate);
      $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
      PDF::SetTextColor(0, 0, 0);
      PDF::SetFont($font, '', 12);
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
   }


     //ok na ito
  public function qf_origamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
     $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;
    $totalagntext=0;

     $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }
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
        $agentamts = number_format($data[$i]['agtamt'], 2);
         if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
        } else {
          $agentext = 0;
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '5', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);
         $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext, $arr_agt]);

        for ($r = 0; $r < $maxrow; $r++) {

              PDF::SetFont($font, '', 10.5);
              PDF::MultiCell(25, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
              PDF::MultiCell(345, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
              PDF::SetFont($font, '', 12); 
              PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
             
              PDF::MultiCell(90, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetFont($font, '', 12);
              PDF::SetTextColor(7, 13, 246);
              PDF::MultiCell(56, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::SetTextColor(0, 0, 0);

              // PDF::MultiCell(94, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);

              PDF::MultiCell(100, 22, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
              PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
       
        //  $totalext= $this->coreFunctions->datareader("
        //       select sum(ext) as value from (
        //       select sum(stock.ext) as ext from lastock as stock where stock.trno='".$data[$i]['trno']."'
        //        union all
        //        select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$data[$i]['trno']."') as a");
          if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->qf_origamt_footer2($params, $data,$rowCount,$totalext);
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
      }
    }
    

  
        $trno = $params['params']['dataid'];
        $totalext= $this->coreFunctions->datareader("
                  select sum(ext) as value from
                  (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
                  union all
                  select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
        
        $qry = $this->return_default_query($params,$data);
        if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
          $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
                PDF::SetFont($font, '', 10.5);
                PDF::MultiCell(25, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
                PDF::MultiCell(345, 22,'RETURN', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', 12);
                PDF::MultiCell(60, 22,'', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::SetFont($font, '', 12);
                PDF::MultiCell(90, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(56, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(100, 22,  ' - '.number_format($qry[0]['ext'], $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
        }
    
         $pwddisc=0;
      
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        //  PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 22, 'GRAND TOTAL:', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(7, 13, 246);
        PDF::MultiCell(94, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        //  PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0); //636
        // PDF::MultiCell(75, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

 
          PDF::SetFont($font, '', 18);
          PDF::MultiCell(764, 15, '', '');
        $username = $params['params']['user'];
        $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

        PDF::SetFont($font, '', 12);
        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


   public function qf_origamt_footer2($params, $data,$rowCount,$totalext){
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
     $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
     $pageLimit = 19;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }

     $vatable=0;
     $vatamt=0;

      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
        }

  
    
       $pwddisc=0;
   
      
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        //  PDF::MultiCell(415, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(420, 22, 'GRAND TOTAL:', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(7, 13, 246);
        PDF::MultiCell(94, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        //  PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::SetTextColor(0, 0, 0);
        // PDF::MultiCell(75, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(100, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

     

          PDF::SetFont($font, '', 18);
          PDF::MultiCell(764, 15, '', '');
        $username = $params['params']['user'];
        $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
        $printeddate = $this->othersClass->getCurrentTimeStamp();
        $datetime = new DateTime($printeddate);
        $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

        PDF::SetFont($font, '', 12);
        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
        PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
   }


   
  //ok na ito
  public function qf_agntamt_1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;
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


        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom,$arr_agt, $arr_agentext, $arr_disc]);

        for ($r = 0; $r < $maxrow; $r++) {
                  PDF::SetFont($font, '', 10.5);
                  PDF::MultiCell(25, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
                  PDF::MultiCell(365, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::SetFont($font, '', 12);
                  PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                  PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
               
                  PDF::MultiCell(123, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
                
            
            PDF::SetFont($font, '', 12);
          
            PDF::MultiCell(103, 22, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          
            PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }
        // $totalext += $agentext;
        $totalext= $this->coreFunctions->datareader("
               select sum(agentamt) as value from (
               select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
         if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->qf_origamt_footer1($params, $data,$rowCount,$totalext); 
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
      }
    }
    $vatable=0;
    $vatamt=0;

    if ($data[0]['vattype'] == 'VATABLE') {
        $vatable=$totalext/1.12;
        $vatamt=$vatable*.12;
      } 

    
    $pwddisc=0;
   
      PDF::SetFont($fontbold, '', $fontsize);
      //  //TOTAL SALES (Vat inclusive)
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(2, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(509, 22, 'GRAND TOTAL: ', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      //  PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(103, 22, number_format($totalext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);



    
        PDF::SetFont($font, '', 18);
        PDF::MultiCell(764, 15, '', '');

      
      $username = $params['params']['user'];
      $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
      $printeddate = $this->othersClass->getCurrentTimeStamp();
      $datetime = new DateTime($printeddate);
      $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

      PDF::SetFont($font, '', 12);
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


    //ok na ito
  public function qf_agntamt_2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    
    $reporttype = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $border = "1px solid ";
    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    }

    $fontcalibri = "";
    $fontboldcalibri = "";
    if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
      $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
      $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
    }
    $this->headers($params, $data,$next=0);

    PDF::MultiCell(0, 0, "\n");
    // PDF::setCellPadding($left, $top, $right, $bottom);
  

    PDF::SetFont($font, '', 1.5);
    PDF::MultiCell(764, 0, '', '');
    // PDF::setCellPaddings(0, 11, 0, 9); //important !
    $rowCount = 0;
    $pageLimit = 19;
    $stopItems = false;
    $totalagntext=0;

    $trno = $params['params']['dataid'];
    $totalext= $this->coreFunctions->datareader("
               select sum(ext) as value from
               (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
               union all
               select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
    
    $qry = $this->return_default_query($params,$data);
    if (!empty($qry) && isset($qry[0]['ext']) && floatval($qry[0]['ext']) != 0) {
       $totalext = $totalext - (isset($qry[0]['ext']) ? $qry[0]['ext'] : 0);
    }
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

        $agentamts = number_format($data[$i]['agtamt'], 2);

        if ($agentamts != 0) {
          $agentext = $data[$i]['agtamt'] * $data[$i]['qty'];
          $genttl = number_format($agentext, 2);
        } else {
          $agentext = 0;
          $genttl =  number_format($agentext, 2);
        }

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '60', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '20', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

        $arr_agt = $this->reporter->fixcolumn([$agentamts], '20', 0);
        $arr_agentext = $this->reporter->fixcolumn([$genttl], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_qty, $arr_uom, $arr_amt,  $arr_agt, $arr_agentext]);

        for ($r = 0; $r < $maxrow; $r++) {

       
            PDF::SetFont($font, '', 10.5);
            PDF::MultiCell(25, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, '', true);
                // PDF::SetFont($font, '', 11);
            PDF::MultiCell(345, 22, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(60, 22, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(60, 22, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(90, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetTextColor(7, 13, 246);
            PDF::MultiCell(75, 22, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetTextColor(0, 0, 0);
            // PDF::MultiCell(94, 22, (isset($arr_agt[$r]) ? $arr_agt[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(81, 22, (isset($arr_agentext[$r]) ? $arr_agentext[$r] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
          $rowCount++;
        }

        
        // $totalext= $this->coreFunctions->datareader("
        //        select sum(ext) as value from (
        //        select sum(stock.ext) as ext from lastock as stock where stock.trno='".$data[$i]['trno']."'
        //        union all
        //        select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$data[$i]['trno']."') as a");
        $totalagntext= $this->coreFunctions->datareader("
                select sum(agentamt) as value from (
              select sum(stock.agentamt * stock.isqty) as agentamt from lastock as stock where stock.trno='".$data[$i]['trno']."'
               union all
               select sum(stock.agentamt * stock.isqty) as agentamt from glstock as stock  where stock.trno='".$data[$i]['trno']."') as b");
         if ($rowCount >= $pageLimit && $i < count($data) - 1) {
            $next=1;
            $this->qf_agent_footer2($params, $data,$rowCount,$totalext,$totalagntext);
            $this->headers($params, $data,$next);
            $rowCount = 0; // reset counter
          }
      }
    }

       $trno = $params['params']['dataid'];
        $totalext= $this->coreFunctions->datareader("
                  select sum(ext) as value from
                  (select sum(stock.ext) as ext from lastock as stock where stock.trno='".$trno."'
                  union all
                  select sum(stock.ext) as ext from glstock as stock  where stock.trno='".$trno."') as a");
      

         $pwddisc=0;
          PDF::SetFont($fontbold, '', $fontsize);
        //TOTAL SALES (Vat inclusive)
           PDF::MultiCell(25, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(345, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
             PDF::MultiCell(120, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 22, 'GRAND TOTAL:', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(7, 13, 246);
          PDF::MultiCell(75, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::SetTextColor(0, 0, 0);
          // PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(81, 22, number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
     
    

            PDF::SetFont($font, '', 18);
            PDF::MultiCell(764, 15, '', '');

          
          $username = $params['params']['user'];
          $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
          $printeddate = $this->othersClass->getCurrentTimeStamp();
          $datetime = new DateTime($printeddate);
          $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

          PDF::SetFont($font, '', 12);
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
          PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


            return PDF::Output($this->modulename . '.pdf', 'S');
  }


    public function qf_agent_footer2($params, $data,$rowCount,$totalext,$totalagntext){

      $companyid = $params['params']['companyid'];
      $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
      $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
      $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
      
      $reporttype = $params['params']['dataparams']['reporttype'];
      $center = $params['params']['center'];
      $username = $params['params']['user'];
      $count = $page = 35;
      $border = "1px solid ";
      $font = "";
      $fontbold = "";
      $fontsize = 13;
      $pageLimit = 19;
      if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
        $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
        $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
      }

      $fontcalibri = "";
      $fontboldcalibri = "";
      if (Storage::disk('sbcpath')->exists('/fonts/calibri.ttf')) {
        $fontcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibri.ttf');
        $fontboldcalibri = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/calibriB.ttf');
      }

      $vatable=0;
      $vatamt=0;
      $agentvatable=0;
      $agentvatamt=0;

      if ($data[0]['vattype'] == 'VATABLE') {
          $vatable=$totalext/1.12;
          $vatamt=$vatable*.12;
          $agentvatable=$totalagntext/1.12;
          $agentvatamt=$agentvatable*.12;
        }
        
        $pwddisc=0;
      
       
            PDF::SetFont($fontbold, '', $fontsize);
          //TOTAL SALES (Vat inclusive)
            PDF::MultiCell(25, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(345, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
             PDF::MultiCell(120, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(90, 22, 'GRAND TOTAL:', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetTextColor(7, 13, 246);
            PDF::MultiCell(75, 22, number_format($totalext, $decimalcurr), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            //  PDF::MultiCell(100, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::SetTextColor(0, 0, 0);
            // PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(81, 22, number_format($totalagntext, $decimalcurr) , '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(28, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);
     
  

              PDF::SetFont($font, '', 18);
              PDF::MultiCell(764, 15, '', '');

            
            $username = $params['params']['user'];
            $createby=strtoupper(isset($data[0]['createby']) ? $data[0]['createby'] : '');
            $printeddate = $this->othersClass->getCurrentTimeStamp();
            $datetime = new DateTime($printeddate);
            $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm

            PDF::SetFont($font, '', 12);
            PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(60, 22, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(17, 22, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(400, 22, 'CREATED BY:'.$createby.' PRINTED BY:'.$username.' '.$formattedDate, '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(94, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(103, 22, '', '', 'R', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            PDF::MultiCell(30, 22, '', '', 'R', false, 1, '', '', true, 0, false, true, 0, 'B', true);


    }

  

}
