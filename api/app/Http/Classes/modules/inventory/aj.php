<?php

namespace App\Http\Classes\modules\inventory;

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

class aj
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'INVENTORY ADJUSTMENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => false];
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
  public $defaultContra = 'IS1';
  private $fields = [
    'trno',
    'docno',
    'dateid',
    'wh',
    'yourref',
    'ourref',
    'rem',
    'contra',
    'projectid',
    'branch',
    'deptid',
    'whref',
    'client',
    'clientname',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid'
  ];
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
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 291,
      'edit' => 292,
      'new' => 293,
      'save' => 294,
      // 'change' => 295, remove change doc
      'delete' => 296,
      'print' => 297,
      'lock' => 298,
      'unlock' => 299,
      'post' => 300,
      'unpost' => 301,
      'acctg' => 302,
      'additem' => 823,
      'edititem' => 824,
      'deleteitem' => 825,
      'changeamt' => 826,
      'viewcost' => 368,
      'viewamt' => 368
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;
    $rem = 7;
    $postdate = 8;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'rem', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    if ($companyid != 28) $cols[$rem]['type'] = 'coldel'; //not xcomp
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 12: //afti usd
      case 10: //afti
        $fields = ['selectprefix', 'docno'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'docno.type', 'input');
        data_set($col1, 'docno.label', 'Search');
        data_set($col1, 'selectprefix.label', 'Search by');
        data_set($col1, 'selectprefix.type', 'lookup');
        data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
        data_set($col1, 'selectprefix.action', 'lookupsearchby');

        $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");

        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
        break;

      default:
        return [];
        break;
    }
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');

    $companyid = $config['params']['companyid'];
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = '';
    $addparams = '';
    $join = '';
    $hjoin = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if ($test['selectprefix'] != "") {
        switch ($test['selectprefix']) {
          case 'Item Code':
            $addparams = " and (item.partno like '%" . $test['docno'] . "%')";
            break;
          case 'Item Name':
            $addparams = " and (item.itemname like '%" . $test['docno'] . "%' )";
            break;
          case 'Model':
            $addparams = " and (model.model_name like '%" . $test['docno'] . "%' )";
            break;
          case 'Brand':
            $addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%' )";
            break;
          case 'Item Group':
            $addparams = " and (p.name like '%" . $test['docno'] . "%')";
            break;
        }

        if (isset($test)) {
          $join = " left join " . $this->stock . " as stock on head.trno = stock.trno 
          left join item on item.itemid = stock.itemid 
          left join model_masterfile as model on model.model_id = item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join projectmasterfile as p on p.line = item.projectid ";

          $hjoin = " left join " . $this->hstock . " as stock on head.trno = stock.trno 
          left join item on item.itemid = stock.itemid 
          left join model_masterfile as model on model.model_id = item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join projectmasterfile as p on p.line = item.projectid ";
          $limit = '';
        }
      }
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid,head.dateid as date2";
        if ($searchfilter == "") $limit = 'limit 25';
        $orderby = "order by date2 desc, docno desc";
        break;
      case 19: //housegem
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby = "order by docno desc, dateid desc";
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";
        break;
    }


    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'wh.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      if ($companyid == 28) array_push($searchfield, 'head.rem'); //xcomp
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "select head.trno,head.docno,wh.clientname,$dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref, head.rem
     from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join client as wh on wh.client=head.wh 
     " . $join . " where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "
     " . $addparams . " " . $filtersearch . "
     union all
     select head.trno,head.docno,wh.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref, head.rem
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno left join client as wh on wh.clientid=head.whid " . $hjoin . " where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "
     " . $addparams . " " . $filtersearch . "
     $orderby $limit";

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
    $step1 = $this->helpClass->getFields(['btnnew', 'cswhname', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'cswhname', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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

    $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
    $buttons['others']['items']['downloadexcel'] = ['label' => 'Download AJ Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'aj', 'title' => 'AJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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


    if ($config['params']['companyid'] == 60) { //transpower      
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5495);
      if ($changecode) {
        $changecode = ['customform' => ['action' => 'customform', 'lookupclass' => 'changebarcode']];
        $return['CHANGE CODE'] = ['icon' => 'fa fa-qrcode', 'customform' => $changecode];
      }
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $invonly = $this->companysetup->isinvonly($config['params']);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $headgridbtns = [];

    $columns =  [
      'action',
      'itemdescription',
      'serialno',
      'pnp',
      'rrqty',
      'reasonname',
      'uom',
      'kgs',
      'rrcost',
      'cost',
      'ext',
      'wh',
      'whname',
      'ref',
      'loc',
      'expiry',
      'rem',
      'pallet',
      'location',
      'itemname',
      'stock_projectname',
      'subcode',
      'partno',
      'boxcount',
      'barcode'
    ];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    switch ($systemtype) {
      case 'REALESTATE':
        $project = 24;
        $phasename = 25;
        $housemodel = 26;
        $blk = 27;
        $lot = 28;
        $amenityname = 29;
        $subamenityname = 30;
        array_push($column, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        array_push($sortcolumn, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }

    $periodic = $this->companysetup->getisperiodic($config['params']);

    if ($invonly) {
      $headgridbtns = [];
    } else {
      if (!$periodic) {
        $headgridbtns = ['viewdistribution'];
      }
    }

    if ($companyid == 10) { //afti
      array_push($headgridbtns, 'viewitemstockinfo');
    }

    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

    if ($iskgs) {
      $computefield['kgs'] = 'kgs';
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'sortcolumns' => $columns,
        'computefield' =>  $computefield,
        'headgridbtns' => $headgridbtns
      ],
    ];

    if ($this->companysetup->getserial($config['params'])) {
      $stockbuttons = ['save', 'delete', 'serialin', 'serialout'];
    } else {
      $stockbuttons = ['save', 'delete', 'showbalance', 'stockinfo'];
    }

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'AIMS':
        switch ($companyid) {
          case 10: //afti
            array_push($stockbuttons, 'iteminfo');
            break;
          case 59: //ROOSEVELT
            $stockbuttons = ['delete'];
            break;
          default: //main
            array_push($stockbuttons, 'stockinfo');
            break;
          
        }
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
    }

    switch ($companyid) {
      case 41:
      case 52: //technolab
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serialno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        if (!$isexpiry) {
          $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
          $obj[0][$this->gridname]['columns'][$pallet]['action'] = 'lookuppalletbalance';
        } else {
          $obj[0]['inventory']['columns'][$location]['readonly'] = false;
          $obj[0]['inventory']['columns'][$location]['type'] = 'editlookup';
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
        }
        break;
      case 10: //afti
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0]['inventory']['descriptionrow'] = [];
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$itemdescription]['readonly'] = true;
        $obj[0]['inventory']['columns'][$itemdescription]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
        $obj[0]['inventory']['columns'][$serialno]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$serialno]['readonly'] = true;
        $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
        $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
        $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
        if (!$isexpiry) {
          $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
          $obj[0][$this->gridname]['columns'][$pallet]['action'] = 'lookuppalletbalance';
        } else {
          $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
          $obj[0]['inventory']['columns'][$loc]['type'] = 'editlookup';
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
        }
        break;
      case 40:
        $obj[0]['inventory']['columns'][$serialno]['type'] = 'textarea2';
        $obj[0]['inventory']['columns'][$serialno]['readonly'] = true;
        $obj[0]['inventory']['columns'][$serialno]['label'] = 'Engine/Chassis#';
        $obj[0]['inventory']['columns'][$serialno]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$pnpcsr]['readonly'] = true;
        $obj[0]['inventory']['columns'][$pnpcsr]['label'] = 'PNP/CSR#';
        $obj[0]['inventory']['columns'][$pnpcsr]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        if (!$isexpiry) {
          $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
          $obj[0][$this->gridname]['columns'][$pallet]['action'] = 'lookuppalletbalance';
        } else {
          $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
          $obj[0]['inventory']['columns'][$loc]['type'] = 'editlookup';
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
        }
        break;
      case 50: //unitech
        $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serialno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$pnp]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
        $obj[0]['inventory']['columns'][$loc]['type'] = 'editlookup';
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
        break;
      default:
        $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$serialno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$pnp]['type'] = 'coldel';
        if ($companyid != 6) { //not mitsukoshi
          $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        } else {
          $obj[0]['inventory']['columns'][$partno]['label'] = 'Old SKU';
          $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
          $obj[0]['inventory']['columns'][$partno]['align'] = 'left';
          $obj[0]['inventory']['columns'][$partno]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

          $obj[0]['inventory']['columns'][$subcode]['label'] = 'Part No.';
          $obj[0]['inventory']['columns'][$subcode]['type'] = 'label';
          $obj[0]['inventory']['columns'][$subcode]['align'] = 'left';
          $obj[0]['inventory']['columns'][$subcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

          $obj[0]['inventory']['columns'][$boxcount]['label'] = 'Box Pack';
          $obj[0]['inventory']['columns'][$boxcount]['type'] = 'label';
          $obj[0]['inventory']['columns'][$boxcount]['align'] = 'left';
          $obj[0]['inventory']['columns'][$boxcount]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';
        }
        $obj[0]['inventory']['columns'][$itemdescription]['type'] = 'coldel';

        if (!$isexpiry) {
          if ($companyid == 8) { // maxipro
            $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
            $obj[0]['inventory']['columns'][$loc]['type'] = 'lookup';
            $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$pallet]['action'] = 'lookuppalletbalance';
          } else {
            $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
            $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
            $obj[0][$this->gridname]['columns'][$pallet]['action'] = 'lookuppalletbalance';
          }
        } else {
          $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
          $obj[0]['inventory']['columns'][$loc]['type'] = 'editlookup';
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
        }
        break;
    }

    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Kgs';
    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$pallet]['type'] = 'coldel';
    if (!$ispallet) {
      $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$pallet]['type'] = 'coldel';
    }

    if ($this->companysetup->getserial($config['params'])) {
      $obj[0]['inventory']['columns'][0]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    }

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
    }

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'AIMS':
        if ($companyid == 0 || $companyid == 10) { //main & afti
          $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        }
        break;

      case 'REALESTATE':
        $obj[0][$this->gridname]['columns'][$blk]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$lot]['readonly'] = true;
        break;
    }

    if ($companyid == 21) { //kinggeorge
      $obj[0]['inventory']['columns'][$rrcost]['label'] = 'Landed Cost';
      $obj[0]['inventory']['columns'][$wh]['type'] = 'label';
    }

    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila & technolab
      $obj[0]['inventory']['columns'][$loc]['label'] = 'Lot/Serial#';
      $obj[0]['inventory']['columns'][$expiry]['label'] = 'Expiry/Mfr Date';
    }

    if ($companyid == 42) { //pdpi
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$barcode]['label'] = '';
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';

    if ($companyid == 47) { //kitchenstar
      if ($viewcost == '0') {
        $obj[0]['inventory']['showtotal'] = false;
        $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
      }
    }

    if ($companyid != 56) {
      $obj[0]['inventory']['columns'][$reasonname]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 11) { //summit
      $tbuttons = ['eggitems', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    } else {
      $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
    }

    if ($this->companysetup->getispallet($config['params'])) {
      array_push($tbuttons, 'pendingsplitqtypicker');
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $invonly = $this->companysetup->isinvonly($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    if ($companyid == 10) { //afti
      $fields = ['docno', 'dwhname', 'dbranchname'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'docno.label', 'Transaction#');
      data_set($col1, 'dbranchname.required', true);

      $fields = ['dateid', 'isacnoname', 'ddeptname'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'isacnoname.label', 'IS Account');
      data_set($col2, 'ddeptname.label', 'Department');
    } else {
      $fields = ['docno', 'dwhname', 'dprojectname'];
      if ($systemtype == 'REALESTATE') {
        array_push($fields,  'phase');
      }

      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'docno.label', 'Transaction#');

      if ($companyid == 42) { //pdpi
        data_set($col1, 'dprojectname.type', 'coldel');
      }

      if ($systemtype == 'REALESTATE') {
        data_set($col1, 'phase.addedparams', ['projectid']);
      }


      if ($invonly) {
        $fields = ['dateid'];
      } else {
        $fields = ['dateid', 'isacnoname'];
      }


      if ($companyid == 11) { //summit
        array_push($fields, 'dwhref');
      }

      if ($companyid == 28) { //XCOMP
        array_push($fields, 'client', 'clientname');
      }

      if ($systemtype == 'REALESTATE') {
        array_push($fields, 'housemodel', ['blklot', 'lot']);
      }

      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'isacnoname.label', 'IS Account');
      data_set($col2, 'client.lookupclass', 'customer');
      data_set($col2, 'clientname.class', 'sbccsreadonly');

      if ($systemtype == 'REALESTATE') {
        data_set($col2, 'housemodel.addedparams', ['projectid']);
        data_set($col2, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
      }
    }


    $fields = [['yourref', 'ourref']];
    if ($systemtype == 'REALESTATE') {
      $fields = ['amenityname', 'subamenityname', 'rem'];
    }

    $col3 = $this->fieldClass->create($fields);

    if ($systemtype == 'REALESTATE') {
      data_set($col3, 'subamenityname.addedparams', ['amenityid']);
    }

    $fields = ['rem'];
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    $col4 = $this->fieldClass->create($fields);

    if ($companyid == 21) { //kinggeorge
      data_set($col1, 'dprojectname.type', 'coldel');
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['projectcode'] = '';
    $data[0]['projectid'] = 0;
    $data[0]['projectname'] = '';
    $data[0]['branch'] = '0';
    $data[0]['branchname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['dbranchname'] = '';
    $data[0]['deptid'] = 0;
    $data[0]['ddeptname'] = '';
    $data[0]['deptname'] = '';
    $data[0]['dept'] = '';
    $data[0]['dwhref'] = '';
    $data[0]['whref'] = '';
    $data[0]['whnameref'] = '';
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['forex'] = 1;

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
    return $data;
  }

  public function loadheaddata($config)
  {
    ini_set('memory_limit', '-1');

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
         head.clientname,
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
         warehouse.client as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         head.projectid,
         ifnull(project.name,'') as projectname,ifnull(project.code,'') as projectcode,
         '' as dprojectname,
         left(head.due,10) as due,
         client.groupid,head.branch,ifnull(b.clientname,'') as branchname,
         ifnull(b.client,'') as branchcode,'' as dbranchname,ifnull(d.client,'') as dept,
         ifnull(d.clientname,'') as deptname,head.deptid,'' as ddeptname,'' as dwhref, whref.clientname as whnameref, head.whref,
          head.phaseid, ps.code as phase,  head.modelid, hm.model as housemodel, head.blklotid, 
           bl.blk as blklot,  bl.lot, amen.line as amenityid, amen.description as amenityname, 
           subamen.line as subamenityid, subamen.description as subamenityname ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as whref on whref.client = head.whref
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 

         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid

        where head.trno = ? and num.doc=? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join client as whref on whref.client = head.whref
        left join client as b on b.clientid = head.branch
        left join client as d on d.clientid = head.deptid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 

         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid
        
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
      $this->recomputecost($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['wh']);
    }
  } // end function

  public function deletetrans($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("update hpchead set yourref ='' where yourref = ?", "update", [$docno]);

    if ($this->companysetup->getsystemtype($config['params']) == 'ATI') {
      $this->coreFunctions->execqry('update hrrfams set ajtrno=0, ajline=0 where ajtrno=?', 'delete', [$trno]);
    }

    $stockinfo_table = "stockinfotrans";
    if ($companyid == 10) { //afti
      $stockinfo_table = "stockinfo";
    }
    $this->coreFunctions->execqry('delete from ' . $stockinfo_table . ' where trno=?', 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $periodic = $this->companysetup->getisperiodic($config['params']);

    $serial = $this->companysetup->getserial($config['params']);


    if ($serial) {
      if (!$this->othersClass->checkserialin($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
      }
    }

    if ($periodic) {
      return $this->othersClass->posttranstock($config);
    }

    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      $checkacct = $this->othersClass->checkcoaacct(['IN1', 'IS1']);

      if ($checkacct != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
      }

      $stock = $this->openstock($trno, $config);
      $checkcosting = $this->othersClass->checkcosting($stock);
      if ($checkcosting != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
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
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];

    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    $serialfield = '';

    if ($companyid == 10 || $companyid == 12) { //afti
      $qty_dec = 0;
      $serialfield = ",case stock.iss when 0 then ifnull(group_concat(rr.serial separator '\\r\\n'),'') else ifnull(group_concat(rr2.serial separator '\\r\\n'),'') end as serialno";
    }

    if ($companyid == 40) { //cdo
      $serialfield = ",case stock.iss when 0 then ifnull(group_concat(concat('Engine/Chassis#: ',rr.serial,'/',rr.chassis,'\\n','Color: ',rr.color) separator '\\n\\r'),'') else ifnull(group_concat(concat('Engine/Chassis#: ',rr2.serial,'/',rr2.chassis,'\\n','Color: ',rr2.color) separator '\\n\\r'),'') end as serialno ";
    }

    $addedfields = '';
    if ($companyid == 56) { //homeworks
      $addedfields = 'stock.reasonid, rc.code as reasoncode, rc.description as reasonname,';
    }

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
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
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as qty,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as " . $this->dqty . ",
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    stock.void,
    stock.iss,
    round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    " . $addedfields . "
    stock.loc,
    stock.expiry,
    item.brand,
    stock.palletid,
    stock.locid,
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    stock.rem,stock.loc as eloc,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    '' as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid, 

     stock.phaseid, ps.code as phasename,  stock.modelid, hm.model as housemodel,stock.blklotid, bl.blk, bl.lot,
     prj.code as project,
     amen.line as amenity, amen.description as amenityname,  subamen.line as subamenity, subamen.description as subamenityname,

    item.subcode, item.partno, 
    round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount ,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription $serialfield
    ,case stock.iss when 0 then ifnull(group_concat(concat('PNP#: ',rr.pnp,' / CSR#: ',rr.csr,'\\n','Remarks: ',rr.remarks) separator '\\n\\r'),'') else
    ifnull(group_concat(concat('PNP#: ',rr2.pnp,' / CSR#: ',rr2.csr) separator '\\n\\r'),'') end as pnp";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti
      $qty_dec = 0;
    }

    $sqlselect = $this->getstockselect($config);

    $leftjoin = '';
    $rcgrpby = '';
    if ($companyid == 56) {
      $leftjoin = 'left join reqcategory as rc on rc.line = stock.reasonid';
      $rcgrpby = 'stock.reasonid, rc.code, rc.description,';
    }

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    left join serialout as rr2 on rr2.trno = stock.trno and rr2.line = stock.line
    left join iteminfo as i on i.itemid  = item.itemid

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line

    " . $leftjoin . "

    where stock.trno =? group by item.brand,mm.model_name,item.itemid,stock.trno,
    stock.line,stock.sortline,stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") ,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,stock.encodeddate,
    stock.disc,stock.void,stock.iss,stock.qa,stock.ref,stock.whid,warehouse.client,warehouse.clientname, " . $rcgrpby . " stock.loc,
    stock.expiry,stock.palletid,stock.locid,pallet.name,location.loc,stock.rem,stock.loc,uom.factor,
    prj.name,stock.projectid, item.subcode, item.partno, item.dqty,brand.brand_desc,i.itemdescription,

    stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description ,  subamen.line , subamen.description, sit.itemdesc

    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    left join serialout as rr2 on rr2.trno = stock.trno and rr2.line = stock.line
    left join iteminfo as i on i.itemid  = item.itemid

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

    left join hstockinfo as sit on sit.trno = stock.trno and sit.line=stock.line

    " . $leftjoin . "

    where stock.trno =? group by item.brand,mm.model_name,item.itemid,stock.trno,
    stock.line,stock.sortline,stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") ,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,stock.encodeddate,
    stock.disc,stock.void,stock.iss,stock.qa,stock.ref,stock.whid,warehouse.client,warehouse.clientname, " . $rcgrpby . " stock.loc,
    stock.expiry,stock.palletid,stock.locid,pallet.name,location.loc,stock.rem,stock.loc,uom.factor,
    prj.name,stock.projectid, item.subcode, item.partno, item.dqty,brand.brand_desc,i.itemdescription,
    
    stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description , subamen.line , subamen.description, sit.itemdesc
    
    order by sortline,line";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    if ($companyid == 10 || $companyid == 12) { //afti
      $qty_dec = 0;
    }

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];

    $leftjoin = '';
    $rcgrpby = '';
    if ($companyid == 56) {
      $leftjoin = 'left join reqcategory as rc on rc.line = stock.reasonid';
      $rcgrpby = 'stock.reasonid, rc.code, rc.description,';
    }

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join serialin as rr on rr.trno = stock.trno and rr.line = stock.line
    left join serialout as rr2 on rr2.trno = stock.trno and rr2.line = stock.line
    left join iteminfo as i on i.itemid  = item.itemid

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

    left join stockinfo as sit on sit.trno = stock.trno and sit.line=stock.line

    " . $leftjoin . "

    where stock.trno = ? and stock.line = ? group by item.brand,mm.model_name,item.itemid,stock.trno,
    stock.line,stock.sortline,stock.refx,stock.linex,item.barcode,item.itemname,stock.uom,stock.kgs,
    stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") ,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  ,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,stock.encodeddate,
    stock.disc,stock.void,stock.iss,stock.qa,stock.ref,stock.whid,warehouse.client,warehouse.clientname, " . $rcgrpby . " stock.loc,
    stock.expiry,stock.palletid,stock.locid,pallet.name,location.loc,stock.rem,stock.loc,uom.factor,
    prj.name,stock.projectid, item.subcode, item.partno, item.dqty,brand.brand_desc,i.itemdescription,

    stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
    prj.code ,amen.line , amen.description ,  subamen.line , subamen.description , sit.itemdesc,
    stock.sortline";
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
      case 'geteggitems':
        return $this->geteggitems($config);
        break;
      case 'getpendingpickeradj':
        return $this->getpendingpickeradj($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'uploadexcel':
        return $this->othersClass->uploadexcel($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'updatecost': //kinggeorge - used for not tally cost
        return $this->updatecost($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function updatecost($config)
  {
    ini_set('max_execution_time', 0);
    $trno = $config['params']['trno'];

    $data = $this->coreFunctions->opentable("select docno, doc, trno, line, iss, cost, actualcost,actualcost * iss
      from (
      select h.docno, h.doc, s.trno, s.line, s.iss, s.cost, 
      (  select round(ifnull(sum(rs.cost * c.served) / stock.iss,0), 6) from glstock as stock left join costing as c on c.trno=stock.trno and c.line=stock.line
          left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex 
          where stock.trno=s.trno and stock.line=s.line and stock.trno=" . $trno . " 
          group by stock.trno,stock.line,stock.iss) as actualcost
      from glstock as s left join glhead as h on h.trno=s.trno
      where s.iss<>0 and s.trno=" . $trno . "
      ) as x where round(cost,4)<>round(actualcost,4)");

    if (!empty($data)) {
      foreach ($data as $key => $value) {
        $this->coreFunctions->execqry("update glstock set cost=" . $value->actualcost . " where trno=" . $value->trno . " and line=" . $value->line);
        $this->coreFunctions->execqry("update glstock set ext=round(cost*iss,2)*-1 where trno=" . $value->trno . " and line=" . $value->line);
      }
      $this->coreFunctions->execqry("update glhead set isreentryinv=1 where trno=" . $trno);
      $this->coreFunctions->execqry("update cntnum set isok=2 where trno=" . $trno);
    }

    return ['status' => true, 'msg' => 'Finished'];
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
        $msg1 = ' Out of stock ';
      }

      if ($config['params']['companyid'] == 56) {
        if ($data2[$key]['reasonid'] == 0) {
          $data[$key]->errcolor = 'bg-red-2';
          $isupdate = false;
          $msg2 = ' / Reason cannot be blank';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . $msg2];
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
        $msg1 = ' Out of stock ';
      }

      if ($config['params']['companyid'] == 56) {
        if ($data2[$key]['reasonid'] == 0) {
          $data[$key]->errcolor = 'bg-red-2';
          $isupdate = false;
          $msg2 = ' / (Some reasons are blank)';
        }
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ') ' . $msg2];
    }
  } //end function


  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $msg = 'Successfully saved.';
      $config['params']['data'] = $value;
      $return = $this->additem('insert', $config);
      if ($return['status'] == false) {
        $msg = $return['msg'];
        break;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $msg2 = '';


    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $status = false;
        $msg = 'Please check; some items are out of stock.';
      }
      if ($config['params']['companyid'] == 56) {
        if ($data2[$key]['reasonid'] == 0) {
          $data[$key]->errcolor = 'bg-red-2';
          $status = false;
          $msg2 = ' / Some reasons are blank.';
        }
      }
    }
    return ['inventory' => $data, 'status' => true, 'msg' => $msg . $msg2];
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
    $ispallet = $this->companysetup->getispallet($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['data']['wh'];

    $disc = '';
    $expiry = '';
    $loc = '';
    $rem = '';

    $refx = 0;
    $linex = 0;
    $ref = '';
    $palletid = 0;
    $locid = 0;
    $projectid = 0;
    $reasonid = 0;

    if (isset($config['params']['data']['disc'])) {
      $disc = $config['params']['data']['disc'];
    }
    if (isset($config['params']['data']['loc'])) {
      $loc = $config['params']['data']['loc'];
    }
    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }
    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }
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
    if (isset($config['params']['data']['reasonid'])) {
      $reasonid = $config['params']['data']['reasonid'];
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
      $rrqty = $config['params']['data']['qty'];
      if ($rrqty >= 0) {
        $iss = 0;
      } else {
        $iss = $config['params']['data']['qty'];
      }

      if ($companyid == 10) { //afti
        if ($projectid == 0) {
          $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
        }
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $rrqty = $config['params']['data'][$this->dqty];
      if ($rrqty >= 0) {
        $iss = 0;
      } else {
        $iss = $config['params']['data'][$this->dqty];
      }
      $config['params']['line'] = $line;
      if ($companyid == 10) {
        $projectid = $config['params']['data']['projectid'];
      }
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $rrqty = $this->othersClass->sanitizekeyfield('qty', $rrqty);
    $iss = $this->othersClass->sanitizekeyfield('iss', $iss);
    $kgs = $this->othersClass->sanitizekeyfield('qty', $kgs);

    if ($systemtype == 'REALESTATE') {
      $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
      $phaseid = $this->coreFunctions->getfieldvalue($this->head, "phaseid", "trno=?", [$trno]);
      $modelid = $this->coreFunctions->getfieldvalue($this->head, "modelid", "trno=?", [$trno]);
      $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      $amenityid = $this->coreFunctions->getfieldvalue($this->head, "amenityid", "trno=?", [$trno]);
      $subamenityid = $this->coreFunctions->getfieldvalue($this->head, "subamenityid", "trno=?", [$trno]);
    }

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      $isnoninv =  $item[0]->isnoninv;
    }
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $rrqty = $this->othersClass->sanitizekeyfield('rrqty', $rrqty);
    $iss = $this->othersClass->sanitizekeyfield('iss', $iss);
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $rrqty = round($rrqty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $rrqty, $factor, $vat, 'P', $kgs);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $cost = number_format($computedata['amt'], $this->companysetup->getdecimal('price', $config['params']), '.', '');

    if ($companyid == 23) { //labsol cebu
      if ($rrqty > 0) {
        if ($forex <= 1) {
          $cost = number_format($computedata['amt']  * 1.15, $this->companysetup->getdecimal('price', $config['params']), '.', '');
        } else {
          $cost = number_format($computedata['amt'] * 1.3, $this->companysetup->getdecimal('price', $config['params']), '.', '');
        }
      }
    }

    if ($companyid == 41 || $companyid == 52) { //labsol manila & technolab
      if ($rrqty > 0) {
        $cost = number_format($computedata['amt']  * 1.05, $this->companysetup->getdecimal('price', $config['params']), '.', '');
      }
    }

    if ($companyid == 56) { //homeworks
      if ($reasonid != 0) {
        $reasonid = $this->coreFunctions->getfieldvalue('reqcategory', 'line', 'line=?', [$reasonid]);
      } else {
        $rrqty = 0;
      }
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $cost,
      $this->dqty => $rrqty,
      $this->hqty => $rrqty > 0 ? $computedata['qty'] : 0,
      'iss' => $rrqty < 0 ? $computedata['qty'] : 0,
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'palletid' => $palletid,
      'locid' => $locid,
      'rem' => $rem
    ];

    if ($systemtype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }

    if ($companyid == 10) { //afti
      $data['projectid'] = $projectid;
    }

    if ($companyid == 56) { //homeworks
      $data['reasonid'] = $reasonid;
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0 || $companyid == 10) { //main & afti
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
            }
            break;
        }

        if ($companyid == 16) { //ati
          $stockinfo_data = ['trno' => $trno, 'line' => $line];
          $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $havestock = true;
        if ($data['iss'] > 0 && $isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'rrcost' => round($cost * $factor, $this->companysetup->getdecimal('price', $config['params'])), 'ext' => round($cost * $factor, $this->companysetup->getdecimal('price', $config['params'])) * $rrqty], ['trno' => $trno, 'line' => $line]);
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'iss' => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          }
        }

        $row = $this->openstockline($config);
        $msg = 'Item was successfully added.';
        if (!$havestock) {
          $row[0]->errcolor = 'bg-red-2';
          $msg = 'Out of Stock.';
        }
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($data['iss'] > 0 && $isnoninv == 0) {
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'rrcost' => round($cost * $factor, $this->companysetup->getdecimal('price', $config['params'])), 'ext' => round($cost * $factor, $this->companysetup->getdecimal('qty', $config['params'])) * $rrqty], ['trno' => $trno, 'line' => $line]);
        } else {
          $havestock = false;
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'iss' => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
        }
      } else {
        if ($this->setserveditems($refx, $linex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
        }
        $this->coreFunctions->execqry("delete from costing where trno= ? and line= ?", "delete", [$trno, $line]);
      }
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
      foreach ($data as $key => $value) {
        $this->othersClass->deleteserialout($data[$key]->trno, $data[$key]->line);
      }
      $this->coreFunctions->execqry('delete from serialin where trno=?', 'delete', [$trno]);
    }

    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    $this->setservedsplitqtyitems(0, 0, $trno, true);

    if ($this->companysetup->getsystemtype($config['params']) == 'ATI') {
      $this->coreFunctions->execqry('update hrrfams set ajtrno=0, ajline=0 where ajtrno=?', 'delete', [$trno]);
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='RR' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='RR' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hpostock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function deleteitem($config)
  {
    $companyid = $config['params']['companyid'];
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);

    if ($this->companysetup->getserial($config['params'])) {
      $this->othersClass->deleteserialout($data[0]->trno, $data[0]->line);
      $this->coreFunctions->execqry("delete from serialin where trno= ? and line= ? ", 'delete', [$data[0]->trno, $data[0]->line]);
    }

    if ($this->companysetup->getispallet($config['params'])) {
      $this->setservedsplitqtyitems($data[0]->refx, $data[0]->linex, 0, false);
    }

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry("delete from costing where trno= ? and line= ? ", 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $defuominout = $this->companysetup->getisdefaultuominout($config['params']);
    $data = [];
    $companyid =$config['params']['companyid'];

    $filter = '';
    switch ($config['params']['companyid']) {
      case 21: //kinggeorge
        $wh = $this->coreFunctions->getfieldvalue("lahead", "wh", "trno=?", [$trno]);
        $filter = " and wh.client='" . $wh . "'";
        break;
    }

    if ($defuominout) {
      $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
          stock.cost as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ?
          and stock.rrcost <> 0 and cntnum.trno <>? " . $filter . "
          UNION ALL
          select head.docno,head.dateid,stock.rrcost as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ?
          and stock." . $this->damt . " <> 0 and cntnum.trno <>? " . $filter . "
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
      $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
    } else {
      if($companyid == 60){//transpower
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(
        select '' as docno,now() as dateid,item.namt4 as amt,item.uom,'' as disc from item
        where item.barcode =? 
        union all 
        select head.docno,head.dateid,
          (stock.rrcost*if(head.forex=0,1,head.forex)) as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
          and item.barcode = ?
          and stock.rrcost <> 0 and cntnum.trno <>?
          UNION ALL
          select head.docno,head.dateid,(stock.rrcost*if(head.forex=0,1,head.forex)) as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
          and item.barcode = ? 
          and stock." . $this->damt . " <> 0 and cntnum.trno <>?
          order by dateid desc limit 5) as tbl ";
          $data = $this->coreFunctions->opentable($qry, [$barcode,$center, $barcode, $trno, $center, $barcode, $trno]);
      }else{
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
        (stock.rrcost*if(head.forex=0,1,head.forex)) as amt,stock.uom,stock.disc
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid = stock.itemid
        left join client as wh on wh.clientid=stock.whid
        where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
        and item.barcode = ?
        and stock.rrcost <> 0 and cntnum.trno <>?
        UNION ALL
        select head.docno,head.dateid,(stock.rrcost*if(head.forex=0,1,head.forex)) as amt,
        stock.uom,stock.disc from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client on client.clientid = head.clientid
        left join cntnum on cntnum.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
        and item.barcode = ? 
        and stock." . $this->damt . " <> 0 and cntnum.trno <>?
        order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
      }
      
    }


    if (empty($data)) { // if walang data from filter ng rrcost latest transaction, requery sa cost na field 
      $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
          stock.cost as amt,item.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and cntnum.trno <>? and stock.cost<>0 " . $filter . "
          UNION ALL
          select head.docno,head.dateid,stock.cost as amt,
          item.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and  cntnum.trno <>?  and stock.cost<>0 " . $filter . "
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
      $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
    }

    if (!empty($data)) {
      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $data[0]->docno = 'UOM';
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault = 1 where item.barcode=?", [$barcode]);
        $this->coreFunctions->LogConsole('Def' . $defuom);
        if ($defuom != "") {
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if ($data[0]->amt != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            }
          }
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $data[0]->docno = 'UOM';
          if($companyid != 50){
            $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
          }
        }
      }
    }else{
      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $this->coreFunctions->LogConsole("test 1");
        $data[0]['docno'] = 'UOM';
        
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault = 1 where item.barcode=?", [$barcode]);
        $this->coreFunctions->LogConsole('Def('. $barcode.')' . $defuom);
        if ($defuom != "") {
          $data[0]['uom'] = $defuom;
          $data[0]['amt'] =0;
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
           $data[0]['docno'] = 'UOM';
           if($companyid != 50){
            $data[0]['amt'] = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
           }
        }
      }
    }
    

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

  public function geteggitemsqry($config, $itemid)
  {
    return "select i.itemid,i.barcode,i.itemname,i.uom,i.disc
            from item as i
            left join itemcategory as cat on i.category= cat.line
            where cat.name = 'Egg' and i.itemid = " . $itemid . " ";
  }

  public function geteggitems($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $itemid = $config['params']['rows'][$key]['itemid'];
      $qry = $this->geteggitemsqry($config, $itemid);
      $data = $this->coreFunctions->opentable($qry);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = '';
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['ref'] = '';
          $config['params']['data']['amt'] = '';
          $config['params']['data']['stageid'] = '';

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function getposummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hpohead as head left join hpostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.qty>stock.qa and stock.void=0
    ";
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
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function

  public function getpodetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno,
        stock.line, item.barcode,stock.uom, stock.cost,
        (stock.qty-stock.qa) as qty,stock.rrcost,
        round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        stock.disc
        FROM hpohead as head left join hpostock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
    ";
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
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->rrcost;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Items Successful...'];
  } //end function

  public function getpendingpickeradj($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $data['data'] = [];
    array_push($config['params'], $data);
    foreach ($config['params']['rows'] as $key2 => $value) {

      $config['params']['data']['uom'] = $value['uom'];
      $config['params']['data']['itemid'] = $value['itemid'];
      $config['params']['trno'] = $trno;
      $config['params']['data']['disc'] = '';
      $config['params']['data']['qty'] = abs($value['isqty']) * -1;
      $config['params']['data']['wh'] = $wh;
      $config['params']['data']['whid'] = $value['whid'];
      $config['params']['data']['locid'] = $value['locid'];
      $config['params']['data']['palletid'] = $value['palletid'];
      $config['params']['data']['loc'] = '';
      $config['params']['data']['expiry'] = '';
      $config['params']['data']['rem'] = '';
      $config['params']['data']['refx'] = $value['trno'];
      $config['params']['data']['linex'] = $value['line'];

      $latestcost = $this->othersClass->getlatestcostTS($config, $value['barcode'], '', $config['params']['center'], $trno);
      if ($latestcost['status']) {
        $amt = $latestcost['data'][0]->amt;
      } else {
        $amt = 0;
      }

      $config['params']['data']['amt'] = $amt;
      $return = $this->additem('insert', $config);
      if ($return['status']) {
        if ($this->setservedsplitqtyitems($value['trno'], $value['line'], $trno, false) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $line = $return['row'][0]->line;
          $config['params']['trno'] = $trno;
          $config['params']['line'] = $line;
          $row = $this->openstockline($config);
          $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
        }
        array_push($rows, $return['row'][0]);
      }
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function setservedsplitqtyitems($refx, $linex, $trno, $delete)
  {
    if ($delete) {
      return $this->coreFunctions->execqry("update splitstock set qatrno=0 where qatrno=" . $trno, 'update');
    } else {
      return $this->coreFunctions->execqry("update splitstock set qatrno=" . $trno . " where trno=" . $refx . " and line=" . $linex, 'update');
    }
  }

  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    switch ($companyid) {
      case 10: //afti
        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.disc,stock.rrqty,stock.projectid,head.branch,head.deptid
          from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join item on item.itemid=stock.itemid left join projectmasterfile as p on p.line = stock.projectid 
          left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid where head.trno=?';
        break;
      case 11: //summit
        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.disc,stock.rrqty,head.projectid,head.whref
        from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid where head.trno=?';
        break;
      default:
        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,round(((stock.qty-stock.iss) * stock.cost),2) as ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.disc,stock.rrqty,head.projectid
        from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid where head.trno=?';
        break;
    }

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $vat = $stock[0]->tax;
      $tax1 = 0;
      $tax2 = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));
        if ($vat != 0) {
          $tax = round(($stock[$key]->ext / $tax1) * $tax2, 2);
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->rrqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'projectid' => $stock[$key]->projectid
        ];

        if ($companyid == 10) { //afti
          $params['branch'] = $stock[$key]->branch;
          $params['deptid'] = $stock[$key]->deptid;
        }

        if ($companyid == 11) { //summit
          $params['whref'] = $stock[$key]->whref;
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
        if ($this->acctg[$key]['cr'] < 0) {
          $this->acctg[$key]['db'] =  ($this->acctg[$key]['cr']) * -1;
          $this->acctg[$key]['cr'] = 0;
        }

        if ($this->acctg[$key]['db'] < 0) {
          $this->acctg[$key]['cr'] =  ($this->acctg[$key]['db']) * -1;
          $this->acctg[$key]['db'] = 0;
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
    //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
    $entry = [];
    $forex = $params['forex'];
    $companyid = $config['params']['companyid'];
    if ($forex == 0) {
      $forex = 1;
    }
    $cur = $params['cur'];
    $invamt = ($params['ext'] - $params['tax']);
    //AP
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => ($params['ext'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $params['ext'], 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //INV
    if (floatval($invamt) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
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

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 40) { // cdo
      $dataparams = $config['params']['dataparams'];
      if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
      if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
      if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
    }

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function recomputecost($head, $config)
  {
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    foreach ($data2 as $key => $value) {
      $this->othersClass->logConsole(json_encode($value));

      $damt = $this->othersClass->sanitizekeyfield('amt', $data2[$key][$this->damt]);
      $dqty =  round($this->othersClass->sanitizekeyfield('qty', $data2[$key][$this->dqty]), $this->companysetup->getdecimal('qty', $config['params']));

      $computedata = $this->othersClass->computestock($damt, $data[$key]->disc, $dqty, $data[$key]->uomfactor);

      if ($config['params']['companyid'] == 23) { //labsol cebu
        if ($head['forex'] == 1 || $head['forex'] == 0) {
          $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.15 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        } else {
          $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.30 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        }
      } elseif ($config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) { //labsol manila & technolab
        $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.05 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      } else {
        $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      }
    }
    return $exec;
  }
} //end class
