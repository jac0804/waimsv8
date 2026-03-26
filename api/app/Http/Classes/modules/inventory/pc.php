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
use App\Http\Classes\sqlquery;
use Exception;

class pc
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PHYSICAL COUNT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'pchead';
  public $hhead = 'hpchead';
  public $stock = 'pcstock';
  public $hstock = 'hpcstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = ['trno', 'docno', 'dateid', 'wh', 'yourref', 'ourref', 'rem', 'projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'];
  private $otherfields = ['trno', 'sizeid', 'partid'];
  private $except = ['trno', 'dateid'];
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
    $this->sqlquery = new sqlquery;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 276,
      'edit' => 277,
      'new' => 278,
      'save' => 279,
      'change' => 67,
      'delete' => 281,
      'print' => 282,
      'lock' => 283,
      'unlock' => 284,
      'changeamt' => 838,
      'post' => 285,
      'unpost' => 286,
      'additem' => 835,
      'edititem' => 836,
      'deleteitem' => 837,
      'viewcost' => 368,
      'viewamt' => 368
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'rem', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];

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
    $cols[$postdate]['label'] = 'Post Date';

    if ($config['params']['companyid'] == 37) { //megacrystal
      $cols[$rem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    } else {
      $cols[$rem]['type'] = 'coldel';
    }
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


    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'wh.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
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
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby = "order by docno desc, dateid desc";
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";
        break;
    }
    $qry = "select head.trno,head.docno,wh.clientname,$dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref,head.rem
     from " . $this->head . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno left join client as wh on wh.client=head.wh " . $join . " where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
     union all
     select head.trno,head.docno,wh.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
       head.yourref, head.ourref,head.rem  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num
     on num.trno=head.trno left join client as wh on wh.client=head.wh  " . $hjoin . " where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $addparams . " " . $filtersearch . "
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

    switch ($config['params']['companyid']) {
      case 14: //majesty
      case 47: //kstar
      case 56: //homeworks
      case 60://transpower
        $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
        $buttons['others']['items']['downloadexcel'] = ['label' => 'Download PC Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
        break;
    }

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'pc', 'title' => 'PC_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
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

    if ($config['params']['companyid'] == 60) { //transpower      
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5494);
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
    $resellerid = $config['params']['resellerid'];
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451); //kinggeorge

    $action = 0;
    $itemdesc = 1;
    $oqty = 2;
    $rrqty = 3;
    $uom = 4;
    $rrcost = 5;
    $ext = 6;
    $wh = 7;
    $whname = 8;
    $loc = 9;
    $expiry = 10;
    $rem = 11;

    $stock_projectname = 12;
    $subcode = 13;
    $partno = 14;
    $boxcount = 15;
    $consignee = 16;
    $location = 17;

    $asofqty = 18;

    $itemname = 19;
    $barcode = 20;

    $column = [
      'action',
      'itemdescription',
      'oqty',
      'rrqty',
      'uom',
      'rrcost',
      'ext',
      'wh',
      'whname',
      'loc',
      'expiry',
      'rem',
      'stock_projectname',
      'subcode',
      'partno',
      'boxcount',
      'consignee',
      'location',
      'asofqty',
      'itemname',
      'barcode'
    ];

    switch ($systemtype) {
      case 'REALESTATE':
        $project = 21;
        $phasename = 22;
        $housemodel = 23;
        $blk = 24;
        $lot = 25;
        $amenityname = 26;
        $subamenityname = 27;
        array_push($column, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');
        break;
    }

    $headgridbtns = ['adjust'];

    if ($companyid == 10) { //afti
      array_push($headgridbtns, 'viewitemstockinfo');
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],
    ];
    

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

    // $stockbuttons = ['save', 'delete', 'showbalance'];

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }


    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'AIMS':
        if ($companyid == 0) { //main
          array_push($stockbuttons, 'stockinfo');
        } else if ($companyid == 10) { // afti
          array_push($stockbuttons, 'iteminfo');
        }
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // 7 - ref
    $obj[0]['inventory']['columns'][$rrcost]['label'] = 'Unit Cost';
    // if ($companyid == 42) { //pdpi mis
    //   $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
    //   $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
    // }
    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
      $obj[0]['inventory']['columns'][$ext]['readonly'] = true;
    }

    if ($viewcost == '0') {
      if ($isexpiry) {
        //loc
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
        $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
        //expiry
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
      }
      $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';
    } else {
      if ($isexpiry) {
        //loc
        $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
        $obj[0]['inventory']['columns'][$loc]['type'] = 'input';
        //expiry
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
      }
    }

    if (!$isexpiry) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$expiry]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px';

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

    $obj[0]['inventory']['columns'][$rem]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';

    $obj[0]['inventory']['columns'][$itemname]['style'] = 'width: 1%;whiteSpace: normal;min-width:1%;max-width:1%';
    $obj[0]['inventory']['columns'][$barcode]['style'] = 'width: 1%;whiteSpace: normal;min-width:1%;max-width:1%';

    if ($companyid != 10) { //not afti
      $obj[0]['inventory']['columns'][$stock_projectname]['type'] = 'coldel';
    }

    if ($companyid != 6) { //not mitsukoshi
      $obj[0]['inventory']['columns'][$partno]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$subcode]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$boxcount]['type'] = 'coldel';
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

    if ($this->companysetup->getsystemtype($config['params']) != "FAMS") {
      $obj[0]['inventory']['columns'][$consignee]['type'] = 'coldel';
    }

    if (!$this->companysetup->getispallet($config['params'])) {
      $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
    }

    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $obj[0]['inventory']['descriptionrow'] = [];
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
      $obj[0]['inventory']['columns'][$wh]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$whname]['type'] = 'lookup';
      $obj[0]['inventory']['columns'][$whname]['lookupclass'] = 'whstock';
      $obj[0]['inventory']['columns'][$whname]['action'] = 'lookupclient';
    } else {
      $obj[0]['inventory']['columns'][$whname]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
    }

    if ($resellerid != 2) {
      $obj[0]['inventory']['columns'][$oqty]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    if ($resellerid != 2) {
      $obj[0]['inventory']['columns'][$asofqty]['type'] = 'coldel';
    }

    if ($companyid == 23 || $companyid == 41 || $companyid == 52) { //labsol cebu, labsol manila & technolab
      $obj[0]['inventory']['columns'][$loc]['label'] = 'Lot/Serial#';
      $obj[0]['inventory']['columns'][$expiry]['label'] = 'Expiry/Mfr Date';
    }

    if ($companyid == 47) { //kitchenstar
      if ($viewcost == '0') {
        $obj[0]['inventory']['showtotal'] = false;
      }
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    if ($config['params']['companyid'] == 17) { //unihome
      $tbuttons = ['pendingat', 'additem', 'quickadd', 'saveitem', 'deleteallitem'];
    } else {
      $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $resellerid = $config['params']['resellerid'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4852);
    $fields = ['docno', 'dwhname', 'yourref'];

    if ($systemtype == 'REALESTATE') {
      array_push($fields, 'dprojectname', 'phase');
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');

    if ($systemtype == 'REALESTATE') {
      data_set($col1, 'dprojectname.lookupclass', 'project');
      data_set($col1, 'phase.addedparams', ['projectid']);
    }

    $fields = ['dateid', 'ourref'];

    if ($systemtype == 'REALESTATE') {
      array_push($fields, 'housemodel', ['blklot', 'lot']);
    }
    $col2 = $this->fieldClass->create($fields);

    if ($companyid == 40) { //cdo
      if ($noeditdate) {
        data_set($col2, 'dateid.class', 'sbccsreadonly');
      }
    }

    if ($systemtype == 'REALESTATE') {
      data_set($col2, 'housemodel.addedparams', ['projectid']);
      data_set($col2, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
    }
    $fields = ['rem'];
    if ($systemtype == 'REALESTATE') {
      $fields = ['amenityname', 'subamenityname', 'rem'];
    }

    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    $col3 = $this->fieldClass->create($fields);

    if ($systemtype == 'REALESTATE') {
      data_set($col3, 'subamenityname.addedparams', ['amenityid']);
    }

    $fields = [];
    if ($companyid == 37) { //mega crystal
      array_push($fields, 'create');
    }

    if ($resellerid == 2) { //mis
      array_push($fields, 'loadinventorywithbal');
    }

    $col4 = $this->fieldClass->create($fields);
    if ($companyid == 37) { //mega crystal
      data_set($col4, 'create.type', 'actionbtn');
      data_set($col4, 'create.label', 'LOAD INVENTORY WITH BALANCE');
      data_set($col4, 'create.confirm', true);
      data_set($col4, 'create.confirmlabel', 'Proceed to load inventory?');
      data_set($col4, 'create.access', 'save');
      data_set($col4, 'create.lookupclass', 'stockstatusposted');
      data_set($col4, 'create.action', 'loadinv');
    }
    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['sizeid'] = '';
    $data[0]['partname'] = '';
    $data[0]['partid'] = '0';
    $data[0]['stockgrp'] = '';
    $data[0]['groupid'] = '0';

    $data[0]['projectcode'] = '';
    $data[0]['projectid'] = 0;
    $data[0]['projectname'] = '';

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
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
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
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid,
         head.clientname,
         head.address,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.agent,
         agent.clientname as agentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         left(head.due,10) as due,
         client.groupid,
         hinfo.sizeid,
         hinfo.groupid, ifnull(stockgrp.stockgrp_name,'') as stockgrp, 
         hinfo.partid,ifnull(pmaster.part_name,'') as partname,
        
         head.projectid,ifnull(project.code,'') as projectcode,
         ifnull(project.name,'') as projectname,
         head.phaseid, ps.code as phase,head.modelid, hm.model as housemodel, head.blklotid, 
         bl.blk as blklot,  bl.lot, amen.line as amenityid, amen.description as amenityname, 
         subamen.line as subamenityid, subamen.description as subamenityname
         ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join headinfotrans as hinfo on hinfo.trno=head.trno
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = hinfo.groupid
        left join part_masterfile as pmaster on pmaster.part_id = hinfo.partid

        left join projectmasterfile as project on project.line=head.projectid
        left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid

        where head.trno = ? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join headinfotrans as hinfo on hinfo.trno=head.trno
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = hinfo.groupid
        left join part_masterfile as pmaster on pmaster.part_id = hinfo.partid

        left join projectmasterfile as project on project.line=head.projectid
        left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid

        where head.trno = ? and num.center=? ";


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
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
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
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($companyid == 14) { //majesty
      $info['trno'] = $head['trno'];
      $info['sizeid'] = $head['sizeid'];
      $info['partid'] = $head['partid'];
      $info['groupid'] = $head['groupid'];
    }

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      if ($companyid == 14) { //majesty
        $this->coreFunctions->sbcupdate('headinfotrans', $info, ['trno' => $head['trno']]);
      }
    } else {

      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      if ($companyid == 14) { //majesty
        $this->coreFunctions->sbcinsert('headinfotrans', $info);
      }
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['wh']);
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

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
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

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,
      projectid, phaseid,modelid,blklotid,amenityid,subamenityid)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,
      head.projectid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid
      FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {

      if (!$this->othersClass->postingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      // for glstock
      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,expiry,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,refx,linex,rem,palletid,locid, projectid, oqty,
        phaseid,modelid,blklotid,amenityid,subamenityid)
        SELECT trno, line, itemid, uom,whid,loc,expiry,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,refx,linex,rem,palletid,locid, projectid, oqty,
        phaseid,modelid,blklotid,amenityid,subamenityid
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
      //if($posthead){
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,clientname,address,shipto,dateid,terms,rem,forex,
  yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,
     projectid, phaseid,modelid,blklotid,amenityid,subamenityid)
  select head.trno, head.doc, head.docno,  head.clientname, head.address, head.shipto,
  head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
  head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,
   head.projectid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid
  from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
  where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while unposting head data.'];
      }

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,expiry,ref,disc,
      cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,
      refx,linex,palletid,locid, projectid, oqty,
      phaseid,modelid,blklotid,amenityid,subamenityid)
      select trno, line, itemid, uom,whid,loc,expiry,ref,disc,cost, qty,void, rrcost, rrqty,
      ext,rem, encodeddate, qa, encodedby, editdate, editby,
      refx,linex,palletid,locid, projectid, oqty,
      phaseid,modelid,blklotid,amenityid,subamenityid
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti & afti usd
      $qty_dec = 0;
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
    item.itemname,
    stock.uom,
    stock.cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $qty_dec . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    FORMAT(stock.oqty," . $qty_dec . ")  as oqty,
    FORMAT(stock.asofqty," . $qty_dec . ")  as asofqty,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,stock.expiry,
    item.brand,
    stock.rem,
    stock.palletid,
    stock.locid,
    ifnull(pallet.name,'') as pallet,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid, 
    item.subcode, item.partno, 
    round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,
    stock.consignee,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription,
    
    stock.phaseid, ps.code as phasename,  stock.modelid, hm.model as housemodel,stock.blklotid, bl.blk, bl.lot,
    stock.projectid, prj.code as project,
     amen.line as amenity, amen.description as amenityname,  subamen.line as subamenity, subamen.description as subamenityname

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
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid

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
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    where stock.trno =?  order by sortline,line ";

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
  left join projectmasterfile as prj on prj.line = stock.projectid
  left join frontend_ebrands as brand on brand.brandid = item.brand
  left join iteminfo as i on i.itemid  = item.itemid 

   left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
  where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        if ($config['params']['resellerid'] == 2) {
          $config['params']['getqoh'] = true;
        }
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        if ($config['params']['resellerid'] == 2) {
          $config['params']['getqoh'] = true;
        }
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
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
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'getatsummary':
        return $this->getatsummary($config);
        break;
      case 'getatdetails':
        return $this->getatdetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function getatsummary($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];
    $filtercenter = " and transnum.center = '" . $center . "' ";
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "select head.docno, item.itemid,stock.trno,stock.line, item.barcode,
                           stock.uom, stock.cost,stock.qty,stock.rrcost,
                            round((stock.qty)/ case when ifnull(uom.factor,0)=0 
                            then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                        stock.disc,stock.rem,stock.ext
                        FROM hathead as head 
                        left join hatstock as stock on stock.trno=head.trno
                        left join transnum on transnum.trno=head.trno 
                        left join item on item.itemid=stock.itemid 
                        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
                        where stock.trno = ? " . $filtercenter . " and stock.ispc=0
               and stock.void=0";

      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {

          $data[$key2]->rrqty = $this->othersClass->sanitizekeyfield("qty", $data[$key2]->rrqty);

          $line = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and itemid=? ", [$trno, $data[$key2]->itemid], '', true);

          $type = 'insert';
          if ($line != 0) {
            $config['params']['line'] = $line;

            $data2 = $this->coreFunctions->opentable("select itemid, rrcost, rrqty, uom  from " . $this->stock . " where trno=? and line=?", [$trno, $line]);

            $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
            $item = $this->coreFunctions->opentable($qry, [$data[$key2]->uom, $data[$key2]->itemid]);
            $factor = 1;

            if (!empty($item)) {
              $item[0]->factor = $this->othersClass->val($item[0]->factor);
              if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            }

            $qty = round($data[$key2]->rrqty + $data2[0]->rrqty, $this->companysetup->getdecimal('qty', $config['params']));
            $computedata = $this->othersClass->computestock($data2[0]->rrcost, '', $qty, $factor);

            $update = ['rrqty' => $qty, 'qty' => $computedata['qty'], 'ext' => $computedata['ext']];
            if ($this->coreFunctions->sbcupdate($this->stock, $update, ['trno' => $trno, 'line' => $line])) {
              $return = ['status' => true];
            } else {
              $return = ['status' => false, 'msg' => 'Failed to add item ' . $data[$key2]->barcode];
            }

            $type = 'update';
          } else {
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->rrqty;
            $config['params']['data']['wh'] = $wh;
            $config['params']['data']['loc'] = '';
            $config['params']['data']['expiry'] = '';
            $config['params']['data']['rem'] = $data[$key2]->rem;
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->rrcost;
            $config['params']['data']['ext'] = $data[$key2]->ext;

            $config['params']['barcode'] = $data[$key2]->barcode;
            $config['params']['client'] = '';
            $lprice = $this->getlatestprice($config);
            $lprice = json_decode(json_encode($lprice), true);
            if (!empty($lprice['data'])) {
              $config['params']['data']['amt'] = $lprice['data'][0]['amt'];
              $config['params']['data']['disc'] = $lprice['data'][0]['disc'];
            }

            $return = $this->additem($type, $config);
          }

          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
            }
          }
        } // end foreach
      } //end if
    } //end foreach
    $data = $this->openstock($trno, $config);
    return ['status' => true, 'reloadgriddata' => true, 'msg' => 'Items were successfully added.', 'griddata' => ['inventory' => $data]];
  } //end function

  public function getatdetails($config)
  {
    ini_set('max_execution_time', -1);
    ini_set('memory_limit', '-1');

    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $wh = $config['params']['wh'];
    $center = $config['params']['center'];
    $rows = [];

    $filtercenter = " and transnum.center = '" . $center . "' ";

    foreach ($config['params']['rows'] as $key => $value) {

      $qry = "select head.docno, item.itemid,stock.trno,stock.line, item.barcode,
                           stock.uom, stock.cost,stock.qty,stock.rrcost,
                            round((stock.qty)/ case when ifnull(uom.factor,0)=0 
                            then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
                        stock.disc,stock.rem,stock.ext
                        FROM hathead as head 
                        left join hatstock as stock on stock.trno=head.trno
                        left join transnum on transnum.trno=head.trno 
                        left join item on item.itemid=stock.itemid 
                        left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
                        where stock.trno = ? and stock.line=? " . $filtercenter . " 
               and stock.void=0 and stock.ispc=0";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);

      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $data[$key2]->rrqty = $this->othersClass->sanitizekeyfield("qty", $data[$key2]->rrqty);

          $line = $this->coreFunctions->getfieldvalue($this->stock, "line", "trno=? and itemid=? ", [$trno, $data[$key2]->itemid], '', true);

          $type = 'insert';
          if ($line != 0) {
            $config['params']['line'] = $line;

            $data2 = $this->coreFunctions->opentable("select itemid, rrcost, rrqty, uom  from " . $this->stock . " where trno=? and line=?", [$trno, $line]);

            $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
            $item = $this->coreFunctions->opentable($qry, [$data[$key2]->uom, $data[$key2]->itemid]);
            $factor = 1;

            if (!empty($item)) {
              $item[0]->factor = $this->othersClass->val($item[0]->factor);
              if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            }

            $qty = round($data[$key2]->rrqty + $data2[0]->rrqty, $this->companysetup->getdecimal('qty', $config['params']));
            $computedata = $this->othersClass->computestock($data2[0]->rrcost, '', $qty, $factor);

            $update = ['rrqty' => $qty, 'qty' => $computedata['qty'], 'ext' => $computedata['ext']];
            if ($this->coreFunctions->sbcupdate($this->stock, $update, ['trno' => $trno, 'line' => $line])) {
              $return = ['status' => true];
            } else {
              $return = ['status' => false, 'msg' => 'Failed to add item ' . $data[$key2]->barcode];
            }
          } else {
            $config['params']['data']['uom'] = $data[$key2]->uom;
            $config['params']['data']['itemid'] = $data[$key2]->itemid;
            $config['params']['trno'] = $trno;
            $config['params']['data']['disc'] = $data[$key2]->disc;
            $config['params']['data']['qty'] = $data[$key2]->rrqty;
            $config['params']['data']['wh'] = $wh;
            $config['params']['data']['loc'] = '';
            $config['params']['data']['expiry'] = '';
            $config['params']['data']['rem'] = $data[$key2]->rem;
            $config['params']['data']['refx'] = $data[$key2]->trno;
            $config['params']['data']['linex'] = $data[$key2]->line;
            $config['params']['data']['ref'] = $data[$key2]->docno;
            $config['params']['data']['amt'] = $data[$key2]->rrcost;
            $config['params']['data']['ext'] = $data[$key2]->ext;

            $return = $this->additem($type, $config);
          }

          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
            }
          }
        } // end foreach
      } //end if

    } //end foreach

    $data = $this->openstock($trno, $config);
    return ['status' => true, 'reloadgriddata' => true, 'msg' => 'Items were successfully added.', 'griddata' => ['inventory' => $data]];
  } //end function

  public function setserveditems($refx, $linex, $void = 0)
  {
    $filter = "";


    $qry1 = "select stock." . $this->hqty . " 
             from pchead as head 
             left join pcstock as stock on stock.trno=head.trno 
             where head.doc='PC' and stock.void = 0 
                   and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all 
             select stock." . $this->hqty . " 
             from hpchead as head 
             left join hpcstock as stock on stock.trno=head.trno 
             where head.doc='PC' and stock.void = 0 
                   and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hatstock set ispc=1 where trno=" . $refx . " and line=" . $linex, 'update');
  }


  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'loadinv':
        $this->loadinv($config);
        return ['status' => false, 'msg' => 'Successfully loaded.'];
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'uploadexcel':
        if ($config['params']['resellerid'] == 2) {
          $config['params']['getqoh'] = true;
        }
        return $this->othersClass->uploadexcel($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public  function getqoh($config) {}

  public function loadinv($config)
  {
    $trno = $config['params']['trno'];
    $header = $this->coreFunctions->opentable("select wh, date(dateid) as dateid from " . $this->head . " where trno=?", [$trno]);

    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Load Inventory');

    $item = $this->coreFunctions->opentable("select barcode, itemid, uom, itemname from item where isinactive=0 and barcode<>'' order by itemname");

    $itemcount = 0;
    if (!empty($item)) {
      foreach ($item as $key => $value) {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', -1);
        $bal = $this->sqlquery->getbalbydate($value->barcode, $header[0]->wh, '', '', $header[0]->dateid);
        if ($bal > 0) {
          $itemcount += 1;
          $config['params']['data']['uom'] = $value->uom;
          $config['params']['data']['itemid'] = $value->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = '';
          $config['params']['data']['wh'] = $header[0]->wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['oqty'] = $bal;
          $config['params']['data']['amt'] = '0';
          $config['params']['data']['qty'] = '0';

          $config['params']['barcode'] = $value->barcode;
          $config['params']['wh'] = $header[0]->wh;
          $config['params']['client'] = '';
          $cost = $this->getlatestprice($config);
          if (!empty($cost['data'])) {
            $config['params']['data']['amt'] = $cost['data'][0]->amt;
          }

          $result = $this->additem("insert", $config);
        }
      }

      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Total items:' . $itemcount);
    }
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';

    $msg = '';
    if (isset($isupdate['msg'])) {
      if ($isupdate['msg'] != '') {
        $msg = $isupdate['msg'];
      }
    }

    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
      }
    }

    if (!$isupdate) {
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
        if (isset($update['msg'])) {
          $msg = $msg . ' ' . $update['msg'];
        }
      } else {
        if (isset($update['msg'])) {
          $msg = $update['msg'];
        }
      }
    }
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
    $item = $this->coreFunctions->opentable("select item.itemid,0 as amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : '';
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];
    $void = 'false';

    $getqoh = isset($config['params']['getqoh']) ? true : false;

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    $refx = 0;
    $linex = 0;
    $rem = '';
    $expiry = '';
    $palletid = 0;
    $locid = 0;
    $projectid = 0;
    $consignee = "";
    $oqty = 0;
    $amt = 0;

    if (isset($config['params']['data']['oqty'])) {
      $oqty = $config['params']['data']['oqty'];
    }

    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }

    if ($getqoh) {
      $dateid = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
      $bal = $this->sqlquery->getbalbydate($config['params']['data']['barcode'], $config['params']['data']['wh'], $loc, $expiry, date('Y-m-d', strtotime($dateid)));
      if ($bal == '') {
        $bal = 0;
      }
      $oqty = $bal;
    }


    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }


    if (isset($config['params']['data']['palletid'])) {
      $palletid = $config['params']['data']['palletid'];
    }

    if (isset($config['params']['data']['consignee'])) {
      $consignee = $config['params']['data']['consignee'];
    }

    if (isset($config['params']['data']['locid'])) {
      $locid = $config['params']['data']['locid'];
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
      $qty = $config['params']['data']['qty'];

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
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);


    if ($systemtype == 'REALESTATE') {
      $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
      $phaseid = $this->coreFunctions->getfieldvalue($this->head, "phaseid", "trno=?", [$trno]);
      $modelid = $this->coreFunctions->getfieldvalue($this->head, "modelid", "trno=?", [$trno]);
      $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      $amenityid = $this->coreFunctions->getfieldvalue($this->head, "amenityid", "trno=?", [$trno]);
      $subamenityid = $this->coreFunctions->getfieldvalue($this->head, "subamenityid", "trno=?", [$trno]);
    }

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $amt,
      'cost' => number_format($computedata['amt'], $this->companysetup->getdecimal('price', $config['params']), '.', ''),
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'refx' => $refx,
      'linex' => $linex,
      'rem' => $rem,
      'palletid' => $palletid,
      'locid' => $locid,
      'expiry' => $expiry,
      'oqty' => $oqty,
    ];

    if ($systemtype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }

    switch ($companyid) {
      case 11: //summit
        $data['rem'] = $rem;
        break;
      case 10: //afti
        $data['projectid'] = $projectid;
        break;
    }

    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'FAMS':
        $data['rem'] = $rem;
        $data['consignee'] = $consignee;
        break;
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
              $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            }
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $computedata['ext'] . ' Uom:' . $uom);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = ['status' => true, 'msg' => 'Successfully updated.'];
      if ($this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]) != 1) {
        $return = ['status' => false, 'msg' => 'Update item failed'];
      }

      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
    }
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
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
    if ($data[0]->refx !== 0) {
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $loc = isset($config['params']['loc']) ? $config['params']['loc'] : '';

    $data = $this->othersClass->getlatestcostTS($config, $barcode, $client, $center, $trno, $loc);

    if (!empty($data['data'])) {
      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $data['data'][0]->docno = 'UOM';
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault = 1 where item.barcode=?", [$barcode]);
        $this->coreFunctions->LogConsole('Def' . $defuom);
        if ($defuom != "") {
          $data['data'][0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if ($data['data'][0]->amt != 0) {
              $data['data'][0]->amt = $data['data'][0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            } else {
              $data['data'][0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
            }
          }
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $data['data'][0]->docno = 'UOM';
          $data['data'][0]->amt = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
        }
      }
    }

    if (!empty($data['data'])) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data['data']];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function

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

    $print = $config['params']['dataparams']['print'];

    if ($companyid == 36 && $print == 'excel') { //rozlab
      $format = $config['params']['dataparams']['reporttype'];
      if ($format == '0') {
        $str = app($this->companysetup->getreportpath($config['params']))->reportplottingexcelPC($config, $data);
      } else {
        $str = app($this->companysetup->getreportpath($config['params']))->reportplottingexcelIR($config, $data);
      }
    } else {
      $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
