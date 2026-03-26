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

class es
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
    $query = "select head.doc,head.docno,head.dateid,head.yr,p.code as period,client.client,client.clientname,c.coursecode,c.coursename,sy.sy,sem.term as semester,sec.section,
    stock.units,stock.laboratory,stock.lecture,stock.hours,stock.schedday,stock.schedstarttime,stock.schedendtime,stock.minslot,stock.maxslot,
    s.subjectcode,s.subjectname,i.client as teachercode,i.clientname as teachername,r.roomcode,r.roomname,b.bldgcode,b.bldgname,head.curriculumname,head.curriculumcode,head.curriculumdocno
    from en_schead as head left join en_scsubject as stock on stock.trno=head.trno left join client on client.clientid=head.adviserid
    left join en_period as p on p.line=head.periodid left join en_course as c on c.line=head.courseid left join en_schoolyear as sy on sy.line=head.syid
    left join en_term as sem on sem.line=head.semid left join en_section as sec on sec.line=head.sectionid
    left join client as i on i.clientid=stock.instructorid left join en_subject as s on s.trno=stock.subjectid
    left join en_bldg as b on b.line=stock.bldgid left join en_rooms as r on r.line=stock.roomid where head.doc='ES'and head.trno=".$trno."
    union all
    select head.doc,head.docno,head.dateid,head.yr,p.code as period,client.client,client.clientname,c.coursecode,c.coursename,sy.sy,sem.term as semester,sec.section,
    stock.units,stock.laboratory,stock.lecture,stock.hours,stock.schedday,stock.schedstarttime,stock.schedendtime,stock.minslot,stock.maxslot,
    s.subjectcode,s.subjectname,i.client as teachercode,i.clientname as teachername,r.roomcode,r.roomname,b.bldgcode,b.bldgname,head.curriculumname,head.curriculumcode,head.curriculumdocno
    from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno left join client on client.clientid=head.adviserid
    left join en_period as p on p.line=head.periodid left join en_course as c on c.line=head.courseid left join en_schoolyear as sy on sy.line=head.syid
    left join en_term as sem on sem.line=head.semid left join en_section as sec on sec.line=head.sectionid
    left join client as i on i.clientid=stock.instructorid left join en_subject as s on s.trno=stock.subjectid
    left join en_bldg as b on b.line=stock.bldgid left join en_rooms as r on r.line=stock.roomid where head.doc='ES' and head.trno=".$trno;

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

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  $str .= $this->reporter->col('Class Schedule','580',null,false, $border,'','C', $font,'18','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  
  $str .= $this->reporter->col('SCHOOL YEAR : ','110',null,false, $border,'','R', $font,'12','B','','');
  $str .= $this->reporter->col((isset($data[0]['sy'])? $data[0]['sy']:''),'0',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('PERIOD : ','0',null,false, $border,'','R', $font,'12','B','','');
  $str .= $this->reporter->col((isset($data[0]['period'])? $data[0]['period']:''),'100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br><br>';


  $str .= $this->reporter->begintable('800');

  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('DOCUMENT NO. : ','110',null,false, $border,'','L', $font,'12','B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'410',null,false, $border,'B','L', $font,'12','','30px','4px');
  $str .= $this->reporter->col('DATE : ','70',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'140',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('ADVISER : ','90',null,false, $border,'','L', $font,'12','B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname'].' ('. $data[0]['client'].')':''),'420',null,false, $border,'B','L', $font,'12','','30px','4px');
  $str .= $this->reporter->col('GRADE/YR : ','70',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col((isset($data[0]['yr'])? $data[0]['yr']:''),'140',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();


  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('COURSE : ','90',null,false, $border,'','L', $font,'12','B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['coursename'])? $data[0]['coursename'].' ('.$data[0]['coursecode'].')':''),'420',null,false, $border,'B','L', $font,'12','','30px','4px');
  $str .= $this->reporter->col('SEM : ','70',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col((isset($data[0]['semester'])? $data[0]['semester']:''),'140',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  
  
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('CURRICULUM : ','90',null,false, $border,'','L', $font,'12','B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['curriculumdocno'])? $data[0]['curriculumname'].' ('.$data[0]['curriculumdocno'].')':''),'420',null,false, $border,'B','L', $font,'12','','30px','4px');
  $str .= $this->reporter->col('SECTION : ','70',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col((isset($data[0]['section'])? $data[0]['section']:''),'140',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow(null,null,false, $border,'','R', $font, $fontsize,'','','4px');
  $str .= $this->reporter->pagenumber('Page');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('TIME','80',null,false, $border,'TLRB','C', $font,'12','B','30px','8px');
  $str .= $this->reporter->col('DAY','80',null,false, $border,'TLRB','C', $font,'12','B','30px','8px');
  $str .= $this->reporter->col('SUBJECT','250',null,false, $border,'TLRB','C', $font,'12','B','30px','8px');
  $str .= $this->reporter->col('INSTRUCTOR','250',null,false, $border,'TLRB','C', $font,'12','B','30px','8px');
  $str .= $this->reporter->col('ROOM','100',null,false, $border,'TLRB','C', $font,'12','B','30px','8px');

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



  $totalext=0;
  for($i=0;$i<count($data);$i++){
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();

    $str .= $this->reporter->col(date('h:i A', strtotime($data[$i]['schedstarttime'])).' - '.date('h:i A', strtotime($data[$i]['schedendtime'])),'120',null,false, $border,'TLRB','C', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['schedday'],'80',null,false, $border,'TLRB','C', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['subjectname'],'200',null,false, $border,'TLRB','L', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['teachername'],'200',null,false, $border,'TLRB','L', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['bldgname'].' - '.$data[$i]['roomname'],'120',null,false, $border,'TLRB','c', $font, $fontsize,'','','2px');



    if($this->reporter->linecounter==$page){
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->default_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page=$page + $count;
    }
  }

  $str .= $this->reporter->endtable();

  $str .= '<br><br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Prepared By : ','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Approved By :','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Received By :','100',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $str .= $this->reporter->begintable('800');
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
