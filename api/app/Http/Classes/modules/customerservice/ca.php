<?php

namespace App\Http\Classes\modules\customerservice;

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

class ca
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TICKET';
  public $gridname = 'singleinput';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'csstickethead';
  public $hhead = 'hcsstickethead';
  public $stock = 'csscomment';
  public $hstock = 'hcsscomment';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = ['trno', 'docno', 'client', 'dateid', 'rem', 'orderid', 'channelid', 'clienttype', 'empid', 'branchid']; //, 'sitrno'
  private $except = ['trno', 'dateid', 'rem'];
  private $otherfields = ['trno', 'gendercaller'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary']
  ];
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
      'view' => 1060,
      'edit' => 1061,
      'new' => 1062,
      'save' => 1063,
      'change' => 1064,
      'delete' => 1065,
      'print' => 1066,
      // 'lock' => 1067,
      // 'unlock' => 1068,
      'changeamt' => 1069,
      'post' => 1070,
      'unpost' => 1071,
      'additem' => 1072,
      'edititem' => 1073,
      'deleteitem' => 1074
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $openticket = $this->othersClass->checkAccess($config['params']['user'], 4732); //allow view open ticket
    $inprogress = $this->othersClass->checkAccess($config['params']['user'], 4733); //allow view in-progress ticket
    $resolved = $this->othersClass->checkAccess($config['params']['user'], 4734); //allow view resolved

    if ($openticket == 1) {
      array_push($this->showfilterlabel, ['val' => 'open', 'label' => 'Open', 'color' => 'primary']);
    }

    if ($inprogress == 1) {
      array_push($this->showfilterlabel, ['val' => 'inprogress', 'label' => 'In-Progress', 'color' => 'primary']);
    }

    if ($resolved == 1) {
      array_push($this->showfilterlabel, ['val' => 'resolved', 'label' => 'Resolved', 'color' => 'primary']);
    }

    array_push($this->showfilterlabel, ['val' => 'posted', 'label' => 'Closed', 'color' => 'primary']);
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $itemfilter = $config['params']['itemfilter'];

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $searchfilter = $config['params']['search'];
    $limit = "limit 50";

    $condition = '';
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'client.clientname', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }
    switch ($itemfilter) {
      case 'draft':
        $status = "'DRAFT'";
        $condition .= ' and num.postdate is null and num.statid=0';
        break;
      case 'open':
        $condition .= " and num.postdate is null and num.statid=92";
        $status = "stat.status";
        break;
      case 'inprogress':
        $condition .= " and num.postdate is null and num.statid=93";
        $status = "stat.status";
        break;
      case 'resolved':
        $condition .= " and num.postdate is null and num.statid=94";
        $status = "stat.status";
        break;
      case 'posted':
        $condition .= ' and num.postdate is not null';
        $status = "stat.status";
        break;
    }

    $qry = "select head.trno,head.docno,client.clientname,left(head.dateid,10) as dateid, 
     head.createby,head.editby,head.viewby,num.postedby," . $status . " as status 
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join client on client.client=head.client 
     left join trxstatus as stat on stat.line=num.statid
      where head.doc=? and num.center=?  " . $condition . "  " . $filtersearch . "
      union all
      select head.trno,head.docno,client.clientname,left(head.dateid,10) as dateid,
     head.createby,head.editby,head.viewby,num.postedby," . $status . " as status 
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join client on client.clientid=head.clientid
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center=?  " . $condition . "  " . $filtersearch . "
     order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $doc, $center]);
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
      // 'lock',
      // 'unlock',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {

    $fields = ['wysiwygrem'];
    $col1 = $this->fieldClass->create($fields);

    $tab = [
      $this->gridname => ['inputcolumn' => ['col1' => $col1], 'label' => 'DESCRIPTION'],
      'rwncomment' => [
        'action' => 'customerservice',
        'lookupclass' => 'cacommententry',
        'label' => 'COMMENT SECTION'
      ],
      'carefdoc' => [
        'action' => 'customerservice',
        'lookupclass' => 'capostedsj',
        'label' => 'REFERENCE DOCUMENTS'
      ]
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

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
    return [];
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'client', 'clientname', 'tel'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'tel.label', 'Contact Number');
    data_set($col1, 'clientname.readonly', true);
    data_set($col1, 'tel.readonly', true);
    data_set($col1, 'company.class', 'cs sbccsreadonly');
    data_set($col1, 'clientname.class', 'cs sbccsreadonly');
    data_set($col1, 'tel.class', 'cs sbccsreadonly');

    $fields = [['dateid', 'clienttype'], 'email', 'gender', 'ordertype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'ordertype.required', true);
    data_set($col2, 'email.readonly', true);
    data_set($col2, 'gender.label', 'Caller Gender');
    data_set($col2, 'gender.name', 'gendercaller');
    data_set($col2, 'gender.required', true);
    data_set($col2, 'email.class', 'cs sbccsreadonly');

    $fields = ['channel', 'empname', 'dbranchname', 'registername'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'empname.type', 'lookup');
    data_set($col3, 'empname.action', 'lookupclient');
    data_set($col3, 'empname.lookupclass', 'employee');
    data_set($col3, 'empname.label', 'Recipient');
    data_set($col3, 'dbranchname.lookupclass', 'hbranch');
    data_set($col3, 'company.class', 'cscompany sbccsreadonly');
    data_set($col3, 'registername.label', 'Company Name');
    data_set($col3, 'channel.required', true);
    data_set($col3, 'registername.readonly', true);
    data_set($col3, 'registername.class', 'cs sbccsreadonly');

    $fields = ['submit', 'open', 'inprogress', 'resolved', 'reopen', 'posted'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'submit.name', 'backlisting');


    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';

    //tel & email ng client
    $data[0]['tel'] = '';
    $data[0]['email'] = '';
    $data[0]['rem'] = '';

    //order
    $data[0]['orderid'] = 0;
    $data[0]['ordertype'] = '';
    //channel
    $data[0]['channelid'] = 0;
    $data[0]['channel'] = '';

    $data[0]['branchcode'] = '';
    $data[0]['branch'] = '0';

    $data[0]['branchid'] = 0;
    $data[0]['branchname'] = '';

    $data[0]['clienttype'] = '';
    $data[0]['empid'] = 0;
    $data[0]['empname'] = '';

    $data[0]['registername'] = '';
    $data[0]['gendercaller'] = '';

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $companyname = $this->companysetup->getcompanyname(['companyid' => $companyid]);
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
    $statid = $this->othersClass->getstatid($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select 
         num.center,
         head.trno,
         head.docno,
         client.client,
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         client.clientname,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem, ifnull(head.clienttype,'') as clienttype,
         ifnull(req1.category,'') as ordertype , head.orderid, ifnull(client.tel,'') as tel, ifnull(client.email,'') as email,
         ifnull(req2.category,'') as channel , head.channelid,emp.clientname as empname, head.empid,
          ''  as dbranchname,ifnull(branch.client,'') as branchcode,ifnull(branch.clientid,'') as branch,
         ifnull(branch.clientname,'') as branchname, head.branchid,client.registername, 
         ifnull(info.gendercaller,'') as gendercaller ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on client.client = head.client
        left join client as emp on head.empid = emp.clientid
        left join client as branch on branch.clientid = head.branchid
        left join reqcategory as req1 on req1.line=head.orderid
        left join reqcategory as req2 on req2.line=head.channelid
        left join headinfotrans as info on info.trno=head.trno
        where head.trno = ? and num.center = ? and num.postdate is null and head.lockdate is null
        union all 
        " . $qryselect . " from $htable as head 
         left join $tablenum as num on num.trno = head.trno
        left join client on client.clientid = head.clientid
        left join client as emp on head.empid = emp.clientid
        left join client as branch on branch.clientid = head.branchid
        left join reqcategory as req1 on req1.line=head.orderid
        left join reqcategory as req2 on req2.line=head.channelid
        left join hheadinfotrans as info on info.trno=head.trno
         where head.trno = ? and num.center = ? and num.postdate is not null 
        ";
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
      $hideobj = [];
      $label = '';
      $hideobj['submit'] = true;
      $hideobj['open'] = true; //nakahide pag true
      $hideobj['inprogress'] = true;
      $hideobj['resolved'] = true;
      $hideobj['reopen'] = true;
      $hideobj['posted'] = true;

      $hideobj['openstat'] = true;
      $hideobj['iprogresstat'] = true;
      $hideobj['resolvedstat'] = true;

      $breadcrumbs = [];
      $lblOpen = ['label' => 'Open', 'icon' => 'file_open']; //chevron_right
      $lblInP = ['label' => 'In Progress', 'icon' => 'pending'];
      $lblRes = ['label' => 'Resolved', 'icon' => 'task'];
      $lblClosed = ['label' => 'Closed', 'icon' => 'fact_check'];
      switch ($statid) {
        case 0: //draft
          $hideobj['submit'] = false;
          break;
        case 92: //open
          $hideobj['inprogress'] = false;
          $hideobj['openstat'] = false;
          array_push($breadcrumbs, $lblOpen);
          break;
        case 93: // in progress
          $hideobj['resolved'] = false;
          $hideobj['iprogresstat'] = false;
          array_push($breadcrumbs, $lblOpen, $lblInP);
          break;
        case 94: //resolved
          $hideobj['reopen'] = false;
          $hideobj['resolvedstat'] = false;
          array_push($breadcrumbs, $lblOpen, $lblInP, $lblRes);
          break;
        default:
          array_push($breadcrumbs, $lblOpen, $lblInP, $lblRes, $lblClosed, ['label' => 'Total Void Amount: 1,000.00']);
          break;
      }


      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj, 'breadcrumbsbottom' => $breadcrumbs];
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
    $dataother = [];
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

    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataother[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
        } //end if
      }
    }


    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
    $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("headinfotrans", $dataother);
    } else {
      $this->coreFunctions->sbcupdate("headinfotrans", $dataother, ['trno' => $head['trno']]);
    }
  } // end function

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $commentcount = $this->coreFunctions->datareader('select count(trno) as value from csscomment where trno=?', [$trno]);
    if ($commentcount !== 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Delete Failed, already have COMMENTS'];
    }

    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select num.trno as value from " . $this->tablenum . " as num left join " . $this->head . " as head on head.trno=num.trno where num.doc=? and head.lockdate is null order by num.trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc]);

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function openstock($trno, $config)
  {
    $qry = 'select line, trno, comment, "" as bgcolor from csscomment where trno=?';
    return $this->coreFunctions->opentable($qry, [$trno]);
  } //end function

  public function openstockline($config)
  {

    if (isset($config['params']['trno'])) {
      $line = $config['params']['line'];
      $trno = $config['params']['trno'];
    } else {
      $trno = $config['params']['row']['trno'];
      $line = $config['params']['row']['line'];
    }
    $qry = 'select line, trno, comment,"" as bgcolor from csscomment where trno=? and line=?';
    return $this->coreFunctions->opentable($qry, [$trno, $line]);
  } // end function

  public function stockstatus($config)
  {

    switch ($config['params']['action']) {
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
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'submit':
        return $this->submit($config);
        break;
      case 'inprogress':
        return $this->inprogress($config);
        break;
      case 'resolved':
        return $this->resolved($config);
        break;
      case 'reopen':
        return $this->reopen($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function submit($config)
  {
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 92], ['trno' => $config['params']['trno']])) { //open statid update
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Open Tickets');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for open ticket'];
    }
  }

  public function inprogress($config)
  {
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 93], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'In-Progress Tickets');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for In-progress ticket'];
    }
  }

  public function resolved($config)
  {

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 94], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tickets was already resolved');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Resolved Failed'];
    }
  }

  public function reopen($config)
  {
    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 92], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Re-Open Ticket');
      return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to re-open ticket'];
    }
  }

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

    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $comment = $config['params']['data']['comment'];
    $line = $config['params']['data']['line'];

    $data = [
      'trno' => $trno,
      'line' => $line,
      'comment' => $comment
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if ($action == 'insert') {
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
        $this->logger->sbcmasterlog(
          $trno,
          $config,
          ' CREATE - LINE: ' . $line . ''
            . ', COMMENT: ' . $comment
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } elseif ($action == 'update') {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      return $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
    }
  } // end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->getfieldvalue($this->tablenum, 'docno', 'trno=?', [$trno]);
    $statid = $this->othersClass->getstatid($config);

    if ($statid != 94) { //resolved
      return ['status' => false, 'msg' => 'Posting failed. Transaction has not been resolved.'];
    }

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for hcsstickethead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,clientid,dateid,rem,yourref,ourref,
      clienttype,orderid,channelid,empid,branchid,
      lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate)
      SELECT head.trno,head.doc,head.docno,client.clientid,head.dateid,head.rem,head.yourref,head.ourref,
      head.clienttype,head.orderid,head.channelid,head.empid,head.branchid,
      head.lockuser,head.lockdate,head.openby,head.users,head.createdate,head.createby,head.editby,
      head.editdate,head.viewby,head.viewdate FROM " . $this->head . " as head 
      left join client on client.client=head.client 
      where head.trno=? limit 1";


    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {


      if (!$this->othersClass->postingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }
      // for hcsscomment
      $qry = "insert into " . $this->hstock . "(line,trno,createdate,createby,comment,ispa)
        SELECT line,trno,createdate,createby,comment,ispa FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 12];
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
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    $qry = "insert into " . $this->head . "(trno,doc,docno,client,dateid,rem,yourref,ourref,
    clienttype,orderid,channelid,empid,branchid,
    lockuser,lockdate,openby,users,createdate,createby,editby,editdate,viewby,viewdate)
    select head.trno,head.doc,head.docno,client.client,head.dateid,head.rem,head.yourref,head.ourref,
     head.clienttype,head.orderid,head.channelid,head.empid,head.branchid,
    head.lockuser,head.lockdate,head.openby,head.users,head.createdate,head.createby,head.editby,head.editdate,head.viewby,head.viewdate
    from (" . $this->hhead . " as head 
    left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
    left join client on client.clientid=head.clientid ) 
  
    where head.trno=? limit 1";


    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }
      $qry = "insert into " . $this->stock . "(
      line,trno,createdate,createby,comment,ispa)
      SELECT line,trno,createdate,createby,comment,ispa
      from " . $this->hstock . " where trno=?";
      //stock

      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, statid= 94,postedby='' where trno=?", 'update', [$trno]); //back to resolve 
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
