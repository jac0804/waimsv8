<?php

namespace App\Http\Classes\modules\enrollment;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;

class es
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Schedule';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_schead';
  public $hhead = 'en_glhead';
  public $stock = 'en_scsubject';
  public $hstock = 'en_glsubject';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'courseid', 'coursename', 'curriculumcode', 'curriculumname', 'semid', 'periodid', 'yr', 'syid', 'adviserid', 'advisername', 'curriculumdocno', 'sectionid', 'rem', 'ischinese'];
  private $except = ['trno', 'dateid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 951,
      'edit' => 950,
      'new' => 952,
      'save' => 953,
      'change' => 955,
      'delete' => 954,
      'print' => 158,
      'lock' => 958,
      'unlock' => 959,
      'post' => 956,
      'unpost' => 957,

      'additem' => 1318,
      'edititem' => 1319,
      'deleteitem' => 1320
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listsy', 'listperiod', 'listyr', 'listsemester', 'listcourse', 'listcoursename', 'listsection'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $limit = "limit 150";

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'sy.sy', 'period.code', 'head.hr', 'sem.term', 'course.coursename', 'sec.section'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $qry = "select 'DRAFT' as status, head.trno, head.docno, course.coursecode as coursecode, left(head.dateid,10) as dateid, 'DRAFT' as status, course.coursename, head.curriculumname, sec.section, head.semid, sem.term as terms, period.code as period, head.yr, head.curriculumcode, instructor.client as advisercode,instructor.clientname  as advisername, head.curriculumdocno,sy.sy
      from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join client  as instructor on instructor.clientid=head.adviserid left join en_course as course on course.line=head.courseid left join en_term as sem on sem.line=head.semid
      left join en_schoolyear as sy on sy.line=head.syid left join en_period as period on period.line=head.periodid left join en_section as sec on sec.line=head.sectionid
      where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
      union all
      select 'POSTED' as status, head.trno, head.docno, course.coursecode as coursecode, left(head.dateid,10) as dateid, 'POSTED' as status, course.coursename, head.curriculumname, sec.section, head.semid, sem.term as terms, period.code as period, head.yr, head.curriculumcode, instructor.client as advisercode,instructor.clientname  as advisername, head.curriculumdocno,sy.sy
      from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join client  as instructor on instructor.clientid=head.adviserid left join en_course as course on course.line=head.courseid left join en_term as sem on sem.line=head.semid
      left join en_schoolyear as sy on sy.line=head.syid  left join en_period as period on period.line=head.periodid left join en_section as sec on sec.line=head.sectionid
      where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
      order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'post',
      'unpost',
      'lock',
      'unlock',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'subjectcode', 'subjectname', 'units', 'lecture', 'laboratory', 'hours', 'linstructorcode', 'instructorname', 'lbldgcode', 'roomcode', 'schedday', 'schedstarttime', 'schedendtime', 'minslot', 'maxslot'], 'headgridbtns' => ['reportcard', 'regstudent', 'regstudentbatch', 'studlevelup']]];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'SUBJECT';
    $obj[0][$this->gridname]['descriptionrow'] = ['subjectname', 'subjectcode', 'Subject'];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;'; //action
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][6]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][8]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][10]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][11]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][14]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][15]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';

    return $obj;
  }

  public function createtabbutton($config)
  {
    // $tbuttons = ['generatecurriculum', 'addsubject', 'saveitem', 'deleteallitem'];
    $tbuttons = ['reportcard', 'generatesubject', 'addsubject', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    // $obj[0]['label'] = 'Curriculum';
    $obj[2]['label'] = 'Add';
    $obj[2]['lookupclass'] = 'lookupsubject';
    $obj[2]['action'] = 'lookupsubject';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'lcoursecode', 'advisercode', 'curriculumdocno', 'yr'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'yr.label', 'Grade/Year');
    data_set($col1, 'yr.class', 'csyr sbccsreadonly');
    data_set($col1, 'curriculumdocno.type', 'lookup');
    data_set($col1, 'curriculumdocno.lookupclass', 'lookupcurriculum');
    data_set($col1, 'curriculumdocno.action', 'lookupcurriculum');
    data_set($col1, 'curriculumdocno.addedparams', ['courseid', 'syid']);
    data_set($col1, 'curriculumdocno.class', 'sbccsreadonly');
    data_set($col1, 'yr.addedparams', ['curriculumdocno']);

    $fields = ['dateid', 'coursename', 'advisername', 'curriculumcode', 'semester'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'advisername.class', 'csadvisername sbccsreadonly');
    data_set($col2, 'curriculumcode.class', 'sbccsreadonly');
    data_set($col2, 'semester.type', 'input');

    $fields = ['period', 'sy', 'section', 'curriculumname'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'period.label', 'Period (SY & Grade/Year) Ex.19-1');
    data_set($col3, 'period.type', 'input');
    data_set($col3, 'sy.type', 'input');
    data_set($col3, 'section.class', 'cssection sbccsreadonly');
    data_set($col3, 'curriculumname.class', 'sbccsreadonly');

    $fields = ['rem', 'ischinese'];
    $col4 = $this->fieldClass->create($fields);


    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['coursecode'] = '';
    $data[0]['courseid'] = 0;
    $data[0]['coursename'] = '';
    $data[0]['periodid'] = $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
    $data[0]['period'] = $this->coreFunctions->getfieldvalue('en_period', 'code', 'isactive=1');
    $data[0]['section'] = '';
    $data[0]['sectionid'] = 0;
    $data[0]['yr'] = '';
    $schoolyear  = $this->coreFunctions->getfieldvalue('en_period', 'sy', 'isactive=1');
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'sy=?', [$schoolyear]);
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'sy=?', [$schoolyear]);
    $data[0]['advisercode'] = '';
    $data[0]['adviserid'] = 0;
    $data[0]['advisername'] = '';
    $data[0]['curriculumcode'] = '';
    $data[0]['curriculumname'] = '';
    $data[0]['curriculumdocno'] = '';
    $data[0]['terms'] = '';
    $data[0]['semid'] = 0;
    $data[0]['rem'] = '';
    $data[0]['ischinese'] = '0';

    // $data[0]['agent'] = '';
    // $data[0]['wh'] = $this->companysetup->getwh($params);
    // $name = $this->coreFunctions->datareader("select clientname as value from client where client='".$data[0]['wh']."'");
    // $data[0]['whname'] = $name;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select head.trno, head.docno, head.courseid, course.coursecode, left(head.dateid,10) as dateid, 'DRAFT' as status, course.coursename,
        head.curriculumname, sec.section, head.sectionid, head.semid, sem.term as terms, head.periodid, period.code as period, head.yr, head.curriculumcode, head.rem,
        head.adviserid, instructor.client as advisercode, instructor.clientname as advisername, head.curriculumdocno, date_format(head.createdate,'%Y-%m-%d') as createdat, head.syid, sy.sy, head.ischinese";

    $qry = $qryselect . " from " . $table . " as head
        left join " . $tablenum . " as num on num.trno = head.trno  left join en_course as course on course.line=head.courseid  left join client as instructor on instructor.clientid=head.adviserid left join en_period as period on period.line=head.periodid left join en_schoolyear as sy on sy.line=head.syid
        left join en_term as sem on sem.line=head.semid left join en_section as sec on sec.line=head.sectionid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from " . $htable . " as head
        left join " . $tablenum . " as num on num.trno = head.trno  left join en_course as course on course.line=head.courseid left join client as instructor on instructor.clientid=head.adviserid  left join en_period as period on period.line=head.periodid left join en_schoolyear as sy on sy.line=head.syid
        left join en_term as sem on sem.line=head.semid left join en_section as sec on sec.line=head.sectionid
        where head.trno = ? and num.center=? ";


    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      if ($head[0]->ischinese) {
        $head[0]->ischinese = '1';
      } else {
        $head[0]->ischinese = '0';
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }

    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['coursecode'] . ' - ' . $head['curriculumcode'] . ' - ' . $head['curriculumdocno'] . ' - ' . $head['advisercode']);
    }
  } // end function



  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $qry = "select value from (
      select trno as value from en_srchead where schedtrno=".$trno." union all
      select trno as value from en_sosubject where ctrno=".$trno." union all
      select trno as value from en_sjsubject where ctrno=".$trno." union all
      select trno as value from en_glsubject where ctrno=".$trno." union all
      select trno as value from en_gegrades where ctrno=".$trno." union all
      select trno as value from en_glgrades where ctrno=".$trno."
    ) as v";
    $check = $this->coreFunctions->datareader($qry);
    if ($check != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to delete, already has transaction...'];
    }
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function




  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    // $isitemzeroqty = $this->coreFunctions->opentable($qry,[$trno]);
    // if(!empty($isitemzeroqty)){
    //   return ['status'=>false,'msg'=>'Posting failed. Check carefully, some items have zero quantity.'];
    // }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, courseid, curriculumname, syid, sectionid, semid, periodid, yr, curriculumcode,
      adviserid, curriculumdocno, editdate, editby, createdate, createby, encodeddate, encodedby, viewdate, viewby, lockdate, rem, ischinese)
      SELECT head.trno, head.doc, head.docno, head.dateid, head.courseid, head.curriculumname, head.syid, head.sectionid, head.semid, head.periodid,
      head.yr, head.curriculumcode, head.adviserid, head.curriculumdocno, head.editdate, head.editby, head.createdate, head.createby,
      head.encodeddate, head.encodedby, head.viewdate, head.viewby, head.lockdate, head.rem, head.ischinese FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno 
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . "(trno,line,subjectid,units,laboratory,lecture,hours,instructorid,bldgid,roomid,schedday,schedtime,schedendtime,schedstarttime,minslot,maxslot,asqa,astempqa,qa,encodeddate,encodedby,editdate,editby, rctrno, rcline)
        SELECT subject.trno,subject.line,subject.subjectid,subject.units,subject.laboratory,subject.lecture,subject.hours,subject.instructorid,
        subject.bldgid,subject.roomid,subject.schedday,subject.schedtime,subject.schedendtime,subject.schedstarttime,subject.minslot,subject.maxslot,subject.asqa,subject.astempqa,subject.qa,subject.encodeddate,subject.encodedby,subject.editdate,subject.editby, subject.rctrno, subject.rcline FROM " . $this->stock . " as subject  where subject.trno =? ";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
      //if($posthead){      
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function transwithreference($config)
  {
    $trno = $config['params']['trno'];
    $docno = $this->coreFunctions->getfieldvalue($this->hhead, 'docno', 'trno=?', [$trno]);
    $check = $this->coreFunctions->datareader("select value from (
      select trno as value from en_adhead where schedcode=?
      select trno as value from en_adsubject where schedref=?
      select trno as value from en_athead where scheddocno=?
      select trno as value from en_atstudents where schedtrno=?
    ) as v", [$docno, $docno, $docno, $trno]);
    if ($check != '') return 'This Transaction cannot be UNPOSTED, Already have a reference';
    return $check;
  }

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $msg = $this->transwithreference($config);
    if ($msg !== '') return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, courseid, curriculumname, syid, sectionid, semid, periodid, yr, curriculumcode,
    adviserid, curriculumdocno, editdate, editby, createdate, createby, encodeddate, encodedby, viewdate, viewby, lockdate, rem, ischinese)
    SELECT head.trno, head.doc, head.docno, head.dateid, head.courseid, head.curriculumname, head.syid, head.sectionid, head.semid,
    head.periodid, head.yr, head.curriculumcode, head.adviserid, head.curriculumdocno, head.editdate, head.editby, head.createdate,
    head.createby, head.encodeddate, head.encodedby, head.viewdate, head.viewby, head.lockdate, head.rem, head.ischinese FROM " . $this->hhead . " as head
    left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(trno,line,subjectid,units,laboratory,lecture,hours,instructorid,bldgid,roomid,schedday,schedtime,schedendtime,schedstarttime,minslot,maxslot,asqa,astempqa,qa,encodeddate,encodedby,editdate,editby, rctrno, rcline)
        SELECT subject.trno,subject.line,subject.subjectid,subject.units,subject.laboratory,subject.lecture,subject.hours,subject.instructorid,subject.bldgid,subject.roomid,subject.schedday,subject.schedtime,subject.schedendtime,subject.schedstarttime,subject.minslot,subject.maxslot,subject.asqa, subject.astempqa,subject.qa,subject.encodeddate,subject.encodedby,subject.editdate,subject.editby, subject.rctrno, subject.rcline 
     FROM " . $this->hstock . " as subject where subject.trno =?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect .
      "  FROM " . $this->stock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as s on s.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as rooms on rooms.line=stock.roomid left join en_bldg as bldg on bldg.line=stock.bldgid
    where stock.trno = ? and num.postdate is null
  union all " . $sqlselect .
      "  FROM " . $this->hstock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as s on s.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as rooms on rooms.line=stock.roomid left join en_bldg as bldg on bldg.line=stock.bldgid
    where stock.trno = ? and num.postdate is not null 
  order by line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function    

  private function getstockselect($config)
  {

    $sqlselect = "select stock.trno,stock.line,stock.subjectid,s.subjectcode,s.subjectname,stock.units,stock.laboratory,stock.lecture, stock.hours,stock.instructorid, i.client as linstructorcode, i.clientname as instructorname,stock.roomid,rooms.roomcode, stock.bldgid,bldg.bldgcode as lbldgcode,stock.schedday,stock.schedtime,stock.schedstarttime,time(stock.schedstarttime) as ftime,stock.schedendtime,stock.minslot,stock.maxslot,
    '' as bgcolor,
    '' as errcolor ";
    return $sqlselect;
  }

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
   FROM " . $this->stock . "  as stock left join en_subject as s on s.trno=stock.subjectid
   left join client as i on i.clientid=stock.instructorid left join en_rooms as rooms on rooms.line=stock.roomid left join en_bldg as bldg on bldg.line=stock.bldgid
   where stock.trno =? and stock.line = ? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'generatecurriculum': //save all item edited
        return $this->generatecurriculum($config);
        break;
      case 'generatesubject':
        return $this->generatesubject($config);
        break;
      case 'getsubject':
        return $this->getsubject($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function getsubject($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select yr, semid, curriculumcode, courseid  from en_CCHead where trno=?";
    $headdetail = $this->coreFunctions->opentable($qry, [$trno]);

    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['data']['trno'] = $trno;
      $config['params']['data']['subjectid'] = $value['trno'];
      $config['params']['data']['units'] = $value['units'];
      $config['params']['data']['lecture'] = $value['lecture'];
      $config['params']['data']['laboratory'] = $value['laboratory'];
      $config['params']['data']['hours'] = $value['hours'];
      $config['params']['data']['instructorid'] = 0;
      $config['params']['data']['bldgid'] = 0;
      $config['params']['data']['roomid'] = 0;
      $config['params']['data']['schedday'] = '';
      $config['params']['data']['schedstarttime'] = null;
      $config['params']['data']['schedendtime'] = null;
      $config['params']['data']['maxslot'] = 0;
      $config['params']['data']['minslot'] = 0;

      $return = $this->addsubject('insert', $config);
      array_push($rows, $return['row'][0]);
    }
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Subject Successfull...'];
  }


  public function addsubject($action, $config)
  {
    $trno = $config['params']['trno'];
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
    } else {
      $line = $config['params']['data']['line'];
    }
    $config['params']['line'] = $line;

    $data = [
      'trno' => $config['params']['trno'],
      'line' => $line,
      'subjectid' => $config['params']['data']['subjectid'],
      'units' => $config['params']['data']['units'],
      'lecture' => $config['params']['data']['lecture'],
      'subjectid' => $config['params']['data']['subjectid'],
      'units' => $config['params']['data']['units'],
      'lecture' => $config['params']['data']['lecture'],
      'laboratory' => $config['params']['data']['laboratory'],
      'hours' => $config['params']['data']['hours'],
      'instructorid' => $config['params']['data']['instructorid'],
      'bldgid' => $config['params']['data']['bldgid'],
      'roomid' => $config['params']['data']['roomid'],
      'schedday' => $config['params']['data']['schedday'],
      'schedstarttime' => $config['params']['data']['schedstarttime'],
      'schedendtime' => $config['params']['data']['schedendtime'],
      'minslot' => $config['params']['data']['minslot'],
      'maxslot' => $config['params']['data']['maxslot']
    ];

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item failed'];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
    }

    return true;
  }

  public function generatesubject($config)
  {
    $status = true;
    $msg = '';
    $rows = [];
    $trno = $config['params']['trno'];

    $curriculum = $this->coreFunctions->opentable("select curriculumdocno, yr, semid,ischinese from en_schead where doc='ES' and trno=?", [$trno]);
    if (!empty($curriculum)) {
      $qry = "select h.trno, h.docno, h.curriculumcode, h.courseid, h.syid, h.curriculumname, s.subjectid, s.units, s.lecture, s.laboratory, s.hours, y.year, y.semid
        from en_glhead AS h
        left join en_glyear as y on y.trno=h.trno
        left join en_glsubject as s on s.trno=h.trno and s.cline=y.line
        left join en_term as t on t.line=s.semid
        where h.doc='EC' and h.docno=? and y.semid=? and y.year=? and h.ischinese=?";

      $ecdata = $this->coreFunctions->opentable($qry, [$curriculum[0]->curriculumdocno, $curriculum[0]->semid, $curriculum[0]->yr, $curriculum[0]->ischinese]);
      $center = $config['params']['center'];
      $rows = [];
      if (!empty($ecdata)) {
        foreach ($ecdata as $key2 => $value) {
          $config['params']['data']['trno'] = $trno;
          $config['params']['data']['subjectid'] = $ecdata[$key2]->subjectid;
          $config['params']['data']['units'] = $ecdata[$key2]->units;
          $config['params']['data']['lecture'] = $ecdata[$key2]->lecture;
          $config['params']['data']['laboratory'] = $ecdata[$key2]->laboratory;
          $config['params']['data']['hours'] = $ecdata[$key2]->hours;
          $config['params']['data']['instructorid'] = 0;
          $config['params']['data']['bldgid'] = 0;
          $config['params']['data']['roomid'] = 0;
          $config['params']['data']['schedday'] = '';
          $config['params']['data']['schedstarttime'] = null;
          $config['params']['data']['schedendtime'] = null;
          $config['params']['data']['maxslot'] = 0;
          $config['params']['data']['minslot'] = 0;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            array_push($rows, $return['row'][0]);
          }
          $msg = 'Items were successfully added.';
        } // end foreach
      } //end if
    } else {
      $msg = "Please select curriculum first.";
      $status = false;
    }
    return ['inventory' => $rows, 'status' => $status, 'msg' => $msg];
  }

  public function generatecurriculum($config)
  {
    $status = true;
    $msg = '';
    $trno = $config['params']['trno'];

    $ecdocno =  $config['params']['rows'][0]['docno'];
    $sy =  $config['params']['rows'][0]['sy'];
    $semester = $config['params']['rows'][0]['terms'];
    $yr =  $config['params']['rows'][0]['yearnum'];
    $curriculumcode =  $config['params']['rows'][0]['curriculumcode'];
    $curriculumdocno = $config['params']['rows'][0]['docno'];
    $coursecode = $config['params']['rows'][0]['coursecode'];

    $table = $this->head;

    $this->coreFunctions->sbcupdate($table, ['curriculumdocno' => $curriculumdocno, 'curriculumcode' => $curriculumcode], ['trno' => $trno]);

    $qry = "select h.trno,h.docno,h.curriculumcode,h.courseid,h.syid, s.yearnum,s.semid,h.curriculumname,s.subjectid,s.units,s.lecture,s.laboratory,s.hours
      from en_glhead AS h left join en_glsubject as s on s.trno=h.trno left join en_term as t on t.line=s.semid
      where h.doc='EC' and h.docno= ? and t.term = ? and s.yearnum = ? ";

    $ecdata = $this->coreFunctions->opentable($qry, [$curriculumdocno, $semester, $yr]);
    $center = $config['params']['center'];

    $rows = [];
    if (!empty($ecdata)) {
      foreach ($ecdata as $key2 => $value) {
        $config['params']['data']['trno'] = $trno;
        $config['params']['data']['subjectid'] = $ecdata[$key2]->subjectid;
        $config['params']['data']['units'] = $ecdata[$key2]->units;
        $config['params']['data']['lecture'] = $ecdata[$key2]->lecture;
        $config['params']['data']['laboratory'] = $ecdata[$key2]->laboratory;
        $config['params']['data']['hours'] = $ecdata[$key2]->hours;
        $config['params']['data']['instructorid'] = 0;
        $config['params']['data']['bldgid'] = 0;
        $config['params']['data']['roomid'] = 0;
        $config['params']['data']['schedday'] = '';
        $config['params']['data']['schedstarttime'] = null;
        $config['params']['data']['schedendtime'] = null;
        $config['params']['data']['maxslot'] = 0;
        $config['params']['data']['minslot'] = 0;
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      } // end foreach
    } //end if

    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function
  // insert and update item
  public function additem($action, $config)
  {

    $trno =  $config['params']['data']['trno'];
    $subjectid =  $config['params']['data']['subjectid'];
    $units = $config['params']['data']['units'];
    $lecture =  $config['params']['data']['lecture'];
    $laboratory = $config['params']['data']['laboratory'];
    $hours =  $config['params']['data']['hours'];
    $instructorid =  $config['params']['data']['instructorid'];
    $bldgid =  $config['params']['data']['bldgid'];
    $roomid =  $config['params']['data']['roomid'];
    $schedday =  $config['params']['data']['schedday'];
    $schedstarttime =  $config['params']['data']['schedstarttime'];
    $schedendtime =  $config['params']['data']['schedendtime'];
    $maxslot =  $config['params']['data']['maxslot'];
    $minslot =  $config['params']['data']['minslot'];
    $line = 0;

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'subjectid' => $subjectid,
      'units' => $units,
      'lecture' => $lecture,
      'laboratory' => $laboratory,
      'hours' => $hours,
      'instructorid' => $instructorid,
      'bldgid' => $bldgid,
      'roomid' => $roomid,
      'schedday' => $schedday,
      'schedstarttime' => $schedstarttime,
      'schedendtime' => $schedendtime,
      'minslot' => $minslot,
      'maxslot' => $maxslot
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    if ($bldgid != 0 && $roomid != 0 && $schedday != '' && $schedstarttime != '' && $schedendtime != '' && $instructorid != 0) {
      $schedcheck = $this->checksched($config, $data);
    } else {
      $schedcheck['status'] = true;
      $schedcheck['msg'] = '';
    }

    if ($data['schedstarttime'] == '') $data['schedstarttime'] = null;
    if ($data['schedendtime'] == '') $data['schedendtime'] = null;


    if ($schedcheck['status'] == true) {
      if ($action == 'insert') {
        $data['encodeddate'] = $current_timestamp;
        $data['encodedby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'SUBJECT SCHEDULE', 'ADD - Line:' . $line . ' subjectid:' . $subjectid . ' Units:' . $units . ' Lecture:' . $lecture . ' laboratory:' . $laboratory . ' instructorid:' . $instructorid . ' schedday:' . $schedday . ' schedstarttime:' . $schedstarttime . ' schedendtime:' . $schedendtime . ' Bldg id:' . $bldgid . ' Roomid:' . $roomid);
          $row = $this->openstockline($config);
          return ['row' => $row, 'status' => true, 'msg' => 'Add Schedule Successfully'];
        } else {
          return ['status' => false, 'msg' => 'Add Schedule Failed'];
        }
      } elseif ($action == 'update') {

        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
        return ['status' => true, 'msg' => $schedcheck['msg']];
      }
    } else {
      return ['status' => false, 'msg' => $schedcheck['msg']];
    }
  } // end function

  public function checksched($config, $data)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $qry = "select periodid as value from " . $this->head . " where trno=?";
    $periodid = $this->coreFunctions->datareader($qry, [$trno]);
    $schedday = $data['schedday'];
    $schedstarttime = $data['schedstarttime'];
    $schedendtime = $data['schedendtime'];
    $instructorid = $data['instructorid'];
    $bldgid = $data['bldgid'];
    $roomid = $data['roomid'];
    $line = $data['line'];
    $start = date("h:i:sa", strtotime($schedstarttime));
    $end = date("h:i:sa", strtotime($schedendtime));

    if ($schedday != '') {
      $days = explode(",", $schedday);
      for ($i = 0; $i < count($days); $i++) {
        $d = strtolower($days[$i]);
        $d = substr_replace($d, strtoupper(substr($d, 0, 1)), 0, 1);
        $qry = "select head.trno,stock.schedstarttime,stock.schedendtime from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno
                  where head.periodid=? and stock.instructorid=? and stock.schedday like '%" . $d . "%'  and (stock.trno<>? or stock.line<>?)
                  AND ((Time('" . $schedstarttime . "') >= Time(schedstarttime) AND Time('" . $schedstarttime . "') <= Time(schedendtime))
                  or
                  (Time('" . $schedendtime . "')>Time(schedstarttime) and Time('" . $schedendtime . "') < Time(schedendtime)))
                  union all
                  select head.trno,stock.schedstarttime,stock.schedendtime from en_schead as head left join en_scsubject as stock on stock.trno=head.trno
                  where head.periodid=? and stock.instructorid=?  and stock.schedday like '%" . $d . "%'  and (stock.trno<>? or stock.line<>?)
                  AND ((Time('" . $schedstarttime . "') >= Time(schedstarttime) AND Time('" . $schedstarttime . "') <= Time(schedendtime))
                  or
                  (Time('" . $schedendtime . "')>Time(schedstarttime) and Time('" . $schedendtime . "') < Time(schedendtime)))";
        $data =  $this->coreFunctions->opentable($qry, [$periodid, $instructorid, $trno, $line, $periodid, $instructorid, $trno, $line]);

        if (!empty($data)) {
          $msg = "Conflict Schedule with Professor Schedule: Day-" . $d . " Time-" . $start . " " . $end;
          return ['status' => false, 'msg' => $msg];
        } else {
          $qry = "select head.trno,stock.schedstarttime,stock.schedendtime from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno
              where head.periodid=? and stock.bldgid=? and stock.roomid=? and stock.schedday like '%" . $d . "%'  and (stock.trno<>? or stock.line<>?)
              AND ((Time('" . $schedstarttime . "') >= Time(schedstarttime) AND Time('" . $schedstarttime . "') <= Time(schedendtime))
                  or
                  (Time('" . $schedendtime . "')>Time(schedstarttime) and Time('" . $schedendtime . "') < Time(schedendtime)))
              union all
              select head.trno,stock.schedstarttime,stock.schedendtime from en_schead as head left join en_scsubject as stock on stock.trno=head.trno
              where head.periodid=? and stock.bldgid=? and stock.roomid=? and stock.schedday like '%" . $d . "%'  and (stock.trno<>? or stock.line<>?)
              AND ((Time('" . $schedstarttime . "') >= Time(schedstarttime) AND Time('" . $schedstarttime . "') <= Time(schedendtime))
                  or
                  (Time('" . $schedendtime . "')>Time(schedstarttime) and Time('" . $schedendtime . "') < Time(schedendtime)))";
          $data =  $this->coreFunctions->opentable($qry, [$periodid, $bldgid, $roomid, $trno, $line, $periodid, $bldgid, $roomid, $trno, $line]);

          if (!empty($data)) {
            $msg = "Conflict Schedule with Bldg/Room Schedule: Day-" . $d . " Time-" . $start . " " . $end;
            return ['status' => false, 'msg' => $msg];
          } else {
            return ['status' => true, 'msg' => ''];
          }
        }
      }
    }
    return ['status' => true, 'msg' => ''];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $stat = $this->additem('update', $config);
    $data = $this->openstockline($config);
    if ($stat['status'] == true) {
      return ['row' => $data, 'status' => $stat['status'], 'msg' => 'Successfully Save Schedule...'];
    } else {
      return ['row' => $data, 'status' => $stat['status'], 'msg' => $stat['msg']];
    }
  }

  public function updateitem($config)
  {
    $isstat = true;
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $stat = $this->additem('update', $config);
      if ($stat['status'] == false) {
        $msg = $msg . ' ' . $stat['msg'];
        $isstat = false;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';

    if ($isstat) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => false, 'msg' => 'Some ' . $msg];
    }
  } //end function

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    //if(($data[0]->qa == $data[0]->qty)){
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'SUBJECT', 'REMOVED - Line:' . $line . ' Subject ID:' . $data[0]['subjectid'] . ' units:' . $data[0]['units'] . ' InstructorID:' . $data[0]['instructorid'] . ' schedday:' . $data[0]['schedday'] . ' schedstarttime:' . $data[0]['schedstarttime'] . ' schedendtime:' . $data[0]['schedendtime'] . ' bldgid:' . $data[0]['bldgid'] . ' room:' . $data[0]['roomid']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end function


  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  // start
  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter($config);
    // $txtdata = $this->reportparamsdata($config);

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
