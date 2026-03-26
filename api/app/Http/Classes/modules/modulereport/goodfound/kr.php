<?php

namespace App\Http\Classes\modules\modulereport\goodfound;

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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class kr
{

  private $modulename = "Counter Receipt";
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
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => '1', 'color' => 'blue'],
      ['label' => 'Provisional Receipt', 'value' => '4', 'color' => 'blue'],
      ['label' => 'Provisional Receipt Shooting', 'value' => '5', 'color' => 'blue'],
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '1' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $report = $filters['params']['dataparams']['reporttype'];
    switch ($report) {
      case '4':
      case '5':
        $query = "select a.distributor,a.sodocno,right(a.cwodr,8) as cwodr,date(a.dateid) as dateid,sum(a.portqty) as port,sum(a.pozzqty) as pozz,
      case when sum(a.portqty)=0 then -1 else 1 end as portsort,
      case when sum(a.pozzqty)=0 then -1 else 1 end as pozzsort,
      (case when sum(a.portqty)=0 then 2 else 1 end)+(case when sum(a.pozzqty)=0 then 2 else 1 end) as sort
      from(
      select sjhead.clientname as distributor,
      case when trans.bref='ND' then concat(client.areacode,trans.docno) else trans.docno end as sodocno,
      ifnull(trans.bref,'') as bref,
      icat.name as category,
      sjhead.docno as cwodr,
      sjhead.dateid,
      (case when icat.line=1 then sum(sjstock.isqty) else 0 end) as portqty,
      (case when icat.line=3114 then sum(sjstock.isqty) else 0 end) as pozzqty
      from arledger as ledger
      left join glstock as sjstock on sjstock.trno=ledger.trno
      left join glhead as sjhead on sjhead.trno=sjstock.trno
      left join item as i on i.itemid=sjstock.itemid
      left join itemcategory as icat on icat.line=i.category
      left join transnum as trans on trans.trno=sjstock.refx
      left join client on client.clientid=sjhead.clientid
      where ledger.kr=$trno
      group by sjhead.clientname,trans.bref,trans.docno,client.areacode,
      icat.name,icat.line,
      sjhead.docno,sjhead.dateid
      ) as a
      where a.sodocno is not null
      group by distributor,sodocno,cwodr,dateid
      order by dateid,sodocno,sort asc";

        break;

      default:
        $query = "
      select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
      head2.yourref as krourref,
      coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, ar.docno as ref,client.tel
      from (krhead as head 
      left join arledger as ar on ar.kr=head.trno)
      left join coa on coa.acnoid=ar.acnoid
      left join glhead as head2 on head2.trno = ar.trno 
      left join client on client.client = head.client
      where head.trno='$trno'
      union all
      select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
      head2.yourref as krourref,
      coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, ar.docno as ref,client.tel
      from (hkrhead as head 
      left join arledger as ar on ar.kr=head.trno)
      left join coa on coa.acnoid=ar.acnoid
      left join glhead as head2 on head2.trno = ar.trno 
      left join client on client.client = head.client
      where head.trno='$trno' order by postdate,dateid, docno";
        break;
    }


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }


  public function reportplotting($params, $data)
  {
    $type = $params['params']['dataparams']['reporttype'];

    switch ($type) {
      case '1': // default
        return $this->default_KR_PDF($params, $data);
        break;

      case '2':
        return $this->provi_PDF($params, $data);
        break;

      case '4':
        return $this->provisionalReceiptCategorized_PDF($params, $data);
        break;
      case '5':
        return $this->provisionalReceiptCategorizedShooting_PDF($params, $data);
        break;
    } // end switch ($type)

  } // end fn



  public function provisionalReceiptCategorizedShooting_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $trno = $params['params']['dataid'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    // $this->provisionalReceiptCategorized_header_PDF($params, $data);


    //$width = 800; $height = 1000;



    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(40, 40);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetCellPaddings(2, 2, 2, 2);


    $sodocno = '';
    $sodisplay = 0;
    $count = 0;
    $totalportqty = 0;
    $totalpozzqty = 0;
    $totalamt = 0;

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(300, 21.5, $data[0]['distributor'], '', 'L', 0, 1, '190', '100');
    

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(250, 0, $data[0]['sodocno'], '', 'L', 0, 1, '210', '120');

    $cc = 0;
    for ($i = 0; $i < count($data); $i++) {

      if ($data[$i]['port'] != 0) {
        $hgt = 16;
        $hgt *= $count;
        PDF::SetFont($font, '', $fontsize - 1);
        PDF::MultiCell(120, 0, $data[$i]['dateid'], '', 'L', 0, 0, 35, 184 + $hgt);
        PDF::MultiCell(120, 0, $data[$i]['cwodr'], '', 'L', 0, 0, 125, 184 + $hgt);
        PDF::MultiCell(120, 0, number_format($data[$i]['port'], 2), '', 'R', 0, 0, 145, 184 + $hgt);
        $count += 1;
      }
    }

    $count = 0;
    for ($i = 0; $i < count($data); $i++) {


      if ($data[$i]['pozz'] != 0) {
        $hgt = 16;
        $hgt *= $count;
        PDF::MultiCell(120, 0, $data[$i]['dateid'], '', 'L', 0, 0, 300, 184 + $hgt);
        PDF::MultiCell(120, 0, $data[$i]['cwodr'], '', 'L', 0, 0, 405, 184 + $hgt);
        PDF::MultiCell(120, 0, number_format($data[$i]['pozz'], 2), '', 'R', 0, 0, 430, 184 + $hgt);
        $count += 1;
      }
      $totalportqty += $data[$i]['port'];
      $totalpozzqty += $data[$i]['pozz'];
      $cc += 1;
    }

    if ($cc < 20) {
      for ($c = $cc; $c < 20; $c++) {
        PDF::MultiCell(120, 0, '', '', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', '', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', '', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', '', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', '', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', '', 'C', 0, 1);
      }
    }
    //  -- cut
    $prdoc = json_decode(json_encode($this->coreFunctions->opentable("select right(docno,8) as prdoc from transnum where trno=$trno")), true);
    $amt = json_decode(json_encode($this->coreFunctions->opentable("select sum(db) as amt from arledger where kr=$trno")), true);
    $totalamt = $amt[0]['amt'];


    PDF::MultiCell(120, 0, number_format($totalportqty, 2), '', 'r', 0, 0, 145, 490);

    PDF::MultiCell(120, 0, number_format($totalpozzqty, 2), '', 'r', 0, 1, 375, 490);



    PDF::MultiCell(600, 0, number_format($totalamt, 2), '', 'r', 0, 1, 220, 507);

    PDF::SetFont($font, '', $fontsize + 7);
    PDF::MultiCell(150, 0, $prdoc[0]['prdoc'], '', 'C', 0, 1, 325, 708);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function provisionalReceiptCategorized_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select left(name,29) as name1,substring(name,29,length(name)) as name2,address,tel from center where code = '" . $center . "'";
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
    PDF::SetMargins(40, 40);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetCellPaddings(2, 2, 2, 2);

    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name1), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name2), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address), '', 'C');


    PDF::setFontSpacing(3);
    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(720, 0, 'PROVISIONAL RECEIPT', '', 'C');
    PDF::SetFont($fontbold, '', $fontsize + 3);
    PDF::setFontSpacing(0);
    PDF::MultiCell(120, 0, 'DISTRIBUTOR : ', '', 'L', 0, 0);
    PDF::SetFont($font, '', $fontsize + 2);
    PDF::MultiCell(120, 21.5, $data[0]['distributor'], '', 'L', 0, 1);
  }

  public function provisionalReceiptCategorized_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $trno = $params['params']['dataid'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    $this->provisionalReceiptCategorized_header_PDF($params, $data);

    $sodocno = '';
    $sodisplay = 0;
    $count = 0;
    $totalportqty = 0;
    $totalpozzqty = 0;
    $totalamt = 0;


    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      if ($sodocno == '') {
        $sodocno = $data[$i]['sodocno'];
      }
      PDF::SetFont($fontbold, '', $fontsize);
      if ($sodocno != $data[$i]['sodocno']) {
        $sodocno = $data[$i]['sodocno'];
        PDF::MultiCell(720, 0, '', 'T', 'C', 0, 1);
        PDF::MultiCell(120, 0, 'SALES ORDER NO. : ', '', 'L', 0, 0);
        PDF::SetFont($font, '', $fontsize - 1);
        PDF::MultiCell(120, 0, $data[$i]['sodocno'], '', 'L', 0, 1);

        PDF::MultiCell(360, 0, 'PORTLAND', 'TL', 'C', 0, 0);
        PDF::MultiCell(360, 0, 'POZZOLAN', 'TLR', 'C', 0, 1);

        PDF::MultiCell(120, 0, 'DATE', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, 'CWODR NO.', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, 'QUANTITY', 'TLR', 'C', 0, 0);
        PDF::MultiCell(120, 0, 'DATE', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, 'CWODR NO.', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, 'QUANTITY', 'TLR', 'C', 0, 1);
      } else {
        if ($sodisplay == 0) {
          PDF::SetFont($fontbold, '', $fontsize);
          PDF::MultiCell(120, 0, 'SALES ORDER NO. : ', '', 'L', 0, 0);
          PDF::SetFont($font, '', $fontsize - 1);
          PDF::MultiCell(120, 0, $data[$i]['sodocno'], '', 'L', 0, 1);

          PDF::MultiCell(360, 0, 'PORTLAND', 'TL', 'C', 0, 0);
          PDF::MultiCell(360, 0, 'POZZOLAN', 'TLR', 'C', 0, 1);

          PDF::MultiCell(120, 0, 'DATE', 'TL', 'C', 0, 0);
          PDF::MultiCell(120, 0, 'CWODR NO.', 'TL', 'C', 0, 0);
          PDF::MultiCell(120, 0, 'QUANTITY', 'TLR', 'C', 0, 0);
          PDF::MultiCell(120, 0, 'DATE', 'TL', 'C', 0, 0);
          PDF::MultiCell(120, 0, 'CWODR NO.', 'TL', 'C', 0, 0);
          PDF::MultiCell(120, 0, 'QUANTITY', 'TLR', 'C', 0, 1);
          $sodisplay = 1;
        }
      }

      PDF::SetFont($font, '', $fontsize - 1);

      if ($data[$i]['port'] == 0) {
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TLR', 'C', 0, 0);
      } else {
        PDF::MultiCell(120, 0, $data[$i]['dateid'], 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, $data[$i]['cwodr'], 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, number_format($data[$i]['port'], 2), 'TLR', 'C', 0, 0);
      }

      if ($data[$i]['pozz'] == 0) {
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TLR', 'C', 0, 1);
      } else {
        PDF::MultiCell(120, 0, $data[$i]['dateid'], 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, $data[$i]['cwodr'], 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, number_format($data[$i]['pozz'], 2), 'TLR', 'C', 0, 1);
      }
      $count += 1;
      $totalportqty += $data[$i]['port'];
      $totalpozzqty += $data[$i]['pozz'];
    }
    if ($count < 20) {
      for ($c = $count; $c < 20; $c++) {
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TLR', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
        PDF::MultiCell(120, 0, '', 'TLR', 'C', 0, 1);
      }
    }
    $amt = json_decode(json_encode($this->coreFunctions->opentable("select sum(db) as amt from arledger where kr=$trno")), true);
    $totalamt = $amt[0]['amt'];

    PDF::MultiCell(120, 0, 'TOTAL : ', 'TL', 'L', 0, 0);
    PDF::MultiCell(120, 0, number_format($totalportqty, 2), 'T', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TR', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'TOTAL : ', 'TL', 'L', 0, 0);
    PDF::MultiCell(120, 0, number_format($totalpozzqty, 2), 'T', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TR', 'C', 0, 1);


    PDF::MultiCell(120, 0, 'TOTAL AMOUNT : ', 'LT', 'L', 0, 0);
    PDF::MultiCell(600, 0, number_format($totalamt, 2), 'TR', 'L', 0, 1);

    PDF::MultiCell(720, 0, 'LESS DEDUCTIONS : ', 'TLR', 'L', 0, 1);

    PDF::MultiCell(720, 0, 'NET AMOUNT : ', 'TLR', 'L', 0, 1);

    PDF::MultiCell(600, 0, 'DETAILS OF PAYMENT', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'REMARK', 'TLR', 'C', 0, 1);

    PDF::MultiCell(120, 0, 'BANK', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'BRANCH', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'CHECK NO.', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'DATE', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'AMOUNT', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TLR', 'C', 0, 1);

    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);


    PDF::MultiCell(600, 0, 'TOTAL AMOUNT PAID:', 'TL', 'L', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(600, 0, 'AMOUNT PAID IN WORDS:', 'TL', 'L', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(600, 0, '', 'L', 'L', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(600, 0, '', 'L', 'L', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);


    PDF::MultiCell(240, 0, 'COLLECTED BY:', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'DATE', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'PR NO.', 'TL', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'TL', 'L', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(240, 0, '', 'L', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'L', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'L', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'L', 'L', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);

    PDF::MultiCell(240, 0, '', 'L', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'L', 'C', 0, 0);
    PDF::MultiCell(120, 0, 'PR-', 'L', 'C', 0, 0);
    PDF::MultiCell(120, 0, '', 'L', 'L', 0, 0);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1);



    PDF::MultiCell(720, 0, '', 'T', 'C', 0, 1);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  public function default_KR_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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

    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');

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
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_KR_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_KR_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $acno = $data[$i]['acno'];
        $acnoname = $data[$i]['acnoname'];
        $ref = $data[$i]['ref'];
        $postdate = $data[$i]['postdate'];
        $debit = number_format($data[$i]['db'], $decimalcurr);
        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $client = $data[$i]['client'];
        $debit = $debit < 0 ? '-' : $debit;
        $credit = $credit < 0 ? '-' : $credit;

        $arr_acno = $this->reporter->fixcolumn([$acno], '16', 0);
        $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '35', 0);
        $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);
        $arr_postdate = $this->reporter->fixcolumn([$postdate], '16', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
        $arr_client = $this->reporter->fixcolumn([$client], '16', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_ref, $arr_postdate, $arr_debit, $arr_credit, $arr_client]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(90, 0, (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(160, 0, (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->default_KR_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(425, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(85, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(85, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

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

  public function provi_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    PDF::SetMargins(40, 40);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetCellPaddings(2, 2, 2, 2);

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    PDF::MultiCell(0, 0, '');

    PDF::SetFont($fontbold, '', 16);
    PDF::MultiCell(720, 0, 'PROVISIONAL RECEIPT', '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(720, 0, 'DISTRIBUTOR : ', '', 'L');
    PDF::MultiCell(720, 0, 'SALES ORDER NO.', '', 'L');

    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::MultiCell(360, 0, 'PORTLAND', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(360, 0, 'POZZOLAN', 'TLBR', 'C', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(120, 0, 'DATE', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'CWORD NO.', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'QUANTITY', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'DATE', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'CWORD NO.', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'QUANTITY', 'TLBR', 'C', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);

    for ($i = 0; $i < 10; $i++) {  // loop for stock

      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }

    PDF::MultiCell(240, 0, 'TOTAL : ', 'TLB', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'BAGS', 'TBR', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(240, 0, 'TOTAL : ', 'TLB', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'BAGS', 'TBR', 'L', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(720, 0, 'LESS DEDUCTIONS :', 'TLBR', 'L', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(720, 0, 'NET AMOUNT :', 'TLBR', 'L', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);


    PDF::MultiCell(600, 0, 'DETAILS OF PAYMENT', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'REMARK', 'TBR', 'L', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(120, 0, 'BANK', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'BRANCH', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'CHECK NO.', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'DATE', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, 'AMOUNT', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);


    for ($i = 0; $i < 5; $i++) { // loop for details

      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'TLBR', 'C', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
      PDF::MultiCell(120, 0, '', 'LR', 'C', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);
    }

    PDF::MultiCell(600, 0, 'TOTAL AMOUNT PAID : ', 'TLBR', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(120, 0, '', 'LR', 'L', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(600, 50, 'AMOUNT PAID IN WORDS :', 'TLBR', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(120, 50, '', 'LR', 'L', 0, 1, '',  '', true, 0, false, true, 0, 'M', true);


    PDF::MultiCell(240, 50, 'COLLECTED BY', 'TLBR', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(120, 50, 'DATE', 'TLRB', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(120, 50, 'PR NO.', 'TLRB', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(120, 50, '', 'TLRB', 'L', 0, 0, '',  '', true, 0, false, true, 0, 'T', true);
    PDF::MultiCell(120, 50, '', 'LRB', 'L', 0, 1, '',  '', true, 0, false, true, 0, 'T', true);




    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
