<?php

namespace App\Http\Classes\modules\kitchenstar;

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
    'subamenityid'
  ];
  private $except = ['trno', 'dateid', 'due', 'creditinfo'];
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
      'whinfo' => 3959
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $userid = $config['params']['adminid'];
    $dept = '';

    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $total = 5;
    $yourref = 6;
    $ourref = 7;
    $rem = 8;
    $ar = 9;
    $postedby = 10;
    $createby = 11;
    $editby = 12;
    $viewby = 13;
    $receiveby = 14;
    $receivedate = 15;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'total', 'yourref', 'ourref', 'rem', 'ar', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby', 'receiveby', 'receivedate'];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';

    $cols[$total]['label'] = 'Total Amount';
    $cols[$total]['align'] = 'text-left';
    $cols[$rem]['type'] = 'coldel';
    $cols[$ar]['type'] = 'coldel';
    $cols[$receiveby]['type'] = 'coldel';
    $cols[$receivedate]['type'] = 'coldel';

    $cols[$liststatus]['name'] = 'statuscolor';

    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];

    $doc = $config['params']['doc'];
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
        $condition = ' and head.lockdate is null and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        break;
      case 'forwtinput':
        $condition = ' and num.postdate is null and num.statid=74';
        break;
      case 'forposting':
        $condition = ' and num.postdate is null and num.statid=39';
        break;
    }

    $linkstock = false;
    $dateid = "left(head.dateid,10) as dateid";
    $orderby = "order by docno desc";

    $lfield = ',format(sum(stock.ext),2) as total';
    $gfield = ',format(sum(stock.ext),2) as total';
    $ljoin = 'left join ' . $this->stock . ' as stock on stock.trno=head.trno';
    $gjoin = 'left join ' . $this->hstock . ' as stock on stock.trno=head.trno';
    $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref, head.ourref,head.lockdate';
    $orderby = "order by docno desc";
    if ($searchfilter == "") $limit = 'limit 150';

    $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
    $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.rem'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    if ($linkstock) {
      if ($group == '') {
        $group = 'group by head.trno,head.docno,head.clientname,head.dateid,
        head.createby,head.editby,head.viewby,num.postedby,
         head.yourref, head.ourref';
      }
    }
    $qry = "select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid, $lstat as status, $lstatcolor as statuscolor,$rem
    head.createby,head.editby,head.viewby,num.postedby,
     head.yourref, head.ourref $lfield
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $ljoin
     " . $join . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and num.bref <> 'SJS' 
     $group
     union all
     select head.dateid as date2,head.trno,head.docno,head.clientname,$dateid,$gstat as status,$gstatcolor as statuscolor,$rem
     head.createby,head.editby,head.viewby, num.postedby,
      head.yourref, head.ourref $gfield
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno 
     $gjoin
     " . $hjoin . "
     left join trxstatus as stat on stat.line=num.statid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
     and num.bref <> 'SJS' 
     $group
    $orderby $limit";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
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

    $fields = [];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'docno.type', 'input');
    data_set($col2, 'docno.label', 'Seq. No');

    $prefix = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'doc=? and psection=?', ['SED', 'SJ']);
    if ($prefix != '') {
      $prefixes = explode(",", $prefix);
      $list = array();
      foreach ($prefixes as $key) {
        array_push($list, ['label' => $key, 'value' => $key]);
      }
      data_set($col2, 'selectprefix.options', $list);
    }
    $data = $this->coreFunctions->opentable("select '' as docno, '' as selectprefix");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
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
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $release = $this->othersClass->checkAccess($config['params']['user'], 2994);
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $makecr = $this->othersClass->checkAccess($config['params']['user'], 3578);
    $inv = $this->companysetup->isinvonly($config['params']);

    $trip_tab = $this->othersClass->checkAccess($config['params']['user'], 4488);
    $arrived_tab = $this->othersClass->checkAccess($config['params']['user'], 4489);
    $trip_approve = $this->othersClass->checkAccess($config['params']['user'], 4494);

    $viewfieldsforgate2users = $this->othersClass->checkAccess($config['params']['user'], 2509);
    $systemtype = $this->companysetup->getsystemtype($config['params']);


    $action = 0;
    $itemdesc = 1;
    $itemdesc2 = 2;
    $isqty = 3;
    $uom = 4;
    $isamt = 5;
    $disc = 6;
    $ext = 7;
    $cost = 8;
    $markup = 9;
    $wh = 10;
    $whname =  11;
    $ref = 12;
    $loc = 13;
    $expiry = 14;
    $rem = 15;
    $itemname = 16;
    $stock_projectname = 17;
    $noprint = 18;
    $barcode = 19;


    if ($inv) {
      $headgridbtns = ['viewref', 'viewdiagram', 'viewitemstockinfo'];
    } else {
      $headgridbtns = ['viewdistribution', 'viewref', 'viewdiagram', 'viewitemstockinfo'];
    }

    $column = ['action', 'itemdescription', 'itemdesc', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'cost', 'markup',  'wh', 'whname', 'ref', 'loc', 'expiry', 'rem', 'itemname', 'stock_projectname', 'noprint', 'barcode'];
    $sortcolumn = ['action', 'itemdescription', 'itemdesc', 'isqty', 'uom', 'isamt', 'disc', 'ext', 'cost', 'markup',  'wh', 'whname', 'ref', 'loc', 'expiry', 'rem', 'itemname', 'stock_projectname', 'noprint', 'barcode'];


    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

    if ($iskgs) {
      $computefield['kgs'] = 'kgs';
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ]
    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$markup]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
    }


    $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$itemdesc2]['type'] = 'input';
    $obj[0]['inventory']['columns'][$itemdesc2]['readonly'] = 'false';
    $obj[0]['inventory']['columns'][$itemdesc2]['label'] = 'Other Description';
    $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
    }
    $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';


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
    $isserial = $this->companysetup->getserial($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    if ($isserial) {
      $tbuttons = ['poserial', 'pendingso', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    } elseif ($ispallet) {
      $tbuttons = ['poserial', 'additem', 'saveitem', 'deleteallitem'];
    } else {
      $tbuttons = [];
      array_push($tbuttons, 'additem', 'quickadd', 'saveitem', 'deleteallitem', 'pendingso');
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($isserial) {
      $obj[0]['label'] = 'SO Serial';
      $obj[0]['lookupclass'] = 'soserial';
      $obj[0]['action'] = 'soserial';
    } elseif ($ispallet) {
      $obj[0]['label'] = 'SO';
      $obj[0]['lookupclass'] = 'sopallet';
      $obj[0]['action'] = 'sopallet';
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $inv = $this->companysetup->isinvonly($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['docno', 'client', 'clientname'];

    array_push($fields, 'dprojectname');
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'client.required', false);
    data_set($col1, 'docno.label', 'Transaction#');

    if ($inv) {
      $fields = [['dateid', 'terms'], 'due', 'dwhname'];
    } else {
      $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dwhname'];
    }

    // COL2
    $fields = [['dateid', 'terms'], 'due', 'deldate', 'dacnoname', 'dwhname', 'shipto'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AR Account');
    data_set($col2, 'dacnoname.lookupclass', 'AR');
    data_set($col2, 'shipto.label', 'Trucking');

    data_set($col2, 'statname.label', 'Type');
    data_set($col2, 'statname.lookupclass', 'lookup_sjtype');

    //col3
    if ($inv) {
      $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dagentname'];
    } else {
      $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dagentname'];
    }

    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dvattype', 'dagentname', 'crref'];

    $col3 = $this->fieldClass->create($fields);

    data_set($col3, 'yourref.label', 'PO#');
    data_set($col3, 'crref.label', 'No. of boxes');

    $fields = ['rem', 'creditinfo'];


    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
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
    $data[0]['projectid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['taxdef'] = '0';
    $data[0]['dept'] = '';
    $data[0]['sotrno'] = 0;
    $data[0]['ms_freight'] = '0.00';
    $data[0]['mlcp_freight'] = '';
    $data[0]['shipto'] = '';
    $data[0]['salestype'] = '';
    $data[0]['statid'] = '0';
    $data[0]['statname'] = '';
    $data[0]['deldate'] = date('Y-m-d');
    $data[0]['crref'] = '';

    $data[0]['hauler'] = '';
    $data[0]['driver'] = '';
    $data[0]['plateno'] = '';
    $data[0]['licenseno'] = '';

    $data[0]['batchno'] = '';
    $data[0]['cwano'] = '';

    $data[0]['weightin'] = 0.00;
    $data[0]['weightintime'] = '';

    $data[0]['weightout'] = 0.00;
    $data[0]['weightouttime'] = '';

    $data[0]['cwatime'] = '';
    $data[0]['kilo'] = '0.00';
    $data[0]['assignedlane'] = '';

    $data[0]['sano'] = '0';
    $data[0]['pono'] = '0';
    $data[0]['sadesc'] = '';
    $data[0]['podesc'] = '';
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
    $data[0]['projectid'] = '0';
    $data[0]['billid'] = '0';
    $data[0]['shipid'] = '0';
    $data[0]['billcontactid'] = '0';
    $data[0]['shipcontactid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['branch'] = 0;
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['taxdef'] = '0';
    $data[0]['dept'] = '';
    $data[0]['sotrno'] = 0;
    $data[0]['ms_freight'] = '0.00';
    $data[0]['mlcp_freight'] = '';
    $data[0]['shipto'] = '';
    $data[0]['salestype'] = '';
    $data[0]['statid'] = '0';
    $data[0]['statname'] = '';
    $data[0]['deldate'] = $this->othersClass->getCurrentDate();
    $data[0]['crref'] = '';

    $data[0]['hauler'] = '';
    $data[0]['driver'] = '';
    $data[0]['plateno'] = '';
    $data[0]['licenseno'] = '';

    $data[0]['batchno'] = '';
    $data[0]['cwano'] = '';

    $data[0]['weightin'] = 0.00;
    $data[0]['weightintime'] = '';

    $data[0]['weightout'] = 0.00;
    $data[0]['weightouttime'] = '';

    $data[0]['cwatime'] = '';
    $data[0]['kilo'] = '0.00';
    $data[0]['assignedlane'] = '';

    $data[0]['sano'] = '0';
    $data[0]['pono'] = '0';
    $data[0]['sadesc'] = '';
    $data[0]['podesc'] = '';
    $data[0]['istrip'] = '0';
    $data[0]['ewt'] = '';
    $data[0]['dewt'] = '';
    $data[0]['ewtrate'] = 0;

    $data[0]['phaseid'] = 0;
    $data[0]['phase'] = '';

    $data[0]['modelid'] = 0;
    $data[0]['housemodel'] = '';

    $data[0]['blklotid'] = 0;
    $data[0]['blklot'] = '';
    $data[0]['lot'] = '';


    $data[0]['amenityid'] = 0;
    $data[0]['amenityname'] = '';

    $data[0]['subamenityid'] = 0;
    $data[0]['subamenityname'] = '';

    $data[0]['interestrate'] = 0;
    $data[0]['downpayment'] = 0.00;
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
        $trno = $this->coreFunctions->datareader("select trno as value 
        from " . $this->tablenum . " 
        where doc=? and center=? and bref <> 'SJS'
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
         left(head.deldate,10) as deldate,
          head.projectid,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         ifnull(head.crref,'') as crref,
         client.groupid,head.creditinfo,ifnull(project.code,'') as projectcode,
         head.ms_freight, num.statid as numstatid,
         head.billid, head.shipid,ifnull(b.client,'') as branchcode ,ifnull(b.clientname,'') as branchname, head.branch,'' as dbranchname,ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname, head.taxdef, head.billcontactid, head.shipcontactid,head.sotrno,
         head.mlcp_freight,head.salestype,
         head.statid, ifnull(stat.status,'') as statname,
         head.driver, ifnull(hinfo.hauler,'') as hauler, ifnull(hinfo.plateno,'') as plateno, ifnull(hinfo.licenseno,'') as licenseno, ifnull(hinfo.batchno,'') as batchno, 
         ifnull(hinfo.cwano,'') as cwano, 

         ifnull(hinfo.cwatime,'') as cwatime, 
         ifnull(hinfo.weightin,'') as weightin, 
         ifnull(hinfo.weightintime,'') as weightintime, 
         ifnull(hinfo.weightout,'') as weightout, 
         ifnull(hinfo.weightouttime,'') as weightouttime, 
         ifnull(hinfo.kilo,0) as kilo,
         ifnull(hinfo.assignedlane,'') as assignedlane,
         head.sano, ifnull(sa.sano,'') as sadesc,
         head.pono,ifnull(po.sano,'') as podesc,
         cast(ifnull(head.istrip,0) as char) as istrip,
         head.ewt,head.ewtrate,'' as dewt,
         hinfo.interestrate,hinfo.downpayment,  head.phaseid, ps.code as phase,  head.modelid, hm.model as housemodel, head.blklotid, 
           bl.blk as blklot,  bl.lot, amen.line as amenityid, amen.description as amenityname, 
           subamen.line as subamenityid, subamen.description as subamenityname,head.shipto
         ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join trxstatus as stat on stat.line = head.statid
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono 

         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid

        where head.trno = ? and num.doc=? and num.center = ? and num.bref <> 'SJS'
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join trxstatus as stat on stat.line = head.statid
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono

         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid
        where head.trno = ? and num.doc=? and num.center=? and num.bref <> 'SJS' ";

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

      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
        'islocked' => $islocked,
        'isposted' => $isposted,
        'isnew' => false,
        'status' => true,
        'msg' => $msg,
        'hideobj' => $hideobj
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
    $trno2 = $this->coreFunctions->getfieldvalue($table, 'trno', 'doc=? and trno<?', [$doc, $trno]);
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

    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if (!$this->othersClass->checkserialout($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    }

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
            $this->logger->sbcwritelog($trno, $config, 'POST', 'Customer Status is not Active');
            return ['status' => false, 'msg' => 'Posting failed. Customer status is not Active.'];
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
        if ($return) {
          $ref = $this->coreFunctions->opentable("select distinct refx from glstock where trno =?", [$trno]);

          if (!empty($ref)) {
            foreach ($ref as $key => $value) {
              $sotrno = $this->coreFunctions->datareader("select sotrno as value from hqshead where trno=?", [$ref[$key]->refx]);
              $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hqsstock where trno=? and iss=(sjqa+voidqty)", [$ref[$key]->refx]);
              if ($status) {
                $this->coreFunctions->execqry("update transnum set statid=9 where trno=" . $sotrno);
              }
            }
          }
        }
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
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $itemname = 'item.itemname,';
    $serialfield = '';

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
    uom.factor*stock.cost as cost,
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
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,
    round(case when (stock.Amt>0 and stock.iss>0 and stock.Cost>0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / (stock.Amt * stock.Iss))/head.forex)*100) else 0 end,2) markup,stock.rebate,
    round(case when stock.Amt>0 then ((stock.amt-stock.cost)/head.forex) else 0 end,2) as gprofit,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,

    case when stock.noprint=0 then 'false' else 'true' end as noprint,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,'')) as itemdescription,info.itemdesc";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
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
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join stockinfo as info on info.trno = stock.trno and info.line = stock.line
    where stock.trno =?
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc, stock.isqty,info.itemdesc 



    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join $this->hhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join hstockinfo as info on info.trno = stock.trno and info.line = stock.line
    where stock.trno =? 
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc, stock.isqty,info.itemdesc

    order by sortline, line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
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
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join stockinfo as info on info.trno = stock.trno and info.line = stock.line
    where stock.trno = ? and stock.line = ? 
    group by item.brand,mm.model_name,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, $stockinfogroup stock.uom,stock.kgs,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.palletid,stock.locid,
    pallet.name,location.loc,uom.factor,head.forex,stock.rebate,
    prj.name,stock.projectid,stock.sgdrate,stock.noprint,brand.brand_desc, stock.isqty,info.itemdesc
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
        if ($this->companysetup->getserial($config['params'])) {
          return $this->getsosummaryserial($config);
        } else {
          return $this->getsosummary($config);
        }
        break;
      case 'getsodetails':
        if ($this->companysetup->getserial($config['params'])) {
          return $this->getsodetailsserial($config);
        } else {
          return $this->getsodetails($config);
        }
        break;
      case 'getsqsummary':
        return $this->getsqsummary($config);
        break;
      case 'getsqdetails':
        return $this->getsqdetails($config);
        break;
      case 'refreshso':
        $data = $this->sqlquery->getpendingsodetailsperpallet($config);
        return ['status' => true, 'msg' => 'Refresh Data', 'data' => $data];
        break;
      case 'getserialout':
        return $this->getserialout($config);
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
     where glstock.trno = ?
     group by head.trno,head.docno,head.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    $a = 0;
    if (!empty($t)) {
      $startx = 550;

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
            where glstock.trno = ?
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
          where glstock.trno = ?
          group by head.docno,head.dateid
          union all
          select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hsqhead as head
          left join hqshead as qthead on qthead.sotrno = head.trno
          left join hqsstock as s on s.trno = qthead.trno
          left join glstock as glstock on glstock.refx = qthead.trno
          where glstock.trno = ?
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
    where sjhead.trno = ? and sjhead.docno is not null
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
    where sjhead.trno = ? and sjhead.docno is not null
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
        where stock.refx=?
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where stock.refx=?
        group by head.docno, head.dateid";
        $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
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

  public function stockstatusposted($config)
  {
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
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'SJCR');
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'downloadexcel':
        return $this->downloadexcel($config);
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
    $ispallet = $this->companysetup->getispallet($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];

    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';
    $itemdesc = isset($config['params']['data']['itemdesc']) ? $config['params']['data']['itemdesc'] : '';
    $locid = isset($config['params']['data']['locid']) ? $config['params']['data']['locid'] : 0;
    $palletid = isset($config['params']['data']['palletid']) ? $config['params']['data']['palletid'] : 0;
    $weight = isset($config['params']['data']['weight']) ? $config['params']['data']['weight'] : 0;
    $expiry = '';
    $info = [];
    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }


    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }

    $rebate = 0;
    $refx = 0;
    $linex = 0;
    $ref = '';
    $projectid = 0;
    $sgdrate = 0;
    $noprint = 'false';
    $rem = '';
    $poref = '';
    $podate = null;

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['rebate'])) {
      $rebate = $config['params']['data']['rebate'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $projectid = $config['params']['data']['projectid'];
    }

    if (isset($config['params']['data']['noprint'])) {
      $noprint = $config['params']['data']['noprint'];
    }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['podate'])) {
      $podate = $config['params']['data']['podate'];
    }

    $itemstatus = '';
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
    $kgs = $this->othersClass->sanitizekeyfield('qty', $kgs);


    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv,item.foramt from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    $floorprice = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $floorprice = $item[0]->foramt;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    if ($this->companysetup->getisdiscperqty($config['params'])) {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs, 0, 1);
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
    }

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
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'rem' => $rem,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'locid' => $locid,
      'palletid' => $palletid,
      'rebate' => $rebate,
      'noprint' => $noprint
    ];

    if ($itemdesc != '') {
      $info = [
        'trno' => $trno,
        'line' => $line,
        'itemdesc' => $itemdesc
      ];
    }


    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if (!empty($info)) {
      foreach ($info as $key2 => $v) {
        $info[$key2] = $this->othersClass->sanitizekeyfield($key2, $info[$key2]);
      }
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }

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

        if (!empty($info)) {
          $this->coreFunctions->sbcinsert("stockinfo", $info);
        }


        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' Uom:' . $uom . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        if ($isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

            //CHECK BELOW floor price
            $override = $this->othersClass->checkAccess($config['params']['user'], 1736);
            if ($override != 1) {
              if ($this->companysetup->checkbelowcost($config['params'])) {
                $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
                if ($amt == 0) {
                  $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
                } elseif ($amt < $floorprice) {
                  $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW FLOOR PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                  $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                  $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW FLOOR PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                  $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's FLOOR PRICE!!!";
                } elseif ($belowcost == 2) {
                  $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'CHECK PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                  $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                  $this->logger->sbcwritelog($trno, $config, 'STOCK', 'CHECK PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                  $msg = "(" . $item[0]->barcode . ") You can't issue this item/s, please check Price!!!";
                }
              }
            }
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
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
          $row[0]->errcolor = 'bg-red-2';
          $msg = '(' . $item[0]->barcode . ') Out of Stock.';
        }
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if (!empty($info)) {
        $exist = $this->coreFunctions->getfieldvalue("stockinfo", "trno", "trno=? and line =?", [$trno, $line]);
        if (floatval($exist) != 0) {
          $this->coreFunctions->sbcupdate("stockinfo", $info, ['trno' => $trno, 'line' => $line]);
        } else {
          $this->coreFunctions->sbcinsert("stockinfo", $info);
        }
      }
      if ($isnoninv == 0) {
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);

          //CHECK BELOW COST
          $override = $this->othersClass->checkAccess($config['params']['user'], 1736);
          if ($override != 1) {
            if ($this->companysetup->checkbelowcost($config['params'])) {
              $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
              if ($amt == 0) {
                $msg = '(' . $item[0]->barcode . ') Is this free if charge? Please check.';
              } elseif ($amt < $floorprice) {
                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW FLOOR PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW FLOOR PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s, please check Price!!!";
                $return = false;
              } elseif ($belowcost == 2) {
                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'CHECK PRICE', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'CHECK PRICE - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s,please check Price!!!";
                $return = false;
              }
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

      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
        $return = false;
        $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
      }

      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
      foreach ($data2 as $key => $value) {
        $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
      }
    }

    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
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
    stock on stock.trno=head.trno where head.doc in ('SJ','BO') and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc in ('SJ','BO') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    $result = $this->coreFunctions->execqry("update hsostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');

    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and iss>qa", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hsostock where trno=? and qa<>0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $refx);
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $refx);
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $refx);
    }

    return $result;
  }

  public function setservedsqitems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='SJ' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='SJ' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if (floatval($qty) == 0) {
      $qty = 0;
    }

    $return =  $this->coreFunctions->execqry("update hqsstock set sjqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    $sotrno = $this->coreFunctions->datareader("select sotrno as value from hqshead where trno=?", [$refx]);
    $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hqsstock where trno=? and iss>(sjqa+voidqty)", [$refx]);
    if ($status) {
      $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hqsstock where trno=? and sjqa<>0", [$refx]);
      if ($status) {
        $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $sotrno);
      } else {
        $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $sotrno);
      }
    } else {
      $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $sotrno);
    }
    return $return;
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];

    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    if ($this->companysetup->getserial($config['params'])) {
      $this->othersClass->deleteserialout($trno, $line);
    }

    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
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
    $companyid = $config['params']['companyid'];

    $pricetype = $this->companysetup->getpricetype($config['params']);
    $pricegrp = '';
    $data = [];

    switch ($pricetype) {
      case 'Stockcard':
        goto itempricehere;
        break;

      case 'CustomerGroup':
      case 'CustomerGroupLatest':
        $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$client]);
        if ($pricegrp != '') {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $this->coreFunctions->LogConsole($pricefield);

          $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as computeramt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            order by dateid desc limit 5) as tbl order by dateid desc";
          //latest trans
          $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

          if (empty($data)) {
            $this->coreFunctions->LogConsole("Empty");
            $qry = "select '" . $pricefield['label'] . "' as docno, left(now(),10) as dateid," . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom from item where barcode=? 
              union all
              select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom from(select head.docno,head.dateid,
                stock.isamt as amt,stock.uom,stock.disc
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid = stock.itemid
                where head.doc = 'SJ' and cntnum.center = ?
                and item.barcode = ? and head.client = ?
                and stock.isamt <> 0 and cntnum.trno <> ?
                UNION ALL
                select head.docno,head.dateid,stock.isamt as computeramt,
                stock.uom,stock.disc from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join item on item.itemid = stock.itemid
                left join client on client.clientid = head.clientid
                left join cntnum on cntnum.trno=head.trno
                where head.doc = 'SJ' and cntnum.center = ?
                and item.barcode = ? and client.client = ?
                and stock.isamt <> 0 and cntnum.trno <> ?
                order by dateid desc limit 5) as tbl order by dateid desc";
            $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);
          }



          if (!empty($data)) {
            goto setpricehere;
          }
        } else {
          if ($pricetype == 'CustomerGroupLatest') {
            goto getCustomerLatestPriceHere;
          } else {
            goto setpricehere;
          }
        }
        break;

      default:
        getCustomerLatestPriceHere:
        $amtfield = 'amt';
        $qry = "select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
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

        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);
        break;
    }

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
    } else {
      itempricehere:
      $qry = "select 'STOCKCARD'  as docno,left(now(),10) as dateid,amt,amt as defamt,disc,uom,'test' as rem from item where barcode=? 
            union all
            select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem from(select head.docno,head.dateid,
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
            order by dateid desc limit 5) as tbl";
      $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);

      setpricehere:
      $usdprice = 0;
      $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
      $defuom = '';

      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        if (empty($data)) {
          $data[0]->docno = 'UOM';
        }
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
        $this->coreFunctions->LogConsole('def' . $defuom . $data[0]->amt);
        if ($defuom != "") {
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            } else {
              $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
            }
          }
        } else {
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]));
            } else {
              $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]);
            }
          }
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $data[0]->docno = 'UOM';
          $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom." . $pricefield['amt'] . ",0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
        }
      }

      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, $this->companysetup->getdecimal('price', $config['params']));
        }
      }

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

  public function getposummaryqry($config)
  {
    return "
        select head.docno,head.client, head.clientname, head.address, ifnull(head.rem,'') as rem, 
        head.cur, head.forex, head.shipto,head.ourref , head.yourref, head.terms, 
        ifnull(head.branch,0) as branch,item.itemid,stock.trno,stock.line, item.barcode,
        stock.uom,stock.amt,(stock.iss-stock.qa) as iss,stock.isamt,stock.kgs,stock.weight,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.projectid,head.shipto,head.mlcp_freight,
        head.ms_freight,head.agent,head.projectid as hprojectid,wh.client as swh,
        info.driverid,info.helperid,info.checkerid,info.plateno,info.truckid,sinfo.itemdesc,head.sano,head.pono,head.wh,head.salestype,head.due,head.dateid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid 
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
        left join client as wh on wh.clientid=stock.whid 
        left join hheadinfotrans as info on info.trno=head.trno
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
        where stock.trno = ? and stock.iss>stock.qa and stock.void=0 order by stock.trno,stock.line";
  }

  public function getsosummary($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);
    $this->coreFunctions->LogConsole('FIFO-' . $fifoexpiration);
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    $updatehead = 0;

    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getposummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {

        if ($updatehead == 0) {
          $headupdate = [
            'ourref' => $data[0]->ourref,
            'yourref' => $data[0]->yourref,
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
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function insertfifoexpiration($config, $value, $wh, $setlog = false)
  {
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


  public function getsosummaryserial($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.whid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $qry = "select serialin.sline as value from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
                where rrstatus.itemid=? and rrstatus.whid=? and serialin.serial=? and serialin.outline=0 ";
          $sline = $this->coreFunctions->datareader($qry, [$data[$key2]->itemid, $data[$key2]->whid, $data[$key2]->loc]);

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';

          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
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
            } else {
              if ($sline != '') {
                $line = $return['row'][0]->line;
                $this->othersClass->insertserialout($sline, $trno, $line, $data[$key2]->loc);
              }
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function


  public function getsodetailsserial($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.disc,stock.loc,stock.expiry,stock.whid
        FROM hsohead as head left join hsostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $qry = "select serialin.sline as value from rrstatus left join serialin on serialin.trno=rrstatus.trno and serialin.line=rrstatus.line
                where rrstatus.itemid=? and rrstatus.whid=? and serialin.serial=? and serialin.outline=0 ";
          $sline = $this->coreFunctions->datareader($qry, [$data[$key2]->itemid, $data[$key2]->whid, $data[$key2]->loc]);

          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';

          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
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
              $return = ['row' => $row, 'status' => true, 'msg' => $return['msg']];
            } else {
              if ($sline != '') {
                $line = $return['row'][0]->line;
                $this->othersClass->insertserialout($sline, $trno, $line, $data[$key2]->loc);
              }
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $return['msg']];
  } //end function



  public function getsodetails($config)
  {
    $fifoexpiration = $this->companysetup->getfifoexpiration($config['params']);

    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';

    $addfield = '';

    foreach ($config['params']['rows'] as $key => $value) {

      $qry = "
        select head.docno, head.ourref, head.yourref, head.agent, head.shipto, head.projectid as hprojectid,head.rem,item.itemid,stock.trno,
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
            $config['params']['data']['wh'] = $wh;
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

  public function getsqsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, 
      date(head.dateid) as dateid, left(head.due, 10) as podate, head.yourref,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.projectid,stock.sgdrate,stock.sortline
      from hsqhead as so 
      left join hqshead as head on head.sotrno=so.trno 
      left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.voidqty) and stock.void = 0 and stock.trno=? order by stock.line
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
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          $config['params']['data']['poref'] = $data[$key2]->yourref;
          $config['params']['data']['podate'] = $data[$key2]->podate;
          $config['params']['data']['sortline'] = $data[$key2]->sortline;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function getsqdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, 
      date(head.dateid) as dateid, left(head.due, 10) as podate, head.yourref,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      FORMAT(((stock.iss-(stock.qa+stock.sjqa+stock.voidqty))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
      stock.projectid,stock.sgdrate,stock.sortline
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa+stock.voidqty) and stock.void = 0 and stock.trno=? and stock.line=?
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
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
          $config['params']['data']['poref'] = $data[$key2]->yourref;
          $config['params']['data']['podate'] = $data[$key2]->podate;
          $config['params']['data']['sortline'] = $data[$key2]->sortline;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
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
    $delcharge = $this->coreFunctions->getfieldvalue($this->head, "ms_freight", "trno=?", [$trno]);
    if ($delcharge == '') {
      $delcharge = 0;
    }
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
          $revacct = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct = $stock[$key]->rev;
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
          'revenue' => $revacct,
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

        if ($isvatexsales) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
      }
    }

    //entry ar and vat if with default tax    
    $taxdef = $this->coreFunctions->getfieldvalue($this->head, "taxdef", "trno=?", [$trno]);
    if ($taxdef != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$d[0]->contra]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => (($totalar + $taxdef) * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $totalar + $taxdef, 'fcr' => 0];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ["TX2"]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'cr' => ($taxdef * $d[0]->forex), 'db' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $taxdef, 'fcr' => 0];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if ($delcharge != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['DC1']);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => 0, 'cr' => $delcharge * $d[0]->forex, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => ($delcharge * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid, 'fcr' => 0];

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
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
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
    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ar'] - $ewtamt) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : ($params['ar'] - $ewtamt), 'fcr' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
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

    //rebate vitaline
    if (floatval($params['rebate']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR3']);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => $params['rebate'] * $forex, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['rebate'], 'fdb' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      //sales
      $sales = ($params['ext'] - $params['rebate'] - $params['tax']);
      $sales  = $sales + $params['discamt'];
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }

      // output tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sales
      $sales = ($params['ext'] - $params['rebate']);
      $sales = round(($sales + $params['discamt']), 2);
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  } //end function

  public function distributionvatex($params, $config)
  {
    $periodic = $this->companysetup->getisperiodic($config['params']);
    $systype = $this->companysetup->getsystemtype($config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) {
      $forex = 1;
    }

    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ar'] + $params['tax']) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ar'] + $params['tax'], 'fcr' => 0, 'projectid' => $params['projectid']];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //disc
    if ($this->companysetup->getissalesdisc($config['params'])) {
      if (floatval($params['discamt']) != 0) {
        $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //INV
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //cogs
        $cogs =  $params['expense'] == 0 ? $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']) : $params['expense'];
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' => $params['cost'], 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'], 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    //sales
    $sales = $params['ext'];
    if ($this->companysetup->getissalesdisc($config['params'])) {
      $sales = round(($sales + $params['discamt']), 2);
    }

    if (floatval($sales) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    // output tax
    if ($params['tax'] != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  public function getpaysummaryqry($config)
  {
    return "
    select arledger.docno,arledger.trno,arledger.line,ctbl.clientname,ctbl.client,forex.cur,forex.curtopeso as forex,arledger.acnoid,coa.acno,coa.acnoname,cntnum.center,
    arledger.clientid,arledger.db,arledger.cr, arledger.bal ,left(arledger.dateid,10) as dateid,
    abs(arledger.fdb-arledger.fcr) as fdb,glhead.yourref,gldetail.rem as drem,glhead.rem as hrem,gldetail.projectid,gldetail.subproject,
    gldetail.stageid,gldetail.branch,gldetail.deptid,gldetail.poref,gldetail.podate,coa.alias,gldetail.postdate,glhead.tax,glhead.vattype,glhead.ewt,glhead.ewtrate,a.client as agent from (arledger
    left join coa on coa.acnoid=arledger.acnoid)
    left join glhead on glhead.trno = arledger.trno
    left join gldetail on gldetail.trno=arledger.trno and gldetail.line=arledger.line
    left join cntnum on cntnum.trno = glhead.trno
    left join client as ctbl on ctbl.clientid = arledger.clientid
    left join client as a on a.clientid = glhead.agentid
    left join forex_masterfile as forex on forex.line = ctbl.forexid
    where cntnum.trno = ? and arledger.bal<>0";
  }


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
    $dataparams = $config['params']['dataparams'];
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }

  private function autocreatestock($config, $data2, $trno)
  {
    $wh = $data2['wh'];
    $rows = [];
    $msg = '';
    $qry = "
      select concat(stock.trno,stock.line) as keyid, stock.trno, stock.line, stock.itemid, item.barcode, item.itemname, stock.uom, so.docno, date(head.dateid) as dateid,
      (stock.iss-(stock.qa+stock.sjqa)) as iss,stock.isamt, stock.disc,stock.amt,stock.ext,
      ((stock.iss-(stock.qa+stock.sjqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end) as isqty,stock.projectid,stock.sgdrate
      from hsqhead as so left join hqshead as head on head.sotrno=so.trno left join hqsstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join transnum on transnum.trno = head.trno
      where so.doc='SQ' and stock.iss > (stock.qa+stock.sjqa) and stock.void = 0 and stock.trno=? order by stock.line
    ";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      foreach ($data as $key2 => $value) {
        $config['params']['data']['uom'] = $data[$key2]->uom;
        $config['params']['data']['itemid'] = $data[$key2]->itemid;
        $config['params']['trno'] = $config['params']['trno'];
        $config['params']['data']['disc'] = $data[$key2]->disc;
        $config['params']['data']['qty'] = $data[$key2]->isqty;
        $config['params']['data']['ext'] = $data[$key2]->ext;
        $config['params']['data']['wh'] = $wh;
        $config['params']['data']['rem'] = '';
        $config['params']['data']['refx'] = $data[$key2]->trno;
        $config['params']['data']['linex'] = $data[$key2]->line;
        $config['params']['data']['ref'] = $data[$key2]->docno;
        $config['params']['data']['amt'] = $data[$key2]->isamt;
        $config['params']['data']['projectid'] = $data[$key2]->projectid;
        $config['params']['data']['sgdrate'] = $data[$key2]->sgdrate;
        $return = $this->additem('insert', $config);

        if ($msg = '') {
          $msg = $return['msg'];
        } else {
          $msg = $msg . $return['msg'];
        }

        if ($return['status']) {
          if ($this->setservedsqitems($data[$key2]->trno, $data[$key2]->line) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $return['row'][0]->trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $return['row'][0]->trno, 'line' => $line]);
            $this->setservedsqitems($data[$key2]->trno, $data[$key2]->line);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => $msg];
          }
          array_push($rows, $return['row'][0]);
        }
      }
      return ['row' => $rows, 'status' => true, 'msg' => 'Item was successfully added.', 'reloaddata' => true];
    }
  }


  public function recomputestock($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
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

  public function getserialout($config)
  {
    $dinsert = [];
    $trno = $config['params']['trno'];

    foreach ($config['params']['rows'] as $key => $value) {
      $dinsert['trno'] = $trno;
      $dinsert['line'] = $config['params']['rows'][$key]['stockline'];
      $dinsert['serial'] = $config['params']['rows'][$key]['serial'];
      $sline = $config['params']['rows'][$key]['sline'];
      $outline = $this->coreFunctions->insertGetId('serialout', $dinsert);
      if ($outline != 0) {
        $qry = "update serialin set outline=? where sline=? and outline=0";
        $this->coreFunctions->execqry($qry, 'update', [$outline, $sline]);
      }
      $stockline = $config['params']['rows'][$key]['stockline'];
    }

    $data = $this->openstock($trno, $config);
    return ['status' => true, 'reloadgriddata' => true, 'msg' => 'Serial has been added.', 'griddata' => ['inventory' => $data]];
  } //end function  


} //end class