<?php

namespace App\Http\Classes\modules\autoserv;

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

class am
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Service Invoice';
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

  public $amstock = 'amjobs';
  public $hamstock = 'hamjobs';

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
    'carid'
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
      'view' => 5866,
      'edit' => 5867,
      'new' => 5868,
      'save' => 5869,
      'delete' => 5870,
      'print' => 5871,
      'lock' => 5872,
      'unlock' => 5873,
      'acctg' => 5877,
      'changeamt' => 5876,
      'post' => 5874,
      'unpost' => 5875,
      'additem' => 5878,
      'edititem' => 5879,
      'deleteitem' => 5880
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname',  'shipto', 'yourref', 'ourref',  'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
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
    $cols[$liststatus]['name'] = 'statuscolor';
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
      // case 'forwtinput':
      //   $condition = ' and num.postdate is null and num.statid=74';
      //   break;
      case 'forposting':
        $condition = ' and num.postdate is null and num.statid=39';
        break;
    }

    $dateid = "left(head.dateid,10) as dateid";
    $orderby = "order by dateid desc, docno desc";
    if ($searchfilter == "") $limit = 'limit 150';
    $lstat = "case ifnull(head.lockdate,'') when '' then 'DRAFT' else 'LOCKED' end";
    $lstatcolor = "case ifnull(head.lockdate,'') when '' then 'red' else 'green' end";
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
     and left(num.bref,3) <> 'SJS' 
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
     and left(num.bref,3) <> 'SJS' 
     $group
    $orderby $limit";
    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function paramsdatalisting($config)
  {
    // $companyid = $config['params']['companyid'];
    // $isshortcutso = $this->companysetup->getisshortcutso($config['params']);

    $fields = [];
    // switch ($companyid) {
    //   case 11: //summit
    //     $post = $this->othersClass->checkAccess($config['params']['user'], 178);
    //     if ($post) {
    //       array_push($fields, 'batchpostsj');
    //     }
    //     break;
    // }

    // if ($isshortcutso) {
    //   $allownew = $this->othersClass->checkAccess($config['params']['user'], 171);
    //   if ($allownew == '1') {
    //     array_push($fields, 'pickpo');
    //   }
    // }

    $col1 = $this->fieldClass->create($fields);
    // if ($companyid == 20) { //proline
    //   data_set($col1, 'pickpo.label', 'PICK JO');
    // } else {
    //   data_set($col1, 'pickpo.label', 'PICK SO');
    // }
    // data_set($col1, 'pickpo.lookupclass', 'pendingsosummaryshortcut');
    // data_set($col1, 'pickpo.action', 'pendingsosummary');
    // data_set($col1, 'pickpo.confirmlabel', 'Proceed to pick SO?');
    // data_set($col1, 'pickpo.addedparams', ['docno', 'selectprefix']);

    $fields = [];
    // switch ($companyid) {
    //   case 17: //unihome
    //   case 20: //proline
    //   case 10: //afti
    //   case 12: //afti usd
    //   case 27: //NTE
    //   case 28: //xcomp
    //   case 36: //ROZLAB
    //   case 39: //CBBSI
    //     array_push($fields, ['selectprefix', 'docno']);
    //     break;
    // }
    $col2 = $this->fieldClass->create($fields);
    // switch ($companyid) {
    //   case 10: //afti
    //   case 12: //afti usd
    //     data_set($col2, 'docno.type', 'input');
    //     data_set($col2, 'docno.label', 'Search');
    //     data_set($col2, 'selectprefix.label', 'Search by');
    //     data_set($col2, 'selectprefix.type', 'lookup');
    //     data_set($col2, 'selectprefix.lookupclass', 'lookupsearchby');
    //     data_set($col2, 'selectprefix.action', 'lookupsearchby');
    //     break;
    //   default:
    //     data_set($col2, 'docno.type', 'input');
    //     data_set($col2, 'docno.label', 'Seq. No');
    //     break;
    // }

    // $prefix = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'doc=? and psection=?', ['SED', 'SJ']);
    // if ($prefix != '') {
    //   $prefixes = explode(",", $prefix);
    //   $list = array();
    //   foreach ($prefixes as $key) {
    //     array_push($list, ['label' => $key, 'value' => $key]);
    //   }
    //   data_set($col2, 'selectprefix.options', $list);
    // }
    // $data = $this->coreFunctions->opentable("select '' as docno, '' as selectprefix");

    return ['status' => true, 'data' => '', 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
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
      'lock',
      'unlock',
      'post',
      'unpost',
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
    // var_dump($config['params']);
    $columns = ['action',  'code', 'description', 'rem', 'packname']; // 'packname'
    foreach ($columns as $key => $value) {
      $$value = $key;
    }


    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $tab['tableentry'] = ['action' => 'autoserventry', 'lookupclass' => 'entryamlabor', 'label' => 'TASK/LABOR'];
    $tab['tableentry2']  =  ['action' => 'autoserventry', 'lookupclass' => 'entryamparts', 'label' => 'PARTS'];

    $stockbuttons = ['save', 'addtask', 'delete'];
    $obj = $this->tabClass->createTab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$code]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$description]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";
    $obj[0][$this->gridname]['columns'][$packname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$packname]['label'] = 'Package';

    $obj[0][$this->gridname]['columns'][$code]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$description]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['action'] = 'autoserventry';
    $obj[0][$this->gridname]['columns'][$action]['btns']['addtask']['lookupclass'] = 'entryamlabor';

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['label'] = 'JOBS';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addvehicle', 'addpackage', 'addjob']; //, 'pendingso' 'quickadd', 'saveitem', 'deleteallitem','additem',
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {

    $fields = ['docno', 'client', 'clientname', 'dagentname', 'address', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'client.lookupclass', 'customer');

    $fields = [['dateid', 'terms'], 'due', 'dacnoname', 'dvattype', ['cur', 'forex'], ['yourref', 'ourref']];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dacnoname.label', 'AR Account');
    data_set($col2, 'dacnoname.lookupclass', 'AR');

    $fields = [['vehicle', 'year'], ['modelname', 'mileage'], ['licenseno', 'type'], ['motorno', 'chassisno'], ['submodel', 'engine'], ['transmission', 'mvno']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'vehicle.readonly', true);
    data_set($col3, 'vehicle.type', 'input');
    data_set($col3, 'vehicle.label', 'Car Make');
    data_set($col3, 'year.readonly', true);
    data_set($col3, 'modelname.readonly', true);
    data_set($col3, 'modelname.type', 'input');

    data_set($col3, 'licenseno.label', 'License');

    data_set($col3, 'mileage.label', 'Mileage');
    data_set($col3, 'mileage.readonly', true);


    data_set($col3, 'type.type', 'input');
    data_set($col3, 'type.readonly', true);

    data_set($col3, 'transmission.required', false);
    data_set($col3, 'mvno.required', false);

    data_set($col3, 'year.class', 'csyear sbccsreadonly');
    data_set($col3, 'licenseno.class', 'cslicenseno sbccsreadonly');
    data_set($col3, 'motorno.class', 'csmotorno sbccsreadonly');
    data_set($col3, 'submodel.class', 'cssubmodel sbccsreadonly');
    data_set($col3, 'transmission.class', 'cstransmission sbccsreadonly');

    data_set($col3, 'type.class', 'cstype sbccsreadonly');
    data_set($col3, 'mileage.class', 'csmileage sbccsreadonly');
    data_set($col3, 'chassisno.class', 'cschassisno sbccsreadonly');
    data_set($col3, 'engine.class', 'csengine sbccsreadonly');
    data_set($col3, 'mvno.class', 'csmvno sbccsreadonly');

    data_set($col3, 'submodel.required', false);
    data_set($col3, 'year.required', false);
    data_set($col3, 'type.required', false);


    $fields = ['kmno', 'rem', 'rem1', 'porem'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.type', 'input');
    data_set($col4, 'rem.label', 'Customer Notes');

    data_set($col4, 'kmno.required', false);



    data_set($col4, 'rem1.label', 'Complaints');
    data_set($col4, 'rem1.type', 'ctextarea');
    data_set($col4, 'rem1.readonly', false);
    data_set($col4, 'rem1.class', 'csrem1');
    data_set($col4, 'porem.label', 'Recommendations');
    data_set($col4, 'porem.readonly', false);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function defaultheaddata($params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = '';
    $data[0]['dateid'] = date('Y-m-d');
    $data[0]['due'] = date('Y-m-d');
    $data[0]['client'] = 'AM0000000000001';
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
    $data[0]['carid'] = 0;

    $data[0]['kmno'] = '';
    $data[0]['rem1'] = '';
    $data[0]['porem'] = '';
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
    $data[0]['agentname'] = '';
    $data[0]['tax'] = 0;
    $data[0]['vattype'] = 'NON-VATABLE';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['dwhname'] = '';
    $data[0]['carid'] = 0;

    $data[0]['kmno'] = '';
    $data[0]['rem1'] = '';
    $data[0]['porem'] = '';
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
        where doc=? and center=? and left(bref,3) <> 'SJS'
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
         client.groupid,
         ifnull(hinfo.kmno,'') as kmno,ifnull(hinfo.complaints,'') as rem1,
         ifnull(hinfo.recomm,'') as porem,
         ifnull(cvh.cmake,'') as vehicle,ifnull(model.model,'') as modelname,
         ifnull(cvh.licenseno,'') as licenseno,ifnull(cvh.motorno,'') as motorno,
         ifnull(model.sub_model,'') as submodel, ifnull(cvh.transmission,'') as transmission,
          model.cryear as year,ifnull(cvh.mileage,0) as mileage,ifnull(model.crtype,'') as type,
          ifnull(cvh.chassis,'') as chassisno,ifnull(cvh.carengine,'') as engine,
          ifnull(cvh.mvno,'') as mvno, cvh.line";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join coa on coa.acno=head.contra
        left join cvehicle as cvh on cvh.clientid=client.clientid and cvh.line = head.carid
        left join cmodel as model on model.line=cvh.cmodelline
        left join cntnuminfo as hinfo on hinfo.trno = head.trno
        where head.trno = ? and num.doc=? and num.center = ? and left(num.bref,3) <> 'SJS'
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acno=head.contra
        left join cvehicle as cvh on cvh.clientid=client.clientid
        left join cmodel as model on model.line=cvh.cmodelline
        left join hcntnuminfo as hinfo on hinfo.trno = head.trno
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
      $receivedby = $this->coreFunctions->datareader("select receivedby as value from cntnum  where trno=?", [$trno]);
      $lblreceived_stat = $receivedby == "" ? true : false;
      $hideobj = ['lblreceived' => $lblreceived_stat];
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
    $dateTables = ['lahead'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], 0, [], false, $dateTables);
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          // $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
          $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
        } //end if
      }
    }

    if ($data['terms'] == '') {
      $data['due'] = $data['dateid'];
    } else {
      $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      // $this->othersClass->getcreditinfo($config, $this->head);
      $info['trno'] = $head['trno'];
      $info['kmno'] = $head['kmno'];
      $info['complaints'] = $head['rem1'];
      $info['recomm'] = $head['porem'];
      $this->coreFunctions->sbcupdate('cntnuminfo', $info, ['trno' => $head['trno']]);
      $this->recomputestock($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      // $this->othersClass->getcreditinfo($config, $this->head);

      $info = [];
      $info['trno'] = $head['trno'];
      $info['kmno'] = $head['kmno'];
      $info['complaints'] = $head['rem1'];
      $info['recomm'] = $head['porem'];
      $this->coreFunctions->sbcinsert('cntnuminfo', $info);
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
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
    $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1']);
    if ($checkacct != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    }

    $stock = $this->partstock($trno, $config);
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
      }
    }


    if (!$this->createdistribution($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
    } else {
      $return = $this->othersClass->posttranstock($config);

      if ($return['status']) {
        //insert jobs
        $jobqry = "insert into " . $this->hamstock . "(trno,line,jobid,packagetrno,rem,encodeddate,encodedby,editdate,editby)
        SELECT job.trno,job.line,job.jobid,job.packagetrno,job.rem,job.encodeddate,job.encodedby,job.editdate,job.editby
        FROM " . $this->amstock . " as job left join cntnum on cntnum.trno=job.trno
        where job.trno=?";
        $postamjobs = $this->coreFunctions->execqry($jobqry, 'insert', [$trno]);
        if ($postamjobs) {
          //delete sa unposted amjobs
          $this->coreFunctions->execqry("delete from " . $this->amstock . " where trno=?", "delete", [$trno]);
          //insert tasks
          $taskqry = "insert into hamtask (trno,line,jobline,laborline,mecline,cost,rate,rem,encodeddate,encodedby,editdate,editby)
          SELECT task.trno,task.line,task.jobline,task.laborline,task.mecline,task.cost,task.rate,task.rem,task.encodeddate,task.encodedby,task.editdate,task.editby
          FROM amtask as task left join cntnum on cntnum.trno=task.trno
          where task.trno=?";
          $posttask = $this->coreFunctions->execqry($taskqry, 'insert', [$trno]);
          if (!$posttask) {
            if ($this->othersClass->unposttranstock($config)) { //insert pabalik sa lahead /lastock
              //delete sa mga nagposted
              $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
              $this->coreFunctions->execqry("delete from hamjobs where trno=?", "delete", [$trno]);
            }
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Tasks'];
          } else { //inserted
            //delete sa uposted amtask
            $this->coreFunctions->execqry("delete from amtask where trno=?", "delete", [$trno]);
          }
        } else {
          if ($this->othersClass->unposttranstock($config)) { //insert pabalik sa lahead /lastock
            //delete sa mga nagposted
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from arledger where trno=?", "delete", [$trno]);
          }
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Jobs'];
        }
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      }
      return $return;
    }
    // }
  } //end function


  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];

    $return = $this->othersClass->unposttranstock($config);

    if ($return['status']) {
      //insert jobs
      $jobqry = "insert into " . $this->amstock . "(trno,line,jobid,packagetrno,rem,encodeddate,encodedby,editdate,editby)
        SELECT job.trno,job.line,job.jobid,job.packagetrno,job.rem,job.encodeddate,job.encodedby,job.editdate,job.editby
        FROM " . $this->hamstock . " as job left join cntnum on cntnum.trno=job.trno
        where job.trno=?";
      $unpostamjobs = $this->coreFunctions->execqry($jobqry, 'insert', [$trno]);


      if ($unpostamjobs) {
        //delete sa posted hamjobs
        $this->coreFunctions->execqry("delete from " . $this->hamstock . " where trno=?", "delete", [$trno]);

        //insert tasks
        $taskqry = "insert into amtask (trno,line,jobline,laborline,mecline,cost,rate,rem,encodeddate,encodedby,editdate,editby)
          SELECT task.trno,task.line,task.jobline,task.laborline,task.mecline,task.cost,task.rate,task.rem,task.encodeddate,task.encodedby,task.editdate,task.editby
          FROM hamtask as task left join cntnum on cntnum.trno=task.trno
          where task.trno=?";
        $unposttask = $this->coreFunctions->execqry($taskqry, 'insert', [$trno]);
        if (!$unposttask) {
          if ($this->othersClass->posttranstock($config)) { //insert sa glhead pag failed
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);

            $jobqry = "insert into " . $this->hamstock . "(trno,line,jobid,packagetrno,rem,encodeddate,encodedby,editdate,editby)
            SELECT job.trno,job.line,job.jobid,job.packagetrno,job.rem,job.encodeddate,job.encodedby,job.editdate,job.editby
            FROM " . $this->amstock . " as job left join cntnum on cntnum.trno=job.trno
            where job.trno=?";
            $postamjobs = $this->coreFunctions->execqry($jobqry, 'insert', [$trno]); //post hamjob pabalik

            $this->coreFunctions->execqry("delete from amjobs where trno=?", "delete", [$trno]);
          }
          return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Tasks'];
        } else { //inserted sa task
          //delete sa posted hamtask
          $this->coreFunctions->execqry("delete from hamtask where trno=?", "delete", [$trno]);
        }
      } else {
        if ($this->othersClass->posttranstock($config)) { //insert sa glhead
          $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
          $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        }
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Jobs'];
      }
    }

    return $return;

    // return $this->othersClass->unposttranstock($config);


  } //end function



  public function partstock($trno, $config)
  {
    $qry = "select stock.line,stock.jobline,stock.taskline,stock.amt,item.barcode,item.itemname,item.uom,
        format(stock.isqty,2) as isqty,stock.iss,stock.disc,stock.itemid,stock.uom,
        stock.trno,stock.rem,format(stock.ext,2) as ext,format(stock.isamt,2) as isamt,wh.clientname as whname, stock.whid,
        FORMAT(uom.factor*stock.cost,6) as cost,ifnull(uom.factor,1) as uomfactor
        from lastock as stock
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid=stock.whid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 

        where stock.trno = ? 

        union all

        select stock.line,stock.jobline,stock.taskline,stock.amt,item.barcode,item.itemname,item.uom,
        format(stock.isqty,2) as isqty,stock.iss,stock.disc,stock.itemid,stock.uom,
        stock.trno,stock.rem,format(stock.ext,2) as ext,format(stock.isamt,2) as isamt,wh.clientname as whname, stock.whid,
        FORMAT(uom.factor*stock.cost,6) as cost,ifnull(uom.factor,1) as uomfactor
        from glstock as stock
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid=stock.whid
        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
        where stock.trno = ? 
        order by line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function




  private function getstockselect($config)
  {

    $sqlselect = "select  am.line,am.trno,am.jobid,pk.docno as packname,am.rem,
     job.docno as code,job.jobtitle as description,
     '' as bgcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);
    $qry = $sqlselect . "
    FROM $this->amstock as am
    left join $this->head as head on head.trno = am.trno
    left join jobthead as job on job.line=am.jobid
    left join hpthead as pk on pk.trno=am.packagetrno
    where am.trno =?
    group by am.line,am.trno,am.jobid,pk.docno,am.rem, job.docno,job.jobtitle
    UNION ALL
    " . $sqlselect . "
    FROM $this->hamstock as am
    left join $this->hhead as head on head.trno = am.trno
    left join jobthead as job  on job.line=am.jobid
    left join hpthead as pk on pk.trno=am.packagetrno
    where am.trno =? 
    group by am.line,am.trno,am.jobid,pk.docno,am.rem, job.docno,job.jobtitle
    order by line";
    // var_dump($qry);
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
    FROM $this->amstock as am
    left join $this->head as head on head.trno = am.trno
    left join jobthead as job on job.line=am.jobid
    left join hpthead as pk on pk.trno=am.packagetrno
    where am.trno = ? and am.line = ? 
    group by am.line,am.trno,am.jobid,pk.docno,am.rem, job.docno,job.jobtitle
    
    union all

    $sqlselect

     FROM $this->hamstock as am
    left join $this->hhead as head on head.trno = am.trno
    left join jobthead as job on job.line=am.jobid
    left join hpthead as pk on pk.trno=am.packagetrno
    where am.trno = ? and am.line = ? 
    group by am.line,am.trno,am.jobid,pk.docno,am.rem, job.docno,job.jobtitle";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        $return =  $this->additem('insert', $config);
        // if ($return['status'] == true) {
        //   // $this->othersClass->getcreditinfo($config, $this->head);
        // }
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
      case 'getautojob':
        return $this->getautojob($config);
        break;

      case 'getvehicle':
        return $this->getvehicle($config);
        break;

      case 'addpackage':
        return $this->addpackage($config);
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
      case 'makepayment':
        return $this->othersClass->generateShortcutTransaction($config, 0, 'SJCR');
        break;
      case 'donetodo':
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'downloadexcel':
        return $this->downloadexcel($config);
        break;
      // case 'posted':
      //   return $this->warehousedone($config);
      //   break;
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
    $data = $this->openstockline($config);
    $msg = '';
    if ($isupdate['msg'] != '') {
      $msg = $isupdate['msg'];
    }
    if (!$isupdate['status']) {
      $msg = 'Update failed.';

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
    // $this->othersClass->getcreditinfo($config, $this->head);
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


  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];

    $data = $this->coreFunctions->opentable('select refx,linex,porefx,polinex,drrefx,drlinex from ' . $this->stock . ' where trno=? and (refx<>0 or drrefx<>0)', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from amjobs where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from amtask where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function setserveditems($refx, $linex, $companyid = 0)
  {
    if ($refx == 0) {
      return 1;
    }


    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc in ('SJ') and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc in ('SJ') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

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

    $exist = !empty($this->coreFunctions->getfieldvalue("amtask", "trno",  "trno=? and  jobline=?",  [$trno, $line]));

    if ($exist) {
      return ['status' => false, 'msg' => 'Some tasks already exist in this job.'];
    } else {

      $qry = "delete from " . $this->amstock . " where trno=? and line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
      $this->logger->sbcwritelog($trno, $config, 'JOB', 'REMOVED - Line:' . $line
        . ' Description:' . ' ' . $data[0]->description);
      return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    }
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    $pricetype = $this->companysetup->getpricetype($config['params']);
    $pricedec = $this->companysetup->getdecimal('price', $config['params']);

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
          $qry = "select 'A' as g,'" . $pricefield['label'] . "' as docno, left(now(),10) as dateid," . $pricefield['amt'] . " as amt," . $pricefield['amt'] . " as defamt, " . $pricefield['disc'] . " as disc, uom, itemid,1 as factor from item where barcode=? 
            union all
            select 'Z' as g,docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,itemid,factor from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc,stock.itemid,uom.factor
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as computeramt,
            stock.uom,stock.disc,stock.itemid,uom.factor from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            order by dateid desc limit 5) as tbl order by g,dateid desc";
          $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $client, $trno, $center, $barcode, $client, $trno]);


          if (!empty($data)) {
            goto setpricehere;
          }
        } else {
          if ($pricetype == 'CustomerGroupLatest') {
            goto getCustomerLatestPriceHere;
          } else {
            goto itempricehere;
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
      $qry = "select 'STOCKCARD'  as docno,left(now(),10) as dateid,amt,amt as defamt,disc,uom,'test' as rem,1 as factor from item where barcode=? 
        union all
        select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,rem,factor from(select head.docno,head.dateid,
        stock.isamt as amt,stock.uom,stock.disc,'test' as rem,uom.factor
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
        where head.doc = 'SJ' and cntnum.center = ?
        and item.barcode = ? and head.client = ?
        and stock.isamt <> 0 and cntnum.trno <> ?
        UNION ALL
        select head.docno,head.dateid,stock.isamt as computeramt,
        stock.uom,stock.disc,'test' as rem,uom.factor from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join uom on uom.itemid = stock.itemid and uom.uom = stock.uom
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
          $deffactor = $this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
          $data[0]->uom = $defuom;
          $data[0]->factor = $deffactor;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = number_format($data[0]->amt * $deffactor, 2);
            } else {
              if ($companyid != 32) { //not 3m
                $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
              }
            }
          }
        } else {
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if (floatval($data[0]->amt) != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]));
            } else {
              if ($companyid != 32) { //not 3m
                $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = item.uom where item.barcode=?", [$barcode]);
              }
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

    $commexp = $this->coreFunctions->datareader("select commamt-commvat as value from cntnuminfo where trno=?", [$trno], '', true);

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

        if ($isvatexsales) {
          $this->distributionvatex($params, $config);
        } else {
          $this->distribution($params, $config);
        }
      }
    }

    //entry ar and vat if with default tax    
    $taxdef = $this->coreFunctions->getfieldvalue($this->head, "taxdef", "trno=?", [$trno]);

    $d = [];
    if ($taxdef != 0 || $delcharge != 0 || $commexp != 0) {
      $qry = "select client,forex,dateid,cur,branch,deptid,contra from " . $this->head . " where trno = ?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
    }

    if ($taxdef != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$d[0]->contra]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => (($totalar + $taxdef) * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $totalar + $taxdef, 'fcr' => 0];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ["TX2"]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'cr' => ($taxdef * $d[0]->forex), 'db' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $taxdef, 'fcr' => 0];

      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if ($delcharge != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['DC1']);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => 0, 'cr' => $delcharge * $d[0]->forex, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => ($delcharge * $d[0]->forex), 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid, 'fcr' => 0];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if ($commexp != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['EXCOM']);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => $commexp * $d[0]->forex, 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge, 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $d[0]->client, 'db' => ($commexp * $d[0]->forex) * -1, 'cr' => 0, 'postdate' => $d[0]->dateid, 'cur' => $d[0]->cur, 'forex' => $d[0]->forex, 'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid, 'fcr' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
    $dateTables = ['ladetail'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], 0, [], false, $dateTables);
    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) {
          // $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
          $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $value2, $lookups);
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


    //AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => (($params['ar'] - $ewtamt) * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : ($params['ar'] - $ewtamt), 'fcr' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      if ($params['discamt'] < 0) {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => 0, 'cr' => abs($params['discamt'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
      } else {
        $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
      }

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
    $companyid = $config['params']['companyid'];
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
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);


    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'reloadhead' => true];
  }


  public function recomputestock($head, $config)
  {
    $data = $this->partstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $deci = $this->companysetup->getdecimal('price', $config['params']);

    $dateTables = ['lastock'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], 0, [], false, $dateTables);
    foreach ($data2 as $key => $value) {
      // $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      // $dqty = $this->othersClass->sanitizekeyfield('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])));

      $damt = $this->othersClass->sanitizekeyfieldFast('amt', $data2[$key][$this->damt], $lookups);
      $dqty = $this->othersClass->sanitizekeyfieldFast('qty', round($data2[$key][$this->dqty], $this->companysetup->getdecimal('qty', $config['params'])), $lookups);

      $computedata = $this->othersClass->computestock(
        $damt * $head['forex'],
        $data[$key]->disc,
        $dqty,
        $data[$key]->uomfactor,
        0
      );

      $computedata['amt']  = number_format($computedata['amt'], $deci, '.', '');
      // $computedata['amt'] = $this->othersClass->sanitizekeyfield('amt', $computedata['amt']);
      $computedata['amt'] = $this->othersClass->sanitizekeyfieldFast('amt', $computedata['amt'], $lookups);

      $exec = $this->coreFunctions->execqry("update lastock set amt = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
    }
    return $exec;
  }


  public function additem($action, $config)
  {


    $trno = $config['params']['trno'];
    if (isset($config['params']['data']['jobid'])) {
      $jobid = $config['params']['data']['jobid'];
    }
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    $data = [
      'trno' => $trno,
      'jobid' => $jobid,
      'rem' => $rem
    ];
    $dateTables = ['amstock'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], 0, [], false, $dateTables);
    foreach ($data as $key => $value) {
      // $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
      $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
    }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->amstock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      $data['line'] = $line;
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->amstock, $data) == 1) {
        $query = "select line as jobid,docno as code,jobtitle as description from jobthead where line = ?";
        $job = $this->coreFunctions->opentable($query, [$jobid]);

        $this->logger->sbcwritelog($trno, $config, 'JOB', 'ADD JOB- Line:' . $line . ' Code:' . $job[0]->code . ' ' . ' Description: ' . $job[0]->description);
        $this->loadheaddata($config);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      // var_dump($data);
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];

      $data['editdate'] = $current_timestamp;
      $data['editby'] = $config['params']['user'];
      $return = true;
      $this->coreFunctions->sbcupdate($this->amstock, $data, ['trno' => $trno, 'line' => $line]);
      return $return;
    }
  }


  public function getautojob($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];

    foreach ($config['params']['rows'] as $key => $value) {

      $query = "select line as jobid,docno as code,jobtitle as description from jobthead where line = ?";
      $data = $this->coreFunctions->opentable($query, [$value['line']]);

      foreach ($data as $key2 => $value2) {
        $config['params']['data']['jobid'] = $value2->jobid;
        $config['params']['data']['rem'] = '';
        $config['params']['trno'] = $trno;
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          $line = $return['row'][0]->line;
          $config['params']['trno'] = $trno;
          $config['params']['line'] = $line;
          $row = $this->openstockline($config);
          $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
          array_push($rows, $return['row'][0]);
        }
      }
    } //end foreach
    $this->coreFunctions->LogConsole(json_encode($rows));
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  }

  public function getvehicle($config)
  {
    $trno = $config['params']['trno'];
    $vehicleline = $config['params']['rows'][0]['keyid'];
    $data['carid'] = $vehicleline;
    $updatehead = $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $trno]);
    $stat = true;
    $msg = "Header updated successfully.";
    $reload = true;

    if ($updatehead != 1) {
      $stat = false;
      $msg = "'Failed to update the header";
      $reload = false;
    }
    return ['status' => $stat, 'msg' => $msg, 'reloadhead' => $reload];
  }



  public function addpackage($config)
  {
    $trno = $config['params']['trno'];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $encodeddate = $current_timestamp;
    $encodedby = $config['params']['user'];
    $rows = [];

    foreach ($config['params']['rows'] as $key => $value) {
      $aktrno = $value['keyid'];

      $packageqry = "select docno from pthead where trno = ?";
      $packagedata = $this->coreFunctions->opentable($packageqry, [$aktrno]);
      $packagedocno = !empty($packagedata) ? $packagedata[0]->docno : '';

      $query = "select pt.trno, jb.jobid, jb.line as jobline, jb.rem as jobrem
                  from hpthead as pt
                  left join hptjobs as jb on jb.trno = pt.trno
                  where pt.trno = ?";

      $data = $this->coreFunctions->opentable($query, [$aktrno]);

      $amjob = $this->coreFunctions->getfieldvalue("amjobs", "ifnull(max(line),0)", "trno=?", [$trno], '', true);
      $jobln = $amjob + 1;

      foreach ($data as $key2 => $value2) {
        $oldJobLine = $value2->jobline;
        $newJobLine = $jobln;

        $job = [
          'jobid'       => $value2->jobid,
          'line'        => $newJobLine,
          'rem'         => $value2->jobrem,
          'encodeddate' => $encodeddate,
          'encodedby'   => $encodedby,
          'packagetrno' => $value2->trno,
          'trno'        => $trno
        ];

        $insertjob = $this->coreFunctions->sbcinsert('amjobs', $job);

        if (!$insertjob) {
          return ['row' => $rows, 'status' => false, 'msg' => 'Inserting jobs failed.'];
        }

        $query3 = "select pt.line as taskline, pt.jobline, pt.laborline, pt.mecline, pt.cost, pt.rate, pt.rem
                       from hpttask as pt
                       where pt.trno = ? and pt.jobline = ?";

        $data3 = $this->coreFunctions->opentable($query3, [$aktrno, $oldJobLine]);

        $amtask = $this->coreFunctions->getfieldvalue("amtask", "ifnull(max(line),0)", "trno=?", [$trno], '', true);
        $taskln = $amtask + 1;

        foreach ($data3 as $key3 => $value3) {
          $oldTaskLine = $value3->taskline;
          $newTaskLine = $taskln;

          $task = [
            'line'        => $newTaskLine,
            'jobline'     => $newJobLine,
            'laborline'   => $value3->laborline,
            'mecline'     => $value3->mecline,
            'cost'        => $value3->cost,
            'rate'        => $value3->rate,
            'rem'         => $value3->rem,
            'encodeddate' => $encodeddate,
            'encodedby'   => $encodedby,
            'trno'        => $trno
          ];


          $inserttask = $this->coreFunctions->sbcinsert('amtask', $task);

          if (!$inserttask) {
            $this->coreFunctions->execqry("delete from amjobs where trno=? and line =? ", "delete", [$trno, $newJobLine]);
            return ['row' => $rows, 'status' => false, 'msg' => 'Inserting labor/tasks failed.'];
          }

          $query4 = "select stock.taskline, stock.jobline, stock.itemid, stock.uom, stock.disc, stock.rem, stock.amt, stock.isqty, stock.isamt, stock.iss, stock.ext
                           from hptstock as stock
                           where stock.trno = ? and stock.jobline = ? and stock.taskline = ?";

          $data4 = $this->coreFunctions->opentable($query4, [$aktrno, $oldJobLine, $oldTaskLine]);


          foreach ($data4 as $key4 => $value4) {
            $config['params']['tableid'] = $trno;

            $wh = $this->companysetup->getwh($config['params']);
            $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

            $config['params']['row'] = [
              'trno'     => $trno,
              'line'     => 0,
              'taskline' => $newTaskLine,
              'jobline'  => $newJobLine,
              'itemid'   => $value4->itemid,
              'uom'      => $value4->uom,
              'isqty'    => $value4->isqty,
              'isamt'    => $value4->isamt,
              'disc'     => $value4->disc,
              'rem'      => $value4->rem,
              'ext'      => $value4->ext,
              'wh'       => $wh,
              'whid'     => $whid,
              'bgcolor'  => 'bg-blue-2'
            ];

            $urlHistory = 'App\Http\Classes\modules\autoserventry\\' . 'entryamparts';
            $return = app($urlHistory)->save($config);

            if (!$return['status']) {
              $this->coreFunctions->execqry("delete from amjobs where trno=? and line =? ", "delete", [$trno, $newJobLine]);
              $this->coreFunctions->execqry("delete from amtask where trno=? and line =? and jobline=?", "delete", [$trno, $newTaskLine, $newJobLine]);
              return ['row' => $rows, 'status' => false, 'msg' => 'Inserting parts/items failed.'];
            }
          }

          $taskln++;
        }

        $config['params']['line'] = $newJobLine;
        $row = $this->openstockline($config);

        if (!empty($row)) {
          $rowarray = json_decode(json_encode($row[0]), true);
          $rowarray['package'] = $packagedocno;
          $rows[] = $rowarray;
        }

        $jobln++;
      }

      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD PACKAGE - ' . $packagedocno . ' from trno: ' . $aktrno);
    }

    return ['status' => true, 'msg' => 'Package added successfully...', 'row' => $rows];
  }
} //end class