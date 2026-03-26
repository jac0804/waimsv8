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

class ds
{

  private $modulename = "Deposit Slip";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
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
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($filters)
  {
    $trno = md5($filters['params']['dataid']);
    $query = "
    select head.trno, date(head.dateid) as dateid, head.docno, 
    coa.acnoname as clientname, coa.acno,
    coa.acnoname, client.client, detail.ref,
    date(detail.postdate) as postdate, detail.db, 
    detail.cr, detail.line,coa.alias
    from ((lahead as head 
    left join ladetail as detail on detail.trno=head.trno)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.client=detail.client
    where head.doc='ds' and md5(head.trno)='$trno'
    union all
    select head.trno, date(head.dateid) as dateid, head.docno, 
    hcoa.acnoname as clientname, coa.acno,
    coa.acnoname, client.client, detail.ref,
     date(detail.postdate) as postdate, detail.db, 
     detail.cr, detail.line,coa.alias
    from (((glhead as head 
    left join gldetail as detail on detail.trno=head.trno)
    left join coa as hcoa on hcoa.acno=head.contra)
    left join coa on coa.acnoid=detail.acnoid)
    left join client on client.clientid=detail.clientid
    where head.doc='ds' and md5(head.trno)='$trno' order by line";

    $result = $this->coreFunctions->opentable($query);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_ds_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_DS_PDF($params, $data);
    }
  }

  public function rpt_ds_header_default($config, $result)
  {
    $str = '';
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPOSIT SLIP', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->docno) ? $result[0]->docno : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BANK&nbspNAME : ', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    foreach ($result as $key => $datas) {
      if (substr($datas->alias, 0, -1) == 'CB') {
        $str .= $this->reporter->col($datas->acnoname, '500', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
      }
    }
    $str .= $this->reporter->col('DATE&nbsp:&nbsp', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]->dateid) ? $result[0]->dateid : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '350', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REFERENCE #', '90', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CLIENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_ds_layout($config, $result)
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

    $str .= $this->rpt_ds_header_default($config, $result);

    $totaldb = 0;
    $totalcr = 0;
    foreach ($result as $key => $data) {
      $debit = number_format($data->db, 2);
      if ($debit < 1) {
        $debit = '-';
      }
      $credit = number_format($data->cr, 2);
      if ($credit < 1) {
        $credit = '-';
      }
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

        $str .= $this->rpt_ds_header_default($config, $result);

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
    $str .= $this->reporter->col('GRAND TOTAL :', '330', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '95', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
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

  public function default_DS_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize9 = 9;
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
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(40, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 10);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(380, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]->docno) ? $data[0]->docno : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(60, 0, "Bank Name: ", '', 'L', false, 0, '',  '');
    $ii = 0;
    PDF::SetFont($font, '', $fontsize9);
    foreach ($data as $key => $datas) {
      if (substr($datas->alias, 0, -1) == 'CB') {
        $ii++;
        if ($ii > 1) {
          PDF::MultiCell(60, 0, "", '', 'L', false, 0, '',  '');
          PDF::MultiCell(250, 0, $datas->acnoname, '', 'L', 0, 1, '', '');
        } else {
          PDF::MultiCell(250, 0, $datas->acnoname, '', 'L', 0, 1, '', '');
        }
      }
    }
    PDF::MultiCell(60, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(250, 0, "", 'B', 'L', 0, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '420',  '130');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, (isset($data[0]->dateid) ? $data[0]->dateid : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(60, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(470, 0, '', '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(50, 0, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(60, 0, "ACCOUNT#", '', 'L', false, 0);
    PDF::MultiCell(120, 0, "ACCOUNT NAME", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "REFERENCE #", '', 'L', false, 0);
    PDF::MultiCell(60, 0, "DATE", '', 'C', false, 0);
    PDF::MultiCell(55, 0, "DEBIT", '', 'R', false, 0);
    PDF::MultiCell(55, 0, "CREDIT", '', 'R', false, 0);
    PDF::MultiCell(80, 0, "CLIENT", '', 'C', false);

    PDF::MultiCell(530, 0, '', 'B');
  }

  public function default_DS_PDF($params, $data)
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
    $fontsize9 = "9";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_DS_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;

    if (!empty($data)) {
      $totaldb = 0;
      $totalcr = 0;
      for ($i = 0; $i < count($data); $i++) {
        $arracnoname = (str_split($data[$i]->acnoname, 40));
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

        if ($data[$i]->acnoname == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $acno =  $data[$i]->acno;
              $ref = $data[$i]->ref;
              $postdate = $data[$i]->postdate;
              $debit = number_format($data[$i]->db, $decimalcurr);
              $debit = $debit < 0 ? '-' : $debit;

              $credit = number_format($data[$i]->cr, $decimalcurr);
              $credit = $credit < 0 ? '-' : $credit;
              $client = $data[$i]->client;
            } else {
              $acno = '';
              $ref = '';
              $postdate = '';
              $debit = '';
              $credit = '';
              $client = '';
            }
            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(60, 0, $acno, '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(120, 0, isset($acnonamedescs[$r]) ? $acnonamedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ref, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(60, 0, $postdate, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(55, 0, $debit, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(55, 0, $credit, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 0, $client, '', 'L', false, 1, '', '', false, 1);
          }
        }
        $totaldb += $data[$i]->db;
        $totalcr += $data[$i]->cr;

        if (intVal($i) + 1 == $page) {
          $this->default_DS_header_PDF($params, $data);
          $page += $count;
        }
      }
    }

    PDF::MultiCell(530, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(340, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(55, 0, number_format($totaldb, $decimalprice), '', 'R', false, 0);
    PDF::MultiCell(55, 0, number_format($totalcr, $decimalprice), '', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(480, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(153, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(153, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(153, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(153, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
