<?php

namespace App\Http\Classes\modules\modulereport\main;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class batchsetup
{

  private $modulename = "Batch Setup";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
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

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "select line as clientid,batch as client,date(dateid) as dateid,date(startdate) as startdate, date(enddate) as enddate,
                  case
                    when paymode = 's' then 'Semi-Monthly'
                    when paymode = 'w' then 'Weekly'
                    when paymode = 'p' then 'Pierce'
                    when paymode = 'l' then 'Last Pay'
                  end as paymode,postdate, sss, ph, hdmf,
                  case
                    when (paymode = 's' or paymode = 'w' or paymode = 'p' or paymode = 'l') and right(batch, 2) = '13' then '13th'
                    when (paymode = 'w' or paymode = 'p' or paymode = 'l') and right(batch, 2) = '01' then 'W1'
                    when (paymode = 'w' or paymode = 'p' or paymode = 'l') and right(batch, 2) = '02' then 'W2'
                    when (paymode = 'w' or paymode = 'p' or paymode = 'l') and right(batch, 2) = '03' then 'W3'
                    when (paymode = 'w' or paymode = 'p' or paymode = 'l') and right(batch, 2) = '04' then 'W4'
                    when (paymode = 'w' or paymode = 'p' or paymode = 'l') and right(batch, 2) = '05' then 'W5'
                    when (paymode = 's') and right(batch, 2) = '02' then '1st Half'
                    when (paymode = 's') and right(batch, 2) = '04' then '2nd Half'
                  end as paymodetype,
                  tax as istax,
                  tax, adjustm, custcode, allow, pgroup,
                  is13,
                  date(13start) as 13start, date(13end) as 13end from batch where line = " . $trno . "";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->rpt_batchsetup_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_batchsetup_PDF($params, $data);
    }
  }

  public function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BATCH SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('13th Month', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    // $str .= $this->reporter->col('','200',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Batch Code', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Pay Mode', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Type', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Pay Group', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Month Covered', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Period Start', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Period End', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Period Start', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Period End', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function rpt_batchsetup_layout($filters, $data)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($data[0]['client'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['paymode'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['paymodetype'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['pgroup'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['dateid'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['startdate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['enddate'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['13start'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col($data[0]['13end'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();


    $str .= $this->reporter->endreport();
    return $str;
  }

  public function default_batchsetup_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');

    // PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(535, 0, "", '', 'C', false, 0);
    PDF::MultiCell(125, 0, "13th Month", 'B', 'C', false);

    PDF::MultiCell(75, 0, "Batch Code", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Pay Mode", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Type", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Pay Group", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Month Covered", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Period Start", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Period End", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Period Start", '', 'C', false, 0);
    PDF::MultiCell(75, 0, "Period End", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_batchsetup_PDF($params, $data)
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
    $this->default_batchsetup_header_PDF($params, $data);




    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $client = $data[$i]['client'];
      $paymode = $data[$i]['paymode'];
      $paymodetype = $data[$i]['paymodetype'];
      $pgroup = $data[$i]['pgroup'];
      $dateid = $data[$i]['dateid'];
      $startdate = $data[$i]['startdate'];
      $enddate = $data[$i]['enddate'];
      $start13 = $data[$i]['13start'];
      $end13 = $data[$i]['13end'];

      $arr_client = $this->reporter->fixcolumn([$client], '16', 0);
      $arr_paymode = $this->reporter->fixcolumn([$paymode], '16', 0);
      $arr_paymodetype = $this->reporter->fixcolumn([$paymodetype], '16', 0);
      $arr_pgroup = $this->reporter->fixcolumn([$pgroup], '16', 0);
      $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
      $arr_startdate = $this->reporter->fixcolumn([$startdate], '16', 0);
      $arr_enddate = $this->reporter->fixcolumn([$enddate], '16', 0);
      $arr_13start = $this->reporter->fixcolumn([$start13], '16', 0);
      $arr_13end = $this->reporter->fixcolumn([$end13], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_client, $arr_paymode, $arr_paymodetype, $arr_pgroup, $arr_dateid, $arr_startdate, $arr_enddate, $arr_13start, $arr_13end]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(75, 0, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_paymode[$r]) ? $arr_paymode[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_paymodetype[$r]) ? $arr_paymodetype[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_pgroup[$r]) ? $arr_pgroup[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_startdate[$r]) ? $arr_startdate[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_enddate[$r]) ? $arr_enddate[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_13start[$r]) ? $arr_13start[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(75, 0, (isset($arr_13end[$r]) ? $arr_13end[$r] : ''), '', 'C', 0, 0, '', '');
        PDF::MultiCell(75, 0, '', '', 'L', 0, 1, '', '');
      }

      // PDF::SetFont($font, '', $fontsize);
      // PDF::MultiCell(75, 0, $data[0]['client'], '', 'L', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['paymode'], '', 'L', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['paymodetype'], '', 'L', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['pgroup'], '', 'L', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['dateid'], '', 'C', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['startdate'], '', 'C', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['enddate'], '', 'C', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['13start'], '', 'C', 0, 0, '', '');
      // PDF::MultiCell(75, 0, $data[0]['13end'], '', 'C', 0, 0, '', '');
      // PDF::MultiCell(75, 0, '', '', 'L', 0, 1, '', '');
      if (intVal($i) + 1 == $page) {
        $this->default_batchsetup_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(700, 0, "", "T");
    // PDF::MultiCell(760, 0, '', 'B');
    // PDF::MultiCell(0, 0, "\n");

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

    //PDF::AddPage();
    //$b = 62;
    //for ($i = 0; $i < 1000; $i++) {
    //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //  PDF::MultiCell(0, 0, "\n");
    //  if($i==$b){
    //    PDF::AddPage();
    //    $b = $b + 62;
    //  }
    //}

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
