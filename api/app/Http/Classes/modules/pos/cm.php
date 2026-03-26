<?php

namespace App\Http\Classes\modules\pos;

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

use App\Http\Classes\sqlquery;

class cm
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SALES RETURN POS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AR1';

  private $sqlquery;

  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'terms', 'forex', 'cur', 'wh', 'address', 'contra', 'tax', 'vattype', 'projectid', 'agent', 'deptid', 'branch', 'billid', 'shipid', 'billcontactid', 'shipcontactid'];
  private $except = ['trno', 'dateid', 'due'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = false;
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
      'view' => 2008,
      'edit' => 2155,
      'new' => 2173,
      'save' => 2192,
      'delete' => 2098,
      'print' => 2115,
      'lock' => 2339,
      'unlock' => 2356,
      'post' => 2387,
      'unpost' => 67,
      'acctg' => 83,
      'changeamt' => 2244,
      // 'changedisc'=>3303,
      'additem' => 102,
      'edititem' => 623,
      'deleteitem' => 2648
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
    $station = 5;
    $branch = 6;
    $salestype = 7;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'station', 'branch', 'salestype'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$branch]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';
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
      $searchfield = ['head.docno', 'head.clientname', 'num.station', 'br.clientname'];
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
    head.createby,head.editby,head.viewby,num.postedby, date(head.lockdate) as lockdate, 
    date(num.postdate) as postdate,  head.yourref, head.ourref, num.station, br.clientname as branch, head.salestype
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno left join client as br on br.clientid=head.branch
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     and left(num.bref,3) = 'SRS'
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(head.lockdate) as lockdate, 
     date(num.postdate) as postdate,  head.yourref, head.ourref, num.station, br.clientname as branch, head.salestype
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno left join client as br on br.clientid=head.branch
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     and left(num.bref,3) = 'SRS'
     order by dateid desc, docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      // 'new',
      // 'save',
      'delete',
      // 'cancel',
      'print',
      'post',
      'unpost',
      // 'lock',
      // 'unlock',
      'logs',
      // 'edit',
      'backlisting',
      'toggleup',
      'toggledown',
      'help'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
    $companyid = $config['params']['companyid'];

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    if ($companyid == 10) {
      $return['SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
    }
    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $invonly = $this->companysetup->isinvonly($config['params']);

    $action = 0;
    $rrqty = 1;
    $uom = 2;
    $isamt = 3;
    $disc = 4;
    $lessvat = 5;
    $sramt = 6;
    $pwdamt = 7;
    $ext = 8;
    $wh = 9;
    $ref = 10;
    $loc = 11;
    $expiry = 12;
    $rem = 13;
    $pallet = 14;
    $location = 15;
    $itemname  = 16;
    $barcode = 17;
    $stock_projectname = 18;
    $subcode = 19;
    $partno = 20;
    $boxcount = 21;
    $agent = 22;


    $column = ['action', 'rrqty', 'uom', 'isamt', 'disc', 'lessvat', 'sramt', 'pwdamt', 'ext', 'wh', 'ref', 'loc', 'expiry', 'rem', 'pallet', 'location', 'channel', 'client', 'clientname', 'itemname', 'cost', 'banktype', 'bankrate', 'terminalid', 'modepayamt', 'comm1', 'comap', 'cardcharge', 'comm2', 'comap2', 'netap', 'stock_projectname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'agent'];
    $sortcolumn = ['action', 'rrqty', 'uom', 'isamt', 'disc', 'lessvat', 'sramt', 'pwdamt', 'ext', 'wh', 'ref', 'loc', 'expiry', 'rem', 'pallet', 'location', 'channel', 'client', 'clientname', 'itemname', 'cost', 'banktype', 'bankrate', 'terminalid', 'modepayamt', 'comm1', 'comap', 'cardcharge', 'comm2', 'comap2', 'netap', 'stock_projectname', 'barcode', 'stock_projectname', 'partno', 'subcode', 'boxcount', 'agent'];
    foreach ($column as $key => $value) {
      $$value = $key;
    }
    if ($invonly) {
      $headgridbtns = ['viewref', 'viewdiagram'];
    } else {
      $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram'];
    }

    if ($companyid == 10) {
      array_push($headgridbtns, 'viewitemstockinfo');
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],

    ];


    $stockbuttons = ['showbalance'];

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'AIMS':
        if ($companyid == 0) {
          array_push($stockbuttons, 'stockinfo');
        } else if ($companyid == 10) {
          array_push($stockbuttons, 'iteminfo');
        }
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    // $obj[0]['inventory']['columns'][$barcode]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
    } else {
      $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
      $obj[0]['inventory']['columns'][$loc]['type'] = 'input';

      $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
    }

    $obj[0]['inventory']['columns'][$pallet]['type'] = 'coldel';
    if (!$ispallet) {
      $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
    }


    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refcm';

    if ($companyid != 10) {
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
    }
    if ($companyid != 56) { //not homewroks
      $obj[0]['inventory']['columns'][$channel]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$client]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$clientname]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$banktype]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$bankrate]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$terminalid]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$modepayamt]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$comm1]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$comap]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$comm2]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$comap2]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$netap]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$cardcharge]['type'] = 'coldel';
    } else {
      $obj[0]['inventory']['columns'][$client]['label'] = 'Supplier Code';
      $obj[0]['inventory']['columns'][$clientname]['type'] = 'Supplier';
      $obj[0]['inventory']['columns'][$comm1]['label'] = 'Comm Rate';
      $obj[0]['inventory']['columns'][$comm2]['label'] = 'Comm Rate 2';
      $obj[0]['inventory']['columns'][$cost]['label'] = 'Unit cost';
    }

    $obj[0]['inventory']['columns'][$partno]['label'] = 'Old SKU';
    $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
    $obj[0]['inventory']['columns'][$partno]['align'] = 'left';
    $obj[0]['inventory']['columns'][$partno]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$subcode]['label'] = 'Part No.';
    $obj[0]['inventory']['columns'][$subcode]['type'] = 'label';
    $obj[0]['inventory']['columns'][$subcode]['align'] = 'left';
    $obj[0]['inventory']['columns'][$subcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    $obj[0]['inventory']['columns'][$boxcount]['label'] = 'QTY Per Box';
    $obj[0]['inventory']['columns'][$boxcount]['type'] = 'label';
    $obj[0]['inventory']['columns'][$boxcount]['align'] = 'left';
    $obj[0]['inventory']['columns'][$boxcount]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

    if ($companyid != 6) {
      $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
    }

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'AIMS':
        if ($companyid == 0 || $companyid == 10) {
          $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        }
        break;
    }

    $obj[0]['inventory']['columns'][$ref]['type'] = 'input';
    $obj[0]['inventory']['columns'][$wh]['type'] = 'input';

    $obj[0]['inventory']['columns'][$rrqty]['readonly'] = true;
    $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
    $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    $obj[0]['inventory']['columns'][$rem]['readonly'] = true;
    $obj[0]['inventory']['columns'][$ref]['readonly'] = true;
    $obj[0]['inventory']['columns'][$wh]['readonly'] = true;

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $inv = $this->companysetup->isinvonly($config['params']);
    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Customer');
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');

    if ($inv) {
      $fields = ['station', ['dateid', 'terms'], 'due', 'dagentname', 'dwhname'];
    } else {
      $fields = ['station', ['dateid', 'terms'], ['due', 'dvattype'], 'dacnoname', 'dagentname', 'dwhname'];
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AR Account');

    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname', 'salestype'];
    if ($config['params']['companyid'] == 10) {
      unset($fields[2]);
      array_push($fields, 'dbranchname');
    }
    $col3 = $this->fieldClass->create($fields);

    $fields = ['rem']; //, 'refresh'
    $col4 = $this->fieldClass->create($fields);
    // data_set($col4, 'refresh.type', 'actionbtn');
    // data_set($col4, 'refresh.name', 'backlisting');
    // data_set($col4, 'refresh.label', 'Recompute Net AP');
    // data_set($col4, 'refresh.access', 'view');
    // data_set($col4, 'refresh.action', 'recomputenetap');
    // data_set($col4, 'refresh.lookupclass', 'stockstatusposted');

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
    $data[0]['station'] = '';
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['dagentname'] = '';
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';
    $data[0]['forex'] = 1;
    $data[0]['tax'] = 0;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['salestype'] = '';
    $data[0]['branch'] = 0;
    $data[0]['billid'] = 0;
    $data[0]['shipid'] = 0;
    $data[0]['billcontactid'] = 0;
    $data[0]['shipcontactid'] = 0;
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
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " 
          where doc=? and center=? and left(bref,3) = 'SRS'
          order by trno desc 
          limit 1", [$doc, $center]);
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
         num.station,
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
         head.vattype,
         '' as dvattype,
         ifnull(warehouse.client, '') as wh,
         ifnull(warehouse.clientname, '') as whname,
         '' as dwhname,
         head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         left(head.due,10) as due,
         client.groupid,ifnull(agent.client, '') as agent,ifnull(agent.clientname, '') as agentname,
         '' as dagentname,ifnull(project.code,'') as projectcode,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname, head.salestype,
         ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, 
         head.branch,'' as dbranchname, head.billid, head.shipid, head.billcontactid, head.shipcontactid ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        where head.trno = ? and num.doc=? and num.center = ? and left(num.bref,3) = 'SRS'
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as agent on agent.clientid = head.agentid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        where head.trno = ? and num.doc=? and num.center=? and left(num.bref,3) = 'SRS' ";

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
    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->recomputestock($head, $config);
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    if (!$this->othersClass->checkserialin($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }

    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'SR1']);

      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        return $this->othersClass->posttranstock($config);
      }
    }
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    stock.uom,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
    FORMAT(stock.ext-round(ifnull(abs(info.lessvat),0),4)-round(ifnull(abs(info.sramt),0),4)-round(ifnull(abs(info.pwdamt),0),4)," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
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
    stock.palletid,
    stock.locid,
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    stock.rem,stock.cost,stock.fcost,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid, round(ifnull(abs(info.lessvat),0),4) as lessvat, round(ifnull(abs(info.sramt),0),4) as sramt, round(ifnull(abs(info.pwdamt),0),4) as pwdamt,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount, agent.client as agent,supplier.client,supplier.clientname,info.channel,info.banktype,
    info.bankrate,info.terminalid,ifnull(info.modepayamt,'0.00') as modepayamt,format(info.comm1,2) as comm1,format(info.comap,2) as comap,format(info.comm2,2) as comm2,
    format(info.comap2,2) as comap2,format(info.netap,2) as netap,format(info.cardcharge,2) as cardcharge";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join client as agent on agent.clientid=stock.agentid
    left join client as supplier on supplier.clientid=stock.suppid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join client as agent on agent.clientid=stock.agentid
    left join client as supplier on supplier.clientid=stock.suppid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
    where stock.trno =? order by line";

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
  left join pallet on pallet.line=stock.palletid
  left join location on location.line=stock.locid
  left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
  left join client as warehouse on warehouse.clientid=stock.whid
  left join client as agent on agent.clientid=stock.agentid
  left join client as supplier on supplier.clientid=stock.suppid 
  left join projectmasterfile as prj on prj.line = stock.projectid
  left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
  where stock.trno = ? and stock.line = ? ";
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
      case 'getsjsummary':
        return $this->getsjsummary($config);
        break;
      case 'getsjdetails':
        return $this->getsjdetails($config);
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
      case 'recomputenetap':
        return $this->recomputenetap($config);
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

    //CM
    $qry = "
    select head.trno, head.docno, date(head.dateid) as dateid,
    CAST(concat('Total CM Amt: ',round(sum(stock.ext),2)) as CHAR) as rem, stock.refx
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    where head.trno = ?
    group by head.trno,head.docno,head.dateid,stock.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'left',
            'x' => $startx + 400,
            'y' => 200,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'red',
            'details' => [$t[$key]->dateid]
          ]
        );

        if (floatval($t[$key]->refx) != 0) {
          //SJ
          $qry = "
          select head.docno,
          date(head.dateid) as dateid,
          CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem,
          stock.refx, head.trno
          from glhead as head
          left join glstock as stock on head.trno = stock.trno
          left join arledger as ar on ar.trno = head.trno
          where head.trno=? and head.doc = 'SJ'
          group by head.docno, head.dateid, head.trno, ar.bal, stock.refx";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
          if (!empty($x)) {
            foreach ($x as $key2 => $value1) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => $startx,
                  'y' => 100,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => 'green',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $t[$key]->docno]);

              //SO
              $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
              CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
              from hsohead as so
              left join hsostock as s on s.trno = so.trno
              where so.trno = ?
              group by so.trno,so.docno,so.dateid";
              $sodata = $this->coreFunctions->opentable($qry, [$x[$key2]->refx]);
              if (!empty($sodata)) {
                foreach ($sodata as $k => $v) {
                  data_set(
                    $nodes,
                    $sodata[$k]->docno,
                    [
                      'align' => 'right',
                      'x' => 200,
                      'y' => 50 + $a,
                      'w' => 250,
                      'h' => 80,
                      'type' => $sodata[$k]->docno,
                      'label' => $sodata[$k]->rem,
                      'color' => 'blue',
                      'details' => [$sodata[$k]->dateid]
                    ]
                  );
                  array_push($links, ['from' => $x[$key2]->docno, 'to' => $sodata[$k]->docno]);
                  $a = $a + 100;
                }
              }

              //APV
              $rrtrno = $x[$key2]->trno;
              $apvqry = "
              select  head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from glhead as head
              left join gldetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'
              union all
              select  head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from lahead as head
              left join ladetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'";
              $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
              if (!empty($apvdata)) {
                foreach ($apvdata as $key3 => $value2) {
                  data_set(
                    $nodes,
                    'apv',
                    [
                      'align' => 'left',
                      'x' => $startx + 400,
                      'y' => 100,
                      'w' => 250,
                      'h' => 80,
                      'type' => $apvdata[$key3]->docno,
                      'label' => $apvdata[$key3]->rem,
                      'color' => 'red',
                      'details' => [$apvdata[$key3]->dateid]
                    ]
                  );
                  array_push($links, ['from' => $x[$key2]->docno, 'to' => 'apv']);
                  $a = $a + 100;
                }
              }

              //CV
              if (!empty($apvdata)) {
                $apvtrno = $apvdata[0]->trno;
              } else {
                $apvtrno = $rrtrno;
              }
              $cvqry = "
              select head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from glhead as head
              left join gldetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'
              union all
              select head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from lahead as head
              left join ladetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'";
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
                  array_push($links, ['from' => 'apv', 'to' => $cvdata[$key2]->docno]);
                  $a = $a + 100;
                }
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

  public function diagram_aftech($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
     CAST(concat('Total OP Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
     from hophead as head
     left join hopstock as s on s.trno = head.trno
     left join hqsstock as qtstock on qtstock.refx = s.trno and s.line = qtstock.linex
     left join hqshead as qthead on qthead.trno = qtstock.trno
     left join hsqhead as sohead on sohead.trno = qthead.sotrno
     left join glstock as glstock on glstock.refx = qthead.trno
     left join glstock as cmstock on cmstock.refx = glstock.trno
     where cmstock.trno = ?
     group by head.trno,head.docno,head.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //qs quotation 
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 100,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => '#88DDFF',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'qt']);
        $a = $a + 100;


        // quotation
        $qry = "
            select head.docno,left(head.dateid,10) as dateid,
            CAST(concat('Total QS Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hqshead as head 
            left join hqsstock as s on s.trno = head.trno
            left join glstock as glstock on glstock.refx = head.trno
            left join glstock as cmstock on cmstock.refx = glstock.trno
            where cmstock.trno = ?
            group by head.docno,head.dateid";
        $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        $poref = $t[$key]->docno;
        if (!empty($x)) {
          foreach ($x as $key2 => $value) {
            data_set(
              $nodes,
              'qt',
              [
                'align' => 'left',
                'x' => 500,
                'y' => 50 + $a,
                'w' => 250,
                'h' => 80,
                'type' => $x[$key2]->docno,
                'label' => $x[$key2]->rem,
                'color' => '#ff88dd',
                'details' => [$x[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'qt', 'to' => 'so']);
            $a = $a + 100;
          }
        }


        // SO
        $qry = "
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from sqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          left join glstock as glstock on glstock.refx = qthead.trno
          left join glstock as cmstock on cmstock.refx = glstock.trno
          where cmstock.trno = ?
          group by head.docno,head.dateid
          union all
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hsqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          left join glstock as glstock on glstock.refx = qthead.trno
          left join glstock as cmstock on cmstock.refx = glstock.trno
          where cmstock.trno = ?
          group by head.docno,head.dateid";
        $sodata = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($sodata)) {
          foreach ($sodata as $sodatakey => $value) {
            data_set(
              $nodes,
              'so',
              [
                'align' => 'left',
                'x' => 600,
                'y' => 100 + $a,
                'w' => 250,
                'h' => 80,
                'type' => $sodata[$sodatakey]->docno,
                'label' => $sodata[$sodatakey]->rem,
                'color' => 'blue',
                'details' => [$sodata[$sodatakey]->dateid]
              ]
            );
            array_push($links, ['from' => 'so', 'to' => 'sj']);
            $a = $a + 100;
          }
        }
      }
    }

    //SJ
    $qry = "
    select sjhead.docno,
    date(sjhead.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    sjhead.trno
    from hqshead as head
    left join hqsstock as stock on stock.trno = head.trno
    left join hsqhead as sohead on sohead.trno = head.sotrno
    left join glstock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join glhead as sjhead on sjhead.trno = sjstock.trno
    left join arledger as ar on ar.trno = sjhead.trno
    left join glstock as cmstock on cmstock.refx = sjstock.trno
    where cmstock.trno = ? and sjhead.docno is not null
    group by sjhead.docno, sjhead.dateid, ar.bal, sjhead.trno
    union all 
    select sjhead.docno,
    date(sjhead.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(sjstock.ext),2), ' - ', 'Balance: ', round(sum(sjstock.ext),2)) as CHAR) as rem, 
    sjhead.trno
    from hqshead as head
    left join hqsstock as stock on stock.trno = head.trno
    left join hsqhead as sohead on sohead.trno = head.sotrno
    left join lastock as sjstock on sjstock.refx = stock.trno and sjstock.linex = stock.line
    left join lahead as sjhead on sjhead.trno = sjstock.trno
    left join glstock as cmstock on cmstock.refx = sjstock.trno
    where cmstock.trno = ? and sjhead.docno is not null
    group by sjhead.docno, sjhead.dateid, sjhead.trno";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => 450 + $startx,
          'y' => 300,
          'w' => 250,
          'h' => 80,
          'type' => $t[0]->docno,
          'label' => $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as $key => $value) {
        //CR
        $rrtrno = $t[$key]->trno;
        $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ?
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ?";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 800,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => '#6D50E8',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid
        where head.trno=?
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where head.trno=?
        group by head.docno, head.dateid";
        $dmdata = $this->coreFunctions->opentable($dmqry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($dmdata)) {
          foreach ($dmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 800,
                'y' => 300,
                'w' => 250,
                'h' => 80,
                'type' => $dmdata[$key2]->docno,
                'label' => $dmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$dmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
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
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than SJ Qty ';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
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
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
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
      $config['params']['barcode'] = $barcode;
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
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $qty = $config['params']['data']['qty'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $expiry = $config['params']['data']['expiry'];
    $rem = '';
    $cost = 0;
    $fcost = 0;
    $sgdrate = 0;
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    $refx = 0;
    $linex = 0;
    $ref = '';
    $palletid = 0;
    $locid = 0;
    $projectid = 0;

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['locid'])) {
      $locid = $config['params']['data']['locid'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $projectid = $config['params']['data']['projectid'];
    }

    if ($companyid == 10) {
      if (isset($config['params']['data']['sgdrate'])) {
        $sgdrate = $config['params']['data']['sgdrate'];
      } else {
        $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
      }
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
      //getting latestcost
      $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
      $cost = $this->othersClass->getlatestcost($itemid, $dateid, $config);
      $cforex = $this->othersClass->getlatestcost($itemid, $dateid, $config, '', 'forex');
      if (floatval($cforex == 0)) {
        $cforex = 1;
      }
      $fcost = $cost / $cforex;

      //end getlatestcost

      if ($companyid == 10) {
        if ($projectid == 0) {
          $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
        }
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $cost = $config['params']['data']['cost'];
      $fcost = $config['params']['data']['fcost'];
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

    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    if (floatval($curtopeso) == 0) {
      $curtopeso = 1;
    }
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $computedata['amt'] * $curtopeso,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'rem' => $rem,
      'cost' => $cost,
      'palletid' => $palletid,
      'locid' => $locid,
      'fcost' => $fcost
    ];

    if ($companyid == 10) {
      $data['projectid'] = $projectid;
      $data['sgdrate'] = $sgdrate;
    }

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
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0 || $companyid == 10) {
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            }
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext']);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($this->setserveditems($refx, $linex) === 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $return = false;
      }
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {


    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from serialin where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='CM' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='CM' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update glstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
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
    $qry = "delete from serialin where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);

    if ($data[0]->refx !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' wh:' . $data[0]['wh'] . ' ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno =  $config['params']['trno'];
    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
          stock." . $this->damt . " as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc in ('SJ') and cntnum.center = ? and head.client = ?
          and item.barcode = ? and stock." . $this->damt . " <> 0
          UNION ALL
          select head.docno,head.dateid,stock." . $this->damt . " as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          where head.doc in ('SJ') and cntnum.center = ? and client.client = ?
          and item.barcode = ?  and stock." . $this->damt . " <> 0
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $client, $barcode, $center, $client, $barcode]);
    $usdprice = 0;
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      $qry = "select amt,disc,uom from item where barcode=?";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);
      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, 2);
        }
      }

      if (floatval($data[0]->amt) == 0) {
        return ['status' => false, 'msg' => 'No Latest price found...'];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
      }
    }
  } // end function


  public function getsjsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $forex = 1;
    $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.fcost,stock.loc,stock.expiry,stock.projectid,stock.sgdrate
        FROM glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          if ($companyid == 10) {
            $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          }

          if (floatval($data[$key2]->cost) == 0) {
            $config['params']['data']['cost'] = $this->othersClass->getlatestcost($data[$key2]->itemid, $dateid, $config);
            $forex = $this->othersClass->getlatestcost($data[$key2]->itemid, $dateid, $config, '', 'forex');
            $config['params']['data']['fcost'] = $config['params']['data']['cost'] * $forex;
          } else {
            $config['params']['data']['cost'] = $data[$key2]->cost;
            $config['params']['data']['fcost'] = $data[$key2]->fcost;
          }

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function


  public function getsjdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $forex = 1;
    $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.fcost,stock.loc,stock.expiry,stock.projectid,stock.sgdrate
        FROM glhead as head left join glstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;

          if ($companyid == 10) {
            $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          }

          if (floatval($data[$key2]->cost) == 0) {
            $config['params']['data']['cost'] = $this->othersClass->getlatestcost($data[$key2]->itemid, $dateid, $config);
            $forex = $this->othersClass->getlatestcost($data[$key2]->itemid, $dateid, $config, '', 'forex');
            if ($forex == 0) {
              $forex = 1;
            }
            $config['params']['data']['fcost'] = $config['params']['data']['cost'] / $forex;
          } else {
            $config['params']['data']['cost'] = $data[$key2]->cost;
            $config['params']['data']['fcost'] = $data[$key2]->fcost;
          }

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    if ($companyid == 10) {
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(a.acno,"") as asset,ifnull(r.acno,"") as revenue,stock.isamt,stock.cost,
    stock.amt,stock.disc,stock.rrqty,stock.qty,stock.fcost,stock.projectid,head.branch,head.deptid
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid left join projectmasterfile as p on p.line = stock.projectid 
          left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid where head.trno=?';
    } else {
      $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
    stock.isamt,stock.cost,stock.amt,stock.disc,stock.rrqty,stock.qty,stock.fcost,head.projectid
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid where head.trno=?';
    }
    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SR1']);
      $vat = $stock[0]->tax;
      $tax1 = 0;
      $tax2 = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
        if ($vat != 0) {

          $tax = round(($stock[$key]->ext / $tax1), 2);
          $tax = round(($stock[$key]->ext - $tax), 2);
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $stock[$key]->revenue !== '' ? $stock[$key]->revenue : $revacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->rrqty,
          'cost' => $stock[$key]->cost * $stock[$key]->qty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'fcost' => $stock[$key]->fcost * $stock[$key]->qty,
          'projectid' => $stock[$key]->projectid
        ];
        if ($companyid == 10) {
          $params['branch'] = $stock[$key]->branch;
          $params['deptid'] = $stock[$key]->deptid;
        }

        $this->distribution($params, $config);
      }
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
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status =  true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {

    $companyid = $config['params']['companyid'];
    $entry = [];
    $forex = $params['forex'];
    if ($forex == 0) {
      $forex = 1;
    }
    $cur = $params['cur'];
    $invamt = round($params['cost'], 2);
    //AR
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => ($params['ext'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $params['ext'], 'projectid' => $params['projectid']];
      if ($companyid == 10) {
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //INV
    if (floatval($invamt) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => $invamt, 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['fcost']), 'projectid' => $params['projectid']];
      if ($companyid == 10) {
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
      $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => 0, 'cr' => $invamt, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['fcost']), 'fdb' => 0, 'projectid' => $params['projectid']];
      if ($companyid == 10) {
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      //sr
      $sr = (($params['ext'] - $params['tax']) * $forex);
      if (floatval($sr) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => 0, 'db' => $sr, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['ext'] - $params['tax']), 'projectid' => $params['projectid']];
        if ($companyid == 10) {
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      // ouput tax
      $ouput = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $ouput, 'client' => $params['client'], 'cr' => 0, 'db' => ($params['tax'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['tax']), 'projectid' => $params['projectid']];
      if ($companyid == 10) {
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sr
      $sr = ($params['ext'] * $forex);
      if (floatval($sr) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => 0, 'db' => $sr, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['ext']), 'projectid' => $params['projectid']];
        if ($companyid == 10) {
          $entry['branch'] = $params['branch'];
          $entry['deptid'] = $params['deptid'];
        }
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  // reports starto

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




  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $computedata = $this->othersClass->computestock($data2[$key][$this->damt] * $head['forex'], $data[$key]->disc, $data2[$key][$this->dqty], $data[$key]->uomfactor, 0);
      $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }


  private function recomputenetap($config)
  {
    $trno = $config['params']['trno'];

    $qry = "select s.trno, s.line, s.iss, s.qty, s.cost, s.ext, info.comm1, info.comm2, info.comap2, info.comap, item.channel, info.cardcharge, info.netap
        from glstock as s left join hstockinfo as info on info.trno=s.trno and info.line=s.line
        left join item on item.itemid=s.itemid where s.trno=" . $trno;

    $data = $this->coreFunctions->opentable($qry);

    foreach ($data as $key2 => $value) {
      $comap1 = 0;
      $comap2 = 0;

      if ($value->channel == 'CONCESSION') {
        if ($value->comm1 != 0) {
          $commrate = $value->comm1;
          $commamt = 0;

          if (abs($value->ext) > 0) {
            $commamt = number_format(abs($value->ext) *  ($commrate / 100), 2, '.', '') * -1;
            $comap1 = abs($value->ext) + $commamt;
          }
        }
      } else {
        $defaultcost = $value->cost;
        if ($value->iss > 0) {
          if (abs($value->ext) > 0) {
            $comap1 = number_format($value->iss *  $defaultcost, 2, '.', '');
          }
        }
        if ($value->qty > 0) {
          $comap1 = number_format($value->qty *  $defaultcost, 2, '.', '');
        }
      }

      if ($value->comm2 != 0) {
        if (abs($value->ext) > 0) {
          $comap2 = number_format(abs($comap1) *  ($value->comm2 / 100), 2, '.', '');
        }
      }

      $dataupdate = ['comap' => $comap1, 'comap2' => $comap2, 'netap' => $comap1 - $comap2 - $value->cardcharge];
      $this->coreFunctions->sbcupdate("hstockinfo", $dataupdate, ['trno' => $value->trno, 'line' => $value->line]);
    }


    $doc = $config['params']['doc'];

    $dataAPOut = $this->coreFunctions->opentable("SELECT stock.whid, client.client, client.clientid, h.dateid, sum(info.comap) as comap, sum(info.cardcharge) as cardcharge, sum(info.comap2) as comap2, sum(info.netap) as netap
                    from glstock as stock LEFT JOIN hstockinfo AS info ON info.trno=stock.trno AND info.line=stock.line LEFT JOIN client ON client.clientid=stock.suppid left join item on item.itemid=stock.itemid
                    left join glhead as h on h.trno=stock.trno 
                    where item.channel='CONCESSION' and stock.trno=" . $trno . " group by stock.whid, client.client, h.dateid, client.clientid");

    foreach ($dataAPOut as $keyAP => $valAP) {
      if ($valAP->netap > 0) {
        $dcNetComAP = $dcInputTax = 0;
        if ($valAP->comap != 0) {
          $dcInputTax = number_format((($valAP->comap / 1.12) * 0.12), 2, '.', '');
          $dcNetComAP = number_format($valAP->comap - $dcInputTax, 2, '.', '');
        }

        $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG1']);
        $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $doc  == 'CM' ? 0 : $dcNetComAP, 'cr' => $doc  == 'CM' ? $dcNetComAP : 0, 'postdate' => $valAP->dateid];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['TX1']);
        $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $doc  == 'CM' ? 0 : $dcInputTax, 'cr' => $doc  == 'CM' ? $dcInputTax : 0, 'postdate' => $valAP->dateid];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['AP1']);
        $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $doc  == 'CM' ? $valAP->comap : 0, 'cr' => $doc  == 'CM' ? 0 : $valAP->comap, 'postdate' => $valAP->dateid];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        if ($valAP->cardcharge != 0) {
          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARBC']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $doc  == 'CM' ? 0 : $valAP->cardcharge, 'cr' => $doc  == 'CM' ? $valAP->cardcharge : 0, 'postdate' => $valAP->dateid];
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIBC']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $doc  == 'CM' ?  $valAP->cardcharge : 0, 'cr' => $doc  == 'CM' ? 0 : $valAP->cardcharge, 'postdate' => $valAP->dateid];
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }

        if ($valAP->comap2 != 0) {
          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARMS']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $doc  == 'CM' ? 0 : $valAP->comap2, 'cr' => $doc  == 'CM' ? $valAP->comap2 : 0, 'postdate' => $valAP->dateid];
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIMS']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $doc  == 'CM' ? $valAP->comap2 : 0, 'cr' => $doc  == 'CM' ? 0 : $valAP->comap2, 'postdate' => $valAP->dateid];
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }
      }
    }

    $config['params']['trno'] = $trno;
    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $qry = "select line as value from gldetail where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno], '', true);
      $line += 1;

      foreach ($this->acctg as $key3 => $value) {
        foreach ($value as $key2 => $value2) {
          $this->acctg[$key3][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
        $this->acctg[$key3]['line'] = $line;
        $this->acctg[$key3]['editdate'] = $current_timestamp;
        $this->acctg[$key3]['editby'] = $config['params']['user'];
        $this->acctg[$key3]['encodeddate'] = $current_timestamp;
        $this->acctg[$key3]['encodedby'] = 'EXTRACTION';
        $this->acctg[$key3]['trno'] = $trno;
        $this->acctg[$key3]['db'] = round($this->acctg[$key3]['db'], 2);
        $this->acctg[$key3]['cr'] = round($this->acctg[$key3]['cr'], 2);
        $this->acctg[$key3]['fdb'] = round($this->acctg[$key3]['fdb'], 2);
        $this->acctg[$key3]['fcr'] = round($this->acctg[$key3]['fcr'], 2);

        unset($this->acctg[$key3]['client']);

        $exist = $this->coreFunctions->datareader("select trno as value from gldetail where trno=? and clientid=? and db=? and cr=? and acnoid=?", [$trno, $this->acctg[$key3]['clientid'], $this->acctg[$key3]['db'], $this->acctg[$key3]['cr'], $this->acctg[$key3]['acnoid']], '', true);
        if ($exist == 0) {
          if ($this->coreFunctions->sbcinsert($this->hdetail, $this->acctg[$key3]) == 1) {

            $line += 1;

            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'EXTRACT SALES ENTRY');
          } else {
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'EXTRACT SALES ENTRY FAILED');
            return ['status' => false, 'msg' => 'Extraction Failed-Error on Accounting Entry(' . $trno . ')'];
          }
        }
      }

      $this->coreFunctions->execqry(" insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
                select d.postdate,d.trno,d.line,coa.acnoid,d.clientid,round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal, head.docno,d.ref,d.agentid,d.fdb,d.fcr,d.forex
                from glhead as head left join gldetail as d on head.trno=d.trno left join coa on coa.acnoid=d.acnoid left join arledger as ar on ar.trno=d.trno and ar.line=d.trno
                where left(coa.alias,2)='AR' and d.trno=" . $trno . " and d.refx=0  and ar.trno is null");

      $this->coreFunctions->execqry("insert into apledger(dateid,trno,line,acnoid,clientid,db,cr,bal,fdb,fcr,docno,ref,cur,forex)
              select d.postdate,d.trno,d.line,d.acnoid,d.clientid,round(d.db,2),round(d.cr,2),round(d.db,2)+round(d.cr,2) as bal,d.fdb,d.fcr,head.docno,d.ref,d.cur,d.forex
              from glhead as head left join gldetail as d on head.trno=d.trno
              left join coa on coa.acnoid=d.acnoid left join apledger as ap on ap.trno=d.trno and ap.line=d.line
              where left(coa.alias,2)='AP' and d.trno=" . $trno . " and d.refx=0 and ap.trno is null");
    }

    return ['status' => true, 'msg' => ''];
  }
} //end class
