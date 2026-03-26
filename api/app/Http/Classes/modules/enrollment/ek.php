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

class ek
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Student Report Card';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_srchead';
  public $stock = '';
  public $hstock = '';

  public $hhead = 'en_glhead';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'periodid', 'syid', 'levelid', 'courseid', 'yr', 'adviserid', 'sectionid', 'rem', 'schedtrno'];

  private $except = ['trno', 'dateid'];
  private $acctg = [];
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
      'view' => 2693,
      'edit' => 2692,
      'new' => 2694,
      'save' => 2695,
      'change' => 2697,
      'delete' => 2696,
      'print' => 2702,
      'lock' => 2698,
      'unlock' => 2699,
      'post' => 2700,
      'unpost' => 2701,
      'additem' => 2703,
      'edititem' => 2704,
      'deleteitem' => 2705
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate'];
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
      $searchfield = ['head.docno'];
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

    $qry = "select head.trno, head.docno, left(head.dateid, 10) as dateid, 'DRAFT' as status, head.adviserid, cl.clientname as teachername
      from " . $this->head . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.clientid = head.adviserid
      where head.doc = ? and num.center = ? and CONVERT(head.dateid, DATE) >= ? and CONVERT(head.dateid, DATE) <= ? " . $condition . " " . $filtersearch . "
      union all
      select head.trno, head.docno, left(head.dateid, 10) as dateid, 'POSTED' as status, head.adviserid, cl.clientname as teachername
      from " . $this->hhead . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.clientid = head.adviserid
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
      'lock',
      'unlock',
      'post',
      'unpost',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  }

  public function createTab($access, $config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'clientname']]];

    $stockbuttons = ['rcattendance', 'rcremarks', 'rcenggrade', 'rcchigrade'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'STUDENTS';
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['columns'][0]['label'] = 'Actions (Attendance, Remarks, Eng/Chi. Grades)';
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:200px;min-width:200px;max-width:200px;whiteSpace:normal';
    $obj[0][$this->gridname]['columns'][1]['label'] = 'Name';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:300px;min-width:300px;max-width:300px;whiteSpace:normal';
    return $obj;
  }

  public function createtabbutton($config)
  {
    // $tbuttons = ['generateattendance'];
    // $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj = [];
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'advisercode', 'sy', 'schedcode'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'advisercode.required', true);
    data_set($col1, 'schedcode.name', 'scheddocno');
    data_set($col1, 'sy.type', 'input');

    $fields = ['dateid', 'advisername', 'period', 'coursecode'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'advisername.class', 'sbccsreadonly');
    data_set($col2, 'coursecode.class', 'sbccsreadonly');
    data_set($col2, 'coursecode.required', false);
    data_set($col2, 'period.required', true);
    data_set($col2, 'period.type', 'input');
    data_set($col2, 'period.label', 'Period (SY & Grade/Year) Ex.19-1');

    $fields = ['yr', 'section', 'levels', 'coursename'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'yr.type', 'input');
    data_set($col3, 'yr.label', 'Year/Grade');
    data_set($col3, 'section.type', 'input');
    data_set($col3, 'section.required', false);
    data_set($col3, 'coursename.class', 'sbccsreadonly');
    data_set($col3, 'section.class', 'sbccsreadonly');
    data_set($col3, 'levels.class', 'sbccsreadonly');

    $fields = ['rem'];
    $col4 = $this->fieldClass->create($fields);

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
    $data[0]['schedtrno'] = 0;
    $data[0]['scheddocno'] = '';
    $data[0]['courseid'] = '';
    $data[0]['coursecode'] = '';
    $data[0]['coursename'] = '';
    $data[0]['levelid'] = 0;
    $data[0]['levels'] = '';
    $data[0]['rem'] = '';
    $schoolyear  = $this->coreFunctions->getfieldvalue('en_period', 'sy', 'isactive=1');
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'sy=?', [$schoolyear]);
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'sy=?', [$schoolyear]);
    $data[0]['periodid'] = $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
    $data[0]['period'] = $this->coreFunctions->getfieldvalue('en_period', 'code', 'isactive=1');
    $data[0]['yr'] = '';
    $data[0]['sectionid'] = '';
    $data[0]['section'] = '';
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

    $qryselect = "select head.trno, head.docno, head.adviserid, client.clientname as advisername, client.client as advisercode, head.dateid,
      head.schedtrno, head.courseid, c.coursename, c.coursecode, head.syid, schoolyear.sy, head.periodid, p.code as period, level.levels,
      head.yr, head.sectionid, sec.section, sched.docno as scheddocno, head.rem, head.levelid ";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join en_glhead as sched on sched.trno=head.schedtrno
      left join client on client.clientid = head.adviserid
      left join en_course as c on c.line = head.courseid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_period as p on p.line = head.periodid
      left join en_section as sec on sec.line = head.sectionid
      left join en_levels as level on level.line = head.levelid
      where head.trno = ? and num.center = ?
      union all
      " . $qryselect . " from " . $htable . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join en_glhead as sched on sched.trno=head.schedtrno
      left join client on client.clientid = head.adviserid
      left join en_course as c on c.line = head.courseid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_period as p on p.line = head.periodid
      left join en_section as sec on sec.line = head.sectionid
      left join en_levels as level on level.line = head.levelid
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
      // $this->generateAttendance($config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->head, $data) > 0) {
        // $this->generateAttendance($config);
        $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['coursecode']);
      }
    }
  } // end function

  public function generateAttendance($config)
  {
    $trno = $config['params']['trno'];
    $students = $config['params']['row'];
    $qry = "select periodid, syid, yr, schedtrno, courseid, adviserid, sectionid, levelid from en_srchead where trno=?";
    $head = $this->coreFunctions->opentable($qry, [$trno]);

    // $check = $this->coreFunctions->opentable("select * from en_srcattendance where trno=?", [$trno]);
    // if(!empty($check)) {
    //   $status = false;
    //   $msg = 'Attendance already generated.';
    // } else {
    // }
    if (!empty($head)) {
      $this->coreFunctions->execqry("delete from en_srcattendance where trno=?", 'delete', [$trno]);
      $attendance = $this->coreFunctions->opentable("select * from en_attendancesetup where syid=? and levelid=?", [$head[0]->syid, $head[0]->levelid]);
      if (!empty($attendance)) {
        if (!empty($students)) {
          foreach ($students as $stud) {
            $selectqry = "select stock.line, head.trno, head.docno, head.dateid, sy.sy, period.code as period, head.schedday,
              head.schedtime, sec.section, i.client as instructorcode, i.clientname as instructorname, head.scheddocno,
              subj.subjectcode, subj.subjectname, rm.roomcode, bldg.bldgcode, course.coursecode, course.coursename,
              head.curriculumcode, head.curriculumdocno, client.client, client.clientname, stock.atdate, attype.type";
            $qry = "select sy, period, section, coursecode, coursename, coursename, curriculumcode, curriculumdocno, client, clientname, month(atdate) as mon, type,count(line) as counts
              from (" . $selectqry . "
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
                where head.syid=" . $head[0]->syid . " and client.clientid=" . $stud['clientid'] . "
              union all
              " . $selectqry . "
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
              where head.syid=" . $head[0]->syid . " and client.clientid=" . $stud['clientid'] . ") as tb where type is not null
                group by sy, period, section, coursecode, coursename, coursename, curriculumcode, curriculumdocno, client, clientname, type, month(atdate) order by clientname";
            $data = $this->coreFunctions->opentable($qry);
            $at = ['trno' => $trno, 'clientid' => $stud['clientid'], 'syid' => $head[0]->syid, 'levelid' => $head[0]->levelid, 'total' => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0];
            $atlate = ['trno' => $trno, 'clientid' => $stud['clientid'], 'syid' => $head[0]->syid, 'levelid' => $head[0]->levelid, 'islate' => 1, 'total' => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0];
            $atupdate = false;
            $atlateupdate = false;
            if (!empty($data)) {
              foreach ($data as $d) {
                switch (strtolower($d->type)) {
                  case 'absent':
                    $atupdate = true;
                    $at[$d->mon] += $d->counts;
                    $at['total'] += $d->counts;
                    break;
                  case 'late':
                    $atlateupdate = true;
                    $atlate[$d->mon] += $d->counts;
                    $atlate['total'] += $d->counts;
                    break;
                }
              }
            }
            if ($atupdate) {
              $atdata = [
                'trno' => $trno,
                'clientid' => $stud['clientid'],
                'syid' => $head[0]->syid,
                'levelid' => $head[0]->levelid,
                'dayspresent' => ($attendance[0]->totaldays - $at['total']),
                'jan' => ($attendance[0]->jan - $at[1]),
                'feb' => ($attendance[0]->feb - $at[2]),
                'mar' => ($attendance[0]->mar - $at[3]),
                'apr' => ($attendance[0]->apr - $at[4]),
                'may' => ($attendance[0]->may - $at[5]),
                'jun' => ($attendance[0]->jun - $at[6]),
                'jul' => ($attendance[0]->jul - $at[7]),
                'aug' => ($attendance[0]->aug - $at[8]),
                'sep' => ($attendance[0]->sep - $at[9]),
                'oct' => ($attendance[0]->oct - $at[10]),
                'nov' => ($attendance[0]->nov - $at[11]),
                'dec' => ($attendance[0]->dec - $at[12])
              ];
              $this->coreFunctions->execqry("insert into en_srcattendance(`trno`, `clientid`, `dayspresent`, `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec`, `syid`, `levelid`) values('" . $atdata['trno'] . "', '" . $atdata['clientid'] . "', '" . $atdata['dayspresent'] . "', '" . $atdata['jan'] . "', '" . $atdata['feb'] . "', '" . $atdata['mar'] . "', '" . $atdata['apr'] . "', '" . $atdata['may'] . "', '" . $atdata['jun'] . "', '" . $atdata['jul'] . "', '" . $atdata['aug'] . "', '" . $atdata['sep'] . "', '" . $atdata['oct'] . "', '" . $atdata['nov'] . "', '" . $atdata['dec'] . "', '" . $atdata['syid'] . "', '" . $atdata['levelid'] . "')", 'insert');
            }
            if ($atlateupdate) {
              $latetotal = ($atlate[1] + $atlate[2] + $atlate[3] + $atlate[4] + $atlate[5] + $atlate[6] + $atlate[7] + $atlate[8] + $atlate[9] + $atlate[10] + $atlate[11] + $atlate[12]);
              $this->coreFunctions->execqry("insert into en_srcattendance(`trno`, `clientid`, `dayspresent`, `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec`, `syid`, `levelid`, `islate`) values('" . $trno . "', '" . $stud['clientid'] . "', '" . $latetotal . "', '" . $atlate[1] . "', '" . $atlate[2] . "', '" . $atlate[3] . "', '" . $atlate[4] . "', '" . $atlate[5] . "', '" . $atlate[6] . "', '" . $atlate[7] . "', '" . $atlate[8] . "', '" . $atlate[9] . "', '" . $atlate[10] . "', '" . $atlate[11] . "', '" . $atlate[12] . "', '" . $head[0]->syid . "', '" . $head[0]->levelid . "', 1)", 'insert');
            }
          }
          $status = true;
          $msg = 'Attendance successfully generated.';
        } else {
          $msg = "Attendance setup not found.";
          $status = false;
        }
      }
    }
    return ['inventory' => $this->openstock($trno, $config), 'status' => $status, 'msg' => $msg];
  }

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    // $this->coreFunctions->execqry('delete from '.$this->stock." where trno=?",'delete',[$trno]);
    $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from " . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from en_srcattendance where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from en_srcremarks where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . " where trno=?", [$trno]);
    if ($this->othersClass->isposted($config)) return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    $qry = "insert into " . $this->hhead . "
      (trno, doc, docno, dateid, periodid, syid, levelid, courseid, yr, adviserid, sectionid, rem, schedtrno, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate)
    select trno, doc, docno, dateid, periodid, syid, levelid, courseid, yr, adviserid, sectionid, rem, schedtrno, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate from " . $this->head . " where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      if ($this->coreFunctions->execqry("insert into en_glsrcattendance(trno, line, clientid, dayspresent, attrno, `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec`) select trno, line, clientid, dayspresent, attrno, `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec` from en_srcattendance where trno=?", 'insert', [$trno])) {
        $this->coreFunctions->execqry("delete from en_srcattendance where trno=?", "delete", [$trno]);
        if ($this->coreFunctions->execqry("insert into en_glsrcremarks(trno, line, clientid, quarterid, remarks, semid, ischinese) select trno, line, clientid, quarterid, remarks, semid, ischinese from en_srcremarks where trno=?", "insert", [$trno])) {
          $date = $this->othersClass->getCurrentTimeStamp();
          $this->coreFunctions->execqry("delete from en_srcremarks where trno=?", "delete", [$trno]);
          $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
          $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
          $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from en_glsrcremarks where trno=?", "delete", [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Remarks'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Attendance'];
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
    $qry = "insert into " . $this->head . "
      (trno, doc, docno, dateid, periodid, syid, levelid, courseid, yr, adviserid, sectionid, rem, schedtrno, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate)
    select trno, doc, docno, dateid, periodid, syid, levelid, courseid, yr, adviserid, sectionid, rem, schedtrno, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate from " . $this->hhead . " where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      if ($this->coreFunctions->execqry("insert into en_srcattendance(trno, line, clientid, dayspresent, attrno, `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec`) select trno, line, clientid, dayspresent, attrno, `jan`, `feb`, `mar`, `apr`, `may`, `jun`, `jul`, `aug`, `sep`, `oct`, `nov`, `dec` from en_glsrcattendance where trno=?", 'insert', [$trno])) {
        if ($this->coreFunctions->execqry("insert into en_srcremarks(trno, line, clientid, quarterid, remarks, semid, ischinese) select trno, line, clientid, quarterid, remarks, semid, ischinese from en_glsrcremarks where trno=?", "insert", [$trno])) {
          $date = date("Y-m-d H:i:s");
          $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
          $this->coreFunctions->execqry("delete from en_glsrcattendance where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from en_glsrcremarks where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
          return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from en_srcremarks where trno=?", "delete", [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, Remarks table problems...'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, Attendance table problems...'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno, stock.line, stock.code, stock.title, stock.yr as year, stock.sectionid, stock.times, stock.order, s.section, '' as bgcolor, '' as errcolor";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $qry = "select periodid, syid, courseid, yr, adviserid, sectionid from " . $this->head . " where trno=? union all select periodid, syid, courseid, yr, adviserid, sectionid from " . $this->hhead . " where trno=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    $stock = [];
    if (!empty($head)) {
      $qry = "select distinct " . $trno . " as trno, qg.clientid, client.clientname, '' as bgcolor from en_gequartergrade as qg left join client on client.clientid=qg.clientid left join en_glhead as head on head.trno=qg.trno where head.periodid=? and syid=? and courseid=? and yr=? and adviserid=? and sectionid=?";
      $stock = $this->coreFunctions->opentable($qry, [$head[0]->periodid, $head[0]->syid, $head[0]->courseid, $head[0]->yr, $head[0]->adviserid, $head[0]->sectionid]);
    }
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . " from " . $this->stock . " as stock left join en_section as s on s.line=stock.sectionid where stock.trno=? and stock.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line,]);
    return $stock;
  } // end function

  public function addrow($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['trno'] = $config['params']['trno'];
    $data['code'] = '';
    $data['title'] = '';
    $data['year'] = '';
    $data['sectionid'] = 0;
    $data['section'] = '';
    $data['times'] = '';
    $data['order'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function saveitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->saveperitem($config, 'all');
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }

  public function saveperitem($config, $type = '')
  {
    if ($type == '') {
      $config['params']['data'] = $config['params']['row'];
    }
    $data = [
      'trno' => $config['params']['data']['trno'],
      'line' => $config['params']['data']['line'],
      'code' => $config['params']['data']['code'],
      'title' => $config['params']['data']['title'],
      'yr' => $config['params']['data']['year'],
      'sectionid' => $config['params']['data']['sectionid'],
      'times' => $config['params']['data']['times'],
      'order' => $config['params']['data']['order']
    ];
    if ($data['line'] == 0) {
      $line = $this->coreFunctions->datareader("select line as value from " . $this->stock . " where trno=? order by line desc limit 1", [$config['params']['data']['trno']]);
      if ($line == '') $line = 0;
      $line += 1;
      $data['line'] = $line;
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        if ($type == '') {
          $config['params']['line'] = $data['line'];
          $row = $this->openstockline($config);
          return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
        } else {
          return true;
        }
      } else {
        if ($type == '') {
          return ['status' => false, 'msg' => 'Add item failed'];
        } else {
          return true;
        }
      }
    } else {
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $data['trno'], 'line' => $data['line']]);
      if ($type == '') {
        $row = $this->openstockline($config);
        return ['status' => true, 'row' => $row, 'msg' => 'Update item successfully'];
      } else {
        return true;
      }
    }
  }

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'saveperitem':
        return $this->saveperitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->saveitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'generateattendance':
        return $this->generateAttendance($config);
        break;
    }
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'DETAIL', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'CARD DETAIL', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['code'] . ' Title:' . $data[0]['title'] . ' Year/Grade:' . $data[0]['year'] . ' Sectionid:' . $data[0]['sectionid'] . ' Times:' . $data[0]['times'] . ' Order:' . $data[0]['order']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end 

  public function reportsetup($config)
  {

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);

    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
