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

class et
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
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
            'default' as print,
            '' as prepared,
            '' as approved,
            '' as received
        "
    );
  }

  public function report_default_query($trno)
  {
    $query = "select ath.trno, ath.docno, date(ath.dateid) as dateid, ath.periodid, p.code as periodcode, p.name as periodname, ath.syid, sy.sy,
    atf.line, atf.levelid, l.levels, atf.departid, d.clientname as deptname, atf.courseid, c.coursecode, c.coursename,
    atf.yr, atf.semid, t.term as semester, atf.section, atf.subjectid, sj.subjectcode, sj.subjectname, atf.sex,
    atf.feesid, f.feescode, f.feesdesc, atf.schemeid, s.scheme, atf.rate,
    atf.isnew, atf.isold, atf.isforeign, atf.isadddrop, atf.iscrossenrollee, atf.islateenrollee, atf.istransferee
    from en_athead as ath left join en_period as p on p.line=ath.periodid left join en_schoolyear as sy on sy.line=ath.syid
    left join en_atfees as atf on atf.trno=ath.trno left join en_levels as l on l.line=atf.levelid left join client as d on d.clientid=atf.departid
    left join en_course as c on c.line=atf.courseid left join en_term as t on t.line=atf.semid left join en_subject as sj on sj.trno=atf.subjectid
    left join en_fees as f on f.line=atf.feesid left join en_scheme as s on s.line=atf.schemeid
    where ath.trno=" . $trno . "
    union all
    select ath.trno, ath.docno, date(ath.dateid) as dateid, ath.periodid, p.code as periodcode, p.name as periodname, ath.syid, sy.sy,
    atf.line, atf.levelid, l.levels, atf.departid, d.clientname as deptname, atf.courseid, c.coursecode, c.coursename,
    atf.yr, atf.semid, t.term as semester, atf.section, atf.subjectid, sj.subjectcode, sj.subjectname, atf.sex,
    atf.feesid, f.feescode, f.feesdesc, atf.schemeid, s.scheme, atf.rate,
    atf.isnew, atf.isold, atf.isforeign, atf.isadddrop, atf.iscrossenrollee, atf.islateenrollee, atf.istransferee
    from en_glhead as ath left join en_period as p on p.line=ath.periodid left join en_schoolyear as sy on sy.line=ath.syid
    left join en_glfees as atf on atf.trno=ath.trno left join en_levels as l on l.line=atf.levelid left join client as d on d.clientid=atf.departid
    left join en_course as c on c.line=atf.courseid left join en_term as t on t.line=atf.semid left join en_subject as sj on sj.trno=atf.subjectid
    left join en_fees as f on f.line=atf.feesid left join en_scheme as s on s.line=atf.schemeid
    where ath.trno=" . $trno . "
    order by trno, levelid, departid, courseid";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('1500');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ASSESSMENT SETUP', '580', null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('1500');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction # : ', '250', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '500', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col('Date : ', '150', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '600', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Period (SY & Grade/Year) : ', '250', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['periodcode']) ? $data[0]['periodcode'] : ''), '500', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col('School Year : ', '150', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col((isset($data[0]['sy']) ? $data[0]['sy'] : ''), '600', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1500');
    // $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $yr = '';

    $templevel = "";
    $dept = "";
    $course = "";

    $str .= $this->reporter->begintable('1500');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Level', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department', '130', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Course', '133', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Year/Grade', '90', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sem', '70', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Section', '70', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Subject', '170', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sex', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Fees', '170', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Scheme', '70', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Rate', '80', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('New', '60', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Foreign', '60', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transfer', '60', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Late', '57', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cross', '50', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Add/Drop', '50', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($data); $i++) {
      if ($templevel == '' || $templevel != $data[$i]['levels']) {
        $templevel = $data[$i]['levels'];
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['levels'], '100', null, false, $border, 'TLR', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data[$i]['deptname'], '130', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['coursename'], '133', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['yr'], '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '170', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '170', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '57', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');

        $course = $data[$i]['coursename'];
        //start test for course
        if ($course == $data[$i]['coursename']) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '133', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['semester'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['section'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['subjectname'], '180', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['sex'], '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['feesdesc'], '160', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['scheme'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '80', null, false, $border, 'TR', 'R', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col($data[$i]['isnew'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['isforeign'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['istransferee'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['islateenrollee'], '57', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['iscrossenrollee'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data[$i]['isadddrop'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
        }


        //end test for course

        $dept = $data[$i]['deptname'];

        $str .= $this->reporter->endrow();
      } else {

        if ($data[$i]['deptname'] == "") {
          if ($course != $data[$i]['coursename']) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '133', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['semester'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['section'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['subjectname'], '180', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['sex'], '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['feesdesc'], '160', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['scheme'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '80', null, false, $border, 'TR', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['isnew'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['isforeign'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['istransferee'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['islateenrollee'], '57', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['iscrossenrollee'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['isadddrop'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '133', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['semester'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['section'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['subjectname'], '180', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['sex'], '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['feesdesc'], '160', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['scheme'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '80', null, false, $border, 'TR', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['isnew'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['isforeign'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['istransferee'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['islateenrollee'], '57', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['iscrossenrollee'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['isadddrop'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
          }
        } else {
          if ($dept == '' || $dept != $data[$i]['deptname']) {
            $dept = $data[$i]['deptname'];
            $course = $data[$i]['coursename'];

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['deptname'], '130', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['coursename'], '133', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data[$i]['yr'], '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '180', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '160', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '57', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '50', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();

            if ($course != $data[$i]['coursename']) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '133', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['semester'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['section'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['subjectname'], '180', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['sex'], '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['feesdesc'], '160', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['scheme'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '80', null, false, $border, 'TR', 'R', $font, $fontsize, '', '', '2px');
              $str .= $this->reporter->col($data[$i]['isnew'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['isforeign'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['istransferee'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['islateenrollee'], '57', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['iscrossenrollee'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['isadddrop'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->endrow();
            } else {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '133', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['semester'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['section'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['subjectname'], '180', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['sex'], '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['feesdesc'], '160', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['scheme'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '80', null, false, $border, 'TR', 'R', $font, $fontsize, '', '', '2px');
              $str .= $this->reporter->col($data[$i]['isnew'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['isforeign'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['istransferee'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['islateenrollee'], '57', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['iscrossenrollee'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['isadddrop'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->endrow();
            }
          } else {
            if ($course == $data[$i]['coursename']) {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '133', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['semester'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['section'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['subjectname'], '180', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['sex'], '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['feesdesc'], '160', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['scheme'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '80', null, false, $border, 'TR', 'R', $font, $fontsize, '', '', '2px');
              $str .= $this->reporter->col($data[$i]['isnew'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['isforeign'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['istransferee'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['islateenrollee'], '57', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['iscrossenrollee'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['isadddrop'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->endrow();
            } else {
              $str .= $this->reporter->startrow();
              $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data[$i]['coursename'], '133', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '180', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '160', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '57', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col('', '50', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
              $str .= $this->reporter->endrow();
              $course = $data[$i]['coursename'];

              if ($course == $data[$i]['coursename']) {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '130', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '133', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '90', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['semester'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['section'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['subjectname'], '180', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['sex'], '80', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['feesdesc'], '160', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['scheme'], '70', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data[$i]['rate'], 2), '80', null, false, $border, 'TR', 'R', $font, $fontsize, '', '', '2px');
                $str .= $this->reporter->col($data[$i]['isnew'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['isforeign'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['istransferee'], '60', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['islateenrollee'], '57', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['iscrossenrollee'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data[$i]['isadddrop'], '50', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
              }
            }
          }
        }
      }
    }

    //add 2021.09.18
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '103', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '92', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '170', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '57', null, false, $border, 'T', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    //add 2021.09.18

    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->default_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1500');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1500');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '100', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
