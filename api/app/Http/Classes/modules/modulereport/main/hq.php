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

class hq
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
        '' as received"
    );
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];

    $query = "
        select num.trno,
        head.docno,  date(head.dateid) as dateid, head.dept, personnel, date(dateneed) as dateneed , job, head.class, headcount, hpref, agerange,
        gpref, rank, reason, remark, refx, qualification,
        d.clientname as deptname, em.clientname as personnelname, job.jobtitle, empstat.empstatus
        from personreq as head
        left join client as em on em.clientid = head.empid
        left join client as d on d.client = head.dept
        left join hrisnum as num on num.trno = head.trno
        left join jobthead as job on job.docno=head.job
        left join empstatentry as empstat on empstat.line = head.empstatusid
        where num.trno = '$trno' and num.doc='HQ'
        union all
        select num.trno,
        head.docno,  date(head.dateid) as dateid, head.dept, personnel, date(dateneed) as dateneed, job, head.class, headcount, hpref, agerange,
        gpref, rank, reason, remark, refx, qualification,
        d.clientname as deptname, em.clientname as personnelname, job.jobtitle, empstat.empstatus
        from hpersonreq as head
        left join client as em on em.clientid = head.empid
        left join client as d on d.client = head.dept
        left join hrisnum as num on num.trno = head.trno
        left join jobthead as job on job.docno=head.job
        left join empstatentry as empstat on empstat.line = head.empstatusid
        where num.trno = '$trno' and num.doc='HQ'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn



  public function reportplotting($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_HQ_layout($config, $data);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_HQ_PDF($config, $data);
    }
    return $str;
  }

  public function default_header($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $str = "";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PERSONNEL REQUISITION', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '150', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REQUESTING PERSONNEL : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['personnelname']) ? $data[0]['personnelname'] : ''), '275', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '20', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REQUESTING DEPARTMENT : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '275', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('POSITION : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['jobtitle'], '275', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('CLASS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['class'], '275', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('NO. OF HEADS : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['headcount'], '50', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE NEEDED : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['dateneed'], '100', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('HIRING PREFERENCE : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['hpref'], '100', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('AGE RANGE: ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['agerange'], '100', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GENDER PREFERENCE : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['gpref'], '100', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('EMPLOYMENT STATUS : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['empstatus'], '100', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('RANK: ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['rank'], '100', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REASON FOR HIRING : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['reason'], '275', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('OTHER QUALIFICATIONS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['qualification'], '355', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REMARKS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col($data[0]['remark'], '275', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JOB DESCRIPTION', '100', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('SKILLS REQUIREMENTS', '100', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function rpt_HQ_layout($config, $data)
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

    $str = '';
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($config, $data);

    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $trno = $data[0]['trno'];
      $qry1 = "
          select jd.description as description from jobthead AS jh
          left join jobtdesc AS jd ON jh.line = jd.trno
          where docno = (select job from ( select trno, job from personreq 
          union all select trno, job from hpersonreq ) as d where trno = '$trno')";
      $getjobdesc = $this->coreFunctions->opentable($qry1);

      $qry2 = "
          select s.skill as description from jobthead AS jh
          left join jobtskills as js ON jh.line = js.trno
          left join skillrequire as s ON s.line = js.skills
          where docno = (select job from ( select trno, job from personreq 
          union all select trno, job from hpersonreq ) as d where trno = '$trno')";
      $getskillreq = $this->coreFunctions->opentable($qry2);

      $jdesc = "";
      $jskillreq = "";
      foreach ($getjobdesc as $key => $jobdesc) {
        $jdesc .= $jobdesc->description . "<br>";
      }

      foreach ($getskillreq as $key => $skillreq) {
        $jskillreq .= $skillreq->description . "<br>";
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($jdesc, '100', null, false, $border, '', 'L', $font, '11', '', '', '2px');
      $str .= $this->reporter->col($jskillreq, '100', null, false, $border, '', 'L', $font, '11', '', '', '2px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function default_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $border = '1px solid';

    $font = "";
    $fontbold = "";
    $font_size = '10';

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

    PDF::SetFont($fontbold, 'B', 14);
    PDF::MultiCell(510, 30, "PERSONNEL REQUISITION", '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 30, "DOCUMENT # : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 30, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(160, 0, "REQUESTING PERSONNEL : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(350, 0, (isset($data[0]['personnelname']) ? $data[0]['personnelname'] : ''), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 0, "DATE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(160, 0, "REQUESTING DEPARTMENT : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(350, 0, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(120, 10, "POSITION : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(135, 10, $data[0]['jobtitle'], '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(130, 10, "CLASS : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 10, $data[0]['class'], '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 10, "NO. OF HEADS : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, $data[0]['headcount'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(120, 10, "DATE NEEDED : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(135, 10, $data[0]['dateneed'], '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(130, 10, "HIRING PREFERENCE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 10, $data[0]['hpref'], '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 10, "AGE RANGE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, $data[0]['agerange'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(120, 10, "GENDER PREFERENCE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(135, 10, $data[0]['gpref'], '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(130, 10, "EMPLOYMENT STATUS : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(125, 10, $data[0]['empstatus'], '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 10, "RANK : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, $data[0]['rank'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 10, "REASON FOR HIRING : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(105, 10, $data[0]['reason'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 10, "OTHER QUALIFICATIONS : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(105, 10, $data[0]['qualification'], '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(150, 10, "REMARKS : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(105, 10, $data[0]['remark'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(380, 10, "JOB DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(380, 10, "SKILLS REQUIREMENTS", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");
  }


  public function rpt_HQ_PDF($config, $data)
  {
    $border = '1px solid';
    $fontsize = '11';
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $count = 35;
    $page = 35;

    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->default_header_PDF($config, $data);

    for ($i = 0; $i < count($data); $i++) {
      $trno = $data[0]['trno'];
      $qry1 = "
          select jd.description as description from jobthead AS jh
          left join jobtdesc AS jd ON jh.line = jd.trno
          where docno = (select job from ( select trno, job from personreq 
          union all select trno, job from hpersonreq ) as d where trno = '$trno')";
      $getjobdesc = $this->coreFunctions->opentable($qry1);

      $qry2 = "
          select s.skill as description from jobthead AS jh
          left join jobtskills as js ON jh.line = js.trno
          left join skillrequire as s ON s.line = js.skills
          where docno = (select job from ( select trno, job from personreq 
          union all select trno, job from hpersonreq ) as d where trno = '$trno')";
      $getskillreq = $this->coreFunctions->opentable($qry2);

      PDF::SetFont($font, '', $fontsize);
      $jdesc = "";
      $jskillreq = "";
      foreach ($getjobdesc as $key => $jobdesc) {
        $jdesc .= $jobdesc->description . "\n";
      }

      foreach ($getskillreq as $key => $skillreq) {
        $jskillreq .= $skillreq->description . "\n";
      }

      $maxrow = 1;
      $arr_jdesc = $this->reporter->fixcolumn([$jdesc], '40', 0);
      $arr_jskillreq = $this->reporter->fixcolumn([$jskillreq], '40', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_jdesc, $arr_jskillreq]);
      for ($r = 0; $r < $maxrow; $r++) {
        PDF::MultiCell(380, 10, (isset($arr_jdesc[$r]) ? $arr_jdesc[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(380, 10, (isset($arr_jskillreq[$r]) ? $arr_jskillreq[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
      }
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn
}
