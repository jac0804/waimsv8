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

class eg
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

    public function createreportfilter(){
        $fields = ['radioprint','prepared','approved','received','print'];
        $col1 = $this->fieldClass->create($fields);
        return array('col1'=>$col1);
    }

    public function reportparamsdata($config){
        return $this->coreFunctions->opentable(
            "select
            'default' as print,
            '' as prepared,
            '' as approved,
            '' as received
        ");
    }

public function report_default_query($trno){
    
    $query = "select head.trno, head.docno, head.clientid, head.dateid, client.clientname, client.client, 
                head.levelid, level.levels, head.syid, en_schoolyear.sy, head.courseid, course.coursename, 
                course.coursecode, head.curriculumdocno, curriculum.curriculumname, curriculum.curriculumcode,
                stock.curriculumcode, stock.yearnum, stock.terms, s.subjectcode, s.subjectname, stock.units, 
                stock.pre1, stock.pre2, stock.pre3,stock.pre4, stock.pre5, stock.lecture, stock.laboratory, 
                stock.coreq, stock.grade, stock.equivalent, stock.subjectid, stock.semid, sem.term, head.yr
              from en_sgshead as head
              left join client on client.clientid = head.clientid
              left join en_levels as level on level.line = head.levelid
              left join en_schoolyear on en_schoolyear.line = head.syid
              left join en_course as course on course.line = head.courseid
              left join en_cchead as curriculum on curriculum.docno = head.curriculumdocno
              left join en_sgssubject as stock on stock.trno = head.trno
              left join en_subject as s on s.trno = stock.subjectid
              left join en_term as sem on sem.line=stock.semid
              where head.trno = ".$trno."
              union all
              select head.trno, head.docno, head.clientid, head.dateid, client.clientname, client.client, 
                head.levelid, level.levels, head.syid, en_schoolyear.sy, head.courseid, course.coursename, 
                course.coursecode, head.curriculumdocno, curriculum.curriculumname, curriculum.curriculumcode,
                stock.curriculumcode, stock.yearnum, stock.terms, s.subjectcode, s.subjectname, stock.units, 
                stock.pre1, stock.pre2, stock.pre3, stock.pre4, stock.pre5, stock.lecture, stock.laboratory, 
                stock.coreq, stock.grade, stock.equivalent, stock.subjectid, stock.semid, sem.term, head.yr
              from en_glhead as head
              left join client on client.clientid = head.clientid
              left join en_levels as level on level.line = head.levelid
              left join en_schoolyear on en_schoolyear.line = head.syid
              left join en_course as course on course.line = head.courseid
              left join en_cchead as curriculum on curriculum.docno = head.curriculumdocno
              left join en_sgssubject as stock on stock.trno = head.trno
              left join en_subject as s on s.trno = stock.subjectid
              left join en_term as sem on sem.line=stock.semid
              where head.trno = ".$trno."";
        
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
}//end fn

public function default_header($params, $data) {
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
  $str .= $this->reporter->col('STUDENT GRADE ENTRY','580',null,false, $border,'','C', $font,'18','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  
  $str .= '<br>';

  $str .= $this->reporter->begintable('1000');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction # ', '40', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('Date : ', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '220', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Levels : ', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['levels']) ? $data[0]['levels'] : ''), '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Student Code : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['client']) ? $data[0]['client'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('Student Name : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('School Year : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['sy']) ? $data[0]['sy'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Course : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['coursecode']) ? $data[0]['coursecode'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col('Course Name : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['coursename']) ? $data[0]['coursename'] : ''), '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $str .= $this->reporter->begintable('1000');
  // $str .= $this->reporter->endtable();
  return $str;
}

public function reportplotting($params,$data){
  $companyid = $params['params']['companyid'];
  $decimal = $this->companysetup->getdecimal('currency',$params['params']);

  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = '';
  $count=35;
  $page=35;
  $font =  "Century Gothic";
  $fontsize = "11";
  $border = "1px solid ";

  $str .= $this->reporter->beginreport();
  $str .= $this->default_header($params, $data);

  $year = "";
  $year1 = "";
  $year2 = "";
  $year3 = "";

  $tempsem = "";
  $tempsem1 = "";
  $tempsem2 = "";
  $tempsem3 = "";
  // $tempstudid = "";

  $str .= $this->reporter->begintable('1000');

  
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Subject Code', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
  $str .= $this->reporter->col('Subject Name', '350', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
  $str .= $this->reporter->col('Units', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
  $str .= $this->reporter->col('Lecture', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
  $str .= $this->reporter->col('Laboratory', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
  $str .= $this->reporter->col('Grade', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
  $str .= $this->reporter->endrow();


  // for ($i = 0; $i < count($data); $i++) {
     
  //     $str .= $this->reporter->startrow();
  //     $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectcode'], '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
  //     $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectname'], '350', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
  //     $str .= $this->reporter->col($data[$i]['units'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
  //     $str .= $this->reporter->col($data[$i]['lecture'], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
  //     $str .= $this->reporter->col($data[$i]['laboratory'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
  //     $str .= $this->reporter->col($data[$i]['grade'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
  //     $str .= $this->reporter->endrow();
  // }

  for ($i = 0; $i < count($data); $i++) {
    if ($year != "") {
      $year1 = $data[$i]['yr'];
      
      if ($year1 ==$year2){
        
      }else{
        $year3 = $data[$i]['yr'];
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['yr'], '100', null, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
      }
      $year2 = $year1;

      if ($tempsem != "") {
        $tempsem1 = $data[$i]['term'];
        
        if ($tempsem1 == $tempsem) {
        } else {
          $tempstud3 = $data[$i]['term'];
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['term'], '100', null, false, $border, 'TL', 'L', $font, '12', 'B', '', '');
          $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'TR', 'C', $font, '12', '', '', '');
          $str .= $this->reporter->endrow();

        }
        $tempsem = $tempsem1;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectcode'], '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectname'], '350', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['units'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['lecture'], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['laboratory'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['grade'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectcode'], '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectname'], '350', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['units'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['lecture'], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['laboratory'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['grade'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }
    }else{
      
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectcode'], '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp'.$data[$i]['subjectname'], '350', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['units'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['lecture'], '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['laboratory'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['grade'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

    }

      
  }

  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '', '');
  $str .= $this->reporter->col('', '350', null, false, $border, 'T', 'C', $font, '12', '', '', '');
  $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
  $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
  $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
  $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', '', '', '');
  $str .= $this->reporter->endrow();

    if($this->reporter->linecounter==$page){
     $str .= $this->reporter->endtable();
     $str .= $this->reporter->page_break();
     $str .= $this->default_header($params, $data);
     $str .= $this->reporter->endrow();
     $str .= $this->reporter->printline();
     $page=$page + $count;
   }

  $str .= $this->reporter->endtable();

  $str .= '<br><br>';
  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Prepared By : ','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Approved By :','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Received By :','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($params['params']['dataparams']['prepared'],'100',null,false, $border,'B','C', $font,'12','B','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col($params['params']['dataparams']['approved'],'100',null,false, $border,'B','C', $font,'12','B','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col($params['params']['dataparams']['received'],'100',null,false, $border,'B','C', $font,'12','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->endtable();
  $str .= $this->reporter->endreport();

  return $str;
}



}
