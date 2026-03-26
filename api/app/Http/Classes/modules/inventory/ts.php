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

class ts
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TRANSFER SLIP';
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
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'IS1';

  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'wh', 'projectid', 'sdate1', 'sdate2', 'deptid', 'istrip', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'];
  private $except = ['trno', 'dateid'];
  private $otherfields = [];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;
  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
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
      'view' => 309,
      'edit' => 310,
      'new' => 311,
      'save' => 312,
      'delete' => 314,
      'print' => 315,
      'lock' => 316,
      'unlock' => 317,
      'changeamt' => 834,
      'post' => 318,
      'unpost' => 319,
      'additem' => 831,
      'edititem' => 832,
      'deleteitem' => 833,
      'tripapproved' => 4496,
      'tripdisapproved' => 4739,
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
    $postdate = 7;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listsource', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    if ($companyid == 43) { //mighty
      $this->showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
      ];
    }

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$listsource]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$listsource]['label'] = 'Source Warehouse';
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
      case 43: //mighty
        $fields = ['picktr'];
        $col1 = $this->fieldClass->create($fields);
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
        break;

      default:
        return [];
        break;
    }
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
    $addparams = '';
    $join = '';
    $hjoin = '';
    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null and head.lockdate is null';
        $status = '"DRAFT"';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        $status = '"POSTED"';
        break;
      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        $status = '"LOCKED"';
        break;
      case 'all':
        $status = " (case when num.postdate is not null then 'POSTED' 
                    when head.lockdate is not null and num.postdate is null then 'LOCKED'
                    else 'DRAFT' end) ";
        break;
    }

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if (isset($test['selectprefix']) != "") {
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

    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
        if ($searchfilter == "") $limit = 'limit 25';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      case 19: //housegem
        $orderby = "order by docno desc, dateid desc";
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";
        break;
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.clientname', 'wh.clientname'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }
    $qry = "select head.trno,head.docno,head.clientname,$dateid,  $status as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref ,wh.clientname as source
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno " . $join . "
     left join client as wh on wh.client = head.wh 
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "
    " . $addparams . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,$dateid, $status as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref  ,wh.clientname as source
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno " . $hjoin . " 
     left join client as wh on wh.clientid = head.whid 
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "
    " . $addparams . " " . $filtersearch . "
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
    $step1 = $this->helpClass->getFields(['btnnew', 'destination', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'destination', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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

    if ($config['params']['companyid'] == 47 || $config['params']['companyid'] == 56) { //kstar | homeworks
      $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
      $buttons['others']['items']['downloadexcel'] = ['label' => 'Download TS Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
    }


    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'ts', 'title' => 'TS_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5496);
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
    $isserial = $this->companysetup->getserial($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

    $trip_tab = $this->othersClass->checkAccess($config['params']['user'], 4492);
    $arrived_tab = $this->othersClass->checkAccess($config['params']['user'], 4493);
    $trip_approve = $this->othersClass->checkAccess($config['params']['user'], 4496);
    $trip_disapprove = $this->othersClass->checkAccess($config['params']['user'], 4739);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451); //kinggeorge

    $action = 0;
    $itemdesc = 1;
    $isqty = 2;
    $uom = 3;
    $serial = 4;
    $pnpcsr = 5;
    $rrcost = 6;
    $kgs = 7;
    $isamt = 8;
    $disc = 9;
    $ext = 10;
    $ext2 = 11;
    $wh = 12;
    $whname = 13;
    $ref = 14;
    $loc = 15;
    $expiry = 16;
    $loc2 = 17;
    $rem = 18;
    $location = 19;
    $pallet = 20;
    $location2 = 21;
    $pallet2 = 22;
    $itemname = 23;
    $stock_projectname = 24;
    $subcode = 25;
    $partno = 26;
    $boxcount = 27;
    $barcode = 28;

    $column =  ['action', 'itemdescription', 'isqty', 'uom', 'serialno', 'pnp', 'rrcost', 'kgs', 'isamt', 'disc', 'ext', 'ext2', 'wh', 'whname', 'ref', 'loc', 'expiry', 'loc2', 'rem', 'location', 'pallet', 'location2', 'pallet2', 'itemname', 'stock_projectname', 'subcode', 'partno', 'boxcount', 'barcode'];
    $sortcolumn =  ['action', 'itemdescription', 'isqty', 'uom', 'serialno', 'pnp', 'rrcost', 'kgs', 'isamt', 'disc', 'ext', 'ext2', 'wh', 'whname', 'ref', 'loc', 'expiry', 'loc2', 'rem', 'location', 'pallet', 'location2', 'pallet2', 'itemname', 'stock_projectname', 'subcode', 'partno', 'boxcount', 'barcode'];

    switch ($systemtype) {
      case 'REALESTATE':
        $project = 29;
        $phasename = 30;
        $housemodel = 31;
        $blk = 32;
        $lot = 33;
        $amenityname = 34;
        $subamenityname = 35;
        array_push($column, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        array_push($sortcolumn, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }

    $headgridbtns = [];

    switch ($companyid) {
      case 10: //afti
        array_push($headgridbtns, 'viewitemstockinfo');
        break;
      case 43: //mighty
        if ($trip_approve) array_push($headgridbtns, 'tripapproved');
        if ($trip_disapprove) array_push($headgridbtns, 'tripdisapproved');
        break;
    }

    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ],
    ];

    if ($this->companysetup->getserial($config['params'])) {
      $stockbuttons = ['save', 'delete', 'serialout'];
    } else {
      
      switch ($companyid) {
        case 21: //kinggeorge
          if ($allowviewbalance != 0) {
            $stockbuttons = ['save', 'delete', 'showbalance'];
          } else {
            $stockbuttons = ['save', 'delete'];
          }
          break;
        case 59: //ROOSEVELT
          $stockbuttons = ['delete'];
          break;
        default:
          $stockbuttons = ['save', 'delete', 'showbalance'];
          break;
      }
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
            break;
          default: //main
            array_push($stockbuttons, 'stockinfo');
            break;
          
        }
        break;
    }

    switch ($companyid) {
      case 43: //mighty
        if ($trip_tab) $tab['customform'] = ['event' => ['action' => 'customform', 'lookupclass' => 'tripdetails', 'access' => 'trip'], 'label' => 'TRIP'];
        if ($arrived_tab) $tab['customform2'] = ['event' => ['action' => 'customform', 'lookupclass' => 'tripdetails2', 'access' => 'dispatched'], 'label' => 'DISPATCHED'];
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($companyid == 21) { //kinggeorge
      $obj[0][$this->gridname]['totalfield'] = 'ext2';
    }

    if ($companyid == 42) { // PDPI
      $obj[0][$this->gridname]['columns'][$isamt]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$disc]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$ext]['type'] = 'coldel';
    }

    if (!$isexpiry) {
      if ($companyid == 8) { // maxipro
        $obj[0]['inventory']['columns'][$loc]['label'] = 'Brand';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$loc2]['label'] = 'Brand to';
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
      } else {
        $obj[0][$this->gridname]['columns'][$loc]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$loc2]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$pallet]['action'] = 'lookuppalletbalance';
      }
    } else {
      $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
      $obj[0]['inventory']['columns'][$loc]['type'] = 'editlookup';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
      $obj[0]['inventory']['columns'][$expiry]['style'] = 'text-align: left; width: 180px;whiteSpace: normal;min-width:180px;max-width:180px;';
    }

    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Kgs';
    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'][$pallet]['type'] = 'coldel';
    $obj[0][$this->gridname]['columns'][$pallet2]['type'] = 'coldel';
    if (!$ispallet) {
      $obj[0][$this->gridname]['columns'][$location]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$location2]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';


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

    switch ($companyid) {
      case 0: //main
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext2]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'coldel';
        break;
      case 10: //afti
      case 12: //afti usd
        $obj[0]['inventory']['descriptionrow'] = [];
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
        $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
        $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
        $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext2]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'coldel';
        break;
      case 23: //labsol cebu
      case 41: //labsol manila
      case 52: //technolab
        $obj[0]['inventory']['columns'][$loc]['label'] = 'Lot/Serial#';
        $obj[0]['inventory']['columns'][$expiry]['label'] = 'Expiry/Mfr Date';
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext2]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'coldel';
        break;
      case 21: //kinggeorge
        $obj[0][$this->gridname]['columns'][$isamt]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$disc]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rrcost]['label'] = 'Cost';
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'coldel';
        break;
      case 32: //3m
        $obj[0]['inventory']['columns'][$isamt]['label'] = 'SRP';
        $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
        $obj[0]['inventory']['columns'][$rrcost]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext2]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        break;
      case 40: //cdo
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext2]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Cost';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';

        $obj[0]['inventory']['columns'][$serial]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$serial]['readonly'] = true;
        $obj[0]['inventory']['columns'][$serial]['label'] = 'Engine/Chassis#';
        $obj[0]['inventory']['columns'][$serial]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'textarea';
        $obj[0]['inventory']['columns'][$pnpcsr]['readonly'] = true;
        $obj[0]['inventory']['columns'][$pnpcsr]['label'] = 'PNP/CSR#';
        $obj[0]['inventory']['columns'][$pnpcsr]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:250px;max-width:2350px;';

        $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
        break;
      default:
        $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$ext2]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$disc]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$isamt]['label'] = 'Unit Cost';
        if ($companyid != 6) { //not mitsukoshi
          $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
          $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
        }
        $obj[0]['inventory']['columns'][$pnpcsr]['type'] = 'coldel';

        break;
    }

    if (!$isserial) {
      $obj[0]['inventory']['columns'][$serial]['type'] = 'coldel';
    }

    if ($viewcost == '0') {
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$isamt]['type'] = 'coldel';
    }

    if ($systemtype == 'REALESTATE') {
      $obj[0][$this->gridname]['columns'][$blk]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$lot]['readonly'] = true;
    }

    if ($companyid == 47) { //kitchenstar
      if ($viewcost == '0') {
        $obj[0]['inventory']['showtotal'] = false;
      }
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $allowso = $this->othersClass->checkAccess($config['params']['user'], 5257);
    switch ($config['params']['companyid']) {
      case 16: //ati
        $tbuttons = ['prtagged', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;
      case 27: //NTE
      case 36: //ROZLAB
      case 43: //mighty
        $tbuttons = ['pendingtr', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;
      default:
        $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
        break;
    }
    if ($this->companysetup->getisrefillts($config['params'])) {
      array_push($tbuttons, 'refillitem');
    }

    if ($this->companysetup->getisshowtsso($config['params'])) {
      if ($allowso == '1') {
        array_push($tbuttons, 'pendingso');
      }
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    if ($this->companysetup->getisshowtsso($config['params'])) {
      $obj[4]['lookupclass'] = "pendingtsso";
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      $obj[2]['label'] = "SAVE ALL";
      $obj[3]['label'] = "DELETE ALL";
    }

    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    switch ($companyid) {
      case 8: //maxipro
        $fields = ['docno', 'dwhname', 'dprojectname'];
        break;
      default:
        $fields = ['docno', 'client', 'clientname'];
        break;
    }
    if ($companyid == 43) { //mighty
      array_push($fields, 'ddeptname');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.label', 'Destination Code');
    data_set($col1, 'client.lookupclass', 'whtslip');
    data_set($col1, 'clientname.label', 'Destination Name');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'client.condition', ['checkstock']);

    if ($companyid == 43) { //MIGHTY
      data_set($col1, 'ddeptname.label', 'Dept Code');
    }

    if ($companyid == 8) { //maxipro
      data_set($col1, 'dprojectname.required', true);
      data_set($col1, 'dwhname.label', 'Source Warehouse');
    }
    //col2

    switch ($companyid) {
      case 8: //maxipro
        $fields = ['dateid', 'client', 'clientname'];
        break;
      case 42: //pdpi
        $fields = ['dateid', 'dwhname'];
        break;
      default:
        $fields = ['dateid', 'dwhname', 'dprojectname'];
        break;
    }


    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'dwhname.label', 'Source Warehouse');

    if ($companyid == 8) { //maxipro
      data_set($col2, 'client.label', 'Destination Code');
      data_set($col2, 'client.lookupclass', 'whtslip');
      data_set($col2, 'clientname.label', 'Destination Name');
      data_set($col2, 'docno.label', 'Transaction#');
    }

    data_set($col2, 'dwhname.condition', ['checkstock']);

    //col 3
    $fields = [['yourref', 'ourref'], 'rem'];

    $col3 = $this->fieldClass->create($fields);

    switch ($companyid) {
      case 32: //3m
        $fields = ['sdate1', 'sdate2'];
        break;
      case 43: //mighty
        $fields = ['istrip', 'lblapproved'];
        break;
      default:
        $fields = [];
        if ($systemtype == 'REALESTATE') {
          $fields = ['phase', 'housemodel', ['blklot', 'lot'], 'amenityname', 'subamenityname'];
        }
        break;
    }

    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    $col4 = $this->fieldClass->create($fields);

    if ($companyid == 21) { //kinggeorge
      data_set($col2, 'dprojectname.type', 'coldel');
    }

    if ($companyid == 43) { //mighty
      data_set($col4, 'lblapproved.type', 'label');
      data_set($col4, 'lblapproved.label', 'APPROVED!');
      data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');
    }
    if ($systemtype == 'REALESTATE') {
      data_set($col4, 'phase.addedparams', ['projectid']);
      data_set($col4, 'housemodel.addedparams', ['projectid']);
      data_set($col4, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
      data_set($col4, 'subamenityname.addedparams', ['amenityid']);
    }

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
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    $data[0]['whname'] = $name;
    $data[0]['projectid'] = 0;
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['sdate1'] = $this->othersClass->getCurrentDate();
    $data[0]['sdate2'] = $this->othersClass->getCurrentDate();

    // 43-mighty
    $data[0]['ddeptname'] = '';
    $data[0]['deptid'] = '0';
    $data[0]['dept'] = '';
    $data[0]['istrip'] = '0';

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
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $isapproved = $this->othersClass->isapproved($config['params']['trno'], "hcntnuminfo");
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
         head.tax,
         head.vattype,
         '' as dvattype,
         warehouse.client as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         client.groupid,
         head.projectid,ifnull(project.code,'') as projectcode,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,head.sdate1,head.sdate2,
         ifnull(d.client,'') as dept,ifnull(d.clientname,'') as deptname,
         head.deptid,
         cast(ifnull(head.istrip,0) as char) as istrip, head.phaseid, ps.code as phase,  head.modelid, hm.model as housemodel, head.blklotid, 
           bl.blk as blklot,  bl.lot, amen.line as amenityid, amen.description as amenityname, 
           subamen.line as subamenityid, subamen.description as subamenityname";
    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join headinfotrans as info on info.trno=head.trno
        left join client as wh2 on wh2.clientid= info.wh2 

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
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join client as d on d.clientid = head.deptid
        left join hheadinfotrans as info on info.trno=head.trno
        left join client as wh2 on wh2.clientid = info.wh2 

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
      $receivedby = $this->coreFunctions->datareader("select receivedby as value from cntnum  where trno=?", [$trno]);

      $lblreceived_stat = $receivedby == "" ? true : false;
      $hideobj = ['lblreceived' => $lblreceived_stat];

      $hideheadergridbtns = [];
      if ($companyid == 43) { //mighty
        $hideobj = ['lblapproved' => !$isapproved];
        $hideheadergridbtns = ['tripapproved' => $isapproved, 'tripdisapproved' => !$isapproved];
      }
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

    $dataother = [];
    foreach ($this->otherfields as $key) {
      $dataother[$key] = $head[$key];
      $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key]);
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
    if ($config['params']['companyid'] == 43) { // mighty 
      $info = [];
      $info['trno'] = $head['trno'];
      $this->coreFunctions->sbcinsert("cntnuminfo", $info);
      $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
      if ($infotransexist == '') {
        $this->coreFunctions->sbcinsert("headinfotrans", $dataother);
      } else {
        $dataother['editby'] = $config['params']['user'];
        $dataother['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->sbcupdate("headinfotrans", $dataother, ['trno' => $head['trno']]);
      }
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
    $this->coreFunctions->execqry("delete from replenishstock where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];

    if (!$this->othersClass->postingheadinfotrans($config)) { //mighty
      return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
    }
    if (!$this->othersClass->postcntnuminfo($config, true)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting cntnuminfo'];
    }
    $stock = $this->openstock($trno, $config);
    $checkcosting = $this->othersClass->checkcosting($stock);
    if ($checkcosting != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
    }
    $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
    $this->coreFunctions->execqry("delete from cntnuminfo where trno=?", "delete", [$trno]);
    return $this->othersClass->posttranstock($config);
  } //end function

  public function unposttrans($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    if (!$this->othersClass->unpostingheadinfotrans($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while unposting head data.'];
    }
    if (!$this->othersClass->postcntnuminfo($config, false)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting cntnuminfo'];
    }
    $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
    $this->coreFunctions->execqry("delete from hcntnuminfo where trno=?", "delete", [$trno]);
    return $this->othersClass->unposttranstock($config);
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $qty_dec = 0;
    }

    $sqlselect = "select item.brand as brand, ifnull(mm.model_name,'') as model,item.itemid,stock.trno,
                      stock.line,stock.sortline,stock.refx,stock.linex,stock.reqtrno,stock.reqline,
                      item.barcode,item.itemname,stock.uom,stock.kgs, stock." . $this->hamt . ",
                      stock." . $this->hqty . " as qty,stock." . $this->hqty . " as iss,
                      FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
                      FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as " . $this->dqty . ",
                      FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
                      FORMAT(stock.cost," . $this->companysetup->getdecimal('currency', $config['params']) . ") as cost,
                      FORMAT(stock.cost * uom.factor," . $this->companysetup->getdecimal('currency', $config['params']) . ") as rrcost,
                      FORMAT((stock.cost * stock." . $this->hqty . ")," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext2,
                      left(stock.encodeddate,10) as encodeddate,stock.disc,stock.void,
                      round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 
                      then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                      stock.ref,stock.whid,warehouse.client as wh, warehouse.clientname as whname,
                      stock.loc,stock.loc2,stock.expiry,item.brand,stock.rem,
                      stock.palletid,stock.locid,ifnull(pallet.name,'') as pallet,
                      ifnull(location.loc,'') as location,stock.palletid2,stock.locid2,
                      ifnull(pallet2.name,'') as pallet2,ifnull(location2.loc,'') as location2,
                      ifnull(uom.factor,1) as uomfactor,'' as bgcolor,'' as errcolor,
                      prj.name as stock_projectname,stock.projectid as projectid,item.subcode, item.partno, 
                      round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,
                      concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',
                      ifnull(i.itemdescription,'')) as itemdescription,
                      ifnull(group_concat(concat('Engine/Chassis#: ',rr.serial,'/',rr.chassis,'\\n','Color: ',rr.color) separator '\\n\\r'),'') as serialno,
                      ifnull(group_concat(concat('PNP#: ',rr.pnp,' / CSR#: ',rr.csr) separator '\\n\\r'),'') as pnp,
                      stock.phaseid, ps.code as phasename,  stock.modelid, hm.model as housemodel,
                      stock.blklotid, bl.blk, bl.lot,prj.code as project,amen.line as amenity, 
                      amen.description as amenityname,  subamen.line as subamenity, subamen.description as subamenityname,
                      stock.sorefx,stock.solinex
                      ";
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
    left join pallet as pallet2 on pallet2.line=stock.palletid2
    left join location as location2 on location2.line=stock.locid2
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid


    where stock.tstrno=0 and stock.trno =?
    group by item.brand,mm.model_name,item.itemid, stock.trno,stock.line,
    stock.sortline,stock.refx,stock.linex,stock.reqtrno,stock.reqline,
    item.barcode,item.itemname,stock.uom,stock.kgs, stock." . $this->hamt . ", stock." . $this->hqty . ",
    stock." . $this->hqty . ",stock." . $this->damt . ",
    stock." . $this->dqty . ", stock.ext,stock.cost,
    uom.factor,stock.encodeddate,stock.disc,stock.void,stock.qa,
    stock.ref,stock.whid, warehouse.client,warehouse.clientname,
    stock.loc,stock.loc2,stock.expiry,item.brand,stock.rem,
    stock.palletid,stock.locid,pallet.name,location.loc,
    stock.palletid2,stock.locid2,pallet2.name,location2.loc,uom.factor,
    prj.name,stock.projectid,item.subcode, item.partno,item.dqty,i.itemdescription,brand.brand_desc,

     stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description ,  subamen.line , subamen.description ,stock.sorefx,stock.solinex

    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join pallet on pallet.line=stock.palletid
    left join location on location.line=stock.locid
    left join pallet as pallet2 on pallet2.line=stock.palletid2
    left join location as location2 on location2.line=stock.locid2
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

    where stock.tstrno=0 and stock.trno =? group by item.brand,mm.model_name,item.itemid, stock.trno,stock.line,
    stock.sortline,stock.refx,stock.linex,stock.reqtrno,stock.reqline,
    item.barcode,item.itemname,stock.uom,stock.kgs, stock." . $this->hamt . ", stock." . $this->hqty . ",
    stock." . $this->hqty . ",stock." . $this->damt . ",
    stock." . $this->dqty . ", stock.ext,stock.cost,
    uom.factor,stock.encodeddate,stock.disc,stock.void,stock.qa,
    stock.ref,stock.whid, warehouse.client,warehouse.clientname,
    stock.loc,stock.loc2,stock.expiry,item.brand,stock.rem,
    stock.palletid,stock.locid,pallet.name,location.loc,
    stock.palletid2,stock.locid2,pallet2.name,location2.loc,uom.factor,
    prj.name,stock.projectid,item.subcode, item.partno,item.dqty,i.itemdescription,brand.brand_desc,

     stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description ,  subamen.line , subamen.description ,stock.sorefx,stock.solinex

    order by sortline,line";

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
    left join pallet as pallet2 on pallet2.line=stock.palletid2
    left join location as location2 on location2.line=stock.locid2
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

    where stock.trno = ? and stock.line = ? 
    group by item.brand,mm.model_name,item.itemid, stock.trno,stock.line,
    stock.sortline,stock.refx,stock.linex,stock.reqtrno,stock.reqline,
    item.barcode,item.itemname,stock.uom,stock.kgs, stock." . $this->hamt . ", stock." . $this->hqty . ",
    stock." . $this->hqty . ",stock." . $this->damt . ",
    stock." . $this->dqty . ", stock.ext,stock.cost,
    uom.factor,stock.encodeddate,stock.disc,stock.void,stock.qa,
    stock.ref,stock.whid, warehouse.client,warehouse.clientname,
    stock.loc,stock.loc2,stock.expiry,item.brand,stock.rem,
    stock.palletid,stock.locid,pallet.name,location.loc,
    stock.palletid2,stock.locid2,pallet2.name,location2.loc,uom.factor,
    prj.name,stock.projectid,item.subcode, item.partno,item.dqty,i.itemdescription,brand.brand_desc,

     stock.phaseid, ps.code ,  stock.modelid, hm.model,stock.blklotid, bl.blk, bl.lot,
     prj.code ,amen.line , amen.description ,  subamen.line , subamen.description ,stock.sorefx,stock.solinex
    ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'getrqtype':
        return $this->getrqtype($config);
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
      case 'getsplitqtydetails':
        return $this->getsplitqtydetails($config);
        break;
      case 'gettrsummary':
        return $this->gettrsummary($config);
        break;
      case 'gettrdetails':
        return $this->gettrdetails($config);
        break;
      case 'refillitem':
        return $this->refillitem($config);
        break;
      case 'getsodetails':
        return $this->getsodetails($config);
        break;
      case 'getsosummary':
        return $this->getsosummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


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
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
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
            if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
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


  public function getsosummary($config)
  {
    $companyid = $config['params']['companyid'];
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
            'rem' => $data[0]->rem,
            'wh' => $data[0]->wh,
            'projectid' => $data[0]->hprojectid
          ];

          $updatehead = $this->coreFunctions->sbcupdate($this->head, $headupdate, ["trno" => $trno]);
        }

        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $data[$key2]->swh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['sorefx'] = $data[$key2]->trno;
          $config['params']['data']['solinex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $config['params']['data']['projectid'] = $data[$key2]->projectid;
          $config['params']['data']['kgs'] = $data[$key2]->kgs;
          $config['params']['data']['weight'] = $data[$key2]->weight;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setservedsoitems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservedsoitems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
  } //end function


  public function getposummaryqry($config)
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
        info.driverid,info.helperid,info.checkerid,info.plateno,info.truckid,sinfo.itemdesc,head.sano,head.pono,head.wh,head.salestype,head.due
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

  public function gettrsummary($config)
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
        stock.disc,head.rem
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {

        if ($config['params']['companyid'] == 43) { //mighty
          $this->coreFunctions->sbcupdate($this->head, [
            'editdate' => $this->othersClass->getCurrentTimeStamp(),
            'editby' => $config['params']['user'],
            'ourref' => $data[0]->docno,
            'rem' => $data[0]->rem
          ], ['trno' => $trno]);
        }

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
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function gettrdetails($config)
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
        stock.disc,head.rem
        FROM htrhead as head left join htrstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {

        if ($config['params']['companyid'] == 43) { //mighty
          $this->coreFunctions->sbcupdate($this->head, [
            'editdate' => $this->othersClass->getCurrentTimeStamp(),
            'editby' => $config['params']['user'],
            'ourref' => $data[0]->docno,
            'rem' => $data[0]->rem
          ], ['trno' => $trno]);
        }

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
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function


  public function getrqtype($config)
  {
    $trno = $config['params']['trno'];
    $wh = $this->coreFunctions->getfieldvalue("lahead", "wh", "trno=?", [$trno]);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = " select head.docno,head.trno,item.itemid,item.uom,item.barcode,
        item.itemname,hps.line,hps.rrqty as rrqty
        from hprhead as head
        left join hprstock as hps on hps.trno=head.trno
        left join item on item.itemid = hps.itemid
        left join uom on uom.itemid=item.itemid and uom.uom=hps.uom
        where item.itemid = " . $value['itemid'] . " 
        and hps.trno=" . $value['trno'] . "
        and hps.line=" . $value['line'] . "";

      $data = $this->coreFunctions->opentable($qry);
      $insert_success = true;
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {

          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['data']['trno '] = $trno;
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['disc'] = '';
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['amt'] = 0;
          $config['params']['data']['reqtrno'] = $data[$key2]->trno;
          $config['params']['data']['reqline'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setservepritems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setservepritems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          } else {
            $insert_success = false;
          }
        }
      }
    }

    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  }



  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    if ($action == 'stockstatusposted') {
      $action = $config['params']['lookupclass'];
    }
    switch ($action) {

      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'updatecost':
        return $this->updatezerrocost($config);
        break;
      case 'tripapproved':
        return $this->tripapproved($config);
        break;
      case 'tripdisapproved':
        return $this->tripdisapproved($config);
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
  public function updatezerrocost($config)
  {
    ini_set('max_execution_time', 0);

    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable("select h.docno, s.trno, s.cost, rs.cost as costrr, s.itemid, s.line, h.dateid
              from glstock as s left join glhead as h on h.trno=s.trno
              left join costing as c on c.trno=s.trno and c.line=s.line
              left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex
              where s.cost=0 and s.iss<>0 and rs.cost<>0 and s.trno=" . $trno);

    foreach ($data as $key => $value) {
      $this->coreFunctions->LogConsole("TS trno:" . $value->trno . " line:" . $value->line . ' - costrr: ' . $value->costrr);
      $this->coreFunctions->execqry("update glstock set cost=" . $value->costrr . " where trno=" . $value->trno . " and line=" . $value->line);
      $this->coreFunctions->execqry("update glstock set cost=" . $value->costrr . " where tstrno=" . $value->trno . " and tsline=" . $value->line);

      $costts = $this->coreFunctions->opentable("select trno, line, cost from glstock where tstrno=" . $value->trno  . " and tsline=" . $value->line);

      $this->coreFunctions->LogConsole("costts count:" . count($costts));
      foreach ($costts as $key2 => $value2) {
        $this->coreFunctions->execqry("update rrstatus set cost=" . $value->costrr . " where trno=" . $value2->trno . " and line=" . $value2->line);

        $costing = $this->coreFunctions->opentable("
        select c.trno, c.line, c.refx, c.linex, 0 as posted, s.cost, c.doc from costing as c left join cntnum as num on num.trno=c.trno left join lastock as s on s.trno=c.trno and s.line=c.line
        where c.refx=" . $value2->trno . " and c.linex=" . $value2->line . " and num.postdate is null
        union all
        select c.trno, c.line, c.refx, c.linex, 1 as posted, s.cost, c.doc from costing as c left join cntnum as num on num.trno=c.trno left join glstock as s on s.trno=c.trno and s.line=c.line
        where c.refx=" . $value2->trno . " and c.linex=" . $value2->line . " and num.postdate is not null;");

        $this->coreFunctions->LogConsole("costing count:" . count($costing));

        foreach ($costing as $key3 => $value3) {
          $this->coreFunctions->LogConsole("trno:" . $value3->trno . " line:" . $value3->line);

          $tbl = 'lastock';
          $tbl2 = 'lahead';
          if ($value3->posted) {
            $tbl = 'glstock';
            $tbl2 = 'glhead';
          }

          $sql = "select round(ifnull(sum(rs.cost * c.served) / s.iss,0), 6) as value from " . $tbl . " as s left join costing as c on c.trno=s.trno and c.line=s.line
                  left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex where s.trno=" . $value3->trno . " and s.line=" . $value3->line . "  group by s.trno,s.line,s.iss";
          $actualcost = $this->coreFunctions->datareader($sql, [], '', true);

          $this->coreFunctions->LogConsole($tbl . ' actual cost - ' . $actualcost);
          $this->coreFunctions->LogConsole($tbl . " trno:" . $value3->trno . " line:" . $value3->line . ' - ' . $actualcost . ' - ' . $value3->cost);

          if ($actualcost != $value3->cost) {
            $this->coreFunctions->LogConsole("****cost not equal doc " . $value3->doc);
            // $this->coreFunctions->LogConsole($sql);
            $this->coreFunctions->execqry("update " . $tbl . " set cost=" . $actualcost . " where trno=" . $value3->trno . " and line=" . $value3->line);
            if ($value3->doc = 'AJ') {
              $this->coreFunctions->execqry("update " . $tbl . " set ext=round((iss*cost),2)*-1 where trno=" . $value3->trno . " and line=" . $value3->line);
              $this->coreFunctions->LogConsole("update AJ ext");
            }
            if ($value3->posted) $this->coreFunctions->execqry("update " . $tbl2 . " set isreentryinv=1 where trno=" . $value3->trno);
            if ($value3->posted) $this->coreFunctions->execqry("update cntnum set isok=2 where trno=" . $value3->trno);
            $this->coreFunctions->LogConsole("");
          }
        }
      }
    }

    $this->coreFunctions->LogConsole("------");
    return ['status' => true, 'msg' => 'test'];
  }


  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);
    // if(!$isupdate){
    //   $data[0]->errcolor = 'bg-red-2';
    // }
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
    $msg = '';
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $res = $this->additem('insert', $config);
      if ($res['status']) {
        if ($res['msg'] != '') {
          $msg .= $res['msg'] . " " . $config['params']['data']['itemname'];
        }
      }
      if ($res['status'] == false) {
        $msg .= $res['msg'];
      }
    }
    if ($msg == '') {
      $msg = 'Successfully saved.';
    }
    $data = $this->openstock($config['params']['trno'], $config);
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
  public function tripapproved($config)
  {
    $trno = $config['params']['trno'];

    $msg = "";
    $status = true;
    $qry = "select receiveby, ifnull(date(receivedate), '') as receivedate from hcntnuminfo where trno = ?";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);

    if ($checking[0]->receivedate == "") {
      $tag = $this->coreFunctions->execqry("update hcntnuminfo set receiveby= '" . $config['params']['user'] . "',receivedate = '" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? ", "update", [$trno]);

      if ($tag) {
        $msg = "Trip Approved";
        $status = true;
        $this->logger->sbcwritelog($trno, $config, 'APPROVAL', $msg);
      } else {
        $msg = "Failed to approved";
        $status = false;
      }
    } else {
      $msg = "Already approved. " . $checking[0]->receiveby . ' ' . $checking[0]->receivedate;
    }
    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }
  public function tripdisapproved($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;
    $qry = "select date(receivedate) as receivedate from hcntnuminfo where trno = ? and receivedate is not null and receiveby <> ''";
    $checking = $this->coreFunctions->opentable($qry, [$trno]);
    if ($checking[0]->receivedate != "") {
      $this->coreFunctions->execqry("update hcntnuminfo set receiveby='',receivedate = null where trno=? ", "update", [$trno]);
      $msg = "Trip Disapproved";
      $status = true;
      $this->logger->sbcwritelog($trno, $config, 'DISAPPROVAL', $msg);
    }
    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  // insert and update item
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $ispallet = $this->companysetup->getispallet($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $wh = $config['params']['data']['wh'];
    $loc2 = '';
    $disc = '';
    $expiry = $config['params']['data']['expiry'];
    $rem = '';
    $loc = '';

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    if (isset($config['params']['data']['loc'])) {
      $loc = $config['params']['data']['loc'];
    }

    if (isset($config['params']['data']['disc'])) {
      $disc = $config['params']['data']['disc'];
    }

    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }


    $refx = 0;
    $linex = 0;
    $reqtrno = isset($config['params']['data']['reqtrno']) ? $config['params']['data']['reqtrno'] : 0;
    $reqline = isset($config['params']['data']['reqline']) ? $config['params']['data']['reqline'] : 0;
    $ref = '';
    $palletid = 0;
    $locid = 0;
    $palletid2 = 0;
    $locid2 = 0;
    $projectid = 0;
    $sorefx = 0;
    $solinex = 0;

    if (isset($config['params']['data']['loc2'])) {
      $loc2 = $config['params']['data']['loc2'];
    } else {
      if ($companyid == 42) { //pdpi mis
        $loc2 = $config['params']['data']['loc'];
      }
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

    if (isset($config['params']['data']['palletid2'])) {
      $palletid2 = $config['params']['data']['palletid2'];
    }
    if (isset($config['params']['data']['locid2'])) {
      $locid2 = $config['params']['data']['locid2'];
    }


    $allowso = $this->othersClass->checkAccess($config['params']['user'], 5257);

    if ($this->companysetup->getisshowtsso($config['params'])) {

      if ($allowso == '1') {
        if (isset($config['params']['data']['sorefx'])) {
          $sorefx = $config['params']['data']['sorefx'];
        }
        if (isset($config['params']['data']['solinex'])) {
          $solinex = $config['params']['data']['solinex'];
        }
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
      $qty = $config['params']['data']['qty'];
      $amt = $config['params']['data']['amt'];
      if ($companyid == 10) { //afti
        $projectid = $this->coreFunctions->getfieldvalue("item", 'projectid', 'itemid=?', [$itemid]);
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;

      if ($companyid == 10) { //afti
        $projectid = $config['params']['data']['projectid'];
      }
    }

    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
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
    $isnoninv= 0;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      $isnoninv = $item[0]->isnoninv;
    }

    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, $vat, 'P', $kgs);


    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => number_format($computedata['amt'], $this->companysetup->getdecimal('price', $config['params']), '.', ''),
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'loc' => $loc,
      'loc2' => $loc2,
      'expiry' => $expiry,
      'uom' => $uom,
      'palletid' => $palletid,
      'locid' => $locid,
      'palletid2' => $palletid2,
      'locid2' => $locid2,
      'rem' => $rem,
      'reqtrno' => $reqtrno,
      'reqline' => $reqline,
      'sorefx' => $sorefx,
      'solinex' => $solinex
    ];

    switch ($companyid) {
      case 11: //summit
        $data['rem'] = $rem;
        break;
      case 10: //afti
        $data['projectid'] = $projectid;
        break;
    }

    if ($systemtype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
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


        // if ($this->companysetup->getisshowtsso($config['params'])) {
        //   if ($allowso == '1') {
        //     $this->coreFunctions->execqry("update hsostock set tsqa=" . $qty . " where trno=" . $sorefx . " and line=" . $solinex, 'update');
        //   }
        // }

        if ($companyid == 16) { //ati
          $stockinfo_data = ['trno' => $trno, 'line' => $line];
          $this->coreFunctions->sbcinsert('stockinfo', $stockinfo_data);
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $havestock = true;
        if($isnoninv ==0){
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
  
            if ($companyid == 21) { //kinggeorge
              $this->logger->sbcwritelog($trno, $config, 'DEBUG', 'ADD - Line:' . $line . ' itemid:' . $itemid . '. Cost:' . $cost);
            }
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:0.0');
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
      if($isnoninv == 0){
        if ($ispallet) {
          $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        }

        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->setserveditems($refx, $linex);
          $this->setservepritems($reqtrno, $reqline);
          $this->setservedsoitems($sorefx, $solinex);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:0.0');
          $return = false;
        }
      }      

      
      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $return = false;
      }
      if ($reqtrno != 0) {
        if ($this->setservepritems($reqtrno, $reqline) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setservepritems($reqtrno, $reqline);
          $return = false;
        }
      }

      if ($this->setservedsoitems($sorefx, $solinex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setservedsoitems($sorefx, $solinex);
        $return = false;
      }
      
      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    if ($this->companysetup->getserial($config['params'])) {
      $data2 = $this->coreFunctions->opentable('select trno,line from ' . $this->stock . ' where trno=?', [$trno]);
      foreach ($data2 as $key => $value) {
        $this->othersClass->deleteserialout($data2[$key]->trno, $data2[$key]->line);
      }
    }

    $data = $this->coreFunctions->opentable('select refx,linex,reqtrno,reqline,sorefx,solinex from ' . $this->stock . ' where trno=? and (refx<>0 or reqtrno<>0 or sorefx <>0)', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);

    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      $this->setservedsplitqtyitems($data[$key]->refx, $data[$key]->linex);

      if ($data[$key]->reqtrno != 0) {
        $this->setservepritems($data[$key]->reqtrno, $data[$key]->reqline);
      }
      if ($data[$key]->sorefx != 0) {
        $this->setservedsoitems($data[$key]->sorefx, $data[$key]->solinex);
      }
    }

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='TS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='TS' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }


    return $this->coreFunctions->execqry("update htrstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function setservedsoitems($sorefx, $solinex)
  {
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='TS' and stock.sorefx=" . $sorefx . " and stock.solinex=" . $solinex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='TS' and glstock.sorefx=" . $sorefx . " and glstock.solinex=" . $solinex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }


    return $this->coreFunctions->execqry("update hsostock set tsqa=" . $qty . " where trno=" . $sorefx . " and line=" . $solinex, 'update');
  }

  public function setservepritems($refx, $linex)
  {

    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='TS' and stock.reqtrno=" . $refx . " and stock.reqline=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='TS' and glstock.reqtrno=" . $refx . " and glstock.reqline=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    return $this->coreFunctions->execqry("update hprstock set tsqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }


  public function setservedsplitqtyitems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='TS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 .= " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='TS' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(count(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty > 0) {
      $qty = 1;
    } else {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update splitqty set isqa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function deleteitem($config)
  {
    $companyid = $config['params']['companyid'];
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


    if ($data[0]->refx != 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
      $this->setservedsplitqtyitems($data[0]->refx, $data[0]->linex);
    }
    if ($data[0]->reqtrno != 0) {
      $this->setservepritems($data[0]->reqtrno, $data[0]->reqline);
    }

    if ($data[0]->sorefx != 0) {
      $this->setservedsoitems($data[0]->sorefx, $data[0]->solinex);
    }

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getsplitqtydetails($config)
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
      $config['params']['data']['qty'] = $value['splitqty'];
      $config['params']['data']['wh'] = $wh;
      $config['params']['data']['whid'] = $value['whid'];
      $config['params']['data']['locid'] = $value['locid'];
      $config['params']['data']['locid2'] = $value['locid2'];
      $config['params']['data']['palletid'] = $value['palletid'];
      $config['params']['data']['palletid2'] = $value['palletid'];
      $config['params']['data']['loc'] = '';
      $config['params']['data']['expiry'] = '';
      $config['params']['data']['rem'] = '';
      $config['params']['data']['refx'] = $value['trno'];
      $config['params']['data']['linex'] = $value['line'];
      $config['params']['data']['ref'] = $value['docno'];
      $config['params']['data']['amt'] = $value['rrcost'];
      $return = $this->additem('insert', $config);
      if ($return['status']) {
        if ($this->setservedsplitqtyitems($value['trno'], $value['line']) == 0) {
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

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    if ($companyid == 32) { //3m
      $qry = "select 'SRP' as docno, left(now(),10) as dateid,(case uom.amt when 0 then (item.amt*uom.factor) else uom.amt end) as amt,(case uom.amt when 0 then (item.amt*uom.factor) else uom.amt end)  as defamt, '' as disc, uom.uom from item left join uom on uom.itemid = item.itemid where item.barcode=? and uom.isdefault =1  
          union all
          select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,'' as disc,uom from(
          select head.docno,head.dateid,
          (stock.cost*uom.factor) as amt,uom.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            left join uom on uom.itemid = item.itemid and uom.isdefault = 1
            where cntnum.center = ? and item.barcode = ?
            and stock.rrcost <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,(stock.cost*uom.factor) as amt,
            uom.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            left join uom on uom.itemid = item.itemid and uom.isdefault = 1
            where cntnum.center = ? and item.barcode = ?
            and stock.rrcost <> 0 and cntnum.trno <> ?
            order by dateid desc limit 5) as tbl";

      $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $trno, $center, $barcode, $trno]);
      if (!empty($data)) {
        return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
      }
    } else {
      $getlatestcostTS = $this->othersClass->getlatestcostTS($config, $barcode, $client, $center, $trno);

      if ($companyid == 37) { //mega crystal
        $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$barcode]);

        if (isset($getlatestcostTS['data'])) {
          $getlatestcostTS['data'][0]->docno = 'UOM';
          $getlatestcostTS['data'][0]->dateid = null;
          $getlatestcostTS['data'][0]->amt = $this->coreFunctions->getfieldvalue("uom", "amt", "itemid=? and uom=?", [$itemid, $getlatestcostTS['data'][0]->uom]);
        } else {
          $itemuom = $this->coreFunctions->getfieldvalue("item", "uom", "itemid=?", [$itemid]);
          $getlatestcostTS['data'] = [
            'docno' => 'UOM',
            'dateid' => null,
            'amt' => $this->coreFunctions->getfieldvalue("uom", "amt", "itemid=? and uom=?", [$itemid, $itemuom])
          ];
        }
      }
      return $getlatestcostTS;
    }
  } // end function
  public function gettrsummaryqry($config)
  {
    return "
select head.trno,head.docno,head.dateid,wh2.client as wh2,head.deptid,wh.client ,wh.clientname,
head.projectid,head.yourref,head.ourref,head.rem,head.terms,head.cur,head.forex,head.address,head.branch,head.vattype,
stock.rem as rem2,item.itemid,stock.trno, stock.line, item.barcode,stock.uom,stock.loc,stock.disc,
stock.qty,stock.rrcost,stock.rrqty,stock.ext,stock.qa,stock.cost
from htrhead as head
left join htrstock as stock on stock.trno = head.trno
left join client as wh on wh.client = head.wh
left join item on item.itemid=stock.itemid
left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
left join hheadinfotrans as info on info.trno=head.trno
left join client as wh2 on wh2.clientid = info.wh2
left join client as whs on whs.clientid=stock.whid
where stock.trno = ? and stock.qty>stock.qa";
  }
  public function refillitem($config)
  {
    $trno = $config['params']['trno'];
    $wh = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $swh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$trno]);
    $swhid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$swh]);
    $data = [];
    $msg = '';
    $date =  date("Y-m-d", strtotime($this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno])  . '-1 days'));
    $tsdate =  date("Y-m-d", strtotime($this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno])));

    $date1 =  date("Y-m-d", strtotime($this->coreFunctions->getfieldvalue($this->head, "sdate1", "trno=?", [$trno])));
    $date2 =  date("Y-m-d", strtotime($this->coreFunctions->getfieldvalue($this->head, "sdate2", "trno=?", [$trno])));

    $qry = "select count(line) as value from (select s.line from lastock as s left join lahead as h on h.trno = s.trno left join cntnum as c on c.trno = h.trno 
    where c.bref='WS' and h.client = ? and date(h.sdate1) = '" . $date1 . "' and date(h.sdate2) ='" . $date2 . "' and h.trno <> ?
    union all
    select s.line from glstock as s left join glhead as h on h.trno = s.trno left join client as cl on cl.clientid = h.clientid left join cntnum as c on c.trno = h.trno 
    where c.bref='WS' and cl.client = ? and date(h.sdate1) = '" . $date1 . "' and date(h.sdate2) ='" . $date2 . "' and h.trno <> ?) as a ";

    $exist = $this->coreFunctions->datareader($qry, [$wh,  $trno, $wh, $trno]);

    $this->coreFunctions->LogConsole($qry);
    if (floatval($exist) != 0) {
      $returns = $this->openstock($trno, $config);
      return ['inventory' => $returns, 'status' => true, 'msg' => 'Refill already exist for this warehouse.'];
    } else {
      $exist = $this->coreFunctions->datareader("select count(line) as value from " . $this->stock . " where trno= ?", [$trno]);

      if (floatval($exist) != 0) {
        $qry = "delete from " . $this->stock . " where trno=? ";
        $this->coreFunctions->execqry($qry, 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
      }

      $qry = "select item.itemid,item.barcode,item.itemname,s.whid,sum(s.isqty) as isqty,sum(s.iss) as iss,s.uom,s.cost,(s.cost*uom.factor) as rrcost from lahead as h 
      left join lastock as s on s.trno = h.trno left join item on item.itemid = s.itemid left join client as wh on wh.clientid = s.whid  left join uom on uom.itemid = item.itemid and uom.uom = s.uom
      where h.doc='SJ' and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and wh.client = '" . $wh . "'  group by item.itemid,item.barcode,item.itemname,wh.clientid,s.whid,s.cost,s.uom,uom.factor
      union all
      select item.itemid,item.barcode,item.itemname,wh.clientid as whid,sum(s.isqty) as isqty,sum(s.iss) as iss,s.uom,s.cost,(s.cost*uom.factor) as rrcost from glhead as h left join glstock as s on s.trno = h.trno left join item on item.itemid = s.itemid 
      left join client as wh on wh.clientid = s.whid  left join uom on uom.itemid = s.itemid and uom.uom = s.uom
      where h.doc='SJ' and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "' and wh.client = '" . $wh . "' group by item.itemid,item.barcode,item.itemname,wh.clientid,s.whid,s.cost,s.uom,uom.factor ";

      $data = $this->coreFunctions->opentable($qry);

      foreach ($data as $key => $value) {
        $config['params']['data']['uom'] = $data[$key]->uom;
        $config['params']['data']['disc'] = '';
        $config['params']['data']['itemid'] = $data[$key]->itemid;
        $config['params']['data']['itemname'] = $data[$key]->itemname;
        $config['params']['trno'] = $trno;
        $config['params']['data']['qty'] = $data[$key]->isqty;
        $config['params']['data']['wh'] = $swh;
        $config['params']['data']['whid'] = $swhid;
        $config['params']['data']['loc'] = '';
        $config['params']['data']['expiry'] = '';
        $config['params']['data']['rem'] = '';
        $config['params']['data']['amt'] = $data[$key]->rrcost;
        $return = $this->additem('insert', $config);
        if ($return['status']) {
          if ($return['msg'] != '') {
            $msg .= $return['msg'] . " " . $config['params']['data']['itemname'];
          }

          if ($return['status'] == false) {
            $msg .= $return['msg'];
          }
        }
      }
      if ($msg == '') {
        $msg = 'Successfully saved.';
      }
      $this->coreFunctions->execqry("update " . $this->head . " set rem ='Refill for Sales transaction " . date("m/d/Y", strtotime($date1)) . " to " . date("m/d/Y", strtotime($date2)) . "' where trno = ?", "update", [$trno]);
      $returns = $this->openstock($trno, $config);
      return ['inventory' => $returns, 'status' => true, 'msg' => $msg, 'reloadhead' => true];
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

    switch ($companyid) {
      case 40: //cdo
      case 52: //technolab
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['received'])) $this->othersClass->writeSignatories($config, 'received', $dataparams['received']);
        break;
    }

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
