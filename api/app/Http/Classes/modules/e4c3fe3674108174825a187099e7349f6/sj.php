<?php

namespace App\Http\Classes\modules\e4c3fe3674108174825a187099e7349f6;

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
use App\Http\Classes\headClass;
use App\Http\Classes\builder\helpClass;
use Exception;

class sj
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SALES JOURNAL';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $statlogs = 'cntnum_stat';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AR1';
  private $stockselect;
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
    'agent',
    'projectid',
    'creditinfo',
    'billid',
    'shipid',
    'branch',
    'deptid',
    'taxdef',
    'billcontactid',
    'shipcontactid',
    'ms_freight',
    'mlcp_freight',
    'shipto',
    'salestype',
    'sotrno',
    'statid',
    'deldate',
    'crref',
    'istrip',
    'ewt',
    'ewtrate',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid',
    'bpo',
    'ctnsno',
    'invoiceno','partreqtypeid','pltrno','sdate1','sdate2','amount'
  ];

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

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
    $this->headClass = new headClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 169,
      'edit' => 170,
      'new' => 171,
      'save' => 172,
      // 'change' => 173, remove change doc
      'delete' => 174,
      'print' => 175,
      'lock' => 176,
      'unlock' => 177,
      'acctg' => 183,
      'changeamt' => 180,
      'post' => 178,
      'unpost' => 179,
      'additem' => 802,
      'edititem' => 803,
      'deleteitem' => 804,
      'release' => 2994,
      'whinfo' => 3959,
      'tripapproved' => 4494,
      'tripdisapproved' => 4738,

    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $userid = $config['params']['adminid'];
    $dept = '';
   
    if ($this->companysetup->linearapproval($config['params'])) {
      $this->showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
        ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
      ];
    }

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'total',  'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }


    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';


    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
 
    $cols[$total]['label'] = 'Total Amount';
    $cols[$total]['align'] = 'text-left';

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];

    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = '';
    $lfield = '';
    $gfield = '';
    $ljoin = '';
    $gjoin = '';
    $group = '';
    $lstat = "'DRAFT'";
    $gstat = "'POSTED'";
    $lstatcolor = "'blue'";
    $gstatcolor = "'grey'";

    $rem = '';
    $join = '';
    $hjoin = '';
    $addparams = '';

    $userid = $config['params']['adminid'];
    $dept = '';
   

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and head.lockdate is null and num.postdate is null and num.statid=0';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        break;
    }

    $linkstock = false;

    $dateid = "left(head.dateid,10) as dateid";
    $orderby = "order by dateid desc, docno desc";

    if ($searchfilter == "") $limit = 'limit 150';
        $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
        $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";
        if ($this->companysetup->linearapproval($config['params'])) {
          $lstat = "case when num.postdate is null and head.lockdate is null and num.statid=10 then 'FOR APPROVAL' 
          when num.postdate is null and num.statid=36 then 'APPROVED' else 'DRAFT' end";
          $lstatcolor = "case when num.postdate is null and num.statid=36 or num.statid=10 then 'grey' when head.lockdate is not null then 'green' else 'red' end";
    }


    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = [
        'head.docno',
        'head.clientname',
        'head.yourref',
        'head.ourref',
        'num.postedby',
        'head.createby',
        'head.editby',
        'head.viewby',
        'head.rem'
      ];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $lstat as status, $lstatcolor as statuscolor,$rem
    head.createby,head.editby,head.viewby,num.postedby,
     head.yourref, head.ourref,head.shipto $lfield
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $ljoin
     " . $join . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     
     $group
     union all
     select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid,$gstat as status,$gstatcolor as statuscolor,$rem
     head.createby,head.editby,head.viewby, num.postedby,
      head.yourref, head.ourref,head.shipto $gfield
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $gjoin
     " . $hjoin . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
    
     $group
    $orderby $limit";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    $isshortcutso = $this->companysetup->getisshortcutso($config['params']);

    $fields = [];
   
    if ($isshortcutso) {
      $allownew = $this->othersClass->checkAccess($config['params']['user'], 171);
      if ($allownew == '1') {
        array_push($fields, 'pickpo');
      }
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'pickpo.label', 'PICK SO');
    data_set($col1, 'pickpo.lookupclass', 'pendingsosummaryshortcut');
    data_set($col1, 'pickpo.action', 'pendingsosummary');
    data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick SO?');
    data_set($col1, 'pickpo.addedparams', ['docno', 'selectprefix']);

   
    $data = $this->coreFunctions->opentable("select '' as docno, '' as selectprefix");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'btnstocksave', 'btnsaveitem']);
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'sj', 'title' => 'SJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
    $deliverystatus = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewdeliverystatus']];
    $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

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
    $companyid = $config['params']['companyid'];
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $viewacctg = $this->othersClass->checkAccess($config['params']['user'], 183);

    if (!$viewacctg) {
      $headgridbtns = ['viewref', 'viewdiagram', 'viewitemstockinfo'];
    } else {
      $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram', 'viewitemstockinfo'];
    }

    $column = [
      'action',
      'isqty',
      'uom',
      'isamt',
      'disc',
      'ext',
      'sku',
      'cost',
      'markup',
      'wh',
      'ref',
      'rem',
      'itemname',
      'noprint',
      'barcode'
    ];
    $sortcolumn = [
      'action',
      'isqty',
      'uom',
      'isamt',
      'disc',
      'ext',
      'sku',
      'cost',
      'markup',
      'wh',
      'ref',
      'rem',
      'itemname',
      'noprint',
      'barcode'
    ];

    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];
    foreach ($column as $key => $value) {
        $$value = $key;
      }
    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ]
    ];

    $stockbuttons = ['save', 'delete', 'showbalance', 'stockinfo','showcomponent'];


    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][$action]['style'] = 'text-align: left; width: 150px;whiteSpace: normal;min-width:150px';
   
    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$markup]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';
    $obj[0]['inventory']['columns'][$sku]['readonly'] = true;
    $obj[0]['inventory']['columns'][$sku]['style'] = 'text-align: left; width: 150px;whiteSpace: normal;min-width:150px';
    if (!$access['changeamt']) {
      // 3 - isamt
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      // 4 - disc
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $isserial = $this->companysetup->getserial($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $tbuttons =['multiitem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingso'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $inv = $this->companysetup->isinvonly($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['docno', 'client', 'clientname','address','dwhname'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = [['dateid', 'terms'], 'due', ['dacnoname','dvattype'],['subgroup' ,'fname'],['mname','lname'],['sdate1','sdate2']];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'dacnoname.label', 'AR Account');
    data_set($col2, 'dacnoname.lookupclass', 'AR');
    data_set($col2, 'subgroup.lookupclass','lookupgroup');
    data_set($col2, 'subgroup.action','lookupgroup');
    data_set($col2, 'subgroup.label','Group #');
    data_set($col2, 'fname.class','csfname sbccsreadonly');
    data_set($col2, 'fname.label','Repacker 1');
    data_set($col2, 'fname.required',false);
    data_set($col2, 'mname.class','csmname sbccsreadonly');
    data_set($col2, 'mname.required',false);
    data_set($col2, 'mname.label','Repacker 2');
    data_set($col2, 'lname.class','cslname sbccsreadonly');
    data_set($col2, 'lname.label','Repacker 3');
    data_set($col2, 'lname.required',false);

    data_set($col2, 'dateid2.readonly',false);
    data_set($col2, 'dateid2.label','Start Date');

    data_set($col2, 'dateid3.readonly',false);
    data_set($col2, 'dateid3.label','End Date');
    
    //col3
    $fields = [['yourref', 'ourref'], ['cur', 'forex'],'dagentname',['salesgroup','mmname'],['sname','ename'],['deldate','amount']];
    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'salesgroup.lookupclass','lookupgroup1');
    data_set($col3, 'salesgroup.action','lookupgroup');
    data_set($col3, 'salesgroup.label','Group #');
    data_set($col3, 'mmname.class','csmmname sbccsreadonly');
    data_set($col3, 'mmname.label','Repacker 1');
    data_set($col3, 'mmname.required',false);
    data_set($col3, 'sname.class','cssname sbccsreadonly');
    data_set($col3, 'sname.required',false);
    data_set($col3, 'sname.label','Repacker 2');
    data_set($col3, 'ename.class','csename sbccsreadonly');
    data_set($col3, 'ename.label','Repacker 3');
    data_set($col3, 'ename.required',false);

    data_set($col3, 'deldate.readonly',false);
    data_set($col3, 'deldate.label','Deliver Date');

    data_set($col3, 'amount.label','Total Boxes');


    $fields = ['rem', 'creditinfo'];
   
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    if ($this->companysetup->linearapproval($config['params'])) {
      array_push($fields, 'forapproval', 'doneapproved', 'lblapproved');
    }


    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function defaultheaddata($params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = '';
    $data[0]['dateid'] = date('Y-m-d');
    $data[0]['due'] = date('Y-m-d');
    $data[0]['client'] = 'CL0000000000001';
    $data[0]['clientname'] = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['client']]);
    $data[0]['address'] = $this->coreFunctions->getfieldvalue('client', 'addr', 'client=?', [$data[0]['client']]);
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['tax'] = 0;
    $data[0]['dagentname'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['dacnoname'] = '';
    $data[0]['agent'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['agentname'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';

    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';
    
    $data[0]['taxdef'] = '0';
    $data[0]['dept'] = '';
    $data[0]['partreqtypeid'] = '0';
    $data[0]['pltrno'] = '0';

    $data[0]['sdate1'] = date('Y-m-d');
    $data[0]['sdate2'] = date('Y-m-d');
    $data[0]['deldate'] = date('Y-m-d');
    $data[0]['amount'] = 0;

    return $data;
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
    $data[0]['tax'] = 0;
    $data[0]['dagentname'] = '';
    $data[0]['dvattype'] = '';
    $data[0]['dacnoname'] = '';
    $data[0]['agent'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['agentname'] = '';
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';
    $data[0]['partreqtypeid'] = '0'; //group 1
    $data[0]['pltrno'] = '0'; //group 2

    $data[0]['sdate1'] = $this->othersClass->getCurrentDate();
    $data[0]['sdate2'] = $this->othersClass->getCurrentDate();
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['amount'] = 0;
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $isapproved = $this->othersClass->isapproved($config['params']['trno'], "hcntnuminfo");
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value 
        from " . $this->tablenum . " 
        where doc=? and center=? 
        order by trno desc limit 1", [$doc, $center]);
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
    $hideheadergridbtns = [];

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
         ifnull(agent.client,'') as agent,
         ifnull(agent.clientname,'') as agentname,'' as dagentname,
         head.tax,
         head.vattype,
         '' as dvattype,
         warehouse.client as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         date(head.deldate) as deldate,head.partreqtypeid ,head.pltrno, 
         req.category as subgroup, req.reqtype as fname ,req.code as mname ,req.position as lname,
         req2.category as salesgroup,  req2.reqtype as mmname ,req2.code as sname ,req2.position as ename,
         date(head.sdate1) as sdate1, date(head.sdate2) as sdate2, format(head.amount,0) as amount
         
         ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
        left join trxstatus as stat on stat.line = head.statid
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        left join reqcategory as req on req.line=head.partreqtypeid
        left join reqcategory as req2 on req2.line=head.pltrno
        where head.trno = ? and num.doc=? and num.center = ?
        union all 
        " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acno=head.contra        
        left join trxstatus as stat on stat.line = head.statid
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        left join reqcategory as req on req.line=head.partreqtypeid
        left join reqcategory as req2 on req2.line=head.pltrno
        where head.trno = ? and num.doc=? and num.center=? and left(num.bref,3) <> 'SJS' ";

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

      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }    
      $hideheadergridbtns = [];
    
      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
        'islocked' => $islocked,
        'isposted' => $isposted,
        'isnew' => false,
        'status' => true,
        'msg' => $msg,
        'hideobj' => $hideobj,
        'hideheadgridbtns' => $hideheadergridbtns
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
    $companyid = $config['params']['companyid'];
    $data = [];
    $info = [];
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
      $this->othersClass->getcreditinfo($config, $this->head);
      $this->recomputestock($head, $config);

    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->othersClass->getcreditinfo($config, $this->head);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
  } // end function

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->getfieldvalue($table, 'docno', 'trno=?', [$trno]);
    $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno], 'trno desc');
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $table . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from delstatus where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from cntnuminfo where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    
    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1']);
     
      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      $stock = $this->openstock($trno, $config);
      $checkcosting = $this->othersClass->checkcosting($stock);
      if ($checkcosting != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
      }

      $override = $this->othersClass->checkAccess($config['params']['user'], 1729);

      $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
      $islimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);

      if (floatval($islimit) == 0) {
        if ($override == '0') {
            $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
            $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
            $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
            $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);

            if ($cstatus <> 'ACTIVE') {
              $this->logger->sbcwritelog($trno, $config, 'POST', 'Customer Status is not Active.');
              return ['status' => false, 'msg' => 'Posting failed. The customer`s status is not active.'];
            }

            //if (floatval($overdue) > 0) {
            if (floatval($crline) < floatval($totalso)) {
              $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit');
              return ['status' => false, 'msg' => 'Posting failed. Overdue account or credit limit exceeded.'];
            }
            //}
          }
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        $return = $this->othersClass->posttranstock($config);
        return $return;
      }
    }
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $itemname = 'item.itemname,';
    $markup_field = "stock.Amt";

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno,
    stock.line,
    stock.sortline,
    stock.refx,
    stock.linex,
    item.barcode,
    $itemname
    stock.uom,
    FORMAT(uom.factor*stock.cost,6) as cost,
    stock.kgs,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as iss,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as isqty,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as qty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    stock.void,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,
    item.brand,
    stock.rem,
    stock.palletid,
    stock.locid,
    ifnull(uom.factor,1) as uomfactor,
    round(case when (stock.Amt>0 and stock.iss>0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / ($markup_field * stock.Iss))/head.forex)*100) else 0 end,2) as markup,
    
    stock.rebate,
    round(case when stock.Amt>0 then ((stock.amt-stock.cost)/head.forex) else 0 end,2) as gprofit,
    '' as bgcolor,
    '' as errcolor,

    case when stock.noprint=0 then 'false' else 'true' end as noprint,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    sku.sku,stock.color,stock.cline";

    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
 
    $leftjoin = '';
    $hleftjoin = '';
    $stockinfogroup = '';

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client on client.client=head.client
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join sku on sku.itemid = item.itemid and sku.groupid = client.groupid
       
    $leftjoin
    where stock.trno =? and stock.cline=0
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    uom.factor,head.forex,stock.rebate,
    stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color,
    stock.phaseid, stock.modelid,stock.blklotid,item.namt6,sku.sku,stock.cline

    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join $this->hhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client on client.clientid=head.clientid
    left join client as warehouse on warehouse.clientid=stock.whid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join sku on sku.itemid = item.itemid and sku.groupid = client.groupid
    
    $hleftjoin
    where stock.trno =?  and stock.cline=0
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    uom.factor,head.forex,stock.rebate,
    stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color,
    stock.phaseid, stock.modelid,stock.blklotid,  item.namt6,sku.sku,stock.cline

    order by sortline, line";


    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
   
    $leftjoin = '';
    $stockinfogroup = '';

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client on client.client=head.client   
    left join client as warehouse on warehouse.clientid=stock.whid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    left join sku on sku.itemid = item.itemid and sku.groupid = client.groupid
   
    $leftjoin
    where stock.trno = ? and stock.line = ?  and stock.cline=0
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    uom.factor,head.forex,stock.rebate,
    stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc,i.itemdescription,stock.itemstatus, stock.isqty,stock.color,
     stock.phaseid,  stock.modelid, stock.blklotid, item.namt6,sku.sku,stock.cline
    ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        $return =  $this->additem('insert', $config);
        if ($return['status'] == true) {
          $this->othersClass->getcreditinfo($config, $this->head);
        }
        return $return;
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
      case 'getsosummary':
        return $this->getsosummary($config);
        break;
      case 'getsodetails':
        return $this->getsodetails($config);
        break;
      case 'getitem':
        return $this->othersClass->getmultiitem($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ') SJ'];
        break;
    }
  }

  public function diagram($config)
  {
    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1650;
    $startx = 100;
    $a = 0;

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so
     left join hsostock as s on s.trno = so.trno
     left join glstock as sstock on sstock.refx = s.trno and sstock.linex = s.line
     where sstock.trno = ?
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      foreach ($t as $key => $value) {
        //SO
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
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'sj']);
        $a = $a + 100;
      }
    }

    //SJ
    $qry = "
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), if(head.ms_freight<>0,concat('\rOther Charges: ',round(head.ms_freight,2)),''),'\r\r', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem,
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where head.trno=?
    group by head.docno, head.dateid, head.trno, ar.bal, head.ms_freight";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      data_set(
        $nodes,
        'sj',
        [
          'align' => 'left',
          'x' => $startx,
          'y' => 100,
          'w' => 400,
          'h' => 80,
          'type' => $t[0]->docno,
          'label' => $t[0]->rem,
          'color' => 'green',
          'details' => [$t[0]->dateid]
        ]
      );

      foreach ($t as $key => $value) {
        //CR
        $sjtrno = $t[$key]->trno;
        $crqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'";
        $crdata = $this->coreFunctions->opentable($crqry, [$sjtrno, $sjtrno]);
        if (!empty($crdata)) {
          foreach ($crdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $crdata[$key2]->docno,
                'label' => $crdata[$key2]->rem,
                'color' => 'red',
                'details' => [$crdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $cmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid";
        $cmdata = $this->coreFunctions->opentable($cmqry, [$sjtrno, $sjtrno]);
        if (!empty($cmdata)) {
          foreach ($cmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $cmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' => $cmdata[$key2]->docno,
                'label' => $cmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$cmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $cmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
      }
    }
    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }


  public function stockstatusposted($config)
  {
    $tablenum = $this->tablenum;
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }

    switch ($action) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'downloadexcel':
        return $this->downloadexcel($config);
        break;
      case 'uploadexcel':
        return $this->othersClass->uploadexcel($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  private function downloadexcel($config)
  {
    $trno = $config['params']['trno'];
    $cntnum = $this->coreFunctions->opentable("select docno, ifnull(postdate,'') as postdate from cntnum where trno=?", [$trno]);
    if (empty($cntnum)) {
      return ['status' => false, 'msg' => 'Failed to download, invalid transaction', 'name' => 'dr', 'data' => []];
    }

    $data = $this->coreFunctions->opentable("select item.barcode as `itemcode`, s.uom, s.isqty as `qty`, s.disc, s.isamt as `cost`, s.kgs, s.sortline, s.line from lahead as h left join lastock as s on s.trno=h.trno left join item on item.itemid=s.itemid where h.trno=? 
                                            union all
                                            select item.barcode as `itemcode`, s.uom, s.isqty as `qty`, s.disc, s.isamt as `cost`, s.kgs, s.sortline, s.line from glhead as h left join glstock as s on s.trno=h.trno left join item on item.itemid=s.itemid where h.trno=? 
                                            order by sortline, line", [$trno, $trno]);

    $this->logger->sbcwritelog($trno, $config, 'EXPORT', 'DOWNLOAD EXCEL FILE');
    return ['status' => true, 'msg' => $cntnum[0]->docno . ' is ready to Download', 'name' => 'dr', 'data' => $data];
  }

 
  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstockline($config);
    $msg = '';
    if ($isupdate['msg'] != '') {
      $msg = $isupdate['msg'];
    }
    if (!$isupdate['status']) {
      $data[0]->errcolor = 'bg-red-2';

      return ['row' => $data, 'status' => true, 'msg' => $msg];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }

  public function updateitem($config)
  {
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $update = $this->additem('update', $config);
      if ($msg != '') {
        $msg = $msg . ' ' . $update['msg'];
      } else {
        $msg = $update['msg'];
      }
    }
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
      }
    }

    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
  } //end function

  public function addallitem($config)
  {
    $companyid = $config['params']['companyid'];
    $msg = '';

    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $row = $this->additem('insert', $config);

      if ($msg != '') {
        $msg = $msg . ' ' . $row['msg'];
      } else {
        $msg = $row['msg'];
      }

      if (isset($config['params']['data']['refx'])) {
        if ($config['params']['data']['refx'] != 0) {
          if ($this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $row['row'][0]->trno, 'line' => $row['row'][0]->line]);
            $this->setserveditems($config['params']['data']['refx'], $config['params']['data']['linex']);
            if ($msg != '') {
              $msg = $msg . '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
            } else {
              $msg = '(' . $row['row'][0]->barcode . ') Issued Qty is Greater than SO Qty ';
            }
          }
        }
      }
    }

    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $status = true;

    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $status = false;
      }
    }

    return ['inventory' => $data, 'status' => true, 'msg' => $msg];
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
    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom, '' as expiry from item where barcode=?", [$barcode]);
    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $data = $this->getlatestprice($config);

      if (!empty($data)) {
        $item[0]->amt = $data['data'][0]->amt;
        $item[0]->disc = $data['data'][0]->disc;
        $item[0]->uom = $data['data'][0]->uom;
      }
      $config['params']['data'] = json_decode(json_encode($item[0]), true);
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $ispallet = $this->companysetup->getispallet($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];
    $line = 0;
    $cline=0;

    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : "";
    $wh = $config['params']['data']['wh'];
    $refx = isset($config['params']['data']['refx']) ? $config['params']['data']['refx'] : 0;
    $linex = isset($config['params']['data']['linex']) ? $config['params']['data']['linex'] : 0;
    $ref = isset($config['params']['data']['ref']) ? $config['params']['data']['ref'] : '';
    $noprint = isset($config['params']['data']['noprint']) ? $config['params']['data']['noprint'] : 'false';
    $rem = isset($config['params']['data']['rem']) ? $config['params']['data']['rem'] : '';
    $cline = isset($config['params']['data']['cline']) ? $config['params']['data']['cline'] : 0;
    

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
     
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $iscomponent =0;
    
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv,namt4,isfg from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    $cost = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      $iscomponent = $item[0]->isfg;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));

    if ($this->companysetup->getisdiscperqty($config['params'])) {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur,0, 0, 1);
    } else {
        $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);
    }

    $this->coreFunctions->LogConsole($qty.'ccc');
    if (floatval($curtopeso) == 0) {
      $curtopeso = 1;
    }

    $hamt = $computedata['amt'] * $curtopeso;
    $hamt = $this->othersClass->sanitizekeyfield('amt', $hamt);


    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $hamt,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'rem' => $rem,
      'ref' => $ref,
      'uom' => $uom,
      'noprint' => $noprint,
      'cline' => $cline
    ];

    
    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }

    $iss = $computedata['qty'];
    //insert item
    if ($action == 'insert') {
      $sjitemlimit = $this->companysetup->getsjitemlimit($config['params']);
      if ($sjitemlimit != 0) {
        $qry = "select ifnull(count(stock.trno),0) as itmcnt from lahead as head
              left join lastock as stock on stock.trno=head.trno
              where head.doc='sj' and head.trno=?";
        $count = $this->coreFunctions->opentable($qry, [$trno]);

        if ($count[0]->itmcnt >= $sjitemlimit) {
          return ['status' => false, 'msg' => 'Item Records Limit Reached(' . $sjitemlimit . 'max)'];
        }
      }

      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      if (isset($config['params']['data']['sortline'])) {
        $data['sortline'] =  $config['params']['data']['sortline'];
      } else {
        $data['sortline'] =  $data['line'];
      }

      $trno = $this->othersClass->val($trno);
      if ($trno == 0) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ZERO TRNO (SJ)');
        return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $havestock = true;
        $msg = 'Item was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' Uom:' . $uom . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');

        if ($isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], '', '', $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

            //CHECK BELOW COST
            if ($this->companysetup->checkbelowcost($config['params'])) {
              $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
              if ($belowcost == 1) {
                $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
              } elseif ($belowcost == 2) {
                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
              }
            }
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
          }
        }

        //insert components
        if($iscomponent == 1){
          $cline = $line;
          $citem =  $item[0]->barcode;
          $addc = $this->addcomponents($config,$itemid,$trno,$line,$iss,$wh,'insert');          
          if(!$addc){
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK_COMPONENT', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $cline]);
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK_COMPONENT', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'cline' => $cline]);
            $cdata =$this->coreFunctions->opentable("select trno,line from ".$this->stock." where trno =? and cline =?",[$trno,$cline]);

            if(!empty($cdata)){//delete components
              foreach ($cdata as $key => $value) {
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$cdata[$key]->trno, $cdata[$key]->line]);
              }
            }

            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF COMPONENTS - Line:' . $cline . ' barcode:' . $citem . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
            $msg = $citem.' component is out of Stock.';
          }
        }

        if ($this->setserveditems($refx, $linex) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than RR Qty.";
          }

        $this->othersClass->getcreditinfo($config, $this->head);
        $row = $this->openstockline($config);
        if (!$havestock) {
          if($cline == 0){
            $row[0]->errcolor = 'bg-red-2';
            $msg = '(' . $item[0]->barcode . ') Out of Stock.';
          }
          
        }
       
        return ['row' => $row, 'status' => true, 'msg' => $msg,'havestock'=>$havestock];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed','havestock'=>false];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);

      if ($isnoninv == 0) {
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], '','', $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

          //CHECK BELOW COST
          if ($this->companysetup->checkbelowcost($config['params'])) {
            $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
            if ($belowcost == 1) {
              $msg = '(' . $item[0]->barcode . ') Is this free if charge? Please check.';
            } elseif ($belowcost == 2) {
              $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
              $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
              $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
              $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
              $return = false;
            }
          }
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->setserveditems($refx, $linex);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Out of Stock.";
        }
      }

      //update components
      if($iscomponent == 1){
        $cline = $line;
        $citem =  $item[0]->barcode;
        $addc = $this->addcomponents($config,$itemid,$trno,$line,$iss,$wh,'update');          
        if(!$addc){
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK_COMPONENT', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $cline]);
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK_COMPONENT', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'cline' => $cline]);
          $cdata =$this->coreFunctions->opentable("select trno,line from ".$this->stock." where trno =? and cline =?",[$trno,$cline]);

          if(!empty($cdata)){//delete components
            foreach ($cdata as $key => $value) {
              $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$cdata[$key]->trno, $cdata[$key]->line]);
            }
          }
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF COMPONENTS - Line:' . $cline . ' barcode:' . $citem . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
          $msg = $citem.' component (' . $item[0]->barcode . ') Out of Stock.';
        }
      }

      setServed:
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
        }

        return ['status' => $return, 'msg' => $msg, 'havestock' => $return];
    }
  } // end function

  private function addcomponents($config,$itemid,$trno,$line,$qty,$wh,$action){
    if($action == 'insert'){
      $cdata = $this->coreFunctions->opentable("select 1 as noprint,$line as cline,0 as line,i.itemid,c.isqty,(c.qty/uom.factor) as qty,c.uom from component as c left join item as i on i.barcode = c.barcode left join uom on uom.uom = c.uom and uom.itemid = i.itemid where c.itemid = ".$itemid);
    }else{
      $cdata = $this->coreFunctions->opentable("select s.noprint,s.cline,s.line,i.itemid,c.isqty,(c.qty/uom.factor) as qty,s.uom
      from ".$this->stock." as s left join item as i on i.itemid =s.itemid
      left join component as c on c.barcode = i.barcode
      left join uom on uom.uom = c.uom and uom.itemid = i.itemid where s.trno = ".$trno." and s.cline =".$line);
    }
    
    foreach ($cdata as $c => $v) {
      $config['params']['data']['uom'] = $cdata[$c]->uom;
      $config['params']['data']['itemid'] = $cdata[$c]->itemid;
      $config['params']['trno'] = $trno;
      
      if($action == 'update'){
        $config['params']['data'][$this->dqty] =  $cdata[$c]->isqty*$qty;
        $config['params']['data'][$this->damt] = 0;
      }else{
        $config['params']['data']['qty'] =  $cdata[$c]->isqty*$qty;
        $config['params']['data']['amt'] = 0;
      }
      
      $config['params']['data']['wh'] = $wh;     
      $config['params']['data']['cline'] = $cdata[$c]->cline;
      $config['params']['data']['line'] = $cdata[$c]->line;
      $config['params']['data']['noprint'] = $cdata[$c]->noprint;
      $return = $this->additem($action, $config);
      if(!$return['havestock']){
        return false;
      }
    
    }
    return true;
  }

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
      foreach ($data2 as $key => $value) {
        $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
      }
    }

    $data = $this->coreFunctions->opentable('select refx,linex,porefx,polinex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function setservedpoitems($refx, $linex)
  {
    if ($refx == 0) return 1;
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as stock on stock.trno=head.trno where head.doc in ('SJ', 'BO') and stock.porefx=" . $refx . " and stock.polinex=" . $linex . " union all
      select stock." . $this->hqty . " from glhead as head left join glstock as stock on stock.trno=head.trno where head.doc in ('SJ', 'BO') and stock.porefx=" . $refx . " and stock.polinex=" . $linex;
    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2, [], '', true);
    $this->coreFunctions->create_Elog($qry1);
    $result = $this->coreFunctions->execqry("update hpostock set sjqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and qty>sjqa and void=0", [$refx], '', true);
    if ($status == 1) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hpostock where trno=? and sjqa<>0 and void=0", [$refx], '', true);
      if ($status == 1) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx, 'update');
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx, 'update');
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx, 'update');
    }
    return $result;
  }

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc in ('SJ','BO') and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc in ('SJ','BO') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    $result = $this->coreFunctions->execqry("update hsostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');

    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and iss>qa and void=0", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and qa<>0 and void=0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx); // partial
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx); // no SJ
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx); //complete
    }

    return $result;
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];

    $data = $this->openstockline($config);
    
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $cdata =$this->coreFunctions->opentable("select trno,line from ".$this->stock." where trno =? and cline =?",[$trno,$line]);

    if ($this->companysetup->getserial($config['params'])) {
      $this->othersClass->deleteserialout($trno, $line);
    }

    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);

    if(!empty($cdata)){//delete components
      foreach ($cdata as $key => $value) {
        $this->coreFunctions->execqry("delete from ".$this->stock."  where trno=? and line=?", 'delete', [$cdata[$key]->trno, $cdata[$key]->line]);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$cdata[$key]->trno, $cdata[$key]->line]);
      }
    }

    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );

    $this->setserveditems($data[0]->refx, $data[0]->linex);

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
   
    $data = [];
