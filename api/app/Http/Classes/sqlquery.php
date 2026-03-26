<?php

namespace App\Http\Classes;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\Logger;
use App\Http\Classes\filesaving;
use App\Http\Classes\modules\issuance\trapproval;
use PhpParser\Node\Stmt\Else_;

class sqlquery
{

  protected $othersClass;
  protected $coreFunctions;
  protected $companysetup;
  protected $filesaving;
  private $logger;
  private $acctg = [];

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->logger = new Logger;
    $this->filesaving = new filesaving;
  } //end fn


  // list of function under inquiry
  public function inquiry($config)
  {
    $companyid = $config['params']['companyid'];
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451);

    switch ($config['params']['action']) {
      case 'itembalance':
        if ($companyid == 21 && $allowviewbalance == 0) {
          return ['data' => [], 'status' => true, 'msg' => 'Balances cannot be shown.'];
        }
        $data =  $this->itembalance($config);
        return ['data' => $data, 'status' => true, 'msg' => 'Balances were successfully obtained.'];
        break;
      default:
        return ['status' => false, 'msg' => 'No setup for inquiry (' . $config['params']->action . ')'];
        break;
    }
  }

  public function getledgerclient($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 32: //3m
        $fields = array('clientname', 'client', 'addr', 'brgy', 'area', 'province', 'region');
        break;
      case 34: //evergreen
        $fields = array('client.clientname', 'client.client', 'client.addr', 'client.brgy', 'client.area', 'client.province', 'client.region');
        break;
      default:
        $fields = array('clientname', 'client', 'addr');
        break;
    }


    $filter = '';
    $addedjoin = '';
    $active = '';
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    switch (strtolower($config['params']['doc'])) {
      case 'customer':
        $filter = ' where client.iscustomer=1 ' . $filter;
        break;
      case 'supplier':
        $filter = ' where client.issupplier=1 ' . $filter;
        break;
      case 'warehouse':
        $filter = ' where client.iswarehouse=1 ' . $filter;
        break;
      case 'agent':
        $filter = ' where client.isagent=1 ' . $filter;
        break;
      case 'department':
        $filter = ' where client.isdepartment=1 ' . $filter;
        break;
      case 'employee':
      case 'ep': //employee record
        $emplvl = $this->othersClass->checksecuritylevel($config, true);
        $filteremplvl =  'and emp.level in ' . $emplvl;
        if ($config['params']['moduletype'] == 'MASTERFILE') {
          $filteremplvl = '';
        }
        $addedjoin = "left join employee as emp on emp.empid = client.clientid";
        if ($config['params']['doc'] == 'EMPLOYEE') {
          if ($companyid == 53) { //camera
            $active = " and emp.isactive = 1 and emp.resigned is null ";
          }
        }
        $filter = ' where client.isemployee=1 ' . $filteremplvl . '' . $filter . ' ' . $active;
        break;
      case 'forwarder':
        $filter = ' where client.istrucking=1 ' . $filter;
        break;
      case 'en_student':
        $filter = ' where client.isstudent=1 ' . $filter;
        break;
      case 'en_instructor':
        $filter = ' where client.isinstructor=1 ' . $filter;
        break;
      case 'branch':
        $filter = ' where client.isbranch=1 ' . $filter;
        break;
      case 'tenant':
        $filter = ' where client.istenant=1 ' . $filter;
        break;
      case 'applicantledger':
        break;
      default:
        $filter = ' where client.iscustomer=1 ' . $filter;
        break;
    }

    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
    $adminid = $config['params']['adminid'];
    $condition = "";

    if ($this->companysetup->customerperagent($config['params'])) {
      if ($companyid == 34) { //evergreen
        if ($allowall == '0') {
          if ($adminid != 0) {
            $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
            if (floatval($isleader) == 1) {
              $addedjoin .= " left join client as ag on ag.client = client.agent left join client as lead on lead.clientid = ag.parent ";
              $condition  = " and (lead.clientid = " . $adminid . " or  ag.clientid =  " . $adminid . ") ";
            } else {
              $addedjoin .= " left join client as ag on ag.client = client.agent  ";
              $condition  = " and ag.clientid = " . $adminid . " ";
            }
          }
        }
      } else {
        if ($allowall == '0') {
          if ($adminid != 0) {
            $addedjoin .= " left join client as ag on ag.client = client.agent  ";
            $condition  = " and ag.clientid = " . $adminid . " ";
          }
        }
      }
    }


    $qry = "select client.clientid,client.client,client.clientname,client.addr,client.brgy,client.area,client.region,client.province
    from client
    " . $addedjoin . "
    " . $filter . $condition;
    if (strtolower($config['params']['doc']) == 'applicantledger') {
      $qry = "select empid as clientid,empcode as client,concat(emplast,', ',empfirst,' ',empmiddle) as clientname,address as addr from app " . $filter;
    }

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function getbuilding($config)
  {
    $fields = array('bldgcode', 'bldgname');
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    if ($filter !== '') {
      $filter = ' where 1=1 ' . $filter;
    }
    $qry = "select line as clientid,bldgcode,bldgname from en_bldg " . $filter . " order by bldgcode,bldgname LIMIT 50 ";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function

  public function getsubject($config)
  {
    $fields = array('subjectcode', 'subjectname');
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    if ($filter !== '') {
      $filter = ' where 1=1 ' . $filter;
    }
    $qry = "select trno as clientid,subjectcode,subjectname from en_subject " . $filter . " order by subjectcode,subjectname LIMIT 50 ";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function

  public function searchlocationledger($config)
  {
    $fields = array('code', 'name');
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    if ($filter !== '') {
      $filter = ' where 1=1 ' . $filter;
    }
    $qry = "select line as clientid, code, name, emeter, 
      wmeter, semeter, area, phase, section 
      from loc " . $filter . " 
      order by line 
      LIMIT 50 ";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function

  public function getinvoice($config)
  {
    $fields = array('docno', 'clientname', 'client', 'dateid');
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    if ($filter !== '') {
      $filter = ' where 1=1 ' . $filter;
    }
    $qry = "select * from
    (select head.trno,tablenum.docno,head.clientname,cl.client as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
    head.yourref,head.ourref,head.rem
    from cntnum as tablenum left join glhead as head on head.trno = tablenum.trno left join client as cl on cl.clientid = head.clientid
    where tablenum.doc = 'SJ' and tablenum.postdate is not null)  as tbl " . $filter . " order by docno,dateid LIMIT 50 ";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function

  public function getdocno($config)
  {
    switch ($config['params']['doc']) {
      case 'EARNINGDEDUCTIONSETUP':
      case 'ADVANCESETUP':
      case 'LOANAPPLICATION':
      case 'LOANAPPLICATIONPORTAL':
        $head = $config['docmodule']->head;
        break;

      default:
        $head = $config['docmodule']->head;
        $hhead = $config['docmodule']->hhead;
        $tablenum = $config['docmodule']->tablenum;
        $isproject = $this->companysetup->getisproject($config['params']);
        break;
    }

    $projectfilter = "";

    switch ($config['params']['doc']) {
      case 'ET':
        $fields = array('docno', 'dateid', 'period');
        break;
      case 'PL':
        $fields = array('docno', 'dateid', 'postdate', 'postedby', 'rem');
        break;
      case 'EARNINGDEDUCTIONSETUP':
      case 'LOANAPPLICATION':
      case 'LOANAPPLICATIONPORTAL':
        $fields = array('docno', 'client', 'clientname', 'acnoname');
        break;
      case 'ADVANCESETUP':
        $fields = array('docno', 'client', 'clientname');
        break;

      case 'LE':
        $fields = array('docno', 'client', 'clientname', 'dateid');
        break;

      case 'DX':
        $fields = array('docno', 'bank', 'amount', 'dateid');
        break;
      case 'TC':
        $fields = array('docno', 'rem', 'amount', 'dateid', 'postdate', 'postedby');
        break;
      case 'PX':
        $fields = array('docno', 'dateid', 'clientname', 'project', 'pcfno', 'dtcno', 'postdate', 'postedby');
        break;
      default:
        $fields = array('docno', 'clientname', 'client', 'dateid', 'yourref');
        if ($config['params']['companyid'] == 29) { //sbc main
          array_push($fields, 'rem');
        }
        break;
    }


    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);

    if ($filter !== '') {
      $filter = ' where 1=1 ' . $filter;
    }
    switch ($config['params']['doc']) {
      case 'PC':
      case 'AJ':
        $strcode = " wh.client";
        $strname = " wh.clientname";
        break;
      case 'CA':
      case 'CB':
      case 'CC':
        $strcode = " cl.client";
        $strname = " cl.clientname";
        break;
      case 'SQ':
      case 'AO':
        $strcode = " qt.client";
        $strname = " qt.clientname";
        break;
      case 'PL':
        break;
      case 'RQ':
      case 'BR':
      case 'BL':
        $strcode = " p.code";
        $strname = " p.name";
        break;
      case 'EARNINGDEDUCTIONSETUP':
      case 'LOANAPPLICATION':
      case 'LOANAPPLICATIONPORTAL':
        $strcode = " em.client";
        $strname = " em.clientname";
        break;
      case 'LE':
        $strcode = "  head.client ";
        $strname = " concat(head.lname,', ',head.fname,' ',head.mname) ";
        break;
      default:
        $strcode = " cl.client";
        $strname = " head.clientname";
        break;
    }

    switch ($config['params']['doc']) {
      case 'ES':
        $qry = "Select  head.yr,tablenum.postdate,tablenum.trno,head.docno,head.dateid,p.code as period,c.coursecode,c.coursename,sy.sy,head.section,head.curriculumcode,sem.term as terms,head.section
                from  " . $head . " as head left join " . $tablenum . " as tablenum on tablenum.trno=head.trno left join en_course as c on c.line=head.courseid
                left join en_schoolyear as sy on sy.line=head.syid left join en_period as p on p.line=head.periodid left join en_term as sem on sem.line=head.semid
                where tablenum.postdate is null and tablenum.center = '" . $config['params']['center'] . "'
                union all
                Select  head.yr,tablenum.postdate,tablenum.trno,head.docno,head.dateid,p.code as period,c.coursecode,c.coursename,sy.sy,head.section,head.curriculumcode,sem.term as terms,head.section
                from  " . $hhead . " as head left join " . $tablenum . " as tablenum on tablenum.trno=head.trno left join en_course as c on c.line=head.courseid
                left join en_schoolyear as sy on sy.line=head.syid  left join en_period as p on p.line=head.periodid  left join en_term as sem on sem.line=head.semid where tablenum.doc='" . $config['params']['doc'] . "' and tablenum.postdate is not null and tablenum.center = '" . $config['params']['center'] . "';";
        break;
      case 'EG':
        $qry = "select tablenum.trno, head.docno, course.coursecode, left(head.dateid, 10) as dateid, course.coursename,
          head.curriculumdocno, en_cchead.curriculumcode, en_cchead.curriculumname, head.clientid, client.client, client.clientname,
          head.syid, schoolyear.sy, head.levelid, level.levels, left(tablenum.postdate, 10) as postdate, tablenum.postedby
          from $head as head
          left join $tablenum as tablenum on tablenum.trno = head.trno
          left join en_course as course on course.line = head.courseid
          left join client on client.clientid = head.clientid
          left join en_schoolyear as schoolyear on schoolyear.line = head.syid
          left join en_cchead on en_cchead.docno = head.curriculumdocno
          left join en_levels as level on level.line = head.levelid
          where tablenum.doc = '{$config['params']['doc']}' and tablenum.postdate is null and tablenum.center = '{$config['params']['center']}'
        union all
          select tablenum.trno, head.docno, course.coursecode, left(head.dateid, 10) as dateid, course.coursename,
          head.curriculumdocno, en_cchead.curriculumcode, en_cchead.curriculumname, head.clientid, client.client, client.clientname,
          head.syid, schoolyear.sy, head.levelid, level.levels, left(tablenum.postdate, 10) as postdate, tablenum.postedby
          from $hhead as head
          left join $tablenum as tablenum on tablenum.trno = head.trno
          left join en_course as course on course.line = head.courseid
          left join client on client.clientid = head.clientid
          left join en_schoolyear as schoolyear on schoolyear.line = head.syid
          left join en_cchead on en_cchead.docno = head.curriculumdocno
          left join en_levels as level on level.line = head.levelid
          where tablenum.doc = '{$config['params']['doc']}' and tablenum.postdate is not null and tablenum.center = '{$config['params']['center']}'";
        break;
      case 'EH':
        $qry = "select tablenum.trno, head.docno, left(head.dateid, 10) as dateid, cl.clientname as teachername, subj.subjectname, left(tablenum.postdate, 10) as postdate, tablenum.postedby,
          pr.name as period, cr.coursename, sec.section, q.name as quarter
          from $head as head
          left join $tablenum as tablenum on tablenum.trno = head.trno
          left join en_period as pr on pr.line=head.periodid
          left join en_course as cr on cr.line=head.courseid
          left join en_section as sec on sec.line=head.sectionid
          left join en_quartersetup as q on q.line=head.quarterid
          left join client as cl on cl.clientid = head.adviserid
          left join en_subject as subj on subj.trno = head.subjectid
          where tablenum.doc = '{$config['params']['doc']}' and tablenum.postdate is null and tablenum.center = '{$config['params']['center']}'
        union all
          select tablenum.trno, head.docno, left(head.dateid, 10) as dateid, cl.clientname as teachername, subj.subjectname, left(tablenum.postdate, 10) as postdate, tablenum.postedby,
          pr.name as period, cr.coursename, sec.section, q.name as quarter
          from $hhead as head
          left join $tablenum as tablenum on tablenum.trno = head.trno
          left join en_period as pr on pr.line=head.periodid
          left join en_course as cr on cr.line=head.courseid
          left join en_section as sec on sec.line=head.sectionid
          left join en_quartersetup as q on q.line=head.quarterid
          left join client as cl on cl.clientid = head.adviserid
          left join en_subject as subj on subj.trno = head.subjectid
          where tablenum.doc='{$config['params']['doc']}' and tablenum.postdate is not null and tablenum.center = '{$config['params']['center']}'";
        break;
      case 'EM':
      case 'EF':
        $qry = "select tablenum.trno, head.docno, left(head.dateid, 10) as dateid, cl.clientname as teachername, subj.subjectname, left(tablenum.postdate, 10) as postdate, tablenum.postedby
          from $head as head
          left join $tablenum as tablenum on tablenum.trno = head.trno
          left join client as cl on cl.clientid = head.adviserid
          left join en_subject as subj on subj.trno = head.subjectid
          where tablenum.doc = '{$config['params']['doc']}' and tablenum.postdate is null and tablenum.center = '{$config['params']['center']}'
        union all
          select tablenum.trno, head.docno, left(head.dateid, 10) as dateid, cl.clientname as teachername, subj.subjectname, left(tablenum.postdate, 10) as postdate, tablenum.postedby
          from $hhead as head
          left join $tablenum as tablenum on tablenum.trno = head.trno
          left join client as cl on cl.clientid = head.adviserid
          left join en_subject as subj on subj.trno = head.subjectid
          where tablenum.doc='{$config['params']['doc']}' and tablenum.postdate is not null and tablenum.center = '{$config['params']['center']}'";
        break;
      case 'EA':
      case 'EI':
      case 'ED':
      case 'ER':
        $qry = "Select  head.yr,tablenum.postdate,tablenum.trno,head.docno,head.dateid,p.code as period,c.coursecode,c.coursename,sy.sy,s.section,sem.term as terms,client.client,client.clientname
                from  " . $head . " as head left join " . $tablenum . " as tablenum on tablenum.trno=head.trno left join en_course as c on c.line=head.courseid
                left join en_schoolyear as sy on sy.line=head.syid left join en_period as p on p.line=head.periodid left join en_term as sem on sem.line=head.semid
                left join client on client.client=head.client  left join en_section as s on s.line=head.sectionid
                where tablenum.postdate is null and tablenum.center = '" . $config['params']['center'] . "'
                union all
                Select  head.yr,tablenum.postdate,tablenum.trno,head.docno,head.dateid,p.code as period,c.coursecode,c.coursename,sy.sy,s.section,sem.term as terms,client.client,client.clientname
                from  " . $hhead . " as head left join " . $tablenum . " as tablenum on tablenum.trno=head.trno left join en_course as c on c.line=head.courseid
                left join en_schoolyear as sy on sy.line=head.syid left join en_period as p on p.line=head.periodid left join en_term as sem on sem.line=head.semid
                left join client on client.clientid=head.clientid  left join en_section as s on s.line=head.sectionid where tablenum.doc='" . $config['params']['doc'] . "' and tablenum.postdate is not null and tablenum.center = '" . $config['params']['center'] . "';";
        break;
      case 'CA':
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join client as cl on cl.client = head.client
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null and tablenum.center = '" . $config['params']['center'] . "')  as tbl " . $filter . " order by docno,dateid LIMIT 50 ";
        break;
      case 'LE':
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno 
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null and tablenum.center = '" . $config['params']['center'] . "'
        union all
        select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate
        from " . $tablenum . " as tablenum left join " . $hhead . " as head on head.trno = tablenum.trno 
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null and tablenum.center = '" . $config['params']['center'] . "')  as tbl " . $filter . " order by docno,dateid LIMIT 50 ";
        break;
      case 'CB':
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join client as cl on cl.client = head.client
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null and head.lockdate is not null and tablenum.center = '" . $config['params']['center'] . "')  as tbl " . $filter . " order by docno,dateid LIMIT 50 ";

        break;

      case 'DX':
        $qry = "select * from
        (select head.trno,tablenum.docno,coa.acnoname as bank,head.amount, left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate
        from " . $tablenum . " as tablenum  left join " . $head . " as head on head.trno = tablenum.trno left join coa on coa.acnoid=head.bank 
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null 
        and tablenum.center = '" . $config['params']['center'] . "')  as tbl " . $filter . " order by docno,dateid LIMIT 50 ";
        break;

      case 'TC':
        $qry = "select * from
        (select head.trno,tablenum.docno,head.rem,head.amount, left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate
        from " . $tablenum . " as tablenum  left join " . $head . " as head on head.trno = tablenum.trno
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null 
        and tablenum.center = '" . $config['params']['center'] . "')  as tbl " . $filter . " order by docno,dateid LIMIT 50 ";
        break;

      case 'EC':
        $qry = "select * from
            (select head.trno,head.docno, c.coursecode as client, c.coursename as clientname, sy.sy, head.dateid, num.postdate, num.postedby from " . $head . " as head
            left join " . $tablenum . " as num on num.trno=head.trno left join en_course as c on c.line=head.courseid
            left join en_schoolyear as sy on sy.line=head.syid where head.doc='EC' and num.center ='" . $config['params']['center'] . "' and num.postdate is null
            union all
            select head.trno,head.docno, c.coursecode, c.coursename, sy.sy, head.dateid, num.postdate, num.postedby from " . $hhead . " as head
            left join  " . $tablenum . " as num on num.trno=head.trno left join en_course as c on c.line=head.courseid
             left join en_schoolyear as sy on sy.line=head.syid
             where head.doc='" . $config['params']['doc'] . "' and num.center ='" . $config['params']['center'] . "'  and num.postdate is not null) as tbl
             " . $filter . "
             order by docno,dateid desc limit 50";

        break;
      case 'ET':
        $qry = "select * from
            (select head.trno, head.docno, left(head.dateid,10) as dateid, p.code as period, s.sy
            from " . $head . "  as head left join " . $tablenum . " as num on num.trno=head.trno
            left join en_period as p on p.line=head.periodid left join en_schoolyear as s on s.line=head.syid
            where head.doc='" . $config['params']['doc'] . "' and num.center='" . $config['params']['center'] . "' and num.postdate is null
            union all
            select head.trno, head.docno, left(head.dateid,10) as dateid, p.code as period, s.sy
            from  " . $hhead . "  as head left join " . $tablenum . " as num on num.trno=head.trno
            left join en_period as p on p.line=head.periodid left join en_schoolyear as s on s.line=head.syid
            where head.doc='" . $config['params']['doc'] . "' and num.center='" . $config['params']['center'] . "'  and num.postdate is not null
            order by dateid desc,docno desc limit 150) as tbl
             " . $filter . "
             order by docno,dateid desc limit 50";

        break;


      case 'HD':
        $qryselect = "select
          head.trno, head.docno, head.empid, head.dateid,
          head.artid, head.sectionno, head.violationno,
          head.startdate, head.enddate, head.amt,
          head.detail, emp.clientname,
          head.jobtitle,
          chead.description as articlename,
          cdetail.description as sectionname,
          head.penalty, head.numdays,
          head.refx,
          emp.client,
          dept.client as dept,
          head.deptid,
          ir.docno as irno,
          ir.idescription as irdesc,
          chead.code as artcode,
          cdetail.section as sectioncode";
        $qry = $qryselect . " from " . $head . " as head
        left join client as emp on emp.clientid=head.empid
        left join client as dept on dept.clientid=head.deptid
        left join hincidenthead as ir on head.refx=ir.trno
        left join codehead as chead on chead.artid=head.artid
        left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
        left join " . $tablenum . " as num on num.trno = head.trno
        where num.doc='HD' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
          left join client as emp on emp.clientid=head.empid
          left join client as dept on dept.clientid=head.deptid
          left join hincidenthead as ir on head.refx=ir.trno
          left join codehead as chead on chead.artid=head.artid
          left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
          left join " . $tablenum . " as num on num.trno = head.trno
          where num.doc='HD' and
          num.center='" . $config['params']['center'] . "' ";
        break;

      case 'HJ':
        $qryselect = "select
        num.trno,
        num.docno,
        head.empid,
        em.client,
        em.clientname,
        num.postdate,
        num.postedby,
        head.dateid,
        head.jobtitle";

        $qry = $qryselect . " from " . $head . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'";
        break;
      case 'HO':
        $qryselect = "select
        num.trno,
        num.docno,
        head.empid,
        em.client,
        em.clientname,
        num.postdate,
        num.postedby,
        head.dateid,
        head.jobtitle";

        $qry = $qryselect . " from " . $head . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'";
        break;
      case 'HR':
        $qryselect = "select
        num.trno,
        num.docno,
        head.empid,
        em.client,
        em.clientname,
        num.postdate,
        num.postedby,
        head.dateid,
        head.jobtitle";

        $qry = $qryselect . " from " . $head . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'";
        break;
      case 'HA':
        $qryselect = "select
        num.trno,
        num.docno,
        head.empid,
        em.client,
        em.clientname,
        num.postdate,
        num.postedby,
        head.dateid,
        head.title";

        $qry = $qryselect . " from " . $head . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'";
        break;

      case 'HI':
        $qryselect = "select
          num.trno,
          num.docno,
          head.tempid,
          em.client,
          em.clientname,
          num.postdate,
          num.postedby,
          head.dateid";

        $qry = $qryselect . " from " . $head . " as head
          left join client as em on em.clientid=head.tempid
          left join $tablenum as num on num.trno = head.trno
          where num.doc='" . $config['params']['doc'] . "' and
          num.center='" . $config['params']['center'] . "'
          union all
          " . $qryselect . " from " . $hhead . " as head
          left join client as em on em.clientid=head.tempid
          left join $tablenum as num on num.trno = head.trno
          where num.doc='" . $config['params']['doc'] . "' and
          num.center='" . $config['params']['center'] . "'";
        break;
      case 'HC':
        $qryselect = "select
            num.trno,
            num.docno,
            head.empid,
            em.client,
            em.clientname,
            num.postdate,
            num.postedby,
            head.dateid";

        $qry = $qryselect . " from " . $head . " as head
            left join client as em on em.clientid=head.empid
            left join $tablenum as num on num.trno = head.trno
            where num.doc='" . $config['params']['doc'] . "' and
            num.center='" . $config['params']['center'] . "'
            union all
            " . $qryselect . " from " . $hhead . " as head
            left join client as em on em.clientid=head.empid
            left join $tablenum as num on num.trno = head.trno
            where num.doc='" . $config['params']['doc'] . "' and
            num.center='" . $config['params']['center'] . "'";
        break;

      case 'HQ':
        $qryselect = "select
        num.trno,
        num.docno,
        head.empid,
        em.client,
        em.clientname,
        num.postdate,
        num.postedby,
        head.dateid";

        $qry = $qryselect . " from " . $head . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'";
        break;
      case 'HT':
        $qryselect = "select
        num.trno,
        num.docno,
        head.title,
        head.ttype,
        num.postdate,
        num.postedby,
        head.dateid,
        head.title";

        $qry = $qryselect . " from " . $head . " as head
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'";
        break;
      case 'HQ':
        $qryselect = "select
        num.trno,
        num.docno,
        head.empid,
        em.client,
        em.clientname,
        num.postdate,
        num.postedby,
        head.dateid";

        $qry = $qryselect . " from " . $head . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join client as em on em.clientid=head.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and
        num.center='" . $config['params']['center'] . "'";
        break;
      case 'HN':
        $qryselect = "select
          head.trno, head.docno, head.empid, head.dateid,
          head.artid,
          femp.client as fempcode, femp.clientname as fempname, head.fempjob,
          emp.clientname,
          head.empjob,
          chead.description as articlename,
          cdetail.description as sectionname,
          head.refx, head.hplace,
          head.line, head.explanation,
          head.ddate, head.htime, head.comments,
          head.hdatetime, head.remarks,
          emp.client,
          dept.client as dept,
          head.deptid,
          ir.docno as irno,
          ir.idescription as irdesc,
          chead.code as artcode,
          cdetail.section as sectioncode,
          head.fempid";
        $qry = $qryselect . " from " . $head . " as head
        left join client as emp on emp.clientid=head.empid
        left join client as dept on dept.clientid=head.deptid
        left join hincidenthead as ir on head.refx=ir.trno
        left join codehead as chead on chead.artid=head.artid
        left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
        left join client as femp on head.fempid=femp.clientid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
          left join client as emp on emp.clientid=head.empid
          left join client as dept on dept.clientid=head.deptid
          left join hincidenthead as ir on head.refx=ir.trno
          left join codehead as chead on chead.artid=head.artid
          left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
          left join client as femp on head.fempid=femp.clientid
          left join $tablenum as num on num.trno = head.trno
          where num.doc='" . $config['params']['doc'] . "' and num.center='" . $config['params']['center'] . "'";
        break;
      case 'HS':
        $qryselect = "select
          head.trno, head.docno, head.empid, date(head.dateid) as dateid,
          client.clientname,client.client,
          dept.clientid as deptid, dept.clientname as deptname, dept.client as dept,
          ap.jobtitle, ap.jobcode,
          stat.code as statcode, stat.stat as statdesc,
          head.description, date(head.effdate) as effdate, date(head.constart) as constart, date(head.conend) as conend,
          date(head.resigned) as resigned, head.remarks,
          head.ftype, head.flevel, head.fjobcode, head.fempstatcode,
          head.frank, head.fjobgrade, head.fdeptcode, head.flocation,
          head.fpaymode, head.fpaygroup, head.fpayrate, head.fallowrate, head.fbasicrate,
          head.ttype, head.tlevel, head.tjobcode, head.tempstatcode, head.trank, head.tjobgrade,
          head.tdeptcode,head.tlocation,head.tpaymode,head.tpaygroup,head.tpayrate,head.tallowrate,
          head.tbasicrate, head.isactive

        ";
        $qry = $qryselect . " from " . $head . " as head
        left join employee as emp on emp.empid=head.empid
        left join client as dept on dept.clientid=head.deptid
        left join app as ap on ap.empid=emp.aplid
        left join statchange as stat on head.statcode=stat.code
        left join client on client.clientid=emp.empid
        left join $tablenum as num on num.trno = head.trno
        where num.doc='" . $config['params']['doc'] . "' and num.center='" . $config['params']['center'] . "'
        union all
        " . $qryselect . " from " . $hhead . " as head
        left join employee as emp on emp.empid=head.empid
        left join client as dept on dept.clientid=head.deptid
        left join app as ap on ap.empid=emp.aplid
        left join statchange as stat on head.statcode=stat.code
        left join $tablenum as num on num.trno = head.trno
        left join client on client.clientid=emp.empid
        where num.doc='" . $config['params']['doc'] . "' and num.center='" . $config['params']['center'] . "'
        ";
        break;
      case 'SA':
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from  " . $head . " as head left join " . $tablenum . " as tablenum on tablenum.trno = head.trno  left join client as cl on cl.client = head.client
        left join client as agent on agent.client=head.agent
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' ";
        $qry = $qry . " UNION ALL
        select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from  " . $hhead . " as head left join  " . $tablenum . " as tablenum on  tablenum.trno = head.trno
        left join client as cl on cl.client = head.client left join client as agent on agent.client=head.agent
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
        and tablenum.center = '" . $config['params']['center'] . "'
        ) as tbl " . $filter . " order by dateid desc, docno desc LIMIT 50";
        break;
      case 'SB':
        $filterclient = " and cl.clientid=" . $config['params']['adminid'];
        $admin = $this->othersClass->checkAccess($config['params']['user'], 2217);
        if ($admin) {
          $filterclient = '';
        }

        $qry = "select * from
          (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
          head.yourref,head.ourref,head.rem
          from  " . $head . " as head left join " . $tablenum . " as tablenum on tablenum.trno = head.trno  left join client as cl on cl.client = head.client
          left join client as agent on agent.client=head.agent
          where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null " . $filterclient . "
          and tablenum.center = '" . $config['params']['center'] . "' ";
        $qry = $qry . " UNION ALL
          select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
          head.yourref,head.ourref,head.rem
          from  " . $hhead . " as head left join  " . $tablenum . " as tablenum on  tablenum.trno = head.trno
          left join client as cl on cl.client = head.client left join client as agent on agent.client=head.agent
          where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null " . $filterclient . " 
          and tablenum.center = '" . $config['params']['center'] . "'
          ) as tbl " . $filter . " order by dateid desc, docno desc LIMIT 50";
        break;

      case 'PL':
        $qry = "select * from
          (select h.trno, h.docno, date(h.dateid) as dateid, h.rem, null as postdate, t.postedby
          from plhead as h left join transnum as t on t.trno=h.trno
          where t.center='" . $config['params']['center'] . "' and t.doc='" . $config['params']['doc'] . "'
          union all
          select h.trno, h.docno, date(h.dateid) as dateid, h.rem, t.postdate, t.postedby
          from hplhead as h left join transnum as t on t.trno=h.trno
          where t.center='" . $config['params']['center'] . "' and t.doc='" . $config['params']['doc'] . "'
          ) as t " . $filter . "
          order by t.docno desc, t.docno desc limit 50";
        break;
      case 'VA':
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];

        $fields = "
          head.trno as clientid, head.docno as client,
          ifnull(cl.clientid, 0) as whid, ifnull(cl.client, '') as wh, ifnull(cl.clientname, '') as whname,
          head.trno, head.docno, date(head.dateid) as dateid, head.whid, head.yourref, head.ourref,
          head.notes, head.port, head.arrival, head.departure, head.enginerpm,
          head.timeatsea, head.avespeed, head.enginefueloil, head.cylinderoil,
          head.enginelubeoil, head.hiexhaust, head.loexhaust, head.exhaustgas,
          head.hicoolwater, head.locoolwater, head.lopress, head.fwpress,
          head.airpress, head.airinletpress, head.coolerin, head.coolerout,
          head.coolerfwin, head.coolerfwout, head.seawatertemp, head.engroomtemp,
          head.begcash, head.addcash, head.usagefeeamt, head.mooringamt,
          head.coastguardclearanceamt, head.pilotageamt, head.lifebouyamt,
          head.bunkeringamt, head.sopamt, head.othersamt, head.purchaseamt,
          head.crewsubsistenceamt, head.waterexpamt, head.localtranspoamt,
          head.others2amt, head.reqcash,
          head.usagefee, head.mooring, head.coastguardclearance,
          head.pilotage, head.lifebouy,
          head.bunkering, head.sop, head.others,
          head.purchase, head.crewsubsistence, head.waterexp,
          head.localtranspo, head.others2";

        $qry = "select " . $fields . "
        FROM " . $head . " as head
        left join hrisnum as num on num.trno = head.trno
        left join client as cl on cl.clientid = head.whid
        where num.doc = '$doc' and num.center = '$center'
        UNION ALL
        select " . $fields . "
        FROM " . $hhead . " as head
        left join hrisnum as num on num.trno = head.trno
        left join client as cl on cl.clientid = head.whid
        where num.doc = '$doc' and num.center = '$center'";
        break;
      case 'RR':
        if ($isproject) {
          $viewall = $this->othersClass->checkAccess($config['params']['user'], 2163);
          $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
          $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
          if ($viewall == '0') {
            $projectfilter = " and head.projectid = " . $projectid . " ";
          }
        }
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join client as cl on cl.client = head.client
        left join client as wh on wh.client=head.wh
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter;

        $qry = $qry . " UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                head.yourref,head.ourref,head.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno
                left join client as cl on cl.clientid = head.clientid  left join client as wh on wh.clientid=head.whid
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "'" . $projectfilter . "
                ) as tbl " . $filter . " order by docno,dateid LIMIT 50";

        break;
      case 'DM':
        if ($isproject) {
          $viewall = $this->othersClass->checkAccess($config['params']['user'], 2164);
          $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
          $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
          if ($viewall == '0') {
            $projectfilter = " and head.projectid = " . $projectid . " ";
          }
        }
        $qry = "select * from
          (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
          head.yourref,head.ourref,head.rem
          from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join client as cl on cl.client = head.client
          left join client as wh on wh.client=head.wh
          where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
          and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter;

        $qry = $qry . " UNION ALL
                  select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                  head.yourref,head.ourref,head.rem
                  from " . $tablenum . " as tablenum
                  left join " . $hhead . " as head on head.trno = tablenum.trno
                  left join client as cl on cl.clientid = head.clientid  left join client as wh on wh.clientid=head.whid
                  where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                  and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter . "
                  ) as tbl " . $filter . " order by docno desc,dateid LIMIT 50";

        break;
      case 'MI':
        if ($isproject) {
          $viewall = $this->othersClass->checkAccess($config['params']['user'], 2165);
          $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
          $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
          if ($viewall == '0') {
            $projectfilter = " and head.projectid = " . $projectid . " ";
          }
        }
        $qry = "select * from
          (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
          head.yourref,head.ourref,head.rem
          from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join client as cl on cl.client = head.client
          left join client as wh on wh.client=head.wh
          where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
          and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter;

        $qry = $qry . " UNION ALL
                  select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                  head.yourref,head.ourref,head.rem
                  from " . $tablenum . " as tablenum
                  left join " . $hhead . " as head on head.trno = tablenum.trno
                  left join client as cl on cl.clientid = head.clientid  left join client as wh on wh.clientid=head.whid
                  where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                  and tablenum.center = '" . $config['params']['center'] . "'" . $projectfilter . "
                  ) as tbl " . $filter . " order by docno desc,dateid LIMIT 50";

        break;
      case 'RQ':
        $projectfilter = "";
        $viewall = $this->othersClass->checkAccess($config['params']['user'], 2272);
        $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
        $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        if ($systemtype == 'CAIMS') {
          if ($viewall == '0') {
            $projectfilter = " and head.projectid = " . $projectid . " ";
          }
        }

        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join projectmasterfile as p on p.line = head.projectid
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter;

        $qry = $qry . " UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                head.yourref,head.ourref,head.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno left join projectmasterfile as p on p.line = head.projectid
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter . "
                ) as tbl " . $filter . " order by docno desc,dateid LIMIT 50";

        break;

      case 'BR':
        $projectfilter = "";
        $viewall = $this->othersClass->checkAccess($config['params']['user'], 2271);
        $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
        $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        if ($systemtype == 'CAIMS') {
          if ($viewall == '0') {
            $projectfilter = " and head.projectid = " . $projectid . " ";
          }
        }

        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join projectmasterfile as p on p.line = head.projectid
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter;

        $qry = $qry . " UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                head.yourref,head.ourref,head.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno left join projectmasterfile as p on p.line = head.projectid
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter . "
                ) as tbl " . $filter . " order by docno,dateid LIMIT 50";

        break;

      case 'BL':
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join projectmasterfile as p on p.line = head.projectid
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' ";

        $qry = $qry . " UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                head.yourref,head.ourref,head.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno left join projectmasterfile as p on p.line = head.projectid
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "' 
                ) as tbl " . $filter . " order by docno,dateid LIMIT 50";

        break;

      case 'DT': // Document Management
        $qry = "select head.trno, num.postdate, num.postedby, head.docno, head.dateid, client.clientname, doctype.documenttype, head.invoiceno, head.title
          from $head as head
          left join $tablenum as num on num.trno=head.trno
          left join dt_documenttype as doctype on doctype.id=head.doctypeid
          left join client on client.clientid=head.clientid
          where num.postdate is null and num.center='{$config['params']['center']}'
          union all
          select head.trno, num.postdate, num.postedby, head.docno, head.dateid, client.clientname, doctype.documenttype, head.invoiceno, head.title
          from $hhead as head
          left join $tablenum as num on num.trno=head.trno
          left join dt_documenttype as doctype on doctype.id=head.doctypeid
          left join client on client.clientid=head.clientid
          where num.doc='{$config['params']['doc']}' and num.postdate is not null and num.center='{$config['params']['center']}'";
        break;

      case 'CR':
      case 'CV':

        $detail = $config['docmodule']->detail;
        $hdetail = $config['docmodule']->hdetail;

        if ($isproject) {
          $viewall = $this->othersClass->checkAccess($config['params']['user'], 2163);
          $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
          $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
          if ($viewall == '0') {
            $projectfilter = " and head.projectid = " . $projectid . " ";
          }
        }
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem, round(sum(detail.db),2) as amt
        from " . $tablenum . " as tablenum 
        left join " . $head . " as head on head.trno = tablenum.trno 
        left join " . $detail . " as detail on detail.trno = head.trno 
        left join client as cl on cl.client = head.client
        left join client as wh on wh.client=head.wh
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter .
          "group by head.trno, tablenum.docno, head.clientname, cl.client, head.dateid,10, tablenum.postedby,
        tablenum.postdate, head.yourref,head.ourref,head.rem";

        $qry = $qry . " UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                head.yourref,head.ourref,head.rem, round(sum(detail.db),2) as amt
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno
                left join " . $hdetail . " as detail on detail.trno = head.trno 
                left join client as cl on cl.clientid = head.clientid  left join client as wh on wh.clientid=head.whid
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "'" . $projectfilter . "

                group by tablenum.trno, tablenum.docno, head.clientname, cl.client, head.dateid,10, 
                tablenum.postedby,tablenum.postdate, head.yourref,head.ourref,head.rem
                ) as tbl " . $filter . " 
                order by docno desc,dateid LIMIT 50";

        break;
      case 'SQ':
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        qt.yourref,qt.ourref,qt.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join hqshead as qt on qt.sotrno=head.trno left join client as cl on cl.client = qt.client
        left join client as wh on wh.client=qt.wh
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' 
        UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                qt.yourref,qt.ourref,qt.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno left join hqshead as qt on qt.sotrno=head.trno
                left join client as cl on cl.client = qt.client left join client as wh on wh.client=qt.wh
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "'
                ) as tbl " . $filter . " order by docno,dateid LIMIT 50";
        break;
      case 'AO':
        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        qt.yourref,qt.ourref,qt.rem
        from " . $tablenum . " as tablenum left join " . $head . " as head on head.trno = tablenum.trno left join hqshead as qt on qt.sotrno=head.trno left join client as cl on cl.client = qt.client
        left join client as wh on wh.client=qt.wh
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        and tablenum.center = '" . $config['params']['center'] . "' 
        UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                qt.yourref,qt.ourref,qt.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno left join hqshead as qt on qt.sotrno=head.trno
                left join client as cl on cl.client = qt.client left join client as wh on wh.client=qt.wh
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "'
                ) as tbl " . $filter . " order by docno,dateid LIMIT 50";
        break;
      case 'EARNINGDEDUCTIONSETUP':
      case 'LOANAPPLICATION':
        $qry = "
         select s.trno as clientid, s.docno as client, s.docno,
         s.dateid, s.empid, s.remarks, pac.code as acno, s.amt, s.paymode,
         w1,w2,w3,w4,w5,w13,halt,s.priority, s.earnded, s.amortization, s.effdate,s.payment,
         concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
         concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as clientname,
         pac.codename as acnoname, client.client as empcode,
         balance, s.acnoid
         from standardsetup as s
           left join employee as e on s.empid = e.empid
           left join client on client.clientid = e.empid
           left join paccount as pac on pac.line = s.acnoid";
        break;
      case 'LOANAPPLICATIONPORTAL':
        $empid = $config['params']['adminid'];
        $qry = "
          select s.trno as clientid, s.docno as client, s.docno,
          s.dateid, s.empid, s.remarks, pac.code as acno, s.amt, s.paymode,
          w1,w2,w3,w4,w5,w13,halt,s.priority, s.earnded, s.amortization, s.effdate,s.payment,
          concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
          concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as clientname,
          pac.codename as acnoname, client.client as empcode,
          balance, s.acnoid
          from loanapplication as s
            left join employee as e on s.empid = e.empid
            left join client on client.clientid = e.empid
            left join paccount as pac on pac.line = s.acnoid
          where s.empid = $empid";
        break;
      case 'ADVANCESETUP':
        $qry = "
          select s.trno as clientid, s.docno as client, s.docno,
          s.dateid, s.empid, s.remarks, pac.code as acno, s.amt, s.paymode,
          w1,w2,w3,w4,w5,w13,halt,s.priority, s.earnded, s.amortization, s.effdate,s.payment,
          concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as empname,
          concat(e.emplast,', ',e.empfirst,' ',e.empmiddle) as clientname,
          pac.codename as acnoname, client.client as empcode,
          balance, s.acnoid
          from standardsetupadv as s
            left join employee as e on s.empid = e.empid
            left join client on client.clientid = e.empid
            left join paccount as pac on pac.line = s.acnoid";
        break;

      case 'VR':
        $qry = "select * from
        (select head.trno, head.docno, c.client as client, c.clientname as clientname, head.dateid, num.postdate, num.postedby from " . $head . " as head
        left join " . $tablenum . " as num on num.trno=head.trno 
        left join client as c on c.clientid = head.clientid
        where head.doc='" . $config['params']['doc'] . "' and num.center ='" . $config['params']['center'] . "'  
        and head.status = '' and num.postdate is null
        union all
        select head.trno, head.docno, c.client as client, c.clientname as clientname, head.dateid, num.postdate, num.postedby from " . $hhead . " as head
        left join  " . $tablenum . " as num on num.trno=head.trno
        left join client as c on c.clientid = head.clientid
        where head.doc='" . $config['params']['doc'] . "' and num.center ='" . $config['params']['center'] . "'  
        and head.status = ''
        and num.postdate is not null) as tbl
        " . $filter . "
        order by docno,dateid desc limit 50";
        break;

      case 'BA':
        $qry = "select * from
            (select head.trno,tablenum.docno, " . $strname . " as  clientname,
            " . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,
                    left(tablenum.postdate,10) as postdate,head.yourref,head.ourref,head.rem
            from " . $tablenum . "  as tablenum
            left join " . $head . " as head on head.trno = tablenum.trno
            left join client as cl on cl.client = head.client
            where tablenum.doc = 'BA' and tablenum.postdate is null 
            and tablenum.center = '" . $config['params']['center'] . "'
            UNION ALL
            select tablenum.trno,tablenum.docno, " . $strname . " as clientname,
            " . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,
                   left(tablenum.postdate,10) as postdate,
                   head.yourref,head.ourref,head.rem
           from " . $tablenum . " as tablenum
           left join " . $hhead . " as head on head.trno = tablenum.trno
           left join client as cl on cl.client = head.client
           where tablenum.doc = 'BA' and tablenum.postdate is not null
           and tablenum.center = '" . $config['params']['center'] . "' ) as tbl " . $filter . "  order by docno,dateid LIMIT 50";
        break;

      case 'PM':
        $qry = "select * from
                (select head.trno,tablenum.docno, " . $strname . " as  clientname,
                " . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,
                left(tablenum.postdate,10) as postdate,head.rem
                from " . $tablenum . " as tablenum
                left join " . $head . " as head on head.trno = tablenum.trno
                left join client as cl on cl.client = head.client
                left join client as wh on wh.client=head.wh
                where tablenum.doc = 'PM' and tablenum.postdate is null
                and tablenum.center = '" . $config['params']['center'] . "'
               UNION ALL
                select tablenum.trno,tablenum.docno, " . $strname . " as clientname,
                " . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,
                left(tablenum.postdate,10) as postdate,head.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno
                left join client as cl on cl.client = head.client
                left join client as wh on wh.client=head.wh
                where tablenum.doc = 'PM' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "') as tbl " . $filter . "
                order by docno,dateid desc LIMIT 50";
        break;

      case 'JC':
        $projectfilter = "";

        $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
        $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);

        if ($project != '') {
          $projectfilter = " and head.projectid = " . $projectid . " ";
        }

        $qry = "select * from
                      (select head.trno,tablenum.docno, " . $strname . " as  clientname, " . $strcode . " as client,
                              left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                              head.yourref,head.ourref,head.rem
                      from " . $tablenum . " as tablenum
                      left join " . $head . " as head on head.trno = tablenum.trno
                      left join client as cl on cl.client = head.client
                      where tablenum.doc = 'JC' and tablenum.postdate is null and head.trno is not null
                      and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter . "
                      UNION ALL
                      select tablenum.trno,tablenum.docno, " . $strname . " as clientname, " . $strcode . " as client,
                            left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                            head.yourref,head.ourref,head.rem
                      from " . $tablenum . " as tablenum
                      left join " . $hhead . " as head on head.trno = tablenum.trno
                      left join client as cl on cl.clientid = head.client
                      where tablenum.doc = 'JC' and tablenum.postdate is not null and head.trno is not null
                      and tablenum.center = '" . $config['params']['center'] . "' " . $projectfilter . ") as tbl " . $filter . "
                order by docno,dateid desc LIMIT 50";
        break;
      case 'CP':
        $moduletype = $config['params']['moduletype'];
        $agentfilter = "";
        $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
        $adminid =  $config['params']['adminid'];


        if ($allowall == '0') {
          if ($adminid != 0) {
            $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
            if (floatval($isleader) == 1) {
              $agentfilter = " and (lead.clientid = " . $adminid . " or  ag.clientid =  " . $adminid . ") ";
            } else {
              $agentfilter = " and ag.clientid = " . $adminid . " ";
            }
          }
        }

        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem 
        from " . $tablenum . " as tablenum 
        left join " . $head . " as head on head.trno = tablenum.trno       
        left join client as cl on cl.client = head.client
        left join client as ag on ag.client = head.agent
        left join client as lead on lead.clientid = ag.parent
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
         and tablenum.center = '" . $config['params']['center'] . "' " . $agentfilter . "
         UNION ALL 
         select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
         head.yourref,head.ourref,head.rem
         from " . $tablenum . " as tablenum
         left join " . $hhead . " as head on head.trno = tablenum.trno
         left join client as cl on cl.clientid = head.clientid 
         left join client as ag on ag.clientid = head.agentid
         left join client as lead on lead.clientid = ag.parent
         where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
         and tablenum.center = '" . $config['params']['center'] . "' " . $agentfilter . "
         ) as tbl " . $filter . " order by docno,dateid desc LIMIT 50";

        break;
      case 'AF':
        $moduletype = $config['params']['moduletype'];
        $agentfilter = "";
        $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);
        $adminid =  $config['params']['adminid'];

        if ($allowall == '0') {
          if ($adminid != 0) {
            $isleader = $this->coreFunctions->getfieldvalue("client", "isleader", "clientid=?", [$adminid]);
            if (floatval($isleader) == 1) {
              $agentfilter = " and (lead.clientid = " . $adminid . " or  ag.clientid =  " . $adminid . ") ";
            } else {
              $agentfilter = " and ag.clientid = " . $adminid . " ";
            }
          }
        }

        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem 
        from " . $tablenum . " as tablenum 
        left join " . $head . " as head on head.trno = tablenum.trno       
        left join client as cl on cl.client = head.client
        left join client as ag on ag.client = head.agent
        left join client as lead on lead.clientid = ag.parent
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
         and tablenum.center = '" . $config['params']['center'] . "' " . $agentfilter . "
         UNION ALL 
         select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
         head.yourref,head.ourref,head.rem
         from " . $tablenum . " as tablenum
         left join " . $hhead . " as head on head.trno = tablenum.trno
         left join client as cl on cl.client = head.client 
         left join client as ag on ag.client = head.agent
         left join client as lead on lead.clientid = ag.parent
         where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
         and tablenum.center = '" . $config['params']['center'] . "' " . $agentfilter . "
         ) as tbl " . $filter . " order by docno,dateid desc LIMIT 50";


        break;

      case 'PA':
      case 'PP':
        $qry = "select * from
        (select head.trno,tablenum.docno,left(head.dateid,10) as dateid,left(head.due,10) as due,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem 
        from " . $tablenum . " as tablenum 
        left join " . $head . " as head on head.trno = tablenum.trno       
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
         UNION ALL 
         select tablenum.trno,tablenum.docno,left(head.dateid,10) as dateid,left(head.due,10) as due,tablenum.postedby,left(tablenum.postdate,10) as postdate,
         head.yourref,head.ourref,head.rem
         from " . $tablenum . " as tablenum
         left join " . $hhead . " as head on head.trno = tablenum.trno
         where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
         ) as tbl " . $filter . " order by dateid desc,docno LIMIT 50";
        break;

      case 'PX':
        $adminid = $config['params']['adminid'];
        $isadmin = $this->othersClass->checkAccess($config['params']['user'], 5389);
        $isphead = 0;
        $filter2 = '';

        if ($adminid != 0) {
          $username = $this->coreFunctions->datareader("select email as value from client where clientid =" . $adminid);
        } else {
          $username = $config['params']['user'];
        }


        if ($isadmin == 0) {
          $isphead = $this->coreFunctions->getfieldvalue("projectmasterfile", "agentid", "agentid = ?", [$adminid], '', true);
        }

        if ($isadmin == 0 && $isphead == 0) {
          $filter2 .= " and head.createby='" . $username . "'";
        } else {
          if ($isphead == 1) {
            $filter2 .= " and pm.agentid = " . $adminid;
          }
        }

        $qry = "select * from (
          select head.trno, tablenum.docno, left(head.dateid,10) as dateid, tablenum.postedby, left(tablenum.postdate,10) as postdate,
          head.rem, " . $strname . " as clientname, " . $strcode . " as client,head.project,head.pcfno,head.dtcno
          from " . $head . " as head
          left join " . $tablenum . " as tablenum on tablenum.trno=head.trno
          left join projectmasterfile as pm on pm.code = head.project
          left join client as cl on cl.clientid=head.clientid where tablenum.doc ='PX' " . $filter2 . "
          union all
          select head.trno, tablenum.docno, left(head.dateid,10) as dateid, tablenum.postedby, left(tablenum.postdate,10) as postdate,
          head.rem, " . $strname . " as clientname, " . $strcode . " as client,head.project,head.pcfno,head.dtcno
          from " . $hhead . " as head
          left join " . $tablenum . " as tablenum on tablenum.trno=head.trno
          left join projectmasterfile as pm on pm.code = head.project
          left join client as cl on cl.clientid=head.clientid where tablenum.doc ='PX'  " . $filter2 . "
        ) as tbl " . $filter . " order by dateid desc, docno limit 50";
        $this->coreFunctions->LogConsole($qry);
        break;
      default:
        $moduletype = $config['params']['moduletype'];
        $addedfilter = "";
        $addedgrp = "";
        $addedfields = "";
        $addedjoins1 = "";
        $addedjoins2 = "";

        if ($config['params']['doc'] == "SJ") {
          if ($moduletype == "POS") {
            $addedfilter = " and left(tablenum.bref,3) = 'SJS'";
          } else {
            $addedfilter = " and left(tablenum.bref,3) <> 'SJS'";
          }
        }

        if ($config['params']['doc'] == "CM") {
          if ($moduletype == "POS") {
            $addedfilter = " and left(tablenum.bref,3) = 'SRS'";
          } else {
            $addedfilter = " and left(tablenum.bref,3) <> 'SRS'";
          }
        }

        switch ($config['params']['companyid']) {
          case 19: //housegem
            if ($config['params']['doc'] == "SO") {

              $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5347);

              $user = $config['params']['user'];
              $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);

              $addedjoins1 = "left join useraccess as user on user.username=head.createby";
              $addedjoins2 = "left join useraccess as user on user.username=head.createby";

              if ($userid != 0) {
                if ($viewaccess == '0') {
                  $addedfilter .= " and user.userid = $userid ";
                }
              }
            }
            break;
          case 21: //kinggeorge
            if ($config['params']['doc'] == "GJ") {
              $detail = $config['docmodule']->detail;
              $hdetail = $config['docmodule']->hdetail;
              $addedfields = ", round(sum(detail.db),2) as amt";
              $addedjoins1 = "left join " . $detail . " as detail on detail.trno = head.trno ";
              $addedjoins2 = "left join " . $hdetail . " as detail on detail.trno = head.trno ";
              $addedgrp = "group by tablenum.trno,head.trno, tablenum.docno, head.clientname, cl.client, head.dateid,10, tablenum.postedby,tablenum.postdate, head.yourref,head.ourref,head.rem";
            }
            break;
        }

        $qry = "select * from
        (select head.trno,tablenum.docno," . $strname . " as  clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
        head.yourref,head.ourref,head.rem " . $addedfields . "
        from " . $tablenum . " as tablenum 
        left join " . $head . " as head on head.trno = tablenum.trno 
        " . $addedjoins1 . "
        
        left join client as cl on cl.client = head.client
        left join client as wh on wh.client=head.wh
        where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is null
        " . $addedfilter . "
        and tablenum.center = '" . $config['params']['center'] . "' 
        " . $addedgrp;

        $orderby = "order by docno,dateid desc";
        if ($config['params']['companyid'] == 60) { //transpower
          $orderby = " order by dateid desc,docno";
        }
        switch ($tablenum) {
          case 'cntnum':
            $qry = $qry . " UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                head.yourref,head.ourref,head.rem " . $addedfields . "
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno
                " . $addedjoins2 . "
                left join client as cl on cl.clientid = head.clientid  left join client as wh on wh.clientid=head.whid
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                " . $addedfilter . "
                and tablenum.center = '" . $config['params']['center'] . "' " . $addedgrp . "
                ) as tbl " . $filter . " 
                $orderby LIMIT 50";
            break;
          case 'transnum':
            $qry = $qry . " UNION ALL
                select tablenum.trno,tablenum.docno," . $strname . " as clientname," . $strcode . " as client,left(head.dateid,10) as dateid,tablenum.postedby,left(tablenum.postdate,10) as postdate,
                head.yourref,head.ourref,head.rem
                from " . $tablenum . " as tablenum
                left join " . $hhead . " as head on head.trno = tablenum.trno
                 " . $addedjoins2 . "
                left join client as cl on cl.client = head.client 
                left join client as wh on wh.client=head.wh
                where tablenum.doc = '" . $config['params']['doc'] . "' and tablenum.postdate is not null
                and tablenum.center = '" . $config['params']['center'] . "' " . $addedfilter . "
                ) as tbl " . $filter . "  $orderby LIMIT 50";
            break;
        }
        break;
    }


    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function getterms($config)
  {
    $qry = "select terms,days from terms";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }



  public function itemSearch($config)
  {
    ini_set('max_execution_time', -1);

    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $moduletype = $config['params']['moduletype'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $addedfields = "";
    $itemname = "itemname";
    $joins = "";
    $limit = " limit 500";

    $uomfield = 'item.uom';
    $isclientitem = $this->companysetup->isclientitem($config['params']);

    if ($this->companysetup->getisdefaultuominout($config['params'])) {
      switch (strtoupper($doc)) {
        case 'PO':
        case 'RR':
          $uomfield = 'ifnull(uom.uom,item.uom) as uom';
          $joins = " left join uom on uom.itemid=item.itemid and uom.isdefault=1 "; //in default uom
          if ($companyid == 22) { //eipi
            $joins = $joins . " left join client as supp on supp.clientid=item.supplier ";
          }
          break;

        case 'CM':
        case 'PC':
        case 'TS':
          $uomfield = 'ifnull(uom.uom,item.uom) as uom';
          $joins = " left join uom on uom.itemid=item.itemid and uom.isdefault=1 "; //in default uom
          break;
        case 'SO':
        case 'SJ':
        case 'RM':
        case 'DM':

          $uomfield = 'ifnull(uom.uom,item.uom) as uom';
          $joins = " left join uom on uom.itemid=item.itemid and uom.isdefault2=1"; //out default uom
          if ($companyid == 22) { //eipi
            $uomfield .= ',sku.amt as eskuamt';
            $client = $config['params']['client'];
            $clid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$client]);
            $joins .= " left join sku on sku.itemid=item.itemid and sku.clientid= $clid ";
          }
          break;
        default:
          goto defaulthere;
          break;
      }
    } else {
      defaulthere:
      switch ($companyid) {
        case 6: // mitsukoshi
          $addedfields .= ", item.partno, item.subcode";
          $itemname = "concat(itemname,'\\nModel: ',ifnull(model.model_name,'')) as itemname";
          break;
        case 10: //afti
        case 12: //afti usd
          $joins = " left join projectmasterfile as p on p.line = item.projectid ";
          $addedfields .= ", item.partno,item.moq,item.mmoq,p.name as pname ";
          $itemname = "concat(itemname,'\\nBrand: ',ifnull(brand.brand_desc,'')) as itemname";
          break;
        case 14: //majesty
        case 47: //kitchenstar
          $addedfields .=
            ",ifnull(grp.stockgrp_name,'') as division,
            ifnull(icat.name,'') as categoryname,
            ifnull(isub.name,'') as subcatname,
            ifnull(dep.clientname,'') as deptname,replace(item.partno,'-','') as partno2
            ";
          $joins = "            
            left join itemsubcategory as isub on isub.line=item.subcat
            left join client as dep on dep.clientid=item.linkdept
            ";

          break;
        case 16: //ati
          switch ($doc) {
            case 'GP':
            case 'IS':
              $addedfields .= ", info.serialno, emp.clientname as empname, dept.clientname as deptname ";
              $joins = " left join iteminfo as info on info.itemid=item.itemid left join client as emp on emp.clientid=info.empid left join client as dept on dept.clientid=info.locid ";
          }
          break;
        case 20: //proline
          //$addedfields .= ",ifnull(brand.brand_desc,'') as brandname";
          break;
        case 27: //nte
        case 36: //rozlab
          switch ($doc) {
            case 'PO':
            case 'RR':
              $uomfield = 'uom.uom';
              $joins = " left join uom on uom.itemid=item.itemid and uom.isdefault=1";
              break;
          }
          break;
        case 22: //eipi
          switch ($doc) {
            case 'PO':
            case 'RR':
              $joins = " left join client as supp on supp.clientid=item.supplier ";
              break;
          }
          break;
        case 35: //aquamax
          $addedfields .= ",ifnull(item.shortname,'') as shortname, p.name as projectname, item.projectid";
          $joins = " left join projectmasterfile as p on p.line=item.projectid ";
          break;
        case 39: //cbbsi
          $addedfields .= ",ifnull(left(item.barcode,8),'') as shortcode";
          if ($isclientitem) {
            $joins = " left join client as supp on supp.clientid=item.supplier";
          }
          break;

        case 19: //housegem
          switch ($doc) {
            case 'PO':
              $joins = "left join supplieritem as sp on sp.itemid=item.itemid";
              break;
            default:
              goto default_left_join_here;
              break;
          }
          break;

        case 56: //homeworks
          switch ($doc) {
            case 'PO':
            case 'RR':
            case 'DM':
              $joins = " left join client as supp on supp.clientid=item.supplier ";
              break;
            default:
              goto default_left_join_here;
              break;
          }
          break;

        default:
          default_left_join_here:
          if ($isclientitem) {
            $joins = " left join client as supp on supp.clientid=item.supplier ";
          }
          break;
      }
    }
    $qry = '';

    if ($companyid == 16 && $config['params']['doc'] == 'BARCODEASSIGNING') { //ati
      $qry = "select '' as sizeid,'' barcode,0 as itemid,'' as category,'' as groupid,'' as othcode,'' as itemname,'' as uom,0 as factor,'' as amt,'' as brand,'' as class,'' as body,'' as part,'' as model,'' as disc,'' as partno,'' as shortname,'' as netprice, '' as brandname union all ";
    }

    $amtfield = ',round(item.amt,2) as amt';
    if ($companyid == 39 && $config['params']['doc'] == 'ST') $amtfield = ',round(item.amt9,2) as amt'; //cbbsi


    //itemsearch
    $qry .= "select sizeid,barcode,item.itemid as itemid,item.category,grp.stockgrp_name as groupid,item.othcode,
    " . $itemname . "," . $uomfield . ",uom1.factor" . $amtfield . ",brand,ifnull(cls.cl_name,'') as class,body,
    ifnull(part.part_name,'') as part,ifnull(model.model_name,'') as model,item.disc, item.partno,item.shortname,
    round(item.amt - (item.amt * (REPLACE(item.disc,'%','')/100)),2) as netprice, ifnull(brand.brand_desc,'') as brandname,item.color,
    format(item.namt5,2) as namt5,format(item.namt7,2) as namt7,format(item.amt2,2) as amt2,item.disc2, format(item.namt4,2) as namt4 " . $addedfields . "
    from item
    left join item_class as cls on cls.cl_id=item.class
    left join uom as uom1 on item.itemid = uom1.itemid and uom1.uom = item.uom
    left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
    left join model_masterfile as model on model.model_id = item.model
    left join part_masterfile as part on part.part_id = item.part
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join itemcategory as icat on icat.line=item.category
    " . $joins;

    if ($companyid == 40) { //cdo
      $qry = "select sizeid,barcode,item.itemid as itemid,item.category,
        " . $itemname . "," . $uomfield . ",uom1.factor" . $amtfield . ",brand,body,
        ifnull(model.model_name,'') as model, item.partno,
         ifnull(brand.brand_desc,'') as brandname, ifnull(icat.name,'') as categoryname,
            ifnull(isub.name,'') as subcatname,item.shortname as partno2
        from item
        left join uom as uom1 on item.itemid = uom1.itemid and uom1.uom = item.uom
        left join model_masterfile as model on model.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join itemcategory as icat on icat.line=item.category
         left join itemsubcategory as isub on isub.line=item.subcat";
    }

    if ($companyid == 42 && $config['params']['doc'] == 'TS') { //pdpi mis
      $qry = "select item.itemname,item.barcode,item.itemid,grp.stockgrp_name as groupid,item.uom from item 
      left join rrstatus as rr on rr.itemid = item.itemid left join client as wh on wh.clientid = rr.whid 
      left join item_class as cls on cls.cl_id=item.class
      left join uom as uom1 on item.itemid = uom1.itemid and uom1.uom = item.uom
      left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
      left join model_masterfile as model on model.model_id = item.model
      left join part_masterfile as part on part.part_id = item.part
      left join frontend_ebrands as brand on brand.brandid = item.brand
        ";
    }

    $keyword = explode(",", $config['params']['search']);
    $criteria = "";
    $addparams  = "";
    $ops = "";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd        
        if (isset($config['params']['itemfilter'])) {
          $keyword2 = $config['params']['itemfilter']['docno'];
          if (isset($config['params']['itemfilter']['selectprefix'])) {
            if ($config['params']['itemfilter']['selectprefix'] != "") {
              $ops = $config['params']['itemfilter']['operator'];
              if (strtoupper($ops) == 'LIKE') {
                $keyword2 = "%" . $config['params']['itemfilter']['docno'] . "%";
              } else {
                $ops =  "=";
              }
              switch ($config['params']['itemfilter']['selectprefix']) {
                case 'Item Code':
                  $addparams = " and item.partno " . $ops . " '" . $keyword2 . "'";
                  break;
                case 'Item Name':
                  $addparams = " and item.itemname " . $ops . " '" . $keyword2 . "'";
                  break;
                case 'Model':
                  $addparams = " and model.model_name " . $ops . " '" . $keyword2 . "'";
                  break;
                case 'Brand':
                  $addparams = " and brand.brand_desc " . $ops . " '" . $keyword2 . "'";
                  break;
                case 'Item Group':
                  $addparams = " and p.name " . $ops . " '" . $keyword2 . "'";
                  break;
              }
              $criteria = " where item.isinactive = 0 " . $addparams;
            } else {
              $criteria = " where item.isinactive = 0 ";
            }
          }
        }


        foreach ($keyword as $key) {
          if ($criteria == "") {
            $criteria = "  and (
                                  item.itemname LIKE '%" . $key . "%' or
                                  item.barcode LIKE '%" . $key . "%' or
                                  brand.brand_desc LIKE '%" . $key . "%' or
                                  model.model_name LIKE '%" . $key . "%' or
                                  part.part_name LIKE '%" . $key . "%' or
                                  icat.name LIKE '%" . $key . "%' or
                                  grp.stockgrp_name LIKE '%" . $key . "%' or
                                  cls.cl_name LIKE '%" . $key . "%' or
                                  item.body LIKE '%" . $key . "%' or
                                  item.sizeid LIKE '%" . $key . "%' or
                                  item.subcode LIKE '%" . $key . "%' or
                                  item.partno LIKE '%" . $key . "%' or
                                  item.color LIKE '%" . $key . "%' or
                                  item.sizeid LIKE '%" . $key . "%')";
          } else {
            $criteria = $criteria . "  and (
                                  item.itemname LIKE '%" . $key . "%' or
                                  item.barcode LIKE '%" . $key . "%' or
                                  brand.brand_desc LIKE '%" . $key . "%' or
                                  model.model_name LIKE '%" . $key . "%' or
                                  part.part_name LIKE '%" . $key . "%' or
                                  icat.name LIKE '%" . $key . "%' or
                                  grp.stockgrp_name LIKE '%" . $key . "%' or
                                  cls.cl_name LIKE '%" . $key . "%' or
                                  item.body LIKE '%" . $key . "%' or
                                  item.sizeid LIKE '%" . $key . "%' or 
                                  item.subcode LIKE '%" . $key . "%' or
                                  item.partno LIKE '%" . $key . "%' or
                                  item.color LIKE '%" . $key . "%' or
                                  item.sizeid LIKE '%" . $key . "%')";
          } //end if
        } //end for each

        break;
      default:


        switch ($companyid) {
          case 22: //eipi
            switch (strtoupper($doc)) {
              case 'PO':
              case 'RR':
                $criteria = " where item.isinactive = 0 and supp.client = '" . $config['params']['client'] . "' ";
                break;
              default:
                $criteria = " where item.isinactive = 0 ";
                break;
            }
            break;

          case 24: // good found
            switch (strtoupper($doc)) {
              case 'PO':
              case 'RR':
                $criteria = " where item.isinactive = 0 and item.isofficesupplies = 1 ";
                break;
              case 'PU':
              case 'RU':
                $criteria = " where item.isinactive = 0 and item.fg_isequipmenttool = 1 ";
                break;
              default:
                $criteria = " where item.isinactive = 0 ";
                break;
            }
            break;

          case 35: //aquamax
            $criteria = " where item.clientid = 0 ";
            break;
          case 40: //cdo
            if ($doc <> 'STOCKCARD') {
              $criteria = " where item.isinactive = 0 and partno <>'' ";
            } else {
              $criteria = " where item.isinactive = 0 ";
            }

            break;
          case 19: //housegem
            switch (strtoupper($doc)) {
              case 'PO':
                $clientid = $this->coreFunctions->datareader("select clientid as value from client where client='" . $config['params']['client'] . "'");
                $criteria = " where item.isinactive = 0 and sp.clientid = $clientid ";
                break;
              default:
                goto default_filter_here;
                break;
            }
            break;

          case 56: //homeworks
            switch (strtoupper($doc)) {
              case 'PO':
              case 'RR':
              case 'DM':
                $itemtype = "";
                if (isset($config['params']['itemfilter']['isfa'])) {
                  $itemtype = " and isfa=" . $config['params']['itemfilter']['isfa'];
                }
                $criteria = " where item.isinactive = 0 and supp.client = '" . $config['params']['client'] . "'" . $itemtype;
                break;
              default:
                goto default_filter_here;
                break;
            }
            break;

          default:
            default_filter_here:
            if ($isclientitem) {
              switch (strtoupper($doc)) {
                case 'SJ':
                case 'RR':
                  $criteria = " where item.isinactive = 0 and supp.client = '" . $config['params']['client'] . "' ";
                  break;
                default:
                  $criteria = " where item.isinactive = 0 ";
                  break;
              }
            } else {
              if ($config['params']['action'] == 'searchitem2') {
                $criteria = " where item.isinactive = 0 and item.fg_isequipmenttool=1 ";
              } else {
                $criteria = " where item.isinactive = 0 ";
              }
            }

            break;
        }

        $ispurchases = $this->companysetup->getispurchases($config['params']);

        switch ($moduletype) {
          case 'PURCHASE':
            if ($doc == 'OS') {
              $criteria .= " and item.isoutsource=1 ";
            } else {
              if ($systemtype == 'FAMS' && $ispurchases) {
                $criteria .=  " and item.isfa=0 ";
              } else {
                if ($companyid == 56 && ($doc == 'PO' || $doc == 'RR' || $doc == 'DM')) { //homeworks
                } else {
                  $criteria .= " and item.isfa=0 and item.isgeneric=0";
                }
              }
            }
            break;
          case 'OUTSOURCE':
            $criteria .= " and item.isoutsource=1 ";
            break;
          case 'GENERICITEM';
            $criteria .= " and item.isgeneric=1 ";
            break;
          case 'FIXEDASSET':
          case 'FAMS':
            if ($doc == 'FC') goto defaultitemhere;
            $criteria .= " and item.isfa=1 ";
            break;
          default:
            defaultitemhere:
            $addonfilter = ' ';
            if ($companyid == 16 && $doc == 'IS') { //ati
              $criteria .=  $addonfilter;
            } elseif ($companyid == 16 && $doc == 'BARCODEASSIGNING') { //ati
              $criteria .= " and item.isfa=0 " . $addonfilter;
            } elseif ($companyid == 42 && $doc == 'TS') { //pdpi mis
              $wh = $config['params']['itemfilter']['wh'];
              $criteria .= " and wh.client ='" . $wh . "' and item.isfa=0 and rr.bal<>0 " . $addonfilter;
            } else {
              $criteria .= " and item.isfa=0 " . $addonfilter;
            }
            break;
        }

        if (isset($config['params']['search'])) {
          switch ($companyid) {
            case 47: //kitchenstar
              $searchfield = ['item.itemname', 'item.barcode', 'brand.brand_desc', 'model.model_name', 'part.part_name', 'icat.name',  'grp.stockgrp_name', 'cls.cl_name', 'item.body', 'item.sizeid', 'item.subcode', 'item.partno', 'item.othcode', 'item.shortname', 'isub.name', 'item.color'];
              break;
            case 40: //cdo
              $searchfield = ['item.itemname', 'item.barcode', 'brand.brand_desc', 'model.model_name', 'icat.name',  'item.shortname', 'isub.name', 'item.partno', 'item.body'];
              break;
            default:
              $searchfield = ['item.itemname', 'item.barcode', 'brand.brand_desc', 'model.model_name', 'part.part_name', 'icat.name',  'grp.stockgrp_name', 'cls.cl_name', 'item.body', 'item.sizeid', 'item.subcode', 'item.partno', 'item.othcode', 'item.shortname'];
              break;
          }

          $search = $config['params']['search'];
          $criteria .= $this->othersClass->multisearch($searchfield, $search);
        }
        break;
    }

    if ($config['params']['search'] != "" && $companyid != 7) {
      $limit = "";
    }

    $qrysave = "";
    $rowcount = $this->companysetup->getitembatch($config['params']);
    if ($companyid == 42 && $doc == 'TS') { //pdpi mis
      $qrysave = $qry . " " . $criteria . "  group by item.itemname,item.barcode,item.itemid,grp.stockgrp_name,item.uom order by item.itemname asc ";

      $qry = $qry . " " . $criteria . "  group by item.itemname,item.barcode,item.itemid,grp.stockgrp_name,item.uom order by item.itemname asc " . $limit;
    } else {
      if ($rowcount != 0) {
        $limit = "limit 0," . $rowcount;
      }
      $qrysave = $qry . "  " . $criteria . "  order by item.itemname asc ";
      $qry = $qry . "  " . $criteria . "  order by item.itemname asc " . $limit;
    }
    //$this->coreFunctions->LogConsole($qry . '--this');
    $data = $this->coreFunctions->opentable($qry);

    if ($rowcount != 0) {
      $cdata = count($data);
      if ($cdata < $rowcount) {
        return ['data' => $data, 'path' => '', 'callback' => false, 'rowcount' => 0];
      } else {
        $lresult = $this->filesaving->lookupqrysave($config, $qrysave);
        return ['data' => $data, 'path' => $lresult['filename'], 'callback' => true, 'rowcount' => $rowcount];
      }
    } else {
      return ['data' => $data, 'path' => '', 'callback' => false, 'rowcount' => 0];
    }
  } //end search

  public function generalItemSearch($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $moduletype = $config['params']['moduletype'];
    $doc = $config['params']['doc'];
    $addedfields = "";
    $itemname = "";

    $qry = "select 
    '' as category,
    1 as factor, 0 as amt, '' as body,
    '' as part ,ifnull(model.model_name,'') as model,
    '' as disc, 0 as netprice,
    genitem.sizeid,
    genitem.barcode,
    genitem.line as itemid, 
    genitem.itemname,
    genitem.uom, 
    brand.brand_desc as brand, genitem.brandid,
    groups.stockgrp_name as groupname, genitem.groupid,
    model.model_name as model, genitem.modelid,
    classi.cl_name as class, genitem.classid
    from generalitem as genitem
    left join frontend_ebrands as brand on brand.brandid = genitem.brandid
    left join stockgrp_masterfile as groups on groups.stockgrp_id = genitem.groupid
    left join model_masterfile as model on model.model_id = genitem.modelid
    left join item_class as classi on classi.cl_id = genitem.classid";

    $keyword = explode(",", $config['params']['search']);
    $criteria = "";
    $limit = " limit 500";

    foreach ($keyword as $key) {
      if ($criteria == "") {
        $criteria = " where (
          genitem.itemname LIKE '%" . $key . "%' or
          genitem.barcode LIKE '%" . $key . "%' or
          brand.brand_desc LIKE '%" . $key . "%' or
          model.model_name LIKE '%" . $key . "%' or
          groups.stockgrp_name LIKE '%" . $key . "%' or
          classi.cl_name LIKE '%" . $key . "%' or
          genitem.sizeid LIKE '%" . $key . "%')";
      } else {
        $criteria = $criteria . "  and (
          genitem.itemname LIKE '%" . $key . "%' or
          genitem.barcode LIKE '%" . $key . "%' or
          brand.brand_desc LIKE '%" . $key . "%' or
          model.model_name LIKE '%" . $key . "%' or
          groups.stockgrp_name LIKE '%" . $key . "%' or
          classi.cl_name LIKE '%" . $key . "%' or
          genitem.sizeid LIKE '%" . $key . "%'";
      } //end if
    } //end for each

    if ($config['params']['search'] != "") {
      $limit = "";
    }

    $qry = $qry . " " . $criteria . " order by genitem.itemname asc " . $limit;

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end search


  public function fitemsearch($config)
  {
    $qry = "select itemname,barcode from item";
    $limit = " limit 500";
    $keyword = explode(",", $config['params']['search']);
    $criteria = "";
    foreach ($keyword as $key) {
      if ($criteria == "") {
        $criteria = " where (barcode LIKE '%" . $key . "%' or itemname LIKE '%" . $key . "%') and isfa=1 ";
      } else {
        $criteria = $criteria . "  and (barcode LIKE '%" . $key . "%' or itemname LIKE '%" . $key . "%') and isfa=1";
      } //end if
    } //end for each
    if ($config['params']['search'] != "") {
      $limit = "";
    }
    $qry = $qry . " " . $criteria . " order by itemname asc " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end search


  public function modelSearch($config)
  {

    $qry = "select 0 as modelid, '' as modelcode, '' as modelname union all
    select model.model_id as modelid,
    model.model_code as modelcode,
    model.model_name as modelname
    from model_masterfile as model";

    $limit = " limit 500";

    $keyword = explode(",", $config['params']['search']);
    $criteria = "";
    foreach ($keyword as $key) {
      if ($criteria == "") {
        $criteria = " where (model.model_code LIKE '%" . $key . "%' or
                              model.model_name LIKE '%" . $key . "%')";
      } else {
        $criteria = $criteria . "  and (model.model_code LIKE '%" . $key . "%' or
        model.model_name LIKE '%" . $key . "%')";
      } //end if
    } //end for each

    if ($config['params']['search'] != "") {
      $limit = "";
    }
    $qry = $qry . " " . $criteria . " order by modelname asc " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end search


  public function itembalance($config)
  {

    $company = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $filter = '';
    if ($company == 1) { //vitaline
      if ($doc == 'SJ') {
        $filter = 'and rrstatus.whid!=1831';
      }
    }
    $bal = 'FORMAT(sum(rrstatus.bal),2) as bal';
    $join = '';

    switch ($company) {
      case 27: //nte
      case 36: //rozlab
        if ($doc == 'SJ') {
          $bal = 'FORMAT(sum(rrstatus.bal/uom.factor),4) as bal';
        } else {
          $bal = 'FORMAT(sum(rrstatus.bal/uom.factor),2) as bal';
        }

        $join = 'left join uom on uom.itemid=rrstatus.itemid and uom.uom=rrstatus.uom';
        break;
    }

    switch ($company) {
      case 46:
        $qry = "select wh.client as wh,wh.clientname as whname,rrstatus.loc,
              $bal,rrstatus.expiry,item.uom,il.min,il.max,
              ifnull(pallet.`name`,'') as pallet, ifnull(location.loc,'') as location,
              FORMAT(rrstatus.cost,2) as cost,rrstatus.dateid
              from rrstatus
              left join client as wh on wh.clientid = rrstatus.whid
              left join item on item.itemid=rrstatus.itemid 
              left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
              left join pallet on pallet.line=rrstatus.palletid
              left join location on location.line=rrstatus.locid
              $join
              where item.itemid = " . $config['params']['itemid'] . " and rrstatus.bal>0 $filter
              group by wh.client,wh.clientname,rrstatus.loc,rrstatus.expiry,item.uom,il.min,il.max,pallet.`name`,
              location.loc,rrstatus.cost,rrstatus.dateid
              order by rrstatus.dateid";

        break;
      case 60: //transpower
        $year = date('Y');
        $qry = "select rrstatus.wh,rrstatus.clientname as whname,'' as loc,
              sum(rrstatus.qty-rrstatus.iss) as bal,'' as expiry,rrstatus.uom,'' as min,'' as max,
             '' as pallet, '' as location
              from 
                (select sum(stock.iss) as iss,sum(stock.qty) as qty,stock.itemid,stock.whid,client.clientname,client.client as wh,'' as loc,item.uom
                from lastock as stock left join lahead as head on head.trno = stock.trno
                left join client on client.clientid = stock.whid
                left join cntnum on cntnum.trno=stock.trno
                left join item on item.itemid=stock.itemid
                where stock.itemid =" .  $config['params']['itemid'] . " and year(head.dateid)= $year 
                group by client.clientname,stock.whid,stock.itemid,item.uom,client.client
                union all
                select sum(stock.iss) as iss,sum(stock.qty) as qty,stock.itemid,stock.whid,client.clientname,client.client as wh,'' as loc,item.uom
                from glstock as stock left join glhead as head on head.trno = stock.trno
                left join client on client.clientid = stock.whid
                left join cntnum on cntnum.trno=stock.trno
                left join item on item.itemid=stock.itemid
                where stock.itemid =" .  $config['params']['itemid'] . " and year(head.dateid)= $year  
                group by client.clientname,stock.whid,stock.itemid,item.uom,client.client) as rrstatus
              group by clientname,itemid,uom,wh having sum(rrstatus.qty-rrstatus.iss)<>0";
        break;
      default:
        $qry = "select wh.client as wh,wh.clientname as whname,rrstatus.loc,
              $bal,rrstatus.expiry,item.uom,il.min,il.max,
              ifnull(pallet.`name`,'') as pallet, ifnull(location.loc,'') as location
              from rrstatus
              left join client as wh on wh.clientid = rrstatus.whid
              left join item on item.itemid=rrstatus.itemid left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
              left join pallet on pallet.line=rrstatus.palletid
              left join location on location.line=rrstatus.locid
              $join
              where item.itemid = " . $config['params']['itemid'] . " and rrstatus.bal>0 $filter
              group by wh.client,wh.clientname,rrstatus.loc,rrstatus.expiry,item.uom,il.min,il.max,pallet.`name`,
              location.loc";
        break;
    }

    return $this->coreFunctions->opentable($qry);
  } //end function


  public function detailSearch($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];

    $qry = "
      select acnoid,acno,acnoname,left(alias,2) as alias from coa
    ";
    $filter = "";
    $keyword = explode(",", $config['params']['search']);
    $criteria = "";
    $limit = " limit 500";

    foreach ($keyword as $key) {
      if ($criteria == "") {
        $criteria = " where detail=1 and (
                       coa.acnoname like '%" . $key . "%' or
                       coa.acno like '%" . $key . "%' or
                       coa.alias like '%" . $key . "%')";
      } else {
        $criteria = $criteria . "  and (
                       coa.acnoname like '%" . $key . "%' or
                       coa.acno like '%" . $key . "%' or
                       coa.alias like '%" . $key . "%')
        ";
      }
    }



    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        switch ($doc) {
          case 'SU':
            $filter = " and alias = 'WT2'";
            break;
        }
        break;
      case 8: //maxipro
        $filter = " and isinactive=0 ";
        break;
    }

    if ($config['params']['search'] != "") {
      $limit = "";
    }

    $qry = $qry . " " . $criteria . " " . $filter . " order by parent,acno,L1+L2+L3+L4+L5+L6+L7 " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end function

  public function ClientSearch($config, $doc = 'customer')
  {
    $companyid = $config['params']['companyid'];
    $filter = '';
    switch ($doc) {
      case 'customer':
        $filter = " client.iscustomer = 1";
        break;
      case 'supplier':
        $filter = " client.issupplier = 1";
        break;
      case 'allclienthead':
      case 'lookupgjclient':
      case 'allclientdetail':
        $filter = " client.iscustomer=1 or client.issupplier=1 or client.isemployee =1 or client.istenant =1 ";
        break;
      case 'employee':
        $filter = " client.isemployee = 1";
        break;
    }

    $qry = "select client.clientid,client.client,client.clientname,client.client as whto,
                   client.clientname as whtoname,client.addr,client.wh,ifnull(f.cur,'P') as cur,
                   ifnull(f.curtopeso,1) as forex,client.terms,ifnull(a.client,'') as agentcode, 
                   ifnull(a.clientname,'') as agentname, client.shipid, client.billid, 
                   client.tin, client.billcontactid,client.shipcontactid,
                  ifnull(client.deptid, 0) as deptid,
                  ifnull(dept.client, ' ') as dept,
                  ifnull(dept.clientname, ' ') as deptname,client.position,client.groupid,
                  ifnull(a.clientid,0) as agentid, ifnull(terms.days,0) as days,client.vattype,client.tax
            from client 
            left join forex_masterfile as f on f.line = client.forexid 
            left join client as a on a.client = client.agent 
            left join client as dept on dept.clientid = client.deptid
            left join terms on terms.terms = client.terms ";


    //$filter = "";
    $keyword = explode(",", $config['params']['search']);
    $criteria = "";
    $limit = " limit 500";

    foreach ($keyword as $key) {
      if ($criteria == "") {
        $criteria = " where (" . $filter . ") and client.isinactive =0 and (
                       client.client like '%" . $key . "%' or
                       client.addr like '%" . $key . "%' or
                       client.clientname like '%" . $key . "%')";
      } else {
        $criteria = $criteria . "  and (
                       client.client like '%" . $key . "%' or
                       client.addr like '%" . $key . "%' or
                       client.clientname like '%" . $key . "%')
        ";
      }
    }


    if ($config['params']['search'] != "") {
      $limit = "";
    }

    $qry = $qry . " " . $criteria . "  order by client.client,client.clientname " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end function

  public function CustomerSearch($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];

    $qry = "select client.clientid,client.client,client.clientname,client.client as whto,
                   client.clientname as whtoname,client.addr,client.wh,ifnull(f.cur,'P') as cur,
                   ifnull(f.curtopeso,1) as forex,client.terms,ifnull(a.client,'') as agentcode, 
                   ifnull(a.clientname,'') as agentname, client.shipid, client.billid, 
                   client.tin, client.billcontactid,client.shipcontactid,
                  ifnull(client.deptid, 0) as deptid,
                  ifnull(dept.client, ' ') as dept,
                  ifnull(dept.clientname, ' ') as deptname,client.position,client.groupid,
                  ifnull(a.clientid,0) as agentid, ifnull(terms.days,0) as days
            from client 
            left join forex_masterfile as f on f.line = client.forexid 
            left join client as a on a.client = client.agent 
            left join client as dept on dept.clientid = client.deptid
            left join terms on terms.terms = client.terms ";


    $filter = "";
    $keyword = explode(",", $config['params']['search']);
    $criteria = "";
    $limit = " limit 500";

    foreach ($keyword as $key) {
      if ($criteria == "") {
        $criteria = " where (client.iscustomer=1 or client.isstudent=1 or client.istenant=1) and client.isinactive =0 and (
                       client.client like '%" . $key . "%' or
                       client.addr like '%" . $key . "%' or
                       client.clientname like '%" . $key . "%')";
      } else {
        $criteria = $criteria . "  and (
                       client.client like '%" . $key . "%' or
                       client.addr like '%" . $key . "%' or
                       client.clientname like '%" . $key . "%')
        ";
      }
    }


    if ($config['params']['search'] != "") {
      $limit = "";
    }

    $qry = $qry . " " . $criteria . " " . $filter . " order by client.client,client.clientname " . $limit;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } //end function


  //cv
  public function getpendingcdsummary($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $lookupclass = $config['params']['lookupclass'];
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];

    $trnxtype = '';
    $filter = "";
    switch ($companyid) {
      case 3: //conti
      case 16: //ati
        if (isset($config['params']['client'])) {
          $filter .= " and head.client = '" . $config['params']['client'] . "'";
        }
        break;
    }

    $addedjoin = '';
    switch ($doc) {
      case 'OQ':
        $addedjoin .= " left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline left join hprhead as hpr on hpr.trno=info.trno left join reqcategory as cat on cat.line=hpr.ourref ";
        $filter .= ' and ((stock.qty-stock.voidqty) > stock.oqqa) and stock.status=1 and cat.isoracle=1 ';
        break;
      case 'PO':
        if ($companyid == 16) { //ati
          if ($adminid != 0) {
            $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
            $filter .= " and info.trnxtype='" . $trnxtype . "' ";
            $addedjoin = "left join hheadinfotrans as info on info.trno=head.trno";
          }
          $filter .= ' and stock.void = 0 and stock.qty>(stock.qa+stock.voidqty) and stock.status=1 and head.iscanvassonly=0';
        } else {
          goto defaulthere;
        }
        break;
      default:
        defaulthere:
        $filter .= ' and stock.void = 0 and stock.qty>stock.qa and stock.status=1';
        break;
    }

    if ($companyid == 16) { //ati
      $filter .= ' and stock.approveddate2 is not null ';
    }

    if ($lookupclass == 'pendingcdsummaryshortcut') {
      $qry = "select stock.trno,FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
      head.docno,left(head.dateid,10) as dateid,head.client, head.clientname,head.ourref,stock.ref
          from hcdhead as head 
          left join hcdstock as stock on stock.trno = head.trno 
          left join transnum on transnum.trno = head.trno
          where and transnum.center = ? stock.qty>stock.qa and stock.void = 0 and stock.status=1 
          group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.ourref,stock.ref";
    } else {
      $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
        FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref, head.clientname
        from hcdhead as head 
        left join hcdstock as stock on stock.trno = head.trno 
        left join transnum on transnum.trno = head.trno " . $addedjoin . "
        where transnum.center = ?  and stock.status=1 " . $filter . "
        group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname";
    }
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingprsummaryshortcut($config)
  {
    $companyid = $config['params']['companyid'];
    $filter_served = '';
    if ($companyid == 39) { //CBBSI
      $filter_served = 'and stock.qty>(stock.qa+stock.cdqa)';
    }
    $center = $config['params']['center'];
    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname,
      FORMAT(sum((stock.qa+stock.cdqa)*stock.cost)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref
      from hprhead as head left join hprstock as stock on stock.trno = head.trno  left join item on item.itemid = stock.itemid left join transnum on transnum.trno = head.trno
      where transnum.doc='PR' and transnum.center = ? $filter_served
      and ifnull(item.islabor,0) = 0 and stock.void = 0
      group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.yourref order by head.dateid desc";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  }
  public function getpendingprsummary($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $isproject = $this->companysetup->getisproject($config['params']);
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];
    $s = 'RQ';

    $filter = "";
    $pendingqa = "(stock.qa+stock.cdqa)";
    $addleftjoin = "";
    $admin = $this->othersClass->checkAccess($config['params']['user'], 3767);
    $void = "and stock.void = 0";

    switch ($companyid) {
      case 8: //maxipro
        if ($doc == "PO" || $doc == "JO") {
          if ($config['params']['lookupclass'] != "pendingjr_yourref" && $config['params']['lookupclass'] != "pendingpr_yourref") {
            $yourref = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
            if ($yourref != "") {
              $filter .= " and head.docno = '" . $yourref . "'";
            }
          }

          if ($config['params']['lookupclass'] == "lookupsetup") {
            $yourref = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "yourref", "trno=?", [$config['params']['trno']]);
            if ($yourref != "") {
              $filter .= " and head.docno = '" . $yourref . "'";
            }
          }
        }
        break;

      case 16: //ati
        switch ($doc) {
          case "CD":
            if ($admin) {
              $filter .= " and stock.status = 0";
            } else {
              $filter .= " and stock.status = 0 and (stock.suppid=0 or (stock.status=0 and stock.suppid=" . $config['params']['adminid'] . "))";
            }
            if ($adminid != 0) {
              $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
              $filter .= " and headinfo.trnxtype = '" . $trnxtype . "' ";
              $addleftjoin = "left join hheadinfotrans as headinfo on headinfo.trno=head.trno";
            }
            $pendingqa = "(stock.qa+stock.cdqa+stock.voidqty)";
            break;
          case "OQ":
            $pendingqa = "(stock.qa+stock.oqqa+stock.cdqa+stock.voidqty)";
            break;
          case "SS":
            $pendingqa = "(stock.qa+stock.voidqty)";
            $void = "";
            break;
          case "RR":
            if ($adminid != 0) {
              $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
              $filter .= " and headinfo.trnxtype = '" . $trnxtype . "' ";
              $addleftjoin = "left join hheadinfotrans as headinfo on headinfo.trno=head.trno";
            }
            $filter .= " and stock.status = 18"; //with stock
            $pendingqa = "(stock.qa+stock.rrqa+stock.voidqty)";
            break;
          case "PO":
            if ($adminid != 0) {
              $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
              $filter .= " and headinfo.trnxtype = '" . $trnxtype . "' ";
              $addleftjoin = "left join hheadinfotrans as headinfo on headinfo.trno=head.trno";
            }
            $filter .= " and stock.status = 43"; //payment only
            $pendingqa = "(stock.qa+stock.poqa+stock.voidqty)";
            break;
        }

        break;
    }

    if ($isproject) {
      $trno = $config['params']['trno'];
      $project = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "projectid", "trno=?", [$trno]);
      $subproject = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "subproject", "trno=?", [$trno]);

      if ($doc == 'JO') {
        $s = 'JR';
      }

      if ($doc == "PO" || $doc == "JO" || $doc == "MT") {
        if ($config['params']['lookupclass'] == "pendingjr_yourref" || $config['params']['lookupclass'] == "pendingpr_yourref") {
          $project = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : 0;
          $subproject = isset($config['params']['addedparams'][1]) ? $config['params']['addedparams'][1] : 0;
        }
      }

      $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname,
            FORMAT(sum(stock.qa*stock.cost)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref
            from hprhead as head left join hprstock as stock on stock.trno = head.trno left join transnum on transnum.trno = head.trno 
            where head.doc='" . $s . "' and head.projectid=? and head.subproject =? and stock.qty>(stock.qa+stock.siqa) and transnum.center = ? and stock.void = 0
            " . $filter . "
            group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.yourref";

      $this->coreFunctions->LogConsole($qry . $project . '-' . $subproject);
      $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $center]);
    } else {
      switch ($doc) {
        case 'SU':
          $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname,
            FORMAT(sum(stock.qa*stock.cost)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref
            from hprhead as head left join hprstock as stock on stock.trno = head.trno left join transnum on transnum.trno = head.trno
            where transnum.doc='PR' and stock.qty > stock.siqa  and transnum.center = ? and stock.void = 0
            " . $filter . "
            group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.yourref";
          $data = $this->coreFunctions->opentable($qry, [$center]);
          break;
        case 'JB':
          $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname,
            FORMAT(sum(" . $pendingqa . "*stock.cost)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref
            from hprhead as head left join hprstock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid left join transnum on transnum.trno = head.trno
            where transnum.doc='PR' and stock.qty>" . $pendingqa . " and transnum.center = ? and item.islabor = 1 and stock.void = 0
            " . $filter . "
            group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.yourref";
          $data = $this->coreFunctions->opentable($qry, [$center]);
          break;
        default:
          if ($companyid == 40) { //cdo
            $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname,
            FORMAT(sum(" . $pendingqa . "*stock.cost)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref
            from hprhead as head left join hprstock as stock on stock.trno = head.trno  left join item on item.itemid = stock.itemid left join transnum on transnum.trno = head.trno $addleftjoin
            where transnum.doc='PR' and stock.qty>" . $pendingqa . "  and ifnull(item.islabor,0) = 0 and stock.void = 0
            " . $filter . "
            group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.yourref";
            $data = $this->coreFunctions->opentable($qry);
          } else {
            $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname,
            FORMAT(sum(" . $pendingqa . "*stock.cost)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref
            from hprhead as head left join hprstock as stock on stock.trno = head.trno  left join item on item.itemid = stock.itemid left join transnum on transnum.trno = head.trno $addleftjoin
            where transnum.doc='PR' and stock.qty>" . $pendingqa . " and transnum.center = ? and ifnull(item.islabor,0) = 0 " . $void . "
            " . $filter . "
            group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.yourref";
            $data = $this->coreFunctions->opentable($qry, [$center]);
          }


          break;
      }
    }

    return $data;
  } // end function

  public function getpendingmrsummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];

    $all = false;
    switch ($config['params']['lookupclass']) {
      case 'pendingmrsummaryshortcut':
        $all = true;
        break;
    }

    $filterclient = ' and head.client = ?';
    $filterxqa = ' and stock.iss>stock.qa';
    $arrfilter = [];
    if ($all) {
      $filterclient = ' ';
      $arrfilter = [$center];
    } else {
      $arrfilter = [$center, $client];
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
    format(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.yourref, head.ourref, head.client, head.clientname, head.rem, head.address, head.terms, left(head.due,10) as due,
    wh.clientname as whname,project.code as project
    from hmrhead as head
    left join hmrstock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.client=head.wh
    left join projectmasterfile as project on project.line=head.projectid 
    where transnum.center = ? $filterxqa and stock.void=0 and head.doc = 'MR' " . $filterclient . "
    group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref, head.client,head.clientname,head.rem,head.address, 
    head.terms, head.due, wh.clientname,project.code";

    $data = $this->coreFunctions->opentable($qry, $arrfilter);

    return $data;
  } // end function

  public function getpendingcddetails($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];

    $addedfields = '';
    $addedjoin = '';
    $sort = '';

    $qafield = 'qa';
    $voidqtyfld = '';
    $filter = "";
    switch ($companyid) {
      case 3: //conti
      case 16: //ati
        if ($doc != 'OQ') {
          $filter .= " and head.client = '" . $config['params']['client'] . "'";
        }
    }

    switch ($companyid) {
      case 16: //ati
        $addedfields = ", info.itemdesc, date_format(info.ovaliddate,'%m-%d-%Y') as deadline, hpr.clientname, sa.sano, FORMAT(stock.cost-stock.oqpa," . $this->companysetup->getdecimal('price', $config['params']) . ") as pendingamt, info.ctrlno";
        $addedjoin = ' left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline left join hprhead as hpr on hpr.trno=info.trno left join clientsano as sa on sa.line=hpr.sano';
        $sort = ' order by info.ovaliddate desc, info.itemdesc';

        switch ($doc) {
          case 'PO':
            if ($adminid != 0) {
              $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
              $filter .= " and headinfo.trnxtype='" . $trnxtype . "' ";
              $addedjoin .= " left join hheadinfotrans as headinfo on headinfo.trno=head.trno ";
            }
            $voidqtyfld = ' + stock.voidqty';
            $filter .= " and head.iscanvassonly=0";
            break;
        }

        break;
    }

    switch ($doc) {
      case 'OQ':
        $addedfields .= ", cat.category";
        $addedjoin .= " left join reqcategory as cat on cat.line=hpr.ourref ";
        $filter .= " and ((stock.qty-stock.voidqty)>stock.oqqa) and stock.status=1 and cat.isoracle=1";
        $qafield = 'oqqa';
        $voidqtyfld = ' + stock.voidqty';
        break;
      default:

        $filter .= " and stock.void = 0 and stock.qty>(stock.qa + stock.voidqty) and stock.status=1";
        break;
    }

    if ($companyid == 16) { //ati
      $filter .= ' and stock.approveddate2 is not null ';
    }

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,stock.ref,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
              FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT(((stock." . $qafield . ") / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.qty-(stock." . $qafield . $voidqtyfld . "))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref " . $addedfields . "
              from hcdhead as head
              right join hcdstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid " . $addedjoin . "
              where transnum.center = ?  " . $filter . "" . $sort;

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function


  public function getpendingprdetails($config)
  {
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $isproject = $this->companysetup->getisproject($config['params']);
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];
    $s = 'RQ';

    $addedfields = '';
    $addleftjoin = '';
    $sort = '';
    $pendingqa = '(stock.qa+stock.cdqa)';

    $admin = $this->othersClass->checkAccess($config['params']['user'], 3767);

    $filter = "";
    if ($companyid == 8) { //maxipro
      if ($doc == "PO" || $doc == "MT") { //|| $doc == "JO"
        if ($config['params']['lookupclass'] != "pendingjr_yourref" && $config['params']['lookupclass'] != "pendingpr_yourref") {
          $yourref = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "yourref", "trno=?", [$config['params']['trno']]);
          if ($yourref != "") {
            $filter .= " and head.docno = '" . $yourref . "'";
          }
        }
      }
    }

    switch ($companyid) {
      case 16: //ati
        $void = " and stock.void = 0 ";
        switch ($doc) {
          case "CD":
            $addleftjoin = ' left join hheadinfotrans as headinfo on headinfo.trno=head.trno';
            if ($adminid != 0) {
              $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
              $filter .= " and headinfo.trnxtype = '" . $trnxtype . "' ";
            }
            if ($doc == "CD") {
              $pendingqa = '(stock.qa+stock.cdqa)';
              if ($admin) {
                $filter .= " and stock.status = 0";
              } else {
                $filter .= " and stock.status = 0 and (stock.suppid=0 or (stock.status=0 and stock.suppid=" . $config['params']['adminid'] . "))";
              }
            }
            $sort = ' order by info.ovaliddate desc, info.itemdesc';
            break;

          case "OQ":
            $addedfields = ',cat.category, sa.sano';
            $addleftjoin = ' left join reqcategory as cat on cat.line=head.ourref 
                             left join clientsano as sa on sa.line=head.sano';
            $pendingqa = '(stock.qa+stock.oqqa+stock.cdqa)';
            $filter .= " and stock.iscanvass = 0 and cat.isoracle = 1"; //and head.ourref <> '1'
            break;
          case "RR":
            if ($adminid != 0) {
              $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
              $filter .= " and headinfo.trnxtype = '" . $trnxtype . "' ";
              $addleftjoin .= " left join hheadinfotrans as headinfo on headinfo.trno=head.trno ";
            }

            $filter .= " and stock.status = 18 ";
            $pendingqa = '(stock.qa+stock.rrqa)';
            break;
          case "PO":
            if ($adminid != 0) {
              $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
              $filter .= " and headinfo.trnxtype = '" . $trnxtype . "' ";
              $addleftjoin .= " left join hheadinfotrans as headinfo on headinfo.trno=head.trno ";
            }
            $filter .= " and stock.status = 43 ";
            $pendingqa = '(stock.qa+stock.poqa)';
            break;
          case 'SS':
            $void = "";
            $pendingqa = '(stock.qa)';
            $wh = $this->coreFunctions->getfieldvalue('lahead', 'wh', 'trno=?', [$config['params']['trno']]);
            $deptid = $this->coreFunctions->getfieldvalue('lahead', 'deptid', 'trno=?', [$config['params']['trno']]);
            $addedfields = ", ifnull(sa.sano, '') as sano, ifnull(svs.sano, '') as svsno, ifnull(po.sano, '') as pono,
            ifnull((select group_concat(distinct poh.yourref) from hpostock as pos left join hpohead as poh on poh.trno=pos.trno where pos.void=0 and pos.reqtrno=stock.trno and pos.reqline=stock.line),'') as actualpo,left(info.osiref,15) as osino";

            $addleftjoin = ' left join clientsano as sa on sa.line=head.sano left join clientsano as svs on svs.line=head.svsno left join clientsano as po on po.line=head.pono left join trxstatus as stat on stat.line=stock.status';
            $oracc = $this->othersClass->checkAccess($config['params']['user'], 4386);
            if ($oracc != 1) {
              $filter .= " and head.client='" . $config['params']['client'] . "' and head.wh = '" . $wh . "' and head.deptid = '" . $deptid . "' ";
            }

            $filter .= " and (stat.status <> 'Payment Only' or stat.status is null) ";
            break;
        }
        break;
    }

    if ($isproject) {
      $trno = $config['params']['trno'];

      if ($doc == 'MT') {
        $project = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "projectto", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "subprojectto", "trno=?", [$trno]);
      } else {
        $project = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "projectid", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "subproject", "trno=?", [$trno]);
      }


      if ($doc == 'JO') {
        $s = 'JR';
      }

      $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
                  FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                  FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
                  FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
                  FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
                  FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
                  FORMAT(((stock.qa+stock.siqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                  FORMAT(((stock.qty-(stock.qa+stock.siqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
                  stock.loc,head.yourref,s.stage,stock.rem
                  from hprhead as head
                  right join hprstock as stock on stock.trno = head.trno
                  left join item on item.itemid=stock.itemid
                  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                  left join transnum on transnum.trno = head.trno
                  left join client as wh on wh.clientid=stock.whid
                  left join stagesmasterfile as s on s.line = stock.stageid
                  left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                  where head.doc='" . $s . "' and head.projectid=? and head.subproject =? and stock.qty>(stock.qa+stock.siqa) and transnum.center = ? and stock.void = 0 
                  " . $filter . "";

      $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $center]);
    } else {

      switch (strtoupper($doc)) {
        case 'SU':
          $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
          FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
          FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
          FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
          FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
          FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
          FORMAT(((stock.qa+stock.cdqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
          FORMAT(((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
          stock.loc,head.yourref,s.stage,stock.rem " . $addedfields . "
          from hprhead as head
          right join hprstock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join transnum on transnum.trno = head.trno
          left join client as wh on wh.clientid=stock.whid
          left join stagesmasterfile as s on s.line = stock.stageid " . $addleftjoin . "
          where transnum.doc='PR' and  (stock.qa+stock.cdqa)>0 and stock.qty > stock.siqa and transnum.center = ? and stock.void = 0 
          " . $filter . "";
          $data = $this->coreFunctions->opentable($qry, [$center]);
          break;
        case 'JB':
          $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
          FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
          FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
          FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
          FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
          FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
          FORMAT(((stock.qa+" . $pendingqa . ") / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
          FORMAT(((stock.qty-" . $pendingqa . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
          stock.loc,head.yourref,s.stage,stock.rem " . $addedfields . "
          from hprhead as head
          right join hprstock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join transnum on transnum.trno = head.trno
          left join client as wh on wh.clientid=stock.whid
          left join stagesmasterfile as s on s.line = stock.stageid " . $addleftjoin . "
          where transnum.doc='PR' and stock.qty>" . $pendingqa . " and item.islabor = 1 and transnum.center = ? and stock.void = 0 
          " . $filter . "" . $sort;
          $data = $this->coreFunctions->opentable($qry, [$center]);
          break;

        default:

          if ($companyid != 16) { //not ati
            $filter .= " and ifnull(item.islabor,0) = 0 ";
          }

          $filtercenter = " and transnum.center = '" . $center . "'";
          if ($companyid == 16) { //ati

            $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
                FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
                FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
                FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
                FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
                FORMAT(((" . $pendingqa . ") / case when ifnull(uom3.factor,0)<>0 then uom3.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                FORMAT(((stock.qty-" . $pendingqa . "-stock.voidqty)/ case when ifnull(uom3.factor,0)<>0 then uom3.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
                stock.loc,head.yourref,s.stage,stock.rem,info.specs, info.itemdesc, date(info.ovaliddate) as deadline,info.ctrlno,info.requestorname 
                " . $addedfields . "
                from hprhead as head
                left join hprstock as stock on stock.trno = head.trno
                left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                left join item on item.itemid=stock.itemid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                left join transnum on transnum.trno = head.trno
                left join client as wh on wh.clientid=stock.whid
                left join stagesmasterfile as s on s.line = stock.stageid " . $addleftjoin . "
                left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1
                left join uomlist as uom2 on uom2.uom=info.uom2 and uom2.isconvert=1
                where transnum.doc='PR' and stock.qty>(" . $pendingqa . "+stock.voidqty) " . $filtercenter . " " . $void . "
                " . $filter . "" . $sort;
          } else {
            if ($companyid == 40) { //cdo
              $filtercenter = "";
            }
            $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
                FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
                FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
                FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
                FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
                FORMAT(((" . $pendingqa . ") / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                FORMAT(((stock.qty-" . $pendingqa . "-stock.voidqty)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
                stock.loc,head.yourref,s.stage,stock.rem " . $addedfields . "
                from hprhead as head
                left join hprstock as stock on stock.trno = head.trno
                left join item on item.itemid=stock.itemid
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                left join transnum on transnum.trno = head.trno
                left join client as wh on wh.clientid=stock.whid
                left join stagesmasterfile as s on s.line = stock.stageid " . $addleftjoin . "
                where transnum.doc='PR' and stock.qty>(" . $pendingqa . "+stock.voidqty) " . $filtercenter . " and stock.void = 0 
                " . $filter . "" . $sort;
          }
          $data = $this->coreFunctions->opentable($qry);
          break;
      }
      // $this->coreFunctions->LogConsole($qry);

    }
    return $data;
  } // end function

  public function getpendingatdetails($config)
  {
    $center = $config['params']['center'];
    $adminid = $config['params']['adminid'];

    $filtercenter = " and transnum.center = '" . $center . "'";

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,
                  left(head.dateid,10) as dateid,item.barcode,FORMAT(stock.rrqty,2) as rrqty,FORMAT(stock.qty,2) as qty,
                  FORMAT(stock.rrcost,2) as rrcost,stock.disc,FORMAT(stock.cost,2) as cost,
                  FORMAT(stock.ext,2) as ext,wh.client as wh,
                  stock.loc,head.yourref,stock.rem
            from hathead as head
            left join hatstock as stock on stock.trno = head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join transnum on transnum.trno = head.trno
            left join client as wh on wh.clientid=stock.whid
            where transnum.doc='AT' " . $filtercenter . " and stock.void = 0 and stock.ispc=0";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  } // end function


  public function getpendingatsummary($config)
  {
    $center = $config['params']['center'];

    $filter = "";
    $addleftjoin = "";

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt, head.yourref
            from hathead as head 
            left join hatstock as stock on stock.trno = head.trno 
            left join item on item.itemid = stock.itemid 
            left join transnum on transnum.trno = head.trno $addleftjoin
            where transnum.doc='AT' and transnum.center = ? 
             and stock.void = 0 and stock.ispc=0
            group by stock.trno,head.docno,head.dateid,head.client,head.clientname,head.yourref";
    $data = $this->coreFunctions->opentable($qry, [$center]);

    return $data;
  } // end function

  public function getpendingmrdetails($config)
  {
    $center = $config['params']['center'];
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
    FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
    FORMAT(((stock.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.iss-(stock.qa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,
    project.code as project
    from hmrhead as head
    right join hmrstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    left join projectmasterfile as project on project.line=head.projectid 
    where transnum.doc='MR' and  stock.iss>(stock.qa) and transnum.center = ? and stock.void = 0 and head.client = ?";
    $data = $this->coreFunctions->opentable($qry, [$center, $client]);

    return $data;
  } // end function

  public function getpendingopsummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];

    $all = false;
    switch ($config['params']['lookupclass']) {
      case 'pendingopsummaryshortcut':
        $all = true;
        break;
    }

    $filterclient = ' and stock.iss>stock.qa and head.client = ? ';

    $arrfilter = [];
    if ($all) {
      $filterclient = ' and stock.iss>stock.qa ';


      $arrfilter = [$center];
    } else {
      $arrfilter = [$center, $client];
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
              FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
              head.yourref, head.clientname, head.rem
              from hophead as head
              left join hopstock as stock on stock.trno = head.trno
              left join transnum on transnum.trno = head.trno
              left join item on item.itemid = stock.itemid
              where transnum.center = ?  " . $filterclient . "
              group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.rem";

    return $this->coreFunctions->opentable($qry, $arrfilter);
  }

  public function getpendingposummary($config)
  {
    $companyid = $config['params']['companyid'];
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $all = false;
    switch ($config['params']['lookupclass']) {
      case 'pendingposummaryshortcut':
        $all = true;
        break;
    }

    $systemtype = $this->companysetup->getsystemtype($config['params']);

    switch (strtoupper($systemtype)) {
      case 'CAIMS':
        $trno = $config['params']['trno'];
        $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);

        $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref
            from hpohead as head
            left join hpostock as stock on stock.trno = head.trno
            left join transnum on transnum.trno = head.trno
            where head.projectid=? and head.subproject =? and head.client = ? and  stock.qty>stock.qa
            and transnum.center = ?
            and stock.void = 0
            group by stock.trno,head.docno,head.dateid,head.yourref";
        $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $client, $center]);

        break;

      default:
        switch ($doc) {
          case 'PL':
            $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
              head.yourref, head.clientname, head.rem, head.wh
              from hpohead as head
              left join hpostock as stock on stock.trno = head.trno
              left join transnum on transnum.trno = head.trno
              where stock.qty>stock.qa and transnum.center = ? and stock.void = 0
              group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.rem, head.wh";
            $data = $this->coreFunctions->opentable($qry, [$center]);
            break;

          default:
            switch ($companyid) {
              case 24: //goodfound
                $filterclient = ' and head.client = ? ';
                $arrfilter = [];
                if ($all) {
                  $filterclient = ' ';
                  $arrfilter = [$center];
                } else {
                  $arrfilter = [$center, $client];
                }

                $pdoc = "";
                switch ($doc) {
                  case 'RU':
                    $pdoc = " and head.doc = 'PU' ";
                    break;

                  case 'RR':
                    $pdoc = "  and head.doc = 'PO' ";
                    break;
                }
                $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
                    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
                    head.yourref, head.clientname, head.rem
                    from hpohead as head
                    left join hpostock as stock on stock.trno = head.trno
                    left join transnum on transnum.trno = head.trno
                    where transnum.center = ? and  stock.qty>stock.qa and stock.void = 0 and stock.isreturn=0 $pdoc " . $filterclient . "
                    group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.rem";

                $data = $this->coreFunctions->opentable($qry, $arrfilter);
                break;

              default:
                $bfxpdate = $this->othersClass->getCurrentDate();
                $expdate = "";
                $filterclient = ' and head.client = ? ';
                if ($companyid == 56 && $doc == 'RR') { // homeworks
                  $expdate = " and '$bfxpdate' <= date_add(date(head.expiration), INTERVAL 2 DAY) ";
                }

                $centerfilter = "transnum.center=?";
                $filterxqa = "";
                switch ($companyid) {
                  case 16: //ati
                    $filterxqa = "and stock.qty>stock.qa and (stock.qty-stock.voidqty) <> 0";
                    break;
                  case 39: //cbbsi
                    if ($doc == 'DI') {
                      $filterxqa = "and stock.qty>stock.diqa and stock.void = 0";
                    }
                    break;
                  case 60: //transpower
                    if ($doc == 'SJ') {
                      $all = true;
                      $wh = $this->companysetup->getwh($config['params']);
                      $centerfilter = " transnum.center <> ? and wh.client = '" . $wh . "' ";
                      $filterxqa = "and stock.qty>stock.sjqa and stock.void=0 ";
                    } else {
                      $filterxqa = "and stock.qty>stock.qa and stock.void=0";
                    }
                    break;
                  default:
                    $filterxqa = "and stock.qty>stock.qa and stock.void = 0";
                    break;
                }


                $arrfilter = [];
                if ($all) {
                  $filterclient = ' ';
                  $arrfilter = [$center];
                } else {
                  $arrfilter = [$center, $client];
                }

                $pdoc = "";
                if ($companyid == 39) { //cbbsi
                  if ($doc == 'DI') {
                    $pdoc = "  and head.doc = 'PO' ";
                  }

                  if ($doc == 'RR') {
                    $pdoc = "  and head.doc in ('PO','RT') ";
                  }
                }

                $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
                  FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
                  head.yourref,head.ourref, head.clientname, head.rem, head.address, head.terms, 
                  left(head.due,10) as due,
                  wh.clientname as whname, head.vattype
                  from hpohead as head
                  left join hpostock as stock on stock.trno = head.trno
                  left join transnum on transnum.trno = head.trno
                  left join client as wh on wh.client=head.wh
                  where $centerfilter  $filterxqa  and stock.isreturn=0  $pdoc " . $filterclient . " $expdate
                  group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref, head.clientname, head.rem, head.address, 
                  head.terms, head.due, wh.clientname, head.vattype";
                $data = $this->coreFunctions->opentable($qry, $arrfilter);
                break;
            } // end switch companyid

            break; // end default

        } // end switch systemtype

        break;
    }

    return $data;
  } // end function


  public function geteggitemlists($config)
  {
    $center = $config['params']['center'];

    $all = false;
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $arrfilter = [];
    if ($all) {
      $filterclient = "";
      $arrfilter = [$center];
    }

    $filter = "";
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];

    switch ($doc) {
      case 'AJ':
        $chqry = "select itemid from lastock where trno = ?";
        break;

      default:
        $chqry = "select itemid from sostock where trno = ?";
        break;
    }

    $checking = $this->coreFunctions->opentable($chqry, [$trno]);
    if (!empty($checking)) {
      $filter = " and i.itemid not in (";
      foreach ($checking as $key => $value) {
        $filter .= $value->itemid . ",";
      }
      $filter = rtrim($filter, ",");
      $filter .= ")";
    }

    $qry = "select i.itemid,i.barcode,i.itemname,i.uom
            from item as i
            left join itemcategory as cat on i.category= cat.line
            where cat.name = 'Egg'  $filter ";

    $data = $this->coreFunctions->opentable($qry, $arrfilter);

    return $data;
  } // end function


  public function getpendingpodetailsperserial($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno as refx,stock.line as linex,item.itemname,head.docno,head.docno as ref,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,stock.disc,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,
              0 as trno, 0 as line,stock.rem
              from hpohead as head
              right join hpostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where head.client = ? and stock.qty>stock.qa
              and transnum.center = ?
              and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingoqsummary($config)
  {

    $center = $config['params']['center'];

    $qry = "select stock.trno,stock.trno as refx,0 as linex,head.docno,left(head.dateid,10) as dateid,
            FORMAT(sum(stock.rrqty)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
            FORMAT(sum(stock.qty)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
            FORMAT(sum((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
            FORMAT(sum(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end))," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending
            from hoqhead as head
            right join hoqstock as stock on stock.trno = head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join transnum on transnum.trno = head.trno
            left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
            where
            stock.qty>stock.qa
            and transnum.center = ? and stock.void = 0
            group by stock.trno,head.docno,head.dateid";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingoqdetails($config)
  {

    $center = $config['params']['center'];

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno as refx,stock.line as linex,item.itemname,head.docno,head.docno as ref,left(head.dateid,10) as dateid,item.barcode,
            FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
            FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
            stock.uom,item.itemid,
            FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
            FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
            0 as trno, 0 as line,stock.rem,ifnull(xinfo.specs,'') as specs, pr.itemdesc,if(stock.refx<>0,pr2.ctrlno,pr.ctrlno) as ctrlno 
            from hoqhead as head
            right join hoqstock as stock on stock.trno = head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join transnum on transnum.trno = head.trno
            left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
            left join hstockinfotrans as pr on pr.trno=stock.reqtrno and pr.line=stock.reqline
            left join hstockinfotrans as pr2 on pr2.trno=stock.refx and pr2.line=stock.linex
            where
            stock.qty>stock.qa
            and transnum.center = ? and stock.void = 0";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getcriticalstocks($config)
  {

    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);

    $qry = "select itemid as keyid, itemid, barcode, itemname, uom, FORMAT(critical," . $qtydec . ") as critical, FORMAT(reorder," . $qtydec . ") as reorder, 
      FORMAT(bal," . $qtydec . ") as bal, FORMAT(sobal," . $qtydec . ") as sobal, FORMAT(pobal," . $qtydec . ") as pobal from (
      select item.itemid, item.barcode, item.itemname, item.uom, item.critical, item.reorder,
      ifnull((select sum(rs.bal) from rrstatus as rs where rs.bal<>0 and rs.itemid=item.itemid),0) as bal,
      
      ifnull((select sum(sobal) from (
      select so.itemid, sum(so.sobal) as sobal from (
      select so.itemid, ifnull(((so.iss-so.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end),0) as sobal
      from hsastock as so left join uom on uom.uom=so.uom and uom.itemid=so.itemid where so.iss>so.qa
      union all
      select so.itemid, ifnull(((so.iss-so.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end),0) as sobal
      from hsbstock as so left join uom on uom.uom=so.uom and uom.itemid=so.itemid where so.iss>so.qa
      union all
      select so.itemid, ifnull(((so.iss-so.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end),0) as sobal
      from hscstock as so left join uom on uom.uom=so.uom and uom.itemid=so.itemid where so.iss>so.qa) as so group by so.itemid) as sob where sob.itemid=item.itemid),0) as sobal,
      
      ifnull((select ifnull(sum(pobal),0) as pobal from (
      select po.itemid, ifnull(((po.qty-po.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end),0) as pobal
      from postock as po left join uom on uom.uom=po.uom and uom.itemid=po.itemid where po.qty>po.qa
      union all
      select po.itemid, ifnull(((po.qty-po.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end),0) as pobal
      from hpostock as po left join uom on uom.uom=po.uom and uom.itemid=po.itemid where po.qty>po.qa ) as po  where po.itemid=item.itemid),0) as pobal
      
      from item where item.critical<>0) as item where (bal - sobal) <= critical";

    return $this->coreFunctions->opentable($qry);
  }

  public function getpendingpodetails($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];

    switch (strtoupper($systemtype)) {
      case 'CAIMS':
        $trno = $config['params']['trno'];
        $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);

        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,head.terms,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
              FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
              stock.loc,head.yourref,st.stage,stock.reqtrno,stock.reqline
              from hpohead as head
              right join hpostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              left join stagesmasterfile as st on st.line = stock.stageid              
              where head.projectid=? and head.subproject =? and head.client = ? and stock.qty>stock.qa
              and transnum.center = ?
              and stock.void = 0 ";

        $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $client, $center]);
        break;

      default:

        // $filterqa = ' and stock.qty>stock.qa ';

        $voidqtyfld = '';

        if ($companyid == 39 && $doc == 'DI') { //cbbsi
          $filterqa = ' and stock.qty>stock.diqa ';
        } else if ($companyid == 60 && $doc == 'SJ') { //transpower
          $filterqa = ' and stock.qty>stock.sjqa ';
        } else {
          $filterqa = ' and stock.qty>stock.qa ';
        }

        $filterclient = '';
        if ($client != '') {
          $filterclient = " and head.client = '" . $client . "'";
        }

        $filtercenter = 'transnum.center=?';
        if ($companyid == 60 && $doc == 'SJ') { //transpower
          $wh = $this->companysetup->getwh($config['params']);
          $filtercenter = "transnum.center<>? and wh.client = '" . $wh . "' ";
          $filterclient = '';
        }

        $addedfields = '';
        $addleftjoin = '';
        $groupby = '';

        if ($companyid == 16) { // ati
          $addedfields = ", info.itemdesc, ifnull(cvnum.docno,'') as cvref,head.tax,head.vattype,hinfo.pdeadline,stock.reqtrno,stock.reqline,ifnull(info.ctrlno,'') as ctrlno,sum(poinfo.amt1 + poinfo.amt2 + poinfo.amt3 + poinfo.amt4 + poinfo.amt5) as addtlfees";
          $addleftjoin = " left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline left join hstockinfotrans as poinfo on poinfo.trno=stock.trno and poinfo.line=stock.line left join cntnum as cvnum on cvnum.trno=stock.cvtrno ";
          $groupby = " group by stock.trno,stock.line,stock.trno,stock.line,item.itemname,head.docno,head.terms,head.dateid,item.barcode,stock.rrqty,
                            stock.qty,stock.rrcost,stock.disc,stock.cost,stock.ext,wh.client,stock.uom,item.itemid,
                            stock.qa ,uom.factor, stock.voidqty,stock.loc,head.yourref,stock.isadv, head.cur,
                            head.forex,om.paymenttype,info.itemdesc, cvnum.docno,
                            head.tax,head.vattype,hinfo.pdeadline,stock.reqtrno,stock.reqline,info.ctrlno,stock.cdrefx, stock.cdlinex ";
        }

        if ($companyid == 39) { //cbbsi
          $addedfields = ", FORMAT(((stock.qty-stock.diqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as dipending,
                            FORMAT((stock.diqa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as diqa";
        }

        if ($config['params']['lookupclass'] == 'pendingporeqpaydetail') {
          $filterclient .= " and stock.isadv=1 and stock.cvtrno=0";
          $filterqa = '';
        }


        $filterx = "";
        switch ($companyid) {
          case 24: //goodfound
            switch ($doc) {
              case 'RU':
                $filterx .= " and head.doc = 'PU' ";
                break;

              case 'RR':
                $filterx .= " and head.doc = 'PO' ";
                break;
            }
            break;
          case 39: //cbbsi
            switch ($doc) {
              case 'DI':
                $filterx .= " and head.doc = 'PO' ";
                break;
            }
            break;
          case 16: //ati
            switch ($doc) {
              case 'RR':
              case 'CV':
                if ($adminid != 0) {
                  $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
                  $filterx = " and hinfo.trnxtype='" . $trnx . "' ";
                }
                $voidqtyfld = ' + stock.voidqty';
                break;
            }

            break;
        }

        $pending = "";
        if ($companyid == 16) { //ati
          $addedfields .= ",FORMAT(sum(poinfo.amt1 + poinfo.amt2 + poinfo.amt3 + poinfo.amt4 + poinfo.amt5 + ((stock.qty - stock.voidqty)*stock.cost))," . $this->companysetup->getdecimal('price', $config['params']) . ") as ext,stock.cdrefx, stock.cdlinex";
          $filterx .= " and (stock.qty-stock.voidqty) <> 0 ";
          $pending = " stock.qty-stock.voidqty ";
        } else if ($companyid == 60 && $doc == 'SJ') { //transpower
          $addedfields .= ",FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext";
          $filterx .= " and stock.void = 0 ";
          $pending = " stock.qty-(stock.sjqa " . $voidqtyfld . ")";
        } else {
          $addedfields .= ",FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext";
          $filterx .= " and stock.void = 0 ";
          $pending = " stock.qty-(stock.qa " . $voidqtyfld . " )";
        }

        $bfxpdate = $this->othersClass->getCurrentDate();
        $expdate = "";
        if ($companyid == 56 && $doc == 'RR') { // homeworks
          $expdate = " and '$bfxpdate' <= date_add(date(head.expiration), INTERVAL 2 DAY) ";
        }

        $qafield = "FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,";
        if ($companyid == 60 && $doc == 'SJ') { //transpower
          $qafield = "FORMAT((stock.sjqa / case when ifnull(uom.factor,2)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa, ";
        }

        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,head.terms,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
              FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
              wh.client as wh,stock.uom,item.itemid,wh.client as wh,stock.uom,item.itemid,
              " . $qafield . "
              FORMAT((( " . $pending . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
              stock.loc,head.yourref, 
              (case when stock.isadv=1 then 'Yes' else 'No' end) as isadv, head.cur, head.forex,
              om.paymenttype as paymentname
              " . $addedfields . "
              from hpohead as head
              right join hpostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join hheadinfotrans as hinfo on hinfo.trno=head.trno
              left join othermaster as om on om.line = hinfo.paymentid
              left join client as wh on wh.clientid=stock.whid " . $addleftjoin . "
              where " . $filtercenter . "  and stock.isreturn=0 " . $filterqa . $filterclient . $filterx . $expdate . $groupby;

        $data = $this->coreFunctions->opentable($qry, [$center]);
        break;
    }


    return $data;
  } // end function




  public function getpaymentreleased($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $trno = $config['params']['trno'];

    $qry = "select info.trno,info.releasedate,det.line,cv.docno,cv.client,cv.clientname
    from cntnuminfo as info
    left join lahead as cv on cv.trno=info.trno
    left join detailinfo as det on det.trno=info.trno
    where info.releasedate is not null and info.trno=? and info.trno in (select voiddetail.trno from voiddetail where trno=?)";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);

    return $data;
  }

  public function getpendingplsummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.plno,head.shipmentno,head.invoiceno
    from hplhead as head
    left join hplstock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    where stock.qty>stock.qa and transnum.center = ?
    group by stock.trno,head.docno,head.dateid,head.plno,head.shipmentno,head.invoiceno";

    $data = $this->coreFunctions->opentable($qry, [$center]);

    return $data;
  } // end function

  public function getpendingpldetails($config, $trno)
  {
    $center = $config['params']['center'];

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
    FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
    FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
    FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc
    from hplhead as head
    left join hplstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    where head.trno=? and stock.qty>stock.qa and transnum.center = ? and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $center]);
    return $data;
  } // end function

  public function getpendingrrsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $condition = '';
    switch ($config['params']['companyid']) {
      case 8: //maxipro
        $trno = $config['params']['trno'];
        $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);
        $condition = " and head.projectid= $project and head.subproject = $subproject ";
        break;
    }

    $qafilter = '';
    if ($config['params']['companyid'] == 39) { //cbbsi
      $qafilter = " and stock.qty>rrstatus.qa2";
    } else {
      $qafilter = "and stock.qty>rrstatus.qa";
    }
    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid, FORMAT(sum(stock.ext)," . $extdec . ") as totalamt, head.yourref
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
            where head.doc='RR' $condition and client.client = ?  $qafilter and cntnum.center = ? and stock.void = 0
            group by stock.trno,head.docno,head.dateid,head.yourref";

    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingrrsnsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid, FORMAT(sum(stock.ext)," . $extdec . ") as totalamt, head.yourref
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
            where head.doc='RR' and client.client = ? and cntnum.center = ? and stock.void = 0 and cntnum.svnum = 0 
            group by stock.trno,head.docno,head.dateid,head.yourref";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingrrsmsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid, FORMAT(sum(stock.ext)," . $extdec . ") as totalamt, head.yourref
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
            left join hstockinfo as info on info.trno = stock.trno and info.line = stock.line
            where head.doc='RR' and info.isbo <> 1 and client.client = ? and cntnum.center = ? and stock.void = 0 and rrstatus.qty>rrstatus.qa2
            group by stock.trno,head.docno,head.dateid,head.yourref";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingrpsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid, FORMAT(sum(stock.ext)," . $extdec . ") as totalamt, head.yourref
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
            where head.doc='RP' and  stock.qty>rrstatus.qa and cntnum.center = ? and stock.void = 0
            group by stock.trno,head.docno,head.dateid,head.yourref
            ";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingbrdetails($config)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $blsrrcost = $this->coreFunctions->datareader("select rrcost as value from blstock where trno = $trno ");
    if (!empty($blsrrcost)) {
      $refx = " and bls.refx <> ''";
      $where = "where (amount - blsrrcost) is null or balance <> 0";
      $balance = "(amount - sum(blsrrcost))";
    } else {
      $refx = "";
      $where = "";
      $balance = "ext";
    }
    $qry = "select line,trno,docno,dateid,start,end,particulars,rrcost,amount,blsrrcost,balance,ext,rem,bltrno from (
    select line,trno,docno,dateid,start,end,particulars,rrcost,amount,sum(blsrrcost) as blsrrcost,$balance as balance,ext,rem,bltrno
            from(select stock.line,head.trno,head.docno,head.dateid,head.start,head.end,
                        stock.particulars,stock.rrcost,stock.amount,
                        stock.ext,stock.rem,head.bltrno,bls.rrcost as blsrrcost
              from hbrhead as head
              left join hbrstock as stock on stock.trno=head.trno
              left join blstock as bls on bls.trno=head.bltrno and bls.linex = stock.line
              where head.bltrno = ? and stock.status = 1 
              group by stock.line,head.trno,head.docno,head.dateid,head.start,head.end,
                        stock.particulars,stock.rrcost,stock.amount,stock.ext,stock.rem,head.bltrno,bls.rrcost) as a
                        group by line,trno,docno,dateid,start,end,particulars,rrcost,amount,ext,rem,bltrno) tb
            $where";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  } // end function

  public function getsubactivitydetails($config)
  {
    $trno = $config['params']['trno'];

    $head = "select distinct head.projectid,head.subproject,head.stageid,ss.line as substage
    from sohead as head
    left join activity as a on a.subproject=head.subproject and a.stage=head.stageid
    left join substages as ss on ss.stage=head.stageid and ss.line=a.line
    where head.trno = $trno";
    $projhead = json_decode(json_encode($this->coreFunctions->opentable($head)), true);

    $projectid = $projhead[0]['projectid'];
    $subproject = $projhead[0]['subproject'];
    $stageid = $projhead[0]['stageid'];
    $substage = $projhead[0]['substage'];


    $qry = "select psub.subactid,sub.subactivity,sub.description,psub.subproject,psub.stage,
    psub.substage,proj.line as projectid,psub.line as subactivityid,
    concat(psub.subactid,', ',sub.subactivity,' ',sub.description) as sub
    from psubactivity as psub
    left join subactivity as sub on sub.line=psub.line and sub.stage=psub.stage and sub.substage= psub.substage
    left join stages on stages.stage= psub.stage and stages.subproject=psub.subproject and stages.trno=psub.trno
    left join projectmasterfile as proj on proj.line=stages.projectid
    where psub.subproject=? and psub.stage =? and proj.line=?
    order by psub.subactid,sub.subactivity";

    $data = $this->coreFunctions->opentable($qry, [$subproject, $stageid, $projectid]);

    return $data;
  } // end function

  public function getpendingrrdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);
    $amtdec = $this->companysetup->getdecimal('price', $config['params']);
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $condition = '';
    switch ($config['params']['companyid']) {
      case 8: //maxipro
        $trno = $config['params']['trno'];
        $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);
        $condition = " and head.projectid= $project and head.subproject = $subproject ";
        break;
    }

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,
          head.docno,left(head.dateid,10) as dateid,item.barcode,
          FORMAT(stock.rrqty," . $qtydec . ") as rrqty,
          FORMAT(stock.qty," . $qtydec . ") as qty,
          FORMAT(stock.rrcost," . $amtdec . ") as rrcost,stock.disc,
          FORMAT(stock.cost," . $amtdec . ") as cost,
          FORMAT(stock.ext," . $extdec . ") as ext,wh.client as wh,
          FORMAT((rrstatus.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $qtydec . ") as qa,
          FORMAT((stock.qty-rrstatus.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $qtydec . ") as pending,
          stock.loc,head.yourref,stock.expiry,stock.ref,hph.yourref 
          from glhead as head
          right join glstock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join cntnum on cntnum.trno = head.trno
          left join client on client.clientid=head.clientid
          left join client as wh on wh.clientid=stock.whid
          left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
          left join hpostock as hps on hps.trno=stock.refx and hps.line=stock.linex
          left join hpohead as hph on hph.trno=hps.trno
          where head.doc = 'RR' $condition and client.client = ? and stock.qty>rrstatus.qa and cntnum.center = ? and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingrrsmdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);
    $amtdec = $this->companysetup->getdecimal('price', $config['params']);
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $condition = '';

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,
        head.docno,left(head.dateid,10) as dateid,item.barcode,
        FORMAT(stock.rrqty," . $qtydec . ") as rrqty,
        FORMAT(stock.qty," . $qtydec . ") as qty,
        FORMAT(stock.rrcost," . $amtdec . ") as rrcost,stock.disc,
        FORMAT(stock.cost," . $amtdec . ") as cost,
        FORMAT(stock.ext," . $extdec . ") as ext,wh.client as wh,
        FORMAT((rrstatus.qa2 / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $qtydec . ") as qa,
        FORMAT((stock.qty-rrstatus.qa2 / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $qtydec . ") as pending,
        stock.loc,head.yourref,stock.expiry,stock.ref,hph.yourref 
        from glhead as head
        right join glstock as stock on stock.trno = head.trno
        left join item on item.itemid=stock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join cntnum on cntnum.trno = head.trno
        left join client on client.clientid=head.clientid
        left join client as wh on wh.clientid=stock.whid
        left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
        left join hstockinfo as info on info.trno = stock.trno and info.line = stock.line
        left join hpostock as hps on hps.trno=stock.refx and hps.line=stock.linex
        left join hpohead as hph on hph.trno=hps.trno
        where head.doc = 'RR' and info.isbo<>1 and client.client = ? and rrstatus.qty>rrstatus.qa2 and cntnum.center = ? and stock.void = 0 ";

    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingrpdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);
    $amtdec = $this->companysetup->getdecimal('price', $config['params']);
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
          FORMAT(stock.rrqty," . $qtydec . ") as rrqty,
          FORMAT(stock.qty," . $qtydec . ") as qty,
          FORMAT(stock.rrcost," . $amtdec . ") as rrcost,stock.disc,
          FORMAT(stock.cost," . $amtdec . ") as cost,
          FORMAT(stock.ext," . $extdec . ") as ext,wh.client as wh,
          FORMAT((rrstatus.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $qtydec . ") as qa,
          FORMAT((stock.qty-rrstatus.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $qtydec . ") as pending,stock.loc,head.yourref,stock.expiry
          from glhead as head
          right join glstock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join cntnum on cntnum.trno = head.trno
          left join client on client.clientid=head.clientid
          left join client as wh on wh.clientid=stock.whid
          left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
          where head.doc = 'RP' and stock.qty>rrstatus.qa and cntnum.center = ? and stock.void = 0 ";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingtrsummary($config)
  {
    $client = '';
    $wh = '';
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : 0;
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $condition = '';
    switch ($doc) {
      case 'SS':
      case 'ST':
        if ($companyid == 40 && $doc == 'ST') { //cdo
          $filter = $this->coreFunctions->opentable("select c.code as dept,wh as client from lahead as h left join center as c on c.line = h.deptid where h.trno=?", [$trno]);
        } else {
          $filter = $this->coreFunctions->opentable("select client as dept,wh as client from lahead where trno=?", [$trno]);
        }
        //$filter = $this->coreFunctions->opentable("select client as dept,wh as client from lahead where trno=?", [$trno]);
        break;
      case 'PR':
        $filter = $this->coreFunctions->opentable("select client as dept, wh as client from prhead where trno=?", [$trno]);
        break;
      case 'TS':
        if ($trno != 0) {
          goto def;
        } else {
          $filter = '';
        }
        break;
      default:
        def:
        $filter = $this->coreFunctions->opentable("select head.client,client.client as dept from lahead as head left join client on client.clientid=head.deptid where head.trno=?", [$trno]);
        break;
    }
    if (!empty($filter)) {
      $client = $filter[0]->dept;
      $wh = $filter[0]->client;
    }
    switch ($doc) {
      case 'SS':
        $condition = " and head.client='" . $client . "'";
        break;
      case 'ST':
        if ($companyid == 40) { //cdo
          $condition = " and head.wh='" . $wh . "' and head.client ='" . $client . "'";
        } else {
          $condition = " and head.wh='" . $wh . "' ";
        }

        break;
      case 'RM':
        $condition = " and stock.refx='" . $config['params']['addedparams'][0] . "'";
        break;
      case 'TS':
        $condition = " and head.client='" . $client . "' and head.wh='" . $wh . "'";
        if ($companyid == 43) { // mighty
          if ($trno != 0) {
            $condition = " and head.client='" . $client . "' and head.wh='" . $wh . "' and head.approvedate is not null";
          } else {
            $condition = "and head.approvedate is not null";
          }
        }
        break;
      default:
        $condition = " and head.client='" . $client . "' and head.wh='" . $wh . "'";
        break;
    }

    if ($companyid == 40) { //cdo
      $qry = "select head.doc,head.docno, date(head.dateid) as dateid,head.trno, head.wh,
            sum(round(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as totalamt, head.yourref,concat(head.cur,' ',head.forex) as cur,c.name as branch
            FROM htrhead as head
            left join htrstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom = stock.uom
            left join client as warehouse on warehouse.clientid=stock.whid
            left join center as c on c.code = head.client
            where stock.qty>stock.qa " . $condition . " group by head.doc,head.docno,head.dateid,head.trno,head.yourref,head.cur,head.forex,head.wh,c.name";
    } else {
      $qry = "select head.doc,head.docno, date(head.dateid) as dateid,head.trno, head.wh,
            sum(round(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ")) as totalamt, head.yourref,concat(head.cur,' ',head.forex) as cur
            FROM htrhead as head
            left join htrstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom = stock.uom
            left join client as warehouse on warehouse.clientid=stock.whid
            where stock.qty>stock.qa " . $condition . " group by head.doc,head.docno,head.dateid,head.trno,head.yourref,head.cur,head.forex,head.wh";
    }
    $data = $this->coreFunctions->opentable($qry);
    $this->coreFunctions->LogConsole($qry);
    return $data;
  }

  public function getpendingtrdetails($config)
  {

    $client = '';
    $wh = '';
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];

    if ($doc == 'RM') {
      $pdtrno = $this->coreFunctions->getfieldvalue('lahead', 'pdtrno', 'trno=?', [$trno]);
      $condition = " and stock.refx='" . $pdtrno . "'";
    } else {
      switch ($doc) {
        case 'SS':
        case 'TS':
        case 'ST';
          $filter = $this->coreFunctions->opentable("select client as dept,wh as client from lahead where trno=?", [$trno]);
          break;
        case 'PR':
          $filter = $this->coreFunctions->opentable("select client as dept, wh as client from prhead where trno=?", [$trno]);
          break;
        default:
          $filter = $this->coreFunctions->opentable("select client,dept from lahead where trno=?", [$trno]);
          break;
      }

      if ($filter) {
        $client = $filter[0]->dept;
        $wh = $filter[0]->client;
      }

      switch ($doc) {
        case 'SS':
          $condition = " and head.client='" . $client . "'";
          break;
        case 'TS':
        case 'ST':
          $condition = " and head.wh='" . $wh . "' ";
          break;
        default:
          $condition = " and head.client='" . $client . "' and head.wh='" . $wh . "'";
          break;
      }
    }


    $qry = "select concat(stock.trno,stock.line) as keyid, 0 as rrqty, 0 as qty, 0 as amt,htrhead.docno, htrhead.yourref, date(htrhead.dateid) as dateid, item.itemid,stock.trno, stock.line, item.barcode,
    item.itemname, stock.uom, stock.cost, (stock.qty-stock.qa) as iss,
    round(stock.rrcost," . $this->companysetup->getdecimal('currency', $config['params']) . ") as rrcost,
    round(stock.rrqty," . $this->companysetup->getdecimal('quantity', $config['params']) . ") as isqty,
    round(((stock.qty-stock.qa) / case when ifnull(uom.factor,0)=0 then 1 else
    uom.factor end) * stock.rrcost," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate, stock.disc, stock.void,
    round((stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom
    .factor end," . $this->companysetup->getdecimal('quantity', $config['params']) . ") as qa,
    round(((stock.qty-stock.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('quantity', $config['params']) . ") as pending,
    stock.ref,warehouse.client as whcode,warehouse.clientname as wh,stock.loc,item.brand,
    stock.rem,case when ifnull(uom.factor,0)=0 then 1 else uom.factor end as uomfactor,
    '' as expiry,htrhead.trpricegrp
    FROM htrhead
    left join htrstock as stock on stock.trno=htrhead.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join htrhead as head on head.trno=stock.trno
    where stock.void <> 1 and stock.qty>stock.qa " . $condition;

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function getbouncedardetail($config)
  {

    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    $qry = "select concat(stock.trno,stock.line) as keyid,head.doc,head.docno,stock.trno,stock.line,
                   cntnum.center, stock.amount,stock.bank,stock.branch,date(stock.checkdate) as checkdate,
                   stock.checkno,stock.clientid,client.client,client.clientname
            from hparticulars as stock
            left join glhead as head on head.trno = stock.trno
            left join client on client.clientid=stock.clientid
            left join cntnum on cntnum.trno = head.trno
            where head.doc='BE' and cntnum.center=" . $center . " and stock.retrno=0";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  // KEN
  public function getpendingpnsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid, FORMAT(sum(stock.ext)," . $extdec . ") as totalamt, head.yourref
      from glhead as head
      right join glstock as stock on stock.trno = head.trno
      left join cntnum on cntnum.trno = head.trno
      left join client on client.clientid=head.clientid
      left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
      where head.doc='PN' and stock.qty>rrstatus.qa and cntnum.center = ? and stock.void = 0
      group by stock.trno,head.docno,head.dateid,head.yourref";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingpndetails($config)
  {
    $center = $config['params']['center'];
    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);
    $amtdec = $this->companysetup->getdecimal('price', $config['params']);
    $extdec = $this->companysetup->getdecimal('currency', $config['params']);

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,
      head.docno, left(head.dateid, 10) as dateid, item.barcode,
      format(stock.rrqty, " . $qtydec . ") as rrqty,
      format(stock.qty, " . $qtydec . ") as qty,
      format(stock.rrcost, " . $amtdec . ") as rrcost, stock.disc,
      format(stock.cost, " . $amtdec . ") as cost,
      format(stock.ext, " . $extdec . ") as ext, wh.client as wh,
      format((rrstatus.qa / case when ifnull(uom.factor, 0) = 0 then 1 else uom.factor end), " . $qtydec . ") as qa,
      format((stock.qty - rrstatus.qa / case when ifnull(uom.factor, 0) = 0 then 1 else uom.factor end), " . $qtydec . ") as pending,
      stock.loc, head.yourref, stock.expiry, stock.ref, hph.yourref 
      from glhead as head
      left join glstock as stock on stock.trno = head.trno
      left join item on item.itemid = stock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
      left join cntnum on cntnum.trno = head.trno
      left join client on client.clientid = head.clientid
      left join client as wh on wh.clientid = stock.whid
      left join rrstatus on rrstatus.trno = stock.trno and rrstatus.line = stock.line
      left join hprstock as hps on hps.trno = stock.refx and hps.line = stock.linex
      left join hprhead as hph on hph.trno = hps.trno
      where head.doc = 'PN' and stock.qty>rrstatus.qa and cntnum.center = ? and stock.void = 0 ";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpcitems($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select itemid from hpcstock where trno= ?";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  } // end function

  public function adjustpc($config, $data)
  {

    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = 'hpcstock';
    $msq = [];
    $msg1 = '';
    $remmsg = '';
    $msg2 = 'Cannot Adjust! There are unposted documents: ';
    $ajdocnno = '';

    $asofdate = $this->coreFunctions->getfieldvalue("hpchead", "dateid", "trno=?", [$trno]);
    if ($asofdate == '') {
      $asofdate = $this->othersClass->getCurrentDate();
    }
    $asofdate = date('Y-m-d', strtotime($asofdate));

    foreach ($data as $key => $value) {
      $itemid = $data[$key]->itemid;
      $qry = "select group_concat(head.docno) as docno from lahead as head left join lastock as stock on stock.trno=head.trno where stock.itemid= ? and head.dateid<='" . $asofdate . "' ";

      $datachk = $this->coreFunctions->opentable($qry, [$itemid]);

      foreach ($datachk as $key => $value) {
        if (!empty($datachk[$key]->docno)) {
          $msg1  = $datachk[$key]->docno;

          $msg = ['status' => false, 'msg' => $msg2 . $msg1];
          return $msg;
        }
      }

      $qry = "select docno from hpchead where trno= ?";
      $datahere = $this->coreFunctions->opentable($qry, [$trno]);

      $docno = $datahere[$key]->docno;
      $qry2 = " select docno from glhead where doc='AJ' and yourref= '" . $docno . "' ";
      $ajreference = $this->coreFunctions->opentable($qry2);

      if (!empty($ajreference)) {
        $ajdocnno  = $ajreference[0]->docno;
        $msg1 = "Existing " . $ajdocnno . " Reference Adjustment. Already Posted!";
        $msg = ['status' => false, 'msg' => $msg1];
        return $msg;
      }
    }

    $qry = "select head.docno,head.dateid,head.yourref,stock.line,item.barcode,item.itemname,stock.uom,stock.loc,
        wh.client as wh,stock.rrcost,stock.cost,stock.rrqty,stock.qty,stock.expiry,item.itemid,stock.whid,stock.palletid,stock.locid
        from hpchead as head left join hpcstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid=stock.whid
        where stock.trno = ?";

    $datapc = $this->coreFunctions->opentable($qry, [$trno]);

    $wh = $datapc[0]->wh;
    $dateid = $datapc[0]->dateid;
    $pcdocno = $datapc[0]->docno;
    $yourref = $datapc[0]->yourref;
    $alias = 'IS1';
    $pref = 'AJ';
    $docno = 'AJ';


    $whname = $this->coreFunctions->datareader("select clientname as value from client where client='" . $wh . "'");
    $contra = $this->coreFunctions->datareader("select acno as value from coa where alias='" . $alias . "'");

    $ispallet = $this->companysetup->getispallet($config['params']);

    $center = $config['params']['center'];
    $config['params']['doc'] = 'AJ';
    $config['params']['isposted'] = 1;
    $config['params']['return'] = '';

    $config['docmodule']->tablenum = 'cntnum';

    // $insertcntnum = 0;
    // $docnolength =  $this->companysetup->getdocumentlength($config['params']);


    // while ($insertcntnum == 0) {
    //   $seq = $this->othersClass->getlastseq($pref, $config);
    //   if ($seq == 0 || empty($pref)) {
    //     if (empty($pref)) {
    //       $pref = strtoupper($docno);
    //     }
    //     $seq = $this->othersClass->getlastseq($pref, $config);
    //   }

    //   if ($config['params']['companyid'] == 56) {
    //     $brprefix = $this->coreFunctions->datareader("SELECT client.prefix AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE c.code='" . $config['params']['center'] . "'");
    //     $preff = $pref . $brprefix; //AJLP
    //     $poseq = $preff . $seq;
    //   } else {
    //     $poseq = $pref . $seq;
    //   }

    //   $yr =  $this->coreFunctions->datareader("select yr as value from profile where doc='SED' and psection='AJ'");
    //   $newdocno = $this->othersClass->PadJ($poseq, $docnolength, $yr);

    //   if (strlen($yourref) != 0) {
    //     $ajtrno =  $this->coreFunctions->datareader("select ifnull(trno,0) as value from cntnum where docno='" . $yourref . "' and center='" . $center . "' and postdate is not null");
    //     if ($ajtrno == 0) {
    //       $ajtrno =  $this->coreFunctions->datareader("select ifnull(trno,0) as value from cntnum where docno='" . $yourref . "' and center='" . $center . "' and postdate is null");
    //       $newdocno = $yourref;
    //       if ($ajtrno == 0) {
    //         if (!empty($center) || $center != '') {
    //           $col = [];
    //           $col = ['doc' => $config['params']['doc'], 'docno' => $newdocno, 'seq' => $seq, 'bref' => $config['params']['doc'], 'center' => $center];
    //           $table = 'cntnum';
    //           $insertcntnum = $this->coreFunctions->insertGetId($table, $col);
    //         } else {
    //           $insertcntnum = -1;
    //         } //end if empty center
    //       } else {
    //         $insertcntnum = 1;
    //       }
    //     } else {
    //       $msg1 = "Exisitng " . $yourref . " Reference Adjustment. Already Posted!";
    //       $msg = ['status' => false, 'msg' => $msg1];
    //       return $msg;
    //     }
    //   } else {
    //     if (!empty($center) || $center != '') {
    //       $col = [];
    //       $col = ['doc' => $config['params']['doc'], 'docno' => $newdocno, 'seq' => $seq, 'bref' => $config['params']['doc'], 'center' => $center];
    //       $table = $config['docmodule']->tablenum;
    //       $insertcntnum =  $this->coreFunctions->insertGetId($table, $col);
    //       $i = +1;
    //     } else {
    //       $insertcntnum = -1;
    //     } //end if empty center
    //   }
    // }///////////////////////////////////////////////////////////////


    if ($config['params']['companyid'] == 56) { //homeworks
      $pref1 = 'AJ';
      $brprefix = $this->coreFunctions->datareader("SELECT client.prefix AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE c.code='" . $config['params']['center'] . "'");
      $pref = $pref1 . $brprefix; //PVCW
    }


    $ajtrno = $this->othersClass->generatecntnum($config, 'cntnum', 'AJ', $pref);
    $qry = "select trno,docno from cntnum where doc = ? and trno = ? and center = ?";
    $trno_ =  $this->coreFunctions->opentable($qry, ['AJ', $ajtrno, $center]);

    $docno = $trno_[0]->docno;
    $user = $config['params']['user'];

    $this->coreFunctions->execqry("update hpchead set yourref='" . $docno . "' where trno=? ", "update", [$trno]);
    $this->coreFunctions->execqry("delete from lahead  where trno=? ", "update", [$ajtrno]);
    $this->coreFunctions->execqry("delete from lastock where trno=? ", "update", [$ajtrno]);
    $this->coreFunctions->execqry("delete from costing  where trno=? ", "update", [$ajtrno]);

    $this->coreFunctions->execqry("insert into lahead (docno, doc, client, clientname, address, yourref, ourref,
                                  forex, dateid, rem, shipto, terms,trno,createby,contra,tax,wh,agent,pctrno)
                                  values('" . $docno . "','AJ', '" . $wh . "', '" . $whname . "','', '" . $pcdocno . "', '',
                                  '0', '" . $dateid . "', '','', '', '" . $ajtrno . "','" . $user . "','\\$contra','0','$wh','','" . $trno . "')", "insert");

    $this->logger->sbcwritelog($ajtrno, $config, 'CREATE', $docno . ' FROM PC -' . $pcdocno . ' WAREHOUSE -' . $wh);

    $blnCantAdjust = false;

    $b = 1;
    if (!empty($datapc)) {

      foreach ($datapc as $key => $value) {
        $barcode = $datapc[$key]->barcode;
        $itemid = $datapc[$key]->itemid;
        $whid = $datapc[$key]->whid;
        $itemname = $datapc[$key]->itemname;

        $loc =  $datapc[$key]->loc;
        $uom = $datapc[$key]->uom;
        $rrcost =  $datapc[$key]->rrcost;
        $expiry =  $datapc[$key]->expiry;
        $cost = $datapc[$key]->cost;
        $qty = $datapc[$key]->qty;

        $palletid = $datapc[$key]->palletid;
        $locid = $datapc[$key]->locid;

        if ($ispallet) {
          $bal = $this->getbalbydatepallet($barcode, $wh, $locid, $palletid, $dateid);
          $onhand = $this->getcurrentbalpallet($barcode, $wh, $locid, $palletid);
        } else {
          $bal = $this->getbalbydate($barcode, $wh, $loc, $expiry, $dateid);
          $onhand = $this->getcurrentbal($barcode, $wh, $loc, $expiry);
        }

        $factor = $this->getitemuom($barcode, $uom);
        $curbal = floatval($qty) - floatval($bal);

        if ($factor == 0) {
          $factor = 1;
        }

        $this->coreFunctions->LogConsole($expiry . ' ' . $curbal);

        $line = $datapc[$key]->line;
        if ($curbal == 0) {
          $ext = 0;
          $displaycurbal = 0;
        } else {
          $displaycurbal = floatval($curbal) / floatval($factor);
          $ext = floatval($rrcost) * floatval($displaycurbal);
        }
        if ($curbal > 0) {
          $this->coreFunctions->execqry("insert into lastock(trno,line,itemid,uom,rrcost,cost,rrqty,qty,ext,whid,loc,encodedby,expiry,rem,locid,palletid)
                            values('" . $ajtrno . "','" . $b . "'," . $itemid . ",'" . $uom . "','" . $rrcost . "',
                            '" . $cost . "'," . $displaycurbal . "," . $curbal . "," . $ext . "," . $whid . ",'" . $loc . "','" . $user . "','" . $expiry . "',''," . $locid . "," . $palletid . ")", "insert");
          $b++;
        } elseif ($curbal < 0) {
          if ($onhand >= $bal) {
            $this->coreFunctions->execqry("insert into lastock(trno,line,itemid,uom,rrcost,cost,rrqty,iss,ext,whid,loc,encodedby,expiry,rem,locid,palletid)
                                    values('" . $ajtrno . "','" . $b . "'," . $itemid . ",'" . $uom . "','" . $rrcost . "',
                                    '" . $cost . "'," . $displaycurbal . ",abs(" . $curbal . ")," . $ext . "," . $whid . ",'" . $loc . "','" . $user . "','" . $expiry . "',''," . $locid . "," . $palletid . ")", "insert");
            if ($ispallet) {
              $curcost = $this->othersClass->computecostingpallet($itemid, $whid, $locid, $palletid, $ajtrno, $b, abs($curbal), 'AJ', $config['params']);
            } else {
              $curcost = $this->othersClass->computecosting($itemid, $whid, $loc, $expiry, $ajtrno, $b, abs($curbal), 'AJ', $config['params']['companyid']);
            }

            if ($curcost != -1) {
              $this->coreFunctions->execqry("update lastock set cost=" . $curcost . " where trno=" . $ajtrno . " and line=" . $b, "update");
            } else {
              $this->coreFunctions->execqry("update lastock set rrqty=0,iss=0,ext=0 where trno=" . $ajtrno . " and line=" . $b, "update");
            }
            $b++;

            $y = $this->coreFunctions->execqry("update hpcstock set rem='' where trno=" . $trno . " and line=" . $line, "update");
          } elseif (floatval(floatval($qty) - floatval((floatval($bal) - floatval($onhand)))) > 0) {

            $newbalance = floatval($qty) - floatval($bal);
            if ($newbalance < 0) {
              $ext = $ext * -1;

              $this->coreFunctions->execqry("insert into lastock(trno,line,itemid,uom,rrcost,cost,rrqty,iss,ext,whid,loc,encodedby,expiry,rem,locid,palletid)
                                    values('" . $ajtrno . "','" . $b . "'," . $itemid . ",'" . $uom . "','" . $rrcost . "',
                                    '" . $cost . "'," . $newbalance . ",abs(" . $newbalance . ")," . $ext . "," . $whid . ",'" . $loc . "','" . $user . "','" . $expiry . "',''," . $locid . "," . $palletid . ")", "insert");
              if ($ispallet) {
                $curcost = $this->othersClass->computecostingpallet($itemid, $whid, $locid, $palletid, $ajtrno, $b, abs($newbalance), 'AJ', $config['params']);
              } else {
                $curcost = $this->othersClass->computecosting($itemid, $whid, $loc, $expiry, $ajtrno, $b, abs($newbalance), 'AJ', $config['params']['companyid']);
              }

              if ($curcost != -1) {
                $this->coreFunctions->execqry("update lastock set cost=$curcost where trno=" . $ajtrno . " and line=" . $b, "update");
              } else {
                $this->coreFunctions->execqry("update lastock set rrqty=0,iss=0,ext=0 where trno=" . $ajtrno . " and line=" . $b, "update");
              }
              $b++;
              $y = $this->coreFunctions->execqry("update hpcstock set rem='' where trno=" . $trno . " and line=" . $line, "update");
            } //end newbalance < 0
          } else {
            $rem = "Cannot be adjusted. " . $barcode . " -> Onhand Qty:" . round($onhand, 2) . "; Balance by date: " . round($bal, 2) . "; Actual Count: " . round($qty, 2)  . ' Expiry:' . $expiry . '<br>';
            $y = $this->coreFunctions->execqry("update hpcstock set rem='Cannot be adjusted.', asofqty=" . round($onhand, 2) . " where trno=" . $trno . " and line=" . $line, "update");
            $remmsg .= $rem;

            $blnCantAdjust = true;
          } //end onhand >= bal
        } //end if

      } //foreach

      if ($blnCantAdjust) {
        $msg = ['status' => true, 'msg' => $remmsg, 'reloadhead' => true];
        return $msg;
      }

      $msg1 = "Successfully created. Inventory Adjustment: " . $docno . "";
      $msg = ['status' => true, 'msg' => $msg1];
      return $msg;
    }
  } //end function

  public function getbalbydate($barcode, $wh, $loc, $expiry, $date)
  {

    $filter = '';
    if ($expiry != null || $expiry != '') {
      // $filter = " and rrstatus.expiry = '" . $expiry . "' ";

      // $this->coreFunctions->sbclogger($expiry);

      if ($this->othersClass->validateDate($expiry) || $this->othersClass->validateDate($expiry, 'Y/m/d')) {
        $expiry1 = date_format(date_create($expiry), "Y-m-d");
        $expiry2 = date_format(date_create($expiry), "Y/m/d");
        $filter = " and (stock.expiry = '" . $expiry1 . "' or stock.expiry = '" . $expiry2 . "')";
      } else {
        $filter = " and stock.expiry = '" . $expiry . "' ";
      }
    }

    $sql = "select ifnull(sum(qty-iss),0) as value from (select 0 as qty,sum(stock.iss) as iss from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    where item.barcode='" . $barcode . "' and wh.client='" . $wh . "' and stock.loc='" . $loc . "' and head.dateid <= '" . $date . "'" . $filter . "
    union all 
    select sum(stock.qty) as qty,sum(stock.iss) as iss from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid 
    where item.barcode='" . $barcode . "' and wh.client='" . $wh . "' and stock.loc='" . $loc . "' and head.dateid <='" . $date . "'" . $filter . ") as t";

    $ret =  $this->coreFunctions->datareader($sql);
    return $ret;
  } //end function

  private function getbalbydatepallet($barcode, $wh, $locid, $palletid, $date)
  {

    $sql = "select ifnull(sum(qty-iss),0) as value from (
    select 0 as qty,stock.iss from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    where item.barcode='" . $barcode . "' and wh.client='" . $wh . "' and stock.locid='" . $locid . "' and stock.palletid='" . $palletid . "' and head.dateid <= '" . $date . "'
    union all
    select stock.qty,stock.iss from glhead as head left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid
    where item.barcode='" . $barcode . "' and wh.client='" . $wh . "' and stock.locid='" . $locid . "' and stock.palletid='" . $palletid . "' and head.dateid <='" . $date . "') as t";

    $ret =  $this->coreFunctions->datareader($sql);
    return $ret;
  } //end function

  public function getbalbydateloc($barcode, $wh, $date)
  {

    $sql = "select ifnull(sum(qty-iss),0) as bal, loc, expiry from (select 0 as qty,stock.iss,stock.loc,stock.expiry from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join client as wh on wh.clientid=stock.whid
    where item.barcode='" . $barcode . "' and wh.client='" . $wh . "' and head.dateid <= '" . $date . "' 
    union all 
    select stock.qty,stock.iss,stock.loc,stock.expiry from glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid left join client as wh on wh.clientid=stock.whid 
    where item.barcode='" . $barcode . "' and wh.client='" . $wh . "' and head.dateid <='" . $date . "') as t group by loc, expiry";

    $ret =  $this->coreFunctions->opentable($sql);
    return $ret;
  } //end function

  public function getcurrentbal($barcode, $wh, $loc, $expiry)
  {
    $filter = '';
    if ($expiry != null || $expiry != '') {
      // $filter = " and rrstatus.expiry = '" . $expiry . "' ";

      $this->coreFunctions->sbclogger($expiry);

      if ($this->othersClass->validateDate($expiry) || $this->othersClass->validateDate($expiry, 'Y/m/d')) {
        $expiry1 = date_format(date_create($expiry), "Y-m-d");
        $expiry2 = date_format(date_create($expiry), "Y/m/d");
        $filter = " and (rrstatus.expiry = '" . $expiry1 . "' or rrstatus.expiry = '" . $expiry2 . "')";
      } else {
        $filter = " and rrstatus.expiry = '" . $expiry . "' ";
      }
    }
    $sql = " select ifnull(sum(rrstatus.bal),0) as value from rrstatus left join item on item.itemid=rrstatus.itemid left join client as wh on wh.clientid=rrstatus.whid 
    where item.barcode= '" . $barcode . "' and wh.client= '" . $wh . "' and rrstatus.loc='" . $loc . "' and rrstatus.bal<>0 " . $filter;
    // $this->coreFunctions->LogConsole($sql);
    $ret = $this->coreFunctions->datareader($sql);
    return $ret;
  } //end function

  private function getcurrentbalpallet($barcode, $wh, $locid, $palletid)
  {
    $sql = " select ifnull(sum(rrstatus.bal),0) as value from rrstatus left join item on item.itemid=rrstatus.itemid
    left join client as wh on wh.clientid=rrstatus.whid where item.barcode= '" . $barcode . "' and wh.client= '" . $wh . "' and rrstatus.locid= " . $locid . " and rrstatus.palletid= " . $palletid . "  and rrstatus.bal<>0 ";
    $ret = $this->coreFunctions->datareader($sql);
    return $ret;
  } //end function

  private function getitemuom($barcode, $uom)
  {

    $sql = "select ifnull(uom.factor,1) as value from item left join uom on uom.itemid=item.itemid where item.barcode= ? and uom.uom= ? ";

    $ret = $this->coreFunctions->datareader($sql, [$barcode, $uom]);
    return $ret;
  } //end function

  public function getpendingsqsummary($config)
  {
    $client = '';
    if (isset($config['params']['client'])) {
      $client = $config['params']['client'];
    }
    $center = $config['params']['center'];
    $qafilter = '';
    $clientfilter = '';
    $arrfilter = [];
    $stocktbl = 'hqsstock';
    $search = '';
    switch ($config['params']['doc']) {
      case 'PO':
        $qafilter = 'stock.poqa and stock.iscanvass=0 ';
        $arrfilter = [$center];
        if (isset($config['params']['search']) && $config['params']['search'] != "") {
          $txt = $config['params']['search'];
          $search = " and (so.docno like '%" . $txt . "%' or head.dateid like '%" . $txt . "%' or head.yourref like '%" . $txt . "%'
          or head.clientname like '%" . $txt . "%' or head.client like '%" . $txt . "%')";
        } else {
          return [];
        }
        break;

      case 'CD':
        $qafilter = '(stock.qa + stock.sjqa + stock.poqa)';
        $arrfilter = [$center];
        break;
      case 'AI':
        $qafilter = '(stock.sjqa+stock.voidqty) and stock.qa<>0';
        $arrfilter = [$center];
        $stocktbl = 'hqtstock';
        break;

      default:
        $qafilter = '(stock.qa + stock.sjqa + stock.voidqty)';
        $clientfilter = " and head.client=? ";
        if (isset($config['params']['addedparams'][0])) {
          $yourref = $config['params']['addedparams'][0];
          $clientfilter = " and head.client=? and head.yourref = '" . $yourref . "'";
        }
        $arrfilter = [$center, $client];
        break;
    }

    $qry = "select so.trno as sotrno, so.docno, date(so.dateid) as dateid, head.trno,head.yourref,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.branch, head.deptid,
    ifnull(b.client, '') as branchcode, ifnull(b.clientname, '') as branchname,
    ifnull(d.client, '') as dept, ifnull(d.clientname, '') as deptname,head.deldate,head.cur,head.forex,head.tax,head.vattype
    from hsqhead as so 
    left join hqshead as head on head.sotrno=so.trno 
    left join " . $stocktbl . " as stock on stock.trno=head.trno 
    left join client as b on b.clientid = head.branch
    left join client as d on d.clientid = head.deptid
    left join transnum on transnum.trno = so.trno
    where so.doc='SQ' and stock.iss > " . $qafilter . " and stock.void = 0 and transnum.center = ? " . $clientfilter . $search . " 
    group by so.trno, so.docno, so.dateid, head.trno,head.yourref,
    head.branch, head.deptid,
    b.client, b.clientname,
    d.client, d.clientname,head.deldate,head.cur,head.forex,head.tax,head.vattype";

    $data = $this->coreFunctions->opentable($qry, $arrfilter);
    return $data;
  } // end function

  public function getpendingsqdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qafilter = '';
    $clientfilter = '';
    $arrfilter = [];
    switch ($config['params']['doc']) {
      case 'PO':
        if (isset($config['params']['addedparams'])) {
          $qafilter = '(stock.qa + stock.sjqa + stock.poqa) and stock.iscanvass=0 and so.trno = ? ';
          $arrfilter = [$config['params']['addedparams'][0], $center];
        } else {
          $qafilter = '(stock.qa + stock.sjqa + stock.poqa) and stock.iscanvass=0 ';
          $arrfilter = [$center];
        }

        break;

      case 'CD':
        $qafilter = '(stock.qa + stock.sjqa + stock.poqa) ';
        $arrfilter = [$center];
        break;
      case 'AI':
        $qafilter = '(stock.sjqa + stock.voidqty) and stock.qa<>0';
        $arrfilter = [$center];
        break;

      default:
        $qafilter = '(stock.qa + stock.sjqa + stock.voidqty)';
        $clientfilter = " and head.client=? ";
        $arrfilter = [$center, $client];
        break;
    }

    $qry = "select concat(stock.trno,stock.line) as keyid, so.docno, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, so.docno, date(head.dateid) as dateid,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as isamt, stock.disc,
    FORMAT(stock.amt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
    FORMAT(((stock.qa+stock.sjqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.iss-(stock.qa+stock.sjqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,head.yourref,m.model_name as model
    from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join model_masterfile as m on m.model_id = item.model
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    where so.doc='SQ' and stock.iss > " . $qafilter . " and stock.void = 0 and transnum.center = ?  " . $clientfilter . " order by so.docno, stock.line";
    $data = $this->coreFunctions->opentable($qry, $arrfilter);
    return $data;
  } // end function

  //JAC
  public function getpendingsosummary($config)
  {
    $companyid = $config['params']['companyid'];
    $client = isset($config['params']['client'])  ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $filter = '';
    $arrfilter = [$center];
    $total = 'sum(stock.ext)';

    switch ($config['params']['lookupclass']) {
      case 'pendingsosummaryshortcut':
        break;
      case 'pendingsorosummary':
        $filter = ' and stock.roqa<>stock.iss';
        break;
      case 'pendingtsso':
        $total = 'sum((stock.iss - (stock.qa + stock.tsqa))*stock.amt)';
        $filter = ' and head.client = ? and stock.iss > (stock.qa+stock.tsqa)';
        $arrfilter = [$center, $client];
        break;
      default:
        $filter = ' and head.client = ?';
        $arrfilter = [$center, $client];
        break;
    }


    $addedfields = '';
    if (isset($config['params']['addedparams'])) {

      if ($this->companysetup->getisshortcutso($config['params'])) {
        if (!empty($config['params']['addedparams'])) {
          if (floatval($config['params']['addedparams'][0]) != 0) {
            $addedfields = ", " . $config['params']['addedparams'][0] . " as sjseq";
          } else {
            $addedfields .= ", 0 as sjseq";
          }
          if (isset($config['params']['addedparams'][1])) {
            if ($config['params']['addedparams'][1] == '') {
              $addedfields .= ", '' as sjprefix";
            } else {
              $addedfields .= ", '" . $config['params']['addedparams'][1]['value'] . "' as sjprefix";
            }
          }
        }
      }
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.dateid as dateid2,
    FORMAT($total," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.yourref, head.ourref, head.clientname, head.rem, ifnull(sa.sano,'') as sadesc, ifnull(po.sano,'') as podesc " . $addedfields  . "
    from hsohead as head
    left join hsostock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    left join clientsano as sa on sa.line=head.sano
    left join clientsano as po on po.line=head.pono
    where head.doc ='SO' and  stock.iss>stock.qa
    and transnum.center = ? and stock.void = 0 " .  $filter . "
    group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.ourref, head.rem, 
    sa.sano, po.sano order by docno ";

    $this->coreFunctions->LogConsole($qry);
    $data = $this->coreFunctions->opentable($qry, $arrfilter);
    return $data;
  } // end function

  public function getpendingsodetailsperserial($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno as refx,stock.line as linex,item.itemname,head.docno,head.docno as ref,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,stock.disc,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,
              0 as trno, 0 as line,stock.rem
              from hsohead as head
              right join hsostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where head.doc ='SO' and head.client = ? and stock.iss>stock.qa
              and transnum.center = ?
              and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingsodetailsperpallet($config)
  {
    $filter = '';
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $doc =  strtolower($config['params']['doc']);
    $head = 'hsohead';
    $stock = 'hsostock';
    switch ($doc) {
      case 'sd':
        $head = 'hsahead';
        $stock = 'hsastock';
        break;
      case 'se':
        $head = 'hsbhead';
        $stock = 'hsbstock';
        break;
      case 'sf':
        $head = 'hschead';
        $stock = 'hscstock';
        break;
      case 'sh':
        $head = 'hsghead';
        $stock = 'hsgstock';
        $partid = $this->coreFunctions->getfieldvalue("lahead", "partreqtypeid", "trno=" . $config['params']['trno']);
        $filter = " and head.partreqtypeid=" . $partid;
        break;
    }

    $search = '';
    if (isset($config['params']['search'])) {
      $txt = $config['params']['search'];
      $search = " and (head.docno like '%" . $txt . "%' or head.dateid like '%" . $txt . "%' or item.barcode like '%" . $txt . "%'
      or item.itemname like '%" . $txt . "%')";
    }

    $qry = "select stat.status,
      concat(stock.trno,stock.line) as keyid,stock.trno as refx,stock.line as linex,
      item.itemname,head.docno,head.docno as ref,left(head.dateid,10) as dateid,item.barcode,
      FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
      FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,stock.disc,
      FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
      FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
      FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
      stock.loc,head.yourref,0 as trno, 0 as line,stock.rem,
      round(item.dqty, " . $this->companysetup->getdecimal('currency', $config['params']) . ") as qtyperbox, 
      stockinfo.amt1, stockinfo.amt2, stockinfo.amt3, stockinfo.amt4, stockinfo.amt5,transnum.postdate,transnum.postedby
      from " . $head . " as head
      right join " . $stock . " as stock on stock.trno = head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      left join client as wh on wh.clientid=stock.whid    
      left join hstockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line
      left join trxstatus as stat on stat.line=transnum.statid
      where head.client = ? and stock.iss>stock.qa and transnum.center = ? and stock.void = 0 " . $filter . $search;

    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function


  public function getpendingsodetails($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $addfield = "";
    $addfilter = "";
    $filterdata = [$center];
    $filter = 'stock.iss>stock.qa';
    $fieldqa = 'stock.qa';
    if ($systemtype == 'MANUFACTURING') {
      $addfield = ",FORMAT((stock.pdqa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pdqa,
        FORMAT(((stock.iss-stock.pdqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pdpending,
        stock.uom";
      $addfilter = "and stock.iss>stock.pdqa and head.sotype=1";
    } else {
      switch ($config['params']['lookupclass']) {
        case 'pendingsorodetail':
          $filter = "stock.roqa<>stock.iss";
          $fieldqa = 'stock.roqa';
          $filterdata = [$center];
          break;
        case 'pendingtsso':
          $fieldqa = '(stock.qa + stock.tsqa)';
          $addfilter = "and head.client=? and stock.iss > (stock.qa+stock.tsqa)";
          $filterdata = [$center, $client];
          break;
        default:
          $addfilter = "and head.client=?";
          $filterdata = [$center, $client];
          break;
      }
    }
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,stock.kgs,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((" . $fieldqa . " / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.iss-" . $fieldqa . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref, 
              stock.itemid, ifnull(sa.sano,'') as sadesc, ifnull(po.sano,'') as podesc " . $addfield . "
              from hsohead as head
              right join hsostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              left join clientsano as sa on sa.line=head.sano
              left join clientsano as po on po.line=head.pono
              where $filter 
              and transnum.center = ?
              and stock.void = 0 " . $addfilter . " order by head.docno";
    $data = $this->coreFunctions->opentable($qry, $filterdata);

    $this->coreFunctions->logConsole($qry);
    return $data;
  } // end function

  public function getpendingsjsummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : $config['params']['dataid'];
    $center = $config['params']['center'];
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : 0;
    $cur = $this->coreFunctions->getfieldvalue("lahead", "cur", "trno = ?", [$trno]);
    $lookupclass = $config['params']['lookupclass'];


    if ($config['params']['doc'] == 'CUSTOMER') {
      $condition = "where head.doc in ('MJ') and client.clientid = ?
            and cntnum.center = ?";
      $filter =  [$client, $center];
    } else {
      $condition = "where head.doc in ('SJ','SD','SE','SF','CI') and client.client = ? and  stock.iss>stock.qa
            and cntnum.center = ? and head.cur = ? and stock.void = 0";
      $filter = [$client, $center, $cur];
    }


    if ($config['params']['companyid'] == 60) {
      if ($config['params']['doc'] == 'RR') {
        $condition = "where head.doc='SJ' and stock.iss>stock.rrqa and stock.void=0 and cntnum.center<>?";
        $filter = [$center];
      }

      if ($config['params']['doc'] == 'PO') {
        $condition = " left join arledger as ar on ar.trno = head.trno left join client as wh on wh.clientid = head.whid
        left join gldetail as pay on pay.refx = ar.trno and pay.linex = ar.line 
        left join (select ca.depodate,ca.trno from caledger as ca where ca.depodate is not null union all select cr.depodate,cr.trno from crledger as cr where cr.depodate is not null)  as dep on dep.trno = pay.trno
        where head.doc='SJ' and stock.iss>stock.poqa and stock.void=0 and (ar.bal<>0 or dep.depodate is null) and cntnum.center=? ";
        $filter = [$center];
      }
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,client.clientid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref,head.ourref
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            $condition
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientid";

    $this->coreFunctions->LogConsole($qry);
    $data = $this->coreFunctions->opentable($qry, $filter);
    return $data;
  } // end function

  public function getpendingsjdetails($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : (isset($config['params']['dataid']) ? $config['params']['dataid'] : '');
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $cur = $this->coreFunctions->getfieldvalue("lahead", "cur", "trno = ?", [$trno]);
    $qafield = "FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT((stock.iss-stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,";
    $condition = "and head.doc in ('SJ','SD','SE','SF','CI') and client.client=? and stock.iss>stock.qa and cntnum.center=? and head.cur = ?";
    $qrydata = [$client, $center, $cur];
    $join = "";

    if ($config['params']['companyid'] == 60) { //transpower
      if ($config['params']['doc'] == 'RR') {
        $qafield = "FORMAT((stock.rrqa/case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
        FORMAT((stock.iss-stock.rrqa/case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,";
        $condition = "and head.doc in ('SJ') and stock.iss>stock.rrqa and cntnum.center<>?";
        $qrydata = [$center];
      }


      if ($config['params']['doc'] == 'PO') {
        $join = " left join arledger as ar on ar.trno = head.trno 
        left join gldetail as pay on pay.refx = ar.trno and pay.linex = ar.line 
        left join (select ca.depodate,ca.trno from caledger as ca where ca.depodate is not null union all select cr.depodate,cr.trno from crledger as cr where cr.depodate is not null)  as dep on dep.trno = pay.trno ";
        $qafield = "FORMAT((stock.poqa/case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
        FORMAT((stock.iss-stock.poqa/case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,";
        $condition = "and head.doc in ('SJ') and stock.iss>stock.poqa and (ar.bal<>0 or dep.depodate is null) and cntnum.center =? ";
        $qrydata = [$center];
      }
    }

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              " . $qafield . "
              stock.loc,head.yourref,stock.expiry,head.ourref
              from glhead as head
              right join glstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join cntnum on cntnum.trno = head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid=stock.whid " . $join . "
              where 1=1 " . $condition . " 
              and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, $qrydata);
    return $data;
  } // end function
  //END JAC


  public function getpendingsisummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : $config['params']['dataid'];
    $center = $config['params']['center'];
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : 0;
    $cur = $this->coreFunctions->getfieldvalue("lahead", "cur", "trno = ?", [$trno]);

    $condition = "where head.doc in ('SS') and client.client = ? and  stock.iss>stock.qa
            and cntnum.center = ? and head.cur = ? and stock.void = 0";
    $filter = [$client, $center, $cur];

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,client.clientid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref,head.ourref
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            $condition
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientid";
    $data = $this->coreFunctions->opentable($qry, $filter);
    return $data;
  } // end function

  public function getpendingsidetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $cur = $this->coreFunctions->getfieldvalue("lahead", "cur", "trno = ?", [$trno]);

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT((stock.iss-stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,stock.expiry
              from glhead as head
              right join glstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join cntnum on cntnum.trno = head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid=stock.whid
              where head.doc in ('SS') and client.client = ? and stock.iss>stock.qa
              and cntnum.center = ? and head.cur = ?
              and stock.void = 0 ";

    $data = $this->coreFunctions->opentable($qry, [$client, $center, $cur]);
    return $data;
  } // end function


  public function getpendingpysummary($config)
  {
    $qry = "select head.trno, head.yourref, head.ourref, format(sum(ledger.cr-ledger.db), 2) as amt, info.checkdate, cv.docno as cvno, cvd.checkno as checkdetails,
      info.releasetoap, '' as cleardate, info.rem2 as rem, head.docno as plno, date(head.dateid) as pldate, format(sum(ledger.db),2) as db, format(sum(ledger.cr),2) as cr,
      head.client, head.clientname, '' as bgcolor, head.trno as pytrno, head.docno, date(head.dateid) as dateid
      from hpyhead as head
      left join transnum as num on num.trno=head.trno
      left join hheadinfotrans as info on info.trno=head.trno
      left join cntnum as cv on cv.trno=num.cvtrno
      left join gldetail as cvd on cvd.trno=cv.trno
      left join apledger as ledger on ledger.py=head.trno
      left join client on client.client = head.client
      where num.pstrno=0 and client.groupid = 'TRADE'
      group by head.trno, head.yourref, head.ourref, info.checkdate, cv.docno, cvd.checkno,
      info.releasetoap, info.rem2 , head.docno, head.dateid,
      head.client, head.clientname,  head.trno, head.docno";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  public function getpendingdrsummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : '';
    $cur = $this->coreFunctions->getfieldvalue("lahead", "cur", "trno = ?", [$trno]);
    $filter = '';
    $company = $config['params']['companyid'];
    $doc = $config['params']['doc'];

    $docfilter = "'DR'";
    $addqry = '';

    if ($company == 39) { //cbbsi
      switch ($doc) {
        case 'CK':
          $filter .= " and cntnum.svnum<>0 and stock.iss > (stock.ckqa+stock.qa)";
          break;
        case 'CM':
          $filter .= " and cntnum.svnum<>0 and stock.iss>stock.qa ";
          break;
        case 'DP':
          $addqry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
          FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
          head.yourref,head.ourref,client.clientname
          from lahead as head
          right join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno = head.trno
          left join client on client.client=head.client
          where head.doc in ('ST') 
          and cntnum.center = ? " . $filter . "
          and stock.void = 0 and cntnum.dptrno = 0
          group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientname
          union all";
          $filter .= " and cntnum.dptrno = 0 ";
          $docfilter = "'DR','ST'";
          break;
        case 'SK':
          $filter .= " and cntnum.svnum=0 ";
          break;
        default:
          $filter .= " and cntnum.svnum=0 and stock.iss>stock.qa";
          break;
      }
    }

    if ($company == 63) { //ericco
      $filter .= " and cntnum.svnum=0 ";
      $docfilter = "'SJ'";
    }

    if ($client != '') $filter .= " and client.client='" . $client . "'";
    if ($cur != '') $filter .= " and head.cur='" . $cur . "'";

    $qry = $addqry . " 
            select stock.trno,head.docno,left(head.dateid,10) as dateid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref,head.ourref,client.clientname
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            where head.doc in ($docfilter) 
            and cntnum.center = ? " . $filter . "
            and stock.void = 0
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientname";
    $data = $this->coreFunctions->opentable($qry, [$center, $center]);
    return $data;
  } // end function

  public function getpendingrfshortcut($config)
  {
    $center = $config['params']['center'];
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : '';
    $filter = '';
    $company = $config['params']['companyid'];
    $doc = $config['params']['doc'];


    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.yourref,head.ourref,client.clientname
    from hckhead as head
    right join hckstock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    left join client on client.client=head.client
    where  transnum.center = ?  and stock.iss>stock.qa
    and stock.void = 0
    group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientname ";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingstrshortcut($config)
  {
    $center = $config['params']['center'];
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : '';
    $filter = '';
    $company = $config['params']['companyid'];
    $doc = $config['params']['doc'];


    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.yourref,head.ourref,client.clientname
    from htrhead as head
    right join htrstock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    left join client on client.client=head.client
    where  transnum.center = ?  and stock.qty>stock.qa
    and stock.void = 0
    group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientname ";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function


  public function getpendingdrsummaryshortcut($config)
  {

    $center = $config['params']['center'];
    $lookupclass = $config['params']['lookupclass'];
    $companyid = $config['params']['companyid'];
    $filter = '';
    $docfilter = "'DR'";

    switch ($lookupclass) {
      case 'drtaggedshortcut': //pickdr on SI
        $filter = " and cntnum.svnum=0";
        $docfilter = "'SJ'";
        break;

      case 'drrftaggedshortcut': //pick dr on req.salesret
        $filter = " and cntnum.svnum<>0 and stock.iss>(stock.qa+stock.ckqa)";
        $docfilter = "'DR'";
        break;
      case 'pendingdrretsummaryshortcut';
        $filter = " and stock.iss>stock.qa and cntnum.svnum=0";
        $docfilter = "'DR'";
        break;

      default:
        $filter = " stock.iss>stock.qa";
        $docfilter = "'DR'";
        break;
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref,head.ourref,client.clientname
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            where head.doc in ($docfilter) and cntnum.center = ? 
            and stock.void = 0 " . $filter . "
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientname";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function


  public function getpendingwbsummary($config)
  {
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $whfrom = $this->coreFunctions->getfieldvalue("cntnuminfo", "whfromid", "trno = ?", [$trno]);
    $whto = $this->coreFunctions->getfieldvalue("cntnuminfo", "whtoid", "trno = ?", [$trno]);

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid, FORMAT(sum(stock.isamt),2) as totalamt,
            head.yourref,head.ourref,wh.clientid as whid,head.whto,whto.clientid as whtoid,cs.clientname as consignee
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.client=head.client
            left join cntnuminfo as info on info.trno = head.trno
            left join client as whto on whto.client=head.whto
            left join client as cs on cs.clientid=head.consigneeid
            left join client as wh on wh.client=head.wh
            where head.doc ='SJ' and stock.isqty > stock.qa and stock.void = 0 and cntnum.center = '$center' and whto.clientid=$whto and head.lockdate is not null
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,wh.clientid,head.whto,whto.clientid,cs.clientname
            UNION ALL
            select stock.trno,head.docno,left(head.dateid,10) as dateid,FORMAT(sum(stock.isamt),2) as totalamt,
            head.yourref,head.ourref,head.whid,head.whto,whto.clientid as whtoid,cs.clientname as consignee
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            left join hcntnuminfo as info on info.trno = head.trno
            left join client as whto on whto.client=head.whto
            left join client as cs on cs.clientid=head.consigneeid
            where head.doc ='SJ' and stock.isqty > stock.qa and stock.void = 0 and cntnum.center = '$center' and whto.clientid=$whto 
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref,head.whid,head.whto,whto.clientid,cs.clientname";


    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function

  public function getpendingwbdetails($config)
  {
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $whfrom = $this->coreFunctions->getfieldvalue("cntnuminfo", "whfromid", "trno = ?", [$trno]);
    $whto = $this->coreFunctions->getfieldvalue("cntnuminfo", "whtoid", "trno = ?", [$trno]);

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,head.docno,date(head.dateid) as dateid,round(stock.isqty) as isqty,ROUND((stock.isqty - stock.qa)) as pending,
            FORMAT(stock.isamt,2) as isamt, wh.clientid as whid,head.whto,whto.clientid as whtoid,sinfo.itemdesc,FORMAT(sinfo.weight,2) as weight,sinfo.unit,cs.clientname as consignee
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.client=head.client
            left join cntnuminfo as info on info.trno = head.trno
            left join client as whto on whto.client=head.whto
            left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
            left join client as cs on cs.clientid=head.consigneeid
            left join client as wh on wh.client=head.wh
            where head.doc ='SJ' and stock.isqty > stock.qa and stock.void = 0 and cntnum.center = '$center' and whto.clientid=$whto and head.lockdate is not null
    union all
    select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,head.docno,date(head.dateid) as dateid,round(stock.isqty) as isqty,ROUND((stock.isqty - stock.qa)) as pending,
            FORMAT(stock.isamt,2) as isamt, head.whid,head.whto,whto.clientid as whtoid,sinfo.itemdesc,FORMAT(sinfo.weight,2) as weight,sinfo.unit,cs.clientname as consignee
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            left join hcntnuminfo as info on info.trno = head.trno
            left join client as whto on whto.client=head.whto
            left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
            left join client as cs on cs.clientid=head.consigneeid
            where head.doc ='SJ' and stock.isqty > stock.qa and stock.void = 0 and cntnum.center = '$center' and whto.clientid=$whto
            order by docno,line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function


  public function gettaggabledr($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select stock.trno as keyid,head.docno,left(head.dateid,10) as dateid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref,head.ourref
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            where head.doc in ('DR') and client.client = '$client'
            and cntnum.center = '$center' and cntnum.svnum = 0
            and stock.void = 0
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function



  public function gettaggabledn($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select stock.trno as keyid,head.docno,left(head.dateid,10) as dateid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref,head.ourref
            from glhead as head
            right join glstock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno = head.trno
            left join client on client.clientid=head.clientid
            where head.doc in ('DN') and client.client = '$client' and  stock.rrqty>stock.qa
            and cntnum.center = '$center' and cntnum.svnum = 0
            and stock.void = 0
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function


  public function gettaggableso($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select stock.trno as keyid,head.docno,left(head.dateid,10) as dateid,
            FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
            head.yourref,head.ourref
            from hsohead as head
            right join hsostock as stock on stock.trno = head.trno
            left join transnum on transnum.trno = head.trno
            left join client on client.client=head.client
            where client.client = '$client' and  stock.iss>stock.qa
            and transnum.center = '$center' and transnum.sitagging = 0
            and stock.void = 0
            group by stock.trno,head.docno,head.dateid,head.yourref,head.ourref";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  } // end function

  public function getpendingdrdetails($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';

    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $cur = $this->coreFunctions->getfieldvalue("lahead", "cur", "trno = ?", [$trno]);
    $filter = '';
    $company = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $docfilter = "'DR'";
    if ($company == 39) { //cbbsi
      switch ($doc) {
        case 'CK':
          $filter .= " and cntnum.svnum<>0 and stock.iss > (stock.ckqa+stock.qa) ";
          break;
        case 'CM':
          $filter .= " and cntnum.svnum<>0 and stock.iss>stock.qa ";
          break;
        case 'DP':
          $filter .= " and cntnum.svnum=0 and cntnum.dptrno = 0 ";
          $docfilter = "'DR','ST'";
          break;
        case 'SK':
          $filter .= " and cntnum.svnum=0 ";
          break;
        default:
          $filter .= " and cntnum.svnum=0 and stock.iss>stock.qa";
          break;
      }
    }

    if ($client != '') $filter .= " and client.client='" . $client . "'";
    if ($cur != '') $filter .= " and head.cur='" . $cur . "' ";
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT((stock.iss-stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,stock.expiry
              from glhead as head
              right join glstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join cntnum on cntnum.trno = head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid=stock.whid
              where head.doc in ($docfilter) and stock.iss>stock.qa
              and cntnum.center = ? 
              and stock.void = 0 
              $filter
              ";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingshsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
  FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,head.yourref
  from glhead as head
  left join glstock as stock on stock.trno = head.trno
  left join cntnum on cntnum.trno = head.trno
  left join client on client.clientid=head.clientid
  where head.doc='SH' and client.client = ? and  stock.iss>stock.qa and cntnum.center = ? and stock.void = 0
  group by stock.trno,head.docno,head.dateid,head.yourref";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingshdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT((stock.iss-stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,stock.expiry
              from glhead as head
              right join glstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join cntnum on cntnum.trno = head.trno
              left join client on client.clientid=head.clientid
              left join client as wh on wh.clientid=stock.whid
              where head.doc = 'SH' and client.client = ? and stock.iss>stock.qa
              and cntnum.center = ? and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getrefdm($config)
  {
    $refx = $config['params']['row']['refx'];
    $linex = $config['params']['row']['linex'];
    if ($refx == 0) {
      return [];
    }
    $qry = "
    select 'Source Document' as status,head.docno, head.dateid, stock.uom,
    stock.rrqty as qty,
    round((stock.qty-rrstatus.qa)/ case when ifnull(uom.factor,0)=0 then 1
    else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    stock.disc
    FROM glhead as head left join glstock as stock on stock.trno=head.trno
    left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
    left join item on item.itemid=
    stock.itemid left join uom on uom.itemid=item.itemid and
    uom.uom=stock.uom where stock.trno = ? and stock.line=? and
    stock.void=0
    union ALL
    select 'RETURN DOCUMENT' as status,docno,dateid,uom,qty,pending,disc from (
     select head.docno,head.dateid,stock.uom,stock.isqty as qty,0 as
     pending,stock.disc from lahead as head left join lastock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
     union ALL
     select head.docno,head.dateid,stock.uom,stock.isqty as qty,0 as
     pending,stock.disc from glhead as head left join glstock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
    ) as T";
    return $this->coreFunctions->opentable($qry, [$refx, $linex, $refx, $linex, $refx, $linex]);
  }

  public function getrefpo($config)
  {
    $refx = $config['params']['row']['refx'];
    $linex = $config['params']['row']['linex'];
    if ($refx == 0) {
      return [];
    }
    $qry = "
    select 'Source Document' as status,head.docno, head.dateid, stock.uom,
    stock.rrqty as qty,
    round((stock.qty-rrstatus.qa)/ case when ifnull(uom.factor,0)=0 then 1
    else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    stock.disc
    FROM hprhead as head left join hprstock as stock on stock.trno=head.trno
    left join rrstatus on rrstatus.trno=stock.trno and rrstatus.line=stock.line
    left join item on item.itemid=
    stock.itemid left join uom on uom.itemid=item.itemid and
    uom.uom=stock.uom where stock.trno = ? and stock.line=? and
    stock.void=0
    union ALL
    select 'ORDER DOCUMENT' as status,docno,dateid,uom,qty,pending,disc from (
     select head.docno,head.dateid,stock.uom,stock.rrqty as qty,0 as
     pending,stock.disc
     from pohead as head
     left join postock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
     union ALL
     select head.docno,head.dateid,stock.uom,stock.rrqty as qty,0 as
     pending,stock.disc from hpohead as head
     left join hpostock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
    ) as T";

    return $this->coreFunctions->opentable($qry, [$refx, $linex, $refx, $linex, $refx, $linex]);
  }



  public function getrefcm($config)
  {
    $refx = $config['params']['row']['refx'];
    $linex = $config['params']['row']['linex'];
    if ($refx == 0) {
      return [];
    }
    $qry = "
    select 'Source Document' as status,head.docno, head.dateid, stock.uom,
    stock.isqty as qty,
    round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1
    else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    stock.disc
    FROM glhead as head left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=
    stock.itemid left join uom on uom.itemid=item.itemid and
    uom.uom=stock.uom where stock.trno = ? and stock.line=? and
    stock.void=0
    union ALL
    select 'RETURN DOCUMENT' as status,docno,dateid,uom,qty,pending,disc from (
     select head.docno,head.dateid,stock.uom,stock.rrqty as qty,0 as
     pending,stock.disc from lahead as head left join lastock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
     union ALL
     select head.docno,head.dateid,stock.uom,stock.rrqty as qty,0 as
     pending,stock.disc from glhead as head left join glstock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
    ) as T
";
    return $this->coreFunctions->opentable($qry, [$refx, $linex, $refx, $linex, $refx, $linex]);
  }

  public function get_amenity($config)
  {
    $qry = "
      select line,code as amenity_code,description as amenity_desc from amenities
    ";
    // [$refx, $linex, $refx, $linex, $refx, $linex, $refx, $linex]
    return $this->coreFunctions->opentable($qry);
  } //end function

  public function get_subamenity($config)
  {
    // $refx = $config['params']['row']['refx'];
    // $linex = $config['params']['row']['linex'];

    $qry = "
      select line,amenityid,code as subamenity_code,description as subamenity_desc from subamenities
    ";
    // [$refx, $linex, $refx, $linex, $refx, $linex, $refx, $linex]
    return $this->coreFunctions->opentable($qry);
  } //end function

  public function getrefrr($config)
  {
    $refx = $config['params']['row']['refx'];
    $linex = $config['params']['row']['linex'];
    if ($refx == 0) {
      return [];
    }
    $qry = "
    select 'Source Document' as status,head.docno, head.dateid, stock.uom,
    stock.rrqty as qty,
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1
    else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    stock.disc
    FROM hpohead as head left join hpostock as stock on stock.trno=head.trno
    left join item on item.itemid=
    stock.itemid left join uom on uom.itemid=item.itemid and
    uom.uom=stock.uom where stock.trno = ? and stock.line=? and
    stock.void=0
    union all
    select 'Source Document' as status,head.docno, head.dateid, stock.uom,
    stock.isqty as qty,
    round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1
    else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    stock.disc
    FROM hsohead as head left join hsostock as stock on stock.trno=head.trno
    left join item on item.itemid=
    stock.itemid left join uom on uom.itemid=item.itemid and
    uom.uom=stock.uom where stock.trno = ? and stock.line=? and
    stock.void=0
    union ALL
    select 'ISSUED DOCUMENT' as status,docno,dateid,uom,qty,pending,disc from (
     select head.docno,head.dateid,stock.uom,case stock.rrqty when 0 then stock.isqty else stock.rrqty end as qty,0 as
     pending,stock.disc from lahead as head left join lastock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
     union ALL
     select head.docno,head.dateid,stock.uom,case stock.rrqty when 0 then stock.isqty else stock.rrqty end as qty,0 as
     pending,stock.disc from glhead as head left join glstock as stock
     on stock.trno=head.trno where stock.refx=? and stock.linex=?
    ) as T
";
    return $this->coreFunctions->opentable($qry, [$refx, $linex, $refx, $linex, $refx, $linex, $refx, $linex]);
  } //end function

  public function refporr($config)
  {
    $trno = $config['params']['row']['trno'];
    $center = $config['params']['center'];
    $itemid = $config['params']['row']['itemid'];
    $client = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);

    $qry = "select stock.trno,stock.line,item.itemname,head.docno,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
    FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,item.itemid,
    FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending    
    from hpohead as head
    right join hpostock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    left join client  on client.client=head.client
    where transnum.center = ? and head.doc ='PO' and item.itemid = ? and client.client = ? and stock.void = 0 and stock.qty>stock.qa";

    return $this->coreFunctions->opentable($qry, [$center, $itemid, $client]);
  }

  public function refprati($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $qry = "select s.ref, s.reqtrno, s.reqline, info.itemdesc, info.specs, s.deptid, s.suppid, s.sano, s.rrqty2,pr.ourref,cat.category
      from cdstock as s left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
      left join hprstock as prs on prs.trno=s.reqtrno and prs.line=s.reqline
      left join hprhead as pr on pr.trno=prs.trno
      left join reqcategory as cat on cat.line=pr.ourref
      where s.trno=? and s.reqtrno<>0 and s.line<>? group by s.ref, s.reqtrno, s.reqline, info.itemdesc, info.specs, s.deptid, s.suppid, s.sano, s.rrqty2,pr.ourref,cat.category";
    return $this->coreFunctions->opentable($qry, [$trno, $line]);
  }

  public function getrefcv($config)
  {
    $refx = $config['params']['row']['refx'];
    $linex = $config['params']['row']['linex'];
    if ($refx == 0) {
      return [];
    }
    $qry = "
    select 'Source Document' as status,head.docno, head.dateid, detail.db,
    detail.cr
    FROM glhead as head left join gldetail as detail on detail.trno=head.trno
    left join coa on coa.acnoid=detail.acnoid where detail.trno = ? and detail.line=?
    union ALL
    select 'PAYMENT DOCUMENT' as status,docno,dateid,db,cr from (
     select head.docno,head.dateid,detail.db,detail.cr from lahead as head left join ladetail as detail on detail.trno=head.trno where detail.refx=? and detail.linex=?
     union ALL
     select head.docno,head.dateid,detail.db,detail.cr from glhead as head left join gldetail as detail on detail.trno=head.trno where detail.refx=? and detail.linex=?
    ) as T
";
    return $this->coreFunctions->opentable($qry, [$refx, $linex, $refx, $linex, $refx, $linex]);
  } //end function
  public function getrefcr($config)
  {
    $refx = $config['params']['row']['refx'];
    if ($refx == 0) {
      return [];
    }
    $qry = "
            select 'Source Document' as status,arledger.docno,arledger.dateid,CAST(concat('Amount: ',format(arledger.db+arledger.cr,2),'-','BALANCE:' ,format(arledger.bal,2)) as CHAR) as rem
            from arledger
            left join cntnum as num on num.trno = arledger.trno
            where num.trno=?
            union all
            select 'Payment Document' as status,head.docno,head.dateid,CAST(concat('Applied Amount: ',format(detail.db+detail.cr,2)) as CHAR) as rem
            from lahead as head
            left join ladetail as detail on detail.trno=head.trno
            where detail.refx=?";
    return $this->coreFunctions->opentable($qry, [$refx, $refx]);
  }

  public function recomputebal($trno, $line)
  {
    $qry = "
    select ifnull(round(sum(db+cr),2),0) as value
    from (select db,cr from ladetail where refx=? and linex=?
    union all
    select db,cr from gldetail where refx=? and linex=?) as t
  ";

    $bal = $this->coreFunctions->datareader($qry, [$trno, $line, $trno, $line]);
    if ($bal == '') {
      $bal = 0;
    }
    return $bal;
  } //end function

  public function recomputebalparticulars($trno, $line)
  {
    $qry = "
    select ifnull(round(sum(amount),2),0) as value
    from (select amount from particulars where refx=? and linex=?
    union all
    select amount from hparticulars where refx=? and linex=?) as t
  ";

    $bal = $this->coreFunctions->datareader($qry, [$trno, $line, $trno, $line]);
    if ($bal == '') {
      $bal = 0;
    }
    return $bal;
  } //end function


  public function resetdb($trno, $line, $config)
  {
    $table = $config['docmodule']->detail;
    $this->coreFunctions->execqry("update $table set db=0, cr=0,fcr=0,fdb=0 where trno=? and line=?", "update", [$trno, $line]);
  } //end function

  public function getpaymentreference($trno, $line)
  {
    $ref = $this->coreFunctions->opentable("
            select head.docno,left(head.dateid,10) as dateid from lahead as head left join ladetail as d on d.trno=head.trno where d.refx=? and d.linex=?
            union all
            select head.docno,left(head.dateid,10) as dateid from glhead as head left join gldetail as d on d.trno=head.trno where d.refx=? and d.linex=?
        ", [$trno, $line, $trno, $line]);
    $reference = "";
    foreach ($ref as $ref_) {
      $reference .= " " . $ref_->docno . " " . $ref_->dateid;
    }

    if (strlen($reference) == 0) {
      return '';
    } else {
      return $reference;
    }
  } //end function

  public function getpaymentreferenceparticulars($trno, $line)
  {
    $ref = $this->coreFunctions->opentable("
            select head.docno,left(head.dateid,10) as dateid from lahead as head left join particulars as d on d.trno=head.trno where d.refx=? and d.linex=?
            union all
            select head.docno,left(head.dateid,10) as dateid from glhead as head left join hparticulars as d on d.trno=head.trno where d.refx=? and d.linex=?
        ", [$trno, $line, $trno, $line]);


    $reference = "";
    foreach ($ref as $ref_) {
      $reference .= " " . $ref_->docno . " " . $ref_->dateid;
    }

    if (strlen($reference) == 0) {
      return '';
    } else {
      return $reference;
    }
  } //end function


  public function setupdatebal($refx, $linex, $acno, $config, $reset = 0, $istotal = 0, $dateid = '')
  {
    if ($config['params']['doc'] == 'RE') { //Replacement Cheque
      $bal = $this->recomputebalparticulars($refx, $linex);
    } else {
      $bal = $this->recomputebal($refx, $linex);
    }

    // var_dump($bal);
    $companyid = $config['params']['companyid'];
    $arap = $this->coreFunctions->getfieldvalue('gldetail', 'sum(db+cr)', 'trno=? and line=?', [$refx, $linex]);
    if ($istotal) {
      $alias = $this->coreFunctions->getfieldvalue('coa', 'left(alias,2)', 'acno=?', [$acno]);
      $arap = $this->coreFunctions->getfieldvalue(strtolower($alias) . 'ledger', 'sum(db+cr)', 'trno=? and line=?', [$refx, $linex]);
    }

    $this->coreFunctions->logconsole($bal . '=' . $arap);


    if ($bal <= $arap) {
      $alias = $this->coreFunctions->getfieldvalue('coa', 'left(alias,2)', 'acno=?', [$acno]);

      if ($config['params']['doc'] == 'RE') { //Replacement Cheque
        $reference = $this->getpaymentreferenceparticulars($refx, $linex);
      } else {
        $reference = $this->getpaymentreference($refx, $linex);
      }

      switch ($alias) {
        case 'AP':
          $this->coreFunctions->execqry("update apledger set bal=(db+cr)-$bal,ref=? where trno=? and line=?", "update", [$reference, $refx, $linex]);
          break;
        case 'AR':
          if ($companyid == 55) {
            if ($dateid != "") {
              $this->coreFunctions->execqry("update arledger set bal=(db+cr)-$bal,ref=?,lpaydate = '" . $dateid . "' where trno=? and line=?", "update", [$reference, $refx, $linex]);
            } else {
              $this->coreFunctions->execqry("update arledger set bal=(db+cr)-$bal,ref=?,lpaydate = null where trno=? and line=?", "update", [$reference, $refx, $linex]);
            }
          } else {
            if ($config['params']['doc'] == 'RE') { //Replacement Cheque
              $linex = 1;
            }

            $this->coreFunctions->execqry("update arledger set bal=(db+cr)-$bal,ref=? where trno=? and line=?", "update", [$reference, $refx, $linex]);
          }
          // var_dump("update arledger set bal=(db+cr)-$bal,ref=? where trno=? and line=?", "update", [$reference, $refx, $linex]);
          break;
        case 'CR':
        case 'CA':
          if ($reset == 0) {
            $this->coreFunctions->execqry("update " . strtolower($alias) . "ledger set depodate = '" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=?", "update", [$refx, $linex]);
          } else {
            $this->coreFunctions->execqry("update " . strtolower($alias) . "ledger set depodate = null where trno=? and line=?", "update", [$refx, $linex]);
          }
          break;
        default:
          $this->logger->sbcwritelog($config['params']['trno'], $config, 'UNPAID', "ALIAS MISSING for " . $acno . ", Can`t apply payment");
          break;
      }
      return true;
    } else {
      return false;
    }
  } //end function

  public function getunpaidpercust($config)
  {
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $include = $this->coreFunctions->getfieldvalue("dchead", "isinclude", "trno=?", [$trno]);
    $filter = "";
    if ($include == 0) {
      $filter = " and arledger.ka<>0";
    }

    $qry = "select keyid, client, clientname, format(sum(ar)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ar from (
      select client.clientid as keyid, client.client, client.clientname, arledger.bal as ar
        from arledger
        left join cntnum on cntnum.trno=arledger.trno
        left join client on client.clientid=arledger.clientid
        where arledger.bal<>0 and cntnum.center='" . $center . "' " . $filter . "      
    ) as t group by keyid, client, clientname;";
    return $this->coreFunctions->opentable($qry);
  }

  public function getunpaid($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $adminid = $config['params']['adminid'];
    $systype = $this->companysetup->getsystemtype($config['params']);

    $filter = '';
    $addonfields1 = '';
    $addonfields2 = '';
    $addonfields3 = '';
    $addfilter = '';
    $addleftjoin = '';
    $addleftjoinjc = '';
    $clientfilter = 'and ctbl.client = "' . $client . '"';
    $docfilter = "";

    if ($config['params']['companyid'] == 40) { //cdo
      if ($config['params']['doc'] == 'CV') {
        $docfilter = " and cntnum.doc<>'RR' ";
      }
    }

    $re_fields = '';

    if ($config['params']['lookupclass'] == 'unpaidchild') {
      $addleftjoin = ' left join client as parent on parent.client=ctbl.parent ';
      $addleftjoinjc = ' left join client as parent on parent.client=ctbl.parent ';
      $clientfilter = 'and parent.client = "' . $client . '"';
    }



    if ($config['params']['lookupclass'] == 'unpaidall') {
      $clientfilter = '';
    }

    if ($this->companysetup->getisproject($config['params'])) {
      // if ($config['params']['companyid'] != 8) {
      $projectid = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
      $addfilter = " and gldetail.projectid = " . $projectid;
      // }
    }

    if ($config['params']['companyid'] == 34) { //evergreen
      $cptrno = $this->coreFunctions->getfieldvalue("cntnuminfo", "cptrno", "trno=?", [$trno]);
      $addfilter = " and cntnum.trno = " . $cptrno;
    }


    if ($config['params']['companyid'] == 8) { //maxipro
      $addleftjoin = ' left join projectmasterfile as proj on proj.line=glhead.projectid ';
      $addleftjoinjc = ' left join projectmasterfile as proj on proj.line=glhead.projectid ';
      $yourrefap = " (select  group_concat(concat(stock.ref) SEPARATOR ',') as ref
      from glhead as head left join glstock as stock on stock.trno=head.trno
      where head.trno=apledger.trno) as yourref,proj.code as projcode, proj.name as projname ";
      $yourrefar = " (select  group_concat(concat(stock.ref) SEPARATOR ',') as ref
      from glhead as head left join glstock as stock on stock.trno=head.trno
      where head.trno=arledger.trno) as yourref,proj.code as projcode, proj.name as projname ";
    } else if ($config['params']['companyid'] == 59) { //roosevelt
      $yourrefap = $yourrefar = "ifnull(concat(glhead.yourref, '/', glhead.ourref),'') as yourref";
    } else {
      $yourrefap = "ifnull(glhead.yourref,'') as yourref";
      $yourrefar = "ifnull(glhead.yourref,'') as yourref";
    }

    if ($config['params']['companyid'] == 16) { //ati
      $addonfields1 = ",(select group_concat(distinct ref) from glstock where trno=apledger.trno) as poref, info.pdeadline, glhead.tax, glhead.vattype,
      (select group_concat(info.ctrlno) from glstock as s left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline where s.trno=apledger.trno) as ctrlno";
      $addonfields2 = ",'' as poref, info.pdeadline, glhead.tax, glhead.vattype, '' as ctrlno ";
      $addonfields3 = ",'' as poref, info.pdeadline, glhead.tax, glhead.vattype, '' as ctrlno ";
      $addleftjoin = " left join hcntnuminfo as info on info.trno=glhead.trno ";
      $addleftjoinjc = " left join hcntnuminfo as info on info.trno=glhead.trno ";

      if ($adminid != 0) {
        $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
        $addfilter .= " and info.trnxtype='" . $trnx . "' ";
      }
      if ($config['params']['lookupclass'] == 'unpaiddm') {
        $addfilter .= " and cntnum.doc='DM'";
      } else {
        $addfilter .= " and cntnum.doc<>'DM'";
      }
    }

    if ($config['params']['companyid'] == 28) { //xcomp
      $addonfields1 = ",ifnull(glhead.ourref,'') as ourref ";
      $addonfields2 = ",ifnull(glhead.ourref,'') as ourref ";
      $addonfields3 = ",ifnull(glhead.ourref,'') as ourref ";
    }
    if ($config['params']['companyid'] == 34) { //evergreen
      $addleftjoin = " left join heahead as app on app.trno=glhead.aftrno left join heainfo as i on i.trno = app.trno ";
      $addonfields1 = ",ifnull(i.clientname,'') as planholder ";
      $addonfields2 = ",ifnull(i.clientname,'') as planholder ";
      $addonfields3 = ",'' as planholder ";
    }
    $addrem = " gldetail.rem ";
    if ($config['params']['companyid'] == 37) { //megacrystal
      $addrem = " (case when glhead.doc = 'SJ' then glhead.rem else gldetail.rem end) ";
    }
    if ($systype == 'REALESTATE') {
      $re_fields = ',gldetail.phaseid,gldetail.modelid,gldetail.blklotid,gldetail.amenityid,gldetail.subamenityid';
    }

    $filterAlias = " and coa.alias not in ('APWT1','APTX3')";
    if ($config['params']['companyid'] == 19) { //housegem
      $filterAlias = " and coa.alias<>'APTX3'";
    }

    $qry = "select concat(apledger.trno,apledger.line) as keyid,glhead.doc,ctbl.client,ctbl.clientname,apledger.docno,apledger.trno,apledger.line,apledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    apledger.clientid,FORMAT(apledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,FORMAT(apledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, round(apledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(apledger.dateid,10) as dateid,
    abs(apledger.fdb-apledger.fcr) as fdb,$yourrefap,$addrem as rem,glhead.rem as hrem, gldetail.ref,gldetail.projectid,gldetail.subproject,gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate, glhead.invoiceno,
    ifnull(glhead.due,'') as due " . $addonfields1 . " 
    $re_fields
    from (apledger
    left join coa on coa.acnoid=apledger.acnoid)
    left join glhead on glhead.trno = apledger.trno
    left join gldetail on gldetail.trno=apledger.trno and gldetail.line=apledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = apledger.clientid " . $addleftjoin . " 
    where cntnum.center=" . $center . " and apledger.bal<>0 " . $filterAlias . $clientfilter . " " . $addfilter . $docfilter . "
    union all
    select concat(arledger.trno,arledger.line) as keyid,glhead.doc,ctbl.client,ctbl.clientname,case cntnum.recontrno when 0 then arledger.docno else gldetail.ref end as docno,arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,left(arledger.dateid,10) as dateid,
    0 as fdb,$yourrefar,$addrem as rem,glhead.rem as hrem, gldetail.ref,gldetail.projectid,gldetail.subproject,gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate, glhead.invoiceno,
    ifnull(glhead.due,'') as due " . $addonfields2 . " 
    $re_fields
    from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid " . $addleftjoin . " 
    where cntnum.center=" . $center . " and arledger.bal<>0  " . $clientfilter . " " . $addfilter . "
    union all
    select concat(apledger.trno,apledger.line) as keyid,glhead.doc,ctbl.client,ctbl.clientname,apledger.docno,apledger.trno,apledger.line,apledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    apledger.clientid,FORMAT(apledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,FORMAT(apledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, FORMAT(apledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(apledger.dateid,10) as dateid,
    abs(apledger.fdb-apledger.fcr) as fdb,$yourrefap,$addrem as rem,glhead.rem as hrem, gldetail.ref,gldetail.projectid,gldetail.subproject,gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate, '' as invoiceno,
    '' as due " . $addonfields3 . " 
    $re_fields
    from (apledger
    left join coa on coa.acnoid=apledger.acnoid)
    left join hjchead as glhead on glhead.trno = apledger.trno
    left join gldetail on gldetail.trno=apledger.trno and gldetail.line=apledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = apledger.clientid " . $addleftjoinjc . "
    where cntnum.center=" . $center . " and apledger.bal<>0 " . $filterAlias . $clientfilter . " " . $addfilter . $docfilter . " order by dateid, yourref";

    return $data = $this->coreFunctions->opentable($qry);
  } // end function

  public function getchecks($config)
  {
    $systype = $this->companysetup->getsystemtype($config['params']);

    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $re_fields = "";
    $filter = '';
    $params = [$center, $center];

    $filtersearch = '';
    $search = '';

    if ($doc != 'DS') {
      $filter = ' and client.client=?';
      $params = [$center, $client, $center, $client];
    } else {
      $params = [$center, $center];
    }

    if (isset($config['params']['search'])) {
      $searchfield = ['docno', 'client', 'clientname', 'acnoname', 'checkno', 'checkdate',  'yourref'];
      $search = $config['params']['search'];
      $filtersearch = $this->othersClass->multisearch($searchfield, $search);
    }


    if ($systype == 'REALESTATE') {

      $re_fields = "
        ,d.projectid,

        d.phaseid, 

        d.modelid, 

        d.blklotid, 
        
        d.amenityid,
        
        d.subamenityid";
    }

    $qry = "select * from (
    select concat(crledger.trno,crledger.line) as keyid,crledger.docno, crledger.trno, crledger.line, crledger.checkno,left(crledger.checkdate,10) as checkdate,
    crledger.db, crledger.cr,coa.acno, coa.acnoname, client.client, client.clientname,d.fdb,ifnull(gl.yourref,'') as yourref,d.fcr,d.cur,d.forex,d.project as costcenter $re_fields
    from crledger 
    left join coa on coa.acnoid=crledger.acnoid 
    left join client on client.clientid=crledger.clientid
    left join cntnum on cntnum.trno=crledger.trno 
    left join gldetail as d on d.trno = crledger.trno and d.line = crledger.line
    left join glhead as gl on gl.trno=d.trno
    where depodate is null and cntnum.center=? " . $filter . " 
    union all
    select concat(caledger.trno,caledger.line) as keyid,caledger.docno, caledger.trno, caledger.line, concat('CASH' , caledger.checkno) as checkno, left(caledger.dateid,10) as checkdate,
    caledger.db, caledger.cr, coa.acno, coa.acnoname, client.client, client.clientname,d.fdb,ifnull(gl.yourref,'') as yourref,d.fcr,d.cur,d.forex,d.project as costcenter $re_fields
    from caledger 
    left join coa on coa.acnoid=caledger.acnoid 
    left join client on client.clientid=caledger.clientid
    left join cntnum on cntnum.trno=caledger.trno 
    left join gldetail as d on d.trno = caledger.trno and d.line = caledger.line
    left join glhead as gl on gl.trno=d.trno
    where depodate is null and cntnum.center=? " . $filter . " ) as tbl 
    where 1=1 " . $filtersearch . "
    order by checkdate,docno";

    return $data = $this->coreFunctions->opentable($qry, $params);
  } //end function

  public function getprojchecks($config)
  {
    $systype = $this->companysetup->getsystemtype($config['params']);

    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $proj = $config['params']['addedparams'][0];

    $re_fields = "";
    $filter = '';
    $params = [$center, $center];

    $filtersearch = '';
    $search = '';


    if (isset($config['params']['search'])) {
      $searchfield = ['docno', 'client', 'clientname', 'acnoname', 'checkno', 'checkdate',  'yourref'];
      $search = $config['params']['search'];
      $filtersearch = $this->othersClass->multisearch($searchfield, $search);
    }

    $qry = "select * from (
    select concat(crledger.trno,crledger.line) as keyid,crledger.docno, crledger.trno, crledger.line, crledger.checkno,left(crledger.checkdate,10) as checkdate,
    crledger.db, crledger.cr,coa.acno, coa.acnoname, client.client, client.clientname,
    d.fdb,ifnull(gl.yourref,'') as yourref,d.fcr,d.cur,d.forex,d.project as costcenter,gl.projectid $re_fields
    from crledger 
    left join coa on coa.acnoid=crledger.acnoid 
    left join client on client.clientid=crledger.clientid
    left join cntnum on cntnum.trno=crledger.trno 
    left join gldetail as d on d.trno = crledger.trno and d.line = crledger.line
    left join glhead as gl on gl.trno=d.trno
    where depodate is null and gl.projectid=$proj and cntnum.center=? " . $filter . " 
    union all
    select concat(caledger.trno,caledger.line) as keyid,caledger.docno, caledger.trno, caledger.line, concat('CASH' , caledger.checkno) as checkno, left(caledger.dateid,10) as checkdate,
    caledger.db, caledger.cr, coa.acno, coa.acnoname, client.client, client.clientname,
    d.fdb,ifnull(gl.yourref,'') as yourref,d.fcr,d.cur,d.forex,d.project as costcenter,gl.projectid $re_fields
    from caledger 
    left join coa on coa.acnoid=caledger.acnoid 
    left join client on client.clientid=caledger.clientid
    left join cntnum on cntnum.trno=caledger.trno 
    left join gldetail as d on d.trno = caledger.trno and d.line = caledger.line
    left join glhead as gl on gl.trno=d.trno
    where depodate is null and gl.projectid=$proj and cntnum.center=? " . $filter . " ) as tbl 
    where 1=1  " . $filtersearch . "
    order by checkdate,docno";
    return $data = $this->coreFunctions->opentable($qry, $params);
  } //end function


  public function getkrunpaid($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $join = '';
    $addfield = '';
    if ($companyid == 24) { //goodfound
      $join = "   
      left join glstock as stock on stock.trno=glhead.trno and stock.refx<>0 and stock.linex<>0
      left join transnum as so on so.trno=stock.refx
      ";
      $addfield = ",concat(so.doc,so.seq) as sodocno";
    }
    $qry = "select concat(arledger.trno,arledger.line) as keyid,ctbl.client,arledger.docno,arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(arledger.dateid,10) as dateid,
    0 as fdb,ifnull(glhead.ourref,'') as yourref,gldetail.rem,glhead.rem as hrem,glhead.project $addfield
    from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid
    $join
    where ctbl.client=? and cntnum.center=? and arledger.bal<>0 and arledger.kr=0 order by dateid";


    return $data = $this->coreFunctions->opentable($qry, [$client, $center]);
  } // end function

  public function getkrallunpaid($config)
  {
    $center = $config['params']['center'];
    $join = '';
    $addfield = '';

    $qry = "select concat(arledger.trno,arledger.line) as keyid,ctbl.client,arledger.docno,
    arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,
    round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, 
    round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,
    left(arledger.dateid,10) as dateid,ctbl.clientname,
    0 as fdb,ifnull(glhead.ourref,'') as yourref,gldetail.rem,glhead.rem as hrem,glhead.project $addfield
    from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid
    $join
    where cntnum.center=? and arledger.bal<>0 and arledger.kr=0 order by dateid";


    return $data = $this->coreFunctions->opentable($qry, [$center]);
  } // end function

  public function getrcunpaid($config)
  {
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];

    $union = '';
    $datef = '';
    $datef2 = '';


    if ($companyid == 59 && $doc == 'RD') { //roosevelt
      $date = $this->coreFunctions->getfieldvalue("rdhead", "dateid", "trno=?", [$config['params']['trno']]);
      $date      = date("Y-m-d", strtotime($date));
      $datef = " and date(head.dateid) <= '" . $date . "'";
      $datef2 = " and date(detail.checkdate) <= '" . $date . "'";

      $union = "union all
      select head.doc,concat(detail.trno,detail.line) as keyid,detail.trno,detail.line,head.docno,
                   date(head.dateid) as dateid,ag.clientname as agent,detail.bank,'' as checkno,
                   date(head.dateid) as checkdate,detail.amount,detail.branch, concat(head.yourref, '/', head.ourref) as yourref,
                   cl.client,detail.clientid,cl.clientname
            from hrhhead as head
            left join hrhdetail as detail on detail.trno=head.trno
            left join client as ag on ag.client=head.agent
            left join transnum as num on num.trno=head.trno
            left join client as cl on cl.clientid =detail.clientid
            where num.center=? and detail.rdtrno = 0 $datef ";
    }

    $qry = "select head.doc,concat(detail.trno,detail.line) as keyid,detail.trno,detail.line,head.docno,
                   date(head.dateid) as dateid,ag.clientname as agent,detail.bank,detail.checkno,
                   detail.checkdate,detail.amount,detail.branch, concat(head.yourref, '/', head.ourref) as yourref,
                   detail.client,cl.clientid,cl.clientname
            from hrchead as head
            left join hrcdetail as detail on detail.trno=head.trno
            left join client as ag on ag.client=head.agent
            left join transnum as num on num.trno=head.trno
            left join client as cl on cl.client =detail.client
            where num.center=? and detail.rdtrno = 0 $datef2 $union ";

    return $data = $this->coreFunctions->opentable($qry, [$center, $center]);
  } // end function

  public function getkaunpaid($config)
  {
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $qry = "select concat(arledger.trno,arledger.line) as keyid,ctbl.client,arledger.docno,arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(arledger.dateid,10) as dateid,
    0 as fdb,ifnull(glhead.ourref,'') as yourref,gldetail.rem,glhead.rem as hrem,glhead.project 
    from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid
    
    where cntnum.center=? and arledger.bal<>0 and arledger.ka=0 order by dateid";

    return $data = $this->coreFunctions->opentable($qry, [$center]);
  } // end function

  public function getpyunpaid($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];

    $qry = "select concat(apledger.trno,apledger.line) as keyid,ctbl.client,apledger.docno,apledger.trno,apledger.line,apledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    apledger.clientid,round(apledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(apledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, round(apledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(apledger.dateid,10) as dateid,
    0 as fdb,ifnull(glhead.ourref,'') as yourref,gldetail.rem,glhead.rem as hrem,glhead.project 
    from (apledger
    left join coa on coa.acnoid=apledger.acnoid)
    left join glhead on glhead.trno = apledger.trno
    left join gldetail on gldetail.trno=apledger.trno and gldetail.line=apledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = apledger.clientid
    
    where ctbl.client=? and cntnum.center=? and apledger.bal<>0 and apledger.py=0 order by dateid";


    return $data = $this->coreFunctions->opentable($qry, [$client, $center]);
  } // end function


  public function getaccess($user)
  {
    $access = $this->othersClass->getAccess($user);
    return json_decode(json_encode($access), true);
  }

  public function checksecurity($config, $accessid, $path)
  {
    $btnadd = [];
    $access = $this->getaccess($config['params']['user']);
    $isvalid = $access[0]['attributes'][$accessid - 1];
    if ($isvalid != 0) {
      $btnadd = ['path' => $path];
    }
    return $btnadd;
  }

  public function getcurriculum($config)
  {
    switch ($config['params']['doc']) {
      case 'ES':
      case 'EI':
      case 'EN_STUDENT':
        $trno = 0;
        break;
      default:
        $trno = $config['params']['trno'];
        break;
    }
    switch ($config['params']['doc']) {
      case 'EN_STUDENT':
        $qry = "select trno as curriculumtrno, docno, docno as curriculumdocno, curriculumcode, curriculumname from en_glhead where doc='EC' and courseid=?";
        $data = $this->coreFunctions->opentable($qry, [$config['params']['addedparams'][0]]);
        break;
      case 'ES':
      case 'EI':
        $qry = "select head.trno as curriculumtrno, head.docno, head.docno as curriculumdocno, head.curriculumcode, head.curriculumname, head.levelid, l.levels as level from en_glhead as head left join en_levels as l on l.line=head.levelid  where head.doc='EC' and head.courseid=? and head.syid=?";
        $data = $this->coreFunctions->opentable($qry, [$config['params']['addedparams'][0], $config['params']['addedparams'][1]]);
        break;
      case 'EG':
        $qry = "select distinct h.trno,h.docno,h.curriculumcode,course.coursecode,course.coursename,l.levels as level,sy.sy,y.year as yearnum, h.curriculumname,sem.term as terms
        from en_glhead AS h left join en_glyear as y on y.trno=h.trno left join en_glsubject as s on s.trno=y.trno and y.line=s.cline left join en_subject as sub on sub.trno=s.subjectid
        left join en_course as course on course.line=h.courseid left join en_levels as l on l.line=h.levelid left join en_schoolyear as sy on sy.line=h.syid left join en_term as sem on sem.line=y.semid
          where h.doc='EC' and h.courseid= ? and h.syid= ?";
        $filter = $this->coreFunctions->opentable("select courseid, syid from en_sgshead where trno = $trno");
        if ($filter) {
          $course = $filter[0]->courseid;
          $sy = $filter[0]->syid;
        }
        $data = $this->coreFunctions->opentable($qry, [$course, $sy]);
        break;
      case 'EC':
        $qry = "select distinct h.trno as curriculumtrno,h.docno,h.curriculumcode,course.coursecode,course.coursename,l.levels as level,sy.sy, h.curriculumname
          from en_glhead AS h left join en_glyear as y on y.trno=h.trno left join en_glsubject as s on s.trno=h.trno left join en_subject as sub on sub.trno=s.subjectid
          left join en_course as course on course.line=h.courseid left join en_levels as l on l.line=h.levelid left join en_schoolyear as sy on sy.line=h.syid where h.doc='EC'";
        $data = $this->coreFunctions->opentable($qry);
        break;
    }

    return $data;
  }

  public function getledgerapplicant($config)
  {
    $fields = array('emplast', 'empfirst', 'empmiddle', 'empcode', 'address');
    $filter = '';
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    $qry = "select empid,empid as clientid,empcode,concat(emplast,', ',empfirst,' ',empmiddle) as empname,address,empmiddle,emplast,empfirst from app where ''='' " . $filter;

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function regstudentbatch($config)
  {
    $tableid = $config['params']['trno'];
    $data = [];
    $row = [];
    $data2 = [];
    $i = 0;


    $qry = "select head.trno,head.docno,head.yr,head.curriculumdocno,head.adviserid,head.courseid,head.periodid,head.syid,head.semid,head.sectionid from en_glhead as head where head.doc='es' and head.trno=?";
    $datalist = $this->coreFunctions->opentable($qry, [$tableid]);

    foreach ($datalist as $key => $value) {
      if (!empty($datalist[$key]->trno)) {
        $docno = $datalist[$key]->docno;
        $curriculumdocno = $datalist[$key]->curriculumdocno;
        $adviserid = $datalist[$key]->adviserid;
        $courseid = $datalist[$key]->courseid;
        $periodid = $datalist[$key]->periodid;
        $syid = $datalist[$key]->syid;
        $semid = $datalist[$key]->semid;
        $sectionid = $datalist[$key]->sectionid;
        $yr = $datalist[$key]->yr;

        $qry =  "select distinct head.docno,head.dateid,client.client,client.clientname,client.clientid,subject.semid from glhead as head left join client on client.clientid=head.clientid left join glsubject as subject on subject.trno=head.trno
        where subject.screfx=0 and head.doc='ER'  and head.syid=? and head.periodid=? and head.courseid=? and subject.semid=? and head.sectionid=? and head.yr=?";

        $data = $this->coreFunctions->opentable($qry, [$syid, $periodid, $courseid, $semid, $sectionid, $yr]);
      }
    }


    foreach ($data  as $key2 => $value) {
      if (!empty($data[$key2]->clientid)) {
        $clientid = $data[$key2]->clientid;
        $docno = $data[$key2]->docno;
        $client = $data[$key2]->client;
        $clientname = $data[$key2]->clientname;
        $dateid = $data[$key2]->dateid;
        $qry = "select head.trno,head.docno,head.yr,head.curriculumdocno,head.adviserid,head.courseid,head.periodid,head.syid,head.semid,head.sectionid from en_glhead as head where head.doc='es' and head.trno=?";
        $datahead = $this->coreFunctions->opentable($qry, [$tableid]);

        foreach ($datahead as $key => $value) {
          if (!empty($datahead[$key]->trno)) {
            $docno = $datahead[$key]->docno;
            $trno = $datahead[$key]->trno;
            $curriculumdocno = $datahead[$key]->curriculumdocno;
            $adviserid = $datahead[$key]->adviserid;
            $courseid = $datahead[$key]->courseid;
            $periodid = $datahead[$key]->periodid;
            $syid = $datahead[$key]->syid;
            $semid = $datahead[$key]->semid;
            $sectionid = $datahead[$key]->sectionid;
            $yr = $datahead[$key]->yr;

            $qry =  "select distinct head.sotrno,head.trno," . $trno . " as sctrno,'" . $curriculumdocno . "' as curriculumdocno,client.clientid,head.docno,head.dateid,client.client,client.clientname,client.clientid,head.sectionid,head.yr,subject.semid from glhead as head left join client on client.clientid=head.clientid left join glsubject as subject on subject.trno=head.trno
            where head.doc='ER'  and head.syid=? and head.periodid=? and head.courseid=? and subject.semid=? and head.sectionid=? and head.yr=? and client.clientid=?  and subject.screfx=0";

            $data2 = $this->coreFunctions->opentable($qry, [$syid, $periodid, $courseid, $semid, $sectionid, $yr, $clientid]);
            $status =  $this->generatestudentcurriculum($data2);
            if ($status['status'] ==  false) {
              return ['status' => false, 'msg' => $status['msg']];
            }
            $this->coreFunctions->execqry("update en_studentinfo set schedtrno=? where clientid=?", 'update', [$trno, $clientid]);
          }
        }
        $i = $i + 1;
      }
    }

    return ['status' => true, 'msg' => 'Generate ' . $i . ' students successfully.'];
  } //end function

  public function generatestudentcurriculum($data)
  {
    $status = true;
    $clientid = $data[0]->clientid;
    $curriculumdocno = $data[0]->curriculumdocno;
    $sotrno = $data[0]->sotrno;
    $schedtrno = $data[0]->sctrno;
    $semid = $data[0]->semid;
    $sectionid =  $this->coreFunctions->datareader("select sectionid as value from en_glhead where trno=?", [$schedtrno]);
    $schedyear  = $this->coreFunctions->datareader("select yr as value from en_glhead where trno=?", [$schedtrno]);


    $curriculumdocno = $data[0]->curriculumdocno;
    $cctrno  = $this->coreFunctions->datareader("select trno as value from transnum where docno=?", [$curriculumdocno]);
    $this->coreFunctions->execqry("update en_studentinfo set curriculumtrno=?, sectionid=?, yr=? where clientid=?", 'update', [$cctrno,  $sectionid, $schedyear, $clientid]);

    $trno = $this->coreFunctions->datareader("select curriculumtrno as value from en_studentinfo where clientid=?", [$clientid]);
    $courseid = $this->coreFunctions->datareader("select courseid as value from en_studentinfo where clientid=?", [$clientid]);
    if ($trno == '') return ['msg' => 'Please select curriculum first.', 'status' => false];
    $sub = $this->coreFunctions->opentable("select h.docno,s.trno,s.line,s.cline,s.subjectid,s.linex,s.refx,s.schedstarttime,s.schedendtime,s.roomid,s.bldgid,s.schedday,s.instructorid,y.semid
    from en_glhead as h left join en_glyear as y on y.trno=h.trno left join en_glsubject as s on s.trno=y.trno and y.line=s.cline where s.trno=? and y.year=? and y.semid=?", [$trno, $schedyear, $semid]);
    $data = [];

    foreach ($sub as $subject) {

      $qry = "select distinct schead.trno,scstock.line,sostock.subjectid,scstock.schedstarttime,scstock.schedendtime,scstock.roomid,scstock.bldgid,scstock.schedday,scstock.instructorid,sostock.semid
      from en_glhead as sohead left join en_glsubject as sostock on sostock.trno=sohead.trno
      left join  en_glhead as cchead on sohead.curriculumtrno=cchead.trno
      left join  en_glhead as schead on cchead.docno=schead.curriculumdocno
      left join  en_glsubject as scstock on scstock.trno=schead.trno and scstock.subjectid=sostock.subjectid
      where sohead.curriculumtrno=? and scstock.trno is not null and schead.trno=? and sostock.semid=? ";
      $sodetail = $this->coreFunctions->opentable($qry, [$cctrno, $schedtrno, $semid]);

      foreach ($sodetail as $sosubject) {

        $persub = $this->coreFunctions->opentable("select trno,line, cline, subjectid, linex,refx,schedstarttime,schedendtime,roomid,bldgid,schedday,instructorid,semid from en_glsubject where trno=? and subjectid=?", [$trno, $sosubject->subjectid]);
        if (empty($persub)) return ['msg' => 'Curriculum empty. Please try again.', 'status' => false];

        //insert in EA/EI
        $this->coreFunctions->execqry(
          "update en_glsubject set refx=?,linex=?,schedstarttime=?,schedendtime=?,roomid=?,bldgid=?,schedday=?,instructorid=? where trno=? and subjectid=? and semid=?",
          'update',
          [$sosubject->trno, $sosubject->line,  $sosubject->schedstarttime, $sosubject->schedendtime, $sosubject->roomid, $sosubject->bldgid, $sosubject->schedday, $sosubject->instructorid, $sotrno, $sosubject->subjectid, $semid]
        );

        //insert in ER
        $this->coreFunctions->execqry(
          "update glsubject set screfx=?,sclinex=?,schedstarttime=?,schedendtime=?,roomid=?,bldgid=?,schedday=?,instructorid=? where refx=? and subjectid=? and semid=?",
          'update',
          [$sosubject->trno, $sosubject->line,  $sosubject->schedstarttime, $sosubject->schedendtime, $sosubject->roomid, $sosubject->bldgid, $sosubject->schedday, $sosubject->instructorid, $sotrno, $sosubject->subjectid, $semid]
        );
        if ($this->setservedsubject($sosubject->trno, $sosubject->line) == 0) {
          $this->coreFunctions->execqry(
            "update glsubject set screfx=0,sclinex=0,schedstarttime=null,schedendtime=null,roomid=0,bldgid=0,schedday='',instructorid=0 where refx=? and subjectid=? and semid=?",
            'update',
            [$sotrno, $sosubject->subjectid, $semid]
          );
          return ['status' => false, 'msg' => 'Reached Maximum Student Slots', 'data' => $data];
        }

        $this->coreFunctions->execqry(
          "insert into en_scurriculum(trno, line, clientid, cline, subjectid, courseid, grade) values(?, ?, ?, ?, ?, ?, ?)",
          'insert',
          [$trno, $subject->line, $clientid, $subject->cline, $subject->subjectid, $courseid, 0]
        );
      }
    }
    return ['status' => true, 'msg' => 'Curriculum generated.', 'data' => $data];
  }

  public function setservedsubject($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock.trno from en_sjhead as head left join en_sjsubject as
        stock on stock.trno=head.trno where head.doc='ER' and stock.screfx=" . $refx . " and stock.sclinex=" . $linex;

    $qry1 = $qry1 . " union all select stock.trno from glhead as head left join glsubject as stock on stock.trno=
        head.trno where head.doc='ER' and stock.screfx=" . $refx . " and stock.sclinex=" . $linex;

    $qry2 = "select ifnull(count(trno),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update en_glsubject set asqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function generateenbilling($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $headtable = 'glhead';
    $subjecttable = 'glsubject';
    $otherfeestable = 'glotherfees';
    $credentialstable = 'glcredentials';
    $assesstable = 'glsummary';
    $detailtable = 'gldetail';
    $tablenum = 'cntnum';

    $qry = "select head.trno,head.doc,head.docno,head.dateid,head.periodid,head.syid,head.deptid,head.levelid,head.yr,head.semid,head.sectionid,client.clientid,
    head.terms,head.courseid,head.contra,head.editdate,head.editby,head.createdate,head.createby,head.encodeddate,head.encodedby,head.viewdate,
    head.viewby,head.lockdate,head.rem,head.modeofpayment,head.sectionid,head.isenrolled,head.disc
    FROM en_glhead as head left join transnum as cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid where head.trno=? limit 1;";


    $datahead = $this->coreFunctions->opentable($qry, [$trno]);

    $msg1 = '';
    $msg2 = 'Already Generated billing! ';

    $eadocno = $datahead[0]->docno;
    $dateid = $datahead[0]->dateid;
    $clientid = $datahead[0]->clientid;
    $rem = $datahead[0]->rem;
    $yr = $datahead[0]->yr;
    $modeofpayment = $datahead[0]->modeofpayment;
    $courseid = $datahead[0]->courseid;
    $periodid = $datahead[0]->periodid;
    $syid = $datahead[0]->syid;
    $semid = $datahead[0]->semid;
    $levelid = $datahead[0]->levelid;
    $deptid = $datahead[0]->deptid;
    $contra = $datahead[0]->contra;
    $sectionid = $datahead[0]->sectionid;
    $isenrolled = $datahead[0]->isenrolled;
    $disc = $datahead[0]->disc;

    if ($isenrolled) {
      $msg1 = $this->coreFunctions->datareader("select docno as value from glhead where sotrno='" . $trno . "'");
      return ['status' => false, 'msg' => $msg2 . $msg1];
    }

    $pref = 'ER';
    $docno = 'ER';

    $clientname = $this->coreFunctions->datareader("select clientname as value from client where clientid='" . $clientid . "'");

    $center = $config['params']['center'];
    $config['params']['doc'] = 'ER';
    $config['params']['isposted'] = 1;
    $config['params']['return'] = '';

    $config['docmodule']->tablenum = 'cntnum';

    $insertcntnum = 0;
    $docnolength =  $this->companysetup->getdocumentlength($config['params']);


    while ($insertcntnum == 0) {
      $seq = $this->othersClass->getlastseq($pref, $config);
      if ($seq == 0 || empty($pref)) {
        if (empty($pref)) {
          $pref = strtoupper($docno);
        }
        $seq = $this->othersClass->getlastseq($pref, $config);
      }

      $poseq = $pref . $seq;
      $newdocno = $this->othersClass->PadJ($poseq, $docnolength);


      if (!empty($center) || $center != '') {
        $col = [];
        $col = ['doc' => $config['params']['doc'], 'docno' => $newdocno, 'seq' => $seq, 'bref' => $config['params']['doc'], 'center' => $center];
        $table = $config['docmodule']->tablenum;
        $insertcntnum =  $this->coreFunctions->insertGetId($table, $col);
        $i = +1;
      } else {
        $insertcntnum = -1;
      } //end if empty center
    }

    $qry = "select trno,docno from cntnum where doc = ? and docno = ? and center = ?";
    $trno_ =  $this->coreFunctions->opentable($qry, ['ER', $newdocno, $center]);

    $ertrno = $trno_[0]->trno;
    $docno = $trno_[0]->docno;
    $user = $config['params']['user'];

    $this->coreFunctions->execqry("update en_glhead set isenrolled=1 where trno=? ", "update", [$trno]);

    $this->coreFunctions->execqry("insert into " . $headtable . " (trno,doc,docno,clientid,rem,dateid,yr,modeofpayment,contra,courseid,periodid,syid,semid,levelid,deptid,sotrno,sectionid,assessref,disc)
                                  values('" . $ertrno . "','ER','" . $newdocno . "','" . $clientid . "', '" . $rem . "', '" . $dateid . "','" . $yr . "', '" . $modeofpayment . "', '" . addslashes($contra) . "',
                                  '" . $courseid . "', '" . $periodid . "', '" . $syid . "','" . $semid . "', '" . $levelid . "', '" . $deptid . "','" . $trno . "','" . $sectionid . "','" . $eadocno . "','" . $disc . "')", "insert");

    $this->logger->sbcwritelog($ertrno, $config, 'CREATE', $newdocno . ' FROM EA -' . $docno . ' STUDENT -' . $clientname, $newdocno);

    $b = 1;
    if (!empty($datahead)) {
      // for subject
      $qry = "insert into " . $subjecttable . "(trno,line,schedday,units,lecture,laboratory,hours,subjectid,roomid,bldgid,instructorid,schedstarttime,schedendtime,refx,linex,screfx,sclinex,ctrno,cline,scline,semid)
        select " . $ertrno . ",stock.line,stock.schedday,stock.units,stock.lecture,stock.laboratory,stock.hours,stock.subjectid,stock.roomid,stock.bldgid,stock.instructorid,stock.schedstarttime,stock.schedendtime,stock.trno,stock.line,stock.refx,stock.linex,stock.ctrno,stock.cline,stock.scline,stock.semid
        FROM en_glsubject as stock where stock.trno =? ";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into " . $otherfeestable . " (trno,line,isamt,rem,feesid,acnoid) select " . $ertrno . ",stock.line,stock.isamt,stock.rem,stock.feesid,stock.acnoid from en_glotherfees as stock where stock.trno= ?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $qry = "insert into " . $credentialstable . " (trno,line,amt,percentdisc,camt,credentialid,acnoid,feesid,subjectid) select " . $ertrno . ",stock.line,stock.amt,stock.percentdisc,stock.camt,stock.credentialid,stock.acnoid,stock.feesid,stock.subjectid from en_glcredentials as stock where stock.trno= ?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $qry = "insert into " . $assesstable . " (trno,line,amt,feesid,schemeid) select " . $ertrno . ",stock.line,stock.amt,stock.feesid,stock.schemeid from en_glsummary as stock where stock.trno= ?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
              $this->createdistribution($config, $ertrno);

              $this->coreFunctions->execqry("update en_studentinfo set regtrno=? where clientid=?", 'update', [$ertrno, $clientid]);

              $date = $this->othersClass->getCurrentTimeStamp();
              $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
              $this->coreFunctions->sbcupdate($tablenum, $data, ['trno' => $ertrno]);

              $this->logger->sbcwritelog($ertrno, $config, 'CREATED AND POSTED REGISTRATION ', $newdocno);
              return ['trno' => $trno, 'status' => true, 'msg' => 'Billing was successfully created. Reference ' . $newdocno];
            }
          }
        }
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Billing Head'];
    }
  } //end function



  public function createdistribution($config, $trno)
  {
    $headtable = 'glhead';
    $subjecttable = 'glsubject';
    $otherfeestable = 'glotherfees';
    $credentialstable = 'glcredentials';
    $assesstable = 'glsummary';
    $detailtable = 'gldetail';
    $tablenum = 'cntnum';
    $status = true;


    $qry = "select head.dateid,client.client,head.modeofpayment,head.courseid,f.feestype,head.contra,stock.amt,'SUMMARY' as ftype,head.disc
      from " . $headtable . " as head left join " . $assesstable . " as stock on stock.trno=head.trno left join client on client.clientid=head.clientid
      left join en_fees as f on f.line=stock.feesid
      where f.feestype not in ('OTHERS','MISC')  and ifnull(stock.amt,0)>0 and  head.trno=?
      union all
      select head.dateid,client.client,head.modeofpayment,head.courseid,f.feestype,coa.acno,stock.amt,'OTHERFEES' as ftype,head.disc
      from " . $headtable . " as head left join " . $assesstable . " as stock on stock.trno=head.trno left join client on client.clientid=head.clientid
      left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=f.acnoid
      where f.feestype in ('MISC')  and ifnull(stock.amt,0)>0 and  head.trno=?
      union all
      select head.dateid,client.client,head.modeofpayment,head.courseid,f.feestype,coa.acno,stock.isamt,'OTHERFEES' as ftype,head.disc
      from  " . $headtable . " as head left join  " . $otherfeestable . " as stock on stock.trno=head.trno left join client on client.clientid=head.clientid
      left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid
      where  ifnull(stock.isamt,0)>0 and head.trno=?
      union all
      select head.dateid,client.client,head.modeofpayment,head.courseid,f.feestype,coa.acno as contra,stock.camt *-1,'CREDENTIALS' as ftype,head.disc
      from " . $headtable . " as head left join " . $credentialstable . " as stock on stock.trno=head.trno left join en_credentials as c on c.line=stock.credentialid
      left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=c.acnoid left join client on client.clientid=head.clientid
      where ifnull(stock.camt,0)>0 and head.trno=?";


    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno, $trno]);

    $tax = 0;
    if (!empty($stock)) {
      $dcDisc = $stock[0]->disc;
      $strTF = $this->coreFunctions->datareader("select tfaccount as value from en_course where line=?", [$stock[0]->courseid]);

      if ($strTF != '') {
        $strCharge  = $strTF;
      } else {
        $strCharge  = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SA1']);
      }
      $strAddDisc = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SD1']);
      $strMisc  = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['MC1']);
      $strReservationFee = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AR1']);
      $strInterest = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['INT1']);
      if ($stock[0]->modeofpayment != '') {
        $intInterestMonth = $this->coreFunctions->datareader("select months as value from en_modeofpayment where code=?", [$stock[0]->modeofpayment]);
        $intInterest = $this->coreFunctions->datareader("select deductpercent as value from en_modeofpayment where code=?", [$stock[0]->modeofpayment]);
      }

      $dcTotalAmt = 0;
      $dcAmount = 0;
      $dcCredentials = 0;
      $dcOtherfees = 0;
      $strContra = '';

      $strSAContra = $this->coreFunctions->datareader("select acno as value from coa where alias='SA1'");

      $intInterest = $this->coreFunctions->datareader("select deductpercent as value from en_modeofpayment where code=?", [$stock[0]->modeofpayment]);

      foreach ($stock as $key => $value) {
        $feestype = $stock[$key]->feestype;
        $dcAmount = $dcAmount + $stock[$key]->amt;

        if ($stock[$key]->contra != '') {
          $strContra = $stock[$key]->contra;
        } else {
          $strContra = $strSAContra;
        }

        if ($stock[$key]->ftype == 'CREDENTIALS') {
          $params = [
            'client' => $stock[0]->client,
            'acno' => '',
            'ext' =>  $stock[$key]->amt * -1,
            'wh' => '',
            'date' => $stock[$key]->dateid,
            'inventory' => '',
            'revenue' => $strContra,
            'tax' =>  0,
            'discamt' => 0,
            'cost' => 0,
            'fcost' => 0,
            'project' => '',
            'rem' => $stock[$key]->ftype
          ];
          $this->distribution($params, $config);
          $dcCredentials = $dcCredentials +  $stock[$key]->amt * -1;
        } elseif ($stock[$key]->ftype == 'OTHERFEES') {
          $params = [
            'client' => $stock[0]->client,
            'acno' => '',
            'ext' =>  $stock[$key]->amt,
            'wh' => '',
            'date' => $stock[$key]->dateid,
            'inventory' => '',
            'revenue' => $strContra,
            'tax' =>  0,
            'discamt' => 0,
            'cost' => 0,
            'fcost' => 0,
            'project' => '',
            'rem' => $stock[$key]->ftype
          ];
          $this->distribution($params, $config);
          $dcOtherfees = $dcOtherfees +  $stock[$key]->amt;
        }
      }

      $dcInterests = $dcAmount * ($intInterest / 100); //number_format($dcAmount * ($intInterest / 100), $this->companysetup->getdecimal('currency', $config['params']));
      if ($intInterestMonth == '' || $intInterestMonth == '0') {
        $intInterestMonth = 1;
      }
      $dcARAmt = number_format(($dcAmount + $dcInterests) / $intInterestMonth, $this->companysetup->getdecimal('currency', $config['params']));
      $dcARAmtTotal = $dcAmount + $dcInterests - $dcDisc; //number_format($dcAmount + $dcInterests - $dcDisc, $this->companysetup->getdecimal('currency', $config['params']));

      $dcLastARAmt = $dcAmount + $dcInterests  - $dcDisc;

      $params = [];
      $x = 0;
      for ($i = 0; $i < $intInterestMonth; $i++) {
        $x = $i + 1;
        $strD = $this->coreFunctions->datareader("select date" . $x . " as value from en_modeofpayment where code=?", [$stock[0]->modeofpayment]);

        if (!empty($strD)) {
          $strMOPDate = $strD;
        } else {
          $strMOPDate = $stock[0]->dateid;
        }

        if ($i == 0) {
          $strDate = $strMOPDate;
        } else {
          if ($strMOPDate != '') {
            $strDate = $strMOPDate;
          } else {
            $strDate = $stock[0]->dateid;
          }
        }

        $dcCPERc = $this->coreFunctions->datareader("select ifnull(perc" . $x . ",1) as value from en_modeofpayment  where code=?", [$stock[0]->modeofpayment]);
        $dcARAmtComp = $dcARAmtTotal * ($dcCPERc / 100);

        if (($intInterestMonth - 1) == $i) {
          $params = [
            'client' => $stock[0]->client,
            'acno' => $stock[0]->contra,
            'ext' => $dcLastARAmt,
            'wh' => '',
            'date' => $strDate,
            'inventory' => '',
            'revenue' => '',
            'tax' =>  0,
            'discamt' => 0,
            'cost' => 0,
            'fcost' => 0,
            'project' => '',
            'rem' => ($i + 1) . " of " . (int)$intInterestMonth
          ];
        } else {
          $dcLastARAmt = $dcLastARAmt - $dcARAmtComp;

          $params = [
            'client' => $stock[0]->client,
            'acno' => $stock[0]->contra,
            'ext' => $dcARAmtComp,
            'wh' => '',
            'date' => $strDate,
            'inventory' => '',
            'revenue' => '',
            'tax' =>  0,
            'discamt' => 0,
            'cost' => 0,
            'fcost' => 0,
            'project' => '',
            'rem' => ($i + 1) . " of " . (int)$intInterestMonth
          ];
        }
        $this->distribution($params, $config);
      }
      $params = [
        'client' => $stock[0]->client,
        'acno' => '',
        'ext' => $dcInterests,
        'wh' => '',
        'date' => $stock[0]->dateid,
        'inventory' => '',
        'revenue' => $strInterest,
        'tax' =>  0,
        'discamt' => 0,
        'cost' => 0,
        'fcost' => 0,
        'project' => '',
        'rem' => '3'
      ];
      $this->distribution($params, $config);
      $params = [
        'client' => $stock[0]->client,
        'acno' => '',
        'ext' => $dcAmount - $dcCredentials - $dcDisc - $dcOtherfees,
        'wh' => '',
        'date' => $stock[0]->dateid,
        'inventory' => '',
        'revenue' => $strCharge,
        'tax' =>  0,
        'discamt' => 0,
        'cost' => 0,
        'fcost' => 0,
        'project' => '',
        'rem' => '4'
      ];
      $this->distribution($params, $config);
    }

    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
        $this->acctg[$key]['editdate'] = $current_timestamp;
        $this->acctg[$key]['editby'] = $config['params']['user'];
        $this->acctg[$key]['encodeddate'] = $current_timestamp;
        $this->acctg[$key]['encodedby'] = $config['params']['user'];
        $this->acctg[$key]['trno'] = $trno;

        $this->acctg[$key]['clientid'] = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$this->acctg[$key]['client']]);
        unset($this->acctg[$key]['client']);
      }
      if ($this->coreFunctions->sbcinsert($detailtable, $this->acctg) == 1) {
        $eatrno = $config['params']['trno'];
        $eahead = $config['docmodule']->head;
        $eadetail = $config['docmodule']->detail;

        $config['docmodule']->head = $headtable;
        $config['docmodule']->detail = $detailtable;
        $config['params']['trno'] = $trno;

        if ($this->othersClass->postingapledger($config) === 1) {
          if ($this->othersClass->postingarledger($config) === 1) {
            if ($this->othersClass->postingcrledger($config) === 1) {
              if ($this->othersClass->postingcaledger($config) === 1) {
                if ($this->othersClass->postingcbledger($config) === 1) {
                } else {
                  $msg = "Posting failed. Kindly check the detail(CB).";
                }
              } else {
                $msg = "Posting failed. Kindly check the detail(CA).";
              }
            } else {
              $msg = "Posting failed. Kindly check the detail(CR).";
            }
          } else {
            $msg = "Posting failed. Kindly check the detail(AR).";
          }
        } else {
          $msg = "Posting failed. Kindly check the detail(AP).";
        }
        $config['docmodule']->head = $eahead;
        $config['docmodule']->detail = $eadetail;
        $config['params']['trno'] = $eatrno;

        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {
    //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
    $entry = [];
    $sales = 0;
    //AR
    if ($params['acno'] != '') {
      if ($params['ext'] != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
        if ($params['ext'] >= 0) {
          $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => round(($params['ext']), 2), 'cr' => 0, 'postdate' => $params['date'], 'fdb' => 0, 'fcr' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        } else {
          $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => round(($params['ext'] * -1), 2), 'postdate' => $params['date'], 'fdb' => 0, 'fcr' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      if ($params['discamt'] >= 0) {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => round(($params['discamt']), 2), 'cr' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      } else {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => round(($params['discamt']), 2), 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //INV
    if ($params['cost'] != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      if ($params['cost'] >= 0) {
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      } else {
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $params['cost'] * -1, 'cr' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      //cogs
      $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
      if ($params['cost'] >= 0) {
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      } else {
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'] * -1, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if ($params['revenue'] != '') {
      if (floatval($params['tax']) != 0) {
        //sales
        $sales = ($params['ext'] - $params['tax']);
        $sales  = $sales + $params['discamt'];
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);

        if ($sales >= 0) {
          $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => round(($sales), 2), 'db' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        } else {
          $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => 0, 'db' => round(($sales * -1), 2), 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        }

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => round(($params['tax']), 2), 'db' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      } else {
        //sales
        $sales = round(($params['ext'] + $params['discamt']), 2);
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        if ($sales >= 0) {
          $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => round(($sales), 2), 'db' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        } else {
          $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => 0, 'db' => round(($sales * -1), 2), 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function dropstudent($config)
  {
    $tableid = $config['params']['trno'];
    $data = [];
    $row = [];
    $data2 = [];
    $i = 0;


    $qry = "select head.trno,head.docno,head.yr,head.curriculumdocno,head.adviserid,head.courseid,head.periodid,head.syid,head.semid,head.sectionid from en_glhead as head where head.doc='es' and head.trno=?";
    $datalist = $this->coreFunctions->opentable($qry, [$tableid]);

    foreach ($datalist as $key => $value) {
      if (!empty($datalist[$key]->trno)) {
        $docno = $datalist[$key]->docno;
        $curriculumdocno = $datalist[$key]->curriculumdocno;
        $adviserid = $datalist[$key]->adviserid;
        $courseid = $datalist[$key]->courseid;
        $periodid = $datalist[$key]->periodid;
        $syid = $datalist[$key]->syid;
        $semid = $datalist[$key]->semid;
        $sectionid = $datalist[$key]->sectionid;
        $yr = $datalist[$key]->yr;

        $qry =  "select distinct head.docno,head.dateid,client.client,client.clientname,client.clientid,subject.semid from glhead as head left join client on client.clientid=head.clientid left join glsubject as subject on subject.trno=head.trno
        where subject.screfx=0 and head.doc='ER'  and head.syid=? and head.periodid=? and head.courseid=? and subject.semid=? and head.sectionid=? and head.yr=?";

        $data = $this->coreFunctions->opentable($qry, [$syid, $periodid, $courseid, $semid, $sectionid, $yr]);
      }
    }


    foreach ($data  as $key2 => $value) {
      if (!empty($data[$key2]->clientid)) {
        $clientid = $data[$key2]->clientid;
        $docno = $data[$key2]->docno;
        $client = $data[$key2]->client;
        $clientname = $data[$key2]->clientname;
        $dateid = $data[$key2]->dateid;
        $qry = "select head.trno,head.docno,head.yr,head.curriculumdocno,head.adviserid,head.courseid,head.periodid,head.syid,head.semid,head.sectionid from en_glhead as head where head.doc='es' and head.trno=?";
        $datahead = $this->coreFunctions->opentable($qry, [$tableid]);

        foreach ($datahead as $key => $value) {
          if (!empty($datahead[$key]->trno)) {
            $docno = $datahead[$key]->docno;
            $trno = $datahead[$key]->trno;
            $curriculumdocno = $datahead[$key]->curriculumdocno;
            $adviserid = $datahead[$key]->adviserid;
            $courseid = $datahead[$key]->courseid;
            $periodid = $datahead[$key]->periodid;
            $syid = $datahead[$key]->syid;
            $semid = $datahead[$key]->semid;
            $sectionid = $datahead[$key]->sectionid;
            $yr = $datahead[$key]->yr;

            $qry =  "select distinct head.sotrno,head.trno," . $trno . " as sctrno,'" . $curriculumdocno . "' as curriculumdocno,client.clientid,head.docno,head.dateid,client.client,client.clientname,client.clientid,head.sectionid,head.yr,subject.semid from glhead as head left join client on client.clientid=head.clientid left join glsubject as subject on subject.trno=head.trno
            where head.doc='ER'  and head.syid=? and head.periodid=? and head.courseid=? and subject.semid=? and head.sectionid=? and head.yr=? and client.clientid=?  and subject.screfx=0";

            $data2 = $this->coreFunctions->opentable($qry, [$syid, $periodid, $courseid, $semid, $sectionid, $yr, $clientid]);
            $status =  $this->generatestudentcurriculum($data2);
            if ($status['status'] ==  false) {
              return ['status' => false, 'msg' => $status['msg']];
            }
            $this->coreFunctions->execqry("update en_studentinfo set schedtrno=? where clientid=?", 'update', [$trno, $clientid]);
          }
        }
        $i = $i + 1;
      }
    }

    return ['status' => true, 'msg' => 'Generate ' . $i . ' students successfully.'];
  } //end function


  //construction
  public function getpendingboqsummary($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("prhead", "projectid", "trno=?", [$trno]);
    $subproject = $this->coreFunctions->getfieldvalue("prhead", "subproject", "trno=?", [$trno]);

    if ($companyid == 8) { //maxipro
      if ($doc == 'MI') {
        $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);
      }
    }

    $filter = " and item.islabor<>1 ";
    if ($doc == 'JR') {
      $filter = " and item.islabor =1 ";
    }
    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.client,head.clientname, head.yourref,st.stage, stock.loc
        from hsohead as head 
        left join hsostock as stock on stock.trno = head.trno 
        left join transnum on transnum.trno = head.trno
        left join stagesmasterfile as st on st.line = head.stageid 
        left join item on item.itemid = stock.itemid
        where head.doc ='BQ' and head.projectid = ? and head.subproject =? and stock.iss>stock.qa and transnum.center = ? and stock.void = 0 " . $filter . "
        group by stock.trno,head.docno,head.dateid,head.client,head.clientname, head.yourref,st.stage, stock.loc";
    $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $center]);
    return $data;
  } // end function


  public function getpendingboqdetails($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("prhead", "projectid", "trno=?", [$trno]);
    $subproject = $this->coreFunctions->getfieldvalue("prhead", "subproject", "trno=?", [$trno]);

    if ($doc == 'JR') {
      $filter = " and item.islabor =1 ";
    } else {
      $filter = " and item.islabor<>1 ";
    }
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(((stock.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
              stock.loc,head.yourref,st.stage,sa.subactivity,stock.subactid
              from hsohead as head
              right join hsostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
               left join stagesmasterfile as st on st.line = head.stageid
              left join client as wh on wh.clientid=stock.whid
              left join subactivity as sa on sa.line = stock.subactivity
              where head.doc ='BQ' and head.projectid = ? and head.subproject =? and  
              stock.iss>stock.qa and transnum.center = ? and stock.void = 0 " . $filter;

    $data = $this->coreFunctions->opentable($qry,  [$project, $subproject, $center]);
    return $data;
  } // end function

  public function getpendingboqdetails_mi($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
    $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);
    $wh = $this->coreFunctions->getfieldvalue("lahead", "wh", "trno=?", [$trno]);
    $filter = "";
    $companyid = $config['params']['companyid'];
    $unionall = '';
    if ($companyid == 8) { //MAXIPRO
      $unionall = " union all
            select concat(head.trno,stock.line) as keyid,head.trno,stock.line,head.docno,head.clientname, stock.itemid,
                  item.itemname,item.barcode,stock.stageid, st.stage,stock.cost,stat.bal
            from glhead as head
            left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join client as wh on wh.clientid=head.clientid
            left join cntnum on cntnum.trno = head.trno
            left join stagesmasterfile as st on st.line = stock.stageid
            left join rrstatus as stat on stat.trno= head.trno and stat.itemid = stock.itemid and stat.line=stock.line
            where head.doc='MT' and head.projectto = '" . $project . "' and head.subprojectto = '" . $subproject . "' and wh.client = '" . $wh . "' and  cntnum.center = '" . $center . "' 
             and stock.void = 0 and stat.bal > 0  and stock.iss = 0 and stock.line not in (select linex from lastock where trno = $trno)
            group by head.docno,head.clientname,head.trno,stock.line,stock.itemid,item.itemname,item.barcode,stock.stageid,st.stage,stock.cost,stat.bal ";
    }


    $qry = "select concat(head.trno,stock.line) as keyid,head.trno,stock.line,head.docno,head.clientname, stock.itemid, item.itemname,
                   item.barcode, stock.stageid, st.stage,stock.cost,stat.bal
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join cntnum on cntnum.trno = head.trno
            left join stagesmasterfile as st on st.line = stock.stageid
            left join client as wh on wh.clientid=stock.whid
            left join rrstatus as stat on stat.trno= head.trno and stat.itemid = stock.itemid and stat.line=stock.line
            where head.doc = 'RR' and head.projectid = '" . $project . "' and head.subproject = '" . $subproject . "' and wh.client = '" . $wh . "' and
            cntnum.center = '" . $center . "'   and stock.void = 0 and stat.bal > 0 and stock.line not in (select linex from lastock where trno = $trno)
            group by head.docno,head.clientname,head.trno,stock.line,stock.itemid,item.itemname,item.barcode,stock.stageid,st.stage,stock.cost,stat.bal
           $unionall
            order by docno";
    $data = $this->coreFunctions->opentable($qry,  [$project, $subproject, $wh, $center, $project, $subproject, $wh, $center]);
    return $data;
  } // end function

  public function getpendingprrr($config)
  {
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $isproject = $this->companysetup->getisproject($config['params']);
    $trno = $config['params']['trno'];
    // $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
    // $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);
    $wh = $this->coreFunctions->getfieldvalue("lahead", "wh", "trno=?", [$trno]);
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];
    $s = 'RQ';

    $addedfields = '';
    $addleftjoin = '';
    $sort = '';
    $pendingqa = '(stock.qa+stock.cdqa)';

    $admin = $this->othersClass->checkAccess($config['params']['user'], 3767);

    $filter = "";
    if ($companyid == 8) { //maxipro
      if ($doc == "MT") {
        if ($config['params']['lookupclass'] != "pendingjr_yourref" && $config['params']['lookupclass'] != "pendingpr_yourref") {
          $yourref = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "yourref", "trno=?", [$config['params']['trno']]);
          if ($yourref != "") {
            $filter .= " and head.docno = '" . $yourref . "'";
          }
        }
      }
    }

    $trno = $config['params']['trno'];

    if ($doc == 'MT') {
      $project = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "projectid", "trno=?", [$trno]);
      $subproject = $this->coreFunctions->getfieldvalue($config['docmodule']->head, "subproject", "trno=?", [$trno]);
    }

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,
                  left(head.dateid,10) as dateid,item.barcode,
                  FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                  FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
                  FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
                  FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
                  FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
                  FORMAT(((stock.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                  FORMAT(((stock.qty-(stock.qa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
                  stock.loc,head.yourref,st.stage,stock.rem,stock.ref as poref,stock.refx,stock.linex,poh.yourref as prdocno
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid=stock.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join cntnum on cntnum.trno = head.trno
            left join stagesmasterfile as st on st.line = stock.stageid
            left join client as wh on wh.clientid=stock.whid
            left join rrstatus as stat on stat.trno= head.trno and stat.itemid = stock.itemid and stat.line=stock.line
            left join hpostock as pos on pos.trno=stock.refx and pos.line=stock.linex
            left join hpohead as poh on poh.trno=pos.trno
            where head.doc = 'RR' and head.projectid = '" . $project . "' and head.subproject = '" . $subproject . "' 
                  and wh.client = '" . $wh . "' and cntnum.center = '" . $center . "'   and stock.void = 0 and stat.bal > 0 
            group by head.docno,head.clientname,stock.trno,head.dateid,stock.rrqty,stock.qty,stock.rrcost,
                    stock.disc,stock.ext,wh.client,stock.qa,uom.factor,stock.loc,stock.line,stock.itemid,item.itemname,item.barcode,
                    stock.stageid,st.stage,stock.cost,stat.bal,head.yourref,stock.rem,stock.ref,stock.refx,stock.linex,poh.yourref
            
            ";
    $this->coreFunctions->LogConsole($qry);
    $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $center]);
    return $data;
  } // end function

  public function getpendingjosummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("jchead", "projectid", "trno=?", [$trno]);
    $subproject = $this->coreFunctions->getfieldvalue("jchead", "subproject", "trno=?", [$trno]);
    $stageid = $this->coreFunctions->getfieldvalue("jchead", "stageid", "trno=?", [$trno]);

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
        FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
        head.yourref
        from hjohead as head
        left join hjostock as stock on stock.trno = head.trno
        left join transnum as cntnum on cntnum.trno = head.trno
        where head.projectid=? and head.subproject =? and head.client = ? and head.stageid = ? and  stock.qty>stock.qa
        and cntnum.center = ?
        and stock.void = 0
        group by stock.trno,head.docno,head.dateid,head.yourref";

    $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $client, $stageid, $center]);

    return $data;
  } // end function

  public function getpendingjodetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("jchead", "projectid", "trno=?", [$trno]);
    $subproject = $this->coreFunctions->getfieldvalue("jchead", "subproject", "trno=?", [$trno]);
    $stageid = $this->coreFunctions->getfieldvalue("jchead", "stageid", "trno=?", [$trno]);

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
          FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
          FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
          FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
          FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
          FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
          FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
          FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,st.stage,stock.rem
          from hjohead as head
          right join hjostock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join transnum as cntnum on cntnum.trno = head.trno
          left join client as wh on wh.clientid=stock.whid
          left join stagesmasterfile as st on st.line = stock.stageid
          where head.projectid=? and head.subproject =? and head.client = ? and head.stageid =? and stock.qty>stock.qa
          and cntnum.center = ?
          and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $client, $stageid, $center]);

    return $data;
  } // end function

  public function getpcr($config)
  {
    $filters = '';
    $center = $config['params']['center'];
    if ($config['params']['lookupclass'] == 'pendingpcrsummaryshortcut') {
      $filters = ' and transnum.center =?';
    } else {
      $client = $config['params']['client'];
      $trno = $config['params']['trno'];
      $acno = $this->coreFunctions->getfieldvalue("svhead", "contra", "trno=?", [$trno]);
      $filters = ' and head.client =? and transnum.center =? and head.contra =? ';
    }

    $addedfields = '';
    if ($config['params']['companyid'] == 43) { //mighty
      if (isset($config['params']['addedparams'][0]['value'])) {
        $addedfields = ", '" . $config['params']['addedparams'][0]['value'] . "' as svprefix";
      }
    }

    $qry = "select head.trno,head.docno,head.client,head.clientname,head.contra,coa.acnoname as contraname,
    head.dateid,format(sum(detail.amt)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,
    head.rem,head.projectid $addedfields
    from hpqhead as head 
    left join hpqdetail as detail on detail.trno = head.trno
    left join coa on coa.acno = head.contra 
    left join coa as coa2 on coa2.acnoid = detail.acnoid
    left join transnum on transnum.trno = head.trno
    where detail.isok =0 " . $filters .  " group by head.trno,head.docno,head.client,head.clientname,head.contra,coa.acnoname,head.dateid,head.rem,head.projectid order by dateid,docno";

    if ($config['params']['lookupclass'] == 'pendingpcrsummaryshortcut') {
      return $this->coreFunctions->opentable($qry, [$center]);
    } else {
      return $this->coreFunctions->opentable($qry, [$client, $center, $acno]);
    }
  } // end function


  public function getpendingwasummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.yourref, client.clientname,head.rem
    from hwahead as head left join client on client.clientid=head.clientid
    left join hwastock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    where client.client = ? and  stock.qty>stock.qa and transnum.center = ? and stock.void = 0
    group by stock.trno,head.docno,head.dateid,head.yourref, client.clientname,head.rem";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);

    return $data;
  } // end function

  public function getpendingwadetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
    FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
    FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
    FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
    from hwahead as head left join client on client.clientid=head.clientid
    right join hwastock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    where client.client = ? and stock.qty>stock.qa and transnum.center = ? and stock.void = 0 ";

    return $data = $this->coreFunctions->opentable($qry, [$client, $center]);
  } // end function

  public function getpendingpartssummary($config)
  {
    $center = $config['params']['center'];

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,head.yourref, head.clientname, head.rem
          from hsghead as head
          left join hsgstock as stock on stock.trno = head.trno
          left join transnum on transnum.trno = head.trno
          left join partrequest as p on p.line=head.partreqtypeid
          where stock.iss>(stock.qa+stock.waqa) and transnum.center = ? and stock.void = 0 and ucase(p.name)='WARRANTY'
          group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname,head.rem";
    return $this->coreFunctions->opentable($qry, [$center]);
  }

  public function getpendingpartsdetails($config)
  {
    $center = $config['params']['center'];

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT((stock.iss-(stock.qa+stock.waqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,stock.expiry
              from hsghead as head
              right join hsgstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join partrequest as p on p.line=head.partreqtypeid
              where stock.iss>(stock.qa+stock.waqa)
              and transnum.center = ? and stock.void = 0 and ucase(p.name)='WARRANTY'";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  }

  public function getpendingsplitqtydetails($config)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $whid = $config['params']['whid'];

    $qry = "select concat(s.trno,s.line) as keyid, s.trno, s.line, h.docno, date(h.dateid) as dateid,
    s.itemid, item.barcode, item.itemname,
    FORMAT(q.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as splitqty,
    FORMAT(s.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    s.uom, s.rrqty, s.whid, wh.clientname as whname, s.palletid, ifnull(pallet.name,'') as pallet,
    s.locid, loc.loc as location, q.locid as locid2, loc2.loc as location2
    from splitqty  as q
    left join glstock as s on s.trno=q.trno and s.line=q.line
    left join glhead as h on h.trno=s.trno
    left join client as wh on wh.clientid=s.whid
    left join location as loc on loc.line=s.locid
    left join location as loc2 on loc2.line=q.locid
    left join pallet on pallet.line=s.palletid
    left join item on item.itemid=s.itemid
    left join cntnum as c on c.trno = h.trno
    where q.isqa=0 and c.center=? and s.whid=?";

    return $this->coreFunctions->opentable($qry, [$center, $whid]);
  } // end function

  public function pendingsplitqtypicker($config)
  {
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $whid = $config['params']['whid'];

    $qry = "select concat(s.trno,s.line) as keyid, s.trno, s.line, stock.itemid,
    FORMAT(s.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    item.barcode, item.itemname, stock.uom, ifnull(rem.rem,'For Replacement') as rem,
    s.locid, ifnull(loc.loc,'') as location, s.palletid, ifnull(pallet.name,'') as pallet, stock.whid, wh.client, wh.clientname
    from splitstock as s
    left join lastock as stock on stock.trno=s.trno and stock.line=s.line
    left join item on item.itemid=stock.itemid
    left join location as loc on loc.line=s.locid
    left join pallet on pallet.line=s.palletid
    left join whrem as rem on rem.line=s.remid
    left join cntnum on cntnum.trno=s.trno
    left join client as wh on wh.clientid=stock.whid
    where s.qatrno=0 and cntnum.center=? and stock.trno is not null
    union all
    select concat(s.trno,s.line) as keyid, s.trno, s.line, stock.itemid, s.isqty, item.barcode, item.itemname, stock.uom, ifnull(rem.rem,'For Replacement') as rem,
    s.locid, ifnull(loc.loc,'') as location, s.palletid, ifnull(pallet.name,'') as pallet, stock.whid, wh.client, wh.clientname
    from splitstock as s
    left join glstock as stock on stock.trno=s.trno and stock.line=s.line
    left join item on item.itemid=stock.itemid
    left join location as loc on loc.line=s.locid
    left join pallet on pallet.line=s.palletid
    left join whrem as rem on rem.line=s.remid
    left join cntnum on cntnum.trno=s.trno
    left join client as wh on wh.clientid=stock.whid
    where s.qatrno=0 and cntnum.center=? and stock.trno is not null";

    return $this->coreFunctions->opentable($qry, [$center, $center]);
  } // end function

  public function getpcv($config)
  {
    $company = $config['params']['companyid'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $acno = $this->coreFunctions->getfieldvalue("lahead", "contra", "trno=?", [$trno]);
    $tax = $this->coreFunctions->getfieldvalue("lahead", "tax", "trno=?", [$trno]);


    $taxfilter = "and head.tax = $tax";
    if ($company == 3) {
      $taxfilter = '';
    }
    $qry = "select head.trno,head.docno,head.client,head.clientname,
                   head.dateid,head.rem,head.projectid,head.contra,coa.acnoname as contraname, ifnull(sum(detail.cr),0) as cr,
                   prj.name as prjname
            from hsvhead as head left join hsvdetail as detail on detail.trno = head.trno
            left join coa on coa.acno = head.contra 
            left join transnum on transnum.trno = head.trno
            left join projectmasterfile as prj on prj.line=head.projectid
            where head.cvtrno =0 and transnum.center =? and head.contra =? $taxfilter
            group by head.trno,head.docno,head.client,head.clientname,head.dateid,
            head.rem,head.projectid,head.contra,coa.acnoname,prj.name
            order by dateid,docno";
    $data = $this->coreFunctions->opentable($qry, [$center, $acno]);

    return $data;
  } // end function


  public function gethrshold($config)
  {
    $trno = $config['params']['trno'];
    $wh = $this->coreFunctions->getfieldvalue("prhead", "wh", "trno=?", [$trno]);

    $qry = "select item.itemid,item.barcode, item.itemname, item.uom,sum(rs.bal) as qty, ilevel.min, wh.client, wh.clientname, rs.whid
            from rrstatus as rs
            left join item on item.itemid = rs.itemid
            left join client as wh on wh.clientid=rs.whid
            left join itemlevel as ilevel on ilevel.itemid=rs.itemid and ilevel.center = wh.client
            where ifnull(ilevel.min,0)<>0 and wh.client='" . $wh . "'
            group by  item.itemid,item.barcode, item.itemname, item.uom, ilevel.min, wh.client, wh.clientname, rs.whid
            having sum(rs.bal)<>0 and sum(rs.bal) <= ilevel.min";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  } // end function



  public function getfi($config)
  {
    $company = $config['params']['companyid'];
    $addfield = "";
    $leftjoin = "";
    $condition = "item.isfa=1 and ifnull(info.empid,0)= 0";

    $filtersearch = '';
    $limit = ''; //limit 500

    if ($config['params']['doc'] == 'MM' || $config['params']['doc'] == 'PH') {
      $searchfield = ['item.barcode', 'item.othcode', 'item.itemname', 'item.shortname', 'item.uom', 'cat.name', 'subcat.name'];
      $verticalsearch = true;
      $condition = " item.isfa=0 and item.mmtrno = 0";
    }

    if (isset($config['params']['search'])) {
      $searchfield = [];
      $verticalsearch = false;

      $search = $config['params']['search'];
      if ($search != "") {
        $limit = '';
        $filtersearch = $this->othersClass->multisearch($searchfield, $search, $verticalsearch);
      }
    }

    if ($company == 16 || $company == 39) {
      $addfield = ",item.othcode,cat.name as category,subcat.name as subcategory,item.uom,(case when item.isgeneric = 1 then 'Yes' else 'No' end) as isgeneric,item.shortname as specs";
      $leftjoin = "left join itemcategory as cat on cat.line = item.category
        left join itemsubcategory as subcat on subcat.line = item.subcat";
      if ($config['params']['doc'] == 'FI') {
        $condition = " item.isinactive=0 and item.isfa=1 ";
      }
    }

    $qry = "select item.itemname,item.barcode,item.itemid, concat(item.itemid) as rowkey  $addfield from item
      left join iteminfo as info on info.itemid=item.itemid
      $leftjoin
     where $condition $filtersearch $limit";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  } // end function


  public function gettingrqtype($config)
  {
    $qry = " select head.trno,hps.line, type.reqtype,type.ists,item.uom,
             item.itemid,item.barcode, item.itemname,head.docno,
             concat( hps.trno,hps.line ) as keyid,
             FORMAT(hps.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
             FORMAT(hps.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
             FORMAT(((hps.qty-hps.tsqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending
     
            from hprhead as head

            left join hheadinfotrans as info on info.trno=head.trno
            left join reqcategory as type on type.line=info.reqtypeid
            left join hprstock as hps on hps.trno=head.trno
            left join item on item.itemid = hps.itemid
            left join uom on uom.itemid=item.itemid and uom.uom=hps.uom
            where type.ists =1 and hps.qty>hps.tsqa and item.itemname is not null";

    $data = $this->coreFunctions->opentable($qry);


    return $data;
  } // end function


  //JAC
  public function getpendingsoposummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
  FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
  head.yourref
  from hsohead as head
  right join hsostock as stock on stock.trno = head.trno
  left join transnum on transnum.trno = head.trno
  where head.doc='SO' and stock.iss>stock.poqa
  and transnum.center = ?
  and stock.void = 0
  group by stock.trno,head.docno,head.dateid,head.yourref";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingsopodetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.poqa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.iss-stock.poqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
              from hsohead as head
              right join hsostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where head.doc='SO' and stock.iss>stock.poqa
              and transnum.center = ?
              and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingqtsummary($config)
  {
    $center = $config['params']['center'];

    $arrfilter = [$center];

    $filterclient = '';
    if (isset($config['params']['client'])) {
      $client = isset($config['params']['client']) ? $config['params']['client'] : '';
      $filterclient = ' and head.client = ?';
      array_push($arrfilter, $client);
    }

    if ($config['params']['companyid'] == 20) { //proline
      $qry = "select head.trno, head.docno, left(head.dateid,10) as dateid, info.isamt as totalamt, head.yourref, head.clientname
      from hqthead as head left join hqtinfo as info on info.trno=head.trno order by head.dateid desc, head.clientname asc limit 100";
    } else {
      $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
        FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
        head.yourref
        from hqthead as head
        right join hqtstock as stock on stock.trno = head.trno
        left join transnum on transnum.trno = head.trno
        where stock.iss>stock.qa and transnum.center = ? and stock.void = 0 " .  $filterclient . "
        group by stock.trno,head.docno,head.dateid,head.yourref";
    }
    $data = $this->coreFunctions->opentable($qry, $arrfilter);
    return $data;
  } // end function

  public function getpendingqtdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $table = 'hqshead';
    if ($config['params']['companyid'] == 39 && $config['params']['doc'] == 'SO') $table = 'hqthead'; //cbbsi
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
              from " . $table . " as head
              right join hqtstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where head.client = ? and stock.iss>stock.qa
              and transnum.center = ?
              and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingqspcfsummary($config)
  {
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $adminid = $config['params']['adminid'];

    $filter = "";

    $qry = "select head.trno, head.docno, left(head.dateid, 10) as dateid,head.yourref,head.clientname
    from hqshead as head 
    left join transnum as num on num.trno = head.trno
    left join headinfotrans as hi on hi.trno = num.trno
    left join client as agent on agent.client = head.agent
    where num.center = ?
    and hi.dtctrno =0  and agent.clientid = ?
    group by head.trno, head.docno, head.dateid,head.yourref,head.clientname
    union all
    select head.trno, head.docno, left(head.dateid, 10) as dateid,head.yourref,head.clientname
    from qshead as head 
    left join transnum as num on num.trno = head.trno
    left join headinfotrans as hi on hi.trno = num.trno
    left join client as agent on agent.client = head.agent
    where num.center = ?
    and hi.dtctrno =0 and agent.clientid = ?
    group by head.trno, head.docno, head.dateid,head.yourref,head.clientname
    order by dateid desc";
    $data = $this->coreFunctions->opentable($qry, [$center, $adminid, $center, $adminid]);
    return $data;
  } // end function

  public function getpendingqssummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];

    $filter = "";
    switch ($doc) {
      case 'VT':
        $sotrno = $this->coreFunctions->getfieldvalue("vthead", "sotrno", "trno=?", [$trno]);
        $filter .= " and head.trno = '" . $sotrno . "'";
        break;
    }

    $qry = "select head.trno, head.docno, left(head.dateid, 10) as dateid
    from hsqhead as head
    left join hqshead as qthead on qthead.sotrno = head.trno
    left join hqsstock as qtstock on qtstock.trno = qthead.trno
    left join item as item on item.itemid = qtstock.itemid
    left join transnum as num on num.trno = head.trno
    where qthead.client = ? and num.center = ?
    and qtstock.iss != (qtstock.sjqa + qtstock.voidqty) and qtstock.void != 1
    " . $filter . "
    group by head.trno, head.docno, head.dateid";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingqsdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];

    $filter = "";
    switch ($doc) {
      case 'VT':
        $sotrno = $this->coreFunctions->getfieldvalue("vthead", "sotrno", "trno=?", [$trno]);
        $filter .= " and head.trno = '" . $sotrno . "'";
        break;
    }

    $qry = "
    select concat(qtstock.trno,qtstock.line) as keyid, head.trno, qtstock.line, head.docno, left(head.dateid, 10) as dateid, 
    item.barcode, item.itemname, qtstock.uom, qtstock.isqty
    from hsqhead as head
    left join hqshead as qthead on qthead.sotrno = head.trno
    left join hqsstock as qtstock on qtstock.trno = qthead.trno
    left join item as item on item.itemid = qtstock.itemid
    left join transnum as num on num.trno = head.trno
    where qthead.client = ? and num.center = ?
    and qtstock.iss != (qtstock.sjqa + qtstock.voidqty) and qtstock.void != 1
    " . $filter . "";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingaosummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];

    $qry = "select head.trno, head.docno, left(head.dateid, 10) as dateid
    from hsshead as head
    left join hsrhead as srhead on srhead.sotrno = head.trno
    left join hsrstock as srstock on srstock.trno = srhead.trno
    left join item as item on item.itemid = srstock.itemid
    left join transnum as num on num.trno = head.trno
    where srhead.client = ? and num.center = ?
    and srstock.iss != (srstock.sjqa + srstock.voidqty) and srstock.void != 1
    group by head.trno, head.docno, head.dateid";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingaodetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "
    select concat(srstock.trno,srstock.line) as keyid, head.trno, srstock.line, head.docno, left(head.dateid, 10) as dateid,
    item.barcode, item.itemname, srstock.uom, srstock.isqty
    from hsshead as head
    left join hsrhead as srhead on srhead.sotrno = head.trno
    left join hsrstock as srstock on srstock.trno = srhead.trno
    left join item as item on item.itemid = srstock.itemid
    left join transnum as num on num.trno = head.trno
    where srhead.client = ? and num.center = ?
    and srstock.iss != (srstock.sjqa + srstock.voidqty) and srstock.void != 1";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getunbilled($config)
  {
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);


    $qry = "select head.trno as keyid,sum(stock.ext) as amt,head.trno,head.docno,head.dateid,p.name as projectname,head.projectid,head.subproject, sp.subproject as subprojectname, head.rem
    from hbahead as head left join hbastock as stock on stock.trno = head.trno
    left join projectmasterfile as p on p.line = head.projectid left join transnum as num on num.trno = head.trno
    left join (select line, subproject from subproject
              union all
              select line, subproject from hsubproject
              ) as sp on head.subproject = sp.line
  where head.pbtrno = 0 and head.projectid=?  and num.center=? group by head.trno,head.docno,head.dateid,p.name,head.projectid,head.subproject, sp.subproject, head.rem";
    return $data = $this->coreFunctions->opentable($qry, [$project, $center]);
  } // end function

  public function generatedepsched($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $isexist = $this->coreFunctions->getfieldvalue("fasched", "rrtrno", "rrtrno = ? and jvtrno <>0", [$trno]);
    $fa = [];
    $sched = [];

    if (floatval($isexist) != 0) {
      return ['status' => false, 'msg' => 'Already have posted depreciation schedule.'];
    }

    $this->coreFunctions->execqry("delete from fasched where rrtrno =?", "delete", [$trno]);

    $qry = "select head.docno,head.clientid,head.dateid,head.projectid,head.subproject,stock.line,stock.stageid,stock.itemid,item.barcode,item.itemname,stock.rrcost,stock.cost,item.loa,item.revenue,item.expense from glhead as head left join glstock as stock on stock.trno = head.trno left join item on item.itemid = stock.itemid where head.trno =? and item.isfa =1";

    $data = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($data)) {
      foreach ($data as $key => $value) {
        if (floatval($data[$key]->loa) == 0) {
          return ['status' => false, 'msg' => 'Please set up the life of asset for' . $data[$key]->barcode . '. Depreciation Schedule failed.'];
        } else {
          for ($x = 1; $x <= $data[$key]->loa; $x++) {
            $fa['rrtrno'] = $trno;
            $fa['rrline'] = $data[$key]->line;
            $fa['clientid'] = $data[$key]->clientid;
            $fa['itemid'] = $data[$key]->itemid;
            $fa['projectid'] = $data[$key]->projectid;
            $fa['subproject'] = $data[$key]->subproject;
            $fa['stageid'] = $data[$key]->stageid;
            $fa['createby'] = $user;
            $fa['createdate'] = $this->othersClass->getCurrentTimeStamp();

            $date = date_create($data[$key]->dateid);
            date_add($date, date_interval_create_from_date_string($x . " month"));
            $fa['dateid'] = date_format($date, "Y-m-d");

            $amt = round($data[$key]->cost / $data[$key]->loa, 2);
            $fa['amt'] = $amt;

            array_push($sched, $fa);
          }
        }
      }

      foreach ($sched as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $sched[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
      }

      if ($this->coreFunctions->sbcinsert("fasched", $sched)) {
        return ['status' => true, 'msg' => 'Depreciation Schedule generated.'];
      } else {
        $this->coreFunctions->execqry("delete from fasched where rrtrno =?", "delete", [$trno]);
        return ['status' => false, 'msg' => 'Error on generating depreciation schedule'];
      }
    } else {
      return ['status' => false, 'msg' => 'No Fix Asset exist.'];
    }
  }

  public function generatedepschednoitem($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $isexist = $this->coreFunctions->getfieldvalue("fasched", "rrtrno", "rrtrno = ? and jvtrno <>0", [$trno]);
    $fa = [];
    $sched = [];

    if (floatval($isexist) != 0) {
      return ['status' => false, 'msg' => 'Already have posted depreciation schedule.'];
    }

    $this->coreFunctions->execqry("delete from fasched where rrtrno =?", "delete", [$trno]);

    $qry = "select h.trno, h.clientid, h.projectid, info.receivedate, (info.termsyear * 12) as termsyear, ifnull((select sum(ext) from glstock where glstock.trno=h.trno),0) as ext from glhead as h left join hcntnuminfo as info on info.trno=h.trno where h.trno=?";

    $data = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($data)) {
      foreach ($data as $key => $value) {

        for ($x = 1; $x <= $data[$key]->termsyear; $x++) {
          $fa['rrtrno'] = $trno;
          $fa['clientid'] = $data[$key]->clientid;
          $fa['projectid'] = $data[$key]->projectid;
          $fa['createby'] = $user;
          $fa['createdate'] = $this->othersClass->getCurrentTimeStamp();

          $date = date_create($data[$key]->receivedate);
          date_add($date, date_interval_create_from_date_string($x . " month"));
          $fa['dateid'] = date_format($date, "Y-m-d");

          $amt = round($data[$key]->ext / $data[$key]->termsyear, 2);
          $fa['amt'] = $amt;

          array_push($sched, $fa);
        }
      }

      foreach ($sched as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $sched[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
      }

      if ($this->coreFunctions->sbcinsert("fasched", $sched)) {
        return ['status' => true, 'msg' => 'Depreciation Schedule generated.'];
      } else {
        $this->coreFunctions->execqry("delete from fasched where rrtrno =?", "delete", [$trno]);
        return ['status' => false, 'msg' => 'Error on generating depreciation schedule'];
      }
    } else {
      return ['status' => false, 'msg' => 'Nothing to generate.'];
    }
  }

  public function getpendingjcsummary($config)
  {
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("wchead", "projectid", "trno=?", [$trno]);
    $subproject = $this->coreFunctions->getfieldvalue("wchead", "subproject", "trno=?", [$trno]);

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
        FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
        head.yourref
        from hjchead as head
        left join hjcstock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno = head.trno
        where head.projectid=? and head.subproject =? and stock.qty>stock.qa
        and cntnum.center = ?
        and stock.void = 0
        group by stock.trno,head.docno,head.dateid,head.yourref";
    $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $center]);

    return $data;
  } // end function

  public function getpendingjcdetails($config)
  {
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $project = $this->coreFunctions->getfieldvalue("wchead", "projectid", "trno=?", [$trno]);
    $subproject = $this->coreFunctions->getfieldvalue("wchead", "subproject", "trno=?", [$trno]);

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
          FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
          FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
          FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
          FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
          FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
          FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
          FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,st.stage
          from hjchead as head
          right join hjcstock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join cntnum on cntnum.trno = head.trno
          left join client as wh on wh.clientid=stock.whid
          left join stagesmasterfile as st on st.line = stock.stageid
          where head.projectid=? and head.subproject =? and stock.qty>stock.qa
          and cntnum.center = ?
          and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $center]);

    return $data;
  } // end function

  public function getpendingcnsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.yourref
    from hcnhead as head
    right join hcnstock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    where head.client = ? and  stock.iss>stock.qa
    and transnum.center = ?
    and stock.void = 0
    group by stock.trno,head.docno,head.dateid,head.yourref";

    $data = $this->coreFunctions->opentable($qry, [$client, $center]);

    return $data;
  } // end function

  public function getpendingcndetail($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
    FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
    FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
    from hcnhead as head
    right join hcnstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    where head.client = ? and stock.iss>stock.qa
    and transnum.center = ?
    and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingjbsummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $all = false;
    switch ($config['params']['lookupclass']) {
      case 'pendingjbsummaryshortcut':
        $all = true;
        break;
    }

    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $filterclient = ' and head.client = ? ';
    $arrfilter = [];
    if ($all) {
      $filterclient = ' ';
      $arrfilter = [$center];
    } else {
      $arrfilter = [$center, $client];
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
      FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
      head.yourref, head.clientname, head.rem
      from hjohead as head
      left join hjostock as stock on stock.trno = head.trno
      left join transnum on transnum.trno = head.trno
      where transnum.center = ? and  stock.qty>stock.qa and stock.void = 0 " . $filterclient . "
      group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.rem";

    $data = $this->coreFunctions->opentable($qry, $arrfilter);

    return $data;
  } // end function

  public function getpendingjbdetails($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];

    $systemtype = $this->companysetup->getsystemtype($config['params']);

    switch (strtoupper($systemtype)) {
      case 'CAIMS':
        $trno = $config['params']['trno'];
        $project = $this->coreFunctions->getfieldvalue("lahead", "projectid", "trno=?", [$trno]);
        $subproject = $this->coreFunctions->getfieldvalue("lahead", "subproject", "trno=?", [$trno]);

        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
              FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,st.stage
              from hjohead as head
              right join hjostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              left join stagesmasterfile as st on st.line = stock.stageid
              where head.projectid=? and head.subproject =? and head.client = ? and stock.qty>stock.qa
              and transnum.center = ?
              and stock.void = 0 ";
        $data = $this->coreFunctions->opentable($qry, [$project, $subproject, $client, $center]);
        break;

      default:
        $filterclient = '';
        if ($client != '') {
          $filterclient = " and head.client = '" . $client . "'";
        }

        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
              FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
              from hjohead as head
              right join hjostock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where stock.qty>stock.qa and transnum.center = ? and stock.void = 0 " . $filterclient;
        $data = $this->coreFunctions->opentable($qry, [$center]);
        break;
    }


    return $data;
  } // end function

  public function getpendingqtssummary($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];

    $all = false;
    switch ($config['params']['lookupclass']) {
      case 'pendingqtssummaryshortcut':
        $all = true;
        break;
    }

    $filterclient = ' and head.client = ? ';

    $arrfilter = [];
    if ($all) {
      $filterclient = '';
      $arrfilter = [$center];
    } else {
      $arrfilter = [$center, $client];
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
              FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
              head.yourref, head.clientname, head.rem
              from hqshead as head
              left join hqtstock as stock on stock.trno = head.trno
              left join transnum on transnum.trno = head.trno
              left join item on item.itemid = stock.itemid
              where transnum.center = ? and stock.iss<>stock.qa and stock.void =0 " . $filterclient . "
              group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.rem";

    return $this->coreFunctions->opentable($qry, $arrfilter);
  }

  public function getpendingsrsummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select sr.trno as srtrno, sr.docno, date(sr.dateid) as dateid, sr.trno,sr.yourref,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt
    from hsrhead as sr left join hsrstock as stock on stock.trno=sr.trno left join transnum on transnum.trno = sr.trno
    where sr.doc='SR' and stock.iss > stock.qa and stock.void = 0 and sr.sotrno<>0 and transnum.center = ?  group by sr.trno, sr.docno, sr.dateid, sr.yourref";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingsrdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select concat(stock.trno,stock.line) as keyid, sr.docno, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, sr.docno, date(sr.dateid) as dateid,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as isamt, stock.disc,
    FORMAT(stock.amt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
    FORMAT(((stock.qa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,sr.yourref,m.model_name as model
    from hsrhead as sr left join hsrstock as stock on stock.trno=sr.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join model_masterfile as m on m.model_id = item.model
    left join transnum on transnum.trno = sr.trno
    left join client as wh on wh.clientid=stock.whid
    where sr.doc='SR' and stock.iss > stock.qa and stock.void = 0 and sr.sotrno<>0 and transnum.center = ?  order by sr.docno, stock.line";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function

  public function getpendingsssummary($config)
  {
    $client = '';
    if (isset($config['params']['client'])) {
      $client = $config['params']['client'];
    }
    $center = $config['params']['center'];
    $clientfilter = " and head.client=? ";
    $arrfilter = [$center, $client];

    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];

    $filter = "";
    switch ($doc) {
      case 'VS':
        $sotrno = $this->coreFunctions->getfieldvalue("vshead", "sotrno", "trno=?", [$trno]);
        $filter .= " and so.trno = '" . $sotrno . "'";
        break;
    }

    $qry = "select so.trno as sotrno, so.docno, date(so.dateid) as dateid, head.trno,head.yourref,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt
    from hsshead as so 
    left join hsrhead as head on head.sotrno=so.trno 
    left join hsrstock as stock on stock.trno=head.trno 
    left join transnum on transnum.trno = so.trno
    where so.doc='AO' and stock.qa<>0 and  stock.iss > (stock.sjqa+stock.voidqty) and 
    stock.void = 0 and transnum.center = ? 
    " . $clientfilter . " 
    " . $filter . "
    group by so.trno, so.docno, so.dateid, head.trno,head.yourref";
    $data = $this->coreFunctions->opentable($qry, $arrfilter);
    return $data;
  } // end function

  public function getpendingssdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $clientfilter = " and head.client=? ";
    $arrfilter = [$center, $client];

    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];

    $filter = "";
    switch ($doc) {
      case 'VS':
        $sotrno = $this->coreFunctions->getfieldvalue("vshead", "sotrno", "trno=?", [$trno]);
        $filter .= " and stock.iss > (stock.sjqa+stock.voidqty) and stock.void = 0 and transnum.center = ?  and so.trno = '" . $sotrno . "'";
        break;
      default:
        $filter .= " and stock.qa<>0 and stock.iss > (stock.sjqa+stock.voidqty) and stock.void = 0 and transnum.center = ?  ";
        break;
    }

    $qry = "select concat(stock.trno,stock.line) as keyid, so.docno, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, so.docno, date(head.dateid) as dateid,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
    FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as isamt, stock.disc,
    FORMAT(stock.amt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
    FORMAT(((stock.qa+stock.sjqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.iss-(stock.qa+stock.sjqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,head.yourref,m.model_name as model
    from hsshead as so 
    left join hsrhead as head on head.sotrno=so.trno 
    left join hsrstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join model_masterfile as m on m.model_id = item.model
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    where so.doc='AO'  " . $filter . " 
    " . $clientfilter . "    
    order by so.docno, stock.line";
    $data = $this->coreFunctions->opentable($qry, $arrfilter);
    return $data;
  } // end function

  public function getpendingossummary($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
    FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.yourref
    from hoshead as head
    right join hosstock as stock on stock.trno = head.trno
    left join transnum on transnum.trno = head.trno
    where head.doc ='OS' and  head.client = ? and  stock.qty>stock.qa
    and transnum.center = ?
    and stock.void = 0
    group by stock.trno,head.docno,head.dateid,head.yourref";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function

  public function getpendingosdetails($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
              FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
              FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
              FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
              from hoshead as head
              right join hosstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where head.client = ? and stock.qty>stock.qa
              and transnum.center = ?
              and stock.void = 0 ";
    $data = $this->coreFunctions->opentable($qry, [$client, $center]);
    return $data;
  } // end function


  public function getpendingposummarysc($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $filterclient = ' ';
    $arrfilter = [$center];

    $search = '';
    if (isset($config['params']['search'])) {
      $txt = $config['params']['search'];
      $search = " and (head.docno like '%" . $txt . "%' or head.dateid like '%" . $txt . "%' or head.yourref like '%" . $txt . "%'
      or head.clientname like '%" . $txt . "%' or head.client like '%" . $txt . "%')";
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
      FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
      head.yourref, head.clientname, head.rem
      from hpohead as head
      left join hpostock as stock on stock.trno = head.trno
      left join transnum on transnum.trno = head.trno
      where transnum.center = ? and  stock.qty>stock.qa and stock.void = 0 " . $filterclient . $search . "
      group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.rem";


    $data = $this->coreFunctions->opentable($qry, $arrfilter);

    return $data;
  } // end function

  public function getpendingpodetailsearch($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $filtersearch = '';
    $search = '';

    if (isset($config['params']['search'])) {
      $searcfield = ['item.partno', 'brand.brand_desc', 'item.itemname', 'item.barcode', 'head.docno', 'head.dateid', 'head.yourref', 'head.clientname', 'model.model_name'];

      $search = $config['params']['search'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    $filterclient = '';
    if ($client != '') {
      $filterclient = " and head.client = '" . $client . "'";
    }

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
          FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
          FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
          FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
          FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
          FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,stock.uom,item.itemid,
          FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
          FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
          from hpohead as head
          right join hpostock as stock on stock.trno = head.trno
          left join item on item.itemid=stock.itemid
          left join model_masterfile as model on model.model_id = item.model
          left join frontend_ebrands as brand on brand.brandid = item.brand
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join transnum on transnum.trno = head.trno
          left join client as wh on wh.clientid=stock.whid
          where stock.qty>stock.qa and transnum.center = ? and stock.void = 0 " . $filterclient . $filtersearch;

    $data = $this->coreFunctions->opentable($qry, [$center]);


    return $data;
  } // end function

  public function getpendingmrsummarysc($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $doc = $config['params']['doc'];

    $filterclient = ' ';
    $arrfilter = [$center];

    $search = '';
    if (isset($config['params']['search'])) {
      $txt = $config['params']['search'];
      $search = " and (head.docno like '%" . $txt . "%' or head.dateid like '%" . $txt . "%' or head.yourref like '%" . $txt . "%'
      or head.clientname like '%" . $txt . "%' or head.client like '%" . $txt . "%')";
    }

    $qry = "select stock.trno,head.docno,left(head.dateid,10) as dateid,
      FORMAT(sum(stock.ext)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
      head.yourref, head.clientname, head.rem
      from hpohead as head
      left join hpostock as stock on stock.trno = head.trno
      left join transnum on transnum.trno = head.trno
      where transnum.center = ? and  stock.iss>stock.qa and stock.void = 0 " . $filterclient . $search . "
      group by stock.trno,head.docno,head.dateid,head.yourref, head.clientname, head.rem";


    $data = $this->coreFunctions->opentable($qry, $arrfilter);

    return $data;
  } // end function


  public function getpendingrfsodetails($config)
  {

    $trno = $config['params']['trno'];

    $sotrno = $this->coreFunctions->datareader("select sotrno as value from rfhead where trno = " . $trno . " ");
    $qstrno = $this->coreFunctions->datareader("select trno as value from hqshead where sotrno = " . $sotrno . " ");

    $center = $config['params']['center'];
    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno as sidr,sq.docno,left(sq.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
              from glhead as head
              right join glstock as stock on stock.trno = head.trno
              left join hqsstock as qsstock on qsstock.trno = stock.refx and qsstock.line = stock.linex
              left join hqshead as qshead on qshead.trno = qsstock.trno
              left join hsqhead as sq on sq.trno = qshead.sotrno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join cntnum as transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where head.doc in ('SJ') and transnum.center = ?
              and sq.trno = ? 
              union all
              select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno as sidr,sq.docno,left(sq.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
              FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref
              from glhead as head
              right join glstock as stock on stock.trno = head.trno
              left join hsrstock as qsstock on qsstock.trno = stock.refx and qsstock.line = stock.linex
              left join hsrhead as qshead on qshead.trno = qsstock.trno
              left join hsshead as sq on sq.trno = qshead.sotrno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join cntnum as transnum on transnum.trno = head.trno
              left join client as wh on wh.clientid=stock.whid
              where head.doc in ('AI') and transnum.center = ?
              and sq.trno = ? ";
    $data = $this->coreFunctions->opentable($qry, [$center, $sotrno, $center, $sotrno]);
    return $data;
  } // end function

  public function getOSItems($dateid, $wh)
  {
    $sql =  "select  lastock.trno,lastock.line,wh.client,lastock.itemid,lastock.isamt,lastock.amt,lastock.isqty2,lastock.uom,lastock.isqty,lastock.iss,lastock.disc,(lastock.isqty2*uom.factor) as pendingiss,lastock.original_qty,wh.clientid,uom.factor,lastock.expiry,item.barcode,lastock.loc,
  (select rr.cost from rrstatus as rr where rr.itemid = lastock.itemid and rr.whid = lastock.whid and rr.dateid <='" . $dateid . "' order by rr.dateid desc limit 1)*uom.factor as cost from lahead
  left join client on client.clientid = lahead.branch
  left join lastock on lastock.trno = lahead.trno
  left join client as wh on wh.clientid = lastock.whid
  left join item on item.itemid = lastock.itemid
  left join uom on uom.itemid = lastock.itemid and uom.uom = lastock.uom
  where date(lahead.dateid) ='" . $dateid . "' and lastock.isqty2 >0 and wh.client = '" . $wh . "'";

    // $this->coreFunctions->sbclogger($sql);

    return $sql;
  }

  public function getpendingca($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $action = $config['params']['action'];

    switch ($action) {
      case 'pendingca':
        $qry = "select concat(arledger.trno,arledger.line) as keyid,ctbl.client,ctbl.clientname,arledger.docno,arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
        arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, 
        round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(arledger.dateid,10) as dateid,
        0 as fdb,ifnull(glhead.yourref,'') as yourref,gldetail.rem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate
        from (arledger
        left join coa on coa.acnoid=arledger.acnoid)
        left join glhead on glhead.trno = arledger.trno
        left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
        left join cntnum on cntnum.trno = glhead.trno
        left join client as ctbl on ctbl.clientid = arledger.clientid 
        where coa.alias in ('ARCA') and cntnum.center=? and arledger.bal<>0 
        order by dateid";
        break;
      case 'pendingwtax':
        $qry = "select concat(arledger.trno,arledger.line) as keyid,ctbl.client,ctbl.clientname,arledger.docno,arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
        arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, 
        round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(arledger.dateid,10) as dateid,
        0 as fdb,ifnull(glhead.yourref,'') as yourref,gldetail.rem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate
        from (apledger as arledger
        left join coa on coa.acnoid=arledger.acnoid)
        left join glhead on glhead.trno = arledger.trno
        left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
        left join cntnum on cntnum.trno = glhead.trno
        left join client as ctbl on ctbl.clientid = arledger.clientid 
        where coa.alias in ('APWT1') and cntnum.center=? and arledger.bal<>0  order by dateid";
        break;
      case 'pendingetax':
        $qry = "select concat(arledger.trno,arledger.line) as keyid,ctbl.client,ctbl.clientname,arledger.docno,arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
        arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, 
        round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(arledger.dateid,10) as dateid,
        0 as fdb,ifnull(glhead.yourref,'') as yourref,gldetail.rem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate
        from (apledger as arledger
        left join coa on coa.acnoid=arledger.acnoid)
        left join glhead on glhead.trno = arledger.trno
        left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
        left join cntnum on cntnum.trno = glhead.trno
        left join client as ctbl on ctbl.clientid = arledger.clientid 
        where coa.alias in ('APTX3') and cntnum.center=? and arledger.bal<>0  order by dateid";
        break;
    }
    return $data = $this->coreFunctions->opentable($qry, [$center]);
  } // end function

  public function getpvitem($config)
  {
    $center = $config['params']['center'];
    $filtersearch = '';
    $search = '';

    if (isset($config['params']['search'])) {
      $searcfield = ['brand.brand_desc', 'item.itemname', 'item.barcode', 'head.docno', 'head.dateid', 'head.yourref', 'head.clientname', 'mm.model_name'];

      $search = $config['params']['search'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    $filterclient = '';

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
    FORMAT(stock.rrqty,2) as rrqty,
    FORMAT(stock.qty,2) as qty,
    FORMAT(stock.rrcost,2) as rrcost,stock.disc,
    FORMAT(stock.cost,2) as cost,
    FORMAT(stock.ext,2) as ext,wh.client as wh,stock.uom,item.itemid,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    head.yourref,prj.name as stock_projectname
    from glhead as head
    right join glstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join iteminfo as i on i.itemid = item.itemid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join cntnum as transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = item.projectid
    where transnum.doc ='RR' and transnum.center = '" . $center . "' and stock.void = 0 " . $filtersearch .
      " union all
    select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
    FORMAT(stock.isqty,2) as rrqty,
    FORMAT(stock.iss,2) as qty,
    FORMAT(stock.isamt,2) as rrcost,stock.disc,
    FORMAT(stock.amt,2) as cost,
    FORMAT(stock.ext,2) as ext,wh.client as wh,stock.uom,item.itemid,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    head.yourref,prj.name as stock_projectname
    from hrfhead as head
    right join hrfstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join iteminfo as i on i.itemid = item.itemid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = item.projectid
    where transnum.doc ='RF' and transnum.center = '" . $center . "'" . $filtersearch;
    $data = $this->coreFunctions->opentable($qry, [$center]);

    return $data;
  } // end function

  public function getclientsearch($config)
  {
    $fields = array('clientname', 'client', 'addr');
    if ($config['params']['companyid'] == 32) { //3m
      $fields = array('clientname', 'client', 'addr', 'brgy', 'area', 'province', 'region');
    }
    $filter = '';
    $addedjoin = '';
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    switch (strtolower($config['params']['doc'])) {
      case 'customer':
        $filter = ' where client.iscustomer=1 ' . $filter;
        break;
      case 'supplier':
        $filter = ' where client.issupplier=1 ' . $filter;
        break;
      case 'warehouse':
        $filter = ' where client.iswarehouse=1 ' . $filter;
        break;
      case 'agent':
        $filter = ' where client.isagent=1 ' . $filter;
        break;
      case 'department':
        $filter = ' where client.isdepartment=1 ' . $filter;
        break;
      default:
        $filter = ' where client.iscustomer=1 ' . $filter;
        break;
    }

    $qry = "select client.clientid,client.client,client.clientname,client.addr,client.brgy,client.area,client.region,client.province,client.terms,ifnull(terms.days,0) as days
    from client left join terms on terms.terms = client.terms
    " . $addedjoin . "
    " . $filter;

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function getapprovedapplication($config)
  {
    $client = isset($config['params']['client']) ? $config['params']['client'] : '';
    $center = $config['params']['center'];
    $adminid = $config['params']['adminid'];
    $agentfilter = '';
    $allowall = $this->othersClass->checkAccess($config['params']['user'], 4077);

    if ($allowall == '0') {
      if ($adminid != 0) {
        $agentfilter = " and ag.clientid = " . $adminid . " ";
      }
    }

    $qryselect = "select 
         head.trno, 
         head.docno,
         date_format(head.dateid,'%m/%d/%Y') as dateid,
         head.client,
         head.fname,
         head.mname,
         head.lname,
         head.clientname,
         head.ext,
         head.address,
         head.addressno,
         head.street,
         head.subdistown,
         head.city,
         head.country,
         head.zipcode,
         head.terms,
         head.otherterms,
         head.rem,
         head.voiddate,
         head.yourref,
         head.ourref,
         head.contactno,
         head.contactno2,
         head.email,
         head.planid,
         ifnull(ag.client,'') as agent,
         ifnull(ag.clientname,'') as agentname,
         ifnull(pt.name,'') as plantype,
         ifnull(pt.amount,'') as amount,
         '' as dagentname,
         info.isplanholder,
         info.client as bclient,info.clientname as planholder,
         concat(info.fname,' ',info.mname,' ',info.lname,' ',info.ext) as bclientname,
         info.lname as lname2,
         info.fname as fname2,
         info.mname as mname2,
         info.ext as ext2,
         info.gender,
         info.civilstat as civilstatus,
         concat(info.addressno,' ',info.street,' ',info.subdistown,' ',info.city,' ',info.country,' ',info.zipcode) as raddress,
         info.addressno as raddressno,
         info.street as rstreet,
         info.subdistown as rsubdistown,
         info.city as rcity,
         info.country as rcountry,
         info.zipcode as rzipcode,
         info.paddressno,
         info.pstreet,
         info.psubdistown,
         info.pcity,
         info.pcountry,
         info.pzipcode,

         date_format(info.bday,'%m/%d/%Y') as bday,
         info.nationality,
         info.pob,
         case when info.ispassport=0 then '0' else '1' end as ispassport,
         case when info.isprc=0 then '0' else '1' end as isprc,
         case when info.isdriverlisc=0 then '0' else '1' end as isdriverlisc,         
         case when info.isotherid=0 then '0' else '1' end as isotherid,  
         info.idno,
         info.expiration,
         case when info.isemployment=0 then '0' else '1' end as isemployment,  
         case when info.isbusiness=0 then '0' else '1' end as isbusiness,  
         case when info.isinvestment=0 then '0' else '1' end as isinvestment,  
         case when info.isothersource=0 then '0' else '1' end as isothersource,  
         case when info.isemployed=0 then '0' else '1' end as isemployed,  
         case when info.isselfemployed=0 then '0' else '1' end as isselfemployed,  
         case when info.isofw=0 then '0' else '1' end as isofw,  
         case when info.isretired=0 then '0' else '1' end as isretired,  
         case when info.iswife=0 then '0' else '1' end as iswife,  
         case when info.isnotemployed=0 then '0' else '1' end as isnotemployed,  
         info.othersource,
         info.tin,
         info.sssgsis,
         case when info.lessten=0 then '0' else '1' end as lessten,  
         case when info.tenthirty=0 then '0' else '1' end as tenthirty,  
         case when info.thirtyfifty=0 then '0' else '1' end as thirtyfifty,  
         case when info.fiftyhundred=0 then '0' else '1' end as fiftyhundred,  
         case when info.hundredtwofifty=0 then '0' else '1' end as hundredtwofifty,  
         case when info.twofiftyfivehundred=0 then '0' else '1' end as twofiftyfivehundred,  
         case when info.fivehundredup=0 then '0' else '1' end as fivehundredup,  
         info.employer,
         info.otherplan,date_add(now(),interval 2 year) as due,case when info.issenior=0 then '0' else '1' end as issenior,case info.issenior when 1 then 0 else 12 end as tax,case info.issenior when 1 then 'NON-VATABLE' else 'VATABLE' end as vattype
         ";


    $qryselect = $qryselect . " from heahead as head
        left join heainfo as info on head.trno = info.trno
        left join client on head.client = client.client
        left join client as ag on ag.client = head.agent
        left join plantype as pt on pt.line = head.planid 
        where head.catrno = 0 " . $agentfilter;

    return $this->coreFunctions->opentable($qryselect);
  }

  public function getpendingplshortcut($config)
  {
    $center = $config['params']['center'];
    $trno = isset($config['params']['trno']) ? $config['params']['trno'] : '';
    $filter = '';
    $company = $config['params']['companyid'];
    $doc = $config['params']['doc'];


    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
    FORMAT(sum(ap.bal)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as totalamt,
    head.yourref,head.ourref,client.clientname
    from hpyhead as head
    left join apledger as ap on ap.py = head.trno
    left join transnum on transnum.trno = head.trno
    left join client on client.client=head.client
    where  transnum.center = ?  and transnum.cvtrno =0 and ap.bal<>0
    group by head.trno,head.docno,head.dateid,head.yourref,head.ourref,client.clientname ";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    return $data;
  } // end function
  public function getunpaidmccollection($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $adminid = $config['params']['adminid'];
    $mode = $this->coreFunctions->getfieldvalue('mchead', "trnxtype", "trno=?", [$trno]);
    $hdate = $this->coreFunctions->getfieldvalue('mchead', "dateid", "trno=?", [$trno]);
    $rem = "Amount Due";
    if ($mode == 'MA Collection') {
      $rem = "Monthly Amortization";
    }

    if (strtoupper($mode)  == "SPAREPARTS") {
      $qry = "select concat(ar.trno,ar.dateid) as keyid,ar.trno,head.docno,head.yourref,head.ourref,head.crno,head.rfno,head.swsno,head.chsino,
      ar.dateid,sum(ar.db-ar.cr) as bal,0 as principal,0 as interest,'' as penalty,0 as rebate,ar.line, 'CASH' as rem,head.clientname
      from caledger as ar
      left join gldetail on gldetail.trno = ar.trno and gldetail.line = ar.line
      left join glhead as head on head.trno = ar.trno
      left join cntnum on cntnum.trno = head.trno
      left join client on client.clientid=head.clientid
      where  client.client = '" . $client . "' and head.doc = 'CI' and ar.mctrno =0 and cntnum.center = '" . $center . "'
      group by ar.trno,head.docno, gldetail.ref,head.yourref,head.ourref,ar.dateid,ar.line,head.clientname
      union all
      select concat(ar.trno,ar.dateid) as keyid,ar.trno,case cntnum.recontrno when 0 then ar.docno else gldetail.ref end as docno,head.yourref,head.ourref,head.crno,head.rfno,head.swsno,head.chsino,
      ar.dateid,sum(ar.bal) as bal,sum(ifnull(di.principal,0)) as principal,sum(ifnull(di.interest,0)) as interest,i.penalty,i.rebate,ar.line,'" . $rem . "' as rem,head.clientname
      from arledger as ar
      left join gldetail on gldetail.trno = ar.trno and gldetail.line = ar.line
      left join glhead as head on head.trno = ar.trno
      left join hcntnuminfo  as i on i.trno = head.trno
      left join cntnum on cntnum.trno = head.trno
      left join client on client.clientid=head.clientid
      left join hdetailinfo as di on di.trno = gldetail.trno and di.line = gldetail.line
      where  ar.bal<>0 and client.client = '" . $client . "'  and head.doc = 'CI' and cntnum.center = '" . $center . "'
      group by ar.trno,cntnum.recontrno, ar.docno, gldetail.ref,head.yourref,head.ourref,ar.dateid,i.penalty,i.rebate,ar.line,head.clientname ";  // cntnum.center= $center  and gldetail.mctrno =0 
    } else {
      $qry = "select concat(ar.trno,ar.dateid) as keyid,ar.trno,ar.docno,head.yourref,head.ourref,
      head.crref as crno,head.rfno,head.swsno,head.chsino,
      ar.dateid,sum(ar.bal) as bal,sum(ifnull(di.principal,0)) as principal,sum(ifnull(di.interest,0)) as interest,i.penalty,i.rebate,'" . $rem . "' as rem,head.clientname
      from arledger as ar
      left join gldetail on gldetail.trno = ar.trno and gldetail.line = ar.line
      left join coa on coa.acnoid = ar.acnoid
      left join glhead as head on head.trno = ar.trno
      left join hcntnuminfo  as i on i.trno = head.trno
      left join cntnum on cntnum.trno = head.trno
      left join client on client.clientid=head.clientid
      left join hdetailinfo as di on di.trno = gldetail.trno and di.line = gldetail.line
      where cntnum.doc ='MJ' and ar.bal<>0 and client.client = '" . $client . "'  and coa.alias not in ('AR5','ARDP') 
      group by ar.trno,cntnum.recontrno, ar.docno, gldetail.ref,head.yourref,head.ourref,ar.dateid,i.penalty,i.rebate,head.crref,head.rfno,head.swsno,head.chsino,head.clientname
      union all 
      select concat(ar.trno,ar.dateid) as keyid,ar.trno,gldetail.ref as docno,sjh.yourref,sjh.ourref,
      head.crref as crno,head.rfno,head.swsno,head.chsino,
      ar.dateid,sum(ar.bal) as bal,sum(ifnull(di.principal,0)) as principal,sum(ifnull(di.interest,0)) as interest,sji.penalty,i.rebate,'" . $rem . "' as rem,head.clientname
      from arledger as ar
      left join gldetail on gldetail.trno = ar.trno and gldetail.line = ar.line
      left join coa on coa.acnoid = ar.acnoid
      left join glhead as head on head.trno = ar.trno      
      left join hcntnuminfo  as i on i.trno = head.trno
      left join cntnum on cntnum.trno = head.trno
      left join glhead as sjh on sjh.trno = cntnum.recontrno
      left join hcntnuminfo  as sji on sji.trno = sjh.trno
      left join client on client.clientid=head.clientid
      left join hdetailinfo as di on di.trno = gldetail.trno and di.line = gldetail.line
      where cntnum.doc ='GJ' and cntnum.recontrno<>0 and ar.bal<>0 and client.client = '" . $client . "'  and coa.alias not in ('AR5','ARDP') 
      group by ar.trno,cntnum.recontrno, ar.docno, gldetail.ref,sjh.yourref,sjh.ourref,ar.dateid,sji.penalty,i.rebate,head.crref,head.rfno,head.swsno,head.chsino,head.clientname
      union all 
      select concat(ar.trno,ar.dateid,'RB') as keyid,ar.trno,case cntnum.recontrno when 0 then ar.docno else gldetail.ref end as docno,head.yourref,head.ourref,
      head.crref as crno,head.rfno,head.swsno,head.chsino,
      ar.dateid,sum(ar.bal) as bal,0 as principal,0 as interest,'' as penalty,i.rebate,'Rebate' as rem,head.clientname
      from arledger as ar
      left join gldetail on gldetail.trno = ar.trno and gldetail.line = ar.line
      left join coa on coa.acnoid = ar.acnoid
      left join glhead as head on head.trno = ar.trno
      left join hcntnuminfo  as i on i.trno = head.trno
      left join cntnum on cntnum.trno = head.trno
      left join client on client.clientid=head.clientid
      left join hdetailinfo as di on di.trno = gldetail.trno and di.line = gldetail.line
      where  ar.bal<>0 and client.client = '" . $client . "'  and coa.alias = 'AR5' and date(ar.dateid) < '" . date("Y-m-d", strtotime($hdate)) . "'
      group by ar.trno,cntnum.recontrno, ar.docno, gldetail.ref,head.yourref,head.ourref,ar.dateid,i.penalty,i.rebate,head.crref,head.rfno,head.swsno,head.chsino,head.clientname
      union all 
      select concat(ar.trno,ar.dateid,'DP') as keyid,ar.trno,case cntnum.recontrno when 0 then ar.docno else gldetail.ref end as docno,head.yourref,head.ourref,
      head.crref as crno,head.rfno,head.swsno,head.chsino,
      ar.dateid,sum(ar.bal) as bal,0 as principal,0 as interest,'' as penalty,i.rebate,'Downpayment' as rem,head.clientname
      from arledger as ar
      left join gldetail on gldetail.trno = ar.trno and gldetail.line = ar.line
      left join coa on coa.acnoid = ar.acnoid
      left join glhead as head on head.trno = ar.trno
      left join hcntnuminfo  as i on i.trno = head.trno
      left join cntnum on cntnum.trno = head.trno
      left join client on client.clientid=head.clientid
      left join hdetailinfo as di on di.trno = gldetail.trno and di.line = gldetail.line
      where  ar.bal<>0 and client.client = '" . $client . "'  and coa.alias = 'ARDP'
      group by ar.trno,cntnum.recontrno, ar.docno, gldetail.ref,head.yourref,head.ourref,ar.dateid,i.penalty,i.rebate,head.crref,head.rfno,head.swsno,head.chsino,head.clientname order by dateid";  // cntnum.center= $center  and gldetail.mctrno =0 
    }

    return $this->coreFunctions->opentable($qry);
  } // end function

  public function getapprovedloan($config)
  {
    $f = "";
    if (isset($config['params']['addedparams'])) {
      $f = " and head.client ='" . $config['params']['addedparams'][0] . "'";
    }
    $qryselect = "select 0 as trno,'' as docno,'' as dateid,'' as categoryname,0 as monthly,'' as client,
    '' as clientname,'' as lname,'' as fname,'' as mname,'' as mmname,'' as comakername,0 as amount 
    union all
    select   head.trno, 
         head.docno,left(head.dateid,10) as dateid,
         ifnull(r.reqtype,'') as categoryname,   format(head.monthly,2) as monthly, head.client,head.clientname,head.lname,head.fname,head.mname,head.mmname,
         info.clientname  as comakername,format(info.amount,2) as amount";

    $qryselect = $qryselect . " from heahead as head
    left join transnum as num on num.trno = head.trno
        left join heainfo as info on head.trno = info.trno
        left join reqcategory as r on r.line=head.planid  where num.cvtrno = 0 " . $f;

    return $this->coreFunctions->opentable($qryselect);
  }


  public function getlelist($config)
  {
    $fields = array('docno', 'dateid');
    $client = $config['params']['client'];
    $filter = $this->othersClass->createfilter($fields, $config['params']['search']);
    if ($filter !== '') {
      $filter = "  " . $filter;
    }
    $qry = "
    select 
        head.trno, 
        head.docno,left(head.dateid,10) as dateid,
        ifnull(r.reqtype,'') as categoryname
        from heahead as head
        left join reqcategory as r on r.line=head.planid where head.client= '$client' " . $filter . " order by docno,dateid LIMIT 50 ";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  } // end function


  public function getkpunpaid($config)
  {
    $center = $config['params']['center'];
    $client = $config['params']['client'];

    $qry = "
    select concat(apledger.trno,apledger.line) as keyid,ctbl.client,apledger.docno,apledger.trno,apledger.line,apledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    apledger.clientid,round(apledger.db,2) as db,round(apledger.cr,2) as cr, round(apledger.bal,2) as bal ,left(apledger.dateid,10) as dateid,
    0 as fdb,ifnull(glhead.ourref,'') as yourref,gldetail.rem,glhead.rem as hrem,glhead.project 
    from apledger
    left join coa on coa.acnoid=apledger.acnoid
    left join glhead on glhead.trno = apledger.trno
    left join gldetail on gldetail.trno=apledger.trno and gldetail.line=apledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = apledger.clientid
    where ctbl.client=? and cntnum.center=? and apledger.bal<>0 and apledger.kp=0
    order by dateid";

    return $this->coreFunctions->opentable($qry, [$client, $center]);
  }
  public function getunpaidclearance($config)
  {
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $clientfilter = 'and ctbl.client = "' . $client . '"';
    if ($config['params']['lookupclass'] == 'unpaidall') {
      $clientfilter = '';
    }

    $query = "select concat(arledger.trno,arledger.line) as keyid,glhead.doc,ctbl.client,ctbl.clientname,case cntnum.recontrno when 0 then arledger.docno else gldetail.ref end as docno,arledger.trno,arledger.line,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,round(arledger.db," . $this->companysetup->getdecimal('currency', $config['params']) . ") as db,round(arledger.cr," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cr, round(arledger.bal," . $this->companysetup->getdecimal('currency', $config['params']) . ") as bal ,left(arledger.dateid,10) as dateid,
    0 as fdb,ifnull(glhead.yourref,'') as yourref,gldetail.rem as rem,glhead.rem as hrem, gldetail.ref,gldetail.projectid,gldetail.subproject,gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate, glhead.invoiceno,
    ifnull(glhead.due,'') as due,
    case 
	  when cntnum.doc = 'BT' then 'T.R.U Clearance'
	  when cntnum.doc = 'BD' then 'Local Clearance' 
	  when cntnum.doc = 'BC' then 'Business Clearance'
    else '' end as ctype
    from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid 
    where cntnum.center=" . $center . " and arledger.bal<>0  " . $clientfilter . "";

    return  $this->coreFunctions->opentable($query);
  }
}//end class
