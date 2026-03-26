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

class en
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Attendance Entry';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_athead';
  public $hhead = 'en_glhead';
  public $stock = 'en_atstudents';
  public $hstock = 'en_glstudents';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'adviserid', 'syid', 'periodid', 'roomid', 'schedday', 'schedtime', 'scheddocno', 'courseid', 'subjectid', 'curriculumcode', 'curriculumdocno', 'sectionid', 'yr', 'schedtrno', 'schedline', 'ischinese'];
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
    $getcols = ['action', 'liststatus', 'listdocument', 'teachername', 'subjectname'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[2]['style'] = 'width:130px;whiteSpace:normal;min-width:130px;';
    $cols[3]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
    $cols[4]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
    $cols[3]['label'] = 'Adviser Name';
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
    $qry = "select head.trno, head.docno, left(head.dateid, 10) as dateid, 'DRAFT' as status, cl.clientname as teachername, subj.subjectname
      from " . $this->head . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.clientid = head.adviserid
      left join en_subject as subj on subj.trno = head.subjectid
      where head.doc = ? and num.center = ? and CONVERT(head.dateid, DATE) >= ? and CONVERT(head.dateid, DATE) <= ? " . $condition . " " . $filtersearch . "
      union all
      select head.trno, head.docno, left(head.dateid, 10) as dateid, 'POSTED' as status, cl.clientname as teachername, subj.subjectname
      from " . $this->hhead . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as cl on cl.clientid = head.adviserid
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
    // $tab = ['calendar' => [], 'calendar2' => ['label' => 'View']];
    // $stockbuttons = [''];
    // $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // return $obj;

    $fields = ['otherfees'];
    $col1 = $this->fieldClass->create($fields);
    $tab = [
      $this->gridname => ['gridcolumns' => ['action', 'rcmonthjan', 'tjan', 'rcmonthfeb', 'tfeb', 'rcmonthmar', 'tmar', 'rcmonthapr', 'tapr', 'rcmonthmay', 'tmay', 'rcmonthjun', 'tjun', 'rcmonthjul', 'tjul', 'rcmonthaug', 'taug', 'rcmonthsep', 'tsep', 'rcmonthoct', 'toct', 'rcmonthnov', 'tnov', 'rcmonthdec', 'tdec'], 'headgridbtns' => []],
    ];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'ATTENDANCE';
    $obj[0][$this->gridname]['descriptionrow'] = ['client', 'clientname', 'Student'];
    $obj[0][$this->gridname]['showtotal'] = false;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveitem', 'generateattendance'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['lookupclass'] = 'stockstatusposted';
    $obj[1]['label'] = 'Generate Students';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'advisercode', 'schedcode', 'subjectcode', 'yr'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'yr.type', 'input');
    data_set($col1, 'yr.label', 'Year/Grade');
    data_set($col1, 'advisercode.required', true);
    data_set($col1, 'subjectcode.type', 'input');
    data_set($col1, 'subjectcode.label', 'Subject');
    data_set($col1, 'subjectcode.class', 'sbccsreadonly');
    data_set($col1, 'schedcode.name', 'scheddocno');

    $fields = ['dateid', 'advisername', 'coursecode', 'subjectname', 'ischinese'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'advisername.class', 'sbccsreadonly');
    data_set($col2, 'coursecode.class', 'sbccsreadonly');
    data_set($col2, 'coursecode.required', false);
    data_set($col2, 'subjectname.class', 'sbccsreadonly');
    data_set($col2, 'subjectname.label', 'Subject Description');

    $fields = ['sy', 'section', 'coursename', 'curriculumcode'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'yr.type', 'input');
    data_set($col3, 'section.type', 'input');
    data_set($col3, 'section.required', false);
    data_set($col3, 'coursename.class', 'sbccsreadonly');
    data_set($col3, 'curriculumcode.class', 'sbccsreadonly');
    data_set($col3, 'curriculumdocno.type', 'input');
    data_set($col3, 'section.class', 'sbccsreadonly');
    data_set($col3, 'sy.type', 'input');

    $fields = ['period', 'roomcode', 'schedday', 'schedtime'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'period.required', true);
    data_set($col4, 'roomcode.type', 'input');
    data_set($col4, 'schedday.readonly', true);
    data_set($col4, 'schedtime.readonly', true);
    data_set($col4, 'schedday.class', 'sbccsreadonly');
    data_set($col4, 'schedtime.class', 'sbccsreadonly');
    data_set($col4, 'period.type', 'input');
    data_set($col4, 'period.label', 'Period (SY & Grade/Year) Ex.19-1');

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
    $data[0]['schedtrno'] = 0;
    $data[0]['schedline'] = 0;
    $data[0]['courseid'] = '';
    $data[0]['coursecode'] = '';
    $data[0]['curriculumdocno'] = '';
    $data[0]['curriculumcode'] = '';
    $data[0]['coursename'] = '';
    $data[0]['subjectid'] = '';
    $data[0]['subjectcode'] = '';
    $data[0]['subjectname'] = '';
    $data[0]['periodid'] = $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
    $data[0]['period'] = $this->coreFunctions->getfieldvalue('en_period', 'code', 'isactive=1');
    $schoolyear  = $this->coreFunctions->getfieldvalue('en_period', 'sy', 'isactive=1');
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'sy=?', [$schoolyear]);
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'sy=?', [$schoolyear]);
    $data[0]['sectionid'] = '';
    $data[0]['section'] = '';
    $data[0]['roomid'] = '';
    $data[0]['roomcode'] = '';
    $data[0]['schedday'] = '';
    $data[0]['schedtime'] = '';
    $data[0]['yr'] = '';
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
      c.coursename, c.coursecode, head.curriculumdocno, head.curriculumcode, head.subjectid, s.subjectcode, s.subjectname, head.syid, schoolyear.sy, head.periodid, p.code as period,
      head.sectionid, sec.section, head.roomid, r.roomname as room, r.roomcode, head.schedday, head.schedtime, head.schedtrno, head.schedline, head.yr, head.ischinese";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.clientid = head.adviserid
      left join en_course as c on c.line = head.courseid
      left join en_subject as s on s.trno = head.subjectid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_period as p on p.line = head.periodid
      left join en_section as sec on sec.line = head.sectionid
      left join en_rooms as r on r.line = head.roomid
      where head.trno = ? and num.center = ?
      union all
      " . $qryselect . " from " . $htable . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.clientid = head.adviserid
      left join en_course as c on c.line = head.courseid
      left join en_subject as s on s.trno = head.subjectid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_period as p on p.line = head.periodid
      left join en_section as sec on sec.line = head.sectionid
      left join en_rooms as r on r.line = head.roomid
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
    if ($data['roomid'] == '') {
      $data['roomid'] = 0;
    }
    if ($data['courseid'] == '') {
      $data['courseid'] = 0;
    }
    if ($data['subjectid'] == '') {
      $data['subjectid'] = 0;
    }
    if ($data['sectionid'] == '') {
      $data['sectionid'] = 0;
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
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['advisercode']);
    }
  } // end function

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
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
    $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . " where trno=?", [$trno]);
    if ($this->othersClass->isposted($config)) return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.s'];
    $qry = "insert into " . $this->hhead . "
      (trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate, schedday, schedtime, sectionid,
      scheddocno, subjectid, roomid, courseid, curriculumcode, curriculumdocno, adviserid, yr, schedtrno, schedline, ischinese)
    select trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate, schedday, schedtime, sectionid,
      scheddocno, subjectid, roomid, courseid, curriculumcode, curriculumdocno, adviserid, yr, schedtrno, schedline, ischinese from " . $this->head . " where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      // $qry = "insert into " . $this->hstock . "(trno, line, atdate, status, clientid) select trno, line, atdate, status, clientid from " . $this->stock . " where trno=?";
      $qry = "insert into ".$this->hstock."(trno, line, clientid, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, `dec`, tjan, tfeb, tmar, tapr, tmay, tjun, tjul, taug, tsep, toct, tnov, tdec, schedtrno) select trno, line, clientid, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, `dec`, tjan, tfeb, tmar, tapr, tmay, tjun, tjul, taug, tsep, toct, tnov, tdec, schedtrno from ".$this->stock." where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
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
      (trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate, schedday, schedtime, sectionid,
      scheddocno, subjectid, roomid, courseid, curriculumcode, curriculumdocno, adviserid, yr, schedtrno, schedline, ischinese)
    select trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate, schedday, schedtime, sectionid,
      scheddocno, subjectid, roomid, courseid, curriculumcode, curriculumdocno, adviserid, yr, schedtrno, schedline, ischinese from " . $this->hhead . " where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      // $qry = "insert into " . $this->stock . "(trno, line, atdate, status, clientid) select trno, line, atdate, status, clientid from " . $this->hstock . " where trno=?";
      $qry = "insert into ".$this->stock."(trno, line, clientid, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, `dec`, tjan, tfeb, tmar, tapr, tmay, tjun, tjul, taug, tsep, toct, tnov, tdec, schedtrno) select trno, line, clientid, jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, `dec`, tjan, tfeb, tmar, tapr, tmay, tjun, tjul, taug, tsep, toct, tnov, tdec, schedtrno from ".$this->hstock." where trno=?";
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
    $qry = $sqlselect." from ".$this->stock." as stock left join ".$this->tablenum." as num on num.trno=stock.trno left join client on client.clientid=stock.clientid where stock.trno=? and num.postdate is null
      union all
      ".$sqlselect." from ".$this->hstock." as stock left join ".$this->tablenum." as num on num.trno=stock.trno left join client on client.clientid=stock.clientid where stock.trno=? and num.postdate is not null";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function    

  private function getstockselect($config)
  {
    return "select stock.trno, stock.line, client.client, client.clientname, '' as bgcolor, '' as errcolor, stock.jan as rcmonthjan, stock.feb as rcmonthfeb, stock.mar as rcmonthmar, stock.apr as rcmonthapr, stock.may as rcmonthmay, stock.jun as rcmonthjun, stock.jul as rcmonthjul, stock.aug as rcmonthaug, stock.sep as rcmonthsep, stock.oct as rcmonthoct, stock.nov as rcmonthnov, stock.dec as rcmonthdec, stock.tjan, stock.tfeb, stock.tmar, stock.tapr, stock.tmay, stock.tjun, stock.tjul, stock.taug, stock.tsep, stock.toct, stock.tnov, stock.tdec ";
  }

  public function openstockline($config)
  {
    // $sqlselect = $this->getstockselect($config);
    // $trno = $config['params']['trno'];
    // $line = $config['params']['line'];
    // $qry = $sqlselect . " FROM " . $this->stock . "  as stock where stock.trno = ? and stock.line = ? ";
    // $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    // return $stock;
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect." from ".$this->stock." as stock left join ".$this->tablenum." as num on num.trno=stock.trno left join client on client.clientid=stock.clientid where stock.trno=? and stock.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'getcomponents':
        return $this->getcomponents($config);
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
      case 'generateestud':
        return $this->generateEStud($config);
        break;
      case 'generateattendance':
        return $this->generateAttendance($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'generateattendance':
        return $this->generateAttendance($config);
        break;
    }
  }

  public function generateAttendance($config)
  {
    $trno = $config['params']['trno'];
    $schedtrno = $this->coreFunctions->getfieldvalue($this->head, "schedtrno", "trno=?", [$trno]);
    $acount = $this->coreFunctions->datareader("select count(line) as value from en_atstudents where trno=".$trno);
    if ($acount != '' && $acount != 0) {
      return ['status' => false, 'msg' => 'Students already generated'];
    }
    $students = $this->coreFunctions->opentable("select distinct head.docno,head.dateid,client.client,client.clientname,client.clientid
      from glhead as head
      left join client on client.clientid=head.clientid
      left join glsubject as stock on stock.trno=head.trno
      where head.doc='ER' and stock.screfx=".$schedtrno);
    if (!empty($students)) {
      foreach ($students as $stud) {
        $lastline = $this->coreFunctions->datareader("select line as value from en_atstudents where trno=".$trno." order by line desc");
        if ($lastline == '') $lastline = 0;
        $lastline += 1;
        $this->coreFunctions->execqry("insert into en_atstudents(trno, line, clientid, schedtrno) values(".$trno.", ".$lastline.", ".$stud->clientid.", ".$schedtrno.")", 'insert');
      }
      $stocks = $this->openstock($trno, $config);
      return ['status' => true, 'msg' => 'Attendance generated', 'reloaddata' => true];
    } else {
      return ['status' => false, 'msg' => 'No registered students for this schedule.', 'reloaddata' => true];
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

  public function additem($action, $config)
  {
    $trno = $config['params']['data']['trno'];
    $line = $config['params']['data']['line'];
    $config['params']['line'] = $line;

    $data = [
      'trno' => $trno,
      'line' => $line,
      'jan' => $config['params']['data']['rcmonthjan'],
      'feb' => $config['params']['data']['rcmonthfeb'],
      'mar' => $config['params']['data']['rcmonthmar'],
      'apr' => $config['params']['data']['rcmonthapr'],
      'may' => $config['params']['data']['rcmonthmay'],
      'jun' => $config['params']['data']['rcmonthjun'],
      'jul' => $config['params']['data']['rcmonthjul'],
      'aug' => $config['params']['data']['rcmonthaug'],
      'sep' => $config['params']['data']['rcmonthsep'],
      'oct' => $config['params']['data']['rcmonthoct'],
      'nov' => $config['params']['data']['rcmonthnov'],
      'dec' => $config['params']['data']['rcmonthdec'],
      'tjan' => $config['params']['data']['tjan'],
      'tfeb' => $config['params']['data']['tfeb'],
      'tmar' => $config['params']['data']['tmar'],
      'tapr' => $config['params']['data']['tapr'],
      'tmay' => $config['params']['data']['tmay'],
      'tjun' => $config['params']['data']['tjun'],
      'tjul' => $config['params']['data']['tjul'],
      'taug' => $config['params']['data']['taug'],
      'tsep' => $config['params']['data']['tsep'],
      'toct' => $config['params']['data']['toct'],
      'tnov' => $config['params']['data']['tnov'],
      'tdec' => $config['params']['data']['tdec'],
      'editdate' => $this->othersClass->getCurrentTimeStamp(),
      'editby' => $config['params']['user']
    ];
    foreach($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $upd = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
    if ($upd == 1) {
      return ['status' => true, 'msg' => ''];
    } else {
      return ['status' => false, 'msg' => 'Error updating record'];
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
    // $isupdate = true;
    // $msg1 = '';
    // $msg2 = '';

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
    $qry = "delete from $this->stock where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'COMPONENT', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['gccode'] . ' Name:' . $data[0]['gcname'] . ' Percent:' . $data[0]['gcpercent'] . ' Component ID:' . $data[0]['compid']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function


  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function generateEStud($config)
  {
    $trno = $config['params']['trno'];
    // $sql = "select head.trno, head.dateid, head.docno, client.client, head.clientname, subj.subjectcode, subj.subjectname,
    //   subj.isqty, subj.laboratory, subj.lecture, subj.hours, subj.instructorcode
    //   from en_glhead as head
    //   left join en_glsubject as subj on subj.trno=head.trno
    //   left join en_glhead as sc on sc.trno=subj.screfx
    //   left join client on client.clientid=head.clientid
    //   where head.doc='ER' and sc.docno='' and head.coursecode='' and head.sy='' and head.section='' and subj.subjectcode='" & "" & txtSubject.Text & "'"
    $d = $this->coreFunctions->opentable("select adviserid, yr, semid, syid, periodid from en_gehead where trno=?", [$trno]);
    if (!empty($d)) {
      $adviserid = $d[0]->adviserid;
      $yr = $d[0]->yr;
      $semid = $d[0]->semid;
      $syid = $d[0]->syid;
      $periodid = $d[0]->periodid;
    } else {
      $adviserid = $yr = $semid = $syid = $periodid = 0;
    }
    $sql = " select s.trno, s.line, h.dateid, h.docno, client.client, client.clientname, s.trno, s.screfx, s.screfx, hb.docno,  s.subjectid, h.courseid, sb.instructorid, h.yr, h.semid, h.syid, h.periodid
      from en_glhead as h
      left join en_glsubject as s on s.trno = h.trno
      left join client on client.clientid = h.clientid
      left join en_glsubject as sb on sb.trno = s.refx and sb.line = s.linex
      left join en_glhead as hb on hb.trno = sb.trno
      where h.doc='ER' and sb.instructorid=? and h.yr=? and h.semid=? and h.syid=? and h.periodid=?";
    $data = $this->coreFunctions->opentable($sql, [$adviserid, $yr, $semid, $syid, $periodid]);
    if (!empty($data)) {
      foreach ($data as $dt) {
        $sql = "select head.trno, head.docno, head.dateid, head.sy, head.section, head.adviserid, head.scheddocno,
          head.roomid, head.schedday, head.schedtime, subcomp.component, subcomp.gccode, subcomp.gcsubcode, subcomp.topic, subcomp.noofitems, subcomp.line
        from en_glhead as head
        left join en_glsubcomponent as subcomp on subcomp.trno = head.trno
        where head.doc='EF' and head.adviserid=? and head.yr=? and head.semid=? and head.syid=? and head.periodid=?";
        $dd = $this->coreFunctions->opentable($sql, [$adviserid, $yr, $semid, $syid, $periodid]);
        if (!empty($dd)) {
          foreach ($dd as $datas) {
            $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') $line = 0;
            $line += 1;
            $s = [
              'trno' => $trno,
              'line' => $line,
              'client' => $dt->client,
              'clientname' => $dt->clientname,
              'gccode' => $datas->gccode,
              'gcsubcode' => $datas->gcsubcode,
              'components' => $datas->component,
              'topic' => $datas->topic,
              'noofitems' => $datas->noofitems,
              'points' => 0,
              'gstrno' => $datas->trno,
              'gsdocno' => $datas->docno,
              'refx' => $dt->trno,
              'linex' => $dt->line
            ];
            $this->coreFunctions->sbcinsert($this->stock, $s);
          }
        }
      }
    }
  }


  // public function reportsetup($config){
  //   $txtfield = $this->createreportfilter();
  //   $txtdata = $this->reportparamsdata($config);      
  //   $modulename = $this->modulename;
  //   $data = [];
  //   $style = 'width:500px;max-width:500px;';
  //   return ['status'=>true,'msg'=>'Loaded Success','modulename'=>$modulename,'data'=>$data,'txtfield'=>$txtfield,'txtdata'=>$txtdata,'style'=>$style,'directprint'=>false]; 
  // }


  // public function createreportfilter(){
  //      $fields = ['radioprint','prepared','approved','received','print'];
  //      $col1 = $this->fieldClass->create($fields);
  //      return array('col1'=>$col1);
  // }

  // public function reportparamsdata($config){
  //     return $this->coreFunctions->opentable(
  //       "select 
  //       'default' as print,
  //       '' as prepared,
  //       '' as approved,
  //       '' as received
  //       ");
  // }

  // private function report_default_query($trno){
  //   $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  //     date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
  //     stock.barcode, stock.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  //     stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  //     item.sizeid,m.model_name as model
  //     from sohead as head left join sostock as stock on stock.trno=head.trno 
  //     left join item on item.barcode=stock.barcode
  //     left join model_masterfile as m on m.model_id = item.model
  //     left join client on client.client=head.wh
  //     left join client as cust on cust.client = head.client
  //     where head.trno='$trno'
  //     union all
  //     select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  //     date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
  //     stock.barcode, stock.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  //     stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  //     item.sizeid,m.model_name as model
  //     from hsohead as head 
  //     left join hsostock as stock on stock.trno=head.trno
  //     left join item on item.barcode=stock.barcode 
  //     left join model_masterfile as m on m.model_id = item.model
  //     left join client on client.client=head.wh
  //     left join client as cust on cust.client = head.client
  //     where head.doc='so' and head.trno='$trno' order by line";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // }//end fn  


  // public function reportdata($config){
  //   $data = $this->report_default_query($config['params']['dataid']);
  //   $str = $this->reportplotting($config,$data);
  //   return ['status'=>true,'msg'=>'Generating report successfully.','report'=>$str];
  // }

  // public function reportplotting($params,$data){
  //   $companyid = $params['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $params['params']);

  //   $center = $params['params']['center'];
  //   $username = $params['params']['user'];

  //   $str = '';
  //   $count=35;
  //   $page=35;
  //   $str .= $this->reporter->beginreport();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->letterhead($center,$username);
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br><br>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('SALES ORDER','600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
  //   $str .= $this->reporter->col('DOCUMENT # :','100',null,false,'1px solid ','','L','Century Gothic','13','B','','');
  //   $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','B','L','Century Gothic','13','','','').'<br />';
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('CUSTOMER : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
  //   $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
  //   $str .= $this->reporter->col('DATE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('ADDRESS : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
  //   $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'500',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
  //   $str .= $this->reporter->col('TERMS : ','50',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'150',null,false,'1px solid ','B','R','Century Gothic','12','','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null,null,false,'1px solid ','','R','Century Gothic','10','','','4px');
  //   $str .= $this->reporter->pagenumber('Page');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   // $str .= $this->reporter->printline();
  //   //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('QTY','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('UNIT','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('D E S C R I P T I O N','500px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('UNIT PRICE','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('(+/-) %','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('TOTAL','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');

  //   $totalext=0;
  //   for($i=0;$i<count($data);$i++){
  //     $str .= $this->reporter->startrow();
  //     $str .= $this->reporter->addline();
  //     $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty', $params['params'])),'50px',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
  //     $str .= $this->reporter->col($data[$i]['uom'],'50px',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
  //     $str .= $this->reporter->col($data[$i]['itemname'],'500px',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
  //     $str .= $this->reporter->col(number_format($data[$i]['gross'],$decimal),'125px',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
  //     $str .= $this->reporter->col($data[$i]['disc'],'50px',null,false,'1px solid ','','C','Century Gothic','11','','','');
  //     $str .= $this->reporter->col(number_format($data[$i]['ext'],$decimal),'125px',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
  //     $totalext=$totalext+$data[$i]['ext'];  

  //     if($this->reporter->linecounter==$page){
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->page_break();

  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->letterhead($center, $username);
  //       $str .= $this->reporter->endtable();
  //       $str .= '<br><br>';

  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //       $str .= $this->reporter->col('SALES ORDER','600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
  //       $str .= $this->reporter->col('DOCUMENT # :','100',null,false,'1px solid ','','L','Century Gothic','13','B','','');
  //       $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','B','L','Century Gothic','13','','','').'<br />';
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col('CUSTOMER : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
  //       $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
  //       $str .= $this->reporter->col('DATE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //       $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col('ADDRESS : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
  //       $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'500',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
  //       $str .= $this->reporter->col('TERMS : ','50',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //       $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'150',null,false,'1px solid ','B','R','Century Gothic','12','','','');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();

  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow(null,null,false,'1px solid ','','R','Century Gothic','10','','','4px');
  //       $str .= $this->reporter->pagenumber('Page');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();

  //       $str .= $this->reporter->printline();

  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //       $str .= $this->reporter->col('QTY','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //       $str .= $this->reporter->col('UNIT','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //       $str .= $this->reporter->col('D E S C R P T I O N','500px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //       $str .= $this->reporter->col('UNIT PRICE','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //       $str .= $this->reporter->col('(+/-) %','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //       $str .= $this->reporter->col('TOTAL','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->printline();
  //       $page=$page + $count;
  //     }
  //   }   
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('','50px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col('','50px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col('','500px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col('','125px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col('GRAND TOTAL :','50px',null,false,'1px dotted ','T','R','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col(number_format($totalext,$decimal),'125px',null,false,'1px dotted ','T','R','Century Gothic','12','B','','');
  //   $str .= $this->reporter->endrow();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->printline();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('NOTE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col($data[0]['rem'],'600',null,false,'1px solid ','','L','Century Gothic','12','','','');
  //   $str .= $this->reporter->col('','160',null,false,'1px solid ','','L','Century Gothic','12','B','','');

  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br><br>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Prepared By : ','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
  //   $str .= $this->reporter->col('Approved By :','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
  //   $str .= $this->reporter->col('Received By :','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= '<br>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col($params['params']['dataparams']["prepared"],'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col($params['params']['dataparams']["approved"],'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col($params['params']['dataparams']["received"],'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->endtable();


  //   $str .= $this->reporter->endreport();

  //   return $str;
  // }


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
    $this->logger->sbcviewreportlog($config);
    $student = $config['params']['dataparams']['ehstudentlookup'];
    $attendance = $config['params']['dataparams']['emattendancelookup'];
    $trno = $config['params']['dataid'];

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);

    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data, $trno);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
