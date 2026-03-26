<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\sbcscript\sbcscript;

class hn
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'NOTICE TO EXPLAIN';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sbcscript;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'notice_explain';
  public $hhead = 'hnotice_explain';
  public $detail = '';
  public $hdetail = '';
  public $tablelogs = 'hrisnum_log';
  public $tablelogs_del = 'del_hrisnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'IS1';

  private $fields = [
    'trno',
    'docno',
    'empid',
    'dateid',
    'empname',
    'empjob',
    'fempid',
    'fjobtitle',
    'artid',
    'deptid',
    'line',
    'refx',
    'ddate',
    'hdatetime',
    'hplace',
    'explanation',
    'comments',
    'remarks',
    'htime',
    'iswithhearing',
    'violationno',
    'penalty',
    'numdays'
  ];
  private $except = ['trno'];
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
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1209,
      'edit' => 1210,
      'new' => 1211,
      'save' => 1212,
      'change' => 1213,
      'delete' => 1214,
      'print' => 1215,
      'post' => 1216,
      'unpost' => 1217,
      'lock' => 1705,
      'unlock' => 1706
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'empcode', 'empname'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $cols[2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[4]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[5]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[1]['align'] = 'text-left';
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
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['h.docno', 'c.clientname', 'c.client'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, c.client as empcode, c.clientname as empname, 'DRAFT' as status
    from " . $this->head . " as h left join client as c on c.clientid=h.empid left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, c.client as empcode, c.clientname as empname, 'POSTED' as status
    from " . $this->hhead . " as h left join client as c on c.clientid=h.empid left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
    order by docno desc";
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
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryhrisnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    if ($config['params']['companyid'] == 58) { //cdo
      $fields = ['docno', 'irno', 'empcode', 'empname', 'jobtitle', 'dept', 'dateid'];
    } else {
      $fields = ['docno', 'irno', 'irdesc', 'empcode', 'empname', 'jobtitle', 'dept', 'dateid'];
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'empcode.action', 'lookupemployee');
    data_set($col1, 'empcode.addedparams', ['refx']);
    data_set($col1, 'dept.type', 'input');
    data_set($col1, 'dept.label', 'Department');
    data_set($col1, 'dept.class', 'csdept sbccsreadonly');

    data_set($col1, 'jobtitle.name', 'empjob');
    data_set($col1, 'jobtitle.class', 'csempcode sbccsreadonly');

    $fields = ['fempcode', 'fempname', 'fjobtitle', 'artcode', 'articlename', 'sectioncode', 'sectionname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'fempcode.label', 'From Employee Code');
    data_set($col2, 'fempcode.lookupclass', 'fromemployee');
    data_set($col2, 'fempcode.action', 'lookupemployee');

    data_set($col2, 'fempcode.required', false);
    data_set($col2, 'fempname.required', false);
    data_set($col2, 'fjobtitle.required', false);

    data_set($col2, 'fempname.label', 'From Employee Name');
    data_set($col2, 'artcode.lookupclass', 'hnarticle');
    data_set($col2, 'sectioncode.lookupclass', 'hnsection');
    data_set($col2, 'sectionname.type', 'ctextarea');

    if ($config['params']['companyid'] == 58) { //cdo
      data_set($col2, 'sectioncode.addedparams', ['artcode', 'artid', 'empid']);
    }

    $fields = ['dateid', 'iswithhearing', 'start', 'htime', 'position', 'remarks'];
    if ($config['params']['companyid'] == 58) { //cdo
      $fields = [['dateid', 'iswithhearing'], 'start', 'htime', 'position', 'violationno', ['penalty', 'numdays'], 'remarks'];
    }
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dateid.name', 'ddate');
    data_set($col3, 'dateid.label', 'Deadline');

    data_set($col3, 'start.name', 'hdatetime');
    data_set($col3, 'start.label', 'Administrative Hearing Date');


    data_set($col3, 'position.name', 'hplace');
    data_set($col3, 'position.label', 'Place');
    data_set($col3, 'position.type', 'cinput');
    data_set($col3, 'position.class', 'csposition');

    data_set($col3, 'htime.required', false);
    data_set($col3, 'htime.class', 'cshtime sbccsreadonly');
    data_set($col3, 'htime.readonly', true);

    data_set($col3, 'start.required', false);
    data_set($col3, 'start.readonly', true);
    data_set($col3, 'start.class', 'csstart sbccsreadonly');

    data_set($col3, 'position.readonly', true);
    data_set($col3, 'position.class', 'csposition sbccsreadonly');

    if ($config['params']['companyid'] == 58) { //cdo
      data_set($col3, 'violationno.type', 'input');
      data_set($col3, 'violationno.class', 'csline sbccsreadonly');
    }

    data_set($col3, 'remarks.type', 'ctextarea');

    $fields = ['explanation'];

    if ($config['params']['companyid'] != 58) { //cdo
      array_push($fields, 'comments');
    } else {
      $fields = [];
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'explanation.type', 'ctextarea');

    if ($config['params']['companyid'] != 58) { //cdo
      data_set($col4, 'comments.label', 'Findings/Comments');
      data_set($col4, 'comments.type', 'ctextarea');
    }
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    return $this->resetdata($docno, $params);
  }

  public function resetdata($docno = '', $params = [])
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['empid'] = 0;
    $data[0]['fempid'] = 0;
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['empjob'] = '';
    $data[0]['fempcode'] = '';
    $data[0]['fempname'] = '';
    $data[0]['fjobtitle'] = '';
    $data[0]['artcode'] = '';
    $data[0]['line'] = 0;
    $data[0]['refx'] = 0;
    $data[0]['ddate'] = $this->othersClass->getCurrentDate();
    $data[0]['hdatetime'] = NULL;
    $data[0]['hplace'] = '';
    $data[0]['explanation'] = '';
    $data[0]['comments'] = '';
    $data[0]['article'] = '';
    $data[0]['section'] = '';
    $data[0]['remarks'] = '';
    $data[0]['htime'] = '00:00';
    $data[0]['articlename'] = '';
    $data[0]['sectioncode'] = '';
    $data[0]['sectionname'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['irno'] = '';
    $data[0]['irdesc'] = '';
    $data[0]['artid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['iswithhearing'] = '0';
    $data[0]['violationno'] = 0;
    $data[0]['penalty'] = '';
    $data[0]['numdays'] = 0;

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
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc='" . $doc . "' and center=? order by trno desc limit 1", [$doc, $center]);
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

    $qryselect = "select head.trno, head.docno, head.empid, head.dateid, head.artid,femp.client as fempcode,emp.clientname as empname,
                        head.empjob,chead.description as articlename,cdetail.description as sectionname,head.refx, head.hplace,
                        head.line, head.explanation,head.ddate, case when head.hdatetime is null then '00:00' else head.htime end as htime, head.comments,head.hdatetime, head.remarks,
                        emp.client as empcode,dept.clientname as dept,head.deptid,ir.docno as irno,ir.idescription as irdesc,
                        chead.code as artcode,cdetail.section as sectioncode,head.fempid,
                        (case when head.fempid = 0 then head.fempname else femp.clientname end) as fempname,
                        head.fjobtitle, (case when head.iswithhearing = 1 then '1' else '0' end) as iswithhearing,
                        head.violationno,head.penalty,head.numdays
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join client as emp on emp.clientid=head.empid
    left join client as dept on dept.clientid=head.deptid
    left join hincidenthead as ir on head.refx=ir.trno
    left join codehead as chead on chead.artid=head.artid
    left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
    left join client as femp on head.fempid=femp.clientid
    left join $tablenum as num on num.trno = head.trno
    where num.trno = ? and num.doc='" . $doc . "' and num.center=? 
    union all
    " . $qryselect . " from " . $htable . " as head
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join hincidenthead as ir on head.refx=ir.trno
      left join codehead as chead on chead.artid=head.artid
      left join codedetail as cdetail on head.line=cdetail.line and chead.artid=cdetail.artid
      left join client as femp on head.fempid=femp.clientid
      left join $tablenum as num on num.trno = head.trno
      where num.trno = ? and num.doc='" . $doc . "' and num.center=? 
    ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = [];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head = $this->resetdata();
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

    $data['fempname'] = $head['fempname'];
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();

    if ($data['iswithhearing'] == 0) {
      $data['htime'] = '';
      $data['hdatetime'] = NULL;
      $data['hplace'] = '';
    }

    $data['editby'] = $config['params']['user'];
    $data['penalty'] = $head['penalty'];
    if ($isupdate) {

      ///violationno
      $empidh = $head['empid'];
      $artidh = $head['artid'];
      $sectidh = $head['sectioncode'];
      $timesviolated = $this->coreFunctions->opentable("select count(nt.artid) as artid from notice_explain as nt
      left join codehead as code on code.artid=nt.artid
      left join codedetail as cdetail on nt.line=cdetail.line and code.artid=cdetail.artid
      where nt.empid=$empidh and nt.artid=? and cdetail.section=? ", [$artidh, $sectidh]);
      $datahere = json_decode(json_encode($timesviolated), true);
      $new = (int) $datahere[0]['artid'];

      if ($new != 0) {
        $data['violationno'] = $new + 1;
      } else {
        $data['violationno'] = 1;
      }
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];

      ///violationno
      $empidh = $head['empid'];
      $artidh = $head['artid'];
      $sectidh = $head['sectioncode'];
      $timesviolated = $this->coreFunctions->opentable("select count(nt.artid) as artid from notice_explain as nt
      left join codehead as code on code.artid=nt.artid
      left join codedetail as cdetail on nt.line=cdetail.line and code.artid=cdetail.artid
      where nt.empid=$empidh and nt.artid=? and cdetail.section=? ", [$artidh, $sectidh]);
      $datahere = json_decode(json_encode($timesviolated), true);
      $new = (int) $datahere[0]['artid'];

      if ($new != 0) {
        $data['violationno'] = $new + 1;
      } else {
        $data['violationno'] = 1;
      }
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
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

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function sbcscript($config)
  {
    return $this->sbcscript->hn($config);
  }

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into hnotice_explain (trno, docno, empid, dateid, empname, empjob, fempcode, fempname, fjobtitle, artcode, line, 
                  refx, ddate, hdatetime, hplace, explanation, comments, article, section, remarks, htime, createby, createdate, 
                  editby, editdate, lockdate, lockuser, viewdate, viewby, deptid, artid, doc, fempid,iswithhearing,violationno,penalty,numdays)
            select trno, docno, empid, dateid, empname, empjob, fempcode, fempname, fjobtitle, artcode, line, refx, ddate, 
                  hdatetime, hplace, explanation, comments, article, section, remarks, htime, createby, createdate, editby, 
                  editdate, lockdate, lockuser, viewdate, viewby, deptid, artid, doc, fempid,iswithhearing,violationno,penalty,numdays
            from notice_explain where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {
    } else {
      $msg = "Posting failed. Kindly check the head.";
    }

    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $msg = '';

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into notice_explain (trno, docno, empid, dateid, empname, empjob, fempcode, fempname, fjobtitle, artcode, 
                  line, refx, ddate, hdatetime, hplace, explanation, comments, article, section, remarks, htime, createby, 
                  createdate, editby, editdate, lockdate, lockuser, viewdate, viewby, deptid, artid, doc, fempid,iswithhearing,violationno,penalty,numdays)
      select trno, docno, empid, dateid, empname, empjob, fempcode, fempname, fjobtitle, artcode, line, refx, ddate, 
              hdatetime, hplace, explanation, comments, article, section, remarks, htime, createby, createdate, editby, 
              editdate, lockdate, lockuser, viewdate, viewby, deptid, artid, doc, fempid,iswithhearing,violationno,penalty,numdays
      from hnotice_explain where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {
    } else {
      $msg = "Unposting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function


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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
}
