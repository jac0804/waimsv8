<?php

namespace App\Http\Classes\modules\modulereport\roosevelt;

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
use App\Http\Classes\reportheader;
use DateTime;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class cr
{

  private $modulename = "Received Payment";
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'Default', 'value' => '0', 'color' => 'blue'],
        ['label' => 'Collection Receipt', 'value' => '1', 'color' => 'blue']
      ]
    );

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $paramstr = "select
          'PDFM' as print,
           '0' as reporttype,
          '' as prepared,
          '' as approved,
          '' as received";
    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($filters)
  {
    $reporttype = $filters['params']['dataparams']['reporttype'];
    $trno = $filters['params']['dataid'];
    $print = $filters['params']['dataparams']['print'];

    switch ($print) {
      case 'default':
        $query = "
    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
    client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno) 
    left join coa on coa.acnoid=detail.acnoid) 
    left join client on client.client=detail.client
    where head.doc='cr' and head.trno='$trno'
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
    client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno) 
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid 
    where head.doc='cr' and head.trno='$trno' order by line";
        break;
      case 'PDFM':
        switch ($reporttype) {
          case '0': //default same sa main
            $query = "
          select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
          client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
          from ((lahead as head 
          left join ladetail as detail on detail.trno=head.trno) 
          left join coa on coa.acnoid=detail.acnoid) 
          left join client on client.client=detail.client
          where head.doc='cr' and head.trno='$trno'
          union all
          select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
          client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
          from ((glhead as head 
          left join gldetail as detail on detail.trno=head.trno) 
          left join coa on coa.acnoid=detail.acnoid)
          left join client on client.clientid=detail.clientid 
          where head.doc='cr' and head.trno='$trno' order by line";
            break;
          case '1': //NEW
            $query = "
            select  trno, dateid,docno, clientname, address, ref,checkno, sum(wtdb) as wtdb, sum(db) as db, sum(cr) as cr,tin,rem
            from (
          select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, 
          detail.ref,  detail.checkno,
           sum(case when left(coa.alias,2)='WT' then detail.db else 0 end) as wtdb,
          sum(case when left(coa.alias,2)!='WT' then detail.db else 0 end) as db,
           SUM(CASE WHEN detail.ref <> '' THEN detail.cr ELSE 0 END) AS cr,
          
          client.tin,head.rem
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno
          left join client on client.client=detail.client
          left join coa on coa.acnoid=detail.acnoid
          where head.doc='cr' and head.trno='$trno'
          group by trno,  head.dateid, docno, clientname, address, ref, checkno, tin,rem
          union all
          select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, 
           detail.ref,  detail.checkno,
           
            sum(case when left(coa.alias,2)='WT' then detail.db else 0 end) as wtdb,
          sum(case when left(coa.alias,2)!='WT' then detail.db else 0 end) as db,
           SUM(CASE WHEN detail.ref <> '' THEN detail.cr ELSE 0 END) AS cr,
           
           
           client.tin,head.rem
          from glhead as head 
          left join gldetail as detail on detail.trno=head.trno
          left join client on client.clientid=detail.clientid 
          left join coa on coa.acnoid=detail.acnoid
          where head.doc='cr' and head.trno='$trno'
          group by trno,  head.dateid, docno, clientname, address, ref, checkno, tin,rem  order by ref
          ) as a 
          group by trno, dateid,docno, clientname, address, ref,checkno,tin,rem order by ref desc";
            // var_dump($query);
            break;
        }
    }
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    $reporttype = $params['params']['dataparams']['reporttype'];
    $print = $params['params']['dataparams']['print'];

    // if ($params['params']['dataparams']['print'] == "default") {
    //   return $this->default_cr_layout($params, $data);
    // } else if ($params['params']['dataparams']['print'] == "PDFM") {
    //   return $this->default_CR_PDF($params, $data);
    // }
    switch ($print) {
      case 'default':
        return $this->default_cr_layout($params, $data);
        break;
      case 'PDFM':
        switch ($reporttype) {
          case '0': //default same sa main
            return $this->default_CR_PDF($params, $data);
            break;
          case '1': //shooting
            return $this->new_CR_PDF($params, $data);
            break;
        }
        break; //end ng pdf
    }
  }

  public function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('RECEIVED PAYMENT', '600', null, false, '1px solid ', '', 'L', 'Avenir', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Avenir', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Avenir', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '100', null, false, '1px solid ', '', 'L', 'Avenir', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Avenir', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '60', null, false, '1px solid ', '', 'L', 'Avenir', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Avenir', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Avenir', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Avenir', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Avenir', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Avenir', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Avenir', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Avenir', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '320', null, false, '1px solid ', 'B', 'C', 'Avenir', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REFERENCE #', '100', null, false, '1px solid ', 'B', 'C', 'Avenir', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Avenir', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Avenir', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Avenir', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Avenir', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_cr_layout($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 30;
    $page = 30;

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
      $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Avenir', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '320', null, false, '1px solid ', '', 'L', 'Avenir', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['ref'], '100', null, false, '1px solid ', '', 'C', 'Avenir', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['postdate'], '75', null, false, '1px solid ', '', 'C', 'Avenir', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Avenir', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Avenir', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['client'], '75', null, false, '1px solid ', '', 'C', 'Avenir', '11', '', '', '2px');
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

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Avenir', '12', '', '', '2px');

    $str .= $this->reporter->col('Check # : ', '50', null, false, '1px solid ', '', 'R', 'Avenir', '12', 'i', '', '2px');
    for ($c = 0; $c < count($data); $c++) {
      $str .= $this->reporter->col($data[$c]['checkno'], '50', null, false, '1px solid ', '', 'L', 'Avenir', '12', '', '', '2px');
    }

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Avenir', '12', '', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Avenir', '12', '', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Avenir', '12', '', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Avenir', '12', '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'T', 'C', 'Avenir', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'T', 'C', 'Avenir', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '70', null, false, '1px dotted ', 'T', 'C', 'Avenir', '12', 'B', '', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '250', null, false, '1px dotted ', 'T', 'R', 'Avenir', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, $decimal), '70', null, false, '1px dotted ', 'T', 'R', 'Avenir', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, $decimal), '60', null, false, '1px dotted ', 'T', 'R', 'Avenir', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Avenir', '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Avenir', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'L', 'Avenir', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'L', 'Avenir', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Avenir', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'L', 'Avenir', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'L', 'Avenir', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

  public function default_CR_header_PDF($params, $data)
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
    $this->reportheader->getheader($params);


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(535, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 20, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(485, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(485, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(90, 0, "ACCOUNT NO.", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(115, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(75, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(85, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "CLIENT", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }

  public function default_CR_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
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
    $this->default_CR_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        // ///////////////// start acnoname
        // $arracno = array();
        // $acnoname = [];
        // $acnoword=[];
        // $acnoword=explode(' ',$data[$i]['acnoname']);
        // $acnowordstring='';
        // foreach($acnoword as $word) {
        //   $acnowordstring=$acnowordstring.$word.' ';
        //   if(strlen($acnowordstring)>42){
        //     $acnowordstring=str_replace($word,'',$acnowordstring);
        //     array_push($arritem,$acnowordstring);
        //     $acnowordstring='';
        //     $acnowordstring=$acnowordstring.$word.' ';
        //   }
        // }
        // array_push($arracno,$acnowordstring);
        // $acnowordstring='';
        // ///////////////// acnoname
        // if(!empty($arracno)) {
        //   foreach($arracno as $arri) {
        //     if(strstr($arri, "\n")) {
        //       $array = preg_split("/\r\n|\n|\r/", $arri);
        //       foreach($array as $arr) {
        //         array_push($acnoname, $arr);
        //       }
        //     } else {
        //       array_push($acnoname, $arri);
        //     }
        //   }
        // }
        // ////////////////////// end acnoname
        // $maxrow = 1;
        // $countarr = count($acnoname);
        // $maxrow = $countarr;
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
          PDF::MultiCell(115, 0, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(75, 0, (isset($arr_postdate[$r]) ? $arr_postdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(85, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(85, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', false, 1, '', '', false, 1);
        }

        // if ($data[$i]['acnoname'] == '') {
        // } else {
        //   for($r = 0; $r < $maxrow; $r++) {
        //     if($r == 0) {
        //       $acno =  $data[$i]['acno'];
        //       $ref = $data[$i]['ref'];
        //       $postdate = $data[$i]['postdate'];
        //       $debit=number_format($data[$i]['db'],$decimalcurr);
        //       if ($debit<1)
        //       {
        //         $debit='-';
        //       }

        //       $credit=number_format($data[$i]['cr'],$decimalcurr);
        //       if ($credit<1)
        //       {
        //         $credit='-';
        //       }
        //       $client = $data[$i]['client'];
        //     } else {
        //       $acno = '';
        //       $ref = '';
        //       $postdate = '';
        //       $debit = '';
        //       $credit = '';
        //       $client = '';
        //     }
        //     PDF::SetFont($font, '', $fontsize);
        //     PDF::MultiCell(90, 0, $acno, '', 'L', false, 0, '', '', true, 1);
        //     PDF::MultiCell(160, 0, isset($acnoname[$r]) ? $acnoname[$r] : '', '', 'L', false, 0, '', '', false, 1);
        //     PDF::MultiCell(100, 0, $ref, '', 'L', false, 0, '', '', false, 1);
        //     PDF::MultiCell(75, 0, $postdate, '', 'C', false, 0, '', '', false, 1);
        //     PDF::MultiCell(85, 0, $debit, '', 'R', false, 0, '', '', false, 1);
        //     PDF::MultiCell(85, 0, $credit, '', 'R', false, 0, '', '', false, 1);
        //     PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
        //     PDF::MultiCell(100, 0, $client, '', 'L', false, 1, '', '', false, 1);
        //   }
        // }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->default_CR_header_PDF($params, $data);
          $page += $count;
        }
      }
    }


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(440, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
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


  public function new_CR_header_PDF($params, $data)
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


    if (Storage::disk('sbcpath')->exists('/fonts/BroadwayRegular.ttf')) {
      $font2 = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/BroadwayRegular.ttf');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(25, 25); //750


    PDF::SetCellPaddings(1, 1, 1, 1);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(750, 20, "", '', 'L', false, 1, '',  '');

    $x = PDF::GetX();
    $y = PDF::GetY();
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(240, 0, "IN THE SETTLEMENT OF THE FOLLOWING", 'LRT', 'C', false, 0, $x,  $y - 11);
    PDF::MultiCell(510, 0, "", '', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', 20); //ito yung line sa left and right 
    PDF::MultiCell(120, 0, "", 'LR', 'C', false, 0, $x,  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, "INVOICE NO.", 'TB', 'C', false, 0, $x,  '');
    PDF::MultiCell(120, 0, "AMOUNT", 'TB', 'C', false, 0, $x + 120,  '');
    PDF::SetFont($font2, '', 20);
    PDF::SetCellPaddings(0, 2, 0, 3);
    PDF::MultiCell(510, 15, "ROOSEVELT CHEMICAL INC.", 'L', 'C', false, 1, '',  '');
    PDF::Ln(-10);
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    // $y = PDF::GetY();
    // PDF::SetY($y - 12); 
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    $totalcr = 0;
    $totalless = 0;
    $totaldb = 0;
    if (!empty($data)) {
      $company_address1 = "73 F. Mariano Avenue Dela Paz NCR,";
      $company_address2 = "Second District 1600 City of Pasig Philippines";
      $company_contact  = "Contact Number: 8645-1089; 7900-9642 Fax: 8645-3425";
      $company_vat      = "VAT REG. NO.: 000-282-667-00000";
      $line7   = 'title';
      $lines7  = 'docno';
      $date = 'date';
      $datehere = 'date2';
      $received = 'first'; //first sentence
      $with = 'next'; // kadugsong ng first sentence
      $address = 'addr';
      $address2 = 'addr2';
      $sum = 'sum';
      $sum2 = 'sum2';
      $num = 'num';
      $num2 = 'num2';
      $company_lines = [
        $company_address1,
        $company_address2,
        $company_contact,
        $company_vat,
        '', // row 5
        [$line7, $lines7], // row 7
        '',
        [$date, $datehere],
        '',
        [$received, $with],
        [$address, $address2],
        [$sum, $sum2],
        [$num, $num2],

      ];
      PDF::SetCellPaddings(0, 0, 0, 0);
      $numData   = count($data);
      $numLines  = count($company_lines);
      $fixedRows = max($numData, $numLines);
      $startY = PDF::GetY();
      $client2 = '';
      $addressL2 = '';
      $numwords2 = '';
      $unallocated = [];
      $unallocateds = [];
      $allunallocated = false;
      for ($i = 0; $i < $fixedRows; $i++) {

        $credit = isset($data[$i]['cr'])  ? floatval($data[$i]['cr']) : 0;
        $line   = isset($company_lines[$i]) ? $company_lines[$i] : '';

        $ref    = isset($data[$i]['ref']) ? $data[$i]['ref'] : '';
        $credits     = ($credit != 0) ? number_format($credit, $decimalcurr) : '';


        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(120, 0, $ref, '', 'C', false, 0, $x, '');
        PDF::MultiCell(110, 0, $credits, '', 'R', false, 0, $x + 120, '', false, 0);
        PDF::MultiCell(10, 0, '', '', '', false, 0, $x + 230, '', false, 0);


        PDF::SetCellPaddings(4, 4, 4, 4);
        if (is_array($line)) {
          // PDF::SetCellPaddings(4, 4, 4, 4);
          //dito papasok yung mga line na mga hinati
          if ($line[0] == 'date' && $line[1] == 'date2') {
            // Date row
            PDF::SetFont($font, 'B', 12); //580
            PDF::MultiCell(330, 0, '', '', 'L', false, 0, '', '');
            PDF::MultiCell(40, 0, 'DATE', '', 'L', false, 0, '', '');
            PDF::SetFont($font, '', 12);
            PDF::MultiCell(140, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '');
          } elseif ($line[0] == 'title' && $line[1] == 'docno') {
            // Collection receipt row
            PDF::SetFont($font, 'B', 13); //580
            PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
            PDF::MultiCell(240, 0, 'COLLECTION RECEIPT', '', '', false, 0, '', '');
            PDF::MultiCell(80, 0, '', '', '', false, 0, '', '');
            PDF::MultiCell(40, 0, 'No.', '', '', false, 0, '', '');
            PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', '', false, 1, '', '');
          } elseif ($line[0] == 'first' && $line[1] == 'next') {
            //dito yung first sentence
            PDF::SetFont($font, '', $fontsize);
            $tin    = (isset($data[0]['tin']) ? $data[0]['tin'] : '');
            $client    = (isset($data[0]['clientname']) ? $data[0]['clientname'] : '');
            $charcount = strlen($client);
            // VAR_DUMP($charcount); //28 //101
            $f = 37; //248
            $s = 44; //298
            $max = 56; //398

            if ($charcount >= $f && $charcount <= $s) { //mataas sa 37 , mababa o equal sa 44
              PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
              PDF::MultiCell(92, 0, 'Received from', '', 'L', false, 0, '', '');
              PDF::SetFont($fontbold, '', 12);

              PDF::Cell(348, 0, strtoupper($client), 'B', 0, 'L', false, '', 1, false, 'T', 'M');
              PDF::SetFont($font, '', $fontsize);
              PDF::Cell(50, 0, 'with TIN', '', 1, 'C', false, '', 1, false, 'T', 'M');
              //PDF::Cell(100, 0, $tin, 'B', 1, 'L', false, '', 1, false, 'T', 'M'); 
            } elseif ($charcount >= $s && $charcount <= $max) { //mas mataas o equal sa 44 at mas mababa o equal sa 52 yung clientname
              PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
              PDF::MultiCell(92, 0, 'Received from', '', 'L', false, 0, '', '');
              PDF::SetFont($fontbold, '', 12);

              PDF::Cell(398, 0, strtoupper($client), 'B', 1, 'L', false, '', 1, false, 'T', 'M');
    
            } elseif ($charcount >= $max) { // mas matas o equal sa max na 56  yung clientname
              $cutLimit = 56;
              if ($charcount > $cutLimit) {
                list($client1, $client2) = $this->smartSplit($client, $cutLimit);
              } else {
                $client1 = $client;
                $client2 = '';
              }
              PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
              PDF::MultiCell(92, 0, 'Received from', '', 'L', false, 0, '', '');
              PDF::SetFont($fontbold, '', 12);
              PDF::Cell(398, 0, strtoupper($client1), 'B', 1, 'L', false, '', 1, false, 'T', 'M');
            } else { //mababa sa 37
              PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
              PDF::MultiCell(92, 0, 'Received from', '', 'L', false, 0, '', '');
              PDF::SetFont($fontbold, '', 12);
              PDF::Cell(248, 0, strtoupper($client), 'B', 0, 'L', false, '', 1, false, 'T', 'M');
              PDF::SetFont($font, '', $fontsize);
              PDF::Cell(50, 0, 'with TIN', '', 0, 'C', false, '', 0, false, 'T', 'M');
              PDF::Cell(100, 0, $tin, 'B', 1, 'L', false, '', 1, false, 'T', 'M');
            }
          } elseif ($line[0] == 'addr' && $line[1] == 'addr2') {
            // Address line
            $address = (isset($data[0]['address']) ? $data[0]['address'] : '');
         
            $client    = (isset($data[0]['clientname']) ? $data[0]['clientname'] : '');
            $tin    = (isset($data[0]['tin']) ? $data[0]['tin'] : '');
            $charcount = strlen($client);
            // var_dump($charcount); //65

            $addrcount = strlen($address); //41
            // var_dump($addrcount); //56
            // var_dump($addrcount); //61
            $addmax = 49;
            $addmax1 = 49;
            // var_dump($addrcount); //56
            // VAR_DUMP($charcount);
            $f = 37; //248
            $s = 44; //298
            $max = 56; //398

            if ($charcount >= $f && $charcount <= $s) { //mataas sa 37 , mababa sa 44 ang clientname
              if ($addrcount <= $addmax1) { //mababa kesa 49 yung count ng address
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::MultiCell(100, 0, $tin, 'B', 'L', false, 0, '', '');
                PDF::MultiCell(90, 0, 'and address at', '', 'L', false, 0, '', '');
                // PDF::SetFont($font, '', 9);
                PDF::MultiCell(300, 0, strtoupper($address), 'B', '', false, 1, '', '');
              } else { //mataas o equal sa 49

                $cutLimit = 49;
                if ($addrcount > $cutLimit) {
                  list($addressL1, $addressL2) = $this->smartSplit($address, $cutLimit);
                } else {
                  $addressL1 = $address;
                  $addressL2 = '';
                }
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                // PDF::MultiCell(50, 0, 'with TIN', '', 'L', false, 0, '', '');
                PDF::MultiCell(100, 0, $tin, 'B', 'L', false, 0, '', '');
                PDF::MultiCell(90, 0, 'and address at', '', 'L', false, 0, '', '');
                // PDF::SetFont($font, '', 9);
                PDF::MultiCell(300, 0, strtoupper($addressL1), 'B', '', false, 1, '', '');
              } //end ng addrcount

            } elseif ($charcount > $s && $charcount <= $max) { //kapag yung clientname ay mas mataas kesa sa 44 at kapag mas mababa kesa sa 52 ang clientname

              if ($addrcount <= $addmax) { //mababa kesa 41 yung count ng address
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::MultiCell(50, 0, 'with TIN', '', 'L', false, 0, '', '');
                PDF::MultiCell(100, 0, $tin, 'B', 'L', false, 0, '', '');
                PDF::MultiCell(80, 0, 'and address', '', 'L', false, 0, '', '');
                // PDF::SetFont($font, '', 9);
                PDF::MultiCell(260, 0, strtoupper($address), 'B', '', false, 1, '', '');
              } else { //mataas sa 41
                $cutLimit = 41;
                if ($addrcount > $cutLimit) {
                  list($addressLine1, $addressL2) = $this->smartSplit($address, $cutLimit);
                } else {
                  $addressLine1 = $address;
                  $addressL2 = '';
                }
                //  print first line with TIN
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::MultiCell(50, 0, 'with TIN', '', 'L', false, 0, '', '');
                PDF::MultiCell(100, 0, $tin, 'B', 'L', false, 0, '', '');
                PDF::MultiCell(90, 0, 'and address at', '', 'L', false, 0, '', '');
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(250, 0, strtoupper($addressLine1), 'B', '', false, 1, '', '');
              }
            } elseif ($charcount > $max) { //kapag eqqual o mas mataas sa 56 ang clientname
              PDF::SetFont($font, '', $fontsize);
              if (!empty($client2)) {
                $client2_upper = strtoupper($client2);
                $textWidth = PDF::GetStringWidth($client2_upper, $fontbold, '', 12);
                $cellWidth = $textWidth + 8;
                $totalWidth = 490;
                $unused = max(0, $totalWidth - $cellWidth);

                if ($unused <= 0) {
                  PDF::SetFont($fontbold, '', 12);
                  PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                  PDF::MultiCell($cellWidth, 0, $client2_upper, 'B', 'L', false, 1, '', '');
                  $allocStatus = [
                    'withtin'  => false,
                    'tins'     => false,
                    'addrd'    => false,
                    'addrcell' => false,
                  ];
                  continue;
                }

                $withtin = 0;
                $tins = 0;
                $addrd = 0;


                $allocStatus = [
                  'withtin'  => false,
                  'tins'     => false,
                  'addrd'    => false,
                  'addrcell' => false,
                ];

                // Conditional width allocation if enough space
                if ($unused >= 50) {
                  $withtin = 50;
                  $unused -= 50;
                  $allocStatus['withtin'] = true;
                } elseif ($unused > 0) {
                  // less than 50 pero may natira
                  $cellWidth += $unused;
                  $unused = 0; // reset kasi ubos na
                }
                //  var_dump($unused); //56.12

                if ($unused >= 100) {
                  $tins = 100;
                  $unused -= 100;
                  $allocStatus['tins'] = true;
                } elseif ($unused > 0) {
                  $cellWidth += $unused;
                  $unused = 0;
                }


                // var_dump($unused);
                if ($unused >= 90) {
                  $addrd = 90;
                  $unused -= 90;
                  $allocStatus['addrd'] = true;
                } elseif ($unused > 0) {
                  $cellWidth += $unused;
                  $unused = 0;
                }
                // var_dump($unused); //float(38.94)

                // Remaining width goes to address cell
                $addrcell = max(0, $unused);

                if (isset($addrcell)) {
                  $allocStatus['addrcell'] = ($addrcell > 0);
                } else {
                  $allocStatus['addrcell'] = false;
                }

                $lastAllocated = '';
                $unallocated = [];
                foreach ($allocStatus as $key => $status) {
                  if ($status) {
                    $lastAllocated = $key;
                  } else {
                    $unallocated[] = $key;
                  }
                }

                $addrs = strtoupper($address);
                // $addr = strlen($addrs);
                $addr = PDF::GetStringWidth($addrs, $font, '', $fontsize);
                $cutLimit = $addrcell;

                if ($addr > $cutLimit) {
                  list($addrs1, $addressL2) = $this->smartSplit2($addrs, $addrcell, $font, '', $fontsize);
                } else {
                  $addrs1 = $addrs;
                  $addressL2 = '';
                }

                // Handle small leftover (<50): merge into client2 width instead
                if (($totalWidth - $cellWidth) < 50) {
                  $cellWidth += $unused;
                  $withtin = $tins = $addrd = $addrcell = 0; // skip allocations
                }

                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::MultiCell($cellWidth, 0, $client2_upper, 'B', 'L', false, 0, '', '');

                PDF::SetFont($font, '', $fontsize);

                // $rowClosed = false;
                // only print if allocated widths exist
                if ($withtin > 0) {
                  PDF::MultiCell($withtin, 0, 'with TIN', '', 'L', false, $lastAllocated == 'withtin' ? 1 : 0, '', '');
                }
                if ($tins > 0) {
                  PDF::MultiCell($tins, 0, $tin, 'B', 'L', false, $lastAllocated == 'tins' ? 1 : 0, '', '');
                }
                if ($addrd > 0) {
                  PDF::MultiCell($addrd, 0, 'and address at', '', 'L', false, $lastAllocated == 'addrd' ? 1 : 0, '', '');
                }
                if ($addrcell > 0) {
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell($addrcell, 0, $addrs1, 'B', '', false, $lastAllocated == 'addrcell' ? 1 : 0, '', '');
                  // $rowClosed = true;
                }
              } else { //empty yung client2 equal sa 52 ang clientname
                // PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                // PDF::SetFont($font, '', $fontsize);
                // PDF::MultiCell(50, 0, 'with TIN', '', 'L', false, 0, '', '');
                // PDF::MultiCell(100, 0, $tin, 'B', 'L', false, 0, '', '');
                // PDF::MultiCell(80, 0, 'and address', '', 'L', false, 0, '', '');
                // PDF::SetFont($font, '', $fontsize);
                // PDF::MultiCell(260, 0, strtoupper($address), 'B', '', false, 1, '', '');
                $cutLimit = 56;
                if ($addrcount > $cutLimit) {
                  list($addressLine1, $addressL2) = $this->smartSplit($address, $cutLimit);
                } else {
                  $addressLine1 = $address;
                  $addressL2 = '';
                }
                PDF::SetFont($font, '', $fontsize);
                //  PDF::SetCellPaddings(4, 4, 4, 4);
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::MultiCell(90, 0, 'and address at', '', 'L', false, 0, '', '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(400, 0, strtoupper($addressLine1), 'B', '', false, 1, '', '');
              }
            } else { //kapag normal 
              $cutLimit = 56;
              if ($addrcount > $cutLimit) {
                list($addressLine1, $addressL2) = $this->smartSplit($address, $cutLimit);
              } else {
                $addressLine1 = $address;
                $addressL2 = '';
              }
              PDF::SetFont($font, '', $fontsize);
              //  PDF::SetCellPaddings(4, 4, 4, 4);
              PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
              PDF::MultiCell(90, 0, 'and address at', '', 'L', false, 0, '', '');
              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(400, 0, strtoupper($addressLine1), 'B', '', false, 1, '', '');
            }
          } elseif ($line[0] == 'sum' && $line[1] == 'sum2') { //dito yung sum na naka words
            $tl = $totaldb;
            $numtowords = $this->reporter->ftNumberToWordsConverter($tl, false, "PHP") . ' ONLY';
            $tin    = (isset($data[0]['tin']) ? $data[0]['tin'] : '');
            if (!empty($unallocated)) {
              // var_dump($unallocated);
              PDF::SetFont($font, '', $fontsize);


              // total width
              $totalWidth = 510;
              $used = 20; // first empty cell
              PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');

              // default width plan
              $withtinW  = 50;
              $tinsW     = 100;
              $addrdW    = 90;
              $addrcellW = 250;

              // print only those inside $unallocated
              if (in_array('withtin', $unallocated)) {
                PDF::MultiCell($withtinW, 0, 'with TIN', '', 'L', false, 0, '', '');
                $used += $withtinW;
              }
              if (in_array('tins', $unallocated)) {
                PDF::MultiCell($tinsW, 0, $tin, 'B', 'L', false, 0, '', '');
                $used += $tinsW;
              }
              if (in_array('addrd', $unallocated)) {
                PDF::MultiCell($addrdW, 0, 'and address at', '', 'L', false, 0, '', '');
                $used += $addrdW;
              }
              $sumW = 0;
              $remainingAfterSum = 0;

              $allocStatus = [
                'thesumof'  => false,
                'numtowords' => false
              ];

              if (in_array('addrcell', $unallocated)) {
                // check remaining width
                $allused = $used + $addrcellW;
                $try = 510 - $allused;

                // $close = false;
                $addrs = strtoupper($address);
                $adrWidth = PDF::GetStringWidth($addrs, $font, '', $fontsize);

                if ($adrWidth > 250) {
                  $addrcellW += $try;
                  $close = true;
                  $remainingAfterSum = 0; //  stop any extra sum-of print
                } else { ////hindi na maaalocate yung thesumof at 
                  if ($try > 0 && $try < 70) { //kapag yung natitira na space ay mas mababa sa 70 i add na lahat sa  addrcellW
                    $addrcellW += $try;
                    $close = true;
                    $remainingAfterSum = 0; // also stop extra printing
                  } elseif ($try >= 70) { //kapag may space pa na mataas o equal sa 70 iapapasok sa sumW
                    $sumW = 70; //kung may 70 pa para dito dapat di ko muna ico close sa part ng addrcellW
                    $remainingAfterSum = $try - $sumW; //pag may natira pa ipapasok sa remaining
                    $allocStatus['thesumof'] = true;
                    $close = false;
                  }
                }
                // print address line 1

                $cutLimit = 56; //cut limit kapag walang allocation ng tin
                $addrcount = strlen($addrs);
                if ($addrcount > $cutLimit) {
                  list($addressL1, $addressL2) = $this->smartSplit($address, $cutLimit);
                } else {
                  $addressL1 = $address;
                  $addressL2 = '';
                }


                if ($close == true) {
                  // kapag kulang sa 70mm space, isara agad yung line

                  PDF::MultiCell($addrcellW, 0, $addressL1, 'B', '', false, 1, '', '');
                } else {
                  // kapag may sum line pa sa kanan
                  PDF::MultiCell($addrcellW, 0, $addressL1, 'B', '', false, 0, '', '');
                }
                $used += $addrcellW;
              }

              if ($remainingAfterSum != 0) { //kapag yung remaining ay hindi 0 ipapasok sa space para sa numwords1 pero kapag maliit ay hahatiin yung numwords para makuha sa next line 
                $rem = $remainingAfterSum;
                PDF::MultiCell($sumW, 0, 'the sum of', '', 'L', false,  0, '', '');
                $allocStatus['thesumof'] = true;
                // Convert number to words (properly defined)
                $numtowords_upper = strtoupper($numtowords);
                $numtowordsWidth = PDF::GetStringWidth($numtowords_upper, $font, '', $fontsize);

                if ($numtowordsWidth > $rem) {
                  list($numwords1, $numwords2) = $this->smartSplit2($numtowords_upper, $rem, $font, '', $fontsize);
                } else {
                  $numwords1 = $numtowords_upper;
                  $numwords2 = '';
                }

                PDF::MultiCell($rem, 0, $numwords1, 'B', '', false,  1, '', '');
                $allocStatus['numtowords'] = true;
              } else {
                // 0 ang rem → no space
                if ($close == false) {
                  // only print if not closed yet
                  PDF::MultiCell($sumW, 0, 'the sum of', '', 'L', false, 1, '', '');
                }
                // still mark statuses for tracking
                $allocStatus['thesumof'] = ($sumW > 0);
                $allocStatus['numtowords'] = false;
              }


              $unallocateds = [];
              foreach ($allocStatus as $key => $status) {
                if (!$status) {
                  $unallocateds[] = $key;
                }
              }
            } else { //walang unallocated
              if (!empty($addressL2)) {
                $addressL2_upper = strtoupper($addressL2);
                $textWidth = PDF::GetStringWidth($addressL2_upper, $font, '', $fontsize);
                $cellWidth = $textWidth + 8;
                $totalWidth = 490 - 70;
                $unused = max(0, $totalWidth - $cellWidth);

                $numtowords_upper = strtoupper($numtowords);
                $numlen = strlen($numtowords_upper);
                $cutLimit = 59;
                // $numtowordsWidth = PDF::GetStringWidth($numtowords_upper, $font, '',  $fontsize);

                // Compare width vs available space
                // if ($numtowordsWidth > $unused) {
                //   list($numwords1, $numwords2) = $this->smartSplit2($numtowords_upper, $unused, $font, '',  $fontsize);
                // } else {
                //   $numwords1 = $numtowords_upper;
                //   $numwords2 = '';
                // }
                if ($numlen > $cutLimit) {
                  list($numwords1, $numwords2) = $this->smartSplit($numtowords_upper, $cutLimit);
                } else {
                  $numwords1 = $numtowords_upper;
                  $numwords2 = '';
                }

                PDF::SetFont($font, '', $fontsize);

                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::MultiCell($cellWidth, 0, $addressL2, 'B', 'L', false, 0, '', '');
                PDF::MultiCell(70, 0, 'the sum of', '', 'L', false, 0, '', '');
                PDF::MultiCell($unused, 0,  $numwords1, 'B', '', false, 1, '', '');
              } else {
                PDF::SetFont($font, '', $fontsize);

                $numtowords_upper = strtoupper($numtowords);
                $numlen = strlen($numtowords_upper);
                $cutLimit = 59;

                if ($numlen > $cutLimit) {
                  list($numwords1, $numwords2) = $this->smartSplit($numtowords_upper, $cutLimit);
                } else {
                  $numwords1 = $numtowords_upper;
                  $numwords2 = '';
                }
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::MultiCell(70, 0, 'the sum of', '', 'L', false, 0, '', '');
                PDF::MultiCell(420, 0, $numwords1, 'B', '', false, 1, '', '');
              }
            }
          } elseif ($line[0] == 'num' && $line[1] == 'num2') {
            $tl = $totaldb;
            $numtowords = $this->reporter->ftNumberToWordsConverter($tl, false, "PHP") . ' ONLY';
            if (!empty($unallocateds)) {
              $lastallocation = false;
              // var_dump($unallocateds);
              PDF::SetFont($font, '', $fontsize);
              // total width
              $totalWidth = 510;
              $used = 20; // first empty cell
              PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');

              // default width plan
              $thesumof  = 70;
              $add = 100;
              // $numtowordsW = 420;
              if (!empty($addressL2)) { //NIFIX KO LANG NA 100
                PDF::MultiCell($add, 0, $addressL2, 'B', 'L', false, 0, '', '');
                $used += $add;
              }

              // print only those inside $unallocated
              if (in_array('thesumof', $unallocateds)) {
                PDF::MultiCell($thesumof, 0, 'the sum of', '', 'L', false, 0, '', '');
                $used += $thesumof;
              }
              if (in_array('numtowords', $unallocateds)) {

                $remaining = $totalWidth - $used;

                if (!empty($addressL2)) { //320 na lang pag may address2l
                  $cutLimit = 49;
                } else {
                  $cutLimit = 59; //ito yung kasya sa remaining na width na 420
                }
                // var_dump($remaining); //int(420)
                $numtowords_upper = strtoupper($numtowords);
                $numlen = strlen($numtowords_upper);

                if ($numlen > $cutLimit) {
                  list($numwords1, $numwords2) = $this->smartSplit($numtowords_upper, $cutLimit);
                } else {
                  $numwords1 = $numtowords_upper;
                  $numwords2 = '';
                }
                //ipapasok ko sa unaalocated lahat 
                PDF::MultiCell($remaining, 0, $numwords1, 'B', '', false, 1, '', '');
                $used += $numtowords;
              }
              if (!empty($numwords2)) {
                $remm = (isset($data[0]['rem']) ? strtoupper($data[0]['rem']) : '');
                $allocStatus = [
                  'in'  => false,
                  'remm'     => false
                ];
                if ($numwords2 >= 40 && $numwords2 <= 43) {
                  PDF::SetFont($font, '', $fontsize);
                  $tlsum = number_format($totaldb, $decimalprice);
                  PDF::MultiCell(260, 0, '', '', '', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(295, 0, $numwords2, 'B', 'L', false, 0, '', '');
                  PDF::MultiCell(15, 0, '(', '', 'L', false, 0, '', '');
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(30, 0, 'PHP', '', 'L', false, 0, '', '');
                  PDF::MultiCell(130, 0,  $tlsum, 'B', 'L', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(20, 0,  ')', '', 'L', false, 1, '', '');
                  $allocStatus['in'] = false;
                  $allocStatus['remm'] = false;
                  $lastallocation = true;
                } else { //dito sya pumasok kasi yung numwords ay 13
                  PDF::SetFont($font, '', $fontsize);
                  $tlsum = number_format($totaldb, $decimalprice);
                  PDF::MultiCell(260, 0, '', '', '', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(275, 0, $numwords2, 'B', 'L', false, 0, '', '');
                  PDF::MultiCell(15, 0, '(', '', 'L', false, 0, '', '');
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(30, 0, 'PHP', '', 'L', false, 0, '', '');
                  PDF::MultiCell(130, 0,  $tlsum, 'B', 'L', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(20, 0,  ')', '', 'L', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(20, 0, 'in', '', 'L', false, 1, '', '');
                  $allocStatus['remm'] = false; //rem lang ang naka false
                  $allocStatus['in'] = true;
                  $lastallocation = true;
                }
              }

              $lastAllocated = '';
              $unallocated = [];
              foreach ($allocStatus as $key => $status) {
                if ($status) {
                  $lastAllocated = $key;
                } else {
                  $unallocated[] = $key;
                }
              }

              if ($lastallocation) {
                $used = 260; // first empty cell
                $totalWidth = 750;
                PDF::MultiCell(260, 0, '', '', '', false, 0, '', '');
                $ins  = 20;
                if (!empty($unallocated)) {
                  if (in_array('in', $unallocated)) {
                    PDF::MultiCell($ins, 0, 'in', '', 'L', false, 0, '', '');
                    $used += $ins;
                  }

                  $remaining = $totalWidth - $used;
                  // $cutLimit = 59;
                  if (in_array('remm', $unallocated)) {
                    PDF::SetFont($fontbold, '', $fontsize);
                    PDF::MultiCell($remaining, 0, $remm, 'B', 'L', false, 1, '', '');
                    $used += $remaining;
                  }
                }
              }
            } else { //walang unallocated

              if (!empty($numwords2)) { //pag may numwords2 na nakuha ay ibig sabihin ay empty ang allocation nun

                // $numwords2_upper = strtoupper($numwords2);
                // $textWidth = PDF::GetStringWidth($numwords2_upper, $font, '', $fontsize);
                // $cellWidth = $textWidth + 10;
                // // var_dump($cellWidth); //335.07
                // $totalWidth = 510 - 235;
                // $unused = $totalWidth - $cellWidth;

                $remm = (isset($data[0]['rem']) ? strtoupper($data[0]['rem']) : '');
                $allocStatus = [
                  'in'  => false,
                  'remm'     => false
                ];

                if ($numwords2 >= 40 && $numwords2 <= 43) {
                  PDF::SetFont($font, '', $fontsize);
                  $tlsum = number_format($totaldb, $decimalprice);
                  PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(295, 0, $numwords2, 'B', 'L', false, 0, '', '');
                  PDF::MultiCell(15, 0, '(', '', 'L', false, 0, '', '');
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(30, 0, 'PHP', '', 'L', false, 0, '', '');
                  PDF::MultiCell(130, 0,  $tlsum, 'B', 'L', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(20, 0,  ')', '', 'L', false, 1, '', '');
                  $allocStatus['in'] = false;
                  $allocStatus['remm'] = false;
                  $lastallocation = true;
                } else { //dito sya pumasok kasi yung numwords ay 13
                  PDF::SetFont($font, '', $fontsize);
                  $tlsum = number_format($totaldb, $decimalprice);
                  PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(275, 0, $numwords2, 'B', 'L', false, 0, '', '');
                  PDF::MultiCell(15, 0, '(', '', 'L', false, 0, '', '');
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(30, 0, 'PHP', '', 'L', false, 0, '', '');
                  PDF::MultiCell(130, 0,  $tlsum, 'B', 'L', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(20, 0,  ')', '', 'L', false, 0, '', '');
                  PDF::SetFont($font, '', $fontsize);
                  PDF::MultiCell(20, 0, 'in', '', 'L', false, 1, '', '');
                  $allocStatus['remm'] = false; //rem lang ang naka false
                  $allocStatus['in'] = true;
                  $lastallocation = true;
                }

                $lastAllocated = '';
                $unallocated = [];
                foreach ($allocStatus as $key => $status) {
                  if ($status) {
                    $lastAllocated = $key;
                  } else {
                    $unallocated[] = $key;
                  }
                }

                if ($lastallocation) {
                  $used = 260; // first empty cell
                  $totalWidth = 750;
                  PDF::MultiCell(260, 0, '', '', '', false, 0, '', '');
                  $ins  = 20;
                  if (!empty($unallocated)) {
                    if (in_array('in', $unallocated)) {
                      PDF::MultiCell($ins, 0, 'in', '', 'L', false, 0, '', '');
                      $used += $ins;
                    }

                    $remaining = $totalWidth - $used;
                    // var_dump($remaining);
                    // $cutLimit = 59;
                    if (in_array('remm', $unallocated)) {
                      PDF::SetFont($fontbold, '', $fontsize);

                      PDF::MultiCell($remaining, 0, $remm, 'B', 'L', false, 1, '', '');
                      $used += $remaining;
                    }
                  }
                }



                // PDF::SetFont($font, '', $fontsize);
                // $tlsum = number_format($totaldb, $decimalprice);
                // PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');

                // PDF::SetFont($font, '', $fontsize);
                // PDF::MultiCell($cellWidth, 0, $numwords2, 'B', 'L', false, 0, '', ''); //numwords2 ito yung kadugsog nung the sum of sa taas 

                // PDF::MultiCell(15, 0, '(', '', 'L', false, 0, '', '');
                // PDF::SetFont($fontbold, '', $fontsize);
                // PDF::MultiCell(30, 0, 'PHP', '', 'L', false, 0, '', '');
                // PDF::MultiCell(130, 0,  $tlsum, 'B', 'L', false, 0, '', '');
                // PDF::SetFont($font, '', $fontsize);
                // PDF::MultiCell(20, 0,  ')', '', 'L', false, 0, '', '');
                // PDF::SetFont($font, '', $fontsize);
                // PDF::MultiCell(20, 0, 'in', '', 'L', false, 0, '', '');
                // PDF::SetFont($fontbold, '', $fontsize);
                // PDF::MultiCell($unused, 0, (isset($data[0]['rem']) ? strtoupper($data[0]['rem']) : ''), 'B', '', false, 1, '', '');



              } else { //normal
                PDF::SetFont($font, '', $fontsize);
                $tlsum = number_format($totaldb, $decimalprice);
                PDF::MultiCell(20, 0, '', '', '', false, 0, '', '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(15, 0, '(', '', 'L', false, 0, '', '');
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(30, 0, 'PHP', '', 'L', false, 0, '', '');
                PDF::MultiCell(130, 0,  $tlsum, 'B', 'L', false, 0, '', '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 0,  ')', '', 'L', false, 0, '', '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(20, 0, 'in', '', 'L', false, 0, '', '');
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(275, 0, (isset($data[0]['rem']) ? strtoupper($data[0]['rem']) : ''), 'B', '', false, 1, '', '');
              }
            }
          } else {
          }
        } else {
          // normal lines
          // // SetCellPaddings($left, $top, $right, $bottom)
          PDF::SetCellPaddings(0, 2, 0, 0);
          if (substr($line, 0, 3) == 'VAT') {
            PDF::SetFont($font, 'B', $fontsize); // Bold kung VAT
            PDF::MultiCell(510, 0, $line, '', 'C', false, 1, '', '');
            PDF::SetFont($font, '', $fontsize);  // balik sa normal
          } else {
            PDF::SetFont($font, '', $fontsize); // Normal
            // $imagePath = $this->companysetup->getlogopath($params['params']) . 'birlogo.png';
            // $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 270, 40, 100, 100) : 'No image found'; //x,y ,width,Height
            $imagePath = $this->companysetup->getlogopath($params['params']) . 'rooseveltlogo.png';
            $logohere = (isset($imagePath)  || file_exists($imagePath))  ? PDF::Image($imagePath, 270, 40, 100, 100) : 'No image found'; //x, y,width,height
            PDF::MultiCell(510, 0, $line, '', 'C', false, 1, '', '');
          }
        }
        if (isset($data[$i]['cr'])) {
          $totalcr += $data[$i]['cr'];
        }

        if (isset($data[$i]['wtdb'])) {
          $totalless += $data[$i]['wtdb'];
        }
        if (isset($data[$i]['db'])) {
          $totaldb += $data[$i]['db'];
        }
      }
      $endY = PDF::GetY();
      // vertical line nilagay ko dahil nag puputol putol pag naka setcellpadding
      PDF::Line($x,       $startY, $x,       $endY);   // left
      PDF::Line($x + 120, $startY, $x + 120, $endY);   // middle
      PDF::Line($x + 240, $startY, $x + 240, $endY);   // right
    }

    // magdadagdag ako dito ng bagong row
    $rowHeight = 0;
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($font, '', $fontsize);
    // $less = isset($data[0]['wtdb']) ? number_format($data[0]['wtdb'], 2) : 0;


    PDF::MultiCell(120, $rowHeight, 'Less: Withholding Tax', 'LT', 'L', false, 0, '', '');
    PDF::MultiCell(110, $rowHeight, number_format($totalless, $decimalprice), 'LT', 'R', false, 0, '', '');
    PDF::MultiCell(10,  $rowHeight, '', 'T', 'R', false, 0, '', '');
    PDF::MultiCell(510, $rowHeight, '', 'L', 'L', false, 1, '', '');


    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    // PDF::MultiCell(120, 0, "", 'L', '', false, 0, '', '');

    PDF::SetFont($font, '', $fontsize);
    $due = $totaldb; //number_format($due, $decimalprice)
    PDF::MultiCell(120, $rowHeight, 'Amount Due', 'LTR', 'C', false, 0, $x,  '');
    PDF::MultiCell(110, $rowHeight, number_format($due, $decimalprice), 'T', 'R', false, 0,  $x + 120, '');
    PDF::MultiCell(10, $rowHeight, '', 'T', 'R', false, 0,  $x + 230, '', false, 0);
    PDF::MultiCell(510, $rowHeight, '', 'L', 'L', false, 1, '', '');


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(240, 0, 'FORM OF PAYMENT', 'LTR', 'C', false, 0, $x, '');
    PDF::MultiCell(300, 0, '', '', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(30, 0, 'By: ', '', 'L', false, 0, '', '');
    PDF::MultiCell(180, 0, '', 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    $checkno = (isset($data[0]['checkno']) ? $data[0]['checkno'] : '');

    $r1 = '';
    $r2 = '';
    if ($checkno != '') {
      $r1 = 'checked="checked"';
    } else {
      $r2 = 'checked="checked"';
    }
    $html = '
            <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                <!-- First row (issenior and Condition and recommendation) -->
                <tr>
                    <td style="width: 120px; border-left: 1px solid black;border-top: 1px solid black; border-bottom: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="checkno" value="1" readonly="true" ' . $r2 . '/>  
                        <label for="issenior" style="display: inline-block;">Cash</label>
                    </td>
                    <td style="width: 120px; border-top: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black; text-align: left; padding: 0;">
                        <input type="checkbox" name="cash" value="2" readonly="true" ' . $r1 . '/>  
                        <label for="issenior" style="display: inline-block;">Check</label>
                    </td>
                     <!-- empty -->

                        <td style="width: 330px;  text-align: left; padding: 0;"></td>
                        <td style="width: 180px;  text-align: center; padding: 0;"> Authorized Signature </td>


                </tr> </table>';
    PDF::writeHTML($html, true, 0, true, 0);

    PDF::SetCellPaddings(0, 0, 0, 0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(410, 0, '', '', 'C', false, 0, '', '');
    PDF::MultiCell(300, 0, '"THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX"', 'B', 'L', false, 0, '', '');
    PDF::MultiCell(40, 0, '', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(750, 0, 'Acknowledgement Certificate Control No.:', '', 'L', false, 1, '', '');
    PDF::MultiCell(750, 0, 'Date Issued: January 01, 0001', '', 'L', false, 1, '', '');
    PDF::MultiCell(750, 0, 'Inclusion Series: CR000000001 To: CR999999999', '', 'L', false, 1, '', '');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(20, 0, '', '', 'C', false, 1, '', '');

    $printeddate = $this->othersClass->getCurrentTimeStamp();
    $datetime = new DateTime($printeddate);

    // Format with AM/PM
    $formattedDate = $datetime->format('Y-m-d h:i:s a'); //2025-09-25 16:46:32 pm
    $username = $params['params']['user'];
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(180, 0, 'QNE SOFTWARE PHILIPPINES, INC', '', 'L', false, 0, '', '');
    PDF::MultiCell(280, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(290, 0, 'Printed Date/Time: ' . $formattedDate, '', 'L', false, 1, '', '');
    // PDF::MultiCell(80, 0, '', '', 'L', false, 1, '', '');

    PDF::MultiCell(380, 0, 'Unit 806 Pearl of the Orient Tower, 1240 Roxas Blvd., Ermita, Manila', '', 'L', false, 0, '', '');
    PDF::MultiCell(80, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(290, 0, 'Printed By: ' . $username, '', 'L', false, 1, '', '');
    // PDF::MultiCell(80, 0, '', '', 'L', false, 1, '', '');

    PDF::MultiCell(380, 0, 'TIN: 006-934-485-000', '', 'L', false, 0, '', '');
    PDF::MultiCell(80, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(290, 0, 'QNE Optimum Version 2024.1.0.7', '', 'L', false, 1, '', '');
    // PDF::MultiCell(80, 0, '', '', 'L', false, 1, '', '');
  }

  public function new_CR_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
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
    $this->new_CR_header_PDF($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function smartSplit($text, $cutLimit)
  {
    // Get last space before and first space after the limit
    $spaceBefore = strrpos(substr($text, 0, $cutLimit), ' ');
    $spaceAfter  = strpos($text, ' ', $cutLimit);
    // Determine the character at cutLimit
    $charAtLimit = substr($text, $cutLimit - 1, 1);

    // Case 1: may space after 49 → check if the word before 52 is complete
    if ($spaceAfter !== false) {
      // Check kung buong word bago 49
      if (preg_match('/\s/', $charAtLimit)) {
        // kung ang character sa 49 ay space, safe putulin doon
        $breakPos = $cutLimit;
      } else {
        // may space after, so cut sa first space after 52
        $breakPos = $spaceAfter;
      }
    }  // Case 2: walang space after → balik sa last space before
    elseif ($spaceBefore !== false) {
      $breakPos = $spaceBefore;
    } else {
      // Case 3: walang space at all → hard cut
      $breakPos = $cutLimit;
    }

    //return Split text
    return [
      substr($text, 0, $breakPos),
      trim(substr($text, $breakPos))
    ];
  }

  public function smartSplit2($text, $limitWidthMm, $font, $style, $fontSize)
  {
    // Convert mm to TCPDF user units (points)
    $limitWidthPts = $limitWidthMm * 72 / 25.4;

    PDF::SetFont($font, $style, $fontSize);

    $words = explode(' ', trim($text));
    $line1 = '';
    $line2 = '';

    foreach ($words as $word) {
      $testLine = trim($line1 . ' ' . $word);
      $testWidthPts = PDF::GetStringWidth($testLine, $font, $style, $fontSize);

      // Once width exceeds limit, stop and split
      if ($testWidthPts > $limitWidthPts) {
        $line2 = trim(substr($text, strlen($line1)));
        break;
      } else {
        $line1 = $testLine;
      }
    }

    return [rtrim($line1), ltrim($line2)];
  }
}
