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
use DateTime;
use Illuminate\Support\Facades\Storage;

class hc
{

  private $modulename;
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
    $fields = ['radioprint', 'radioreporttype', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'orange'],
      ['label' => 'COE', 'value' => '1', 'color' => 'orange']

    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '0' as reporttype,
      '' as approved
    ");
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
    select
      num.trno,
      num.docno,
      head.empid,
      em.client as empcode,
      upper(em.clientname) as empname,
      head.deptid,
      d.client as dept,
      d.clientname as deptname,
      date(head.dateid) as dateid, 
      date(head.hired) as hired,
      date(head.lastdate) lastday,
      head.jobtitle,
      head.empheadid,
      cl.client as emphead,
      cl.clientname as empheadname,
      head.cause
      from clearance as head
      left join client as cl on cl.clientid=head.empheadid   
      left join client as em on em.clientid=head.empid
      left join client as d on d.clientid=head.deptid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HC'
      union all
      select
      num.trno,
      num.docno,
      head.empid,
      em.client as empcode,
      upper(em.clientname) as empname,
      head.deptid,
      d.client as dept,
      d.clientname as deptname,
      date(head.dateid) as dateid, 
      date(head.hired) as hired,
      date(head.lastdate) lastday,
      head.jobtitle,
      head.empheadid,
      cl.client as emphead,
      cl.clientname as empheadname,
      head.cause
      from hclearance as head
      left join client as cl on cl.clientid=head.empheadid   
      left join client as em on em.clientid=head.empid
      left join client as d on d.clientid=head.deptid
      left join hrisnum as num on num.trno = head.trno
      where num.trno = '$trno' and num.doc='HC'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($config, $data)
  {
    $printtype = $config['params']['dataparams']['print'];
    $reptype = $config['params']['dataparams']['reporttype'];
    $data = $this->report_default_query($config);

    switch ($printtype) {
      case 'PDFM':
        if ($reptype == '0') {
          $str = $this->rpt_HC_PDF($config, $data);
        } else {
          $str = $this->rpt_coe_layout($config, $data);
        }
        break;
      default:
        $str = $this->rpt_HC_layout($config, $data);
        break;
    }
    return $str;
  }

