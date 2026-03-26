<?php

namespace App\Http\Classes\modules\modulereport\cdo;

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

class mj
{
  private $modulename = "Sales Journal";
  private $reportheader;
  private $commonsbc;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $fieldClass;
  private $reporter;
  private $logger;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'noted', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      //['label' => 'Excel', 'value' => 'excel', 'color' => 'red']
    ]);


    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => 0, 'color' => 'red'],
      ['label' => 'For Customer', 'value' => 1, 'color' => 'red'],
      ['label' => 'Reconstructed', 'value' => 2, 'color' => 'red']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $signatories = $this->othersClass->getSignatories($config);
    $prepared = '';
    $noted = '';
    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'prepared':
          $prepared = $value->fieldvalue;
          break;
        case 'noted':
          $noted = $value->fieldvalue;
          break;
      }
    }

    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        0 as reporttype,
        '" . $prepared . "' as prepared,
        '" . $noted . "' as noted"
    );
  }

  // public function report_default_query_MAIN($config)
  // {
  //   $trno = $config['params']['dataid'];
  //   $reporttype = $config['params']['dataparams']['reporttype'];
  //   $doc = "head.doc = 'mj'";
  //   $join = "";
  //   $head = " and head.trno = $trno";
  //   $addfield = ", head.creditinfo as ci, concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''), 
  //   ifnull(sot.color, '')) as model,  mode.name as modeofsales,head.crref as cr, ifnull(hinfo.downpayment, 0) as downpayment ";

  //   if ($reporttype == 2) { //reconstruction
  //     $doc = "head.doc = 'gj'";
  //     $join = "left join cntnum as cn on cn.trno=head.trno";
  //     $head = " and cn.recontrno= $trno";
  //     $addfield = ", '' as ci,
  //     (select concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''),
  //     ifnull(sot.color, '')) as model from glhead as head
  //     left join glstock as stock on stock.trno=head.trno
  //     left join item as item on item.itemid=stock.itemid where head.trno=cn.recontrno) as model,
  //     (select mm.name from glhead as head
  //     left join mode_masterfile as mm on mm.line=head.modeofsales where head.trno=cn.recontrno) as modeofsales,
  //     ifnull(cn.recontrno,0) as recontrno,(select head.crref from glhead as head where head.trno=cn.recontrno ) as cr,
  //     (select ifnull(hinfo.downpayment, 0) as downpayment from glhead as head
  //        left join hcntnuminfo as hinfo on hinfo.trno = head.trno where head.trno=cn.recontrno) as downpayment";
  //   }

  //   $query = "select client.clientname, head.ourref as csi, head.yourref as dr,
  //   client.tel2 as contactno, head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
  //   ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(hinfo.penalty, 0) as penalty,
  //   stock.amt as srp, ifnull(hinfo.rebate, 0) as rebate, '' as due, head.dateid as hdate,
  //   ifnull((hinfo.fma1 + hinfo.rebate), 0) as current $addfield
  //   from lahead as head
  //   left join lastock as stock on stock.trno = head.trno
  //   left join terms on terms.terms = head.terms
  //   left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
  //   left join item on item.itemid = stock.itemid
  //   left join client on client.client = head.client
  //   left join cntnuminfo as hinfo on hinfo.trno = head.trno
  //   left join mode_masterfile as mode on mode.line = head.modeofsales  $join
  //   where $doc  $head
  //   union all
  //   select client.clientname, head.ourref as csi, head.yourref as dr, client.tel2 as contactno,
  //   head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
  //   ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(hinfo.penalty, 0) as penalty,
  //   stock.amt as srp, ifnull(hinfo.rebate, 0) as rebate,
  //   (select date_format(ar.dateid, '%M %d, %Y') as dateid from arledger as ar where head.trno = ar.trno limit 1) as due,  head.dateid as hdate,
  //   ifnull((hinfo.fma1 + hinfo.rebate), 0) as current $addfield
  //   from glhead as head
  //   left join glstock as stock on stock.trno = head.trno
  //   left join terms on terms.terms = head.terms
  //   left join client on client.clientid = head.clientid
  //   left join hcntnuminfo as hinfo on hinfo.trno = head.trno
  //   left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
  //   left join item on item.itemid = stock.itemid
  //   left join mode_masterfile as mode on mode.line = head.modeofsales  $join
  //   where  $doc   $head ";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $doc = "head.doc = 'mj'";
    // $join = "";
    $head = " and head.trno = $trno";
    // $addfield = ", head.creditinfo as ci, concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''), 
    // ifnull(sot.color, '')) as model,  mode.name as modeofsales,head.crref as cr, ifnull(hinfo.downpayment, 0) as downpayment ";

    switch ($reporttype) {
      case 2: //reconstruction
        $query = "select client.clientname, head.ourref as csi, head.yourref as dr, client.tel2 as contactno,
      head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
      ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(hinfo.penalty, 0) as penalty,
      stock.ext as srp, ifnull(sjinfo.rebate, 0) as rebate,
      (select date_format(ar.dateid, '%M %d, %Y') as dateid from arledger as ar where head.trno = ar.trno limit 1) as due,  sjhead.dateid as hdate,
      ifnull((hinfo.fma1 + sjinfo.rebate), 0) as current ,
       '' as ci,
      concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''),
      ifnull(sot.color, ''))  as model,
      mode.name as modeofsales,
      ifnull(cn.recontrno,0) as recontrno,head.crref as cr,
      sjinfo.downpayment as downpayment

      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join terms on terms.terms = head.terms
      left join client on client.clientid = head.clientid
      left join hcntnuminfo as hinfo on hinfo.trno = head.trno
      left join cntnum as cn on cn.trno=head.trno
      left join hcntnuminfo as sjinfo on sjinfo.trno = $trno
      left join glstock as sjstock on sjstock.trno = $trno
      left join glhead as sjhead on sjhead.trno = $trno
      left join serialout as sot on sot.trno = sjstock.trno and sot.line = sjstock.line
      left join item on item.itemid = sjstock.itemid
      left join mode_masterfile as mode on mode.line = sjhead.modeofsales  
      where  head.doc = 'gj'  and cn.recontrno= $trno ";
        break;

      case 1: //for customer 

        $query = "select client.clientname, head.ourref as csi, head.yourref as dr,
      client.tel2 as contactno, head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
      ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(hinfo.penalty, 0) as penalty,
      stock.amt as srp, ifnull(hinfo.rebate, 0) as rebate, '' as due, head.dateid as hdate,
      ifnull((hinfo.fma1 + hinfo.rebate), 0) as current,  head.creditinfo as ci, concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''), 
      ifnull(sot.color, '')) as model,  mode.name as modeofsales,head.crref as cr, ifnull(hinfo.downpayment, 0) as downpayment 
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join terms on terms.terms = head.terms
      left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
      left join item on item.itemid = stock.itemid
      left join client on client.client = head.client
      left join cntnuminfo as hinfo on hinfo.trno = head.trno
      left join mode_masterfile as mode on mode.line = head.modeofsales  
      where $doc  $head
      union all
      select client.clientname, head.ourref as csi, head.yourref as dr, client.tel2 as contactno,
      head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
      ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(hinfo.penalty, 0) as penalty,
      stock.amt as srp, ifnull(hinfo.rebate, 0) as rebate,
      (select date_format(ar.dateid, '%M %d, %Y') as dateid from arledger as ar left join coa on coa.acnoid = ar.acnoid where head.trno = ar.trno and coa.alias in ('AR1','AR2')  limit 1) as due,  head.dateid as hdate,
      ifnull((hinfo.fma1 + hinfo.rebate), 0) as current, head.creditinfo as ci, concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''), 
      ifnull(sot.color, '')) as model,  mode.name as modeofsales,head.crref as cr, ifnull(hinfo.downpayment, 0) as downpayment 
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join terms on terms.terms = head.terms
      left join client on client.clientid = head.clientid
      left join hcntnuminfo as hinfo on hinfo.trno = head.trno
      left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
      left join item on item.itemid = stock.itemid
      left join mode_masterfile as mode on mode.line = head.modeofsales  
      where  $doc   $head ";

        break;
      case 0: //default 
        $query = "select head.dateid, head.clientname, client.tin, ifnull(terms.terms, '') as terms, head.ourref as csi, head.yourref as dr, client.tel2 as contactno, head.address, head.rem, stock.disc,
      ifnull(sot.serial, '') as serial, ifnull(sot.chassis, '') as chassis, ifnull(sot.color, '') as color,
      stock.isamt as srp, head.creditinfo as ci, item.itemname as model, mode.name as modeofsales, head.crref as cr, (stock.ext) as netsales, 
      if(hinfo.downpayment = 0, '', hinfo.downpayment) as downpayment
      from lahead as head
      left join lastock as stock on stock.trno = head.trno
      left join terms on terms.terms = head.terms
      left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
      left join item on item.itemid = stock.itemid
      left join client on client.client = head.client
      left join cntnuminfo as hinfo on hinfo.trno = head.trno
      left join mode_masterfile as mode on mode.line = head.modeofsales
      where $doc $head
      union all
      select head.dateid, head.clientname, client.tin, ifnull(terms.terms, '') as terms, head.ourref as csi, head.yourref as dr, client.tel2 as contactno, head.address, head.rem, stock.disc, 
      ifnull(sot.serial, '') as serial, ifnull(sot.chassis, '') as chassis, ifnull(sot.color, '') as color,
      stock.isamt as srp, head.creditinfo as ci, item.itemname as model, mode.name as modeofsales, head.crref as cr, (stock.ext) as netsales, 
      if(hinfo.downpayment = 0, '', hinfo.downpayment) as downpayment
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join terms on terms.terms = head.terms
      left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
      left join item on item.itemid = stock.itemid
      left join client on client.clientid = head.clientid
      left join hcntnuminfo as hinfo on hinfo.trno = head.trno
      left join mode_masterfile as mode on mode.line = head.modeofsales
      where $doc $head";
        break;
    }

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    ini_set('memory_limit', '-1');
    $reporttype = $params['params']['dataparams']['reporttype'];
    $type = $params['params']['dataparams']['print'];

    switch ($type) {
      case 'PDFM':
        switch ($reporttype) {
          case 0: // default
            $result = $this->default_sj_PDF($params, $data);
            break;
          case 1: // for customer
            $result = $this->default_for_Customer_PDF($params, $data);
            break;
          case 2: // for reconstruction
            $result = $this->reconlayout($params, $data);
            break;
        }
        break;

      case 'excel':
        switch ($reporttype) {
          case 0: // default
            $result = $this->default_sj_layout($params, $data);
            break;
          case 1: // for customer
            // $result = $this->default_for_Customer($params, $data);
            $mode = empty($data) ? 0 : $data[0]['modeofsales'];

            if ($mode != 'INHOUSE INSTALLMENT') {

              $result = $this->default_for_Customer2($params, $data);
            } else {
              $result = $this->default_for_Customer1($params, $data);
            }
            break;
          case 2: // for reconstruction
            $result = $this->default_reconlayout($params, $data);
            break;
        }
        break;
    }


    return $result;
  }

  public function default_sj_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    // $data2 = $this->getdetail($params, $data);
    $totalinterest = 0;
    $totalnetsales = 0;

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->report_default_header($params, $data);

    for ($i = 0; $i < count($data); $i++) {

      $dateid = $data[$i]['dateid'];
      $rem = $data[$i]['rem'];
      $disc = $disc = $data[$i]['disc'];
      if ($disc != '') {
        if ($this->commonsbc->right($data[$i]['disc'], 1) != '%') {
          $disc = number_format($data[$i]['disc'], 2);
        }
      }
      $srp = number_format($data[$i]['srp'], $decimalcurr);
      $discOrDownpayment = ($data[$i]['modeofsales'] != 'CASH') ? number_format($data[$i]['downpayment'], 2) : $disc;
      $termsOrNetsales = ($data[$i]['modeofsales'] != 'CASH') ? $data[$i]['terms'] : number_format($data[$i]['netsales'], $decimalcurr);
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($dateid, '100px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($rem, '300px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($srp, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($discOrDownpayment, '100px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($termsOrNetsales, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $totalinterest += $data[$i]['srp'];
      $totalnetsales += $data[$i]['netsales'];

      if ($this->reporter->linecounter == $page) {

        $str .= $this->reporter->page_break();

        // <--- Header
        $str .= $this->report_default_header($params, $data);

        // $str .= $this->reporter->endrow();
        $page = $page + $count;
      } //end if
    } //end for
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100px', null, false, '1px dotted ', 'T', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '300px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalinterest, $decimal), '150px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '150px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(($data[0]['modeofsales'] != 'CASH') ? '' : number_format($totalnetsales, $decimalcurr), '150px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By: ', '100px', null, false, '', 'T', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('' . $params['params']['dataparams']['prepared'], '150px', null, false, '', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Noted By: ', '150px', null, false, '', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('' . $params['params']['dataparams']['noted'], '150px', null, false, '', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '150px', null, false, '', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_reconlayout($params, $data)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $recon = empty($data) ? 0 : $data[0]['recontrno'];
    $mode = empty($data) ? 0 : $data[0]['modeofsales'];
    if ($recon == '' || $recon == 0) {
      return $this->no_recon_ex();
    }

    if ($mode != 'INHOUSE INSTALLMENT') {
      return $this->default_reconlayout2($params, $data);
    } else {
      return $this->default_reconlayout1($params, $data);
    }
  }

  public function default_reconlayout1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $data2 = $this->getdetail2($params, $data);
    $totalinterest = 0;
    $totalprincipal = 0;

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->report_default_customer($params, $data);
    if (!empty($data2)) {
      $postdate = '';
      $rem = '';
      $crno = '';
      $ma = 0;
      $rebate = 0;
      $penalty = '';
      $current = 0;
      $totalar = 0;
      $initialMonth = null;
      for ($i = 0; $i < count($data2); $i++) {

        $crno = isset($data[$i]['cr']) && $data[$i]['cr'] != '' ? $data[$i]['cr'] : '';
        $downpayment = isset($data[$i]['downpayment']) ? $data[$i]['downpayment'] : '';
        $rebate = isset($data[$i]['rebate']) ? $data[$i]['rebate'] : 0;
        $penalty = isset($data[$i]['penalty']) ? $data[$i]['penalty'] : 0;
        $terms = isset($data[$i]['days']) ? $data[$i]['days'] : '';

        if ($downpayment != 0 && $downpayment != '') {
          $rem = "Downpayment";
          $postdate = $data[$i]['hdate'];
          $ma = $data[$i]['downpayment'];
        } else {
          $rem = $data2[0]['rem'];
          $postdate = isset($data[$i]['due']) ? $data[$i]['due'] : '';
          $ma = isset($data[$i]['ma']) ? $data[$i]['ma'] : 0;
        }

        $current =  isset($data[$i]['current']) ? $data[$i]['current'] : 0;
        $totalar = $current * $terms;
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($postdate, '100px', null, false, $border, '', 'L', $font, '11', '');
        $str .= $this->reporter->col($rem, '140px', null, false, $border, '', 'L', $font, '11', '');
        $str .= $this->reporter->col($crno, '80px', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->col(number_format($ma, 2), '80px', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->col(number_format($rebate, 2), '80px', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->col($penalty, '80px', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->col(number_format($current, 2), '80px', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->col(number_format($totalar, 2), '80px', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        for ($i = 0; $i < count($data2); $i++) {
          $post = $data2[$i]['postdate'];
          $trr = $data2[$i]['trno'];
          $qryh = " select  head.trno, head.yourref,head.docno,test.db,test.postdate,detail.refx
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
             left join (select d.trno,sum(d.db) as db,sum(d.cr) as cr,d.postdate
                  from ladetail as d
                  left join coa on coa.acnoid=d.acnoid
                  where left(coa.alias,2) in ('CB','CA')
                  group by trno,postdate) as test on test.trno=head.trno
                 where detail.refx = $trr and detail.postdate='$post'
                 union all
                      select   head.trno, head.yourref,head.docno ,test.db,test.postdate,detail.refx
                      from glhead as head
                      left join gldetail as detail on detail.trno=head.trno
            left join (select d.trno,sum(d.db) as db,sum(d.cr) as cr,d.postdate
                  from gldetail as d
                  left join coa on coa.acnoid=d.acnoid
                  where left(coa.alias,2) in ('CB','CA')
                  group by trno,postdate) as test on test.trno=head.trno
                      where  detail.refx = $trr and detail.postdate='$post'";
          $crd = $this->coreFunctions->opentable($qryh);
          $drefx = 0;
          if (!empty($crd)) {
            foreach ($crd as $key => $crnoo) {
              $drefx += $crnoo->refx;
            }
          }
          if ($drefx != 0) {
            $crno2 = isset($crnoo->yourref) ? $crnoo->yourref : '';
            // $postdate2 = $crnoo->postdate;
            $postdate2 = isset($crnoo->postdate) ? $crnoo->postdate : '';
            $ma2 = isset($crnoo->db) ? $crnoo->db : '';
            if (is_numeric($ma2)) {
              $ma2 = floatval($ma2);
            } else {
              $ma2 = 0.00;
            }
          } else {
            $crno2 = '';
            $postdate2 = '';
            $ma2 = 0.00;
          }
          $postdate2 = $data2[$i]['postdate'];
          $rem2 = $data2[$i]['rem'];
          // $crno2 = $data2[$i]['crno'];
          // $docno = $data2[$i]['docno'];
          // $ma2 = $data[0]['ma'];
          $rebate2 = $rebate;
          $current2 = $ma2 + $rebate2;
          $penalty2 = $penalty;

          if ($initialMonth === null) {
            $initialMonth = date("M-y", strtotime($data2[$i]['postdate']));
          }
          $incrementedMonth = date("M-y", strtotime('+' . $i . ' month', strtotime($initialMonth)));
          $totalar -= $current2;
          $displayDate = !empty($crno2) ? (isset($postdate2) ? date("j-M-y", strtotime($postdate2)) : '') : '';
          $str .= $this->reporter->begintable('800');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($displayDate, '100px', null, false, $border, '', 'L', $font, '11', '');
          $str .= $this->reporter->col(isset($rem2) ? $this->ordinal($i + 1) . " payment_" . $incrementedMonth : '', '140px', null, false, $border, '', 'L', $font, '11', '');
          $str .= $this->reporter->col($crno2, '80px', null, false, $border, '', 'R', $font, '11', '');
          $str .= $this->reporter->col(number_format($ma2, 2), '80px', null, false, $border, '', 'R', $font, '11', '');
          $str .= $this->reporter->col(number_format($rebate2, 2), '80px', null, false, $border, '', 'R', $font, '11', '');
          $str .= $this->reporter->col($penalty2, '80px', null, false, $border, '', 'R', $font, '11', '');
          $str .= $this->reporter->col(number_format($current2, 2), '80px', null, false, $border, '', 'R', $font, '11', '');
          $total = $totalar == 0 ? '-' : number_format($totalar, 2);
          $str .= $this->reporter->col($total, '80px', null, false, $border, '', 'R', $font, '11', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }




        if ($this->reporter->linecounter == $page) {
          // $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          // <--- Header
          $str .= $this->report_default_customer($params, $data);

          // $str .= $this->reporter->endrow();
          $page = $page + $count;
        }
      }
      // $current = $data[$i]->current;

    }
    // $str .= $this->reporter->begintable('800');
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('TOTAL', '100px', null, false, '1px dotted ', 'T', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('', '300px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col(number_format($totalinterest, 2), '150px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('', '100px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col(number_format($totalprincipal, 2), '150px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    // $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_reconlayout2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $data2 = $this->getdetail($params, $data);
    $totalinterest = 0;
    $totalprincipal = 0;

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->report_default_customer($params, $data);
    if (!empty($data2)) {
      $postdate = '';
      $rem = '';
      $crno = '';
      $ma = 0;
      $rebate = 0;
      $penalty = '';
      $current = 0;
      $totalar = 0;
      $initialMonth = null;
      for ($i = 0; $i < count($data2); $i++) {
        $crno = isset($data[$i]['cr']) && $data[$i]['cr'] != '' ? $data[$i]['cr'] : '';
        $downpayment = isset($data[$i]['downpayment']) ? $data[$i]['downpayment'] : '';
        $rebate = isset($data[$i]['rebate']) ? $data[$i]['rebate'] : 0;
        $penalty = isset($data[$i]['penalty']) ? $data[$i]['penalty'] : 0;
        $terms = isset($data[$i]['days']) ? $data[$i]['days'] : '';

        if ($downpayment != 0 && $downpayment != '') {
          $rem = "Downpayment";
          $postdate = $data[$i]['hdate'];
          $ma = $data[$i]['downpayment'];
        } else {
          $rem = $data2[0]['rem'];
          $postdate = isset($data[$i]['due']) ? $data[$i]['due'] : '';
          $ma = isset($data[$i]['ma']) ? $data[$i]['ma'] : 0;
        }

        $current =  $data[$i]['current'];
        $totalar = $current * $terms;
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($postdate, '100px', null, false, $border, '', 'L', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->col($rem, '140px', null, false, $border, 'B', 'L', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->col($crno, '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->col(number_format($ma, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->col(number_format($rebate, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->col($penalty, '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->col(number_format($current, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->col(number_format($totalar, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        for ($i = 0; $i < count($data2); $i++) {
          $crno2 = '';
          $ma2 = 0;
          $postdate2 = $data2[$i]['postdate'];
          $rem2 = $data2[$i]['rem'];

          $rebate2 = $rebate;
          $current2 = $ma2 + $rebate2;
          $penalty2 = $penalty;

          if ($initialMonth === null) {
            $initialMonth = date("M-y", strtotime($data2[$i]['postdate']));
          }

          $incrementedMonth = date("M-y", strtotime('+' . $i . ' month', strtotime($initialMonth)));
          $displayDate = !empty($crno2) ? (isset($postdate2) ? date("j-M-y", strtotime($postdate2)) : '') : '';
          $str .= $this->reporter->begintable('800');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($displayDate, '100px', null, false, $border, '', 'L', $font, '11', 'B', '30px', '8px');
          $str .= $this->reporter->col(isset($rem2) ? $this->ordinal($i + 1) . " payment_" . $incrementedMonth : '', '140px', null, false, $border, 'B', 'L', $font, '11', 'B', '30px', '8px');
          $str .= $this->reporter->col($crno2, '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
          $str .= $this->reporter->col(number_format($ma2, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
          $str .= $this->reporter->col(number_format($rebate2, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
          $str .= $this->reporter->col($penalty2, '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
          $str .= $this->reporter->col(number_format($current2, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
          $total = $totalar == 0 ? '-' : number_format($totalar, 2);
          $str .= $this->reporter->col(number_format($total, 2), '80px', null, false, $border, 'B', 'R', $font, '11', 'B', '30px', '8px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }



        if ($this->reporter->linecounter == $page) {
          // $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          // <--- Header
          $str .= $this->report_default_customer($params, $data);

          // $str .= $this->reporter->endrow();
          $page = $page + $count;
        }
      }
      // $current = $data[$i]->current;

    }
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100px', null, false, '1px dotted ', 'T', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '300px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalinterest, 2), '150px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalprincipal, 2), '150px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
  public function norecon()
  {
    $font = "";
    $fontbold = "";
    $fontsize = "11";

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
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(500, 0, 'No reconstruction details  available; we cannot generate the report.', '', 'L', false);
    PDF::SetFont($font, '', 5);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function ordinal($number)
  {
    $suffix = 'th';
    if (!in_array($number % 100, [11, 12, 13])) {
      $suffixes = ['th', 'st', 'nd', 'rd'];
      $suffix = $suffixes[($number % 10 < 4) ? $number % 10 : 0];
    }
    return $number . $suffix;
  }

  private function report_default_header($config, $data)
  {
    // $data = $this->reportDefault($config);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    // $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
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
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    // $str .= $this->reporter->col((isset($data[0]->docno) ? $data[0]->docno : ''), '100', null, false, $border, '', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('CSI#: ', '100', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['csi']) ? $data[0]['csi'] : ''), '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DR# : ', '100', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dr']) ? $data[0]['dr'] : ''), '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TIN : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['tin']) ? $data[0]['tin'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('CR# : ', '100', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['cr']) ? $data[0]['cr'] : ''), '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MODEL : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['model']) ? $data[0]['model'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ENGINE #:', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['serial']) ? $data[0]['serial'] : ''), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CHASSIS #:', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['chassis']) ? $data[0]['chassis'] : ''), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COLOR :', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['color']) ? $data[0]['color'] : ''), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    // $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('PARTICULARS', '300px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('SRP', '150px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(($data[0]['modeofsales'] !== 'CASH') ? "DOWNPAYMENT" : "DISCOUNT", '150px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(($data[0]['modeofsales'] !== 'CASH') ? "TERMS" : "NET SALES", '100px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  //aa
  private function report_default_customer($config, $data)
  {
    // $data = $this->reportDefault($config);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    // $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
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
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Model : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['model']) ? $data[0]['model'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Terms : ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '500', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Mode of Payment :', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['modeofsales']) ? $data[0]['modeofsales'] : ''), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Contact No. :', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['contactno']) ? $data[0]['contactno'] : ''), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('First Due :', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['due']) ? $data[0]['due'] : ''), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('M/A', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['ma']) ? number_format($data[0]['ma'] + $data[0]['rebate'], 2) : ''), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($data[0]['modeofsales'] != 'INHOUSE INSTALLMENT') {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('C/I', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
      $str .= $this->reporter->col($data[0]['ci'], '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('C/I', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
      $str .= $this->reporter->col('', '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Due Date', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
      $locale = 'en_US';
      $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
      $str .= $this->reporter->col($nf->format(date('d', strtotime($data[0]['hdate']))), '800', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    // $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '180px', null, false, $border, 'TB', 'L', $font, '12', 'B');
    $str .= $this->reporter->col('PARTICULARS/NOTES', '140px', null, false, $border, 'TB', 'C', $font, '12', 'B');
    $str .= $this->reporter->col('CR#', '80px', null, false, $border, 'TB', 'R', $font, '12', 'B');
    $str .= $this->reporter->col('AMOUNT', '80px', null, false, $border, 'TB', 'R', $font, '12', 'B');
    $str .= $this->reporter->col('REBATE', '80px', null, false, $border, 'TB', 'R', $font, '12', 'B');
    $str .= $this->reporter->col('PENALTY', '80px', null, false, $border, 'TB', 'R', $font, '12', 'B');
    $str .= $this->reporter->col('CURRENT', '80px', null, false, $border, 'TB', 'R', $font, '12', 'B');
    $str .= $this->reporter->col('A/R', '80px', null, false, $border, 'TB', 'R', $font, '12', 'B');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function default_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);

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
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    $this->reportheader->getheader($params);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Name : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "CSI# : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['csi']) ? $data[0]['csi'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "ADDRESS : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "DR #: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dr']) ? $data[0]['dr'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "TIN: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "CR #: ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['cr']) ? $data[0]['cr'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "MODEL: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['model']) ? $data[0]['model'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "ENGINE #: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['serial']) ? $data[0]['serial'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "CHASSIS #: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['chassis']) ? $data[0]['chassis'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "COLOR: ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['color']) ? $data[0]['color'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(300, 0, "PARTICULARS/NOTES", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "SRP", '', 'C', false, 0);
    PDF::MultiCell(100, 0, ($data[0]['modeofsales'] !== 'CASH') ? "DOWNPAYMENT" : "DISCOUNT", '', 'C', false, 0);
    PDF::MultiCell(120, 0, ($data[0]['modeofsales'] !== 'CASH') ? "TERMS" : "NET SALES", '', 'C', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }

  //cus1
  public function default_for_Customer_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    //bb
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    $this->reportheader->getheader($params);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Name : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Model : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['model']) ? $data[0]['model'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Terms : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Mode of Payment : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['modeofsales']) ? $data[0]['modeofsales'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Contact No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['contactno']) ? $data[0]['contactno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "First Due : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['due']) ? $data[0]['due'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "M/A : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['ma']) ? number_format($data[0]['ma'] + $data[0]['rebate'], 2) : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    $data2 = $this->report_default_query($params, $data);
    if (!empty($data2)) {
      foreach ($data2 as $key => $data) {
      }
    }

    if ($data['modeofsales'] == 'INHOUSE INSTALLMENT') {                 
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 20, "Due Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::SetFont($font, '', $fontsize);
      $locale = 'en_US';
      $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
      PDF::MultiCell(420, 20, $nf->format(date('d', strtotime($data['hdate']))), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
      PDF::MultiCell(0, 0, "\n\n");
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');
    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(80, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(160, 0, "PARTICULARS", '', 'C', false, 0);
    PDF::MultiCell(80, 0, 'CR#', '', 'C', false, 0);
    PDF::MultiCell(80, 0, 'AMOUNT', '', 'R', false, 0);
    PDF::MultiCell(80, 0, 'REBATE', '', 'R', false, 0);
    PDF::MultiCell(80, 0, 'PENALTY', '', 'C', false, 0);
    PDF::MultiCell(80, 0, 'CURRENT', '', 'R', false, 0);
    PDF::MultiCell(80, 0, 'A/R', '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }

  public function default_sj_PDF_MAIN($params, $data)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalinterest = 0;
    $totalprincipal = 0;
    $data2 = $this->getdetail($params, $data);

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
    PDF::MultiCell(720, 0, '', '');

    $countarr = 0;

    if (!empty($data2)) {
      for ($i = 0; $i < count($data2); $i++) {
        $maxrow = 1;
        $postdate = $data2[$i]['postdate'];
        $rem = $data2[$i]['rem'];
        $interest = number_format($data2[$i]['interest'], $decimalcurr);
        $principal = number_format($data2[$i]['principal'], $decimalcurr);

        $docno = $data2[$i]['docno'];

        $arr_date = $this->reporter->fixcolumn([$postdate], '25', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
        $arr_interest = $this->reporter->fixcolumn([$interest], '25', 0);
        $arr_principal = $this->reporter->fixcolumn([$principal], '25', 0);
        $arr_crno = $this->reporter->fixcolumn([$docno], '25', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_interest, $arr_principal, $arr_crno]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_date[$r]) ? $arr_date[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(200, 0, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(150, 0, ' ' . (isset($arr_interest[$r]) ? $arr_interest[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(150, 0, ' ' . (isset($arr_principal[$r]) ? $arr_principal[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(120, 0, ' ' . (isset($arr_crno[$r]) ? $arr_crno[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalinterest += $data2[$i]['interest'];
        $totalprincipal += $data2[$i]['principal'];

        if (PDF::getY() > 900) {
          $this->default_sj_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 0, 'TOTAL: ', '', 'L', false, 0);
    PDF::MultiCell(150, 0, number_format($totalinterest, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(150, 0, number_format($totalprincipal, $decimalcurr), '', 'R', false, 0);
    PDF::MultiCell(120, 0, '', '', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_sj_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    // $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    // $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    // $count = s$page = 35;
    $totalsrp = 0;
    $totalnetsales = 0;

    $font = "";
    $fontbold = "";
    // $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_sj_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $dateid = $data[$i]['dateid'];
        $rem = $data[$i]['rem'];
        $disc = $disc = $data[$i]['disc'];
        if ($disc != '') {
          if ($this->commonsbc->right($data[$i]['disc'], 1) != '%') {
            $disc = $data[$i]['disc'];
          }
        }

        $srp = number_format($data[$i]['srp'], $decimalcurr);
        $discOrDownpayment = ($data[$i]['modeofsales'] != 'CASH') ? number_format($data[$i]['downpayment'], 2) : $disc;
        $termsOrNetsales = ($data[$i]['modeofsales'] != 'CASH') ? $data[$i]['terms'] : number_format($data[$i]['netsales'], $decimalcurr);

        $arr_date = $this->reporter->fixcolumn([$dateid], '25', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
        $arr_srp = $this->reporter->fixcolumn([$srp], '25', 0);
        $arr_discOrDownpayment = $this->reporter->fixcolumn([$discOrDownpayment], '25', 0);
        $arr_termsOrNetsales = $this->reporter->fixcolumn([$termsOrNetsales], '25', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_srp, $arr_discOrDownpayment, $arr_termsOrNetsales]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_date[$r]) ? $arr_date[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(300, 0, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_srp[$r]) ? $arr_srp[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' ' . (isset($arr_discOrDownpayment[$r]) ? $arr_discOrDownpayment[$r] : ''), '', ($data[$i]['modeofsales'] != 'CASH') ? 'C' : 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(120, 0, ' ' . (isset($arr_termsOrNetsales[$r]) ? $arr_termsOrNetsales[$r] : ''), '', ($data[$i]['modeofsales'] != 'CASH') ? 'C' : 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalsrp += $data[$i]['srp'];
        $totalnetsales += $data[$i]['netsales'];

        if (PDF::getY() > 900) {
          $this->default_sj_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, 'TOTAL: ', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(300, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, number_format($totalsrp, $decimalcurr), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(120, 20, ($data[0]['modeofsales'] != 'CASH') ? '' : number_format($totalnetsales, $decimalcurr), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(0, 0, "\n\n\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize + 2);
    PDF::MultiCell(100, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(300, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, 'Noted By: ', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, '', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(300, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, $params['params']['dataparams']['noted'], '', 'L', false, 0);
    PDF::MultiCell(120, 0, '', '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_for_Customer_PDF1($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $overalltotal = 0;
    $overallrebate = 0;
    $overallamt = 0;
    $overallcurrent = 0;

    $data2 = $this->getdetail($params, $data);

    $font = "";
    $fontbold = "";
    $fontsize = "11";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->default_for_Customer_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');




    if (!empty($data2)) {
      $postdate = '';
      $rem = '';
      $crno = '';
      $ma = 0;
      $rebate = 0;
      $penalty = '';
      $current = 0;
      $totalar = 0;

      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $crno = $data[$i]['cr'];
        $downpayment = $data[$i]['downpayment'];
        $rebate = $data[$i]['rebate'];
        $penalty = '';//$data[$i]['penalty'];
        $terms = $data[$i]['days'];

        if ($downpayment != 0 && $downpayment != '') {
          $rem = "Downpayment";
          $postdate = $data[$i]['hdate'];
          $ma = $data[$i]['downpayment'];
        } else {
          $rem = $data2[0]['rem'];
          $postdate = $data[$i]['due'];
          $ma = $data[$i]['ma'];
        }

        $current = $data[$i]['current'];
        $totalar = $current * $terms;

        $arr_date = $this->reporter->fixcolumn([$postdate], '25', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
        $arr_crno = $this->reporter->fixcolumn([$crno], '25', 0);
        $arr_downpayment = $this->reporter->fixcolumn([number_format($downpayment, 2)], '25', 0);
        $arr_ma = $this->reporter->fixcolumn([number_format($ma, 2)], '25', 0);
        $arr_rebate = $this->reporter->fixcolumn([number_format($rebate, 2)], '25', 0);
        $arr_penalty = $this->reporter->fixcolumn([$penalty], '25', 0);
        $arr_current = $this->reporter->fixcolumn([number_format($current, 2)], '25', 0);
        $arr_totalar = $this->reporter->fixcolumn([number_format($totalar, 2)], '25', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_crno, $arr_downpayment, $arr_ma, $arr_rebate, $arr_penalty, $arr_current, $arr_totalar]);

        for ($j = 0; $j < $maxrow; $j++) {
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(720, 0, '', '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_date[$j]) ? date("j-M-y", strtotime($arr_date[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(160, 0, ' ' . (isset($arr_rem[$j]) ? $arr_rem[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_crno[$j]) ? $arr_crno[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ma[$j]) ? $arr_ma[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty[$j]) ? $arr_penalty[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_current[$j]) ? $arr_current[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(83, 0, '' . (isset($arr_totalar[$j]) ? $arr_totalar[$j] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }
      }

      for ($i = 0; $i < count($data2); $i++) {
        $maxrow2 = 1;
        $post = $data2[$i]['postdate'];
        $trr = $data2[$i]['trno'];
        $qryh = " select trno,yourref,docno,sum(db) as db,postdate,refx,sum(rebate) as rebate,ardate,penalty from (select  head.trno, head.yourref,head.docno,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
        (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6' and d.type = 'P' and left(d.podate,10)='$post') as penalty,
        detail.postdate as ardate
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno      
                  left join coa on coa.acnoid = detail.acnoid       
                 where detail.refx = $trr and left(detail.postdate,10)='$post'  and coa.alias in ('AR1','AR2')
                 union all
                      select   head.trno, head.yourref,head.docno ,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
                      (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,detail.postdate as ardate
                      from glhead as head
                      left join gldetail as detail on detail.trno=head.trno 
                      left join coa on coa.acnoid = detail.acnoid          
                      where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR1','AR2')
                      union all
      select  head.trno, head.yourref,head.docno,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
      (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,
      detail.postdate as ardate
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid = detail.acnoid
                 where detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')
                 union all
                      select   head.trno, head.yourref,head.docno ,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
                      (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,detail.postdate as ardate
                      from glhead as head
                      left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid = detail.acnoid
                      where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')) as a 
                      group by trno,yourref,docno,postdate,refx,ardate,penalty";

                      //ifnull((select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'R' and left(d.podate,10)='$post'),0) as urebate,
        $crd = $this->coreFunctions->opentable($qryh); //crnumberrrrr here 
        $drefx = 0;

        if (isset($data2[$i]['rem'])) {
          $rem2 = $data2[$i]['rem'];
        } else {
          $rem2 = '';
        }

        $crno2 = '';
        $postdate2 = '';
        $ma2 = 0;
        $rebate2 = 0;        
        $penalty2 = '';
        $particulars='';
        $current2 = $current;
        
        if (!empty($crd)) {
          foreach ($crd as $key => $crnoo) {
            $crno2 = isset($crnoo->yourref) ? $crnoo->yourref : '';
            $postdate2 = isset($crnoo->postdate) ? $crnoo->postdate : '';
            $ma2 = isset($crnoo->db) ? $crnoo->db : '';            

            $rebate2 = $crnoo->rebate;
            if ($crnoo->ardate < $crnoo->postdate) {
              $rebate2 = 0;
              $ma2 = isset($crnoo->db) ? $crnoo->db + $crnoo->rebate : '';             
            }
          
            $dayselapse = date_diff(date_create($crnoo->postdate), date_create($crnoo->ardate));
            if (intval($dayselapse->format("%a")) > 5) {
              if ($crnoo->penalty != 0) {
                $penalty2 = number_format($crnoo->penalty, 2);
              }
            }

          
            $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
            $arr_rem2 = $this->reporter->fixcolumn([$rem2], '35', 0);
            $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
            $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
            $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
            $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
            $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

            $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

            for ($j = 0; $j < $maxrow2; $j++) {
              $totalar -= ($ma2 + $rebate2);
              PDF::SetFont($font, '', 5);
              PDF::MultiCell(720, 0, '', '');
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(160, 0, ' ' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . date("M-y", strtotime($data2[$i]['postdate'])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              //PDF::MultiCell(80, 0, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty2[$j]) ? $arr_penalty2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_current2[$j]) ? $arr_current2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              $total = $totalar == 0 ? '-' : number_format($totalar, 2);
              PDF::MultiCell(80, 0, '' . $total, '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            }
            $drefx = 0;
          }

         
          
        }else{//if wala pa payment
          $current2 = $ma2;
          $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
          $arr_rem2 = $this->reporter->fixcolumn([$rem2], '35', 0);
          $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
          $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
          $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
          $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
          $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

          $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

          for ($j = 0; $j < $maxrow2; $j++) {
            $totalar -= $current2;
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(720, 0, '', '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(160, 0, ' ' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . date("M-y", strtotime($data2[$i]['postdate'])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            //PDF::MultiCell(80, 0, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty2[$j]) ? $arr_penalty2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_current2[$j]) ? $arr_current2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            $total = $totalar == 0 ? '-' : number_format($totalar, 2);
            PDF::MultiCell(80, 0, '' . $total, '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          }
          $drefx = 0;
        }
        
      }

      $overalltotal += $totalar;
      $overallrebate += $rebate2;
      $overallamt += $ma2;
      $overallcurrent += $current2;

      if (PDF::getY() > 900) {
        $this->default_for_Customer_header_PDF($params, $data);
      }
    }

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', 'B');

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', '');

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(320, 0, 'TOTAL: ', '', 'L', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallrebate, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'C', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallcurrent, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overalltotal, $decimalcurr), '', 'R', false, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function getdetail($config)
  {
    $trno = $config['params']['dataid'];
    $data2 = $this->report_default_query($config);

    if (!empty($data2)) {
      foreach ($data2 as $key => $data) {
      }
    }

    $reporttype = $config['params']['dataparams']['reporttype'];

    $this->coreFunctions->LogConsole($data['modeofsales']);
    //default 
    if ($data['modeofsales'] != 'INHOUSE INSTALLMENT') {
      $qry2 = "select '' as rem, '' as postdate, '0' as interest, sum(stock.ext) as principal, '' as docno,'' as crno
        from lahead as head 
        left join lastock as stock on stock.trno = head.trno 
        where head.trno= $trno
        having sum(stock.ext) > 0
        union all 
        select '' as rem, date(cn.postdate) as postdate, '0' as interest, sum(stock.ext) as principal, 
        ifnull((select group_concat(distinct docno separator ', ') from
        (select h.docno, d.refx, d.linex
        from gldetail as d
        left join glhead as h on h.trno = d.trno
        where h.doc = 'CR'
        union all
        select h.docno, d.refx, d.linex
        from ladetail as d
        left join lahead as h on h.trno = d.trno
        where h.doc = 'CR') as a
        where refx = stock.trno and linex = stock.line), '') as docno,
        ifnull((select yourref from (
          select h.yourref, d.refx, d.linex
          from gldetail as d
          left join glhead as h on h.trno = d.trno
          where h.doc = 'CR'
          union all
          select h.yourref, d.refx, d.linex
          from ladetail as d
          left join lahead as h on h.trno = d.trno
          where h.doc = 'CR' ) as a where a.refx = detail.trno and a.linex = detail.line limit 1 ), ''  ) as crno
        from glhead as head 
        left join glstock as stock on stock.trno = head.trno 
        left join gldetail as detail on detail.trno = head.trno
        left join cntnum as cn on cn.trno=head.trno
        where head.trno= $trno
        group by postdate,docno,crno,detail.trno,detail.line";
    } else { //installment 

      if ($reporttype == 1) { //for customer
        $qry2 = "        
            SELECT rem, postdate,trno
            FROM (
                SELECT
                    group_concat(distinct detail.rem ) as rem,
                    CASE WHEN coa.alias IN ('AR1', 'AR2') THEN DATE(detail.postdate) END AS postdate,head.trno
                FROM
                    glhead AS head
                    LEFT JOIN gldetail AS detail ON detail.trno = head.trno
                    LEFT JOIN coa ON coa.acnoid = detail.acnoid
                    LEFT JOIN cntnum AS cn ON cn.trno = head.trno
                WHERE
                    coa.alias IN ('AR1', 'AR2') AND head.trno = $trno
                    group by postdate,trno ) AS a
               GROUP BY postdate,rem,trno";
      } 
      else { //default
        $qry2 = "        
            SELECT rem, postdate,trno
            FROM (
                SELECT
                    group_concat(distinct detail.rem ) as rem,
                    CASE WHEN coa.alias IN ('AR1', 'AR2') THEN DATE(detail.postdate) END AS postdate,head.trno
                FROM
                    glhead AS head
                    LEFT JOIN gldetail AS detail ON detail.trno = head.trno
                    LEFT JOIN coa ON coa.acnoid = detail.acnoid
                    LEFT JOIN cntnum AS cn ON cn.trno = head.trno
                WHERE
                    coa.alias IN ('AR1', 'AR2') AND cn.recontrno = $trno and detail.refx=0
                    group by postdate,trno ) AS a
               GROUP BY postdate,rem,trno";
      }
    }

    $result2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);
    return $result2;
  }

  public function getdetail2($config)
  {
    $trno = $config['params']['dataid'];
    $data3 = $this->report_default_query($config);
    if (!empty($data3)) {
      foreach ($data3 as $key => $data) {
      }
    }

    //default 
    if ($data['modeofsales'] == 'INHOUSE INSTALLMENT') {
      $qry3 = "
      
      select rem, postdate,trno  from (
          select detail.rem as rem, head.dateid as postdate,head.trno
                  from lahead as head
                  left join ladetail as detail on detail.trno = head.trno
                  left join cntnum as cn on cn.trno=head.trno
                  where  head.doc = 'GJ' and detail.refx=0 and cn.recontrno= $trno 
                  group by head.dateid,detail.rem,head.trno) as a
           group by postdate,rem,trno
        
        union all
        select group_concat(distinct rem) as rem, postdate,trno
        from (
        select case when coa.alias in ('AR1', 'AR2') then detail.rem end as rem,
        case when coa.alias in ('AR1', 'AR2') then date(detail.postdate) end as postdate,head.trno
      
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        left join cntnum as cn on cn.trno=head.trno 
        where  coa.alias in ('AR1', 'AR2') and  head.doc = 'GJ' and detail.refx=0 and cn.recontrno= $trno ) as a
       
      group by postdate,trno";
    } else { //other mode
      $qry3 = "select '' as rem, '' as postdate, '0' as interest, sum(stock.ext) as principal, '' as docno,'' as crno
        from lahead as head 
        left join lastock as stock on stock.trno = head.trno 
        where head.trno= $trno
        having sum(stock.ext) > 0
        union all 
        select '' as rem, date(cn.postdate) as postdate, '0' as interest, sum(stock.ext) as principal, 
        ifnull((select group_concat(distinct docno separator ', ') from
        (select h.docno, d.refx, d.linex
        from gldetail as d
        left join glhead as h on h.trno = d.trno
        where h.doc = 'CR'
        union all
        select h.docno, d.refx, d.linex
        from ladetail as d
        left join lahead as h on h.trno = d.trno
        where h.doc = 'CR') as a
        where refx = stock.trno and linex = stock.line), '') as docno,
        ifnull((select yourref from (
          select h.yourref, d.refx, d.linex
          from gldetail as d
          left join glhead as h on h.trno = d.trno
          where h.doc = 'CR'
          union all
          select h.yourref, d.refx, d.linex
          from ladetail as d
          left join lahead as h on h.trno = d.trno
          where h.doc = 'CR' ) as a where a.refx = detail.trno and a.linex = detail.line limit 1 ), ''  ) as crno
        from glhead as head 
        left join glstock as stock on stock.trno = head.trno 
        left join gldetail as detail on detail.trno = head.trno
        left join cntnum as cn on cn.trno=head.trno
        where head.trno= $trno
        group by postdate,docno,crno,detail.trno,detail.line";
    }

    $result3 = json_decode(json_encode($this->coreFunctions->opentable($qry3)), true);
    return $result3;
  }

  public function reconlayout($params, $data)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $recon = empty($data) ? 0 : $data[0]['recontrno'];
    $mode = empty($data) ? 0 : $data[0]['modeofsales'];

    $recon = $this->othersClass->val($recon);
    if ($recon == 0) {
      return $this->norecon();
    } else {
      if ($mode != 'INHOUSE INSTALLMENT') {
        return $this->reconlayout2($params, $data);
      } else {
        return $this->reconlayout1($params, $data);
      }
    }
  }

  public function default_for_Customer_PDF($params, $data)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);
    $mode = empty($data) ? 0 : $data[0]['modeofsales'];

    if ($mode != 'INHOUSE INSTALLMENT') {

      return $this->default_for_Customer_PDF2($params, $data);
    } else {
      return $this->default_for_Customer_PDF1($params, $data);
    }
  }

  public function default_for_Customer_PDF2($params, $data)
  {
    //not house installment
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $overalltotal = 0;
    $overallrebate = 0;
    $overallamt = 0;
    $overallcurrent = 0;

    $data2 = $this->getdetail($params, $data);

    $font = "";
    $fontbold = "";
    $fontsize = "11";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->default_for_Customer_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    if (!empty($data2)) {
      $postdate = '';
      $rem = '';
      $crno = '';
      $ma = 0;
      $rebate = 0;
      $penalty = '';
      $current = 0;
      $totalar = 0;

      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $crno = $data[$i]['cr'];
        $downpayment = $data[$i]['downpayment'];
        $rebate = $data[$i]['rebate'];
        $penalty = $data[$i]['penalty'];
        $terms = $data[$i]['days'];

        if ($downpayment != 0 && $downpayment != '') {
          $rem = "Downpayment";
          $postdate = $data[$i]['hdate'];
          $ma = $data[$i]['downpayment'];
        } else {
          $rem = $data2[0]['rem'];
          $postdate = $data[$i]['due'];
          $ma = $data[$i]['ma'];
          // if ($ma == 0) {
          //   $ma = $data2[0]['principal'];
          //   $totalar = $data2[0]['principal'];
          // }
        }

        $current = $data[$i]['current'];
        $totalar = $current * $terms;

        $arr_date = $this->reporter->fixcolumn([$postdate], '25', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
        $arr_crno = $this->reporter->fixcolumn([$crno], '25', 0);
        $arr_downpayment = $this->reporter->fixcolumn([number_format($downpayment, 2)], '25', 0);
        $arr_ma = $this->reporter->fixcolumn([number_format($ma, 2)], '25', 0);
        $arr_rebate = $this->reporter->fixcolumn([number_format($rebate, 2)], '25', 0);
        $arr_penalty = $this->reporter->fixcolumn([$penalty], '25', 0);
        $arr_current = $this->reporter->fixcolumn([number_format($current, 2)], '25', 0);
        $arr_totalar = $this->reporter->fixcolumn([number_format($totalar, 2)], '25', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_crno, $arr_downpayment, $arr_ma, $arr_rebate, $arr_penalty, $arr_current, $arr_totalar]);

        for ($j = 0; $j < $maxrow; $j++) {
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(720, 0, '', '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_date[$j]) ? date("j-M-y", strtotime($arr_date[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(160, 0, ' ' . (isset($arr_rem[$j]) ? $arr_rem[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_crno[$j]) ? $arr_crno[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ma[$j]) ? $arr_ma[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate[$j]) ? $arr_rebate[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty[$j]) ? $arr_penalty[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_current[$j]) ? $arr_current[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(83, 0, '' . (isset($arr_totalar[$j]) ? $arr_totalar[$j] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }
      }

      for ($i = 0; $i < count($data2); $i++) {
        $maxrow2 = 1;

        $crno2 = '';
        $postdate2 = '';
        $ma2 = $data[0]['ma'];

        if (isset($data2[$i]['rem'])) {
          $rem2 = $data2[$i]['rem'];
        } else {
          $rem2 = '';
        }
        $rebate2 = $rebate;
        $current2 = $ma2 + $rebate2;
        $penalty2 = $penalty;

        $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
        $arr_rem2 = $this->reporter->fixcolumn([$rem2], '35', 0);
        $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
        $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
        $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
        $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
        $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

        $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

        for ($j = 0; $j < $maxrow2; $j++) {
          $totalar -= $current2;
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(720, 0, '', '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(160, 0, ' ' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . date("M-y", strtotime('+1 month', strtotime($data2[$i]['postdate']))) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_current2[$j]) ? $arr_current2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          $total = $totalar == 0 ? '-' : number_format($totalar, 2);
          PDF::MultiCell(80, 0, '' . $total, '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }
        // $drefx = 0;
      }

      $overalltotal += $totalar;
      $overallrebate += $rebate2;
      $overallamt += $ma2;
      $overallcurrent += $current2;

      if (PDF::getY() > 900) {
        $this->default_for_Customer_header_PDF($params, $data);
      }
    }

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', 'B');

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', '');

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(320, 0, 'TOTAL: ', '', 'L', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallrebate, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'C', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallcurrent, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overalltotal, $decimalcurr), '', 'R', false, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function reconlayout1($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $overalltotal = 0;
    $overallrebate = 0;
    $overallamt = 0;
    $overallcurrent = 0;

    $data2 = $this->getdetail($params, $data);

    $font = "";
    $fontbold = "";
    $fontsize = "11";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->default_for_Customer_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');




    if (!empty($data2)) {
      $postdate = '';
      $rem = '';
      $crno = '';
      $ma = 0;
      $rebate = 0;
      $penalty = '';
      $current = 0;
      $totalar = 0;

      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $crno = $data[$i]['cr'];
        $downpayment = $data[$i]['downpayment'];
        $rebate = $data[$i]['rebate'];
        $penalty = '';//$data[$i]['penalty'];
        $terms = $data[$i]['days'];

        if ($downpayment != 0 && $downpayment != '') {
          $rem = "Downpayment";
          $postdate = $data[$i]['hdate'];
          $ma = $data[$i]['downpayment'];
        } else {
          $rem = $data2[0]['rem'];
          $postdate = $data[$i]['due'];
          $ma = $data[$i]['ma'];
        }

        $current = $data[$i]['current'];
        $totalar = $current * $terms;

        $arr_date = $this->reporter->fixcolumn([$postdate], '25', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
        $arr_crno = $this->reporter->fixcolumn([$crno], '25', 0);
        $arr_downpayment = $this->reporter->fixcolumn([number_format($downpayment, 2)], '25', 0);
        $arr_ma = $this->reporter->fixcolumn([number_format($ma, 2)], '25', 0);
        $arr_rebate = $this->reporter->fixcolumn([number_format($rebate, 2)], '25', 0);
        $arr_penalty = $this->reporter->fixcolumn([$penalty], '25', 0);
        $arr_current = $this->reporter->fixcolumn([number_format($current, 2)], '25', 0);
        $arr_totalar = $this->reporter->fixcolumn([number_format($totalar, 2)], '25', 0);


        $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_crno, $arr_downpayment, $arr_ma, $arr_rebate, $arr_penalty, $arr_current, $arr_totalar]);

        for ($j = 0; $j < $maxrow; $j++) {
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(720, 0, '', '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_date[$j]) ? date("j-M-y", strtotime($arr_date[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(160, 0, ' ' . (isset($arr_rem[$j]) ? $arr_rem[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_crno[$j]) ? $arr_crno[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ma[$j]) ? $arr_ma[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' , '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty[$j]) ? $arr_penalty[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_current[$j]) ? $arr_current[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(83, 0, '' . (isset($arr_totalar[$j]) ? $arr_totalar[$j] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }
      }

      for ($i = 0; $i < count($data2); $i++) {
        $maxrow2 = 1;
        $post = $data2[$i]['postdate'];
        $trr = $data2[$i]['trno'];
        $qryh = " select trno,yourref,docno,sum(db) as db,postdate,refx,sum(rebate) as rebate,ardate,penalty from (select  head.trno, head.yourref,head.docno,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
        (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6' and d.type = 'P' and left(d.podate,10)='$post') as penalty,
        detail.postdate as ardate
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno      
                  left join coa on coa.acnoid = detail.acnoid       
                 where detail.refx = $trr and left(detail.postdate,10)='$post'  and coa.alias in ('AR1','AR2')
                 union all
                      select   head.trno, head.yourref,head.docno ,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
                      (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,detail.postdate as ardate
                      from glhead as head
                      left join gldetail as detail on detail.trno=head.trno 
                      left join coa on coa.acnoid = detail.acnoid          
                      where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR1','AR2')
                      union all
      select  head.trno, head.yourref,head.docno,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
      (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,
      detail.postdate as ardate
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid = detail.acnoid
                 where detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')
                 union all
                      select   head.trno, head.yourref,head.docno ,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
                      (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,detail.postdate as ardate
                      from glhead as head
                      left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid = detail.acnoid
                      where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')) as a 
                      group by trno,yourref,docno,postdate,refx,ardate,penalty";

                      //ifnull((select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'R' and left(d.podate,10)='$post'),0) as urebate,
        $crd = $this->coreFunctions->opentable($qryh); //crnumberrrrr here 
        $drefx = 0;

        if (isset($data2[$i]['rem'])) {
          $rem2 = $data2[$i]['rem'];
        } else {
          $rem2 = '';
        }

        $crno2 = '';
        $postdate2 = '';
        $ma2 = 0;
        $rebate2 = 0;        
        $penalty2 = '';
        $particulars='';
        $current2 = $current;
        
        if (!empty($crd)) {
          foreach ($crd as $key => $crnoo) {
            $crno2 = isset($crnoo->yourref) ? $crnoo->yourref : '';
            $postdate2 = isset($crnoo->postdate) ? $crnoo->postdate : '';
            $ma2 = isset($crnoo->db) ? $crnoo->db : '';            

            $rebate2 = $crnoo->rebate;
            if ($crnoo->ardate < $crnoo->postdate) {
              $rebate2 = 0;
              $ma2 = isset($crnoo->db) ? $crnoo->db + $crnoo->rebate : '';             
            }
          
            $dayselapse = date_diff(date_create($crnoo->postdate), date_create($crnoo->ardate));
            if (intval($dayselapse->format("%a")) > 5) {
              if ($crnoo->penalty != 0) {
                $penalty2 = number_format($crnoo->penalty, 2);
              }
            }

          
            $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
            $arr_rem2 = $this->reporter->fixcolumn([$rem2], '35', 0);
            $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
            $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
            $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
            $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
            $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

            $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

            for ($j = 0; $j < $maxrow2; $j++) {
              $totalar -= ($ma2 + $rebate2);
              PDF::SetFont($font, '', 5);
              PDF::MultiCell(720, 0, '', '');
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(160, 0, ' ' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . date("M-y", strtotime($data2[$i]['postdate'])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              //PDF::MultiCell(80, 0, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty2[$j]) ? $arr_penalty2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(80, 0, ' ' . (isset($arr_current2[$j]) ? $arr_current2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              $total = $totalar == 0 ? '-' : number_format($totalar, 2);
              PDF::MultiCell(80, 0, '' . $total, '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            }
            $drefx = 0;
          }

         
          
        }else{//if wala pa payment
          $current2 = $ma2;
          $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
          $arr_rem2 = $this->reporter->fixcolumn([$rem2], '35', 0);
          $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
          $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
          $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
          $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
          $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

          $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

          for ($j = 0; $j < $maxrow2; $j++) {
            $totalar -= $current2;
            PDF::SetFont($font, '', 5);
            PDF::MultiCell(720, 0, '', '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(160, 0, ' ' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . date("M-y", strtotime($data2[$i]['postdate'])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            //PDF::MultiCell(80, 0, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty2[$j]) ? $arr_penalty2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 0, ' ' . (isset($arr_current2[$j]) ? $arr_current2[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            $total = $totalar == 0 ? '-' : number_format($totalar, 2);
            PDF::MultiCell(80, 0, '' . $total, '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
          }
          $drefx = 0;
        }
        
      }

      $overalltotal += $totalar;
      $overallrebate += $rebate2;
      $overallamt += $ma2;
      $overallcurrent += $current2;

      if (PDF::getY() > 900) {
        $this->default_for_Customer_header_PDF($params, $data);
      }
    }

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', 'B');

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', '');

    // PDF::SetFont($fontbold, '', $fontsize);
    // PDF::MultiCell(320, 0, 'TOTAL: ', '', 'L', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallrebate, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'C', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallcurrent, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overalltotal, $decimalcurr), '', 'R', false, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  // public function reconlayout1($params, $data)
  // {
  //   $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
  //   $font = "";
  //   $fontbold = "";
  //   $fontsize = "11";
  //   $overalltotal = 0;
  //   $overallrebate = 0;
  //   $overallamt = 0;
  //   $overallcurrent = 0;

  //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
  //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
  //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
  //   }

  //   $this->default_for_Customer_header_PDF($params, $data);

  //   PDF::SetFont($font, '', 5);
  //   PDF::MultiCell(720, 0, '', '');
  //   $data2 = $this->getdetail2($params, $data);

  //   if (!empty($data2)) {
  //     $postdate = '';
  //     $rem = '';
  //     $crno = '';
  //     $ma = 0;
  //     $rebate = 0;
  //     $penalty = '';
  //     $current = 0;
  //     $totalar = 0;
  //     $initialMonth = null;
  //     // $firstLoop = true;

  //     for ($i = 0; $i < count($data); $i++) {
  //       $maxrow = 1;
  //       $crno = $data[$i]['cr'];
  //       $downpayment = $data[$i]['downpayment'];
  //       $rebate = $data[$i]['rebate'];
  //       $penalty = $data[$i]['penalty'];
  //       $terms = $data[$i]['days'];

  //       if ($downpayment != 0 && $downpayment != '') {
  //         $rem = "Downpayment";
  //         $postdate = $data[$i]['hdate'];
  //         $ma = $data[$i]['downpayment'];
  //         // $current = $ma + $rebate;
  //       } else {
  //         $rem = $data2[0]['rem'];
  //         $postdate = $data[$i]['due'];
  //         $ma = $data[$i]['ma'];
  //       }
  //       $current = $data[$i]['current'];
  //       $totalar = $current * $terms;
  //       $arr_date = $this->reporter->fixcolumn([$postdate], '25', 0);
  //       $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
  //       $arr_crno = $this->reporter->fixcolumn([$crno], '25', 0);
  //       $arr_downpayment = $this->reporter->fixcolumn([number_format($downpayment, 2)], '25', 0);
  //       $arr_ma = $this->reporter->fixcolumn([number_format($ma, 2)], '25', 0);
  //       $arr_rebate = $this->reporter->fixcolumn([number_format($rebate, 2)], '25', 0);
  //       $arr_penalty = $this->reporter->fixcolumn([$penalty], '25', 0);
  //       $arr_current = $this->reporter->fixcolumn([number_format($current, 2)], '25', 0);
  //       $arr_totalar = $this->reporter->fixcolumn([number_format($totalar, 2)], '25', 0);

  //       $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_crno, $arr_downpayment, $arr_ma, $arr_rebate, $arr_penalty, $arr_current, $arr_totalar]);

  //       for ($j = 0; $j < $maxrow; $j++) {
  //         PDF::SetFont($font, '', 5);
  //         PDF::MultiCell(720, 0, '', '');
  //         PDF::SetFont($font, '', $fontsize);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_date[$j]) ? date("j-M-y", strtotime($arr_date[$j])) : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(160, 0, ' ' . (isset($arr_rem[$j]) ? $arr_rem[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_crno[$j]) ? $arr_crno[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_ma[$j]) ? $arr_ma[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate[$j]) ? $arr_rebate[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty[$j]) ? $arr_penalty[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_current[$j]) ? $arr_current[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(83, 0, '' . (isset($arr_totalar[$j]) ? $arr_totalar[$j] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
  //       }
  //     }

  //     for ($i = 0; $i < count($data2); $i++) {
  //       $maxrow2 = 1;
  //       $post = $data2[$i]['postdate'];
  //       $trr = $data2[$i]['trno'];
  //       $qryh = " select  head.trno, head.yourref,head.docno,test.db,test.postdate,detail.refx
  //                 from lahead as head
  //                 left join ladetail as detail on detail.trno=head.trno
  //            left join (select d.trno,sum(d.db) as db,sum(d.cr) as cr,d.postdate
  //                 from ladetail as d
  //                 left join coa on coa.acnoid=d.acnoid
  //                 where left(coa.alias,2) in ('CB','CA')
  //                 group by trno,postdate) as test on test.trno=head.trno
  //                where detail.refx = $trr and detail.postdate='$post'
  //                union all
  //                     select   head.trno, head.yourref,head.docno ,test.db,test.postdate,detail.refx
  //                     from glhead as head
  //                     left join gldetail as detail on detail.trno=head.trno
  //           left join (select d.trno,sum(d.db) as db,sum(d.cr) as cr,d.postdate
  //                 from gldetail as d
  //                 left join coa on coa.acnoid=d.acnoid
  //                 where left(coa.alias,2) in ('CB','CA')
  //                 group by trno,postdate) as test on test.trno=head.trno
  //                     where  detail.refx = $trr and detail.postdate='$post'";

  //       $crd = $this->coreFunctions->opentable($qryh);
  //       $drefx = 0;
  //       if (!empty($crd)) {
  //         foreach ($crd as $key => $crnoo) {
  //           $drefx += $crnoo->refx;
  //         }
  //       }
  //       if ($drefx != 0) {
  //         $crno2 = isset($crnoo->yourref) ? $crnoo->yourref : '';
  //         // $postdate2 = $crnoo->postdate;
  //         $postdate2 = isset($crnoo->postdate) ? $crnoo->postdate : '';
  //         $ma2 = isset($crnoo->db) ? $crnoo->db : '';
  //         if (is_numeric($ma2)) {
  //           $ma2 = floatval($ma2);
  //         } else {
  //           $ma2 = 0.00;
  //         }
  //       } else {
  //         $crno2 = '';
  //         $postdate2 = '';


  //         $ma2 = 0.00;
  //       }
  //       $postdate2 = $data2[$i]['postdate'];
  //       $rem2 = $data2[$i]['rem'];
  //       // $crno2 = $data2[$i]['crno'];
  //       // $docno = $data2[$i]['docno'];
  //       // $ma2 = $data[0]['ma'];
  //       $rebate2 = $rebate;
  //       $current2 = $ma2 + $rebate2;
  //       $penalty2 = $penalty;
  //       if ($initialMonth === null) {
  //         $initialMonth = date("M-y", strtotime($data2[$i]['postdate']));
  //       }

  //       $incrementedMonth = date("M-y", strtotime('+' . $i . ' month', strtotime($initialMonth)));
  //       $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
  //       $arr_rem2 = $this->reporter->fixcolumn([$rem2], '35', 0);
  //       $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
  //       $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
  //       $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
  //       $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
  //       $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

  //       $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

  //       for ($j = 0; $j < $maxrow2; $j++) {
  //         // if ($downpayment != 0 || $downpayment != '') {
  //         // if ($firstLoop) {
  //         //   $currenthere = $current + $current2;
  //         //   $totalar -= $currenthere;
  //         //   $firstLoop = false;
  //         // } else {
  //         //   $totalar -= $current2;
  //         // }
  //         // } else {
  //         $totalar -= $current2;
  //         // }

  //         PDF::SetFont($font, '', 5);
  //         PDF::MultiCell(720, 0, '', '');
  //         PDF::SetFont($font, '', $fontsize);
  //         $displayDate = !empty($crno2) ? (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : '') : '';
  //         PDF::MultiCell(80, 0, ' ' . $displayDate, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(160, 0, ' ' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . $incrementedMonth : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         PDF::MultiCell(80, 0, ' ' . (isset($arr_current2[$j]) ? $arr_current2[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
  //         $total = $totalar == 0 ? '-' : number_format($totalar, 2);
  //         PDF::MultiCell(80, 0, '' . $total, '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
  //       }
  //       $overallrebate += $rebate2;
  //       $overallamt += $ma2;
  //       $overallcurrent += $current2;
  //       // $overalltotal = $totalar;
  //       // $overalltotal = $totalar - $overallcurrent;
  //       if (PDF::getY() > 900) {
  //         $this->default_for_Customer_header_PDF($params, $data);
  //       }
  //     }
  //   }

  //   // PDF::SetFont($font, '', 5);
  //   // PDF::MultiCell(720, 0, '', 'B');

  //   // PDF::SetFont($font, '', 5);
  //   // PDF::MultiCell(720, 0, '', '');

  //   // PDF::SetFont($fontbold, '', $fontsize);
  //   // // if ($downpayment != 0 || $downpayment != '') {
  //   // //   $overallamt = $ma + $overallamt;
  //   // //   $overallcurrent = $current + $overallcurrent;
  //   // // }

  //   // PDF::MultiCell(320, 0, 'TOTAL: ', '', 'L', false, 0);
  //   // PDF::MultiCell(80, 0, number_format($overallamt, $decimalcurr), '', 'R', false, 0);
  //   // PDF::MultiCell(80, 0, number_format($overallrebate, $decimalcurr), '', 'R', false, 0);
  //   // PDF::MultiCell(80, 0, '', '', 'C', false, 0);
  //   // PDF::MultiCell(80, 0, number_format($overallcurrent, $decimalcurr), '', 'R', false, 0);
  //   // PDF::MultiCell(80, 0, number_format($overalltotal, $decimalcurr), '', 'R', false, 0);


  //   return PDF::Output($this->modulename . '.pdf', 'S');
  // }

  public function reconlayout2($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $font = "";
    $fontbold = "";
    $fontsize = "11";
    $overalltotal = 0;
    $overallrebate = 0;
    $overallamt = 0;
    $overallcurrent = 0;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->default_for_Customer_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    $data2 = $this->getdetail2($params, $data);

    if (!empty($data2)) {
      $postdate = '';
      $rem = '';
      $crno = '';
      $ma = 0;
      $rebate = 0;
      $penalty = '';
      $current = 0;
      $totalar = 0;
      $docno = '';
      $initialMonth = null;
      // $firstLoop = true;

      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $crno = $data[$i]['cr'];
        $downpayment = $data[$i]['downpayment'];
        $rebate = $data[$i]['rebate'];
        $penalty = $data[$i]['penalty'];
        $terms = $data[$i]['days'];

        if ($downpayment != 0 && $downpayment != '') {
          $rem = "Downpayment";
          $postdate = $data[$i]['hdate'];
          $ma = $data[$i]['downpayment'];
          // $current = $ma + $rebate;
        } else {
          $rem = $data2[0]['rem'];
          $postdate = $data[$i]['due'];
          $ma = $data[$i]['ma'];
        }
        $current = $data[$i]['current'];
        $totalar = $current * $terms;
        $arr_date = $this->reporter->fixcolumn([$postdate], '25', 0);
        $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
        $arr_crno = $this->reporter->fixcolumn([$crno], '25', 0);
        $arr_downpayment = $this->reporter->fixcolumn([number_format($downpayment, 2)], '25', 0);
        $arr_ma = $this->reporter->fixcolumn([number_format($ma, 2)], '25', 0);
        $arr_rebate = $this->reporter->fixcolumn([number_format($rebate, 2)], '25', 0);
        $arr_penalty = $this->reporter->fixcolumn([$penalty], '25', 0);
        $arr_current = $this->reporter->fixcolumn([number_format($current, 2)], '25', 0);
        $arr_totalar = $this->reporter->fixcolumn([number_format($totalar, 2)], '25', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_crno, $arr_downpayment, $arr_ma, $arr_rebate, $arr_penalty, $arr_current, $arr_totalar]);

        for ($j = 0; $j < $maxrow; $j++) {
          PDF::SetFont($font, '', 5);
          PDF::MultiCell(720, 0, '', '');
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_date[$j]) ? date("j-M-y", strtotime($arr_date[$j])) : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(160, 0, ' ' . (isset($arr_rem[$j]) ? $arr_rem[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_crno[$j]) ? $arr_crno[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ma[$j]) ? $arr_ma[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate[$j]) ? $arr_rebate[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_penalty[$j]) ? $arr_penalty[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_current[$j]) ? $arr_current[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(83, 0, '' . (isset($arr_totalar[$j]) ? $arr_totalar[$j] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        }
      }

      $prevDocNo = '';
      for ($i = 0; $i < count($data2); $i++) {
        $maxrow2 = 1;
        // $post = $data2[$i]['postdate'];
        // $trr = $data2[$i]['trno'];
        // $qryh = " select  head.trno, head.yourref,head.docno,test.db,test.postdate,detail.refx
        //           from lahead as head
        //           left join ladetail as detail on detail.trno=head.trno
        //      left join (select d.trno,sum(d.db) as db,sum(d.cr) as cr,d.postdate
        //           from ladetail as d
        //           left join coa on coa.acnoid=d.acnoid
        //           where left(coa.alias,2) in ('CB','CA')
        //           group by trno,postdate) as test on test.trno=head.trno
        //          where detail.refx = $trr and detail.postdate='$post'
        //          union all
        //               select   head.trno, head.yourref,head.docno ,test.db,test.postdate,detail.refx
        //               from glhead as head
        //               left join gldetail as detail on detail.trno=head.trno
        //     left join (select d.trno,sum(d.db) as db,sum(d.cr) as cr,d.postdate
        //           from gldetail as d
        //           left join coa on coa.acnoid=d.acnoid
        //           where left(coa.alias,2) in ('CB','CA')
        //           group by trno,postdate) as test on test.trno=head.trno
        //               where  detail.refx = $trr and detail.postdate='$post'";

        // $crd = $this->coreFunctions->opentable($qryh);
        // $drefx = 0;
        // if (!empty($crd)) {
        //   foreach ($crd as $key => $crnoo) {
        //     $drefx += $crnoo->refx;
        //   }
        // }
        // if ($drefx != 0) {
        //   $crno2 = isset($crnoo->yourref) ? $crnoo->yourref : '';
        //   // $postdate2 = $crnoo->postdate;
        //   $postdate2 = isset($crnoo->postdate) ? $crnoo->postdate : '';
        //   $ma2 = isset($crnoo->db) ? $crnoo->db : '';
        // } else {
        $crno2 = '';
        // $postdate2 = '';
        $ma2 = 0;
        // }
        $postdate2 = $data2[$i]['postdate'];
        $rem2 = $data2[$i]['rem'];
        // $crno2 = $data2[$i]['crno'];
        // $docno = $data2[$i]['docno'];
        // $ma2 = $data[0]['ma'];
        $rebate2 = $rebate;
        $current2 = $ma2 + $rebate2;
        $penalty2 = $penalty;

        // if ($prevDocNo == $docno) {
        //   $postdate2 = '';
        //   $crno2 = '';
        // } else {
        //   $prevDocNo = $docno;
        // }

        if ($initialMonth === null) {
          $initialMonth = date("M-y", strtotime($data2[$i]['postdate']));
        }

        $incrementedMonth = date("M-y", strtotime('+' . $i . ' month', strtotime($initialMonth)));
        $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
        $arr_rem2 = $this->reporter->fixcolumn([$rem2], '35', 0);
        $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
        $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
        $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
        $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
        $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

        $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

        for ($j = 0; $j < $maxrow2; $j++) {
          // if ($downpayment != 0 || $downpayment != '') {
          // if ($firstLoop) {
          //   $currenthere = $current + $current2;
          //   $totalar -= $currenthere;
          //   $firstLoop = false;
          // } else {
          //   $totalar -= $current2;
          // }
          // } else {
          $totalar -= $current2;
          // }

          PDF::SetFont($font, '', 5);
          PDF::MultiCell(720, 0, '', '');
          PDF::SetFont($font, '', $fontsize);
          $displayDate = !empty($crno2) ? (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : '') : '';
          PDF::MultiCell(80, 0, ' ' . $displayDate, '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(160, 0, ' ' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . $incrementedMonth : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, '', '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' ' . (isset($arr_current2[$j]) ? $arr_current2[$j] : ''), '', 'R', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          $total = $totalar == 0 ? '-' : number_format($totalar, 2);
          PDF::MultiCell(80, 0, '' . $total, '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        }
        $overallrebate += $rebate2;
        $overallamt += $ma2;
        $overallcurrent += $current2;
        // $overalltotal = $totalar;
        // $overalltotal = $totalar - $overallcurrent;
        if (PDF::getY() > 900) {
          $this->default_for_Customer_header_PDF($params, $data);
        }
      }
    }

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', 'B');

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(720, 0, '', '');

    // PDF::SetFont($fontbold, '', $fontsize);
    // // if ($downpayment != 0 || $downpayment != '') {
    // //   $overallamt = $ma + $overallamt;
    // //   $overallcurrent = $current + $overallcurrent;
    // // }

    // PDF::MultiCell(320, 0, 'TOTAL: ', '', 'L', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallamt, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallrebate, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, '', '', 'C', false, 0);
    // PDF::MultiCell(80, 0, number_format($overallcurrent, $decimalcurr), '', 'R', false, 0);
    // PDF::MultiCell(80, 0, number_format($overalltotal, $decimalcurr), '', 'R', false, 0);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_for_Customer2($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $data2 = $this->getdetail($params, $data);
    $overalltotal = 0;
    $overallrebate = 0;
    $overallamt = 0;
    $overallcurrent = 0;

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->report_default_customer($params, $data);

    for ($i = 0; $i < count($data); $i++) {

      $crno = $data[$i]['cr'];
      $downpayment = $data[$i]['downpayment'];
      $rebate = $data[$i]['rebate'];
      $penalty = $data[$i]['penalty'];
      $terms = $data[$i]['days'];

      if ($downpayment != 0 && $downpayment != '') {
        $rem = "Downpayment";
        $postdate = $data[$i]['hdate'];
        $ma = $data[$i]['downpayment'];
      } else {
        $rem = $data2[0]['rem'];
        $postdate = $data[$i]['due'];
        $ma = $data[$i]['ma'];
      }

      $current = $data[$i]['current'];
      $totalar = $current * $terms;

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($postdate, '140px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($rem, '180px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($crno, '80px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($ma, 2), '80px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($rebate, 2), '80px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($penalty, '80px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($current, 2), '80px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($totalar, 2), '80px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    for ($i = 0; $i < count($data2); $i++) {

      $crno2 = '';
      $postdate2 = '';
      $ma2 = $data[0]['ma'];

      if (isset($data2[$i]['rem'])) {
        $rem2 = $data2[$i]['rem'];
      } else {
        $rem2 = '';
      }

      $rebate2 = $rebate;
      $current2 = $ma2 + $rebate2;
      $penalty2 = $penalty;


      $totalar -= $current2;
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($postdate2, '100px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($rem2, '300px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($crno2, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($ma2, 2), '100px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($rebate, 2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($penalty2, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($current2, 2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $total = $totalar == 0 ? '-' : number_format($totalar, 2);
      $str .= $this->reporter->col($total, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $overalltotal += $totalar;
    $overallrebate += $rebate2;
    $overallamt += $ma2;
    $overallcurrent += $current2;

    if ($this->reporter->linecounter == $page) {
      // $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();

      // <--- Header
      $str .= $this->report_default_header($params, $data);

      // $str .= $this->reporter->endrow();
      $page = $page + $count;
    } //end if

    // } //end for
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_for_Customer1($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $data2 = $this->getdetail($params, $data);
    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $overalltotal = 0;
    $overallrebate = 0;
    $overallamt = 0;
    $overallcurrent = 0;

    $str .= $this->reporter->beginreport();
    $str .= $this->report_default_customer($params, $data);

    for ($i = 0; $i < count($data); $i++) {

      $crno = $data[$i]['cr'];
      $downpayment = $data[$i]['downpayment'];
      $rebate = $data[$i]['rebate'];
      $penalty = $data[$i]['penalty'];
      $terms = $data[$i]['days'];

      if ($downpayment != 0 && $downpayment != '') {
        $rem = "Downpayment";
        $postdate = $data[$i]['hdate'];
        $ma = $data[$i]['downpayment'];
      } else {
        $rem = $data2[0]['rem'];
        $postdate = $data[$i]['due'];
        $ma = $data[$i]['ma'];
      }

      $current = $data[$i]['current'];
      $totalar = $current * $terms;
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($postdate, '100px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($rem, '300px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($crno, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($ma, 2), '100px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($rebate, 2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($penalty, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($current, 2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($totalar, 2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      for ($i = 0; $i < count($data2); $i++) {
        $post = $data2[$i]['postdate'];
        $trr = $data2[$i]['trno'];
        $qryh = " select trno,yourref,docno,sum(db) as db,postdate,refx,sum(rebate) as rebate,ardate,penalty from (select  head.trno, head.yourref,head.docno,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
        (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6') as penalty,detail.postdate as ardate
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno      
                  left join coa on coa.acnoid = detail.acnoid       
                 where detail.refx = $trr and left(detail.postdate,10)='$post'  and coa.alias in ('AR1','AR2')
                 union all
                      select   head.trno, head.yourref,head.docno ,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
                      (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6') as penalty,detail.postdate as ardate
                      from glhead as head
                      left join gldetail as detail on detail.trno=head.trno 
                      left join coa on coa.acnoid = detail.acnoid          
                      where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR1','AR2')
                      union all
      select  head.trno, head.yourref,head.docno,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
      (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6') as penalty,detail.postdate as ardate
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
                  left join coa on coa.acnoid = detail.acnoid
                 where detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')
                 union all
                      select   head.trno, head.yourref,head.docno ,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
                      (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6') as penalty,detail.postdate as ardate
                      from glhead as head
                      left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid = detail.acnoid
                      where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')) as a 
                      group by trno,yourref,docno,postdate,refx,ardate,penalty";


        $crd = $this->coreFunctions->opentable($qryh); //crnumberrrrr here 
        $drefx = 0;

        if (isset($data2[$i]['rem'])) {
          $rem2 = $data2[$i]['rem'];
        } else {
          $rem2 = '';
        }

        $crno2 = '';
        $postdate2 = '';
        $ma2 = 0;
        $rebate2 = 0;
        $current2 = $current;
        $penalty2 = 0;

        if (!empty($crd)) {
          foreach ($crd as $key => $crnoo) {
            $drefx += $crnoo->refx;
          }

          if ($drefx != 0) {
            $crno2 = isset($crnoo->yourref) ? $crnoo->yourref : '';
            $postdate2 = isset($crnoo->postdate) ? $crnoo->postdate : '';
            $ma2 = isset($crnoo->db) ? $crnoo->db : '';
          } else {
            $crno2 = '';
            $postdate2 = '';
            $ma2 = 0;
          }

          if ($crnoo->ardate < $crnoo->postdate) {
            $rebate = 0;
            $ma2 = isset($crnoo->db) ? $crnoo->db + $crnoo->rebate : '';
          }

          $rebate2 = $crnoo->rebate;
          //$current2 = $ma2 + $rebate2;
          if ($crnoo->penalty != 0) {
            $penalty2 = number_format($crnoo->penalty, 2);
          }
        }
        $totalar -= $current2;

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($postdate2, '100px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col($rem2, '300px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col($crno2, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col(number_format($ma2, 2), '100px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col(number_format($rebate2, 2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col(($penalty2 == 0 ? '' : $penalty2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col(number_format($current2, 2), '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $total = $totalar == 0 ? '-' : number_format($totalar, 2);
        $str .= $this->reporter->col($total, '150px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $drefx = 0;
      }
      if ($this->reporter->linecounter == $page) {
        // $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        // <--- Header
        $str .= $this->report_default_header($params, $data);

        // $str .= $this->reporter->endrow();
        $page = $page + $count;
      } //end if
    } //end for
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function no_recon_ex()
  {
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $overalltotal = 0;
    $overallrebate = 0;
    $overallamt = 0;
    $overallcurrent = 0;
    $str = '';
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('No reconstruction details  available; we cannot generate the report.', '500px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }
}
