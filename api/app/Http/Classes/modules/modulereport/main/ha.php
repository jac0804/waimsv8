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

class ha
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
      dept.clientname as deptname, dept.client as dept, head.trno, head.docno, 
      head.empid, date(head.dateid) as dateid, head.empname,
      head.jobtitle, head.type, head.title,
      head.venue, date(head.date1) as date1, date(head.date2) as date2, 
      head.purpose, head.budget,head.deptid,
      emp.client as empcode,
      emp.clientname as empname
      from traindev as head
      left join hrisnum as num on num.trno = head.trno
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      where num.trno = '$trno' and num.doc='HA'
      union all
    select
      dept.clientname as deptname, dept.client as dept, head.trno, head.docno, 
      head.empid, date(head.dateid) as dateid, head.empname,
      head.jobtitle, head.type, head.title,
      head.venue, date(head.date1) as date1, date(head.date2) as date2, 
      head.purpose, head.budget,head.deptid,
      emp.client as empcode,
      emp.clientname as empname
      from Htraindev as head
      left join hrisnum as num on num.trno = head.trno
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      where num.trno = '$trno' and num.doc='HA'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config, $data)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_HA_layout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_HA_PDF($config, $data);
    }
    return $str;
  }


  public function rpt_HA_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];
    $border = "1px solid ";
    $fontsize = "11";
    $count = 55;
    $page = 54;
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

    if ($companyid == 58) { // cdohris

      $query = "select division from employee where empid = " . $data[0]['empid'] . "";
      $empcomp = $this->coreFunctions->opentable($query);
      if (!empty($empcomp)) {
        if ($empcomp[0]->division == '001') {
          PDF::Image($this->companysetup->getlogopath($config['params']) . 'logocdo2cycles.jpg', '645', '-5', 160, 160);
        }
        if ($empcomp[0]->division == '002') {
          PDF::Image($this->companysetup->getlogopath($config['params']) . 'logombc.jpg', '645', '-5', 160, 160);
        }
        if ($empcomp[0]->division == '003') {
          PDF::Image($this->companysetup->getlogopath($config['params']) . 'logoridefund.png', '645', '-5', 160, 160);
        }
      } else {
        goto def;
      }
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
      PDF::MultiCell(0, 0, "\n");
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'L');
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L');

      // PDF::MultiCell(0, 0, "\n\n\n\n\n");
    } else {
      def:
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    }

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 30, "REQUEST TRAINING AND DEVELOPMENT", '', 'L', false);

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
    PDF::MultiCell(150, 20, "EMPLOYEE NAME : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['empname'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "DEPARTMENT : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['deptname'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "POSITION : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['jobtitle'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "TITLE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['title'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "PURPOSE OF TRAINING : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['purpose'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "BUDGET : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['budget'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "TYPE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['type'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "VENUE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['venue'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 20, "DATE START : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(320, 20, $data[0]['date1'], '', 'L', false);

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
  } //end fn

  public function rpt_HA_layout($config, $data)
  {

    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared = $config['params']['dataparams']['prepared'];
    $received = $config['params']['dataparams']['received'];
    $approved = $config['params']['dataparams']['approved'];

    $str = '';
    $border = "1px solid ";
    $font = "Century Gothic ";
    $fontsize = "11";
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
    $str .= $this->reporter->col('REQUEST TRAINING AND DEVELOPMENT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->begintable('600');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT # :', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['docno'], '275', null, false, $border, '', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->col('DATE : ', '30', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($data[0]['dateid'], '100', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE NAME:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['empname'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DEPARTMENT:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['deptname'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('POSITION:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['jobtitle'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TITLE:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['title'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PURPOSE OF TRAINING:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['purpose'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BUDGET:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['budget'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TYPE:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['type'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('VENUE:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['venue'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE START:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['date1'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE END:', '80', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['date2'], '120', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


}
