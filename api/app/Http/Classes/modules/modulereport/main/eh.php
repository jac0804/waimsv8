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

class eh
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'ehprint', 'ehquarterlookup', 'ehstudentlookup', 'ehcomponentlookup', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'ehprint.options', [
      ['label' => 'By Student', 'value' => 'ehstudent', 'color' => 'red'],
      ['label' => 'By Component', 'value' => 'ehcomponent', 'color' => 'red'],
      ['label' => 'By Records', 'value' => 'ehrecords', 'color' => 'red'],
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
            'default' as print,
            '' as prepared,
            '' as approved,
            '' as received,
            'ehstudent' as ehprint,
            '' as ehquarterlookup,
            '' as ehstudentlookup,
            '' as ehcomponentlookup,
            '' as ehtopiclookup,
            '' as gccode
        "
    );
  }

  public function report_student_query($config)
  {
    $trno = $config['params']['dataid'];
    // $stud = $config['params']['dataparams']['clientid'];

    $query = "select head.trno,client.clientid,client.client,client.clientname,grades.points,head.docno,head.dateid,
                head.yr,head.curriculumcode,head.curriculumdocno,schedday,schedtime,scheddocno,
                course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                sy.code,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
              from en_gehead as head 
              left join en_gesubcomponent as stock on stock.trno=head.trno 
              left join client as inst on inst.clientid=head.adviserid 
              left join en_schoolyear as sy on sy.line=head.syid 
              left join en_course as course on course.line=head.courseid
              left join en_period as period on period.line=head.periodid 
              left join en_section as sec on sec.line=head.sectionid 
              left join en_rooms as rm on rm.line=head.roomid
              left join en_bldg as bldg on bldg.line=rm.bldgid 
              left join en_subject as subj on subj.trno=head.subjectid 
              left join en_gegrades as grades on grades.trno=head.trno and grades.gsline=stock.line
              left join client on client.clientid=grades.clientid
              left join en_quartersetup as quarter on stock.quarterid = quarter.line
              left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
              left join en_gradecomponent as com on com.line=gcomp.compid
              left join en_term as sem on sem.line=head.semid
              where head.trno=" . $trno . " 
              union all
              select head.trno,client.clientid,client.client,client.clientname,grades.points,head.docno,head.dateid,
                head.yr,head.curriculumcode,head.curriculumdocno,schedday,schedtime,scheddocno,
                course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                sy.code,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
              from en_glhead as head 
              left join en_glsubcomponent as stock on stock.trno=head.trno 
              left join client as inst on inst.clientid=head.adviserid 
              left join en_schoolyear as sy on sy.line=head.syid 
              left join en_course as course on course.line=head.courseid
              left join en_period as period on period.line=head.periodid 
              left join en_section as sec on sec.line=head.sectionid 
              left join en_rooms as rm on rm.line=head.roomid
              left join en_bldg as bldg on bldg.line=rm.bldgid 
              left join en_subject as subj on subj.trno=head.subjectid 
              left join en_glgrades as grades on grades.trno=head.trno and grades.gsline=stock.line
              left join client on client.clientid=grades.clientid
              left join en_quartersetup as quarter on stock.quarterid = quarter.line
              left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
              eft join en_gradecomponent as com on com.line=gcomp.compid
              left join en_term as sem on sem.line=head.semid
              where head.trno=" . $trno . "";


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_topic_query($config)
  {
    $trno = $config['params']['dataid'];
    $comtopic = $config['params']['dataparams']['ehtopiclookup'];

    $query = "select head.trno,client.clientid,client.client,client.clientname,grades.points,head.docno,head.dateid,
                head.yr,head.curriculumcode,head.curriculumdocno,schedday,schedtime,scheddocno,
                course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                sy.code,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
              from en_gehead as head 
              left join en_gesubcomponent as stock on stock.trno=head.trno 
              left join client as inst on inst.clientid=head.adviserid 
              left join en_schoolyear as sy on sy.line=head.syid 
              left join en_course as course on course.line=head.courseid
              left join en_period as period on period.line=head.periodid 
              left join en_section as sec on sec.line=head.sectionid 
              left join en_rooms as rm on rm.line=head.roomid
              left join en_bldg as bldg on bldg.line=rm.bldgid 
              left join en_subject as subj on subj.trno=head.subjectid 
              left join en_gegrades as grades on grades.trno=head.trno and grades.gsline=stock.line
              left join client on client.clientid=grades.clientid
              left join en_quartersetup as quarter on stock.quarterid = quarter.line
              left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
              left join en_gradecomponent as com on com.line=gcomp.compid
              left join en_term as sem on sem.line=head.semid
              where head.trno=" . $trno . " and stock.topic = '" . $comtopic . "'
              union all
              select head.trno,client.clientid,client.client,client.clientname,grades.points,head.docno,head.dateid,
                head.yr,head.curriculumcode,head.curriculumdocno,schedday,schedtime,scheddocno,
                course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                sy.code,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
              from en_glhead as head 
              left join en_glsubcomponent as stock on stock.trno=head.trno 
              left join client as inst on inst.clientid=head.adviserid 
              left join en_schoolyear as sy on sy.line=head.syid 
              left join en_course as course on course.line=head.courseid
              left join en_period as period on period.line=head.periodid 
              left join en_section as sec on sec.line=head.sectionid 
              left join en_rooms as rm on rm.line=head.roomid
              left join en_bldg as bldg on bldg.line=rm.bldgid 
              left join en_subject as subj on subj.trno=head.subjectid 
              left join en_glgrades as grades on grades.trno=head.trno and grades.gsline=stock.line
              left join client on client.clientid=grades.clientid
              left join en_quartersetup as quarter on stock.quarterid = quarter.line
              left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
              left join en_gradecomponent as com on com.line=gcomp.compid
              left join en_term as sem on sem.line=head.semid
              where head.trno=" . $trno . " and stock.topic = '" . $comtopic . "'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_allstudent_query($config)
  {
    $trno = $config['params']['dataid'];
    // $stud = $config['params']['dataparams']['clientid'];

    $query = "select trno,clientid,client,clientname,points,docno,dateid,yr,curriculumcode,
                curriculumdocno,schedday,schedtime,scheddocno,coursecode,coursename,advisercode,
                advisername,scode,code,section,bldgroom,subjectcode,subjectname,
                gccode,gcname,topic,noofitems,quarterid,dategiven,name,sy,semester
              from(select head.trno,client.clientid,client.client,client.clientname,
                    grades.points,head.docno,head.dateid,head.yr,head.curriculumcode,
                    head.curriculumdocno,schedday,schedtime,scheddocno,
                    course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                    sy.code as scode,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                    subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                    stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
                  from en_gehead as head
                  left join en_gesubcomponent as stock on stock.trno=head.trno
                  left join client as inst on inst.clientid=head.adviserid
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_course as course on course.line=head.courseid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_gegrades as grades on grades.trno=head.trno and grades.gsline=stock.line
                  left join client on client.clientid=grades.clientid
                  left join en_quartersetup as quarter on stock.quarterid = quarter.line
                  left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
                  left join en_gradecomponent as com on com.line=gcomp.compid
                  left join en_term as sem on sem.line=head.semid
                  where head.trno=" . $trno . "
                  union all
                  select head.trno,client.clientid,client.client,client.clientname,grades.points,head.docno,head.dateid,
                    head.yr,head.curriculumcode,head.curriculumdocno,schedday,schedtime,scheddocno,
                    course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                    sy.code as scode,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                    subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                    stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
                  from en_glhead as head
                  left join en_glsubcomponent as stock on stock.trno=head.trno
                  left join client as inst on inst.clientid=head.adviserid
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_course as course on course.line=head.courseid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_glgrades as grades on grades.trno=head.trno and grades.gsline=stock.line
                  left join client on client.clientid=grades.clientid
                  left join en_quartersetup as quarter on stock.quarterid = quarter.line
                  left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
                  left join en_gradecomponent as com on com.line=gcomp.compid
                  left join en_term as sem on sem.line=head.semid
                  where head.trno=" . $trno . ") as temp
          order by name,clientname,gccode";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_stud_query($config, $quarter, $student, $component, $topic, $ehprint)
  {
    $trno = $config['params']['dataid'];

    if ($quarter == "") {
      $qtr = "";
    } else {
      $qtr = "and name = '" . $quarter . "'";
    }

    if ($student == "") {
      $stud = "";
    } else {
      $stud = "and clientname = '" . $student . "'";
    }
    if ($component == "") {
      $comp = "";
    } else {
      $comp = "and gcname = '" . $component . "'";
    }

    if ($topic == "") {
      $top = "";
    } else {
      $top = " and topic = '" . $topic . "'";
    }

    if ($ehprint == 'ehstudent') {
      $orderby = "order by name,clientname,gccode";
    } else {
      $orderby = "";
    }
    $query = "select trno,clientid,client,clientname,points,docno,dateid,yr,curriculumcode,
                curriculumdocno,schedday,schedtime,scheddocno,coursecode,coursename,advisercode,
                advisername,scode,code,section,bldgroom,subjectcode,subjectname,
                gccode,gcname,topic,noofitems,quarterid,dategiven,name,sy,semester
              from(select head.trno,client.clientid,client.client,client.clientname,
                    grades.points,head.docno,head.dateid,head.yr,head.curriculumcode,
                    head.curriculumdocno,schedday,schedtime,scheddocno,
                    course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                    sy.code as scode,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                    subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                    stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
                  from en_gehead as head
                  left join en_gesubcomponent as stock on stock.trno=head.trno
                  left join client as inst on inst.clientid=head.adviserid
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_course as course on course.line=head.courseid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_gegrades as grades on grades.trno=head.trno and grades.gsline=stock.line
                  left join client on client.clientid=grades.clientid
                  left join en_quartersetup as quarter on stock.quarterid = quarter.line
                  left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
                  left join en_gradecomponent as com on com.line=gcomp.compid
                  left join en_term as sem on sem.line=head.semid
                  where head.trno=" . $trno . "
                  union all
                  select head.trno,client.clientid,client.client,client.clientname,grades.points,head.docno,head.dateid,
                    head.yr,head.curriculumcode,head.curriculumdocno,schedday,schedtime,scheddocno,
                    course.coursecode,course.coursename,inst.client as advisercode,inst.clientname as advisername,
                    sy.code as scode,period.code,sec.section,concat(bldg.bldgcode,'-',rm.roomcode) as bldgroom,
                    subj.subjectcode,subj.subjectname,stock.gccode,com.gcname,stock.topic,stock.noofitems,
                    stock.quarterid,stock.dategiven,quarter.name,sy.sy,sem.term as semester
                  from en_glhead as head
                  left join en_glsubcomponent as stock on stock.trno=head.trno
                  left join client as inst on inst.clientid=head.adviserid
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_course as course on course.line=head.courseid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_glgrades as grades on grades.trno=head.trno and grades.gsline=stock.line
                  left join client on client.clientid=grades.clientid
                  left join en_quartersetup as quarter on stock.quarterid = quarter.line
                 left join en_gssubcomponent as gcomp on gcomp.gcsubcode =stock.gccode
                left join en_gradecomponent as com on com.line=gcomp.compid
                  left join en_term as sem on sem.line=head.semid
                  where head.trno=" . $trno . ") as temp
          where 1=1 $stud $comp $top $qtr
          $orderby ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function student_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('GRADE ENTRY', '1200', null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';



    $str .= $this->reporter->begintable('1200');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document # : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Date : ', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '220', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Year/Grade : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['yr']) ? $data[0]['yr'] : ''), '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Building & Room Code : ', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['bldgroom']) ? $data[0]['bldgroom'] : ''), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Adviser : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col((isset($data[0]['advisercode']) ? $data[0]['advisercode'] : ''), '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Adviser Name ', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['advisername']) ? $data[0]['advisername'] : ''), '220', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Semester : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['semester']) ? $data[0]['semester'] : ''), '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('School Year : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col((isset($data[0]['sy']) ? $data[0]['sy'] : ''), '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Period : ', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['code']) ? $data[0]['code'] : ''), '220', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Section : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['section']) ? $data[0]['section'] : ''), '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sched Days : ', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['schedday']) ? $data[0]['schedday'] : ''), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Schedule : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col((isset($data[0]['scheddocno']) ? $data[0]['scheddocno'] : ''), '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Course : ', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['coursecode']) ? $data[0]['coursecode'] : ''), '220', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Course Name : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['coursename']) ? $data[0]['coursename'] : ''), '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sched Time : ', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['schedtime']) ? $data[0]['schedtime'] : ''), '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Subject : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->col((isset($data[0]['subjectcode']) ? $data[0]['subjectcode'] : ''), '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Subject Description : ', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['subjectname']) ? $data[0]['subjectname'] : ''), '220', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Curriculum : ', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['curriculumcode']) ? $data[0]['curriculumcode'] : ''), '110', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1200');

    // $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportstudentplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $studid = $params['params']['dataparams']['clientid'];
    $student = $params['params']['dataparams']['ehstudentlookup'];
    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->student_header($params, $data);

    $quarter = "";
    $quarter1 = "";
    $quarter2 = "";
    $quarter3 = "";

    $tempstud = "";
    $tempstud1 = "";
    $tempstud2 = "";
    $tempstud3 = "";
    $tempstudid = "";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }
    $str .= $this->reporter->begintable('1200');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Codebbb', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Topic', '400', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('No. of Items', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Score', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[0]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->endrow();

    if ($tempstud != $data[0]['trno'] && $tempstudid != $studid) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($student, '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->endrow();
    }


    for ($i = 0; $i < count($data); $i++) {
      if ($quarter != $data[$i]['name']) {
        $quarter1 = $data[$i]['name'];

        if ($quarter1 == $quarter2) {
        } else {
          $quarter3 = $data[$i]['name'];
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
        $quarter2 = $quarter1;

        if ($tempstud != $data[$i]['clientname']) {
          $tempstud1 = $data[$i]['clientname'];

          $tempstud = $tempstud1;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['gccode'], '82', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $data[$i]['topic'], '50', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'], '70', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'], '120', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['gccode'], '82', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $data[$i]['topic'], '50', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'], '70', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'], '120', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->student_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
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

  public function reportstudentquarterplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    // $studid = $params['params']['dataparams']['clientid'];
    $student = $params['params']['dataparams']['ehquarterlookup'];
    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->student_header($params, $data);

    $quarter = "";
    $quarter1 = "";
    $quarter2 = "";
    $quarter3 = "";

    $tempstud = "";
    $tempstud1 = "";
    $tempstud2 = "";
    $tempstud3 = "";
    $tempstudid = "";
    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->begintable('1200');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Topic', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('No. of Items', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Score', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[0]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->endrow();
    // && $tempstudid != $studid
    if ($tempstud != $data[0]['trno']) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($student, '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->endrow();
    }

    for ($i = 0; $i < count($data); $i++) {
      if ($quarter != $data[$i]['name']) {
        if ($tempstud != $data[$i]['clientname']) {
          $tempstud1 = $data[$i]['clientname'];

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['clientname'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();

          $tempstud = $tempstud1;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['gccode'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['topic'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['gccode'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['topic'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['clientname'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->student_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
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

  public function reportallstudentplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->student_header($params, $data);

    $quarter = "";
    $quarter1 = "";
    $quarter2 = "";
    $quarter3 = "";

    $tempstud = "";
    $tempstud1 = "";
    $tempstud2 = "";
    $tempstud3 = "";
    $tempstudid = "";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }
    $str .= $this->reporter->begintable('1200');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Topic', '400', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('No. of Items', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Score', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($data); $i++) {
      if ($quarter != $data[$i]['name']) {

        $quarter1 = $data[$i]['name'];

        if ($quarter1 == $quarter2) {
        } else {
          $quarter3 = $data[$i]['name'];
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
        $quarter2 = $quarter1;

        if ($tempstud != $data[$i]['clientname']) {
          $tempstud1 = $data[$i]['clientname'];

          if ($tempstud1 == $tempstud) {
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' .  $data[$i]['clientname'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->endrow();
          }
          $tempstud = $tempstud1;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['gccode'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['topic'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['gccode'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['topic'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['name'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();


    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->student_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportcomponentplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $code = $params['params']['dataparams']['gccode'];
    $name = $params['params']['dataparams']['ehcomponentlookup'];
    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->student_header($params, $data);

    $quarter = "";
    $quarter1 = "";
    $quarter2 = "";
    $quarter3 = "";

    $tempcomp = "";
    $tempcomp1 = "";
    $tempcomp2 = "";
    $tempcomp3 = "";
    $tempcode = "";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->begintable('1200');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Topic', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Student Name', '400', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('No. of Items', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Score', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[0]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->endrow();

    if ($tempcomp != $data[0]['trno'] && $tempcode != $code) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($name, '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->endrow();
    }

    for ($i = 0; $i < count($data); $i++) {
      if ($quarter != $data[$i]['name']) {
        $quarter1 = $data[$i]['name'];

        if ($quarter1 == $quarter2) {
        } else {
          $quarter3 = $data[$i]['name'];
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $data[$i]['name'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
        $quarter2 = $quarter1;

        if ($tempcomp != $data[$i]['trno'] && $tempcode != $code) {
          $tempcomp1 = $data[$i]['trno'];

          $tempcomp = $tempcomp1;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['topic'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['clientname'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['topic'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['clientname'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $data[$i]['name'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
      }

      // if ($tempstud != $data[$i]['trno'] && $tempstudid != $studid) {
      //   $tempstud1 = $data[$i]['trno'];

      //   if ($studid == $data[$i]['clientid']) {
      //     $str .= $this->reporter->startrow();
      //     $str .= $this->reporter->col($data[$i]['gccode'], '82', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
      //     $str .= $this->reporter->col($data[$i]['topic'], '50', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
      //     $str .= $this->reporter->col($data[$i]['noofitems'], '70', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
      //     $str .= $this->reporter->col($data[$i]['points'], '120', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
      //     $str .= $this->reporter->endrow();
      //   }
      // } else {
      //   $str .= $this->reporter->startrow();
      //   $str .= $this->reporter->col($data[$i]['clientname'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
      //   $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
      //   $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
      //   $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
      //   $str .= $this->reporter->endrow();
      // }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->student_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportcomtopicplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $code = $params['params']['dataparams']['gccode'];
    $name = $params['params']['dataparams']['ehcomponentlookup'];
    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->student_header($params, $data);


    $tempcomp = "";
    $tempcomp1 = "";
    $tempcomp2 = "";
    $tempcomp3 = "";
    $tempcode = "";

    $str .= $this->reporter->begintable('1000');


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Topic', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Student Name', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('No. of Items', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Score', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    if ($tempcomp != $data[0]['trno'] && $tempcode != $code) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $name, '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
      $str .= $this->reporter->endrow();
    }

    for ($i = 0; $i < count($data); $i++) {
      if ($tempcomp != $data[$i]['trno'] && $tempcode != $code) {
        $tempcomp1 = $data[$i]['trno'];

        if ($code == $data[$i]['gccode']) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['topic'], '82', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['clientname'], '50', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'], '70', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'], '120', null, false, $border, 'TRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['clientname'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();

    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->student_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
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

  public function reportallcomponentplotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->student_header($params, $data);

    $quarter = "";
    $quarter1 = "";
    $quarter2 = "";
    $quarter3 = "";

    $tempcomp = "";
    $tempcomp1 = "";
    $tempcomp2 = "";
    $tempcomp3 = "";
    $tempcode = "";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->begintable('1200');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Topic', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Student Name', '400', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('No. of Items', '200', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Score', '300', null, false, $border, 'TLRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($data[0]['name'], '200', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '200', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
    // $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($data); $i++) {
      if ($quarter != $data[$i]['name']) {
        $quarter1 = $data[$i]['name'];

        if ($quarter1 == $quarter2) {
        } else {
          $quarter3 = $data[$i]['name'];
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['name'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
        $quarter2 = $quarter1;

        if ($tempcomp != $data[$i]['gcname']) {
          $tempcomp1 = $data[$i]['gcname'];

          if ($tempcomp1 == $tempcomp) {
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['gcname'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
            $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->endrow();
          }
          $tempcomp = $tempcomp1;
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['topic'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['clientname'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data[$i]['topic'], '300', null, false, $border, 'TLRB', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp' . $data[$i]['clientname'], '400', null, false, $border, 'TRB', 'L', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['noofitems'] . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->col($data[$i]['points'] . '&nbsp&nbsp', '300', null, false, $border, 'TRB', 'R', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();
        }
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[0]['name'], '300', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '300', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();


    if ($this->reporter->linecounter == $page) {
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->student_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page = $page + $count;
    }

    $str .= $this->reporter->endtable();

    $str .= '<br><br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1200');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '220', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '220', null, false, $border, 'B', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_records_query($config)
  {
    $trno = $config['params']['dataid'];
    $studentid = isset($config['params']['dataparams']['clientid']) ? $config['params']['dataparams']['clientid'] : '';
    $quartercode = isset($config['params']['dataparams']['code']) ? $config['params']['dataparams']['code'] : '';
    $gccode = isset($config['params']['dataparams']['gccode']) ? $config['params']['dataparams']['gccode'] : '';
    $filter = " and head.trno=".$trno." ";
    if ($studentid != '') $filter .= " and info.clientid=".$studentid." ";
    if ($quartercode != '') $filter .= " and q.code='".$quartercode."' ";
    if ($gccode != '') $filter .= " and gc.gccode='".$gccode."' ";

    $query = "select client.clientname, g.gccode, g.gcsubcode, c.coursecode, sec.section, gs.quarterid, gcg.scoregrade,
      gcg.totalgrade, gcg.percentgrade, gc.line as gcline, gc.gcname, gssub.line as gssubline, gssub.topic, g.noofitems,
      g.points, g.clientid, gssub.noofitems as subpercent, gc.gcpercent, head.subjectid, sub.subjectcode, sub.subjectname,
      sy.sy, c.coursename, q.name as quartername, info.gender, gssub.trno as gssubtrno, gssub.compid as gssubcompid,
      gc.trno as gctrno, gq.tentativetotal, gq.finaltotal
      from en_glhead as head left join en_glgrades as g on head.trno =g.trno
      left join client on client.clientid=g.clientid
      left join en_studentinfo as info on info.clientid=client.clientid
      left join en_glsubcomponent as gs on gs.trno=g.trno and gs.line=g.gsline
      left join en_gssubcomponent as gssub on gssub.trno=gs.getrno and gs.gccode=gssub.gcsubcode
      left join en_gscomponent as gc on gssub.trno=gc.trno and gc.line=gssub.compid
      left join en_subject as sub on sub.trno=head.subjectid
      left join en_gecomponentgrade as gcg on gcg.trno=head.trno and gcg.clientid=g.clientid and g.gccode=gcg.componentcode and gcg.quarterid=gs.quarterid
      left join en_schoolyear as sy on sy.line=head.syid
      left join en_section as sec on sec.line=head.sectionid
      left join en_course as c on c.line=head.courseid
      left join en_quartersetup as q on q.line=gs.quarterid
      left join en_gequartergrade as gq on gq.trno=head.trno and gq.quarterid=gs.quarterid and gq.schedtrno=gcg.schedtrno and gq.schedline=gcg.schedline and g.clientid=gq.clientid and gcg.isconduct=gq.isconduct
      where head.doc='EH' $filter";
    return $query;
    // $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    // return $result;
  }

  public function count($mqry, $tocount, $filter)
  {
    return $this->coreFunctions->opentable("select count(distinct $tocount) as count from ($mqry) as x where 1=1 $filter");
  }

  public function getcolval($mqry, $field, $filter, $orderby)
  {
    if ($orderby == '') {
      $orderby = " order by gcline,topic,gcsubcode";
    }
    return $this->coreFunctions->opentable("select $field from ($mqry) as x where 1=1 $filter $orderby");
  }

  public function getheader($mqry)
  {
    return $this->coreFunctions->opentable("select distinct gcname,topic,gcsubcode from ($mqry) as x order by gcline,topic,gcsubcode");
  }

  public function getLayoutsize($mqry, $component, $w)
  {
    $counts = 0;
    $tcounts = 0;
    for ($a = 0; $a < count($component); $a++) {
      $topic = $this->getcolval($mqry, 'distinct topic,subpercent', ' and gcname= "' . $component[$a]->gcname . '"', '');
      $counts = 0;
      for ($c = 0; $c < count($topic); $c++) {
        $scope2 = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$a]->gcname . '" and topic= "' . $topic[$c]->topic . '"');
        $counts += ($scope2[0]->count + 1);
      }
      $tcounts += ($counts + 1);
    }
    return ($tcounts * $w) + 400;
  }

  public function header_DEFAULT($config, $layoutsize)
  {
    $str = '';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    // $cline  = $config['params']['dataparams']['coursename'];

    // $yr  = $config['params']['dataparams']['yr'];
    // $sy  = $config['params']['dataparams']['sy'];
    // $subject  = $config['params']['dataparams']['subjectcode'] . ' - ' . $config['params']['dataparams']['subject'];
    // $section  = $config['params']['dataparams']['section'];

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Course: ' . $cline, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('Year: ' . $yr, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('School Year: ' . $sy, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '8px');
      $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Subject Code and Name: ' . $subject, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('Section: ' . $section, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportrecordsplotting($config, $mqry)
  {
    $data = $this->coreFunctions->opentable($mqry);
    $componentcount = $this->count($mqry, 'gcname', '');
    $topiccount = $this->count($mqry, 'topic', '');
    $codecount = $this->count($mqry, 'gcsubcode', '');

    $component = $this->getcolval($mqry, 'distinct gcname, gcpercent', '', '');

    $header = $this->getheader($mqry);
    $count = 38;
    $page = 40;

    $str = '';
    $w = 70;

    $layoutsize = $this->getLayoutsize($mqry, $component, $w);
    $lsize = 1200;
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($data)) {
        return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config, $layoutsize);

    $i = 0;

    $grade = '';

    $code = $this->getcolval($mqry, 'distinct gcsubcode', '', '');

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col($config['params']['dataparams']['quartername'], 1200, null, false, $border, 'TLR', 'L', $font, $fontsize, 'B', '', '', '', 0, 'max-width:200px;word-wrap;break-word;',0,8);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("LEARNER'S NAME", 200, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:200px;max-width:200px;word-wrap:break-word;');
    for ($a = 0; $a < count($component); $a++) {
        $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$a]->gcname . '"');
        $topic = $this->getcolval($mqry, 'distinct topic,subpercent', ' and gcname= "' . $component[$a]->gcname . '"', '');
        $counts = 0;
        for ($c = 0; $c < count($topic); $c++) {
            $scope2 = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$a]->gcname . '" and topic= "' . $topic[$c]->topic . '"');
            $counts += ($scope2[0]->count);
        }
        $cwidth = ($counts + 3) * $w;
        if ($counts != 0) {
            $str .= $this->reporter->col($component[$a]->gcname.' ('.(int)$component[$a]->gcpercent.'%)', $cwidth, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$cwidth.'px;max-width:' . $cwidth . 'px;word-wrap;break-word;');
        }
    }
    $str .= $this->reporter->col('', 100, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap;break-word;');
    $str .= $this->reporter->col('', 100, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap;break-word;');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $subpercent = 0;
    $fieldtotal = 0;
    $subsize = 0;
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", 200, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:200px;max-width:200px;word-wrap:break-word;');
    for ($b = 0; $b < count($component); $b++) {
        // Get the # of Topics
        $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$b]->gcname . '"');
        // Formula based on the # of topics to get the size
        $fieldsize = ($scope[0]->count / $codecount[0]->count) * ($layoutsize - 400);
        // Formula to get the size of a column per # of topics
        if ($scope[0]->count != 0) {
            $fieldwidth = ($fieldsize / ($scope[0]->count));
        } else {
            $fieldwith = $fieldsize;
        }

        $topic = $this->getcolval($mqry, 'distinct topic,subpercent', ' and gcname= "' . $component[$b]->gcname . '"', '');
        for ($c = 0; $c < count($topic); $c++) {
            // Get the # of distinct topics
            $scope2 = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$b]->gcname . '" and topic= "' . $topic[$c]->topic . '"');
            // Formula to get the size of 1 topic divided by the # of code it has + 1 for total
            $fieldsize2 = $fieldsize / ((count($topic) * 2) + 1);
            // Formula for topicsize display less the total
            $topicsize = ($fieldsize2 * $scope2[0]->count);
            $subsize = ($fieldsize2 / ($scope2[0]->count + 1));
            $fsize = $w * $scope2[0]->count;
            $str .= $this->reporter->col('', $fsize, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$fsize.'px;max-width:'.$fsize.'px !important;word-wrap:break-word;');
            $subpercent += $topic[$c]->subpercent;
            $fieldtotal += $topicsize + $subsize;
        }
        if ($b <= count($component) - 1) {
            if (count($topic) > 0) {
                $str .= $this->reporter->col('Total', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                $str .= $this->reporter->col('PS', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                $str .= $this->reporter->col('WS', $w, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                $subpercent = 0;
                $fieldtotal = 0;
            }
        }
    }
    $str .= $this->reporter->col('Initial', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
    $str .= $this->reporter->col('Quarter', 100, null, false, $border, 'LR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('HIGHEST POSSIBLE SCORE', 200, null, false, $border, 'LTBR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:200px;max-width:200px;word-wrap:break-word;');
    for ($h = 0; $h < count($component); $h++) {
        $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$h]->gcname . '"');
        $fieldsize = ($scope[0]->count / $codecount[0]->count) * ($layoutsize - 400);
        $topic = $this->getcolval($mqry, 'distinct topic', ' and gcname= "' . $component[$h]->gcname . '"', '');
        $totnum = 0;
        for ($i = 0; $i < count($topic); $i++) {
            $noofitems = $this->getcolval($mqry, 'distinct noofitems,totalgrade,gcsubcode', ' and gcname= "' . $component[$h]->gcname . '" and topic= "' . $topic[$i]->topic . '"', '');
            for ($j = 0; $j < count($noofitems); $j++) {
                $str .= $this->reporter->col($noofitems[$j]->noofitems, $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                $totnum += $noofitems[$j]->noofitems;
            }
            if ($i == count($topic) - 1) {
                $str .= $this->reporter->col($totnum, $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
                $str .= $this->reporter->col('100', $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
                $str .= $this->reporter->col((int)$component[$h]->gcpercent.'%', $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
            }
        }
    }

    $str .= $this->reporter->col('Grade', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
    $str .= $this->reporter->col('Grade', 100, null, false, $border, 'BLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $pastrec = '';
    $counter = 0;
    $str .= $this->reporter->begintable($layoutsize);


    $client = $this->getcolval($mqry, 'distinct clientname,clientid,gender,tentativetotal,finaltotal', ' and gccode !="CG"', ' order by gender,gcline,topic,gcsubcode');
    for ($g = 0; $g < count($client); $g++) {
        if ($pastrec == '' || $pastrec != $client[$g]->gender) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($client[$g]->gender, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $pastrec = $client[$g]->gender;
            $counter = 0;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($counter + 1, 50, null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:50px;max-width:50px;word-wrap:break-word;');
        $counter++;
        $str .= $this->reporter->col($client[$g]->clientname, 150, null, false, $border, 'BTLR', 'C', $font, $fontsize, '','', '', '', 0, 'min-width:150px;max-width:150px;word-wrap:break-word;');
        $initgrade = 0;
        for ($k = 0; $k < count($component); $k++) {
            $gpercent = 0;
            $scope = $this->count($mqry, 'gcsubcode', ' and gcname= "' . $component[$k]->gcname . '"');
            $fieldsize = ($scope[0]->count / $codecount[0]->count) * ($layoutsize - 400);
            $topic = $this->getcolval($mqry, 'distinct topic', ' and gcname= "' . $component[$k]->gcname . '"', '');
            $totpoints = 0;
            $totnum = 0;
            $psnum = 0;
            $wsnum = 0;
            for ($l = 0; $l < count($topic); $l++) {
                $noofitems = $this->getcolval($mqry, 'distinct noofitems,totalgrade,gcsubcode', ' and gcname= "' . $component[$k]->gcname . '" and topic= "' . $topic[$l]->topic . '"', '');
                for ($j = 0; $j < count($noofitems); $j++) {
                    $totnum += $noofitems[$j]->noofitems;
                }
                $points = $this->getcolval($mqry, 'points,percentgrade', ' and gcname= "' . $component[$k]->gcname . '" and topic= "' . $topic[$l]->topic . '" and clientid=' . $client[$g]->clientid . '', '');

                for ($m = 0; $m < count($points); $m++) {
                    $str .= $this->reporter->col($points[$m]->points, $w, null, false, $border, 'BTLR', 'C', $font, $fontsize, '','', '', '', 0, 'min-width:'.$w.'px;max-width:' . $w . 'px;word-wrap:break-word;');
                    $totpoints += $points[$m]->points;
                }

                $gpercent += $points[0]->percentgrade;
            }
            $psnum = number_format(($totpoints/$totnum)*100,2);
            $wsnum = number_format($psnum * ($component[$k]->gcpercent/100),2);
            $initgrade += $wsnum;
            $str .= $this->reporter->col($totpoints, $w, null, false, $border, 'TBLR', 'C', $font, $fontsize, '', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
            $str .= $this->reporter->col($psnum, $w, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
            $str .= $this->reporter->col($wsnum, $w, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:'.$w.'px;max-width:'.$w.'px;word-wrap:break-word;');
        }
        $qGrade = $this->coreFunctions->datareader("select equivalent as value from en_gradeequivalent where '".$initgrade."' between range1 and range2");
        $str .= $this->reporter->col($initgrade, 100, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
        $str .= $this->reporter->col($qGrade, 100, null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '', '', 0, 'min-width:100px;max-width:100px;word-wrap:break-word;');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}