//get from barcode list
    $qry = "select 'Barcode List' as docno, left(now(),10) as dateid,s.amt,s.amt as defamt,s.disc,s.uom,'' as rem from sku as s 
    left join item on item.itemid = s.itemid left join client on client.groupid = s.groupid where item.barcode =? and  client.client = ?
    union all
    select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
        round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem from(select head.docno,head.dateid,
          stock.isamt as amt,stock.uom,stock.disc,'test' as rem
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and head.client = ?
          and stock.isamt <> 0 and cntnum.trno <> ?
          UNION ALL
          select head.docno,head.dateid,stock.isamt as computeramt,
          stock.uom,stock.disc,'test' as rem from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          where head.doc = 'SJ' and cntnum.center = ?
          and item.barcode = ? and client.client = ?
          and stock.isamt <> 0 and cntnum.trno <> ?
          order by dateid desc limit 5) as tbl order by dateid desc";

        $data = $this->coreFunctions->opentable($qry, [$barcode, $client,$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    } else {
      itempricehere:
      $qry = "select 'STOCKCARD'  as docno,left(now(),10) as dateid,amt,amt as defamt,disc,uom,'' as rem from item where barcode=? 
        union all
        select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem from(select head.docno,head.dateid,
        stock.isamt as amt,stock.uom,stock.disc,'' as rem
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid = stock.itemid
        where head.doc = 'SJ' and cntnum.center = ?
        and item.barcode = ? and head.client = ?
        and stock.isamt <> 0 and cntnum.trno <> ?
        UNION ALL
        select head.docno,head.dateid,stock.isamt as computeramt,
        stock.uom,stock.disc,'' as rem from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client on client.clientid = head.clientid
        left join cntnum on cntnum.trno=head.trno
        where head.doc = 'SJ' and cntnum.center = ?
        and item.barcode = ? and client.client = ?
        and stock.isamt <> 0 and cntnum.trno <> ?
        order by dateid desc limit 5) as tbl";
      $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);


      if (isset($data[0]->amt)) {
        if (floatval($data[0]->amt) == 0) {
          return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
        } else {
          return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
        }
      } else {
        return ['status' => false, 'msg' => 'No Latest price found...', 'data' => $data];
      }
    }
  } // end function

  public function getsosummaryqry($config)
  {
    $addfield = ",head.ourref";
    return "
        select head.docno,head.client, head.clientname, head.address, ifnull(head.rem,'') as rem, 
        head.cur, head.forex, head.shipto " . $addfield . " , head.yourref, head.terms, 
        ifnull(head.branch,0) as branch,item.itemid,stock.trno,stock.line, item.barcode,
        stock.uom,stock.amt,(stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,stock.weight,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,head.shipto,head.mlcp_freight,
        head.ms_freight,head.agent,head.projectid as hprojectid,wh.client as swh,
        info.driverid,info.helperid,info.checkerid,info.plateno,info.truckid,sinfo.itemdesc,head.sano,head.pono,head.wh,head.salestype,head.due,stock.agentamt
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as wh on wh.clientid=stock.whid 
        left join hheadinfotrans as info on info.trno=head.trno
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono
        where stock.trno = ? and stock.iss>stock.qa and stock.void=0";
  }

  public function getsosummary($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);
    $companyid = $config['params']['companyid'];
    $this->coreFunctions->LogConsole('FIFO-' . $fifoexpiration);
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $updatehead = 0;

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getsosummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        if ($updatehead == 0) {
            $headupdate = [
                'ourref' => $data[0]->ourref,
                'yourref' => $data[0]->yourref,
                'terms' => $data[0]->terms,
                'agent' => $data[0]->agent,
                'rem' => $data[0]->rem,
                'wh' => $data[0]->wh,
                'shipto' => $data[0]->shipto,
                'projectid' => $data[0]->hprojectid,
                'sano' => $data[0]->sano,
                'pono' => $data[0]->pono
              ];
        
          $updatehead = $this->coreFunctions->sbcupdate($this->head, $headupdate, ["trno" => $trno]);
        }

        foreach ($data as $key2 => $value) {

          if ($fifoexpiration) {
            $wh = $data[$key2]->swh;
            $return_result = $this->insertfifoexpiration($config, $value, $wh);
            if (!empty($return_result)) {
              foreach ($return_result as $key => $return_val) {
                array_push($rows, $return_val);
              }
            } else {
              goto defaultsjentryhere;
            }
          } else {
            defaultsjentryhere:
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->isqty;
            $config['params']['data']['wh'] = $data[$key2]->swh;
            $config['params']['data']['loc'] = $data[$key2]->loc;
            $config['params']['data']['expiry'] = $data[$key2]->expiry;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->isamt;
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['kgs'] = $data[$key2]->kgs;
            $config['params']['data']['weight'] = $data[$key2]->weight;
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;

            $return = $this->additem('insert', $config);

            if ($msg = '') {
              $msg = $return['msg'];
            } else {
              $msg = $msg . $return['msg'];
            }

            if ($return['status']) {
              if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                $line = $return['row'][0]->line;
                $config['params']['trno'] = $trno;
                $config['params']['line'] = $line;
                $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                $row = $this->openstockline($config);
                $return = ['row' => $row, 'status' => true, 'msg' => $msg];
              }
              array_push($rows, $return['row'][0]);
            }
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  } //end function

  public function insertfifoexpiration($config, $value, $wh, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $return_row = [];

    $sql = "select rrstatus.expiry,rrstatus.loc,rrstatus.whid,ifnull(sum(rrstatus.bal),0) as bal from rrstatus
        left join item on item.itemid = rrstatus.itemid left join client on client.clientid=rrstatus.whid
        where rrstatus.itemid = " . $value->itemid . " and client.client = '" . $wh . "' and rrstatus.bal <> 0 
        group by rrstatus.expiry,rrstatus.loc,rrstatus.whid order by rrstatus.expiry,rrstatus.loc,rrstatus.whid asc";

    $invdata = $this->coreFunctions->opentable($sql);

    $running_qty = $value->isqty;
    $qty = 0;

    foreach ($invdata as $key => $val) {
      $expiry  = $val->expiry;
      $loc = $val->loc;

      if ($running_qty > 0) {
        if ($running_qty > $val->bal) {
          $qty = $val->bal;
        } else {
          $qty = $running_qty;
        }

        inserthere:
        $running_qty = $running_qty - $qty;

        $config['params']['data']['uom'] = $value->uom;
        $config['params']['data']['itemid'] = $value->itemid;
        $config['params']['trno'] = $trno;
        $config['params']['data']['disc'] = $value->disc;
        $config['params']['data']['qty'] = $qty;
        $config['params']['data']['wh'] = $value->swh;
        $config['params']['data']['loc'] = $loc;
        $config['params']['data']['expiry'] = $expiry;
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $value->trno;
        $config['params']['data']['linex'] = $value->line;
        $config['params']['data']['ref'] = $value->docno;
        $config['params']['data']['amt'] = $value->isamt;
        $config['params']['data']['projectid'] = $value->projectid;
        $config['params']['data']['itemdesc'] = $value->itemdesc;
        $return = $this->additem('insert', $config, $setlog);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setserveditems($value->trno, $value->line) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($$value->trno, $value->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
          array_push($return_row, $return['row'][0]);
        }
      }

      $this->coreFunctions->LogConsole('key: ' . $key . ' - count: ' . count($invdata) . ' - bal:' . $running_qty);

      if ($key >= (count($invdata) - 1)) {
        if ($running_qty > 0) {
          $qty = $running_qty;
          $expiry  = '';
          $loc = '';
          goto inserthere;
        }
        break;
      }
    } //end foreach

    return $return_row;
  }


  public function getsodetails($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);

    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';

    foreach ($config['params']['rows'] as $key => $value) {

      $qry = "
        select head.docno, head.ourref, head.yourref, head.terms, head.agent, head.shipto, head.projectid as hprojectid,head.rem,item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,wh.client as swh,info.driverid,info.helperid,info.checkerid,info.plateno,stock.weight,sinfo.itemdesc,head.sano,head.pono,head.wh,head.due  $addfield
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as wh on wh.clientid=stock.whid left join hheadinfotrans as info on info.trno=head.trno
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        $updatehead = 0;
        foreach ($data as $key2 => $value) {

          if ($updatehead == 0) {

            $headupdate = [
                'ourref' => $data[0]->ourref,
                'yourref' => $data[0]->yourref,
                'terms' => $data[0]->terms,
                'agent' => $data[0]->agent,
                'rem' => $data[0]->rem,
                'wh' => $data[0]->wh,
                'shipto' => $data[0]->shipto,
                'projectid' => $data[0]->hprojectid,
                'sano' => $data[0]->sano,
                'pono' => $data[0]->pono
              ];
          
            $updatehead = $this->coreFunctions->sbcupdate($this->head, $headupdate, ["trno" => $trno]);
            
          }

          if ($fifoexpiration) {
            $wh = $data[$key2]->swh;
            $return_result = $this->insertfifoexpiration($config, $value, $wh);
            if (!empty($return_result)) {
              foreach ($return_result as $key => $return_val) {
                array_push($rows, $return_val);
              }
            } else {
              goto defaultsjentryhere;
            }
          } else {
            defaultsjentryhere:
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->isqty;
            if ($companyid == 15) { //nathina
              $config['params']['data']['wh'] = $data[$key2]->swh;
            } else {
              $config['params']['data']['wh'] = $wh;
            }
            $config['params']['data']['loc'] = $data[$key2]->loc;
            $config['params']['data']['expiry'] = $data[$key2]->expiry;
            $config['params']['data']['rem'] = '';
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->isamt;
            $config['params']['data']['projectid'] = $data[$key2]->projectid;
            $config['params']['data']['kgs'] = $data[$key2]->kgs;
            $config['params']['data']['weight'] = $data[$key2]->weight;
            $config['params']['data']['itemdesc'] = $data[$key2]->itemdesc;
            $return = $this->additem('insert', $config);
            if ($msg = '') {
              $msg = $return['msg'];
            } else {
              $msg = $msg . $return['msg'];
            }
            if ($return['status']) {
              if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
                $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                $line = $return['row'][0]->line;
                $config['params']['trno'] = $trno;
                $config['params']['line'] = $line;
                $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
                $row = $this->openstockline($config);
                $return = ['row' => $row, 'status' => true, 'msg' => $msg];
              }
              array_push($rows, $return['row'][0]);
            }
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function createdistribution($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $status = true;
    $totalar = 0;
    $ewt = 0;
    $ewtamt = 0;
    $isvatexsales = $this->companysetup->getvatexsales($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);
   
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    $fields = '';
   
    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,
      item.expense,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate,head.taxdef,head.deldate,head.ewt,head.ewtrate
      ' . $fields . '
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SA1']);
      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      foreach ($stock as $key => $value) {
        $params = [];
        $revacct2 = $revacct;

        if ($this->companysetup->getisdiscperqty($config['params'])) {
          $discamt = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
          $disc = $discamt * $stock[$key]->isqty;
        } else {
          $disc = ($stock[$key]->isamt * $stock[$key]->isqty) - ($this->othersClass->discount($stock[$key]->isamt * $stock[$key]->isqty, $stock[$key]->disc));
        }


        if ($vat != 0) {
          if ($isvatexsales) {
            $tax = number_format(($stock[$key]->ext * $tax2), 4, '.', '');
            $totalar = $totalar + $stock[$key]->ext;
          } else {
            $tax = number_format(($stock[$key]->ext / $tax1), 4, '.', '');
            $tax = number_format($stock[$key]->ext - $tax, 4, '.', '');
            $totalar = $totalar + number_format($stock[$key]->ext, 4, '.', '');
          }
        }

        if ($stock[$key]->revenue != '') {
          $revacct2 = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct2 = $stock[$key]->rev;
          }
        }

       
        $expense = isset($stock[$key]->expense) ? $stock[$key]->expense : '';

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => number_format($stock[$key]->ext, 2, '.', ''),
          'ar' => $stock[$key]->taxdef == 0 ? number_format($stock[$key]->ext, 2, '.', '') : 0,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct2,
          'expense' => $expense,
          'tax' =>  $stock[$key]->taxdef == 0 ? $tax : 0,
          'discamt' => number_format($disc, 2, '.', ''),
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => number_format($stock[$key]->cost * $stock[$key]->iss, 2, '.', ''),
          'fcost' => number_format($stock[$key]->fcost * $stock[$key]->iss, 2, '.', ''),
          'projectid' => $stock[$key]->projectid,
          'rebate' => $stock[$key]->rebate,
          'deldate' => $stock[$key]->deldate,
          'ewt' => $ewt
        ];
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
        $this->acctg[$key]['poref'] = $this->acctg[$key]['poref'];
        $this->acctg[$key]['podate'] = $this->acctg[$key]['podate'];
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }

      //checking for 0.01 discrepancy
      $variance = $this->coreFunctions->datareader("select sum(db-cr) as value from " . $this->detail . " where trno=?", [$trno], '', true);
      if (abs($variance) == 0.01) {
        $taxamt = $this->coreFunctions->datareader("select d.cr as value from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and coa.alias='TX2'", [$trno], '', true);
        if ($taxamt != 0) {
          $salesentry = $this->coreFunctions->opentable("select d.line from " . $this->detail . " as d left join coa on coa.acnoid=d.acnoid where d.trno=? and left(coa.alias,2)='SA'  order by d.line desc limit 1", [$trno]);
          if ($salesentry) {
            $this->coreFunctions->execqry("update " . $this->detail . " set cr=cr+" . $variance . " where trno=" . $trno . " and line=" . $salesentry[0]->line);
            $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'FORCE BALANCE WITH 0.01 VARIANCE');
          }
        }
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {
    $companyid = $config['params']['companyid'];
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    $ewtamt = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }
    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        if ($params['expense'] == '') {
          $cogs = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
        } else {
          $cogs =  $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['expense']]);
        }
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
        
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
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
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $dataparams = $config['params']['dataparams'];
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }

 
  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $companyid = $config['params']['companyid'];
    $deci = $this->companysetup->getdecimal('price', $config['params']);
    foreach ($data2 as $key => $value) {
      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty = $this->othersClass->sanitizekeyfield('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])));
      $computedata = $this->othersClass->computestock(
        $damt * $head['forex'],
        $data[$key]->disc,
        $dqty,
        $data[$key]->uomfactor,
        0
      );

      $computedata['amt']  = number_format($computedata['amt'], $deci, '.', '');
      $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);

      $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }

  public function getposummaryqry($config)
  {
    return "
        select head.doc,head.docno, head.client, head.clientname, head.address, ifnull(head.rem,'') as hrem, head.cur, head.forex, head.shipto, head.ourref, head.yourref, head.projectid, head.terms,
        item.itemid,stock.trno, stock.line, item.barcode,stock.uom, stock.cost, (stock.qty-stock.sjqa) as qty,stock.rrcost,stock.ext, head.wh,
        round((stock.qty-stock.sjqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,stock.rem as rem,
        stock.disc,stock.stageid,head.branch,head.billcontactid,head.shipcontactid,head.billid,head.shipid,head.tax,head.vattype,head.yourref,head.deptid,stock.sgdrate,wh.client as swh,stock.loc,
        head.ewt,head.ewtrate,head.wh,hwh.clientid as whid,
        stock.projectid as stock_projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        FROM hpohead as head 
        left join hpostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        left join client as wh on wh.clientid=stock.whid left join client as hwh on hwh.client = head.wh
        where stock.trno = ? and stock.qty>stock.sjqa and stock.void=0 ";
  }

  public function getposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $cl = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $rows = [];
    $config['params']['client'] = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);

      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;

          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['porefx'] = $data[$key2]->trno;
          $config['params']['data']['polinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['agentamt'] = 0;
          $config['params']['barcode'] = $data[$key2]->barcode;
          $iamt = $this->coreFunctions->getfieldvalue("item", "amt", "itemid=?", [$data[$key2]->itemid], '', true);
          $config['params']['data']['amt'] = $iamt;

          $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$cl]);

          if ($pricegrp != "") {
            $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
            $iamt = $this->coreFunctions->opentable("select " . $pricefield['amt'] . " as amt, " . $pricefield['disc'] . " as disc, uom, itemid from item where itemid=?", [$data[$key2]->itemid]);
            if (!empty($iamt)) {
              $config['params']['data']['amt'] = $iamt[0]->amt;
              $config['params']['data']['disc'] = $iamt[0]->disc;
            }
          }
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedpoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedpoitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

  public function getpodetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $config['params']['client'] = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $cl = $this->coreFunctions->getfieldvalue("lahead", "client", "trno=?", [$trno]);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $msg = '';

    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.doc,head.docno, head.rem as hrem, item.itemid,stock.trno,stock.rem,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.sjqa) as qty,stock.rrcost,stock.ext,
        round((stock.qty-stock.sjqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc,stock.stageid,head.yourref,head.terms,head.cur,head.forex,stock.loc,
        head.vattype,head.tax,head.ourref,head.ewt,head.ewtrate,head.wh,wh.clientid as whid,
        stock.projectid, stock.phaseid, stock.modelid, stock.blklotid, stock.amenityid, stock.subamenityid
        FROM hpohead as head 
        left join hpostock as stock on stock.trno=head.trno 
        left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom left join client as wh on wh.client = head.wh where stock.trno = ? and stock.line=? and stock.qty>stock.sjqa and stock.void=0";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);

      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['porefx'] = $data[$key2]->trno;
          $config['params']['data']['polinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['agentamt'] = 0;
          $config['params']['barcode'] = $data[$key2]->barcode;
          $iamt = $this->coreFunctions->getfieldvalue("item", "amt", "itemid=?", [$data[$key2]->itemid], '', true);
          $config['params']['data']['amt'] = $iamt;

          $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$cl]);

          if ($pricegrp != "") {
            $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
            $iamt = $this->coreFunctions->opentable("select " . $pricefield['amt'] . " as amt, " . $pricefield['disc'] . " as disc, uom, itemid from item where itemid=?", [$data[$key2]->itemid]);
            if (!empty($iamt)) {
              $config['params']['data']['amt'] = $iamt[0]->amt;
              $config['params']['data']['disc'] = $iamt[0]->disc;
            }
          }
          $config['params']['data']['stageid'] = $data[$key2]->stageid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservedpoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedpoitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

} //end class