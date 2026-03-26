<?php

namespace App\Http\Classes\modules\seastar;

use Illuminate\Http\Request;
use DB;
use Session;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\headClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\modules\calendar\em;
use Exception;

class fa
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'FIXED ASSET SCHEDULE';
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
  public $damt = 'rrcost';
  public $hamt = 'cost';
  public $defaultContra = 'IN1';

  public $infohead = 'cntnuminfo';
  public $hinfohead = 'hcntnuminfo';
  public $infostock = 'stockinfo';
  public $hinfostock = 'hstockinfo';

  private $fields = [
    'trno',
    'docno',
    'dateid',
    'due',
    'client',
    'clientname',
    'yourref',
    'ourref',
    'rem',
    'terms',
    'forex',
    'cur',
    'wh',
    'address',
    'contra',
    'tax',
    'vattype',
    'projectid',
    'subproject',
    'branch',
    'deptid',
    'billid',
    'shipid',
    'billcontactid',
    'shipcontactid',
    'invoiceno',
    'invoicedate',
    'ewt',
    'ewtrate',
    'driver',
    'plateno',
    'cur2',
    'forex2'
  ];

  private $otherfields = ['loanamt', 'termsyear', 'receivedate', 'depcr', 'depdb'];
  private $except = ['trno', 'dateid', 'due'];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  private $headClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];

  private $barcode;

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
    $this->barcode = new  DNS1D;
    $this->headClass = new headClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 4787,
      'edit' => 4788,
      'new' => 4789,
      'save' => 4790,
      'delete' => 4791,
      'print' => 4792,
      'lock' => 4793,
      'unlock' => 4794,
      'changeamt' => 4797,
      'post' => 4795,
      'unpost' => 4796,
      'additem' => 4798,
      'edititem' => 4799,
      'deleteitem' => 4800
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$liststatus]['name'] = 'statuscolor';
    $cols[$rem]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $isproject = $this->companysetup->getisproject($config['params']);
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];

    $condition = '';
    $projectfilter = '';
    $limit = '';
    $fields = '';

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    $join = '';
    $hjoin = '';
    $addparams = '';

    $balfilter = '';
    $groupbylocal = '';
    $groupby = '';
    $having = '';

    if ($isproject) {
      $viewall = $this->othersClass->checkAccess($config['params']['user'], 2232);
      $project = $this->coreFunctions->getfieldvalue("useraccess", "project", "username=?", [$config['params']['user']]);
      $projectid = $this->coreFunctions->getfieldvalue("projectmasterfile", "line", "code=?", [$project]);
      if ($viewall == '0') {
        $projectfilter = " and head.projectid = " . $projectid . " ";
      }
    }
    $status = "'DRAFT'";
    $lstatus = "'DRAFT'";
    $lstatcolor = "'red'";

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and head.lockdate is null and  num.postdate is null ';
        break;

      case 'locked':
        $condition = ' and num.postdate is null and head.lockdate is not null ';
        $lstatus = "'LOCKED'";
        $lstatcolor = "'green'";
        break;

      case 'partial':
        $balfilter = ' and num.postdate is not null and rrstatus.bal>0';
        $status = "'PARTIAL'";
        break;

      case 'served':
        $balfilter = ' and num.postdate is not null';
        $having = ' having sum(rrstatus.bal)=0';
        $status = "'SERVED'";
        break;

      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $status = "'POSTED'";
    $gstatcolor = "'grey'";

    $fields .= ",left(head.dateid,10) as dateid";
    if ($search == "") $limit = 'limit 150';
    $orderby =  "order by  dateid desc, docno desc";

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }

      $limit = '';
    }


    $qry = "select head.trno,head.docno,head.clientname, case ifnull(head.lockdate,'') when '' then $lstatus else 'LOCKED' end as status,
    case ifnull(head.lockdate,'') when '' then $lstatcolor else 'green' end as statuscolor,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref, head.rem $fields
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     " . $join . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $filtersearch . $addparams . $groupbylocal . " "  . "
     union all
     select head.trno,head.docno,head.clientname,$status as status,$gstatcolor as statuscolor,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref, head.rem $fields
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     " . $hjoin . "
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $projectfilter . $condition . $filtersearch . $addparams . $balfilter . $groupby . $having . " "  . "
    $orderby  $limit";
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

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'rr', 'title' => 'Receiving Items Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createTab($access, $config)
  {

    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

    $columns = ['action', 'itemdesc', 'uom', 'rrqty', 'rrcost', 'ext'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'total' => 'ext'],
        'headgridbtns' => ['viewref', 'generatedepsched']
      ],
    ];

    $stockbuttons = ['save', 'delete'];

    $tab['viewdepsched'] = ['action' => 'fixedasset', 'lookupclass' => 'viewdepsched', 'label' => 'Depreciation Schedule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Quantity';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$itemdesc]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Cost';
    $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Amount';

    if ($viewcost == 0) {
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['headgridbtns']['generatedepsched']['lookupclass'] = 'generatedepschednoitem';
    return $obj;
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
    $systype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['docno', 'client', 'clientname', 'dprojectname'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Vendor');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'dacnoname', 'ddepcrname', 'ddepdbname'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'Asset Account');
    data_set($col2, 'dacnoname.lookupclass', '');

    $fields = [['yourref', 'ourref'], 'receivedate', ['termsyear', 'loanamt']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'termsyear.label', 'Life (years)');
    data_set($col3, 'loanamt.label', 'Monthly Depreciation');
    data_set($col3, 'loanamt.readonly', true);

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
    $data[0]['terms'] = '';

    $data[0]['yourref'] = '';
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = ""; //$this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);

    $data[0]['acnoname'] = ""; //$this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;

    $data[0]['projectid'] = '0';
    $data[0]['projectname'] = '';
    $data[0]['projectcode'] = '';

    $data[0]['dprojectname'] = '';
    $data[0]['subproject'] = '0';
    $data[0]['subprojectname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = '0';
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['billid'] = 0;
    $data[0]['shipid'] = 0;
    $data[0]['billcontactid'] = 0;
    $data[0]['shipcontactid'] = 0;
    $data[0]['invoiceno'] = '';
    $data[0]['invoicedate'] = $this->othersClass->getCurrentDate();
    $data[0]['receivedate'] = null;
    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;
    $data[0]['driver'] = '';
    $data[0]['plateno'] = '';
    $data[0]['forex2'] = 1;
    $data[0]['termsyear'] = 1;
    $data[0]['loanamt'] = 0;
    $data[0]['depcr'] = 0;
    $data[0]['depdb'] = 0;
    $data[0]['depdbcode'] = '';
    $data[0]['depdbname'] = '';
    $data[0]['depcrcode'] = '';
    $data[0]['depcrname'] = '';
    $data[0]['cur2'] = $this->companysetup->getdefaultcurrency($params);

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
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $info = $this->infohead;
    $hinfo = $this->hinfohead;

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
        head.vattype,
        head.billid,
        head.shipid,
        head.billcontactid,
        head.shipcontactid,
        '' as dvattype,
        warehouse.client as wh,
        warehouse.clientname as whname,
        '' as dwhname,
        cast(ifnull(head.istrip,0) as char) as istrip,
        head.projectid,
        '' as dprojectname,
        '' as dexcess,
        left(head.due,10) as due,
        client.groupid,ifnull(p.code,'') as projectcode,ifnull(p.name,'') as projectname,ifnull(s.line,0) as subproject,ifnull(s.subproject,'') as subprojectname,
        head.branch,ifnull(b.clientname,'') as branchname,ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
        head.deptid,'' as ddeptname,head.invoiceno,left(head.invoicedate,10) as invoicedate,head.ewt,head.ewtrate,head.excess,head.excessrate,
        head.driver,head.plateno,head.cur2,head.forex2,hinfo.carrier,hinfo.waybill,cinfo.transtype as transtyperr, cinfo.receivedate, cinfo.termsyear, 0 as loanamt, 
        cinfo.depdb, cinfo.depcr, ifnull(coa2.acno,'') as depdbcode, ifnull(coa2.acnoname,'') as depdbname, ifnull(coa3.acno,'') as depcrcode, ifnull(coa3.acnoname,'') as depcrname";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as b on b.clientid = head.branch
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        left join " . $info . " as hinfo on hinfo.trno=head.trno
        left join cntnuminfo as cinfo on cinfo.trno=head.trno
        left join coa as coa2 on coa2.acnoid=cinfo.depdb
        left join coa as coa3 on coa3.acnoid=cinfo.depcr
        where head.trno = ? and num.doc=? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as b on b.clientid = head.branch
        left join coa on coa.acno=head.contra
        left join projectmasterfile as p on p.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join subproject as s on s.line = head.subproject
        left join " . $hinfo . " as hinfo on hinfo.trno=head.trno
        left join hcntnuminfo as cinfo on cinfo.trno=head.trno
        left join coa as coa2 on coa2.acnoid=cinfo.depdb
        left join coa as coa3 on coa3.acnoid=cinfo.depcr        
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

      $ext = $this->coreFunctions->datareader("select ifnull(sum(ext),0) as value from (select ext from " . $this->stock . " where trno=" . $trno . " union all select ext from " . $this->hstock . " where trno=" . $trno . ") as s");
      if ($ext == 0) {
        $head[0]->loanamt = 0;
      } else {
        $head[0]->loanamt = number_format($ext / ($head[0]->termsyear * 12), 2);
      }

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
    $companyid = $config['params']['companyid'];
    $data = [];
    $dataother = [];
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

    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataother[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
        } //end if
      }
    }

    $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $dataother['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $dataother['editby'] = $config['params']['user'];
    $dataother['trno'] = $head['trno'];

    if ($isupdate) {
      if ($this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']])) {
        $existotherinfo = $this->coreFunctions->opentable("select trno from " . $this->infohead . " where trno=" . $head['trno']);
        if (count($existotherinfo) == 0) {
          $this->coreFunctions->sbcinsert($this->infohead, $dataother);
        } else {
          $this->coreFunctions->sbcupdate($this->infohead, $dataother, ['trno' => $head['trno']]);
        }
      }
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];

      if ($this->coreFunctions->sbcinsert($this->head, $data)) {
        $this->coreFunctions->sbcinsert($this->infohead, $dataother);
      }

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
    $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {

    return $this->othersClass->posttranstock($config);
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $isexist = $this->coreFunctions->getfieldvalue("fasched", "rrtrno", "rrtrno = ? and jvtrno <>0", [$trno]);

    if (floatval($isexist) != 0) {
      return ['status' => false, 'msg' => 'Already have posted depreciation schedule.'];
    }

    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $qty_dec = 0;
    $serialfield = '';
    $qafield = 'stock.qa';
    $costfield = 'stock.cost';

    $sqlselect = "select 
    item.itemid,
    stock.trno,
    stock.line,
    stock.sortline,
    stock.refx,
    stock.linex,
    item.barcode,
    if(ifnull(sit.itemdesc,'')='',item.itemname,sit.itemdesc) as itemname,
    stock.uom,
    stock.kgs,
   " . $costfield . " as " . $this->hamt . ",
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock." . $this->hqty . "-" . $qafield . ")/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.rem,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    prj.code as project,
    stock.poref,stock.sgdrate,
    sit.itemdesc
    
    ";

    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=stock.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    where stock.trno =? order by sortline,line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $qafield = 'stock.qa';

    $qty_dec = 0;

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
   FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    where stock.trno = ? and stock.line = ?";
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

  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];

    $wh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$trno]);
    $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);

    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['itemid'] = 0;
    $data['rrqty'] = 0;
    $data['qty'] = 0;
    $data['uom'] = '';
    $data['rrcost'] = 0;
    $data['amt'] = 0;
    $data['ext'] = 0;
    $data['disc'] = '';
    $data['itemname'] = '';
    $data['itemdesc'] = '';
    $data['barcode'] = '';
    $data['rem'] = '';
    $data['unit'] = '';
    $data['wh'] = $wh;
    $data['whid'] = $whid;
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'flowchart':
        return $this->flowchart($config);
        break;
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
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
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

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,concat('Total PO Amt: ',round(sum(s.ext),2)) as rem,s.refx 
    from hpohead as po left join hpostock as s on s.trno = po.trno left join glstock as g on g.refx = po.trno and g.linex = s.line where g.trno = ? group by po.trno,po.docno,po.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //PO
        data_set($nodes, $t[$key]->docno, ['align' => 'right', 'x' => 200, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'blue', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        if (floatval($t[$key]->refx) != 0) {
          //pr
          $qry = "select po.docno,left(po.dateid,10) as dateid,concat('Total PR Qty: ',round(sum(s.qty),2)) as rem from hprhead as po left join hprstock as s on s.trno = po.trno  where po.trno = ? group by po.docno,po.dateid";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
          $poref = $t[$key]->docno;
          if (!empty($x)) {
            foreach ($x as $key2 => $value) {
              data_set($nodes, $x[$key2]->docno, ['align' => 'right', 'x' => 10, 'y' => 50 + $a, 'w' => 250, 'h' => 80, 'type' => $x[$key2]->docno, 'label' => $x[$key2]->rem, 'color' => 'yellow', 'details' => [$x[$key2]->dateid]]);
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
              $a = $a + 100;
            }
          }
        }
      }
    }

    $qry = "select head.docno,
    left(head.dateid,10) as dateid,
    concat('Amount: ',round(ifnull(apledger.db, 0)+ifnull(apledger.cr, 0),2),'  -  ','BALANCE: ',
    round(ifnull(apledger.bal, 0),2)) as rem
    from glhead as head
    left join apledger on head.trno = apledger.trno
    where head.trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set($nodes, 'rr', ['align' => 'right', 'x' => $startx, 'y' => 100, 'w' => 250, 'h' => 130, 'type' => $t[0]->docno, 'label' => $t[0]->rem, 'color' => 'green', 'details' => [$t[0]->dateid]]);
    }



    $qry = "select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Applied Amount: ',round(detail.db+detail.cr,2)) as CHAR) as rem
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    where detail.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    where head.doc != ? and stock.refx=?
    union all
    select head.docno as docno,left(head.dateid,10) as dateid,
    CAST(concat('Return Item: ',item.barcode,'-',item.itemname,' Qty: ',round(stock.isqty, 2)) as CHAR) as rem
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid = stock.itemid
    where head.doc != ? and stock.refx=?
    ";

    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno'], $config['params']['doc'], $config['params']['trno'], $config['params']['trno'], $config['params']['trno']]);
    if (!empty($t)) {
      $y = 0;
      foreach ($t as $key => $value) {
        data_set($nodes, $t[$key]->docno, ['align' => 'left', 'x' => $startx + 400, 'y' => 50 + $y, 'w' => 250, 'h' => 80, 'type' => $t[$key]->docno, 'label' => $t[$key]->rem, 'color' => 'red', 'details' => [$t[$key]->dateid]]);
        array_push($links, ['from' => 'rr', 'to' => $t[$key]->docno]);
        $y = $y + 120;
      }
    }
    $data['nodes'] = $nodes;




    $data['links'] = $links;
    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function flowchart($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['centerX'] = 1024;
    $data['centerY'] = 140;
    $data['scale'] = 1;
    $qry = "select apledger.docno,left(apledger.dateid,10) as dateid,CAST(concat('Amount: ',round(apledger.db+apledger.cr,2),'  -  ','BALANCE: ',round(apledger.bal,2)) as CHAR) as rem from apledger where trno=?";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    array_push($nodes, ['id' => 2, 'x' => -500, 'y' => -120, 'type' => $t[0]->docno, 'label' => $t[0]->rem]);

    array_push($nodes, ['id' => 4, 'x' => -357, 'y' => 80, 'type' => 'Script', 'label' => 'test2']);
    array_push($nodes, ['id' => 6, 'x' => -557, 'y' => 80, 'type' => 'Rule', 'label' => 'test3']);
    $data['nodes'] = $nodes;
    array_push($links, ['id' => 3, 'from' => 2, 'to' => 4]);
    array_push($links, ['id' => 5, 'from' => 2, 'to' => 6]);
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
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
    } else {
      $msg = "";

      $minmax = $this->othersClass->getitemminmax($data2[0]['barcode'], $data2[0]['wh'], $data2[0]['qty']);
      if ($minmax <> "") {
        $msg = $minmax;
      }
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved -' . $msg];
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
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  public function addallitem($config)
  {
    $row = [];
    foreach ($config['params']['row'] as $key => $value) {
      $msg = 'Successfully saved.';
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);
      if ($row['status'] == false) {
        $msg = $row['msg'];
        break;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function

  public function quickadd($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $trno = $config['params']['trno'];
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $wh = $config['params']['wh'];
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,'' as disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);

    $item = json_decode(json_encode($item), true);
    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno = ?', [$trno]);
    $defuom = '';

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  public function additem($action, $config)
  {
    $trno = $config['params']['trno'];
    $line = $config['params']['data']['line'];
    $itemname = $config['params']['data']['itemdesc'];
    $qty = $config['params']['data']['rrqty'];
    $uom = $config['params']['data']['uom'];
    $rrcost = $config['params']['data']['rrcost'];
    $ext = $config['params']['data']['ext'];
    $data = [
      'trno' => $trno,
      'line' => $line,
      'rrqty' => $qty,
      'qty' => $qty,
      'rrcost' => $rrcost,
      'cost' => $rrcost,
      'ext' => $ext,
      'uom' => $uom
    ];

    $stockinfo = [
      'trno' => $trno,
      'line' => $line,
      'itemdesc' => $itemname
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
      $stockinfo['line'] = $line;
      if ($this->coreFunctions->sbcinsert($this->stock, $data)) {
        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);

        $this->coreFunctions->sbcinsert('stockinfo', $stockinfo);

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line: ' . $line . ' Item: ' . $itemname . ' Qty: ' . $ext . ' Amt: ' . $ext);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
      $this->coreFunctions->sbcupdate('stockinfo', $stockinfo, ['trno' => $trno, 'line' => $data['line']]);
      $return = true;
    }
    return $return;
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
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
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function



  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];

    // auto lock
    $config['params']['action'] = 'lock';
    $config['params']['locktype'] = 'AUTO';

    $this->headClass->lockunlock($config);

    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);

    $dataparams = $config['params']['dataparams'];
    if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
    if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }

  public function getpaysummaryqry($config)
  {
    return "
    select apledger.docno,apledger.trno,apledger.line,ctbl.clientname,ctbl.client,forex.cur,forex.curtopeso as forex,apledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    apledger.clientid,apledger.db,apledger.cr, apledger.bal ,left(apledger.dateid,10) as dateid,
    abs(apledger.fdb-apledger.fcr) as fdb,glhead.yourref,gldetail.rem as drem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,
    gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate,coa.alias,gldetail.postdate,glhead.tax,case glhead.vattype when '' then 'NON-VATABLE' else glhead.vattype end as vattype,glhead.ewt,glhead.ewtrate from (apledger
    left join coa on coa.acnoid=apledger.acnoid)
    left join glhead on glhead.trno = apledger.trno
    left join gldetail on gldetail.trno=apledger.trno and gldetail.line=apledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = apledger.clientid
    left join forex_masterfile as forex on forex.line = ctbl.forexid
    where cntnum.trno = ? and apledger.bal<>0 and coa.alias <> 'APWT1'";
  }
} //end class
