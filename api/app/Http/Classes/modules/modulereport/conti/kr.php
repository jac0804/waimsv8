<?php

namespace App\Http\Classes\modules\modulereport\conti;

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
    $fields = ['radioprint', 'prepared', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'prepared.label', 'Verified By');
    data_set($col1, 'approved.label', 'Receipt Acknowledged By');
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
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];
    $query = "
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
    head2.yourref as krourref,
    coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, head2.rem as rem2, ar.docno as ref,client.tel,head2.due,datediff(head.dateid,head2.due) as age,right(ag.client,4) as agent,client.tel,head2.rem as rem2
    from (krhead as head 
    left join arledger as ar on ar.kr=head.trno)
    left join coa on coa.acnoid=ar.acnoid
    left join glhead as head2 on head2.trno = ar.trno 
    left join client on client.client = head.client
    left join client as ag on ag.clientid=head2.agentid
    where head.trno='$trno'
    union all
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
    head2.yourref as krourref,
    coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, head.rem as rem2, ar.docno as ref,client.tel,head2.due,datediff(head.dateid,head2.due) as age,right(ag.client,4) as agent,client.tel,head2.rem as rem2
    from (hkrhead as head 
    left join arledger as ar on ar.kr=head.trno)
    left join coa on coa.acnoid=ar.acnoid
    left join glhead as head2 on head2.trno = ar.trno 
    left join client on client.client = head.client
    left join client as ag on ag.clientid=head2.agentid
    where head.trno='$trno' order by postdate,dateid, docno";
  

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_kr_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_KR_PDF($params, $data);
    }
  }

  public function rpt_default_header($data, $filters)
  {
    

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('COUNTER RECEIPT', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REFERENCE #', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_kr_layout($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    $totaldb = 0;
    $totalcr = 0;
    for ($i = 0; $i < count($data); $i++) {

      $debit = number_format($data[$i]['db'], $decimal);
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data[$i]['cr'], $decimal);
      $credit = $credit < 0 ? '-' : $credit;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['ref'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['postdate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['client'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $totaldb = $totaldb + $data[$i]['db'];
      $totalcr = $totalcr + $data[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '350', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, $decimal), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, $decimal), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  public function default_KR_header_PDF($params, $data)
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

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 0, $this->modulename, '', 'C');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 1, '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Tel No: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['tel']) ? $data[0]['tel'] : ''), '', 'L', false, 1, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, "Received the following document(s) for purposes of processing, rechecking and vouchering in order to expedite complete payment :", '', 'L', false, 1, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(80, 0, "DOC DATE", '', 'C', false, 0);
    PDF::MultiCell(130, 0, "DOC NUMBER", '', 'C', false, 0);
    PDF::MultiCell(180, 0, "REFERENCE #", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "DEBIT", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "CREDIT", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "REMARKS", '', 'L', false);

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
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_KR_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($data)) {

      for ($i = 0; $i < count($data); $i++) {


        $maxrow = 1;
        $date = $data[$i]['postdate'];
        $docno = $data[$i]['docno'];
        $ref = $data[$i]['ref'];
        $db = number_format($data[$i]['db'], $decimalcurr);
        $cr = number_format($data[$i]['cr'], $decimalcurr);
        if ($cr <= 0) $cr = '-';
        $rem = $data[$i]['rem2'];

        $arr_date = $this->reporter->fixcolumn([$date], '15');
        $arr_docno = $this->reporter->fixcolumn([$docno], '20');
        $arr_ref = $this->reporter->fixcolumn([$ref], '20');
        $arr_db = $this->reporter->fixcolumn([$db], '13');
        $arr_cr = $this->reporter->fixcolumn([$cr], '13');
        $arr_rem = $this->reporter->fixcolumn([$rem], '30');

        $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_docno, $arr_ref, $arr_db, $arr_cr, $arr_rem]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_date[$r]) ? $arr_date[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(130, 15, ' ' . (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(180, 15, ' ' . (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_db[$r]) ? $arr_db[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(80, 15, ' ' . (isset($arr_cr[$r]) ? $arr_cr[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(150, 15, ' ' . (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false);
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (PDF::getY() > 900) {
          $this->default_KR_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, 'TOTAL: ', '', 'C', false, 0);
    PDF::MultiCell(390, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(80, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(150, 0, number_format(($totaldb - $totalcr), $decimalprice), '', 'R', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, "NOTE: We hereby take full responsibility for the safekeeping of the above-mentioned documents. We shall hold you blameless in the event of loss, in which case the duplicate copy of the counter receipt we have acknowledged to have received and forwarded to you shall be conclusive evidence of our duty to you", '', 'L');

    PDF::MultiCell(0, 0, "\n");


    PDF::MultiCell(253, 0, 'Verified By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Receipt Acknowledged By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Date: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(220, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0);
    PDF::MultiCell(13, 0, '', '', '', false, 0);
    PDF::MultiCell(220, 0, $params['params']['dataparams']['approved'], 'B', 'L', false, 0);
    PDF::MultiCell(13, 0, '', '', '', false, 0);
    PDF::MultiCell(220, 0, '', 'B', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
