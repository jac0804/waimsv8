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

class eg
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Student Grade Entry';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_sgshead';
  public $hhead = 'en_glhead';
  public $stock = 'en_sgssubject';
  public $hstock = 'en_glsubject';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'clientid', 'client', 'clientname', 'levels', 'levelid', 'courseid', 'coursename', 'sy', 'syid', 'curriculumdocno'];
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
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'liststudent', 'listsy', 'listlevels', 'listcourse', 'listcoursename'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[4]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';
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
      $searchfield = ['head.docno', 'client.clientname', 'client.client', 'schoolyear.sy', 'level.levels', 'course.coursename', 'course.coursecode'];
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

    $qry = "
    select head.trno, head.docno, course.coursecode, left(head.dateid,10) as dateid, 'DRAFT' as status, course.coursename, head.curriculumdocno,
      en_cchead.curriculumcode, head.curriculumdocno, en_cchead.curriculumname, head.clientid, client.client, client.clientname, head.syid, schoolyear.sy, head.levelid, level.levels
    from " . $this->head . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join en_course as course on course.line = head.courseid
      left join client on client.clientid = head.clientid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_cchead on en_cchead.docno = head.curriculumdocno
      left join en_levels as level on level.line = head.levelid
    where head.doc = ? and num.center = ? and CONVERT(head.dateid,DATE) >=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select head.trno, head.docno, course.coursecode, left(head.dateid,10) as dateid, 'POSTED' as status, course.coursename, head.curriculumdocno,
      en_cchead.curriculumcode, head.curriculumdocno, en_cchead.curriculumname, head.clientid, client.client, client.clientname, head.syid, schoolyear.sy, head.levelid, level.levels
    from " . $this->hhead . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join en_course as course on course.line = head.courseid
      left join client on client.clientid = head.clientid
      left join en_schoolyear as schoolyear on schoolyear.line = head.syid
      left join en_cchead on en_cchead.docno = head.curriculumdocno
      left join en_levels as level on level.line = head.levelid
    where head.doc = ? and num.center = ? and CONVERT(head.dateid,DATE) >=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'subjectcode', 'subjectname', 'units', 'lecture', 'laboratory', 'yearnum', 'term', 'grade', 'equivalent']]];

    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'SUBJECT';
    $obj[0][$this->gridname]['descriptionrow'] = ['subjectname', 'subjectcode', 'Subject'];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;'; //action
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][5]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][6]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][7]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][9]['type'] = 'label';


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['generatecurriculum', 'saveitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'Curriculum';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'lcoursecode'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Student Code');
    data_set($col1, 'client.class', 'csclient sbccsreadonly');
    data_set($col1, 'client.lookupclass', 'student');
    data_set($col1, 'client.action', 'lookupclient');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'clientname', 'coursename'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'clientname.class', 'sbccsreadonly');
    data_set($col2, 'coursename.class', 'sbccsreadonly');

    $fields = ['levels', 'sy'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'levels.class', 'sbccsreadonly');
    data_set($col3, 'sy.type', 'input');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['clientid'] = '';
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['levelid'] = '';
    $data[0]['levels'] = '';
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'issy=1');
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'issy=1');
    $data[0]['coursecode'] = '';
    $data[0]['courseid'] = 0;
    $data[0]['coursename'] = '';
    $data[0]['curriculumdocno'] = '';
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

    $qryselect = "select head.trno, head.docno, head.clientid, head.dateid, client.clientname, client.client, head.levelid, level.levels, head.syid, en_schoolyear.sy, head.courseid, course.coursename, course.coursecode, head.curriculumdocno, curriculum.curriculumname, curriculum.curriculumcode";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.clientid = head.clientid
      left join en_levels as level on level.line = head.levelid
      left join en_schoolyear on en_schoolyear.line = head.syid
      left join en_course as course on course.line = head.courseid
      left join en_cchead as curriculum on curriculum.docno = head.curriculumdocno
      where head.trno = ? and num.center = ?
      union all
      " . $qryselect . " from " . $htable . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.clientid = head.clientid
      left join en_levels as level on level.line = head.levelid
      left join en_schoolyear on en_schoolyear.line = head.syid
      left join en_course as course on course.line = head.courseid
      left join en_cchead as curriculum on curriculum.docno = head.curriculumdocno
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

    $data['doc'] = $config['params']['doc'];
    $data2 = [
      'trno' => $data['trno'],
      'docno' => $data['docno'],
      'dateid' => $data['dateid'],
      'clientid' => $data['clientid'],
      'levelid' => $data['levelid'],
      'syid' => $data['syid'],
      'courseid' => $data['courseid'],
      'curriculumdocno' => $data['curriculumdocno'],
      'doc' => $data['doc']
    ];
    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data2, ['trno' => $head['trno']]);
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data2);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['coursecode'] . ' - ' . $head['curriculumdocno']);
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
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . " (trno, doc, docno, dateid, clientid, levelid, syid, courseid, curriculumdocno)
      select head.trno, head.doc, head.docno, head.dateid, head.clientid, head.levelid, head.syid, head.courseid, head.curriculumdocno from " . $this->head . " as head where head.trno = ? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . " (trno, line, curriculumcode, yearnum, terms, units, pre1, pre2, pre3, pre4, pre5, lecture,
        laboratory, coreq, grade, equivalent, subjectid, semid)
        select stock.trno, stock.line, stock.curriculumcode, stock.yearnum, stock.terms, stock.units, stock.pre1, stock.pre2,
          stock.pre3, stock.pre4, stock.pre5, stock.lecture, stock.laboratory, stock.coreq, stock.grade, stock.equivalent, stock.subjectid, stock.semid from " . $this->stock . " as stock where stock.trno = ?";
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

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . " (trno, doc, docno, dateid, clientid, levelid, syid, courseid, curriculumdocno)
      select head.trno, head.doc, head.docno, head.dateid, head.clientid, head.levelid, head.syid, head.courseid, head.curriculumdocno from " . $this->hhead . " as head where head.trno = ? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . " (trno, line, curriculumcode, yearnum, terms, units, pre1, pre2, pre3, pre4, pre5, lecture,
        laboratory, coreq, grade, equivalent, subjectid, semid)
        select stock.trno, stock.line, stock.curriculumcode, stock.yearnum, stock.terms, stock.units, stock.pre1, stock.pre2,
          stock.pre3, stock.pre4, stock.pre5, stock.lecture, stock.laboratory, stock.coreq, stock.grade, stock.equivalent, stock.subjectid, stock.semid from " . $this->hstock . " as stock where stock.trno = ?";
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
    $qry = $sqlselect . " from " . $this->stock . " as stock left join " . $this->tablenum . " as num on num.trno = stock.trno
    left join en_subject as s on s.trno = stock.subjectid
    left join en_term as t on t.line = stock.semid
    where stock.trno = ? and num.postdate is null
    union all " . $sqlselect . " from " . $this->hstock . " as stock left join " . $this->tablenum . " as num on num.trno = stock.trno
    left join en_subject as s on s.trno = stock.subjectid
    left join en_term as t on t.line = stock.semid
    where stock.trno = ? and num.postdate is not null order by line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function    

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno, stock.line, stock.curriculumcode, stock.yearnum, stock.terms, s.subjectcode, s.subjectname, stock.units, stock.pre1, stock.pre2, stock.pre3,
      stock.pre4, stock.pre5, stock.lecture, stock.laboratory, stock.coreq, stock.grade, stock.equivalent, stock.subjectid, stock.semid, '' as bgcolor, '' as errcolor, t.term ";
    return $sqlselect;
  }

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
     FROM " . $this->stock . "  as stock left join en_subject as s on s.trno=stock.subjectid
     left join en_term as t on t.line = stock.semid
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
      from en_glhead AS h left join en_glsubject as s on s.trno=h.trno left join en_term as t on t.line=s.semid
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

  public function additem($action, $config)
  {
    $trno =  $config['params']['data']['trno'];
    $subjectid =  $config['params']['data']['subjectid'];
    $units = $config['params']['data']['units'];
    $lecture =  $config['params']['data']['lecture'];
    $laboratory = $config['params']['data']['laboratory'];
    $semid = $config['params']['data']['semid'];
    $yearnum = $config['params']['data']['yearnum'];
    // $grade = $config['params']['data']['grade'];
    // $equivalent = $config['params']['data']['equivalent'];
    $grade = $equivalent = 0;
    if (isset($config['params']['data']['grade'])) $grade = $config['params']['data']['grade'];
    if (isset($config['params']['data']['equivalent'])) $equivalent = $config['params']['data']['equivalent'];

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
      'semid' => $semid,
      'yearnum' => $yearnum,
      'grade' => $grade,
      'equivalent' => $equivalent
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    // $data['editdate'] = $current_timestamp;
    // $data['editby'] = $config['params']['user'];

    // if($bldgid != 0 && $roomid != 0 && $schedday != '' && $schedstarttime !='' && $schedendtime != '' && $instructorid != 0) {
    //   $schedcheck = $this->checksched($config, $data); 
    // } else {
    //   $schedcheck['status'] = true;
    //   $schedcheck['msg'] = '';
    // }

    if ($action == 'insert') {
      // $data['encodeddate'] = $current_timestamp;
      // $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'SUBJECT SCHEDULE', 'ADD - Line:' . $line . ' subjectid:' . $subjectid . ' Units:' . $units . ' Lecture:' . $lecture . ' laboratory:' . $laboratory . ' Temrs:' . $semid, ' Year:' . $yearnum);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add Schedule Successfully.'];
      } else {
        return ['status' => false, 'msg' => 'Add Schedule Failed'];
      }
    } elseif ($action == 'update') {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return ['status' => true, 'msg' => ''];
    }
    // if($schedcheck['status'] == true) {
    // } else {
    //   return ['status'=>false,'msg'=>$schedcheck['msg']];  
    // }
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

  public function getEquivalent($row)
  {
    $grade = $row['grade'];
    $equiv = $this->coreFunctions->opentable("select equivalent from en_gradeequivalent where " . $grade . " between range1 and range2 limit 1");
    if (!empty($equiv)) return $equiv[0]->equivalent;
    return 0;
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $equiv = $this->getEquivalent($config['params']['row']);
    $config['params']['data']['equivalent'] = $equiv;
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
      $equiv = $this->getEquivalent($value);
      $config['params']['data']['equivalent'] = $equiv;
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
  //     $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty, $params['params'])),'50px',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
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







} //end class
