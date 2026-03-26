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
use Illuminate\Auth\EloquentUserProvider;

class hc
{

  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CLEARANCE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'hrisnum';
  public $head = 'clearance';
  public $hhead = 'hclearance';
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
    'empheadid',
    'deptid',
    'dateid',
    'hired',
    'lastdate',
    'jobtitle',
    'cause',
    'amount',
    'deduction',
    'witness',
    'witness2',
    'status'
  ];
  private $except = ['trno'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];

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
      'view' => 1229,
      'edit' => 1230,
      'new' => 1231,
      'save' => 1232,
      'change' => 1233,
      'delete' => 1234,
      'print' => 1235,
      'post' => 1236,
      'unpost' => 1237,
      'lock' => 1709,
      'unlock' => 1710
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
        $condition = ' and h.lockdate is null and num.postdate is null ';
        break;
      case 'locked':
        $condition = ' and h.lockdate is not null and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select h.trno, h.docno, date(h.dateid) as dateid, 
    c.client as empcode, c.clientname as empname, 'DRAFT' as status
    from " . $this->head . " as h left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where h.doc=? and num.center = ? and 
    CONVERT(h.dateid,DATE)>=? and 
    CONVERT(h.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
    union all
    select h.trno, h.docno, date(h.dateid) as dateid, 
    c.client as empcode, c.clientname as empname, 'POSTED' as status
    from " . $this->hhead . " as h left join client as c on c.clientid=h.empid 
    left join " . $this->tablenum . " as num on num.trno=h.trno
    where h.doc=? and num.center = ? and 
    CONVERT(h.dateid,DATE)>=? and 
    CONVERT(h.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
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
      'lock',
      'unlock',
      'post',
      'unpost',
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
    $fields = ['docno', 'empcode', 'empname', 'dept'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'empcode.action', 'lookupemployee');
    data_set($col1, 'dept.type', 'input');
    data_set($col1, 'dept.class', 'csdept sbccsreadonly');

    $fields = ['dateid', 'hired', 'lastdate', 'jobtitle'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'jobtitle.class', 'csjobtitle sbccsreadonly');
    data_set($col2, 'hired.class', 'cshired sbccsreadonly');
    data_set($col2, 'lastdate.class', 'cslastdate sbccsreadonly');

    $fields = ['emphead', 'empheadname', 'cause', 'resignedtype'];

    $col3 = $this->fieldClass->create($fields);


    data_set($col3, 'cause.type', 'cinput');
    data_set($col3, 'resigned.type', 'input');
    data_set($col3, 'resigned.reaonly', true);
    data_set($col3, 'resigned.class', 'csresigned sbccsreadonly');

    if ($companyid == 58) { //cdohris
      data_set($col3, 'resignedtype.lookupclass', 'lookupresignedHC');
      $fields = ['amount', 'deduction', 'witnessname', 'witnessname2'];
    } else {
      $fields = [];
    }
    $col4 = $this->fieldClass->create($fields);
    if ($companyid == 58) { //cdohris
      data_set($col4, 'amount.label', 'Last Pay Amount');
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
    $data[0]['hired'] = '';
    $data[0]['lastdate'] = '';
    $data[0]['emphead'] = '';
    $data[0]['empheadname'] = '';
    $data[0]['empheadid'] = 0;
    $data[0]['jobtitle'] = '';
    $data[0]['cause'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['dept'] = '';
    $data[0]['resignedtype'] = '';
    $data[0]['amount'] = 0;
    $data[0]['deduction'] = 0;
    $data[0]['witness'] = 0;
    $data[0]['witness2'] = 0;
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
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
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
        num.trno, 
        num.docno, 
        head.empid,
        em.client as empcode, 
        em.clientname as empname, 
        head.deptid,
        d.client as dept, 
        head.dateid, 
        head.hired, 
        head.jobtitle, 
        head.empheadid,
        cl.client as emphead, 
        cl.clientname as empheadname, 
        head.cause,head.resignedtype,
        head.lastdate,head.amount,head.deduction,
        head.witness,head.witness2, witness.clientname as witnessname,witness2.clientname as witnessname2
    ";
    $qry = $qryselect . " from " . $table . " as head
    left join client as cl on cl.clientid=head.empheadid   
    left join client as em on em.clientid=head.empid
    left join client as d on d.clientid=head.deptid
    left join $tablenum as num on num.trno = head.trno  
    left join employee as emp on emp.empid=head.empid 
    left join client as witness on witness.clientid=head.witness
    left join client as witness2 on witness2.clientid=head.witness2
    where num.trno = ? and num.doc='HC' and num.center=? 
    union all " . $qryselect . " from " . $htable . " as head
    left join client as cl on cl.clientid=head.empheadid   
    left join client as em on em.clientid=head.empid
    left join client as d on d.clientid=head.deptid
    left join $tablenum as num on num.trno = head.trno   
    left join employee as emp on emp.empid=head.empid
    left join client as witness on witness.clientid=head.witness
    left join client as witness2 on witness2.clientid=head.witness2
    where num.trno = ? and num.doc='HC' and num.center=? ";

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
    if ($config['params']['companyid'] == 58) { //cdo
      $data['resignedtype'] = $head['resignedtype'];
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

  public function getCurrentDate()
  {
    //SETS DEFAULT TIME ZONE ** REQUIRED **
    $this->setDefaultTimeZone();
    $current_timestamp = date('Y-m-d');
    return $current_timestamp;
  } //end function

  public function setDefaultTimeZone()
  {
    //SETS DEFAULT TIME ZONE ** REQUIRED **
    date_default_timezone_set('Asia/Singapore');
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into hclearance (trno, docno, dateid, jobtitle, hired, lastdate, cause, doc, lockdate, empid, empheadid, createdate, editdate, createby, editby, deptid, lockuser,resignedtype,amount,deduction,witness,witness2)
    select trno, docno, dateid, jobtitle, hired, lastdate, cause, doc, lockdate, empid, empheadid, createdate, editdate, createby, 
    editby, deptid, lockuser,resignedtype,amount,deduction,witness,witness2 from clearance where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {
      $query = "select empid,resignedtype from hclearance where trno = $trno ";
      $empid = $this->coreFunctions->opentable($query);

      $date = $this->getCurrentDate();
      $update = "update employee set isactive = 0,resignedtype = '" . $empid[0]->resignedtype . "'  where empid= " . $empid[0]->empid;
      $this->coreFunctions->execqry($update);

      $isactive = $this->coreFunctions->getfieldvalue('employee', 'isactive', 'empid=?', [$empid[0]->empid]);
      $isinactive = 1;
      if ($isactive == 1) {
        $isinactive = 0;
      }
      $data2['isinactive'] = $isinactive;
      $this->coreFunctions->sbcupdate('client', $data2, ['clientid' => $empid[0]->empid]);
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

    $qry = "insert into clearance (trno, docno, dateid, jobtitle, hired, lastdate, cause, doc, lockdate, empid, empheadid, createdate, editdate, createby, editby, deptid, lockuser,resignedtype,amount,deduction,witness,witness2)
    select trno, docno, dateid, jobtitle, hired, lastdate, cause, doc, lockdate, empid, empheadid, createdate, editdate, createby, 
    editby, deptid, lockuser,resignedtype,amount,deduction,witness,witness2 from hclearance where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {
      $query = "select empid,resignedtype from hclearance where trno = $trno ";
      $empid = $this->coreFunctions->opentable($query);

      $update = "update employee set isactive = 1,resignedtype = '' where empid= " . $empid[0]->empid;

      $this->coreFunctions->execqry($update);
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
