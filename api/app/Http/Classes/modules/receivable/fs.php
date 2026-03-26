<?php

namespace App\Http\Classes\modules\receivable;

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
use App\Helpers\NumberFormatter;

class fs
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'FINANCING SETUP';
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
  public $defaultContra = 'IS1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'address', 'rem', 'projectid', 'phaseid', 'modelid', 'blklotid'];
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
      'view' => 3893,
      'edit' => 3895,
      'new' => 3896,
      'save' => 3897,
      'delete' => 3898,
      'print' => 3899,
      'lock' => 3900,
      'unlock' => 3901,
      'post' => 3902,
      'unpost' => 3903,
      'edititem' => 3904,
      'deleteitem' => 3905
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

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view', 'duplicatedoc'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:140px;whiteSpace: normal;min-width:140px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';

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
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $companyid = $config['params']['companyid'];

    $dateid = "left(head.dateid,10) as dateid";
    if ($searchfilter == "") $limit = 'limit 150';

    $qry = "select head.trno,head.docno,head.clientname,$dateid, 
    'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby,
     head.yourref, head.ourref   
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     union all
     select head.trno,head.docno,head.clientname,$dateid,
     'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby,
      head.yourref, head.ourref    
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " $filtersearch
     order by dateid desc, docno desc $limit";

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

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    if ($companyid == 10) { //afti
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryparticulars', 'label' => 'Particulars', 'access' => 'view']];
      $particulars = $this->tabClass->createtab($tab, []);

      $return['Particulars'] = ['icon' => 'fa fa-envelope', 'tab' => $particulars];
    }

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    return $return;
  }


  public function createTab($access, $config)
  {

    $action = 0;
    $db = 1;
    $cr = 2;
    $postdate = 3;
    $project = 4;
    $ref = 5;
    $acnoname2 = 6;

    $companyid = $config['params']['companyid'];

    $columns = ['action',  'db', 'cr', 'postdate',  'project', 'ref', 'acnoname'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'headgridbtns' => ['viewacctginfo', 'viewref', 'viewdiagram']
      ],
    ];

    $stockbuttons = ['save', 'delete'];
    array_push($stockbuttons, 'detailinfo');
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['accounting']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['deleteallitem', 'generatepaysched'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "DELETE ACCOUNTS";
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['docno', 'client', 'clientname', 'address', 'rem', 'frefresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'lookupclient');
    data_set($col1, 'docno.label', 'Transaction#');


    $fields = ['dateid', 'reservationdate', 'dueday', 'projectname', 'phase', 'housemodel', ['blklot', 'lot']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'projectname.type', 'lookup');
    data_set($col2, 'projectname.action', 'lookupproject');
    data_set($col2, 'projectname.lookupclass', 'fproject');
    data_set($col2, 'phase.addedparams', ['projectid']);
    data_set($col2, 'housemodel.addedparams', ['projectid']);
    data_set($col2, 'blklot.addedparams', ['projectid', 'phaseid', 'fpricesqm']);
    data_set($col2, 'lot.class', 'cslot sbccsreadonly');

    $fields = ['reservationfee', 'farea', 'fpricesqm', 'ftcplot', 'ftcphouse', 'fma1', 'fma2', 'fma3', 'loanamt'];
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'farea.class', 'cslot sbccsreadonly');
    data_set($col3, 'ftcplot.class', 'cslot sbccsreadonly');
    data_set($col3, 'fma1.class', 'cslot sbccsreadonly');
    data_set($col3, 'fma2.class', 'cslot sbccsreadonly');
    data_set($col3, 'fma3.class', 'cslot sbccsreadonly');

    $fields = ['finterestrate', ['termspercentdp', 'termsmonth'], ['termspercent', 'termsyear'], ['fsellingpricegross', 'fdiscount'], ['fsellingpricenet', 'fmiscfee'], [
      'fcontractprice',
      'fmonthlydp'
    ], ['fmonthlyamortization', 'ffi'], ['fmri']];

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'termspercentdp.label', 'Terms Percent (DP)');
    data_set($col4, 'termsmonth.label', 'No.of Months (DP)');
    data_set($col4, 'termspercent.label', 'Terms Percent (Balance)');
    data_set($col4, 'termsyear.label', 'Terms in Year(Balance)');
    data_set($col4, 'fsellingpricegross.class', 'cslot sbccsreadonly');
    data_set($col4, 'fsellingpricenet.class', 'cslot sbccsreadonly');
    data_set($col4, 'fcontractprice.class', 'cslot sbccsreadonly');
    data_set($col4, 'fmonthlydp.class', 'cslot sbccsreadonly');
    data_set($col4, 'fmonthlyamortization.class', 'cslot sbccsreadonly');
    data_set($col4, 'ffi.class', 'cslot sbccsreadonly');
    data_set($col4, 'fmri.class', 'cslot sbccsreadonly');
    data_set($col4, 'fmiscfee.class', 'cslot sbccsreadonly');

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
    $data[0]['rem'] = '';
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['dueday'] = '';
    $data[0]['reservedate'] = '';
    $data[0]['phase'] = '';
    $data[0]['phaseid'] = 0;
    $data[0]['housemodel'] = '';
    $data[0]['modelid'] = 0;
    $data[0]['blklot'] = '';
    $data[0]['lot'] = '';
    $data[0]['blklotid'] = 0;
    $data[0]['dueday'] = 0;
    $data[0]['reservationdate'] = null;
    $data[0]['reservationfee'] = 0;
    $data[0]['farea'] = 0;
    $data[0]['fpricesqm'] = 0;
    $data[0]['ftcplot'] = 0;
    $data[0]['ftcphouse'] = 0;
    $data[0]['fma1'] = 0;
    $data[0]['fma2'] = 0;
    $data[0]['fma3'] = 0;

    $data[0]['finterestrate'] = 0;
    $data[0]['termspercentdp'] = 0;
    $data[0]['termsmonth'] = 0;
    $data[0]['termspercent'] = 0;
    $data[0]['termsyear'] = 0;
    $data[0]['fsellingpricegross'] = 0;
    $data[0]['fdiscount'] = 0;
    $data[0]['fsellingpricenet'] = 0;
    $data[0]['fmiscfee'] = 0;
    $data[0]['fcontractprice'] = 0;
    $data[0]['fmonthlydp'] = 0;
    $data[0]['fmonthlyamortization'] = 0;
    $data[0]['ffi'] = 0;
    $data[0]['fmri'] = 0;
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
         client.groupid,ifnull(project.code,'') as projectcode,
         hinfo.reservationdate, hinfo.dueday, head.phaseid, ph.code as phase,
         head.modelid, hm.model as housemodel, 
         head.blklotid, bl.blk as blklot, bl.lot,
         hinfo.reservationdate, FORMAT(ifnull(hinfo.reservationfee,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as reservationfee, FORMAT(ifnull(hinfo.farea,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as farea, 
         FORMAT(ifnull(hinfo.fpricesqm,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fpricesqm, FORMAT(ifnull(hinfo.ftcplot,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as ftcplot,
         FORMAT(ifnull(hinfo.ftcphouse,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as ftcphouse, FORMAT(ifnull(hinfo.fma1,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fma1, 
         FORMAT(ifnull(hinfo.fma2,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fma2, FORMAT(ifnull(hinfo.fma3,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fma3,
         FORMAT(ifnull(hinfo.finterestrate,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as finterestrate, FORMAT(ifnull(hinfo.termspercentdp,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as termspercentdp,
         FORMAT(ifnull(hinfo.termsmonth,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as termsmonth, FORMAT(ifnull(hinfo.termspercent,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as termspercent, 
         FORMAT(ifnull(hinfo.termsyear,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as termsyear, FORMAT(ifnull(hinfo.fsellingpricegross,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fsellingpricegross, 
         FORMAT(ifnull(hinfo.fdiscount,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fdiscount,
         FORMAT(ifnull(hinfo.fsellingpricenet,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fsellingpricenet, FORMAT(ifnull(hinfo.fmiscfee,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fmiscfee, 
         FORMAT(ifnull(hinfo.fcontractprice,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fcontractprice, FORMAT(ifnull(hinfo.fmonthlydp,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fmonthlydp, 
         FORMAT(ifnull(hinfo.fmonthlyamortization,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fmonthlyamortization,
        FORMAT(ifnull(hinfo.ffi,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as ffi, FORMAT(ifnull(hinfo.fmri,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as fmri,
        FORMAT(ifnull(hinfo.loanamt,0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as loanamt
          ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
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
    $dataothers = [];
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

    $dataothers['trno'] = $head['trno'];
    $dataothers['dueday'] = $head['dueday'];
    $dataothers['reservationdate'] = $head['reservationdate'];

    $dataothers['reservationfee'] = $head['reservationfee'];
    $dataothers['farea'] = $head['farea'];
    $dataothers['fpricesqm'] = $head['fpricesqm'];
    $dataothers['ftcplot'] = $head['ftcplot'];
    $dataothers['ftcphouse'] = $head['ftcphouse'];
    $dataothers['fma1'] = $head['fma1'];
    $dataothers['fma2'] = $head['fma2'];
    $dataothers['fma3'] = $head['fma3'];

    $dataothers['finterestrate'] = $head['finterestrate'];
    $dataothers['termspercentdp'] = $head['termspercentdp'];
    $dataothers['termsmonth'] = $head['termsmonth'];
    $dataothers['termspercent'] = $head['termspercent'];
    $dataothers['termsyear'] = $head['termsyear'];
    $dataothers['fsellingpricegross'] = $head['fsellingpricegross'];
    $dataothers['fdiscount'] = $head['fdiscount'];
    $dataothers['fsellingpricenet'] = $head['fsellingpricenet'];
    $dataothers['fmiscfee'] = $head['fmiscfee'];
    $dataothers['fcontractprice'] = $head['fcontractprice'];
    $dataothers['fmonthlydp'] = $head['fmonthlydp'];
    $dataothers['fmonthlyamortization'] = $head['fmonthlyamortization'];
    $dataothers['ffi'] = $head['ffi'];
    $dataothers['fmri'] = $head['fmri'];

    $arrcols = array_keys($dataothers);
    foreach ($arrcols as $key) {
      $dataothers[$key] = $this->othersClass->sanitizekeyfield($key, $dataothers[$key]);
    }
    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataothers);
    } else {
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataothers, ['trno' => $head['trno']]);
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
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $ret = $this->othersClass->posttransacctg($config);
    $trno = $config['params']['trno'];
    if ($ret['status']) {
      $clientid = $this->coreFunctions->getfieldvalue($this->hhead, "clientid", "trno=?", [$trno]);
      $blklotid = $this->coreFunctions->getfieldvalue($this->hhead, "blklotid", "trno=?", [$trno]);
      $this->coreFunctions->execqry("update blklot set clientid = " . $clientid . " where line=" . $blklotid);
    }
    return $ret;
  } //end function

  public function unposttrans($config)
  {
    $ret = $this->othersClass->unposttransacctg($config);
    $trno = $config['params']['trno'];
    if ($ret['status']) {
      $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      $this->coreFunctions->execqry("update blklot set clientid = 0 where line=" . $blklotid);
    }
    return $ret;
  } //end function

  private function getdetailselect($config)
  {
    $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,coa.acno,coa.acnoname,
             client.client,client.clientname,d.rem,
             FORMAT(d.db,2) as db,FORMAT(d.cr,2) as cr,d.fdb,d.fcr,d.refx,d.linex,
             left(d.postdate,10) as postdate,d.checkno,coa.alias,d.pdcline,
             d.projectid,ifnull(proj.name,'') as projectname,proj.code as project,d.cur,d.forex,
             d.poref, ifnull(left(d.podate, 10),'') as podate,d.branch,d.deptid,
             case d.isewt when 0 then 'false' else 'true' end as isewt,
             case d.isvat when 0 then 'false' else 'true' end as isvat,
             case d.isvewt when 0 then 'false' else 'true' end as isvewt,
             d.ewtcode,d.ewtrate,d.damt,'' as bgcolor,'' as errcolor ";

    return $qry;
  }


  public function opendetail($trno, $config)
  {
    $sqlselect = $this->getdetailselect($config);

    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    left join coa on d.acnoid=coa.acnoid
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid
     left join projectmasterfile as proj on proj.line = d.projectid
    left join coa on coa.acnoid=d.acnoid
    where d.trno=? order by trno,line
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
      case 'generatepaysched':
        return $this->generatepaysched($config);
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
    $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]); // $config['params']['data']['projectid'];
    $client = $config['params']['data']['client'];
    $refx = 0;
    $linex = 0;
    $ref = '';
    $checkno = '';
    $damt = 0;
    $poref = '';
    $podate = '';
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
    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['podate'])) {
      $podate = $config['params']['data']['podate'];
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      if (isset($config['params']['data']['projectid'])) {
        $project = $config['params']['data']['projectid'];
      }
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
      $cbalias = $this->coreFunctions->getfieldvalue("coa", "left(alias,2)", "acnoid=?", [$acnoid]);

      if ($cbalias == 'CB') {
        $project = 0;
      } else {
        if ($project == '') {
          $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
        }
      }
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
      'projectid' => $project,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'checkno' => $checkno,
      'damt' => $damt
    ];

    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { //afti, afti usd
      $data['poref'] = $poref;

      if ($podate == '') {
        $podate = date('Y-m-d');
      }

      $data['podate'] = $podate;
    }

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
    $data = $this->coreFunctions->opentable('select coa.acno,detail.refx,detail.linex from ' . $this->detail . ' as detail left join coa on coa.acnoid = detail.acnoid where detail.trno=? and detail.refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from detailinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config, 1);
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
      $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config, 1);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'DETAILINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' code:' . $data[0]['acno'] . ' db:' . $data[0]['db'] . ' cr:' . $data[0]['cr'] . ' client:' . $data[0]['client'] . ' date:' . $data[0]['postdate'] . ' ref:' . $data[0]['ref']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
  } // end function

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'duplicatedoc':
        return $this->othersClass->duplicateTransaction($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'fcalc':
        $trno = $config['params']['trno'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        if ($isposted) {
          return ['status' => 'false', 'msg' => 'Already Posted.', 'reloadhead' => true];
        } else {
          return $this->othersClass->financecalc($config);
        }

        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function generatepaysched($config)
  {
    $trno = $config['params']['trno'];
    $d = [];
    $det = [];
    $di = [];
    $dinfo = [];
    $i = 1;
    $prevbal = 0;
    $principal = 0;
    $prevbalh = 0;
    $prevball = 0;
    $prevprincipalcol = 0;
    $total = 0;
    $status = true;
    $msg = '';

    $rfacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'ARRF'");
    $dpacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'ARDP'");
    $maacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'AR1'");
    $gpacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias = 'SAX'");

    $data = $this->coreFunctions->opentable("select head.dateid,head.projectid,head.phaseid,head.modelid,head.blklotid,hinfo.dueday,hinfo.reservationdate, ifnull(hinfo.reservationfee,0) as reservationfee, ifnull(hinfo.farea,0) as farea, ifnull(hinfo.fpricesqm,0) as fpricesqm, ifnull(hinfo.ftcplot,0) as ftcplot,
    ifnull(hinfo.ftcphouse,0) as ftcphouse, ifnull(hinfo.fma1,0) as fma1, ifnull(hinfo.fma2,0) as fma2, ifnull(hinfo.fma3,0) as fma3,
    ifnull(hinfo.finterestrate,0) as finterestrate, ifnull(hinfo.termspercentdp,0) as termspercentdp, ifnull(hinfo.termsmonth,0) as termsmonth, ifnull(hinfo.termspercent,0) as termspercent, 
    ifnull(hinfo.termsyear,0) as termsyear, ifnull(hinfo.fsellingpricegross,0) as fsellingpricegross, ifnull(hinfo.fdiscount,0) as fdiscount,
    ifnull(hinfo.fsellingpricenet,0) as fsellingpricenet, ifnull(hinfo.fmiscfee,0) as fmiscfee, ifnull(hinfo.fcontractprice,0) as fcontractprice, ifnull(hinfo.fmonthlydp,0) as fmonthlydp, ifnull(hinfo.fmonthlyamortization,0) as fmonthlyamortization,
   ifnull(hinfo.ffi,0) as ffi, ifnull(hinfo.fmri,0) as fmri,ifnull(hinfo.loanamt,0) as loanamt from lahead as head left join cntnuminfo as hinfo on hinfo.trno = head.trno  where head.trno = ?", [$trno]);

    if (!empty($data)) {
      //reservation Fee
      $d['trno'] = $trno;
      $d['line'] = $i;
      $d['acnoid'] = $rfacnoid;
      $d['client'] = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
      $d['postdate'] = $data[0]->reservationdate;
      $d['db'] = $data[0]->reservationfee;
      $d['cr'] = 0;
      $d['projectid'] = $data[0]->projectid;
      $d['phaseid'] = $data[0]->phaseid;
      $d['modelid'] = $data[0]->modelid;
      $d['blklotid'] = $data[0]->blklotid;
      $d['rem'] = 'Reservation Fee';
      array_push($det, $d);

      $di['trno'] = $trno;
      $di['line'] = $i;
      $di['principal'] = $data[0]->reservationfee;
      $di['lotbal'] = $data[0]->ftcplot / $data[0]->fcontractprice * ($data[0]->fcontractprice - $data[0]->reservationfee);
      $di['housebal'] = (($data[0]->ftcphouse + $data[0]->fmiscfee) - $data[0]->fdiscount) / $data[0]->fcontractprice * ($data[0]->fcontractprice - $data[0]->reservationfee);
      $di['hlbal'] = $data[0]->fcontractprice - $data[0]->reservationfee;
      $di['fi'] = 0;
      $di['mri'] = 0;
      $di['interest'] = 0;
      $di['payment'] = $data[0]->reservationfee;
      $di['principalcol'] =  $data[0]->reservationfee;
      $di['percentage'] =  ($data[0]->reservationfee / $data[0]->fcontractprice) * 100;
      $prevprincipalcol = $data[0]->reservationfee;
      $total = $data[0]->reservationfee;
      array_push($dinfo, $di);

      $i += 1;


      //monthly dp
      $prevbal = $data[0]->fcontractprice - $data[0]->reservationfee;
      $prevbalh = (($data[0]->ftcphouse + $data[0]->fmiscfee) - $data[0]->fdiscount) / $data[0]->fcontractprice * ($data[0]->fcontractprice - $data[0]->reservationfee);
      $prevball = $data[0]->ftcplot / $data[0]->fcontractprice * ($data[0]->fcontractprice - $data[0]->reservationfee);
      for ($x = 1; $x <= $data[0]->termsmonth; $x++) {
        $d['trno'] = $trno;
        $d['line'] = $i;
        $d['acnoid'] = $dpacnoid;
        $d['client'] = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

        $pdate = strtotime($data[0]->reservationdate);
        $pdate = date("Y-m-d", strtotime("+$x month", $pdate));

        $d['postdate'] = $pdate;
        $d['db'] = $data[0]->fmonthlydp;
        $d['cr'] = 0;
        $d['projectid'] = $data[0]->projectid;
        $d['phaseid'] = $data[0]->phaseid;
        $d['modelid'] = $data[0]->modelid;
        $d['blklotid'] = $data[0]->blklotid;

        $locale = 'en_US';
        $nf = numfmt_create($locale, \NumberFormatter::ORDINAL);
        $d['rem'] = $nf->format($x) . ' DP';
        array_push($det, $d);

        $di['trno'] = $trno;
        $di['line'] = $i;
        $di['principal'] = $data[0]->fmonthlydp;
        $di['lotbal'] = $prevball / $prevbal * ($prevbal - $data[0]->fmonthlydp);
        $di['housebal'] = $prevbalh / $prevbal * ($prevbal - $data[0]->fmonthlydp);
        $di['hlbal'] = $prevbal - $data[0]->fmonthlydp;
        $di['fi'] = 0;
        $di['mri'] = 0;
        $di['interest'] = 0;
        $di['payment'] = $data[0]->fmonthlydp;
        $di['principalcol'] =  $prevprincipalcol + $data[0]->fmonthlydp;

        $prevprincipalcol = $prevprincipalcol + $data[0]->fmonthlydp;

        $di['percentage'] =  ($prevprincipalcol / $data[0]->fcontractprice) * 100;


        $prevbalh = $prevbalh / $prevbal * ($prevbal - $data[0]->fmonthlydp);
        $prevball = $prevball / $prevbal * ($prevbal - $data[0]->fmonthlydp);
        $prevbal = $prevbal - $data[0]->fmonthlydp;
        $total = $total + $data[0]->fmonthlydp;

        array_push($dinfo, $di);
        $i += 1;
      }

      //monthly ma
      $balmons = $data[0]->termsyear * 12;
      $rdate = strtotime($data[0]->reservationdate);
      $mos = $data[0]->termsmonth;
      $lastdpdate = date("Y-m-d", strtotime("+" . $mos . " month", $rdate));

      for ($y = 1; $y <= $balmons; $y++) {
        $d['trno'] = $trno;
        $d['line'] = $i;
        $d['acnoid'] = $maacnoid;
        $d['client'] = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);

        $pdate = strtotime($lastdpdate);
        $pdate = date("Y-m-d", strtotime("+$y month", $pdate));

        $d['postdate'] = $pdate;
        $d['db'] = $data[0]->fmonthlyamortization;
        $d['cr'] = 0;
        $d['projectid'] = $data[0]->projectid;
        $d['phaseid'] = $data[0]->phaseid;
        $d['modelid'] = $data[0]->modelid;
        $d['blklotid'] = $data[0]->blklotid;

        $locale = 'en_US';
        $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
        $d['rem'] = $nf->format($y) . ' MA';
        array_push($det, $d);


        //detailinfo
        $interest = ($prevbal * ($data[0]->finterestrate / 100)) / 12;
        $principal = $data[0]->fmonthlyamortization - $data[0]->ffi - $data[0]->fmri - $interest;

        $di['trno'] = $trno;
        $di['line'] = $i;
        $di['fi'] = $data[0]->ffi;
        $di['mri'] = $data[0]->fmri;
        $di['interest'] = round($interest, 2);
        $di['principal'] = $principal;
        $di['lotbal'] = $prevball / $prevbal * ($prevbal - $principal);
        $di['housebal'] = $prevbalh / $prevbal * ($prevbal - $principal);
        $di['hlbal'] = $prevbal - $principal;
        $di['payment'] = $data[0]->fmonthlyamortization;

        $di['principalcol'] =  $prevprincipalcol + $principal;

        $prevprincipalcol = $prevprincipalcol + $principal;

        $di['percentage'] =  ($prevprincipalcol / $data[0]->fcontractprice) * 100;

        $prevbalh = $prevbalh / $prevbal * ($prevbal - $principal);
        $prevball = $prevball / $prevbal * ($prevbal - $principal);
        $prevbal = $prevbal - $principal;
        $total = $total + $data[0]->fmonthlyamortization;
        array_push($dinfo, $di);
        $i += 1;
      }

      //gp
      $d['trno'] = $trno;
      $d['line'] = $i;
      $d['acnoid'] = $gpacnoid;
      $d['client'] = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
      $d['postdate'] = $data[0]->dateid;
      $d['db'] = 0;
      $d['cr'] = $total;
      $d['projectid'] = $data[0]->projectid;
      $d['phaseid'] = $data[0]->phaseid;
      $d['modelid'] = $data[0]->modelid;
      $d['blklotid'] = $data[0]->blklotid;
      $d['rem'] = '';
      array_push($det, $d);
    }



    if ($this->coreFunctions->sbcinsert($this->detail, $det)) {
      if (!$this->coreFunctions->sbcinsert('detailinfo', $dinfo)) {
        $this->coreFunctions->execqry("delete from " . $this->detail . " where trno = " . $trno);
        $status = false;
        $msg = 'Error in Detail info';
      }
    } else {
      $status = false;
      $msg = 'Error';
    }


    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    //GD
    $gjqry = "
    select  head.docno, date(head.dateid) as dateid, head.trno,
    CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
    from glhead as head
    left join gldetail as detail on head.trno = detail.trno
    where head.trno = ? and detail.refx <> 0";
    $gjdata = $this->coreFunctions->opentable($gjqry, [$config['params']['trno']]);
    if (!empty($gjdata)) {
      $startx = 550;
      $starty = 100;
      $a = 0;
      foreach ($gjdata as $key2 => $value2) {
        data_set(
          $nodes,
          $gjdata[$key2]->docno . $gjdata[$key2]->refx,
          [
            'align' => 'left',
            'x' => $startx + 400,
            'y' => $starty,
            'w' => 250,
            'h' => 80,
            'type' => $gjdata[$key2]->docno,
            'label' => $gjdata[$key2]->rem,
            'color' => 'red',
            'details' => [$gjdata[$key2]->dateid]
          ]
        );
        $starty += 100;
        array_push($links, ['from' => 'gj', 'to' => 'paya_recei']);

        $qry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where head.trno = ?";
        $t = $this->coreFunctions->opentable($qry, [$gjdata[$key2]->refx]);
        $startyyy = 100;
        if (!empty($t)) {
          foreach ($t as $key => $value) {
            data_set(
              $nodes,
              $t[$key]->docno,
              [
                'align' => 'left',
                'x' => $startx,
                'y' => $starty - 100,
                'w' => 250,
                'h' => 80,
                'type' => $t[$key]->docno,
                'label' => $t[$key]->rem,
                'color' => 'green',
                'details' => [$t[$key]->dateid]
              ]
            );
            array_push($links, ['from' => $t[$key]->docno, 'to' => $gjdata[$key2]->docno . $gjdata[$key2]->refx]);

            $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
             CAST(concat('Total Amt: ',round(sum(stock.ext),2)) as CHAR) as rem
             from glhead as head 
             left join glstock as stock on stock.trno = head.trno
             where head.trno = ? 
             group by head.trno,head.docno,head.dateid";
            $stockdata = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
            $startyyy += 100;
            if (!empty($stockdata)) {
              foreach ($stockdata as $key1 => $value1) {
                data_set(
                  $nodes,
                  $stockdata[$key1]->docno,
                  [
                    'align' => 'right',
                    'x' => 200,
                    'y' => $startyyy - 100,
                    'w' => 250,
                    'h' => 80,
                    'type' => $stockdata[$key1]->docno . " $key",
                    'label' => $stockdata[$key1]->rem,
                    'color' => 'blue',
                    'details' => [$stockdata[$key1]->dateid]
                  ]
                );
                array_push($links, ['from' => $stockdata[$key1]->docno, 'to' => $t[$key]->docno]);
              }
            }
          }
        }
      }
    }
    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
