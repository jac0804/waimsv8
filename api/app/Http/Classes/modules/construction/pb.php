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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class pb
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PROGRESS BILLING';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $defaultContra = 'AR1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'address', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'projectid', 'subproject', 'orderno', 'rem2'];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;


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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1843,
      'edit' => 1844,
      'new' => 1845,
      'save' => 1846,
      // 'change'=>1847, remove change doc
      'delete' => 1848,
      'print' => 1849,
      'lock' => 1850,
      'unlock' => 1851,
      'post' => 1852,
      'unpost' => 1853,
      'additem' => 1854,
      'edititem' => 1855,
      'deleteitem' => 1856
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listprojectname', 'rem', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[4]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[4]['label'] = 'Customer Name';
    $cols[5]['style'] = 'width:300px;whiteSpace: normal;min-width:200px;';
    $cols[6]['style'] = 'width:300px;whiteSpace: normal;min-width:200px;';
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
      $searchfield = ['head.docno', 'head.clientname', 'pm.name', 'pm.code', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

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
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby,
     head.rem, concat(pm.code,' - ',pm.name) as projectname  
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join projectmasterfile as pm on pm.line = head.projectid 
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby,
      head.rem, concat(pm.code,' - ',pm.name) as projectname    
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno
     left join projectmasterfile as pm on pm.line = head.projectid  
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     order by dateid desc, docno desc " . $limit;

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
    $step1 = $this->helpClass->getFields(['btnnew', 'customersupplier', 'dateid', 'terms', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customersupplier', 'dateid', 'terms', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnaddaccount', 'db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step4 = $this->helpClass->getFields(['db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add account/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit account details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'pb', 'title' => 'Progress Billing Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $tab = [
      $this->gridname => ['gridcolumns' => [
        'action',
        'acno', 'acnoname', 'db', 'cr', 'postdate', 'rem', 'client', 'ref', 'acnoname'
      ]],
      //                    'adddocument'=>['event'=>['lookupclass' => 'entrycntnumpicture','action' => 'documententry','access' => 'view']] 
    ];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['accounting']['columns'][1]['readonly'] = true;
    $obj[0]['accounting']['columns'][2]['readonly'] = true;

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['unpaid', 'additem', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "BA";
    $obj[0]['lookupclass'] = "unbilled";
    $obj[0]['action'] = "unbilled";
    $obj[1]['label'] = "ADD ACCOUNT";
    $obj[1]['action'] = "adddetail";
    $obj[2]['label'] = "SAVE ALL ACCOUNT";
    $obj[3]['label'] = "DELETE ALL ACCOUNT";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Customer');
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = [['dateid', 'terms'], 'dprojectname', 'ourref'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dprojectname.condition', ['checkstock']);
    data_set($col2, 'dprojectname.required', true);
    data_set($col2, 'dprojectname.lookupclass', 'projectcode');
    data_set($col2, 'ourref.label', 'Billing Inv.#');

    $fields = ['yourref', 'rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'yourref.label', 'Progress Billing#');


    $fields = ['rem2', 'orderno'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem2.label', 'WAC No.');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = 'P';
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['dprojectname'] = '';
    $data[0]['subproject'] = '0';
    $data[0]['subprojectname'] = '';
    $data[0]['orderno'] = '';
    $data[0]['rem2'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
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
         num.center,
         head.trno, 
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         client.groupid,ifnull(project.code,'') as projectcode ,s.line as subproject,s.subproject as subprojectname,head.rem2,head.orderno ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 
        left join subproject as s on s.line = head.subproject
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid 
        left join subproject as s on s.line = head.subproject    
        where head.trno = ? and num.doc=? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->opendetail($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      return  ['head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function




  public function posttrans($config)
  {
    return $this->othersClass->posttransacctg($config);
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttransacctg($config);
  } //end function


  private function getdetailselect($config)
  {
    $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,coa.acno,coa.acnoname,
  client.client,client.clientname,d.rem,
  FORMAT(d.db,2) as db,FORMAT(d.cr,2) as cr,d.fdb,d.fcr,d.refx,d.linex,
  left(d.postdate,10) as postdate,d.checkno,coa.alias,d.pdcline,
  d.project,ifnull(proj.name,'') as projectname,d.cur,d.forex,d.stageid,ifnull(s.stage,'') as stage,
  case d.isewt when 0 then 'false' else 'true' end as isewt,case d.isvat when 0 then 'false' else 'true' end as isvat,case d.isvewt when 0 then 'false' else 'true' end as isvewt,d.ewtcode,d.ewtrate,d.damt,'' as bgcolor,'' as 
  errcolor ";
    return $qry;
  }


  public function opendetail($trno, $config)
  {
    $sqlselect = $this->getdetailselect($config);

    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.code = d.project
    left join stagesmasterfile as s on s.line = d.stageid
    left join coa on d.acnoid=coa.acnoid
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid
    left join projectmasterfile as proj on proj.code = d.project
    left join stagesmasterfile as s on s.line = d.stageid
    left join coa on coa.acnoid=d.acnoid
    where d.trno=?
  ";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $detail;
  }


  public function opendetailline($config)
  {
    $sqlselect = $this->getdetailselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.code = d.project
    left join stagesmasterfile as s on s.line = d.stageid
    left join coa on d.acnoid=coa.acnoid
    where d.trno=? and d.line=?";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $detail;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'adddetail':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all detail edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'getunbilledselected':
        return $this->getunbilledselected($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->opendetailline($config);
    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Payment amount is greater than setup amount.'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $isupdate = $this->additem('update', $config);
      if ($isupdate['status'] == false) {
        break;
      }
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    //$isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key]['db'] == 0 && $data2[$key]['cr'] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Some entries have zero value both debit and credit ';
        } else {
          $msg2 = ' Reference Amount is lower than encoded amount ';
        }
      }
    }
    if ($isupdate['status']) {
      return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      if ($isupdate['msg'] == '') {
        return ['accounting' => $data, 'status' => true, 'msg' => 'Please check some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
      } else {
        return ['accounting' => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
      }
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  // insert and update detail
  public function additem($action, $config)
  {
    $acno = $config['params']['data']['acno'];
    $acnoname = $config['params']['data']['acnoname'];
    $trno = $config['params']['trno'];
    $db = $config['params']['data']['db'];
    $cr = $config['params']['data']['cr'];
    $fdb = $config['params']['data']['fdb'];
    $fcr = $config['params']['data']['fcr'];
    $postdate = $config['params']['data']['postdate'];
    $rem = '';
    $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]); // $config['params']['data']['projectid'];
    $subproject = $this->coreFunctions->getfieldvalue($this->head, "subproject", "trno=?", [$trno]);
    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $refx = 0;
    $linex = 0;
    $ref = '';
    $checkno = '';
    $damt = 0;
    $stageid = 0;

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    if (isset($config['params']['data']['checkno'])) {
      $checkno = $config['params']['data']['checkno'];
    }

    if (isset($config['params']['data']['subproject'])) {
      $subproject = $config['params']['data']['subproject'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      if ($db != 0) {
        $damt = $db;
      } else {
        $damt = $cr;
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
      $damt = $config['params']['data']['damt'];

      if ($refx == 0) {
        if ($db != 0) {
          $ddb = $this->coreFunctions->getfieldvalue($this->detail, 'db', 'trno=? and line =?', [$trno, $line]);

          if ($db != number_format($ddb, 2)) {
            $damt = $db;
          } else {
            $damt = $config['params']['data']['damt'];
          }
        } else {
          $dcr = $this->coreFunctions->getfieldvalue($this->detail, 'cr', 'trno=? and line =?', [$trno, $line]);
          if ($cr != number_format($dcr, 2)) {
            $damt = $cr;
          } else {
            $damt = $config['params']['data']['damt'];
          }
        }
      }
    }

    $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'acnoid' => $acnoid,
      'client' => $client,
      'db' => $db,
      'cr' => $cr,
      'fdb' => $fdb,
      'fcr' => $fcr,
      'postdate' => $postdate,
      'rem' => $rem,
      'projectid' => $project,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'checkno' => $checkno,
      'damt' => $damt,
      'stageid' => $stageid,
      'subproject' => $subproject
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';
    $status = true;


    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' DB:' . $db . ' CR:' . $cr . ' Client:' . $client . ' Date:' . $postdate);
        if ($refx != 0) {
          $this->coreFunctions->sbcupdate("hbahead", ['pbtrno' => $trno], ['trno' => $refx]);
        }
        $row = $this->opendetailline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add Account Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
        if ($refx != 0) {
          $this->coreFunctions->sbcupdate("hbahead", ['pbtrno' => $trno], ['trno' => $refx]);
        }
      } else {
        $return = false;
      }
      return ['status' => $return, 'msg' => ''];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select coa.acno,detail.refx,detail.linex from ' . $this->detail . ' as detail left join coa on coa.acnoid = detail.acnoid where detail.trno=? and detail.refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->coreFunctions->sbcupdate("hbahead", ['pbtrno' => 0], ['trno' => $data[$key]->refx]);
    }
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }



  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->opendetailline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    if ($data[0]->refx != 0) {
      $this->coreFunctions->sbcupdate("hbahead", ['pbtrno' => 0], ['trno' => $data[0]->refx]);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['acno'] . ' DB:' . $data[0]['db'] . ' CR:' . $data[0]['cr'] . ' Client:' . $data[0]['client'] . ' Date:' . $data[0]['postdate'] . ' Ref:' . $data[0]['ref']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
  } // end function

  public function getunbilledselected($config)
  {
    $trno = $config['params']['trno'];
    $qry = "select c.clientid as value from client as c left join lahead as head on head.client=c.client where head.trno='$trno'";
    $clientid = $this->coreFunctions->datareader($qry);

    $rows = [];
    $data = $config['params']['rows'];
    $recoup = 0;
    $retention = 0;
    $ewt = 0;
    $vat = 0;
    $sales = 0;
    $ar = 0;
    $due = 0;
    $rc = .15;

    if ($clientid == 2584) {
      $rc = .10;
    }

    foreach ($data as $key => $value) {
      $amt = number_format($data[$key]['amt'], 2, '.', ''); //gross
      if (floatval($amt) != 0) {
        $recoup = round($data[$key]['amt'] * $rc, 2);
        $retention = round($data[$key]['amt'] * .10, 2);
        $ewt = round((($data[$key]['amt'] - $recoup) / 1.12) * .02, 2);
        $due = round($data[$key]['amt'] - $retention - $recoup, 2);
        $vat = round(($due / 1.12) * .12, 2);
        $sales = round($data[$key]['amt'] - $vat, 2);
        $ar = round($due - $ewt, 2);
      }

      if (floatval($ar) != 0) {
        $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='AR1'");
        $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='AR1'");
        $config['params']['data']['db'] = number_format($ar, 2, '.', '');
        $config['params']['data']['cr'] = 0;
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = 0;
        $config['params']['data']['postdate'] = $data[$key]['dateid'];
        $config['params']['data']['projectid'] = $data[$key]['projectid'];
        $config['params']['data']['subproject'] = $data[$key]['subproject'];
        $config['params']['data']['refx'] = $data[$key]['trno'];
        $config['params']['data']['ref'] = $data[$key]['docno'];
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }

      if (floatval($retention) != 0) {
        $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='AR2'");
        $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='AR2'");
        $config['params']['data']['db'] = number_format($retention, 2, '.', '');
        $config['params']['data']['cr'] = 0;
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = 0;
        $config['params']['data']['postdate'] = $data[$key]['dateid'];
        $config['params']['data']['projectid'] = $data[$key]['projectid'];
        $config['params']['data']['subproject'] = $data[$key]['subproject'];
        $config['params']['data']['refx'] = $data[$key]['trno'];
        $config['params']['data']['ref'] = $data[$key]['docno'];
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }

      if (floatval($recoup) != 0) {
        $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='AR3'");
        $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='AR3'");
        $config['params']['data']['db'] = number_format($recoup, 2, '.', '');
        $config['params']['data']['cr'] = 0;
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = 0;
        $config['params']['data']['postdate'] = $data[$key]['dateid'];
        $config['params']['data']['projectid'] = $data[$key]['projectid'];
        $config['params']['data']['subproject'] = $data[$key]['subproject'];
        $config['params']['data']['refx'] = $data[$key]['trno'];
        $config['params']['data']['ref'] = $data[$key]['docno'];
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }

      if (floatval($ewt) != 0) {
        $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='AR4'");
        $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='AR4'");
        $config['params']['data']['db'] = number_format($ewt, 2, '.', '');
        $config['params']['data']['cr'] = 0;
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = 0;
        $config['params']['data']['postdate'] = $data[$key]['dateid'];
        $config['params']['data']['projectid'] = $data[$key]['projectid'];
        $config['params']['data']['subproject'] = $data[$key]['subproject'];
        $config['params']['data']['refx'] = $data[$key]['trno'];
        $config['params']['data']['ref'] = $data[$key]['docno'];
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }

      if (floatval($vat) != 0) {
        $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='TX2'");
        $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='TX2'");
        $config['params']['data']['db'] = 0;
        $config['params']['data']['cr'] = number_format($vat, 2, '.', '');
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = 0;
        $config['params']['data']['postdate'] = $data[$key]['dateid'];
        $config['params']['data']['projectid'] = $data[$key]['projectid'];
        $config['params']['data']['subproject'] = $data[$key]['subproject'];
        $config['params']['data']['refx'] = $data[$key]['trno'];
        $config['params']['data']['ref'] = $data[$key]['docno'];
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }

      if (floatval($sales) != 0) {
        $config['params']['data']['acno'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='SA1'");
        $config['params']['data']['acnoname'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias='SA1'");
        $config['params']['data']['db'] = 0;
        $config['params']['data']['cr'] = number_format($sales, 2, '.', '');
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = 0;
        $config['params']['data']['postdate'] = $data[$key]['dateid'];
        $config['params']['data']['projectid'] = $data[$key]['projectid'];
        $config['params']['data']['subproject'] = $data[$key]['subproject'];
        $config['params']['data']['refx'] = $data[$key]['trno'];
        $config['params']['data']['ref'] = $data[$key]['docno'];
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          array_push($rows, $return['row'][0]);
        }
      }
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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
