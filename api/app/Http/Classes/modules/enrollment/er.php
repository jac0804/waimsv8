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

class er
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Student Registration';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $head = 'en_sjhead';
  public $stock = 'en_sjsubject';
  public $otherfees = 'en_sjotherfees';
  public $credentials = 'en_sjcredentials';
  public $detail = 'en_sjdetail';
  public $assessment = 'en_sjsummary';

  public $hhead = 'glhead';
  public $hstock = 'glsubject';
  public $hotherfees = 'glotherfees';
  public $hcredentials = 'glcredentials';
  public $hdetail = 'gldetail';
  public $hassessment = 'glsummary';
  public $defaultContra = 'AR1';
  public $units = 'units';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'periodid', 'syid', 'courseid', 'assessref', 'curriculumcode', 'curriculumdocno', 'rem', 'deptid', 'levelid', 'semid', 'yr', 'sectionid', 'contra', 'modeofpayment', 'sotrno', 'disc', 'ischinese'];
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
      'view' => 984,
      'edit' => 983,
      'new' => 985,
      'save' => 986,
      'change' => 988,
      'delete' => 987,
      'print' => 183,
      'acctg' => 183,
      'lock' => 991,
      'unlock' => 992,
      'post' => 989,
      'unpost' => 990,
      'additem' => 1318,
      'edititem' => 1319,
      'deleteitem' => 1320
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listclientname', 'listdate', 'listsy', 'listperiod', 'coursename', 'section'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:85px;whiteSpace:normal;min-width:85px;';
    $cols[2]['style'] = 'width:130px;whiteSpace:normal;min-width:130px;';
    $cols[3]['style'] = 'width:200px;whiteSpace:normal;min-width:200px;';
    $cols[4]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $cols[5]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $cols[6]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $cols[7]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $cols[8]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
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
      $searchfield = ['head.docno', 'sy.sy', 'p.code', 'client.clientname'];
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

    $qry = "select head.trno, head.docno, c.coursecode, left(head.dateid,10) as dateid, 'DRAFT' as status, c.coursename, s.section, t.term as terms, p.code as period, head.yr, sy.sy as sy,client.client,client.clientname, eh.docno as curriculumdocno, eh.curriculumcode
      from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join en_course as c on c.line=head.courseid 
      left join en_term as t on t.line=head.semid left join en_period as p on p.line=head.periodid left join en_schoolyear as sy on sy.line=head.syid  left join en_section as s on s.line=head.sectionid 
      left join client on client.client=head.client left join en_studentinfo as info on info.clientid=client.clientid left join en_glhead as eh on eh.trno=info .curriculumtrno 
      where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
      union all
      select head.trno, head.docno, c.coursecode, left(head.dateid,10) as dateid, 'POSTED' as status, c.coursename, s.section, t.term as terms, p.code as period, head.yr, sy.sy as sy,client.client,client.clientname, eh.docno as curriculumdocno, eh.curriculumcode
      from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno  left join en_course as c on c.line=head.courseid 
      left join en_term as t on t.line=head.semid left join en_period as p on p.line=head.periodid left join en_schoolyear as sy on sy.line=head.syid left join en_section as s on s.line=head.sectionid 
      left join client on client.clientid=head.clientid left join en_studentinfo as info on info.clientid=client.clientid left join en_glhead as eh on eh.trno=info.curriculumtrno 
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
    $fields = ['otherfees'];
    $col1 = $this->fieldClass->create($fields);
    $tab = [
      $this->gridname => ['gridcolumns' => ['action', 'subjectcode', 'subjectname', 'units', 'lecture', 'laboratory', 'hours', 'term', 'linstructorcode', 'instructorname', 'lbldgcode', 'roomcode', 'schedday', 'schedstarttime', 'schedendtime'], 'headgridbtns' => ['viewotherfees', 'viewcredentials', 'viewsosummary', 'viewacctg', 'dropstudent']],
    ];

    $stockbuttons = ['delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'SUBJECT';
    $obj[0][$this->gridname]['descriptionrow'] = ['subjectname', 'subjectcode', 'Subject'];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][1]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][1]['lookupclass'] = "lookupsubjectassess";

    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][7]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][7]['label'] = 'Semester'; //action
    $obj[0][$this->gridname]['columns'][8]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][9]['readonly'] = true; //action

    $obj[0][$this->gridname]['columns'][10]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][11]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][12]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][13]['readonly'] = true; //action


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['getassessment', 'savesched', 'deleteallsched', 'otherfees', 'credentials', 'assummary'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'assessref', 'coursecode', 'yr', 'section'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'coursecode.type', 'lookup');
    data_set($col1, 'coursecode.action', 'lookupcourse');
    data_set($col1, 'coursecode.lookupclass', 'lookupcourse');
    data_set($col1, 'coursecode.class', 'cscoursecode sbccsreadonly');
    data_set($col1, 'client.lookupclass', 'student');
    data_set($col1, 'client.label', 'Student#');
    data_set($col1, 'yr.label', 'Grade/Year');
    data_set($col1, 'section.class', 'cssection sbccsreadonly');
    data_set($col1, 'assessref.class', 'csassessref sbccsreadonly');
    data_set($col1, 'yr.class', 'csyr sbccsreadonly');

    $fields = ['dateid', 'clientname', 'deptcode', 'coursename', 'semester', 'ischinese'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'curriculumcode.class', 'sbccsreadonly');
    data_set($col2, 'curriculumdocno.class', 'sbccsreadonly');
    data_set($col2, 'deptcode.type', 'input');
    data_set($col2, 'clientname.class', 'csclientname sbccsreadonly');
    data_set($col2, 'coursename.class', 'cscoursename sbccsreadonly');

    $fields = ['period', 'sy', 'dlevel', 'modeofpayment', 'contra', 'refresh', 'sotrno'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dlevel.class', 'sbccsreadonly');
    data_set($col3, 'contra.lookupclass', 'AR');
    data_set($col3, 'refresh.action', 'load');
    data_set($col3, 'period.label', 'Period (SY & Grade/Year) Ex.19-1');
    data_set($col3, 'period.type', 'input');
    data_set($col3, 'sy.type', 'input');

    $fields = ['rem', 'disc'];
    $col4 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['courseid'] = 0;
    $data[0]['coursename'] = '';
    $data[0]['coursecode'] = '';
    $data[0]['dlevel'] = '';
    $data[0]['section'] = '';
    $data[0]['sectionid'] = 0;
    $data[0]['periodid'] = $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
    $data[0]['period'] = $this->coreFunctions->getfieldvalue('en_period', 'code', 'isactive=1');
    $data[0]['semid'] = 0;
    $data[0]['yr'] = '';
    $schoolyear  = $this->coreFunctions->getfieldvalue('en_period', 'sy', 'isactive=1');
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'sy=?', [$schoolyear]);
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'sy=?', [$schoolyear]);
    $data[0]['deptid'] = 0;
    $data[0]['curriculumcode'] = '';
    $data[0]['curriculumdocno'] = '';
    $data[0]['terms'] = '';
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['deptcode'] = '';
    $data[0]['levelid'] = 0;
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['rem'] = '';
    $data[0]['modeofpayment'] = '';
    $data[0]['assessref'] = '';
    $data[0]['sotrno'] = 0;
    $data[0]['disc'] = 0;
    $data[0]['ischinese'] = '0';
    return $data;
  }

  private function gettotal($trno, $mop)
  {
    $interestpercent = $this->coreFunctions->datareader("select deductpercent as value from en_modeofpayment  as mop where code=?", [$mop]);

    if ($interestpercent > 0) {
      $qryinterest = "union all 
    select 'Add Interest:' as feestype,sum(amt)*(" . $interestpercent . "/100) from
      (select  s.feestype,s.amt from
      (select 'Total Fees' as feestype,isamt as amt from " . $this->otherfees . " as a  where a.trno=" . $trno . "
      union all select 'Total Fees' as feestype,isamt from " . $this->hotherfees . " as a  where a.trno=" . $trno . " ) as s

      union all select  feestype,sum(s.amt)*-1 as amt from
      (select 'Less Credentials' as feestype,camt as amt from " . $this->credentials . " as a  where a.trno=" . $trno . "
      union all select 'Less Credentials' as feestype,camt from " . $this->hcredentials . " as a  where a.trno=" . $trno . ") as s

      union all select  s.feestype,s.amt from
      (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . "
      union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . ") as s where s.feestype<>'OTHERS') as x
      union all
      select 'Total Balance:' as feestype,sum(amt)+(sum(amt)*(" . $interestpercent . "/100)) from
      (select  s.feestype,s.amt from
      (select 'Total Fees' as feestype,isamt as amt from " . $this->otherfees . " as a  where a.trno=" . $trno . "
      union all select 'Total Fees' as feestype,isamt from " . $this->hotherfees . " as a  where a.trno=" . $trno . " ) as s

      union all select  feestype,sum(s.amt)*-1 as amt from
      (select 'Less Credentials' as feestype,camt as amt from " . $this->credentials . " as a  where a.trno=" . $trno . "
      union all select 'Less Credentials' as feestype,camt from " . $this->hcredentials . " as a  where a.trno=" . $trno . ") as s

      union all select  s.feestype,s.amt from
      (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . "
      union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . ") as s  where s.feestype<>'OTHERS') as x";
    } else {
      $qryinterest = "union all
      select 'Total Balance:' as feestype,sum(amt) from
      (select  s.feestype,s.amt from
      (select 'Total Fees' as feestype,isamt as amt from " . $this->otherfees . " as a  where a.trno=" . $trno . "
      union all select 'Total Fees' as feestype,isamt from " . $this->hotherfees . " as a  where a.trno=" . $trno . " ) as s

      union all select  feestype,sum(s.amt)*-1 as amt from
      (select 'Less Credentials' as feestype,camt as amt from " . $this->credentials . " as a  where a.trno=" . $trno . "
      union all select 'Less Credentials' as feestype,camt from " . $this->hcredentials . " as a  where a.trno=" . $trno . ") as s

      union all select  s.feestype,s.amt from
      (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . "
      union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . ") as s  where s.feestype<>'OTHERS') as x";
    }

    $qry = "select group_concat(trim(stat) SEPARATOR '\n') as value 
      from (select concat(feestype,': ',amt) as stat from
      (select feestype,sum(amt) as amt from (
      select 'Other Fees' as feestype,isamt as amt from " . $this->otherfees . " as a where a.trno=?
      union all select 'Other Fees' as feestype,isamt from " . $this->hotherfees . " as a where a.trno=?) as s group by s.feestype

      union all select  s.feestype,sum(s.amt) as amt
      from (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=?
      union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=?) as s  where s.feestype<>'OTHERS' group by s.feestype

      union all
      select  feestype,sum(s.amt)*-1 from
      (select 'Less Credentials' as feestype,camt as amt from " . $this->credentials . " as a  where a.trno=?
      union all select 'Less Credentials' as feestype,camt from " . $this->hcredentials . " as a  where a.trno=?) as s
      
      
      " . $qryinterest . ") as y
      ) as z";

    return $this->coreFunctions->datareader($qry, [$trno, $trno, $trno, $trno, $trno, $trno]);
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
    $qryselect = "select head.trno,head.doc,head.docno,client.client,client.clientname,head.dateid,head.courseid,course.coursecode,course.coursename,head.periodid,
      p.code as period,head.syid,sy.sy,head.assessref,head.rem,head.levelid,l.levels as `level`,head.deptid,d.client as deptcode,head.yr,s.section,
      head.contra,head.semid,t.term as terms,head.modeofpayment,l.levels as dlevel,'' as sumunits,head.sotrno, eh.docno as curriculumdocno, eh.curriculumcode,head.disc, head.ischinese ";

    $qry = $qryselect . " from " . $table . " as head
        left join " . $tablenum . " as num on num.trno = head.trno  left join client on client.client = head.client
        left join en_course as course on course.line=head.courseid left join en_period as p on p.line=head.periodid
        left join en_schoolyear as sy on sy.line=head.syid left join en_section as s on s.line=head.sectionid
        left join client as d on d.clientid=head.deptid left join en_term as t on t.line=head.semid left join en_levels as l on l.line=head.levelid
        left join en_studentinfo as info on info.clientid=client.clientid left join en_glhead as eh on eh.trno=info .curriculumtrno 
        where head.trno = ? and num.center = ? and head.doc= ?
        union all " . $qryselect . " from " . $htable . " as head
        left join " . $tablenum . " as num on num.trno = head.trno left join client on client.clientid = head.clientid
        left join en_course as course on course.line=head.courseid left join en_period as p on p.line=head.periodid
        left join en_schoolyear as sy on sy.line=head.syid left join en_section as s on s.line=head.sectionid
        left join client as d on d.clientid=head.deptid left join en_term as t on t.line=head.semid left join en_levels as l on l.line=head.levelid
        left join en_studentinfo as info on info.clientid=client.clientid left join en_glhead as eh on eh.trno=info .curriculumtrno 
        where head.trno = ? and num.center=? and head.doc= ?";


    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $doc, $trno, $center, $doc]);

    if (!empty($head)) {
      $head[0]->sumunits = $this->gettotal($trno, $head[0]->modeofpayment);
      $stock = $this->openstock($trno, $config);
      $otherfees = $this->openotherfees($trno, $config);
      $credentials = $this->opencredentials($trno, $config);
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
      return  ['head' => $head, 'griddata' => ['inventory' => $stock, 'otherfees' => $otherfees, 'credentials' => $credentials], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => [], 'otherfees' => [], 'credentials' => []], 'msg' => 'Data Head Fetched Failed'];
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
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['coursecode'] . ' - ' . $head['curriculumcode'] . ' - ' . $head['curriculumdocno']);
    }
  } // end function



  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $user = $config['params']['user'];

    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . " where trno=?", [$trno]);
    $client = $this->coreFunctions->datareader('select c.clientid as value from ' . $this->head . ' as h left join client as c on c.client=h.client where h.trno=?', [$trno]);
    $sotrno = $this->coreFunctions->datareader("select sotrno as value from " . $this->head . " where trno=?", [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry("update en_glhead set isenrolled=0 where trno=?", 'update', [$sotrno]);
    $this->coreFunctions->execqry("update en_studentinfo set regtrno=0 where clientid=?", 'update', [$client]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->otherfees . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->credentials . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->assessment . " where trno=?", 'delete', [$trno]);
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


    // if(!$this->createdistribution($config)){
    //   return ['trno'=>$trno,'status'=>false,'msg'=>'Posting failed. Problems in creating accounting entries.'];
    // }else{
    // }
    return $this->postingTrans($config);

    $utrno = $this->coreFunctions->datareader('select trno as value from ' . $this->stock . ' where trno=? and units=0 ', [$trno]);
    if ($utrno > 0) {
      return ['status' => false, 'msg' => 'Posting failed. With zero units, check schedule availabilty.'];
    }
    return $this->postingTrans($config);
  } //end function

  public function postingTrans($config)
  {
    //for glhead
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,periodid,syid,deptid,levelid,yr,semid,sectionid,clientid,terms,courseid,contra,editdate,editby,createdate,createby,encodeddate,encodedby,viewdate,viewby,lockdate,rem,modeofpayment,assessref,sotrno,disc, ischinese)
    select head.trno,head.doc,head.docno,head.dateid,head.periodid,head.syid,head.deptid,head.levelid,head.yr,head.semid,head.sectionid,client.clientid, head.terms,head.courseid,head.contra,head.editdate,head.editby,head.createdate,head.createby,head.encodeddate,head.encodedby,head.viewdate, head.viewby,head.lockdate,head.rem,head.modeofpayment,head.assessref,head.sotrno,head.disc, head.ischinese 
    FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno  left join client on client.client=head.client where head.trno=? limit 1";

    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . "(trno, line, schedday, units, lecture, laboratory, hours, subjectid, roomid, bldgid, instructorid, schedstarttime,
            schedendtime, refx, linex, screfx, sclinex, schedref, ref, origtrno, origline, origdocno, origsubjectid, ctrno, cline, scline, semid)
            select stock.trno, stock.line, stock.schedday, stock.units, stock.lecture, stock.laboratory, stock.hours, stock.subjectid, stock.roomid,
            stock.bldgid, stock.instructorid, stock.schedstarttime, stock.schedendtime, stock.refx, stock.linex, stock.screfx, stock.sclinex,
            stock.schedref, stock.ref, stock.origtrno, stock.origline, stock.origdocno, stock.origsubjectid, stock.ctrno, stock.cline, stock.scline, stock.semid
            FROM " . $this->stock . " as stock where stock.trno =? ";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

        $qry = "insert into " . $this->hotherfees . " (trno,line,isamt,rem,feesid,acnoid) select stock.trno,stock.line,stock.isamt,stock.rem,stock.feesid,stock.acnoid from " . $this->otherfees . " as stock where stock.trno= ?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

          $qry = "insert into " . $this->hcredentials . " (trno,line,amt,percentdisc,camt,credentialid,acnoid,feesid,subjectid) select stock.trno,stock.line,stock.amt,stock.percentdisc,stock.camt,stock.credentialid,stock.acnoid,stock.feesid,stock.subjectid from " . $this->credentials . " as stock where stock.trno= ?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

            $qry = "insert into " . $this->hassessment . " (trno,line,amt,feesid,schemeid) select stock.trno,stock.line,stock.amt,stock.feesid,stock.schemeid from " . $this->assessment . " as stock where stock.trno= ?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

              $date = $this->othersClass->getCurrentTimeStamp();
              $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
              $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
              $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->otherfees . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->credentials . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->assessment . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
              $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
              $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
              return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
            } else {
              $this->coreFunctions->execqry("delete from " . $this->hcredentials . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hotherfees . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
              return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting summary'];
            }
          } else {
            $this->coreFunctions->execqry("delete from " . $this->hcredentials . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hotherfees . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting credentials'];
          }
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hcredentials . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hotherfees . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting otherfees'];
        }
        //update

      } else {
        $this->coreFunctions->execqry("delete from " . $this->hcredentials . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hotherfees . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
      //if($posthead){      
    } else {
      $this->coreFunctions->execqry("delete from " . $this->hcredentials . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hotherfees . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  }

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];


    $msg = $this->hasbeenarpaid($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = $this->transwithreference($config, 'gldetail', 'refx');
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = $this->transwithreference($config, 'en_adsubject', 'refx');
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }


    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);


    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, periodid, syid, deptid, levelid, yr, semid, sectionid, client, terms, courseid,
    contra, editdate, editby, createdate, createby, encodeddate, encodedby, viewdate, viewby, lockdate, rem, modeofpayment, assessref, sotrno, disc, ischinese)
    select head.trno, head.doc, head.docno, head.dateid, head.periodid, head.syid, head.deptid, head.levelid, head.yr, head.semid, head.sectionid,
    client.client, head.terms, head.courseid, head.contra, head.editdate, head.editby, head.createdate, head.createby, head.encodeddate,
    head.encodedby, head.viewdate, head.viewby, head.lockdate, head.rem, head.modeofpayment, head.assessref, head.sotrno, head.disc, head.ischinese FROM " . $this->hhead . " as head
    left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno  left join client on client.clientid=head.clientid where head.trno=? limit 1";

    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(trno, line, schedday, units, lecture, laboratory, hours, subjectid, roomid, bldgid, instructorid, schedstarttime,
      schedendtime, refx, linex, screfx, sclinex, schedref, ref, origtrno, origline, origdocno, origsubjectid, ctrno, cline, scline, semid)
      select stock.trno, stock.line, stock.schedday, stock.units, stock.lecture, stock.laboratory, stock.hours, stock.subjectid, stock.roomid,
      stock.bldgid, stock.instructorid, stock.schedstarttime, stock.schedendtime, stock.refx, stock.linex, stock.screfx, stock.sclinex, stock.schedref,
      stock.ref, stock.origtrno, stock.origline, stock.origdocno, stock.origsubjectid, stock.ctrno, stock.cline, stock.scline, stock.semid
            FROM " . $this->hstock . " as stock where stock.trno =? ";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into " . $this->otherfees . " (trno,line,isamt,rem,feesid,acnoid) select stock.trno,stock.line,stock.isamt,stock.rem,stock.feesid,stock.acnoid from " . $this->hotherfees . " as stock where stock.trno= ?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

          $qry = "insert into " . $this->credentials . " (trno,line,amt,percentdisc,camt,credentialid,acnoid,feesid,subjectid) select stock.trno,stock.line,stock.amt,stock.percentdisc,stock.camt,stock.credentialid,stock.acnoid,stock.feesid,stock.subjectid from " . $this->hcredentials . " as stock where stock.trno= ?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

            $qry = "insert into " . $this->assessment . " (trno,line,amt,feesid,schemeid) select stock.trno,stock.line,stock.amt,stock.feesid,stock.schemeid from " . $this->hassessment . " as stock where stock.trno= ?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

              $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hcredentials . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hotherfees . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hassessment . " where trno=?", "delete", [$trno]);
              $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
              return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
              $this->coreFunctions->execqry("delete from " . $this->assessment . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->credentials . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->otherfees . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
              return ['trno' => $trno, 'status' => false, 'msg' => 'Error on UnPosting summary'];
            }
          } else {
            $this->coreFunctions->execqry("delete from " . $this->credentials . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->otherfees . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on UnPosting Credential'];
          }
        } else {
          $this->coreFunctions->execqry("delete from " . $this->credentials . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->otherfees . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Otherfees'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->credentials . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->otherfees . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    } else {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Head'];
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

  public function hasbeenarpaid($config)
  {
    $trno = $config['params']['trno'];
    $a = $this->coreFunctions->getfieldvalue('arledger', 'trno', 'trno=? and bal<>abs(db+cr)', [$trno]);
    if ($a !== '') {
      return 'This Transaction cannot be UNPOSTED, Already have a payment';
    } else {
      return '';
    }
  }

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,head.client,head.modeofpayment,head.courseid,f.feestype,head.contra,stock.amt
          from ' . $this->head . ' as head left join ' . $this->assessment . ' as stock on stock.trno=head.trno
          left join en_fees as f on f.line=stock.feesid
          where head.trno=?
          union all
          select head.dateid,head.client,head.modeofpayment,head.courseid,f.feestype,head.contra,stock.isamt
          from  ' . $this->head . ' as head left join  ' . $this->otherfees . ' as stock on stock.trno=head.trno
          left join en_fees as f on f.line=stock.feesid
          where head.trno=?
          union all
          select head.dateid,head.client,head.modeofpayment,head.courseid,f.feestype,head.contra,stock.camt *-1
          from ' . $this->head . ' as head left join ' . $this->credentials . ' as stock on stock.trno=head.trno
          left join en_fees as f on f.line=stock.feesid
          where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno]);

    $tax = 0;
    if (!empty($stock)) {

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

      foreach ($stock as $key => $value) {
        $feestype = $stock[$key]->feestype;
        $dcAmount = $dcAmount + $stock[$key]->amt;
      }

      $dcInterests = $dcAmount * ($intInterest / 100);
      $dcARAmt = ($dcAmount + $dcInterests) / $intInterestMonth;
      $dcARAmtTotal = $dcAmount + $dcInterests;

      $dcLastARAmt = $dcAmount + $dcInterests;

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
            'rem' => ''
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
            'rem' => ''
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
        'rem' => ''
      ];

      $this->distribution($params, $config);

      $params = [
        'client' => $stock[0]->client,
        'acno' => '',
        'ext' => $dcAmount,
        'wh' => '',
        'date' => $stock[0]->dateid,
        'inventory' => '',
        'revenue' => $strCharge,
        'tax' =>  0,
        'discamt' => 0,
        'cost' => 0,
        'fcost' => 0,
        'project' => '',
        'rem' => ''
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
        $this->acctg[$key]['trno'] = $config['params']['trno'];
      }

      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
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

        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => round(($params['ext']), 2), 'cr' => 0, 'postdate' => $params['date'], 'fdb' => 0, 'fcr' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => round(($params['discamt']), 2), 'cr' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //INV
    if ($params['cost'] != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      //cogs
      $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
      $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if ($params['revenue'] != '') {
      if (floatval($params['tax']) != 0) {
        //sales
        $sales = ($params['ext'] - $params['tax']);
        $sales  = $sales + $params['discamt'];
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => round(($sales), 2), 'db' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        // input tax
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => round(($params['tax']), 2), 'db' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      } else {
        //sales
        $sales = round(($params['ext'] + $params['discamt']), 2);
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => round(($sales), 2), 'db' => 0, 'postdate' => $params['date'], 'fcr' => 0, 'fdb' => 0, 'project' => $params['project'], 'rem' => $params['rem']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function


  private function getstockselect($config)
  {
    $sqlselect = "
    select stock.trno, stock.line, stock.subjectid, sub.subjectcode, sub.subjectname, stock.units, stock.laboratory, stock.lecture, stock.hours,
      stock.instructorid, i.client as linstructorcode, i.clientname as instructorname, stock.schedday, stock.schedstarttime, stock.schedendtime,
      stock.roomid, r.roomcode as roomcode, stock.bldgid, b.bldgcode as lbldgcode, stock.origdocno, stock.origsubjectid, stock.refx, stock.linex,
      stock.schedref, stock.ref, stock.screfx, stock.sclinex, stock.origtrno, stock.origline, stock.ctrno, stock.cline, stock.scline, sem.term,
      '' as bgcolor,
      '' as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM " . $this->stock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid left join en_term as sem on sem.line=stock.semid
    where stock.trno = ? and num.postdate is null
    UNION ALL
    " . $sqlselect . "  
    FROM " . $this->hstock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid left join en_term as sem on sem.line=stock.semid
    where stock.trno = ?  and num.postdate is not null";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
   FROM " . $this->stock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid  left join en_term as sem on sem.line=stock.semid where stock.trno = ? and stock.line = ? 
   UNION ALL
    " . $sqlselect . "  
    FROM " . $this->hstock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno
    left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid  left join en_term as sem on sem.line=stock.semid where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  } // end function

  public function openotherfeesline($config)
  {
    $sqlselect = $this->getotherfeesselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
   FROM " . $this->otherfees . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno = ? and stock.line = ? 
   UNION ALL
    " . $sqlselect . "  
    FROM " . $this->hotherfees . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid  where stock.trno = ? and stock.line = ? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  } // end function

  private function getotherfeesselect($config)
  {
    $sqlselect = "select stock.trno,stock.line,stock.rem,f.feescode,stock.feestype,stock.scheme,coa.acno,coa.acnoname,stock.feesid,stock.acnoid,stock.isamt,
    '' as bgcolor,
    '' as errcolor ";
    return $sqlselect;
  }

  public function openotherfees($trno, $config)
  {
    $sqlselect = $this->getotherfeesselect($config);

    $qry = $sqlselect . " 
    FROM " . $this->otherfees . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid
    where stock.trno = ? and num.postdate is null
    UNION ALL
    " . $sqlselect . "  
    FROM " . $this->hotherfees . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=stock.acnoid
    where stock.trno = ?  and num.postdate is not null";

    $otherfees = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $otherfees;
  } //end function

  public function opencredentialsline($config)
  {
    $sqlselect = $this->getcredentialsselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . " 
    FROM " . $this->credentials . " as soc left join " . $this->tablenum . " as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid  where soc.trno = ? and soc.line = ? 
   UNION ALL
    " . $sqlselect . "  
    FROM " . $this->hcredentials . " as soc  left join " . $this->tablenum . " as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid  where soc.trno = ? and soc.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  } // end function


  private function getcredentialsselect($config)
  {
    $sqlselect = "select soc.trno,soc.line,soc.credentialid,soc.amt,soc.particulars,soc.percentdisc,soc.acnoid,soc.feesid,coa.acno,coa.acnoname,c.credentials,c.credentialcode,s.subjectcode,s.subjectname,soc.camt,f.feescode as feescode,soc.scheme,f.feestype,
    '' as bgcolor,
    '' as errcolor ";
    return $sqlselect;
  }


  public function opencredentials($trno, $config)
  {
    $sqlselect = $this->getcredentialsselect($config);

    $qry = $sqlselect . " 
    FROM " . $this->credentials . " as soc left join " . $this->tablenum . " as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid 
    where soc.trno = ? and num.postdate is null
    UNION ALL
    " . $sqlselect . "  
    FROM " . $this->hcredentials . " as soc  left join " . $this->tablenum . " as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid 
    where soc.trno = ?  and num.postdate is not null";

    $otherfees = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $otherfees;
  } //end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem': //save per line item edited
        return $this->updateperitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'assesssummary':
        return $this->assesssummary($config);
        break;
      case 'scheddetail':
        return $this->scheddetail($config);
        break;
      case 'generateassessment':
        return $this->assesssummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function assesssummary($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select h.trno, s.subjectid, s.units, s.lecture, s.laboratory, s.hours,s.schedday,s.schedstarttime,s.schedendtime,
    s.instructorid, s.roomid,s.bldgid,s.trno,s.line,s.refx,s.linex,h.docno,c.docno as scdocno
    from en_glhead as h left join en_glsubject as s on s.trno=h.trno left join en_glhead as c on c.trno=s.refx
     where s.trno= ? ";

      $headdetail = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);


      if (!empty($headdetail)) {
        foreach ($headdetail as $key2 => $value) {
          $config['params']['trno'] = $trno;
          $config['params']['data']['subjectid'] = $headdetail[$key2]->subjectid;
          $config['params']['data']['units'] = $headdetail[$key2]->units;
          $config['params']['data']['lecture'] = $headdetail[$key2]->lecture;
          $config['params']['data']['laboratory'] = $headdetail[$key2]->laboratory;
          $config['params']['data']['hours'] = $headdetail[$key2]->hours;
          $config['params']['data']['schedday'] = $headdetail[$key2]->schedday;
          $config['params']['data']['schedstarttime'] = $headdetail[$key2]->schedstarttime;
          $config['params']['data']['schedendtime'] = $headdetail[$key2]->schedendtime;
          $config['params']['data']['instructorid'] = $headdetail[$key2]->instructorid;
          $config['params']['data']['roomid'] = $headdetail[$key2]->roomid;
          $config['params']['data']['bldgid'] = $headdetail[$key2]->bldgid;
          $config['params']['data']['refx'] = $headdetail[$key2]->trno;
          $config['params']['data']['linex'] = $headdetail[$key2]->line;
          $config['params']['data']['screfx'] = $headdetail[$key2]->refx;
          $config['params']['data']['sclinex'] = $headdetail[$key2]->linex;
          $config['params']['data']['ref'] =  $headdetail[$key2]->docno;
          $config['params']['data']['schedref'] =  $headdetail[$key2]->scdocno;
          $config['params']['data']['origdocno'] = '';
          $config['params']['data']['origsubjectid'] = 0;
          $config['params']['data']['origtrno'] = 0;
          $config['params']['data']['origline'] = 0;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedsubject($headdetail[$key2]->refx, $headdetail[$key2]->linex) == 0) {
              $data2 = [$this->units => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsubject($headdetail[$key2]->refx, $headdetail[$key2]->linex);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => false, 'msg' => 'Reached Maximum Student Slots'];
              array_push($rows, $return['row'][0]);
              //$stock = $this->openstock($trno, $config);
              //return  ['row' => $rows, 'status' => false,'msg'=>'Reached Maximum Student Slots'] ; 
            } else {
              array_push($rows, $return['row'][0]);
            }
          }
        }
      }
    }

    $this->generateotherfees($config);
    $this->generatecredentials($config);
    $this->generatefeessummary($config);
    $stock = $this->openstock($trno, $config);
    // $otherfees = $this->openotherfees($trno, $config);
    // $credentials = $this->opencredentials($trno, $config);

    return ['row' => $rows, 'status' => true, 'msg' => 'Added Subject Successfull...'];
  }

  public function scheddetail($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select h.trno, s.subjectid, s.units, s.lecture, s.laboratory, s.hours,s.schedday,s.schedstarttime,s.schedendtime,
    s.instructorid, s.roomid,s.bldgid,s.trno,s.line,s.refx,s.linex,h.docno,c.docno as scdocno
    from en_glhead as h left join en_glsubject as s on s.trno=h.trno left join en_glhead as c on c.trno=s.refx
     where s.trno= ? and s.line=? ";

      $headdetail = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);

      if (!empty($headdetail)) {
        foreach ($headdetail as $key2 => $value) {
          $config['params']['trno'] = $trno;
          $config['params']['data']['subjectid'] = $headdetail[$key2]->subjectid;
          $config['params']['data']['units'] = $headdetail[$key2]->units;
          $config['params']['data']['lecture'] = $headdetail[$key2]->lecture;
          $config['params']['data']['laboratory'] = $headdetail[$key2]->laboratory;
          $config['params']['data']['hours'] = $headdetail[$key2]->hours;
          $config['params']['data']['schedday'] = $headdetail[$key2]->schedday;
          $config['params']['data']['schedstarttime'] = $headdetail[$key2]->schedstarttime;
          $config['params']['data']['schedendtime'] = $headdetail[$key2]->schedendtime;
          $config['params']['data']['instructorid'] = $headdetail[$key2]->instructorid;
          $config['params']['data']['roomid'] = $headdetail[$key2]->roomid;
          $config['params']['data']['bldgid'] = $headdetail[$key2]->bldgid;
          $config['params']['data']['refx'] = $headdetail[$key2]->trno;
          $config['params']['data']['linex'] = $headdetail[$key2]->line;
          $config['params']['data']['screfx'] = $headdetail[$key2]->refx;
          $config['params']['data']['sclinex'] = $headdetail[$key2]->linex;
          $config['params']['data']['ref'] = $headdetail[$key2]->docno;
          $config['params']['data']['schedref'] = $headdetail[$key2]->scdocno;
          $config['params']['data']['origdocno'] = '';
          $config['params']['data']['origsubjectid'] = 0;
          $config['params']['data']['origtrno'] = 0;
          $config['params']['data']['origline'] = 0;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedsubject($headdetail[$key2]->refx, $headdetail[$key2]->linex) == 0) {
              $data2 = [$this->units => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsubject($headdetail[$key2]->refx, $headdetail[$key2]->linex);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => false, 'msg' => 'Reached Maximum Student Slots'];
              array_push($rows, $return['row'][0]);
              //$stock = $this->openstock($trno, $config);
              //return  ['row' => $rows, 'status' => false,'msg'=>'Reached Maximum Student Slots'] ; 
            } else {
              array_push($rows, $return['row'][0]);
            }
          }
          // array_push($rows, $return['row'][0]);
        }
      }
    }

    $this->generateotherfees($config);
    // $this->generatecredentials($config);
    //  $this->generatefeessummary($config);

    return ['row' => $rows, 'status' => true, 'msg' => 'Added Subject Successfull...'];
  }

  public function setservedsubject($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock.trno from " . $this->head . " as head left join " . $this->stock . " as 
        stock on stock.trno=head.trno where head.doc='ER' and stock.screfx=" . $refx . " and stock.sclinex=" . $linex;

    $qry1 = $qry1 . " union all select stock.trno from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
        head.trno where head.doc='ER' and stock.screfx=" . $refx . " and stock.sclinex=" . $linex;

    $qry2 = "select ifnull(count(trno),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update en_glsubject set asqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function generatecredentials($config)
  {
    $trno = $config['params']['trno'];
    $qry = "delete from " . $this->credentials . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno]);

    $sotrno = $this->coreFunctions->datareader("select sotrno as value from en_sjhead where trno=?", [$trno]);
    $qry = "select cred.trno,cred.line,cred.amt,cred.camt,cred.acnoid,cred.credentialid,cred.percentdisc from en_glcredentials as cred where cred.trno=? ";

    $clientdata = $this->coreFunctions->opentable($qry, [$sotrno]);
    $rows = [];
    if (!empty($clientdata)) {
      foreach ($clientdata as $key => $value) {
        $credentialid = $clientdata[$key]->credentialid;
        $percentdisc = $clientdata[$key]->percentdisc;
        $amt = $clientdata[$key]->amt;
        $camt = $clientdata[$key]->camt;

        $config['params']['data']['amt'] = $amt;
        $config['params']['data']['camt'] = $camt;
        $config['params']['data']['credentialid'] = $credentialid;
        $config['params']['data']['percentdisc'] = $percentdisc;
        $return = $this->addcredentials('insert', $config);
        array_push($rows, $return['row'][0]);
      }
    }
  }

  public function generateotherfees($config)
  {
    // $itrno = $config['params']['rows'][0]['trno'];
    $trno = $config['params']['trno'];
    $qry = "delete from " . $this->otherfees . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno]);
    $sotrno = $this->coreFunctions->datareader("select sotrno as value from en_sjhead where trno=?", [$trno]);

    $qry = "select otherfees.trno,otherfees.line,otherfees.feesid,otherfees.acnoid,fees.feestype,otherfees.isamt,fees.feescode from en_glotherfees as otherfees left join en_fees as fees on fees.line=otherfees.feesid where otherfees.trno=?";

    $otherfeesdetail = $this->coreFunctions->opentable($qry, [$sotrno]);


    $i = 0;
    $isamt = 0;
    $rem = '';
    $rows = [];

    foreach ($otherfeesdetail as $key2 => $value) {

      $acnoid = $otherfeesdetail[$key2]->acnoid;
      $feesid = $otherfeesdetail[$key2]->feesid;
      $feestype = $otherfeesdetail[$key2]->feestype;
      $feescode = $otherfeesdetail[$key2]->feescode;
      $isamt = $otherfeesdetail[$key2]->isamt;

      $config['params']['data']['isamt'] = $isamt;
      $config['params']['data']['rem'] = $rem;
      $config['params']['data']['feestype'] = $feestype;
      $config['params']['data']['feescode'] = $feescode;
      $config['params']['data']['acnoid'] = $acnoid;
      $config['params']['data']['feesid'] = $feesid;
      $return = $this->addotherfees('insert', $config);
      array_push($rows, $return['row'][0]);
    }
  }

  public function generatefeessummary($config)
  {

    $trno = $config['params']['trno'];
    $qry = "delete from " . $this->assessment . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno]);

    $trno = $config['params']['trno'];
    $sotrno = $this->coreFunctions->datareader("select sotrno as value from en_sjhead where trno=?", [$trno]);

    $qry = "select gs.trno,gs.line,gs.amt,gs.feesid,gs.schemeid,f.feescode,f.feestype,s.scheme from en_glsummary as gs left join en_fees as f on f.line=gs.feesid left join en_scheme as s on s.line=gs.schemeid where gs.trno=?";

    $otherfeesdetail = $this->coreFunctions->opentable($qry, [$sotrno]);

    $i = 0;
    $isamt = 0;
    $rem = '';
    $rows = [];

    foreach ($otherfeesdetail as $key2 => $value) {
      $strFeesCode = $otherfeesdetail[$key2]->feesid;
      $strSCheme = $otherfeesdetail[$key2]->schemeid;
      $dcFess = $otherfeesdetail[$key2]->amt;

      $line = $this->coreFunctions->datareader("select line as value from " . $this->assessment . " where trno=$trno order by line desc limit 1");
      if ($line == '') $line = 0;
      $line += 1;


      $this->coreFunctions->execqry("insert into " . $this->assessment . " (trno,line,feesid,schemeid,amt) 
            values (" . $trno . "," . $line . "," . $strFeesCode . "," . $strSCheme . "," . $dcFess . ")", "insert");
    } # end for otherfeesdetail

  }

  public function computefees($FeesCode, $scheme, $dcRate, $strFeesType, $config)
  {
    $trno = $config['params']['trno'];
    $qry = "select stock.lecture,stock.hours,stock.units,stock.laboratory from en_sjsubject as stock where stock.trno= ? ";

    $subjectlist = $this->coreFunctions->opentable($qry, [$trno]);

    $dcS = 0;
    $dcSLec = 0;
    $dcSLab = 0;
    $dcSCnt = 0;
    $dcCntLab = 0;
    $dcTotal = 0;
    $amt = 0;
    $dcCnt = 0;
    $registration = 0;
    $tuitionfee = 0;
    $misc = 0;
    $dcothers = 0;
    $dcTotalOthers = 0;
    $dctotal = 0;


    foreach ($subjectlist as $key2 => $value) {
      $dcS = $dcS + $subjectlist[$key2]->units;
      $dcSLec = $dcSLec + $subjectlist[$key2]->lecture;
      $dcSLab = $dcSLab + $subjectlist[$key2]->laboratory;
      $dcSCnt = $dcSCnt + 1;
      if ($subjectlist[$key2]->laboratory > 0) {
        $dcCntLab = $dcCntLab + 1;
      }
    }

    $totalunit  = $dcS;

    $strScheme = $this->coreFunctions->datareader("select scheme as value from en_scheme where line=" . $scheme);
    $strFeesCode = $this->coreFunctions->datareader("select feestype as value from en_fees where line=" . $FeesCode);


    $qry = "select soc.amt from en_sjcredentials as soc left join en_credentials as c on soc.credentialid=c.line left join en_fees as f on f.line=c.feesid where f.feestype='TF' and soc.trno= ? ";

    $credentiallist = $this->coreFunctions->opentable($qry, [$trno]);

    switch ($strFeesType) {
      case "TF":
        switch ($strScheme) {
          case "PER UNIT":
            if ($strFeesCode == "LAB") {
              $dcTotal = $dcSLab * $dcRate;
            } else {
              $dcTotal = $dcS * $dcRate;
            }

            break;
          case "FIXED":
            $dcTotal = $dcRate;
            break;
          case "PER SUBJECT":

            foreach ($subjectlist as $key2 => $value) {
              $dcCnt = $dcCnt + 1;
              $dcTotal = $dcCnt * $dcRate;
            }

            break;
        }

        foreach ($credentiallist as $key2 => $value) {
          $amt = $credentiallist[$key2]->amt;
          if ($amt <> 0) {
            $dcTotal = $dcTotal - $amt;
          } else {
            $dcTotal = $dcTotal - ($dcTotal * ($amt / 100));
          }
        }

        return  $dcTotal;
        break;
      case "REG":
        switch ($strScheme) {
          case "PER UNIT":
            if ($strFeesCode == "LAB") {
              $dcTotal = $dcSLab * $dcRate;
            } else {
              $dcTotal = $dcS * $dcRate;
            }
            break;
          case "FIXED":
            $cTotal = $dcRate;
            break;
          case "PER SUBJECT":
            foreach ($subjectlist as $key2 => $value) {
              $dcCnt = $dcCnt + 1;
              $dcTotal = $dcCnt * $dcRate;
            }
        }

        foreach ($credentiallist as $key2 => $value) {
          $amt = $credentiallist[$key2]->amt;
          if ($amt <> 0) {
            $dcTotal = $dcTotal - $amt;
          } else {
            $dcTotal = $dcTotal - ($dcTotal * ($amt / 100));
          }
        }

        return $dctotal;

        break;
      case "MISC":

        switch ($strScheme) {
          case "PER UNIT":
            if ($strFeesCode == "LAB") {
              $dcTotal = $dcSLab * $dcRate;
            } else {
              $dcTotal = $dcS * $dcRate;
            }
            break;
          case "FIXED":
            $cTotal = $dcRate;
            break;
          case "PER SUBJECT":
            foreach ($subjectlist as $key2 => $value) {
              $dcCnt = $dcCnt + 1;
              $dcTotal = $dcCnt * $dcRate;
            }
        }

        foreach ($credentiallist as $key2 => $value) {
          $amt = $credentiallist[$key2]->amt;
          if ($amt <> 0) {
            $dcTotal = $dcTotal - $amt;
          } else {
            $dcTotal = $dcTotal - ($dcTotal * ($amt / 100));
          }
        }

        $misc = $misc + $dctotal;

        return $misc;

        break;

      default:

        switch ($strScheme) {
          case "PER UNIT":
            if ($strFeesCode == "LAB") {
              $dcTotalOthers = $dcSLab * $dcRate;
            } else {
              $dcTotalOthers = $dcS * $dcRate;
            }
            $dcothers = $dcothers + $dcTotalOthers;
            break;
          case "FIXED":
            $dcTotalOthers = $dcRate;
            $dcothers = $dcothers + $dcTotalOthers;
            break;
          case "PER SUBJECT":
            foreach ($subjectlist as $key2 => $value) {
              $dcCnt = $dcCnt + 1;
              if ($strFeesCode == "LAB") {
                $dcTotalOthers = $dcCntLab * $dcRate;
              } else {
                $dcTotalOthers = $dcCnt * $dcRate;
              }

              $dcothers = $dcTotalOthers;
            }
        }

        return $dcothers;

        break;
    }
  }


  public function additem($action, $config)
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
      'refx' => $config['params']['data']['refx'],
      'linex' => $config['params']['data']['linex'],
      'screfx' => $config['params']['data']['screfx'],
      'sclinex' => $config['params']['data']['sclinex'],
      'subjectid' => $config['params']['data']['subjectid'],
      'units' => $config['params']['data']['units'],
      'lecture' => $config['params']['data']['lecture'],
      'laboratory' => $config['params']['data']['laboratory'],
      'hours' => $config['params']['data']['hours'],
      'schedday' => $config['params']['data']['schedday'],
      'schedstarttime' => $config['params']['data']['schedstarttime'],
      'schedendtime' => $config['params']['data']['schedendtime'],
      'instructorid' => $config['params']['data']['instructorid'],
      'roomid' => $config['params']['data']['roomid'],
      'bldgid' => $config['params']['data']['bldgid'],
      'schedref' => $config['params']['data']['schedref'],
      'ref' => $config['params']['data']['ref'],
      'origdocno' => $config['params']['data']['origdocno'],
      'origsubjectid' => $config['params']['data']['origsubjectid'],
      'origtrno' => $config['params']['data']['origtrno'],
      'origline' => $config['params']['data']['origline']
    ];
    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $sotrno = $this->coreFunctions->datareader("select sotrno as value from en_sjhead where trno=?", [$trno]);
        $this->coreFunctions->execqry("update en_glhead set isenrolled=1 where trno=?", "update", [$sotrno]);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add subject successfully'];
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

  public function addcredentials($action, $config)
  {
    $trno = $config['params']['trno'];
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->credentials . " where trno=? order by line desc limit 1";
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
      'amt' => $config['params']['data']['amt'],
      'camt' => $config['params']['data']['camt'],
      'credentialid' => $config['params']['data']['credentialid'],
      'percentdisc' => $config['params']['data']['percentdisc']
    ];

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->credentials, $data) == 1) {
        $row = $this->opencredentialsline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add Credential successfully'];
      } else {
        return ['status' => false, 'msg' => 'Add Credential failed'];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->credentials, $data, ['trno' => $trno, 'line' => $line]);
    }


    return true;
  }

  public function addotherfees($action, $config)
  {
    $trno = $config['params']['trno'];
    $line = 0;
    $row = [];
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->otherfees . " where trno=? order by line desc limit 1";
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
      'isamt' => $config['params']['data']['isamt'],
      'rem' => $config['params']['data']['rem'],
      'feestype' => $config['params']['data']['feestype'],
      'acnoid' => $config['params']['data']['acnoid'],
      'feesid' => $config['params']['data']['feesid']
    ];

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->otherfees, $data) == 1) {
        $row = $this->openotherfeesline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add Other fees successfully'];
      } else {
        return ['status' => false, 'msg' => 'Add Other Fees failed'];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->otherfees, $data, ['trno' => $trno, 'line' => $line]);
    }

    return true;
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
      $this->setservedsubject($config['params']['data']['origtrno'], $config['params']['data']['origline']);
      $this->setservedsubject($config['params']['data']['screfx'], $config['params']['data']['sclinex']);
      if (isset($config['params']['sotrno'])) {
        $sotrno = $config['params']['sotrno'];
      } else {
        $sotrno = 0;
      }
      $this->coreFunctions->execqry("update en_glhead set isenrolled=1 where trno=?", "update", [$sotrno]);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';

    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];

    $qry = 'select screfx,sclinex from ' . $this->stock . ' where trno=?';
    $data = $this->coreFunctions->opentable($qry, [$trno]);

    $sotrno = $this->coreFunctions->datareader("select sotrno as value from en_sjhead where trno=?", [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->otherfees . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->credentials . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->assessment . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry("update en_glhead set isenrolled=0 where trno=?", "update", [$sotrno]);

    foreach ($data as $key => $value) {
      $screfx = $data[$key]->screfx;
      $sclinex = $data[$key]->sclinex;
      $this->setservedsubject($screfx, $sclinex);
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

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

    $this->setservedsubject($data[0]->screfx, $data[0]->sclinex);

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'SUBJECT', 'REMOVED - Line:' . $line . ' Subject ID:' . $data[0]['subjectid'] . ' units:' . $data[0]['units'] . ' InstructorID:' . $data[0]['instructorid'] . ' schedday:' . $data[0]['schedday'] . ' schedstarttime:' . $data[0]['schedstarttime'] . ' schedendtime:' . $data[0]['schedendtime'] . ' bldgid:' . $data[0]['bldgid'] . ' room:' . $data[0]['roomid']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
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
    $eiassessment = $config['params']['dataparams']['eiassessment'];
    $eischedule = $config['params']['dataparams']['eischedule'];

    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);

    switch ($eiassessment) {
      case 'withassess':
        switch ($eischedule) {
          case 'withsched':
            $assess = app($this->companysetup->getreportpath($config['params']))->assessment_query($config);
            $str = app($this->companysetup->getreportpath($config['params']))->assessment_report_plotting($config, $data, $assess);
            break;
          default:
            $assess = app($this->companysetup->getreportpath($config['params']))->assessment_query($config);
            $str = app($this->companysetup->getreportpath($config['params']))->assessment2_report_plotting($config, $data, $assess);
            break;
        }

        break;
      default:
        switch ($eischedule) {
          case 'withsched':
            $str = app($this->companysetup->getreportpath($config['params']))->withschedplotting($config, $data);
            break;

          default:
            $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
            break;
        }

        break;
    }

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

  //     $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  //       date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
  //       item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  //       stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  //       item.sizeid,m.model_name as model
  //       from sohead as head left join sostock as stock on stock.trno=head.trno 
  //       left join item on item.itemid=stock.itemid
  //       left join model_masterfile as m on m.model_id = item.model
  //       left join client on client.client=head.wh
  //       left join client as cust on cust.client = head.client
  //       where head.trno='$trno'
  //       union all
  //       select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  //       date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
  //       item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  //       stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  //       item.sizeid,m.model_name as model
  //       from hsohead as head 
  //       left join hsostock as stock on stock.trno=head.trno
  //       left join item on item.itemid=stock.itemid 
  //       left join model_masterfile as m on m.model_id = item.model
  //       left join client on client.client=head.wh
  //       left join client as cust on cust.client = head.client
  //       where head.doc='so' and head.trno='$trno' order by line";

  //     $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //     return $result;
  //   }//end fn  



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







} //end class
