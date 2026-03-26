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

class cv
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CHECK VOUCHER';
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
  public $defaultContra = 'AP1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'forex', 'cur', 'address', 'tax', 'vattype', 'project', 'ewt', 'ewtrate'];
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
      'view' => 117,
      'edit' => 118,
      'new' => 119,
      'save' => 120,
      'change' => 121,
      'delete' => 122,
      'print' => 123,
      'lock' => 124,
      'unlock' => 125,
      'post' => 126,
      'unpost' => 127,
      'additem' => 128,
      'edititem' => 129,
      'deleteitem' => 130
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
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
    // $searchfilter = $config['params']['search'];
    // if($searchfilter!=''){
    //   $condition = " and (head.docno like '%".$searchfilter."%' or head.clientname like '%".$searchfilter."%')";
    // }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname'];
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
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     order by dateid desc, docno desc limit 150";

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

  public function createTab($access, $config)
  {
    $tab = [
      $this->gridname => ['gridcolumns' => [
        'action',
        'isvewt', 'isvat', 'isewt', 'db', 'cr', 'postdate', 'checkno', 'ewtcode', 'ewtrate', 'rem', 'client', 'ref'
      ]],
      'adddocument' => ['event' => ['lookupclass' => 'entrycntnumpicture', 'action' => 'documententry', 'access' => 'view']]
    ];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // 11 - ref 
    $obj[0]['accounting']['columns'][12]['lookupclass'] = 'refcv';
    //10 - client      
    $obj[0]['accounting']['columns'][11]['lookupclass'] = 'vendordetail';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['unpaid', 'additem', 'saveitem', 'deleteallitem', 'generateewt'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "ADD ACCOUNT";
    $obj[1]['action'] = "adddetail";
    $obj[2]['label'] = "SAVE ACCOUNT";
    $obj[3]['label'] = "DELETE ACCOUNT";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'dvattype', 'dprojectname', 'dewt'];
    $col2 = $this->fieldClass->create($fields);

    $fields = [['yourref', 'ourref'], ['cur', 'forex']];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['rem'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
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
    $data[0]['address'] = '';
    $data[0]['yourref'] = '';
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = 'P';
    $data[0]['project'] = '';
    $data[0]['projectname'] = '';
    $data[0]['tax'] = 0;
    $data[0]['ewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
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
         head.tax,
         head.ewt,head.ewtrate,'' as dewt, 
         head.vattype,
         '' as dvattype,
         left(head.due,10) as due, 
         head.project,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         client.groupid  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.code=head.project 
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.code=head.project         
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
  d.project,ifnull(proj.name,'') as projectname,d.cur,d.forex,
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
    left join coa on d.acnoid=coa.acnoid
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid
    left join projectmasterfile as proj on proj.code = d.project
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
      case 'getunpaidselected':
        return $this->getunpaidselected($config);
        break;
      case 'generateewt':
        return $this->generateewt($config);
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
        return ['accounting' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
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
    $rem = $config['params']['data']['rem'];
    $project = $config['params']['data']['project'];
    $client = $config['params']['data']['client'];
    $refx = 0;
    $linex = 0;
    $ref = '';
    $checkno = '';
    $isewt = false;
    $isvat = false;
    $isvewt = false;
    $ewtcode = '';
    $ewtrate = '';
    $damt = 0;
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
    if (isset($config['params']['data']['isvat'])) {
      $isvat = $config['params']['data']['isvat'];
    }
    if (isset($config['params']['data']['isewt'])) {
      $isewt = $config['params']['data']['isewt'];
    }
    if (isset($config['params']['data']['ewtcode'])) {
      $ewtcode = $config['params']['data']['ewtcode'];
    }

    if ($ewtcode == '') {
      $ewtcode = $this->coreFunctions->getfieldvalue($this->head, "ewt", "trno=?", [$trno]);
    }

    if (isset($config['params']['data']['ewtrate'])) {
      $ewtrate = $config['params']['data']['ewtrate'];
    }

    if ($ewtrate == '') {
      $ewtrate = $this->coreFunctions->getfieldvalue($this->head, "ewtrate", "trno=?", [$trno]);
    }

    if (isset($config['params']['data']['isvewt'])) {
      $isvewt = $config['params']['data']['isvewt'];
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
      'project' => $project,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'checkno' => $checkno,
      'isewt' => $isewt,
      'isvat' => $isvat,
      'isvewt' => $isvewt,
      'ewtcode' => $ewtcode,
      'ewtrate' => $ewtrate,
      'damt' => $damt
    ];
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';
    $status = true;

    if ($isvewt == "true" && ($isewt == "true" || $isvat == "true")) {
      $msg = 'Already tagged as VEWT, remove tagging for EWT/VAT';
      return ['status' => false, 'msg' => $msg];
    }

    $cbalias = $this->coreFunctions->getfieldvalue("coa", "left(alias,2)", "acnoid=?", [$acnoid]);
    if ($cbalias == 'CB' && $checkno != '') {
      $qry = "select trno as value from (select trno from ladetail where cr<>0 and acnoid = " . $acnoid . " and trno <> " . $trno . " and checkno ='" . $checkno . "' union all
            select trno from gldetail where cr<>0 and acnoid = " . $acnoid . " and trno <> " . $trno . " and checkno ='" . $checkno . "') as a limit 1";
      $isexist = $this->coreFunctions->datareader($qry, [], '', true);
      if ($isexist != 0) {
        $msg = 'Check number already exist.';
        return ['status' => false, 'msg' => $msg];
      }
    }

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' db:' . $db . ' cr:' . $cr . ' client:' . $client . ' date:' . $postdate);
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $msg = "Payment Amount is greater than Amount Setup";
            $status = false;
          }
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
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
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
    $data = $this->coreFunctions->opentable('select coa.acno,t.refx,t.linex from ' . $this->detail . ' as t left join coa on coa.acnoid=t.acnoid where t.trno=? and t.refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config);
    }
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }



  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->opendetailline($config);
    //if(($data[0]->qa == $data[0]->qty)){
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    if ($data[0]->refx != 0) {
      $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' code:' . $data[0]['acno'] . ' db:' . $data[0]['db'] . ' cr:' . $data[0]['cr'] . ' client:' . $data[0]['client'] . ' date:' . $data[0]['postdate'] . ' ref:' . $data[0]['ref']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end function

  public function getunpaidselected($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $data = $config['params']['rows'];
    foreach ($data as $key => $value) {
      $config['params']['data']['acno'] = $data[$key]['acno'];
      $config['params']['data']['acnoname'] = $data[$key]['acnoname'];
      if ($data[$key]['db'] != 0) {
        $config['params']['data']['db'] = 0;
        $config['params']['data']['cr'] = $data[$key]['bal'];
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = abs($data[$key]['fdb']);
      } else {
        $config['params']['data']['db'] = $data[$key]['bal'];
        $config['params']['data']['cr'] = 0;
        $config['params']['data']['fdb'] = $data[$key]['fdb'];
        $config['params']['data']['fcr'] = 0;
      }
      $config['params']['data']['postdate'] = $data[$key]['dateid'];
      $config['params']['data']['rem'] = $data[$key]['rem'];
      $config['params']['data']['project'] = $data[$key]['project'];
      $config['params']['data']['client'] = $data[$key]['client'];
      $config['params']['data']['refx'] = $data[$key]['trno'];
      $config['params']['data']['linex'] = $data[$key]['line'];
      $config['params']['data']['ref'] = $data[$key]['docno'];

      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function generateewt($config)
  {
    $trno = $config['params']['trno'];
    $data = $config['params']['row'];
    $status = true;
    $msg = '';
    $entry = [];
    $vatrate = 0;
    $vatrate2 = 0;
    $vatvalue = 0;
    $ewtvalue = 0;
    $dbval = 0;
    $crval = 0;
    $db = 0;
    $cr = 0;
    $damt = 0;
    $line = 0;
    $forex = $data[0]['forex'];
    $cur = $data[0]['cur'];
    $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['APWT1']);
    $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);
    $project = $this->coreFunctions->getfieldvalue($this->head, 'project', 'trno=?', [$trno]);

    if (empty($ewtacno) || empty($taxacno)) {
      $status = false;
      $msg = "Please setup account for EWT and Input VAT";
    } else {

      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");
      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $taxacno, "delete");

      foreach ($data as $key => $value) {
        if ($value['isvat'] == true or $value['isewt'] == true or $value['isvewt'] == true) {
          $damt   = $value['damt'];

          if ($value['isvewt'] == 'true') { //for vewt
            if (floatval($value['db']) != 0) {
              $dbval = $damt;
              $crval = 0;
              $ewtvalue = $ewtvalue + (($dbval / 1.12) * ($value['ewtrate'] / 100));
            } else {
              $dbval = 0;
              $crval = $damt;
              $ewtvalue = $ewtvalue + ((($crval / 1.12) * ($value['ewtrate'] / 100)) * -1);
            }
          }

          if ($value['isvat']  == 'true') { //for vat computation
            $vatrate = 1.12;
            $vatrate2 = .12;

            if (floatval($value['db']) != 0) {
              $dbval = $damt / $vatrate;
              $crval  = 0;
              $vatvalue = $vatvalue + ($dbval * $vatrate2);
            } else {
              $dbval = 0;
              $crval = $damt / $vatrate;
              $vatvalue =  $vatvalue + (($crval * $vatrate2) * -1);
            }
          }

          if ($value['isewt']  == 'true') { //for ewt
            if (floatval($value['db']) != 0) {
              if ($value['isvat'] == 'true') {
                $dbval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
              } else {
                $dbval = $damt;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
              }
              $crval = 0;
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              } else {
                $crval = $damt;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              }
              $dbval = 0;
            }
          }


          $ret = $this->coreFunctions->execqry("update ladetail set db = " . round($dbval, 2) . ",cr=" . round($crval, 2) . ",fdb=" . round($dbval * $value['forex'], 2) . ",fcr=" . round($crval * $value['forex'], 2) . " where trno = " . $trno . " and line = " . $value['line'], "update");
          if ($value['refx'] != 0) {
            if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
              $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
              $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
              $msg = "Payment Amount is greater than Amount Setup";
              $status = false;
              $vatvalue = 0;
              $ewtvalue = 0;
            }
          }
        }
      }

      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;


      if ($vatvalue != 0) {
        $entry = [
          'line' => $line, 'acnoid' => $taxacno, 'client' => $data[0]['client'], 'cr' => ($vatvalue < 0 ? abs(round($vatvalue, 2)) : 0), 'db' => ($vatvalue < 0 ? 0 : abs(round($vatvalue, 2))), 'postdate' => $data[0]['dateid'], 'fdb' => ($vatvalue < 0 ? 0 : abs($vatvalue)) * $forex, 'fcr' => ($vatvalue < 0 ? abs($vatvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'project' => $project
        ];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }



      if ($ewtvalue != 0 && $status == true) {
        $entry = ['line' => $line, 'acnoid' => $ewtacno, 'client' => $data[0]['client'], 'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))), 'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex, 'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex, 'project' => $project];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
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
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY SUCCESS');
          $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
          $status = true;
          //return true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
          $status = false;
        }
      }
    } //if (empty($ewtacno) || empty($taxacno)){

    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'checked', 'payor', 'tin', 'position', 'refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioreporttype.label', 'Print Cash/Check Voucher');
    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'VOUCHER', 'value' => '0', 'color' => 'blue'],
        ['label' => 'CHECK', 'value' => '1', 'color' => 'blue'],
        ['label' => 'METROBANK CHECK', 'value' => '3', 'color' => 'blue'],
        ['label' => 'BIR Form 2307', 'value' => '2', 'color' => 'blue']
      ]
    );
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'default' as print,
      '' as prepared,
      '' as approved,
      '' as received,
      '' as checked,
      '' as payor,
      '' as position,
      '' as tin,
      '0' as reporttype
      "
    );
  }

  private function report_default_query($filters)
  {
    $trno = $filters['params']['dataid'];

    switch ($filters['params']['dataparams']['reporttype']) {
      case 2:
        $query = "select * from(
      select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
      head.address,detail.rem, head.yourref, head.ourref,client.tin,
      coa.acno, coa.acnoname, detail.ref,detail.postdate,
      detail.db, detail.cr, detail.client as dclient, detail.checkno,
      detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
      client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno
      left join client on client.client=head.client
      left join ewtlist on ewtlist.code = detail.ewtcode
      left join cntnum on cntnum.trno = head.trno
      left join center on center.code = cntnum.center
      left join coa on coa.acnoid=detail.acnoid
      where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
      union all
      select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
      head.address,detail.rem, head.yourref, head.ourref,client.tin,
      coa.acno, coa.acnoname, detail.ref, detail.postdate,
      detail.db, detail.cr, dclient.client as dclient, detail.checkno,
      detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
      client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno
      left join client on client.clientid=head.clientid
      left join coa on coa.acnoid=detail.acnoid
      left join client as dclient on dclient.clientid=detail.clientid
      left join ewtlist on ewtlist.code = detail.ewtcode
      left join cntnum on cntnum.trno = head.trno
      left join center on center.code = cntnum.center
      where head.doc='cv' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
      as tbl order by tbl.ewtdesc";

        $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        $arrs = [];
        $arrss = [];
        $ewt = '';
        foreach ($result1 as $key => $value) {
          $ewtrateval = floatval($value['ewtrate']) / 100;
          if ($value['db'] == 0) {
            //FOR CR
            if ($value['cr'] < 0) {
              $db = $value['cr'];
            } else {
              $db = floatval($value['cr']) * -1;
            } //end if

            if ($value['isvewt'] == 1) {
              $db = $db / 1.12;
            }

            $ewtamt = $db * $ewtrateval;
          } else {
            //FOR DB
            if ($value['db'] < 0) {
              $db = floatval($value['db']) * -1;
            } else {
              $db = $value['db'];
            } //end if

            if ($value['isvewt'] == 1) {
              $db = $db / 1.12;
            }
            $ewtamt = $db * $ewtrateval;
          } //end if

          if ($ewt != $value['ewtcode']) {
            $arrs[$value['ewtcode']]['oamt'] = $db;
            $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
            $arrs[$value['ewtcode']]['month'] = $value['month'];
          } else {
            array_push($arrss, $arrs);
            $arrs[$value['ewtcode']]['oamt'] = $db;
            $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
            $arrs[$value['ewtcode']]['month'] = $value['month'];
          }

          $ewt = $value['ewtcode'];
        } //end for each

        array_push($arrss, $arrs);
        $keyers = '';
        $finalarrs = [];

        foreach ($arrss as $key => $value) {
          foreach ($value as $key => $y) {
            if ($keyers == '') {
              $keyers = $key;
              $finalarrs[$key]['oamt'] = $y['oamt'];
              $finalarrs[$key]['xamt'] = $y['xamt'];
            } else {
              if ($keyers == $key) {
                $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($y['oamt']);
                $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($y['xamt']);
              } else {
                $finalarrs[$key]['oamt'] = $y['oamt'];
                $finalarrs[$key]['xamt'] = $y['xamt'];
              } //end if
            } //end if
            $finalarrs[$key]['month'] = $y['month'];
          }
          // if($keyers == ''){                    
          //   $keyers = $key;
          //   $finalarrs[$key]['oamt'] = $value['oamt'];
          //   $finalarrs[$key]['xamt'] = $value['xamt'];
          // }else{
          //   if($keyers == $key){
          //     $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($value['oamt']);
          //     $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($value['xamt']);
          //   }else{
          //     $finalarrs[$key]['oamt'] = $value['oamt'];
          //     $finalarrs[$key]['xamt'] = $value['xamt'];
          //   }//end if
          // }//end if
          // $finalarrs[$key]['month'] = $value['month'];
        } //end for each
        if (empty($result1)) {
          $returnarr[0]['payee'] = '';
          $returnarr[0]['tin'] = '';
          $returnarr[0]['payortin'] = '';
          $returnarr[0]['address'] = '';
          $returnarr[0]['month'] = '';
          $returnarr[0]['yr'] = '';
          $returnarr[0]['payorcompname'] = '';
          $returnarr[0]['payoraddress'] = '';
          $returnarr[0]['payorzipcode'] = '';
        } else {
          $returnarr[0]['payee'] = $result1[0]['clientname'];
          $returnarr[0]['tin'] = $result1[0]['tin'];
          $returnarr[0]['payortin'] = $result1[0]['payortin'];
          $returnarr[0]['address'] = $result1[0]['address'];
          $returnarr[0]['month'] = $result1[0]['month'];
          $returnarr[0]['yr'] = $result1[0]['yr'];
          $returnarr[0]['payorcompname'] = $result1[0]['payorcompname'];
          $returnarr[0]['payoraddress'] = $result1[0]['payoraddress'];
          $returnarr[0]['payorzipcode'] = $result1[0]['payorzipcode'];
        }

        $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
        break;

      default:
        $query = "select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%Y-%m-%d')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
      DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
      date(head.dateid) as dateid, 
      date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
      client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
      coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
      detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno
      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
      left join client on client.client=head.client)left join coa on coa.acnoid=detail.acnoid
      left join glhead as head2 on head2.trno = detail.refx
      left join cntnum on cntnum.trno=head.trno
      LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
      where head.doc='cv' and head.trno ='$trno'
      union all
      select ifnull(DATE_FORMAT(cb.checkdate,'%Y-%m-%d'),DATE_FORMAT(head.dateid,'%Y-%m-%d')) as kdate, ifnull(head2.yourref,'') as dyourref,detail.rem as drem,
      DATE_FORMAT(left(detail.postdate,10),'%b %d %Y') as pdate,detail.ref,head.trno, head.docno, 
      date(head.dateid) as dateid, 
      date(cntnum.postdate) as postdate,client.client, head.clientname, head.address,
      client.tin, '' as busstyle, head.terms, head.yourref, head.ourref, head.rem, coa.acno,
      coa.acnoname, detail.rem as drem,round(detail.db,2) as db,round(detail.cr,2) as cr, 
      detail.checkno, left(coa.alias,2) as alias, head2.yourref as invoiceno
      from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
      left join client on client.clientid=head.clientid)left join coa on coa.acnoid=detail.acnoid
      left join glhead as head2 on head2.trno = detail.refx
      left join cntnum on cntnum.trno=head.trno
      LEFT JOIN cbledger AS cb ON cb.trno = detail.trno AND cb.line = detail.line
      where head.doc='cv' and head.trno ='$trno'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        break;
    } // end switch
    return $result;
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    $data = $this->report_default_query($config);
    switch ($config['params']['dataparams']['reporttype']) {
      case 0: // VOUCHER
        $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $config);
        break;
      case 1:
        $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT2($data, $config);
        break;
      case 2:
        $str = $this->rpt_CV_WTAXREPORT($data, $config);
        break;
      case 3:
        $str = $this->METROBANK_CHECK_LAYOUT($data, $config);
        break;
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('CASH/CHECK VOUCHER', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAYEE : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '450', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->col('REFERENCE # :', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '160', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTES : ', '80', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['rem']) ? $data[0]['rem'] : ''), '720', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', '', '30px', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CHECK DETAILS', '200', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEBIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('CREDIT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('REMARKS', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }
  private function rpt_DEFAULT_CCVOUCHER_LAYOUT1($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 30;
    $page = 30;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totaldb = 0;
    $totalcr = 0;
    for ($i = 0; $i < count($data); $i++) {

      $debit = number_format($data[$i]['db'], $decimal);
      if ($debit < 1) {
        $debit = '-';
      }
      $credit = number_format($data[$i]['cr'], $decimal);
      if ($credit < 1) {
        $credit = '-';
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['checkno'], '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['pdate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($debit, '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($credit, '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($data[$i]['drem'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '2px');

      $totaldb = $totaldb + $data[$i]['db'];
      $totalcr = $totalcr + $data[$i]['cr'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '75', null, false, '1px dotted ', 'T', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '30px', '2px');
    $str .= $this->reporter->col(number_format($totaldb, $decimal), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col(number_format($totalcr, $decimal), '100', null, false, '1px dotted ', 'T', 'R', 'Century Gothic', '12', 'B', '', '2px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function rpt_DEFAULT_CCVOUCHER_LAYOUT2($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
  from ladetail as detail
  left join coa on coa.acnoid = detail.acnoid
  where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' 
  group by 
  detail.checkno,coa.acno,
  detail.cr, detail.postdate
  UNION ALL
  select DATE_FORMAT(left(detail.postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
  from gldetail as detail
  left join coa on coa.acnoid = detail.acnoid
  where trno = " . $data[0]['trno'] . "
  and left(coa.alias,2) = 'CB' 
  group by 
  detail.checkno,coa.acno,
  detail.cr, detail.postdate

  ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
      }
      $cc = $data2[$i]['cr'];
      $cdate = $data2[$i]['postdate'];

      $str .= '<div style="margin-top:-2px;letter-spacing: 3px;">';
      $str .= $this->reporter->beginreport('900');


      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col('', '670', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col(('' . isset($cdate) ? $cdate : ''), '180', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col('', '120', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col($data[0]['clientname'], '720', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col((isset($cc) ? number_format($cc, $decimal) : ''), '150', null, false, '1px solid ', '', 'C', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('920');
      $dd = number_format((float)$cc, 2, '.', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col($this->ftNumberToWordsConverter($dd) . ' ONLY', '900', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();
      $str .= '</div>';
      $this->reporter->linecounter = 30;
    }
    return $str;
  } //end fn

  private function rpt_CV_WTAXREPORT($data, $filters)
  {

    $str = '';
    $count = 60;
    $page = 58;

    $birlogo = URL::to('fimages/reports/birlogo.PNG');
    $birblogo = URL::to('fimages/reports/birbarcode.PNG');

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->endtable();
    $str .= '';

    //1st row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For BIR&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspBCS/<br/>Use Only&nbsp&nbsp&nbspItem:', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    //$str .= $this->reporter->col('BCS/<br/>Item:','10',null,false,'2px solid ','','L','Century Gothic','11','','','');
    $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="60px" height ="60px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue', '60', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('<img src ="'.$birlogo.'" alt="BIR" width="80px" height ="80px">','95',null,false,'2px solid ','LTB','C','Century Gothic','15','B','','8px');
    $str .= $this->reporter->col('BIR Form No. <h4><b> 2307 </b></h4> January 2018 (ENCS)', '135', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('Certificate of Creditable Tax <br> Withheld At Source', '450', null, false, '2px solid ', 'RTB', 'C', 'Century Gothic', '16', 'B', '', '');

    //$str .= $this->reporter->col('','55',null,false,'2px solid ','TB','L','Century Gothic','11','B','','');

    $str .= $this->reporter->col('<img src ="' . $birblogo . '" alt="BIR" width="200px" height ="50px">', '130', null, false, '2px solid ', 'TB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', 'RTB', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"', '100', null, false, '2px solid ', 'LRTB', 'L', 'Century Gothic', '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    //2nd row blank
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //3rd row -> 1 for the period
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('1', '40', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('For the Period', '120', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('From', '70', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');


    switch ($data['head'][0]['month']) {
      case '1':
      case '2':
      case '3':
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        //$str .= $this->reporter->col('','270',null,false,'2px solid','LR','C','Century Gothic','14','','','8px');
        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('03', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      case '4':
      case '5':
      case '6':
        $str .= $this->reporter->col('04', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        //$str .= $this->reporter->col('','270',null,false,'2px solid','LR','C','Century Gothic','14','','','8px');
        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');

        $str .= $this->reporter->col('06', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      case '7':
      case '8':
      case '9':
        $str .= $this->reporter->col('07', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('09', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;

      default:
        $str .= $this->reporter->col('10', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', 'Century Gothic', '10', '', '', '3px');

        $str .= $this->reporter->col('12', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
        break;
    }

    $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //5th row -> part 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part I-Payee Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //6th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //7th row -> 2 tax payer
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('2', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');
    // / $str .= $this->reporter->col('','10',null,false,'2px solid','LRTB','L','Century Gothic','11','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //blank row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //9th row -> 3 payees name

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('3', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Payee`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //payees name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('4', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('4A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //address name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col((isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // 5 foreign address

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('5', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Foreign Address, <i>if applicable <i/>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //f address box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col('', '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '10px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //14th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //part II
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part II-Payor Information', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //16th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TIN payor
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('6', '20', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '3px');
    // / $str .= $this->reporter->col('','10',null,false,'2px solid','LRTB','L','Century Gothic','11','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //payor
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('7', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Payor`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //Payor name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $company_name = (isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : '');

    $str .= $this->reporter->col($company_name, '760', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //registered address
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('8', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('8A', '10', null, false, '2px solid', '', 'L', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //address name box
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('' ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

    //$str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

    $str .= $this->reporter->col((isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col((isset($data['head'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '2px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //22th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //part III
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part III-Details of Monthly Income Payments and Taxes Withheld', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //24th row -> income payments 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '3px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRT', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '380', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '11', 'B', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //25th row -> month header
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '2px');
    $str .= $this->reporter->col('ATC', '80', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('1st Month of the Quarter', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('2nd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('3rd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Total', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Tax Withheld For the Quarter', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //26th row -> blank 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LR', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //27th row -> line
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LTRB', 'C', 'Century Gothic', '12', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //28th row -> atc1
    $str .= $this->reporter->begintable('800');

    $total = 0;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;
    $a = -1;
    foreach ($data['detail'] as $key => $value) {
      $a++;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data['res'][$a]['ewtdesc'], '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '2px');
      $str .= $this->reporter->col($key, '80', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');


      switch ($data['detail'][$key]['month']) {
        case '1':
        case '4':
        case '7':
        case '10':
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx1 +=  $data['detail'][$key]['oamt'];
          break;
        case '2':
        case '5':
        case '8':
        case '11':
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx2 +=  $data['detail'][$key]['oamt'];
          break;
        default:
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
          $totalwtx3 +=  $data['detail'][$key]['oamt'];
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      $str .= $this->reporter->col($total, '95', null, false, '2px solid', 'LRB', 'R', 'Century Gothic', '10', '', '', '');
      $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'], 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '10', '', '', '');

      $totalwtx += $data['detail'][$key]['oamt'];
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //29th row -> total
    $str .= $this->reporter->begintable('800');
    $totaltax = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LR', 'L', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col(($totalwtx != 0 ? number_format($totalwtx, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');

    foreach ($data['detail'] as $key2 => $value2) {

      $totaltax = $totaltax + $data['detail'][$key2]['xamt'];
    }

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LR', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //30th row -> space for total 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //31th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '10', '', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //32th row -> money payments row
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', 'Century Gothic', '11', '', '', '');

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LRB', 'R', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //33th row -> declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent  to the processing of our information as contemplated under  the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful  purposes.', '800', null, false, '2px solid ', 'LRT', 'C', 'Century Gothic', '10', '', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '11', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory from parameter
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '11', 'B', '', '3px');
    $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['payor']), '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['tin'], '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['position']), '175', null, false, '2px solid', '', 'C', 'Century Gothic', '11', 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //line after signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent
    <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TAX Agent
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //39th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
      Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //42th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //43th row -> space after declaration
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CONFORME:', '800', null, false, '2px solid ', 'TLBR', 'C', 'Century Gothic', '10', 'B', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory from parameter
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', 'Century Gothic', '10', 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //line after signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //signatory
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent
    <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', 'Century Gothic', '10', '', '', '');
    //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    //TAX Agent
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //39th row -> signature line 1
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
        Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    //52th row -> blank space after authorized signature 
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', 'Century Gothic', '10', 'B', '', '1px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  private function METROBANK_CHECK_LAYOUT($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';

    $count = 1;
    $page = 30;
    $cc = '';
    $cdate = '';

    $qry = "select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
  from ladetail as detail
  left join coa on coa.acnoid = detail.acnoid
  where trno = " . $data[0]['trno'] . " and left(coa.alias,2) = 'CB' 
  group by 
  detail.checkno,coa.acno,
  detail.cr, detail.postdate
  UNION ALL
  select DATE_FORMAT(left(detail.postdate,10),'%M %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr 
  from gldetail as detail
  left join coa on coa.acnoid = detail.acnoid
  where trno = " . $data[0]['trno'] . "
  and left(coa.alias,2) = 'CB' 
  group by 
  detail.checkno,coa.acno,
  detail.cr, detail.postdate

  ";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    for ($i = 0; $i < count($data2); $i++) {
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();
      }
      $cc = $data2[$i]['cr'];
      $cdate = $data2[$i]['postdate'];

      $str .= '<div style="margin-top:80px;letter-spacing: 3px;">';
      $str .= $this->reporter->beginreport('900');

      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col('', '670', null, false, '1px solid ', '', 'L', 'Verdana', '10', '', '30px', '4px');
      $str .= $this->reporter->col(('' . isset($cdate) ? $cdate : ''), '200', null, false, '1px solid ', '', 'R', 'Verdana', '12', '', '30px', '4px');
      $str .= $this->reporter->col('', '50', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col(strtoupper($data[0]['clientname']), '720', null, false, '1px solid ', '', 'L', 'Verdana', '12', '', '30px', '4px');
      $str .= $this->reporter->col((isset($cc) ? number_format($cc, $decimal) : ''), '150', null, false, '1px solid ', '', 'C', 'Verdana', '12', '', '30px', '4px');
      $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('920');
      $dd = number_format((float)$cc, 2, '.', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'C', 'Verdana', '13', 'B', '30px', '4px');
      $str .= $this->reporter->col($this->ftNumberToWordsConverter($dd) . ' ONLY', '900', null, false, '1px solid ', '', 'L', 'Verdana', '12', '', '30px', '4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();
      $str .= '</div>';
      $this->reporter->linecounter = 30;
    }
    return $str;
  } //end fn

  public function ftNumberToWordsConverter($number)
  {
    $numberwords = $this->ftNumberToWordsBuilder($number);

    if (strpos($numberwords, "/") == false) {
      $numberwords .= " PESOS ";
    } else {
      $numberwords = str_replace(" AND ", " PESOS AND ", $numberwords);
    } //end if

    return $numberwords;
  } //end function convert to words

  private function ftNumberToWordsBuilder($number)
  {
    if ($number == 0) {
      return 'Zero';
    } else {
      $hyphen      = ' ';
      $conjunction = ' ';
      $separator   = ' ';
      $negative    = 'negative ';
      $decimal     = ' and ';
      $dictionary  = array(
        0                   => '',
        1                   => 'One',
        2                   => 'Two',
        3                   => 'Three',
        4                   => 'Four',
        5                   => 'Five',
        6                   => 'Six',
        7                   => 'Seven',
        8                   => 'Eight',
        9                   => 'Nine',
        10                  => 'Ten',
        11                  => 'Eleven',
        12                  => 'Twelve',
        13                  => 'Thirteen',
        14                  => 'Fourteen',
        15                  => 'Fifteen',
        16                  => 'Sixteen',
        17                  => 'Seventeen',
        18                  => 'Eighteen',
        19                  => 'Nineteen',
        20                  => 'Twenty',
        30                  => 'Thirty',
        40                  => 'Forty',
        50                  => 'Fifty',
        60                  => 'Sixty',
        70                  => 'Seventy',
        80                  => 'Eighty',
        90                  => 'Ninety',
        100                 => 'Hundred',
        1000                => 'Thousand',
        1000000             => 'Million',
        1000000000          => 'Billion',
        1000000000000       => 'Trillion',
        1000000000000000    => 'Quadrillion',
        1000000000000000000 => 'Quintillion'
      );

      if (!is_numeric($number)) {
        return false;
      } //end if

      if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        return false;
      } //end if

      if ($number < 0) {
        return $negative . $this->ftNumberToWordsBuilder(abs($number));
      } //end if

      $string = $fraction = null;

      if (strpos($number, '.') !== false) {
        $fractionvalues = explode('.', $number);
        if ($fractionvalues[1] != '00' || $fractionvalues[1] != '0') {
          list($number, $fraction) = explode('.', $number);
        } //end if
      } //end if

      switch (true) {
        case $number < 21:
          $string = $dictionary[$number];
          break;

        case $number < 100:
          $tens   = ((int) ($number / 10)) * 10;
          $units  = $number % 10;
          $string = $dictionary[$tens];
          if ($units) {
            $string .= $hyphen . $dictionary[$units];
          } //end if
          break;

        case $number < 1000:
          $hundreds  = $number / 100;
          $remainder = $number % 100;
          $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
          if ($remainder) {
            $string .= $conjunction . $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;

        default:
          $baseUnit = pow(1000, floor(log($number, 1000)));
          $numBaseUnits = (int) ($number / $baseUnit);
          $remainder = $number % $baseUnit;
          $string = $this->ftNumberToWordsBuilder($numBaseUnits) . ' ' . $dictionary[$baseUnit];
          if ($remainder) {
            $string .= $remainder < 100 ? $conjunction : $separator;
            $string .= $this->ftNumberToWordsBuilder($remainder);
          } //end if
          break;
      } //end switch
      if (null !== $fraction && is_numeric($fraction)) {

        $string .= $decimal . ' ' . $fraction .  '/100';
        $words = array();
        $string .= implode(' ', $words);
      } //end if

      return strtoupper($string);
    } //end
  } //end fn


























} //end class
