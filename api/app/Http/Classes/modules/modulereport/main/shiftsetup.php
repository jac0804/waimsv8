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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class shiftsetup
{

  private $modulename = "Shift Setup";
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
    $trno = $filters['params']['dataid'];
    $query = "
        select
          head.shftcode,
          date(head.tschedin) as tschedin,
          time(head.tschedin) as ftime,
          date(head.tschedout) as tschedout,
          time(head.tschedout) as ttime, head.flexit,
          head.gtin, gbrkin, time(head.ndifffrom) as ndifffrom,
          time(head.ndiffto) as ndiffto, head.elapse,
          case 
            when details.dayn = '1' then 'Monday'
            when details.dayn = '2' then 'Tuesday'
            when details.dayn = '3' then 'Wednesday'
            when details.dayn = '4' then 'Thursday'
            when details.dayn = '5' then 'Friday'
            when details.dayn = '6' then 'Saturday'
            when details.dayn = '7' then 'Sunday'
          end as dayn,
          time_format(details.schedin, '%H:%i %p') as schedin,
          time_format(details.schedout, '%H:%i %p') as schedout,
          time_format(details.breakout, '%H:%i %p') as breakout,
          time_format(details.breakin, '%H:%i %p') as breakin,
          details.tothrs
        from tmshifts as head
        left join shiftdetail as details on head.line = details.shiftsid
        where head.line = '$trno'
        order by details.line
      ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->rpt_Shiftsetup_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_ratesetup_PDF($params, $data);
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
    $str .= $this->reporter->col('SHIFT SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->col('Code:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col($data[0]['shftcode'], '690', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Days', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Sched In', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Sched Out', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Lunch Out', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Lunch In', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Total Hours', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function rpt_Shiftsetup_layout($filters, $data)
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
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['dayn'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['schedin'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['schedout'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['breakout'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['breakin'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['tothrs'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn


  public function default_ratesetup_header_PDF($params, $data)
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

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Code: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (isset($data[0]['shftcode']) ? $data[0]['shftcode'] : ''), '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 9);
    PDF::MultiCell(100, 0, "DAYS", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "SCHED IN", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "SCHED OUT", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "LUNCH OUT", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "LUNCH IN", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "TOTAL HOURS", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_ratesetup_PDF($params, $data)
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
    $this->default_ratesetup_header_PDF($params, $data);




    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $arr_dayn = $this->reporter->fixcolumn([$data[$i]['dayn']], '16', 0);
      $arr_schedin = $this->reporter->fixcolumn([$data[$i]['schedin']], '16', 0);
      $arr_schedout = $this->reporter->fixcolumn([$data[$i]['schedout']], '16', 0);
      $arr_breakout = $this->reporter->fixcolumn([$data[$i]['breakout']], '16', 0);
      $arr_breakin = $this->reporter->fixcolumn([$data[$i]['breakin']], '16', 0);
      $arr_tothrs = $this->reporter->fixcolumn([$data[$i]['tothrs']], '16', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_dayn, $arr_schedin, $arr_schedout, $arr_breakout, $arr_breakin, $arr_tothrs]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($arr_dayn[$r]) ? $arr_dayn[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(100, 0, (isset($arr_schedin[$r]) ? $arr_schedin[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(100, 0, (isset($arr_schedout[$r]) ? $arr_schedout[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(100, 0, (isset($arr_breakout[$r]) ? $arr_breakout[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(100, 0, (isset($arr_breakin[$r]) ? $arr_breakin[$r] : ''), '', 'L', 0, 0, '', '');
        PDF::MultiCell(100, 0, (isset($arr_tothrs[$r]) ? $arr_tothrs[$r] : ''), '', 'R', 0, 0, '', '');
        PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '');
      }
      if (intVal($i) + 1 == $page) {
        $this->default_ratesetup_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(700, 0, "", "T");
    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    // PDF::MultiCell(253, 0, 'Prepared By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Approved By: ', '', 'C', false, 0);
    // PDF::MultiCell(253, 0, 'Received By: ', '', 'C');


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
