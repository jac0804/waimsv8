<?php

namespace App\Http\Classes\modules\modulereport\seastar;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ll
{

  private $modulename = "Loading List";
  private $reportheader;
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
  }

  public function createreportfilter($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['radioprint', 'radioreporttype', 'loadedby', 'prepared', 'checked', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'loadedby.readonly', false);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Loading List', 'value' => '1', 'color' => 'red'],
      ['label' => 'Cargo Manifest', 'value' => '2', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];

    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
    $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);

    switch ($companyid) {
      default:
        $paramstr = "select
          'PDFM' as print,
          '1' as reporttype,
          '$username' as prepared,
          '' as loadedby,
          '' as checked";
        break;
    }

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($params)
  {
    $trno = $params['params']['dataid'];
    $report = $params['params']['dataparams']['reporttype'];
    switch ($report) {
      case 1:
        $query = "select head.trno,head.docno, date(head.dateid) as dateid,
                      head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,
                      round(sum(stock.isqty)) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,left(stock.ref,2) as refdoc,
                      right(stock.ref,6) as refnum,head.rem
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join hstockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                group by head.trno,head.docno, head.dateid,
                      head.yourref,head.ourref,
                      whto.clientname,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,wbinfo.unit,cs.clientname,head.rem
                    union all
                    select head.trno,head.docno, date(head.dateid) as dateid,
                      head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,
                      round(sum(stock.isqty)) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,left(stock.ref,2) as refdoc,right(stock.ref,6) as refnum,head.rem
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join stockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                group by head.trno,head.docno, head.dateid,
                      head.yourref,head.ourref,
                      whto.clientname,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,wbinfo.unit,cs.clientname,head.rem
                union all
                select head.trno,head.docno, date(head.dateid) as dateid,
                      head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,
                      round(sum(stock.isqty)) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,left(stock.ref,2) as refdoc,right(stock.ref,6) as refnum,head.rem
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join hcntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join hstockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                group by head.trno,head.docno, head.dateid,
                      head.yourref,head.ourref,
                      whto.clientname,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,wbinfo.unit, cs.clientname,head.rem
                      union all
                      select head.trno,head.docno, date(head.dateid) as dateid,
                      head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,
                      round(sum(stock.isqty)) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,left(stock.ref,2) as refdoc,right(stock.ref,6) as refnum,head.rem
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join hcntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join stockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                group by head.trno,head.docno, head.dateid,
                      head.yourref,head.ourref,
                      whto.clientname,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.ref,wbinfo.unit, cs.clientname,head.rem
                order by refnum";

        break;

      case 2:
        $query = "select head.trno,head.docno, date(head.dateid) as dateid,head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.line,left(stock.ref,2) as refdoc,right(stock.ref,6) as refnum,
                      round(stock.isqty) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,whfrom.clientname as whfrom,stock.isamt,
                      date(sinfo.wbdate) as wbdate ,sh.clientname as shipper
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join client as whfrom on whfrom.clientid=info.whfromid
                left join hstockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                left join glhead as sj on sj.trno=stock.refx
                left join client as sh on sh.clientid=sj.shipperid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                union all
                select head.trno,head.docno, date(head.dateid) as dateid,head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.line,left(stock.ref,2) as refdoc,right(stock.ref,6) as refnum,
                      round(stock.isqty) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,whfrom.clientname as whfrom,stock.isamt,
                      date(sinfo.wbdate) as wbdate ,sh.clientname as shipper
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join client as whfrom on whfrom.clientid=info.whfromid
                left join stockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                left join lahead as sj on sj.trno=stock.refx
                left join client as sh on sh.clientid=sj.shipperid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                union all
                select head.trno,head.docno, date(head.dateid) as dateid,head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.line,left(stock.ref,2) as refdoc,right(stock.ref,6) as refnum,
                      round(stock.isqty) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,whfrom.clientname as whfrom,stock.isamt,
                      date(sinfo.wbdate) as wbdate ,sh.clientname as shipper
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join hcntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join client as whfrom on whfrom.clientid=info.whfromid
                left join hstockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                left join glhead as sj on sj.trno=stock.refx
                left join client as sh on sh.clientid=sj.shipperid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                union all
                select head.trno,head.docno, date(head.dateid) as dateid, head.yourref,head.ourref,
                      whto.clientname as whto,info.vessel,info.plateno,head.ourref,info.voyageno,
                      info.sealno,info.unit,stock.line,left(stock.ref,2) as refdoc,right(stock.ref,6) as refnum,
                      round(stock.isqty) as isqty,wbinfo.unit as uom,
                      cs.clientname as consignee,whfrom.clientname as whfrom,stock.isamt,
                      date(sinfo.wbdate) as wbdate ,sh.clientname as shipper
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join hcntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join client as whfrom on whfrom.clientid=info.whfromid
                left join stockinfo as wbinfo on wbinfo.trno=stock.refx and wbinfo.line=stock.linex
                left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as cs on cs.clientid=sinfo.consignid
                left join lahead as sj on sj.trno=stock.refx
                left join client as sh on sh.clientid=sj.shipperid
                where head.doc='LL' and head.trno = $trno and wbinfo.trno is not null
                order by refnum";
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
    switch ($report) {
      case 1:
        return $this->LoadingList_PDF($params, $data);
        break;
      case 2:
        return $this->CargoManifest_PDF($params, $data);
        break;
    }
  }

  public function LoadingList_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
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

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');

    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(700, 0, 'LOADING LIST', '', 'C', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(467, 20, "  ", '', 'L', false, 0);
    PDF::MultiCell(233, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::MultiCell(467, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Document # : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(133, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(80, 20, "Destination : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(164, 20, (isset($data[0]['whto']) ? $data[0]['whto'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(90, 20, " CM No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(130, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(103, 20, " Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(133, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(80, 20, "Vessel : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(164, 20, (isset($data[0]['vessel']) ? $data[0]['vessel'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(90, 20, " Voyage No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(130, 20, (isset($data[0]['voyageno']) ? $data[0]['voyageno'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(103, 20, " B/L No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(133, 20, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(80, 20, "Van/Plate : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(164, 20, (isset($data[0]['plateno']) ? $data[0]['plateno'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(90, 20, " Seal No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(130, 20, (isset($data[0]['sealno']) ? $data[0]['sealno'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(103, 20, " Size : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(133, 20, (isset($data[0]['unit']) ? $data[0]['unit'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', 'T');
    PDF::MultiCell(100, 0, "", 'TR', '', false, 0);
    PDF::MultiCell(100, 0, "", 'TR', '', false, 0);
    PDF::MultiCell(100, 0, "", 'TR', '', false, 0);
    PDF::MultiCell(400, 0, "", 'T', '', false);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(100, 0, " WAYBILL NO", 'BR', 'L', false, 0);
    PDF::MultiCell(100, 0, "QUANTITY ", 'BR', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT ", 'BR', 'C', false, 0);
    PDF::MultiCell(400, 0, " CONSIGNEE", 'B', 'L', false);

    // PDF::SetFont($font, '', 5);
    // PDF::MultiCell(700, 0, '', 'B');
  }

  public function LoadingList_PDF($params, $data)
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
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->LoadingList_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(100, 0, ' ', 'R', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(100, 0, ' ', 'R', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(100, 0, ' ', 'R', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
    PDF::MultiCell(400, 0, ' ', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $ref = $data[$i]['refdoc'] . $data[$i]['refnum'];
        // $ref = $data[$i]['ref'];
        $qty = $data[$i]['isqty'];
        $uom = $data[$i]['uom'];
        $consignee = $data[$i]['consignee'];


        $arr_ref = $this->reporter->fixcolumn([$ref], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_consignee = $this->reporter->fixcolumn([$consignee], '70', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_ref, $arr_uom, $arr_qty, $arr_consignee]);
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), 'BR', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'BR', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(400, 15, ' ' . (isset($arr_consignee[$r]) ? $arr_consignee[$r] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['isqty'];

        if (PDF::getY() > 900) {
          $this->LoadingList_header_PDF($params, $data);
        }
      }
    }



    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(50, 15, 'Notes: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 15, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(253, 0, 'Loaded By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Checked By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(200, 15, $params['params']['dataparams']['loadedby'], 'B', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 15, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 15, $params['params']['dataparams']['checked'], 'B', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, '', '', 'C', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(200, 0, 'Date : ' . $data[0]['dateid'], '', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Date : ', '', 'L', false, 0);
    PDF::MultiCell(150, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(53, 0, '', '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function CargoManifest_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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
    PDF::AddPage('l', [1100, 800]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');


    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(1020, 0, 'CARGO MANIFEST', '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "From : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(580, 20, (isset($data[0]['whfrom']) ? $data[0]['whfrom'] : '') . '  -  ' . (isset($data[0]['whto']) ? $data[0]['whto'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(105, 20, " Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(255, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Vessel : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(255, 20, (isset($data[0]['vessel']) ? $data[0]['vessel'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, " Voyage No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(255, 20, (isset($data[0]['voyageno']) ? $data[0]['voyageno'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(105, 20, " CM No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(255, 20, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Van/Plate : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(255, 20, (isset($data[0]['plateno']) ? $data[0]['plateno'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(70, 20, " Seal No. : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(255, 20, (isset($data[0]['sealno']) ? $data[0]['sealno'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(105, 20, " Document # : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(255, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);



    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(70, 0, "", 'TR', 'L', false, 0);
    PDF::MultiCell(80, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(75, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(40, 0, "", 'TR', 'R', false, 0);
    PDF::MultiCell(80, 0, "", 'TR', 'C', false, 0);
    PDF::MultiCell(230, 0, "", 'TR', 'L', false, 0);
    PDF::MultiCell(240, 0, "", 'TR', 'L', false, 0);
    PDF::MultiCell(95, 0, "", 'TR', 'L', false, 0);
    PDF::MultiCell(95, 0, "", 'TR', 'L', false, 0);
    PDF::MultiCell(95, 0, "", 'T', 'L', false);


    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(70, 0, " WB No", 'BR', 'C', false, 0);
    PDF::MultiCell(80, 0, "Date", 'BR', 'C', false, 0);
    PDF::MultiCell(75, 0, "Dec Val ", 'BR', 'R', false, 0);
    PDF::MultiCell(40, 0, "Qty ", 'BR', 'R', false, 0);
    PDF::MultiCell(80, 0, "Unit", 'BR', 'C', false, 0);
    PDF::MultiCell(230, 0, "Shipper", 'BR', 'C', false, 0);
    PDF::MultiCell(240, 0, "Consignee", 'BR', 'C', false, 0);
    PDF::MultiCell(95, 0, "Prepaid", 'BR', 'C', false, 0);
    PDF::MultiCell(95, 0, "Collect", 'BR', 'C', false, 0);
    PDF::MultiCell(95, 0, "O.R. No.", 'B', 'C', false);
  }

  public function CargoManifest_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalamt = 0;
    $totalqty = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->CargoManifest_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $ref = $data[$i]['refdoc'] . $data[$i]['refnum'];
        $wbdate = $data[$i]['wbdate'];
        $amt = number_format($data[$i]['isamt'], 2);
        $qty = $data[$i]['isqty'];
        $uom = $data[$i]['uom'];
        $shipper = $data[$i]['shipper'];
        $consignee = $data[$i]['consignee'];

        $arr_ref = $this->reporter->fixcolumn([$ref], '17', 0);
        $arr_wbdate = $this->reporter->fixcolumn([$wbdate], '15', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_shipper = $this->reporter->fixcolumn([$shipper], '33', 0);
        $arr_consignee = $this->reporter->fixcolumn([$consignee], '36', 0);

        $maxrow = $this->othersClass->getmaxcolumn([
          $arr_ref, $arr_wbdate, $arr_uom, $arr_qty,
          $arr_amt, $arr_consignee, $arr_shipper
        ]);

        PDF::SetFont($font, '', 2);
        PDF::MultiCell(70, 0, '', 'R', '', false, 0);
        PDF::MultiCell(80, 0, '', 'R', '', false, 0);
        PDF::MultiCell(75, 0, '', 'R', '', false, 0);
        PDF::MultiCell(40, 0, '', 'R', '', false, 0);
        PDF::MultiCell(80, 0, '', 'R', '', false, 0);
        PDF::MultiCell(230, 0, '', 'R', '', false, 0);
        PDF::MultiCell(240, 0, '', 'R', '', false, 0);
        PDF::MultiCell(95, 0, '', 'R', '', false, 0);
        PDF::MultiCell(95, 0, '', 'R', '', false, 0);
        PDF::MultiCell(95, 0, '', '', '', false);


        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(70, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), 'R', 'C', false, 0, '',  '');
          PDF::MultiCell(80, 0, (isset($arr_wbdate[$r]) ? $arr_wbdate[$r] : ''), 'R', 'C', false, 0, '',  '');
          PDF::MultiCell(75, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), 'R', 'R', false, 0, '',  '');
          PDF::MultiCell(40, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), 'R', 'R', false, 0, '',  '');
          PDF::MultiCell(80, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), 'R', 'C', false, 0, '',  '');
          PDF::MultiCell(230, 0, ' ' . (isset($arr_shipper[$r]) ? $arr_shipper[$r] : ' '), 'R', 'L', false, 0, '',  '');
          PDF::MultiCell(240, 0, ' ' . (isset($arr_consignee[$r]) ? $arr_consignee[$r] : ' '), 'R', 'L', false, 0, '',  '');
          PDF::MultiCell(95, 0, '', 'R', 'L', false, 0, '',  '');
          PDF::MultiCell(95, 0, '', 'R', 'L', false, 0, '',  '');
          PDF::MultiCell(95, 0, '', '', 'L', false, 1, '',  '');
        }

        PDF::SetFont($font, '', 2);
        PDF::MultiCell(70, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(80, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(75, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(40, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(80, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(230, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(240, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(95, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(95, 0, '', 'RB', '', false, 0);
        PDF::MultiCell(95, 0, '', 'B', '', false);


        $totalamt += $data[$i]['isamt'];
        $totalqty += $data[$i]['isqty'];

        if (PDF::getY() > 730) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(110, 0, "Page " . PDF::PageNo() . "  ", '', 'L', false, 0);
          PDF::MultiCell(80, 0, '', '', 'L', false, 0);
          PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
          PDF::MultiCell(80, 0, ' ', '', 'R', false, 0);
          PDF::MultiCell(330, 0, '', '', 'R', false, 0);
          PDF::MultiCell(200, 0, '', '', 'L', false);
          $this->CargoManifest_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1020, 0, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 0, "Page " . PDF::PageNo() . "  ", '', 'L', false, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(80, 0, ' ', '', 'R', false, 0);
    PDF::MultiCell(330, 0, '', '', 'R', false, 0);
    PDF::MultiCell(200, 0, 'Prepared by : ' . $params['params']['dataparams']['prepared'], '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
