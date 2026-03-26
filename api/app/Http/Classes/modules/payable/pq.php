<?php

namespace App\Http\Classes\modules\payable;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

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

class pq
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PETTY CASH REQUEST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'pqhead';
  public $hhead = 'hpqhead';
  public $detail = 'pqdetail';
  public $hdetail = 'hpqdetail';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $defaultContra = 'PC1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'contra', 'address', 'projectid'];
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
      'view' => 2061,
      'edit' => 2062,
      'new' => 2063,
      'save' => 2064,
      // 'change' => 2065, remove change doc
      'delete' => 2066,
      'print' => 2067,
      'lock' => 2068,
      'unlock' => 2069,
      'post' => 2070,
      'unpost' => 2071,
      'additem' => 2072,
      'edititem' => 2073,
      'deleteitem' => 2074
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;
    $postdate = 7;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
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
    // if ($searchfilter != '') {
    //   $condition = " and (head.docno like '%" . $searchfilter . "%' or head.clientname like '%" . $searchfilter . "%' or head.yourref like '%".$searchfilter."%' or head.ourref like '%".$searchfilter."%' or num.postedby like '%".$searchfilter."%' or head.createby like '%".$searchfilter."%' or head.editby like '%".$searchfilter."%' or head.viewby like '%".$searchfilter."%')";
    //   $limit = "";
    // }

    // " . $filtersearch . "
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

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
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref    
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
       head.yourref, head.ourref    
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
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
      'help'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
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
    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    return $return;
  }


  public function createTab($access, $config)
  {
    $action = 0;
    $amt = 1;
    $postdate = 2;
    $rem = 3;
    $project = 4;

    $column = [
      'action', 'amt', 'postdate', 'rem', 'project'
    ];

    $headgridbtns = ['viewref', 'viewdiagram'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'headgridbtns' => $headgridbtns
      ],
    ];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][1]['label'] = 'Amount';
    $obj[0]['inventory']['totalfield'] = 'amt';
    $obj[0]['inventory']['label'] = 'Accounting';
    $obj[0]['inventory']['descriptionrow'] = ['acnoname', 'acno', 'Account Name'];

    $obj[0]['inventory']['columns'][0]['style'] = 'width: 80px;whiteSpace: normal;min-width:80px;max-width:80px';
    $obj[0]['inventory']['columns'][1]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px';
    $obj[0]['inventory']['columns'][2]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';
    $obj[0]['inventory']['columns'][3]['style'] = 'width: 370px;whiteSpace: normal;min-width:370px;max-width:370px';
    $obj[0]['inventory']['columns'][4]['style'] = 'width: 650px;whiteSpace: normal;min-width:650px;max-width:650px';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "ADD ACCOUNT";
    $obj[0]['action'] = "addaccountrow";
    $obj[0]['lookupclass'] = "addaccountrow";
    $obj[1]['label'] = "SAVE ACCOUNT";
    $obj[2]['label'] = "DELETE ACCOUNT";

    return $obj;
  }


  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Employee');
    data_set($col1, 'client.lookupclass', 'employee');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'dprojectname', ['yourref', 'ourref'], 'dacnoname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'Petty Cash Account');
    data_set($col2, 'dacnoname.lookupclass', 'PC');
    data_set($col2, 'dacnoname.required', true);

    $fields = ['rem'];
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      } else {
        $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
        if ($t == '') {
          $trno = 0;
        }
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         head.client,
         head.yourref,
         head.ourref,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,ifnull(project.code,'') as projectcode  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid         
        where head.trno = ? and num.doc=? and num.center=? ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }

      return  [
        'head' => $head, 'griddata' => ['inventory' => $detail], 'islocked' => $islocked,
        'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->detail . " where trno=? and amt=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Please check; some account have zero amount.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,dateid,
      terms,rem,forex,yourref,ourref,viewby,viewdate,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,projectid,contra)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,head.viewby,head.viewdate,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.wh,
      head.due,head.cur,head.projectid,head.contra FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hdetail . "(trno,line,acnoid,client,rem,postdate,amt,isok,
        encodeddate, encodedby,editdate,editby,refx,linex,projectid)
        SELECT trno,line,acnoid,client,rem,postdate,amt,isok,
        encodeddate, encodedby,editdate,editby,refx,linex,projectid FROM " . $this->detail . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting detail'];
      }
      //if($posthead){      
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hdetail . " where trno=? and isok<>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already served...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,
  dateid,terms,rem,forex,yourref,ourref, createdate,viewby,viewdate,createby,editby,editdate,
  lockdate,lockuser,wh,due,cur,projectid,contra)
  select head.trno, head.doc, head.docno, client.client, head.clientname, head.address,
  head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,head.viewby,head.viewdate,
  head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.projectid,head.contra
  from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
  where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->detail . "(trno,line,acnoid,client,rem,postdate,amt,isok,
        encodeddate, encodedby,editdate,editby,refx,linex,projectid)
      select trno,line,acnoid,client,rem,postdate,amt,isok,
        encodeddate, encodedby,editdate,editby,refx,linex,projectid
      from " . $this->hdetail . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function


  private function getdetailselect($config)
  {
    $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,coa.acnoid,coa.acno,coa.acnoname,
  client.client,client.clientname,d.rem,
  FORMAT(d.amt,2) as amt,d.refx,d.linex,
  left(d.postdate,10) as postdate,coa.alias,
  d.projectid,ifnull(proj.name,'') as projectname,proj.code as project,
  '' as bgcolor,'' as 
  errcolor ";
    return $qry;
  }


  public function openstock($trno, $config)
  {
    $sqlselect = $this->getdetailselect($config);

    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join coa on coa.acnoid=d.acnoid
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join coa on coa.acnoid=d.acnoid
    where d.trno=?
  ";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $detail;
  }


  public function openstockline($config)
  {
    $sqlselect = $this->getdetailselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
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
      case 'addtogrid':
        return $this->addnewaccount($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }



  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
    CAST(concat('Total SV Amt: ',round(sum(s.db),2)) as CHAR) as rem,s.refx
    from hsvhead as po
    left join hsvdetail as s on s.trno = po.trno
    where s.refx = ?
    group by po.trno,po.docno,po.dateid,s.refx
    union all
    select po.trno,po.docno,left(po.dateid,10) as dateid,
    CAST(concat('Total SV Amt: ',round(sum(s.db),2)) as CHAR) as rem,s.refx
    from svhead as po
    left join svdetail as s on s.trno = po.trno
    where s.refx = ?
    group by po.trno,po.docno,po.dateid,s.refx";

    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;

      foreach ($t as $key => $value) {
        //PO
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 200,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => '#B5EAEA',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select pr.docno,left(pr.dateid,10) as dateid,
          CAST(concat('Total PQ Amt: ',round(sum(s.amt),2)) as CHAR) as rem
          from hpqhead as pr left join hpqdetail as s on s.trno = pr.trno
          where pr.trno = ?
          group by pr.docno,pr.dateid";
          $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => 10,
                  'y' => 50 + $a,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => '#F5FCC1',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function updateperitem($config)
  {

    $config['params']['data'] = $config['params']['row'];

    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
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
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    //$isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key]['amt'] == 0) {
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
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      if ($isupdate['msg'] == '') {
        return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
      } else {
        return ['inventory' => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
      }
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

  //newrow
  public function addnewaccount($config)
  {
    $data = [];
    $trno = $config['params']['trno'];
    $dateid = $this->coreFunctions->getfieldvalue("pqhead", "dateid", "trno=? ", [$trno]);
    $project = $this->coreFunctions->getfieldvalue("pqhead", "projectid", "trno=? ", [$trno]);

    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['data']['trno'] = $trno;
      $config['params']['data']['line'] = 0;
      $config['params']['data']['acno'] = $value['acno'];
      $config['params']['data']['acnoname'] = $value['acnoname'];
      $config['params']['data']['amt'] = 0;
      $config['params']['data']['rem'] = '';
      $config['params']['data']['postdate'] = $dateid;
      $config['params']['data']['projectid'] = $project;
      $return = $this->additem('insert', $config);
      array_push($data, $return['row'][0]);
    }

    return ['row' => $data, 'status' => true, 'msg' => 'Added Account Successfull...'];
  }


  // insert and update detail
  public function additem($action, $config)
  {
    $acno = $config['params']['data']['acno'];
    $acnoname = $config['params']['data']['acnoname'];
    $trno = $config['params']['trno'];
    $db = $config['params']['data']['amt'];
    $postdate = $config['params']['data']['postdate'];
    $rem = $config['params']['data']['rem'];
    $project = $config['params']['data']['projectid'];

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $refx = 0;
    $linex = 0;
    $ref = '';

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
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
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;
    }
    $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'acnoid' => $acnoid,
      'client' => $client,
      'amt' => $db,
      'postdate' => $postdate,
      'rem' => $rem,
      'projectid' => $project,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' amt:' . $db . ' client:' . $client . ' date:' . $postdate);
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['amt' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $msg = "Payment Amount is greater than Amount Setup";
          }
        }
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add Account Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['amt' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $return = false;
          }
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
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
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
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' code:' . $data[0]['acno'] . ' amt:' . $data[0]['amt'] . 'client:' . $data[0]['client'] . ' date:' . $data[0]['postdate']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end function

  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter();
    // $txtdata = $this->reportparamsdata($config);  

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    // $this->logger->sbcviewreportlog($config);
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config,$data);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 39: // CBBSI
      case 40: // CDO
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        break;
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  // public function createreportfilter(){
  //      $fields = ['radioprint','prepared','approved','received','refresh'];
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

  //   $query = "
  //       select head.rem, detail.rem as remarks, date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,  head.yourref, head.ourref,
  //         coa.acno, coa.acnoname, detail.ref, date(detail.postdate) as postdate, detail.amt,  detail.client as dclient
  //         from ".$this->head." as head left join ".$this->detail." as detail on detail.trno=head.trno 
  //         left join client on client.client=head.client
  //         left join coa on coa.acnoid=detail.acnoid
  //         where head.doc='PQ' and head.trno='$trno'
  //         union all
  //         select head.rem, detail.rem as remarks, date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.yourref, head.ourref,
  //         coa.acno, coa.acnoname, detail.ref, date(detail.postdate) as postdate, detail.amt, dclient.client as dclient
  //         from ".$this->hhead." as head left join ".$this->hdetail." as detail on detail.trno=head.trno left join client on client.client=head.client
  //         left join coa on coa.acnoid=detail.acnoid left join client as dclient on dclient.client=detail.client
  //         where head.doc='PQ' and head.trno='$trno'
  //         ";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // }//end fn

  // private function rpt_default_header($params,$data){
  //   $companyid = $params['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency',$params['params']);

  //   $center = $params['params']['center'];
  //   $username = $params['params']['user'];

  //   $str = '';
  //     $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->letterhead($center,$username);
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/><br/>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('PETTY CASH REQUEST','600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
  //   $str .= $this->reporter->col('DOCUMENT # :','100',null,false,'1px solid ','','L','Century Gothic','13','B','','');
  //   $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','B','L','Century Gothic','13','','','').'<br />';
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('EMPLOYEE : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
  //   $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
  //   $str .= $this->reporter->col('DATE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('ADDRESS : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
  //   $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'520',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
  //   $str .= $this->reporter->col('REF. :','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null,null,false,'1px solid ','','R','Century Gothic','10','','','4px');
  //   $str .= $this->reporter->pagenumber('Page');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->printline();
  //   //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  //   $str .= $this->reporter->col('ACCT.#','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('ACCOUNT NAME','350',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('DATE','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('AMOUNT','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->col('NOTES','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  //   return $str;
  // }

  // public function reportplotting($params,$data){

  //   $companyid = $params['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency',$params['params']);

  //   $center = $params['params']['center'];
  //   $username = $params['params']['user'];

  //   $str = '';
  //   $count=35;
  //   $page=35;
  //   $str .= $this->reporter->beginreport();

  //   $str .= $this->rpt_default_header($params,$data);
  //   $totaldb=0;
  //   $totalcr=0;
  //   for($i=0;$i<count($data);$i++){

  //   $debit=number_format($data[$i]['amt'],$decimal);
  //   if ($debit<1)
  //   {
  //   $debit='-';
  //   }

  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col($data[$i]['acno'],'75',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
  //   $str .= $this->reporter->col($data[$i]['acnoname'],'350',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
  //   $str .= $this->reporter->col($data[$i]['postdate'],'75',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
  //   $str .= $this->reporter->col($debit,'75',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
  //   $str .= $this->reporter->col($data[$i]['remarks'],'75',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
  //   $totaldb=$totaldb+$data[$i]['amt'];

  //   if($this->reporter->linecounter==$page){
  //     $str .= $this->reporter->endtable();
  //     $str .= $this->reporter->page_break();

  //       if ($companyid == 1) {
  //         $loggeduser = $username;  
  //         $str .= $this->rpt_default_header($params,$data);
  //         $str .= $this->reporter->printline();
  //         $page=$page + $count;
  //       }
  //     }
  //   }       

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C','Century Gothic','12','B','','2px');
  //   $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C','Century Gothic','12','B','','2px');
  //   $str .= $this->reporter->col('GRAND TOTAL :','350',null,false,'1px dotted ','T','R','Century Gothic','12','B','30px','2px');
  //   $str .= $this->reporter->col(number_format($totaldb,2),'75',null,false,'1px dotted ','T','R','Century Gothic','12','B','','2px');
  //   $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C','Century Gothic','12','B','30px','8px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->printline();
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/><br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Prepared By : ','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
  //   $str .= $this->reporter->col('Approved By :','266',null,false,'1px solid ','','C','Century Gothic','12','','','');
  //   $str .= $this->reporter->col('Received By :','266',null,false,'1px solid ','','R','Century Gothic','12','','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= '<br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col($params['params']['dataparams']["prepared"],'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col($params['params']['dataparams']["approved"],'266',null,false,'1px solid ','','C','Century Gothic','12','B','','');
  //   $str .= $this->reporter->col($params['params']['dataparams']["received"],'266',null,false,'1px solid ','','R','Century Gothic','12','B','','');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->endtable();


  //   $str .= $this->reporter->endreport();
  //   return $str;
  // }//end fn


























} //end class
