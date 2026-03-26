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

class hs
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
    return $this->coreFunctions->opentable("select 
      'PDFM' as print,
      '' as approved,
      '' as received,
      '' as prepared
    ");
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
    select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid, 
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      head.fpaymode, head.fpaygroup, head.fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, (case when head.isactive=1 then 'YES' else  'NO' end) as isactive,      
      fdiv.divname as fdivname,fsect.sectname as fsectname,frole.name as frolename,
      tdiv.divname as tdivname,tsect.sectname as tsectname,trole.name as trolename
      from eschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join hrisnum as num on num.trno = head.trno
      left join jobthead as jt on jt.line = emp.jobid
      left join rolesetup as frole on frole.line = head.froleid
      left join rolesetup as trole on trole.line = head.troleid
      left join section as tsect on tsect.sectid = head.tsectid
      left join division as tdiv on tdiv.divid = head.tdivid
      left join section as fsect on fsect.sectid = head.fsectid
      left join division as fdiv on fdiv.divid = head.fdivid
      where num.trno = '$trno' and num.doc='HS'
      union all
      select
      head.trno, head.docno, head.empid, date(head.dateid) as dateid,
      concat(emp.empfirst, ' ', emp.empmiddle, ' ', emp.emplast) as empname,
      c.client as empcode,
      ifnull(dept.clientid,0) as deptid, dept.clientname as deptname, dept.client as dept,
      jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid, 
      stat.code as statcode, stat.stat as statdesc,
      head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
      date(head.resigned) as resigned, head.remarks,
      head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
      head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
      head.fpaymode, head.fpaygroup, head.fpayrate, head.fallowrate, head.fbasicrate,
      head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
      head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
      head.tbasicrate, (case when head.isactive=1 then 'YES' else  'NO' end) as isactive,      
      fdiv.divname as fdivname,fsect.sectname as fsectname,frole.name as frolename,
      tdiv.divname as tdivname,tsect.sectname as tsectname,trole.name as trolename
      from heschange as head
      left join employee as emp on emp.empid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join client as c on c.clientid=emp.empid
      left join app as ap on ap.empid=emp.aplid
      left join statchange as stat on head.statcode=stat.code
      left join hrisnum as num on num.trno = head.trno
      left join jobthead as jt on jt.line = emp.jobid
      left join rolesetup as frole on frole.line = head.froleid
      left join rolesetup as trole on trole.line = head.troleid
      left join section as tsect on tsect.sectid = head.tsectid
      left join division as tdiv on tdiv.divid = head.tdivid
      left join section as fsect on fsect.sectid = head.fsectid
      left join division as fdiv on fdiv.divid = head.fdivid
      where num.trno = '$trno' and num.doc='HS'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($config, $data)
  {
    $data = $this->report_default_query($config);

    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_HS_layout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_HS_PDF($config, $data);
    }
    return $str;
  }

  public function rpt_HS_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $fontsize = "11";
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
    PDF::MultiCell(550, 18, "EMPLOYMENT STATUS CHANGE ", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(95, 18, "DOCUMENT # : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(115, 18, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Employee : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(450, 18, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(95, 18, "DATE : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(115, 18, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Job Title : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Department : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Status Change : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['statcode']) ? $data[0]['statcode'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Description : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['statdesc']) ? $data[0]['statdesc'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Contract Start : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['constart']) ? $data[0]['constart'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Contract End : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['conend']) ? $data[0]['conend'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Effectivity of", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, '', '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Rate/Allowance : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['effdate']) ? $data[0]['effdate'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Resigne Date : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['resigned']) ? $data[0]['resigned'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Active Employee : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(280, 18, (isset($data[0]['isactive']) ? $data[0]['isactive'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(380, 0, "FROM", '', 'L', false, 0);
    PDF::MultiCell(380, 0, "TO", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Type : ' . $data[0]['ftype'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Type : ' . $data[0]['ttype'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Level : ' . $data[0]['flevel'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Level : ' . $data[0]['tlevel'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Job Title : ' . $data[0]['fjobcode'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Job Title : ' . $data[0]['tjobcode'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Emp. Status : ' . $data[0]['fempstatcode'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Emp. Status : ' . $data[0]['tempstatcode'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Rank : ' . $data[0]['frank'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Rank : ' . $data[0]['trank'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Job Grade : ' . $data[0]['fjobgrade'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Job Grade : ' . $data[0]['tjobgrade'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Department : ' . $data[0]['fdeptcode'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Department : ' . $data[0]['tdeptcode'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Location : ' . $data[0]['flocation'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Location : ' . $data[0]['tlocation'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Mode of Payment : ' . $data[0]['fpaymode'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Mode of Payment : ' . $data[0]['tpaymode'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Paygroup : ' . $data[0]['fpaygroup'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Paygroup : ' . $data[0]['tpaygroup'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Classrate : ' . $data[0]['fpayrate'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Classrate : ' . $data[0]['tpayrate'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Allowance : ' . $data[0]['fallowrate'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Allowance : ' . $data[0]['tallowrate'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Basic Salary : ' . $data[0]['fbasicrate'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Basic Salary : ' . $data[0]['tbasicrate'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Division : ' . $data[0]['fdivname'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Division : ' . $data[0]['tdivname'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Section : ' . $data[0]['fsectname'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Section : ' . $data[0]['tsectname'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(380, 18, 'Role : ' . $data[0]['frolename'], '', 'L', false, 0);
    PDF::MultiCell(380, 18, 'Role : ' . $data[0]['trolename'], '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "NOTES : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(660, 18, (isset($data[0]['remarks']) ? $data[0]['remarks'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(253, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(254, 0, $config['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function rpt_HS_layout($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];


    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('EMPLOYMENT STATUS CHANGE', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['empname']) ? $data[0]['empname'] : ''), '360', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '75', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Department : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Status Change : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['statcode']) ? $data[0]['statcode'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Description : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['statdesc']) ? $data[0]['statdesc'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Contract Start: ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['constart']) ? $data[0]['constart'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Contract End : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['conend']) ? $data[0]['conend'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Effectivity of Rate/Allowance: ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['effdate']) ? $data[0]['effdate'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Resigne Date: ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['resigned']) ? $data[0]['resigned'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Active Employee: ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['isactive']) ? $data[0]['isactive'] : ''), '350', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FROM', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TO', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Type : ' . $data[0]['ftype'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Type : ' . $data[0]['ttype'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Level : ' . $data[0]['flevel'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Level : ' . $data[0]['tlevel'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title : ' . $data[0]['fjobcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Job Title : ' . $data[0]['tjobcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Emp. Status : ' . $data[0]['fempstatcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Emp. Status : ' . $data[0]['tempstatcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Rank : ' . $data[0]['frank'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Rank : ' . $data[0]['trank'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Grade : ' . $data[0]['fjobgrade'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Job Grade : ' . $data[0]['tjobgrade'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ' . $data[0]['fdeptcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Department : ' . $data[0]['tdeptcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Location : ' . $data[0]['flocation'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Location : ' . $data[0]['tlocation'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Mode of Payment : ' . $data[0]['fpaymode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Mode of Payment : ' . $data[0]['tpaymode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Paygroup : ' . $data[0]['fpaygroup'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Paygroup : ' . $data[0]['tpaygroup'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Classrate : ' . $data[0]['fpayrate'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Classrate : ' . $data[0]['tpayrate'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Allowance : ' . $data[0]['fallowrate'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Allowance : ' . $data[0]['tallowrate'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Basic Salary : ' . $data[0]['fbasicrate'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Basic Salary : ' . $data[0]['tbasicrate'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Division : ' . $data[0]['fdivname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Division : ' . $data[0]['tdivname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Section : ' . $data[0]['fsectname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Section : ' . $data[0]['tsectname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Role : ' . $data[0]['frolename'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Role : ' . $data[0]['trolename'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->col('NOTES :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['remarks'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
