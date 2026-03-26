<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\customerservice\ca;
use App\Http\Classes\SBCPDF;

class hj
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'JOB OFFER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'joboffer';
  public $hhead = 'hjoboffer';
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
    'emptitle',
    'effectdate',
    'classrate',
    'rate',
    'empstat',
    'monthsno',
    'empname',
    'jobtitle',
    'dcode',
    'dname',
    'deptid',
    'sectid',
    'paygroupid',
    'roleid',
    'paymode',
    'branchid',
    'supervisorid',
    'empno'
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
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1250,
      'edit' => 1251,
      'new' => 1252,
      'save' => 1253,
      'delete' => 1255,
      'print' => 1256,
      'post' => 1257,
      'lock' => 1713,
      'unlock' => 1714,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'empcode', 'empname'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;text-align:left';
    $cols[$listdocument]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;';
    $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$empcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$empname]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
    $cols[$empcode]['label'] = "Applicant Code";
    $cols[$empname]['label'] = "Applicant Name";
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
      $searchfield = ['h.docno', 'c.empcode', 'c.emplast', 'c.empfirst', 'c.empmiddle'];
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
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, c.empcode as empcode, concat(c.emplast,', ',empfirst,' ',empmiddle) empname, 'DRAFT' as status
    from " . $this->head . " as h 
    left join app as c on c.empid=h.empid left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, c.empcode as empcode, concat(c.emplast,', ',empfirst,' ',empmiddle) empname, 'POSTED' as status
    from " . $this->hhead . " as h left join app as c on c.empid=h.empid left join " . $this->tablenum . " as num on num.trno=h.trno
    where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
    data_set($buttons, 'unpost.disable', true);
    return $buttons;
  } // createHeadbutton 

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];

    if ($config['params']['companyid'] != 58) { //not cdo
      $multiallow = $this->companysetup->multiallow($config['params']);
      if ($multiallow) {
        $tab = [
          'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrymultiallowance', 'label' => 'ALLOWANCE']
        ];
      }
    }

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
    $fields = ['docno', 'dateid', 'effectdate', 'classrate', 'paymode', 'rate', 'monthsno',];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'classrate.type', 'lookup');
    data_set($col1, 'classrate.action', 'lookupclassrate');
    data_set($col1, 'classrate.lookupclass', 'lookupclassrate');
    data_set($col1, 'paymode.lookupclass', 'lookuppaymode');
    data_set($col1, 'rate.type', 'cinput');
    data_set($col1, 'monthsno.type', 'cinput');
    data_set($col1, 'monthsno.label', 'No. of Months (If Contractual)');


    $fields = ['empcode', 'empname', 'jobcode', 'jobtitle', 'empdesc', 'tpaygroup'];
    if ($config['params']['companyid'] == 58) { //cdo
      array_push($fields, 'dbranchname');
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'empcode.action', 'lookupapplicanthj');
    data_set($col2, 'empcode.label', 'Applicant Code');
    data_set($col2, 'empname.label', 'Applicant Name');
    data_set($col2, 'empname.name', 'empname');
    data_set($col2, 'empdesc.lookupclass', 'jostatus');
    data_set($col2, 'empdesc.label', 'Employment Status');
    data_set($col2, 'jobcode.label', 'Job Code');
    data_set($col2, 'jobcode.name', 'emptitle');
    data_set($col2, 'empcode.required', true);
    if ($config['params']['companyid'] == 58) { //cdo
      data_set($col2, 'dbranchname.required', true);
    }

    data_set($col2, 'jobtitle.class', 'csjobtitle sbccsreadonly');

    $fields = ['rolename', 'joddivname', 'deptname', 'section', 'supervisor', 'empno'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'section.name', 'sectname');
    data_set($col3, 'joddivname.readonly', 'cssection sbccsreadonly');
    data_set($col3, 'joddivname.type', 'cinput');
    data_set($col3, 'section.class', 'cssection sbccsreadonly');
    data_set($col3, 'section.type', 'cinput');
    data_set($col3, 'supervisor.class', 'cssupervisor sbccsreadonly');
    data_set($col3, 'supervisor.type', 'cinput');
    data_set($col3, 'section.required', false);
    data_set($col3, 'empno.type', 'cinput');
    data_set($col3, 'empno.class', 'csempno sbccsreadonly');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
    $data[0]['empname'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['effectdate'] = $this->othersClass->getCurrentDate();
    $data[0]['classrate'] = '';
    $data[0]['rate'] = '';
    $data[0]['dcode'] = '';
    $data[0]['emptitle'] = '';
    $data[0]['empstat'] = 0;
    $data[0]['empdesc'] = '';
    $data[0]['dname'] = '';
    $data[0]['monthsno'] = 0;
    $data[0]['empno'] = '';
    $data[0]['jobtitle'] = '';
    $data[0]['jobdesc'] = '';
    $data[0]['joddivname'] = '';

    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['paygroupid'] = 0;
    $data[0]['tpaygroup'] = '';
    $data[0]['sectname'] = '';
    $data[0]['sectid'] = 0;
    $data[0]['roleid'] = 0;
    $data[0]['rolename'] = '';
    $data[0]['paymode'] = '';

    $data[0]['branchid'] = 0;
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';

    $data[0]['supervisorid'] = 0;
    $data[0]['supervisor'] = '';
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
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc='HJ' and center=? order by trno desc limit 1", [$doc, $center]);
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
        num.trno, num.docno, head.empid, app.empcode, head.dateid, head.emptitle, head.effectdate, head.classrate, 
        head.rate, head.empstat, head.monthsno, head.empname,
        jt.jobtitle as jobtitle, head.empno, head.nodep, head.dcode, head.dname, app.jobcode as jobcode,
        ifnull(dept.clientid, 0) as deptid, dept.client as dept, dept.clientname as deptname, ifnull(section.sectid, 0) as sectid,
        section.sectname, section.sectcode, pgroup.paygroup as tpaygroup, ifnull(pgroup.line, 0) as paygroupid,
        empstat.empstatus as empdesc, ifnull(head.roleid, 0) as roleid, role.name as rolename, 
        divs.divname as joddivname,head.paymode,
        ifnull(branch.clientname,'') as branchname,ifnull(branch.client,'') as branchcode, head.branchid, 
        head.supervisorid, sup.clientname as supervisor,head.empno

    ";
    $qry = $qryselect . " from " . $table . " as head
    left join client as cl on cl.clientid=head.empid
    left join $tablenum as num on num.trno = head.trno
    left join app on app.empid = head.empid 
    left join client as dept on head.deptid=dept.clientid
    left join paygroup as pgroup on pgroup.line=head.paygroupid
    left join section as section on section.sectid=head.sectid
    left join empstatentry as empstat on empstat.line = head.empstat
    left join rolesetup as role on role.line = head.roleid
    left join division as divs on divs.divid = role.divid
    left join jobthead as jt on jt.docno = head.emptitle
    left join client as branch on branch.clientid = head.branchid
    left join client as sup on sup.clientid = head.supervisorid
    where num.trno = ? and num.doc='HJ' and num.center=? 
    union all " . $qryselect . " from " . $htable . " as head
    left join client as cl on cl.clientid=head.empid   
    left join $tablenum as num on num.trno = head.trno
    left join app on app.empid = head.empid
    left join client as dept on head.deptid=dept.clientid
    left join paygroup as pgroup on pgroup.line=head.paygroupid
    left join section as section on section.sectid=head.sectid
    left join empstatentry as empstat on empstat.line = head.empstat
    left join rolesetup as role on role.line = head.roleid
    left join division as divs on divs.divid = role.divid
    left join jobthead as jt on jt.docno = head.emptitle
     left join client as branch on branch.clientid = head.branchid
     left join client as sup on sup.clientid = head.supervisorid
    where num.trno = ? and num.doc='HJ' and num.center=? ";

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
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

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
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $otherdata = [];
    $clientid = 0;

    try {
      $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
      $msg = '';
      $qry = "insert into hjoboffer (trno, docno, empid, dateid, emptitle, effectdate, classrate, rate, empstat, 
          monthsno, empname, jobtitle, empno, nodep, dcode, dname, createdate, editdate, createby, editby, lockuser,
          deptid, paygroupid, sectid, roleid,paymode,branchid,supervisorid)
          select trno, docno, empid, dateid, emptitle, effectdate, classrate, rate, empstat, monthsno, empname, jobtitle,
          empno, nodep, dcode, dname, createdate, editdate, createby, editby, lockuser, deptid, paygroupid, sectid, roleid,paymode,branchid,supervisorid
          from joboffer where trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

      if ($result === 1) {
        $data = $this->coreFunctions->opentable("
        select concat(app.emplast,', ',app.empfirst,' ',app.empmiddle) AS clientname, app.address, hj.empno, 
              hj.empstat, hj.classrate, hj.effectdate, hj.dcode, hj.jobtitle, hj.empid,
              hj.dateid, group_concat(jb.description,'') AS jobdesc, ac.contact1 as contact, 
              app.mobileno, jt.docno as jobcode, hj.rate,hj.deptid, hj.paygroupid, hj.sectid,
              app.empcode,hj.docno, hj.roleid, jt.line as jobid,hj.paymode,hj.branchid, 
              empstat.empstatus as empdesc,hj.supervisorid
        from hjoboffer AS hj
        left join app ON app.empid = hj.empid
        left join acontacts AS ac ON ac.empid = app.empid 
        left join jobthead AS jt ON hj.emptitle = jt.docno
        left join jobtdesc AS jb ON jt.line = jb.trno
        left join empstatentry as empstat on empstat.line = hj.empstat
        where hj.trno = $trno
        group by hj.empid,clientname, app.address, hj.empno, hj.empstat, hj.classrate, 
                 hj.effectdate, hj.dcode, hj.jobtitle, hj.dateid, contact, 
                 app.mobileno, rate, deptid, paygroupid, sectid,jt.docno,app.empcode,hj.docno, 
                 hj.roleid, jt.line,hj.paymode,hj.branchid,empstat.empstatus,hj.supervisorid");

        if ($data[0]->classrate == 'Daily') {
          $classrate = 'D';
        } else {
          $classrate = 'M';
        }

        switch ($data[0]->paymode) {
          case 'Daily':
            $paymode = 'D';
            break;
          case 'Monthly':
            $paymode = 'M';
            break;
          case 'Weekly':
            $paymode = 'W';
            break;
          case 'Semi-monthly':
            $paymode = 'S';
            break;
          default:
            $paymode = 'P';
            break;
        }

        $pref = $this->coreFunctions->datareader("select ifnull(psection, '') as value from profile where doc = 'SED' and pvalue = 'EM' LIMIT 1");
        if ($pref == "") {
          $pref = app("App\Http\Classes\modules\payroll\\employee")->prefix;
        }
        $newempcode = $this->getnewclient($config, $pref);
        $clientname = $data[0]->clientname;
        $address    = $data[0]->address;
        $effectdate    = $data[0]->effectdate;
        $contact    = $data[0]->contact;
        $mobileno    = $data[0]->mobileno;
        $empid    = $data[0]->empid; // app empid
        $empstat    = $data[0]->empstat;
        $empdesc    = $data[0]->empdesc;
        $dcode    = $data[0]->dcode;
        $rate   = $data[0]->rate;
        $dateid   = $data[0]->dateid;
        $jobid   = $data[0]->jobid;
        $jobcode   = $data[0]->jobcode;
        $jobtitle   = $data[0]->jobtitle;
        $jobdesc   = $data[0]->jobdesc;
        $deptid   = $data[0]->deptid;
        $paygroupid   = $data[0]->paygroupid;
        $sectid   = $data[0]->sectid;
        $appcode   = $data[0]->empcode;
        $roleid   = $data[0]->roleid;
        $branchid   = $data[0]->branchid;
        $supervisorid   = $data[0]->supervisorid;

        $divid = $this->coreFunctions->datareader("select divid as value from division where divcode = ? LIMIT 1", [$dcode]);
        if (empty($divid)) {
          $divid = 0;
        }

        $paygroup = $paygroupid;

        $insclient = "insert into client (client, clientname, addr, start, contact, tel2, rem, type, isemployee) 
                    values (?,?,?,?,?,?,'New hired', '', 1) ";

        if ($this->coreFunctions->execqry($insclient, 'insert', [$newempcode, $clientname, $address, $effectdate, $contact, $mobileno])) {
          $updateheadHJ = "update hjoboffer set empno='" . $newempcode . "' where trno = ? ";
          $this->coreFunctions->execqry($updateheadHJ, "update", [$trno]);

          $client = $this->coreFunctions->opentable('select client, clientid from client where client = ?', [$newempcode]);
          $clientid    = $client[0]->clientid;
          $client    = $client[0]->client;
          $shiftid = 0;

          $shiftid = $this->coreFunctions->datareader("select line as value from tmshifts where isdefault=1 LIMIT 1", [], '', true);
          if (empty($shiftid)) {
            $shiftid = 0;
          }

          $ins = "insert into employee (empid,emplast, empfirst, empmiddle, address, city, country, zipcode, telno, mobileno, email, 
                  citizenship, religion, alias, bday, jobtitle, jobcode, jobdesc, maidname, appdate, remarks, status,gender,aplcode,aplid,
                  isactive,hired,empstatus,classrate,mapp,level,emptype,division,nochild,teu, jobid, sectid, divid, deptid,paygroup,roleid, 
                  supervisorid,paymode,branchid,shiftid,branchid2,roleid2,jobid2,supervisorid2,divid2,deptid2,sectid2)
                
                  select '" . $clientid . "' as a, emplast, empfirst, empmiddle, address, city, country, zipcode, telno, 
                  mobileno, email, citizenship, religion, alias, bday, '" . $jobtitle . "', '" . $jobcode . "', '" . $jobdesc . "', 
                  maidname, appdate, remarks, status,
                  gender,'" . $appcode . "',empid,1,'" . $effectdate . "' as c,'" . $empstat . "' as d, '" . $classrate . "' as e, ifnull(mapp,''), 10, ifnull(type,''), 
                  '" . $dcode . "' as f, ifnull(child,''),(case when status='MARRIED' then 'M' else 'S' end) as sta, '" . $jobid . "', '" . $sectid . "', '" . $divid . "', '" . $deptid . "',
                  '" . $paygroup . "', " . $roleid . ", '" . $supervisorid . "', '" . $paymode . "', '" . $branchid . "', '" . $shiftid . "', '" . $branchid . "', " . $roleid . ", '" . $jobid . "',
                  '" . $supervisorid . "', '" . $divid . "', '" . $deptid . "', '" . $sectid . "'
                  from app where empid = ? ";

          $insertemplyee =  $this->coreFunctions->execqry($ins, 'insert', [$empid]);
          if ($insertemplyee) {
            $ins2 = "insert into contacts (empid, contact1, relation1, addr1, homeno1, mobileno1, officeno1, ext1, notes1, contact2, relation2, addr2, homeno2, mobileno2, officeno2, ext2, notes2)  
                select '" . $clientid . "' as a,  contact1, relation1, addr1, homeno1, mobileno1, officeno1, ext1,
                notes1, contact2, relation2, addr2, homeno2, mobileno2, officeno2, ext2, notes2 from acontacts 
                where empid = ?";
            $this->coreFunctions->execqry($ins2, 'insert', [$empid]);

            $ins3 = "insert into dependents (empid,  name, relation, bday, taxin) select '" . $clientid . "' as a,  name, relation, bday, taxin from adependents where empid = ? ";
            $this->coreFunctions->execqry($ins3, 'insert', [$empid]);

            $ins4 = "insert into education (empid, school, address, course, sy, gpa, honor) select '" . $clientid . "' as a, school, address, course, sy, gpa, honor from aeducation where empid = ? ";
            $this->coreFunctions->execqry($ins4, 'insert', [$empid]);

            $ins5 = "insert into employment (empid,company, jobtitle, period, address, salary, reason)  select '" . $clientid . "' as a,  company, jobtitle, period, address, salary, reason from aemployment where empid = ? ";
            $this->coreFunctions->execqry($ins5, 'insert', [$empid]);

            $ins6 = "insert into ratesetup (dateid,dateeffect, dateend, empid, remarks, basicrate, type, hjtrno) values ( '" . $dateid . "','" . $effectdate . "','9999-12-31'," . $clientid . ",'" . $data[0]->docno . "'," . $rate . ",'" . $classrate . "'," . $trno . ")";
            $this->coreFunctions->execqry($ins6, 'insert', [$empid]);

            $upd = "update app set ishired=1, idno='" . $client . "',hired='" . $effectdate . "' where empid = ? ";
            $this->coreFunctions->execqry($upd, "update", [$empid]);

            $upd3 = "update app set jobcode='" . $jobcode . "', jobdesc='" . $jobdesc . "',jobtitle='" . $jobtitle . "' where empid= ? ";
            $this->coreFunctions->execqry($upd3, "update", [$empid]);

            if (!empty($empstat)) {
              $upd4 = "update employee set empstatdate='" .  $this->othersClass->getCurrentTimeStamp() . "' where empid= ? ";
              $this->coreFunctions->execqry($upd4, "update", [$clientid]);
            }

            if (!empty($jobtitle)) {
              $upd5 = "update employee set jobdate='" .  $this->othersClass->getCurrentTimeStamp() . "' where empid= ? ";
              $this->coreFunctions->execqry($upd5, "update", [$clientid]);
            }

            $upd6 = "update allowsetup set empid='" . $clientid . "' where refx= ? ";
            $this->coreFunctions->execqry($upd6, "update", [$trno]);

            // empdesc
            if (strtoupper($empdesc)  == 'PROBATIONARY') {
              $today = $this->othersClass->getCurrentDate();
              $regprocess = $this->coreFunctions->opentable("select line, adddate('" . $today . "', interval num day) as expiration from regularization where isdays=1 and isinactive=0 order by sortline limit 1", [], '', true);
              if (!empty($regprocess)) {
                foreach ($regprocess as $key => $value) {
                  $datareg = ['regid' => $value->line, 'empid' => $clientid, 'expiration' => $value->expiration, 'createby' => $user, 'createdate' => $this->othersClass->getCurrentTimeStamp()];
                  $this->coreFunctions->sbcinsert("regprocess", $datareg);
                }
              }
            }

            //select 0 as trno, line, title, picture, encodeddate, encodedby from app_picture where trno=40

            $attachments = "insert into client_picture (trno, line, title, picture, encodeddate, encodedby)  
                select " . $clientid . " as trno, line, title, picture, encodeddate, encodedby from app_picture where trno=? ";
            $this->coreFunctions->execqry($attachments, 'insert', [$empid]);
          }
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
    } catch (\Exception $e) {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from client where clientid=?", "delete", [$clientid]);
      $this->coreFunctions->execqry("delete from employee where empid=?", "delete", [$clientid]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  private function getnewclient($config, $pref)
  {

    $clientlength =  $this->companysetup->getclientlength($config['params']);
    $last = $this->othersClass->getlastclient($pref, 'employee');
    $start = $this->othersClass->SearchPosition($last);
    $seq = substr($last, $start) + 1;
    $poseq = $pref . $seq;
    $newclient = $this->othersClass->PadJ($poseq, $clientlength);
    return $newclient;
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
}

// Query for insert employee
/*

insert into employee (empcode,emplast, empfirst, empmiddle, address, city, country, zipcode, telno, mobileno, email, citizenship, religion, alias, bday, jobtitle, jobcode, jobdesc, maidname, appdate, remarks, status, gender,aplcode,aplid,isactive,hired,empstatus,classrate,mapp,level,emptype,division,nodeps,teu) select '" & txtEmpcode.Text & "', emplast, empfirst, empmiddle, address, city, country, zipcode, telno, mobileno, email, citizenship, religion, alias, bday, jobtitle, jobcode, jobdesc, maidname, appdate, remarks, status, gender,empcode,empid,1,'" & Format(CDate(dtEffect.Text), "yyyy/MM/dd") & "','" & txtEmpStatus.Text & "','" & IIf(txtClassRAte.Text = "Daily", "D", "M") & "',mapp, 10,type,'" & txtcompany.Text & "',child,(case when status='MARRIED' then 'M' else 'S' end) as sta from app where empcode='" & txtIDNo.Text & "'
insert into contacts (empcode, contact1, relation1, addr1, homeno1, mobileno1, officeno1, ext1, notes1, contact2, relation2, addr2, homeno2, mobileno2, officeno2, ext2, notes2)  select '" & txtEmpcode.Text & "', contact1, relation1, addr1, homeno1, mobileno1, officeno1, ext1, notes1, contact2, relation2, addr2, homeno2, mobileno2, officeno2, ext2, notes2 from acontacts where empcode='" & txtIDNo.Text & "'
insert into dependents (empcode, name, relation, bday, taxin)  select '" & txtEmpcode.Text & "', name, relation, bday, taxin from adependents where empcode='" & txtIDNo.Text & "'
insert into education (empcode, school, address, course, sy, gpa, honor)  select '" & txtEmpcode.Text & "', school, address, course, sy, gpa, honor from aeducation where empcode='" & txtIDNo.Text & "'
insert into employment (empcode, company, jobtitle, period, address, salary, reason)  select '" & txtEmpcode.Text & "', company, jobtitle, period, address, salary, reason from aemployment where empcode='" & txtIDNo.Text & "'
insert into emppics (empcode, picture, picture2, picture3)  select '" & txtEmpcode.Text & "', picture, picture2, picture3 from apppics where empcode='" & txtIDNo.Text & "'
update app set ishired=1,idno='" & txtEmpcode.Text & "',hired='" & Format(CDate(dtEffect.Text), "yyyy/MM/dd") & "' where empcode='" & txtIDNo.Text & "'
insert into ratesetup (dateid,dateeffect, dateend, empcode, remarks, basicrate, type) values ('" & Format(CDate(dteDate.Text), "yyyy/MM/dd") & "','" & Format(CDate(dtEffect.Text), "yyyy/MM/dd") & "','9999-12-31','" & txtEmpcode.Text & "','" & txtDocNo.Text & "'," & Val(txtRate.Text) & ",'" & IIf(txtClassRAte.Text = "Daily", "D", "M") & "')
            
Update Employee set jobcode='" & txtJobCode.Text & "',jobdesc='" & txtjobdesc.Text & "',jobtitle='" & txtjobtitle.Text & "' where empcode='" & txtEmpcode.Text & "'")
Update App set jobcode='" & txtJobCode.Text & "',jobdesc='" & txtjobdesc.Text & "',jobtitle='" & txtjobtitle.Text & "' where empcode='" & txtIDNo.Text & "'

*/