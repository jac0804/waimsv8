<?php

namespace App\Http\Classes\modules\enrollment;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;

class ec
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CURRICULUM';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'en_cchead';
  public $hhead = 'en_glhead';
  public $stock = 'en_ccyear';
  public $hstock = 'en_glyear';
  // public $stock = 'en_ccsubject';
  // public $hstock = 'en_glsubject';
  public $detail = '';
  public $hdetail = '';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'IS1';

  private $fields = [
    'trno', 'docno', 'dateid', 'curriculumcode', 'curriculumname', 'courseid', 'coursename',
    'effectfromdate', 'effecttodate', 'levelid', 'syid', 'doc', 'ischinese'
  ];
  private $except = ['trno'];
  private $acctg = [];
  public $showfilteroption = false;
  public $showfilter = false;
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
      'view' => 940,
      'edit' => 939,
      'new' => 941,
      'save' => 942,
      'change' => 944,
      'delete' => 943,
      'print' => 791,
      'lock' => 947,
      'unlock' => 948,
      'post' => 945,
      'unpost' => 946,

      'additem' => 1318,
      'edititem' => 1319,
      'deleteitem' => 1320
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'coursecode', 'coursename', 'sy'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:100px;whiteSpace:normal;';
    $cols[2]['style'] = 'width:150px;whiteSpace:normal;';
    $cols[3]['style'] = 'width:100px;whiteSpace:normal;';
    $cols[4]['style'] = 'width:100px;whiteSpace:normal;';
    $cols[5]['style'] = 'width:100px;whiteSpace:normal;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $searchfilter = $config['params']['search'];
    $limit = "limit 50";
    $condition = "";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'c.coursecode', 'c.coursename', 'sy.sy'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }



    $qry = "select head.trno,head.docno, 'DRAFT' as status,
      c.coursecode, c.coursename, sy.sy, head.dateid
       from " . $this->head . " as head left join " . $this->tablenum . " as num 
       on num.trno=head.trno left join en_course as c on c.line=head.courseid
       left join en_schoolyear as sy on sy.line=head.syid
       where head.doc='EC' and num.center = ? and num.postdate is null " . $condition . " " . $filtersearch . "
       union all
       select head.trno,head.docno, 'POSTED' as status,
      c.coursecode, c.coursename, sy.sy, head.dateid
       from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
       on num.trno=head.trno left join en_course as c on c.line=head.courseid
       left join en_schoolyear as sy on sy.line=head.syid
       where head.doc='EC' and num.center = ?  and num.postdate is not null " . $condition . " " . $filtersearch . "
       order by docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$center, $center]);
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'year', 'term', 'levelup'], 'headgridbtns' => ['viewcurriculum', 'viewbooks']]];

    $stockbuttons = ['ccsubject', 'save', 'delete', 'books', 'duplicate'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'YEAR';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:60px;whiteSpace:normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:150px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][2]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][2]['action'] = "lookupsemester";
    $obj[0][$this->gridname]['columns'][2]['lookupclass'] = "lookupsemestergrid";
    $obj[0][$this->gridname]['columns'][2]['label'] = "Semester";
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:100px;whiteSpace:normal;';

    $obj[0][$this->gridname]['columns'][3]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][3]['action'] = "lookupcourse";
    $obj[0][$this->gridname]['columns'][3]['lookupclass'] = "lookuplevelupgrid";
    $obj[0][$this->gridname]['columns'][3]['label'] = "Levelup";
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][3]['align'] = 'text-left';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['duplicatecc', 'addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = 'Add';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'course', 'coursename', 'dlevel'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'course.name', 'coursecode');
    data_set($col1, 'dlevel.class', 'csdlevel sbccsreadonly');

    $fields = ['dateid', 'curriculumcode', 'curriculumname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'curriculumcode.type', 'cinput');
    data_set($col2, 'curriculumname.type', 'cinput');

    $fields = ['sy', 'effectfromdate', 'effecttodate', 'ischinese'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['sumunits'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'sumunits.type', 'ctextarea');
    data_set($col4, 'sumunits.class', 'sbccsreadonly');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['curriculumcode'] = '';
    $data[0]['curriculumname'] = '';
    $data[0]['coursecode'] = '';
    $data[0]['coursename'] = '';
    $data[0]['courseid'] = 0;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['effectfromdate'] = $this->othersClass->getCurrentDate();
    $data[0]['effecttodate'] = $this->othersClass->getCurrentDate();
    $data[0]['dlevel'] = '';
    $data[0]['levelid'] = 0;
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'issy=1');
    $data[0]['syid'] =  $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'issy=1');
    $data[0]['doc'] = '';
    $data[0]['curriculumdocno'] = '';
    $data[0]['sumunits'] = '';
    $data[0]['ischinese'] = '0';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc='TR' and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select 
            head.trno, 
            head.docno, 
            head.dateid,
            head.curriculumcode, 
            head.curriculumname, 
            c.coursecode, 
            head.courseid,
            c.coursename, 
            head.effectfromdate, 
            head.effecttodate, 
            head.level,
            head.levelid,
            l.levels,
            l.levels as dlevel, 
            head.sy, 
            head.terms, 
            sy.line as syid, 
            sy.sy, 
            head.semid,
            sem.term as terms, 
            head.doc, 
            head.yr, 
            head.curriculumdocno,
            head.ischinese,
            '' as sumunits";

    $qry = $qryselect . " from " . $table . " as head
          left join " . $tablenum . " as num on num.trno = head.trno
          left join en_course as c on c.line=head.courseid
          left join en_levels as l on l.line=head.levelid
          left join en_schoolyear as sy on sy.line=head.syid
          left join en_term as sem on sem.line=head.semid
          where head.trno = ? and num.doc='EC' and num.center=? 
          union all " . $qryselect . " from " . $htable . " as head
          left join " . $tablenum . " as num on num.trno = head.trno
          left join en_course as c on c.line=head.courseid
          left join en_levels as l on l.line=head.levelid
          left join en_schoolyear as sy on sy.line=head.syid
          left join en_term as sem on sem.line=head.semid
          where head.trno = ? and num.doc='EC' and num.center=?";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $head[0]->sumunits = $this->getsumunits($trno);
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
      // $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  private function getsumunits($trno)
  {
    return $this->coreFunctions->datareader("select group_concat(trim(stat) SEPARATOR '\n') as value from (
      select concat(s.yearnum,' - ',term.term,': ',cast(sum(s.units) as unsigned)) as stat from en_ccsubject  as s left join en_term as term on term.line=s.semid where s.trno=? group by s.yearnum, term.term
      union all
      select concat(s.yearnum,' - ',term.term,': ',cast(sum(s.units) as unsigned)) as stat from en_glsubject  as s left join en_term as term on term.line=s.semid  where s.trno=? group by s.yearnum, term.term
      ) as x", [$trno, $trno]);
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
    if ($data['effectfromdate'] == '') $data['effectfromdate'] = null;
    if ($data['effecttodate'] == '') $data['effecttodate'] = null;

    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['coursecode'] . ' - ' . $head['coursename']);
    }
  } // end function  

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect . " from " . $this->stock . " as s left join en_term as sem on sem.line = s.semid where s.trno=?
    union all
    " . $sqlselect . " from " . $this->hstock . " as s left join en_term as sem on sem.line = s.semid
    where s.trno=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function    

  private function getstockselect()
  {
    return "select s.trno, s.line, s.year, s.semid, sem.term, s.levelup, '' as bgcolor, '' as errcolor";
  }

  public function addrow($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['trno'] = $config['params']['trno'];
    $data['year'] = '';
    $data['semid'] = 0;
    $data['term'] = '';
    $data['levelup'] = '';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'getsubject':
        return $this->getsubject($config);
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
      case 'duplicate':
        return $this->duplicate($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'generatecurriculum':
        return $this->generatecurriculum($config);
        break;
    }
  }

  public function generatecurriculum($config)
  {
    $trno = $config['params']['trno'];
    $cctrno = $config['params']['rows'][0]['curriculumtrno'];

    $qry = "insert into en_ccyear (trno,line,year,semid) select " . $trno . ",line,year,semid from en_glyear where trno=?";
    $this->coreFunctions->execqry($qry, 'insert', [$cctrno]);

    $qry = "insert into en_ccsubject (trno,line,units,laboratory,lecture,hours,subjectid,cline) 
    select " . $trno . ",line,units,laboratory,lecture,hours,subjectid,cline from en_glsubject where trno=?";
    $this->coreFunctions->execqry($qry, 'insert', [$cctrno]);

    $qry = "insert into en_ccbooks (trno,line,itemid,semid,isqty,isamt,amt,ext,disc,uom,cline) 
    select " . $trno . ",line,itemid,semid,isqty,isamt,amt,ext,disc,uom,cline from en_glbooks where trno=?";
    $this->coreFunctions->execqry($qry, 'insert', [$cctrno]);

    $data = $this->openstock($trno, $config);

    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }

  public function getsubject($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select yr, semid, curriculumcode, courseid  from en_CCHead where trno=?";
    $headdetail = $this->coreFunctions->opentable($qry, [$trno]);

    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['trno'] = $trno;
      $config['params']['data']['subjectid'] = $value['trno'];
      $config['params']['data']['subjectname'] = $value['subjectname'];
      $config['params']['data']['units'] = $value['units'];
      $config['params']['data']['lecture'] = $value['lecture'];
      $config['params']['data']['laboratory'] = $value['laboratory'];
      $config['params']['data']['hours'] = $value['hours'];
      $config['params']['data']['yearnum'] = $headdetail[0]->yr;
      $config['params']['data']['semid'] = $headdetail[0]->semid;
      $config['params']['data']['curriculumcode'] = $headdetail[0]->curriculumcode;
      $config['params']['data']['courseid'] = $headdetail[0]->courseid;
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

    $coreq = isset($config['params']['data']['coreqid']) ? $config['params']['data']['coreqid'] : 0;
    $pre1 = isset($config['params']['data']['pre1id']) ? $config['params']['data']['pre1id'] : 0;
    $pre2 = isset($config['params']['data']['pre2id']) ? $config['params']['data']['pre2id'] : 0;
    $pre3 = isset($config['params']['data']['pre3id']) ? $config['params']['data']['pre3id'] : 0;
    $pre4 = isset($config['params']['data']['pre4id']) ? $config['params']['data']['pre4id'] : 0;
    $pre5 = isset($config['params']['data']['pre5id']) ? $config['params']['data']['pre5id'] : 0;

    $data = [
      'trno' => $config['params']['trno'],
      'line' => $line,
      'subjectid' => $config['params']['data']['subjectid'],
      'subjectname' => $config['params']['data']['subjectname'],
      'units' => $config['params']['data']['units'],
      'lecture' => $config['params']['data']['lecture'],
      'laboratory' => $config['params']['data']['laboratory'],
      'hours' => $config['params']['data']['hours'],
      'yearnum' => $config['params']['data']['yearnum'],
      'semid' => $config['params']['data']['semid'],
      'coreqid' => $coreq,
      'pre1id' => $pre1,
      'pre2id' => $pre2,
      'pre3id' => $pre3,
      'pre4id' => $pre4,
      'pre5id' => $pre5,
      'curriculumcode' => $config['params']['data']['curriculumcode'],
      'courseid' => $config['params']['data']['courseid'],
    ];

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item failed'];
      }
    } else if ($action == 'update') {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
    }

    return true;
  }

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from en_ccbooks where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from en_ccsubject where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function deleteitem($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $year = $config['params']['row']['year'];
    $term = $config['params']['row']['term'];
    $levelup = $config['params']['row']['levelup'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $qry2 = "delete from en_ccsubject where trno=? and cline=?";
    $qry3 = "delete from en_ccbooks where trno=? and cline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry($qry2, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry($qry3, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'YEAR', 'REMOVED - Line: ' . $line . ' Year: ' . $year . ' Semester: ' . $term . ' Levelup: ' . $levelup);
    return ['status' => true, 'msg' => 'Delete subject successfully.'];
  }

  public function duplicate($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $qry = "select trno,line,year,semid from " . $this->stock . " where trno=? and line=?";
    $datayear = $this->coreFunctions->opentable($qry, [$trno, $line]);

    foreach ($datayear as $key => $value) {
      $trno = $datayear[$key]->trno;
      $year = $datayear[$key]->year;
      $yline = $datayear[$key]->line;
      $semid = 0;
      $line = $this->coreFunctions->datareader("select line + 1  as value from " . $this->stock . " where trno=? order by line desc limit 1", [$trno]);

      $qry = "insert into " . $this->stock . " (trno,line,year,semid) values (?,?,?,0) ";
      $this->coreFunctions->execqry($qry, 'delete', [$trno, $line, $year]);

      $qry = "select trno,line,units,lecture,laboratory,hours,subjectid,cline from en_ccsubject where trno=? and cline=?;";
      $datachk = $this->coreFunctions->opentable($qry, [$trno, $yline]);

      foreach ($datachk as $key => $value) {
        if (!empty($datachk[$key]->trno)) {
          $sline = $datachk[$key]->line;
          $units = $datachk[$key]->units;
          $lecture = $datachk[$key]->lecture;
          $laboratory = $datachk[$key]->laboratory;
          $hours = $datachk[$key]->hours;
          $subjectid = $datachk[$key]->subjectid;

          $qry = "insert into en_ccsubject (trno,line,units,lecture,laboratory,hours,subjectid,cline) values (?,?,?,?,?,?,?,?) ";
          $this->coreFunctions->execqry($qry, 'insert', [$trno, $sline, $units, $lecture, $laboratory, $hours, $subjectid, $line]);
        }
      }

      $qry = "select trno,line,cline,itemid,semid,isqty,isamt,amt,ext,disc,uom from en_glbooks where trno=? and cline=?;";
      $databooks = $this->coreFunctions->opentable($qry, [$trno, $yline]);

      foreach ($databooks as $key => $value) {
        if (!empty($databooks[$key]->trno)) {
          $sline = $databooks[$key]->line;
          $itemid = $databooks[$key]->itemid;
          $semid = $databooks[$key]->semid;
          $isqty = $databooks[$key]->isqty;
          $isamt = $databooks[$key]->isamt;
          $amt = $databooks[$key]->amt;
          $ext = $databooks[$key]->ext;
          $disc = $databooks[$key]->disc;
          $uom = $databooks[$key]->uom;

          $qry = "insert into en_ccbooks (trno,line,itemid,semid,isqty,isamt,amt,ext,disc,uom,cline) values (?,?,?,?,?,?,?,?,?,?,?) ";
          $this->coreFunctions->execqry($qry, 'insert', [$trno, $sline, $itemid, $semid, $isqty, $isamt, $amt, $ext, $disc, $uom, $line]);
        }
      }
    }

    $data = $this->openstock($config['params']['trno'], $config);
    return ['griddata' => ['inventory' => $data], 'status' => true, 'msg' => 'Duplicate successful'];
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from en_ccsubject where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from en_ccbooks where trno=?", 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL YEAR/GRADE');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function addyear($action, $config)
  {
    $trno = $config['params']['trno'];
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line += 1;
    } else {
      $line = $config['params']['data']['line'];
    }
    $config['params']['line'] = $line;
    $data = ['trno' => $trno, 'line' => $line, 'year' => $config['params']['data']['year'], 'semid' => $config['params']['data']['semid']];
    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item failed'];
      }
    } else {
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
    }
    return true;
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
      $trno = $config['params']['data']['trno'];
    }
    $data = [
      'trno' => $config['params']['data']['trno'],
      'line' => $config['params']['data']['line'],
      'year' => $config['params']['data']['year'],
      'levelup' => $config['params']['data']['levelup'],
      'semid' => $config['params']['data']['semid']
    ];

    if ($data['line'] == 0) {
      $check = $this->coreFunctions->opentable("select * from en_ccyear where trno=? and year=? and semid=?", [$config['params']['data']['trno'], $data['year'], $data['semid']]);
      if (!empty($check)) {
        return ['status' => false, 'msg' => 'Duplicate record. Please try again.'];
      }
      $line = $this->coreFunctions->datareader("select line as value from " . $this->stock . " where trno=? order by line desc limit 1", [$config['params']['data']['trno']]);
      if ($line == '') $line = 0;
      $line += 1;
      $data['line'] = $line;

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($config['params']['data']['trno'], $config, 'YEAR', 'ADD - Line:' . $line . ' Year:' . $data['year'] . ' Sem ID:' . $data['semid']);
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
      $check = $this->coreFunctions->opentable("select year, semid from en_ccyear where trno=? and line<>? and year=? and semid=?", [$data['trno'], $data['line'], $data['year'], $data['semid']]);
      if (!empty($check)) {
        return ['status' => false, 'msg' => 'Duplicate record. Please try again.'];
      }
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $data['trno'], 'line' => $data['line']]);
      if ($type == '') {
        $row = $this->openstockline($config);
        return ['status' => true, 'row' => $row, 'msg' => 'Update item successfully.'];
      } else {
        return true;
      }
    }
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    // $isupdate = $this->addsubject('update', $config);
    $isupdate = $this->addyear('update', $config);
    $data = $this->openstockline($config);
    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => 'Update failed'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->addsubject('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function  

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect();
    $qry = $sqlselect . " from " . $this->stock . " as s left join en_term as sem on sem.line = s.semid where s.trno=? and s.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['line']]);
    return $stock;
  } // end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $msg = '';
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

    if ($this->coreFunctions->execqry("insert into " . $this->hhead . "(trno, dateid, docno, curriculumcode, curriculumname, courseid, effectfromdate, effecttodate, levelid, syid, semid, doc, yr, curriculumdocno, createby, editby, editdate, createdate, lockdate, ischinese )
    select h.trno, h.dateid, h.docno, h.curriculumcode, h.curriculumname, h.courseid, h.effectfromdate, h.effecttodate, h.levelid, h.syid, h.semid, h.doc, h.yr, h.curriculumdocno, h.createby, h.editby, h.editdate, h.createdate, h.lockdate, h.ischinese
    from " . $this->head . " as h where h.trno=?", 'insert', [$trno]) > 0) {
      if ($this->coreFunctions->execqry("insert into " . $this->hstock . "(trno, line, year, semid, levelup) select trno, line, year, semid, levelup from " . $this->stock . " where trno=?", 'insert', [$trno]) > 0) {
        if ($this->coreFunctions->execqry("insert into en_glsubject(trno, line, curriculumcode, yearnum, semid, subjectid, units, courseid, pre1id, pre2id, pre3id,
          pre4id, pre5id, lecture, laboratory, coreqid, hours, cline) select trno, line, curriculumcode, yearnum, semid, subjectid, units, courseid, pre1id, pre2id, pre3id,
          pre4id, pre5id, lecture, laboratory, coreqid, hours, cline from en_ccsubject where trno=?", 'insert', [$trno]) > 0) {
          if ($this->coreFunctions->execqry("insert into en_glbooks(trno,line,cline,itemid,semid,isqty,isamt,amt,ext,disc,uom) 
            select trno,line,cline,itemid,semid,isqty,isamt,amt,ext,disc,uom from en_ccbooks where trno=?", 'insert', [$trno]) > 0) {
            $msg = '';
          } else {
            $msg = "Posting Failed, please check books detail";
          }
        } else {
          $msg = "Posting Failed, please check subject detail";
        }
      } else {
        $msg = "Posting Failed, please check year detail";
      }
    } else {
      $msg = "Posting Failed, pls check head";
    }
    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_ccsubject where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_ccbooks where trno=?", 'delete', [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_glsubject where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_glbooks where trno=?", 'delete', [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $msg = '';

    // $msg = $this->transwithreference($config, 'en_scurriculum', 'trno');
    // if ($msg !== '') return ['trno' => $trno, 'status' => false, 'msg' => $msg];

    $docno = $this->coreFunctions->datareader("select docno as value from " . $this->tablenum . " where trno=?", [$trno]);
    if ($this->coreFunctions->execqry("insert into " . $this->head . " (trno, dateid, docno, curriculumcode, curriculumname, courseid, effectfromdate, effecttodate, levelid, syid, semid, doc, yr, curriculumdocno, createby, editby, editdate, createdate, lockdate, ischinese )
    select h.trno, h.dateid, h.docno, h.curriculumcode, h.curriculumname, h.courseid, h.effectfromdate, h.effecttodate, h.levelid, h.syid, h.semid, h.doc, h.yr, h.curriculumdocno, h.createby, h.editby, h.editdate, h.createdate, h.lockdate, h.ischinese 
    from " . $this->hhead . " as h where h.trno=?", 'insert', [$trno])) {
      if ($this->coreFunctions->execqry("insert into " . $this->stock . "(trno, line, year, semid, levelup) select trno, line, year, semid, levelup from " . $this->hstock . " where trno=?", 'insert', [$trno]) > 0) {
        if ($this->coreFunctions->execqry("insert into en_ccsubject(trno, line, curriculumcode, yearnum, semid, subjectid, units, courseid, pre1id, pre2id, pre3id,
          pre4id, pre5id, lecture, laboratory, coreqid, hours, cline) select trno, line, curriculumcode, yearnum, semid, subjectid, units, courseid, pre1id, pre2id, pre3id,
          pre4id, pre5id, lecture, laboratory, coreqid, hours, cline from en_glsubject where trno=?", 'insert', [$trno]) > 0) {
          if ($this->coreFunctions->execqry("insert into en_ccbooks(trno,line,cline,itemid,semid,isqty,isamt,amt,ext,disc,uom) 
            select trno,line,cline,itemid,semid,isqty,isamt,amt,ext,disc,uom from en_glbooks where trno=?", 'insert', [$trno]) > 0) {
            $msg = '';
          } else {
            $msg = "Unposting Failed, please check books detail";
          }
        } else {
          $msg = "Unposting Failed, please check subject detail";
        }
      } else {
        $msg = "Unposting Failed, please check year detail";
      }
    } else {
      $msg = "Unposting Failed, please check head";
    }
    if ($msg === '') {
      $docno = $this->coreFunctions->getfieldvalue($this->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_glsubject where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_glbooks where trno=?", 'delete', [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_ccsubject where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry("delete from en_ccbooks where trno=?", 'delete', [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  private function transwithreference($config, $table, $field)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue($table, 'trno', $field . '=? ', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, Already have a reference';
    } else {
      return '';
    }
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
    $books = app($this->companysetup->getreportpath($config['params']))->books_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data, $books);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  
  // public function reportsetup($config){
  //   $txtfield = $this->createreportfilter();
  //   $txtdata = $this->reportparamsdata($config);
  //   $modulename = $this->modulename;
  //   $data = [];
  //   $style = 'width:500px;max-width:500px;';
  //   return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  // }


  // public function createreportfilter(){
  //   $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
  //   $col1 = $this->fieldClass->create($fields);
  //   return array('col1' => $col1);
  // }


  // public function reportparamsdata($config){
  //   return $this->coreFunctions->opentable(
  //     "select 
  //     'default' as print,
  //     '' as prepared,
  //     '' as approved,
  //     '' as received
  //     "
  //   );
  // }

  // private function report_default_query($trno){

  //   $query = "
  //   select head.docno, head.client, head.clientname, head.terms,
  //   head.address, head.dateid, head.wh, head.rem,
  //   stock.barcode, stock.itemname, stock.uom, stock.wh as stockwh, stock.barcode,
  //   stock.rrqty, stock.qty, stock.qa, stock.reqqty, (stock.reqqty - stock.rrqty) as pending,
  //   stock.rem as remarks
  //   from htrhead as head
  //   left join htrstock as stock on head.trno = stock.trno
  //   where head.trno = '$trno'
  //   union all
  //   select head.docno, head.client, head.clientname, head.terms,
  //   head.address, head.dateid, head.wh, head.rem,
  //   stock.barcode, stock.itemname, stock.uom, stock.wh as stockwh, stock.barcode,
  //   stock.rrqty, stock.qty, stock.qa, stock.reqqty, (stock.reqqty - stock.rrqty) as pending,
  //   stock.rem as remarks
  //   from trhead as head
  //   left join trstock as stock on head.trno = stock.trno
  //   where head.trno = '$trno'";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // } //end fn

  // public function reportdata($config){
  //   $data = $this->report_default_query($config['params']['dataid']);
  //   $str = $this->reportplotting($config, $data);
  //   return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  // }

  // public function reportplotting($params, $data){

  //   $companyid = $params['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $params['params']);

  //   $center = $params['params']['center'];
  //   $username = $params['params']['user'];

  //   $str = '';
  //   $count = 35;
  //   $page = 35;
  //   $str .= $this->reporter->beginreport();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->letterhead($center, $username);
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br><br>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('STOCK REQUEST APPROVAL', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
  //   $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
  //   $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('DEPARTMENT : ', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
  //   $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
  //   $str .= $this->reporter->col('DATE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
  //   $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('ADDRESS : ', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
  //   $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
  //   $str .= $this->reporter->col('TERMS : ', '70', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
  //   $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
  //   $str .= $this->reporter->pagenumber('Page');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->printline();
  //   //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('BARCODE', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('UNIT', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('REQUEST QTY', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('APPROVED QTY', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('WAREHOUSE', '100px', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '30px', '8px');

  //   for ($i = 0; $i < count($data); $i++) {
  //     $str .= $this->reporter->startrow();
  //     $str .= $this->reporter->addline();
  //     $str .= $this->reporter->col($data[$i]['barcode'], '100px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
  //     $str .= $this->reporter->col($data[$i]['itemname'], '400px', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
  //     $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
  //     $str .= $this->reporter->col($data[$i]['reqqty'], '50px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
  //     $str .= $this->reporter->col($data[$i]['rrqty'], '50px', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
  //     $str .= $this->reporter->col($data[$i]['stockwh'], '100px', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');

  //     if ($this->reporter->linecounter == $page) {
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->page_break();

  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->letterhead($center, $username);
  //       $str .= $this->reporter->endtable();
  //       $str .= '<br><br>';

  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col('STOCK REQUEST APPROVAL', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
  //       $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
  //       $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col('DEPARTMENT : ', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
  //       $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
  //       $str .= $this->reporter->col('DATE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
  //       $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col('ADDRESS : ', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
  //       $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
  //       $str .= $this->reporter->col('TERMS : ', '70', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
  //       $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();

  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
  //       $str .= $this->reporter->pagenumber('Page');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->endtable();

  //       $str .= $this->reporter->printline();
  //       $str .= $this->reporter->begintable('800');
  //       $str .= $this->reporter->startrow();
  //       $str .= $this->reporter->col('BARCODE', '100px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //       $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
  //       $str .= $this->reporter->col('UNIT', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //       $str .= $this->reporter->col('REQUEST QTY', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //       $str .= $this->reporter->col('APPROVED QTY', '50px', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //       $str .= $this->reporter->col('WAREHOUSE', '100px', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
  //       $str .= $this->reporter->endrow();
  //       $str .= $this->reporter->printline();
  //       $page = $page + $count;
  //     }
  //   }

  //   $str .= $this->reporter->startrow();

  //   $str .= $this->reporter->col('', '50px', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('', '50px', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('', '400px', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('', '100px', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col(' ', '125px', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('', '50px', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->col('', '125px', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '8px');
  //   $str .= $this->reporter->endrow();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->printline();
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('NOTE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
  //   $str .= $this->reporter->col($data[0]['rem'], '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
  //   $str .= $this->reporter->col('', '160', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');

  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/><br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
  //   $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
  //   $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= '<br>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
  //   $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
  //   $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->endreport();

  //   return $str;
  // } //end fn


}
