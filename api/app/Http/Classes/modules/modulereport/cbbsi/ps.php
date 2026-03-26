<?php

namespace App\Http\Classes\modules\modulereport\cbbsi;

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

class ps
{

  private $modulename = "Payment Listing Summary";
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
    $fields = ['radioprint', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 'PDFM' as print");
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select trno, docno, date(dateid) as dateid, rem, yourref, ourref from pshead where trno=" . $trno . "
      union all
      select trno, docno, date(dateid) as dateid, rem, yourref, ourref from hpshead where trno=" . $trno;
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    return $this->default_PS_PDF($params, $data);
  }

  public function default_PS_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

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

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('l', [1000, 800]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(780, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Notes:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(760, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Date:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Yourref:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(760, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Ourref:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 11);
    PDF::MultiCell(75, 0, 'Yourref', '', 'C', false, 0);
    PDF::MultiCell(75, 0, 'Ourref', '', 'C', false, 0);
    PDF::MultiCell(120, 0, 'Supplier', '', 'C', false, 0);
    PDF::MultiCell(70, 0, 'Amount', '', 'C', false, 0);
    PDF::MultiCell(70, 0, 'Checkdate', '', 'C', false, 0);
    PDF::MultiCell(90, 0, 'CV No', '', 'C', false, 0);
    PDF::MultiCell(90, 0, 'Check Details', '', 'C', false, 0);
    PDF::MultiCell(85, 0, 'Release to AP', '', 'C', false, 0);
    PDF::MultiCell(90, 0, 'Release to Supp', '', 'C', false, 0);
    PDF::MultiCell(75, 0, 'Clear Date', '', 'C', false, 0);
    PDF::MultiCell(120, 0, 'Notes', '', 'C', false);

    PDF::MultiCell(960, 0, '', 'B');
  }

  public function default_PS_PDF($params, $data)
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
    $fontsize = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_PS_header_PDF($params, $data);
    $data2 = $this->getPSDetail($data);
    $totalamt = 0;

    if (!empty($data2)) {
      for ($i = 0; $i < count($data2); $i++) {
        $arr_yourref = $this->reporter->fixcolumn([$data2[$i]['yourref']], '10', 0);
        $arr_ourref = $this->reporter->fixcolumn([$data2[$i]['ourref']], '10', 0);
        $arr_supp = $this->reporter->fixcolumn([$data2[$i]['clientname']], '23', 0);
        $arr_amount = $this->reporter->fixcolumn([number_format($data2[$i]['amt'], 2)], '10', 0);
        $arr_checkdate = $this->reporter->fixcolumn([$data2[$i]['checkdate']], '10', 0);
        $arr_cvno = $this->reporter->fixcolumn([$data2[$i]['cvno']], '15', 0);
        $arr_checkdetails = $this->reporter->fixcolumn([$data2[$i]['checkdetails']], '10', 0);
        $arr_releasetoap = $this->reporter->fixcolumn([$data2[$i]['releasetoap']], '10', 0);
        $arr_rem = $this->reporter->fixcolumn([$data2[$i]['rem']], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_yourref, $arr_ourref, $arr_supp, $arr_amount, $arr_checkdate, $arr_cvno, $arr_checkdetails, $arr_releasetoap, $arr_rem]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(75, 0, (isset($arr_yourref[$r]) ? $arr_yourref[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(75, 0, (isset($arr_ourref[$r]) ? $arr_ourref[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(120, 0, (isset($arr_supp[$r]) ? $arr_supp[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(70, 0, (isset($arr_amount[$r]) ? $arr_amount[$r] : ''), '', 'R', false, 0);
          PDF::MultiCell(70, 0, (isset($arr_checkdate[$r]) ? $arr_checkdate[$r] : ''), '', 'C', false, 0);
          PDF::MultiCell(90, 0, (isset($arr_cvno[$r]) ? $arr_cvno[$r] : ''), '', 'C', false, 0);
          PDF::MultiCell(90, 0, (isset($arr_checkdetails[$r]) ? $arr_checkdetails[$r] : ''), '', 'L', false, 0);
          PDF::MultiCell(85, 0, (isset($arr_releasetoap[$r]) ? $arr_releasetoap[$r] : ''), '', 'C', false, 0);
          PDF::MultiCell(90, 0, '', '', 'L', false, 0);
          PDF::MultiCell(75, 0, '', '', 'L', false, 0);
          PDF::MultiCell(120, 0, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', false);
        }
        $totalamt += $data2[$i]['amt'];
        if (PDF::getY() > 900) $this->default_PS_header_PDF($params, $data);
      }
    }
    PDF::MultiCell(960, 0, '', 'T');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, 'Total:', '', 'L', false, 0);
    PDF::MultiCell(70, 0, number_format($totalamt, 2), '', 'R', false);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function getPSDetail($data)
  {

    $qry = "select num.pstrno as trno,head.trno as line,cvh.yourref, cvh.ourref, sum(ledger.cr-ledger.db) as amt, date(info.checkdate) as checkdate, cv.docno as cvno, cvd.checkno as checkdetails,
    date(info.releasetoap) as releasetoap, cvd.clearday as cleardate, info.rem2 as rem, head.docno as plno, date(head.dateid) as pldate, format(sum(ledger.db),2) as db, format(sum(ledger.cr),2) as cr,
      head.client, head.clientname, head.trno as pytrno,date(cvinfo.releasedate) as releasetosupp
      from hpyhead as head
      left join transnum as num on num.trno=head.trno
      left join hheadinfotrans as info on info.trno=head.trno
      left join cntnum as cv on cv.trno=num.cvtrno
      left join glhead as cvh on cvh.trno = cv.trno
      left join gldetail as cvd on cvd.trno=cv.trno and cvd.checkno<>''
      left join hcntnuminfo as cvinfo on cvinfo.trno = cv.trno
      left join apledger as ledger on ledger.py=head.trno
      where num.pstrno=" . $data[0]['trno'] . "
      group by num.pstrno,cvh.yourref, cvh.ourref, info.checkdate, cv.docno, cvd.checkno,
      info.releasetoap, cvd.clearday, info.rem2 , head.docno, date(head.dateid) ,
      head.client, head.clientname, head.trno,cvinfo.releasedate order by head.trno";
    return json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
  }
}
