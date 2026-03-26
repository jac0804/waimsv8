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
use Illuminate\Support\Facades\URL;
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class gj
{

  private $modulename = "General Journal";
  private $reportheader;
  private $btnClass;
  private $fieldClass;
  private $tabClass;
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'checked', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
    $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);
    $checked  = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'checked' and doc =? ", [$config['params']['doc']]);

    return $this->coreFunctions->opentable("
      select 'PDFM' as print,
      '$username' as prepared,
      '$approved' as approved,
      '$received' as received,
      '$checked' as checked");
  }

  public function report_default_query($filters)
  {
    $trno = md5($filters['params']['dataid']);
    $query = "
    select head.trno, 
      DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as dateid,
     head.docno, 
    head.clientname, head.address, head.yourref, 
    left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, 
       concat(coa.acno,'- ' ,coa.acnoname) as particular,
        abs(detail.db- detail.cr) as amount,
      
      client.client, detail.ref, 
    date(detail.postdate) as postdate, detail.checkno, 
    detail.db, detail.cr, detail.line,  head.rem
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    where head.doc='gj' and md5(head.trno)='$trno'
    union all
    select head.trno,
      DATE_FORMAT(left(head.dateid,10),'%b %d, %Y') as dateid,
     head.docno, 
    head.clientname, head.address, head.yourref, 
    left(coa.alias,2) as alias, coa.acno,
    coa.acnoname, concat(coa.acno,'-', coa.acnoname) as particular, 
     abs(detail.db- detail.cr) as amount,client.client, detail.ref, 
    date(detail.postdate) as postdate, detail.checkno, 
    detail.db, detail.cr, detail.line, head.rem
    from ((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    where head.doc='gj' and md5(head.trno)='$trno' order by line";


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_gj_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_GJ_PDF($params, $data);
    }
  }

  public function rpt_gj_header_default($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];
    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('GENERAL JOURNAL', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER/SUPPLIER: ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->clientname) ? $result[0]->clientname : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]->address) ? $result[0]->address : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REF. :', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->yourref) ? $result[0]->yourref : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REFERENCE&nbsp#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_gj_layout($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_gj_header_default($config, $result);


    $totaldb = 0;
    $totalcr = 0;
    foreach ($result as $key => $data) {
      $debit = number_format($data->db, 2);
      $debit = $debit < 0 ? '-' : $debit;
      $credit = number_format($data->cr, 2);
      $credit = $credit < 0 ? '-' : $credit;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->acno, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->acnoname, '350', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->ref, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->postdate, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data->client, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $totaldb = $totaldb + $data->db;
      $totalcr = $totalcr + $data->cr;
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_gj_header_default($config, $result);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '350', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
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
    $str .= $this->reporter->col($prepared, '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_GJ_header_PDF($params, $data)
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

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(700, 0, $this->modulename, '', 'C', false, 0);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(520, 0, "", '', 'R', false, 0, '',  '');
    PDF::MultiCell(80, 0, "Docno #: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(35, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(665, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "Customer/Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(590, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(500, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '');
    PDF::MultiCell(150, 0, "", '', 'L', false, 0, '',  '');


    PDF::MultiCell(0, 0, "\n\n\n");
    $style = ['dash' => 3];
    PDF::SetLineStyle($style);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(400, 0, "PARTICULARS", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "CREDIT", '', 'R', false);
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
    $style = ['dash' => 0];
    PDF::SetLineStyle($style);
  }

  public function default_GJ_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 55;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_GJ_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $particular = $data[$i]['particular'];
        $debit = number_format($data[$i]['db'], $decimalcurr);
        $credit = number_format($data[$i]['cr'], $decimalcurr);
        $debit = $debit == 0 ? '' : $debit;
        $credit = $credit == 0 ? '' : $credit;

        $arr_particular = $this->reporter->fixcolumn([$particular], '100', 0);
        $arr_debit = $this->reporter->fixcolumn([$debit], '16', 0);
        $arr_credit = $this->reporter->fixcolumn([$credit], '16', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_particular, $arr_debit, $arr_credit]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(400, 0, (isset($arr_particular[$r]) ? $arr_particular[$r] : ''), '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(100, 0, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', false, 1, '', '', false, 1);
        }
        if (intVal($i) + 1 == $page) {
          $this->default_GJ_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::MultiCell(253, 0, '#', '', 'C', false, 0);
    PDF::MultiCell(200, 0, '#', '', 'L', false, 0);
    PDF::MultiCell(200, 0, '#', '', 'L');

    $style = ['dash' => 3];
    PDF::SetLineStyle($style);

    PDF::SetFont($fontbold, '',  $fontsize);
    PDF::MultiCell(80, 25, 'EXPLANATION:', '', 'C', false, 0);
    PDF::SetFont($font, '',  $fontsize);
    PDF::MultiCell(620, 25, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false);
    PDF::MultiCell(700, 20, '', 'B', '', false);

    $style = ['dash' => 0];
    PDF::SetLineStyle($style);

    $trno = md5($params['params']['dataid']);
    $qryy = " select count(alias) as alias ,postdate,checkno,amount from(
            select
           left(coa.acno,2) as alias, date(detail.postdate) as postdate, detail.checkno,
             abs(detail.db- detail.cr) as amount
            from ((lahead as head
            left join ladetail as detail on detail.trno=head.trno)
            left join coa on coa.acnoid=detail.acnoid)
            left join client on client.client=detail.client where head.doc='gj' and left(coa.alias,2)='CB' and
            md5(head.trno)='$trno'
            union all
            select  left(coa.acno,2) as alias, date(detail.postdate) as postdate, detail.checkno,
             abs(detail.db- detail.cr) as amount
              from ((glhead as head
              left join gldetail as detail on detail.trno=head.trno)
              left join coa on coa.acnoid=detail.acnoid)
              left join client on client.clientid=detail.clientid
            where head.doc='gj' and left(coa.alias,2)='CB'  and md5(head.trno)='$trno' ) as x
            group by postdate,checkno,amount";

    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qryy)), true);



    if (empty($data2)) {

      PDF::MultiCell(720, 20, '', '', '', false);

      PDF::MultiCell(70, 15, 'CHECK : ', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(15, 15, '', '', 'L', false);

      PDF::MultiCell(70, 15, 'DATE : ', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(15, 15, '', '', 'L', false);


      PDF::MultiCell(
        70,
        15,
        'AMOUNT : ',
        '',
        'L',
        false,
        0
      );
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(10, 15, '', '', 'L', false, 0);
      PDF::MultiCell(150, 15, '', 'B', 'L', false, 0);
      PDF::MultiCell(15, 15, '', '', 'L', false);

      PDF::MultiCell(0, 0, "\n\n\n\n");
    } else {
      PDF::MultiCell(0, 0, '', '', '', false);
      $ctr = count($data2);

      if ($ctr != 4) {
        $ctr = 4;
      }
      PDF::SetFont($font, '',  $fontsize);
      for ($c = 0; $c < $ctr; $c++) {
        $count++;
        if ($c == 0) {
          PDF::SetFont($fontbold, '',  $fontsize);
          PDF::MultiCell(80, 0, 'CHECK   :', '', 'L', false, 0);
        }
        PDF::SetFont($font, '',  $fontsize);
        PDF::MultiCell(20, 0, '', '', 'C', false, 0);
        PDF::MultiCell(130, 0, (isset($data2[$c]['checkno']) ? $data2[$c]['checkno'] : ''), 'B', 'C', false, 0);
      }

      PDF::MultiCell(0, 0, '', '', 'L', false);

      PDF::MultiCell(0, 0, "");
      PDF::SetFont($font, '',  $fontsize);
      for ($d = 0; $d < $ctr; $d++) {
        $count++;
        if ($d == 0) {
          PDF::SetFont($fontbold, '',  $fontsize);
          PDF::MultiCell(80, 0, 'DATE       :', '', 'L', false, 0);
        }
        PDF::SetFont($font, '',  $fontsize);
        PDF::MultiCell(20, 0, '', '', 'C', false, 0);
        PDF::MultiCell(130, 0, (isset($data2[$d]['postdate']) ? $data2[$d]['postdate'] : ''), 'B', 'C', false, 0);
      }

      PDF::MultiCell(0, 0, '', '', 'L', false);

      PDF::MultiCell(0, 0, "");
      PDF::SetFont($font, '',  $fontsize);
      for ($d = 0; $d < $ctr; $d++) {
        $count++;
        if ($d == 0) {
          PDF::SetFont($fontbold, '',  $fontsize);
          PDF::MultiCell(80, 0, 'AMOUNT:', '', 'L', false, 0);
        }
        PDF::SetFont($font, '',  $fontsize);
        PDF::MultiCell(20, 0, '', '', 'C', false, 0);
        PDF::MultiCell(130, 0, (isset($data2[$d]['amount']) ? number_format($data2[$d]['amount'], 2) : ''), 'B', 'C', false, 0);
      }
    }


    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(175, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Checked By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(175, 0, 'Received By: ', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(175, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(175, 0, $params['params']['dataparams']['received'], 'B', 'L', false);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
