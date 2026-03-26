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

class em
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'ehstudentlookup', 'emattendancelookup', 'print'];
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
            '' as received,
            '' as ehstudentlookup,
            '' as emattendancelookup
        "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "select trno,docno,dateid,sy,period,schedday,schedtime,section,instructorcode,instructorname,
                    scheddocno,subjectcode,subjectname,roomcode, bldgcode,coursecode, coursename, 
                    coursename, curriculumcode,curriculumdocno,client,clientname,atdate, type 
                from (select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                            head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                            head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                            course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                            client.client,client.clientname,stock.atdate,attype.type
                            from en_athead as head
                            left join en_atstudents as stock on stock.trno=head.trno
                            left join en_schoolyear as sy on sy.line=head.syid
                            left join en_period as period on period.line=head.periodid
                            left join en_section as sec on sec.line=head.sectionid
                            left join client as i on i.clientid=head.adviserid
                            left join en_subject as subj on subj.trno=head.subjectid
                            left join en_rooms as rm on rm.line=head.roomid
                            left join en_bldg as bldg on bldg.line=rm.bldgid
                            left join en_course as course on course.line=head.courseid
                            left join client on client.clientid=stock.clientid
                            left join en_attendancetype as attype on attype.line=stock.status
                            where head.trno = " . $trno . "
                      union all
                      select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                            head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                            head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                            course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                            client.client,client.clientname,stock.atdate,attype.type
                            from en_glhead as head
                            left join en_glstudents as stock on stock.trno=head.trno
                            left join en_schoolyear as sy on sy.line=head.syid
                            left join en_period as period on period.line=head.periodid
                            left join en_section as sec on sec.line=head.sectionid
                            left join client as i on i.clientid=head.adviserid
                            left join en_subject as subj on subj.trno=head.subjectid
                            left join en_rooms as rm on rm.line=head.roomid
                            left join en_bldg as bldg on bldg.line=rm.bldgid
                            left join en_course as course on course.line=head.courseid
                            left join client on client.clientid=stock.clientid
                            left join en_attendancetype as attype on attype.line=stock.status
                            where head.trno = " . $trno . ") as tb 
                order by clientname";

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

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ATTENDANCE ENTRY', '580', null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('1000');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction # ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('Date : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '220', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('School Year : ', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['sy']) ? $data[0]['sy'] : ''), '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Period : ', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['period']) ? $data[0]['period'] : ''), '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Adviser : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['instructorcode']) ? $data[0]['instructorcode'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('Adviser Name : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['instructorname']) ? $data[0]['instructorname'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Section : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['section']) ? $data[0]['section'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Room Code : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['roomcode']) ? $data[0]['roomcode'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Schedule : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['scheddocno']) ? $data[0]['scheddocno'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('Course : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['coursecode']) ? $data[0]['coursecode'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Course Name', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['coursename']) ? $data[0]['coursename'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sched days', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['schedday']) ? $data[0]['schedday'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Subject : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['subjectcode']) ? $data[0]['subjectcode'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('Subject Description : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['subjectname']) ? $data[0]['subjectname'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Curriculum', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['curriculumcode']) ? $data[0]['curriculumcode'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sched Time', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['schedtime']) ? $data[0]['schedtime'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
    return $str;
  }

  public function reportplotting($params, $data,  $trno)
  {
    
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $student = $params['params']['dataparams']['ehstudentlookup'];
    $attendance = $params['params']['dataparams']['emattendancelookup'];

    $qry = "select 0 as line, 'PRESENT' as type union all select line,type from en_attendancetype order by line";
    $type = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $clientname = '';


    $counter = count($type);

    $str .= $this->reporter->begintable('1000');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Student Name', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');

    for ($i = 0; $i < count($type); $i++) {
      if ($attendance =="") {
        $str .= $this->reporter->col((isset($type[$i]['type']) ? $type[$i]['type'] : ''), '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
      }else {
        if ($attendance == $type[$i]['type']) {
          $str .= $this->reporter->col((isset($type[$i]['type']) ? $type[$i]['type'] : ''), '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
        }
      }
      
    }
    $str .= $this->reporter->endrow();

    if ($student == "") {
        $stud = "";
    }else {
        $studid = $params['params']['dataparams']['clientid'];
        $stud = " and client.clientid = '".$studid."'";
    }

  
    if ($student == "" && $attendance == "") {
      $query = "select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                  head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname,'PRESENT' as type, count(head.trno) as ctype, 0 as cline
                  from en_athead as head
                  left join en_atstudents as stock on stock.trno=head.trno
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join client as i on i.clientid=head.adviserid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_course as course on course.line=head.courseid
                  left join client on client.clientid=stock.clientid
                  where head.trno = ".$trno." and stock.status=0
                group by head.trno,head.docno,head.dateid,sy.sy,period.code,head.schedday,
                  head.schedtime,sec.section,i.client,i.clientname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname
                union all
                select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                  head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname,attype.type, count(attype.type) as ctype, attype.line as cline
                  from en_athead as head
                  left join en_atstudents as stock on stock.trno=head.trno
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join client as i on i.clientid=head.adviserid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_course as course on course.line=head.courseid
                  left join client on client.clientid=stock.clientid
                  left join en_attendancetype as attype on attype.line=stock.status
                  where head.trno = ".$trno." and stock.status<>0
                group by head.trno,head.docno,head.dateid,sy.sy,period.code,head.schedday,
                  head.schedtime,sec.section,i.client,i.clientname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname,attype.type, attype.line order by clientname, cline";
    
    }else{
      if ($attendance == 'PRESENT') {
        $query = "select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                    head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                    head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                    course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                    client.clientid,client.client,client.clientname,'PRESENT' as type, count(head.trno) as ctype, 0 as cline
                    from en_athead as head
                    left join en_atstudents as stock on stock.trno=head.trno
                    left join en_schoolyear as sy on sy.line=head.syid
                    left join en_period as period on period.line=head.periodid
                    left join en_section as sec on sec.line=head.sectionid
                    left join client as i on i.clientid=head.adviserid
                    left join en_subject as subj on subj.trno=head.subjectid
                    left join en_rooms as rm on rm.line=head.roomid
                    left join en_bldg as bldg on bldg.line=rm.bldgid
                    left join en_course as course on course.line=head.courseid
                    left join client on client.clientid=stock.clientid
                    where head.trno = ".$trno." $stud and stock.status=0
                  group by head.trno,head.docno,head.dateid,sy.sy,period.code,head.schedday,
                    head.schedtime,sec.section,i.client,i.clientname,
                    head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                    course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                    client.clientid,client.client,client.clientname order by clientname";
      }else {
        if ($attendance == "") {
          $attd = "";
        }else {
          $attd = " and attype.type = '".$attendance."' ";

        }
        if ($student <> "" && $attendance == "") {
          $query = "select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                  head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname,'PRESENT' as type, count(head.trno) as ctype, 0 as cline
                  from en_athead as head
                  left join en_atstudents as stock on stock.trno=head.trno
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join client as i on i.clientid=head.adviserid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_course as course on course.line=head.courseid
                  left join client on client.clientid=stock.clientid
                  where head.trno = ".$trno." $stud and stock.status=0
                group by head.trno,head.docno,head.dateid,sy.sy,period.code,head.schedday,
                  head.schedtime,sec.section,i.client,i.clientname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname
                union all
                select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                  head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname,attype.type, count(attype.type) as ctype, attype.line as cline
                  from en_athead as head
                  left join en_atstudents as stock on stock.trno=head.trno
                  left join en_schoolyear as sy on sy.line=head.syid
                  left join en_period as period on period.line=head.periodid
                  left join en_section as sec on sec.line=head.sectionid
                  left join client as i on i.clientid=head.adviserid
                  left join en_subject as subj on subj.trno=head.subjectid
                  left join en_rooms as rm on rm.line=head.roomid
                  left join en_bldg as bldg on bldg.line=rm.bldgid
                  left join en_course as course on course.line=head.courseid
                  left join client on client.clientid=stock.clientid
                  left join en_attendancetype as attype on attype.line=stock.status
                  where head.trno = ".$trno." $stud and stock.status<>0
                group by head.trno,head.docno,head.dateid,sy.sy,period.code,head.schedday,
                  head.schedtime,sec.section,i.client,i.clientname,
                  head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                  course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                  client.clientid,client.client,client.clientname,attype.type, attype.line order by clientname, cline";
    
        }else {
          $query ="select head.trno,head.docno,head.dateid,sy.sy,period.code as period,head.schedday,
                    head.schedtime,sec.section,i.client as instructorcode,i.clientname as instructorname,
                    head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                    course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                    client.clientid,client.client,client.clientname,attype.type, count(attype.type) as ctype, attype.line as cline
                    from en_athead as head
                    left join en_atstudents as stock on stock.trno=head.trno
                    left join en_schoolyear as sy on sy.line=head.syid
                    left join en_period as period on period.line=head.periodid
                    left join en_section as sec on sec.line=head.sectionid
                    left join client as i on i.clientid=head.adviserid
                    left join en_subject as subj on subj.trno=head.subjectid
                    left join en_rooms as rm on rm.line=head.roomid
                    left join en_bldg as bldg on bldg.line=rm.bldgid
                    left join en_course as course on course.line=head.courseid
                    left join client on client.clientid=stock.clientid
                    left join en_attendancetype as attype on attype.line=stock.status
                    where head.trno = ".$trno." $attd $stud and stock.status<>0
                  group by head.trno,head.docno,head.dateid,sy.sy,period.code,head.schedday,
                    head.schedtime,sec.section,i.client,i.clientname,
                    head.scheddocno,subj.subjectcode,subj.subjectname,rm.roomcode,bldg.bldgcode,
                    course.coursecode,course.coursename,head.curriculumcode,head.curriculumdocno,
                    client.clientid,client.client,client.clientname,attype.type, attype.line order by clientname, cline";
        }
        
      }
      
    }

    $result1 = $this->coreFunctions->opentable($query);

    $present = 0;
    $late = 0;
    $absent = 0;
    $disconnected = 0;
    $drop = 0;
    $subtotal = 0;
    $name = '';
    $name2 = '';

    $counter = 1;
    $subtotal = [];

    foreach ($result1 as $k => $detail) {

      if($name != ''){
        if ($detail->clientname != $name) {
      $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('&nbsp&nbsp&nbsp'.$name, '200', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');

         

      //test
          if ($attendance == "") {  
            foreach ($subtotal as $key => $total) {
                $str .= $this->reporter->col($total, '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
            }
          }else {
            for ($i=0; $i < count($detail); $i++) { 
              if ($attendance == $detail->type) {
                $str .= $this->reporter->col($subtotal[$attendance], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
              }
            }
          }

          //end test

          $str .= $this->reporter->endrow();
          $subtotal = [];
        }
      
      }


      foreach ($type as $key => $countattend) {
        $ctype = 0;
        if ($countattend['type'] == $detail->type) {
          if(isset($subtotal[$countattend['type']])){
            $ctype = $subtotal[$countattend['type']] + $detail->ctype;
          }else{
            $ctype = $detail->ctype;
          }
          
          $subtotal[$countattend['type']] = $ctype;
          
        } else {
          if(isset($subtotal[$countattend['type']])){
            $ctype = $subtotal[$countattend['type']] + 0;
          }else{
            $ctype = 0;
          }
          $subtotal[$countattend['type']] = $ctype;
        }
      }
      
      if(count($result1) == $counter){
        $str .= $this->reporter->startrow();
  
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp'.$detail->clientname, '200', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        
        if ($attendance == "") {  
          foreach ($subtotal as $key => $total) {
              $str .= $this->reporter->col($total, '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
          }
        }else {
          for ($i=0; $i < count($detail); $i++) { 
            if ($attendance == $detail->type) {
              $str .= $this->reporter->col($subtotal[$attendance], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
            }
          }
        }


      //   foreach ($subtotal as $key => $total) {
      //     $str .= $this->reporter->col($total, '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
      //  }
        $str .= $this->reporter->endrow();


      }


$name = $detail->clientname;
$counter = $counter + 1;

    }


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
}
