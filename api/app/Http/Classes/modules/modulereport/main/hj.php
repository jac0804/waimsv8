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

class hj
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
    $query = "
    select
      num.trno, num.docno, head.empid, app.empcode, date(head.dateid) as dateid, head.emptitle, date(head.effectdate) as effectdate, head.classrate,
      head.rate, head.empstat, head.monthsno, head.empname,
      head.jobtitle, head.empno, head.nodep, head.dcode, head.dname, head.emptitle as jobcode,
      dept.clientid as deptid, dept.client as dept, dept.clientname as deptname, section.sectid,
      section.sectname, section.sectcode, pgroup.paygroup as tpaygroup, pgroup.line as paygroupid,
      empstat.empstatus as empdesc
    from joboffer as head
    left join client as cl on cl.clientid=head.empid
    left join hrisnum as num on num.trno = head.trno
    left join app on app.empid = head.empid 
    left join client as dept on head.deptid=dept.clientid
    left join paygroup as pgroup on pgroup.line=head.paygroupid
    left join section as section on section.sectid=head.sectid
    left join empstatentry as empstat on empstat.line = head.empstat
    where num.trno = '$trno' and num.doc='HJ'
    union all
    select
      num.trno, num.docno, head.empid, app.empcode, date(head.dateid) as dateid, head.emptitle,date(head.effectdate) as effectdate, head.classrate,
      head.rate, head.empstat, head.monthsno, head.empname,
      head.jobtitle, head.empno, head.nodep, head.dcode, head.dname, head.emptitle as jobcode,
      dept.clientid as deptid, dept.client as dept, dept.clientname as deptname, section.sectid,
      section.sectname, section.sectcode, pgroup.paygroup as tpaygroup, pgroup.line as paygroupid,
      empstat.empstatus as empdesc
    from hjoboffer as head
    left join client as cl on cl.clientid=head.empid   
    left join hrisnum as num on num.trno = head.trno
    left join app on app.empid = head.empid
    left join client as dept on head.deptid=dept.clientid
    left join paygroup as pgroup on pgroup.line=head.paygroupid
    left join section as section on section.sectid=head.sectid
    left join empstatentry as empstat on empstat.line = head.empstat
    where num.trno = '$trno' and num.doc='HJ'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config, $data)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_HJ_layout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_HJ_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_HJ_PDF($config, $data)
  {
    $border = '1px solid';
    $font_size = '11';

    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $font = "";
    $fontbold = "";

    $count = 55;
    $page = 54;

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

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


    if ($companyid == 58) { // cdohris

      if ($data[0]['dcode'] == '001') {
        PDF::Image($this->companysetup->getlogopath($config['params']) . 'logocdo2cycles.jpg', '645', '-10', 160, 160);
      }
      if ($data[0]['dcode'] == '002') {
        PDF::Image($this->companysetup->getlogopath($config['params']) . 'logombc.jpg', '645', '-10', 160, 160);
      }
      if ($data[0]['dcode'] == '003') {
        PDF::Image($this->companysetup->getlogopath($config['params']) . 'logoridefund.png', '645', '-10', 160, 160);
      }
      
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'L');
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L');

    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    }

    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(800, 30, "JOB OFFER", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "DOCUMENT # : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(350, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "DATE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "APPLICANT NAME : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['empname'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "POSITION : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['jobtitle'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "HIRED DATE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['effectdate'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "RATE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, number_format($data[0]['rate'], 2), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "CLASSRATE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['classrate'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "STATUS : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['empdesc'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "PAYGROUP : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['tpaygroup'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "COMPANY : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['dname'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "DEPARTMENT : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['deptname'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "SECTION : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['sectname'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    PDF::MultiCell(254, 0, $approved, '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_HJ_layout($config, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];


    $str = '';
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB OFFER ', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT # :', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['docno'], '275', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('DATE : ', '30', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['dateid'], '100', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('APPLICANT NAME:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['empname'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('POSITION : ', '80', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['jobtitle'], '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('HIRED DATE : ', '80', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['effectdate'], '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RATE:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col(number_format($data[0]['rate'], 2), '120', null, false, $border, '', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CLASSRATE : ', '80', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['classrate'], '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATUS : ', '80', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['empdesc'], '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAYGROUP : ', '80', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['tpaygroup'], '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COMPANY:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['dname'], '120', null, false, $border, '', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT : ', '80', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['deptname'], '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SECTION : ', '80', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['sectname'], '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


}
