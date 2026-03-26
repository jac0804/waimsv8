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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class ef
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Grade Setup';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_gshead';
  public $hhead = 'en_glhead';
  public $stock = 'en_gscomponent';
  public $hstock = 'en_glcomponent';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'adviserid', 'syid', 'periodid', 'semid', 'yr', 'bldgid', 'roomid', 'schedday', 'schedtime', 'scheddocno', 'schedtrno', 'schedline', 'courseid', 'subjectid', 'curriculumcode', 'curriculumdocno', 'sectionid', 'ischinese'];
  private $except = ['trno', ' dateid'];
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
    $getcols = ['action', 'listdocument', 'teachername', 'subjectname', 'sy', 'period', 'coursename', 'section'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:125px;whiteSpace:normal;min-width:125px;';
    $cols[2]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
    $cols[3]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
    $cols[3]['label'] = 'Adviser Name';
    $cols[4]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $cols[5]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $cols[6]['style'] = 'width:120px;whiteSpace:normal;min-width:120px;';
    $cols[7]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
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
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'cl.clientname', 'subj.subjectname'];

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

    $qry = "select head.trno, head.docno, left(head.dateid, 10) as dateid, 'DRAFT' as status, cl.clientname as teachername, subj.subjectname,
      scy.sy, p.name as period, course.coursename, sec.section
      from " . $this->head . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.clientid = head.adviserid
      left join en_schoolyear as scy on scy.line=head.syid
      left join en_period as p on p.line=head.periodid
      left join en_course as course on course.line=head.courseid
      left join en_section as sec on sec.line=head.sectionid
      left join en_subject as subj on subj.trno = head.subjectid
      where head.doc = ? and num.center = ? and CONVERT(head.dateid, DATE) >= ? and CONVERT(head.dateid, DATE) <= ? " . $condition . " " . $filtersearch . "
      union all
      select head.trno, head.docno, left(head.dateid, 10) as dateid, 'POSTED' as status, cl.clientname as teachername, subj.subjectname,
      scy.sy, p.name as period, course.coursename, sec.section
      from " . $this->hhead . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.clientid = head.adviserid
      left join en_schoolyear as scy on scy.line=head.syid
      left join en_period as p on p.line=head.periodid
      left join en_course as course on course.line=head.courseid
      left join en_section as sec on sec.line=head.sectionid
      left join en_subject as subj on subj.trno = head.subjectid
      where head.doc = ? and num.center = ? and CONVERT(head.dateid, DATE) >= ? and CONVERT(head.dateid, DATE) <= ? " . $condition . " " . $filtersearch . "
      order by dateid desc, docno desc " . $limit;

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
      // 'post',
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'gccode', 'gcname', 'gcpercent'], 'headgridbtns' => ['viewgradesubcomp']]];
    $stockbuttons = ['gradesubcom', 'save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'COMPONENT';
    $obj[0][$this->gridname]['descriptionrow'] = ['gcname', 'gccode', 'Component'];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addcomponent', 'duplicategecomp', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['icon'] = 'batch_prediction';
    $obj[0]['label'] = 'add grade component';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'advisercode', 'sy', 'schedcode', 'subjectcode'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'advisercode.required', true);
    data_set($col1, 'subjectcode.type', 'input');
    data_set($col1, 'subjectcode.label', 'Subject');
    data_set($col1, 'subjectcode.class', 'sbccsreadonly');
    // data_set($col1, 'schedcode.type', 'input');
    data_set($col1, 'schedcode.name', 'scheddocno');

    $fields = ['dateid', 'advisername', 'period', 'coursecode', 'subjectname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'advisername.class', 'sbccsreadonly');
    data_set($col2, 'coursecode.class', 'sbccsreadonly');
    data_set($col2, 'coursecode.required', false);
    data_set($col2, 'subjectname.class', 'sbccsreadonly');
    data_set($col2, 'subjectname.label', 'Subject Description');
    data_set($col2, 'period.required', true);

    $fields = ['yr', 'semester', 'section', 'coursename', 'curriculumcode'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'yr.type', 'input');
    data_set($col3, 'yr.label', 'Year/Grade');
    data_set($col3, 'section.type', 'input');
    data_set($col3, 'section.required', false);
    data_set($col3, 'coursename.class', 'sbccsreadonly');
    data_set($col3, 'curriculumcode.class', 'sbccsreadonly');
    data_set($col3, 'curriculumdocno.type', 'input');
    data_set($col3, 'section.class', 'sbccsreadonly');
    data_set($col3, 'semester.required', true);

    $fields = ['bldgcode', 'roomcode', 'schedday', 'schedtime', 'ischinese'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'bldgcode.class', 'sbccsreadonly');
    data_set($col4, 'roomcode.type', 'input');
    data_set($col4, 'schedday.class', 'sbccsreadonly');
    data_set($col4, 'schedtime.class', 'sbccsreadonly');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['adviserid'] = '';
    $data[0]['advisercode'] = '';
    $data[0]['advisername'] = '';
    $data[0]['scheddocno'] = '';
    $data[0]['courseid'] = '';
    $data[0]['coursecode'] = '';
    $data[0]['curriculumdocno'] = '';
    $data[0]['curriculumcode'] = '';
    $data[0]['coursename'] = '';
    $data[0]['subjectid'] = '';
    $data[0]['subjectcode'] = '';
    $data[0]['subjectname'] = '';

    $schoolyear  = $this->coreFunctions->getfieldvalue('en_period', 'sy', 'isactive=1');
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'sy=?', [$schoolyear]);
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'sy=?', [$schoolyear]);

    $data[0]['periodid'] = $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
    $data[0]['period'] = $this->coreFunctions->getfieldvalue('en_period', 'code', 'isactive=1');

    $data[0]['yr'] = '';
    $data[0]['semid'] = '';
    $data[0]['terms'] = '';
    $data[0]['sectionid'] = '';
    $data[0]['section'] = '';
    $data[0]['roomid'] = '';
    $data[0]['roomcode'] = '';
    $data[0]['bldgid'] = '';
    $data[0]['bldgcode'] = '';
    $data[0]['schedday'] = '';
    $data[0]['schedtime'] = '';
    $data[0]['scheddocno'] = '';
    $data[0]['schedtrno'] = 0;
    $data[0]['schedline'] = 0;
    $data[0]['ischinese'] = '0';
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

    $qryselect = "select head.trno, head.docno, head.adviserid, client.clientname as advisername, client.client as advisercode, head.dateid, head.scheddocno, head.courseid,
      c.coursename, c.coursecode, head.curriculumdocno, head.curriculumcode, head.subjectid, s.subjectcode, s.subjectname, head.syid, schoolyear.sy, head.periodid, p.name as period,
      head.yr, head.semid, sem.term as terms, head.sectionid, sec.section, head.roomid, r.roomname as room, r.roomcode, head.bldgid, bl.bldgname as bldg, bl.bldgcode, head.schedday, head.schedtime, head.schedtrno, head.schedline, head.ischinese ";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.clientid = head.adviserid
      left join en_course as c on c.line = head.courseid
      left join en_subject as s on s.trno = head.subjectid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_period as p on p.line = head.periodid
      left join en_term as sem on sem.line = head.semid
      left join en_section as sec on sec.line = head.sectionid
      left join en_rooms as r on r.line = head.roomid
      left join en_bldg as bl on bl.line = head.bldgid
      where head.trno = ? and num.center = ?
      union all
      " . $qryselect . " from " . $htable . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.clientid = head.adviserid
      left join en_course as c on c.line = head.courseid
      left join en_subject as s on s.trno = head.subjectid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_period as p on p.line = head.periodid
      left join en_term as sem on sem.line = head.semid
      left join en_section as sec on sec.line = head.sectionid
      left join en_rooms as r on r.line = head.roomid
      left join en_bldg as bl on bl.line = head.bldgid
      where head.trno = ? and num.center = ?";

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
    if ($data['courseid'] == '') $data['courseid'] = 0;
    if ($data['subjectid'] == '') $data['subjectid'] = 0;
    if ($data['sectionid'] == '') $data['sectionid'] = 0;
    if ($data['semid'] == '') $data['semid'] = 0;
    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['advisercode']);
    }
  } // end function

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $qry = "select line as value from en_gscomponent where trno=" . $trno . " limit 1";
    $check1 = $this->coreFunctions->datareader($qry);
    if ($check1 != '') return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to delete, already has Component(s)...'];
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from en_gssubcomponent where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . " where trno=?", [$trno]);

    if ($this->othersClass->isposted($config)) return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];

    $qry = "insert into " . $this->hhead . " (trno, doc, docno, dateid, adviserid, yr, bldgid, roomid, semid, periodid, syid, schedday, schedtime, scheddocno, sectionid, courseid, curriculumcode, curriculumdocno, subjectid, ischinese)
      select trno, doc, docno, dateid, adviserid, yr, bldgid, roomid, semid, periodid, syid, schedday, schedtime, scheddocno, sectionid, courseid, curriculumcode, curriculumdocno, subjectid, ischinese from " . $this->head . " as head where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->hstock . " (trno, line, gccode, gcname, gcpercent, compid) select trno, line, gccode, gcname, gcpercent, compid from " . $this->stock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into en_glsubcomponent (trno, line, compline, linex, component, gccode, gcsubcode, topic, noofitems, compid) select trno, line, compline, linex, component, gccode, gcsubcode, topic, noofitems, compid from en_gssubcomponent where trno=?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $date = $this->othersClass->getCurrentTimeStamp();
          $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
          $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from en_gssubcomponent where trno=?", 'delete', [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
          $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting subcomponent'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . " where trno=?", [$trno]);

    $qry = "insert into " . $this->head . " (trno, doc, docno, dateid, adviserid, yr, bldgid, roomid, semid, periodid, syid, schedday, schedtime, scheddocno, sectionid, courseid, curriculumcode, curriculumdocno, subjectid, ischinese)
      select trno, doc, docno, dateid, adviserid, yr, bldgid, roomid, semid, periodid, syid, schedday, schedtime, scheddocno, sectionid, courseid, curriculumcode, curriculumdocno, subjectid, ischinese from " . $this->hhead . " where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . " (trno, line, gccode, gcname, gcpercent, compid) select trno, line, gccode, gcname, gcpercent, compid from " . $this->hstock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into en_gssubcomponent (trno, line, compline, linex, component, gccode, gcsubcode, topic, noofitems, compid) select trno, line, compline, linex, component, gccode, gcsubcode, topic, noofitems, compid from en_glsubcomponent where trno=?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from en_glsubcomponent where trno=?", 'delete', [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, subcomponent problems...'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect . " from " . $this->stock . " as stock left join " . $this->tablenum . " as num on num.trno = stock.trno
    where stock.trno = ? and num.postdate is null
    union all " . $sqlselect . " from " . $this->hstock . " as stock left join " . $this->tablenum . " as num on num.trno = stock.trno
    where stock.trno = ? and num.postdate is not null order by line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function    

  private function getstockselect($config)
  {
    return "select stock.trno, stock.line, stock.gccode, stock.gcname, stock.gcpercent, stock.compid, '' as bgcolor, '' as errcolor ";
  }

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . " FROM " . $this->stock . "  as stock where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'getcomponents':
        return $this->getcomponents($config);
        break;
      case 'getcomp':
        return $this->getcomp($config);
        break;
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
      $config['params']['data']['bldgid'] = '';
      $config['params']['data']['roomid'] = '';
      $config['params']['data']['schedday'] = '';
      $config['params']['data']['schedstarttime'] = '';
      $config['params']['data']['schedendtime'] = '';
      $config['params']['data']['maxslot'] = '';
      $config['params']['data']['minslot'] = '';

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

  public function generatecurriculum($config)
  {
    $status = true;
    $msg = '';
    $trno = $config['params']['trno'];

    $ecdocno =  $config['params']['rows'][0]['docno'];
    $sy =  $config['params']['rows'][0]['sy'];
    // $semester = $config['params']['rows'][0]['terms'];
    $yr =  $config['params']['rows'][0]['yearnum'];
    $curriculumcode =  $config['params']['rows'][0]['curriculumcode'];
    $curriculumdocno = $config['params']['rows'][0]['docno'];
    $coursecode = $config['params']['rows'][0]['coursecode'];

    $table = $this->head;

    $qry = "select h.trno,h.docno,h.curriculumcode,h.courseid,h.syid, s.yearnum,s.semid,h.curriculumname,s.subjectid,s.units,s.lecture,s.laboratory,s.hours
      from en_glHEAD AS h left join en_glsubject as s on s.trno=h.trno left join en_term as t on t.line=s.semid
      where h.doc='EC' and h.docno= ?";

    $ecdata = $this->coreFunctions->opentable($qry, [$curriculumdocno]);
    $center = $config['params']['center'];

    $rows = [];
    if (!empty($ecdata)) {
      foreach ($ecdata as $key2 => $value) {
        $config['params']['data']['trno'] = $trno;
        $config['params']['data']['subjectid'] = $ecdata[$key2]->subjectid;
        $config['params']['data']['units'] = $ecdata[$key2]->units;
        $config['params']['data']['lecture'] = $ecdata[$key2]->lecture;
        $config['params']['data']['laboratory'] = $ecdata[$key2]->laboratory;
        $config['params']['data']['semid'] = $ecdata[$key2]->semid;
        $config['params']['data']['yearnum'] = $ecdata[$key2]->yearnum;
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      } // end foreach
    } //end if
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function getcomponents($config)
  {
    $status = true;
    $msg = '';
    $trno = $config['params']['trno'];
    $rows = [];
    if (!empty($config['params']['rows'])) {
      foreach ($config['params']['rows'] as $key2 => $value) {
        $config['params']['data']['trno'] = $trno;
        $config['params']['data']['gccode'] = $value['gccode'];
        $config['params']['data']['gcname'] = $value['gcname'];
        $config['params']['data']['gcpercent'] = $value['gcpercent'];
        $config['params']['data']['compid'] = $value['compid'];
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }
    }
    return ['row' => $rows, 'status' => true, 'msg' => $msg . ' Added Items Successful...'];
  }

  public function getcomp($config)
  {
    $status = true;
    $msg = '';
    $trno = $config['params']['trno'];
    $getrno = $config['params']['rows'][0]['trno'];

    $qry = "select gccode,gcname,gcpercent,compid from en_gscomponent where trno=?";
    $dataComp = $this->coreFunctions->opentable($qry, [$getrno]);

    $rows = [];
    if (!empty($dataComp)) {
      foreach ($dataComp as $key2 => $value) {
        $config['params']['data']['trno'] = $trno;
        $config['params']['data']['gccode'] = $dataComp[$key2]->gccode;
        $config['params']['data']['gcname'] = $dataComp[$key2]->gcname;
        $config['params']['data']['gcpercent'] = $dataComp[$key2]->gcpercent;
        $config['params']['data']['compid'] = $dataComp[$key2]->compid;
        $return = $this->additem('insert', $config);
        $geline = $return['row'][0]->line;

        $qry = "select " . $trno . " as trno,line,gcsubcode,topic,noofitems," . $geline . " as compid from en_gssubcomponent where trno=? and compid=? order by compid";
        $dataCompSub = $this->coreFunctions->opentable($qry, [$getrno, $geline]);
        $this->addsubcomponent('insert', $dataCompSub, $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }
    }

    return ['row' => $rows, 'status' => true, 'msg' => $msg . ' Added Items Successful...'];
  }

  public function additem($action, $config)
  {
    $trno = $config['params']['data']['trno'];
    $gccode = $config['params']['data']['gccode'];
    $gcname = $config['params']['data']['gcname'];
    $gcpercent = $config['params']['data']['gcpercent'];
    $compid = $config['params']['data']['compid'];
    $line = 0;

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line += 1;
      $config['params']['line'] = $line;
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'gccode' => $gccode,
      'gcname' => $gcname,
      'gcpercent' => $gcpercent,
      'compid' => $compid
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'Grade Setup Component', 'ADD - Line:' . $line . ' Code:' . $gccode . ' Name:' . $gcname . ' Percent:' . $gcpercent);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add Component Successfully.'];
      } else {
        return ['status' => false, 'msg' => 'Add Component Failed'];
      }
    } elseif ($action == 'update') {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return ['status' => true, 'msg' => ''];
    }
  } // end function

  public function addsubcomponent($action, $data, $config)
  {

    foreach ($data as $key => $value) {
      $line = 0;
      $trno = $data[$key]->trno;
      $gcsubcode = $data[$key]->gcsubcode;
      $topic = $data[$key]->topic;
      $noofitems = $data[$key]->noofitems;
      $compid = $data[$key]->compid;

      if ($action == 'insert') {
        $qry = "select line as value from en_gssubcomponent where trno=? and compid=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$trno, $compid]);
        if ($line == '') $line = 0;
        $line += 1;
      }
      $datarow = [
        'trno' => $trno,
        'line' => $line,
        'gcsubcode' => $gcsubcode,
        'topic' => $topic,
        'noofitems' => $noofitems,
        'compid' => $compid
      ];

      foreach ($datarow as $key2 => $value) {
        $datarow[$key2] = $this->othersClass->sanitizekeyfield($key2, $datarow[$key2]);
      }

      if ($action == 'insert') {
        if ($this->coreFunctions->sbcinsert('en_gssubcomponent', $datarow) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'Grade Setup Component', 'ADD - Line:' . $line . ' Code:' . $gcsubcode . ' Name:' . $topic . ' Percent:' . $noofitems . ' Component ID:' . $compid);
        }
      }
    }
  } // end function



  public function updategradeschedule($config)
  {
    return 'waw';
  }

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
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select * from en_gssubcomponent where trno=" . $trno . " and compid=" . $line;
    $check = $this->coreFunctions->opentable($qry);
    if (!empty($check)) {
      return ['status' => false, 'msg' => 'Unable to delete, already has sub-component...'];
    }
    $qry = "delete from $this->stock where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    $qry = "delete from en_gssubcomponent where trno=? and compid=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'COMPONENT', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['gccode'] . ' Name:' . $data[0]['gcname'] . ' Percent:' . $data[0]['gcpercent'] . ' Component ID:' . $data[0]['compid']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function


  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $comps = $this->coreFunctions->opentable("select s.line, c.gcname from " . $this->stock . " as s left join en_gradecomponent as c on c.line=s.compid where trno=" . $trno);
    $msg = "";
    if (!empty($comps)) {
      foreach ($comps as $c) {
        $check = $this->coreFunctions->opentable("select * from en_gssubcomponent where trno=" . $trno . " and compid=" . $c->line);
        if (!empty($check)) {
          if ($msg != '') {
            $msg .= ", '" . $c->gcname . "'";
          } else {
            $msg = "Unable to delete '" . $c->gcname . "'";
          }
        } else {
          $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=? and line=' . $c->line, 'delete', [$trno]);
          // $this->coreFunctions->execqry('delete from en_gssubcomponent where trno=?', 'delete', [$trno]);
        }
      }
    }
    if ($msg != '') $msg = $msg . ', already has sub-component...';
    $data = $this->openstock($trno, $config);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted. ' . $msg, 'inventory' => $data];
  }


  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
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
        '' as received
        "
    );
  }

  private function report_default_query($trno)
  {
    $query = "select head.trno,head.doc,head.docno,head.dateid,
      #head.coursecode,head.coursename,
      head.curriculumname,head.sy,head.section,head.terms,head.period,head.yr,
      head.curriculumcode,
      
      head.curriculumdocno,
      
      head.schedday,head.schedtime,head.scheddocno,head.courseid,head.adviserid,head.syid,
      head.periodid,head.sectionid,head.semid,head.bldgid,head.roomid,head.subjectid,head.lockdate,head.lockuser,head.viewdate,head.viewby,head.schedtrno,head.schedline,head.ischinese,head.editdate,
      head.editby,
      comp.gccode,comp.gcname,comp.gcpercent,comp.compid
      
      from en_gshead as head
      left join en_gscomponent as comp on comp.trno=head.trno
      where head.doc='EF' and head.trno='" . $trno . "'
      union all
      select head.trno,head.doc,head.docno,head.dateid,
      
      head.curriculumname,head.sy,head.section,head.terms,head.period,head.yr,
      head.curriculumcode,
      
      head.curriculumdocno,
      
      head.schedday,head.schedtime,head.scheddocno,head.courseid,head.adviserid,head.syid,
      head.periodid,head.sectionid,head.semid,head.bldgid,head.roomid,head.subjectid,head.lockdate,head.lockuser,head.viewdate,head.viewby,head.schedtrno,head.schedline,head.ischinese,head.editdate,
      head.editby,
      comp.gccode,comp.gcname,comp.gcpercent,comp.compid
      
      from en_glhead as head
      left join en_glcomponent as comp on comp.trno=head.trno
      where head.doc='EF' and head.trno='" . $trno . "'";

    // select head.trno,head.doc,head.docno,head.dateid,
    // #head.coursecode,head.coursename,
    // head.curriculumname,head.sy,head.section,head.terms,head.period,head.yr,
    // head.curriculumcode,
    // #head.advisercode,head.advisername,
    // head.curriculumdocno,
    // #head.sheddocno,head.subjectcode,head.subjectname,
    // #head.room,
    // head.schedday,head.schedtime,head.scheddocno,head.courseid,head.adviserid,head.syid,
    // head.periodid,head.sectionid,head.semid,head.bldgid,head.roomid,head.subjectid,head.lockdate,head.lockuser,head.viewdate,head.viewby,head.schedtrno,head.schedline,head.ischinese,head.editdate,
    // head.editby,
    // comp.gccode,comp.gcname,comp.gcpercent,comp.compid
    // #comp.editdate,comp.editby
    // from en_gshead as head
    // left join en_gscomponent as comp on comp.trno=head.trno
    // where head.doc='EF' and head.trno='".$trno."'
    // union all
    // select head.trno,head.doc,head.docno,head.dateid,
    // #head.coursecode,head.coursename,
    // head.curriculumname,head.sy,head.section,head.terms,head.period,head.yr,
    // head.curriculumcode,
    // #head.advisercode,head.advisername,
    // head.curriculumdocno,
    // #head.sheddocno,head.subjectcode,head.subjectname,
    // #head.room,
    // head.schedday,head.schedtime,head.scheddocno,head.courseid,head.adviserid,head.syid,
    // head.periodid,head.sectionid,head.semid,head.bldgid,head.roomid,head.subjectid,head.lockdate,head.lockuser,head.viewdate,head.viewby,head.schedtrno,head.schedline,head.ischinese,head.editdate,
    // head.editby,
    // comp.gccode,comp.gcname,comp.gcpercent,comp.compid
    // #comp.editdate,comp.editby
    // from en_glhead as head
    // left join en_glcomponent as comp on comp.trno=head.trno
    // where head.doc='EF' and head.trno='".$trno."' order by line

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportdata($config)
  {
    $data = $this->report_default_query($config['params']['dataid']);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_default_layout($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->pdf_layout($data, $config);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function PDF_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];
    $companyid = $filters['params']['companyid'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

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
    PDF::SetMargins(10, 10);

    if ($companyid == 3) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    }

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, 'GRADE SETUP LIST', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(200, 20, "CODE", '', 'L', false, 0);
    PDF::MultiCell(200, 20, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(200, 20, "PERCENTAGE", '', 'L', false, 0);
    PDF::MultiCell(180, 20, "", '', 'L', false);





    // PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", 'T', 'L', false);
  }

  private function pdf_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "7";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_default_header($data, $filters);
    $i = 0;

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(1000, 0, "", 'T', 'L', false);
    // foreach ($data as $key => $value) {
    //   $i++;
    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(200, 20, $data[$i]['gccode'], '', 'L', false, 0);
      PDF::MultiCell(200, 20, $data[$i]['gcname'], '', 'L', false, 0);
      PDF::MultiCell(200, 20, $data[$i]['gcpercent'], '', 'L', false, 0);
      PDF::MultiCell(180, 20, '', '', 'L', false);


      if (intVal($i) + 1 == $page) {
        $this->PDF_default_header($data, $filters);
        $page += $count;
      }
    }

    for ($i = 0; $i < count($data); $i++) {
    }

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  private function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '1000';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRADE SETUP LIST', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('PERCENTAGE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  private function rpt_default_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '1000';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['gccode'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['gcname'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['gcpercent'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');


      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_default_header($data, $filters);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn






} //end class
