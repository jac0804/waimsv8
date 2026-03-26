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

class hd
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'NOTICE OF DISCIPLINARY ACTION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'disciplinary';
  public $hhead = 'hdisciplinary';
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
    'artid',
    'sectionno',
    'violationno',
    'startdate',
    'enddate',
    'amt',
    'detail',
    'jobtitle',
    'penalty',
    'numdays',
    'refx',
    'deptid',
    'prepared',
    'supervisor',
    'notedby1',
    'notedby2',
    'notedby3',
    'notedby4',
    'position1',
    'position2',
    'position3',
    'position4',
    'isuspended',
    'findings',
    'explanation'
  ];
  private $except = ['trno'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  public $sbcscript;


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
      'view' => 1199,
      'edit' => 1200,
      'new' => 1201,
      'save' => 1202,
      'change' => 1203,
      'delete' => 1204,
      'print' => 1205,
      'post' => 1206,
      'unpost' => 1207,
      'lock' => 1707,
      'unlock' => 1708
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
    where h.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, c.client as empcode, c.clientname as empname, 'POSTED' as status
    from " . $this->hhead . " as h left join client as c on c.clientid=h.empid left join " . $this->tablenum . " as num on num.trno=h.trno
    where h.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton 

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];
    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysuspension', 'label' => 'SUSPENSION DETAILS', 'access' => 'view']];
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
    $companyid = $config['params']['companyid'];

    if ($companyid == 58) { //cdo
      $fields = [['docno', 'dateid'], 'irno', 'artcode', 'articlename', 'sectioncode', 'sectionname'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'sectionname.type', 'ctextarea');

      $fields = ['empcode', 'empname', 'jobtitle', 'dept', 'violationno', 'penalty', 'numdays'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'empcode.type', 'input');
      data_set($col2, 'dept.type', 'input');
      data_set($col2, 'dept.class', 'csdept sbccsreadonly');
      data_set($col2, 'jobtitle.class', 'csjobtitle sbccsreadonly');

      $fields = ['start', 'end', 'amt', 'detail', 'findings'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'start.name', 'startdate');
      data_set($col3, 'end.name', 'enddate');
      data_set($col3, 'amt.label', 'Amount');
      data_set($col3, 'amt.readonly', false);
      data_set($col3, 'amt.type', 'cinput');
      data_set($col3, 'detail.type', 'cinput');

      $fields = ['explanation', 'isuspended'];
      $col4 = $this->fieldClass->create($fields);
      data_set($col4, 'explanation.type', 'textarea');
    } else {
      $fields = ['docno', 'empcode', 'empname', 'jobtitle', 'dept', 'dateid'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'empcode.action', 'lookupemployee');
      data_set($col1, 'dept.type', 'input');
      data_set($col1, 'dept.class', 'csdept sbccsreadonly');
      data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');

      $fields = ['irno', 'irdesc', 'artcode', 'articlename', 'sectioncode', 'sectionname'];
      $col2 = $this->fieldClass->create($fields);

      $fields = ['violationno', 'penalty', 'numdays', 'start', 'end', 'amt', 'detail'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'start.name', 'startdate');
      data_set($col3, 'end.name', 'enddate');
      data_set($col3, 'amt.label', 'Amount');
      data_set($col3, 'amt.readonly', false);

      data_set($col3, 'amt.type', 'cinput');
      data_set($col3, 'detail.type', 'cinput');

      switch ($companyid) {
        case 3: // conti
          data_set($col3, 'penalty.class', 'cspenalty');
          data_set($col3, 'numdays.class', 'csnumdays');

          $fields = ['prepared', 'supervisor', 'notedby1', 'position1', 'notedby2', 'position2', 'notedby3', 'position3', 'notedby4', 'position4'];
          $col4 = $this->fieldClass->create($fields);
          data_set($col4, 'prepared.class', 'csprepared');
          data_set($col4, 'supervisor.class', 'cssupervisor');
          break;
        default:
          $fields = [];
          $col4 = $this->fieldClass->create($fields);
          break;
      }
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    return $this->resetdata($docno);
  }

  public function resetdata($docno = '')
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['empid'] = 0;
    $data[0]['empcode'] = '';
    $data[0]['empname'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['deptid'] = 0;
    $data[0]['startdate'] = $this->othersClass->getCurrentDate();
    $data[0]['enddate'] = $this->othersClass->getCurrentDate();
    $data[0]['jobtitle'] = '';
    $data[0]['refx'] = 0;
    $data[0]['irdesc'] = '';
    $data[0]['artid'] = 0;
    $data[0]['articlename'] = '';
    $data[0]['artcode'] = '';
    $data[0]['sectionno'] = 0;
    $data[0]['sectionname'] = '';
    $data[0]['sectioncode'] = '';
    $data[0]['violationno'] = 0;
    $data[0]['penalty'] = '';
    $data[0]['numdays'] = 0;
    $data[0]['start'] = '';
    $data[0]['end'] = '';
    $data[0]['amt'] = '';
    $data[0]['detail'] = '';
    $data[0]['dept'] = '';

    $data[0]['prepared'] = '';
    $data[0]['supervisor'] = '';
    $data[0]['notedby1'] = '';
    $data[0]['position1'] = '';
    $data[0]['notedby2'] = '';
    $data[0]['position2'] = '';
    $data[0]['notedby3'] = '';
    $data[0]['position3'] = '';
    $data[0]['notedby4'] = '';
    $data[0]['position4'] = '';
    $data[0]['isuspended'] = '0';

    $data[0]['findings'] = '';
    $data[0]['explanation'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $this->othersClass->val($config['params']['trno']);
    $center = $config['params']['center'];

    if ($trno == 0) $trno = $this->getlasttrno();
    $config['params']['trno'] = $trno;

    $center = $config['params']['center'];

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $qryselect = "select 
      head.trno, head.docno, head.empid, head.dateid, 
      head.artid, head.sectionno, head.violationno,
      head.startdate, head.enddate, head.amt, 
      head.detail, emp.clientname as empname,
      head.jobtitle,
      chead.description as articlename, 
      cdetail.description as sectionname,
      head.penalty, head.numdays,
      head.refx,
      emp.client as empcode,
      dept.client as dept,
      head.deptid,
      ir.docno as irno,
      ir.idescription as irdesc,
      chead.code as artcode,
      cdetail.section as sectioncode,
      head.prepared,
      head.supervisor,
      head.notedby1,
      head.position1,
      head.notedby2,
      head.position2,
      head.notedby3,
      head.position3,
      head.notedby4,
      head.position4,
      (case when head.isuspended = 1 then '1' else '0' end) as isuspended,head.findings,head.explanation
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join client as emp on emp.clientid=head.empid
    left join client as dept on dept.clientid=head.deptid
    left join hincidenthead as ir on head.refx=ir.trno
    left join codehead as chead on chead.artid=head.artid
    left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
    left join $tablenum as num on num.trno = head.trno
    where num.trno = ? and num.doc='HD' and num.center=? 
    union all
    " . $qryselect . " from " . $htable . " as head
      left join client as emp on emp.clientid=head.empid
      left join client as dept on dept.clientid=head.deptid
      left join hincidenthead as ir on head.refx=ir.trno
      left join codehead as chead on chead.artid=head.artid
      left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
      left join $tablenum as num on num.trno = head.trno
      where num.trno = ? and num.doc='HD' and num.center=? 
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

  public function getlasttrno()
  {
    $last_id = $this->coreFunctions->datareader("
        select trno as value 
        from " . $this->head . " 
        union all
        select trno as value 
        from " . $this->hhead . " 
        order by value DESC LIMIT 1");

    return $last_id;
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
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $data['penalty'] = $head['penalty'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
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

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into hdisciplinary (trno, docno, empid, dateid, artid, sectionno,
      violationno, startdate, enddate, amt, detail,
      empname, jobtitle, department, articlename,
      sectionname, penalty, numdays, posteddate, postedby,
      createdby, irdesc, createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby, deptid, doc, refx,
      prepared, supervisor, notedby1, position1, notedby2, position2,
      notedby3,position3,notedby4,position4,isuspended,findings,explanation
      )
      select trno, docno, empid, dateid, artid, sectionno,
      violationno, startdate, enddate, amt, detail,
      empname, jobtitle, department, articlename,
      sectionname, penalty, numdays, posteddate, postedby,
      createdby, irdesc, createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby, deptid, doc, refx,
      prepared, supervisor, notedby1, position1, notedby2, position2,
      notedby3,position3,notedby4,position4,isuspended,findings,explanation
      from disciplinary where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {
      // $query = "select startdate,enddate,empid from $this->hhead where trno = $trno";

      $query = "select startdate,enddate,empid from datesuspension where trno = $trno";
      $data = $this->coreFunctions->opentable($query);
      if (!empty($data)) {
        foreach ($data as $key => $value) {
          $qry = "update timecard set isuspended = 1 where empid= " . $data[0]->empid . " and date(dateid) between date('" . $value->startdate . "') and  date('" . $value->enddate . "')";
          $this->coreFunctions->execqry($qry, 'update');
        }
      }
      

        $explanation = $this->coreFunctions->datareader("select explanation as value from disciplinary where trno=?", [$trno]);
        if($explanation !=''){
           $this->coreFunctions->execqry("delete from pendingapp where doc='HD' and trno=" . $trno , 'delete');
        }else{
           return ['status' => false, 'msg' => 'Posting failed. Explanation cannot be blank.'];
        }

    } else {
      $msg = "Posting failed. Kindly check the head data.";
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

    $qry = "insert into disciplinary (trno, docno, empid, dateid, artid, sectionno,
      violationno, startdate, enddate, amt, detail,
      empname, jobtitle, department, articlename,
      sectionname, penalty, numdays, posteddate, postedby,
      createdby, irdesc, createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby, deptid, doc, refx,
      prepared, supervisor, notedby1, position1, notedby2, position2,
      notedby3,position3,notedby4,position4,isuspended,findings,explanation)
      select trno, docno, empid, dateid, artid, sectionno,
      violationno, startdate, enddate, amt, detail,
      empname, jobtitle, department, articlename,
      sectionname, penalty, numdays, posteddate, postedby,
      createdby, irdesc, createby, createdate, editby,
      editdate, lockdate, lockuser, viewdate, viewby, deptid, doc, refx,
      prepared, supervisor, notedby1, position1, notedby2, position2,
      notedby3,position3,notedby4,position4,isuspended,findings,explanation
      from hdisciplinary where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {
      // $query = "select startdate,enddate,empid from $this->head where trno = $trno";
      $query = "select startdate,enddate,empid from datesuspension where trno = $trno";
      $data = $this->coreFunctions->opentable($query);
      if (!empty($data)) {
        foreach ($data as $key => $value) {
          $qry = "update timecard set isuspended = 0 where empid= " . $data[0]->empid . " and date(dateid) between date('" . $value->startdate . "') and  date('" . $value->enddate . "')";
          $update_sus = $this->coreFunctions->execqry($qry, 'update');
        }
      }
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
  public function sbcscript($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 58: //cdohris
        return $this->sbcscript->hd($config);
        break;
      default:
        return true;
        break;
    }
  }
}
