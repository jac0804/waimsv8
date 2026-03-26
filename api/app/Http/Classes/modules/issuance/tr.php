<?php

namespace App\Http\Classes\modules\issuance;

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

class tr
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STOCK REQUEST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'trhead';
  public $hhead = 'htrhead';
  public $stock = 'trstock';
  public $hstock = 'htrstock';
  public $detail = '';
  public $hdetail = '';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'reqqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'IS1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'wh', 'projectid', 'deptid'];
  private $except = ['trno', 'dateid'];
  private $headinfofield = ['trno', 'wh2', 'trnxtype'];


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
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 785,
      'edit' => 786,
      'new' => 787,
      'save' => 788,
      'delete' => 790,
      'print' => 791,
      'lock' => 792,
      'unlock' => 793,
      'changeamt' => 842,
      'post' => 794,
      'unpost' => 795,
      'additem' => 839,
      'edititem' => 840,
      'deleteitem' => 841
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listapprovedate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
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
    $limit = "limit 150";

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

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
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby, null as approvedate  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby, head.approvedate  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . " 
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
    $step1 = $this->helpClass->getFields(['btnnew', 'department', 'dateid', 'whcode', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'department', 'dateid', 'whcode', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'reqqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['reqqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $tr_btndisapprove_access = $this->othersClass->checkAccess($config['params']['user'], 3589);


    $headgridbtns = ['viewref', 'generatepr', 'disapprove'];

    if ($tr_btndisapprove_access == 0) {
      unset($headgridbtns[2]);
    }
    $action = 0;
    $reqqty = 1;
    $rrcost = 2;
    $ext = 3;
    $rrqty = 4;
    $qa = 5;
    $uom = 6;
    $rem = 7;
    $wh = 8;
    $itemname = 9;
    $barcode = 10;
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'reqqty', 'rrcost', 'ext', 'rrqty', 'qa', 'uom', 'rem', 'wh', 'itemname', 'barcode'],
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ]
    ];
    $stockbuttons = ['save', 'delete', 'showbalance'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($companyid == 39) { //cbbsi
      $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Amount';
      $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Cost';
    } else {
      $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$ext]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Approved Qty';
    $obj[0][$this->gridname]['columns'][$rrqty]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'hidden';
    $obj[0][$this->gridname]['columns'][$barcode]['label'] = '';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    if ($config['params']['companyid'] == 43) { //mighty
      $this->modulename = 'Transfer Request';
    }

    //col 1
    if ($config['params']['companyid'] == 43 || $config['params']['companyid'] == 39) { // mighty & cbbsi
      $fields = ['docno', 'dept', 'clientname'];
    } else {
      $fields = ['docno', 'client', 'clientname'];
    }
    if ($config['params']['companyid'] == 39) { //cbbsi
      array_push($fields, 'wh');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Department Code');
    data_set($col1, 'client.lookupclass', 'lookupdeptwh');
    data_set($col1, 'clientname.label', 'Department Name');
    data_set($col1, 'docno.label', 'Transaction#');


    if ($config['params']['companyid'] == 43 || $config['params']['companyid'] == 39) { // mighty & cbbsi
      data_set($col1, 'dept.lookupclass', 'lookupdept');
      data_set($col1, 'dept.label', 'Dept Code');
      data_set($col1, 'clientname.label', 'Dept Name');
    }

    //col 2
    switch ($config['params']['companyid']) {
      case 43: //mighty
        $fields = ['dateid', 'wh', 'whname', 'rem'];
        break;
      case 39: //cbbsi
        $fields = ['dateid', 'dwhname2', 'rem'];
        break;
      default:
        $fields = ['dateid', 'wh', 'rem'];
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    if ($config['params']['companyid'] == 43) { //mighty
      data_set($col2, 'wh.label', 'Destination Code');
      data_set($col2, 'whname.label', 'Destination Name');
    }

    //col3
    $fields = ['yourref', 'ourref'];
    if ($config['params']['companyid'] == 43) { //mighty
      array_push($fields, 'dwhname2', 'dprojectname');
    }
    if ($config['params']['companyid'] == 39) { //cbbsi
      data_set($col2, 'dwhname2.required', true);
      data_set($col2, 'dwhname2.label', 'Destination Warehouse');
      array_push($fields, 'trnxtype');
    }
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'ourref.label', 'PR Reference');

    if ($config['params']['companyid'] == 39) { //cbbsi
      data_set($col3, 'trnxtype.required', true);
    }

    //col 4
    $fields = ['approved', 'approvedate'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'ourref.class', 'csourref sbccsreadonly');
    data_set($col4, 'approved.class', 'csapproved sbccsreadonly');
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['trnxtype'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['approved'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;

    if ($params['companyid'] == 43) { //mighty
      $data[0]['wh2'] = '';
    } else {
      $data[0]['wh2'] = $this->companysetup->getwh($params);
    }

    $name1 = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh2']]);
    $wh2id = $this->coreFunctions->getfieldvalue('client', 'client', 'client=?', [$data[0]['wh2']]);

    $data[0]['wh2name'] = $name1;
    $data[0]['whid2'] = $wh2id;


    $data[0]['projectid'] = 0;
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';

    $data[0]['dept'] = '';
    $data[0]['deptid'] = '0';

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
         head.client,
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         '' as dacnoname,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.wh,
         info.trnxtype,
         warehouse.clientname as whname, 
         left(head.due,10) as due, 
         client.groupid,
         head.projectid,ifnull(project.code,'') as projectcode,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
        head.deptid,d.client as dept,
        wh2.clientid as wh2,ifnull(wh2.clientname,'') as wh2name,ifnull(wh2.client,'') as whid2";

    $qry = $qryselect . ",null as approvedate,'' as approved from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join headinfotrans as info on info.trno=head.trno
        left join client as wh2 on wh2.clientid = info.wh2
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
            left join projectmasterfile as project on project.line=head.projectid
              left join client as d on d.clientid = head.deptid
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . ",head.approvedate,head.approved from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join hheadinfotrans as info on info.trno=head.trno
        left join client as wh2 on wh2.clientid = info.wh2
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
            left join projectmasterfile as project on project.line=head.projectid
              left join client as d on d.clientid = head.deptid
        where head.trno = ? and num.doc=? and num.center=? ";


    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
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
    $companyid = $config['params']['companyid'];
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

    $dataother = [];
    foreach ($this->headinfofield as $key) {
      $dataother[$key] = $head[$key];
      $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key]);
    }

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

    if ($config['params']['companyid'] == 39 || $config['params']['companyid'] == 43) { //cbbsi & mighty  
      $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
      if ($infotransexist == '') {
        $this->coreFunctions->sbcinsert("headinfotrans", $dataother);
      } else {
        $dataother['editby'] = $config['params']['user'];
        $dataother['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->sbcupdate("headinfotrans", $dataother, ['trno' => $head['trno']]);
      }
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
    $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and reqqty=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $stock = $this->coreFunctions->datareader("select count(trno) as value from " . $this->stock . " where trno=?", [$config['params']['trno']], '', true);
    if ($stock == 0) {
      return ['status' => false, 'msg' => 'Unable to post, Please add items first...'];
    }

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,yourref,ourref,createdate,createby,
          editby,editdate,lockdate,lockuser,agent,wh,due,cur,trroute,trpricegrp,deptid,projectid)
          SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
          head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
          head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
          head.due,head.cur,head.trroute,head.trpricegrp,head.deptid,head.projectid FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
          where head.trno=? limit 1";


    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      if (!$this->othersClass->postingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,whid,
            disc,cost,qty,void,rrcost,rrqty,ext,encodeddate,qa,encodedby,editdate,editby,loc,rem,expiry,reqqty)
            SELECT trno, line, itemid, uom,whid,disc,cost, qty,void,
            rrcost, rrqty, ext, encodeddate,qa, encodedby,editdate,editby,loc,rem,expiry,reqqty 
            FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $result = $this->checkallowunpost($config);
    if (!$result['status']) {
      return ['trno' => $trno, 'status' => false, 'msg' => $result['msg']];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if (!$this->othersClass->unpostingheadinfotrans($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while unposting head data.'];
    }
    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
        yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,deptid,projectid)
        select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
        head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
        head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.deptid,head.projectid
        from (" . $this->hhead . " as head left join cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
        where head.trno=? limit 1";

    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(trno,line,itemid,uom,whid,disc,cost,qty,void,rrcost,
            rrqty,ext,encodeddate,qa,rem,encodedby,editdate,editby,loc,expiry,reqqty)
            select trno, line, itemid, uom,whid,disc,cost, qty,void, rrcost,
            rrqty, ext, encodeddate, qa,rem, encodedby, editdate, editby,loc,expiry,reqqty 
            from " . $this->hstock . " where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
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

  private function checkallowunpost($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    $qry = "select trno as value from " . $this->hhead . " where trno=? and prdate is not null";
    $data = $this->coreFunctions->datareader($qry, [$trno]);
    if ($data) {
      return ['status' => false, 'msg' => 'UNPOST FAILED, already have purchase requistion reference...'];
    }

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }

    if ($companyid != 43) { //if mighty no need to check if approved
      $qry = "select trno as value from " . $this->hhead . " where trno=? and approvedate is not null";
      $data = $this->coreFunctions->datareader($qry, [$trno]);
      if ($data) {
        return ['status' => false, 'msg' => 'UNPOST FAILED, already approved...'];
      }
    }

    return ['status' => true, 'msg' => ''];
  }

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    item.amt9,
    stock.trno, 
    stock.line,
    stock.refx, 
    stock.linex, 
    item.barcode, 
    item.itemname,
    stock.rrcost,
    stock.uom, 
    FORMAT(stock.reqqty," . $this->companysetup->getdecimal('price', $config['params']) . ") as reqqty,
    stock." . $this->hamt . ", 
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    stock.void, 
    round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,   
    item.brand,
    stock.rem,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    '' as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid where stock.trno =? order by line";

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
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
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
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'disapprove':
        return $this->disapprove($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function disapprove($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $msg = "";
    $status = true;

    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['status' => false, 'msg' => 'DISAPPROVE FAILED, either already served or have item voided...'];
    }

    $qry = "select approved,ifnull(date(approvedate), '') as approvedate from htrhead where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->approvedate == "") {
      $msg = "Already Disapprove! " . $checking[0]->approved . ' ' . $checking[0]->approvedate;
    } else {

      $tag = $this->coreFunctions->execqry("update htrhead set approved='',approvedate = null where trno=? ", "update", [$trno]);
      if ($tag) {
        $tag =    $this->coreFunctions->execqry("update htrstock set rrqty=0,qty=0,ext=0 where trno=? ", "update", [$trno]);
      }

      if ($tag) {
        $msg = "Disapprove Success!";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'DISAPPROVED', 'CLICKED DISAPPROVED');
      } else {
        $msg = "Disapprove Failed!";
        $status = false;
      }
    }

    return ['status' => $status, 'msg' => $msg, 'dd' => 'Disapprove', 'reloadhead' => true];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);

    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Out of Stock'];
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
      if ($data2[$key]['reqqty'] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
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
    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
      $lprice = $this->getlatestprice($config);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) {
        $item[0]['amt'] = $lprice['data'][0]['amt'];
        $item[0]['disc'] = $lprice['data'][0]['disc'];
      }

      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  // insert and update item
  public function additem($action, $config)
  {
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $qty = $config['params']['data']['qty'];
    $wh = $config['params']['data']['wh'];
    $ext = 0;

    $rem = '';
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
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

    $vat = 0;
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $ext = number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', '');
    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'reqqty' => $qty,
      $this->damt => $amt,
      $this->hamt => $computedata['amt'],
      $this->dqty => $qty,
      $this->hqty => 0,
      'ext' => $ext,
      'disc' => $disc,
      'whid' => $whid,
      'uom' => $uom,
      'rem' => $rem

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
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' wh:' . $wh . ' Uom:' . $uom);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
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
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' wh:' . $data[0]['wh'] . ' ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];

    if ($config['params']['companyid'] == 39) { //cbbsi
      $qry =
        "select docno,round(amt,2) as amt,disc,uom from(
          select 'LATEST COST' as docno, i.amt9 as amt,i.disc,i.uom
          from item as i
          where i.barcode = ?
        ) as tbl
       group by docno,amt,uom,disc";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);
    } else {
      $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(
  		  select head.docno,head.dateid,
          stock." . $this->damt . " as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid=stock.itemid
          where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.rrcost <> 0
          UNION ALL
          select head.docno,head.dateid,stock." . $this->damt . " as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc = '" . $config['params']['doc'] . "' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock." . $this->damt . " <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
      $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);
    }

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
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
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));

      $computedata = $this->othersClass->computestock($damt, $data[$key]->disc, $dqty, $data[$key]->uomfactor);
      $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }
} //end class
