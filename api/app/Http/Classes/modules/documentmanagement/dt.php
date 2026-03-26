<?php

namespace App\Http\Classes\modules\documentmanagement;

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

class dt
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DOCUMENT ENTRY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $tablenum = 'docunum';
  public $head = 'dt_dthead';
  public $hhead = 'hdt_dthead';
  public $stock = 'dt_dtstock';
  public $hstock = 'hdt_dtstock';
  public $tablelogs = 'docunum_log';
  public $tablelogs_del = 'del_docunum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'terms', 'isapproved', 'clientid', 'invdate', 'dtdivid', 'dtdivname', 'invoiceno', 'due', 'poref', 'title', 'forex', 'amt', 'doctypeid', 'documenttype', 'costcenterid'];
  // private $except = ['trno','dateid','due'];
  private $except = [];
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
      'view' => 2487,
      'edit' => 2488,
      'new' => 2489,
      'save' => 2490,
      // 'change'=>2491, remove change doc
      'delete' => 2492,
      'print' => 2493,
      'lock' => 2494,
      'unlock' => 2495,
      'post' => 2496,
      'unpost' => 2497,
      'additem' => 2498
    );
    return $attrib;
  }


  public function createdoclisting()
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname'];
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
    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

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
    $qry = "select head.trno, head.docno, head.clientid, client.client, client.clientname, left(head.dateid,10) as dateid, 'DRAFT' as status, head.createby, head.editby, head.viewby, num.postedby
      from $this->head as head
      left join $this->tablenum as num on num.trno=head.trno
      left join client on client.clientid = head.clientid
      where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? $condition " . $filtersearch . "
      union all
      select head.trno, head.docno, head.clientid, client.client, client.clientname, left(head.dateid,10) as dateid, 'POSTED' as status, head.createby, head.editby, head.viewby, num.postedby
      from $this->hhead as head
      left join $this->tablenum as num on num.trno=head.trno
      left join client on client.clientid = head.clientid
      where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? $condition " . $filtersearch . "";
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
    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrydocunumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }


  public function createTab($access, $config)
  {
    $column = ['statusdoc', 'dateid', 'username', 'usertype', 'issues', 'details', 'rem'];
    $sortcolumn = ['statusdoc', 'dateid', 'username', 'usertype', 'issues', 'details', 'rem'];
    $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $sortcolumn, 'headgridbtns' => ['dtaddstatus']]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    $obj[0][$this->gridname]['label'] = 'Document Status';

    $obj[0][$this->gridname]['columns'][0]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][0]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:150px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][3]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][4]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:150px;whiteSpace:normal;';

    $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][6]['style'] = 'width:150px;whiteSpace:normal;';

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['dtaddstatus'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'forex', 'documenttype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'client.lookupclass', 'dtvendor');
    data_set($col1, 'docno.label', 'Barcode Ref#');

    $fields = ['dateid', 'terms', 'dtdivcode', 'due', 'amt'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'amt.label', 'Amount');

    $fields = ['invdate', 'invoiceno', 'dtdivname', 'email', 'isapproved'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'email.class', 'sbccsreadonly');
    data_set($col3, 'invoiceno.label', 'Invoice No.');

    $fields = ['poref', 'title', 'costcenter', 'tin'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'costcenter.label', 'Cost Center');
    data_set($col4, 'tin.class', 'sbccsreadonly');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['clientid'] = 0;
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['forex'] = '';
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['terms'] = '';
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['amt'] = '';
    $data[0]['dtdivid'] = 0;
    $data[0]['dtdivname'] = '';
    $data[0]['invdate'] = $this->othersClass->getCurrentDate();
    $data[0]['invoiceno'] = '';
    $data[0]['email'] = '';
    $data[0]['poref'] = '';
    $data[0]['title'] = '';
    $data[0]['costcenterid'] = '';
    $data[0]['code'] = '';
    $data[0]['name'] = '';
    $data[0]['tin'] = '';
    $data[0]['doctypeid'] = 0;
    $data[0]['documenttype'] = '';
    $data[0]['isapproved'] = '0';
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

    $qryselect = "select
        head.trno, head.docno, head.dateid,
        head.terms, head.isapproved, head.clientid,
        client.client, client.clientname,
        head.invdate, dt_division.division as dtdivname, head.invoiceno,
        head.due, dt_documenttype.documenttype,
        head.doctypeid, head.divid as dtdivid,
        project.code, project.name, project.line as costcenterid,
        head.poref, head.title,
        num.center, client.email,
        client.tin, head.forex,
        head.amt ";
    $qry = "$qryselect from $table as head
        left join $tablenum as num on num.trno=head.trno
        left join client on head.clientid=client.clientid
        left join dt_division on dt_division.id=head.divid
        left join dt_documenttype on dt_documenttype.id=head.doctypeid
        left join projectmasterfile as project on project.line=head.costcenter
        where head.trno=? and num.center=?
        union all
        $qryselect from $htable as head
        left join $tablenum as num on num.trno=head.trno
        left join client on head.clientid=client.clientid
        left join dt_division on dt_division.id=head.divid
        left join dt_documenttype on dt_documenttype.id=head.doctypeid
        left join projectmasterfile as project on project.line=head.costcenter
        where head.trno=? and num.center=?";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      if ($head[0]->isapproved == 1) {
        $head[0]->isapproved = '1';
      } else {
        $head[0]->isapproved = '0';
      }
      $stock = $this->othersClass->opendtstock($trno);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) $msg = $config['msg'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $hideobj = ['rem' => false, 'clientname' => false];
      $hidetabbtn = ['btndeleteallitem' => false];
      $clickobj = ['button.btnadditem'];
      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,
        'hideobj' => $hideobj, 'clickobj' => $clickobj, 'hidetabbtn' => $hidetabbtn
      ];
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
    // if($isupdate){
    //   unset($this->fields[1]);
    //   unset($head['docno']);
    // }
    foreach ($this->fields as $key) {
      $data[$key] = $head[$key];
      if (!in_array($key, $this->except)) {
        $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
      } //end if
    }
    $data2['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data2['editby'] = $config['params']['user'];
    $data2['viewby'] = '';
    $data2['viewdate'] = null;
    $data2['lockuser'] = '';
    $data2['lockdate'] = null;
    $data2['divid'] = $data['dtdivid'];
    $data2['doctypeid'] = $data['doctypeid'];
    $data2['trno'] = $data['trno'];
    $data2['docno'] = $data['docno'];
    $data2['dateid'] = $data['dateid'];
    $data2['terms'] = $data['terms'];
    $data2['isapproved'] = $data['isapproved'];
    $data2['clientid'] = $data['clientid'];
    $data2['invdate'] = $data['invdate'];
    $data2['invoiceno'] = $data['invoiceno'];
    $data2['due'] = $data['due'];
    $data2['poref'] = $data['poref'];
    $data2['title'] = $data['title'];
    $data2['forex'] = $data['forex'];
    $data2['amt'] = $data['amt'];
    $data2['costcenter'] = $data['costcenterid'];

    if ($isupdate) {
      $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data2['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data2, ['trno' => $head['trno']]);
      // $this->logger->sbcwritelog($head['trno'],$config,'UPDATE',$data2['docno'].' - '.$head['client'].' - '.$head['clientname']);
    } else {
      $data2['doc'] = $config['params']['doc'];
      $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data2['createby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->head, $data2) > 0) {
        $this->savereceivedstatus($config);
        $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
      }
    }
  } // end function

  public function savereceivedstatus($config)
  {
    $userid = $this->coreFunctions->getfieldvalue('useraccess', 'userid', 'username=?', [$config['params']['user']]);
    $usertypeid = $this->coreFunctions->getfieldvalue('useraccess', 'accessid', 'username=?', [$config['params']['user']]);
    $statusid = $this->coreFunctions->getfieldvalue('dt_statuslist', 'id', "alias='RECEIVED'", []);
    $docstatusid = $this->coreFunctions->getfieldvalue('dt_status', 'id', 'statusdoc=? and userid=?', [$statusid, $usertypeid]);
    $dateid = date('Y-m-d H:i:s');

    $line = $this->coreFunctions->datareader("select line as value from $this->stock where trno=? order by line desc limit 1", [$config['params']['head']['trno']]);
    if ($line == '') $line = 0;
    $line = $line + 1;

    $data = [
      'trno' => $config['params']['head']['trno'],
      'line' => $line,
      'userid' => $userid,
      'usertypeid' => $usertypeid,
      'dateid' => $dateid,
      'docstatusid' => $docstatusid
    ];
    if ($docstatusid != '') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) > 0) {
        $this->coreFunctions->execqry("update dt_dthead set currentstatusid=?, currentdate=?, currentuserid=?, currentusertypeid=? where trno=?", 'update', [$docstatusid, $dateid, $userid, $usertypeid, $config['params']['head']['trno']]);
      }
    }
  }



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
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);

    $this->othersClass->deleteattachments($config);

    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    $qry = "insert into $this->hhead (trno, doc, docno, dateid, terms, isapproved, clientid, invdate, divid, invoiceno, due, docstatusid,
      poref, title, forex, amt, currentstatusid, currentdate, currentuserid, currentusertypeid, createby, createdate, editby, editdate,
      viewby, viewdate, lockdate, lockuser, doctypeid, costcenter) select trno, doc, docno, dateid, terms, isapproved, clientid, invdate, divid, invoiceno, due, docstatusid,
      poref, title, forex, amt, currentstatusid, currentdate, currentuserid, currentusertypeid, createby, createdate, editby, editdate,
      viewby, viewdate, lockdate, lockuser, doctypeid, costcenter from $this->head where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $qry = "insert into $this->hstock (trno, line, docstatusid, issueid, detailid, rem, dateid, userid, usertypeid)
        select trno, line, docstatusid, issueid, detailid, rem, dateid, userid, usertypeid from $this->stock where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from $this->stock where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from $this->head where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
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
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into $this->head (trno, doc, docno, dateid, terms, isapproved, clientid, invdate, divid, invoiceno, due, docstatusid,
      poref, title, forex, amt, currentstatusid, currentdate, currentuserid, currentusertypeid, createby, createdate, editby, editdate,
      viewby, viewdate, lockdate, lockuser, doctypeid, costcenter) select trno, doc, docno, dateid, terms, isapproved, clientid, invdate, divid, invoiceno, due, docstatusid,
      poref, title, forex, amt, currentstatusid, currentdate, currentuserid, currentusertypeid, createby, createdate, editby, editdate,
      viewby, viewdate, lockdate, lockuser, doctypeid, costcenter from $this->hhead where trno=? limit 1";
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into $this->stock (trno, line, docstatusid, issueid, detailid, rem, dateid, userid, usertypeid)
        select trno, line, docstatusid, issueid, detailid, rem, dateid, userid, usertypeid from $this->hstock where trno=?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update $this->tablenum set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from $this->hhead where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from $this->hstock where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno, stock.line, stock.docstatusid, stock.issueid,
      issue.issues, stock.detailid, detail.details, stock.rem, stock.dateid,
      users.username as usertype, useraccess.username, '' as bgcolor, statuslist.status as statusdoc";
    return $sqlselect;
    $sqlselect = "";
  }

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $qry = "$sqlselect from $this->stock as stock
      left join dt_status on dt_status.id=stock.docstatusid
      left join dt_statuslist as statuslist on statuslist.id=dt_status.statusdoc
      left join dt_issues as issue on issue.id=stock.issueid
      left join dt_details as detail on detail.id=stock.detailid
      left join users on users.idno=stock.usertypeid
      left join useraccess on useraccess.userid=stock.userid
      where stock.trno=? and stock.line=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'statuslist':
        return $this->savestatus($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function savestatus($config)
  {
    $trno = $config['params']['trno'];
    $userid = $this->coreFunctions->getfieldvalue('useraccess', 'userid', 'username=?', [$config['params']['user']]);
    $usertypeid = $this->coreFunctions->getfieldvalue('useraccess', 'accessid', 'username=?', [$config['params']['user']]);
    $dateid = date('Y-m-d');
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $config['params']['trno'] = $trno;
      $config['params']['data']['userid'] = $userid;
      $config['params']['data']['usertypeid'] = $usertypeid;
      $config['params']['data']['dateid'] = $dateid;
      $config['params']['data']['docstatusid'] = $value['id'];
      $return = $this->additem('insert', $config);
      array_push($rows, $return['row'][0]);
    }
    return ['row' => $rows, 'status' => true, 'msg' => 'Status Added Successfull...'];
  }

  public function additem($action, $config)
  {
    $trno = $config['params']['trno'];
    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from $this->stock where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') $line = 0;
      $line = $line + 1;
    } else {
      $line = $config['params']['data']['line'];
    }
    $config['params']['line'] = $line;
    $data = [
      'trno' => $trno,
      'line' => $line,
      'userid' => $config['params']['data']['userid'],
      'usertypeid' => $config['params']['data']['usertypeid'],
      'dateid' => $config['params']['data']['dateid'],
      'docstatusid' => $config['params']['data']['docstatusid']
    ];
    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->coreFunctions->execqry("update dt_dthead set currentstatusid=?, currentdate=?, currentuserid=?, currentusertypeid=? where trno=?", 'update', [$data['docstatusid'], $data['dateid'], $data['userid'], $data['usertypeid'], $data['trno']]);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Add subject successfully.'];
      } else {
        return ['status' => false, 'msg' => 'Add item failed'];
      }
    } else if ($action == 'update') {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]) > 0) {
        $this->coreFunctions->execqry("update dt_dthead set currentstatusid=?, currentdate=?, currentuserid=?, currentusertypeid=? where trno=?", 'update', [$data['docstatusid'], $data['dateid'], $data['userid'], $data['usertypeid'], $data['trno']]);
      }
    }
    return true;
  }

  public function updateitem($config)
  {
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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
