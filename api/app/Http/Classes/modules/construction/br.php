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

class br
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BUDGET REQUEST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'brhead';
  public $hhead = 'hbrhead';
  public $stock = 'brstock';
  public $hstock = 'hbrstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'qty';
  public $damt = 'rrcost';
  private $fields = ['trno', 'docno', 'dateid', 'start', 'end', 'address', 'yourref', 'ourref', 'rem', 'projectid', 'subproject'];
  private $except = ['trno', 'dateid', 'due'];
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
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2256,
      'edit' => 2257,
      'new' => 2258,
      'save' => 2259,
      'delete' => 2261,
      'print' => 2262,
      'lock' => 2263,
      'unlock' => 2264,
      'post' => 2265,
      'unpost' => 2266,
      'additem' => 2267,
      'edititem' => 2268,
      'deleteitem' => 2269
    );
    return $attrib;
  }


  public function createdoclisting()
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listprojectname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[2]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[4]['style'] = 'width:600px;whiteSpace: normal;min-width:600px;';
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
    $projectfilter = "";

    $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
    $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);

    if ($projectid != "") {
      $projectfilter = " and head.projectid = " . $projectid . " ";
    }

    $viewall = $this->othersClass->checkAccess($config['params']['user'], 2271);

    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'p.name', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    if ($viewall == '0') {
      if (floatval($projectid) == 0) {
        return ['data' => [], 'status' => false, 'msg' => 'Please assign project for this user...'];
      }
      $projectfilter = " and head.projectid = " . $projectid . " ";
    } else {
      $projectfilter = "";
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $qry = "select head.trno,head.docno,p.name as projectname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join projectmasterfile as p on p.line = head.projectid where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
     union all
     select head.trno,head.docno,p.name as projectname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join projectmasterfile as p on p.line = head.projectid where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $projectfilter . " " . $filtersearch . "
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
    $step1 = $this->helpClass->getFields(['btnnew', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'br', 'title' => 'Budget Request Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {

    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action', 'particulars',
          'qty', 'uom', 'rrcost', 'ext', 'amount', 'status', 'rem'
        ], 'computefield' => ['dqty' => 'qty', 'damt' => 'rrcost', 'total' => 'ext'],
        'headgridbtns' => ['viewref']
      ],
    ];


    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'Quantity';
    $obj[0][$this->gridname]['columns'][4]['label'] = 'Estimated Amount';

    $obj[0][$this->gridname]['columns'][5]['label'] = 'Amount';
    $obj[0][$this->gridname]['columns'][6]['label'] = 'Approved Budget';
    $obj[0][$this->gridname]['columns'][6]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][7]['readonly'] = true;
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0]['inventory']['totalfield'] = 'amount';
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
    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "SAVE ALL";
    $obj[2]['label'] = "DELETE ALL";
    return $obj;
  }

  public function createHeadField($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['docno', 'dprojectname', 'subprojectname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'dprojectname.type', 'input');
    data_set($col1, 'dprojectname.condition', ['checkstock']);
    data_set($col1, 'dprojectname.required', true);
    data_set($col1, 'subprojectname.type', 'lookup');
    data_set($col1, 'subprojectname.lookupclass', 'default');
    data_set($col1, 'subprojectname.action', 'lookupsubproject');
    data_set($col1, 'subprojectname.addedparams', ['projectid']);
    data_set($col1, 'subprojectname.required', true);
    data_set($col1, 'address.label', 'Location');


    $fields = ['dateid', ['yourref', 'ourref']];

    $col2 = $this->fieldClass->create($fields);

    $fields = [['start', 'end'], 'rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'start.required', true);
    data_set($col3, 'end.required', true);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['start'] = $this->othersClass->getCurrentDate();
    $data[0]['end'] = $this->othersClass->getCurrentDate();
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['address'] = '';
    $data[0]['rem'] = '';
    $pid = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$params['user']]);
    $data[0]['projectid'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$pid]);
    $data[0]['projectcode'] =  $pid;
    $data[0]['projectname'] = $this->coreFunctions->getfieldvalue("projectmasterfile", "name", "code=?", [$pid]);
    $data[0]['subproject'] = '0';
    $data[0]['subprojectname'] = '';

    return $data;
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
    $projectfilter = "";

    $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
    $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
    $viewall = $this->othersClass->checkAccess($config['params']['user'], 2271);


    if ($viewall == '0') {
      $projectfilter = " and head.projectid = " . $projectid . " ";
    }


    $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid,
         left(head.start,10) as start,
         left(head.end,10) as end,
         head.address,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,ifnull(project.code,'') as projectcode,s.line as subproject,s.subproject as subprojectname ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join projectmasterfile as project on project.line=head.projectid 
        left join subproject as s on s.line = head.subproject
        where head.trno = ? and num.center = ? " . $projectfilter . "
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join projectmasterfile as project on project.line=head.projectid 
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
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['projectcode'] . ' - ' . $head['projectname']);
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

    $isitemzeroqty = $this->coreFunctions->getfieldvalue($this->stock, "trno", "trno=? and status=0", [$trno]);
    if (floatval($isitemzeroqty) != 0) {
      return ['status' => false, 'msg' => 'Posting failed. There are some pending items in your request; please check.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Trnasaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,address,dateid,start,end,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,subproject,voiddate,openby,users,viewby,viewdate)
      SELECT trno,doc,docno,address,dateid,start,end,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,subproject,voiddate,openby,users,viewby,viewdate FROM " . $this->head . " as head 
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . "(trno,line,particulars,rem,rrcost,amount,qty,uom,ext,qa,void,refx,linex,ref,encodeddate,encodedby,editdate,editby,approvedby,approveddate,status)
        SELECT trno,line,particulars,rem,rrcost,amount,qty,uom,ext,qa,void,refx,linex,ref,encodeddate,encodedby,editdate,editby,approvedby,approveddate,status FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
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
    $qry = "select trno from " . $this->hhead . " where trno=? and bltrno<>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already liquidated...'];
    }

    $qry = "select trno from " . $this->hhead . " where trno=? and cvtrno<>0";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, already have CV...'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,address,dateid,start,end,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,subproject,voiddate,openby,users,viewby,viewdate)
    select trno,doc,docno,address,dateid,start,end,rem,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,projectid,subproject,voiddate,openby,users,viewby,viewdate from " . $this->hhead . " as head 
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(trno,line,particulars,rem,rrcost,amount,qty,uom,ext,qa,void,refx,linex,ref,encodeddate,encodedby,editdate,editby,approvedby,approveddate,status)
      select trno,line,particulars,rem,rrcost,amount,qty,uom,ext,qa,void,refx,linex,ref,encodeddate,encodedby,editdate,editby,approvedby,approveddate,status
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);


        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Unposting Head'];
    }
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno,stock.line,stock.refx,stock.linex,stock.particulars,stock.uom,FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.amount," . $this->companysetup->getdecimal('currency', $config['params']) . ")  as amount,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,case stock.status when 0 then 'Pending' when 1 then 'Approved' else 'Disapproved' end as status,
    case when stock.void=0 then 'false' else 'true' end as void,stock.qa,stock.ref,stock.rem,'' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock where stock.trno =? ";
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
   where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'additem':
        return $this->additem('insert', $config);
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

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];
    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['particulars'] = '';
    $data['amount']  = 0;
    $data['qty'] = 0;
    $data['rrcost'] = 0;
    $data['ext'] = 0;
    $data['rem'] = '';
    $data['uom'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
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

    if ($config['params']['line'] != 0) {
      $this->additem('update', $config);
      $data = $this->openstockline($config);
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      $data = $this->additem('insert', $config);
      if ($data['status'] == true) {
        return ['row' => $data['data'], 'status' => true, 'msg' => 'Successfully saved.'];
      } else {
        return ['row' => $data['data'], 'status' => false, 'msg' => $data['msg']];
      }
    }
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      if ($value['line'] != 0) {
        $this->additem('update', $config);
      } else {
        $this->additem('insert', $config);
      }
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
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function


  // insert and update item
  public function additem($action, $config)
  {

    $trno = $config['params']['trno'];

    $line = $config['params']['data']['line'];
    $particulars = $config['params']['data']['particulars'];
    $qty = $config['params']['data']['qty'];
    $uom = $config['params']['data']['uom'];
    $rrcost = $config['params']['data']['rrcost'];
    $amount = $config['params']['data']['amount'];
    $rem = $config['params']['data']['rem'];
    $ext = $config['params']['data']['ext'];
    $data = [
      'trno'            => $trno,
      'line'            => $line,
      'particulars'       => $particulars,
      'qty'     => $qty,
      'uom'      => $uom,
      'rrcost'      => $rrcost,
      'ext'      => $ext,
      'amount'      => $amount,
      'rem'      => $rem,
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

      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";

      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;

      if ($this->coreFunctions->sbcinsert($this->stock, $data)) {
        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line: ' . $line . ' Particulars: ' . $particulars . ' Amt: ' . $ext);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } else if ($action == 'update') {
      return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
    }
  }

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    if ($data[0]->refx !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line: ' . $line . ' Particulars: ' . $data[0]->particulars . ' Qty: ' . $data[0]->qty);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function


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
