<?php

namespace App\Http\Classes\modules\hris;

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

class applicantledger
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'APPLICANT LEDGER';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'app';
  public $contact = 'acontacts';
  public $prefix = 'AL';
  public $tablelogs = 'masterfile_log';
  public $statlogs = 'app_stat';
  public $tablelogs_del = 'del_client_log';
  private $stockselect;

  private $fields = ['empcode', 'emplast', 'empfirst', 'empfirst', 'empmiddle', 'hired', 'idno', 'appdate', 'address', 'city', 'country', 'telno', 'mobileno', 'citizenship', 'jobtitle', 'jobcode', 'jobdesc', 'maidname', 'bplace', 'child', 'jstatus', 'gender', 'ishired', 'remarks', 'bday', 'status', 'zipcode', 'email', 'religion', 'alias', 'type', 'mapp', 'jobid', 'statid', 'branchid', 'hqtrno'];

  private $contactfields = ['contact1', 'relation1', 'addr1', 'homeno1', 'mobileno1', 'officeno1', 'ext1', 'notes1', 'contact2', 'relation2', 'addr2', 'homeno2', 'mobileno2', 'officeno2', 'ext2', 'notes2'];
  private $except = ['empid', 'age', 'client', 'clientid'];
  private $blnfields = ['ishired'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  public $showfilterlabel = [];
  // public $showfilterlabel = [
  //   ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
  //   ['val' => 'preempexam', 'label' => 'Pre-Employment Exam', 'color' => 'primary'],
  //   ['val' => 'backgroundcheck', 'label' => 'Background Checking', 'color' => 'primary'],
  //   ['val' => 'finalinterview', 'label' => 'Final Interview', 'color' => 'primary'],
  //   ['val' => 'preempreq', 'label' => 'Hiring & Pre-Employment Requirements', 'color' => 'primary'],
  //   ['val' => 'empjoboffer', 'label' => 'For Job Offer', 'color' => 'primary'],
  //   ['val' => 'all', 'label' => 'All Applicants', 'color' => 'primary']
  // ];


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
      'view' => 1109,
      'edit' => 1110,
      'new' => 1111,
      'save' => 1112,
      'change' => 1113,
      'delete' => 1114,
      'print' => 1115,
      'post' => 1116,
      'unpost' => 1117,
      'lock' => 1670,
      'unlock' => 1671,
      'preempexam' => 5201,
      'backgroundcheck' => 5202,
      'finalinterview' => 5203,
      'preempreq' => 5204,
      'empjoboffer' => 5205
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    switch ($config['params']['companyid']) {
      case 58: //cdo
      case 25:
        $this->showfilterlabel = [
          ['val' => 'draft', 'label' => 'Pre-Screening', 'color' => 'primary'],
          ['val' => 'preempexam', 'label' => 'Pre-Employment Exam', 'color' => 'primary'],
          ['val' => 'backgroundcheck', 'label' => 'Background Checking', 'color' => 'primary'],
          ['val' => 'finalinterview', 'label' => 'Final Interview', 'color' => 'primary'],
          ['val' => 'empjoboffer', 'label' => 'For Job Offer', 'color' => 'primary'],
          ['val' => 'preempreq', 'label' => 'Hiring & Pre-Employment Requirements', 'color' => 'primary'],
          ['val' => 'all', 'label' => 'All Applicants', 'color' => 'primary']
        ];
        break;
    }


    $getcols = ['action', 'listempcode', 'listempname', 'listapplied', 'listjobapplied', 'listappstatus', 'branch'];
    $stockbuttons = ['view'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:5px;whiteSpace: normal;min-width:5px;';
    $cols[$listempcode]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;text-align:left';
    $cols[$listempname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;text-align:left';
    $cols[$listapplied]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:left';
    $cols[$listjobapplied]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;text-align:left';
    $cols[$listappstatus]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;text-align:left';
    $cols[$branch]['style'] = 'width:450px;whiteSpace: normal;min-width:450px;text-align:left';

    $cols[$listempcode]['label'] = 'Applicant Code';

    if ($config['params']['companyid'] != 58) {
      $cols[$branch]['type'] = 'coldel';
    }

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    $filter = '';

    switch ($config['params']['companyid']) {
      case 58: //cdo
      case 25:
        $option = $config['params']['itemfilter'];

        if ($config['params']['date1'] == 'Invalid date') {
          $config['params']['date1'] =  $config['params']['date2'];
        }

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));

        switch ($option) {
          case 'draft':
            $filter = " and date(app.appdate) between '$date1' and '$date2' and app.statid = 0 and app.jstatus = ''";
            break;
          case 'preempexam':
            $filter = " and date(app.appdate) between '$date1' and '$date2' and app.statid = 98 and app.jstatus = ''";
            break;
          case 'backgroundcheck':
            $filter = " and date(app.appdate) between '$date1' and '$date2' and app.statid = 99 and app.jstatus = ''";
            break;
          case 'finalinterview':
            $filter = " and date(app.appdate) between '$date1' and '$date2' and app.statid = 100 and app.jstatus = ''";
            break;
          case 'preempreq':
            $filter = " and date(app.appdate) between '$date1' and '$date2' and app.statid = 101";
            break;
          case 'empjoboffer':
            $filter = " and date(app.appdate) between '$date1' and '$date2' and app.statid = 102 and app.jstatus = ''";
            break;
        }
        break;
    }


    if (isset($config['params']['search'])) {
      $searchfield = ['empcode', 'emplast', 'empfirst', 'empmiddle', 'jobtitle', 'jstatus'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $qry = "select app.empid as clientid,app.empid,app.empcode,concat(app.emplast,', ',app.empfirst,' ',app.empmiddle) as empname,
                  app.jobtitle, date(app.appdate) as appdate, app.jstatus, br.clientname as branch
                  from app left join client as br on br.clientid=app.branchid
                  where 1=1 " . $filtersearch . " $filter
                  order by empname";

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
    $fields = [['jobcode', 'jobtitle'], 'jobdesc', 'mapp'];
    if ($config['params']['companyid'] == 58) { //cdo
      array_push($fields, 'dbranchname');
    } else {
      array_push($fields, 'hqdocno');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'jobdesc.type', 'ctextarea');
    data_set($col1, 'mapp.type', 'ctextarea');
    data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');

    if ($config['params']['companyid'] == 58) { //cdo
      data_set($col1, 'dbranchname.name', 'branchname');
      data_set($col1, 'dbranchname.addedparams', ['jobid']);
    }

    $fields = ['contact', 'relation', 'addr', ['homeno', 'mobileno'], ['officeno', 'ext1'], 'notes'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'contact.name', 'contact1');
    data_set($col2, 'contact.label', 'Contact Person 1');
    data_set($col2, 'relation.name', 'relation1');
    data_set($col2, 'addr.name', 'addr1');
    data_set($col2, 'homeno.name', 'homeno1');
    data_set($col2, 'mobileno.name', 'mobileno1');
    data_set($col2, 'officeno.name', 'officeno1');
    data_set($col2, 'notes.name', 'notes1');

    data_set($col2, 'contact.type', 'cinput');
    data_set($col2, 'relation.type', 'cinput');
    data_set($col2, 'addr.type', 'cinput');
    data_set($col2, 'homeno.type', 'cinput');
    data_set($col2, 'mobileno.type', 'cinput');
    data_set($col2, 'officeno.type', 'cinput');
    data_set($col2, 'ext1.type', 'cinput');
    data_set($col2, 'notes.type', 'ctextarea');

    $fields = ['contact', 'relation', 'addr', ['homeno', 'mobileno'], ['officeno', 'ext1'], 'notes'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'contact.name', 'contact2');
    data_set($col3, 'contact.label', 'Contact Person 2');
    data_set($col3, 'relation.name', 'relation2');
    data_set($col3, 'addr.name', 'addr2');
    data_set($col3, 'homeno.name', 'homeno2');
    data_set($col3, 'mobileno.name', 'mobileno2');
    data_set($col3, 'officeno.name', 'officeno2');
    data_set($col3, 'notes.name', 'notes2');
    data_set($col3, 'ext1.name', 'ext2');

    data_set($col3, 'contact.type', 'cinput');
    data_set($col3, 'relation.type', 'cinput');
    data_set($col3, 'addr.type', 'cinput');
    data_set($col3, 'homeno.type', 'cinput');
    data_set($col3, 'mobileno.type', 'cinput');
    data_set($col3, 'officeno.type', 'cinput');
    data_set($col3, 'ext1.type', 'cinput');
    data_set($col3, 'notes.type', 'ctextarea');

    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3], 'label' => 'CONTACT PERSON INFO']
    ];

    $tab['stathistorytab'] = ['action' => 'tableentry', 'lookupclass' => 'tabrecruitprocess', 'label' => 'Status', 'checkchanges' => 'tableentry'];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createTab2($acccess, $config)
  {
    $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'entryappdependents', 'label' => 'DEPENDENTS']];
    $dependants = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'entryappeducation', 'label' => 'EDUCATION']];
    $education = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'entryappemployment', 'label' => 'EMPLOYMENT']];
    $employment = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'entryappreq', 'label' => 'REQUIREMENTS']];
    $requirements = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'hrisentry', 'lookupclass' => 'entryapptest', 'label' => 'PRE-EMPLOYMENT TEST']];
    $pre_employment_test = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryapppicture', 'label' => 'Attachment', 'access' => 'view']];
    $attach = $this->tabClass->createtab($tab, []);
    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $attach];

    $return['DEPENDENTS'] = ['icon' => 'fa fa-envelope', 'tab' => $dependants];
    $return['EDUCATION']  = ['icon' => 'fa fa-envelope', 'tab' => $education];
    $return['EMPLOYMENT']  = ['icon' => 'fa fa-envelope', 'tab' => $employment];
    $return['REQUIREMENTS'] = ['icon' => 'fa fa-envelope', 'tab' => $requirements];
    $return['PRE EMPLOYMENT TEST']   = ['icon' => 'fa fa-envelope', 'tab' => $pre_employment_test];

    if ($config['params']['companyid'] == 58) {
      $administrator = $this->othersClass->checkAccess($config['params']['user'], 2580);
      if ($administrator) {
        $user = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewappuseraccount']];
        $return['USER ACCOUNT'] = ['icon' => 'fa fa-user', 'customform' => $user];
      }
    }
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
    $fields = ['client', 'emplast', 'empfirst', 'empmiddle', 'remarks'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.class', 'csclient sbccsenablealways');
    data_set($col1, 'client.lookupclass', 'lookupapplicant');
    data_set($col1, 'client.action', 'lookupledgerclient');
    data_set($col1, 'client.label', 'Applicant Code');
    data_set($col1, 'client.name', 'client');

    data_set($col1, 'emplast.type', 'cinput');
    data_set($col1, 'empfirst.type', 'cinput');
    data_set($col1, 'empmiddle.type', 'cinput');
    data_set($col1, 'remarks.type', 'cinput');

    $fields = ['idno', 'address', 'atype', 'jstatus', ['appdate', 'hired']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'idno.label', 'Employee No.');
    data_set($col2, 'idno.class', 'csidno sbccsreadonly');
    data_set($col2, 'address.type', 'cinput');
    data_set($col2, 'hired.class', 'cshired sbccsreadonly');
    data_set($col2, 'jstatus.type', 'input');
    data_set($col2, 'jstatus.readonly', true);


    $fields = [['city', 'country'], ['citizenship', 'religion'], ['telno', 'mobileno'], 'zipcode', 'maidname'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'city.type', 'cinput');
    data_set($col3, 'country.type', 'cinput');
    data_set($col3, 'citizenship.type', 'cinput');
    data_set($col3, 'religion.type', 'cinput');
    data_set($col3, 'telno.type', 'cinput');
    data_set($col3, 'mobileno.type', 'cinput');
    data_set($col3, 'zipcode.type', 'cinput');
    data_set($col3, 'maidname.type', 'cinput');

    $fields = [['bday', 'bplace'], ['age', 'gender'], 'alias', 'email', ['mstatus', 'child']];

    switch ($config['params']['companyid']) {
      case 58: //cdo
        array_push($fields, 'preempexam', 'backgroundcheck', 'finalinterview', 'preempreq', 'empjoboffer');
        break;
    }
    if ($config['params']['companyid'] == 58) { //cdo

    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'bplace.type', 'cinput');
    data_set($col4, 'age.type', 'cinput');
    data_set($col4, 'alias.type', 'cinput');
    data_set($col4, 'email.type', 'cinput');
    data_set($col4, 'child.type', 'cinput');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function newclient($config)
  {
    $data = [];
    $data[0]['empid'] = 0;
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $config['newclient'];
    $data[0]['empcode'] = $config['newclient'];

    $data[0]['empfirst'] = '';
    $data[0]['emplast'] = '';
    $data[0]['empmiddle'] = '';
    $data[0]['remarks'] = '';

    $data[0]['type'] = '';
    $data[0]['jstatus'] = '';
    $data[0]['idno'] = '';
    $data[0]['address'] = '';
    $data[0]['hired'] = null;
    $data[0]['appdate'] = $this->othersClass->getCurrentDate();

    $data[0]['city'] = '';
    $data[0]['country'] = '';
    $data[0]['citizenship'] = '';
    $data[0]['religion'] = '';
    $data[0]['telno'] = '';
    $data[0]['maidname'] = '';
    $data[0]['mobileno'] = '';
    $data[0]['alias'] = '';
    $data[0]['gender'] = '';
    $data[0]['status'] = '';

    $data[0]['jobid'] = 0;
    $data[0]['jobtitle'] = '';
    $data[0]['jobcode'] = '';
    $data[0]['jobdesc'] = '';

    $data[0]['bday'] = null;
    $data[0]['bplace'] = '';
    $data[0]['age'] = 0;
    $data[0]['email'] = '';
    $data[0]['child'] = 0;
    $data[0]['zipcode'] = '';

    $data[0]['mapp'] = '';
    $data[0]['contact1'] = '';
    $data[0]['relation1'] = '';
    $data[0]['addr1'] = '';
    $data[0]['homeno1'] = '';
    $data[0]['mobileno1'] = '';
    $data[0]['officeno1'] = '';
    $data[0]['notes1'] = '';
    $data[0]['ext1'] = '';

    $data[0]['contact2'] = '';
    $data[0]['relation2'] = '';
    $data[0]['addr2'] = '';
    $data[0]['homeno2'] = '';
    $data[0]['mobileno2'] = '';
    $data[0]['officeno2'] = '';
    $data[0]['notes2'] = '';
    $data[0]['ext2'] = '';

    $data[0]['hqtrno'] = 0;
    $data[0]['branchid'] = 0;
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';

    $data[0]['isapplicant'] = '1';


    $hideobj = [];

    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger', 'hideobj' => $hideobj];
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    if ($clientid == 0) {
      $clientid = $this->othersClass->readprofile($doc, $config);
      if ($clientid == 0) {
        $clientid = $this->coreFunctions->datareader("select empid as value from app where  center=? order by empid desc limit 1", [$center]);
      }
      $config['params']['clientid'] = $clientid;
    } else {
      $this->othersClass->checkprofile($doc, $clientid, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $fields = "app.empcode as client,app.empid as clientid,app.empid,'' as atype, year(now())-year(app.bday) as age,'' as djobtitle,
    ifnull(branch.clientname,'') as branchname,ifnull(branch.client,'') as branchcode,req.docno as hqdocno,app.hqtrno";
    foreach ($this->fields as $key => $value) {
      $fields = $fields . ',app.' . $value;
    }

    foreach ($this->contactfields as $key => $value) {
      $fields = $fields . ',' . $this->contact . '.' . $value;
    }

    $qryselect = "select " . $fields;

    $qry = $qryselect . " from app left join " . $this->contact . " on " . $this->contact . ".empid=app.empid
        left join client as branch on branch.clientid = app.branchid
        left join hpersonreq as req on req.trno = app.hqtrno and req.job = app.jobcode
        where app.empid = ? ";

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
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['empid' => $clientid]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      ////////////////

      $hideobj = [];

      switch ($config['params']['companyid']) {
        case 58: //cdo
          if ($head[0]->jstatus == "" && $head[0]->statid == 0) {
            $hideobj['preempexam'] = false;
          } else {
            $hideobj['preempexam'] = true;
          }

          $hideobj['backgroundcheck'] = true;
          $hideobj['finalinterview'] = true;
          $hideobj['preempreq'] = true;
          $hideobj['empjoboffer'] = true;

          switch ($head[0]->statid) {
            case 98: //preempexam
              $hideobj['preempexam'] = true;
              $hideobj['backgroundcheck'] = false;
              break;
            case 99: //backgroundcheck
              $hideobj['backgroundcheck'] = true;
              $hideobj['finalinterview'] = false;
              break;
            case 100: //finalinterview
              $hideobj['finalinterview'] = true;
              $hideobj['empjoboffer'] = false;
              break;
            case 102: //preempreq
              $hideobj['preempreq'] = false;
              break;
          }
          break;
      }

      ///////////////
      return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj];
    } else {
      $head[0]['empid'] = 0;
      $head[0]['clientid'] = 0;
      $head[0]['empcode'] = '';
      $head[0]['client'] = '';
      $head[0]['emplast'] = '';
      $head[0]['ishired'] = 0;
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
    $msg  = '';
    foreach ($this->fields as $key) {
      if (isset($head[$key])) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    foreach ($this->contactfields as $key) {
      if (isset($head[$key])) {
        $dataOther[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataOther[$key] = $this->othersClass->sanitizekeyfield($key, $dataOther[$key]);
        } //end if  
      }
    }

    $dataOther['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $dataOther['editby'] = $config['params']['user'];

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate('app', $data, ['empid' => $head['empid']]);
      $exist = $this->coreFunctions->getfieldvalue($this->contact, "empid", "empid=?", [$head['empid']]);
      if (floatval($exist) != 0) {
        $this->coreFunctions->sbcupdate($this->contact, $dataOther, ['empid' => $head['empid']]);
      } else {
        $dataOther['empid'] = $head['empid'];
        $this->coreFunctions->sbcinsert($this->contact, $dataOther);
      }

      $clientid = $head['empid'];
      $empid = $head['empid'];
    } else {
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $data['center'] = $center;
      $clientid = $this->coreFunctions->insertGetId('app', $data);
      if ($clientid) {
        $dataOther['empid'] = $clientid;
        $this->coreFunctions->sbcinsert($this->contact, $dataOther);
      }
      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'CREATE - ',
        $clientid . ' - CODE: ' . $head['empcode']
          . ' - NAME: ' . $data['emplast'] . ',' . $data['empfirst'] . ' ' . $data['empmiddle']
          . ' , STATUS: ' . $data['jstatus']
          . ' , JOB DESC: ' . $data['jobdesc']
      );
    }

    if ($data['hqtrno'] != 0) {
      $this->coreFunctions->execqry("update hpersonreq set isapplied=1 where trno=" . $data['hqtrno']);
    }


    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select empcode as value from app  order by empcode desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select empcode as value from app where  left(empcode,?)=? order by empcode desc limit 1', [$length, $pref]);
    }
    return $return;
  }


  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $empcode = $this->coreFunctions->getfieldvalue('app', 'empcode', 'empid=?', [$clientid]);
    $ishired = $this->coreFunctions->getfieldvalue('app', 'ishired', 'empid=?', [$clientid]);

    $qry = "
      select trno as value from joboffer where empid=?
      union all
      select trno as value from hjoboffer where empid=?
    ";
    $count = $this->coreFunctions->datareader($qry, [$clientid, $clientid], '', true);

    if (($count != 0)) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction.'];
    }

    if (floatval($ishired != 0)) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already hired.'];
    }

    $qry = "select empid as value from app where empid<? order by empid desc limit 1 ";
    $clientid2 = $this->coreFunctions->datareader($qry, [$clientid]);
    $this->coreFunctions->execqry('delete from app where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from acontacts where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from adependents where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from aeducation where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from aemployment where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from arequire where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from apreemploy where empid=?', 'delete', [$clientid]);
    $this->coreFunctions->execqry('delete from app_stat where trno=?', 'delete', [$clientid]);
    $this->logger->sbcdel_log($clientid, $config, $empcode);
    return ['clientid' => $clientid2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'preempexam':
        return $this->preempexam($config);
        break;
      case 'empjoboffer':
        return $this->empjoboffer($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function preempexam($config)
  {
    if ($this->coreFunctions->sbcupdate($this->head, ['statid' => 98], ['empid' => $config['params']['trno']])) {
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Tag for Pre-Employment Exam.');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for the Pre-Employment Exam.'];
    }
  }

  public function empjoboffer($config)
  {
    if ($this->coreFunctions->sbcupdate($this->head, ['statid' => 102], ['empid' => $config['params']['trno']])) {
      $this->logger->sbcstatlog($config['params']['trno'], $config, 'HEAD', 'Tag for Job Offer.');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for the Job Offer.'];
    }
  }

  // report startto

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

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
