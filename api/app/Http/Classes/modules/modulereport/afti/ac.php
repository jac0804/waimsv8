<?php

namespace App\Http\Classes\modules\modulereport\afti;

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
use Illuminate\Support\Facades\URL;
use App\Http\Classes\reportheader;

class ac
{

  private $modulename;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $fieldClass;
  private $reporter;
  private $logger;
  private $reportheader;
  private $DNS1;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->DNS1 = new DNS1D;
    $this->reportheader = new reportheader;
  }

  public function createreportfilter($config)
  {
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'checked', 'payor', 'tin', 'position', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'prepared.type', 'lookup');
    data_set($col1, 'prepared.action', 'lookuppreparedby');
    data_set($col1, 'prepared.lookupclass', 'prepared');
    data_set($col1, 'prepared.readonly', true);

    data_set($col1, 'approved.type', 'lookup');
    data_set($col1, 'approved.action', 'lookuppreparedby');
    data_set($col1, 'approved.lookupclass', 'approved');
    data_set($col1, 'approved.readonly', true);

    data_set($col1, 'checked.type', 'lookup');
    data_set($col1, 'checked.action', 'lookuppreparedby');
    data_set($col1, 'checked.lookupclass', 'checked');
    data_set($col1, 'checked.readonly', true);

    data_set($col1, 'payor.type', 'lookup');
    data_set($col1, 'payor.action', 'lookuppreparedby');
    data_set($col1, 'payor.lookupclass', 'payor');
    data_set($col1, 'payor.readonly', true);

    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'Default', 'value' => '0', 'color' => 'magenta'],
        ['label' => 'BIR Form 2307 (New)', 'value' => '1', 'color' => 'blue']
      ]
    );

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
      'PDFM' as print,
      '' as prepared,
      'Elezandra Dela Cruz Tandayag' as approved,
      '' as received,
      '0' as reporttype,
      '' as checked,
      '' as payor,
      '' as tin,
      '' as position
      "
    );
  }

  public function report_default_query($params)
  {
    $reporttype = $params['params']['dataparams']['reporttype'];
    $trno = $params['params']['dataid'];

    switch ($reporttype) {
      case '0':
        return $this->default_ac_query($trno);
        break;
      case '1':
        return $this->bir_form_query($trno);
        break;
    }
  } //end fn

  public function default_ac_query($trno)
  {
    $query = "select head.docno,head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,date(stock.expiry) as expiry,stock.rem as srem,
        item.sizeid,m.model_name as model, b.addr as billaddress, brand.brand_desc as brandname, iteminfo.itemdescription,item.isvat as vattype,grp.name as itemgroup,head.yourref
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client as wh on wh.clientid = stock.whid
        left join client as supp on supp.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join billingaddr as b on b.line=supp.billid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as iteminfo on iteminfo.itemid = item.itemid
        left join projectmasterfile as grp on grp.line=item.projectid
        where head.trno='$trno' and head.doc='AC' 
        union all
        select head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.terms, head.rem,
        item.barcode, item.itemname, stock.rrcost as gross, stock.cost as netamt, stock.rrqty as qty,
        stock.uom, stock.disc, stock.ext, stock.line,wh.client as wh,wh.clientname as whname,stock.loc,
        date(stock.expiry) as expiry,stock.rem as srem,item.sizeid,m.model_name as model, b.addr as billaddress, brand.brand_desc as brandname, 
        iteminfo.itemdescription,item.isvat as vattype,grp.name as itemgroup,head.yourref
        from (glhead as head
        left join glstock as stock on stock.trno=head.trno)
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join client as supp on supp.clientid=head.clientid
        left join model_masterfile as m on m.model_id = item.model
        left join billingaddr as b on b.line=supp.billid
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join iteminfo as iteminfo on iteminfo.itemid = item.itemid
        left join projectmasterfile as grp on grp.line=item.projectid
        where head.trno='$trno' and head.doc='AC' 
        order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function bir_form_query($trno)
  {
    // BIR Form 2307
    $query = "select * from(
      select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
      head.address,detail.rem, head.yourref, head.ourref,client.tin,
      coa.acno, coa.acnoname, detail.ref,detail.postdate,
      detail.db, detail.cr, detail.client as dclient, detail.checkno,
      detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
      client.zipcode
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno
      left join client on client.client=head.client
      left join ewtlist on ewtlist.code = detail.ewtcode
      left join coa on coa.acnoid=detail.acnoid
      where head.doc='AC' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
      union all
      select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
      head.address,detail.rem, head.yourref, head.ourref,client.tin,
      coa.acno, coa.acnoname, detail.ref, detail.postdate,
      detail.db, detail.cr, dclient.client as dclient, detail.checkno,
      detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
      client.zipcode
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno
      left join client on client.clientid=head.clientid
      left join coa on coa.acnoid=detail.acnoid
      left join client as dclient on dclient.clientid=detail.clientid
      left join ewtlist on ewtlist.code = detail.ewtcode
      where head.doc='AC' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
      as tbl order by tbl.ewtdesc";

    $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    $arrs = [];
    $arrss = [];
    $ewt = '';
    foreach ($result1 as $key => $value) {
      $ewtrateval = floatval($value['ewtrate']) / 100;
      if ($value['db'] == 0) {
        //FOR CR
        if ($value['cr'] < 0) {
          $db = $value['cr'];
        } else {
          $db = floatval($value['cr']) * -1;
        } //end if

        if ($value['isvewt'] == 1) {
          $db = $db / 1.12;
        }

        $ewtamt = $db * $ewtrateval;
      } else {
        //FOR DB
        if ($value['db'] < 0) {
          $db = floatval($value['db']) * -1;
        } else {
          $db = $value['db'];
        } //end if

        if ($value['isvewt'] == 1) {
          $db = $db / 1.12;
        }
        $ewtamt = $db * $ewtrateval;
      } //end if

      if ($ewt != $value['ewtcode']) {
        $arrs[$value['ewtcode']]['oamt'] = $db;
        $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
        $arrs[$value['ewtcode']]['month'] = $value['month'];
      } else {
        array_push($arrss, $arrs);
        $arrs[$value['ewtcode']]['oamt'] = $db;
        $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
        $arrs[$value['ewtcode']]['month'] = $value['month'];
      }

      $ewt = $value['ewtcode'];
    } //end for each

    array_push($arrss, $arrs);
    $keyers = '';
    $finalarrs = [];

    foreach ($arrss as $key => $value) {
      foreach ($value as $key => $y) {
        if ($keyers == '') {
          $keyers = $key;
          $finalarrs[$key]['oamt'] = $y['oamt'];
          $finalarrs[$key]['xamt'] = $y['xamt'];
        } else {
          if ($keyers == $key) {
            $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($y['oamt']);
            $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($y['xamt']);
          } else {
            $finalarrs[$key]['oamt'] = $y['oamt'];
            $finalarrs[$key]['xamt'] = $y['xamt'];
          } //end if
        } //end if
        $finalarrs[$key]['month'] = $y['month'];
      }
    } //end for each
    if (empty($result1)) {
      $returnarr[0]['payee'] = '';
      $returnarr[0]['tin'] = '';
      $returnarr[0]['address'] = '';
      $returnarr[0]['month'] = '';
      $returnarr[0]['yr'] = '';
    } else {
      $returnarr[0]['payee'] = $result1[0]['clientname'];
      $returnarr[0]['tin'] = $result1[0]['tin'];
      $returnarr[0]['address'] = $result1[0]['address'];
      $returnarr[0]['month'] = $result1[0]['month'];
      $returnarr[0]['yr'] = $result1[0]['yr'];
    }

    $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
    return $result;
  }

  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0':
        return $this->rpt_AC_PDF($params, $data);
        break;
      case '1':
        return $this->bir_pdf_new($params, $data);
        break;
    }
  }

  public function default_header($params, $data)
  {
    $this->modulename = app('App\Http\Classes\modules\purchase\ac')->modulename;

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : '') . QrCode::size(100)->generate($data[0]['docno'] . '-' . $data[0]['trno']), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    //$str .= $this->reporter->col(DNS1D::getBarcodeHTML($data[0]['docno'].'-'.$data[0]['trno'], 'C39+', 1, 33, 'black', true), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '70', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('EXPIRY', '100px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DISC', '50px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');

    return $str;
  }

  public function default_ac_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $ext = number_format($data[$i]['ext'], $decimal);
      $ext = $ext < 0 ? '-' : $ext;
      $netamt = number_format($data[$i]['netamt'], $decimal);
      $netamt = $netamt < 0 ? '-' : $netamt;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '400px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['expiry'], '100px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['gross'], $this->companysetup->getdecimal('price', $params['params'])), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ext, '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
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

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


  public function header_AC_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $fontsize9 = "9";
    $fontsize = "11";
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    $this->reportheader->getHeader($params);
    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($font, '');
    PDF::SetFont($fontbold, '', 12);

    PDF::MultiCell(50, 0, "", '', 'L', false, 0);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(50, 0, "", '', 'L', false, 0);
    PDF::MultiCell(350, 0, strtoupper($headerdata[0]->address), '', 'L');
    PDF::MultiCell(50, 0, "", '', 'L', false, 0);
    PDF::MultiCell(350, 0,  "VAT REG TIN: " . strtoupper($headerdata[0]->tin) . "\n\n\n", '', 'L');


    PDF::SetFont($fontbold, '', 20);
    PDF::MultiCell(500, 0, "JOB COMPLETION/", '', 'C', false);
    PDF::MultiCell(500, 0, "ACCOUNTS PAYABLE VOUCHER", '', 'C', false);

    PDF::MultiCell(0, 20, "\n");

    $supp = $data[0]['clientname'];
    $supplen = strlen($supp) / (strlen($supp) * 1.1);
    $padsupp = $supplen * 10;

    $bill = $data[0]['billaddress'];
    $billlen = 0;
    if ($bill != '') {
      $billlen = strlen($bill) / (strlen($bill) * 1.1);
    }

    $padbill = $billlen * 10;

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, $padsupp, "SUPPLIER: ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(325, $padsupp, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(55, $padsupp, 'RR/APV#: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, $padsupp, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    if (strlen($supp) > 75) {
      PDF::MultiCell(0, 0, "\n");
    }

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 0, "TIN: ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(120, 0, (isset($data[0]['supptin']) ? $data[0]['supptin'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(75, 0, 'CUSTOMER PO: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(130, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(55, 0, 'DATE: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, $padbill, "ADDRESS: ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(325, $padbill, (isset($data[0]['billaddress']) ? $data[0]['billaddress'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(55, $padbill, 'TERMS: ', '', 'R', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, $padbill, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

    if (strlen($bill) > 75) {
      PDF::MultiCell(0, 0, "\n");
    }

    PDF::MultiCell(0, 0, "\n\n");
    PDF::MultiCell(530, 0, '', 'T');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(66, 0, "ITEM GROUP", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "D E S C R I P T I O N", '', 'L', false, 0);
    PDF::MultiCell(66, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(66, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(66, 0, "UNIT PRICE", '', 'C', false, 0);
    PDF::MultiCell(66, 0, "TOTAL", '', 'C', false);

    PDF::MultiCell(530, 0, '', 'B');
  }

  public function rpt_AC_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 850;
    $totalext = 0;

    $font = '';
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    }
    $this->header_AC_PDF($config, $data, $font);

    // $arritemname = array();
    // $countarr = 0;

    $fontsize9 = "9";
    $height = 15;
    if (!empty($data)) {

      for ($i = 0; $i < count($data); $i++) {

        $ext = number_format($data[$i]['ext'], 2);
        $ext = $ext < 0 ? '-' : $ext;
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        $netamt = $netamt < 0 ? '-' : $netamt;
        $concat_itemname = $data[$i]['itemname'] . " " . $data[$i]['model'] . " " . $data[$i]['brandname'] . "\n" . $data[$i]['itemdescription'];
        $item_h = PDF::getStringHeight(400, $concat_itemname);
        $maxh = PDF::getStringHeight(400, $data[$i]['itemdescription']);
        if ($data[$i]['vattype'] == 1) {
          $vat = 'V';
        } else {
          $vat = 'NV';
        }

        if ($i == 0) {
          $maxh = $maxh - 10;
        }

        $ps1 = ceil(strlen($concat_itemname) / 40);
        $p1 = $height * $ps1;
        $height = max($p1, 15);

        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(66, $height, $data[$i]['itemgroup'], '', 'C', false, 0);
        PDF::MultiCell(200, $height, $concat_itemname, '', 'L', 0, 0, '', '', true, 0, false, true, 0);
        PDF::MultiCell(66, $height, number_format($data[$i]['qty']), '', 'C', false, 0);
        PDF::MultiCell(66, $height, $data[$i]['uom'], '', 'C', false, 0);
        PDF::MultiCell(66, $height, number_format($data[$i]['gross'], $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(46, $height, $ext, '', 'R', false, 0);
        PDF::MultiCell(20, $height, $vat, '', 'R');


        $totalext = $totalext + $data[$i]['ext'];
        $height = 15;
      }
    }


    //1-w, 2-h, 3-txt, 4-border = 0, 5-align = 'J', 6-fill = 0, 7-ln = 1, 8-x = '', 9-y = '', 10-reseth = true, 11-stretch = 0, 12-ishtml = false, 13-autopadding = true, 14-maxh = 0
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(530, 0, '', 'B');
    PDF::MultiCell(464, 0, 'PHP Grand Total: ', '', 'R', false, 0);
    PDF::MultiCell(66, 0, number_format($totalext, $decimalcurr), '', 'C');


    $trno = $data[0]['trno'];
    $usdqry = "select detail.acnoid,detail.fcr
      from ladetail as detail
      left join lahead as head on head.trno=detail.trno
      where detail.trno= $trno and detail.acnoid = '5202'
      union all
      select detail.acnoid,detail.fcr
      from gldetail as detail
      left join glhead as head on head.trno=detail.trno
      where detail.trno= $trno and detail.acnoid = '5202'";
    $qryresult = json_decode(json_encode($this->coreFunctions->opentable($usdqry)), true);

    if (!empty($qryresult[0]['fcr'])) {
      PDF::SetFont($font, '', $fontsize9);
      PDF::MultiCell(530, 0, '', '');
      PDF::MultiCell(464, 0, 'USD Grand Total: ', '', 'R', false, 0);
      PDF::MultiCell(66, 0, number_format($qryresult[0]['fcr'], $decimalcurr), '', 'C');
    }


    PDF::MultiCell(530, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    $this->AC_Details($config, $data[0]['trno']);




    PDF::SetFont($font, '', 2);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 0);
    PDF::MultiCell(65, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false);

    PDF::SetFont($font, '', 1);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(10, 0, '', '', 'R', false, 0);
    PDF::MultiCell(65, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(75, 0, '', '', 'R', false, 0);
    PDF::MultiCell(80, 0, '', '', 'R', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 0, "\n\n");


    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L');


    PDF::MultiCell(0, 0, "\n\n");

    //PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    //PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    //PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(200, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(23, 0, '', '', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(47, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, 'Approved By:', '', 'L', false);



    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(200, 0, $config['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(23, 0, '', '', 'C', false, 0);
    PDF::MultiCell(30, 0, '', '', 'C', false, 0);
    PDF::MultiCell(47, 0, '', '', 'C', false, 0);
    PDF::MultiCell(200, 0, $config['params']['dataparams']['approved'], 'B', 'C', false);


    // PDF::MultiCell(253, 0, $config['params']['dataparams']['received'], '', 'L');


    PDF::MultiCell(0, 0, "\n\n\n\n\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(560, 0, 'Page ' . PDF::PageNo() . ' of ' . PDF::getAliasNbPages(), '', 'R', false);
    // PDF::MultiCell(760, 0, "Page " . $this->reporter->PageNo($pdf) . "  ", '', 'R', false);

    // PDF::AddPage();
    // $b = 62;
    // for ($i = 0; $i < 1000; $i++) {
    //   PDF::SetTextColor(0, 55, 255);
    //   PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //   PDF::Cell(200, 5, 'AAAAAAAAAAAA ', '', 0, 'L', 0);
    //   PDF::Cell(90, 5, 'SSSSSSSSSSS ', '', 0, 'L', 0);
    //   PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(255, 255, 0)));      
    //   //PDF::MultiCell(0, 0, "\n");
    //   PDF::Ln(15);
    //   if($i==$b){
    //     PDF::AddPage();
    //     $b = $b + 62;
    //   }
    // }

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function AC_Details($config, $trno)
  {
    $fontsize = "11";
    $font = "";
    $fontbold = "";
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);

    $totaldb = 0;
    $totalcr = 0;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $fontsize9 = "9";
    PDF::SetFont($fontbold, 'B', $fontsize9);
    PDF::MultiCell(50, 0, "ACNO", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "DEBIT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "CREDIT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "ITEM GROUP", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "DEPARTMENT", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "BRANCH", '', 'C', false);

    PDF::MultiCell(530, 0, '', 'B');

    $qry2 = "select c.acno,c.acnoname,g.db as debit,g.cr as credit,ifnull(p.name,'') as itemgroup,
    ifnull(b.clientname,'') as branch,ifnull(d.clientname,'') as dept
    from glhead as h
    left join gldetail as g on g.trno=h.trno
    left join glstock as s on s.trno=g.trno and s.line=g.line
    left join coa as c on c.acnoid=g.acnoid
    left join projectmasterfile as p on p.line=s.projectid
    left join client as b on b.clientid=h.branch
    left join client as d on d.clientid=h.deptid
    where g.trno=$trno";


    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);
    $acc = "";
    $ig = "";
    $dept = "";
    $branch = "";
    $acclen = 0;
    $iglen = 0;
    $deptlen = 0;
    $branchlen = 0;

    $pad = 0;

    if (!empty($data2)) {

      for ($i = 0; $i < count($data2); $i++) {

        $db = number_format($data2[$i]['debit'], $decimalcurr);
        $cr = number_format($data2[$i]['credit'], $decimalcurr);
        $db = $db < 0 ? '-' : $db;
        $cr = $cr < 0 ? '-' : $cr;

        $acc = $data2[$i]['acnoname'];
        $acclen = strlen($acc) / 12;
        $pad = $acclen * 12;

        if (isset($data2[$i]['itemgroup'])) {
          $ig = $data2[$i]['itemgroup'];
          $iglen = strlen($ig) / 7;

          if ($pad < ($iglen * 10)) {
            $pad = $iglen * 10;
          }
        }

        if (isset($data2[$i]['dept'])) {
          $dept = $data2[$i]['dept'];
          $deptlen = strlen($dept) / 7;

          if ($pad < ($deptlen * 10)) {
            $pad = $deptlen * 10;
          }
        }

        if (isset($data2[$i]['branch'])) {
          $branch = $data2[$i]['branch'];
          $branchlen = strlen($branch) / 7;

          if ($pad < ($branchlen * 10)) {
            $pad = $branchlen * 10;
          }
        }
        PDF::SetFont($font, '', $fontsize9);
        PDF::MultiCell(50, $pad, $data2[$i]['acno'], '', 'L', false, 0);
        PDF::MultiCell(100, $pad, $data2[$i]['acnoname'], '', 'L', false, 0);
        PDF::MultiCell(75, $pad, $db, '', 'R', false, 0);
        PDF::MultiCell(75, $pad, $cr, '', 'R', false, 0);
        PDF::MultiCell(75, $pad, $data2[$i]['itemgroup'], '', 'C', false, 0);
        PDF::MultiCell(75, $pad, $data2[$i]['dept'], '', 'L', false, 0);
        PDF::MultiCell(75, $pad, $data2[$i]['branch'], '', 'L', false);


        $totaldb = $totaldb + $data2[$i]['debit'];
        $totalcr = $totalcr + $data2[$i]['credit'];
      }
    }

    PDF::MultiCell(150, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(75, 0, number_format($totaldb, $decimalcurr), 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, number_format($totalcr, $decimalcurr), 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(75, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(80, 0, '', 'T', 'R', false);
  }

  public function bir_form_ac_layout($params, $data)
  {
    $str = '';
    $count = 60;
    $page = 58;

    $birlogo = URL::to('/images/reports/birlogo.png');
    $birblogo = URL::to('/images/reports/birbarcode.png');
    $bir = URL::to('/images/reports/bir2307.png');
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->endtable();
    $str .= '';

    //1st row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For BIR&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspBCS/<br/>Use Only&nbsp&nbsp&nbspItem:', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="60px" height ="60px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue', '60', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<img src ="' . $bir . '">', '135', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Certificate of Creditable Tax <br> Withheld At Source', '450', null, false, '2px solid ', 'RTB', 'C', 'Century Gothic', '16', 'B', '', '');

    $str .= $this->reporter->col('<img src ="' . $birblogo . '" alt="BIR" width="200px" height ="50px">', '130', null, false, '2px solid ', 'TB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', 'RTB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"', '100', null, false, '2px solid ', 'LRTB', 'L', 'Century Gothic', '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    //2nd row blank
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //3rd row -> 1 for the period
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('1', '40', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('For the Period', '120', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('From', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');


    switch ($data['head'][0]['month']) {
      case '1':
      case '2':
      case '3':
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('03', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      case '4':
      case '5':
      case '6':
        $str .= $this->reporter->col('04', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col('06', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      case '7':
      case '8':
      case '9':
        $str .= $this->reporter->col('07', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');
        $str .= $this->reporter->col('09', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      default:
        $str .= $this->reporter->col('10', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');
        $str .= $this->reporter->col('12', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;
    }

    $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //5th row -> part 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part I-Payee Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //6th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //7th row -> 2 tax payer
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('2', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');
    $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //blank row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //9th row -> 3 payees name

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('3', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Payee`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //payees name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('4', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');
    $str .= $this->reporter->col('4A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //address name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col((isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // 5 foreign address

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('5', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Foreign Address, <i>if applicable <i/>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //f address box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '10px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //14th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //part II
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part II-Payor Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //16th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TIN payor
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('6', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');
    $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //payor
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('7', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Payor`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //Payor name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('8', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('8A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //address name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col((isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '2px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //22th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //part III
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part III-Details of Monthly Income Payments and Taxes Withheld', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //24th row -> income payments 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRT', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '380', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //25th row -> month header
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '2px');
    $str .= $this->reporter->col('ATC', '80', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('1st Month of the Quarter', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('2nd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('3rd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Total', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Tax Withheld For the Quarter', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //26th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //27th row -> line
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LTRB', 'C', 'Century Gothic', '12', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //28th row -> atc1
    $str .= $this->reporter->begintable('800');

    $total = 0;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;
    $a = -1;
    foreach ($data['detail'] as $key => $value) {
      $a++;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data['res'][$a]['ewtdesc'], '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '2px');
      $str .= $this->reporter->col($key, '80', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

      switch ($data['detail'][$key]['month']) {
        case '1':
        case '4':
        case '7':
        case '10':
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx1 +=  $data['detail'][$key]['oamt'];
          break;
        case '2':
        case '5':
        case '8':
        case '11':
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx2 +=  $data['detail'][$key]['oamt'];
          break;
        default:
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx3 +=  $data['detail'][$key]['oamt'];
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      $str .= $this->reporter->col($total, '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'], 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');

      $totalwtx += $data['detail'][$key]['oamt'];
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //29th row -> total
    $str .= $this->reporter->begintable('800');
    $totaltax = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx != 0 ? number_format($totalwtx, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');

    foreach ($data['detail'] as $key2 => $value2) {

      $totaltax = $totaltax + $data['detail'][$key2]['xamt'];
    }

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //30th row -> space for total 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //31th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //33th row -> declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent  to the processing of our information as contemplated under  the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful  purposes.', '800', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory from parameter
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col(ucwords($params['params']['dataparams']['payor']), '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['tin'], '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(ucwords($params['params']['dataparams']['position']), '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //line after signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent
      <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TAX Agent
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //39th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
        Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //42th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //43th row -> space after declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CONFORME:', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory from parameter
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //line after signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent
      <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TAX Agent
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //39th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
          Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //52th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function bir_pdf_new($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $border = '2px solid';
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
    PDF::SetMargins(10, 10);



    //Row 1 Logo
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(780, 10, '', '', 'L', false);
    PDF::MultiCell(50, 10, 'For BIR' . "\n" . 'Use Only', '', 'L', false, 0);
    PDF::MultiCell(50, 10, 'BCS/' . "\n" . 'Item:', '', 'L', false, 0);
    PDF::MultiCell(270, 10, '', '', 'L', false, 0);
    PDF::MultiCell(140, 10, 'Repupblic of the Philippines' . "\n" . 'Department of Finance' . "\n" . 'Bureau of Internal Revenue', '', 'C', false, 0);
    PDF::MultiCell(270, 10, '', '', 'L', false);
    PDF::Image(public_path() . '/images/afti/birlogo.png', '310', '10', 55, 55);

    //Row 2
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(120, 55, '', 'TBLR', 'L', false, 0, 10);
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(460, 55, 'Certificate of Credible Tax' . "\n" . 'Withheld at Source', 'TBLR', 'C', false, 0, 130);

    PDF::MultiCell(200, 55, '', 'TBLR', 'L', false, 1, 590);
    PDF::Image(public_path() . '/images/afti/bir2307.png', '12', '80', 103, 43);
    PDF::Image(public_path() . '/images/afti/birbarcode.png', '595', '80', 190, 43);

    //Row 3
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 10, 'Fill in all applicable spaces. Mark all appropriate boxes with an "X"', 'TBLR', 'L', false, 1, 10, 129);

    //Row 4
    $d1 = '';
    $m1 = '';
    $y1 = '';

    $d2 = '';
    $m2 = '';
    $y2 = '';

    switch ($data['head'][0]['month']) {
      case '1':
      case '2':
      case '3':
        $d1 = '01';
        $m1 = '01';
        $y1 = '18';

        $d2 = '03';
        $m2 = '31';
        $y2 = '18';
        break;

      case '4':
      case '5':
      case '6':
        $d1 = '04';
        $m1 = '01';
        $y1 = '18';

        $d2 = '06';
        $m2 = '30';
        $y2 = '18';
        break;

      case '7':
      case '8':
      case '9':
        $d1 = '07';
        $m1 = '01';
        $y1 = '18';

        $d2 = '09';
        $m2 = '30';
        $y2 = '18';
        break;

      default:
        $d1 = '10';
        $m1 = '01';
        $y1 = '18';

        $d2 = '12';
        $m2 = '31';
        $y2 = '18';
        break;
    }

    PDF::SetFont($font, '', 16);
    PDF::MultiCell(780, 10, '', 'LR', '', false, 0, 10, 142);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 10, '1', 'L', 'C', false, 0, 10, 145);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 10, 'For the Period', '', 'L', false, 0);
    PDF::MultiCell(90, 10, '', '', '', false, 0);
    PDF::MultiCell(35, 10, 'From', '', '', false, 0);
    PDF::MultiCell(20, 10, '', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(25, 15, $d1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $m1, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $y1, 'LTBR', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 10, '', '', '', false, 0);
    PDF::MultiCell(25, 15, $d2, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $m2, 'LTB', 'C', false, 0);
    PDF::MultiCell(25, 15, $y2, 'LTBR', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(75, 10, '(MM/DD/YY)', '', '', false, 0);
    PDF::MultiCell(95, 10, '', 'R', '', false);

    //Row 5
    PDF::MultiCell(780, 18, '', 'LTBR', '', false, 0, 10, 163);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part I - Payee Information', 'LTBR', 'C', false, 1, 10, 164);

    //Row 6
    PDF::MultiCell(780, 25, '', 'LTBR', '', false, 0);
    PDF::MultiCell(50, 25, '2', '', 'C', false, 0, 10, 185);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
    PDF::MultiCell(520, 18, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'LTBR', 'C', false, 0);
    PDF::MultiCell(10, 25, '', '', 'C', false);

    //Row 7
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '3', 'LT', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Payee's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'TR', 'L', false);

    //Row 8
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 9
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '4', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 15, '4A', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
    PDF::MultiCell(10, 15, '', 'R', 'L', false);

    //Row 10
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(630, 18, (isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, (isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 11
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '5', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Foreign Address If Applicable", 'R', 'L', false);

    //Row 12
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, "", 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);

    //Row 13
    PDF::MultiCell(780, 18, '', 'LRB', '', false, 1, 10, 295);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part II - Payor Information', 'LTRB', 'C', false);

    //Row 14
    PDF::MultiCell(780, 25, '', 'LTR', '', false, 0);
    PDF::MultiCell(50, 25, '6', '', 'C', false, 0, 10, 335);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(200, 25, 'Tax Payer Identification Number (TIN)', '', 'C', false, 0);
    PDF::MultiCell(520, 18, (isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), 'LTBR', 'C', false, 0);
    PDF::MultiCell(10, 25, '', '', 'C', false);

    //Row 15
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 25, '', 'LR', '', false, 1, 10, 340);
    PDF::MultiCell(50, 15, '7', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 15, "Payor's Name (Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)", 'R', 'L', false);

    //Row 16
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(720, 18, (isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);


    //Row 17
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(50, 15, '8', 'L', 'C', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(640, 15, "Registered Address", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(30, 15, '8A', '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(50, 15, 'Zipcode', '', 'L', false, 0);
    PDF::MultiCell(10, 15, '', 'R', 'L', false);

    //Row 18
    PDF::MultiCell(50, 18, '', 'L', '', false, 0);
    PDF::MultiCell(630, 18, (isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", '', 'L', false, 0);
    PDF::MultiCell(80, 18, (isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), 'LTRB', 'L', false, 0);
    PDF::MultiCell(10, 18, "", 'R', 'L', false);


    //Row 13
    PDF::MultiCell(780, 1, '', 'LRB', '', false, 1, 10, 425);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(780, 18, 'Part III - Details of Monthly Income Payments and Taxes Withheld', 'LTRB', 'C', false);

    //Row 14
    PDF::MultiCell(200, 20, '', 'LTR', 'C', false, 0, 10, 457);
    PDF::MultiCell(80, 20, '', 'LTR', 'C', false, 0, 210, 457);
    PDF::MultiCell(380, 20, 'AMOUNT OF INCOME PAYMENTS', 'LTR', 'C', false, 0, 290, 457);
    PDF::MultiCell(120, 20, '', 'LTR', 'C', false, 1, 670, 457);

    //Row 15
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(200, 20, 'Income Payments Subject to' . "\n" . ' Expanded Withholding Tax', 'LR', 'C', false, 0);
    PDF::MultiCell(80, 20, 'ATC', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '1st Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '2nd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, '3rd Month of the' . "\n" . 'Quarter', 'LTR', 'C', false, 0);
    PDF::MultiCell(95, 20, 'Total', 'LTR', 'C', false, 0);
    PDF::MultiCell(120, 20, 'Tax Withheld for the' . "\n" . 'Quarter', 'LTR', 'C', false, 1);

    //Row 16
    PDF::MultiCell(780, 20, '', 'T', '', false);

    //Row 17
    PDF::MultiCell(780, 20, '', 'T', '', false, 1, 10, 500);

    PDF::MultiCell(200, 20, '', 'LR', '', false, 0, 10, 500);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0, 210);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 290);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 385);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 480);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0, 575);

    PDF::MultiCell(120, 20, '', 'LR', 'R', false, 1, 670);

    //Row 18 ---atc1

    $total = 0;
    $a = -1;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;
    foreach ($data['detail'] as $key => $value) {
      $a++;
      PDF::MultiCell(200, 20, $data['res'][$a]['ewtdesc'], 'LRB', '', false, 0);
      PDF::MultiCell(80, 20, $key, 'LRB', '', false, 0);

      switch ($data['head'][0]['month']) {
        case '1':
        case '4':
        case '7':
        case '10':
          PDF::MultiCell(95, 20, number_format($data['detail'][$key]['oamt']), 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          $totalwtx1 +=  $data['detail'][$key]['oamt'];
          break;
        case '2':
        case '5':
        case '8':
        case '11':
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, number_format($data['detail'][$key]['oamt']), 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          $totalwtx2 +=  $data['detail'][$key]['oamt'];
          break;
        default:
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, '', 'LRB', '', false, 0);
          PDF::MultiCell(95, 20, number_format($data['detail'][$key]['oamt']), 'LRB', '', false, 0);
          $totalwtx3 +=  $data['detail'][$key]['oamt'];
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      PDF::MultiCell(95, 20, $total, 'LRB', '', false, 0);
      PDF::MultiCell(95, 20, number_format($data['detail'][$key]['xamt']), 'LRB', '', false, 0);

      $totalwtx += $data['detail'][$key]['oamt'];
    }

    //Row 19 ----total
    $totaltax = 0;
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'LR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), 'LR', '', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), 'LR', '', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), 'LR', '', false, 0);
    PDF::MultiCell(95, 20, ($totalwtx != 0 ? number_format($totalwtx, 2) : ''), 'LR', '', false, 0);
    foreach ($data['detail'] as $key => $value) {
      $totaltax = $totaltax + $data['detail'][$key]['xamt'];
    }
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(120, 20, number_format($totaltax, 2), 'LR', 'R', false);

    //Row 20 ---space for total 
    PDF::MultiCell(200, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'LR', 'R', false);

    //Row 21
    PDF::MultiCell(200, 10, 'Money Payments Subjects to', 'TLR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'TLR', 'R', false);

    PDF::MultiCell(200, 10, 'Withholding of Business Tax', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    PDF::MultiCell(200, 10, '(Government & Private)', 'LR', '', false, 0);
    PDF::MultiCell(80, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(95, 10, '', 'LR', '', false, 0);
    PDF::MultiCell(120, 10, '', 'LR', 'R', false);

    //Row 22
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);


    //Row 23
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //Row 24
    PDF::MultiCell(200, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, '', 'TLR', 'R', false);

    //Row 25
    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, '   Total', 'TLR', '', false, 0);
    PDF::MultiCell(80, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(95, 20, '', 'TLR', '', false, 0);
    PDF::MultiCell(120, 20, number_format($totaltax, 2), 'TLR', 'R', false);

    //Row 26
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 20, 'We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.', 'TLR', 'C', false);

    //Row 27
    PDF::MultiCell(10, 30, '', 'LT', '', false, 0);
    PDF::MultiCell(395, 30, ucwords($params['params']['dataparams']['payor']), 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'T', '', false, 0);
    PDF::MultiCell(175, 30, $params['params']['dataparams']['tin'], 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'T', '', false, 0);
    PDF::MultiCell(170, 30, ucwords($params['params']['dataparams']['position']), 'T', '', false, 0);
    PDF::MultiCell(10, 30, '', 'TR', '', false);

    //Row 28
    PDF::MultiCell(780, 30, 'Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

    //Row 29
    PDF::MultiCell(780, 30, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
    PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
    PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
    PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
    PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);

    //Row 30
    PDF::MultiCell(780, 15, 'CONFORME:', 'LTRB', 'C', false, 1, 10, 797);

    //Row 31
    PDF::MultiCell(780, 30, '', 'LTRB', '', false);

    //Row 32
    PDF::MultiCell(780, 30, 'Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent' . "\n" . '(Indicate Title/Designation and TIN)', 'LTRB', 'C', false);

    //Row 29
    PDF::MultiCell(780, 30, 'Tax Agent Accreditation No./' . "\n" . 'Attorney`s Roll No. (if applicable)', 'LTRB', 'L', false, 0);
    PDF::MultiCell(170, 25, '', 'LTRB', '', false, 0, 190);
    PDF::MultiCell(90, 25, 'Date of Issue' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 360);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 450);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 470);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 0, 490);
    PDF::MultiCell(50, 25, '', '', '', false, 0, 540);
    PDF::MultiCell(90, 25, 'Date of Expiry' . "\n" . '(MM/DD/YYY)', '', '', false, 0, 590);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 680);
    PDF::MultiCell(20, 25, '', 'LTRB', '', false, 0, 700);
    PDF::MultiCell(40, 25, '', 'LTRB', '', false, 1, 720);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
