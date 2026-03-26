<?php

namespace App\Http\Classes\modules\cbbsi;

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

class ps
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Payment Listing Summary';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'pshead';
  public $hhead = 'hpshead';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $defaultContra = 'AP1';

  private $fields = ['trno', 'docno', 'dateid', 'acnoid', 'yourref', 'ourref', 'rem', 'asofdate'];
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
      'view' => 4422,
      'edit' => 4423,
      'new' => 4424,
      'save' => 4425,
      'delete' => 4426,
      'print' => 4427,
      'lock' => 4428,
      'unlock' => 4429,
      'post' => 4430,
      'unpost' => 4431,
      'additem' => 4432,
      'edititem' => 4433,
      'deleteitem' => 4434
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listacnoname = 5;
    $yourref = 6;
    $ourref = 7;
    $postdate = 8;
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'acno', 'acnoname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listacnoname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$listacnoname]['type'] = 'label';
    $cols[$listacnoname]['label'] = 'Account Name';
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
    $limit = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $dateid = "left(head.dateid,10) as dateid";
    if ($searchfilter == "") $limit = 'limit 150';
    $orderby =  "order by  dateid desc, docno desc";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'coa.acno', 'coa.acnoname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.trno, head.docno, head.acnoid, date(head.dateid) as dateid, 'DRAFT' as status, coa.acno, coa.acnoname,
      head.createby, head.editby, head.viewby, num.postedby, date(num.postdate) as postdate, head.yourref, head.ourref
      from " . $this->head . " as head
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join coa on coa.acnoid=head.acnoid
      where head.doc='" . $doc . "' and num.center='" . $center . "' and convert(head.dateid,date)>='" . $date1 . "' and convert(head.dateid,date)<='" . $date2 . "' " . $condition . " " . $filtersearch . "
    union all
    select head.trno, head.docno, head.acnoid, date(head.dateid) as dateid, 'POSTED' as status, coa.acno, coa.acnoname,
      head.createby, head.editby, head.viewby, num.postedby, date(num.postdate) as postdate, head.yourref, head.ourref
      from " . $this->hhead . " as head
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join coa on coa.acnoid=head.acnoid
      where head.doc='" . $doc . "' and num.center='" . $center . "' and convert(head.dateid,date)>='" . $date1 . "' and convert(head.dateid,date)<='" . $date2 . "' " . $condition . " " . $filtersearch . " "
      . $orderby . " " . $limit;
    $data = $this->coreFunctions->opentable($qry);
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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnunpaidkr', 'db', 'cr', 'rem']);
    $step4 = $this->helpClass->getFields(['db', 'cr', 'rem']);
    $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add account/s', 'action' => $step3],
      'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'kr', 'title' => 'KR_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

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
    $tab = [
      $this->gridname => [
        'gridcolumns' => [
          'action', 'yourref', 'ourref', 'amt', 'checkdate', 'cvno', 'checkdetails', 'releasetoap', 'releasetosupp', 'cleardate', 'rem', 'plno', 'pldate'
        ]
      ]
    ];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['accounting']['columns'][11]['label'] = 'PL No.';
    $obj[0]['accounting']['columns'][4]['readonly'] = true;
    $obj[0]['accounting']['descriptionrow'] = ['clientname', 'client', 'Supplier'];
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingpy', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "DELETE ACCOUNT";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'contra', 'acnoname', 'asofdate'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, "contra.lookupclass", "CB");

    $fields = ['dateid', ['yourref', 'ourref'], 'rem'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['dacnoname'] = '';
    $data[0]['acnoid'] = '';
    $data[0]['acno'] = '';
    $data[0]['acnoname'] = '';
    $data[0]['rem'] = '';
    $data[0]['asofdate'] = $this->othersClass->getCurrentDate();
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
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

    if ($this->companysetup->getistodo($config['params'])) $this->othersClass->checkseendate($config, $tablenum);

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);

    $qry = "select head.trno, head.docno, date(head.dateid) as dateid, head.rem, head.yourref, head.acnoid, head.ourref, coa.acno as contra, coa.acnoname, head.asofdate
      from " . $this->head . " as head
      left join " . $this->tablenum . " as num on num.trno=head.trno
      left join coa on coa.acnoid=head.acnoid
      where head.trno=? and num.doc=? and num.center=?
      union all
      select head.trno, head.docno, date(head.dateid) as dateid, head.rem, head.yourref, head.acnoid, head.ourref, coa.acno as contra, coa.acnoname, head.asofdate
      from " . $this->hhead . " as head
      left join " . $this->tablenum . " as num on num.trno=head.trno
      left join coa on coa.acnoid=head.acnoid
      where head.trno=? and num.doc=? and num.center=?";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->opendetail($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) $msg = $config['msg'];
      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      return  ['head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['acno'] . ' - ' . $head['acnoname']);
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
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, acnoid, asofdate, yourref, ourref, rem, lockuser, lockdate, createdate, createby, editby, editdate, viewby, viewdate) select trno, doc, docno, dateid, acnoid, asofdate, yourref, ourref, rem, lockuser, lockdate, createdate, createby, editby, editdate, viewby, viewdate from " . $this->head . " where trno=" . $trno . " limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, acnoid, asofdate, yourref, ourref, rem, lockuser, lockdate, createdate, createby, editby, editdate, viewby, viewdate) select trno, doc, docno, dateid, acnoid, asofdate, yourref, ourref, rem, lockuser, lockdate, createdate, createby, editby, editdate, viewby, viewdate from " . $this->hhead . " where trno=" . $trno . " limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting'];
    }
  } //end function

  public function opendetail($trno, $config)
  {
    $qry = "select num.pstrno as trno,head.trno as line,head.yourref, head.ourref, case when ifnull(cvd.cr,0) = 0 then format(head.amt, 2) else format(cvd.cr, 2) end as amt, case ifnull(cvd.postdate,'') when '' then date(info.checkdate) else cvd.postdate end as checkdate, cv.docno as cvno, cvd.checkno as checkdetails,
    date(info.releasetoap) as releasetoap, cvd.clearday as cleardate, info.rem2 as rem, head.docno as plno, date(head.dateid) as pldate, format(sum(ledger.db),2) as db, format(sum(ledger.cr),2) as cr,
      head.client, head.clientname, '' as bgcolor, head.trno as pytrno,date(cvinfo.releasedate) as releasetosupp
      from hpyhead as head
      left join transnum as num on num.trno=head.trno
      left join hheadinfotrans as info on info.trno=head.trno
      left join cntnum as cv on cv.trno=num.cvtrno
      left join glhead as cvh on cvh.trno = cv.trno
      left join gldetail as cvd on cvd.trno=cv.trno and cvd.checkno<>''
      left join hcntnuminfo as cvinfo on cvinfo.trno = cv.trno
      left join apledger as ledger on ledger.py=head.trno
      where num.pstrno=?
      group by num.pstrno,head.yourref, head.ourref, info.checkdate, cv.docno, cvd.checkno,
      info.releasetoap, cvd.clearday, info.rem2 , head.docno, date(head.dateid) ,
      head.client, head.clientname, head.trno,cvinfo.releasedate,head.amt,cvd.cr,cvd.postdate order by head.trno";

    $detail = $this->coreFunctions->opentable($qry, [$trno]);
    return $detail;
  }

  public function saveperitem($config)
  {
    $row = $config['params']['row'];
    $pytrno = $row['pytrno'];
    $data = [];
    $hhead = [];
    if ($row['checkdate'] != null && $row['checkdate'] != '') $data['checkdate'] = $row['checkdate'];
    if ($row['releasetoap'] != null && $row['releasetoap'] != '') $data['releasetoap'] = $row['releasetoap'];
    if ($row['rem'] != null && $row['rem'] != '') $data['rem2'] = $row['rem'];
    if ($row['yourref'] != '') $hhead['yourref'] = $row['yourref'];
    if ($row['ourref'] != '') $hhead['ourref'] = $row['ourref'];
    if ($row['amt'] != '') $hhead['amt'] = $row['amt'];
    $exist = $this->coreFunctions->getfieldvalue("hheadinfotrans", "trno", "trno=?", [$pytrno]);
    if (floatval($exist) != 0) {
      $isupdate = $this->coreFunctions->sbcupdate('hheadinfotrans', $data, ['trno' => $pytrno]);
    } else {
      $data['trno'] = $pytrno;
      $isupdate = $this->coreFunctions->sbcinsert('hheadinfotrans', $data);
    }

    if (!empty($hhead)) {
      $this->coreFunctions->sbcupdate('hpyhead', $hhead, ['trno' => $pytrno]);
    }

    $data2 = $this->opendetailline($config);
    if ($isupdate == 1) {
      return ['row' => $data2, 'status' => true, 'msg' => 'Detail was saved successfully.'];
    } else {
      $data2[0]->errcolor = 'bg-red-2';
      return ['row' => $data2, 'status' => false, 'msg' => 'Error updating detail...'];
    }
  }

  public function opendetailline($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $qry = "select  num.pstrno as trno,head.trno as line,head.yourref, head.ourref, case when ifnull(cvd.cr,0) = 0 then format(head.amt, 2) else format(cvd.cr, 2) end as amt, case ifnull(cvd.postdate,'') when '' then date(info.checkdate) else cvd.postdate end  as checkdate, cv.docno as cvno, cvd.checkno as checkdetails,
      date(info.releasetoap) as releasetoap, cvd.clearday as cleardate, info.rem2 as rem, head.docno as plno, date(head.dateid) as pldate, format(sum(ledger.db),2) as db, format(sum(ledger.cr),2) as cr,
      head.client, head.clientname, '' as bgcolor, head.trno as pytrno,date(cvinfo.releasedate) as releasetosupp
      from hpyhead as head
      left join transnum as num on num.trno=head.trno
      left join hheadinfotrans as info on info.trno=head.trno
      left join cntnum as cv on cv.trno=num.cvtrno
      left join glhead as cvh on cvh.trno = cv.trno
      left join gldetail as cvd on cvd.trno=cv.trno  and cvd.checkno<>''
      left join hcntnuminfo as cvinfo on cvinfo.trno = cv.trno
      left join apledger as ledger on ledger.py=head.trno
      where num.pstrno=? and head.trno=?
      group by num.pstrno,head.yourref, head.ourref, info.checkdate, cv.docno, cvd.checkno ,
      info.releasetoap, cvd.clearday , info.rem2, head.docno , date(head.dateid),
      head.client, head.clientname,head.trno,cvinfo.releasedate,cvd.cr,head.amt,cvd.postdate  order by head.trno";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $detail;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'saveperitem':
        return $this->saveperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'getunpaidselected':
        return $this->getunpaidselected($config);
        break;
      case 'getpysummary':
        return $this->getpysummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function getpysummary($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    if (!empty($config['params']['rows'])) {
      foreach ($config['params']['rows'] as $r) {
        if ($this->coreFunctions->sbcupdate('transnum', ['pstrno' => $trno], ['trno' => $r['pytrno']]) == 1) {
          $config['params']['row']['trno'] = $r['pytrno'];
          $config['params']['row']['line'] = $r['trno'];
          $row = $this->opendetailline($config);
          array_push($rows, $row);
        }
      }
    }
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Details Successfully...', 'reloadhead' => true];
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
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

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable("select trno from transnum where pstrno=" . $trno);
    if (!empty($data)) {
      foreach ($data as $d) {
        $this->coreFunctions->sbcupdate('transnum', ['pstrno' => 0], ['trno' => $d->trno]);
      }
    }
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }



  public function deleteitem($config)
  {
    $trno = $config['params']['trno'];
    $pytrno = $config['params']['row']['pytrno'];
    $pydocno = $config['params']['row']['plno'];
    $this->coreFunctions->sbcupdate('transnum', ['pstrno' => 0], ['trno' => $pytrno]);
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED PY- Docno:' . $pydocno);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
  } // end function

  public function getunpaidselected($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $data = $config['params']['rows'];
    foreach ($data as $key => $value) {
      $qry = "update apledger set py = " . $trno . " where trno = ? and line =?";
      $return = $this->coreFunctions->execqry($qry, "update", [$data[$key]['trno'], $data[$key]['line']]);
      if ($return == 1) {
        $row = $this->opendetailline($data[$key]['trno'], $data[$key]['line'], $config);
        array_push($rows, $row[0]);
      }
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Accounts Successfull...'];
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
