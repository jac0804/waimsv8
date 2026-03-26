<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class en_student
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STUDENT LIST';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $studentidLen = 10;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'client';
  public $headOther = 'en_studentinfo';
  public $prefix = 'ST';
  public $tablelogs = 'client_log';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;

  private $fields = ['client', 'clientname', 'isstudent', 'bday', 'accountant', 'email', 'picture'];
  private $fieldsOther = [
    'studentid', 'lname', 'fname', 'mname', 'courseid', 'branch', 'gender', 'civilstatus', 'bplace', 'nationality', 'extramural',
    'haddr', 'htel', 'baddr', 'btel', 'guardian', 'gtel',
    'elementary', 'eyear', 'highschool', 'hyear', 'college', 'cyear', 'postschool', 'pyear', 'company',
    'isold', 'isnew', 'isforeign', 'isadddrop', 'iscrossenrollee', 'istransferee', 'islateenrollee', 'isregular', 'isirregular', 'curriculumtrno', 'sectionid', 'yr', 'levelup', 'schedtrno', 'assesstrno', 'regtrno', 'chinesename', 'chinesecourseid', 'chineselevelup'
  ];
  private $except = ['clientid', 'studentid'];
  private $blnfields = ['isstudent', 'isold', 'isnew', 'isforeign', 'isadddrop', 'iscrossenrollee', 'istransferee', 'islateenrollee', 'isregular', 'isirregular'];
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
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 924,
      'edit' => 923,
      'new' => 925,
      'save' => 926,
      'change' => 1315,
      'delete' => 927,
      'print' => 928,
      'load' => 922
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'listclientname', 'listisnew', 'listcoursename'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['client.clientid', 'client.client', 'client.clientname', 'o.chinesename', 'client.addr', 'c.coursename'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $qry = "select client.clientid,client.client,concat(client.clientname,' ',o.chinesename) as clientname,client.addr,
    (case when o.isnew=1 then 'true' else 'false' end) as isnew,c.coursename
    from client left join en_studentinfo as o on o.clientid=client.clientid 
    left join en_course as c on c.line=o.courseid
    where isstudent =1 " . $filtersearch . "
    order by concat(o.lname,', ',o.fname,' ',o.mname)";

    $data = $this->coreFunctions->opentable($qry);

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
    $fields = ['bday', 'bplace', 'nationality', 'extramural',];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'bplace.type', 'cinput');
    data_set($col1, 'extramural.type', 'cinput');

    $fields = ['haddr', 'htel', 'baddr', 'btel'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'haddr.type', 'cinput');
    data_set($col4, 'htel.type', 'cinput');
    data_set($col4, 'baddr.type', 'cinput');
    data_set($col4, 'btel.type', 'cinput');

    $fields = ['guardian', 'gtel'];
    $col5 = $this->fieldClass->create($fields);
    data_set($col5, 'guardian.type', 'cinput');
    data_set($col5, 'gtel.type', 'cinput');

    $fields = ['elementary', 'eyear', 'highschool', 'hyear'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'elementary.type', 'cinput');
    data_set($col2, 'eyear.type', 'cinput');
    data_set($col2, 'highschool.type', 'cinput');
    data_set($col2, 'hyear.type', 'cinput');

    $fields = ['college', 'cyear', 'postschool', 'pyear'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'college.type', 'cinput');
    data_set($col3, 'cyear.type', 'cinput');
    data_set($col3, 'postschool.type', 'cinput');
    data_set($col3, 'pyear.type', 'cinput');

    $fields = ['company', 'accountant', 'email'];
    $col6 = $this->fieldClass->create($fields);
    data_set($col6, 'company.type', 'cinput');
    data_set($col6, 'accountant.type', 'cinput');
    data_set($col6, 'email.type', 'cinput');

    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col4' => $col4, 'col5' => $col5], 'label' => 'PERSONAL INFO'],
      'multiinput2' => ['inputcolumn' => ['col1' => $col2, 'col3' => $col3, 'col6' => $col6], 'label' => 'EDUCATION'],
      'scurriculum' => ['action' => 'enrollmententry', 'lookupclass' => 'entrystudcurriculum', 'label' => 'CURRICULUM'],
      'archive' => ['action' => 'enrollmententry', 'lookupclass' => 'viewstudarchive', 'label' => 'ARCHIVE']
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    // $tbuttons = ['generatestudcurriculum', 'viewar', 'viewstudentcredentials'];
    $tbuttons = ['viewar', 'viewstudenthistory'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = 'HISTORY';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['client', 'studentid', 'lname', 'fname', 'mname', 'chinesename', 'gender', 'civilstatus'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Student Code');
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'lookupstudent');
    data_set($col1, 'client.action', 'lookupledgerclient');

    data_set($col1, 'studentid.type', 'cinput');
    data_set($col1, 'lname.type', 'cinput');
    data_set($col1, 'fname.type', 'cinput');
    data_set($col1, 'mname.type', 'cinput');
    data_set($col1, 'chinesename.type', 'cinput');

    $fields = ['course', 'coursename', 'chinesecourse', 'chinesecoursename', 'levelup', 'chineselevelup', 'curriculumdocno', 'curriculumcode', 'curriculumname', 'schedcode'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'course.required', 'sbccsreadonly');
    data_set($col2, 'gender.required', 'sbccsreadonly');
    data_set($col2, 'levelup.class', 'sbccsreadonly');

    data_set($col2, 'levelup.name', 'levelupcode');
    data_set($col2, 'chinesecoursename.class', 'sbccsreadonly');
    data_set($col2, 'chineselevelup.class', 'sbccsreadonly');

    data_set($col2, 'coursename.class', 'cscoursename sbccsreadonly');
    data_set($col2, 'curriculumdocno.type', 'lookup');
    data_set($col2, 'curriculumdocno.lookupclass', 'lookupcurriculum');
    data_set($col2, 'curriculumdocno.action', 'lookupcurriculum');
    data_set($col2, 'curriculumdocno.addedparams', ['courseid']);
    data_set($col2, 'curriculumdocno.class', 'sbccsreadonly');
    data_set($col2, 'curriculumcode.class', 'sbccsreadonly');
    data_set($col2, 'curriculumname.class', 'sbccsreadonly');

    $fields = ['radioregular', 'radiostudent', ['islateenrollee', 'isadddrop'], ['istransferee', 'iscrossenrollee'], 'assesscode', 'regcode', 'branch', 'yr', 'section'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'assesscode.class', 'sbccsreadonly');
    data_set($col3, 'regcode.class', 'sbccsreadonly');
    data_set($col3, 'branch.type', 'cinput');
    data_set($col3, 'yr.type', 'cinput');
    data_set($col3, 'yr.label', 'Grade/Year');
    data_set($col3, 'yr.class', 'sbccsreadonly');
    data_set($col3, 'yr.required', false);
    data_set($col3, 'section.class', 'sbccsreadonly');
    data_set($col3, 'section.required', false);
    data_set($col3, 'section.type', 'cinput');


    $fields = ['picture'];
    $col4 = $this->fieldClass->create($fields);

    data_set($col4, 'picture.lookupclass', 'client');
    data_set($col4, 'picture.folder', 'student');
    data_set($col4, 'picture.table', 'client');
    data_set($col4, 'picture.fieldid', 'clientid');
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $strYearPref = date('Y') . '-';
    $number = ($this->coreFunctions->getfieldvalue('en_studentinfo', 'max(right(studentid,5))', 'left(studentid,5)=' . date('Y')) + 1);
    $studentid = str_pad($number, $this->studentidLen - (strlen($strYearPref)), '0', STR_PAD_LEFT);
    $data[0]['studentid'] = date('Y') . '-' . $studentid;
    $data[0]['lname'] = '';
    $data[0]['fname'] = '';
    $data[0]['mname'] = '';
    $data[0]['chinesename'] = '';
    $data[0]['picture'] = '';

    $data[0]['courseid'] = 0;
    $data[0]['course'] = '';
    $data[0]['coursename'] = '';

    $data[0]['chinesecourseid'] = 0;
    $data[0]['chinesecourse'] = '';
    $data[0]['chinesecoursename'] = '';

    $data[0]['branch'] = '';
    $data[0]['gender'] = '';
    $data[0]['civilstatus'] = '';

    $data[0]['bday'] = null;
    $data[0]['bplace'] = '';
    $data[0]['nationality'] = '';
    $data[0]['haddr'] = '';
    $data[0]['htel'] = '';
    $data[0]['baddr'] = '';
    $data[0]['btel'] = '';
    $data[0]['guardian'] = '';
    $data[0]['gtel'] = '';

    $data[0]['elementary'] = '';
    $data[0]['eyear'] = '0';
    $data[0]['highschool'] = '';
    $data[0]['hyear'] = '0';
    $data[0]['college'] = '';
    $data[0]['cyear'] = '0';
    $data[0]['postyear'] = '';
    $data[0]['pyear'] = '0';

    $data[0]['company'] = '';
    $data[0]['accountant'] = '';
    $data[0]['email'] = '';

    $data[0]['isstudent'] = '1';
    $data[0]['isold'] = '0';
    $data[0]['isnew'] = '1';
    $data[0]['isforeign'] = '0';
    $data[0]['isadddrop'] = '0';
    $data[0]['islateenrollee'] = '0';
    $data[0]['istransferee'] = '0';
    $data[0]['iscrossenrollee'] = '0';
    $data[0]['isregular'] = '1';
    $data[0]['isirregular'] = '0';
    $data[0]['radioregular'] = 'R';
    $data[0]['radiostudent'] = 'N';

    $data[0]['curriculumtrno'] = 0;
    $data[0]['curriculumdocno'] = '';
    $data[0]['curriculumcode'] = '';
    $data[0]['curriculumname'] = '';
    $data[0]['schedtrno'] = 0;
    $data[0]['schedcode'] = '';
    $data[0]['assesstrno'] = 0;
    $data[0]['assesscode'] = '';
    $data[0]['regtrno'] = 0;
    $data[0]['regcode'] = '';

    $data[0]['section'] = '';
    $data[0]['sectionid'] = 0;
    $data[0]['yr'] = '';
    $data[0]['levelup'] = 0;
    $data[0]['levelupcode'] = '';
    $data[0]['chineselevelup'] = 0;
    $data[0]['chineselevelupcode'] = '';

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select clientid as value from client where isstudent=1 and center=? order by clientid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = "client.clientid, 
    (case when " . $this->headOther . ".isregular=1 then 'R' when " . $this->headOther . ".isirregular=1 then 'I' else 'A' end) as radioregular,
    (case when " . $this->headOther . ".isnew=1 then 'N' when " . $this->headOther . ".isold=1 then 'O' else 'A' end) as radiostudent,
    ec.docno as curriculumdocno, ec.curriculumcode, ec.curriculumname, sec.section, t.docno as assesscode, r.docno as regcode, s.docno as schedcode, " . $this->headOther . ".chinesename ";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',client.' . $value;
    }
    foreach ($this->fieldsOther as $key => $value) {
      $fields = $fields . ',' . $this->headOther . '.' . $value;
    }
    $qryselect = "select " . $fields . ', coursel.coursecode as levelupcode, coursechil.coursecode as chineselevelupcode, courseeng.coursecode as course, courseeng.coursename, coursechi.coursecode as chinesecourse, coursechi.coursename as chinesecoursename';

    $qry = $qryselect . " from client left join " . $this->headOther . " on " . $this->headOther . ".clientid=client.clientid
      left join en_glhead as ec on ec.trno=" . $this->headOther . ".curriculumtrno left join en_section as sec on sec.line= " . $this->headOther . ".sectionid
      left join transnum as t on t.trno=" . $this->headOther . ".assesstrno
      left join transnum as s on s.trno=" . $this->headOther . ".schedtrno
      left join cntnum as r on r.trno=" . $this->headOther . ".regtrno
      left join en_course as courseeng on courseeng.line=" . $this->headOther . ".courseid
      left join en_course as coursechi on coursechi.line=" . $this->headOther . ".chinesecourseid
      left join en_course as coursel on coursel.line=" . $this->headOther . ".levelup
      left join en_course as coursechil on coursechil.line=" . $this->headOther . ".chineselevelup
        where client.clientid = ? ";
    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['clientid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head[0]['clientid'] = 0;
      $head[0]['client'] = '';
      $head[0]['clientname'] = '';
      $head[0]['lname'] = '';
      // $head[0]['isold'] = 0;
      // $head[0]['isnew'] = 0;
      return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    $dataOther = [];
    if ($isupdate) {
      unset($this->fields['client']);
    }
    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (isset($head[$key])) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    foreach ($this->fieldsOther as $key) {
      if (isset($head[$key])) {
        $dataOther[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataOther[$key] = $this->othersClass->sanitizekeyfield($key, $dataOther[$key]);
        } //end if  
      }
    }
    if (isset($head['radioregular'])) {
      $dataOther['isregular'] = $head['radioregular'] == 'R' ? 1 : 0;
      $dataOther['isirregular'] = $head['radioregular'] == 'I' ? 1 : 0;
    }
    if (isset($head['radiostudent'])) {
      $dataOther['isnew'] = $head['radiostudent'] == 'N' ? 1 : 0;
      $dataOther['isold'] = $head['radiostudent'] == 'O' ? 1 : 0;
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    $data['clientname'] = $dataOther['lname'] . ', ' . $dataOther['fname'] . ' ' . $dataOther['mname'];


    if ($isupdate) {
      $isstudentidexist = $this->checkstudentid($dataOther['studentid'], $head['clientid']);
      if ($isstudentidexist) {
        $msg = "Cannot Save! StudentID already exist!";
        goto exithere;
      }

      $clientid = $head['clientid'];
      $schedtrno = $this->coreFunctions->datareader("select schedtrno as value from en_studentinfo where clientid=?", [$clientid]);
      if ($dataOther['schedtrno'] != $schedtrno) {
        $scheddata = $this->coreFunctions->opentable("select line,screfx,sclinex,subjectid,schedstarttime,schedendtime,roomid,bldgid,schedday,instructorid 
        from en_glsubject where trno=?", [$dataOther['schedtrno']]);
        foreach ($scheddata as $sch) {
          $this->coreFunctions->execqry("update glsubject set screfx=?,sclinex=?,schedstarttime=?,schedendtime=?,roomid=?,bldgid=?,schedday=?,instructorid=? 
          where trno=? and subjectid=?", 'update', [$dataOther['schedtrno'], $sch->line, $sch->schedstarttime, $sch->schedendtime, $sch->roomid, $sch->bldgid, $sch->schedday, $sch->instructorid, $dataOther['regtrno'], $sch->subjectid]);

          $this->coreFunctions->execqry("update glhead set sectionid=? where trno=?", 'update', [$dataOther['sectionid'], $dataOther['regtrno']]);

          $this->coreFunctions->execqry("update en_glsubject set refx=?,linex=?,schedstarttime=?,schedendtime=?,roomid=?,bldgid=?,schedday=?,instructorid=? 
          where trno=? and subjectid=?", 'update', [$dataOther['schedtrno'], $sch->line, $sch->schedstarttime, $sch->schedendtime, $sch->roomid, $sch->bldgid, $sch->schedday, $sch->instructorid, $dataOther['assesstrno'], $sch->subjectid]);

          $this->coreFunctions->execqry("update en_glhead set sectionid=? where trno=?", 'update', [$dataOther['sectionid'], $dataOther['assesstrno']]);
        }
      }
      $this->coreFunctions->sbcupdate('client', $data, ['clientid' => $head['clientid']]);
      $this->coreFunctions->sbcupdate($this->headOther, $dataOther, ['clientid' => $head['clientid']]);
    } else {
      $isstudentidexist = $this->checkstudentid($dataOther['studentid'], 0);
      if ($isstudentidexist) {
        $msg = "Cannot Save! StudentID already exist!";
        goto exithere;
      }
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['isstudent'] = 1;
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('client', $data);
      if ($clientid) {
        $dataOther['clientid'] = $clientid;
        $this->coreFunctions->sbcinsert($this->headOther, $dataOther);
      } else {
        $msg = "Failed to create.";
      }
      $this->logger->sbcwritelog($clientid, $config, 'CREATE', $clientid . ' - ' . $head['client'] . ' - ' . $data['clientname']);
    }
    exithere:
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
    
  } // end function

  private function checkstudentid($studentid, $clientid)
  {
    if ($clientid == 0) {
      $qry = "select clientid as value from en_studentinfo where studentid=?";
      $id = $this->coreFunctions->datareader($qry, [$studentid]);
    } else {
      $qry = "select clientid as value from en_studentinfo where studentid=? and clientid<>?";
      $id = $this->coreFunctions->datareader($qry, [$studentid, $clientid]);
    }

    if ($id == 0) {
      return false;
    } else {
      return true;
    }
  }

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select client as value from client where  isstudent=1 order by client desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select client as value from client where  isstudent=1 and left(client,?)=? order by client desc limit 1', [$length, $pref]);
    }
    return $return;
  }





  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $client = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$clientid]);
    // $qry = "select val from (
    //   select trno as val from en_addetail where clientid=? union all
    //   select trno as val from en_adhead where clientid=? union all
    //   select trno as val from en_atstudents where clientid=? union all
    //   select trno as val from en_gegrades where clientid=?
    // ) as v";
    // $count = $this->coreFunctions->datareader($qry, [$client, $client, $client, $clientid]);
    $qry = "select value from (
      select trno as value from en_sohead where client='".$client."' union all
      select trno as value from en_sjhead where client='".$client."' union all
      select trno as value from en_glhead where clientid=".$clientid." union all
      select trno as value from en_gegrades where clientid=".$clientid." union all
      select trno as value from en_glgrades where clientid=".$clientid." union all
      select trno as value from en_glstudents where clientid=".$clientid." union all
      select trno as value from en_atstudents where clientid=".$clientid." union all
      select trno as value from en_atstudents where clientid=".$clientid." union all
      select trno as value from en_adhead where client='".$client."' union all
      select trno as value from en_srcattendance where clientid=".$clientid." union all
      select line as value from en_studentcredentials where clientid=".$clientid."
    ) as v";
    $count = $this->coreFunctions->datareader($qry);
    if (($count != '')) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "select clientid as value from client where clientid<? and isemployee=1 order by clientid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from client where clientid=?', 'delete', [$clientid]);

    $this->logger->sbcdel_log($clientid, $config, $client);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function



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
    $fields = [
      'radioprint',
      'prepared',
      'approved',
      'received',
      'print'
    ];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
  ");
  }


  public function generateResult($config)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $clientid = md5($config['params']['dataid']);

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    // $query = "select client.client,client.clientname,client.addr,client.tel,
    // client.tel2,client.tin,client.mobile,client.rem,
    // client.email,client.contact,client.fax,client.start,client.status,client.quota,
    // client.area,client.province,client.region,client.groupid,client.issupplier,client.iscustomer,
    // client.isagent,client.isemployee
    // from client where md5(client.clientid)='" . $clientid . "'";
    $query = "select client.clientid, client.client, client.clientname, si.gender, co.coursecode, co.coursename,
        si.civilstatus, si.haddr, si.htel, si.baddr, si.btel, client.bday, si.bplace, si.nationality,
        si.extramural, si.guardian, si.gtel, si.elementary, si.eyear, si.highschool, si.hyear,
        si.college, si.cyear, client.picture
      from client
      left join en_studentinfo as si on si.clientid=client.clientid
      left join en_course as co on co.line=si.courseid
      where md5(client.clientid)='".$clientid."'";

    return $this->coreFunctions->opentable($query);
  }


  public function reportdata($config)
  {
    
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->reportplotting($config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->PDF_reportplotting($config);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function PDF_header($filters, $data)
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
    PDF::SetMargins(20, 20);

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
    PDF::MultiCell(800, 20, 'STUDENT PROFILE ', '', 'L', false);

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(800, 20, 'Run Date :' . date('M-d-Y h:i:s a', time()), '', 'L', false);
    // PDF::MultiCell(70, 20, 'Name :', '', 'L', false, 0);
    // PDF::SetFont($fontbold, '', 9);
    // PDF::MultiCell(730, 20, '(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false);
    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(800, 20, 'Address :' . (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false);
  }

  public function PDF_reportplotting($filters)
  {
    $data     = $this->generateResult($filters);
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 65;
    $page = 65;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_header($filters, $data);

    if ($data[0]->picture != null && $data[0]->picture != '') {
      PDF::Image(public_path().$data[0]->picture, 550, 110, 100, 100);
    } else {
      PDF::Image('images/demo/nouserimg.jpg', '', '', 100, 100);
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Student Code: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->client) ? $data[0]->client : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Student ID: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->clientid) ? $data[0]->clientid : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Name: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false);
    
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Gender: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->gender) ? $data[0]->gender : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Civil Status: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->civilstatus) ? $data[0]->civilstatus : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Course: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->coursecode) ? $data[0]->coursecode : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Course Name: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->coursename) ? $data[0]->coursename : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', '11');
    PDF::MultiCell(150, 0, 'PERSONAL INFORMATION', '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Home Address: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(660, 0, (isset($data[0]->haddr) ? $data[0]->haddr : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Home Tel No.: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(660, 0, (isset($data[0]->htel) ? $data[0]->htel : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Business Address: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(660, 0, (isset($data[0]->baddr) ? $data[0]->baddr : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Business No.: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(660, 0, (isset($data[0]->btel) ? $data[0]->btel : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Birthday: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->bday) ? $data[0]->bday : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Nationality: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->nationality) ? $data[0]->nationality : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Birth Place: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->bplace) ? $data[0]->bplace : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Extramural: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->extramural) ? $data[0]->extramural : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Guardian: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->guardian) ? $data[0]->guardian : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Guardian No.: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->gtel) ? $data[0]->gtel : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 0, 'EDUCATIONAL BACKGROUND', '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Elementary: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->elementary) ? $data[0]->elementary : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Year: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->eyear) ? ($data[0]->eyear <= 0 ? '' : $data[0]->eyear) : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Highschool: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->highschool) ? $data[0]->highschool : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Year: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->hyear) ? ($data[0]->hyear <= 0 ? '' : $data[0]->hyear) : ''), '', 'L', false);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'College: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->college) ? $data[0]->college : ''), '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Year: ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(280, 0, (isset($data[0]->cyear) ? ($data[0]->cyear <= 0 ? '' : $data[0]->cyear) : ''), '', 'L', false);


    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(250, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(3, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(3, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(250, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(3, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(3, 0, '', '', 'L', false, 0);
    PDF::MultiCell(250, 0, $filters['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_header($config, $data)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STUDENT - PROFILE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col('(' . (isset($data[0]->client) ? $data[0]->client : '') . ')' . '&nbsp;&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), '750', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportplotting($config)
  {
    $data     = $this->generateResult($config);
    $center   = $config['params']['center'];
    $username = $config['params']['user'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($config, $data);


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
} //end class
