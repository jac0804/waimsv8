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

class ea
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'College Assessment';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_sohead';
  public $stock = 'en_sosubject';
  public $otherfees = 'en_sootherfees';
  public $credentials = 'en_socredentials';
  public $detail = 'en_sodetail';
  public $assessment = 'en_sosummary';

  public $hhead = 'en_glhead';
  public $hstock = 'en_glsubject';
  public $hotherfees = 'en_glotherfees';
  public $hcredentials = 'en_glcredentials';
  public $hdetail = 'en_gldetail';
  public $hassessment = 'en_glsummary';
  public $defaultContra = 'AR1';

  public $schedday = 'schedday';
  public $refx = 'refx';
  public $linex = 'linex';
  public $roomid = 'roomid';
  public $bldgid = 'bldgid';
  public $instructorid = 'instructorid';
  public $schedstarttime = 'schedstarttime';
  public $schedendtime = 'schedendtime';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'periodid', 'syid', 'courseid', 'schedcode', 'rem', 'deptid', 'levelid', 'semid', 'yr', 'sectionid', 'contra', 'modeofpayment', 'curriculumtrno'];

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
      'view' => 973,
      'edit' => 972,
      'new' => 974,
      'save' => 975,
      'change' => 977,
      'delete' => 976,
      'acctg' => 183,
      'print' => 183,
      'lock' => 980,
      'unlock' => 981,
      'post' => 978,
      'unpost' => 979,

      'additem' => 1318,
      'edititem' => 1319,
      'deleteitem' => 1320
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listclientname', 'listdate', 'listsy', 'listperiod'];
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
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
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

    $qry = "select head.trno, head.docno, c.coursecode, left(head.dateid,10) as dateid, 'DRAFT' as status, c.coursename, head.curriculumname, head.section, t.term as terms, p.code as period, head.yr, head.curriculumcode,sy.sy as sy,client.client,client.clientname, head.sectionid
      from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join en_course as c on c.line=head.courseid 
      left join en_term as t on t.line=head.semid left join en_period as p on p.line=head.periodid left join en_schoolyear as sy on sy.line=head.syid
      left join client on client.client=head.client 
      where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
      union all
      select head.trno, head.docno, c.coursecode, left(head.dateid,10) as dateid, 'POSTED' as status, c.coursename, head.curriculumname, head.section, t.term as terms, p.code as period, head.yr, head.curriculumcode,sy.sy as sy,client.client,client.clientname, head.sectionid
      from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno  left join en_course as c on c.line=head.courseid 
      left join en_term as t on t.line=head.semid left join en_period as p on p.line=head.periodid left join en_schoolyear as sy on sy.line=head.syid
      left join client on client.clientid=head.clientid
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
      $this->gridname => ['gridcolumns' => ['action', 'subjectcode', 'subjectname', 'units', 'lecture', 'laboratory', 'hours', 'eainstructorcode', 'instructorname', 'lbldgcode', 'roomcode', 'schedday', 'schedstarttime', 'schedendtime', 'availableslot'], 'headgridbtns' => ['viewotherfees', 'viewcredentials', 'viewsosummary', 'generateenbilling']],
    ];

    $stockbuttons = ['delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'SUBJECT';
    $obj[0][$this->gridname]['descriptionrow'] = ['subjectname', 'subjectcode', 'Subject'];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][7]['readonly'] = true; //action
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
    $tbuttons = ['generatestudcurriculum', 'getschedule', 'savesched', 'deleteallsched', 'otherfees', 'credentials', 'assummary'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'Extract Curriculum';
    $obj[0]['lookupclass'] = 'stockstatus';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'sy', 'client', 'coursecode', 'curriculumdocno', 'yr'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'coursecode.type', 'input');
    data_set($col1, 'coursecode.class', 'sbccsreadonly');
    data_set($col1, 'client.lookupclass', 'student');
    data_set($col1, 'client.label', 'Student#');
    data_set($col1, 'yr.label', 'Grade/Year');
    data_set($col1, 'yr.addedparams', ['curriculumdocno']);
    data_set($col1, 'yr.class', 'sbccsreadonly');
    data_set($col1, 'curriculumdocno.type', 'input');
    data_set($col1, 'curriculumdocno.class', 'sbccsreadonly');
    data_set($col1, 'sy.type', 'input');

    $fields = ['dateid', 'deptcode', 'clientname', 'coursename', 'curriculumcode', 'semester'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'clientname.class', 'csclientname sbccsreadonly');
    data_set($col2, 'curriculumcode.class', 'cscurriculumcode sbccsreadonly');
    data_set($col2, 'coursename.class', 'cscoursename sbccsreadonly');
    data_set($col2, 'semester.type', 'input');

    $fields = ['period', 'levels', 'modeofpayment', 'contra', 'curriculumname', 'section'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'curriculumname.class', 'sbccsreadonly');
    data_set($col3, 'deptcode.type', 'input');
    data_set($col3, 'levels.type', 'input');
    data_set($col3, 'levels.class', 'sbccsreadonly');
    data_set($col3, 'section.class', 'sbccsreadonly');
    data_set($col3, 'contra.lookupclass', 'AR');
    data_set($col3, 'period.label', 'Period (SY & Grade/Year) Ex.19-1');
    data_set($col3, 'period.type', 'input');
    data_set($col3, 'modeofpayment.required', true);

    $fields = ['rem', 'sumunits'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'sumunits.type', 'ctextarea');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['sectionid'] = 0;
    $data[0]['section'] = '';
    $data[0]['courseid'] = 0;
    $data[0]['coursecode'] = '';
    $data[0]['coursename'] = '';
    $data[0]['periodid'] = $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
    $data[0]['period'] = $this->coreFunctions->getfieldvalue('en_period', 'code', 'isactive=1');
    $data[0]['semid'] = 0;
    $data[0]['yr'] = '';
    $schoolyear  = $this->coreFunctions->getfieldvalue('en_period', 'sy', 'isactive=1');
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'sy=?', [$schoolyear]);
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'sy=?', [$schoolyear]);
    $data[0]['deptid'] = 0;
    $data[0]['curriculumtrno'] = 0;
    $data[0]['curriculumcode'] = '';
    $data[0]['curriculumdocno'] = '';
    $data[0]['curriculumname'] = '';
    $data[0]['terms'] = '';
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['deptcode'] = '';
    $data[0]['levelid'] = 0;
    $data[0]['levels'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['rem'] = '';
    $data[0]['modeofpayment'] = '';
    $data[0]['schedcode'] = '';
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
        group by s.feestype

        union all select  s.feestype,s.amt from
        (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . "
        union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . ") as s 
        where s.feestype<>'OTHERS') as x
        group by x.feestype

        union all
        select 'Total Balance:' as feestype,sum(amt)+(sum(amt)*(" . $interestpercent . "/100)) from
        (select  s.feestype,s.amt from
        (select 'Total Fees' as feestype,isamt as amt from " . $this->otherfees . " as a  where a.trno=" . $trno . "
        union all select 'Total Fees' as feestype,isamt from " . $this->hotherfees . " as a  where a.trno=" . $trno . " ) as s

        union all select  feestype,sum(s.amt)*-1 as amt from
        (select 'Less Credentials' as feestype,camt as amt from " . $this->credentials . " as a  where a.trno=" . $trno . "
        union all select 'Less Credentials' as feestype,camt from " . $this->hcredentials . " as a  where a.trno=" . $trno . ") as s
        group by s.feestype

        union all select  s.feestype,s.amt from
        (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . "
        union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . ") as s  
        where s.feestype<>'OTHERS') as x group by x.feestype";
    } else {
      $qryinterest = "union all
        select 'Total Balance:' as feestype,sum(amt) from
        (select  s.feestype,s.amt from
        (select 'Total Fees' as feestype,isamt as amt from " . $this->otherfees . " as a  where a.trno=" . $trno . "
        union all select 'Total Fees' as feestype,isamt from " . $this->hotherfees . " as a  where a.trno=" . $trno . " ) as s

        union all select  feestype,sum(s.amt)*-1 as amt from
        (select 'Less Credentials' as feestype,camt as amt from " . $this->credentials . " as a  where a.trno=" . $trno . "
        union all select 'Less Credentials' as feestype,camt from " . $this->hcredentials . " as a  where a.trno=" . $trno . ") as s
        group by s.feestype

        union all select  s.feestype,s.amt from
        (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . "
        union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=" . $trno . ") as s  where s.feestype<>'OTHERS') as x";
    }

    $qry = "select concat(feestype,' ',sum(amt)) as stat
      from (select feestype,amt from
      (select feestype,sum(amt) as amt from (
      select 'Other Fees' as feestype,isamt as amt from " . $this->otherfees . " as a where a.trno=?
      union all select 'Other Fees' as feestype,isamt from " . $this->hotherfees . " as a where a.trno=?) as s group by s.feestype

      union all select  s.feestype,sum(s.amt) as amt
      from (select f.feestype,a.amt as amt from " . $this->assessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=?
      union all select f.feestype,a.amt from " . $this->hassessment . " as a  left join en_fees as f on f.line=a.feesid  where a.trno=?) as s  
      where s.feestype<>'OTHERS' group by s.feestype

      union all
      select  feestype,sum(s.amt)*-1 from
      (select 'Less Credentials' as feestype,camt as amt from " . $this->credentials . " as a  where a.trno=?
      union all select 'Less Credentials' as feestype,camt from " . $this->hcredentials . " as a  where a.trno=?) as s
      group by s.feestype
      
      " . $qryinterest . ") as y
      ) as z group by feestype";



    $headsum = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno, $trno, $trno, $trno]);

    $strheadsum = '';

    if (!empty($headsum)) {
      foreach ($headsum as $key2 => $value) {

        $strheadsum = $strheadsum . PHP_EOL . $headsum[$key2]->stat;
      }
      return  $strheadsum;
    }
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
      p.code as period,head.syid,sy.sy,head.schedcode,head.rem,head.levelid,head.deptid,d.client as deptcode,head.yr,s.section,head.sectionid,
      head.contra,head.semid,t.term as terms,head.modeofpayment,l.levels as levels,'' as sumunits, ec.docno as curriculumdocno, ec.curriculumcode, ec.curriculumname,
      head.curriculumtrno";

    $qry = $qryselect . " from " . $table . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.client = head.client
      left join en_course as course on course.line=head.courseid
      left join en_period as p on p.line=head.periodid
      left join en_schoolyear as sy on sy.line=head.syid 
      left join client as d on d.clientid=head.deptid
      left join en_term as t on t.line=head.semid
      left join en_levels as l on l.line=head.levelid
      left join en_section as s on s.line=head.sectionid
      left join en_glhead as ec on ec.trno=head.curriculumtrno and ec.doc='EC'
      where head.trno = ? and num.center = ? 
      union all " . $qryselect . " from " . $htable . " as head
      left join " . $tablenum . " as num on num.trno = head.trno
      left join client on client.clientid=head.clientid
      left join en_course as course on course.line=head.courseid
      left join en_period as p on p.line=head.periodid
      left join en_schoolyear as sy on sy.line=head.syid
      left join client as d on d.clientid=head.deptid
      left join en_term as t on t.line=head.semid
      left join en_levels as l on l.line=head.levelid
      left join en_section as s on s.line=head.sectionid
      left join en_glhead as ec on ec.trno=head.curriculumtrno and ec.doc='EC'
      where head.trno = ? and num.center=? ";


    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

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
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->detail . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->otherfees . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->credentials . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->assessment . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function checkstudentslot($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];

    $qry = "select h.trno, s.subjectid, s.units, s.lecture, s.laboratory, s.hours,s.schedday,s.schedstarttime,s.schedendtime,
    s.instructorid, s.roomid,s.bldgid,s.trno,s.line,s.refx,s.linex,h.docno,c.docno as scdocno
    from en_sohead as h left join en_sosubject as s on s.trno=h.trno left join en_glhead as c on c.trno=s.refx
    where s.trno= ? ";

    $headdetail = $this->coreFunctions->opentable($qry, [$trno]);


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


        if ($this->setservedpostsubject($headdetail[$key2]->refx, $headdetail[$key2]->linex, $trno) == 0) {
          $data2 = [$this->instructorid => 0, $this->schedday => '', $this->refx => 0, $this->linex => 0, $this->roomid => 0, $this->bldgid => 0, $this->schedstarttime => null, $this->schedendtime => null];
          $line = $headdetail[$key2]->line;
          $config['params']['trno'] = $trno;
          $config['params']['line'] = $line;
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservedpostsubject($headdetail[$key2]->refx, $headdetail[$key2]->linex, $trno);
          return true;
        }
      }
      return false;
    }
    return  true;
  }

  public function setservedpostsubject($refx, $linex, $trno)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock.trno from " . $this->head . " as head left join " . $this->stock . "  as 
        stock on stock.trno=head.trno where head.doc='EA' and head.trno=" . $trno . " and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock.trno from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
        head.trno where head.doc='EA' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(count(trno),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update en_glsubject set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);


    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    if ($this->checkstudentslot($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Check the schedule.'];
    } else {
      return $this->postingTrans($config);
    }
  } //end function

  public function updateqaonposting($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select h.trno, s.line, s.subjectid, s.units, s.lecture, s.laboratory, s.hours,s.schedday,s.schedstarttime,s.schedendtime,
    s.instructorid, s.roomid,s.bldgid,s.trno,s.line,s.refx,s.linex,h.docno,c.docno as scdocno
    from en_sohead as h left join en_sosubject as s on s.trno=h.trno left join en_glhead as c on c.trno=s.refx
    where s.trno= ?  ";

    $headsubject = $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function postingTrans($config)
  {
    //for glhead
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "
      insert into " . $this->hhead . "(trno, doc, docno, dateid, periodid, syid, deptid, levelid, yr, semid, sectionid, clientid, terms, courseid,
        contra, editdate, editby, createdate, createby, encodeddate, encodedby, viewdate, viewby, lockdate, rem, modeofpayment, curriculumtrno)
    select head.trno, head.doc, head.docno, head.dateid, head.periodid, head.syid, head.deptid, head.levelid, head.yr, head.semid, head.sectionid,
      client.clientid, head.terms, head.courseid, head.contra, head.editdate, head.editby, head.createdate, head.createby, head.encodeddate,
      head.encodedby, head.viewdate, head.viewby, head.lockdate, head.rem, head.modeofpayment, head.curriculumtrno FROM " . $this->head . " as head
      left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno  left join client on client.client=head.client where head.trno=? limit 1";

    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . "(trno,line,schedday,units,lecture,laboratory,hours,subjectid,roomid,bldgid,instructorid,schedstarttime,schedendtime,refx,linex,ctrno,cline,scline)
        select stock.trno,stock.line,stock.schedday,stock.units,stock.lecture,stock.laboratory,stock.hours,stock.subjectid,stock.roomid,stock.bldgid,stock.instructorid,stock.schedstarttime,stock.schedendtime,stock.refx,stock.linex,stock.ctrno,stock.cline,stock.scline 
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
              $this->coreFunctions->execqry("delete from " . $this->hassessment . " where trno=?", "delete", [$trno]);
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
    if ($msg !== '') return ['trno' => $trno, 'status' => false, 'msg' => $msg];

    $msg = $this->transwithreference($config, 'en_sjsubject', 'refx');
    if ($msg !== '') return ['trno' => $trno, 'status' => false, 'msg' => $msg];

    $msg = $this->transwithreference($config, 'en_glsubject', 'refx');
    if ($msg !== '') return ['trno' => $trno, 'status' => false, 'msg' => $msg];

    $msg = $this->transwithreference($config, 'en_adsubject', 'refx');
    if ($msg !== '') return ['trno' => $trno, 'status' => false, 'msg' => $msg];

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, periodid, syid, deptid, levelid, yr, semid, section, client, terms, courseid,
      contra, editdate, editby, createdate, createby, encodeddate, encodedby, viewdate, viewby, lockdate, rem, modeofpayment, curriculumtrno, sectionid)
      select head.trno, head.doc, head.docno, head.dateid, head.periodid, head.syid, head.deptid, head.levelid, head.yr, head.semid, head.section,
      client.client, head.terms, head.courseid, head.contra, head.editdate, head.editby, head.createdate, head.createby, head.encodeddate,
      head.encodedby, head.viewdate, head.viewby, head.lockdate, head.rem, head.modeofpayment, head.curriculumtrno, head.sectionid FROM " . $this->hhead . " as head
      left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno  left join client on client.clientid=head.clientid where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(trno,line,schedday,units,lecture,laboratory,hours,subjectid,roomid,bldgid,instructorid,schedstarttime,schedendtime,refx,linex,ctrno,cline,scline)
        select stock.trno,stock.line,stock.schedday,stock.units,stock.lecture,stock.laboratory,stock.hours,stock.subjectid,stock.roomid,stock.bldgid,stock.instructorid,stock.schedstarttime,stock.schedendtime,stock.refx,stock.linex,stock.ctrno,stock.cline,stock.scline 
        FROM " . $this->hstock . " as stock where stock.trno =? ";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $qry = "insert into " . $this->otherfees . " (trno,line,isamt,rem,feesid,acnoid) select stock.trno,stock.line,stock.isamt,stock.rem,stock.feesid,stock.acnoid from " . $this->hotherfees . " as stock where stock.trno= ?";
        if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
          $qry = "insert into " . $this->credentials . " (trno,line,amt,percentdisc,camt,credentialid,acnoid,feesid,subjectid) select stock.trno,stock.line,stock.amt,stock.percentdisc,stock.camt,stock.credentialid,stock.acnoid,stock.feesid,stock.subjectid from " . $this->hcredentials . " as stock where stock.trno= ?";
          if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
            $qry = "insert into " . $this->assessment . " (trno,line,amt,feesid,schemeid) select stock.trno,stock.line,stock.amt,stock.feesid,stock.schemeid from " . $this->hassessment . " as stock where stock.trno= ?";
            if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
              $this->coreFunctions->execqry("delete from apledger where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from crledger where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from cbledger where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from caledger where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from rrstatus where trno=?", "delete", [$trno]);

              $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hcredentials . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hotherfees . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hassessment . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
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

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = "select head.dateid,head.client,head.modeofpayment,head.courseid,f.feestype,head.contra,stock.amt,'SUMMARY' as ftype
      from " . $this->hhead . " as head left join " . $this->hassessment . " as stock on stock.trno=head.trno
      left join en_fees as f on f.line=stock.feesid
      where f.feestype<>'OTHERS' and  head.trno=?
      union all
      select head.dateid,head.client,head.modeofpayment,head.courseid,f.feestype,head.contra,stock.isamt,'OTHERFEES' as ftype
      from  " . $this->hhead . " as head left join  " . $this->hotherfees . " as stock on stock.trno=head.trno
      left join en_fees as f on f.line=stock.feesid
      where head.trno=?
      union all
      select head.dateid,head.client,head.modeofpayment,head.courseid,f.feestype,coa.acno as contra,stock.camt *-1,'CREDENTIALS' as ftype
      from " . $this->hhead . " as head left join " . $this->hcredentials . " as stock on stock.trno=head.trno left join en_credentials as c on c.line=stock.credentialid
      left join en_fees as f on f.line=stock.feesid left join coa on coa.acnoid=c.acnoid
      where head.trno=?";

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
      $dcCredentials = 0;

      foreach ($stock as $key => $value) {
        $feestype = $stock[$key]->feestype;
        $dcAmount = $dcAmount + $stock[$key]->amt;

        if ($stock[$key]->ftype == 'CREDENTIALS') {
          $params = [
            'client' => $stock[0]->client,
            'acno' => '',
            'ext' =>  $stock[$key]->amt * -1,
            'wh' => '',
            'date' => $stock[$key]->dateid,
            'inventory' => '',
            'revenue' => $stock[$key]->contra,
            'tax' =>  0,
            'discamt' => 0,
            'cost' => 0,
            'fcost' => 0,
            'project' => '',
            'rem' => $stock[$key]->ftype
          ];
          $this->distribution($params, $config);
          $dcCredentials = $dcCredentials +  $stock[$key]->amt * -1;
        }
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
            'rem' => '1'
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
            'rem' => '2'
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
        'ext' => $dcAmount - $dcCredentials,
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

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno,stock.line,stock.subjectid,sub.subjectcode,sub.subjectname,stock.units,stock.laboratory,stock.lecture,stock.hours,stock.instructorid,i.client as eainstructorcode,i.clientname as instructorname,stock.schedday,stock.schedstarttime,stock.schedendtime, stock.roomid,r.roomcode as roomcode,stock.bldgid, b.bldgcode as lbldgcode, stock.schedref,stock.origdocno,stock.origsubjectcode, stock.refx,stock.linex,
      '' as bgcolor,
      '' as errcolor,s.maxslot-(select count(ea.trno) from (
        select head.trno,stock.refx,stock.linex
        from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno
        where head.doc='EA'
        union all
        select head.trno,stock.refx,stock.linex
        from en_sohead as head left join en_sosubject as stock on stock.trno=head.trno
        where head.doc='EA') as ea where ea.refx=stock.refx and ea.linex=stock.linex) as availableslot ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
      FROM " . $this->stock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid
      left join en_glsubject as s on s.trno=stock.refx and s.line=stock.linex
      where stock.trno = ? and num.postdate is null
      UNION ALL
      " . $sqlselect . "  
      FROM " . $this->hstock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid
      left join en_glsubject as s on s.trno=stock.refx and s.line=stock.linex
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
    FROM " . $this->stock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid where stock.trno = ? and stock.line = ? 
    UNION ALL
      " . $sqlselect . "  
      FROM " . $this->hstock . " as stock left join " . $this->tablenum . " as num on num.trno=stock.trno
      left join en_subject as sub on sub.trno=stock.subjectid left join client as i on i.clientid=stock.instructorid left join en_rooms as r on r.line=stock.roomid left join en_bldg as b on b.line=stock.bldgid where stock.trno = ? and stock.line = ? ";
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
      case 'generatecurriculum':
        return $this->generatecurriculum($config);
        break;
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
      case 'schedsummary':
        return $this->schedsummary($config);
        break;
      case 'scheddetail':
        return $this->scheddetail($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function schedsummary($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->credentials . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->assessment . ' where trno=?', 'delete', [$trno]);
    $rows = [];
    $status = true;
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select h.trno, s.subjectid, s.units, s.lecture, s.laboratory, s.hours,s.schedday,s.schedstarttime,s.schedendtime,
        s.instructorid, s.roomid,s.bldgid,s.trno,s.line, sb.subjectcode
        from en_glhead as h left join en_glsubject as s on s.trno=h.trno left join en_subject  as sb on sb.trno=s.subjectid
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
          $config['params']['data']['schedref'] = '';
          $config['params']['data']['origdocno'] = '';
          $config['params']['data']['origsubjectcode'] = '';
          $sub = $this->coreFunctions->opentable("select * from en_sosubject where subjectid=? and trno=?", [$config['params']['data']['subjectid'], $trno]);
          if (!empty($sub)) {
            if ($this->coreFunctions->sbcupdate($this->stock, $config['params']['data'], ['trno' => $trno, 'subjectid' => $config['params']['data']['subjectid']]) > 0) {
              if ($this->setservedsubject($headdetail[$key2]->trno, $headdetail[$key2]->line) == 0) {
                $status = false;
                if ($msg == '') {
                  $msg = $headdetail[$key2]->subjectcode;
                } else {
                  $msg = $msg . ',' . $headdetail[$key2]->subjectcode;
                }
              } else {
              }
            }
          }
        }
        $rows = $this->openstock($trno, $config);
      }
    }

    $this->generateotherfees($config);
    $this->generatefeessummary($config);
    $this->generatecredentials($config);
    $this->openstock($trno, $config);
    $this->openotherfees($trno, $config);
    $this->opencredentials($trno, $config);
    $this->loadheaddata($config);
    $rows = ['inventory' => $rows];
    if ($status) {
      return ['griddata' => $rows, 'status' => true, 'msg' => 'Added Subject Successfull...', 'reloadgriddata' => true];
    } else {
      return ['griddata' => $rows, 'status' => true, 'msg' => $msg . ' have reached Maximum Student Slots', 'reloadgriddata' => true];
    }
  }

  public function scheddetail($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->credentials . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->assessment . ' where trno=?', 'delete', [$trno]);
    $rows = [];
    $status = true;
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select h.trno, s.subjectid, s.units, s.lecture, s.laboratory, s.hours,s.schedday,s.schedstarttime,s.schedendtime,
        s.instructorid, s.roomid,s.bldgid,s.trno,s.line,sb.subjectcode
        from en_glhead as h left join en_glsubject as s on s.trno=h.trno  left join en_subject  as sb on sb.trno=s.subjectid
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
          $config['params']['data']['schedref'] = '';
          $config['params']['data']['origdocno'] = '';
          $config['params']['data']['origsubjectcode'] = '';
          $return = $this->additem('insert', $config);
          // array_push($rows, $return['row'][0]);

          if ($this->setservedsubject($headdetail[$key2]->trno, $headdetail[$key2]->line) == 0) {
            array_push($rows, $return['row'][0]);
            $stock = $this->openstock($trno, $config);
            $status = false;
            if ($msg == '') {
              $msg = $headdetail[$key2]->subjectcode;
            } else {
              $msg = $msg . ',' . $headdetail[$key2]->subjectcode;
            }
          } else {
            array_push($rows, $return['row'][0]);
          }
        }
      }
    }

    $this->generateotherfees($config);
    $this->generatefeessummary($config);
    $this->generatecredentials($config);

    if ($status) {
      return ['row' => $rows, 'status' => true, 'msg' => 'Added Subject Successfull...', 'reloaddata' => true];
    } else {
      return ['row' => $rows, 'status' => true, 'msg' => $msg . ' have reached Maximum Student Slots', 'reloaddata' => true];
    }
  }

  public function generatecredentials($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select client.clientid as value from en_sohead as head left join client on client.client=head.client where head.trno=? ";
    $clientid = $this->coreFunctions->datareader($qry, [$trno]);

    $qry = "select credentialid,percentdisc,amt from en_studentcredentials as sc where sc.clientid=? ";

    $clientdata = $this->coreFunctions->opentable($qry, [$clientid]);

    if (!empty($clientdata)) {
      foreach ($clientdata as $key => $value) {
        $credentialid = $clientdata[$key]->credentialid;
        $percentdisc = $clientdata[$key]->percentdisc;
        $amt = $clientdata[$key]->amt;

        $config['params']['data']['amt'] = $amt;
        $config['params']['data']['credentialid'] = $credentialid;
        $config['params']['data']['percentdisc'] = $percentdisc;
        return $this->addcredentials('insert', $config);
        $return = $this->addcredentials('insert', $config);
        if ($return['status'])  array_push($rows, $return['row'][0]);
      }
    }
  }

  public function generateotherfees($config)
  {
    // $itrno = $config['params']['rows'][0]['trno'];
    $trno = $config['params']['trno'];
    $qry = "select c.isnew,c.isold,c.islateenrollee,c.isforeign,c.isadddrop,c.iscrossenrollee,c.istransferee,head.periodid,head.courseid,head.yr from en_sohead as head left join client on client.client=head.client left join en_studentinfo as c on c.clientid=client.clientid where head.trno= ? ";

    $clientdata = $this->coreFunctions->opentable($qry, [$trno]);

    $isnew = false;
    $isold = false;
    $islateenrollee = false;
    $isforeign = false;
    $isadddrop = false;
    $iscrossenrollee = false;
    $istransferee = false;
    $filter = '';
    $periodid = 0;
    $courseid = 0;
    $dcSLab = 0;
    $dcS = 0;
    $dcSCnt = 0;
    $dcCntLab = 0;

    if (!empty($clientdata)) {
      foreach ($clientdata as $key => $value) {
        $isnew = $clientdata[$key]->isnew;
        $isold = $clientdata[$key]->isold;
        $islateenrollee = $clientdata[$key]->islateenrollee;
        $isforeign = $clientdata[$key]->isforeign;
        $isadddrop = $clientdata[$key]->isadddrop;
        $iscrossenrollee = $clientdata[$key]->iscrossenrollee;
        $istransferee = $clientdata[$key]->istransferee;
        $periodid = $clientdata[$key]->periodid;
        $courseid = $clientdata[$key]->courseid;
        $yr = $clientdata[$key]->yr;
      }
    }

    if ($isnew) {
      $filter = " and fees.isnew=true";
    } elseif ($isold) {
      $filter = " and fees.isold=true";
    } elseif ($islateenrollee) {
      $filter = " and fees.islateenrollee=true";
    } elseif ($isforeign) {
      $filter = " and fees.isforeign=true";
    } elseif ($isadddrop) {
      $filter = " and fees.isadddrop=true";
    } elseif ($iscrossenrollee) {
      $filter = " and fees.iscrossenrollee=true";
    } elseif ($istransferee) {
      $filter = " and fees.istransferee=true";
    }

    $qry = "select s.lecture,s.laboratory from en_sosubject as s   
      where s.trno= ?  ";

    $subjectdetail = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($subjectdetail)) {
      foreach ($subjectdetail as $key2 => $value) {

        $dcS = $dcS + $subjectdetail[$key2]->lecture;
        $dcSLab = $dcSLab + $subjectdetail[$key2]->laboratory;
        $dcSCnt = $dcSCnt + 1;
        if ($subjectdetail[$key2]->laboratory > 0) {
          $dcCntLab = $dcCntLab + 1;
        }
      }


      $qry = "select head.trno,head.docno,head.dateid,head.semid,head.syid,head.curriculumcode,
        head.periodid,head.adviserid,head.curriculumdocno,fees.trno,fees.line,fees.subjectid,fees.feesid,
        fees.schemeid,fees.rate,fees.isnew,fees.isold,fees.isforeign,fees.isadddrop,fees.iscrossenrollee,fees.istransferee,fees.levelid,fees.departid,
        fees.courseid,fees.section,fees.sex,fees.rooms,fees.yrid,fees.semid,f.feestype,f.acno,f.feesdesc,s.scheme,f.feescode,coa.acnoid
        from en_glhead as head left join en_glfees as fees on fees.trno=head.trno left join en_fees as f on f.line=fees.feesid 
        left join en_scheme as s on s.line=fees.schemeid  left join coa on coa.acnoid=f.acnoid
        where head.doc='ET'  and f.feestype not in ('REG','MISC','TF') " . $filter . " and head.periodid= ? and (fees.courseid=? or fees.courseid=0) and (fees.yr=? or fees.yr=0)  ";

      $otherfeesdetail = $this->coreFunctions->opentable($qry, [$periodid, $courseid, $yr]);


      $i = 0;
      $isamt = 0;
      $rem = '';
      $rows = [];

      foreach ($otherfeesdetail as $key2 => $value) {
        $feestype = $otherfeesdetail[$key2]->feestype;
        $scheme = $otherfeesdetail[$key2]->scheme;
        $feescode = $otherfeesdetail[$key2]->feescode;
        $acnoid = $otherfeesdetail[$key2]->acnoid;
        $feesid = $otherfeesdetail[$key2]->feesid;
        $rate = $otherfeesdetail[$key2]->rate;
        switch ($scheme) {
          case "PER UNIT":
            if ($feescode == "LAB") {
              $isamt = $rate * $dcSLab;
            } else {
              $isamt = $rate * $dcS;
            }
            break;
          case "PER SUBJECT":
            if ($feescode == "LAB") {
              $isamt = $rate * $dcCntLab;
            } else {
              $isamt = $rate * $dcSCnt;
            }
            break;
          default:
            $isamt = $rate;
            break;
        }

        $config['params']['data']['isamt'] = $isamt;
        $config['params']['data']['rem'] = $rem;
        $config['params']['data']['feestype'] = $feestype;
        $config['params']['data']['feescode'] = $feescode;
        $config['params']['data']['scheme'] = $scheme;
        $config['params']['data']['acnoid'] = $acnoid;
        $config['params']['data']['feesid'] = $feesid;
        $return = $this->addotherfees('insert', $config);
        if ($return['status'])  array_push($rows, $return['row'][0]);
      }
    }
  }

  public function generatefeessummary($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select c.isnew,c.isold,c.islateenrollee,c.isforeign,c.isadddrop,c.iscrossenrollee,c.istransferee,head.periodid,head.courseid,head.syid from en_sohead as head left join client on client.client=head.client left join en_studentinfo as c on c.clientid=client.clientid where head.trno= ? ";

    $clientdata = $this->coreFunctions->opentable($qry, [$trno]);

    $isnew = false;
    $isold = false;
    $islateenrollee = false;
    $isforeign = false;
    $isadddrop = false;
    $iscrossenrollee = false;
    $istransferee = false;
    $filter = '';
    $periodid = 0;
    $courseid = 0;
    $dcSLab = 0;
    $dcS = 0;
    $dcSCnt = 0;
    $dcCntLab = 0;
    $dcFess = 0;

    if (!empty($clientdata)) {
      foreach ($clientdata as $key => $value) {
        $isnew = $clientdata[$key]->isnew;
        $isold = $clientdata[$key]->isold;
        $islateenrollee = $clientdata[$key]->islateenrollee;
        $isforeign = $clientdata[$key]->isforeign;
        $isadddrop = $clientdata[$key]->isadddrop;
        $iscrossenrollee = $clientdata[$key]->iscrossenrollee;
        $istransferee = $clientdata[$key]->istransferee;
        $periodid = $clientdata[$key]->periodid;
        $courseid = $clientdata[$key]->courseid;
        $syid = $clientdata[$key]->syid;
      }
    }

    if ($isnew) {
      $filter = " and fees.isnew=true";
    } elseif ($isold) {
      $filter = " and fees.isold=true";
    } elseif ($islateenrollee) {
      $filter = " and fees.islateenrollee=true";
    } elseif ($isforeign) {
      $filter = " and fees.isforeign=true";
    } elseif ($isadddrop) {
      $filter = " and fees.isadddrop=true";
    } elseif ($iscrossenrollee) {
      $filter = " and fees.iscrossenrollee=true";
    } elseif ($istransferee) {
      $filter = " and fees.istransferee=true";
    }


    $qry = "select s.lecture,s.laboratory from en_sosubject as s   
    where s.trno= ?  ";

    $subjectdetail = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($subjectdetail)) {
      foreach ($subjectdetail as $key2 => $value) {
        $dcS = $dcS + $subjectdetail[$key2]->lecture;
        $dcSLab = $dcSLab + $subjectdetail[$key2]->laboratory;
        $dcSCnt = $dcSCnt + 1;
        if ($subjectdetail[$key2]->laboratory > 0) {
          $dcCntLab = $dcCntLab + 1;
        }
      }
      $qry = "select head.docno,head.dateid,head.periodid,head.syid,fees.trno,fees.line,fees.levelid,fees.departid,fees.courseid,fees.yr,fees.semid,
        fees.sectionid,fees.subjectid,fees.sex,fees.feesid,fees.schemeid,fees.rate,fees.isnew,fees.isold,fees.isforeign,fees.istransferee,fees.islateenrollee,fees.iscrossenrollee, fees.isadddrop, f.feestype,f.acnoid,f.amount
        from en_glhead as head left join en_glfees as fees on head.trno=fees.trno left join en_fees as f on f.line=fees.feesid 
        where head.doc='ET'  and head.periodid=? and head.syid=? " . $filter . "  and (fees.courseid=? or fees.courseid=0) ";
      $otherfeesdetail = $this->coreFunctions->opentable($qry, [$periodid, $syid, $courseid]);
      $i = 0;
      $isamt = 0;
      $rem = '';
      $rows = [];

      foreach ($otherfeesdetail as $key2 => $value) {
        $strSY = $otherfeesdetail[$key2]->syid;
        $strCourse = $otherfeesdetail[$key2]->courseid;
        $strCurriculum = $otherfeesdetail[$key2]->docno;
        $strSection = $otherfeesdetail[$key2]->sectionid;
        $strPeriod = $otherfeesdetail[$key2]->periodid;
        $strSubject = $otherfeesdetail[$key2]->subjectid;
        $strFeesCode = $otherfeesdetail[$key2]->feesid;
        $strFeesType = $otherfeesdetail[$key2]->feestype;
        $strSCheme = $otherfeesdetail[$key2]->schemeid;
        $dcRate = $otherfeesdetail[$key2]->rate;
        $strLevel = $otherfeesdetail[$key2]->levelid;
        $strDept = $otherfeesdetail[$key2]->departid;
        $strSex = $otherfeesdetail[$key2]->sex;

        $strYear = $otherfeesdetail[$key2]->yr;
        $strSem = $otherfeesdetail[$key2]->semid;

        if ($strSY > 0) {
          $searchSY = " and head.syid=" . $strSY . " ";
        } else {
          $searchsSY = '';
        }

        if ($strCourse > 0) {
          $searchCourse = " and head.courseid=" . $strCourse . " ";
        } else {
          $searchCourse = '';
        }

        if ($strSection  != '') {
          $searchSection = " and fees.section=" . $strSection . " ";
        } else {
          $searchSection = '';
        }

        if ($strPeriod > 0) {
          $searchPeriod = " and head.periodid=" . $strPeriod . " ";
        } else {
          $searchPeriod = '';
        }

        if ($strSubject  > 0) {
          $searchSubject = " and s.subjectid=" . $strSubject . " ";
        } else {
          $searchSubject = '';
        }

        if ($strLevel > 0) {
          $searchLevel = " and head.levelid=" . $strLevel . " ";
        } else {
          $searchLevel = '';
        }

        if ($strDept > 0) {
          $searchDept = " and head.deptid=" . $strDept . " ";
        } else {
          $searchDept = '';
        }

        if ($strYear > 0) {
          $searchYear = " and head.yr=" . $strYear . " ";
        } else {
          $searchYear = '';
        }

        if ($strSem  > 0) {
          $searchSem = " and fees.semid=" . $strSem . " ";
        } else {
          $searchSem = '';
        }

        $qryhead = "select head.trno,head.docno,head.client,head.dateid,head.yr,head.courseid,head.semid,head.periodid,head.deptid,head.syid,head.levelid,head.section,head.modeofpayment,head.contra from en_sohead as head where head.trno= ? " . $searchCourse . $searchDept . $searchPeriod . $searchSection . $searchSY . $searchYear . $searchLevel;

        $qrysubject = "select s.subjectid from en_sosubject as s where s.trno= ? " . $searchSubject;
        $dthead = $this->coreFunctions->opentable($qryhead, [$trno]);
        $dtsubject = $this->coreFunctions->opentable($qrysubject, [$trno]);

        if ($strSubject == 0) {
          foreach ($dthead as $key => $value) {
            if ($dthead[$key]->docno != '') {
              if ($strSubject == 0) {
                $dcFess = $this->computefees($strFeesCode, $strSCheme, $dcRate, $strFeesType, $config, $strSubject);
              }
            }
          }   #end dthead for
        } else {
          foreach ($dthead as $key => $value) {
            if ($dthead[$key]->docno != '') {
              if ($strSubject != 0) {
                foreach ($dtsubject as $key2 => $value) {
                  if ($dtsubject[$key2]->subjectid != 0) {
                    $dcFess = $this->computefees($strFeesCode, $strSCheme, $dcRate, $strFeesType, $config, $strSubject);
                  }
                }
              }
            }
          }   #end dthead for
        }

        if ($strFeesType != 'OTHERS') {
          $line = $this->coreFunctions->datareader("select line as value from " . $this->assessment . " where trno=" . $trno . " order by line desc limit 1");
          if ($line == '') $line = 0;
          $line += 1;

          $this->coreFunctions->execqry("insert into " . $this->assessment . " (trno,line,feesid,schemeid,amt) 
          values (" . $trno . "," . $line . "," . $strFeesCode . "," . $strSCheme . "," . $dcFess . ")", "insert");
        }
      } # end for otherfeesdetail
    } #end for subecjectdetails
  }

  public function computefees($FeesCode, $scheme, $dcRate, $strFeesType, $config, $strSubject)
  {
    $trno = $config['params']['trno'];
    $qry = "select stock.lecture,stock.hours,stock.units,stock.laboratory from en_sosubject as stock where stock.trno= ? and (stock.subjectid=? or stock.subjectid=0) ";

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


    $qry = "select soc.amt from en_socredentials as soc left join en_credentials as c on soc.credentialid=c.line left join en_fees as f on f.line=c.feesid where f.feestype='TF' and soc.trno= ? ";

    $credentiallist = $this->coreFunctions->opentable($qry, [$trno]);
    switch ($strFeesCode) {
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

            // foreach($subjectlist as $key2 => $value){
            //   $dcCnt = $dcCnt + 1;
            // }
            $dcTotal = $dcSCnt * $dcRate;

            break;
        }

        foreach ($credentiallist as $key2 => $value) {
          $amt = $credentiallist[$key2]->amt;
          if ($amt != 0) {
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
            $dcTotal = $dcRate;
            break;
          case "PER SUBJECT":
            foreach ($subjectlist as $key2 => $value) {
              $dcCnt = $dcCnt + 1;
            }
            $dcTotal = $dcCnt * $dcRate;
        }

        foreach ($credentiallist as $key2 => $value) {
          $amt = $credentiallist[$key2]->amt;
          if ($amt != 0) {
            $dcTotal = $dcTotal - $amt;
          } else {
            $dcTotal = $dcTotal - ($dcTotal * ($amt / 100));
          }
        }

        return $dcTotal;

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
            $dcTotal = $dcRate;
            break;
          case "PER SUBJECT":
            foreach ($subjectlist as $key2 => $value) {
              $dcCnt = $dcCnt + 1;
            }
            $dcTotal = $dcSCnt * $dcRate;
        }

        // foreach($credentiallist as $key2 => $value){
        // $amt = $credentiallist[$key2]->amt;
        //   if ($amt != 0) {
        //         $dcTotal = $dcTotal - $amt;
        //       }else{
        //         $dcTotal = $dcTotal - ($dcTotal * ($amt / 100));
        //       }
        // }

        $misc = $misc + $dcTotal;

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
      'origdocno' => $config['params']['data']['origdocno'],
      'origsubjectcode' => $config['params']['data']['origsubjectcode']
    ];
    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add subject successfully.'];
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
      'credentialid' => $config['params']['data']['credentialid'],
      'percentdisc' => $config['params']['data']['percentdisc']
    ];

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->credentials, $data) == 1) {
        $row = $this->opencredentialsline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add Credential successfully.'];
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
      'scheme' => $config['params']['data']['scheme'],
      'acnoid' => $config['params']['data']['acnoid'],
      'feesid' => $config['params']['data']['feesid']
    ];

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->otherfees, $data) == 1) {
        $row = $this->openotherfeesline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add Other fees successfully.'];
      } else {
        return ['status' => false, 'msg' => 'Add Other Fees failed'];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->otherfees, $data, ['trno' => $trno, 'line' => $line]);
    }
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
      $this->setservedsubject($config['params']['data']['refx'], $config['params']['data']['linex']);
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

    $qry = 'select refx,linex from ' . $this->stock . ' where trno=?';
    $data = $this->coreFunctions->opentable($qry, [$trno]);

    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->otherfees . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->credentials . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->assessment . ' where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      $refx = $data[$key]->refx;
      $linex = $data[$key]->linex;
      $this->setservedsubject($refx, $linex);
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

    $qry = "delete from " . $this->otherfees . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno]);

    $qry = "delete from " . $this->credentials . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno]);

    $qry = "delete from " . $this->assessment . " where trno=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno]);

    $this->setservedsubject($data[0]->refx, $data[0]->linex);

    $this->generateotherfees($config);
    $this->generatecredentials($config);
    $this->generatefeessummary($config);

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'SUBJECT', 'REMOVED - Line:' . $line . ' Subject ID:' . $data[0]['subjectid'] . ' units:' . $data[0]['units'] . ' InstructorID:' . $data[0]['instructorid'] . ' schedday:' . $data[0]['schedday'] . ' schedstarttime:' . $data[0]['schedstarttime'] . ' schedendtime:' . $data[0]['schedendtime'] . ' bldgid:' . $data[0]['bldgid'] . ' room:' . $data[0]['roomid']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end 

  public function setservedsubject($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock.trno from " . $this->head . " as head left join " . $this->stock . "  as 
        stock on stock.trno=head.trno where head.doc='EA' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock.trno from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
        head.trno where head.doc='EA' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(count(trno),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update en_glsubject set asqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
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
  //   $fields = ['radioprint','prepared','approved','received','print'];
  //   $col1 = $this->fieldClass->create($fields);
  //   return array('col1'=>$col1);
  // }

  // public function reportparamsdata($config){
  //   return $this->coreFunctions->opentable(
  //     "select 
  //     'default' as print,
  //     '' as prepared,
  //     '' as approved,
  //     '' as received
  //     ");
  // }

  // private function report_default_query($trno){
  //   $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  //     date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
  //     item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  //     stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  //     item.sizeid,m.model_name as model
  //     from sohead as head left join sostock as stock on stock.trno=head.trno 
  //     left join item on item.itemid=stock.itemid
  //     left join model_masterfile as m on m.model_id = item.model
  //     left join client on client.client=head.wh
  //     left join client as cust on cust.client = head.client
  //     where head.trno='$trno'
  //     union all
  //     select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  //     date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
  //     item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  //     stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  //     item.sizeid,m.model_name as model
  //     from hsohead as head 
  //     left join hsostock as stock on stock.trno=head.trno
  //     left join item on item.itemid=stock.itemid 
  //     left join model_masterfile as m on m.model_id = item.model
  //     left join client on client.client=head.wh
  //     left join client as cust on cust.client = head.client
  //     where head.doc='so' and head.trno='$trno' order by line";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // }//end fn  

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

  // public function reportdata($config){
  //   $data = $this->report_default_query($config['params']['dataid']);
  //   $str = $this->reportplotting($config,$data);
  //   return ['status'=>true,'msg'=>'Generating report successfully.','report'=>$str];
  // }



  public function generatecurriculum($config)
  {
    $trno = $config['params']['trno'];
    $head = $this->coreFunctions->opentable("select curriculumtrno, yr, semid from en_sohead where trno=?", [$trno]);
    if (empty($head)) return ['status' => false, 'msg' => 'An error occurred; please try again.'];
    $curriculumtrno = $head[0]->curriculumtrno;
    $yr = $head[0]->yr;
    $semid = $head[0]->semid;
    $qry = "select cur.trno, cur.line, cur.cline, cur.subjectid, yr.year, yr.semid, sub.units, sub.lecture, sub.laboratory, sub.hours
      from en_scurriculum as cur
      left join en_glyear as yr on yr.trno=cur.trno and yr.line=cur.cline
      left join en_glsubject as sub on sub.trno=cur.trno and sub.cline=cur.cline and sub.subjectid=cur.subjectid
      where cur.trno=? and yr.year=? and yr.semid=?";
    $subjects = $this->coreFunctions->opentable($qry, [$curriculumtrno, $yr, $semid]);
    if (empty($subjects)) return ['status' => false, 'msg' => 'Nothing to extract. Please try again.'];
    foreach ($subjects as $sub) {
      $c = $this->coreFunctions->opentable("select line from " . $this->stock . " where cline=? and scline=? and ctrno=?", [$sub->cline, $sub->line, $sub->trno]);
      if (empty($c)) {
        $line = $this->coreFunctions->datareader("select line as value from " . $this->stock . " where trno=" . $trno . " order by line desc limit 1");
        if ($line == '') $line = 0;
        $line += 1;
        $qry = "insert into " . $this->stock . "(trno, line, subjectid, ctrno, cline, scline, units, lecture, laboratory, hours) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->coreFunctions->execqry($qry, 'insert', [$trno, $line, $sub->subjectid, $sub->trno, $sub->cline, $sub->line, $sub->units, $sub->lecture, $sub->laboratory, $sub->hours]);
      }
    }
    $data = $this->openstock($trno, $config);
    return ['status' => true, 'inventory' => $data, 'msg' => 'Successfully saved.'];
  }
} //end class
