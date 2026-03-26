<?php

namespace App\Http\Classes\modules\construction;

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
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class jc
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'JOB COMPLETION';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $head = 'jchead';
  public $hhead = 'hjchead';
  public $stock = 'jcstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $hstock = 'hjcstock';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'address', 'projectid', 'subproject', 'stageid', 'workloc', 'workdesc', 'tax', 'vattype', 'retention', 'contra'];
  private $except = ['trno', 'dateid', 'due'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  public $defaultContra = 'AP4';

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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1827,
      'edit' => 1828,
      'new' => 1829,
      'save' => 1830,
      // 'change'=>1831, remove change doc
      'delete' => 1832,
      'print' => 1833,
      'lock' => 1834,
      'unlock' => 1835,
      'post' => 1836,
      'unpost' => 1837,
      'additem' => 1838,
      'edititem' => 1839,
      'deleteitem' => 1840,
      'acctg' => 1841
    );
    return $attrib;
  }


  public function createdoclisting()
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'yourref', 'listdate', 'listclientname', 'rem', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[3]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[6]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $cols[7]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[8]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[9]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[10]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $user = $config['params']['user'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
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

    $projectfilter = "";


    $viewall = $this->othersClass->checkAccess($config['params']['user'], 4896);
    $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
    $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
    if ($viewall == '0') {
      if ($project == '' && $user != 'sbc') {
        return ['data' => [], 'status' => true, 'msg' => 'Please advice administrator to setup default project.'];
      }
      if ($project != '') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }


    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby,head.yourref,head.rem
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby ,head.yourref ,head.rem
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
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
      'toggledown',
      'help',
      'others'
    );

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add item/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'jc', 'title' => 'Job Completion Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createTab($access, $config)
  {

    $gridcolumns = [
      'action',
      'rrqty',
      'uom',
      'rrcost',
      'disc',
      'ext',
      'wh',
      'qa',
      'rem',
      'ref',
      'void'
    ];
    if ($this->companysetup->getisproject($config['params'])) {
      $gridcolumns = ['action', 'rrqty', 'uom', 'rrcost', 'disc', 'ext', 'qa', 'rem', 'ref', 'stage', 'void'];
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $gridcolumns,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => ['viewdistribution', 'viewref', 'viewdiagram']
      ],
    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];


    // 9- ref 

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][8]['lookupclass'] = 'refpo';
    if ($this->companysetup->getisproject($config['params'])) {
      $obj[0]['inventory']['columns'][9]['readonly'] = true;
    }
    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingjo', 'saveitem', 'deleteallitem'];

    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($config['params']['companyid'] == 8) { //maxipro
      $obj[1]['label'] = "SAVE ALL";
      $obj[2]['label'] = "DELETE ALL";
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address', 'dacnoname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Subcon');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'dacnoname.label', 'AP Account');
    data_set($col1, 'dacnoname.required', true);


    $fields = [['dateid', 'terms'], ['due', 'dvattype'], 'dprojectname', 'subprojectname', 'stage'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dprojectname.type', 'input');
    data_set($col2, 'dprojectname.condition', ['checkstock']);
    data_set($col2, 'dprojectname.required', true);
    data_set($col2, 'subprojectname.type', 'lookup');
    data_set($col2, 'subprojectname.lookupclass', 'lookupsubproject');
    data_set($col2, 'subprojectname.action', 'lookupsubproject');
    data_set($col2, 'subprojectname.addedparams', ['projectid']);
    data_set($col2, 'subprojectname.required', true);


    $fields = [['yourref', 'ourref'], 'workloc', 'workdesc'];
    $col3 = $this->fieldClass->create($fields);


    $fields = ['retention', 'rem'];
    $col4 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['retention'] = '';
    $pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$params['user']]);
    $data[0]['projectid'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$pid]);
    $data[0]['projectcode'] =  $pid;
    $data[0]['projectname'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "name", "code=?", [$pid]);

    $data[0]['dprojectname'] = '';
    $data[0]['stageid'] = 0;
    $data[0]['stage'] = '';
    $data[0]['subprojectname'] = '';
    $data[0]['subproject'] = 0;
    $data[0]['workloc'] = '';
    $data[0]['workdesc'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
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

    $projectfilter = "";

    $viewall = $this->othersClass->checkAccess($config['params']['user'], 4896);
    $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
    $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
    if ($viewall == '0') {
      if ($project != '') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }

    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.agent, 
         head.tax,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         head.vattype,
         '' as dvattype,
         agent.clientname as agentname,
         left(head.due,10) as due, head.stageid,
         client.groupid,head.projectid,p.code as projectcode,p.name as projectname,s.line as subproject,s.subproject as subprojectname,stage.stage,head.workloc,head.workdesc,head.retention  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
         left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join stagesmasterfile as stage on stage.line = head.stageid
        where head.trno = ? and num.center = ? " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
         left join projectmasterfile as p on p.line = head.projectid
         left join stagesmasterfile as stage on stage.line = head.stageid
        left join subproject as s on s.line = head.subproject
          where head.trno = ? and num.center=? " . $projectfilter;

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
      unset($this->fields[1]);
      unset($head['docno']);
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }
    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->recomputecost($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
  } // end function


  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));

      $computedata = $this->othersClass->computestock($damt, $data[$key]->disc, $dqty, $data[$key]->uomfactor, $head['tax']);


      $exec = $this->coreFunctions->execqry("update " . $this->stock . " set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }


  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    //INSC - SUBCON COST
    //APRT - AP RETENTION
    $checkacct = $this->othersClass->checkcoaacct(['AP4', 'INSC', 'APRT', 'TX1']);

    if ($checkacct != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    }

    if (!$this->createdistribution($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
    } else {
      $return = $this->posttransjc($config);
      if ($this->companysetup->getisproject($config['params'])) {
        if ($return['status']) {
          $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno and d.line = a.line  where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");
          $this->updateprojmngmtap($config, $data);
        }
      }
      return $return;
    }
  } //end function

  public function posttransjc($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    if (!$this->othersClass->isacctgbalance($trno, $config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Accounting entries are not balanced.'];
    }

    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,projectid,subproject,stageid,workloc,workdesc,retention,contra,vattype)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.projectid,head.subproject,head.stageid,head.workloc,head.workdesc,head.retention,head.contra,head.vattype FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,rem,stageid)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,rem,stageid FROM " . $this->stock . " where trno =?";
      $poststock = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($poststock) {
        $qry = "insert into " . $config['docmodule']->hdetail . " (postdate,trno,line,acnoid,clientid,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
          editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid)
          select d.postdate,d.trno,d.line,d.acnoid,
          ifNull(client.clientid,0),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
          d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
          d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid
          from " . $config['docmodule']->head . " as h
          left join " . $config['docmodule']->detail . " as d on d.trno=h.trno
          left join client on client.client=d.client
          where  d.trno=?";
        $postdetail = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($postdetail) {
          if ($this->othersClass->postingapledger($config) === 1) {
            //update transnum
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
            $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
          } else {
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Kindly check the detail(AP).'];
          }
        } else {
          $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting detail'];
        }
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
      //if($posthead){      
    } else {
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable("select sum(a.cr-a.db) as bal,d.projectid,d.subproject,d.stageid from apledger as a left join gldetail as d on d.trno = a.trno and d.line = a.line  where a.trno =" . $trno . " group by d.projectid,d.subproject,d.stageid");

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data2 = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data2)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already have WAC or have item voided...'];
    }

    $return = $this->unposttransjc($config);
    if ($this->companysetup->getisproject($config['params'])) {
      if ($return['status']) {
        $this->updateprojmngmtap($config, $data);
      }
    }
    return $return;
  } //end function
  public function unposttransjc($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = $this->othersClass->hasbeenappaid($config);
    if ($msg !== '') {
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }

    $msg = '';
    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,projectid,subproject,stageid,workloc,workdesc,retention,contra,vattype)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.projectid,head.subproject,head.stageid,head.workloc,head.workdesc,head.retention,head.contra,head.vattype FROM " . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $unpostinghead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($unpostinghead) {
      $qry = "insert into " . $this->stock . "(trno,line,itemid,uom,
        whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,rem,stageid)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,rem,stageid FROM " . $this->hstock . " where trno =?";
      $unpoststock = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
      if ($unpoststock) {
      } else {
        $msg = 'Unposting failed. Please check stock.';
      }
    } else {
      $msg = 'Unposting failed. Please check head.';
    }

    if ($msg === '') {
      $docno = $this->coreFunctions->getfieldvalue($this->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from apledger where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.cost,stock.disc,stock.rrqty,stock.qty,head.projectid,head.subproject,stock.stageid,head.retention
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid 
          left join item on item.itemid=stock.itemid where head.trno=?';
    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['INSC']);
      $vat = $stock[0]->tax;
      $tax1 = 0;
      $tax2 = 0;
      $retention = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));
        if ($vat != 0) {
          $tax = round(($stock[$key]->ext / $tax1), 2);
          $tax = round($stock[$key]->ext - $tax, 2);
        }

        if ($stock[$key]->retention != '') {
          $retention = $stock[$key]->ext * (floatval($stock[$key]->retention) / 100);
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->rrqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => $stock[$key]->cost * $stock[$key]->qty,
          'projectid' => $stock[$key]->projectid,
          'subproject' => $stock[$key]->subproject,
          'stageid' => $stock[$key]->stageid,
          'retention' => $retention
        ];

        $this->distribution($params, $config);
      }
    }
    if (!empty($this->acctg)) {
      $isglc = $this->companysetup->isglc($config['params']);
      $tdb = 0;
      $tcr = 0;
      if ($isglc) {
        //loop to get totals
        foreach ($this->acctg as $key => $value) {
          $tdb = $tdb +  round($this->acctg[$key]['db'], 2);
          $tcr = $tcr +  round($this->acctg[$key]['cr'], 2);
        }

        $diff = $tdb - $tcr;
        $this->coreFunctions->LogConsole(round($diff, 2));
        $alias = 'GLC';

        if ($diff != 0) {
          $qry = "select client,forex,dateid,cur,branch,contra,projectid,wh from " . $this->head . " where trno = ?";
          $d = $this->coreFunctions->opentable($qry, [$trno]);

          if ($config['params']['companyid'] == 8) { //maxipro
            switch ($params['projectid']) {
              case '3':
              case '7':
                $alias = 'GLC1';
                break;

              default:
                $alias = 'GLC2';
                break;
            }
          }

          if (abs(round($diff, 2)) != 0) {
            if ($diff < 0) {
              $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$alias]);
              $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => abs(round($diff, 2)), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', [$alias]);
              $entry = ['acnoid' => $acnoid, 'client' => $d[0]->wh, 'db' => 0, 'cr' => abs(round($diff, 2)), 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => 0, 'fdb' => 0, 'projectid' => $d[0]->projectid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }
      }

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
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status =  true;
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
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }

    $cur = $params['cur'];
    $invamt = $params['cost']; //round(($params['ext']-$params['tax']) + $params['discamt'],2);
    $retention = $params['retention'];
    //AP
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => (($params['ext'] - $retention) * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $params['ext'] - $retention, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($retention) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APRT']);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($retention * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $retention, 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if (floatval($params['discamt']) != 0) {
      $inputid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['PD1']);
      $entry = ['acnoid' => $inputid, 'client' => $params['client'], 'cr' => ($params['discamt'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //INV
    if (floatval($invamt) != 0) {
      if (floatval($params['discamt']) != 0) {
        $invamt  = $invamt + ($params['discamt'] * $forex);
      }
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt / $forex), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      // input tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid'], 'subproject' => $params['subproject'], 'stageid' => $params['stageid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function


  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    stock.refx, 
    stock.linex,    
    item.barcode, 
    item.itemname,
    stock.uom, 
    stock.cost, 
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    case when stock.void=0 then 'false' else 'true' end as void, 
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    item.brand,
    stock.rem, stock.stageid,st.stage,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid 
    left join stagesmasterfile as st on st.line = stock.stageid 
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid  
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join stagesmasterfile as st on st.line = stock.stageid 
    where stock.trno =? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
   FROM $this->stock as stock
  left join item on item.itemid=stock.itemid 
  left join model_masterfile as mm on mm.model_id = item.model
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid left join stagesmasterfile as st on st.line = stock.stageid  where stock.trno = ? and stock.line = ? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getjosummary':
        return $this->getjosummary($config);
        break;
      case 'getjodetails':
        return $this->getjodetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'updateitemvoid':
        return $this->updateitemvoid($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  private function updateitemvoid($config)
  {
    $trno = $config['params']['trno'];
    $rows = $config['params']['rows'];
    foreach ($rows as $key) {
      $this->coreFunctions->execqry('update ' . $this->hstock . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
    }
  } //end function

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than PR Qty ';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  public function quickadd($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $wh = $config['params']['wh'];
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) {
        $item[0]['amt'] = $lprice['data'][0]['amt'];
        $item[0]['disc'] = $lprice['data'][0]['disc'];
      }

      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.' . $barcodelength, ''];
    }
  }

  // insert and update item
  public function additem($action, $config)
  {
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $loc = $config['params']['data']['loc'];
    $ref = '';
    $void = 'false';
    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    $refx = 0;
    $linex = 0;
    $rem = '';
    $stageid = 0;
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    } else {
      $stageid = $this->coreFunctions->getfieldvalue($this->head, "stageid", "trno=?", [$trno]);
    }

    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    if ($forex == 0) {
      $forex = 1;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);

    if ($config['params']['companyid'] != 8) { //not maxipro
      $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    }
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat);
    $cost =   $this->othersClass->sanitizekeyfield('amt', $computedata['amt'] * $forex);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' => $cost,
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'refx' => $refx,
      'linex' => $linex,
      'rem' => $rem,
      'ref' => $ref,
      'stageid' => $stageid
    ];


    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' Ext:' . $computedata['ext']);
        $this->updateprojmngmt($config, $stageid);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      $this->updateprojmngmt($config, $stageid);
      if ($refx != 0) {
        if ($this->setserveditems($refx, $linex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
        }
      }

      return $return;
    }
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex,stageid from ' . $this->stock . ' where trno=? and refx<>0 ', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
      $this->updateprojmngmt($config, $data[$key]->stageid);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $config['params']['stageid'] = $config['params']['row']['stageid'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    if ($data[0]->refx !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }
    $this->updateprojmngmt($config, $config['params']['stageid']);
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from jchead as head left join jcstock as 
    stock on stock.trno=head.trno where head.doc='JC' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select hjcstock." . $this->hqty . " from hjchead left join hjcstock on hjcstock.trno=
    hjchead.trno where hjchead.doc='JC' and hjcstock.refx=" . $refx . " and hjcstock.linex=" . $linex;

    $qry2 = "select round(sum(" . $this->hqty . "),6) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }

    return $this->coreFunctions->execqry("update hjostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }


  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
          stock.rrcost as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          where head.doc = 'RR' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.rrcost <> 0
          UNION ALL
          select head.docno,head.dateid,stock.rrcost as computeramt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = 'RR' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock.rrcost <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function


  private function updateprojmngmt($config, $stage)
  {
    $trno = $config['params']['trno'];
    $data = $this->openstock($trno, $config);
    $proj = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
    $sub = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);

    $qry1 = "select stock.ext from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='JC' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry1 = $qry1 . " union all select stock.ext from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
      head.trno where head.doc='JC' and head.projectid = " . $proj . " and head.subproject = " . $sub . " and stock.stageid=" . $stage;

    $qry2 = "select ifnull(sum(ext),0) as value from (" . $qry1 . ") as t";

    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $editdate = $this->othersClass->getCurrentTimeStamp();
    $editby = $config['params']['user'];

    $updatestage = $this->coreFunctions->execqry("update stages set jc=" . $qty . ", editdate = '" . $editdate . "', editby = '" . $editby . "' where projectid = " . $proj . " and subproject=" . $sub . " and stage=" . $stage, 'update');
    if ($updatestage) {
      $this->logger->sbcwritelog(
        $trno,
        $config,
        'UPDATE STAGE',
        'JC => ' . $qty .
          ' PROJECT_ID => ' . $proj .
          ' SUBPRJECT_ID => ' . $sub .
          ' STAGE_ID => ' . $stage
      );
    }
    return $updatestage;
  }

  private function updateprojmngmtap($config, $data)
  {
    $trno = $config['params']['trno'];
    foreach ($data as $key => $value) {
      $data2 = $this->coreFunctions->datareader("select sum(a.cr-a.db) as value from apledger as a left join gldetail as d on d.trno = a.trno and d.line = a.line  where d.projectid =" . $data[$key]->projectid . " and d.subproject=" . $data[$key]->subproject . " and d.stageid=" . $data[$key]->stageid);
      if (empty($data2)) {
        $data2 = 0;
      }
      $this->coreFunctions->execqry("update stages set ap=" . $data2 . " where projectid = " . $data[$key]->projectid . " and subproject=" . $data[$key]->subproject . " and stage=" . $data[$key]->stageid, 'update');
    }
    return true;
  }

  public function getjosummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = '';
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        stock.disc,stock.stageid
        FROM hjohead as head left join hjostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function


  public function getjodetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = '';
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        FORMAT(((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.stageid,stock.rem
        FROM hjohead as head left join hjostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