  public function rpt_HC_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $fontsize = "13";
    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(550, 18, "CLEARANCE ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(95, 18, "DOCUMENT # : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(115, 18, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(550, 18, " ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(95, 18, "DATE : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(115, 18, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 18, 'To Whom It May Concern: ', '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 18, 'This is to certify that ' . $data[0]['empname'] . ', whose signature appears below,', '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 18, 'has been cleared from any property and financial obligations with the company.', '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(145, 18, "DATE HIRED : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 18, (isset($data[0]['hired']) ? $data[0]['hired'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(145, 18, "LAST DAY OF WORK : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 18, (isset($data[0]['lastday']) ? $data[0]['lastday'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(145, 18, "CAUSE OF SEPERATION : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 18, (isset($data[0]['cause']) ? $data[0]['cause'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(145, 18, "IMMEDIATE HEAD : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(120, 18, (isset($data[0]['empheadname']) ? $data[0]['empheadname'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "_____________________________________", '', 'C', false, 0);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "_____________________________________", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "Department Head", '', 'C', false, 0);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "Accounting Department", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "_____________________________________", '', 'C', false, 0);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "_____________________________________", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "Human Resources Department", '', 'C', false, 0);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "Audit Department", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "", '', 'C', false, 0);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, $data[0]['empname'], '', 'C', false, 0);
    PDF::MultiCell(100, 18, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "_____________________________________", '', 'C', false, 0);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "_____________________________________", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "VP/President", '', 'C', false, 0);
    PDF::MultiCell(100, 18, " ", '', 'L', false, 0);
    PDF::MultiCell(230, 18, "Print Name & Signature of Employee", '', 'C', false, 0);
    PDF::MultiCell(100, 18, "", '', 'L', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_HC_layout($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "13";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('CLEARANCE', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('', '360', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '75', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br />';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('To Whom It May Concern:', '105', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('This is to certify that ' . $data[0]['empname'] . ', whose signature appears below,', '305', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('has been cleared from any property and financial obligations with the company.', '305', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();
    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE HIRED : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['hired'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LAST DAY OF WORK : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['lastday'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CAUSE OF SEPERATION : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['cause'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('IMMEDIATE HEAD : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['empheadname'], '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department Head', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Accounting Department', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Human Resources Department', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Audit Department', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['empname'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('_____________________________________', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('VP/President', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Print Name & Signature of Employee', '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
  public function rpt_coe_layout($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $fontsize = "14";
    $font_size = "11";
    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/times.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/times.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/timesbd.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $qry = "select divi.address ,divi.divname from employee as emp left join division as divi on divi.divid = emp.divid where emp.empid = " . $data[0]['empid'] . "";
    $div = $this->coreFunctions->opentable($qry);

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(140, 140);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, '', '', false);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(520, 0, strtoupper($headerdata[0]->name), '', 'C', false, 1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(520, 0, strtoupper($headerdata[0]->address), '', 'C', false, 1);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(520, 0, strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, "This is to certify that", '', 'C', false);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(520, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, 'B', 18);
    PDF::MultiCell(520, 0, "" . $data[0]['empname'], '', 'C', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(520, 0, "", '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, "has been employed in", '', 'C', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(520, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 15);
    PDF::MultiCell(520, 0, "" . $div[0]->divname, '', 'C', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, "as", '', 'C', false);

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(520, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 18);
    PDF::MultiCell(520, 0, "" . $data[0]['jobtitle'], '', 'C', false);
    $lastday = "";
    if ($data[0]['lastday'] != null) {
      $lastday = $data[0]['lastday'];
    } else {
      $lastday = date('Y-m-d'); //current
    }
    $gender = $this->coreFunctions->datareader("select gender as value from employee where empid= " . $data[0]['empid'] . "");

    if (empty($gender) || !$gender) {
      $gender = 'Mr/Ms. ';
      $phrases = 'his/her';
      $phrase = 'He/She';
    } else {
      if (substr($gender, 0, 1) != 'F') {
        $gender = 'Mr. ';
        $phrases = 'his';
        $phrase = 'He';
      } else {
        $gender = 'Ms. ';
        $phrases = 'her';
        $phrase = 'She';
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(520, 0, "From " . $data[0]['hired'] . " up to " . $lastday, '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");


    $lname = explode(',', $data[0]['empname']);
    $lastname = ucfirst(strtolower(trim($lname[0])));
    PDF::SetFont($font, '', $fontsize);
    $first = "Furthermore, $gender $lastname is cleared all of $phrases accountabilities, propriety and monetary in";
    $second  = "connection with $phrases employement. $phrase is know to be efficient and effective in performing";
    $third  = "$phrases duties and reponsibilities and has a good moral character.";

    $arr_first = $this->reporter->fixcolumn([$first], '115', 0);
    $arr_second = $this->reporter->fixcolumn([$second], '115', 0);
    $arr_third = $this->reporter->fixcolumn([$third], '115', 0);

    $maxrow = $this->othersClass->getmaxcolumn([$arr_first, $arr_second, $arr_third]);


    for ($r = 0; $r < $maxrow; $r++) {

      PDF::MultiCell(520, 0, "" . (isset($arr_first[$r]) ? $arr_first[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(520, 0, "" . (isset($arr_second[$r]) ? $arr_second[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      PDF::MultiCell(520, 0, "" . (isset($arr_third[$r]) ? $arr_third[$r] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
    }
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(520, 0, "This certification is being issued upon request by aforementioned name for whatever lawful purposes it may serve", '', 'C', false);
    PDF::MultiCell(0, 0, "\n\n\n\n");
    $current = date('Y-m-d');

    $date = new DateTime($current);
    $day = $date->format('j');
    $myear = $date->format('F Y');
    $orday = $this->othersClass->getOrdinal($day);

    $add = "No address";
    if (!empty($div)) {
      $add = $div[0]->address;
    }
    $approved = isset($config['params']['dataparams']['approved']) ? strtoupper($config['params']['dataparams']['approved']) : '';
    PDF::MultiCell(520, 0, "Given this $orday day of $myear at $add ", '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(185, 0, "", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "" . $approved, 'B', 'C', false, 0);
    PDF::MultiCell(185, 0, "", '', 'C', false, 1);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(185, 0, "", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "General Manager", '', 'C', false, 0);
    PDF::MultiCell(185, 0, "", '', 'C', false, 1);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
