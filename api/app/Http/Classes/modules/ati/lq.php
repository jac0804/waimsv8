<?php

namespace App\Http\Classes\modules\ati;

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

class lq
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Cash Liquidation Form';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'lqhead';
  public $hhead = 'hlqhead';
  public $stock = 'lqstock';
  public $hstock = 'hlqstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = [
    'trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms',
    'forex', 'cur', 'wh', 'address', 'projectid', 'subproject', 'branch', 'deptid', 'tax', 'vattype', 'empid', 'sotrno', 'billid', 'shipid', 'billcontactid', 'shipcontactid',
    'revision', 'rqtrno', 'deldate', 'deladdress'
  ];
  private $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    // ['val' => 'pending', 'label' => 'Pending', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {

    $attrib = array(
      'view' => 3725,
      'edit' => 3726,
      'new' => 3727,
      'save' => 3728,
      // 'change' => 67, remove change doc
      'delete' => 3729,
      'print' => 3730,
      'lock' => 3731,
      'unlock' => 3732,
      'changeamt' => 3733,
      'post' => 3734,
      'unpost' => 3735,
      'additem' => 3736,
      'edititem' => 3737,
      'deleteitem' => 3738,
      'viewamt' => 3739,
      'prbutton' => 3640,
      'voidbutton' => 3741

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
    $listpostedby = 8;
    $listcreateby = 8;
    $listeditby = 10;
    $listviewby = 11;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view', 'diagram'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';

    $cols[$postdate]['label'] = 'Post Date';

    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = ['uploadexcel'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'uploadexcel.label', 'UPLOAD EXCEL');
    $data = [];

    return ['status' => true, 'data' => $data, 'txtfield' => ['col1' => $col1]];
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
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    $dateid = "left(head.dateid,10) as dateid";

    $status = "'DRAFT'";
    $leftjoin = "";
    $leftjoin_posted = "";
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null ';
        break;

        // case 'pending':
        //   $leftjoin = ' left join oqstock as stock on stock.trno=head.trno';
        //   $leftjoin_posted = ' left join hoqstock as stock on stock.trno=head.trno';
        //   $condition = ' and stock.qty>stock.qa';
        //   break;

      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        $status = "'LOCKED'";
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select head.trno,head.docno,head.clientname,$dateid,'Pending' as stat,head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref  
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     " . $leftjoin . "
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,$dateid,stat.status as stat,head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref  
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join trxstatus as stat on stat.line=num.statid 
     " . $leftjoin_posted . "
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     group by head.trno, head.docno, head.clientname, head.dateid, stat.status, head.createby, head.editby, head.viewby, num.postedby, num.postdate, head.yourref, head.ourref 
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


  public function createTab($access, $config)
  {
    $action = 0;
    $requestorname = 1;
    $deptname = 2;
    $sono = 3;
    $reqdate = 4;
    $releasedate = 5;
    $purpose = 6;
    $rem = 7;
    $ref = 8;
    $rrcost = 9;
    $ctrlno = 10;


    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action', 'requestorname', 'deptname', 'sono', 'reqdate', 'releasedate', 'purpose', 'rem', 'ref', 'rrcost', 'ctrlno'
        ]
      ]
    ];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'Cash Advance Liquidation';

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';
    $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';

    $obj[0][$this->gridname]['columns'][$ref]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$ref]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$requestorname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$requestorname]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$deptname]['type'] = 'lookup';

    $obj[0][$this->gridname]['columns'][$sono]['label'] = 'CLR Ref No';
    $obj[0][$this->gridname]['columns'][$purpose]['label'] = 'CRF Release for';
    $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Particulars';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'ADD';
    $obj[1]['label'] = 'SAVE ALL';
    $obj[2]['label'] = 'DELETE ALL';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'ddeptname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.label', 'Date of Request');

    $fields = ['yourref', 'rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'yourref.label', 'Reference No');

    $fields = [];
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
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['projectid'] = 0;
    $data[0]['subprojectname'] = '';
    $data[0]['subproject'] = 0;
    $data[0]['dwhname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['branchcode'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;

    $data[0]['empid'] = '0';
    $data[0]['empname'] = '';
    $data[0]['empcode'] = '';
    $data[0]['tel2'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['sotrno'] = '0';
    $data[0]['sodocno'] = '';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['revision'] = '';
    $data[0]['rqtrno'] = '0';
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['deladdress'] = '';



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

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'CAIMS':
        $addedfield = ", rqhead.docno as yourref, head.rqtrno, left(head.deldate, 10) as deldate, head.deladdress";
        $addedjoin = "left join hprhead as rqhead on rqhead.trno = head.rqtrno";
        break;
      default:
        $addedfield = ", head.yourref, head.rqtrno, left(head.deldate, 10) as deldate, head.deladdress";
        $addedjoin = "";
        break;
    }
    $qryselect = "select
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.terms,
         head.cur,
         head.forex,
         head.ourref,
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         head.shipto,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.billid,
         head.shipid,
         head.billcontactid,
         head.shipcontactid,
         head.vattype,
         '' as dvattype,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         client.groupid,head.projectid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,
         s.line as subproject,s.subproject as subprojectname,head.branch,ifnull(b.clientname,'') as branchname,
         ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname, head.empid, e.clientname as empname,
         e.client as empcode, e.tel2,head.sotrno,ifnull(so.docno,'') as sodocno,
         head.revision
         " . $addedfield . "  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        " . $addedjoin . "
        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join client as e on e.clientid = head.empid
        left join projectmasterfile as p on p.line = head.projectid
        left join subproject as s on s.line = head.subproject
        left join hsqhead as so on so.trno = head.sotrno
        " . $addedjoin . "
        where head.trno = ? and num.center=? ";

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
      //$hideobj = ['lblreceived' => false];
      $hidetabbtn = ['btndeleteallitem' => false];
      $clickobj = ['button.btnadditem'];

      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg,
        'clickobj' => $clickobj, 'hidetabbtn' => $hidetabbtn
      ];
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
      unset($this->fields[1]);
      unset($head['docno']);
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if
      }
    }
    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      //$this->autocreatestock($config, $data);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $insert = $this->coreFunctions->sbcinsert($this->head, $data);
      //  $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
      //$this->autocreatestock($config, $data);
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

    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully loaded.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for hqlhead
    $qry = "insert into " . $this->hhead . "(trno,docno,dateid,rem)
      SELECT head.trno, head.docno,head.dateid, head.rem
      FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";

    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for hqlstock

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,rrqty,ref)
        SELECT trno, line, rrqty,ref
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
        // $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
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
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,dateid,rem)
    select head.trno, head.doc, head.docno, head.client,
    head.dateid,  head.rem
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,rrqty, ref)
      select stock.trno, stock.line, stock.rrqty, stock.ref
      from " . $this->hstock . " as stock where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
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
    $sqlselect = "select 
    stock.trno,
    stock.line,
    stock.uom,
    stock.qty,
    stock.rrqty,
    stock.rrcost,
    stock.cost,
    stock.ext,
    stock.ref,
    info.requestorname,
    info.purpose,
    info.reqdate,
    info.releasedate,
    info.rem,
    stock.deptid,
    ifnull(dept.clientname,'') as deptname,
    info.sono,
    '' as bgcolor,info.ctrlno";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
    left join client as dept on dept.clientid=stock.deptid
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
    left join client as dept on dept.clientid=stock.deptid
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
      left join stockinfotrans as info on info.trno=stock.trno and info.line=stock.line
      left join client as dept on dept.clientid=stock.deptid
      where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function



  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];
    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['rrqty'] = 1;
    $data['qty'] = 1;
    $data['rrcost'] = 0;
    $data['cost'] = 0;
    $data['ext'] = 0;
    $data['ref'] = '';
    $data['requestorname'] = '';
    $data['deptid'] = 0;
    $data['deptname'] = '';
    $data['reqdate'] = $this->othersClass->getCurrentDate();
    $data['releasedate'] = null;
    $data['purpose'] = '';
    $data['rem'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
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



  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'updateitemvoid':
        return $this->updateitemvoid($config);
        break;
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'exportcsv':
        return $this->exportcsv($config, 'F');
        //return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => 'xxx', 'ext' => 'txt', 'csv' => 'abc' . "\t" . 'def' . "\t" . 'ghi' . "\t"];
        break;
      case 'exportcsvd':
        return $this->exportcsv($config, 'D');
        //return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => 'xxx', 'ext' => 'txt', 'csv' => 'abc' . "\t" . 'def' . "\t" . 'ghi' . "\t"];
        break;
      case 'print1':
        return $this->reportsetup($config);
        //return ['status'=>true,'msg'=>'Please check stockstatusposted ('.$config['params']['action'].')'];
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function exportcsv($config, $type)
  {
    $trno = $config['params']['trno'];
    $str = "";
    $separator = "@@@";
    $nextline = "###";
    $filename = '';

    switch ($type) {
      case 'F':
        $qry = "select head.trno as exportid, head.yourref as ponum,right(head.docno,5) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname,' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, 'Exworks' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from oqhead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
        where head.doc='oq' and head.trno=" . $trno . "
        union all
        select head.trno as exportid, head.yourref as ponum,right(head.docno,5) as erp,ifnull(date_format(qtn.dateid,'%m%/%d%/%Y'),'') as qtndate,
        ifnull(qtn.clientname,'') as customername, ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as contactperson,
        ifnull(qtn.terms, '') as terms, '???' as fob,
        0 as zero1, 0 as zero2,ifnull(date_format(so.dateid,'%m%/%d%/%Y'),'') as custpodate, ifnull(date_format(qtn.deldate,'%m%/%d%/%Y'),'') as deliverydate,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,
        'URGENT' as priority,ifnull(concat(conbill.fname, ' ', conbill.mname, ' ',conbill.lname),'') as billname,
        ifnull(bill.addrline1,'') as billaddr1,ifnull(bill.addrline2,'') as billaddr2,ifnull(bill.city,'') as billcity,
        ifnull(bill.country,'') as billcountry,ifnull(bill.contactno,'') as billcontactno,ifnull(bill.fax,'') as billfax,
        ifnull(conbill.email,'') as billemail,
        ifnull(concat(conship.fname, ' ', conship.mname, ' ',conship.lname),'') as shipname,
        ifnull(ship.addrline1,'') as shipaddr1,ifnull(ship.addrline2,'') as shipaddr2,ifnull(ship.city,'') as shipcity,
        ifnull(ship.country,'') as shipcountry,ifnull(ship.contactno,'') as shipcontactno,ifnull(ship.fax,'') as shipfax,
        ifnull(conship.email,'') as shipemail,head.rem,date_format(curdate(),'%m%/%d%/%Y') as dategenerated
        from hoqhead as head
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        left join client on client.client=qtn.client
        left join contactperson as conbill on conbill.line=qtn.billcontactid
        left join contactperson as conship on conship.line=qtn.shipcontactid
        left join billingaddr as bill on bill.line = qtn.billid and bill.clientid = client.clientid
        left join billingaddr as ship on ship.line = qtn.shipid and ship.clientid = client.clientid
        where head.doc='oq' and head.trno=" . $trno;

        $data = $this->coreFunctions->opentable($qry);
        if (!empty($data)) {
          // $str = $data[0]->exportid.$separator.$data[0]->ponum.$separator.$data[0]->erp.$separator.$data[0]->qtndate.$separator.$data[0]->customername.$separator.$data[0]->contactperson;
          // $str = $str.$separator.$data[0]->terms.$separator.$data[0]->fob.$separator.'0'.$separator.'0'.$separator.$data[0]->qtndate.$separator.$data[0]->custpodate.$separator.$data[0]->deliverydate.$separator.$data[0]->podate.$separator.$data[0]->priority;
          // $str = $str.$separator.$data[0]->billname.$separator.$data[0]->billaddr1.' '.$data[0]->billaddr2.' City: '.$data[0]->billcity.' Country: '.$data[0]->billcountry.' Phone: '.$data[0]->billcontactno."\t".'Fax: '.$data[0]->billfax.' Email: '.$data[0]->billemail;
          // $str = $str.$separator.$data[0]->shipname.$separator.$data[0]->shipaddr1.' '.$data[0]->shipaddr2.' City: '.$data[0]->shipcity.' Country: '.$data[0]->shipcountry.' Phone: '.$data[0]->shipcontactno."\t".'Fax: '.$data[0]->shipfax.' Email: '.$data[0]->shipemail;
          // $str = $str.$separator.$data[0]->rem;
          // $str = $str."###";

          if ($data[0]->rem == "") {
            $rem = ' ';
          } else {
            $rem = $data[0]->rem;
          }

          $str = $data[0]->erp . $separator . $data[0]->ponum . $separator . $data[0]->erp . $separator . $data[0]->podate . $separator . $data[0]->customername . $separator . $data[0]->contactperson;
          $str = $str . $separator . $data[0]->terms . $separator . $data[0]->fob . $separator . $data[0]->fob . $separator . '0' . $separator . $data[0]->podate . $separator . $data[0]->podate . $separator . $data[0]->deliverydate . $separator . $data[0]->dategenerated . $separator . $data[0]->priority;
          $str = $str . $separator . $data[0]->customername . $separator . $data[0]->billaddr1 . ' ' . $data[0]->billaddr2 . ' City: ' . $data[0]->billcity . ' Country: ' . $data[0]->billcountry . ' Phone: ' . $data[0]->billcontactno . "\t" . 'Fax: ' . $data[0]->billfax . ' Email: ' . $data[0]->billemail;
          $str = $str . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail;
          $str = $str . $separator . $data[0]->billname . $separator . $data[0]->shipaddr1 . ' ' . $data[0]->shipaddr2 . ' City: ' . $data[0]->shipcity . ' Country: ' . $data[0]->shipcountry . ' Phone: ' . $data[0]->shipcontactno . "\t" . 'Fax: ' . $data[0]->shipfax . ' Email: ' . $data[0]->shipemail . $separator . $data[0]->shipname . $separator . $rem;
          $str = $str . $separator . "0" . $separator . $data[0]->erp . "####";
          $filename = 'POCPHP-F-' . $data[0]->erp . date("dmyHi");
        }
        break;

      case 'D':
        $qry = "select right(head.docno,5) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,iteminfo.itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from oqhead as head
        left join oqstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        where head.doc='oq' and head.trno=" . $trno . "
        union all
        select right(head.docno,5) as erp, stock.line, head.yourref as ponum, stock.rrqty as pocustqty, stock.uom,
        head.cur as currency, round(stock.rrcost,4) as poprice, stock.rrqty as pobalance,
        date_format(head.dateid,'%m%/%d%/%Y') as podate,date_format(qtn.deldate,'%m%/%d%/%Y') as deliverydate,date_format(curdate(),'%m%/%d%/%Y') as dategenerated,brand.brand_desc,
        item.itemname, model.model_name,iteminfo.itemdescription,iteminfo.accessories,stockinfo.rem as itemremarks,
        brand.brand_desc as crossmfr, model.model_name as crossmfritemno,
        0 as weight, 0 as ssm1, 0 as price1, 0 as ssm2, 0 as price2, 0 as ssm3, 0 as price3, 0 as ssm4, 0 as price4,
        0 as ssm5, 0 as price5, 0 as ssm6, 0 as price6,p.name as project
        from hoqhead as head
        left join hoqstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
        left join part_masterfile as part on part.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join model_masterfile as model on model.model_id = item.model
        left join projectmasterfile as p on p.line = item.projectid
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = head.trno and stockinfo.line = stock.line
        left join hsqhead as so on so.trno=head.sotrno
        left join hqshead as qtn on qtn.sotrno=so.trno
        where head.doc='oq' and head.trno=" . $trno;

        $data = $this->coreFunctions->opentable($qry);
        if (!empty($data)) {
          foreach ($data as $key => $val) {
            // $str = $str.$data[$key]->erp.$separator.$data[$key]->line.$separator.$data[$key]->ponum.$separator.$data[$key]->pocustqty.$separator.$data[$key]->uom.$separator.$data[$key]->currency;
            // $str = $str.$separator.$data[$key]->poprice.$separator.$data[$key]->pobalance.$separator.$data[$key]->podate.$separator.$data[$key]->deliverydate.$separator.$data[$key]->dategenerated.$separator.$data[$key]->brand_desc.$separator.$data[$key]->itemname.$separator.$data[$key]->model_name;
            // $str = $str.$separator.$data[$key]->itemdescription.$separator.$data[$key]->itemremarks.' '.$data[$key]->crossmfr.$separator.$data[$key]->crossmfritemno;
            // $str = $str.$separator.'0'.$separator.$data[$key]->currency.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0'.$separator.'0';
            // $str = $str."###";
            if ($data[0]->accessories == "") {
              $accessories = ' ';
            } else {
              $accessories = $data[0]->accessories;
            }

            if ($data[0]->itemdescription == "") {
              $itemdescription = ' ';
            } else {
              $itemdescription = $data[0]->itemdescription;
            }

            $str = $str . $data[$key]->erp . $separator . $data[$key]->line . $separator . $data[$key]->ponum . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->uom . $separator . $data[$key]->currency;
            $str = $str . $separator . $data[$key]->poprice . $separator . round($data[$key]->pobalance, 0) . $separator . $data[$key]->podate . $separator . $data[$key]->deliverydate . $separator . $data[$key]->dategenerated . $separator . $data[$key]->project . $separator . $data[$key]->itemname . $separator . $data[$key]->model_name;
            $str = $str . $separator . $itemdescription . $separator . $accessories . $separator;
            $str = $str . $separator . $separator . $separator . '0' . $separator . $data[$key]->currency . $separator . round($data[$key]->pocustqty, 0) . $separator . $data[$key]->poprice . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator . '0' . $separator;
            $str = $str . "####";
          }

          $filename = 'POCPHP-D-' . $data[0]->erp . date("dmyHi");
        }

        break;
    }


    return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => $filename, 'ext' => 'txt', 'csv' => $str];
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from hoqhead as po
       left join hoqstock as s on s.trno = po.trno
       where po.trno = ?
       group by po.trno,po.docno,po.dateid,s.refx
       union all
       select po.trno,po.docno,left(po.dateid,10) as dateid,
       CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
       from oqhead as po
       left join oqstock as s on s.trno = po.trno
       where po.trno = ?
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
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select po.docno,left(po.dateid,10) as dateid,
            CAST(concat('Total PR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hprhead as po left join hprstock as s on s.trno = po.trno
            where po.trno = ?
            group by po.docno,po.dateid";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
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
                  'color' => 'yellow',
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

    //RR
    $qry = "
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ap.bal, 2)) as CHAR) as rem,
      head.trno
      from glhead as head
      left join glstock as stock on head.trno = stock.trno
      left join apledger as ap on ap.trno = head.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno, ap.bal
      union all
      select head.docno,
      date(head.dateid) as dateid,
      CAST(concat('Total RR Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem,
      head.trno
      from lahead as head
      left join lastock as stock on head.trno = stock.trno
      where stock.refx=? and head.doc = 'RR'
      group by head.docno, head.dateid, head.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'rr',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
          'w' => 250,
          'h' => 80,
          'type' => $t[0]->docno,
          'label' => $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as $key => $value) {
        //APV
        $rrtrno = $t[$key]->trno;
        $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'AP'";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'apv',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => 'red',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'rr', 'to' => 'apv']);
            $a = $a + 100;
          }
        }

        //CV
        if (!empty($apvdata)) {
          $apv_rr_links = "apv";
          $apvtrno = $apvdata[0]->trno;
        } else {
          $apvtrno = $rrtrno;
          $apv_rr_links = "rr";
        }
        $cvqry = "
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'
        union all
        select head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CV'";
        $cvdata = $this->coreFunctions->opentable($cvqry, [$apvtrno, $apvtrno]);
        if (!empty($cvdata)) {
          foreach ($cvdata as $key2 => $value2) {
            data_set(
              $nodes,
              $cvdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 800,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $cvdata[$key2]->docno,
                'label' => $cvdata[$key2]->rem,
                'color' => 'red',
                'details' => [$cvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => $apv_rr_links, 'to' => $cvdata[$key2]->docno]);
            $a = $a + 100;
          }
        }

        //DM
        $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'DM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total DM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'DM'
        group by head.docno, head.dateid";
        $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
        if (!empty($dmdata)) {
          foreach ($dmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' => $dmdata[$key2]->docno,
                'label' => $dmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$dmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'rr', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
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

  // insert and update item
  public function additem($action, $config)
  {

    $trno = $config['params']['trno'];

    $line = $config['params']['data']['line'];
    $rrqty = $config['params']['data']['rrqty'];
    $amt = $config['params']['data']['rrcost'];
    $ref = $config['params']['data']['ref'];
    $requestorname = $config['params']['data']['requestorname'];
    $purpose = $config['params']['data']['purpose'];
    $reqdate = $config['params']['data']['reqdate'];
    $releasedate = $config['params']['data']['releasedate'];
    $sono = $config['params']['data']['sono'];

    $data = [
      'trno'  => $trno,
      'line' => $line,
      'rrqty' => $rrqty,
      'rrcost' => $amt,
      'ext' => $amt,
      'ref' => $ref,
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $dataOthers = [
      'trno'  => $trno,
      'line' => $line,
      'requestorname' => $requestorname,
      'purpose' => $purpose,
      'reqdate' => $reqdate,
      'releasedate' => $releasedate,
      'sono' => $sono
    ];

    foreach ($dataOthers as $key => $value) {
      $dataOthers[$key] = $this->othersClass->sanitizekeyfield($key, $dataOthers[$key]);
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
        $this->coreFunctions->sbcinsert('stockinfotrans', $dataOthers);

        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);
        //  $this->logger->sbcwritelog($trno,$config,'STOCK','ADD - Line: '.$line.' rrqty: '.$rrqty.' ref: '.$ref);
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
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    // foreach ($data as $key => $value) {
    //   if($data[$key]->refx!=0){
    //     $this->setserveditems($data[$key]->refx,$data[$key]->linex);
    //   }
    // }
    // $this->logger->sbcwritelog($trno,$config,'STOCK','DELETED ALL ITEMS');        
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
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry("delete from stockinfotrans where trno=? and line=?", 'delete', [$trno, $line]);
    //   if ($data[0]->refx !== 0) {
    //     $this->setserveditems($data[0]->refx, $data[0]->linex);
    //   }
    //  $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line: ' . $line . ' rrqty: ' . $data[0]->rrqty . ' ref: ' . $data[0]->ref );
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    //} else {
    //    return ['status'=>false,'msg'=>'Cannot delete, already served'];
    //}
  } // end function



  // start
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
  // end

} //end class
