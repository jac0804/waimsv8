<?php

namespace App\Http\Classes\modules\modulereport\cbbsi;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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

class rr
{
  private $modulename = "Receiving Report";
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'received', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      ['label' => 'Excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'RR', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Bad Order Slip', 'value' => '1', 'color' => 'orange'],
      ['label' => 'RR w/ Item ID', 'value' => '2', 'color' => 'orange'],
      ['label' => 'NEW RR', 'value' => '3', 'color' => 'orange']
    ]);
    data_set($col1, 'received.label', 'Stock Counted By');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $qry = "select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if ((isset($name[0]['name']) ? $name[0]['name'] : '') != '') {
      $user = $name[0]['name'];
    }

    $signatories = $this->othersClass->getSignatories($config);
    $prepared = '';
    $received =  '';
    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'prepared':
          $prepared = $value->fieldvalue;
        case 'received':
          $received = $value->fieldvalue;
          break;
      }
    }

    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '$user' as prepared,
      '" . $prepared . "' as approved,
      '0' as reporttype,
      '" . $received . "' as received
      "
    );
  }

  public function report_default_query($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $filter = '';
    if ($config['params']['dataparams']['reporttype'] == 1) $filter = ' and sinfo.isbo=1';

    $trno = $config['params']['dataid'];

    switch ($config['params']['dataparams']['reporttype']) {
      case 3:
        $query = "select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem, head.yourref, head.ourref, item.barcode, item.itemid, item.itemname, sum(stock.rrcost) as gross, sum(stock.cost) as netamt, sum(stock.rrqty) as rrqty, sum(stock.rrqty * uom.factor) as pcs, client.fax, info.carrier, info.waybill, stock.uom, sum(stock.disc) as disc, sum(stock.ext) as ext, wh.client as wh, wh.clientname as whname, stock.loc, date(stock.expiry) as expiry, stock.rem as srem, item.sizeid, m.model_name as model, sum(stock.rrcost) as rrcost, sum(if(sinfo.isbo = 1, stock.rrqty, 0)) as boqty, sum(if(stock.ref = '', stock.rrqty, 0)) as excess
        from (lahead as head
        left join lastock as stock on stock.trno = head.trno)
        left join client on client.client = head.client
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join cntnuminfo as info on info.trno = head.trno
        left join stockinfo as sinfo on sinfo.trno = head.trno and sinfo.line = stock.line
        left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
        where head.trno = $trno
        group by head.docno, head.trno, head.clientname, head.address, date(head.dateid), head.terms, head.rem, head.yourref, head.ourref, item.barcode, item.itemid, item.itemname, client.fax, info.carrier, info.waybill, stock.uom, wh.client, wh.clientname, stock.loc, date(stock.expiry), stock.rem, item.sizeid, m.model_name
        union all
        select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem, head.yourref, head.ourref, item.barcode, item.itemid, item.itemname, sum(stock.rrcost) as gross, sum(stock.cost) as netamt, sum(stock.rrqty) as rrqty, sum(stock.rrqty * uom.factor) as pcs, client.fax, info.carrier, info.waybill, stock.uom, sum(stock.disc) as disc, sum(stock.ext) as ext, wh.client as wh, wh.clientname as whname, stock.loc, date(stock.expiry) as expiry, stock.rem as srem, item.sizeid, m.model_name as model, sum(stock.rrcost) as rrcost, sum(if(sinfo.isbo = 1, stock.rrqty, 0)) as boqty, sum(if(stock.ref = '', stock.rrqty, 0)) as excess
        from (glhead as head
        left join glstock as stock on stock.trno = head.trno)
        left join client on client.clientid = head.clientid
        left join item on item.itemid = stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        left join hcntnuminfo as info on info.trno = head.trno
        left join hstockinfo as sinfo on sinfo.trno = head.trno and sinfo.line = stock.line
        left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
        where head.trno = $trno
        group by head.docno, head.trno, head.clientname, head.address, date(head.dateid), head.terms, head.rem, head.yourref, head.ourref, item.barcode, item.itemid, item.itemname, client.fax, info.carrier, info.waybill, stock.uom, wh.client, wh.clientname, stock.loc, date(stock.expiry), stock.rem, item.sizeid, m.model_name";
        break;
      default:
        $query = "select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem, head.yourref, head.ourref, stock.ref, item.barcode, item.itemid, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty, stock.rrqty * uom.factor as pcs, client.fax, info.carrier, info.waybill, stock.uom, stock.disc as disc, stock.ext as ext, wh.client as wh, wh.clientname as whname, stock.loc, date(stock.expiry) as expiry, stock.rem as srem, item.sizeid, m.model_name as model, stock.rrcost as rrcost, stock.line
        from (lahead as head
        left join lastock as stock on stock.trno = head.trno)
        left join client on client.client = head.client
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join cntnuminfo as info on info.trno = head.trno
        left join stockinfo as sinfo on sinfo.trno = head.trno and sinfo.line = stock.line
        left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
        where head.trno = '$trno' $filter
        union all
        select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem, head.yourref, head.ourref, stock.ref, item.barcode, item.itemid, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty, stock.rrqty * uom.factor as pcs, client.fax, info.carrier, info.waybill, stock.uom, stock.disc as disc, stock.ext as ext, wh.client as wh, wh.clientname as whname, stock.loc, date(stock.expiry) as expiry, stock.rem as srem, item.sizeid, m.model_name as model, stock.rrcost as rrcost, stock.line
        from (glhead as head
        left join glstock as stock on stock.trno = head.trno)
        left join client on client.clientid = head.clientid
        left join item on item.itemid = stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        left join hcntnuminfo as info on info.trno = head.trno
        left join hstockinfo as sinfo on sinfo.trno = head.trno and sinfo.line = stock.line
        left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
        where head.trno = '$trno' $filter
        order by line";
        break;
    }

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_Query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select stock.itemid, stock.ref, wh.client as wh
    from lahead as head
    left join lastock as stock on stock.trno = head.trno
    left join client as wh on wh.clientid = stock.whid
    where head.trno = $trno and stock.ref <> ''
    union all
    select stock.itemid, stock.ref, wh.client as wh
    from glhead as head
    left join glstock as stock on stock.trno = head.trno
    left join client as wh on wh.clientid = stock.whid
    where head.trno = $trno and stock.ref <> ''";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
    // Itira lang related sa stock.ref
  }

  public function reportplotting($params, $data)
  {
    switch ($params['params']['dataparams']['print']) {
      case 'excel':
        switch ($params['params']['dataparams']['reporttype']) {
          case 1:
            return $this->default_RR_badorder_layout($params, $data);
            break;
          default:
            return $this->default_RR_layout($params, $data);
            break;
        }
        break;
      default:
        switch ($params['params']['dataparams']['reporttype']) {
          case 1:
            return $this->default_RR_badorder_PDF($params, $data);
            break;
          default:
            return $this->default_RR_PDF($params, $data);
            break;
        }
        break;
    }
  }

  public function default_header($params, $data)
  {
    // $this->modulename = app('App\Http\Classes\modules\purchase\rr')->modulename;

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = ($params['params']['dataparams']['reporttype'] == 3) ? '1000' : '800';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, ($layoutsize == '1000') ? '810' : '610', null, false, $border, '', 'L', $font, '18', 'B', '', '', 0, '', 0, 2);
    $str .= $this->reporter->col('Document #:', '90', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '');
    // $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : '') . QrCode::size(100)->generate($data[0]['docno'] . '-' . $data[0]['trno']), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', ($layoutsize == '1000') ? '810' : '610', null, false, $border, '', 'L', $font, '18', 'B', '', '', 0, '', 0, 2);
    $str .= $this->reporter->col('Date:', '90', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier: ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), ($layoutsize == '1000') ? '710' : '510', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('Terms: ', '90', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '100', null, false, $border, 'B', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address: ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), ($layoutsize == '1000') ? '710' : '510', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('Ourref: ', '90', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '100', null, false, $border, 'B', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse: ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['whname']) ? $data[0]['whname'] : ''), ($layoutsize == '1000') ? '710' : '510', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('Yourref: ', '90', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '100', null, false, $border, 'B', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Carrier: ', '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['carrier']) ? $data[0]['carrier'] : ''), ($layoutsize == '1000') ? '710' : '510', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('Waybill: ', '90', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['waybill']) ? $data[0]['waybill'] : ''), '100', null, false, $border, 'B', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    switch ($params['params']['dataparams']['reporttype']) {
      case 2:
        $str .= $this->reporter->col('ITEM ID', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '70', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '70', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DESCRIPTION', '360', null, false, $border, 'TB', 'L', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'TB', 'R', $font, '12', 'B', '30px', '8px');
        break;
      case 3:
        $str .= $this->reporter->col('PO #', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DISCRIPTION', '230', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '80', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '80', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('BO QTY', '80', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('EXCESS', '80', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('WH', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REMARKS', '150', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        break;
      default:
        $str .= $this->reporter->col('PO #', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('QTY', '50', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('PCS', '50', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('DESCRIPTION', '200', null, false, $border, 'TB', 'L', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('WH', '100', null, false, $border, 'TB', 'L', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('REMARKS', '150', null, false, $border, 'TB', 'L', $font, '12', 'B', '30px', '8px');
        break;
    }

    $str .= $this->reporter->endrow();

    return $str;
  }

  public function default_RR_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $data2 = $this->report_Query($params);

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";
    $layoutsize = ($params['params']['dataparams']['reporttype'] == 3) ? '1000' : '800';

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $totalamt = 0;

    for ($i = 0; $i < count($data); $i++) {
      $amt = number_format($data[$i]['rrcost'], $decimal);
      $amt = $amt < 0 ? '-' : $amt;
      $netamt = number_format($data[$i]['netamt'], $decimal);
      $netamt = $netamt < 0 ? '-' : $netamt;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      // $ref = $data[$i]['ref'];

      switch ($params['params']['dataparams']['reporttype']) {
        case 2:
          $itemid = (string) $data[$i]['itemid'];
          $itemid = str_replace('I', '1', $this->othersClass->Padj('I' . $itemid, 12));

          $str .= $this->reporter->col($itemid, '100', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col(number_format($data[$i]['rrqty'], $this->companysetup->getdecimal('qty', $params['params'])), '70', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['uom'], '70', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['itemname'], '360', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col(number_format($data[$i]['rrcost'], $this->companysetup->getdecimal('price', $params['params'])), '100', null, false, $border, '', 'R', $font, '12', '', '30px', '4px');
          break;
        case 3:

          for ($j = 0; $j < count($data2); $j++) {
            if ($data[$i]['itemid'] == $data2[$j]['itemid'] && $data[$i]['wh'] == $data2[$j]['wh']) {
              $str .= $this->reporter->col($data2[$j]['ref'], '100', null, false, $border, '', 'C', $font, '12', '', '30px', '2px');
              break;
            }
            if ($j == count($data2) - 1) {
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, '12', '', '30px', '2px');
            }
          }

          if (!empty($data[$i]['boqty']) || !empty($data[$i]['excess'])) {
            $data[$i]['rrqty'] = max(0, $data[$i]['rrqty'] - ($data[$i]['excess'] + $data[$i]['boqty']));
          }
          $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, '12', '', '30px', '2px');
          $str .= $this->reporter->col($data[$i]['itemname'], '230', null, false, $border, '', 'L', $font, '12', '', '30px', '2px');
          $str .= $this->reporter->col($data[$i]['uom'], '80', null, false, $border, '', 'C', $font, '12', '', '30px', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['rrqty'], $this->companysetup->getdecimal('qty', $params['params'])), '80', null, false, $border, '', 'C', $font, '12', '', '30px', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['boqty'], $this->companysetup->getdecimal('qty', $params['params'])), '80', null, false, $border, '', 'C', $font, '12', '', '30px', '2px');
          $str .= $this->reporter->col(number_format($data[$i]['excess'], $this->companysetup->getdecimal('qty', $params['params'])), '80', null, false, $border, '', 'C', $font, '12', '', '30px', '2px');
          $str .= $this->reporter->col($data[$i]['whname'], '100', null, false, $border, '', 'L', $font, '12', '', '30px', '2px');
          $str .= $this->reporter->col($data[$i]['srem'], '150', null, false, $border, '', 'L', $font, '12', '', '30px', '2px');
          break;
        default:
          $str .= $this->reporter->col($data[$i]['ref'], '100', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col(number_format($data[$i]['rrqty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col(number_format($data[$i]['pcs'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['itemname'], '200', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['whname'], '100', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
          $str .= $this->reporter->col($data[$i]['srem'], '150', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
      }

      $totalamt += $data[$i]['rrcost'];

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    switch ($params['params']['dataparams']['reporttype']) {
      case 2:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col('TOTAL :', '360', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->col(number_format($totalamt, $decimal), '100', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
        $str .= $this->reporter->endrow();
        break;
      case 3:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '230', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->endrow();
        break;
      default:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '2px');
        $str .= $this->reporter->endrow();
        break;
    }

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], ($layoutsize == '1000') ? '750' : '550', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $colwidth = ($layoutsize == '1000') ? '333' : '266';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', $colwidth, null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Approved By :', $colwidth, null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Received By :', $colwidth, null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], $colwidth, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], $colwidth, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], $colwidth, null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function default_RR_badorder_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    // $fontsize = "11";
    $border = "1px dotted ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Fox No.:', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('Notes:', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BAD ORDER SLIP', '610', null, false, $border, '', 'C', $font, '18', 'B', '', '', 0, '', 0, 4);
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RR #:', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('DATE:', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '260', null, false, $border, 'TB', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '70', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('PO NO.', '100', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '70', null, false, $border, 'TB', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('PRICE', '100', null, false, $border, 'TB', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function default_RR_badorder_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px dotted";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_RR_badorder_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $ext = number_format($data[$i]['ext'], $decimal);
      $ext = $ext < 0 ? '-' : $ext;
      $netamt = number_format($data[$i]['netamt'], $decimal);
      $netamt = $netamt < 0 ? '-' : $netamt;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col($data[$i]['itemname'], '260', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col($data[$i]['uom'], '70', null, false, $border, '', 'C', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col($data[$i]['ourref'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col($data[$i]['rrqty'], '70', null, false, $border, '', 'C', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col($netamt, '100', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '4px');
      $str .= $this->reporter->col($ext, '100', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '4px');

      $totalext += $data[$i]['ext'];

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        // $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '260', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL:', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Warehouse in Charge ', '400', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('Approved By:', '400', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, '1px solid', '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '300', null, false, '1px solid', '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, '1px solid', 'T', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('Warehouse Manager', '300', null, false, '1px solid', 'T', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid', '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_RR_badorder_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select code, name, address, tel from center where code='" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = '';
    $fontbold = '';
    $fontsize = '11';
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

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'Fax No.: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]['fax']) ? $data[0]['fax'] : ''), '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'Notes: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false);

    PDF::SetFont($font, '', '3');
    PDF::MultiCell(700, 0, '', '', 'L', false);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(700, 0, 'BAD ORDER SLIP', '', 'C', false);

    PDF::SetFont($font, '', '3');
    PDF::MultiCell(700, 0, '', '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'RR #: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'Date: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 8);
    PDF::MultiCell(700, 0, '', ['T' => ['dash' => 2]]);

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(90, 0, "CODE", '', 'L', false, 0);
    PDF::MultiCell(220, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(110, 0, "PO NO.", '', 'L', false, 0);
    PDF::MultiCell(70, 0, "QTY", '', 'R', false, 0);
    PDF::MultiCell(80, 0, "PRICE", '', 'R', false, 0);
    PDF::MultiCell(80, 0, "AMOUNT", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_RR_badorder_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];
    $totalext = 0;

    $font = '';
    // $fontbold = '';
    // $border = '1px solid';
    $fontsize = '11';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      // $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_RR_badorder_header_PDF($params, $data);

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 0;

        $barcode = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $uom = $data[$i]['uom'];
        $ourref = $data[$i]['ourref'];
        $rrqty = number_format($data[$i]['rrqty'], $decimalqty);
        $netamt = number_format($data[$i]['netamt'], $decimalprice);
        $ext = number_format($data[$i]['ext'], $decimalcurr);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '12', 0);
        $arr_ourref = $this->reporter->fixcolumn([$ourref], '16', 0);
        $arr_qty = $this->reporter->fixcolumn([$rrqty], '12', 0);
        $arr_netamt = $this->reporter->fixcolumn([$netamt], '12', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '12', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_uom, $arr_ourref, $arr_qty, $arr_netamt, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(90, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '', '');
          PDF::MultiCell(220, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '');
          PDF::MultiCell(50, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '');
          PDF::MultiCell(110, 0, ' ' . (isset($arr_ourref[$r]) ? $arr_ourref[$r] : ''), '', 'L', false, 0, '', '');
          PDF::MultiCell(70, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '');
          PDF::MultiCell(80, 0, ' ' . (isset($arr_netamt[$r]) ? $arr_netamt[$r] : ''), '', 'R', false, 0, '', '');
          PDF::MultiCell(80, 0, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false);
        }
        $totalext += $data[$i]['ext'];
        if (PDF::getY() > 900) {
          $this->default_RR_badorder_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(540, 0, '', '', 'L', false, 0);
    PDF::MultiCell(80, 0, 'Total: ', '', 'R', false, 0);
    PDF::MultiCell(80, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(250, 0, 'Warehouse in Charge', '', 'L', false, 0);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Approved By:', '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::MultiCell(250, 0, '', ['B' => ['dash' => 0]], 'L', false, 0);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, '', ['B' => ['dash' => 0]], 'L', false);

    PDF::SetFont($font, '', 4);
    PDF::MultiCell(250, 0, '', '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Warehouse Manager', '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_RR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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
    PDF::AddPage($reporttype == 3 ? 'l' : 'p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '120');

    switch ($reporttype) {
      case 3:
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(920, 0, '', '');
        PDF::MultiCell(740, 0, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, 'Doc #: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, $data[0]['docno'], 'B', 'L', false);

        PDF::MultiCell(740, 0, '', '', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, 'Date: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, $data[0]['dateid'], 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Supplier: ', '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(660, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, 'Terms: ', '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Address: ', '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(660, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, 'Ourref: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Warehouse: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(660, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, 'Yourref: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Carrier: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(660, 0, (isset($data[0]['carrier']) ? $data[0]['carrier'] : ''), 'B', 'L', false, 0);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(60, 0, 'Waybill: ', '', 'L', false, 0);
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['waybill']) ? $data[0]['waybill'] : ''), 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(920, 0, '', 'T');
        break;
      default:
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, '', '', 'L', false);
        PDF::MultiCell(550, 0, '', '', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, 'Doc #: ', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, $data[0]['docno'], 'B', 'L', false);

        PDF::MultiCell(550, 0, '', '', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, 'Date: ', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, $data[0]['dateid'], 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Ourref: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Warehouse: ', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, 'Yourref: ', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, 'Carrier: ', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['carrier']) ? $data[0]['carrier'] : ''), 'B', 'L', false, 0, '', '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, 'Waybill: ', '', 'L', false, 0, '', '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, (isset($data[0]['waybill']) ? $data[0]['waybill'] : ''), 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'T');
        break;
    }

    PDF::SetFont($font, 'B', 12);

    switch ($reporttype) {
      case 2:
        PDF::MultiCell(95, 0, 'ITEM ID', '', 'C', false, 0);
        PDF::MultiCell(120, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(65, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(70, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(220, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(120, 0, 'PRICE', '', 'R', false);
        break;
      case 3:
        PDF::MultiCell(120, 0, "PO #", '', 'C', false, 0);
        PDF::MultiCell(120, 0, "BARCODE", '', 'C', false, 0);
        PDF::MultiCell(220, 0, "DESCRIPTION", '', 'L', false, 0);
        PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "BO QTY", '', 'C', false, 0);
        PDF::MultiCell(50, 0, "EXCESS", '', 'C', false, 0);
        PDF::MultiCell(100, 0, "WH", '', 'C', false, 0);
        PDF::MultiCell(160, 0, 'REMARKS', '', 'L', false);
        break;
      default:
        PDF::MultiCell(110, 0, 'PO #', '', 'C', false, 0);
        PDF::MultiCell(100, 0, 'BARCODE', '', 'C', false, 0);
        PDF::MultiCell(45, 0, 'QTY', '', 'C', false, 0);
        PDF::MultiCell(60, 0, 'PCS', '', 'R', false, 0);
        PDF::MultiCell(50, 0, 'UNIT', '', 'C', false, 0);
        PDF::MultiCell(160, 0, 'DESCRIPTION', '', 'L', false, 0);
        PDF::MultiCell(75, 0, 'WH', '', 'C', false, 0);
        PDF::MultiCell(100, 0, 'REMARKS', '', 'L', false);
        break;
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell($reporttype == 3 ? 920 : 720, 0, '', 'B');
  }

  public function default_RR_PDF($params, $data)
  {
    $reporttype = $params['params']['dataparams']['reporttype'];
    $data2 = $this->report_Query($params);
    $font = "";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    }
    $this->default_RR_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell($reporttype == 3 ? 920 : 720, 0, '', '');

    $totalamt = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;

        $ref = '';
        $boqty = 0;
        $excess = 0;
        $arr_boqty = [];
        $arr_excess = [];

        switch ($reporttype) {
          case 3:
            for ($j = 0; $j < count($data2); $j++) {
              if ($data[$i]['itemid'] == $data2[$j]['itemid'] && $data[$i]['wh'] == $data2[$j]['wh']) {
                $ref = $data2[$j]['ref'];
                break;
              }
            }

            if (!empty($data[$i]['boqty']) || !empty($data[$i]['excess'])) {
              $data[$i]['rrqty'] = max(0, $data[$i]['rrqty'] - ($data[$i]['excess'] + $data[$i]['boqty']));
            }
            break;
          default:
            $ref = $data[$i]['ref'];
            break;
        }

        $itemid = (string) $data[$i]['itemid'];
        $itemid = str_replace('I', '1', $this->othersClass->Padj('I' . $itemid, 12));
        $barcode = $data[$i]['barcode'];
        $rrqty = number_format($data[$i]['rrqty'], 0);
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $whname = $data[$i]['whname'];
        $rem = $data[$i]['srem'];
        $pcs = number_format($data[$i]['pcs'], 0);
        $rrcost = number_format($data[$i]['rrcost'], 2);

        switch ($reporttype) {
          case 3:
            $boqty = number_format($data[$i]['boqty'], 0);
            $excess = number_format($data[$i]['excess'], 0);

            $arr_boqty = $this->reporter->fixcolumn([$boqty], '7', 0);
            $arr_excess = $this->reporter->fixcolumn([$excess], '7', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '15', 0);
            $arr_itemid = $this->reporter->fixcolumn([$itemid], '7', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '25', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '50', 0);
            $arr_qty = $this->reporter->fixcolumn([$rrqty], '7', 0);
            $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '7', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
            $arr_whname = $this->reporter->fixcolumn([$whname], '15', 0);
            $arr_pcs = $this->reporter->fixcolumn([$pcs], '7', 0);
            $arr_rrcost = $this->reporter->fixcolumn([$rrcost], '15', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '40', 0);
            break;
          default:
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
            $arr_itemid = $this->reporter->fixcolumn([$itemid], '7', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '23', 0);
            $arr_qty = $this->reporter->fixcolumn([$rrqty], '7', 0);
            $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '7', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
            $arr_whname = $this->reporter->fixcolumn([$whname], '15', 0);
            $arr_pcs = $this->reporter->fixcolumn([$pcs], '7', 0);
            $arr_rrcost = $this->reporter->fixcolumn([$rrcost], '15', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '17', 0);
            break;
        }

        $maxrow = $this->othersClass->getmaxcolumn([$arr_ref, $arr_itemid, $arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_whname, $arr_pcs, $arr_rrcost, $arr_rem, $arr_rrqty, $arr_boqty, $arr_excess]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);

          switch ($reporttype) {
            case 2:
              PDF::MultiCell(95, 15, ' ' . (isset($arr_itemid[$r]) ? $arr_itemid[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(120, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(65, 15, ' ' . (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(250, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(120, 0, ' ' . (isset($arr_rrcost[$r]) ? $arr_rrcost[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
              break;
            case 3:
              PDF::MultiCell(120, 0, ' ' . (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(120, 0, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(220, 0, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 0, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 0, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] = ($arr_qty[$r] == 0) ? '' : $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 0, ' ' . (isset($arr_boqty[$r]) ? $arr_boqty[$r] = ($arr_boqty[$r] == 0) ? '' : $arr_boqty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 0, ' ' . (isset($arr_excess[$r]) ? $arr_excess[$r] = ($arr_excess[$r] == 0) ? '' : $arr_excess[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 0, ' ' . (isset($arr_whname[$r]) ? $arr_whname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(160, 0, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
              break;
            default:
              PDF::MultiCell(110, 15, ' ' . (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(45, 15, ' ' . (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(60, 15, ' ' . (isset($arr_pcs[$r]) ? $arr_pcs[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(160, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(75, 15, ' ' . (isset($arr_whname[$r]) ? $arr_whname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
              PDF::MultiCell(100, 0, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
              break;
          }
        }

        $totalamt += $data[$i]['rrcost'];

        if (PDF::getY() > 900) {
          $this->default_RR_header_PDF($params, $data);
        }
      }
    }

    switch ($reporttype) {
      case 2:
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(720, 0, '', 'B');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(95, 15, '', '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(65, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(70, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(250, 15, 'GRAND TOTAL: ', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(120, 0, number_format($totalamt, 2), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        break;
      default:
        PDF::SetFont($font, '', 5);
        PDF::MultiCell($reporttype == 3 ? 920 : 720, 0, '', 'B');
        break;
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell($reporttype == 3 ? 920 : 720, 0, '', '');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell($reporttype == 3 ? 760 : 560, 0, $data[0]['rem'], '', 'L');
    PDF::MultiCell($reporttype == 3 ? 260 : 60, 0, $data[0]['rem'], '', 'L');

    $colwidth = $reporttype == 3 ? 306 : 253;
    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell($colwidth, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell($colwidth, 0, 'Stock Counted By: ', '', 'L', false, 0);
    PDF::MultiCell($colwidth, 0, 'Approved By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell($colwidth, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell($colwidth, 0, $params['params']['dataparams']['received'], '', 'L', false, 0);
    PDF::MultiCell($colwidth, 0, $params['params']['dataparams']['approved'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
