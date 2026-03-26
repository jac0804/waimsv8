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

class ei
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'eiassessment', 'eischedule', 'eiwithbooks', 'print'];
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
        'withassess' as eiassessment,
        'withsched' as eischedule,
        'withbooks' as eiwithbooks
    "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "select head.trno,client.client,client.clientname,head.docno,head.dateid,head.yr,
                head.curriculumcode,head.curriculumdocno,course.coursecode,course.coursename,
                sy.code,period.code,sec.section,head.rem,head.modeofpayment,head.contra,sem.term,
                dept.clientname as department,levels.levels,chead.docno as curriculum,head.disc,
                stock.units,stock.lecture,stock.laboratory,stock.hours,subj.subjectcode,subj.subjectname,
                sy.sy,chead.curriculumcode,chead.curriculumname,
                stock.schedstarttime,stock.schedendtime,prof.clientid as instructorid,
                prof.clientname as instructorname,stock.schedday,stock.roomid,stock.bldgid
            from en_sohead as head 
            left join en_sosubject as stock on stock.trno=head.trno 
            left join client on client.client=head.client 
            left join en_schoolyear as sy on sy.line=head.syid
            left join en_course as course on course.line=head.courseid 
            left join en_term as sem on sem.line=head.semid 
            left join client as dept on dept.clientid=head.deptid
            left join en_levels  as levels on levels.line=head.levelid 
            left join en_glhead as chead on chead.trno=head.curriculumtrno
            left join en_period as period on period.line=head.periodid 
            left join en_section as sec on sec.line=head.sectionid 
            left join en_subject as subj on subj.trno=stock.subjectid
            left join client as prof on stock.instructorid= prof.clientid
            where head.doc='EI' and head.trno= " . $trno . "
            union all
            select head.trno,client.client,client.clientname,head.docno,head.dateid,head.yr,
                head.curriculumcode,head.curriculumdocno,course.coursecode,course.coursename,
                sy.code,period.code,sec.section,head.rem,head.modeofpayment,head.contra,sem.term,
                dept.clientname as department,levels.levels,chead.docno as curriculum,head.disc,
                stock.units,stock.lecture,stock.laboratory,stock.hours,subj.subjectcode,subj.subjectname,
                sy.sy,chead.curriculumcode,chead.curriculumname,
                stock.schedstarttime,stock.schedendtime,prof.clientid as instructorid,
                prof.clientname as instructorname,stock.schedday,stock.roomid,stock.bldgid
            from en_glhead as head 
            left join en_glsubject as stock on stock.trno=head.trno 
            left join client on client.clientid=head.clientid 
            left join en_schoolyear as sy on sy.line=head.syid
            left join en_course as course on course.line=head.courseid 
            left join en_term as sem on sem.line=head.semid 
            left join client as dept on dept.clientid=head.deptid
            left join en_levels  as levels on levels.line=head.levelid 
            left join en_glhead as chead on chead.trno=head.curriculumtrno
            left join en_period as period on period.line=head.periodid 
            left join en_section as sec on sec.line=head.sectionid 
            left join en_subject as subj on subj.trno=stock.subjectid
            left join client as prof on stock.instructorid= prof.clientid
            where head.doc='EI' and head.trno=" . $trno . "";


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function assessment_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "select grp as feescode,feestype,round(sum(amt),2) as amt
    from (select grp,feestype,amt
          from (select grp,feestype,sum(amt) as amt
                from (select '3' as grp,'Other Fees' as feestype,isamt as amt
                      from en_sootherfees as a
                where a.trno=" . $trno . "
                union all
                select '3' as grp,'Other Fees' as feestype,isamt
                from en_glotherfees as a
                where a.trno=" . $trno . ") as s
          group by s.feestype,s.grp
          union all
          select s.grp,s.feestype,sum(s.amt) as amt
          from (select '0' as grp,'Books' as feestype,a.amt as amt
                from en_sohead as h
                left join en_glbooks as a on h.curriculumtrno=a.trno
                where h.trno=" . $trno . "
                union all
                select '0' as grp,'Books' as feestype,a.amt as amt
                from en_glhead as h
                left join en_glbooks as a on h.curriculumtrno=a.trno
                where h.trno=" . $trno . ") as s
          group by s.feestype,s.grp
          union all
          select s.grp,s.feestype,sum(s.amt) as amt
          from (select '0' as grp,f.feestype,a.amt as amt
                from en_sosummary as a
                left join en_fees as f on f.line=a.feesid
                where a.trno=" . $trno . "
                union all
                select '0' as grp,f.feestype,a.amt
                from en_glsummary as a
                left join en_fees as f on f.line=a.feesid
                where a.trno=" . $trno . ") as s
          where s.feestype not in ('OTHERS','LAB')
          group by s.feestype,s.grp
          union all
          select s.grp,feestype,sum(s.amt)*-1
          from (select '2' as grp,'Less Credentials' as feestype,camt as amt
                from en_socredentials as a
                where a.trno=" . $trno . "
                union all
                select '2' as grp,'Less Credentials' as feestype,camt
                from en_glcredentials as a
                where a.trno=" . $trno . ") as s
          group by s.feestype,s.grp
          union all
          select s.grp,feestype,sum(s.amt)*-1
          from (select '3' as grp,'Less Discount' as feestype,disc as amt
                from en_sohead as a
                where a.trno=" . $trno . "
                union all
                select '3' as grp,'Less Discount' as feestype,disc
                from en_glhead as a
                where a.trno=" . $trno . ") as s
          group by s.feestype,s.grp
          union all
          select 1 as grp,'Add Interest:' as feestype,sum(amt)*(10.00/100)
          from (select s.grp,s.feestype,s.amt
                from (select 3 as grp,'Total Fees' as feestype,isamt as amt
                      from en_sootherfees as a
                      where a.trno=" . $trno . "
                      union all
                      select 3 as grp,'Total Fees' as feestype,isamt
                      from en_glotherfees as a
                      where a.trno=" . $trno . ") as s
                union all
                select s.grp,feestype,sum(s.amt)*-1 as amt
                from (select 2 as grp,'Less Credentials' as feestype,camt as amt
                      from en_socredentials as a
                      where a.trno=" . $trno . "
                      union all
                      select 2 as grp,'Less Credentials' as feestype,camt
                      from en_glcredentials as a
                      where a.trno=" . $trno . ") as s
                group by s.feestype,s.grp
                union all
                select s.grp,s.feestype,s.amt
                from (select 0 as grp,f.feestype,a.amt as amt
                      from en_sosummary as a
                      left join en_fees as f on f.line=a.feesid
                      where a.trno=" . $trno . "
                      union all
                      select 0 as grp,f.feestype,a.amt
                      from en_glsummary as a
                      left join en_fees as f on f.line=a.feesid
                      where a.trno=" . $trno . ") as s
                where s.feestype not in ('OTHERS','LAB')) as x
                group by x.feestype,x.grp
                union all
                select 4 as grp,'Total Balance:' as feestype,sum(amt)
                from (select s.grp,s.feestype,s.amt+(s.amt*(10.00/100)) as amt
                      from (select 3 as grp,'Total Fees' as feestype,isamt as amt
                            from en_sootherfees as a
                            where a.trno=" . $trno . "
                      union all
                      select 3 as grp,'Total Fees' as feestype,isamt
                      from en_glotherfees as a
                      where a.trno=" . $trno . ") as s
                union all
                select s.grp,feestype,(sum(s.amt)+(sum(s.amt)*(10.00/100)))*-1 as amt
                from (select 2 as grp,'Less Credentials' as feestype,camt as amt
                      from en_socredentials as a
                      where a.trno=" . $trno . "
                union all
                select 2 as grp,'Less Credentials' as feestype,camt
                from en_glcredentials as a
                where a.trno=" . $trno . ") as s
          group by s.feestype,s.grp
          union all
          select s.grp,s.feestype,s.amt+((s.amt)*(10.00/100)) as amt
          from (select 0 as grp,f.feestype,a.amt as amt
                from en_sosummary as a
                left join en_fees as f on f.line=a.feesid
                where a.trno=" . $trno . "
                union all
                select 0 as grp,f.feestype,a.amt
                from en_glsummary as a
                left join en_fees as f on f.line=a.feesid
                where a.trno=" . $trno . ") as s
          where s.feestype not in ('OTHERS','LAB')
          union all
          select s.grp,feestype,sum(s.amt)*-1
          from (select '4' as grp,'Total Balance' as feestype,disc as amt
                from en_sohead as a
                where a.trno=" . $trno . "
                union all
                select '4' as grp,'Total Balance' as feestype,disc
                from en_glhead as a where a.trno=" . $trno . ") as s
          group by s.feestype,s.grp
          union all
          select s.grp,s.feestype,sum(s.amt) as amt
          from (select '4' as grp,'Total Balance' as feestype,a.amt as amt
          from en_sohead as h
          left join en_glbooks as a on h.curriculumtrno=a.trno
          where h.trno=" . $trno . "
          union all
          select '4' as grp,'Total Balance' as feestype,a.amt as amt
          from en_glhead as h
          left join en_glbooks as a on h.curriculumtrno=a.trno
          where h.trno=" . $trno . " ) as s
    group by s.feestype,s.grp ) as x
    group by x.feestype,x.grp) as y ) as z
    group by feestype,grp order by grp";


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  private function books_table($books, $font, $fontsize, $border)
  {
    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Books', '200', null, false, $border, '', 'L', $font, '14', 'B', '', '8px');
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '450', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '150', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $totalamt = 0;
    for ($j = 0; $j < count($books); $j++) {
      // col($txt = '', $w = null, $h = null, $bg = false, $b = false, $b_ = '', $al = '', $f = '', $fs = '', $fw = '', $fc = '', $pad = '', $m = '', $len = 0, $addedstyle = '', $jsamount = 0, $colspan = 0, $bc = null)
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($books[$j]['code'], '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp' . $books[$j]['description'], '450', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($books[$j]['qty'], '150', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($books[$j]['amount'], 2) . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalamt += $books[$j]['amount'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '450', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Amount:', '150', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2) . '&nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function withbooks_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "select item.barcode as code,item.itemname as description,books.isqty as qty,books.isamt as amount
    from en_sohead as head 
    left join en_glbooks as books on books.trno=head.curriculumtrno
    left join item on item.itemid=books.itemid
    where head.trno=" . $trno . "

    UNION ALL

    select item.barcode as code,item.itemname as description,books.isqty as qty,books.isamt as amount
    from en_glhead as head 
    left join en_glbooks as books on books.trno=head.curriculumtrno 
    left join item on item.itemid=books.itemid
    where head.trno=" . $trno . "";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('GRADES SCHOOL ASSESSMENT', '580', null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';

    $str .= $this->reporter->begintable('1000');

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Enrollment No. : ', '40', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    // $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    // $str .= $this->reporter->col('Enrollment Date : ', '120', null, false, $border, '', 'L', $font, '12', '', '', '');
    // $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '220', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('School Year : ', '50', null, false, $border, '', 'L', $font, '12', '', '', '');
    // $str .= $this->reporter->col((isset($data[0]['sy']) ? $data[0]['sy'] : ''), '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Student No. : ', '100', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    // $str .= $this->reporter->col((isset($data[0]['client']) ? $data[0]['client'] : ''), '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    // $str .= $this->reporter->col('Student Type : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('Course : ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    // $str .= $this->reporter->col((isset($data[0]['coursename']) ? $data[0]['coursename'] : ''), '100', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('Student Name : ', '100', null, false, $border, '', 'L', $font, '12', '', '30px', '4px');
    // $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '100', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->col('Major ', '100', null, false, $border, '', 'L', $font, '12', '', '', '');
    // $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    // $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction # ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date : ', '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? date('Y-m-d', strtotime($data[0]['dateid'])) : ''), '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Period : ', '165', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['code']) ? $data[0]['code'] : ''), '145', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('School Year : ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['sy']) ? $data[0]['sy'] : ''), '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department : ', '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['department']) ? $data[0]['department'] : ''), '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Level : ', '165', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['levels']) ? $data[0]['levels'] : ''), '145', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Student # : ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['client']) ? $data[0]['client'] : ''), '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Student Name : ', '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MOP : ', '165', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['modeofpayment']) ? $data[0]['modeofpayment'] : ''), '145', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Course : ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['coursecode']) ? $data[0]['coursecode'] : ''), '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Course Name : ', '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['coursename']) ? $data[0]['coursename'] : ''), '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Account : ', '165', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['contra']) ? $data[0]['contra'] : ''), '145', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Curriculum Document : ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['curriculum']) ? $data[0]['curriculum'] : ''), '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Curriculum : ', '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['curriculumcode']) ? $data[0]['curriculumcode'] : ''), '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Curriculum Name : ', '165', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['curriculumname']) ? $data[0]['curriculumname'] : ''), '145', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grade/Year : ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['yr']) ? $data[0]['yr'] : ''), '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Semester : ', '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['term']) ? $data[0]['term'] : ''), '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount : ', '165', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['disc']) ? $data[0]['disc'] : ''), '145', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Section : ', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['section']) ? $data[0]['section'] : ''), '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes: ', '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '490', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 3);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');

    // $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportplotting($params, $data, $books)
  {
    // $companyid = $params['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $total = '';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Subject Code', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Subject Name', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Units', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($data); $i++) {

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['subjectcode'], '200', null, false, $border, 'TLR', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['subjectname'], '200', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['hours'], '200', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $total = $total + $data[$i]['hours'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'LTB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Units : &nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // ________________________________________________________________________________
    $str .= $this->books_table($books, $font, $fontsize, $border);

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
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function withschedplotting($params, $data, $books)
  {
    // $companyid = $params['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];
    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Subject Code', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Subject Name', '250', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Instructor Name', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Bldg Code - Room Code', '150', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Schedule', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Period', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $data[$i]['subjectcode'], '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $data[$i]['subjectname'], '250', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $data[$i]['instructorname'], '200', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['roomid'] . ' - ' . $data[$i]['bldgid'], '150', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['schedday'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['code'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    // ________________________________________________________________________________
    $str .= $this->books_table($books, $font, $fontsize, $border);

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
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function booksonly_plotting($params, $data, $books)
  {
    // $companyid = $params['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);
    $str .= $this->books_table($books, $font, $fontsize, $border);

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
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function assessment_report_plotting($params, $data, $assess, $books)
  {
    // $companyid = $params['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    if ($params['params']['dataparams']['eiwithbooks'] != 'assessmentonly') {
      $str .= $this->reporter->begintable('1000');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Subject Code', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Subject Name', '250', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Instructor Name', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Bldg Code - Room Code', '150', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Schedule', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Period', '100', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();

      for ($i = 0; $i < count($data); $i++) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $data[$i]['subjectcode'], '100', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $data[$i]['subjectname'], '250', null, false, $border, 'TRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('&nbsp&nbsp&nbsp' . $data[$i]['instructorname'], '200', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['roomid'] . ' - ' . $data[$i]['bldgid'], '150', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['schedday'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data[$i]['code'], '100', null, false, $border, 'TRB', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->endtable();

      // ________________________________________________________________________________
      $str .= $this->books_table($books, $font, $fontsize, $border);
    }

    // ________________________________________________________________________________
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Assessment', '150', null, false, $border, '', 'L', $font, '14', 'B', '', '8px');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    for ($k = 0; $k < count($assess); $k++) {
      $str .= $this->reporter->startrow();
      if ($assess[$k]['feestype'] == 'Total Balance:') {
        $str .= $this->reporter->col('&nbsp&nbsp' . $assess[$k]['feestype'], '150', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($assess[$k]['amt'], 2) . '&nbsp&nbsp', '150', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->col('&nbsp&nbsp' . $assess[$k]['feestype'], '150', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($assess[$k]['amt'], 2) . '&nbsp&nbsp', '150', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }
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
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function assessment2_report_plotting($params, $data, $assess, $books)
  {
    // $companyid = $params['params']['companyid'];
    // $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $total = '';

    $str .= $this->reporter->begintable('1000');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Subject Code', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Subject Name', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Units', '200', null, false, $border, 'TLRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();


    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['subjectcode'], '200', null, false, $border, 'TLR', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp&nbsp' . $data[$i]['subjectname'], '200', null, false, $border, 'TR', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['hours'], '200', null, false, $border, 'TR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $total = $total + $data[$i]['hours'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'LTB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Units : &nbsp&nbsp', '200', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '200', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // ________________________________________________________________________________
    $str .= $this->books_table($books, $font, $fontsize, $border);

    // ________________________________________________________________________________
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Assessment', '150', null, false, $border, '', 'L', $font, '14', 'B', '', '8px');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    for ($k = 0; $k < count($assess); $k++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->startrow();
      if ($assess[$k]['feestype'] == 'Total Balance:') {
        $str .= $this->reporter->col('&nbsp&nbsp' . $assess[$k]['feestype'], '150', null, false, $border, 'TLRB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($assess[$k]['amt'], 2) . '&nbsp&nbsp', '150', null, false, $border, 'TRB', 'R', $font, $fontsize, 'B', '', '');
      } else {
        $str .= $this->reporter->col('&nbsp&nbsp' . $assess[$k]['feestype'], '150', null, false, $border, 'TLRB', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($assess[$k]['amt'], 2) . '&nbsp&nbsp', '150', null, false, $border, 'TRB', 'R', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

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
    $str .= $this->reporter->col('Prepared By : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
