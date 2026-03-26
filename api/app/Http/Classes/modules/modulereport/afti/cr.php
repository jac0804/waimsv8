<?php

namespace App\Http\Classes\modules\modulereport\afti;

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

class cr
{

  private $modulename = "Received Payment";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

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
    $fields = ['radioprint', 'radiosjafti', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radiosjafti.label', 'Report Type');

    data_set($col1, 'radiosjafti.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'red'],
      ['label' => 'Credit Note', 'value' => '1', 'color' => 'red']
    ]);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'prepared.readonly', true);
    data_set($col1, 'prepared.type', 'lookup');
    data_set($col1, 'prepared.action', 'lookupclient');
    data_set($col1, 'prepared.lookupclass', 'prepared');

    data_set($col1, 'approved.readonly', true);
    data_set($col1, 'approved.type', 'lookup');
    data_set($col1, 'approved.action', 'lookupclient');
    data_set($col1, 'approved.lookupclass', 'approved');

    data_set($col1, 'received.readonly', true);
    data_set($col1, 'received.type', 'lookup');
    data_set($col1, 'received.action', 'lookupclient');
    data_set($col1, 'received.lookupclass', 'received');
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received,
      '0' as radiosjafti
      "
    );
  }

  public function report_default_query($filters)
  {
    switch ($filters['params']['dataparams']['radiosjafti']) {
      case 1:
        $trno = md5($filters['params']['dataid']);
        $query = "
        select head.trno, date(head.dateid) as dateid, head.docno, 
        head.clientname, case when head.address<>'' then head.address else client.addr end as address, head.yourref, 
        left(coa.alias,2) as alias, coa.acno,
        coa.acnoname, client.client,concat(left(detail.ref,3),right(detail.ref,5)) as ref, 
        date(detail.postdate) as postdate, detail.checkno, 
        detail.db, detail.cr, detail.line,p.name as costcenter,dept.clientname as department,ifnull(branch.clientname,'') as branch,coa.cat,head.cur,deets.rem
        from ((lahead as head 
        left join ladetail as detail on detail.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.client=detail.client
        left join projectmasterfile as p on p.line=detail.projectid
        left join client as dept on dept.clientid=detail.deptid
        left join client as branch on branch.clientid=detail.branch
        left join detailinfo as deets on deets.trno=detail.trno and deets.line=detail.line
        where head.doc='cr' and md5(head.trno)='$trno'
        union all
        select head.trno, date(head.dateid) as dateid, head.docno, 
        head.clientname, case when head.address<>'' then head.address else client.addr end as address, head.yourref, 
        left(coa.alias,2) as alias, coa.acno,
        coa.acnoname, client.client,concat(left(detail.ref,3),right(detail.ref,5)) as ref, 
        date(detail.postdate) as postdate, detail.checkno, 
        detail.db, detail.cr, detail.line,p.name as costcenter,dept.clientname as department,ifnull(branch.clientname,'') as branch,coa.cat,head.cur,deets.rem
        from ((glhead as head 
        left join gldetail as detail on detail.trno=head.trno)
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.clientid=detail.clientid
        left join projectmasterfile as p on p.line=detail.projectid
        left join client as dept on dept.clientid=detail.deptid
        left join client as branch on branch.clientid=detail.branch
        left join hdetailinfo as deets on deets.trno=detail.trno and deets.line=detail.line
        where head.doc='cr' and md5(head.trno)='$trno' order by line";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
        break;
      default:
        $trno = $filters['params']['dataid'];
        $query = "
        select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode) as address, head.yourref,head.ourref,head.rem, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
        client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
        from ((lahead as head 
        left join ladetail as detail on detail.trno=head.trno) 
        left join coa on coa.acnoid=detail.acnoid) 
        left join client on client.client=detail.client
        left join billingaddr as b on b.clientid = client.clientid and client.billid = b.line
        where head.doc='cr' and head.trno='$trno' and coa.alias='AR5'
        union all
        select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode) as  address, head.yourref,head.ourref,head.rem, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
        client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
        from ((glhead as head 
        left join gldetail as detail on detail.trno=head.trno) 
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.clientid=detail.clientid 
        left join billingaddr as b on b.clientid = client.clientid and client.billid = b.line
        where head.doc='cr' and head.trno='$trno' and coa.alias='AR5' order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
        break;
    }
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_cr_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      switch ($params['params']['dataparams']['radiosjafti']) {
        case 1:
          return $this->CreditNote_CR_PDF($params, $data);
          break;
        default:
          return $this->default_CR_PDF($params, $data);
          break;
      }
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
      if ($debit < 1) {
        $debit = '-';
      }
      $credit = number_format($data[$i]['cr'], $decimal);
      if ($credit < 1) {
        $credit = '-';
      }

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

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;
    $fontsize9 = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(350, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(340, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(90, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(340, 0, (isset($fontsize9[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Ref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(90, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(530, 0, '', 'T');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(60, 0, "ACCOUNT NO.", '', 'L', false, 0);
    PDF::MultiCell(130, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(50, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(60, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(10, 0, "", '', 'R', false, 0);
    PDF::MultiCell(85, 0, "CLIENT", '', 'C', false);

    PDF::MultiCell(530, 0, '', 'B');
  }

  public function default_CR_PDF($params, $data)
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
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_CR_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $arracnoname = (str_split($data[$i]['acnoname'], 40));
        $acnonamedescs = [];

        if (!empty($arracnoname)) {
          foreach ($arracnoname as $arri) {
            if (strstr($arri, "\n")) {
              $array = preg_split("/\r\n|\n|\r/", $arri);
              foreach ($array as $arr) {
                array_push($acnonamedescs, $arr);
              }
            } else {
              array_push($acnonamedescs, $arri);
            }
          }
        }
        $countarr = count($acnonamedescs);

        $maxrow = $countarr;

        if ($data[$i]['acnoname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $acno =  $data[$i]['acno'];
              $ref = $data[$i]['ref'];
              $postdate = $data[$i]['postdate'];
              $debit = number_format($data[$i]['db'], $decimalcurr);
              $debit = $debit < 0 ? '-' : $debit;

              $credit = number_format($data[$i]['cr'], $decimalcurr);
              $credit = $credit < 0 ? '-' : $credit;
              $client = $data[$i]['client'];
            } else {
              $acno = '';
              $ref = '';
              $postdate = '';
              $debit = '';
              $credit = '';
              $client = '';
            }

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(60, 0, $acno, '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(130, 0, isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(75, 0, $ref, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $postdate, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0, $debit, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0, $credit, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(10, 0, '', '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(85, 0, $client, '', 'L', false, 1, '', '', false, 1);
          }
        }
        $totaldb += $data[$i]['db'];
        $totalcr += $data[$i]['cr'];

        if (intVal($i) + 1 == $page) {
          $this->default_CR_header_PDF($params, $data);
          $page += $count;
        }
      }
    }


    PDF::MultiCell(530, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(315, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(60, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(60, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(176, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(176, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(176, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(176, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(176, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(176, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function CreditNote_Header_CR_PDF($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $font = '';
    $fontbold = '';
    $fontsize = '11';
    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";


    PDF::SetFont($font, '', 14);

    PDF::Image(public_path() . '/images/afti/qslogo.png', '', '', 200, 50);
    PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(290, 0, '', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);

    $drdocno = isset($data[0]['docno']) ? $data[0]['docno'] : '';

    PDF::MultiCell(0, 40, "\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->name, '', 'L', false, 0, '', '');
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, ' ' . '',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, ' ', '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->address, '', '', false, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, '',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, '', '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(0, 40, "\n");

    PDF::SetFont($font, 'B', 14);
    PDF::MultiCell(525, 0, 'CREDIT NOTE', '', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "CN NO.: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Date: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(270, 0, '', '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Page: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), 'B', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n");

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $totalext += $data[$i]['cr'];
    }

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, " Credit to : ", 'TLR', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'TLR', 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, " Amount : ", 'TLR', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0,  '  ' . number_format($totalext, $decimalprice), 'TLR', 'L', false, 1);

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 120, " Description : ", 'TLRB', 'R', false, 0, '',  '');
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(470, 120, '  ' . (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'TLRB', 'L', false, 1);

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(535, 0, 'This is a system-generated document Signature of approver is not required.', '', 'C', false, 1);
  }

  public function CreditNote_CR_PDF($params, $data)
  {
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
    $this->CreditNote_Header_CR_PDF($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
